<?php

/**
 * OIDC kimlik jetonu (id_token) doğrulayıcı — sağlayıcıdan bağımsız.
 *
 * Google, Apple ve diğer OpenID Connect sağlayıcılarının verdiği RS256 imzalı JWT'yi
 * doğrular. Composer bağımlılığı yok: imza openssl_verify ile, anahtarlar sağlayıcının
 * JWKS adresinden (curl) alınır ve Cache-Control süresince dosyada önbelleklenir.
 *
 *   $claims = OidcVerifier::verify($jwt, [
 *       'jwks_uri' => 'https://www.googleapis.com/oauth2/v3/certs',
 *       'issuers'  => ['accounts.google.com', 'https://accounts.google.com'],
 *       'audience' => $clientId,          // string ya da dizi
 *       'nonce'    => $expectedNonce,     // isteğe bağlı
 *   ]);
 *   // geçersizse null; nedeni OidcVerifier::lastError()
 *
 * Denetlenenler: alg yalnız RS256 (none/HS* reddedilir), imza, iss, aud (+ çoklu aud'da
 * azp), exp / iat / nbf (leeway ile), nonce.
 */
final class OidcVerifier
{
    private static string $lastError = '';

    public static function lastError(): string
    {
        return self::$lastError;
    }

    public static function verify(string $jwt, array $opts): ?array
    {
        self::$lastError = '';

        $parts = explode('.', $jwt);
        if (count($parts) !== 3 || strlen($jwt) > 8192) {
            return self::fail('malformed');
        }
        [$h64, $p64, $s64] = $parts;

        $header  = json_decode((string)self::b64urlDecode($h64), true);
        $payload = json_decode((string)self::b64urlDecode($p64), true);
        $sig     = self::b64urlDecode($s64);
        if (!is_array($header) || !is_array($payload) || $sig === null || $sig === '') {
            return self::fail('malformed');
        }

        // Algoritma sabit: jetonun kendi söylediğine güvenilmez (alg=none / HS256 saldırısı).
        if (($header['alg'] ?? '') !== 'RS256') {
            return self::fail('alg');
        }

        $jwksUri = (string)($opts['jwks_uri'] ?? '');
        if ($jwksUri === '') {
            return self::fail('config');
        }

        $kid = (string)($header['kid'] ?? '');
        $pem = self::keyFor($jwksUri, $kid, $opts);
        if ($pem === null) {
            return self::fail('key');
        }

        $ok = openssl_verify($h64 . '.' . $p64, $sig, $pem, OPENSSL_ALGO_SHA256);
        if ($ok !== 1) {
            return self::fail('signature');
        }

        $now    = time();
        $leeway = (int)($opts['leeway'] ?? 60);

        $issuers = (array)($opts['issuers'] ?? []);
        if (!in_array($payload['iss'] ?? null, $issuers, true)) {
            return self::fail('iss');
        }

        $audiences = array_values(array_filter((array)($opts['audience'] ?? []), 'strlen'));
        $aud       = $payload['aud'] ?? null;
        $tokenAuds = is_array($aud) ? $aud : [$aud];
        if (empty($audiences) || !array_intersect($tokenAuds, $audiences)) {
            return self::fail('aud');
        }
        // Birden çok aud varsa jetonun kime verildiği (azp) da bizim olmalı.
        if (is_array($aud) && count($aud) > 1 && !in_array($payload['azp'] ?? null, $audiences, true)) {
            return self::fail('azp');
        }

        if (!isset($payload['exp']) || (int)$payload['exp'] < $now - $leeway) {
            return self::fail('expired');
        }
        if (isset($payload['iat']) && (int)$payload['iat'] > $now + $leeway) {
            return self::fail('iat');
        }
        if (isset($payload['nbf']) && (int)$payload['nbf'] > $now + $leeway) {
            return self::fail('nbf');
        }

        // nonce: string ya da kabul edilen değerler dizisi (Apple bazı istemcilerde
        // nonce'un SHA-256'sını gömer).
        if (array_key_exists('nonce', $opts) && $opts['nonce'] !== null) {
            $got = (string)($payload['nonce'] ?? '');
            $ok  = false;
            foreach ((array)$opts['nonce'] as $expected) {
                $expected = (string)$expected;
                if ($expected !== '' && $got !== '' && hash_equals($expected, $got)) {
                    $ok = true;
                    break;
                }
            }
            if (!$ok) {
                return self::fail('nonce');
            }
        }

        return $payload;
    }

    /**
     * Payload'u imzayı DOĞRULAMADAN okur. Yalnız doğrulama parametresini seçmek için
     * (ör. Microsoft'ta iss kiracıya göre değişir: tid önce okunur, sonra verify'da
     * beklenen iss olarak sınanır). Dönen değere asla güvenme.
     */
    public static function peek(string $jwt): ?array
    {
        $parts = explode('.', $jwt);
        if (count($parts) !== 3) return null;
        $payload = json_decode((string)self::b64urlDecode($parts[1]), true);
        return is_array($payload) ? $payload : null;
    }

    /** base64url çözer (Facebook signed_request için de kullanılır). */
    public static function b64url(string $s): ?string
    {
        return self::b64urlDecode($s);
    }

    /* ------------------------------------------------------------------ *
     *  JWKS
     * ------------------------------------------------------------------ */

    private static function keyFor(string $jwksUri, string $kid, array $opts): ?string
    {
        $file   = self::cacheFile($jwksUri, $opts);
        $cached = self::readCache($file);

        $key = $cached ? self::findKey($cached['keys'], $kid) : null;
        if ($key !== null && $cached['expires'] > time()) {
            return $key;
        }

        // Önbellek yok, süresi dolmuş ya da kid bulunamadı (anahtar dönmüş olabilir).
        // Tanınmayan kid ile gelen her istek sağlayıcıya gitmesin: taze önbellekte
        // bulunamayan kid için en çok 5 dakikada bir yeniden çekilir.
        $fresh = $cached && $cached['expires'] > time();
        if ($fresh && $key === null && (time() - $cached['fetched']) < 300) {
            return null;
        }

        $fetched = self::fetch($jwksUri);
        if ($fetched === null) {
            // Ağ hatası: süresi geçmiş de olsa elde olan anahtarla devam (sağlayıcı kesintisi).
            return $key;
        }
        self::writeCache($file, $fetched);

        return self::findKey($fetched['keys'], $kid);
    }

    private static function findKey(array $keys, string $kid): ?string
    {
        foreach ($keys as $jwk) {
            if (!is_array($jwk) || ($jwk['kty'] ?? '') !== 'RSA') continue;
            if (isset($jwk['use']) && $jwk['use'] !== 'sig') continue;
            if ($kid !== '' && ($jwk['kid'] ?? '') !== $kid) continue;
            $pem = self::jwkToPem($jwk);
            if ($pem !== null) return $pem;
        }
        return null;
    }

    private static function fetch(string $uri): ?array
    {
        if (!function_exists('curl_init')) {
            return null;
        }
        $headers = [];
        $ch = curl_init($uri);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 5,
            CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_PROTOCOLS      => CURLPROTO_HTTPS,
            CURLOPT_HEADERFUNCTION => function ($ch, $line) use (&$headers) {
                $p = explode(':', $line, 2);
                if (count($p) === 2) $headers[strtolower(trim($p[0]))] = trim($p[1]);
                return strlen($line);
            },
        ]);
        $body = curl_exec($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        // curl_close() çağrılmaz: PHP 8'den beri etkisiz, 8.5'te deprecated.

        if ($code !== 200 || !is_string($body)) {
            return null;
        }
        $json = json_decode($body, true);
        if (!is_array($json) || !isset($json['keys']) || !is_array($json['keys'])) {
            return null;
        }

        $ttl = 3600;
        if (preg_match('/max-age=(\d+)/', $headers['cache-control'] ?? '', $m)) {
            $ttl = (int)$m[1];
        }
        $ttl = max(300, min($ttl, 86400));

        return ['keys' => $json['keys'], 'expires' => time() + $ttl, 'fetched' => time()];
    }

    /**
     * Anahtar önbelleğinin yolu.
     *
     * 3.5.2: Varsayılan sys_get_temp_dir() DEĞİL. Paylaşımlı barındırmada
     * /tmp bütün kiracılarındır ve dosya adı tahmin edilebilirdi; başka bir
     * kiracı kendi anahtarını yazıp sahte id_token ile istediği hesaba
     * girebilirdi. Önbellek artık projenin files/cache/oidc dizininde
     * (0700, web'e kapalı) durur.
     */
    private static function cacheFile(string $uri, array $opts): string
    {
        $dir = (string)($opts['cache_dir'] ?? '');
        if ($dir === '') {
            $dir = (defined('VNL_ROOT') ? VNL_ROOT : dirname(__DIR__, 3)) . '/files/cache/oidc';
        }
        if (!is_dir($dir)) {
            @mkdir($dir, 0700, true);
        }
        return rtrim($dir, '/\\') . DIRECTORY_SEPARATOR . 'vnl-oidc-' . sha1($uri) . '.json';
    }

    /**
     * Önbellek dosyasına güvenilir mi? Başkası yazabiliyorsa (grup/herkes
     * yazma izni) ya da sahibi bu süreç değilse HAYIR: dosya yok sayılır ve
     * anahtarlar sağlayıcıdan yeniden çekilir.
     */
    private static function trustedCacheFile(string $file): bool
    {
        $perms = @fileperms($file);
        if ($perms === false || ($perms & 0022) !== 0) {
            return false;
        }
        if (function_exists('posix_geteuid')) {
            $owner = @fileowner($file);
            if ($owner === false || $owner !== posix_geteuid()) {
                return false;
            }
        }
        return true;
    }

    private static function readCache(string $file): ?array
    {
        if (!is_file($file) || !self::trustedCacheFile($file)) return null;
        $data = json_decode((string)@file_get_contents($file), true);
        if (!is_array($data) || !is_array($data['keys'] ?? null)) return null;
        return [
            'keys'    => $data['keys'],
            'expires' => (int)($data['expires'] ?? 0),
            'fetched' => (int)($data['fetched'] ?? 0),
        ];
    }

    private static function writeCache(string $file, array $data): void
    {
        $tmp = $file . '.' . bin2hex(random_bytes(4));
        if (@file_put_contents($tmp, json_encode($data)) !== false) {
            @chmod($tmp, 0600);
            @rename($tmp, $file);
        }
        @unlink($tmp);
    }

    /* ------------------------------------------------------------------ *
     *  JWK (n, e) → PEM
     * ------------------------------------------------------------------ */

    private static function jwkToPem(array $jwk): ?string
    {
        $n = self::b64urlDecode((string)($jwk['n'] ?? ''));
        $e = self::b64urlDecode((string)($jwk['e'] ?? ''));
        if (!$n || !$e) return null;

        $int = function (string $bytes): string {
            if (ord($bytes[0]) > 0x7f) $bytes = "\x00" . $bytes; // pozitif tamsayı
            return "\x02" . self::derLen(strlen($bytes)) . $bytes;
        };
        $rsaKey = $int($n) . $int($e);
        $rsaKey = "\x30" . self::derLen(strlen($rsaKey)) . $rsaKey;

        // AlgorithmIdentifier: rsaEncryption (1.2.840.113549.1.1.1), NULL
        $algo = "\x30\x0d\x06\x09\x2a\x86\x48\x86\xf7\x0d\x01\x01\x01\x05\x00";
        $bits = "\x03" . self::derLen(strlen($rsaKey) + 1) . "\x00" . $rsaKey;
        $spki = "\x30" . self::derLen(strlen($algo . $bits)) . $algo . $bits;

        return "-----BEGIN PUBLIC KEY-----\n"
            . chunk_split(base64_encode($spki), 64, "\n")
            . "-----END PUBLIC KEY-----\n";
    }

    private static function derLen(int $len): string
    {
        if ($len < 0x80) return chr($len);
        $out = ltrim(pack('N', $len), "\x00");
        return chr(0x80 | strlen($out)) . $out;
    }

    private static function b64urlDecode(string $s): ?string
    {
        if ($s === '' || preg_match('/[^A-Za-z0-9_\-]/', $s)) return null;
        $r = base64_decode(strtr($s, '-_', '+/') . str_repeat('=', (4 - strlen($s) % 4) % 4), true);
        return $r === false ? null : $r;
    }

    private static function fail(string $why): ?array
    {
        self::$lastError = $why;
        return null;
    }
}

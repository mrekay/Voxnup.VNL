<?php
/*!
 * ============================================================
 *  Voxnup Not Laravel (VNL) — TOTP
 *  RFC 6238 zaman tabanlı tek kullanımlık parola (2FA).
 *  Authors: Voxnup Authors
 * ============================================================
 */

/**
 * İki adımlı doğrulama (Google Authenticator, Aegis, 1Password ...).
 *
 *   $secret = Totp::generateSecret();                          // base32
 *   $uri    = Totp::uri($secret, $user->username, 'VNL Panel');  // QR kodu içeriği
 *   Totp::verify($secret, '123456');                           // bool
 *
 *   // Saklama: sır ŞİFRELİ tutulur (APP_ENCRYPTION_KEY)
 *   $user->totp_secret  = Totp::encryptSecret($secret);
 *   $user->totp_enabled = 1;
 *   Totp::decryptSecret($user->totp_secret);                   // ?string
 *
 * Parametreler RFC 6238 varsayılanıdır ve uygulamaların tamamıyla uyumludur:
 * HMAC-SHA1, 30 saniye, 6 hane. Saat kaymasına karşı ±1 adım (±30 sn) kabul
 * edilir. Karşılaştırma sabit zamanlıdır.
 *
 * Tekrar kullanım (aynı kodun pencere içinde ikinci kez girilmesi) için
 * match() eşleşen adımı döner; çağıran son adımı saklayıp daha küçük ya da
 * eşit bir adımı reddedebilir.
 */
class Totp
{
    private const ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    private const PERIOD   = 30;
    private const DIGITS   = 6;

    /** 160 bit rastgele sır, base32 (32 karakter, dolgusuz). */
    public static function generateSecret(int $bytes = 20): string
    {
        return self::base32Encode(random_bytes(max(10, $bytes)));
    }

    public static function verify(string $secret, string $code, int $window = 1): bool
    {
        return self::match($secret, $code, $window) !== null;
    }

    /**
     * Kod eşleşiyorsa eşleşen zaman adımını (unix / 30), değilse null döner.
     */
    public static function match(string $secret, string $code, int $window = 1, ?int $time = null): ?int
    {
        $code = preg_replace('/\s+/', '', $code) ?? '';

        if (!preg_match('/^\d{' . self::DIGITS . '}$/', $code)) {
            return null;
        }

        $key = self::base32Decode($secret);
        if ($key === null || $key === '') {
            return null;
        }

        $step    = intdiv($time ?? time(), self::PERIOD);
        $matched = null;

        /* Döngü erken bitmez: süre, kodun hangi adımda eşleştiğini sızdırmasın. */
        for ($i = -$window; $i <= $window; $i++) {
            if (hash_equals(self::codeAt($key, $step + $i), $code) && $matched === null) {
                $matched = $step + $i;
            }
        }

        return $matched;
    }

    /** Belirli bir zamandaki kod (test ve tanılama için). */
    public static function code(string $secret, ?int $time = null): string
    {
        $key = self::base32Decode($secret);

        if ($key === null || $key === '') {
            throw new InvalidArgumentException('Geçersiz TOTP sırrı (base32 bekleniyor).');
        }

        return self::codeAt($key, intdiv($time ?? time(), self::PERIOD));
    }

    /**
     * otpauth:// adresi — uygulamaya QR kod ya da bağlantı olarak verilir.
     */
    public static function uri(string $secret, string $label, string $issuer): string
    {
        return 'otpauth://totp/' . rawurlencode($issuer) . ':' . rawurlencode($label)
            . '?secret=' . rawurlencode($secret)
            . '&issuer=' . rawurlencode($issuer)
            . '&algorithm=SHA1&digits=' . self::DIGITS . '&period=' . self::PERIOD;
    }

    /** Sırrı veritabanına yazılacak biçime çevirir (AES-256-GCM). */
    public static function encryptSecret(string $secret): string
    {
        return EncryptionModule::safeEncrypt($secret);
    }

    /** Şifreli sırrı çözer; çözülemezse null. */
    public static function decryptSecret(?string $stored): ?string
    {
        if ($stored === null || $stored === '') {
            return null;
        }

        try {
            $plain = EncryptionModule::safeDecrypt($stored);
        } catch (Throwable $e) {
            error_log('[VNL][Totp] Sır çözülemedi: ' . $e->getMessage());

            return null;
        }

        return is_string($plain) && $plain !== '' ? $plain : null;
    }

    /* ---------------------------------------------------------------- */

    private static function codeAt(string $key, int $step): string
    {
        $counter = pack('N*', 0, $step);      // 64 bit, big-endian
        $hash    = hash_hmac('sha1', $counter, $key, true);
        $offset  = ord($hash[19]) & 0x0f;

        $value = ((ord($hash[$offset]) & 0x7f) << 24)
            | ((ord($hash[$offset + 1]) & 0xff) << 16)
            | ((ord($hash[$offset + 2]) & 0xff) << 8)
            | (ord($hash[$offset + 3]) & 0xff);

        return str_pad((string)($value % (10 ** self::DIGITS)), self::DIGITS, '0', STR_PAD_LEFT);
    }

    private static function base32Encode(string $binary): string
    {
        $bits = '';
        foreach (str_split($binary) as $char) {
            $bits .= str_pad(decbin(ord($char)), 8, '0', STR_PAD_LEFT);
        }

        $out = '';
        foreach (str_split($bits, 5) as $chunk) {
            $out .= self::ALPHABET[bindec(str_pad($chunk, 5, '0', STR_PAD_RIGHT))];
        }

        return $out;
    }

    private static function base32Decode(string $text): ?string
    {
        $text = strtoupper(preg_replace('/[\s=-]+/', '', $text) ?? '');

        if ($text === '' || strspn($text, self::ALPHABET) !== strlen($text)) {
            return null;
        }

        $bits = '';
        foreach (str_split($text) as $char) {
            $bits .= str_pad(decbin(strpos(self::ALPHABET, $char)), 5, '0', STR_PAD_LEFT);
        }

        $out = '';
        foreach (str_split($bits, 8) as $byte) {
            if (strlen($byte) === 8) {
                $out .= chr(bindec($byte));
            }
        }

        return $out;
    }
}

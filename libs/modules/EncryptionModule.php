<?php
/*!
 * ============================================================
 *  Voxnup Not Laravel (VNL) 3.0.0 — Şifreleme Modülü
 *  Parola hash'leme/doğrulama ve genel amaçlı veri şifreleme.
 *  Authors: Voxnup Authors
 * ============================================================
 */

class EncryptionModule
{
    /** VNL 2.x'ten kalan sha1(md5()) hash'i: 40 karakter onaltılık. */
    private const LEGACY_HASH_LENGTH = 40;

    /** Kimlik doğrulamalı şifreleme: kurcalanan veri çözülmez, sessizce bozulmaz. */
    private const CIPHER    = 'aes-256-gcm';
    private const KEY_BYTES = 32;
    private const IV_BYTES  = 12;   // GCM için standart nonce uzunluğu
    private const TAG_BYTES = 16;

    private static ?string $key = null;

    /**
     * Parolayı saklanacak biçime çevirir.
     *
     * Her çağrıda farklı bir tuz üretilir; bu yüzden sonucu sorguda
     * karşılaştırma ölçütü olarak KULLANMAYIN. Doğrulama için önce
     * kullanıcıyı çekip PasswordVerify() çağırın.
     */
    public static function PasswordHash(string $plain): string
    {
        return password_hash($plain, PASSWORD_DEFAULT);
    }

    /**
     * Parolayı saklanan hash ile karşılaştırır.
     *
     * VNL 2.x'ten kalan sha1(md5()) hash'lerini de tanır; böylece
     * geçişte kimsenin parolasını sıfırlaması gerekmez.
     */
    public static function PasswordVerify(string $plain, ?string $hash): bool
    {
        if ($hash === null || $hash === '') {
            return false;
        }

        if (self::IsLegacyPasswordHash($hash)) {
            foreach (self::legacyCandidates($plain) as $candidate) {
                if (hash_equals($hash, $candidate)) {
                    return true;
                }
            }

            return false;
        }

        return password_verify($plain, $hash);
    }

    /**
     * Saklanan hash yenilenmeli mi?
     *
     * Eski biçimdeyse ya da PHP'nin varsayılan maliyeti yükseldiyse true
     * döner. Başarılı girişten sonra çağırıp hash'i tazeleyin.
     */
    public static function PasswordNeedsRehash(?string $hash): bool
    {
        if ($hash === null || $hash === '') {
            return true;
        }

        if (self::IsLegacyPasswordHash($hash)) {
            return true;
        }

        return password_needs_rehash($hash, PASSWORD_DEFAULT);
    }

    /** Hash VNL 2.x biçiminde mi? password_hash çıktısı daima "$" ile başlar. */
    public static function IsLegacyPasswordHash(string $hash): bool
    {
        return strlen($hash) === self::LEGACY_HASH_LENGTH && ctype_xdigit($hash);
    }

    /**
     * Veriyi şifreler; URL güvenli bir metin döner.
     *
     * Anahtar .env'deki APP_ENCRYPTION_KEY'den okunur. Her çağrıda yeni
     * bir nonce üretildiği için aynı veri her seferinde farklı çıktı verir.
     * Çıktı biçimi: base64url(nonce | doğrulama etiketi | şifreli metin)
     */
    public static function safeEncrypt($data): string
    {
        $iv  = random_bytes(self::IV_BYTES);
        $tag = '';

        $cipher = openssl_encrypt(
            json_encode($data, JSON_UNESCAPED_UNICODE),
            self::CIPHER,
            self::key(),
            OPENSSL_RAW_DATA,
            $iv,
            $tag,
            '',
            self::TAG_BYTES
        );

        if ($cipher === false) {
            throw new RuntimeException('Şifreleme başarısız: ' . self::CIPHER . ' bu sunucuda desteklenmiyor olabilir.');
        }

        return self::base64UrlEncode($iv . $tag . $cipher);
    }

    /**
     * Şifreli metni çözer. Anahtar yanlışsa, veri kurcalanmışsa ya da
     * biçim tanınmıyorsa false döner — kısmi/bozuk veri asla dönmez.
     */
    public static function safeDecrypt($encrypted)
    {
        $text = (string)$encrypted;
        $blob = self::base64UrlDecode($text);

        if ($blob === false || strlen($blob) <= self::IV_BYTES + self::TAG_BYTES) {
            return false;
        }

        /* Dolgusuz base64'te son karakterin bir kısmı anlamsız bit taşır;
           birden fazla metin aynı bayta çözülür. Kanonik olmayan biçimi
           reddederek "tek veri = tek metin" güvencesini koruyoruz. */
        if (self::base64UrlEncode($blob) !== $text) {
            return false;
        }

        $plain = openssl_decrypt(
            substr($blob, self::IV_BYTES + self::TAG_BYTES),
            self::CIPHER,
            self::key(),
            OPENSSL_RAW_DATA,
            substr($blob, 0, self::IV_BYTES),
            substr($blob, self::IV_BYTES, self::TAG_BYTES)
        );

        if ($plain === false) {
            return false;
        }

        return json_decode($plain, true);
    }

    /* ---------------------------------------------------------------- */

    /**
     * Şifreleme anahtarı.
     *
     * .env'de base64 kodlanmış 32 bayt beklenir. Eksik ya da geçersizse
     * sessizce zayıf bir anahtara düşmek yerine hata verilir.
     */
    private static function key(): string
    {
        if (self::$key !== null) {
            return self::$key;
        }

        $howTo = 'Üretmek için: php -r "echo base64_encode(random_bytes(' . self::KEY_BYTES . '));"';
        $raw   = (string)VnlEnv::get('APP_ENCRYPTION_KEY', '');

        if ($raw === '') {
            throw new RuntimeException('APP_ENCRYPTION_KEY tanımlı değil. .env dosyanıza ekleyin. ' . $howTo);
        }

        $key = base64_decode($raw, true);

        if ($key === false || strlen($key) !== self::KEY_BYTES) {
            throw new RuntimeException(
                'APP_ENCRYPTION_KEY geçersiz: base64 kodlanmış ' . self::KEY_BYTES . ' bayt olmalı. ' . $howTo
            );
        }

        return self::$key = $key;
    }

    private static function base64UrlEncode(string $binary): string
    {
        return rtrim(strtr(base64_encode($binary), '+/', '-_'), '=');
    }

    private static function base64UrlDecode(string $text)
    {
        return base64_decode(strtr($text, '-_', '+/'), true);
    }

    /**
     * 2.x'te parola, Request sınıfı tarafından htmlspecialchars'tan
     * geçirildikten SONRA hash'leniyordu. Kaçış bayraklarının varsayılanı
     * PHP 8.1'de değiştiği için hangi sürümde kaydedildiği bilinemez;
     * üç olasılık da denenir.
     */
    private static function legacyCandidates(string $plain): array
    {
        $variants = array_unique([
            $plain,
            htmlspecialchars($plain, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            htmlspecialchars($plain, ENT_COMPAT, 'UTF-8'),
        ]);

        return array_map(static fn(string $v): string => sha1(md5($v)), $variants);
    }
}

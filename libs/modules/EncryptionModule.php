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

    public static function safeEncrypt($data)
    {
        return str_replace(array('/', '+'), array('_', '-'), @openssl_encrypt(json_encode($data), 'aes-128-cbc', '12?28a**038ss.y'));
    }

    public static function safeDecrypt($encrypted)
    {
        try {
            $result = json_decode(@openssl_decrypt(str_replace(array('_', '-'), array('/', '+'), $encrypted), 'aes-128-cbc', '12?28a**038ss.y'), true);
        } catch (Exception $e) {
            $result = false;
        }

        return $result;
    }

    /* ---------------------------------------------------------------- */

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

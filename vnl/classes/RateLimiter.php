<?php
/*!
 * ============================================================
 *  Voxnup Not Laravel (VNL) — Hız Sınırlayıcı
 *  Anahtar başına sayaç; kaba kuvvet denemelerini frenler.
 *  Authors: Voxnup Authors
 * ============================================================
 */

/**
 * Veritabanı tabanlı sabit pencere sayacı (tbl_vnl_rate_limits).
 *
 *   $key = 'login:ip:' . RateLimiter::ipKey(UserModule::CurrentIp());
 *
 *   // ÖNCE say, SONRA karar ver — kimlik bilgisini denetlemeden önce:
 *   if (!RateLimiter::hit($key, 5, 900)) {
 *       returnErrorMessage(lang('ERR_TOO_MANY_ATTEMPTS', ceil(RateLimiter::availableIn($key) / 60)), 429, 'VNL_RATE');
 *   }
 *   // ... denetim başarılı:
 *   RateLimiter::clear($key);        // ya da decrement(): yalnız bu denemeyi geri al
 *
 * 3.4.0 (gözden geçirme): kapı hit()'in DÖNÜŞÜNE bağlanır. "tooMany() ile
 * oku, başarısızlıkta hit() ile say" kalıbı kontrol-sonra-eylemdir: aynı
 * anda gelen N istek aynı düşük sayıyı okur ve hepsi bir tahmin hakkı
 * kazanır (PHP-FPM işçi sayısı kadar). hit() atomik artırmadan sonra
 * okur; k'ıncı eşzamanlı istek en az k görür, pencere başına en fazla
 * $maxAttempts istek geçer. tooMany() yalnız ucuz bir ön kontroldür,
 * tek kapı olamaz.
 *
 * Anahtarın kendisi saklanmaz, SHA-256 özeti saklanır (kullanıcı adları ve
 * IP'ler tabloda düz durmasın). Sayma tek bir atomik INSERT … ON DUPLICATE
 * KEY UPDATE ile yapılır: paralel istekler sayacı kaçıramaz. Pencere
 * dolunca aynı satır yeniden kullanılır; süresi çoktan geçmiş satırlar
 * ara sıra temizlenir, tablo büyümez.
 *
 * IP anahtarı için ipKey(): IPv6'da tek bir makine bütün bir /64'e sahip
 * olduğundan adres /64 önekine indirgenir; IPv4 (ve ::ffff: ile gelen
 * IPv4) tam adresle sayılır.
 *
 * Zaman veritabanının NOW()'ıdır — PHP ile MySQL saat dilimi farklı olsa da
 * pencere tutarlı kalır.
 *
 * Tablo yoksa (migration çalıştırılmadıysa) istisna yükselir: giriş gibi
 * güvenlik kapılarında "sınırlayıcı çalışmıyor, geç" demek yerine istek
 * düşer ve sebep günlüğe yazılır.
 */
class RateLimiter
{
    public const TABLE = 'tbl_vnl_rate_limits';

    /**
     * Denemeyi sayar. Sayımdan SONRA hâlâ sınırın içindeyse true döner.
     */
    public static function hit(string $key, int $maxAttempts, int $decaySeconds): bool
    {
        $decaySeconds = max(1, $decaySeconds);
        $table        = self::table();

        Database::connection()->statement(
            "INSERT INTO `{$table}` (`key_hash`, `hits`, `reset_at`)
             VALUES (?, 1, DATE_ADD(NOW(), INTERVAL ? SECOND))
             ON DUPLICATE KEY UPDATE
                `hits`     = IF(`reset_at` < NOW(), 1, `hits` + 1),
                `reset_at` = IF(`reset_at` < NOW(), DATE_ADD(NOW(), INTERVAL ? SECOND), `reset_at`)",
            [self::hash($key), $decaySeconds, $decaySeconds]
        );

        /* Ara sıra (~%1) süresi bir günden fazla geçmiş satırları sil. */
        if (random_int(1, 100) === 1) {
            self::purge();
        }

        return self::attempts($key) <= $maxAttempts;
    }

    /**
     * Pencere içindeki deneme sayısı sınıra ulaştı mı? (sayım yapmaz)
     *
     * Yalnız ön kontrol ya da gösterim içindir; güvenlik kapısı hit()'in
     * dönüşüdür (bkz. sınıf açıklaması — eşzamanlı istekler).
     */
    public static function tooMany(string $key, int $maxAttempts): bool
    {
        return self::attempts($key) >= $maxAttempts;
    }

    /** Geçerli penceredeki deneme sayısı; pencere dolmuşsa 0. */
    public static function attempts(string $key): int
    {
        $row = Database::table(self::TABLE)
            ->where('key_hash', self::hash($key))
            ->whereRaw('`reset_at` >= NOW()')
            ->first(['hits']);

        return $row ? (int)$row->hits : 0;
    }

    /** Pencerenin kapanmasına kalan saniye (sınır yoksa 0). */
    public static function availableIn(string $key): int
    {
        $row = Database::table(self::TABLE)
            ->where('key_hash', self::hash($key))
            ->selectRaw('GREATEST(0, TIMESTAMPDIFF(SECOND, NOW(), `reset_at`)) AS `wait`')
            ->first();

        return $row ? (int)$row->wait : 0;
    }

    /** Sayacı sıfırlar (ör. başarılı girişten sonra). */
    public static function clear(string $key): void
    {
        Database::table(self::TABLE)->where('key_hash', self::hash($key))->delete();
    }

    /**
     * Bu penceredeki bir sayımı geri alır (sıfırın altına inmez).
     *
     * hit() denemeden ÖNCE çağrıldığı için başarılı deneme de sayılır;
     * paylaşılan bir anahtarda (ör. aynı NAT arkasındaki kullanıcıların IP
     * anahtarı) başarılı denemeyi bütçeden düşmek için kullanılır.
     */
    public static function decrement(string $key): void
    {
        Database::table(self::TABLE)
            ->where('key_hash', self::hash($key))
            ->whereRaw('`reset_at` >= NOW()')
            ->update(['hits' => Database::raw('IF(`hits` > 0, `hits` - 1, 0)')]);
    }

    /**
     * Hız sınırı için istemci adresinin anahtar biçimi.
     *
     *   RateLimiter::ipKey('203.0.113.7')            // '203.0.113.7'
     *   RateLimiter::ipKey('::ffff:203.0.113.7')     // '203.0.113.7' (IPv4-mapped)
     *   RateLimiter::ipKey('2001:db8:1:2:a:b:c:d')   // 'ip6:20010db800010002' (/64)
     *
     * IPv6'da tek bir sunucuya genellikle bütün bir /64 (2^64 adres) verilir;
     * tam adresle saymak saldırgana adres başına ayrı bir bütçe verirdi.
     * IPv4-mapped adresler (::ffff:a.b.c.d, çift yığınlı dinleyicide
     * REMOTE_ADDR böyle gelir) gömülü IPv4'e açılır: /64'e indirgenseler
     * bütün IPv4 istemcileri tek bir kovayı paylaşır ve biri herkesi
     * kilitleyebilirdi. Çözülemeyen değer olduğu gibi kullanılır.
     *
     * Adresin gerçek istemci olması sunucu yapılandırmasına bağlıdır:
     * vekil (nginx → Apache) arkasında mod_remoteip ya da eşdeğeri yoksa
     * her istek vekilin adresinden gelir ve tek kovayı paylaşır.
     */
    public static function ipKey(string $ip): string
    {
        $ip     = trim($ip);
        $packed = $ip !== '' ? @inet_pton($ip) : false;

        if ($packed === false) {
            return $ip === '' ? 'unknown' : $ip;
        }

        if (strlen($packed) === 4) {
            return (string)inet_ntop($packed);
        }

        if (strlen($packed) === 16
            && substr($packed, 0, 10) === str_repeat("\0", 10)
            && substr($packed, 10, 2) === "\xff\xff") {
            return (string)inet_ntop(substr($packed, 12, 4));
        }

        return 'ip6:' . bin2hex(substr($packed, 0, 8));
    }

    /** Süresi bir günden uzun süre önce dolmuş satırları siler. */
    public static function purge(): int
    {
        return Database::table(self::TABLE)
            ->whereRaw('`reset_at` < DATE_SUB(NOW(), INTERVAL 1 DAY)')
            ->delete();
    }

    /* ---------------------------------------------------------------- */

    private static function hash(string $key): string
    {
        return hash('sha256', $key);
    }

    /** Ham SQL için önekli tablo adı (Eloquent modelleriyle aynı kural). */
    private static function table(): string
    {
        return Database::connection()->getTablePrefix() . self::TABLE;
    }
}

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
 *   $key = 'login:user:' . mb_strtolower($username);
 *
 *   if (RateLimiter::tooMany($key, 5)) {
 *       returnErrorMessage(lang('ERR_TOO_MANY_ATTEMPTS', ceil(RateLimiter::availableIn($key) / 60)), 429, 'VNL_RATE');
 *   }
 *   // ... başarısız deneme:
 *   RateLimiter::hit($key, 5, 900);
 *   // ... başarılı:
 *   RateLimiter::clear($key);
 *
 * Anahtarın kendisi saklanmaz, SHA-256 özeti saklanır (kullanıcı adları ve
 * IP'ler tabloda düz durmasın). Sayma tek bir atomik INSERT … ON DUPLICATE
 * KEY UPDATE ile yapılır: paralel istekler sayacı kaçıramaz. Pencere
 * dolunca aynı satır yeniden kullanılır; süresi çoktan geçmiş satırlar
 * ara sıra temizlenir, tablo büyümez.
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

    /** Pencere içindeki deneme sayısı sınıra ulaştı mı? (sayım yapmaz) */
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

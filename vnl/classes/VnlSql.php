<?php
/*!
 * ============================================================
 *  Voxnup Not Laravel (VNL) — Sürücüye Göre SQL Parçaları
 *  Çekirdek sınıfların ham SQL'i için küçük bir lehçe katmanı.
 *  Authors: Voxnup Authors
 * ============================================================
 */

/**
 * Çekirdeğin ham SQL yazdığı birkaç yer (RateLimiter, Queue, VnlJob,
 * queue:work) veritabanı saatine ve tanımlayıcı tırnağına ihtiyaç duyar.
 * Sorgu kurucusu ikisini de sürücüden bağımsız vermez; bu sınıf verir.
 *
 *   VnlSql::now()            NOW() | LOCALTIMESTAMP(0) | CAST(GETDATE() AS DATETIME2(0)) | datetime('now')
 *   VnlSql::nowPlus(30)      şimdi + 30 sn
 *   VnlSql::nowMinus(86400)  şimdi − 1 gün
 *   VnlSql::wrap('reset_at') `reset_at` | "reset_at" | [reset_at]
 *
 * Saniyeler tamsayıya çevrilip SQL'e gömülür (bağlanmaz): PostgreSQL'de
 * "? * INTERVAL" parametre türünü çıkaramaz, ve değer hiçbir zaman
 * dışarıdan gelen bir metin değildir.
 *
 * Desteklenen lehçeler: mysql (mariadb dahil), pgsql, sqlsrv, sqlite.
 * MySQL çıktısı 4.1.0 öncesinde sınıfların yazdığı SQL'in aynısıdır.
 *
 * Zaman, veritabanının kendi saatidir (PHP ile veritabanı saat dilimi
 * farklı olabilir; ikisi karıştırılmaz). SQLite'ta datetime('now') UTC'dir.
 */
final class VnlSql
{
    /** Bağlantının lehçesi: mysql | pgsql | sqlsrv | sqlite. */
    public static function dialect(): string
    {
        $driver = Database::connection()->getDriverName();

        return $driver === 'mariadb' ? 'mysql' : $driver;
    }

    /**
     * Veritabanı saatiyle "şimdi", saniyeye yuvarlanmış.
     *
     * SQL Server'da GETDATE() milisaniye taşır, çekirdek sütunları ise
     * DATETIME2(0)'dır: yazılan değer en yakın saniyeye YUKARI da
     * yuvarlanabilir. Karşılaştırmanın öbür yanı yuvarlanmamış GETDATE()
     * olsaydı, az önce eklenen iş "henüz zamanı gelmemiş" görünürdü. İki
     * yan da aynı biçimde yuvarlanır (PostgreSQL'de LOCALTIMESTAMP(0) gibi).
     */
    public static function now(): string
    {
        return match (self::dialect()) {
            'pgsql'  => 'LOCALTIMESTAMP(0)',
            'sqlsrv' => 'CAST(GETDATE() AS DATETIME2(0))',
            'sqlite' => "datetime('now')",
            default  => 'NOW()',
        };
    }

    /** Şimdi + $seconds saniye. */
    public static function nowPlus(int $seconds): string
    {
        return match (self::dialect()) {
            'pgsql'  => "(LOCALTIMESTAMP(0) + INTERVAL '{$seconds} seconds')",
            'sqlsrv' => "DATEADD(SECOND, {$seconds}, CAST(GETDATE() AS DATETIME2(0)))",
            'sqlite' => "datetime('now', '" . ($seconds >= 0 ? '+' : '') . "{$seconds} seconds')",
            default  => "DATE_ADD(NOW(), INTERVAL {$seconds} SECOND)",
        };
    }

    /** Şimdi − $seconds saniye. */
    public static function nowMinus(int $seconds): string
    {
        return self::dialect() === 'mysql'
            ? "DATE_SUB(NOW(), INTERVAL {$seconds} SECOND)"
            : self::nowPlus(-$seconds);
    }

    /** $column'daki ana kalan saniye; geçmişse 0. */
    public static function secondsUntil(string $column): string
    {
        $c = self::wrap($column);

        return match (self::dialect()) {
            'pgsql'  => "GREATEST(0, FLOOR(EXTRACT(EPOCH FROM ({$c} - LOCALTIMESTAMP(0)))))",
            'sqlsrv' => "CASE WHEN {$c} > GETDATE() THEN DATEDIFF(SECOND, GETDATE(), {$c}) ELSE 0 END",
            'sqlite' => "MAX(0, CAST(strftime('%s', {$c}) AS INTEGER) - CAST(strftime('%s', 'now') AS INTEGER))",
            default  => "GREATEST(0, TIMESTAMPDIFF(SECOND, NOW(), {$c}))",
        };
    }

    /** Tırnaklı sütun adı. */
    public static function wrap(string $column): string
    {
        return Database::connection()->getQueryGrammar()->wrap($column);
    }

    /** Önekli ve tırnaklı tablo adı (ham SQL için). */
    public static function table(string $table): string
    {
        return Database::connection()->getQueryGrammar()->wrapTable($table);
    }
}

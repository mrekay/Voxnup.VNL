-- =============================================================================
-- 0003 — Hız sınırlayıcı sayaçları (VNL 3.4.0)
--
-- RateLimiter::hit/tooMany/availableIn/clear. Anahtarın kendisi değil
-- SHA-256 özeti saklanır (kullanıcı adları ve IP'ler düz durmasın).
-- Pencere dolan satır yeniden kullanılır; tablo büyümez.
-- =============================================================================

CREATE TABLE IF NOT EXISTS `tbl_vnl_rate_limits` (
    `key_hash` CHAR(64)     NOT NULL,          -- sha256(anahtar), onaltılık
    `hits`     INT UNSIGNED NOT NULL,
    `reset_at` DATETIME     NOT NULL,          -- pencerenin kapandığı an (veritabanı saati)
    PRIMARY KEY (`key_hash`),
    KEY `ix_rate_limits_reset` (`reset_at`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci;

-- =============================================================================
-- 0005 — İş kuyruğu (VNL 3.4.0)
--
-- Queue::push() yazar, php vnl.php queue:work okur. Durumlar:
-- queued → running → done | failed | canceled.
-- Zaman sütunları veritabanının NOW()'ı ile yazılır (işçi NOW() ile
-- karşılaştırır; PHP ile MySQL saat dilimi farkı gecikmeyi kaydırmasın).
-- =============================================================================

CREATE TABLE IF NOT EXISTS `tbl_vnl_jobs` (
    `id`           BIGINT UNSIGNED  NOT NULL AUTO_INCREMENT,
    `queue`        VARCHAR(50)      NOT NULL DEFAULT 'default',
    `type`         VARCHAR(100)     NOT NULL,                  -- jobs/<type>.php
    `payload`      JSON             NOT NULL,
    `status`       VARCHAR(16)      NOT NULL DEFAULT 'queued', -- queued|running|done|failed|canceled
    `attempts`     TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `max_attempts` TINYINT UNSIGNED NOT NULL DEFAULT 1,
    `progress`     TINYINT UNSIGNED NOT NULL DEFAULT 0,        -- 0–100
    `output`       MEDIUMTEXT       DEFAULT NULL,              -- VnlJob::log() satırları
    `error`        TEXT             DEFAULT NULL,
    `reserved_by`  CHAR(32)         DEFAULT NULL,              -- ayırma jetonu
    `available_at` DATETIME         NOT NULL,
    `reserved_at`  DATETIME         DEFAULT NULL,
    `finished_at`  DATETIME         DEFAULT NULL,
    `created_by`   INT UNSIGNED     DEFAULT NULL,
    `created_at`   DATETIME         NOT NULL,
    `updated_at`   DATETIME         DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `ix_jobs_pick` (`queue`, `status`, `available_at`),
    KEY `ix_jobs_reserved` (`reserved_by`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci;

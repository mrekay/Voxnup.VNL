-- =============================================================================
-- 0006 — API jetonları (VNL 3.4.0)
--
-- Authorization: Bearer <jeton> ile /app/* çağrısı (oturumsuz, CSRF'siz).
-- Jetonun kendisi değil SHA-256 özeti saklanır; düz jeton yalnız
-- üretildiği anda bir kez gösterilir (php vnl.php token:create).
-- =============================================================================

CREATE TABLE IF NOT EXISTS `tbl_vnl_api_tokens` (
    `id`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id`      INT UNSIGNED    NOT NULL,
    `name`         VARCHAR(100)    NOT NULL,
    `token_hash`   CHAR(64)        NOT NULL,
    `abilities`    JSON            DEFAULT NULL,      -- ["*"] | ["domains", "jobs:status"]
    `last_used_at` DATETIME        DEFAULT NULL,
    `expires_at`   DATETIME        DEFAULT NULL,
    `created_at`   DATETIME        NOT NULL,
    `revoked_at`   DATETIME        DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_api_token_hash` (`token_hash`),
    KEY `ix_api_token_user` (`user_id`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci;

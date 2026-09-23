-- =============================================================================
-- 0004 — Denetim kaydı (VNL 3.4.0)
--
-- Audit::log('domain.create', 'domain', 7, [...]) — kim, ne zaman, neyi.
-- Satırlar güncellenmez ve silinmez (yalnız eklenir); saklama süresi
-- projenin kararıdır (ör. zamanlanmış bir temizlik komutu).
-- =============================================================================

CREATE TABLE IF NOT EXISTS `tbl_vnl_audit_log` (
    `id`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id`      INT UNSIGNED    DEFAULT NULL,      -- işlemi yapan (konsolda Audit::actingAs)
    `action`       VARCHAR(100)    NOT NULL,          -- auth.login, domain.create ...
    `subject_type` VARCHAR(100)    DEFAULT NULL,      -- user, domain ...
    `subject_id`   VARCHAR(64)     DEFAULT NULL,
    `data`         JSON            DEFAULT NULL,      -- password/token/secret anahtarları ayıklanmış
    `ip`           VARCHAR(45)     DEFAULT NULL,
    `user_agent`   VARCHAR(255)    DEFAULT NULL,
    `created_at`   DATETIME        NOT NULL,
    PRIMARY KEY (`id`),
    KEY `ix_audit_user` (`user_id`),
    KEY `ix_audit_action` (`action`),
    KEY `ix_audit_subject` (`subject_type`, `subject_id`),
    KEY `ix_audit_created` (`created_at`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci;

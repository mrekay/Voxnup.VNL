-- =============================================================================
-- 0002 — Kullanıcılar ve kullanıcı yetkileri (VNL 3.4.0)
--
-- 3.3'e kadar hiçbir migration tbl_users ve tbl_user_permissions'ı
-- oluşturmuyordu; kod ise ikisine de dayanıyordu. Sıfırdan kurulumda
-- giriş hiç çalışmıyordu.
--
-- Hem sıfırdan hem mevcut kurulumda çalışır:
--   - tablolar CREATE TABLE IF NOT EXISTS ile açılır,
--   - mevcut tablolarda eksik sütunlar KOŞULLU eklenir (0001'in kalıbı:
--     MySQL'de ADD COLUMN IF NOT EXISTS yoktur).
--
-- İlk yönetici SQL ile TOHUMLANMAZ (herkesin bildiği parola her kurulumda
-- açık kapı olurdu). Komut satırından:
--   php vnl.php user:create --username=admin --super
-- =============================================================================


-- -----------------------------------------------------------------------------
-- 1) tbl_users
--
-- perm_id: rol. 1 = süper yönetici (helpers/permissions.php her yetkiyi verir).
-- is_active: 0 pasif — giriş reddedilir, açık oturum bir sonraki istekte kapanır.
-- totp_*: iki adımlı doğrulama (sır EncryptionModule ile şifreli saklanır).
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tbl_users` (
    `id`               INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    `username`         VARCHAR(64)   NOT NULL,
    `password`         VARCHAR(255)  NOT NULL,
    `name`             VARCHAR(100)  NOT NULL DEFAULT '',
    `surname`          VARCHAR(100)  NOT NULL DEFAULT '',
    `email`            VARCHAR(190)  DEFAULT NULL,
    `perm_id`          INT UNSIGNED  DEFAULT NULL,
    `is_active`        TINYINT(1)    NOT NULL DEFAULT 1,
    `last_activity_at` DATETIME      DEFAULT NULL,
    `last_login_at`    DATETIME      DEFAULT NULL,
    `last_login_ip`    VARCHAR(45)   DEFAULT NULL,
    `totp_secret`      VARCHAR(255)  DEFAULT NULL,
    `totp_enabled`     TINYINT(1)    NOT NULL DEFAULT 0,
    `created_at`       DATETIME      DEFAULT NULL,
    `updated_at`       DATETIME      DEFAULT NULL,
    `deleted_at`       DATETIME      DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_users_username` (`username`),
    KEY `ix_users_perm` (`perm_id`),
    KEY `ix_users_deleted` (`deleted_at`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci;


-- -----------------------------------------------------------------------------
-- 2) Mevcut tbl_users — eksik sütunlar
-- -----------------------------------------------------------------------------
SET @vnl_alter := IF((SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tbl_users' AND COLUMN_NAME = 'name') = 0,
    'ALTER TABLE `tbl_users` ADD COLUMN `name` VARCHAR(100) NOT NULL DEFAULT ''''', 'DO 0');
PREPARE vnl_stmt FROM @vnl_alter; EXECUTE vnl_stmt; DEALLOCATE PREPARE vnl_stmt;

SET @vnl_alter := IF((SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tbl_users' AND COLUMN_NAME = 'surname') = 0,
    'ALTER TABLE `tbl_users` ADD COLUMN `surname` VARCHAR(100) NOT NULL DEFAULT ''''', 'DO 0');
PREPARE vnl_stmt FROM @vnl_alter; EXECUTE vnl_stmt; DEALLOCATE PREPARE vnl_stmt;

SET @vnl_alter := IF((SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tbl_users' AND COLUMN_NAME = 'email') = 0,
    'ALTER TABLE `tbl_users` ADD COLUMN `email` VARCHAR(190) DEFAULT NULL', 'DO 0');
PREPARE vnl_stmt FROM @vnl_alter; EXECUTE vnl_stmt; DEALLOCATE PREPARE vnl_stmt;

SET @vnl_alter := IF((SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tbl_users' AND COLUMN_NAME = 'perm_id') = 0,
    'ALTER TABLE `tbl_users` ADD COLUMN `perm_id` INT UNSIGNED DEFAULT NULL', 'DO 0');
PREPARE vnl_stmt FROM @vnl_alter; EXECUTE vnl_stmt; DEALLOCATE PREPARE vnl_stmt;

SET @vnl_alter := IF((SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tbl_users' AND COLUMN_NAME = 'is_active') = 0,
    'ALTER TABLE `tbl_users` ADD COLUMN `is_active` TINYINT(1) NOT NULL DEFAULT 1', 'DO 0');
PREPARE vnl_stmt FROM @vnl_alter; EXECUTE vnl_stmt; DEALLOCATE PREPARE vnl_stmt;

SET @vnl_alter := IF((SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tbl_users' AND COLUMN_NAME = 'last_activity_at') = 0,
    'ALTER TABLE `tbl_users` ADD COLUMN `last_activity_at` DATETIME DEFAULT NULL', 'DO 0');
PREPARE vnl_stmt FROM @vnl_alter; EXECUTE vnl_stmt; DEALLOCATE PREPARE vnl_stmt;

SET @vnl_alter := IF((SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tbl_users' AND COLUMN_NAME = 'last_login_at') = 0,
    'ALTER TABLE `tbl_users` ADD COLUMN `last_login_at` DATETIME DEFAULT NULL', 'DO 0');
PREPARE vnl_stmt FROM @vnl_alter; EXECUTE vnl_stmt; DEALLOCATE PREPARE vnl_stmt;

SET @vnl_alter := IF((SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tbl_users' AND COLUMN_NAME = 'last_login_ip') = 0,
    'ALTER TABLE `tbl_users` ADD COLUMN `last_login_ip` VARCHAR(45) DEFAULT NULL', 'DO 0');
PREPARE vnl_stmt FROM @vnl_alter; EXECUTE vnl_stmt; DEALLOCATE PREPARE vnl_stmt;

SET @vnl_alter := IF((SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tbl_users' AND COLUMN_NAME = 'totp_secret') = 0,
    'ALTER TABLE `tbl_users` ADD COLUMN `totp_secret` VARCHAR(255) DEFAULT NULL', 'DO 0');
PREPARE vnl_stmt FROM @vnl_alter; EXECUTE vnl_stmt; DEALLOCATE PREPARE vnl_stmt;

SET @vnl_alter := IF((SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tbl_users' AND COLUMN_NAME = 'totp_enabled') = 0,
    'ALTER TABLE `tbl_users` ADD COLUMN `totp_enabled` TINYINT(1) NOT NULL DEFAULT 0', 'DO 0');
PREPARE vnl_stmt FROM @vnl_alter; EXECUTE vnl_stmt; DEALLOCATE PREPARE vnl_stmt;

SET @vnl_alter := IF((SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tbl_users' AND COLUMN_NAME = 'created_at') = 0,
    'ALTER TABLE `tbl_users` ADD COLUMN `created_at` DATETIME DEFAULT NULL', 'DO 0');
PREPARE vnl_stmt FROM @vnl_alter; EXECUTE vnl_stmt; DEALLOCATE PREPARE vnl_stmt;

SET @vnl_alter := IF((SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tbl_users' AND COLUMN_NAME = 'updated_at') = 0,
    'ALTER TABLE `tbl_users` ADD COLUMN `updated_at` DATETIME DEFAULT NULL', 'DO 0');
PREPARE vnl_stmt FROM @vnl_alter; EXECUTE vnl_stmt; DEALLOCATE PREPARE vnl_stmt;

SET @vnl_alter := IF((SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tbl_users' AND COLUMN_NAME = 'deleted_at') = 0,
    'ALTER TABLE `tbl_users` ADD COLUMN `deleted_at` DATETIME DEFAULT NULL', 'DO 0');
PREPARE vnl_stmt FROM @vnl_alter; EXECUTE vnl_stmt; DEALLOCATE PREPARE vnl_stmt;


-- -----------------------------------------------------------------------------
-- 3) tbl_permissions — zaman damgaları
--
-- Permission modeli Eloquent zaman damgalarını kullanır; sütunlar olmadan
-- Permission::create() "Unknown column 'updated_at'" ile düşüyordu.
-- (Tablo 0001'de oluşturulur.)
-- -----------------------------------------------------------------------------
SET @vnl_alter := IF((SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tbl_permissions' AND COLUMN_NAME = 'created_at') = 0,
    'ALTER TABLE `tbl_permissions` ADD COLUMN `created_at` DATETIME DEFAULT NULL', 'DO 0');
PREPARE vnl_stmt FROM @vnl_alter; EXECUTE vnl_stmt; DEALLOCATE PREPARE vnl_stmt;

SET @vnl_alter := IF((SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tbl_permissions' AND COLUMN_NAME = 'updated_at') = 0,
    'ALTER TABLE `tbl_permissions` ADD COLUMN `updated_at` DATETIME DEFAULT NULL', 'DO 0');
PREPARE vnl_stmt FROM @vnl_alter; EXECUTE vnl_stmt; DEALLOCATE PREPARE vnl_stmt;


-- -----------------------------------------------------------------------------
-- 4) tbl_user_permissions — kullanıcı × yetki kodu × yetenek
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tbl_user_permissions` (
    `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id`       INT UNSIGNED NOT NULL,
    `permission_id` INT UNSIGNED NOT NULL,
    `can_view`      TINYINT(1)   NOT NULL DEFAULT 0,
    `can_insert`    TINYINT(1)   NOT NULL DEFAULT 0,
    `can_edit`      TINYINT(1)   NOT NULL DEFAULT 0,
    `can_delete`    TINYINT(1)   NOT NULL DEFAULT 0,
    `created_at`    DATETIME     DEFAULT NULL,
    `updated_at`    DATETIME     DEFAULT NULL,
    `deleted_at`    DATETIME     DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_user_permission` (`user_id`, `permission_id`),
    KEY `ix_user_permission_permission` (`permission_id`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci;

SET @vnl_alter := IF((SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tbl_user_permissions' AND COLUMN_NAME = 'created_at') = 0,
    'ALTER TABLE `tbl_user_permissions` ADD COLUMN `created_at` DATETIME DEFAULT NULL', 'DO 0');
PREPARE vnl_stmt FROM @vnl_alter; EXECUTE vnl_stmt; DEALLOCATE PREPARE vnl_stmt;

SET @vnl_alter := IF((SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tbl_user_permissions' AND COLUMN_NAME = 'updated_at') = 0,
    'ALTER TABLE `tbl_user_permissions` ADD COLUMN `updated_at` DATETIME DEFAULT NULL', 'DO 0');
PREPARE vnl_stmt FROM @vnl_alter; EXECUTE vnl_stmt; DEALLOCATE PREPARE vnl_stmt;

SET @vnl_alter := IF((SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tbl_user_permissions' AND COLUMN_NAME = 'deleted_at') = 0,
    'ALTER TABLE `tbl_user_permissions` ADD COLUMN `deleted_at` DATETIME DEFAULT NULL', 'DO 0');
PREPARE vnl_stmt FROM @vnl_alter; EXECUTE vnl_stmt; DEALLOCATE PREPARE vnl_stmt;


-- -----------------------------------------------------------------------------
-- 5) Çekirdeğin kendi yetki kodu: kullanıcı yönetimi (actions/action_users.php)
-- -----------------------------------------------------------------------------
INSERT IGNORE INTO `tbl_permissions` (`name`, `code`, `created_at`, `updated_at`)
VALUES ('Kullanıcılar', 'Users', NOW(), NOW());

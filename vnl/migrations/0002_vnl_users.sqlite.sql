-- =============================================================================
-- 0002 — Kullanıcılar ve kullanıcı yetkileri (SQLite hâli, VNL 4.1.0)
--
-- Tablolar yoksa açılır. SQLite'ta ADD COLUMN IF NOT EXISTS olmadığı için
-- projenin ÖNCEDEN açtığı tbl_users / tbl_permissions / tbl_user_permissions
-- tablolarına eksik sütun eklenmez: öyle bir kurulumda sütunları kendi
-- migration'ınızla ekleyin (listesi 0002_vnl_users.sql'de).
--
-- İlk yönetici SQL ile TOHUMLANMAZ:
--   php vnl.php user:create --username=admin --super
-- =============================================================================

CREATE TABLE IF NOT EXISTS tbl_users (
    id               INTEGER PRIMARY KEY AUTOINCREMENT,
    username         VARCHAR(64)  NOT NULL COLLATE NOCASE,   -- MySQL _ci gibi: 'Admin' = 'admin'
    password         VARCHAR(255) NOT NULL,
    name             VARCHAR(100) NOT NULL DEFAULT '',
    surname          VARCHAR(100) NOT NULL DEFAULT '',
    email            VARCHAR(190) DEFAULT NULL,
    perm_id          INTEGER      DEFAULT NULL,
    is_active        TINYINT(1)   NOT NULL DEFAULT 1,
    last_activity_at DATETIME     DEFAULT NULL,
    last_login_at    DATETIME     DEFAULT NULL,
    last_login_ip    VARCHAR(45)  DEFAULT NULL,
    totp_secret      VARCHAR(255) DEFAULT NULL,
    totp_enabled     TINYINT(1)   NOT NULL DEFAULT 0,
    totp_last_step   BIGINT       DEFAULT NULL,   -- MySQL'de 0007 ekler
    created_at       DATETIME     DEFAULT NULL,
    updated_at       DATETIME     DEFAULT NULL,
    deleted_at       DATETIME     DEFAULT NULL
);
CREATE UNIQUE INDEX IF NOT EXISTS uq_users_username ON tbl_users (username);
CREATE INDEX IF NOT EXISTS ix_users_perm ON tbl_users (perm_id);
CREATE INDEX IF NOT EXISTS ix_users_deleted ON tbl_users (deleted_at);

-- tbl_permissions zaman damgaları: SQLite hâlinde 0001 tabloyu onlarla açar.

CREATE TABLE IF NOT EXISTS tbl_user_permissions (
    id            INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id       INTEGER    NOT NULL,
    permission_id INTEGER    NOT NULL,
    can_view      TINYINT(1) NOT NULL DEFAULT 0,
    can_insert    TINYINT(1) NOT NULL DEFAULT 0,
    can_edit      TINYINT(1) NOT NULL DEFAULT 0,
    can_delete    TINYINT(1) NOT NULL DEFAULT 0,
    created_at    DATETIME   DEFAULT NULL,
    updated_at    DATETIME   DEFAULT NULL,
    deleted_at    DATETIME   DEFAULT NULL
);
CREATE UNIQUE INDEX IF NOT EXISTS uq_user_permission ON tbl_user_permissions (user_id, permission_id);
CREATE INDEX IF NOT EXISTS ix_user_permission_permission ON tbl_user_permissions (permission_id);

INSERT INTO tbl_permissions (name, code, created_at, updated_at)
SELECT 'Kullanıcılar', 'Users', datetime('now'), datetime('now')
 WHERE NOT EXISTS (SELECT 1 FROM tbl_permissions WHERE code = 'Users');

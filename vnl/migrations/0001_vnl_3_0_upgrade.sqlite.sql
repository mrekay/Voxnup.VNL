-- =============================================================================
-- 0001 — VNL 3.0 Yükseltmesi (SQLite hâli, VNL 4.1.0)
--
-- SQLite'ta VNL 2.x kurulumu yoktur; parola sütununu genişletme adımı
-- gereksizdir (SQLite VARCHAR uzunluğunu zaten uygulamaz). Yalnız tablolar.
--
-- SQLite'ın koşullu ALTER'ı yoktur. Bu yüzden .sqlite.sql dosyaları sıfırdan
-- kuruluma göre yazılır: projenin önceden kendi açtığı tablolara eksik sütun
-- EKLENMEZ (0002'ye bakın).
-- =============================================================================

CREATE TABLE IF NOT EXISTS tbl_remember_tokens (
    id         INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id    INTEGER      NOT NULL,
    token_hash CHAR(64)     NOT NULL,
    auth_stamp CHAR(32)     DEFAULT NULL,   -- MySQL'de 0008 ekler
    expires_at DATETIME     NOT NULL,
    user_agent VARCHAR(255) DEFAULT NULL,
    ip_address VARCHAR(45)  DEFAULT NULL,
    created_at DATETIME     NOT NULL,
    used_at    DATETIME     DEFAULT NULL
);
CREATE UNIQUE INDEX IF NOT EXISTS uq_remember_token_hash ON tbl_remember_tokens (token_hash);
CREATE INDEX IF NOT EXISTS ix_remember_user ON tbl_remember_tokens (user_id);
CREATE INDEX IF NOT EXISTS ix_remember_expires ON tbl_remember_tokens (expires_at);

CREATE TABLE IF NOT EXISTS tbl_permissions (
    id         INTEGER PRIMARY KEY AUTOINCREMENT,
    name       VARCHAR(255) NOT NULL,
    code       VARCHAR(100) NOT NULL,
    deleted_at DATETIME     DEFAULT NULL,
    created_at DATETIME     DEFAULT NULL,   -- MySQL'de 0002 ekler
    updated_at DATETIME     DEFAULT NULL
);
CREATE UNIQUE INDEX IF NOT EXISTS uq_permission_code ON tbl_permissions (code);

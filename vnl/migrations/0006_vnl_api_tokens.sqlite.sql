-- =============================================================================
-- 0006 — API jetonları (SQLite hâli, VNL 4.1.0)
-- =============================================================================

CREATE TABLE IF NOT EXISTS tbl_vnl_api_tokens (
    id           INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id      INTEGER      NOT NULL,
    name         VARCHAR(100) NOT NULL,
    token_hash   CHAR(64)     NOT NULL,
    abilities    TEXT         DEFAULT NULL,
    last_used_at DATETIME     DEFAULT NULL,
    expires_at   DATETIME     DEFAULT NULL,
    created_at   DATETIME     NOT NULL,
    revoked_at   DATETIME     DEFAULT NULL
);
CREATE UNIQUE INDEX IF NOT EXISTS uq_api_token_hash ON tbl_vnl_api_tokens (token_hash);
CREATE INDEX IF NOT EXISTS ix_api_token_user ON tbl_vnl_api_tokens (user_id);

-- =============================================================================
-- 0003 — Hız sınırlayıcı sayaçları (PostgreSQL hâli, VNL 4.1.0)
--
-- RateLimiter. reset_at veritabanı saatiyle (LOCALTIMESTAMP) yazılır.
-- =============================================================================

CREATE TABLE IF NOT EXISTS tbl_vnl_rate_limits (
    key_hash CHAR(64)     NOT NULL PRIMARY KEY,
    hits     INTEGER      NOT NULL,
    reset_at TIMESTAMP(0) NOT NULL
);
CREATE INDEX IF NOT EXISTS ix_rate_limits_reset ON tbl_vnl_rate_limits (reset_at);

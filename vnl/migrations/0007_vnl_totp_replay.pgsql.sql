-- =============================================================================
-- 0007 — TOTP tekrar kullanım koruması (PostgreSQL hâli, VNL 4.1.0)
--
-- RFC 6238 §5.2. Sütun yoksa iki adımlı giriş kapalı kalır (fail closed).
-- =============================================================================

ALTER TABLE tbl_users ADD COLUMN IF NOT EXISTS totp_last_step BIGINT DEFAULT NULL;

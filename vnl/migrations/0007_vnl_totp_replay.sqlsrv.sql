-- =============================================================================
-- 0007 — TOTP tekrar kullanım koruması (SQL Server hâli, VNL 4.1.0)
--
-- RFC 6238 §5.2. Sütun yoksa iki adımlı giriş kapalı kalır (fail closed).
-- =============================================================================

IF OBJECT_ID(N'tbl_users', N'U') IS NOT NULL AND COL_LENGTH(N'tbl_users', N'totp_last_step') IS NULL
    ALTER TABLE [tbl_users] ADD [totp_last_step] BIGINT NULL;

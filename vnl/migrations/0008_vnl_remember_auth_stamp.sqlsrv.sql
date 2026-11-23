-- =============================================================================
-- 0008 — "Beni hatırla" jetonu kimlik bilgisine bağlanır (SQL Server hâli,
-- VNL 4.1.0)
--
-- UserModule::AuthStamp. Tablo yoksa dokunulmaz.
-- =============================================================================

IF OBJECT_ID(N'tbl_remember_tokens', N'U') IS NOT NULL AND COL_LENGTH(N'tbl_remember_tokens', N'auth_stamp') IS NULL
    ALTER TABLE [tbl_remember_tokens] ADD [auth_stamp] NCHAR(32) NULL;

-- =============================================================================
-- 0008 — "Beni hatırla" jetonu kimlik bilgisine bağlanır (PostgreSQL hâli,
-- VNL 4.1.0)
--
-- UserModule::AuthStamp. Tablo 0001'de açılır; yine de yoksa dokunulmaz.
-- =============================================================================

ALTER TABLE IF EXISTS tbl_remember_tokens ADD COLUMN IF NOT EXISTS auth_stamp CHAR(32) DEFAULT NULL;

-- =============================================================================
-- 0006 — API jetonları (SQL Server hâli, VNL 4.1.0)
--
-- Authorization: Bearer <jeton>. Yalnız SHA-256 özeti saklanır.
-- =============================================================================

IF OBJECT_ID(N'tbl_vnl_api_tokens', N'U') IS NULL
CREATE TABLE [tbl_vnl_api_tokens] (
    [id]           BIGINT IDENTITY(1,1) NOT NULL PRIMARY KEY,
    [user_id]      INT           NOT NULL,
    [name]         NVARCHAR(100) NOT NULL,
    [token_hash]   NCHAR(64)     NOT NULL,
    [abilities]    NVARCHAR(MAX) NULL,
    [last_used_at] DATETIME2(0)  NULL,
    [expires_at]   DATETIME2(0)  NULL,
    [created_at]   DATETIME2(0)  NOT NULL,
    [revoked_at]   DATETIME2(0)  NULL,
    CONSTRAINT [uq_api_token_hash] UNIQUE ([token_hash]),
    INDEX [ix_api_token_user] ([user_id])
);

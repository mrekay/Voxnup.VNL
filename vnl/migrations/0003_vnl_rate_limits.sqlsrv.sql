-- =============================================================================
-- 0003 — Hız sınırlayıcı sayaçları (SQL Server hâli, VNL 4.1.0)
--
-- RateLimiter. Sayaç tek bir MERGE … WITH (HOLDLOCK) ile atomik artırılır.
-- =============================================================================

IF OBJECT_ID(N'tbl_vnl_rate_limits', N'U') IS NULL
CREATE TABLE [tbl_vnl_rate_limits] (
    [key_hash] NCHAR(64)    NOT NULL PRIMARY KEY,
    [hits]     INT          NOT NULL,
    [reset_at] DATETIME2(0) NOT NULL,
    INDEX [ix_rate_limits_reset] ([reset_at])
);

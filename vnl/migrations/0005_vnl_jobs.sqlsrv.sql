-- =============================================================================
-- 0005 — İş kuyruğu (SQL Server hâli, VNL 4.1.0)
--
-- Queue / queue:work. İşçi işi UPDLOCK + READPAST ile ayırır
-- (Queue::reserve): iki işçi aynı satırı alamaz, biri öbürünü beklemez.
-- =============================================================================

IF OBJECT_ID(N'tbl_vnl_jobs', N'U') IS NULL
CREATE TABLE [tbl_vnl_jobs] (
    [id]           BIGINT IDENTITY(1,1) NOT NULL PRIMARY KEY,
    [queue]        NVARCHAR(50)  NOT NULL DEFAULT N'default',
    [type]         NVARCHAR(100) NOT NULL,
    [payload]      NVARCHAR(MAX) NOT NULL,
    [status]       NVARCHAR(16)  NOT NULL DEFAULT N'queued',
    [attempts]     TINYINT       NOT NULL DEFAULT 0,
    [max_attempts] TINYINT       NOT NULL DEFAULT 1,
    [progress]     TINYINT       NOT NULL DEFAULT 0,
    [output]       NVARCHAR(MAX) NULL,
    [error]        NVARCHAR(MAX) NULL,
    [reserved_by]  NCHAR(32)     NULL,
    [available_at] DATETIME2(0)  NOT NULL,
    [reserved_at]  DATETIME2(0)  NULL,
    [finished_at]  DATETIME2(0)  NULL,
    [created_by]   INT           NULL,
    [created_at]   DATETIME2(0)  NOT NULL,
    [updated_at]   DATETIME2(0)  NULL,
    INDEX [ix_jobs_pick] ([queue], [status], [available_at]),
    INDEX [ix_jobs_reserved] ([reserved_by])
);

-- =============================================================================
-- 0004 — Denetim kaydı (SQL Server hâli, VNL 4.1.0)
--
-- Audit::log(). data JSON metindir (NVARCHAR(MAX)).
-- =============================================================================

IF OBJECT_ID(N'tbl_vnl_audit_log', N'U') IS NULL
CREATE TABLE [tbl_vnl_audit_log] (
    [id]           BIGINT IDENTITY(1,1) NOT NULL PRIMARY KEY,
    [user_id]      INT           NULL,
    [action]       NVARCHAR(100) NOT NULL,
    [subject_type] NVARCHAR(100) NULL,
    [subject_id]   NVARCHAR(64)  NULL,
    [data]         NVARCHAR(MAX) NULL,
    [ip]           NVARCHAR(45)  NULL,
    [user_agent]   NVARCHAR(255) NULL,
    [created_at]   DATETIME2(0)  NOT NULL,
    INDEX [ix_audit_user] ([user_id]),
    INDEX [ix_audit_action] ([action]),
    INDEX [ix_audit_subject] ([subject_type], [subject_id]),
    INDEX [ix_audit_created] ([created_at])
);

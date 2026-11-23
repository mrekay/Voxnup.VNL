-- =============================================================================
-- 0002 — Kullanıcılar ve kullanıcı yetkileri (SQL Server hâli, VNL 4.1.0)
--
-- 0002_vnl_users.sql'in karşılığı: tablolar yoksa açılır, projenin kendi
-- açtığı tablolarda eksik sütunlar eklenir (COL_LENGTH ile koşullu). Var
-- olan sütunun türüne dokunulmaz.
--
-- İlk yönetici SQL ile TOHUMLANMAZ:
--   php vnl.php user:create --username=admin --super
-- =============================================================================


-- -----------------------------------------------------------------------------
-- 1) tbl_users
-- -----------------------------------------------------------------------------
IF OBJECT_ID(N'tbl_users', N'U') IS NULL
CREATE TABLE [tbl_users] (
    [id]               INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
    [username]         NVARCHAR(64)  NOT NULL,
    [password]         NVARCHAR(255) NOT NULL,
    [name]             NVARCHAR(100) NOT NULL DEFAULT N'',
    [surname]          NVARCHAR(100) NOT NULL DEFAULT N'',
    [email]            NVARCHAR(190) NULL,
    [perm_id]          INT           NULL,
    [is_active]        TINYINT       NOT NULL DEFAULT 1,
    [last_activity_at] DATETIME2(0)  NULL,
    [last_login_at]    DATETIME2(0)  NULL,
    [last_login_ip]    NVARCHAR(45)  NULL,
    [totp_secret]      NVARCHAR(255) NULL,
    [totp_enabled]     TINYINT       NOT NULL DEFAULT 0,
    [created_at]       DATETIME2(0)  NULL,
    [updated_at]       DATETIME2(0)  NULL,
    [deleted_at]       DATETIME2(0)  NULL,
    CONSTRAINT [uq_users_username] UNIQUE ([username]),
    INDEX [ix_users_perm] ([perm_id]),
    INDEX [ix_users_deleted] ([deleted_at])
);


-- -----------------------------------------------------------------------------
-- 2) Mevcut tbl_users — eksik sütunlar
-- -----------------------------------------------------------------------------
IF COL_LENGTH(N'tbl_users', N'name') IS NULL
    ALTER TABLE [tbl_users] ADD [name] NVARCHAR(100) NOT NULL DEFAULT N'';
IF COL_LENGTH(N'tbl_users', N'surname') IS NULL
    ALTER TABLE [tbl_users] ADD [surname] NVARCHAR(100) NOT NULL DEFAULT N'';
IF COL_LENGTH(N'tbl_users', N'email') IS NULL
    ALTER TABLE [tbl_users] ADD [email] NVARCHAR(190) NULL;
IF COL_LENGTH(N'tbl_users', N'perm_id') IS NULL
    ALTER TABLE [tbl_users] ADD [perm_id] INT NULL;
IF COL_LENGTH(N'tbl_users', N'is_active') IS NULL
    ALTER TABLE [tbl_users] ADD [is_active] TINYINT NOT NULL DEFAULT 1;
IF COL_LENGTH(N'tbl_users', N'last_activity_at') IS NULL
    ALTER TABLE [tbl_users] ADD [last_activity_at] DATETIME2(0) NULL;
IF COL_LENGTH(N'tbl_users', N'last_login_at') IS NULL
    ALTER TABLE [tbl_users] ADD [last_login_at] DATETIME2(0) NULL;
IF COL_LENGTH(N'tbl_users', N'last_login_ip') IS NULL
    ALTER TABLE [tbl_users] ADD [last_login_ip] NVARCHAR(45) NULL;
IF COL_LENGTH(N'tbl_users', N'totp_secret') IS NULL
    ALTER TABLE [tbl_users] ADD [totp_secret] NVARCHAR(255) NULL;
IF COL_LENGTH(N'tbl_users', N'totp_enabled') IS NULL
    ALTER TABLE [tbl_users] ADD [totp_enabled] TINYINT NOT NULL DEFAULT 0;
IF COL_LENGTH(N'tbl_users', N'created_at') IS NULL
    ALTER TABLE [tbl_users] ADD [created_at] DATETIME2(0) NULL;
IF COL_LENGTH(N'tbl_users', N'updated_at') IS NULL
    ALTER TABLE [tbl_users] ADD [updated_at] DATETIME2(0) NULL;
IF COL_LENGTH(N'tbl_users', N'deleted_at') IS NULL
    ALTER TABLE [tbl_users] ADD [deleted_at] DATETIME2(0) NULL;


-- -----------------------------------------------------------------------------
-- 3) tbl_permissions — zaman damgaları (tablo 0001'de açılır)
-- -----------------------------------------------------------------------------
IF COL_LENGTH(N'tbl_permissions', N'created_at') IS NULL
    ALTER TABLE [tbl_permissions] ADD [created_at] DATETIME2(0) NULL;
IF COL_LENGTH(N'tbl_permissions', N'updated_at') IS NULL
    ALTER TABLE [tbl_permissions] ADD [updated_at] DATETIME2(0) NULL;


-- -----------------------------------------------------------------------------
-- 4) tbl_user_permissions — kullanıcı × yetki kodu × yetenek
-- -----------------------------------------------------------------------------
IF OBJECT_ID(N'tbl_user_permissions', N'U') IS NULL
CREATE TABLE [tbl_user_permissions] (
    [id]            INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
    [user_id]       INT          NOT NULL,
    [permission_id] INT          NOT NULL,
    [can_view]      TINYINT      NOT NULL DEFAULT 0,
    [can_insert]    TINYINT      NOT NULL DEFAULT 0,
    [can_edit]      TINYINT      NOT NULL DEFAULT 0,
    [can_delete]    TINYINT      NOT NULL DEFAULT 0,
    [created_at]    DATETIME2(0) NULL,
    [updated_at]    DATETIME2(0) NULL,
    [deleted_at]    DATETIME2(0) NULL,
    CONSTRAINT [uq_user_permission] UNIQUE ([user_id], [permission_id]),
    INDEX [ix_user_permission_permission] ([permission_id])
);

IF COL_LENGTH(N'tbl_user_permissions', N'created_at') IS NULL
    ALTER TABLE [tbl_user_permissions] ADD [created_at] DATETIME2(0) NULL;
IF COL_LENGTH(N'tbl_user_permissions', N'updated_at') IS NULL
    ALTER TABLE [tbl_user_permissions] ADD [updated_at] DATETIME2(0) NULL;
IF COL_LENGTH(N'tbl_user_permissions', N'deleted_at') IS NULL
    ALTER TABLE [tbl_user_permissions] ADD [deleted_at] DATETIME2(0) NULL;


-- -----------------------------------------------------------------------------
-- 5) Çekirdeğin kendi yetki kodu (INSERT IGNORE karşılığı: varsa dokunulmaz)
-- -----------------------------------------------------------------------------
IF NOT EXISTS (SELECT 1 FROM [tbl_permissions] WHERE [code] = N'Users')
    INSERT INTO [tbl_permissions] ([name], [code], [created_at], [updated_at])
    VALUES (N'Kullanıcılar', N'Users', GETDATE(), GETDATE());

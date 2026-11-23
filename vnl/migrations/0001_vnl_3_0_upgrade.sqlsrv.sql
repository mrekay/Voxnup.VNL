-- =============================================================================
-- 0001 — VNL 3.0 Yükseltmesi (SQL Server hâli, VNL 4.1.0)
--
-- 0001_vnl_3_0_upgrade.sql'in karşılığı. Hem sıfırdan kurulumda hem projenin
-- kendi açtığı tablolar varken çalışır.
--
-- Yazım kuralı (bütün .sqlsrv.sql dosyaları): her ifade ayrı bir toplu iş
-- (batch) olarak gönderilir; GO yazılmaz. Koşullu iş tek ifadede yapılır
-- (IF … CREATE / IF … ALTER). Metinler N'…' ile yazılır (Unicode).
-- Tablolar oturumun varsayılan şemasında aranır ve açılır (çoğunlukla dbo).
-- =============================================================================


-- -----------------------------------------------------------------------------
-- 1) Parola sütununu genişlet — yalnız sütun varsa ve 255'ten darsa.
--
-- bcrypt 60 karakterdir; dar sütun hash'i kırpar, kullanıcı giremez.
-- Sütunun türü (varchar/nvarchar) ve NULL kabulü olduğu gibi korunur.
-- Tek ifadedir (DECLARE … EXEC arasında noktalı virgül yok).
-- -----------------------------------------------------------------------------
DECLARE @vnl_sql NVARCHAR(400)
SELECT @vnl_sql = N'ALTER TABLE [tbl_users] ALTER COLUMN [password] '
       + CASE WHEN DATA_TYPE IN ('nvarchar', 'nchar') THEN N'NVARCHAR(255)' ELSE N'VARCHAR(255)' END
       + CASE WHEN IS_NULLABLE = 'YES' THEN N' NULL' ELSE N' NOT NULL' END
  FROM INFORMATION_SCHEMA.COLUMNS
 WHERE TABLE_SCHEMA = SCHEMA_NAME()
   AND TABLE_NAME   = N'tbl_users'
   AND COLUMN_NAME  = N'password'
   AND DATA_TYPE IN ('varchar', 'nvarchar', 'char', 'nchar')
   AND CHARACTER_MAXIMUM_LENGTH BETWEEN 1 AND 254
IF @vnl_sql IS NOT NULL EXEC sp_executesql @vnl_sql;


-- -----------------------------------------------------------------------------
-- 2) "Beni hatırla" jetonları (yalnız SHA-256 özeti saklanır)
--
-- Özet sütunları NCHAR: pdo_sqlsrv metni nvarchar gönderir; CHAR sütun
-- örtük dönüşüm yüzünden dizini kullanamazdı.
-- -----------------------------------------------------------------------------
IF OBJECT_ID(N'tbl_remember_tokens', N'U') IS NULL
CREATE TABLE [tbl_remember_tokens] (
    [id]         INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
    [user_id]    INT           NOT NULL,
    [token_hash] NCHAR(64)     NOT NULL,
    [expires_at] DATETIME2(0)  NOT NULL,
    [user_agent] NVARCHAR(255) NULL,
    [ip_address] NVARCHAR(45)  NULL,
    [created_at] DATETIME2(0)  NOT NULL,
    [used_at]    DATETIME2(0)  NULL,
    CONSTRAINT [uq_remember_token_hash] UNIQUE ([token_hash]),
    INDEX [ix_remember_user] ([user_id]),
    INDEX [ix_remember_expires] ([expires_at])
);


-- -----------------------------------------------------------------------------
-- 3) Yetki tablosu — yalnızca yoksa
-- -----------------------------------------------------------------------------
IF OBJECT_ID(N'tbl_permissions', N'U') IS NULL
CREATE TABLE [tbl_permissions] (
    [id]         INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
    [name]       NVARCHAR(255) NOT NULL,
    [code]       NVARCHAR(100) NOT NULL,
    [deleted_at] DATETIME2(0)  NULL,
    CONSTRAINT [uq_permission_code] UNIQUE ([code])
);

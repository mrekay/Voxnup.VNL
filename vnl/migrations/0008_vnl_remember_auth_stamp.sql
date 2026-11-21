-- =============================================================================
-- 0008 — "Beni hatırla" jetonu kimlik bilgisine bağlanır (VNL 4.0.0)
--
-- Parolası ya da 2FA'sı değişen hesabın açık oturumları UserModule::AuthStamp
-- ile kapanır. Ama oturumu olmayan, yalnız "beni hatırla" çerezi taşıyan bir
-- cihaz (örneğin çerezi çalınmış bir tarayıcı) bir sonraki istekte jetonla
-- YENİ oturum açıp yeni damgayı alırdı. Jeton artık oluşturulduğu andaki
-- damgayı taşır; tutmazsa reddedilir ve silinir.
--
-- Sütunu NULL olan (4.0 öncesi) jetonlar kabul edilir ve ilk kullanımda
-- döndürülürken damgalanır. Sütun yoksa jeton damgasız yazılır, uyarı
-- günlüğe düşer — önce: php vnl.php migrate --run
-- =============================================================================

SET @vnl_alter := IF((SELECT COUNT(*) FROM information_schema.TABLES
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tbl_remember_tokens') = 1
    AND (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tbl_remember_tokens' AND COLUMN_NAME = 'auth_stamp') = 0,
    'ALTER TABLE `tbl_remember_tokens` ADD COLUMN `auth_stamp` CHAR(32) DEFAULT NULL AFTER `token_hash`', 'DO 0');
PREPARE vnl_stmt FROM @vnl_alter; EXECUTE vnl_stmt; DEALLOCATE PREPARE vnl_stmt;

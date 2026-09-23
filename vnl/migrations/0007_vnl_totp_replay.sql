-- =============================================================================
-- 0007 — TOTP tekrar kullanım koruması (VNL 3.4.0)
--
-- RFC 6238 §5.2: doğrulayıcı aynı tek kullanımlık parolayı ikinci kez kabul
-- ETMEMELİDİR. Kod ±1 adım (±30 sn) penceresinde geçerli olduğu için,
-- omuz üstünden görülen ya da kaydedilen bir kod çalınan parolayla birlikte
-- ~60–90 sn içinde yeniden kullanılabiliyordu.
--
-- action_login::totp() eşleşen adımı bu sütuna koşullu UPDATE ile yazar
-- (User::claimTotpStep): yalnız kayıtlı adımdan BÜYÜK bir adım kabul edilir.
-- Sütun yoksa iki adımlı giriş KAPALI kalır (fail closed) ve sebep günlüğe
-- yazılır — önce: php vnl.php migrate --run
--
-- 0002 düzenlenmez (yayımlanmış migration); sütun koşullu eklenir.
-- =============================================================================

SET @vnl_alter := IF((SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tbl_users' AND COLUMN_NAME = 'totp_last_step') = 0,
    'ALTER TABLE `tbl_users` ADD COLUMN `totp_last_step` BIGINT DEFAULT NULL AFTER `totp_enabled`', 'DO 0');
PREPARE vnl_stmt FROM @vnl_alter; EXECUTE vnl_stmt; DEALLOCATE PREPARE vnl_stmt;

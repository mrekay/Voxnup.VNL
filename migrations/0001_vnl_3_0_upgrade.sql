-- =============================================================================
-- 0001 — VNL 3.0 Yükseltmesi
--
-- İlk çalıştırılacak migration. VNL 2.x'ten gelen kurulumlarda da,
-- sıfırdan kurulumda da bir kez çalıştırın.
-- =============================================================================


-- -----------------------------------------------------------------------------
-- 1) Parola sütununu genişlet
--
-- VNL 2.x parolaları sha1(md5()) ile saklıyordu: 40 karakter. VNL 3.0
-- password_hash() kullanır ve bcrypt 60 karakterdir. Sütun dar kalırsa hash
-- SESSİZCE KIRPILIR ve kullanıcı bir sonraki girişinde içeri giremez.
-- Algoritma ileride değişebileceği için 255 önerilir.
-- -----------------------------------------------------------------------------
ALTER TABLE `tbl_users` MODIFY `password` VARCHAR(255) NOT NULL;


-- -----------------------------------------------------------------------------
-- 2) "Beni hatırla" jetonları
--
-- Çerezde jetonun kendisi, burada yalnızca SHA-256 özeti durur; tablo sızsa
-- bile geçerli çerez üretilemez. Her kullanımda jeton yenilenir, bu yüzden
-- çalınan bir çerez meşru kullanımın ardından geçersiz kalır.
--
-- Kullanıcı başına birden çok satır olabilir: her cihaz kendi jetonunu taşır.
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tbl_remember_tokens` (
    `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id`    INT UNSIGNED NOT NULL,
    `token_hash` CHAR(64)     NOT NULL,               -- sha256, onaltılık
    `expires_at` DATETIME     NOT NULL,
    `user_agent` VARCHAR(255) DEFAULT NULL,           -- hangi cihaz olduğunu göstermek için
    `ip_address` VARCHAR(45)  DEFAULT NULL,           -- IPv6 sığacak uzunluk
    `created_at` DATETIME     NOT NULL,
    `used_at`    DATETIME     DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_remember_token_hash` (`token_hash`),
    KEY `ix_remember_user` (`user_id`),
    KEY `ix_remember_expires` (`expires_at`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci;


-- -----------------------------------------------------------------------------
-- 3) Yetki tablosu — yalnızca sıfırdan kurulumlar için
--
-- Mevcut kurulumlarda bu tablo zaten vardır; IF NOT EXISTS sayesinde
-- dokunulmaz. Sütun sözleşmesi: id, name, code, deleted_at.
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tbl_permissions` (
    `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name`       VARCHAR(255) NOT NULL,
    `code`       VARCHAR(100) NOT NULL,
    `deleted_at` DATETIME     DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_permission_code` (`code`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci;


-- -----------------------------------------------------------------------------
-- 4) Eski çerezler — sunucuda yapılacak bir şey yok
--
-- VNL 2.x'in {prefix}auth çerezi kullanıcı adını ve DÜZ PAROLAYI taşıyordu.
-- Kod artık o çerezi her istekte siliyor. Ancak o parolalar tarayıcılarda
-- dolaştığı için, sızma ihtimalini ciddiye alıyorsanız kullanıcılara parola
-- değiştirtmek doğru olur.
-- -----------------------------------------------------------------------------

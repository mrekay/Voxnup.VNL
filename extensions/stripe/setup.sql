-- =============================================================================
-- Stripe Extension — DB Kurulum
-- Bu dosyayı bir kez çalıştırın.
-- =============================================================================

CREATE TABLE IF NOT EXISTS `tbl_stripe_payments` (
    `Id`                    INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `UserId`                INT UNSIGNED NOT NULL,
    `StripeCustomerId`      VARCHAR(255) DEFAULT NULL,
    `StripeSessionId`       VARCHAR(255) DEFAULT NULL,
    `StripePaymentIntentId` VARCHAR(255) DEFAULT NULL,
    `Amount`                INT          NOT NULL DEFAULT 0,        -- kuruş (9900 = 99.00 TL)
    `Currency`              VARCHAR(10)  NOT NULL DEFAULT 'TRY',
    `Status`                VARCHAR(50)  NOT NULL DEFAULT 'pending', -- pending|paid|failed|refunded
    `Description`           VARCHAR(500) DEFAULT NULL,
    `Metadata`              TEXT         DEFAULT NULL,              -- JSON
    `InsertDate`            DATETIME     DEFAULT NULL,
    `UpdateDate`            DATETIME     DEFAULT NULL,
    `deleted_at`            DATETIME     DEFAULT NULL,
    PRIMARY KEY (`Id`),
    INDEX `idx_sp_user`    (`UserId`),
    INDEX `idx_sp_customer`(`StripeCustomerId`),
    INDEX `idx_sp_intent`  (`StripePaymentIntentId`),
    INDEX `idx_sp_status`  (`Status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `tbl_stripe_subscriptions` (
    `Id`                    INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `UserId`                INT UNSIGNED NOT NULL,
    `StripeCustomerId`      VARCHAR(255) NOT NULL,
    `StripeSubscriptionId`  VARCHAR(255) DEFAULT NULL,
    `StripePriceId`         VARCHAR(255) DEFAULT NULL,
    `PlanName`              VARCHAR(255) DEFAULT NULL,
    `Status`                VARCHAR(50)  NOT NULL DEFAULT 'incomplete',
    -- incomplete|incomplete_expired|trialing|active|past_due|canceled|unpaid|paused
    `CurrentPeriodStart`    DATETIME     DEFAULT NULL,
    `CurrentPeriodEnd`      DATETIME     DEFAULT NULL,
    `CancelAtPeriodEnd`     TINYINT(1)   NOT NULL DEFAULT 0,        -- 1 = dönem sonunda iptal
    `InsertDate`            DATETIME     DEFAULT NULL,
    `UpdateDate`            DATETIME     DEFAULT NULL,
    `deleted_at`            DATETIME     DEFAULT NULL,
    PRIMARY KEY (`Id`),
    INDEX `idx_ss_user`        (`UserId`),
    INDEX `idx_ss_customer`    (`StripeCustomerId`),
    INDEX `idx_ss_subscription`(`StripeSubscriptionId`),
    INDEX `idx_ss_status`      (`Status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

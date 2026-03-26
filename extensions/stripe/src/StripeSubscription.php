<?php

use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * StripeSubscription — tbl_stripe_subscriptions
 *
 * Kullanıcıya ait abonelik kayıtlarını tutar.
 * Aktif abonelik: Status = 'active' veya 'trialing' olan en güncel kayıttır.
 *
 * CREATE TABLE `tbl_stripe_subscriptions` (
 *   `Id`                    INT UNSIGNED NOT NULL AUTO_INCREMENT,
 *   `UserId`                INT UNSIGNED NOT NULL,
 *   `StripeCustomerId`      VARCHAR(255) NOT NULL,
 *   `StripeSubscriptionId`  VARCHAR(255) DEFAULT NULL,
 *   `StripePriceId`         VARCHAR(255) DEFAULT NULL,
 *   `PlanName`              VARCHAR(255) DEFAULT NULL,
 *   `Status`                VARCHAR(50) NOT NULL DEFAULT 'incomplete',
 *   -- Stripe durumları: incomplete|incomplete_expired|trialing|active|past_due|canceled|unpaid|paused
 *   `CurrentPeriodStart`    DATETIME DEFAULT NULL,
 *   `CurrentPeriodEnd`      DATETIME DEFAULT NULL,
 *   `CancelAtPeriodEnd`     TINYINT(1) NOT NULL DEFAULT 0,   -- 1 = dönem sonunda iptal
 *   `InsertDate`            DATETIME DEFAULT NULL,
 *   `UpdateDate`            DATETIME DEFAULT NULL,
 *   `deleted_at`            DATETIME DEFAULT NULL,
 *   PRIMARY KEY (`Id`),
 *   INDEX (`UserId`), INDEX (`StripeCustomerId`), INDEX (`StripeSubscriptionId`), INDEX (`Status`)
 * ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
 */
class StripeSubscription extends TableModel
{
    use SoftDeletes;

    public $timestamps = false;

    protected $table = 'tbl_stripe_subscriptions';

    protected static $columns = [
        'UserId'               => ['name' => 'UserId'],
        'StripeCustomerId'     => ['name' => 'StripeCustomerId'],
        'StripeSubscriptionId' => ['name' => 'StripeSubscriptionId'],
        'Status'               => ['name' => 'Status'],
    ];

    public static function SelectList($filters = [], $extras = [])
    {
        return self::selectListQuery(self::$columns, $filters, $extras);
    }

    /** Abonelik aktif mi? */
    public function isActive(): bool
    {
        return in_array($this->Status, ['active', 'trialing']);
    }

    /** Dönem sonunda iptal edilecek mi? */
    public function isCanceling(): bool
    {
        return $this->isActive() && (bool)$this->CancelAtPeriodEnd;
    }
}

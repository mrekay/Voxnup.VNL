<?php

use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * StripePayment — tbl_stripe_payments
 *
 * Her türlü başarılı ödemeyi tutar: tek seferlik veya abonelik yenilemesi.
 *
 * CREATE TABLE `tbl_stripe_payments` (
 *   `Id`                    INT UNSIGNED NOT NULL AUTO_INCREMENT,
 *   `UserId`                INT UNSIGNED NOT NULL,
 *   `StripeCustomerId`      VARCHAR(255) DEFAULT NULL,
 *   `StripeSessionId`       VARCHAR(255) DEFAULT NULL,
 *   `StripePaymentIntentId` VARCHAR(255) DEFAULT NULL,
 *   `Amount`                INT NOT NULL DEFAULT 0,        -- kuruş cinsinden (9900 = 99.00 TL)
 *   `Currency`              VARCHAR(10) NOT NULL DEFAULT 'TRY',
 *   `Status`                VARCHAR(50) NOT NULL DEFAULT 'pending',  -- pending|paid|failed|refunded
 *   `Description`           VARCHAR(500) DEFAULT NULL,
 *   `Metadata`              TEXT DEFAULT NULL,             -- JSON
 *   `InsertDate`            DATETIME DEFAULT NULL,
 *   `UpdateDate`            DATETIME DEFAULT NULL,
 *   `deleted_at`            DATETIME DEFAULT NULL,
 *   PRIMARY KEY (`Id`),
 *   INDEX (`UserId`), INDEX (`StripeCustomerId`), INDEX (`StripePaymentIntentId`), INDEX (`Status`)
 * ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
 */
class StripePayment extends TableModel
{
    use SoftDeletes;

    public $timestamps = false;

    protected $table = 'tbl_stripe_payments';

    protected static $columns = [
        'UserId'     => ['name' => 'UserId'],
        'Status'     => ['name' => 'Status'],
        'InsertDate' => ['name' => 'InsertDate'],
    ];

    public static function SelectList($filters = [], $extras = [])
    {
        return self::selectListQuery(self::$columns, $filters, $extras);
    }

    public function getMetadata(): array
    {
        return json_decode($this->Metadata ?? '{}', true) ?? [];
    }
}

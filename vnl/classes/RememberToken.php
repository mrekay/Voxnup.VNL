<?php
/*!
 * ============================================================
 *  Voxnup Not Laravel (VNL) — "Beni Hatırla" Jetonu
 *  Kalıcı giriş jetonlarının saklandığı tablo.
 *  Authors: Voxnup Authors
 * ============================================================
 */

use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Kalıcı giriş jetonu.
 *
 * Çerezde jetonun kendisi, veritabanında yalnızca SHA-256 özeti tutulur;
 * tablo sızsa bile çerezler üretilemez. Jeton 32 rastgele bayt olduğu için
 * kaba kuvvet mümkün değildir — bu yüzden bcrypt yerine hızlı ve
 * indekslenebilir bir özet kullanılır.
 *
 * SoftDeletes KULLANILMAZ: iptal edilen jeton tablodan gerçekten silinmelidir.
 */
class RememberToken extends TableModel
{
    use HasFactory;

    protected $table = 'tbl_remember_tokens';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'token_hash',
        'expires_at',
        'user_agent',
        'ip_address',
        'created_at',
        'used_at',
    ];

    protected $foreign_keys = [
        'user' => ['class' => User::class, 'column' => 'user_id'],
    ];

    protected static $columns = array(
        'id'         => ['name' => 'tbl_remember_tokens.id'],
        'user_id'    => ['name' => 'tbl_remember_tokens.user_id'],
        'token_hash' => ['name' => 'tbl_remember_tokens.token_hash'],
    );

    public static function SelectList($filters = [], $extras = [])
    {
        return self::selectListQuery(self::$columns, $filters, $extras)
            ->select('tbl_remember_tokens.*');
    }

    /** Çerezde taşınan ham jetonun veritabanındaki karşılığı. */
    public static function hashOf(string $rawToken): string
    {
        return hash('sha256', $rawToken);
    }

    /** Süresi geçmiş jetonları siler; dönen değer silinen satır sayısıdır. */
    public static function purgeExpired(): int
    {
        return self::where('expires_at', '<', date('Y-m-d H:i:s'))->delete();
    }
}

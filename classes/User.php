<?php
/*!
 * ============================================================
 *  Voxnup Not Laravel (VNL) — Kullanıcı Modeli
 *  tbl_users (şema: vnl/migrations/0002_vnl_users.sql).
 *  Authors: Voxnup Authors
 * ============================================================
 */

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Kullanıcı.
 *
 * perm_id = rol. 1 süper yöneticidir: helpers/permissions.php'deki varsayılan
 * kanca ona her yetkiyi verir. Kod tabanlı yetkiler tbl_user_permissions'ta
 * (UserPermissions::Grant).
 *
 * $hidden: parola özeti ve TOTP sırrı toArray()/JSON'a hiçbir koşulda
 * girmez — bir action yanlışlıkla `returnSuccessMessage('', $user)` yazsa
 * bile dışarı sızmaz.
 */
class User extends TableModel
{
    use HasFactory, SoftDeletes;

    /** Süper yönetici rolü (perm_id). */
    public const SUPER_ADMIN = 1;

    protected $table = 'tbl_users';

    protected $fillable = ['username', 'name', 'surname', 'email', 'perm_id', 'is_active'];

    protected $hidden = ['password', 'Password', 'totp_secret'];

    protected $foreign_keys = [
        "permission" => ['class' => Permission::class, 'column' => 'perm_id'],
    ];

    protected static $columns = array(
        'id'        => ['name' => 'tbl_users.id'],
        'name'      => ['name' => 'tbl_users.name', 'operator' => 'like'],
        'username'  => ['name' => 'tbl_users.username'],
        'email'     => ['name' => 'tbl_users.email', 'operator' => 'like'],
        'perm_id'   => ['name' => 'tbl_users.perm_id'],
        'is_active' => ['name' => 'tbl_users.is_active', 'zero' => true],
        'password'  => ['name' => 'tbl_users.password'],
    );

    public static function SelectList($filters = [], $extras = [])
    {

        $query = self::selectListQuery(self::$columns,$filters, $extras)->select("tbl_users.*", "p.name as perm_name")
            ->leftJoin(Permission::tableName("p"), "perm_id", "=", "p.id");


        return $query;
    }

    /** Kullanıcı adı başka bir kayıtta (silinmişler dahil) kullanılıyor mu? */
    public static function usernameExists(string $username, int $exceptId = 0): bool
    {
        return self::withTrashed()
            ->where('username', $username)
            ->when($exceptId > 0, fn($q) => $q->where('id', '!=', $exceptId))
            ->exists();
    }

    public function fullname()
    {
        return trim($this->name . ' ' . $this->surname);
    }

    public function isSuperAdmin(): bool
    {
        return (int)$this->perm_id === self::SUPER_ADMIN;
    }

    /** İki adımlı doğrulama açık mı (ve sır okunabiliyor mu)? */
    public function hasTotp(): bool
    {
        return !empty($this->getAttributes()['totp_enabled'] ?? null)
            && !empty($this->getAttributes()['totp_secret'] ?? null);
    }

    /** TOTP tekrar kullanım sütunu (vnl/migrations/0007) bu kayıtta var mı? */
    public function hasTotpReplayGuard(): bool
    {
        return array_key_exists('totp_last_step', $this->getAttributes());
    }

    /**
     * Doğrulanan TOTP adımını tüketir (RFC 6238 §5.2: aynı kod ikinci kez
     * kabul edilmez).
     *
     * Tek bir koşullu UPDATE: yalnız kayıtlı son adımdan BÜYÜK bir adım
     * yazılabilir. Aynı kodla gelen iki paralel istekten yalnız biri bir
     * satır değiştirir; diğeri (ve sonraki her tekrar) false alır. Sorgu
     * kurucusunun temel hali kullanılır: updated_at'e dokunulmaz.
     */
    public function claimTotpStep(int $step): bool
    {
        $claimed = static::query()->toBase()
            ->where('id', (int)$this->id)
            ->where(static function ($q) use ($step) {
                $q->whereNull('totp_last_step')->orWhere('totp_last_step', '<', $step);
            })
            ->update(['totp_last_step' => $step]) === 1;

        if ($claimed) {
            $this->setRawAttributes(['totp_last_step' => $step] + $this->getAttributes(), true);
        }

        return $claimed;
    }

    /**
     * Son etkinlik — dakikada bir yazılır; zaman damgalarına dokunmaz.
     *
     * VNL 3.3.1'de UserModule bu metodu çağırıyordu ama metot yoktu: Eloquent
     * çağrıyı sorgu kurucusuna iletiyor, BadMethodCallException ile girişli
     * HER istek 500 veriyordu.
     */
    public function SetOnlineToLastActivity(): void
    {
        if (!array_key_exists('last_activity_at', $this->getAttributes())) {
            return;   // sütun yoksa sessizce geç (eski şema)
        }

        if (empty($this->last_activity_at) || strtotime((string)$this->last_activity_at) < time() - 60) {
            $this->timestamps = false;
            $this->last_activity_at = date('Y-m-d H:i:s');
            $this->save();
            $this->timestamps = true;
        }
    }

    /** Başarılı girişin zamanı ve IP'si (sütunlar varsa). */
    public function MarkLogin(string $ip): void
    {
        $attributes = $this->getAttributes();
        $dirty      = false;

        if (array_key_exists('last_login_at', $attributes)) {
            $this->last_login_at = date('Y-m-d H:i:s');
            $dirty = true;
        }

        if (array_key_exists('last_login_ip', $attributes)) {
            $this->last_login_ip = mb_substr($ip, 0, 45);
            $dirty = true;
        }

        if ($dirty) {
            $this->timestamps = false;
            $this->save();
            $this->timestamps = true;
        }
    }

    /**
     * Saklanan parola hash'i.
     *
     * Sütunun yazımı eski projelerde password/Password olarak değişiyor.
     * Doğrulama tek noktadan geçsin diye ikisi de tolere edilir.
     */
    public function storedPassword(): ?string
    {
        $hash = $this->password ?? $this->Password ?? null;

        return ($hash === null || $hash === '') ? null : (string)$hash;
    }

    /** Parola hash'ini yazar; kaydın mevcut sütun yazımını korur. */
    public function setStoredPassword(string $hash): void
    {
        if (array_key_exists('Password', $this->getAttributes())) {
            $this->Password = $hash;

            return;
        }

        $this->password = $hash;
    }

}

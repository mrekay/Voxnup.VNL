<?php
/*!
 * ============================================================
 *  Voxnup Not Laravel (VNL) — Kullanıcı Yetkileri
 *  tbl_user_permissions: kullanıcı × yetki kodu × yetenek.
 *  Authors: Voxnup Authors
 * ============================================================
 */

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Kod tabanlı yetkiler.
 *
 *   UserPermissions::Grant($userId, 'Domains', ['view', 'edit']);
 *   UserPermissions::Can($userId, 'Domains', 'edit');   // bool
 *   UserPermissions::Revoke($userId, 'Domains');
 *
 * Yetenekler: view, insert, edit, delete (can_<yetenek> sütunları).
 * Sorular istek içinde önbelleğe alınır; Grant/Revoke önbelleği temizler.
 */
class UserPermissions extends TableModel
{
    use HasFactory, SoftDeletes;

    public const ABILITIES = ['view', 'insert', 'edit', 'delete'];

    protected $table = 'tbl_user_permissions';
    protected static array $permissionCache = [];

    protected $fillable = [
        "user_id",
        "permission_id",
        "can_view",
        "can_insert",
        "can_edit",
        "can_delete",
    ];

    protected $foreign_keys = [];



    protected static $columns = array(
        'id' => ['name' => 'tbl_user_permissions.id'],
        'user_id' => ['name' => 'tbl_user_permissions.user_id'],
        'permission_id' => ['name' => 'tbl_user_permissions.permission_id'],
        'can_view' => ['name' => 'tbl_user_permissions.can_view', 'zero' => true],
        'can_insert' => ['name' => 'tbl_user_permissions.can_insert', 'zero' => true],
        'can_edit' => ['name' => 'tbl_user_permissions.can_edit', 'zero' => true],
        'can_delete' => ['name' => 'tbl_user_permissions.can_delete', 'zero' => true],
    );

    public static function SelectList($filters = [], $extras = [])
    {

        $query = self::selectListQuery(self::$columns,$filters, $extras)->select("tbl_user_permissions.*");

        return $query;
    }

    /**
     * Permission Code üzerinden yetki kontrolü yapar.
     * Herhangi bir yetki varsa tüm satırı, yoksa false döner.
     */
    public static function HasPermissionByCode($userId, $permissionCode)
    {
        $cacheKey = $userId . ':' . $permissionCode;

        if (array_key_exists($cacheKey, self::$permissionCache)) {
            return self::$permissionCache[$cacheKey];
        }

        $permission = self::join('tbl_permissions', 'tbl_permissions.id', '=', 'tbl_user_permissions.permission_id')
            ->where('tbl_user_permissions.user_id', $userId)
            ->where('tbl_permissions.code', $permissionCode)
            ->whereNull('tbl_permissions.deleted_at')
            ->select('tbl_user_permissions.*')
            ->first();

        if (!$permission) {
            return self::$permissionCache[$cacheKey] = false;
        }

        $hasAny = $permission->can_view ||
            $permission->can_insert ||
            $permission->can_edit ||
            $permission->can_delete;

        return self::$permissionCache[$cacheKey] = $hasAny ? $permission : false;
    }


    /**
     * Permission Code üzerinden sadece CanView yetkisine bakar.
     */
    public static function CanViewByCode($userId, $permissionCode)
    {
        return self::Can((int)$userId, (string)$permissionCode, 'view');
    }

    /** Kullanıcının bu kodda bu yeteneği var mı? */
    public static function Can(int $userId, string $code, string $ability = 'view'): bool
    {
        if (!in_array($ability, self::ABILITIES, true)) {
            return false;
        }

        $row = self::HasPermissionByCode($userId, $code);

        return $row !== false && !empty($row->{'can_' . $ability});
    }

    /**
     * Yetkiyi verir (satır yoksa açar, varsa YENİ değerlerle ezer).
     *
     *   UserPermissions::Grant(5, 'Domains', ['view', 'edit']);
     *   UserPermissions::Grant(5, 'Domains', ['*']);            // dördü birden
     *
     * Kod tbl_permissions'ta yoksa oluşturulur. Listede olmayan yetenekler
     * kapatılır — Grant "şu an sahip olacağı hal"dir, ekleme değil.
     */
    public static function Grant(int $userId, string $code, array $abilities): self
    {
        $permission = Permission::EnsureCode($code);

        $all    = in_array('*', $abilities, true);
        $values = [];
        foreach (self::ABILITIES as $ability) {
            $values['can_' . $ability] = ($all || in_array($ability, $abilities, true)) ? 1 : 0;
        }

        $row = self::withTrashed()
            ->where('user_id', $userId)
            ->where('permission_id', $permission->id)
            ->first();

        if ($row === null) {
            $row = self::create(['user_id' => $userId, 'permission_id' => $permission->id] + $values);
        } else {
            if ($row->trashed()) {
                $row->restore();
            }
            $row->fill($values)->save();
        }

        self::clearCache();

        return $row;
    }

    /** Kullanıcının bu koddaki yetkisini kaldırır. */
    public static function Revoke(int $userId, string $code): void
    {
        $permission = Permission::query()->where('code', $code)->first();

        if ($permission !== null) {
            self::query()
                ->where('user_id', $userId)
                ->where('permission_id', $permission->id)
                ->delete();
        }

        self::clearCache();
    }

    /**
     * Kullanıcının bütün yetkileri: kod => ['view' => bool, ...]
     * Yönetim ekranındaki yetki tablosu için.
     */
    public static function ForUser(int $userId): array
    {
        $rows = self::join('tbl_permissions', 'tbl_permissions.id', '=', 'tbl_user_permissions.permission_id')
            ->where('tbl_user_permissions.user_id', $userId)
            ->whereNull('tbl_permissions.deleted_at')
            ->select('tbl_user_permissions.*', 'tbl_permissions.code as permission_code')
            ->get();

        $out = [];
        foreach ($rows as $row) {
            foreach (self::ABILITIES as $ability) {
                $out[$row->permission_code][$ability] = !empty($row->{'can_' . $ability});
            }
        }

        return $out;
    }

    /** Kullanıcının bütün yetki satırlarını siler (kullanıcı silinirken). */
    public static function RevokeAll(int $userId): void
    {
        self::query()->where('user_id', $userId)->delete();
        self::clearCache();
    }

    public static function clearCache(): void
    {
        self::$permissionCache = [];
    }
}

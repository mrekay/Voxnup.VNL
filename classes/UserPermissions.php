<?php

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserPermissions extends TableModel
{
    use HasFactory, SoftDeletes;

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
        'can_view' => ['name' => 'tbl_user_permissions.can_view'],
        'can_insert' => ['name' => 'tbl_user_permissions.can_insert'],
        'can_edit' => ['name' => 'tbl_user_permissions.can_edit'],
        'can_delete' => ['name' => 'tbl_user_permissions.can_delete'],
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

        $permission = self::join('tbl_permissions', 'tbl_permissions.Id', '=', 'tbl_user_permissions.PermissionId')
            ->where('tbl_user_permissions.UserId', $userId)
            ->where('tbl_permissions.Code', $permissionCode)
            ->whereNull('tbl_permissions.DeleteDate')
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
        $cacheKey = 'view:' . $userId . ':' . $permissionCode;

        if (array_key_exists($cacheKey, self::$permissionCache)) {
            return self::$permissionCache[$cacheKey];
        }

        $permission = self::join('tbl_permissions', 'tbl_permissions.id', '=', 'tbl_user_permissions.permission_id')
            ->where('tbl_user_permissions.user_id', $userId)
            ->where('tbl_permissions.code', $permissionCode)
            ->whereNull('tbl_permissions.deleted_at')
            ->select('tbl_user_permissions.can_view')
            ->first();

        return self::$permissionCache[$cacheKey] =
            $permission ? (bool) $permission->CanView : false;
    }



}
<?php
/*!
 * ============================================================
 *  Voxnup Not Laravel (VNL) — Yetki Tanımı
 *  tbl_permissions kayıtları; User ve UserPermissions bu tabloya bağlıdır.
 *  Authors: Voxnup Authors
 * ============================================================
 */

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Yetki tanımı.
 *
 * VNL 3.0'a kadar bu sınıf yoktu, ama User hem $foreign_keys'te hem de
 * SelectList()'teki leftJoin'de ona bağlıydı — yani her User::Select()
 * çağrısı "Class Permission not found" ile düşüyordu.
 *
 * Sütun sözleşmesi: tbl_permissions(id, name, code, created_at, updated_at,
 * deleted_at) — zaman damgaları 3.4.0 (0002) ile eklendi.
 * UserPermissions bu tabloya HasPermissionByCode içinde Id/Code/DeleteDate
 * diye erişiyordu; o yazım hiç doğru olmadı, düzeltildi.
 */
class Permission extends TableModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'tbl_permissions';

    protected $fillable = ['name', 'code'];

    protected $foreign_keys = [];

    protected static $columns = array(
        'id'   => ['name' => 'tbl_permissions.id'],
        'name' => ['name' => 'tbl_permissions.name', 'operator' => 'like'],
        'code' => ['name' => 'tbl_permissions.code'],
    );

    public static function SelectList($filters = [], $extras = [])
    {
        return self::selectListQuery(self::$columns, $filters, $extras)
            ->select('tbl_permissions.*');
    }

    /**
     * Kod varsa kaydı döner, yoksa oluşturur (tohumlama ve kurulum için).
     * 3.4.0: tablo created_at/updated_at taşır (0002 migration'ı ekler);
     * 3.3'te bu çağrı "Unknown column 'updated_at'" ile düşüyordu.
     */
    public static function EnsureCode(string $code, ?string $name = null): self
    {
        $permission = self::withTrashed()->where('code', $code)->first();

        if ($permission === null) {
            return self::create(['code' => $code, 'name' => $name ?? $code]);
        }

        if ($permission->trashed()) {
            $permission->restore();
        }

        return $permission;
    }
}

<?php
/*!
 * ============================================================
 *  Voxnup Not Laravel (VNL) 3.0.0 — Yetki Tanımı
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
 * Sütun adları User::SelectList'in join'inden ve UserPermissions'ın
 * sorgularından türetilmiştir: tbl_permissions(id, name, code).
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
}

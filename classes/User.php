<?php

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends TableModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'tbl_users';

    protected $fillable = ['name'];

    protected $foreign_keys = [
        "permission" => ['class' => Permission::class, 'column' => 'perm_id'],
    ];

    public static function SelectList($filters = [], $extras = [])
    {
        $filters_default = array(
            'name' => ['operator' => 'like', 'value' => ''], // and
            'id' => ['name' => 'tbl_users.id', 'value' => ''],
            'username' => ['name' => 'tbl_users.username', 'value' => ''],
            'password' => ['name' => 'tbl_users.password', 'value' => ''],
        );


        $filters = self::setFilterDefault($filters_default, $filters);

        $query = self::selectListQuery($filters, $extras)->select("tbl_users.*", "p.name as perm_name")
            ->leftJoin(Permission::tableName("p"), "perm_id", "=", "p.id");


        return $query;
    }

    public function fullname()
    {
        return $this->name . ' ' . $this->surname;
    }

}
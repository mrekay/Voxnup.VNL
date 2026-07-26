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

    protected static $columns = array(
        'name' => ['operator' => 'like'], // and
        'id' => ['name' => 'tbl_users.id'],
        'username' => ['name' => 'tbl_users.username'],
        'password' => ['name' => 'tbl_users.password'],
    );

    public static function SelectList($filters = [], $extras = [])
    {

        $query = self::selectListQuery(self::$columns,$filters, $extras)->select("tbl_users.*", "p.name as perm_name")
            ->leftJoin(Permission::tableName("p"), "perm_id", "=", "p.id");


        return $query;
    }

    public function fullname()
    {
        return $this->name . ' ' . $this->surname;
    }

    /**
     * Saklanan parola hash'i.
     *
     * Sütunun yazımı projeden projeye password/Password olarak değişiyor
     * (bu şablonda UserValidator küçük, action_users büyük harfle okuyor).
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
<?php
/*!
 * ============================================================
 *  Voxnup Not Laravel (VNL) — Kullanıcı Yönetimi
 *  POST /app/users/<getList|get|save|delete|permissions|setPermissions>
 *  Authors: Voxnup Authors
 * ============================================================
 *
 * 3.4.0'da yeniden yazıldı. Eski dosya PascalCase sütunlar, MSSQL'e özgü
 * NEWID() ve DataTables sözleşmesiyle yazılmıştı; VNL'in hiçbir sürümünde
 * çalışmıyordu. Şimdi 0002 şeması + VNL Table sözleşmesi.
 *
 * Yetki kodu 'Users' (view / insert / edit / delete). Yetki yükseltmesine
 * karşı kurallar:
 *  - Süper yönetici (perm_id = 1) atamak ya da bir süper yöneticiyi
 *    değiştirmek yalnız süper yöneticinin işidir.
 *  - Kimse kendi hesabını silemez, pasifleştiremez, kendi rolünü ve
 *    yetkilerini değiştiremez.
 *  - Süper olmayan yönetici ancak KENDİSİNDE olan yetenekleri dağıtabilir.
 */

use ActionGlobal\Auth;

class action_users
{
    private const PERM = 'Users';

    private const MIN_PASSWORD = 10;

    /** VNL Table veri ucu. Satırlar ham değer taşır; parola/TOTP sırrı asla. */
    public function getList($req)
    {
        Auth::CheckPermission(self::PERM, 'view');

        $t = VnlTableRequest::from($req);

        $filters = $t->search !== '' ? ['name' => $t->search] : [];

        $rows = User::SelectList($filters, $t->extras('id', 'desc'))->get()
            ->map(fn(User $u) => $this->row($u))->all();

        if ($t->op === VnlTableRequest::OP_EXPORT) {
            $all = User::SelectList($filters, ['order' => 'id'])->get()
                ->map(fn(User $u) => $this->row($u))->all();

            $t->exportXlsx($all, [
                'id'            => 'ID',
                'username'      => lang('USERS_COL_USERNAME'),
                'fullname'      => lang('USERS_COL_NAME'),
                'email'         => lang('USERS_COL_EMAIL'),
                'is_active'     => lang('USERS_COL_STATUS'),
                'last_login_at' => lang('USERS_COL_LAST_LOGIN'),
            ], 'users');
        }

        $t->respond(
            $rows,
            User::SelectList([], null)->count(),
            User::SelectList($filters, null)->count()
        );
    }

    /** Tek kayıt (düzenleme modalı). */
    public function get($req)
    {
        Auth::CheckPermission(self::PERM, 'view');

        $user = $this->find((int)$req->post('id'));

        returnSuccessMessage('', $this->row($user) + [
            'name'    => (string)$user->name,
            'surname' => (string)$user->surname,
            'perm_id' => $user->perm_id !== null ? (int)$user->perm_id : null,
        ]);
    }

    /** Ekle / güncelle. Parola yeni kayıtta zorunlu, güncellemede isteğe bağlı. */
    public function save($req)
    {
        $id = (int)$req->post('id', 0);
        Auth::CheckPermission(self::PERM, $id > 0 ? 'edit' : 'insert');

        $me       = vnl_user();
        $username = trim((string)$req->post('username', ''));
        $password = (string)$req->post('password', '');
        $permId   = $req->post('perm_id');
        $permId   = ($permId === null || $permId === '' || $permId === '0') ? null : (int)$permId;
        $isActive = $req->post('is_active', 1) ? 1 : 0;

        DefaultValidator::validateTextLength($username, 64, 3, lang('USERS_COL_USERNAME'));
        if (!preg_match('/^[A-Za-z0-9._@-]+$/', $username)) {
            returnErrorMessage(lang('USERS_ERR_USERNAME'));
        }

        if ($id === 0 || $password !== '') {
            if (mb_strlen($password) < self::MIN_PASSWORD) {
                returnErrorMessage(lang('ERR_PASSWORD_TOO_SHORT', self::MIN_PASSWORD));
            }
        }

        if (User::usernameExists($username, $id)) {
            returnErrorMessage(lang('USERS_ERR_EXISTS', $username));
        }

        $user = $id > 0 ? $this->find($id) : new User();

        /* Yetki yükseltmesi kapıları. */
        $superTouched = $permId === User::SUPER_ADMIN || ($id > 0 && $user->isSuperAdmin());
        if ($superTouched && !$me->isSuperAdmin()) {
            returnErrorMessage(lang('ERR_PERM'), 403, 'VNL_PERM');
        }

        if ($id > 0 && (int)$user->id === (int)$me->id) {
            if ($isActive === 0 || $permId !== ($user->perm_id !== null ? (int)$user->perm_id : null)) {
                returnErrorMessage(lang('USERS_ERR_SELF'), 403, 'VNL_PERM');
            }
        }

        $user->username  = $username;
        $user->name      = trim((string)$req->post('name', ''));
        $user->surname   = trim((string)$req->post('surname', ''));
        $user->email     = trim((string)$req->post('email', '')) ?: null;
        $user->perm_id   = $permId;
        $user->is_active = $isActive;

        if ($password !== '') {
            $user->setStoredPassword(EncryptionModule::PasswordHash($password));
        }

        $user->save();

        if ($id > 0 && $password !== '') {
            UserModule::ForgetAllDevices($user->id);   // eski "beni hatırla" jetonları geçersiz
            Audit::log('auth.password_changed', 'user', $user->id);
        }

        Audit::log($id > 0 ? 'user.update' : 'user.create', 'user', $user->id, [
            'username'  => $username,
            'perm_id'   => $permId,
            'is_active' => $isActive,
        ]);

        returnSuccessMessage(lang('USERS_SAVED'), ['id' => (int)$user->id]);
    }

    /** Yumuşak silme. Kendi hesabını ve (süper değilsen) süper yöneticiyi silemezsin. */
    public function delete($req)
    {
        Auth::CheckPermission(self::PERM, 'delete');

        $user = $this->find((int)$req->post('id'));
        $me   = vnl_user();

        if ((int)$user->id === (int)$me->id) {
            returnErrorMessage(lang('USERS_ERR_SELF'), 403, 'VNL_PERM');
        }

        if ($user->isSuperAdmin() && !$me->isSuperAdmin()) {
            returnErrorMessage(lang('ERR_PERM'), 403, 'VNL_PERM');
        }

        $user->delete();
        UserModule::ForgetAllDevices($user->id);
        UserPermissions::RevokeAll((int)$user->id);

        Audit::log('user.delete', 'user', $user->id, ['username' => $user->username]);

        returnSuccessMessage(lang('USERS_DELETED'));
    }

    /** Kullanıcının kod tabanlı yetkileri: {kod: {view, insert, edit, delete}} */
    public function permissions($req)
    {
        Auth::CheckPermission(self::PERM, 'view');

        $user = $this->find((int)$req->post('id'));

        returnSuccessMessage('', [
            'user_id'     => (int)$user->id,
            'super'       => $user->isSuperAdmin(),
            'permissions' => UserPermissions::ForUser((int)$user->id),
        ]);
    }

    /**
     * Yetkileri toplu yazar.
     *   permissions: {"Domains": ["view","edit"], "Users": []}
     * Boş liste o kodu kaldırır. Listede olmayan kodlara dokunulmaz.
     */
    public function setPermissions($req)
    {
        Auth::CheckPermission(self::PERM, 'edit');

        $user = $this->find((int)$req->post('id'));
        $me   = vnl_user();

        if ((int)$user->id === (int)$me->id) {
            returnErrorMessage(lang('USERS_ERR_SELF'), 403, 'VNL_PERM');
        }

        if ($user->isSuperAdmin() && !$me->isSuperAdmin()) {
            returnErrorMessage(lang('ERR_PERM'), 403, 'VNL_PERM');
        }

        $input = $req->post('permissions', []);
        if (is_string($input)) {
            $input = json_decode($input, true);
        }
        if (!is_array($input)) {
            returnErrorMessage(lang('ERR_INVALID_PARAMETERS_SENT'));
        }

        $applied = [];

        foreach ($input as $code => $abilities) {
            $code = (string)$code;

            if (!preg_match('/^[A-Za-z0-9_.:-]{1,100}$/', $code) || !is_array($abilities)) {
                returnErrorMessage(lang('ERR_INVALID_PARAMETERS_SENT'));
            }

            $abilities = array_values(array_intersect(UserPermissions::ABILITIES, array_map('strval', $abilities)));

            /* Süper olmayan, kendisinde olmayan yeteneği dağıtamaz. */
            if (!$me->isSuperAdmin()) {
                foreach ($abilities as $ability) {
                    if (!vnl_can($code, $ability)) {
                        returnErrorMessage(lang('ERR_PERM'), 403, 'VNL_PERM');
                    }
                }
            }

            if ($abilities === []) {
                UserPermissions::Revoke((int)$user->id, $code);
            } else {
                UserPermissions::Grant((int)$user->id, $code, $abilities);
            }

            $applied[$code] = $abilities;
        }

        Audit::log('user.permissions', 'user', $user->id, ['permissions' => $applied]);

        returnSuccessMessage(lang('USERS_PERMISSIONS_SAVED'));
    }

    /* ---------------------------------------------------------------- */

    private function find(int $id): User
    {
        $user = $id > 0 ? User::Select(['id' => $id]) : null;

        if (empty($user)) {
            returnErrorMessage(lang('USERS_ERR_NOT_FOUND'), 404);
            exit;
        }

        return $user;
    }

    /** Tablo satırı: yalnız HAM değerler. */
    private function row(User $u): array
    {
        return [
            'id'            => (int)$u->id,
            'username'      => (string)$u->username,
            'fullname'      => $u->fullname(),
            'email'         => $u->email,
            'perm_id'       => $u->perm_id !== null ? (int)$u->perm_id : null,
            'perm_name'     => $u->perm_name,
            'is_active'     => (int)($u->is_active ?? 1),
            'totp_enabled'  => (int)($u->totp_enabled ?? 0),
            'last_login_at' => $u->last_login_at !== null ? (string)$u->last_login_at : null,
            'created_at'    => $u->created_at !== null ? (string)$u->created_at : null,
        ];
    }
}

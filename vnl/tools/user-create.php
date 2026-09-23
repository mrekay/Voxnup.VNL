<?php
/*!
 * ============================================================
 *  Voxnup Not Laravel (VNL) — user:create komutu
 *  Kullanıcı (ilk yönetici dahil) oluşturur.
 *  Authors: Voxnup Authors
 * ============================================================
 *
 *   php vnl.php user:create --username=admin --super
 *   php vnl.php user:create --username=ali --name=Ali --surname=Veli --email=ali@example.com
 *
 * Parola verilmezse 24 karakterlik rastgele parola üretilir ve YALNIZ BİR
 * KEZ ekrana basılır. --password= komut geçmişine düşer; mümkünse
 * vermeyin ya da --password-stdin ile boru üzerinden verin.
 */

return static function (array $args): int {
    $o = vnl_cli_options($args);

    if (isset($o['help']) || in_array('-h', $args, true) || empty($o['username']) || $o['username'] === true) {
        fwrite(STDOUT, <<<TXT

  Kullanici olusturur.

    php vnl.php user:create --username=admin --super
    php vnl.php user:create --username=ali [--name=] [--surname=] [--email=]
                            [--perm=<rol id>] [--super] [--password=|--password-stdin]

  --super       perm_id = 1 (tum yetkiler; helpers/permissions.php)
  Parola verilmezse rastgele uretilir ve bir kez gosterilir.


TXT
        );

        return isset($o['help']) ? 0 : 1;
    }

    vnl_cli_boot();

    $username = trim((string)$o['username']);

    if (!preg_match('/^[A-Za-z0-9._@-]{3,64}$/', $username)) {
        fwrite(STDERR, "\n  Gecersiz kullanici adi (3-64 karakter: harf, rakam, . _ @ -).\n\n");

        return 1;
    }

    if (User::usernameExists($username)) {
        fwrite(STDERR, "\n  Bu kullanici adi zaten var: {$username}\n\n");

        return 1;
    }

    $password  = null;
    $generated = false;

    if (isset($o['password-stdin'])) {
        $password = rtrim((string)fgets(STDIN), "\r\n");
    } elseif (isset($o['password']) && $o['password'] !== true) {
        $password = (string)$o['password'];
    }

    if ($password === null || $password === '') {
        $password  = bin2hex(random_bytes(12));
        $generated = true;
    }

    if (mb_strlen($password) < 10) {
        fwrite(STDERR, "\n  Parola en az 10 karakter olmali.\n\n");

        return 1;
    }

    $permId = isset($o['super']) ? User::SUPER_ADMIN
        : ((isset($o['perm']) && $o['perm'] !== true) ? (int)$o['perm'] : null);

    $user = new User();
    $user->username  = $username;
    $user->name      = isset($o['name']) && $o['name'] !== true ? (string)$o['name'] : '';
    $user->surname   = isset($o['surname']) && $o['surname'] !== true ? (string)$o['surname'] : '';
    $user->email     = isset($o['email']) && $o['email'] !== true ? (string)$o['email'] : null;
    $user->perm_id   = $permId;
    $user->is_active = 1;
    $user->setStoredPassword(EncryptionModule::PasswordHash($password));
    $user->save();

    Audit::log('user.create', 'user', $user->id, ['username' => $username, 'perm_id' => $permId, 'via' => 'cli']);

    fwrite(STDOUT, "\n  Kullanici olusturuldu: {$username} (id {$user->id})"
        . ($permId === User::SUPER_ADMIN ? '  [super yonetici]' : '') . "\n");

    if ($generated) {
        fwrite(STDOUT, "  Parola: {$password}\n  Bu parola bir daha gosterilmeyecek.\n");
    }

    fwrite(STDOUT, "\n");

    return 0;
};

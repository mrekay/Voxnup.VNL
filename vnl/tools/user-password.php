<?php
/*!
 * ============================================================
 *  Voxnup Not Laravel (VNL) — user:password komutu
 *  Kullanıcının parolasını değiştirir.
 *  Authors: Voxnup Authors
 * ============================================================
 *
 *   php vnl.php user:password --username=admin
 *
 * Parola verilmezse rastgele üretilir ve bir kez basılır. Kullanıcının
 * bütün cihazlardaki "beni hatırla" jetonları iptal edilir.
 */

return static function (array $args): int {
    $o = vnl_cli_options($args);

    if (isset($o['help']) || empty($o['username']) || $o['username'] === true) {
        fwrite(STDOUT, "\n  Kullanicinin parolasini degistirir.\n\n"
            . "    php vnl.php user:password --username=admin [--password=|--password-stdin]\n\n"
            . "  Parola verilmezse rastgele uretilir ve bir kez gosterilir.\n\n");

        return isset($o['help']) ? 0 : 1;
    }

    vnl_cli_boot();

    $user = User::Select(['username' => (string)$o['username']]);

    if (empty($user)) {
        fwrite(STDERR, "\n  Kullanici bulunamadi: {$o['username']}\n\n");

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

    $user->setStoredPassword(EncryptionModule::PasswordHash($password));
    $user->save();

    UserModule::ForgetAllDevices($user->id);
    Audit::log('auth.password_changed', 'user', $user->id, ['via' => 'cli']);

    fwrite(STDOUT, "\n  Parola degistirildi: {$user->username}\n");
    if ($generated) {
        fwrite(STDOUT, "  Yeni parola: {$password}\n  Bu parola bir daha gosterilmeyecek.\n");
    }
    fwrite(STDOUT, "  Tum cihazlardaki kalici girisler iptal edildi.\n\n");

    return 0;
};

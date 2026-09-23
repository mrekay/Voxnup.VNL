<?php
/*!
 * ============================================================
 *  Voxnup Not Laravel (VNL) — user:totp komutu
 *  İki adımlı doğrulamayı (TOTP) açar ya da kapatır.
 *  Authors: Voxnup Authors
 * ============================================================
 *
 *   php vnl.php user:totp --username=admin            yeni sır üretir, açar
 *   php vnl.php user:totp --username=admin --disable  kapatır
 *
 * Sır APP_ENCRYPTION_KEY ile şifrelenip saklanır; otpauth:// adresi ve
 * sır YALNIZ BİR KEZ basılır (uygulamaya elle girilir ya da QR'a çevrilir).
 * Web arayüzü isteyen proje aynı adımları Totp sınıfıyla yapar.
 */

return static function (array $args): int {
    $o = vnl_cli_options($args);

    if (isset($o['help']) || empty($o['username']) || $o['username'] === true) {
        fwrite(STDOUT, "\n  Iki adimli dogrulamayi acar/kapatir.\n\n"
            . "    php vnl.php user:totp --username=admin [--issuer=\"VNL Panel\"]\n"
            . "    php vnl.php user:totp --username=admin --disable\n\n");

        return isset($o['help']) ? 0 : 1;
    }

    vnl_cli_boot();

    $user = User::Select(['username' => (string)$o['username']]);

    if (empty($user)) {
        fwrite(STDERR, "\n  Kullanici bulunamadi: {$o['username']}\n\n");

        return 1;
    }

    if (!array_key_exists('totp_enabled', $user->getAttributes())) {
        fwrite(STDERR, "\n  tbl_users.totp_* sutunlari yok. Once: php vnl.php migrate --run\n\n");

        return 1;
    }

    /* Yeni sır (ya da kapatma) ile tekrar kullanım sayacı da sıfırlanır
       (0007; sütun yoksa dokunulmaz). */
    $hasLastStep = array_key_exists('totp_last_step', $user->getAttributes());

    if (isset($o['disable'])) {
        $user->totp_secret  = null;
        $user->totp_enabled = 0;
        if ($hasLastStep) {
            $user->totp_last_step = null;
        }
        $user->save();

        Audit::log('auth.totp_disabled', 'user', $user->id, ['via' => 'cli']);
        fwrite(STDOUT, "\n  Iki adimli dogrulama kapatildi: {$user->username}\n\n");

        return 0;
    }

    global $cDefinitions;

    $issuer = (isset($o['issuer']) && $o['issuer'] !== true) ? (string)$o['issuer'] : $cDefinitions->appName();
    $secret = Totp::generateSecret();

    try {
        $user->totp_secret = Totp::encryptSecret($secret);
    } catch (Throwable $e) {
        fwrite(STDERR, "\n  Sir sifrelenemedi: " . $e->getMessage() . "\n\n");

        return 1;
    }

    $user->totp_enabled = 1;
    if ($hasLastStep) {
        $user->totp_last_step = null;
    }
    $user->save();

    Audit::log('auth.totp_enabled', 'user', $user->id, ['via' => 'cli']);

    if (!$hasLastStep) {
        fwrite(STDERR, "\n  UYARI: tbl_users.totp_last_step yok (vnl/migrations/0007). Girisin ikinci\n"
            . "  adimi migration uygulanana kadar kapali kalir: php vnl.php migrate --run\n");
    }

    fwrite(STDOUT, "\n  Iki adimli dogrulama acildi: {$user->username}\n\n"
        . "  Sir     : {$secret}\n"
        . "  Adres   : " . Totp::uri($secret, (string)$user->username, $issuer) . "\n\n"
        . "  Bu bilgiler bir daha gosterilmeyecek. Dogrulama uygulamasina ekleyin.\n\n");

    return 0;
};

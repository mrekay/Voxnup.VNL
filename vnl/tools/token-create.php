<?php
/*!
 * ============================================================
 *  Voxnup Not Laravel (VNL) — token:create komutu
 *  API jetonu üretir (Authorization: Bearer).
 *  Authors: Voxnup Authors
 * ============================================================
 *
 *   php vnl.php token:create --user=admin --name="yedek betiği"
 *   php vnl.php token:create --user=admin --name=izleme --abilities=jobs:status,domains:getList --days=90
 *
 * Jeton YALNIZ BİR KEZ basılır; tabloda SHA-256 özeti durur.
 */

return static function (array $args): int {
    $o = vnl_cli_options($args);

    if (isset($o['help']) || empty($o['user']) || $o['user'] === true || empty($o['name']) || $o['name'] === true) {
        fwrite(STDOUT, "\n  API jetonu uretir.\n\n"
            . "    php vnl.php token:create --user=<kullanici adi> --name=<ad>\n"
            . "                             [--abilities=*|modul,modul:metot] [--days=<gun>]\n\n"
            . "  Yetenekler modul duzeyinde bir dar listedir; kullanicinin kendi\n"
            . "  yetkileri (Auth::CheckPermission) ayrica uygulanir.\n\n");

        return isset($o['help']) ? 0 : 1;
    }

    vnl_cli_boot();

    $user = User::Select(['username' => (string)$o['user']]);

    if (empty($user)) {
        fwrite(STDERR, "\n  Kullanici bulunamadi: {$o['user']}\n\n");

        return 1;
    }

    $abilities = ['*'];
    if (isset($o['abilities']) && $o['abilities'] !== true) {
        $abilities = array_values(array_filter(array_map('trim', explode(',', (string)$o['abilities']))));
    }

    foreach ($abilities as $ability) {
        if (!preg_match('/^(\*|[a-z0-9_]+(:(\*|[a-z0-9_]+))?)$/i', $ability)) {
            fwrite(STDERR, "\n  Gecersiz yetenek: {$ability}\n\n");

            return 1;
        }
    }

    $days  = (isset($o['days']) && $o['days'] !== true) ? max(1, (int)$o['days']) : null;
    $plain = ApiToken::issue((int)$user->id, (string)$o['name'], $abilities, $days);

    Audit::actingAs((int)$user->id);
    Audit::log('api.token_created', 'user', $user->id, ['name' => (string)$o['name'], 'abilities' => $abilities, 'via' => 'cli']);

    fwrite(STDOUT, "\n  Jeton uretildi ({$user->username}, " . implode(',', $abilities)
        . ($days ? ", {$days} gun" : ', suresiz') . ")\n\n"
        . "    {$plain}\n\n"
        . "  Bu jeton bir daha gosterilmeyecek.\n"
        . "  Kullanim: curl -X POST -H \"Authorization: Bearer <jeton>\" https://<host>/app/<modul>/<metot>\n\n");

    return 0;
};

<?php
/*!
 * ============================================================
 *  Voxnup Not Laravel (VNL) — token:revoke komutu
 *  API jetonunu iptal eder; --list jetonları gösterir.
 *  Authors: Voxnup Authors
 * ============================================================
 *
 *   php vnl.php token:revoke --list --user=admin
 *   php vnl.php token:revoke --id=12
 */

return static function (array $args): int {
    $o = vnl_cli_options($args);

    if (isset($o['help']) || (empty($o['id']) && !isset($o['list']))) {
        fwrite(STDOUT, "\n  API jetonunu iptal eder.\n\n"
            . "    php vnl.php token:revoke --list --user=<kullanici adi>\n"
            . "    php vnl.php token:revoke --id=<jeton id>\n\n");

        return isset($o['help']) ? 0 : 1;
    }

    vnl_cli_boot();

    if (isset($o['list'])) {
        $user = (!empty($o['user']) && $o['user'] !== true) ? User::Select(['username' => (string)$o['user']]) : null;

        if (empty($user)) {
            fwrite(STDERR, "\n  --list icin --user=<kullanici adi> gerekli ve kullanici var olmali.\n\n");

            return 1;
        }

        fwrite(STDOUT, "\n  {$user->username} jetonlari\n\n");
        foreach (ApiToken::forUser((int)$user->id) as $row) {
            fwrite(STDOUT, sprintf(
                "    #%-5d %-24s %-28s son: %-19s %s\n",
                $row->id,
                mb_substr((string)$row->name, 0, 24),
                mb_substr((string)$row->abilities, 0, 28),
                (string)($row->last_used_at ?? '-'),
                $row->revoked_at ? 'IPTAL' : ($row->expires_at ? 'bitis ' . $row->expires_at : 'suresiz')
            ));
        }
        fwrite(STDOUT, "\n");

        return 0;
    }

    $id = (int)$o['id'];

    if (!ApiToken::revoke($id)) {
        fwrite(STDERR, "\n  Jeton bulunamadi ya da zaten iptal: #{$id}\n\n");

        return 1;
    }

    Audit::log('api.token_revoked', 'api_token', $id, ['via' => 'cli']);
    fwrite(STDOUT, "\n  Jeton iptal edildi: #{$id}\n\n");

    return 0;
};

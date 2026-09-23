<?php
/*!
 * ============================================================
 *  Voxnup Not Laravel (VNL) — API Jetonu
 *  Authorization: Bearer ile oturumsuz action çağrısı.
 *  Authors: Voxnup Authors
 * ============================================================
 */

/**
 * API jetonları (tbl_vnl_api_tokens).
 *
 *   $plain = ApiToken::issue($userId, 'yedekleme betiği', ['backups', 'domains:getList'], 90);
 *   // $plain YALNIZ BİR KEZ gösterilir; tabloda SHA-256 özeti durur.
 *
 *   curl -X POST -H "Authorization: Bearer $plain" https://panel/app/domains/getList
 *
 * Dağıtıcı (VnlDispatcher) başlığı görünce oturum ve CSRF yerine jetonu
 * doğrular, isteği jetonun kullanıcısıyla çalıştırır. Jeton tarayıcı
 * tarafından kendiliğinden gönderilmediği için CSRF'e açık değildir.
 *
 * Yetenekler (abilities) modül düzeyinde bir DAR LİSTEDİR; kullanıcının
 * kendi yetkileri (Auth::CheckPermission) ayrıca uygulanır:
 *   ['*']                         her modül
 *   ['domains']                   /app/domains/*
 *   ['domains:getList', 'jobs:*'] tek metot / modülün tümü
 */
class ApiToken
{
    public const TABLE  = 'tbl_vnl_api_tokens';
    public const PREFIX = 'vnl_';

    /** Yeni jeton üretir; DÜZ metni döner (bir kez gösterin). */
    public static function issue(int $userId, string $name, array $abilities = ['*'], ?int $days = null): string
    {
        $plain = self::PREFIX . bin2hex(random_bytes(32));

        Database::table(self::TABLE)->insert([
            'user_id'    => $userId,
            'name'       => mb_substr($name, 0, 100),
            'token_hash' => hash('sha256', $plain),
            'abilities'  => json_encode(array_values(array_map('strval', $abilities))),
            'expires_at' => ($days !== null && $days > 0) ? date('Y-m-d H:i:s', time() + $days * 86400) : null,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        return $plain;
    }

    /**
     * Düz jetonu doğrular; geçerliyse satırı (abilities çözülmüş), değilse null.
     * İptal edilmiş ve süresi geçmiş jetonlar geçmez.
     */
    public static function authenticate(string $plain): ?object
    {
        if (!str_starts_with($plain, self::PREFIX) || strlen($plain) !== strlen(self::PREFIX) + 64) {
            return null;
        }

        $row = Database::table(self::TABLE)
            ->where('token_hash', hash('sha256', $plain))
            ->whereNull('revoked_at')
            ->first();

        if (!$row) {
            return null;
        }

        if ($row->expires_at !== null && strtotime((string)$row->expires_at) < time()) {
            return null;
        }

        /* Son kullanım dakikada bir yazılır. */
        if ($row->last_used_at === null || strtotime((string)$row->last_used_at) < time() - 60) {
            Database::table(self::TABLE)->where('id', $row->id)->update(['last_used_at' => date('Y-m-d H:i:s')]);
        }

        $abilities      = json_decode((string)$row->abilities, true);
        $row->abilities = is_array($abilities) ? $abilities : [];

        return $row;
    }

    /** Jeton bu modül/metoda izin veriyor mu? */
    public static function allows(object $token, string $module, string $method): bool
    {
        $module = strtolower($module);
        $method = strtolower($method);

        foreach ((array)($token->abilities ?? []) as $ability) {
            $ability = strtolower(trim((string)$ability));

            if ($ability === '*' || $ability === $module || $ability === $module . ':*'
                || $ability === $module . ':' . $method) {
                return true;
            }
        }

        return false;
    }

    public static function revoke(int $id): bool
    {
        return Database::table(self::TABLE)
            ->where('id', $id)
            ->whereNull('revoked_at')
            ->update(['revoked_at' => date('Y-m-d H:i:s')]) > 0;
    }

    /** Kullanıcının jetonları (özet sütunu hariç). */
    public static function forUser(int $userId): array
    {
        return Database::table(self::TABLE)
            ->where('user_id', $userId)
            ->orderByDesc('id')
            ->get(['id', 'name', 'abilities', 'last_used_at', 'expires_at', 'created_at', 'revoked_at'])
            ->all();
    }

    /** İstekteki "Authorization: Bearer …" değeri; yoksa null. */
    public static function fromRequest(): ?string
    {
        $header = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';

        if ($header === '' && function_exists('getallheaders')) {
            foreach ((array)getallheaders() as $name => $value) {
                if (strcasecmp((string)$name, 'Authorization') === 0) {
                    $header = (string)$value;
                    break;
                }
            }
        }

        if (!preg_match('/^Bearer\s+(\S+)$/i', trim((string)$header), $m)) {
            return null;
        }

        return $m[1];
    }
}

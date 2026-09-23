<?php
/*!
 * ============================================================
 *  Voxnup Not Laravel (VNL) — Denetim Kaydı
 *  Kim, ne zaman, neyi yaptı: tbl_vnl_audit_log.
 *  Authors: Voxnup Authors
 * ============================================================
 */

/**
 * Denetim kaydı.
 *
 *   Audit::log('domain.create', 'domain', $domain->id, ['name' => $name]);
 *   Audit::log('auth.login_failed', 'user', null, ['username' => $u]);
 *
 *   // Yönetim ekranı: sorgu kurucusu döner
 *   Audit::query()->where('user_id', 5)->orderByDesc('id')->limit(50)->get();
 *
 * Kullanıcı (vnl_user()), IP, tarayıcı ve zaman kendiliğinden doldurulur.
 * Konsolda ya da kuyruk işinde işlemi yapan kişi Audit::actingAs($userId)
 * ile bildirilir.
 *
 * ASLA İSTİSNA FIRLATMAZ: denetim kaydı yazılamadı diye asıl işlem (ve
 * kullanıcının isteği) düşmemeli. Hata PHP günlüğüne yazılır.
 *
 * $data içinde adı password / token / secret İÇEREN anahtarların değeri (iç
 * içe dizilerde de, değer dizi olsa bile tamamı) '[redacted]' olarak yazılır
 * — parola ya da jeton yanlışlıkla kalıcı hale gelmesin, ama alanın var
 * olduğu kayıtta görünsün.
 *
 * $data ANAHTARLARI alan adıdır, veri değil. Veriden türeyen tanımlayıcılar
 * (izin kodu, alan adı, kullanıcı adı ...) anahtar yapılmaz, kayıt listesi
 * olarak yazılır:
 *
 *   // YANLIŞ: 'ApiTokens' anahtarı süzgece takılır
 *   Audit::log('user.permissions', 'user', $id, ['permissions' => ['ApiTokens' => ['view']]]);
 *   // DOĞRU
 *   Audit::log('user.permissions', 'user', $id, ['permissions' => [['code' => 'ApiTokens', 'abilities' => ['view']]]]);
 *
 * Yerleşik olaylar: auth.login, auth.login_failed, auth.logout,
 * auth.password_changed, auth.totp_enabled, auth.totp_disabled,
 * user.create, user.update, user.delete, user.permissions, api.token_created,
 * api.token_revoked.
 */
class Audit
{
    public const TABLE = 'tbl_vnl_audit_log';

    /** Değeri kayda girmeyecek anahtar parçaları (küçük harf). */
    private const SENSITIVE = ['password', 'token', 'secret'];

    private static ?int $actor = null;

    public static function log(string $action, ?string $subjectType = null, $subjectId = null, array $data = []): void
    {
        try {
            $data = self::scrub($data);

            Database::table(self::TABLE)->insert([
                'user_id'      => self::actorId(),
                'action'       => mb_substr($action, 0, 100),
                'subject_type' => $subjectType !== null ? mb_substr($subjectType, 0, 100) : null,
                'subject_id'   => ($subjectId === null || $subjectId === '') ? null : mb_substr((string)$subjectId, 0, 64),
                'data'         => $data === [] ? null
                    : json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PARTIAL_OUTPUT_ON_ERROR),
                'ip'           => self::ip(),
                'user_agent'   => PHP_SAPI === 'cli' ? 'cli' : mb_substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
                'created_at'   => date('Y-m-d H:i:s'),
            ]);
        } catch (Throwable $e) {
            error_log('[VNL][Audit] ' . $action . ' kaydedilemedi: ' . $e->getMessage());
        }
    }

    /** Yönetim ekranları için sorgu kurucusu. */
    public static function query()
    {
        return Database::table(self::TABLE);
    }

    /**
     * Oturumu olmayan bağlamda (konsol, kuyruk işi) işlemi yapan kullanıcı.
     * null verildiğinde yeniden vnl_user()'a bakılır.
     */
    public static function actingAs(?int $userId): void
    {
        self::$actor = ($userId !== null && $userId > 0) ? $userId : null;
    }

    /* ---------------------------------------------------------------- */

    private static function actorId(): ?int
    {
        if (self::$actor !== null) {
            return self::$actor;
        }

        if (function_exists('vnl_user')) {
            $user = vnl_user();

            if ($user !== null && !empty($user->id)) {
                return (int)$user->id;
            }
        }

        return null;
    }

    private static function ip(): ?string
    {
        if (PHP_SAPI === 'cli') {
            return null;
        }

        $ip = (string)($_SERVER['REMOTE_ADDR'] ?? '');

        return $ip === '' ? null : mb_substr($ip, 0, 45);
    }

    private static function scrub(array $data): array
    {
        $clean = [];

        foreach ($data as $key => $value) {
            $lower = strtolower((string)$key);

            foreach (self::SENSITIVE as $needle) {
                if (str_contains($lower, $needle)) {
                    /* Değer türü ne olursa olsun (dizi dahil) maskelenir;
                       anahtar düşürülmez, alanın varlığı kayıtta görünür. */
                    $clean[$key] = '[redacted]';
                    continue 2;
                }
            }

            $clean[$key] = is_array($value) ? self::scrub($value) : $value;
        }

        return $clean;
    }
}

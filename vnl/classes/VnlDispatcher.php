<?php
/*!
 * ============================================================
 *  Voxnup Not Laravel (VNL) — Action Dağıtıcısı
 *  POST /app/<modül>/<metot> → actions/action_<modül>.php
 *  Authors: Voxnup Authors
 * ============================================================
 */

/**
 * Action dağıtıcısı (3.4.0).
 *
 * controller/app.php yalnız VnlDispatcher::handle($req) çağırır. Mantık
 * çekirdektedir: 3.3'e kadar proje dosyasındaydı ve oradaki güvenlik
 * açıkları (her HTTP yöntemi, CSRF yok, varsayılan açık, __construct dahil
 * her metot çağrılabilir, ham istisna mesajı) güncellemeyle kapanamıyordu.
 * Farklı davranış isteyen proje classes/VnlDispatcher.php ile ezer.
 *
 * Sıra — her adım başarısızsa JSON {message, devcode} ile biter:
 *
 *   1. Yöntem   POST değilse                          405  VNL_METHOD
 *   2. Ad       modül/metot [a-z0-9_]+ değilse        404  VNL_NOT_FOUND
 *   3. Sınıf    dosya/sınıf/metot yoksa; metot public
 *               değilse, static ise ya da __ ile başlıyorsa  404
 *   4. Kimlik   Authorization: Bearer varsa jeton     401  VNL_TOKEN / 403 VNL_PERM
 *               (jetonla gelen istekte 5 ve 6 atlanır)
 *   5. Giriş    sınıfın PUBLIC_METHODS listesinde
 *               değilse ve girişsizse                 401  VNL_AUTH
 *   6. CSRF     Origin + CSRF jetonu                  403  VNL_CSRF
 *               (sınıfın CSRF_EXEMPT listesi hariç)
 *   7. Çağrı    throwErrorMessage → kendi kodu; başka her istisna günlüğe
 *               yazılır, kullanıcıya genel metin      500  VNL_FATAL (+ ref)
 *
 * Action sınıfındaki isteğe bağlı sabitler:
 *
 *   public const PUBLIC_METHODS = ['login'];     // girişsiz çağrılabilir
 *   public const CSRF_EXEMPT    = ['webhook'];   // imzasını KENDİSİ doğrular
 */
class VnlDispatcher
{
    private const NAME_PATTERN = '/^[a-z0-9_]+$/i';

    public static function handle($req = null): never
    {
        global $CurrentUser;

        $req = $req ?? ($GLOBALS['req'] ?? new Request());

        vnl_json_header();

        /* 1) Yalnız POST. GET ile durum değiştiren uç, bir <img src> ile
              tetiklenebilirdi. */
        if (strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            if (!headers_sent()) {
                header('Allow: POST');
            }
            self::deny(lang('ERR_METHOD_NOT_ALLOWED'), 405, 'VNL_METHOD');
        }

        /* 2) Ad denetimi — dosya yoluna girecek parçalar. */
        $module = route(1);
        $method = route(2);

        if (!is_string($module) || !is_string($method)
            || !preg_match(self::NAME_PATTERN, $module) || !preg_match(self::NAME_PATTERN, $method)) {
            self::notFound();
        }

        /* 3) Sınıf ve metot. */
        $path = self::resolve($module);
        if ($path === null) {
            self::notFound();
        }

        $authFile = VNL_ROOT . '/actions/Auth.php';
        if (is_file($authFile)) {
            require_once $authFile;
        }

        $class = 'action_' . $module;

        try {
            require_once $path;

            if (!class_exists($class, false)) {
                self::notFound();
            }

            $reflection = new ReflectionMethod($class, $method);
        } catch (ReflectionException $e) {
            self::notFound();
        } catch (Throwable $e) {
            self::fail($e);
        }

        $name = $reflection->getName();

        if (!$reflection->isPublic() || $reflection->isStatic() || $reflection->isAbstract()
            || $reflection->isConstructor() || $reflection->isDestructor() || str_starts_with($name, '__')) {
            self::notFound();
        }

        $lower         = strtolower($name);
        $publicMethods = self::classList($class, 'PUBLIC_METHODS');
        $csrfExempt    = self::classList($class, 'CSRF_EXEMPT');

        /* 4) Kimlik: API jetonu ya da oturum (5 giriş, 6 CSRF). */
        $bearer = class_exists('ApiToken') ? ApiToken::fromRequest() : null;

        if ($bearer !== null) {
            try {
                self::authenticateToken($bearer, $module, $name);
            } catch (Throwable $e) {
                self::fail($e);   // ör. tablo yok: ayrıntı günlüğe
            }
        } else {
            /* 5) Varsayılan: giriş zorunlu. CSRF'ten ÖNCE bakılır: oturumu
                  biten (boşta kalma, GC) sekmenin jetonu da eskimiştir; önce
                  CSRF'e bakılsaydı istemci 401 yerine 403 VNL_CSRF alır ve
                  vnl.js onu /login'e hiç göndermezdi. Güvenlik değişmez:
                  401 yolunda action çalışmaz; PUBLIC_METHODS ve girişli
                  istekler CSRF denetiminden yine geçer. */
            if (!in_array($lower, $publicMethods, true) && !vnl_logged_in()) {
                self::deny(lang('ERR_LOGIN_FIRST'), 401, 'VNL_AUTH');
            }

            if (!in_array($lower, $csrfExempt, true)) {
                if (!vnl_same_origin() || !vnl_csrf_check()) {
                    self::deny(lang('ERR_CSRF'), 403, 'VNL_CSRF');
                }
            }
        }

        /* 7) Çağrı. */
        try {
            $instance = new $class();
            $reflection->invoke($instance, $req);
        } catch (VnlHttpException $e) {
            self::cleanBuffers();
            self::deny($e->getMessage(), $e->status(), $e->devcode());
        } catch (Throwable $e) {
            /* Projenin ezdiği eski bir throwErrorMessage() düz Exception
               fırlatıyor olabilir: kaynağı yığından tanınır (3.3 davranışı). */
            if ($e instanceof Exception && (($e->getTrace()[0]['function'] ?? '') === 'throwErrorMessage')) {
                $code = (int)$e->getCode();
                self::cleanBuffers();
                self::deny($e->getMessage(), ($code >= 400 && $code <= 599) ? $code : 400);
            }

            self::fail($e);
        }

        exit;
    }

    /* ---------------------------------------------------------------- */

    /** actions/action_<modül>.php, yoksa etkin eklentilerdeki karşılığı. */
    private static function resolve(string $module): ?string
    {
        $path = VNL_ROOT . '/actions/action_' . $module . '.php';

        if (is_file($path)) {
            return $path;
        }

        foreach (vnl_enabled_extensions() as $extension) {
            $path = VNL_ROOT . '/extensions/' . $extension . '/actions/action_' . $module . '.php';

            if (is_file($path)) {
                return $path;
            }
        }

        return null;
    }

    /** Sınıf sabitindeki metot listesi, küçük harfe çevrilmiş. */
    private static function classList(string $class, string $constant): array
    {
        if (!defined($class . '::' . $constant)) {
            return [];
        }

        $list = constant($class . '::' . $constant);

        return is_array($list) ? array_map(static fn($m) => strtolower((string)$m), $list) : [];
    }

    /**
     * Bearer jetonunu doğrular ve isteği jetonun kullanıcısıyla sürdürür.
     * Oturuma dokunulmaz; kimlik yalnız bu istek içindir.
     */
    private static function authenticateToken(string $bearer, string $module, string $method): void
    {
        global $CurrentUser;

        $token = ApiToken::authenticate($bearer);

        if ($token === null) {
            self::deny(lang('ERR_TOKEN_INVALID'), 401, 'VNL_TOKEN');
        }

        if (!ApiToken::allows($token, $module, $method)) {
            self::deny(lang('ERR_PERM'), 403, 'VNL_PERM');
        }

        $user = User::Select(['id' => (int)$token->user_id]);

        if (empty($user) || empty($user->id) || !UserModule::IsActiveUser($user)) {
            self::deny(lang('ERR_TOKEN_INVALID'), 401, 'VNL_TOKEN');
        }

        $CurrentUser = $user;
    }

    private static function notFound(): never
    {
        self::deny(lang('FUNCTION_NOT_FOUND'), 404, 'VNL_NOT_FOUND');
    }

    /**
     * Hata yanıtı yazar ve HER DURUMDA betiği bitirir. returnErrorMessage()
     * PHPEXCELMODE tanımlıyken durmaz; bir güvenlik kapısının ardından akışın
     * sürmesi kabul edilemez.
     */
    private static function deny(string $message, int $status, string $devcode = ''): never
    {
        http_response_code($status);
        vnl_json_header();
        echo json_encode(['message' => $message, 'devcode' => $devcode]);

        exit;
    }

    /** Action yarıda bir şey bastıysa hata gövdesine karışmasın. */
    private static function cleanBuffers(): void
    {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
    }

    /**
     * Beklenmeyen hata: ayrıntı günlüğe, kullanıcıya genel metin + başvuru
     * kodu. APP_DEBUG=true iken istisna sınıfı, mesajı ve konumu da döner.
     */
    private static function fail(Throwable $e): never
    {
        $ref = vnl_log_exception($e);

        self::cleanBuffers();

        if (!headers_sent()) {
            http_response_code(500);
        }
        vnl_json_header();

        if (vnl_debug()) {
            vnl_render_error_json($e);
        }

        echo json_encode([
            'message' => lang('ERR_GENERAL'),
            'devcode' => 'VNL_FATAL',
            'ref'     => $ref,
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }
}

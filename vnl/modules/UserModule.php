<?php
/*!
 * ============================================================
 *  Voxnup Not Laravel (VNL) — Kullanıcı Oturumu
 *  Giriş/çıkış, "beni hatırla", boşta kalma sınırı.
 *  Authors: Voxnup Authors
 * ============================================================
 */

class UserModule
{
    /** "Beni hatırla" varsayılan süresi (gün). .env VNL_REMEMBER_DAYS ezer; 0 kapatır. */
    const REMEMBER_DAYS = 30;

    /** Boşta kalma sınırı varsayılanı (sn). .env VNL_SESSION_IDLE ezer; 0 kapatır. */
    const SESSION_IDLE = 3600;

    /**
     * Oturumu kullanıcıya bağlar.
     *
     * 3.4.0: önce oturum kimliği yenilenir (session fixation — giriş öncesi
     * kimlik saldırganın dayattığı kimlik olabilir), sonra anahtarlar tek
     * seferde yazılır ve CSRF jetonu döndürülür.
     */
    static function SetUser($user_data)
    {
        if (isset($user_data->id) && !empty($user_data->id) && $user_data->id > 0) {
            Session::regenerate();
            Session::delete('_2fa_pending');
            Session::setMany([
                'login'      => true,
                'lock'       => false,
                'user_id'    => $user_data->id,
                'login_time' => time(),
                'last_seen'  => time(),
            ]);
            vnl_csrf_rotate();
        }
    }

    /**
     * Parola (ve varsa ikinci adım) doğrulandıktan sonra girişi tamamlar:
     * oturum, son giriş bilgisi, isteğe bağlı kalıcı giriş ve denetim kaydı.
     * action_login::login() ve ::totp() buradan geçer.
     */
    static function CompleteLogin($user, bool $remember = false): void
    {
        global $CurrentUser;

        self::SetUser($user);
        $CurrentUser = $user;

        if (method_exists($user, 'MarkLogin')) {
            try {
                $user->MarkLogin(self::CurrentIp());
            } catch (Throwable $e) {
                error_log('[VNL][Auth] Son giriş bilgisi yazılamadı: ' . $e->getMessage());
            }
        }

        if ($remember) {
            self::RememberMe($user);
        }

        Audit::log('auth.login', 'user', $user->id);
    }

    /**
     * Oturumdaki kullanıcıyı $CurrentUser'a yükler.
     *
     * 3.4.0:
     *  - VNL_SESSION_IDLE saniyeden uzun süre istek gelmemişse oturum biter
     *    ve bu cihazın "beni hatırla" jetonu da iptal edilir (F06.3);
     *    kullanıcı girişsiz kalır, çekirdek /login'e yollar. Oturum verisi
     *    daha önce silinmişse (tarayıcı kapandı, GC) aynı sınırı
     *    CheckCookieAuth() jetonun son kullanımına bakarak uygular.
     *    VNL_IDLE_KEEPS_REMEMBER=true eski davranışı açar: oturum aynı
     *    istekte çerezle sessizce yeniden açılır.
     *  - Girişli istek dakikada bir last_seen'i ve bu cihazın jetonunun
     *    used_at'ini yeniler (boşta kalma ölçüsü jetonda da tutulur).
     *  - Pasif (is_active = 0) ya da silinmiş kullanıcının oturumu kapanır:
     *    hesabı dondurulan kişi açık sekmesiyle çalışmayı sürdüremez.
     *  - SetOnlineToLastActivity() yalnız tanımlıysa çağrılır (3.3.1'de
     *    tanımsızdı ve girişli her istek 500 veriyordu).
     */
    static function SetCurrentUserInfo()
    {
        global $CurrentUser;

        if (empty(Session::get('user_id'))) {
            return;
        }

        $idle     = self::idleLimit();
        $lastSeen = (int)Session::get('last_seen');

        if ($idle > 0 && $lastSeen > 0 && time() - $lastSeen > $idle) {
            if (!self::idleKeepsRemember()) {
                /* Tam kapanış: bu cihazın kalıcı jetonu da gider — aksi halde
                   çerez oturumu aynı istekte yeniden açar ve sınır, "beni
                   hatırla" diyen her cihazda hiçbir şey yapmazdı. */
                try {
                    self::ForgetMe();
                } catch (Throwable $e) {
                    error_log('[VNL][Auth] ForgetMe: ' . $e->getMessage());
                }

                self::EndSession();

                return;
            }

            self::EndSession();
            self::CheckCookieAuth();

            if (empty(Session::get('user_id'))) {
                return;
            }

            $lastSeen = (int)Session::get('last_seen');
        }

        $user = User::Select(['id' => Session::get('user_id')]);

        if (empty($user) || empty($user->id) || !self::IsActiveUser($user)) {
            self::LogOut();

            return;
        }

        $CurrentUser = $user;

        /* last_seen dakikada bir yazılır: her istekte oturum dosyasını
           yeniden yazmak gereksiz disk yükü ve kilit demek. */
        if (time() - $lastSeen >= 60) {
            Session::set('last_seen', time());
            self::touchRememberToken((int)$user->id);
        }

        if (method_exists($CurrentUser, 'SetOnlineToLastActivity')) {
            try {
                $CurrentUser->SetOnlineToLastActivity();
            } catch (Throwable $e) {
                /* Son etkinlik bilgisi yazılamadı diye istek düşmesin. */
                error_log('[VNL][Auth] SetOnlineToLastActivity: ' . $e->getMessage());
            }
        }
    }

    /**
     * Hesap etkin mi?
     *
     * is_active sütunu yoksa ya da NULL ise etkin sayılır (eski şema).
     * 0 (3.4 şeması) ve 2 (2.x geleneği) pasiftir.
     */
    static function IsActiveUser($user): bool
    {
        if (empty($user) || !is_object($user)) {
            return false;
        }

        $attributes = method_exists($user, 'getAttributes') ? $user->getAttributes() : (array)$user;

        if (!array_key_exists('is_active', $attributes) || $attributes['is_active'] === null) {
            return true;
        }

        return !in_array((int)$attributes['is_active'], [0, 2], true);
    }

    /** "Beni hatırla" süresi (gün); 0 ise özellik kapalıdır. */
    static function rememberDays(): int
    {
        return max(0, (int)VnlEnv::get('VNL_REMEMBER_DAYS', self::REMEMBER_DAYS));
    }

    /** Boşta kalma sınırı (sn); 0 ise sınır yoktur. */
    static function idleLimit(): int
    {
        return max(0, (int)VnlEnv::get('VNL_SESSION_IDLE', self::SESSION_IDLE));
    }

    /**
     * Boşta kalan oturum "beni hatırla" jetonuyla sessizce yeniden açılsın
     * mı? (VNL_IDLE_KEEPS_REMEMBER, varsayılan false.)
     */
    static function idleKeepsRemember(): bool
    {
        return VnlEnv::get('VNL_IDLE_KEEPS_REMEMBER', false) === true;
    }

    /**
     * Bu cihazın kalıcı jetonunun son kullanımını (used_at) yeniler.
     * SetCurrentUserInfo() dakikada bir çağırır; CheckCookieAuth() boşta
     * kalma sınırını bu değere göre uygular.
     */
    private static function touchRememberToken(int $userId): void
    {
        $raw = $_COOKIE[self::rememberCookieName()] ?? '';

        if ($raw === '' || !is_string($raw) || $userId <= 0) {
            return;
        }

        try {
            RememberToken::where('token_hash', RememberToken::hashOf($raw))
                ->where('user_id', $userId)
                ->update(['used_at' => date('Y-m-d H:i:s')]);
        } catch (Throwable $e) {
            error_log('[VNL][Auth] Jeton kullanımı yazılamadı: ' . $e->getMessage());
        }
    }

    /**
     * Kalıcı giriş çerezini kontrol eder ve geçerliyse oturumu açar.
     *
     * Çerezde jetonun kendisi, veritabanında yalnızca SHA-256 özeti durur.
     * Her başarılı kullanımda jeton yenilenir: çalınan bir çerez, meşru
     * kullanıcı siteye girdiği anda geçersiz kalır.
     */
    static function CheckCookieAuth()
    {
        global $cDefinitions;

        /* VNL 2.x'in {prefix}auth çerezi kullanıcı adı ve DÜZ PAROLA
           taşıyordu; karşılaşıldığı yerde tarayıcıdan silinir. */
        $legacy = $cDefinitions->getCookiePrefix() . 'auth';
        if (!empty($_COOKIE[$legacy])) {
            setcookie($legacy, '', time() - 3600, '/');
            unset($_COOKIE[$legacy]);
        }

        if (!empty(Session::get('login'))) {
            return;
        }

        $raw = $_COOKIE[self::rememberCookieName()] ?? '';
        if ($raw === '' || !is_string($raw)) {
            return;
        }

        /* Özellik kapatıldıysa (VNL_REMEMBER_DAYS=0) eski çerezler de geçmez. */
        if (self::rememberDays() === 0) {
            self::ForgetMe();

            return;
        }

        $token = RememberToken::where('token_hash', RememberToken::hashOf($raw))->first();

        /* Tanınmayan jeton: çerez eski, iptal edilmiş ya da uydurma. */
        if (empty($token)) {
            self::clearRememberCookie();

            return;
        }

        if (strtotime((string)$token->expires_at) < time()) {
            $token->delete();
            self::clearRememberCookie();

            return;
        }

        /* Boşta kalma sınırı jetona da uygulanır (F06): oturum dosyası
           silinmiş olsa bile (tarayıcı kapandı, GC) gözetimsiz bir tarayıcı
           VNL_REMEMBER_DAYS boyunca açık kalmaz. Ölçü: jetonun son kullanımı
           (used_at; hiç yenilenmediyse oluşturulduğu an). */
        $idle = self::idleLimit();
        if ($idle > 0 && !self::idleKeepsRemember()) {
            $lastUse = strtotime((string)($token->used_at ?: $token->created_at));

            if ($lastUse !== false && time() - $lastUse > $idle) {
                $token->delete();
                self::clearRememberCookie();

                return;
            }
        }

        $user = User::Select(['id' => $token->user_id]);
        if (empty($user) || empty($user->id) || !self::IsActiveUser($user)) {
            $token->delete();
            self::clearRememberCookie();

            return;
        }

        self::SetUser($user);

        /* Jeton döndürülür: aynı çerez ikinci kez kullanılamaz. */
        $token->delete();
        self::RememberMe($user);
    }

    /**
     * Bu cihaz için kalıcı giriş jetonu üretir ve çereze yazar.
     * Başarılı girişten sonra, kullanıcı "beni hatırla" seçtiyse çağrılır.
     */
    static function RememberMe($user): void
    {
        if (empty($user->id)) {
            return;
        }

        $days = self::rememberDays();
        if ($days === 0) {
            return;
        }

        $raw     = bin2hex(random_bytes(32));
        $expires = time() + $days * 86400;

        RememberToken::create([
            'user_id'    => $user->id,
            'token_hash' => RememberToken::hashOf($raw),
            'expires_at' => date('Y-m-d H:i:s', $expires),
            'user_agent' => mb_substr(self::CurrentBrowser(), 0, 255),
            'ip_address' => mb_substr(self::CurrentIp(), 0, 45),
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        setcookie(self::rememberCookieName(), $raw, [
            'expires'  => $expires,
            'path'     => '/',
            'secure'   => defined('VNL_HTTPS') ? VNL_HTTPS : (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
            'httponly' => true,   // JavaScript okuyamaz
            'samesite' => 'Lax',
        ]);
    }

    /** Yalnızca bu cihazın jetonunu iptal eder. */
    static function ForgetMe(): void
    {
        $raw = $_COOKIE[self::rememberCookieName()] ?? '';

        if ($raw !== '' && is_string($raw)) {
            RememberToken::where('token_hash', RememberToken::hashOf($raw))->delete();
        }

        self::clearRememberCookie();
    }

    /** Kullanıcının tüm cihazlardaki jetonlarını iptal eder. */
    static function ForgetAllDevices($userId): void
    {
        if (!empty($userId)) {
            RememberToken::where('user_id', $userId)->delete();
        }
    }

    private static function rememberCookieName(): string
    {
        global $cDefinitions;

        return $cDefinitions->getCookiePrefix() . 'remember';
    }

    private static function clearRememberCookie(): void
    {
        $name = self::rememberCookieName();

        setcookie($name, '', time() - 3600, '/');
        unset($_COOKIE[$name]);
    }

    /**
     * Kullanıcının oturumu geçerli değilse oturumu kapatıp,
     * login'e yönlendirir...
     * @return void
     */
    static function CheckUser()
    {
        global $CurrentUser;

        if (!empty(Session::get('login')) && empty($CurrentUser->id)) {
            self::LogOut();

            header('Location:/login');
            die();
        }
    }

    /**
     * Oturumu bitirir ama cihazın "beni hatırla" jetonuna dokunmaz.
     * Boşta kalma sınırı jetonu ayrıca ForgetMe() ile iptal eder
     * (VNL_IDLE_KEEPS_REMEMBER=true değilse). Tam çıkış için LogOut().
     */
    static function EndSession(): void
    {
        global $CurrentUser;

        Session::destroy();
        vnl_csrf_rotate();

        $CurrentUser = new User();
    }

    /**
     * Tam çıkış: bu cihazın kalıcı jetonu iptal edilir, çerezler silinir,
     * oturum boşaltılıp YENİ bir kimlikle sürdürülür ve CSRF jetonu döner.
     *
     * 3.3'e kadar session_destroy() yalnız oturum açıkken çağrılıyordu;
     * Session her yazmadan sonra oturumu kapattığı için o dal hiç
     * çalışmıyordu ve asıl temizliği görünüm dosyası (view/logout.php)
     * yapıyordu. Artık tek yer burası.
     */
    static function LogOut()
    {
        global $CurrentUser;

        /* Çerezi silmek yetmez; jeton satırı da iptal edilmeli. */
        try {
            self::ForgetMe();
        } catch (Throwable $e) {
            /* Veritabanı yoksa bile çıkış tamamlanmalı. */
            error_log('[VNL][Auth] ForgetMe: ' . $e->getMessage());
        }

        $sessionName = session_name();
        foreach ($_COOKIE as $key => $value) {
            if ($key === $sessionName) {
                continue;   // oturum çerezini Session::destroy() yeniler
            }
            setcookie($key, "", time() - 3600, "/");
            unset($_COOKIE[$key]);
        }

        Session::destroy();
        vnl_csrf_rotate();

        $CurrentUser = new User();
    }


    /**
     * Kullanıcının ip adresini döner
     * @return string
     */
    static function CurrentIp(): string
    {
        return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '0:0:0';
    }

    /**
     * Kullanıcının tarayıcısını döner
     * @return string
     */
    static function CurrentBrowser(): string
    {
        return isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
    }
    public static function CurrentOs()
    {
        $userAgent = self::CurrentBrowser();
        $osArray = [
            'Windows' => 'Windows',
            'Mac' => 'Macintosh',
            'iPhone' => 'iPhone',
            'iPad' => 'iPad',
            'Android' => 'Android',
            'Linux' => 'Linux',
        ];
        foreach ($osArray as $key => $os) {
            if (stripos($userAgent, $key) !== false) {
                return $os;
            }
        }
        return 'Bilinmeyen OS';
    }
    public static function BrowserName()
    {
        $userAgent = self::CurrentBrowser();
        $browsers = [
            'Edge' => 'Edge',
            'OPR' => 'Opera',
            'Chrome' => 'Chrome',
            'Safari' => 'Safari',
            'Firefox' => 'Firefox',
            'MSIE' => 'Internet Explorer',
        ];
        foreach ($browsers as $key => $browser) {
            if (stripos($userAgent, $key) !== false) {
                return $browser;
            }
        }
        return 'Bilinmeyen Tarayıcı';
    }

    public static function CurrentLocation()
    {
        $ip = self::CurrentIp();
        // Üçüncü parti servis ile basit bir çözüm (mesela ipinfo.io)
        $details = @file_get_contents("http://ipinfo.io/{$ip}/json");
        if ($details) {
            $details = json_decode($details, true);
            return $details['city'] . ', ' . $details['region'] . ', ' . $details['country'];
        }
        return 'Bilinmeyen Konum';
    }

    public static function IsMobile()
    {
        $userAgent = self::CurrentBrowser();
        $mobileDevices = ['Android', 'iPhone', 'iPad', 'Windows Phone'];
        foreach ($mobileDevices as $device) {
            if (stripos($userAgent, $device) !== false) {
                return true;
            }
        }
        return false;
    }
}

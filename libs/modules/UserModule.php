<?php

class UserModule
{
    static function SetUser($user_data){
        if (isset($user_data->id) && !empty($user_data->id) && $user_data->id > 0){
            Session::set('login', true);
            Session::set('lock', false);
            Session::set('user_id', $user_data->id);
            Session::set('login_time', time());
        }
    }
    static function SetCurrentUserInfo()
    {
        global $CurrentUser;

        if (!empty(Session::get('user_id'))) {

            $CurrentUser = User::Select(['id'=>Session::get('user_id')]);
            if (!empty($CurrentUser))
            $CurrentUser->SetOnlineToLastActivity();
            else self::LogOut();
        }
    }

    /** "Beni hatırla" jetonunun geçerlilik süresi (gün). */
    const REMEMBER_DAYS = 30;

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
        if ($raw === '') {
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

        $user = User::Select(['id' => $token->user_id]);
        if (empty($user) || empty($user->id)) {
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

        $raw     = bin2hex(random_bytes(32));
        $expires = time() + self::REMEMBER_DAYS * 86400;

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
            'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
            'httponly' => true,   // JavaScript okuyamaz
            'samesite' => 'Lax',
        ]);
    }

    /** Yalnızca bu cihazın jetonunu iptal eder. */
    static function ForgetMe(): void
    {
        $raw = $_COOKIE[self::rememberCookieName()] ?? '';

        if ($raw !== '') {
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

            Session::destroy();
            header('Location:/login');
            die();
        }
    }

    static function LogOut()
    {
        /* Çerezi silmek yetmez; jeton satırı da iptal edilmeli. */
        self::ForgetMe();

        foreach ($_COOKIE as $key => $value) {
            setcookie($key, "", time() - 3600, "/");
            unset($_COOKIE[$key]);
        }

        if (!(session_status() === PHP_SESSION_NONE)) {
            unset($CurrentUser);
            session_destroy();
        }



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
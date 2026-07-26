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

    /**
     * "Beni hatırla" çerezi.
     *
     * VNL 3.0'da devre dışı: eski uygulama kullanıcı adı ve DÜZ PAROLAYI
     * {prefix}auth çerezine yazıyor, her istekte çözüp yeniden giriş
     * yaptırıyordu. Çerez EncryptionModule::safeEncrypt ile korunuyordu
     * ama o fonksiyonun anahtarı kaynak kodda sabit ve IV kullanılmıyor —
     * yani şablona erişen herkes çerezleri çözebiliyordu. password_hash'e
     * geçilen bir sistemde parolanın çerezde taşınması anlamsız.
     *
     * Ayrıca çağırdığı User::Login() metodu hiçbir zaman var olmadı;
     * {prefix}auth çerezi bir kez yazılsaydı bu satır fatal verecekti.
     *
     * Doğru çözüm rastgele bir hatırlama jetonu: jetonun hash'i kullanıcı
     * kaydında saklanır, çerezde yalnızca jeton taşınır ve her kullanımda
     * yenilenir. Bunun için bir sütun/tablo gerektiğinden ayrı ele alınacak.
     */
    static function CheckCookieAuth()
    {
        global $cDefinitions;

        $cookie = $cDefinitions->getCookiePrefix() . 'auth';

        /* Eski çerez düz parola taşıyor; tarayıcıdan sil. */
        if (!empty($_COOKIE[$cookie])) {
            setcookie($cookie, '', time() - 3600, '/');
            unset($_COOKIE[$cookie]);
        }
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
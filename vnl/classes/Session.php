<?php
/*!
 * ============================================================
 *  Voxnup Not Laravel (VNL) — Oturum
 *  $_SESSION'ın kilitsiz, statik sarmalayıcısı.
 *  Authors: Voxnup Authors
 * ============================================================
 */

/**
 * Oturum erişimi.
 *
 * Her yazma session_start() + session_write_close() çiftidir: PHP'nin dosya
 * tabanlı oturumu istek boyunca kilit tutar ve aynı kullanıcının paralel
 * AJAX istekleri birbirini beklerdi. Okuma, init()'in doldurduğu $_SESSION
 * kopyasından yapılır — kilit gerektirmez.
 *
 * SINIF YALNIZ STATİKTİR. VNL 3.4'e kadar kurucu init() çağırıyor, yıkıcı
 * destroy() çağırıyordu: `new Session()` yazan her kod, nesne kapsamdan
 * çıktığı anda kullanıcının oturumunu siliyordu. İkisi de kaldırıldı;
 * `new Session()` artık zararsızdır (ve gereksizdir).
 *
 * Komut satırında (kuyruk işçisi, zamanlayıcı) oturum deposuna HİÇ
 * dokunulmaz: $_SESSION süreç içi bir dizi olarak kullanılır. Kök
 * yetkisiyle çalışan bir işçinin oturum dizinine dosya bırakması hem
 * gereksiz hem sahiplik sorunu yaratırdı.
 */
class Session
{
    /** Oturumu okur ve kilidi hemen bırakır; $_SESSION dolu kalır. */
    public static function init()
    {
        if (self::isCli()) {
            if (!isset($_SESSION) || !is_array($_SESSION)) {
                $_SESSION = [];
            }

            return;
        }

        if (self::open()) {
            session_write_close();
        }
    }

    public static function set($key, $val)
    {
        if (self::isCli()) {
            $_SESSION[$key] = $val;

            return;
        }

        $opened = self::open();
        $_SESSION[$key] = $val;

        if ($opened) {
            session_write_close();
        }
    }

    /** Birden çok anahtarı tek açılışta yazar (tek kilit, tek dosya yazımı). */
    public static function setMany(array $values): void
    {
        if (self::isCli()) {
            foreach ($values as $key => $val) {
                $_SESSION[$key] = $val;
            }

            return;
        }

        $opened = self::open();

        foreach ($values as $key => $val) {
            $_SESSION[$key] = $val;
        }

        if ($opened) {
            session_write_close();
        }
    }

    public static function get($key)
    {
        if (isset($_SESSION[$key])) {
            return $_SESSION[$key];
        }

        return false;
    }

    public static function delete($key)
    {
        if (!isset($_SESSION[$key])) {
            return false;
        }

        if (self::isCli()) {
            unset($_SESSION[$key]);

            return true;
        }

        $opened = self::open();
        unset($_SESSION[$key]);

        if ($opened) {
            session_write_close();
        }

        return true;
    }

    public static function checkSession()
    {
        if (self::get('login') == true) {
            if (self::get('lock') == true) {
                return 0;
            }

            return 1;
        }

        if (strpos(basename($_SERVER['REQUEST_URI'] ?? ''), 'login') === false) {
            $url = basename($_SERVER['REQUEST_URI'] ?? '', '?' . (isset($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : ''));
            self::set('REQUEST_URI', $url);
        }

        return -1;
    }

    /**
     * Oturum kimliğini yeniler; veriler korunur, eski kimlik geçersizleşir.
     *
     * Girişte çağrılır (UserModule::SetUser): giriş öncesi kimlik, bir
     * saldırganın kurbana dayattığı kimlik olabilir (session fixation).
     * Yetki seviyesi değişen her noktada kimlik de değişmelidir.
     */
    public static function regenerate(): void
    {
        if (self::isCli()) {
            return;
        }

        $opened = self::open();

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }

        if ($opened) {
            session_write_close();
        }
    }

    /**
     * Oturumu tamamen boşaltır ve YENİ bir kimlikle devam eder.
     *
     * Eski kimliğin deposu silinir (session_regenerate_id(true)), tarayıcıya
     * yeni ve boş bir oturum çerezi gider. Çıkıştan sonra aynı istekte yazılan
     * her şey (ör. yeni CSRF jetonu) bu temiz oturuma düşer; çalınmış eski
     * kimlik hiçbir şeye karşılık gelmez.
     */
    public static function destroy()
    {
        if (self::isCli()) {
            $_SESSION = [];

            return;
        }

        $opened = self::open();
        $_SESSION = [];

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }

        if ($opened) {
            session_write_close();
        }
    }

    /** Geçerli oturum kimliği (CLI'da boş dizge). */
    public static function id(): string
    {
        return self::isCli() ? '' : (string)session_id();
    }

    /* ---------------------------------------------------------------- */

    /**
     * Oturumu açar. Bu çağrı açtıysa true döner — kapatma sorumluluğu
     * da onundur; zaten açık bir oturumu (proje kendi açtıysa) kapatmayız.
     *
     * Başlıklar gönderildikten sonra session_start() uyarı basar ve çerez
     * yazamaz; o durumda yalnız bellek içi $_SESSION güncellenir.
     */
    private static function open(): bool
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return false;
        }

        if (headers_sent()) {
            if (!isset($_SESSION) || !is_array($_SESSION)) {
                $_SESSION = [];
            }

            return false;
        }

        return session_start();
    }

    private static function isCli(): bool
    {
        return PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg';
    }
}

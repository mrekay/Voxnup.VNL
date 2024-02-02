<?php

class UserModule
{
    static function SetCurrentUserInfo()
    {
        global $CurrentUser;
        if (!empty(Session::get('user_id'))) {

            $CurrentUser->id = Session::get("user_id");
            $CurrentUser->name = Session::get("user_name");
            $CurrentUser->surname = Session::get("user_last_name");
            $CurrentUser->unique_text = Session::get("user_unique_text");
            $CurrentUser->company = Session::get("user_company");
            $CurrentUser->phone = Session::get("user_phone");

        }
    }

    /**
     * Kullanıcının oturumu geçerli değilse oturumu kapatıp,
     * login'e yönlendirir...
     * @return void
     */
    static function CheckUser()
    {
        if (!empty($CurrentUser->id) && empty($CurrentUser->name)) {
            LogOut();
            Session::destroy();
            header('Location:/login');
            die();
        }
    }

    static function LogOut()
    {
        global $siteCookiePrefix;

        setcookie($siteCookiePrefix . 'sessionId', '', time() - 3600, '/');
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
}
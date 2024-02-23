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
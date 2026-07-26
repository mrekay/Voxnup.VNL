<?php

namespace ActionGlobal;

class Auth
{

    public static function User()
    {
        global $CurrentUser;

        return $CurrentUser;
    }

    public static function UserPermis()
    {
        return self::User()->perm_id;
    }

    public static function CheckLogin($throwError = true){
        $isLogined = !empty(self::User());
        if (!$isLogined && $throwError) throwErrorMessage(lang('ERR_LOGIN_FIRST'));
        return !empty(self::User());
    }

    public static function HasPermis(array $perms,$throwError = true)
    {
        $perm_result =  in_array(self::User()->perm_id,$perms);

        if (!$perm_result && $throwError) throwErrorMessage(lang('ERR_PERM'));

        return $perm_result;

    }
}


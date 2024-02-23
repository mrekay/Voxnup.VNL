<?php

namespace actions;

use function ActionGlobal\throwErrorMessage;

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

    public static function HasPermis(array $perms,$throwError = true)
    {
        $perm_result =  in_array(self::User()->perm_id,$perms);

        if (!$perm_result && $throwError) throwErrorMessage(Language::$ERR_PERM);

        return $perm_result;

    }
}


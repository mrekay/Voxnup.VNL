<?php

class LoginValidator
{

	static function validateUsername($username)
	{
		if (empty($username)) {
			throw new EmptyDataException(Language::$ERR_LOGIN_EXCEPTION);
		}
	}

    static function validateLoginData($loginData,$redirect_page = false){
        global $CurrentUser;
        if (empty($loginData)) {
            if (!$redirect_page)throw new EmptyDataException(Language::$ERR_LOGIN_EXCEPTION);
            else return false;
        }

        if (empty($loginData)) {
            if (!$redirect_page) throw new EmptyDataException(Language::$ERR_LOGIN_EXCEPTION);
            else return false;
        }

        if (empty($loginData['session_id']) ||
            ($loginData['session_id'] != Session::GetSessionId()
                || $loginData['current_user_id'] != $CurrentUser->id
                || $loginData['user_agent'] != \UserModule::CurrentBrowser()
                || $loginData['ip_adress'] != \UserModule::CurrentIp()
            )) {
            if (!$redirect_page) throw new StandartException(Language::$ERR_LOGIN_EXCEPTION);
            else return false;
        }
        return true;
    }

	static function validatePassword($password)
	{
		if (empty($password)) {
			 throw new EmptyDataException(Language::$ERR_LOGIN_EXCEPTION);
		}
	}

	static function validateUser($user){
        if (!isset($user->id) && empty($user->id) && $user->id > 0){
			throw new StandartException(Language::$ERR_LOGIN_EXCEPTION);

		}
	}

	static function validateActive($is_active){
		if ($is_active == 2){
			LogOut(false);
			throw new StandartException(Language::$ERR_LOGIN_PASSIVE);
		}
	}

	static function validateSession($sesion){
		if ($sesion != 1){
			throw new StandartException(Language::$ERR_LOGIN_EXCEPTION);
		}
	}

}

<?php

class LoginValidator
{

	static function validateUsername($username)
	{
		if (empty($username)) {
			throw new EmptyDataException(lang('ERR_LOGIN_EXCEPTION'));
		}
	}

    static function validateLoginData($loginData,$redirect_page = false){
        global $CurrentUser;
        if (empty($loginData)) {
            if (!$redirect_page)throw new EmptyDataException(lang('ERR_LOGIN_EXCEPTION'));
            else return false;
        }

        /* 3.4.0: Session::GetSessionId() hiç tanımlı olmadı → Session::id(). */
        if (empty($loginData['session_id']) ||
            ($loginData['session_id'] != Session::id()
                || $loginData['current_user_id'] != $CurrentUser->id
                || $loginData['user_agent'] != \UserModule::CurrentBrowser()
                || $loginData['ip_adress'] != \UserModule::CurrentIp()
            )) {
            if (!$redirect_page) throw new StandartException(lang('ERR_LOGIN_EXCEPTION'));
            else return false;
        }
        return true;
    }

	static function validatePassword($password)
	{
		if (empty($password)) {
			 throw new EmptyDataException(lang('ERR_LOGIN_EXCEPTION'));
		}
	}

	/**
	 * 3.4.0: eski koşul (!isset && empty && > 0) hiçbir zaman doğru
	 * olamıyordu; denetim hiçbir şeyi reddetmiyordu.
	 */
	static function validateUser($user){
		if (empty($user) || empty($user->id)) {
			throw new StandartException(lang('ERR_LOGIN_EXCEPTION'));
		}
	}

	/**
	 * Hesap etkin mi? Kullanıcı nesnesi ya da is_active değeri alır.
	 * 0 (3.4 şeması) ve 2 (2.x geleneği) pasiftir; NULL/sütun yok etkin.
	 *
	 * 3.4.0: tanımsız global LogOut(false) yerine UserModule::LogOut().
	 */
	static function validateActive($userOrFlag){
		$active = is_object($userOrFlag)
			? UserModule::IsActiveUser($userOrFlag)
			: ($userOrFlag === null || !in_array((int)$userOrFlag, [0, 2], true));

		if (!$active){
			if (vnl_logged_in()) {
				UserModule::LogOut();
			}
			throw new StandartException(lang('ERR_LOGIN_PASSIVE'), 403);
		}
	}

	static function validateSession($sesion){
		if ($sesion != 1){
			throw new StandartException(lang('ERR_LOGIN_EXCEPTION'));
		}
	}

}

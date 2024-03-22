<?php

class LoginValidator
{

	static function validateUsername($username)
	{
		if (empty($username)) {
			throw new EmptyDataException(Language::$ERR_LOGIN_EXCEPTION);
		}
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

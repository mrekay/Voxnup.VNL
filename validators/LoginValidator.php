<?php

class LoginValidator
{

	static function validateUsername($username)
	{
		if (empty($username)) {
			throw new EmptyDataException(MSG_ERR_LOGIN);
		}
	}

	static function validatePassword($password)
	{
		if (empty($password)) {
			 throw new EmptyDataException(MSG_ERR_LOGIN);
		}
	}

	static function validateUser($user){
		if (!empty(isset($data["user"]["user_id"]))){
			throw new StandartException(MSG_ERR_LOGIN);

		}
	}

	static function validateActive($is_active){
		if ($is_active == 2){
			LogOut(false);
			throw new StandartException(MSG_LOGIN_PASSIVE);
		}
	}

	static function validateSession($sesion){
		if ($sesion != 1){
			throw new AuthException(MSG_ERR_LOGIN);
		}
	}

}

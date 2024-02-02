<?php


class AuthException extends Exception
{
	public function __construct($msg)
	{
		parent::__construct($msg);
		$errMessage = new ErrorMessage(parent::getMessage(), 401);
		returnErrorMessage($errMessage, 401);
	}


}

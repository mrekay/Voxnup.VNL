<?php


/**
 * Kimlik hatası. Web isteğinde 401 JSON yanıtı yazar ve isteği bitirir;
 * komut satırında (3.4.0) yalnız fırlatılır — bkz. StandartException.
 */
class AuthException extends Exception
{
	public function __construct($msg)
	{
		parent::__construct($msg);

		if (PHP_SAPI !== 'cli' && PHP_SAPI !== 'phpdbg') {
			$errMessage = new ErrorMessage(parent::getMessage(), 401);
			returnErrorMessage($errMessage, 401);
		}
	}


}

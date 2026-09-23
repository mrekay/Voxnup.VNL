<?php


/**
 * Boş veri hatası. Web isteğinde 400 JSON yanıtı yazar ve isteği bitirir;
 * komut satırında (3.4.0) yalnız fırlatılır — bkz. StandartException.
 */
class EmptyDataException extends Exception
{
	public function __construct($msg)
	{
		parent::__construct($msg);

		if (PHP_SAPI !== 'cli' && PHP_SAPI !== 'phpdbg') {
			returnErrorMessage(parent::getMessage(), 400);
		}
	}
}

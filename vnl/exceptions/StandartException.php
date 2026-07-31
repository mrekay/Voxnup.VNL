<?php


class StandartException extends Exception
{
	public function __construct($msg,$error = 400)
	{
		parent::__construct($msg);
		returnErrorMessage($msg, $error);
	}

}

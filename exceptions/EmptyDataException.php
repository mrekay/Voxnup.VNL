<?php


class EmptyDataException extends Exception
{
	public function __construct($msg)
	{
		parent::__construct($msg);
		$errMessage = new ErrorMessage(parent::getMessage(), 400);
		returnErrorMessage($errMessage, 400);
	}
}

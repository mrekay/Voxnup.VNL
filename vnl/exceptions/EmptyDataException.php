<?php


class EmptyDataException extends Exception
{
	public function __construct($msg)
	{
		parent::__construct($msg);
		returnErrorMessage(parent::getMessage(), 400);
	}
}

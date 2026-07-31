<?php


class ErrorMessage
{

	public $message,$error_code;

	public function __construct($message,$error_code)
	{
		$this->message=$message;
		$this->error_code=$error_code;
	}

}

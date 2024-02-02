<?php

class ManageStaffValidator
{

	static function validateEmptyString($param)
	{
		if (empty($param)) {
			throw new EmptyDataException(MSG_ERR_EMPTY);
		}
	}


	static function validateEmptyID($param)
	{
		if (empty($param)) {
			throw new EmptyDataException(MSG_ERR_SELECT);
		}else if (!AsInt($param)){
			throw new ErrorException(PLEASE_SELECT_NUMBER);
		}
	}

}

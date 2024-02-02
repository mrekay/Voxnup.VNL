<?php

class DepartmentsValidator extends DefaultValidator
{

	static function validateEmptyDepartment($rows)
	{
			if (empty($rows['name']) || empty($rows['status']))
				throw new EmptyDataException(MSG_ERR_EMPTY);

	}

	static function validateEmptyDepartmentID($param){
		if (empty($param)){
			throw new EmptyDataException(MSG_ERR_SELECT);
		}
	}
	static function validateEmptyName($param){
		if (empty($param)){
			throw new EmptyDataException(MSG_ERR_EMPTY);
		}
	}
		static function validateEmptyDescription($param){
		if (empty($param)){
			throw new EmptyDataException(MSG_ERR_EMPTY);
		}
	}

}

<?php

class CompaniesValidator
{

	static function validateEmptyRows($rows)
	{
		foreach ($rows as $row){
			if (empty($row['name']) || empty($row['description']))
				throw new EmptyDataException(MSG_ERR_EMPTY);
		}
	}

	static function validateEmptyID($param){
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

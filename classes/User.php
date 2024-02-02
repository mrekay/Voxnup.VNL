<?php

class User
{
	public static $user_types = array(
		1 => array(
			'title' => "Sistem Yöneticisi",
			'icon'  => 'fa fa-cog',
		),
		2 => array(
			'title' => "Kullanıcı",
			'icon'  => 'fa fa-user-circle-o',
		),

	);

	public $id;
	public $user_type;
	public $username;
	public $password;
	public $unique_text;
	public $name;
	public $surname;
	public $company;
	public $phone;

	protected $db;


	public function __construct()
	{
		global $db_pdo;
		$this->db = $db_pdo;
		$this->ClearVariables();
	}


	public function ClearVariables($unset_array = [])
	{
		foreach ($this as $key => $value) {
			if (!in_array($key, $unset_array) && $key != 'db' && (strpos($key, '_list') === false)) {
				$this->{$key} = null;
			}
		}
	}

	public function Select()
	{
		$this->ClearVariables(['id']);
		if ($this->id > 0) {
			$result = $this->SelectList(['id' => $this->id], null);
			if (empty($result)) return false;
			$this->db->AddDataToClassFromArray($this, $result[0]);

			return true;
		}

		return false;
	}

	public function SelectList($filters, $extras)
	{
		$outParams = [];
		$extras_default = [
			'begin'     => 0,
			'count'     => -1,
			'search'    => '',
			'order'     => '',
			'get_count' => null,
		];
		$filters_default = [
			'id'                   => null,
			'username'                => null,
			'password'             => null,
			'name_surname'             => null,
		];

		$extras = array_merge_intersect_key($extras_default, $extras);
		$filters = array_merge_intersect_key($filters_default, $filters);
		$array = $this->db->createProcedureParameters($filters, $extras, $outParams);
		$sql = $this->db->CreateProcedureSql('UserSelectList', $array);

		return $this->db->GetToTable($sql, $array);

	}

	public function SelectListCount($filters, $extras)
	{
		$extras = [];
		$extras['get_count'] = 1;
		$result = $this->SelectList($filters, $extras);

		return (isset($result) ? $result[0]['count'] : 0);

	}

	public function Delete()
	{
		$array = array(
			':prm_id' => $this->id,
		);
		$sql = $this->db->CreateProcedureSql('UserDelete', $array);

		return $this->db->AffectedRows($sql, $array);
	}

	public function Update()
	{

		$outParams = ['id'];
		$params = [
			'id'                 => $this->id,
			'company_id'      => $this->company_id,
			'department_id'      => $this->departman_id,
			'department_unit_id' => $this->departman_unit_id,
			'email'              => $this->email,
			'username'           => $this->username,
			'password'           => $this->password,
			'name'               => $this->name,
			'surname'            => $this->surname,
			'tc_no'              => $this->tc_no,
			'sicil_no'           => $this->sicil_no,
			'birth_day'          => $this->birth_day,
			'gender'             => $this->gender,
			'user_type'          => $this->user_type,
			'is_active'          => $this->is_active,
			'uniqe_text'         => $this->uniqe_text,
			'kayit_yetkisi'         => $this->kayit_yetkilisi,
		];

		$array = $this->db->createProcedureParameters($params, null, $outParams);
		$sql = $this->db->CreateProcedureSql('UserUpdate', $array);
		$result = $this->db->getOutParamsProcedure($sql, $array);

		return isset($result['prm_id']) ? $result['prm_id'] : false;

	}

    public function PagePermission($pageName, $type = '')
	{
		global $ActiveProductId;

		$temp_permiss = $this->getPagePermission($pageName, $ActiveProductId);

		if ($type != '') {
			if ($temp_permiss != $type && $this->PermissionTypeComparison($temp_permiss, $type) == $type) {
				$temp_permiss = false;
			}
			else
				$temp_permiss = true;
		}

		return $temp_permiss;
	}

	public function getPagePermission($pageName, $productID)
	{
		$permiss = '';
		$per_select = $this->PermissionSelectList($pageName, $productID);

		if (isset($per_select)) {
			foreach ($per_select as $temp_per) {
				$permiss = $this->PermissionTypeComparison($permiss, $temp_per['type']);
			}
		}

		if ($this->user_type == 1) {
			$permiss = 'a';
		}

		return $permiss;
	}

	public function PermissionSelectList($page_name, $ActiveProductId)
	{

		$array = array(
			':prm_product_id'  => $ActiveProductId,
			':prm_user_type' => $this->user_type,
			':prm_page_name'   => $page_name,
		);

		$sql = $this->db->CreateProcedureSql($this->db->master_db . '.PermissionUserPageSelect', $array);

		return $this->db->GetToTable($sql, $array);

	}

	public function PermissionTypeComparison($type1, $type2)
	{
		if ($this->user_type == 1) {
			return 'a';
		}
		$type1Number = 0;
		$type2Number = 0;
		switch ($type1) {
			case 'n':
				$type1Number = 0;
				break;
			case 'r':
				$type1Number = 1;
				break;
			case 'w':
				$type1Number = 2;
				break;
			case 'd':
				$type1Number = 3;
				break;
			case 'a':
				$type1Number = 4;
				break;
		}

		switch ($type2) {
			case 'n':
				$type2Number = 0;
				break;
			case 'r':
				$type2Number = 1;
				break;
			case 'w':
				$type2Number = 2;
				break;
			case 'd':
				$type2Number = 3;
				break;
			case 'a':
				$type2Number = 4;
				break;
		}

		if ($type1Number > $type2Number)
			return $type1;
		else
			return $type2;
	}

}

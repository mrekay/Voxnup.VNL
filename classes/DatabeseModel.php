<?php

class DatabeseModel
{

    protected $db;

    public function __construct()
    {
        global $db_pdo;
        $this->db = $db_pdo;
        $this->ClearVariables();
    }

    public function DebugMode($debug_mode)
    {
        $this->db->debug_mode = $debug_mode;
    }

    public function ClearVariables($unset_array = [])
    {
        foreach ($this as $key => $value) {
            if (!in_array($key, $unset_array) && $key != 'model_name' && $key != 'db' && (strpos($key, '_list') === false)) {
                $this->{$key} = null;
            }
        }
    }

    public function Select()
    {
        $filters = [];
        $class = $this;
        foreach ($class as $key => $value) {
            if ($key != 'model_name' && $key != 'db' && (strpos($key, '_list') === false)) {
                $filters[$key] = $class->{$key};
            }
        }

        $item = $this->SelectList($filters);

        $this->ClearVariables();
        if (!empty($item)) {
            $this->SetFromArray($item[0]);
            return true;
        }
        else {
            return false;
        }

    }

    public function SetFromArray($item)
    {
        $this->ClearVariables();
        $this->db->AddDataToClassFromArray($this, $item);
    }


    public function SelectList($filters = [], $extras = [])
    {
        $outParams = [];
        $extras_default = [
            'begin' => 0,
            'count' => -1,
            'search' => "",// null,
            'order' => "",//null,
            'get_count' => null
        ];

        $extras = array_merge_intersect_key($extras_default, $extras);

        $array = $this->db->createProcedureParameters($filters, $extras, $outParams);

        $procedure_name = self::getModelName() . 'SelectList';
        $sql = $this->db->CreateProcedureSql($procedure_name, $array);
        $result = $this->db->GetToTable($sql, $array);

        return $result;

    }


    public function SelectListCount($filters = [], $extras = [])
    {

        $extras['get_count'] = 1;
        $result = $this->SelectList($filters, $extras);

        return empty($result) ? 0 : $result[0]['count'];
    }

    public function Delete()
    {
        $array = array(
            ':prm_id' => $this->id,
        );
        $procedure_name = self::getModelName() . 'Delete';

        $sql = $this->db->CreateProcedureSql($procedure_name, $array);

        return $this->db->AffectedRows($sql, $array);
    }

    private function getModelName()
    {
        return isset($this->model_name) && !empty($this->model_name) ? $this->model_name : get_class($this);
    }

}
<?php


abstract class TableModel extends \Illuminate\Database\Eloquent\Model
{

    protected $foreign_keys = [];

    public static function tableName(string $as)
    {
        return with(new static)->getTable() . (!empty($as) ? " as " . $as : "");
    }

    public function getBelongTable($key)
    {
        return $this->belongsTo($this->foreign_keys[$key]['class'], $this->foreign_keys[$key]['column'])->get();
    }

    protected static function setFilterDefault($filters_default, $filters)
    {
        $filters_changed = $filters_default;

        /* eğer filtre gelmedi ise procedure'de olduğu gibi 1=1 yapmaya gerek yok
           direkt filtre göndermeyeceğiz*/
        if (!empty($filters)) {

            foreach ($filters as $key => $filter) {
                if (!isset($filters_changed[$key])) throwErrorMessage("İç Veritabanı Hatası : Geçerli Filtre Gelmedi");

                if (!empty($filters[$key])) {
                    $filters_changed[$key]['name'] = $filters_changed[$key]['name'] ?? $key;
                    $filters_changed[$key]['operator'] = $filters[$key]['operator'] ?? ($filters_changed[$key]['operator'] ?? null);
                    $filters_changed[$key]['value'] = $filters[$key]['value'] ?? $filters[$key];
                }
            }

            /* boş filtreleri göndermesini engelliyoruz */
            foreach ($filters_changed as $key=>$changedFilter){
                if (empty($changedFilter["value"])){
                    unset($filters_changed[$key]);
                }
            }

            return $filters_changed;
        }

        return [];
    }

    protected static function selectListQuery($filters = [], $extras = [])
    {
        $extras_default = array(
            'begin' => 0,
            'limit' => -1,
            'order' => '',
            'order_by' => 'asc'
        );
        $extras = empty($extras) ? $extras_default : array_merge($extras_default, $extras);

        $query = self::query();

        if ($extras['begin'])
            $query->offset($extras['begin']);

        if ($extras['limit'])
            $query->limit($extras['limit']);

        if (!empty($extras['order']))
            $query->orderBy($extras['order'],$extras['order_by']);

        foreach ($filters as $key => $filter) {
            if (!empty($filter['value']))
                $query->where($filter['name'], ($filter['operator'] ?? '='), ($filter['value'] ?? $filter));
        }
        return $query;
    }

    public static function Select($filter = null)
    {
        return static::SelectList($filter, ['limit' => 1])->first();
    }

    public abstract static function SelectList($filters, $extras);


}
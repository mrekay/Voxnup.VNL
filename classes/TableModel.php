<?php


abstract class TableModel extends \Illuminate\Database\Eloquent\Model
{

    protected $foreign_keys = [];
    protected $primaryKey = 'id';
    const DELETED_AT = 'deleted_at';
    const UPDATED_AT = 'updated_at';
    const CREATED_AT =  'created_at';

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
                if (!isset($filters_changed[$key])) throwErrorMessage("İç Veritabanı Hatası : Geçerli Filtre Gelmedi (" . $key . ")");

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

    protected static function selectListQuery($columns = [],$filters = [], $extras = [])
    {
        $extras_default = array(
            'begin' => 0,
            'limit' => -1,
            'order' => '',
            'order_by' => 'asc'
        );
        $extras = empty($extras) ? $extras_default : array_merge($extras_default, $extras);

        $filters = self::setFilterDefault($columns, $filters);

        $query = self::query();

        if ($extras['begin'])
            $query->offset($extras['begin']);

        if ($extras['limit'])
            $query->limit($extras['limit']);

        if (!empty($extras['order']) && !empty($columns[$extras['order']]))
            $query->orderBy($columns[$extras['order']]['name']??$extras['order'],$extras['order_by']);

        foreach ($filters as $key => $filter) {
            if (!empty($filter['value'])) {

                // Buradaki blok eklendi/güncellendi:
                $operator = $filter['operator'] ?? '=';
                $value = $filter['value'] ?? $filter;

                if (strtolower($operator) === 'like') {
                    // Eğer operatör 'like' ise, değerin başına ve sonuna % ekle
                    $value = "%" . $value . "%";
                }

                $query->where($filter['name'], $operator, $value);
            }
        }
        return $query;
    }

    public static function Select($filter = null)
    {
        return static::SelectList($filter, ['limit' => 1])->first();
    }

    public abstract static function SelectList($filters, $extras);


}
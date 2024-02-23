<?php

class ArrayModule
{
   static  function searchArrayInArrayWithValue($value, $array)
    {
        foreach ($array as $key => $val) {
            if (is_array($val)) {

                $res = searchArrayInArrayWithValue($value, $val);
                if (!empty($res)) {
                    return $res;
                }
            } else {
                if (is_array($value)) {

                    if (array_keys($value)[0] === $key && tr_toUpper($value[array_keys($value)[0]]) === tr_toUpper($val))
                        return $array;
                }
                if ($val == $value)
                    return $array;
            }

        }

        return null;
    }

    static function array_merge_intersect_key($array1, $array2)
    {
        return empty($array2) ? $array1 : array_merge($array1, array_intersect_key($array2, $array1));
    }

}
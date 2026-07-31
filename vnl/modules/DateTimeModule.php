<?php

class DateTimeModule
{

   static function ConvertTrDate($date,$hasClock = true)
    {
        $STR_DATE = strtotime($date);
        return date("d.m.Y". ($hasClock? " H:i:s" : ""), $STR_DATE);
    }

    static function ago($date, $lang = 'tr')
    {
        $terimler = array(
            'tr' => array(
                "saniye", "dakika", "saat", "gün", "hafta", "ay", "yıl", "onyıl"
            ),
            'en' => array(
                "second", "minute", "hour", "day", "week", "month", "year", "decade"
            )
        );

        $words = array(
            'tr' => array(
                "Tarih girilmemiş", "Geçersiz Tarih", "önce", "sonra", ""
            ),
            'en' => array(
                "No date provided", "Bad Date", "ago", "from now", "s"
            )
        );

        if (empty($date)) {
            return $words[$lang][0];
        }
        $periods = $terimler[$lang];
        $lengths = array("60", "60", "24", "7", "4.35", "12", "10");
        //echo $date.' '. date("Y-m-d H:i:s");
        $now = strtotime(date("Y-m-d H:i:s"));
        $unix_date = strtotime($date);
        // check validity of date
        if (empty($unix_date)) {
            return $words[$lang][1];
        }
        //echo $unix_date. ' '. $now;
        // is it future date or past date
        if ($now > $unix_date) {
            $difference = $now - $unix_date;
            $tense = $words[$lang][2];
        } else {
            $difference = $unix_date - $now;
            $tense = $words[$lang][3];
        }
        for ($j = 0; $difference >= $lengths[$j] && $j < count($lengths) - 1; $j++) {
            $difference /= $lengths[$j];
        }
        $difference = round($difference);
        if ($difference != 1) {
            $periods[$j] .= $words[$lang][4];
        }

        return "$difference $periods[$j] {$tense}";
    }

    static function CalculateTotalTime($array)
    {
        //array(hour, minute, second)
        $total_working_s = $array['second'] % 60;
        $total_working_m = $array['minute'] + (($array['second'] - ($array['second'] % 60)) / 60);
        $total_working_h = $array['hour'] + (($total_working_m - ($total_working_m % 60)) / 60);
        $total_working_m = $total_working_m % 60;

        $total_working_h = $total_working_h < 10 ? '0' . $total_working_h : $total_working_h;
        $total_working_m = $total_working_m < 10 ? '0' . $total_working_m : $total_working_m;
        $total_working_s = $total_working_s < 10 ? '0' . $total_working_s : $total_working_s;

        return $total_working_h . ':' . $total_working_m . ':' . $total_working_s;
    }

    static function FormatDate($Date, $Format, $Lang = 'tr')
    {

        if ($Date == '') {
            $Date = date('d M Y H:i:s');
        }
        if ($Format == '') {
            $Format = 'd M Y H:i:s';
        }
        $Months['en'] = array('Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday');
        $Months['tr'] = array('Ocak', 'Şubat', 'Mart', 'Nisan', 'Mayıs', 'Haziran', 'Temmuz', 'Ağustos', 'Eylül', 'Ekim', 'Kasım', 'Aralık', 'Pazartesi', 'Salı', 'Çarşamba', 'Perşembe', 'Cuma', 'Cumartesi', 'Pazar');
        $Months['ru'] = array('январь', 'февраль', 'март', 'апрель', 'может', 'июнь', 'июль', 'июль', 'сентябрь', 'октябрь', 'ноябрь', 'декабрь', '', '', '', '', '', '', '');
        $Months['ar'] = array('يناير', 'فبراير', 'آذار', 'أبريل', 'مايو', 'يونيو', 'يوليو', 'يوليو', 'أيلول', 'أكتوبر', 'نوفمبر', 'ديسمبر', '', '', '', '', '', '', '');
        $BugDate = array('30/11/-0001', '30-11--0001', '30:11:-0001');
        $FixDate = array('00/00/0000', '00-00-0000', '00:00:0000');

        return str_replace($BugDate, $FixDate, str_replace($Months['en'], $Months[$Lang], date($Format, strtotime($Date))));
    }

    static function DiffDay($date_first, $date_last, $now = 0)
    {

        if ($now > 0) {

            $first_date = time();
            $last_date = strtotime(FormatDate($date_first, "Y-m-d", 'tr'));

        } else {

            $first_date = strtotime(FormatDate($date_first, "Y-m-d", 'tr'));
            $last_date = strtotime(FormatDate($date_last, "Y-m-d", 'tr'));

        }

        $datediff = $last_date - $first_date;
        $left_date = floor($datediff / (60 * 60 * 24));

        return $left_date;
    }

    static function time_diff($dt1, $dt2)
    {
        $y1 = substr($dt1, 0, 4);
        $m1 = substr($dt1, 5, 2);
        $d1 = substr($dt1, 8, 2);
        $h1 = substr($dt1, 11, 2);
        $i1 = substr($dt1, 14, 2);
        $s1 = substr($dt1, 17, 2);

        $y2 = substr($dt2, 0, 4);
        $m2 = substr($dt2, 5, 2);
        $d2 = substr($dt2, 8, 2);
        $h2 = substr($dt2, 11, 2);
        $i2 = substr($dt2, 14, 2);
        $s2 = substr($dt2, 17, 2);

        $r1 = date('U', mktime($h1, $i1, $s1, $m1, $d1, $y1));
        $r2 = date('U', mktime($h2, $i2, $s2, $m2, $d2, $y2));

        return ($r1 - $r2);

    }

static function dateRangeConvertFormat($date_range)
    {
        // Convert Daterange format dd/mm/yyyy-dd/mm/yyyy to yyyy-mm-dd/yyyy-mm-
        if (empty($date_range))
            return null;

        $date_range_array = explode("-", $date_range);
        if (count($date_range_array) <= 1) {
            return $date_range_array[0];
        }

        $date = str_replace('/', '-', $date_range_array[0]);
        $date_range_array[0] = date('Y-m-d', strtotime($date));
        $date = str_replace('/', '-', $date_range_array[1]);
        $date_range_array[1] = date('Y-m-d', strtotime($date));

        return $date_range_array[0] . '|' . $date_range_array[1];
    }

static function isBetweenDate($date, $date_start, $date_end)
    {
        if (($date >= $date_start) && ($date <= $date_end)) {
            return 1;
        } else {
            return 0;
        }
    }
}
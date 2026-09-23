<?php

class TextGeneralModule
{
    static function trToUpper($text, $lang = 'tr')
    {
        if ($lang == 'tr') {
            $text = str_replace(array('ı', 'i', 'ğ', 'ü', 'ş', 'ö', 'ç'), array('I', 'İ', 'Ğ', 'Ü', 'Ş', 'Ö', 'Ç'), $text);
        }

        return mb_strtoupper($text, 'UTF-8');
    }

    static function trToLower($text)
    {
        return mb_strtolower(str_replace(array('I', 'İ', 'Ğ', 'Ü', 'Ş', 'Ö', 'Ç'), array('ı', 'i', 'ğ', 'ü', 'ş', 'ö', 'ç'), $text), 'utf-8');
    }

    static function IsNumberic($value)
    {
        $n_index = strrpos($value, '.');
        $v_index = strrpos($value, ',');
        if ($n_index < $v_index) {
            $value = str_replace('.', '', $value);
            $value = str_replace(',', '.', $value);
        }
        if (strrpos($value, ',')) {
            $value = str_replace(',', '', $value);
        }

        return is_numeric($value);
    }

    static function NumberFormatEN($value, $decimals)
    {
        $n_index = strrpos($value, '.');
        $v_index = strrpos($value, ',');
        if ($n_index > $v_index) {
            $value = str_replace('.', '', $value);
            $value = str_replace(',', '.', $value);
        }

        return number_format($value, $decimals, '.', ',');
    }

    static function NumberFormatTR($value, $decimals)
    {
        return number_format($value, $decimals, ',', '.');
    }

    static function JsSlashDateFix2($Date)
    {
        $day = substr($Date, 0, 2);
        $month = substr($Date, 3, 2);
        $year = substr($Date, 6, 4);


        return $year . '-' . $month . '-' . $day;
    }

    static function FloatFormat($data, $digits, $withoutcomma = false, $roundup = false)
    {
        /*test*/
        if ($roundup) {
            $zeros = '0.';
            for ($i = 1; $i <= $digits; $i++) {
                $zeros .= '0';
            }
            $zeros .= '5';
            $data += $zeros;
        }
        $data = is_numeric($data) && !empty($data) ? $data : 0;
        $return = number_format($data, $digits, '.', ',');
        if ($withoutcomma) {// Virgülü kaldır
            $return = WithoutComma($return);
        }

        return $return;
    }

    static function DoubleFormat($data)
    {
        $data = is_numeric(DotFix($data)) && !empty($data) ? $data : 0;

        return DotFix($data);
    }

    static function DotFix($data)
    {
        return str_replace(',', '.', $data);
    }

    static function AsInt($value, $return = 0)
    {
        if (!isset($value) || $value == null || $value == '' || !is_numeric($value)) {
            return $return;
        }

        return $value;
    }

    static function AsString($value, $return = '')
    {
        if (!isset($value) || $value == null || $value == '') {
            return $return;
        }

        return $value;
    }

    static function WithoutComma($data)
    {
        return str_replace(',', '', $data);
    }

    static function SafeURL($fonktmp)
    {
        $returnstr = '';
        $turkcefrom = array('Ğ', 'Ü', 'Ş', 'İ', 'Ö', 'Ç', 'ğ', 'ü', 'ş', 'ı', 'ö', 'ç');
        $turkceto = array('G', 'U', 'S', 'I', 'O', 'C', 'g', 'u', 's', 'i', 'o', 'c');
        $fonktmp = preg_replace('/[^0-9a-zA-ZÄzÜŞİÖÇğüşıöç]/', ' ', $fonktmp);
        // Türkçe harfleri ingilizceye çevir
        $fonktmp = str_replace($turkcefrom, $turkceto, $fonktmp);
        // Birden fazla olan boşlukları tek boşluk yap
        $fonktmp = preg_replace('/ +/', ' ', $fonktmp);
        // Boşukları - işaretine çevir
        $fonktmp = preg_replace('/ /', '-', $fonktmp);
        // Tüm beyaz karekterleri sil
        $fonktmp = preg_replace('/\s/', '', $fonktmp);
        // Karekterleri küçült
        $fonktmp = strtolower($fonktmp);
        // Başta ve sonda - işareti kaldıysa yoket
        $fonktmp = preg_replace('/^-/', '', $fonktmp);
        $fonktmp = preg_replace('/-$/', '', $fonktmp);
        $returnstr = $fonktmp;

        return $returnstr;
    }

    static function SafeText($content)
    {
        $content = stripslashes(html_entity_decode($content));

        return $content;
    }

    static function UrlDecode($url)
    {
        return str_replace(array("&slash;", "&rslash;", "&equal;"), array("/", "\\", "="), $url);
    }

    static function IsDateNull($date, $format = 'Y-m-d')
    {
        return IsDateValid($date, $format) ? $date : DefaultDateTime($format);
    }

    static function IsDateValid($date, $format = 'Y-m-d')
    {
        $d = DateTime::createFromFormat($format, $date);

        return $d && $d->format($format) == $date;
    }

    static function RandomTextGenerator($length = 10, $count = 1, $characters = 'l,u,n,s')
    {

// $length - the length of the generated password
// $count - number of passwords to be generated
// $characters - types of characters to be used in the password

// define variables used within the static function
        $symbols = array();
        $passwords = array();
        $used_symbols = '';
        $pass = array();

// an array of different character types
        $symbols["l"] = 'abcdefghijklmnopqrstuvwxyz'; //lower_case
        $symbols["u"] = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ'; // upper_case
        $symbols["n"] = '1234567890'; //numbers
        $symbols["s"] = '!?~@#-_+>[]{}'; //special_symbols

        $characters = explode(',', $characters); // get characters types to be used for the passsword
        foreach ($characters as $key => $value) {
            $used_symbols .= $symbols[$value]; // build a string with all characters
        }
        $symbols_length = strlen($used_symbols) - 1; //strlen starts from 0 so to get number of characters deduct 1

        for ($p = 0; $p < $count; $p++) {
            $pass = array();
            for ($i = 0; $i < $length; $i++) {
                $n = random_int(0, $symbols_length); // kriptografik rastgele (3.4.0: rand() parola üretiyordu)
                $pass[] = $used_symbols[$n]; // add the character to the password string
            }
            $passwords[] = implode('', $pass);
        }
        unset($pass);

        return !empty($passwords) && count($passwords) == 1 ? $passwords[0] : $passwords; // return the generated password
    }

    static function ConvertBytesToReadableFormat($bytes, $addUnitLabel = true)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
        $factor = floor((strlen($bytes) - 1) / 3);

        if ($factor > 0) {
            $size = sprintf("%.2f", $bytes / pow(1024, $factor));
        } else {
            $size = $bytes;
        }

        return $addUnitLabel ? $size . ' ' . $units[$factor] : $size;
    }

    static function ConvertHexCode($code){
        if (substr($code, 0, 2) === '0x') {
            $renkKodu = substr($code, 2);
        }
        return '#' . $renkKodu;
    }

    static function LimitString($string, $length) {
        if (strlen($string) > $length) {
            return substr($string, 0, $length) . "...";
        } else {
            return $string;
        }
    }


}
<?php

class DefaultValidator
{

    static function validateEmptyString($param, $region = '')
    {
        if (empty($param)) {
            throw new StandartException(empty($region) ? PLEASE_FILL_THE_REQUIRED_FIELD : sprintf(PLEASE_FILL_IN_FIELD, $region));
        }
    }
    static function validateEmptyOption($param, $region = '')
    {
        if (empty($param)) {
            throw new StandartException(empty($region) ? PLEASE_SELECT_VALID_OPTION : sprintf(PLEASE_SELECT_VALID_OPTION_IN_FIELD, $region));
        }
    }

    static function validateEmptyPhone($param, $region = '')
    {
        if (empty($param)) {
            throw new StandartException(empty($region) ? PLEASE_ENTER_VALID_PHONE_NUMBER : sprintf(PLEASE_FILL_IN_FIELD, $region));
        }
        self::validatePhone($param, $region);

    }

    static function validatePhone($param, $region = '')
    {
        $param = str_replace([' ', '-', '_', '(', ')'], '', $param);
        if (empty($param)) return;
        if (!AsInt($param) || strlen($param) < 10) {
            throw new StandartException(empty($region) ? PLEASE_ENTER_VALID_PHONE_NUMBER : sprintf(PLEASE_ENTER_VALID_PHONE_NUMBER_IN_FIELD, $region));
        }
    }

    static function validateEmptyMail($param)
    {
        if (empty($param) || !IsValidEmail($param)) {
            throw new EmptyDataException(EMAIL_ADDRESS_CANNOT_BE_EMPTY);
        }
    }

    static function validateMail($param)
    {
        if (empty($param)) return;
        if (!IsValidEmail($param)) {
            throw new EmptyDataException(EMAIL_ADDRESS_INVALID);
        }
    }

    static function validateEmptyData($param, $region = '')
    {
        if (empty($param)) {
            throw new StandartException(empty($region) ? PLEASE_FILL_THE_REQUIRED_FIELD : sprintf(PLEASE_FILL_IN_FIELD, $region));
        }
    }

    static function validateEmptyID($param, $region = '')
    {
        if (empty($param)) {
            throw new StandartException(empty($region) ? PLEASE_SELECT_VALID_OPTION : sprintf(PLEASE_SELECT_VALID_OPTION_IN_FIELD, $region));
        } else if (!AsInt($param, false) || $param <= 0) {
            throw new StandartException(empty($region) ? PLEASE_SELECT_VALID_OPTION : sprintf(PLEASE_SELECT_VALID_OPTION_IN_FIELD, $region));
        }
    }

    static function validateEmptyIDArray($param, $region = '')
    {
        if (is_array($param)) {
            foreach ($param as $item) {
                self::validateEmptyID($item, $region);
            }
        } else {
            self::validateEmptyID($param, $region);
        }
    }

    static function validateInt($param, $region = '')
    {
        if ($param != '' && !AsInt($param, false)) {
            throw new StandartException(empty($region) ? PLEASE_ENTER_VALID_NUMBER : sprintf(PLEASE_ENTER_VALID_NUMBER_IN_FIELD, $region));
        }
    }

    static function validateEmptyInt($param, $region = '')
    {
        if (!empty($param) && AsInt($param,true) <= 0) {
            throw new StandartException(empty($region) ? PLEASE_ENTER_VALID_NUMBER : sprintf(PLEASE_ENTER_VALID_NUMBER_IN_FIELD, $region));
        }
    }

    /* EMPTYID OLMAMASININ SEBEBİ VERDİĞİ HATA MESAJININ FARKLI OLMASIDIR */
    static function validateEmptyINT_ID($param, $region = '')
    {
        if ((!empty($param) && !AsInt($param, false)) || $param <= 0) {
            throw new StandartException(empty($region) ? PLEASE_ENTER_NUMBER_BIGGER_THAN_0 : sprintf(PLEASE_ENTER_NUMBER_BIGGER_THAN_0_IN_FIELD, $region));
        }
    }

    static function validateDate($date, $region = '', $format = 'Y-m-d')
    {
        $d = DateTime::createFromFormat($format, $date);

        // The Y ( 4 digits year ) returns TRUE for any integer with any number of digits so changing the comparison from == to === fixes the issue.
        if (($d && $d->format($format) === $date) == false) {
            throw new StandartException(empty($region) ? PLEASE_ENTER_VALID_DATE : sprintf(PLEASE_ENTER_VALID_DATE_IN_FIELD, $region));
        }
    }

    static function validateDateNow($date, $region = '', $format = 'Y-m-d')
    {

        $d = DateTime::createFromFormat($format, $date);

        // The Y ( 4 digits year ) returns TRUE for any integer with any number of digits so changing the comparison from == to === fixes the issue.
        if (($d && $d->format($format) === $date) == false) {
            throw new StandartException(empty($region) ? PLEASE_ENTER_VALID_DATE : sprintf(PLEASE_ENTER_VALID_DATE_IN_FIELD, $region));
        } else if ($date > date('Y-m-d')) {
            throw new EmptyDataException(YOU_CANT_ENTER_FEATURE_DATE);
        }
    }

    static function validateEmptyDate($date, $region = '', $format = 'Y-m-d')
    {
        if (empty($date)) throw new EmptyDataException(PLEASE_ENTER_DATE);
        $d = DateTime::createFromFormat($format, $date);

        // The Y ( 4 digits year ) returns TRUE for any integer with any number of digits so changing the comparison from == to === fixes the issue.
        if (($d && $d->format($format) === $date) == false) {
            throw new StandartException(empty($region) ? PLEASE_ENTER_VALID_DATE : sprintf(PLEASE_ENTER_VALID_DATE_IN_FIELD, $region));
        }
    }

    static function validateTextLength($param, $maxlength = 255, $minlength = 3, $region = '')
    {
        $strlen = strlen($param);
        if ($strlen > $maxlength) {
            throw new StandartException(empty($region) ? sprintf(PLEASE_DO_NOT_PASS_X_CHAR_LIMIT, $maxlength) : sprintf(PLEASE_DO_NOT_PASS_X_CHAR_LIMIT_IN_FIELD, $region));
        }
        if ($strlen < $minlength) {
            throw new StandartException(empty($region) ? sprintf(PLEASE_DO_NOT_PASS_X_CHAR_LIMIT, $maxlength) : sprintf(PLEASE_DO_NOT_PASS_X_CHAR_LIMIT_IN_FIELD, $region));
        }

    }

}

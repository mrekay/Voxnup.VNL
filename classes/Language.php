<?php

/* DİL DOSYALARI LSLANGUAGE UYGULAMASINDAN DOLDURULMAKTADIR.*/

class Language
{

     const base_language = Languages::TR;

    public static $ERR_GENERAL = "";
    public static $ERR_INVALID_PARAMETERS_SENT = "";
    public static $ERR_PERM = "";
    public static $ERR_DATABASE = "";
    public static $SUC_GENERAL = "";
    public static $SUC_EXPORT = "";
    public static $ERR_LOGIN_EXCEPTION = "";
    public static $ERR_LOGIN_PASSIVE = "";
    public static $SUC_LOGIN = "";
    public static $ERR_LOGIN = "";
    public static $PLEASE_ENTER_VALID_NUMBER = "";
    public static $PLEASE_ENTER_VALID_NUMBER_IN_FIELD = "";
    public static $PLEASE_FILL_THE_REQUIRED_FIELD = "";
    public static $PLEASE_FILL_IN_FIELD = "";
    public static $PLEASE_SELECT_VALID_OPTION = "";
    public static $PLEASE_SELECT_VALID_OPTION_IN_FIELD = "";
    public static $PLEASE_ENTER_VALID_PHONE_NUMBER = "";
    public static $PLEASE_ENTER_VALID_PHONE_NUMBER_IN_FIELD = "";
    public static $EMAIL_ADDRESS_CANNOT_BE_EMPTY = "";
    public static $EMAIL_ADDRESS_INVALID = "";
    public static $PLEASE_ENTER_NUMBER_BIGGER_THAN_0 = "";
    public static $PLEASE_ENTER_NUMBER_BIGGER_THAN_0_IN_FIELD = "";
    public static $PLEASE_ENTER_VALID_DATE = "";
    public static $PLEASE_ENTER_VALID_DATE_IN_FIELD = "";
    public static $YOU_CANT_ENTER_FEATURE_DATE = "";
    public static $PLEASE_ENTER_DATE = "";
    public static $PLEASE_DO_NOT_PASS_X_CHAR_LIMIT = "";
    public static $PLEASE_DO_NOT_PASS_X_CHAR_LIMIT_IN_FIELD = "";
    public static $ERR_LOGIN_FIRST = "";
    public static $ERR_PASSWORD_VALIDATION = "";
    public static $ERR_PASSWORD_DOESNT_MATCH = "";   
    public static $BTN_LOADING = "";
    public static $LBL_COPIED = "";
    public static $DATATABLES_PROCESSING = "";
    public static $DATATABLES_LENGTHMENU = "";
    public static $DATATABLES_ZERORECORDS = "";
    public static $DATATABLES_INFO = "";
    public static $DATATABLES_INFOEMPTY = "";
    public static $DATATABLES_INFOFILTERED = "";
    public static $DATATABLES_INFOPOSTFIX = "";
    public static $DATATABLES_SEARCH = "";
    public static $DATATABLES_URL = "";
    public static $DATATABLES_PAGINATE_FIRST = "";
    public static $DATATABLES_PAGINATE_PREVIOUS = "";
    public static $DATATABLES_PAGINATE_NEXT = "";
    public static $DATATABLES_PAGINATE_LAST = "";
    public static $DATATABLES_BTN_SEARCH = "";
    public static $DATATABLES_BTN_CLEAR = "";
    public static $DATATABLES_BTN_EXCEL_OUTPUT = "";
    public static $DATATABLES_LBL_PLEASE_WRITE_FILTER = "";

    public function __construct(Languages $lang = Languages::TR)
    {
        Session::delete("Language");
        Session::set("Language", $lang);
        $this->loadLanguage($lang->value);
    }

    private function loadLanguage($langPos)
    {
        $jsonData = file_get_contents(GetDocumentRoot() . '/libs/languages/' . $langPos . '.json');
        $data = json_decode($jsonData, true)[0];
        foreach ($data as $key => $value) {
            self::$$key = $value;
        }
    }

    public static function GetLanguage()
    {
        return !empty( Session::get("Language")) ? Session::get("Language"): self::base_language;
    }

}
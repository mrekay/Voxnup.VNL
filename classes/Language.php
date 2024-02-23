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
    public static $PLEASE_ENTER_VALID_NUMBER = "";
    public static $PLEASE_ENTER_VALID_NUMBER_IN_FIELD = "";
    public static $PLEASE_FILL_THE_REQUIRED_FIELD = "";
    public static $PLEASE_FILL_IN_FIELD = "";
    public static $SUC_EXPORT = "";
    public static $ERR_LOGIN_EXCEPTON = "";
    public static $ERR_LOGIN_PASSIVE = "";
    public static $SUC_LOGIN = "";
    public static $ERR_LOGIN = "";
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
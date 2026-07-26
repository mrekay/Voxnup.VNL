<?php

/* DİL DOSYALARI LSLANGUAGE UYGULAMASINDAN DOLDURULMAKTADIR.*/

class Language
{

     const base_language = Languages::TR;

    public static $ERR_GENERAL = "";
    public static $ERR_INVALID_PARAMETERS_SENT = "";
    public static $ERR_PERM = "";
    public static $ERR_DATABASE = "";
    public static $FUNCTION_NOT_FOUND = "";
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
    public static $PLEASE_DO_NOT_PASS_X_CHAR_MIN_LIMIT = "";
    public static $PLEASE_DO_NOT_PASS_X_CHAR_MIN_LIMIT_IN_FIELD = "";
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

    /* Dil dosyasında olup sınıfta statik karşılığı bulunmayan anahtarlar.
       Eskiden bu durum "Access to undeclared static property" fatal'i veriyordu. */
    private static array $extras = [];

    /* Yükleme sırasında tespit edilen tutarsızlıklar; Language::issues() ile okunur. */
    private static array $issues = [];

    public function __construct(Languages $lang = Languages::TR)
    {
        Session::delete("Language");
        Session::set("Language", $lang);
        $this->loadLanguage($lang->value);
    }

    private function loadLanguage($langPos)
    {
        $loaded = $langPos;
        $data   = self::readLanguageFile($langPos);

        /* Seçili dil okunamadıysa temel dile düş; istek yine de ayakta kalsın. */
        if ($data === null) {
            self::$issues[] = "'{$langPos}' dil dosyası okunamadı veya geçersiz.";
            if ($langPos !== self::base_language->value) {
                $loaded = self::base_language->value;
                $data   = self::readLanguageFile($loaded);
            }
        }

        if ($data === null) {
            self::reportIssues();
            return;
        }

        $known     = self::translatableKeys();
        $undefined = [];

        foreach ($data as $key => $value) {
            if (!is_string($key) || (!is_scalar($value) && !is_null($value))) {
                continue;
            }

            if (isset($known[$key])) {
                self::$$key = (string)$value;
            } else {
                /* Tanımsız anahtar sistemi çökertmez; yedek havuza yazılır. */
                self::$extras[$key] = (string)$value;
                $undefined[] = $key;
            }
        }

        /* Dil dosyasında karşılığı olmayan tanımlı anahtarlar boş string kalır. */
        $missing = array_values(array_diff(array_keys($known), array_keys($data)));

        if ($undefined) {
            self::$issues[] = count($undefined) . " anahtar {$loaded}.json içinde var ama Language sınıfında tanımlı değil: " . self::summarize($undefined);
        }

        if ($missing) {
            self::$issues[] = count($missing) . " özellik Language sınıfında tanımlı ama {$loaded}.json içinde yok: " . self::summarize($missing);
        }

        self::reportIssues();
    }

    private static function summarize(array $keys, int $limit = 8): string
    {
        $shown = array_slice($keys, 0, $limit);
        $text  = implode(', ', $shown);

        return count($keys) > $limit ? $text . ', … (+' . (count($keys) - $limit) . ')' : $text;
    }

    /* Dil dosyasını okur; herhangi bir aksaklıkta hata fırlatmadan null döner. */
    private static function readLanguageFile(string $langPos): ?array
    {
        $path = GetDocumentRoot() . '/libs/languages/' . basename($langPos) . '.json';

        if (!is_file($path) || !is_readable($path)) {
            return null;
        }

        $raw = @file_get_contents($path);
        if ($raw === false || trim($raw) === '') {
            return null;
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return null;
        }

        /* Dosya [ { ... } ] biçimindedir; düz { ... } biçimi de kabul edilir. */
        $data = (isset($decoded[0]) && is_array($decoded[0])) ? $decoded[0] : $decoded;

        return is_array($data) ? $data : null;
    }

    /* Dil anahtarı olarak yazılabilecek özellikler: yalnızca public static olanlar. */
    private static function translatableKeys(): array
    {
        static $keys = null;

        if ($keys === null) {
            $keys = [];
            foreach ((new ReflectionClass(self::class))->getProperties(ReflectionProperty::IS_STATIC) as $property) {
                if ($property->isPublic()) {
                    $keys[$property->getName()] = true;
                }
            }
        }

        return $keys;
    }

    /* Tanımsız anahtarda fatal vermeyen güvenli okuma.
       Karşılığı yoksa $fallback, o da yoksa anahtarın kendisi döner. */
    public static function get(string $key, ?string $fallback = null): string
    {
        $known = self::translatableKeys();

        if (isset($known[$key])) {
            $value = self::$$key;
            if ($value !== null && $value !== '') {
                return (string)$value;
            }
        }

        if (isset(self::$extras[$key]) && self::$extras[$key] !== '') {
            return self::$extras[$key];
        }

        return $fallback ?? $key;
    }

    public static function has(string $key): bool
    {
        return self::get($key, '') !== '';
    }

    /* Language::PLEASE_FILL_IN_FIELD('Ad Soyad') — sprintf'li güvenli kullanım. */
    public static function __callStatic($key, $arguments)
    {
        $text = self::get($key);

        if (empty($arguments)) {
            return $text;
        }

        /* Yer tutucu sayısı tutmazsa metin ham haliyle döner, ValueError fırlatılmaz. */
        try {
            return vsprintf($text, $arguments);
        } catch (Throwable $e) {
            return $text;
        }
    }

    /* Yükleme sırasında biriken tutarsızlık listesi. */
    public static function issues(): array
    {
        return self::$issues;
    }

    private static function reportIssues(): void
    {
        static $reported = 0;

        foreach (array_slice(self::$issues, $reported) as $issue) {
            error_log('[VNL][Language] ' . $issue);
        }

        $reported = count(self::$issues);
    }

    public static function GetLanguage(): Languages
    {
        $stored = Session::get("Language");

        if ($stored instanceof Languages) {
            return $stored;
        }

        /* Session'da eski/bozuk bir değer varsa TypeError yerine temel dile düş. */
        if (is_string($stored) && Languages::tryFrom($stored) !== null) {
            return Languages::from($stored);
        }

        return self::base_language;
    }

}

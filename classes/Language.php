<?php

/**
 * VNL dil sistemi.
 *
 * Dil dosyaları (libs/languages/*.json) TEK doğruluk kaynağıdır; sınıf içinde
 * anahtar tanımı tutulmaz. Bu sayede dosya ile kod arasında kayma oluşamaz ve
 * tanımsız bir anahtara erişmek hiçbir koşulda ölümcül hata vermez.
 *
 * Erişim:  lang('ANAHTAR')  ·  lang('ANAHTAR', $arg)  ·  Language::get('ANAHTAR')
 *
 * DİL DOSYALARI LSLANGUAGE UYGULAMASINDAN DOLDURULMAKTADIR.
 */
class Language
{

    const base_language = Languages::TR;

    /** Aktif dilin anahtar => metin sözlüğü. */
    private static array $lines = [];

    /** Aktif dilde karşılığı olmayan anahtarlar için temel dil sözlüğü. */
    private static array $fallback = [];

    /** Yükleme ve erişim sırasında tespit edilen sorunlar. */
    private static array $issues = [];

    private static ?Languages $current = null;

    public function __construct(Languages $lang = self::base_language)
    {
        self::set($lang);
    }

    /** Aktif dili değiştirir, session'a yazar ve sözlüğü yükler. */
    public static function set(Languages $lang): void
    {
        Session::delete("Language");
        Session::set("Language", $lang);

        self::$current = $lang;
        self::$lines   = self::readFile($lang->value) ?? [];
        self::$fallback = [];

        if (self::$lines === []) {
            self::$issues[] = "'{$lang->value}' dil dosyası okunamadı, geçersiz veya boş.";
        }

        /* Aktif dilde eksik kalan anahtarlar temel dilden tamamlanır. */
        if ($lang !== self::base_language) {
            self::$fallback = self::readFile(self::base_language->value) ?? [];

            $missing = array_diff(array_keys(self::$fallback), array_keys(self::$lines));
            if ($missing) {
                self::$issues[] = count($missing) . " anahtar {$lang->value}.json içinde yok, "
                    . self::base_language->value . ".json'dan karşılanacak: " . self::summarize($missing);
            }
        }

        self::reportIssues();
    }

    /**
     * Anahtarın karşılığını döner. Ek argümanlar sprintf yer tutucularını doldurur.
     * Anahtar hiçbir dil dosyasında yoksa anahtarın kendisi döner — fatal vermez.
     */
    public static function get(string $key, ...$args): string
    {
        if (array_key_exists($key, self::$lines)) {
            $text = (string)self::$lines[$key];
        } elseif (array_key_exists($key, self::$fallback)) {
            $text = (string)self::$fallback[$key];
        } else {
            self::reportMissingKey($key);
            $text = $key;
        }

        if (empty($args)) {
            return $text;
        }

        /* Yer tutucu sayısı tutmazsa metin ham haliyle döner, ValueError fırlatılmaz. */
        try {
            return vsprintf($text, $args);
        } catch (Throwable $e) {
            return $text;
        }
    }

    /** Anahtarın aktif dilde ya da temel dilde karşılığı var mı? */
    public static function has(string $key): bool
    {
        return array_key_exists($key, self::$lines) || array_key_exists($key, self::$fallback);
    }

    /** Tüm sözlük — tanılama ve ön yüze aktarım için. Aktif dil önceliklidir. */
    public static function all(): array
    {
        return self::$lines + self::$fallback;
    }

    /** Language::PLEASE_FILL_IN_FIELD('Ad Soyad') kısayolu. */
    public static function __callStatic($key, $arguments)
    {
        return self::get($key, ...$arguments);
    }

    /** Yüklü olan dil. */
    public static function current(): Languages
    {
        return self::$current ?? self::base_language;
    }

    /** Session'daki dil tercihi; yoksa veya bozuksa temel dil. */
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

    /** Yükleme ve erişim sırasında biriken tutarsızlık listesi. */
    public static function issues(): array
    {
        return self::$issues;
    }

    /* ---------------------------------------------------------------- */

    /** Dil dosyasını okur; herhangi bir aksaklıkta hata fırlatmadan null döner. */
    private static function readFile(string $langPos): ?array
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
        if (!is_array($data)) {
            return null;
        }

        /* Yalnızca skaler değerler metin olarak kullanılabilir. */
        $lines = [];
        foreach ($data as $key => $value) {
            if (is_string($key) && (is_scalar($value) || is_null($value))) {
                $lines[$key] = (string)$value;
            }
        }

        return $lines;
    }

    /** Her anahtar için istek başına yalnızca bir kez rapor edilir. */
    private static function reportMissingKey(string $key): void
    {
        static $seen = [];

        if (isset($seen[$key])) {
            return;
        }
        $seen[$key] = true;

        self::$issues[] = "'{$key}' anahtarı hiçbir dil dosyasında yok.";
        self::reportIssues();
    }

    private static function reportIssues(): void
    {
        static $reported = 0;

        foreach (array_slice(self::$issues, $reported) as $issue) {
            error_log('[VNL][Language] ' . $issue);
        }

        $reported = count(self::$issues);
    }

    private static function summarize(array $keys, int $limit = 8): string
    {
        $shown = array_slice($keys, 0, $limit);
        $text  = implode(', ', $shown);

        return count($keys) > $limit ? $text . ', … (+' . (count($keys) - $limit) . ')' : $text;
    }

}

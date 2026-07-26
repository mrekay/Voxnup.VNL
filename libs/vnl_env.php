<?php
/*!
 * ============================================================
 *  Voxnup Not Laravel (VNL) 3.0.0 — Ortam Değişkenleri
 *  .env dosyasını okur; gizli bilgiler koddan ve sürüm
 *  kontrolünden ayrı tutulur.
 *  Authors: Voxnup Authors
 * ============================================================
 */

/**
 * Basit .env okuyucu.
 *
 * Dış bağımlılık kullanmaz — VNL'in libs/vendor dizini her projeye
 * kopyalandığı için çekirdek, paket eklemeden çalışabilmelidir.
 *
 * Desteklenen biçim:
 *   KEY=value              tırnaksız
 *   KEY="value"            çift tırnak (\n \t \" kaçışları çözülür)
 *   KEY='value'            tek tırnak (ham)
 *   export KEY=value       export öneki yok sayılır
 *   # yorum                satır yorumu
 *   KEY=value # yorum      tırnaksız değerde " #" sonrası atılır
 *
 * true/false/null/empty değerleri PHP karşılıklarına çevrilir.
 * Sunucu seviyesinde tanımlı gerçek ortam değişkeni .env'i ezer.
 */
class VnlEnv
{
    private static array $vars = [];
    private static bool $loaded = false;
    private static ?string $file = null;

    public static function load(?string $path = null): void
    {
        if (self::$loaded && $path === null) {
            return;
        }

        /* Açık yolla yeniden yüklemede önceki değerler taşınmaz;
           aksi halde iki dosyanın içeriği birbirine karışır. */
        if ($path !== null) {
            self::$vars = [];
        }

        self::$loaded = true;
        self::$file   = $path ?? self::defaultPath();

        if (!is_file(self::$file) || !is_readable(self::$file)) {
            return;
        }

        $lines = @file(self::$file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            return;
        }

        foreach ($lines as $line) {
            $line = trim($line);

            if ($line === '' || $line[0] === '#') {
                continue;
            }

            if (str_starts_with($line, 'export ')) {
                $line = trim(substr($line, 7));
            }

            $pos = strpos($line, '=');
            if ($pos === false) {
                continue;
            }

            $key = trim(substr($line, 0, $pos));
            if (!preg_match('/^[A-Za-z_][A-Za-z0-9_.]*$/', $key)) {
                continue;
            }

            self::$vars[$key] = self::parseValue(trim(substr($line, $pos + 1)));
        }
    }

    /** Değeri döner. Sunucu ortam değişkeni varsa .env'in önüne geçer. */
    public static function get(string $key, $default = null)
    {
        self::load();

        $server = getenv($key);
        if ($server !== false && $server !== '') {
            return self::cast($server);
        }

        return array_key_exists($key, self::$vars) ? self::$vars[$key] : $default;
    }

    public static function has(string $key): bool
    {
        self::load();

        return array_key_exists($key, self::$vars) || getenv($key) !== false;
    }

    /** Okunan dosyanın yolu; .env yoksa dosya bulunamadığı için null döner. */
    public static function file(): ?string
    {
        self::load();

        return (self::$file !== null && is_file(self::$file)) ? self::$file : null;
    }

    /* ---------------------------------------------------------------- */

    private static function defaultPath(): string
    {
        $root = rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', "/\\");

        /* CLI'da DOCUMENT_ROOT boştur; libs/ dizininin üstü proje köküdür. */
        if ($root === '') {
            $root = rtrim(dirname(__DIR__), "/\\");
        }

        return $root . '/.env';
    }

    private static function parseValue(string $value)
    {
        if ($value === '') {
            return '';
        }

        $first = $value[0];
        $last  = substr($value, -1);

        if (strlen($value) > 1 && $first === '"' && $last === '"') {
            $inner = substr($value, 1, -1);

            return str_replace(
                ['\\n', '\\r', '\\t', '\\"', '\\\\'],
                ["\n", "\r", "\t", '"', '\\'],
                $inner
            );
        }

        if (strlen($value) > 1 && $first === "'" && $last === "'") {
            return substr($value, 1, -1);
        }

        /* Tırnaksız değerde satır sonu yorumu atılır: KEY=value # yorum */
        $hash = strpos($value, ' #');
        if ($hash !== false) {
            $value = rtrim(substr($value, 0, $hash));
        }

        return self::cast($value);
    }

    private static function cast(string $value)
    {
        switch (strtolower($value)) {
            case 'true':
            case '(true)':
                return true;
            case 'false':
            case '(false)':
                return false;
            case 'null':
            case '(null)':
                return null;
            case 'empty':
            case '(empty)':
                return '';
        }

        return $value;
    }
}

/**
 * Ortam değişkenini okur.
 *
 *   env('DB_HOST', 'localhost')
 *   env('APP_DEBUG', false)
 */
function env(string $key, $default = null)
{
    return VnlEnv::get($key, $default);
}

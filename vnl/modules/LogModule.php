<?php
/*!
 * ============================================================
 *  Voxnup Not Laravel (VNL) — Günlük Modülü
 *  Kanal ve seviye taşıyan JSON satırı günlükler.
 *  Authors: Voxnup Authors
 * ============================================================
 */

/**
 * Uygulama günlüğü.
 *
 *   LogModule::info('backup', 'Yedek alındı', ['domain' => 'x.com']);
 *   LogModule::error('queue', 'İş düştü', ['job' => 42]);
 *   LogModule::write('mail', 'warning', 'SMTP yavaş', ['ms' => 5200]);
 *
 * Her satır bir JSON nesnesidir:
 *   {"ts","level","channel","msg","ctx","user","ip"}
 * Dosya: <LOG_PATH>/<kanal>-YYYY-MM-DD.log  (LOG_PATH varsayılanı files/logs)
 *
 * 3.4.0'a kadar günlükler web kökünde (mysql-log/, event-log/) 0777 izinle
 * oluşturuluyordu: dünyaca yazılabilir ve .htaccess'e bağlı olarak web'den
 * okunabilir. Artık dizinler 0750, dosyalar 0640; varsayılan yer files/logs
 * (nginx/.htaccess örnekleri bu dizini servis etmez).
 *
 * Günlük yazılamazsa istek DÜŞMEZ: hata PHP'nin error_log'una gider.
 */
class LogModule
{
    public const LEVELS = ['debug', 'info', 'notice', 'warning', 'error', 'critical'];

    /** Günlük dizini (sonunda ayraç yok). */
    public static function path(): string
    {
        $path = (string)VnlEnv::get('LOG_PATH', '');

        if ($path === '') {
            $path = VNL_ROOT . '/files/logs';
        } elseif (!preg_match('~^([A-Za-z]:[\\\\/]|[\\\\/])~', $path)) {
            $path = VNL_ROOT . '/' . $path;   // göreli yol proje köküne göredir
        }

        return rtrim($path, '/\\');
    }

    public static function write(string $channel, string $level, string $message, array $context = []): void
    {
        $channel = preg_replace('/[^a-z0-9_.-]/i', '-', $channel) ?: 'app';
        $level   = in_array(strtolower($level), self::LEVELS, true) ? strtolower($level) : 'info';

        $user = null;
        if (function_exists('vnl_user')) {
            try {
                $current = vnl_user();
                $user    = $current !== null ? (int)$current->id : null;
            } catch (Throwable $e) {
                $user = null;
            }
        }

        $line = json_encode([
            'ts'      => date('c'),
            'level'   => $level,
            'channel' => $channel,
            'msg'     => $message,
            'ctx'     => $context === [] ? new stdClass() : $context,
            'user'    => $user,
            'ip'      => PHP_SAPI === 'cli' ? 'cli' : ($_SERVER['REMOTE_ADDR'] ?? null),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PARTIAL_OUTPUT_ON_ERROR);

        self::append($channel . '-' . date('Y-m-d') . '.log', (string)$line);
    }

    public static function debug(string $channel, string $message, array $context = []): void
    {
        self::write($channel, 'debug', $message, $context);
    }

    public static function info(string $channel, string $message, array $context = []): void
    {
        self::write($channel, 'info', $message, $context);
    }

    public static function warning(string $channel, string $message, array $context = []): void
    {
        self::write($channel, 'warning', $message, $context);
    }

    public static function error(string $channel, string $message, array $context = []): void
    {
        self::write($channel, 'error', $message, $context);
    }

    /* ---- 3.3 uyumluluğu: eski adlar yeni API'ye yönlenir ---- */

    /** @deprecated 3.4.0 — LogModule::error('mysql', ...) */
    static function SetMysqErrorlLog($log_msg)
    {
        self::write('mysql', 'error', (string)$log_msg);
    }

    /** @deprecated 3.4.0 — LogModule::info('mysql', ...) */
    static function SetMysqlLog($log_msg)
    {
        self::write('mysql', 'info', (string)$log_msg);
    }

    /** @deprecated 3.4.0 — LogModule::info('event', ...) */
    static function SetEventLog($log_msg)
    {
        self::write('event', 'info', (string)$log_msg);
    }

    /* ---------------------------------------------------------------- */

    private static function append(string $file, string $line): void
    {
        $dir = self::path();

        if (!is_dir($dir) && !@mkdir($dir, 0750, true) && !is_dir($dir)) {
            error_log('[VNL][Log] Günlük dizini oluşturulamadı: ' . $dir . ' — ' . $line);

            return;
        }

        $path  = $dir . '/' . $file;
        $isNew = !is_file($path);

        if (@file_put_contents($path, $line . "\n", FILE_APPEND | LOCK_EX) === false) {
            error_log('[VNL][Log] Günlük yazılamadı: ' . $path . ' — ' . $line);

            return;
        }

        if ($isNew) {
            @chmod($path, 0640);
        }
    }
}

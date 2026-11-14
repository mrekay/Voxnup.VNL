<?php
/*!
 * ============================================================
 *  Voxnup Not Laravel (VNL) — Test Alt Süreci
 *  Tek bir test dosyasını yalıtılmış süreçte koşar.
 *  Authors: Voxnup Authors
 * ============================================================
 *
 *   php vnl/tests/lib/run-file.php <test dosyası> <sonuç dosyası> [filtre]
 *
 * `php vnl.php test` çağırır; elle çalıştırmak gerekmez. Sonuçlar satır
 * başına bir JSON olarak sonuç dosyasına EKLENİR: her testten önce
 * {"event":"start"}, sonra {"event":"end","status":...}. Test exit() ile
 * biterse "end" satırı yazılmaz ve çalıştırıcı testi "düştü" sayar.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit;
}

[, $vnlTestFile, $vnlResultFile] = $argv + [null, null, null];
$vnlTestFilter = (string)($argv[3] ?? '');

if (!is_string($vnlTestFile) || !is_file($vnlTestFile) || !is_string($vnlResultFile)) {
    fwrite(STDERR, "kullanim: run-file.php <test> <sonuc> [filtre]\n");
    exit(2);
}

define('VNL_PATH', dirname(__DIR__, 2));
define('VNL_ROOT', rtrim((string)(getenv('VNL_TEST_ROOT') ?: dirname(VNL_PATH)), "/\\"));
define('VNL_TESTING', true);

require_once VNL_PATH . '/cli.php';

/* Komut satırında SCRIPT_NAME bu dosyanın yoludur; getSubFolderName() onu
   alt klasör kurulumu sanardı. Test süreci kökte kurulu bir site gibi açılır. */
$_SERVER['SCRIPT_NAME'] = '/index.php';

vnl_cli_boot();

require_once __DIR__ . '/VnlTestCase.php';
require_once __DIR__ . '/VnlHttpTestCase.php';

$vnlEmit = static function (array $event) use ($vnlResultFile): void {
    file_put_contents($vnlResultFile, json_encode($event, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE) . "\n", FILE_APPEND | LOCK_EX);
};

$vnlBefore = get_declared_classes();
require $vnlTestFile;
$vnlClasses = array_diff(get_declared_classes(), $vnlBefore);

foreach ($vnlClasses as $vnlClass) {
    $vnlRef = new ReflectionClass($vnlClass);

    if ($vnlRef->isAbstract() || !$vnlRef->isSubclassOf(VnlTestCase::class)) {
        continue;
    }

    foreach ($vnlRef->getMethods(ReflectionMethod::IS_PUBLIC) as $vnlMethod) {
        $vnlName = $vnlMethod->getName();

        if (!str_starts_with($vnlName, 'test') || $vnlMethod->isStatic() || $vnlMethod->getDeclaringClass()->getName() !== $vnlClass) {
            continue;
        }

        $vnlId = $vnlClass . '::' . $vnlName;

        if ($vnlTestFilter !== '' && stripos($vnlId, $vnlTestFilter) === false && stripos(basename($vnlTestFile), $vnlTestFilter) === false) {
            continue;
        }

        $vnlEmit(['event' => 'start', 'test' => $vnlId]);

        $vnlStatus  = 'pass';
        $vnlMessage = '';
        $vnlStarted = microtime(true);

        /* Testin her çalıştırmada temiz durumla başlaması: oturum ve süper
           küreseller süreç içinde paylaşılıyor. */
        $_SESSION = [];
        $vnlServer = $_SERVER;

        ob_start();
        $vnlInstance = $vnlRef->newInstance();

        try {
            $vnlInstance->setUp();
            $vnlInstance->$vnlName();
        } catch (VnlTestSkipped $e) {
            $vnlStatus  = 'skip';
            $vnlMessage = $e->getMessage();
        } catch (VnlAssertionFailed $e) {
            $vnlStatus  = 'fail';
            $vnlMessage = $e->getMessage();
        } catch (Throwable $e) {
            $vnlStatus  = 'error';
            $vnlMessage = get_class($e) . ': ' . $e->getMessage() . "\n  " . $e->getFile() . ':' . $e->getLine();
        }

        try {
            $vnlInstance->tearDown();
        } catch (Throwable $e) {
            if ($vnlStatus === 'pass') {
                $vnlStatus  = 'error';
                $vnlMessage = 'tearDown: ' . get_class($e) . ': ' . $e->getMessage();
            }
        }

        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        $_SERVER = $vnlServer;

        $vnlEmit([
            'event'   => 'end',
            'test'    => $vnlId,
            'status'  => $vnlStatus,
            'message' => $vnlMessage,
            'ms'      => (int)round((microtime(true) - $vnlStarted) * 1000),
        ]);
    }
}

exit(0);

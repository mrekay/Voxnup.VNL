<?php
/*!
 * ============================================================
 *  Voxnup Not Laravel (VNL) — Eşzamanlılık Testi Alt Süreci
 *  Aynı satıra aynı anda dokunan süreçleri taklit eder.
 *  Authors: Voxnup Authors
 * ============================================================
 *
 *   php vnl/tests/lib/db-worker.php hit <anahtar> <adet> <basla_unix>
 *   php vnl/tests/lib/db-worker.php reserve <kuyruk> <basla_unix>
 *
 * CoreTablesTest çağırır. Süreçler <basla_unix> anına kadar bekleyip aynı
 * anda başlar. Çıktı tek satır JSON'dur: hit → {"errors": n}, reserve →
 * {"ids": [...], "errors": n}.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit;
}

define('VNL_PATH', dirname(__DIR__, 2));
define('VNL_ROOT', rtrim((string)(getenv('VNL_TEST_ROOT') ?: dirname(VNL_PATH)), "/\\"));
define('VNL_TESTING', true);

require_once VNL_PATH . '/cli.php';

$_SERVER['SCRIPT_NAME'] = '/index.php';

vnl_cli_boot();

$mode = (string)($argv[1] ?? '');

$waitUntil = static function (float $start): void {
    while (microtime(true) < $start) {
        usleep(2000);
    }
};

if ($mode === 'hit') {
    $key   = (string)$argv[2];
    $count = (int)$argv[3];
    $waitUntil((float)$argv[4]);

    /* Her süreç aynı anahtar listesini aynı sırayla sayar: her anahtarın
       İLK sayımı (satır yokken) süreçler arasında yarışır — kilitsiz bir
       upsert'ün kırıldığı yer orasıdır. */
    $errors = [];
    for ($i = 0; $i < $count; $i++) {
        try {
            RateLimiter::hit($key . ':' . $i, 1000000, 600);
        } catch (Throwable $e) {
            $errors[] = $e->getMessage();
        }
    }

    echo json_encode(['errors' => count($errors), 'first' => $errors[0] ?? null]), "\n";
    exit(0);
}

if ($mode === 'reserve') {
    $queue = (string)$argv[2];
    $waitUntil((float)$argv[3]);

    $ids    = [];
    $errors = [];
    $empty  = 0;

    /* Kuyruk boşalana kadar al; boş görünce birkaç kez daha dene (başka
       süreçlerin kilitlediği satırlar atlanmış olabilir). */
    while ($empty < 3) {
        try {
            $job = Queue::reserve($queue);
        } catch (Throwable $e) {
            $errors[] = $e->getMessage();
            usleep(10000);
            continue;
        }

        if ($job === null) {
            $empty++;
            usleep(20000);
            continue;
        }

        $empty = 0;
        $ids[] = $job->id();
    }

    echo json_encode(['ids' => $ids, 'errors' => count($errors), 'first' => $errors[0] ?? null]), "\n";
    exit(0);
}

fwrite(STDERR, "kullanim: db-worker.php hit|reserve ...\n");
exit(2);

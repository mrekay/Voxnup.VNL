<?php
/*!
 * ============================================================
 *  Voxnup Not Laravel (VNL) — serve komutu
 *  Authors: Voxnup Authors
 * ============================================================
 */

return static function (array $args): int {
    $host = '0.0.0.0';
    $port = '8080';

    foreach ($args as $i => $arg) {
        if ($arg === '--port' || $arg === '-p') {
            $port = $args[$i + 1] ?? $port;
        } elseif ($arg === '--host') {
            $host = $args[$i + 1] ?? $host;
        }
    }

    if (in_array('--help', $args, true) || in_array('-h', $args, true)) {
        fwrite(STDOUT, <<<TXT

  Yerel gelistirme sunucusunu baslatir.

    php vnl.php serve
    php vnl.php serve --port 8091
    php vnl.php serve --host 127.0.0.1 --port 8091

  Uretim icin degildir: php -S tek is parcaciklidir ve guvenlik
  sertlestirmesi yoktur.


TXT
        );

        return 0;
    }

    if (!function_exists('passthru')) {
        fwrite(STDERR, "\n  passthru() kapali; sunucu baslatilamiyor. Elle:\n"
            . "    php -S {$host}:{$port} vnl/tools/dev-router.php\n\n");

        return 1;
    }

    fwrite(STDOUT, "\n  http://{$host}:{$port}  —  durdurmak icin Ctrl+C\n\n");

    /* PHP_BINARY, bu betigi calistiran yorumlayicidir: birden fazla PHP
       kurulu makinede "php" adiyla cagirmak baska bir surume dusebilir. */
    $command = escapeshellarg(PHP_BINARY)
        . ' -S ' . escapeshellarg($host . ':' . $port)
        . ' -t ' . escapeshellarg(VNL_ROOT)
        . ' ' . escapeshellarg(VNL_PATH . '/tools/dev-router.php');

    passthru($command, $exitCode);

    return (int)$exitCode;
};

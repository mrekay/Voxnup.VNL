<?php
/*!
 * ============================================================
 *  Voxnup Not Laravel (VNL) — test komutu
 *  Çekirdek ve proje testlerini yalıtılmış süreçlerde koşar.
 *  Authors: Voxnup Authors
 * ============================================================
 *
 * Testler iki yerde durur:
 *   vnl/tests/<takım>/*Test.php   çekirdeğin — projeye kurulu hâliyle,
 *                                  projenin ezdiği dosyalara karşı koşar
 *   tests/<takım>/*Test.php        projenin
 *
 * Takımlar: unit (süreç içi, veritabanısız), http (yerel sunucuya gerçek
 * istek), db (veritabanı yoksa atlanır). http ya da db seçiliyse komut
 * php -S ile bir sunucu açar ve adresini VNL_TEST_BASE_URL ile verir.
 */

return static function (array $args): int {
    $opts = vnl_cli_options($args);

    if (isset($opts['help']) || in_array('-h', $args, true)) {
        fwrite(STDOUT, <<<TXT

  Cekirdek ve proje testlerini calistirir.

    php vnl.php test                       hepsi
    php vnl.php test --suite=unit,http     yalniz bu takimlar (unit|http|db)
    php vnl.php test --filter=Csrf         adinda bu gecen dosya/testler
    php vnl.php test --core | --project    yalniz cekirdegin / projenin testleri
    php vnl.php test --verbose             her testi ayri satirda goster

  Cikis kodu: basarisiz ya da dusen test varsa 1.


TXT);

        return 0;
    }

    $suites  = isset($opts['suite']) && is_string($opts['suite'])
        ? array_values(array_intersect(['unit', 'http', 'db'], array_map('trim', explode(',', $opts['suite']))))
        : ['unit', 'http', 'db'];
    $filter  = isset($opts['filter']) && is_string($opts['filter']) ? $opts['filter'] : '';
    $verbose = isset($opts['verbose']);

    $roots = [];
    if (!isset($opts['project'])) {
        $roots['cekirdek'] = VNL_PATH . '/tests';
    }
    if (!isset($opts['core'])) {
        $roots['proje'] = VNL_ROOT . '/tests';
    }

    /* Dosyaları topla: takım sırası korunur (unit → http → db). */
    $files = [];
    foreach ($suites as $suite) {
        foreach ($roots as $owner => $dir) {
            foreach (glob($dir . '/' . $suite . '/*Test.php') ?: [] as $path) {
                $files[] = ['suite' => $suite, 'owner' => $owner, 'path' => $path];
            }
        }
    }

    if ($files === []) {
        fwrite(STDOUT, "\n  Test bulunamadi.\n\n");

        return 0;
    }

    $tmp = sys_get_temp_dir() . '/vnl-test-' . bin2hex(random_bytes(4));
    @mkdir($tmp, 0700, true);

    $cleanup = static function () use ($tmp): void {
        foreach (glob($tmp . '/*') ?: [] as $f) {
            @unlink($f);
        }
        @rmdir($tmp);
    };

    /* Sunucu: yalnız http/db dosyası varsa. */
    $server  = null;
    $baseUrl = '';
    $needsServer = false;
    foreach ($files as $f) {
        if ($f['suite'] !== 'unit') {
            $needsServer = true;
            break;
        }
    }

    $env = getenv();
    $env['VNL_TEST_ROOT'] = VNL_ROOT;

    if ($needsServer) {
        $probe = @stream_socket_server('tcp://127.0.0.1:0', $errno, $errstr);
        $port  = $probe ? (int)substr(strrchr((string)stream_socket_get_name($probe, false), ':'), 1) : 0;
        if ($probe) {
            fclose($probe);
        }

        if ($port <= 0) {
            fwrite(STDERR, "  Bos port bulunamadi; http/db testleri atlanacak.\n");
        } else {
            /* Canlı ayarlar sunucu sürecinde ezilir: ayrıntılı hata sayfası
               kapalı, belgeler girişe kapalı (çekirdeğin varsayılanı). */
            $serverEnv = $env + [];
            $serverEnv['APP_DEBUG']       = 'false';
            $serverEnv['VNL_PUBLIC_DOCS'] = 'false';

            $server = proc_open(
                [PHP_BINARY, '-S', '127.0.0.1:' . $port, VNL_PATH . '/tools/dev-router.php'],
                [0 => ['pipe', 'r'], 1 => ['file', $tmp . '/server.out', 'w'], 2 => ['file', $tmp . '/server.log', 'w']],
                $pipes,
                VNL_ROOT,
                $serverEnv
            );

            $ready = false;
            for ($i = 0; $i < 100 && is_resource($server); $i++) {
                $conn = @fsockopen('127.0.0.1', $port, $errno, $errstr, 0.2);
                if ($conn) {
                    fclose($conn);
                    $ready = true;
                    break;
                }
                usleep(50000);
            }

            if ($ready) {
                $baseUrl = 'http://127.0.0.1:' . $port;
                $env['VNL_TEST_BASE_URL'] = $baseUrl;
            } else {
                fwrite(STDERR, "  Test sunucusu acilamadi; http/db testleri atlanacak.\n");
            }
        }
    }

    $stopServer = static function () use (&$server): void {
        if (is_resource($server)) {
            proc_terminate($server);
            proc_close($server);
        }
        $server = null;
    };

    $totals  = ['pass' => 0, 'fail' => 0, 'error' => 0, 'skip' => 0, 'crash' => 0];
    $reports = [];
    $started = microtime(true);

    fwrite(STDOUT, "\n  VNL testleri" . ($baseUrl !== '' ? "  (sunucu {$baseUrl})" : '') . "\n");

    $currentSuite = '';
    foreach ($files as $i => $file) {
        if ($file['suite'] !== $currentSuite) {
            $currentSuite = $file['suite'];
            fwrite(STDOUT, "\n  [{$currentSuite}]\n");
        }

        $result = $tmp . "/result-{$i}.jsonl";
        $out    = $tmp . "/out-{$i}.txt";
        touch($result);

        $proc = proc_open(
            [PHP_BINARY, VNL_PATH . '/tests/lib/run-file.php', $file['path'], $result, $filter],
            [0 => ['pipe', 'r'], 1 => ['file', $out, 'w'], 2 => ['file', $out, 'a']],
            $pipes,
            VNL_ROOT,
            $env
        );

        if (!is_resource($proc)) {
            $totals['crash']++;
            $reports[] = ['crash', $file['path'], 'alt surec baslatilamadi'];
            continue;
        }

        fclose($pipes[0]);
        $exit = proc_close($proc);

        $started_tests = [];
        $ended         = [];
        foreach (file($result, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
            $event = json_decode($line, true);
            if (!is_array($event)) {
                continue;
            }
            if ($event['event'] === 'start') {
                $started_tests[] = $event['test'];
            } elseif ($event['event'] === 'end') {
                $ended[$event['test']] = $event;
            }
        }

        $rel  = ltrim(str_replace([VNL_ROOT, '\\'], ['', '/'], $file['path']), '/');
        $line = sprintf('    %-52s ', $rel);
        $marks = '';

        foreach ($started_tests as $test) {
            $event  = $ended[$test] ?? null;
            $status = $event['status'] ?? 'crash';
            $totals[$status]++;

            $marks .= ['pass' => '.', 'fail' => 'F', 'error' => 'E', 'skip' => 's', 'crash' => 'X'][$status];

            if ($verbose) {
                fwrite(STDOUT, sprintf("      %-5s %s%s\n", strtoupper($status), $test,
                    $status === 'skip' ? '  (' . $event['message'] . ')' : ''));
            }

            if ($status === 'fail' || $status === 'error') {
                $reports[] = [$status, $test, $event['message']];
            } elseif ($status === 'crash') {
                $tail = trim(implode("\n", array_slice(file($out, FILE_IGNORE_NEW_LINES) ?: [], -15)));
                $reports[] = ['crash', $test, "test sureci sonlandirdi (exit {$exit})" . ($tail !== '' ? "\n" . $tail : '')];
            } elseif ($status === 'skip' && !$verbose) {
                $reports[] = ['skip', $test, $event['message']];
            }
        }

        /* Hiç test başlamadan düşen dosya (söz dizimi, önyükleme hatası). */
        if ($started_tests === [] && $exit !== 0) {
            $totals['crash']++;
            $marks .= 'X';
            $tail = trim(implode("\n", array_slice(file($out, FILE_IGNORE_NEW_LINES) ?: [], -15)));
            $reports[] = ['crash', $rel, "dosya yuklenemedi (exit {$exit})" . ($tail !== '' ? "\n" . $tail : '')];
        }

        if (!$verbose) {
            fwrite(STDOUT, $line . ($marks === '' ? '-' : $marks) . "\n");
        }
    }

    if ($server !== null && is_file($tmp . '/server.log') && ($totals['fail'] + $totals['error'] + $totals['crash']) > 0) {
        $log = trim(implode("\n", array_slice(file($tmp . '/server.log', FILE_IGNORE_NEW_LINES) ?: [], -20)));
        $log = implode("\n", array_filter(explode("\n", $log), static fn($l) => !preg_match('/Accepted|Closing|\[\d{3}\]: (GET|POST)/', $l)));
        if ($log !== '') {
            $reports[] = ['log', 'sunucu gunlugu (son satirlar)', $log];
        }
    }

    $stopServer();
    $cleanup();

    $skips = array_filter($reports, static fn($r) => $r[0] === 'skip');
    if ($skips !== []) {
        fwrite(STDOUT, "\n  Atlananlar\n");
        foreach ($skips as [, $name, $msg]) {
            fwrite(STDOUT, "    - {$name}: {$msg}\n");
        }
    }

    foreach ($reports as [$kind, $name, $msg]) {
        if ($kind === 'skip') {
            continue;
        }
        $label = ['fail' => 'BASARISIZ', 'error' => 'HATA', 'crash' => 'DUSTU', 'log' => 'GUNLUK'][$kind];
        fwrite(STDOUT, "\n  {$label}  {$name}\n");
        foreach (explode("\n", (string)$msg) as $l) {
            fwrite(STDOUT, "    {$l}\n");
        }
    }

    $bad = $totals['fail'] + $totals['error'] + $totals['crash'];
    fwrite(STDOUT, sprintf(
        "\n  %s  %d gecti, %d basarisiz, %d hata, %d dustu, %d atlandi  (%.1f sn)\n\n",
        $bad === 0 ? 'TAMAM' : 'KIRMIZI',
        $totals['pass'], $totals['fail'], $totals['error'], $totals['crash'], $totals['skip'],
        microtime(true) - $started
    ));

    return $bad === 0 ? 0 : 1;
};

<?php
/*!
 * ============================================================
 *  Voxnup Not Laravel (VNL) — schedule:run komutu
 *  config/schedule.php'deki zamanı gelmiş görevleri çalıştırır.
 *  Authors: Voxnup Authors
 * ============================================================
 *
 * Sistem cron'u her dakika bir kez çağırır:
 *
 *   * * * * * www-data php /opt/vnl-panel/vnl.php schedule:run >> /dev/null 2>&1
 *
 * config/schedule.php bir liste döner:
 *
 *   return [
 *       ['cron' => '*\/5 * * * *', 'command' => 'backup:prune', 'args' => ['--days=7']],
 *       ['cron' => '0 3 * * *',   'job' => 'backup.nightly', 'payload' => [], 'queue' => 'system'],
 *   ];
 *
 * 'command' girdileri AYRI SÜREÇTE (proc_open, argüman dizisi — kabuk yok)
 * paralel çalışır; komut bitene kadar girdinin kilidi tutulur, bir sonraki
 * dakikanın schedule:run'ı hâlâ süren görevi atlar (üst üste binme yok).
 * 'job' girdileri Queue::push ile kuyruğa yazılır.
 *
 * Aynı dakika içinde iki kez çağrılsa da her girdi dakikada bir kez çalışır
 * (kilit dosyası son çalıştığı dakikayı tutar).
 * Kilitler: files/cache/schedule-<sha1>.lock · Çıktı: <LOG_PATH>/schedule-YYYY-MM-DD.log
 */

return static function (array $args): int {
    $o = vnl_cli_options($args);

    if (isset($o['help'])) {
        fwrite(STDOUT, "\n  Zamani gelen gorevleri calistirir (cron: her dakika).\n\n"
            . "    php vnl.php schedule:run [--list]\n\n"
            . "  Gorevler: config/schedule.php\n\n");

        return 0;
    }

    $file = VNL_ROOT . '/config/schedule.php';

    if (!is_file($file)) {
        fwrite(STDOUT, "  config/schedule.php yok; calisacak gorev yok.\n");

        return 0;
    }

    vnl_cli_boot();

    $entries = require $file;

    if (!is_array($entries)) {
        fwrite(STDERR, "  config/schedule.php bir dizi dondurmeli.\n");

        return 1;
    }

    $say = static function (string $line): void {
        fwrite(STDOUT, '[' . date('Y-m-d H:i:s') . '] ' . $line . "\n");
    };

    if (isset($o['list'])) {
        foreach ($entries as $i => $entry) {
            $cron = (string)($entry['cron'] ?? '');
            $what = isset($entry['command']) ? 'komut ' . $entry['command'] : 'is ' . ($entry['job'] ?? '?');
            fwrite(STDOUT, sprintf("  %-3d %-16s %-40s %s\n", $i, $cron, $what,
                VnlCron::isValid($cron) ? (VnlCron::isDue($cron) ? 'SIMDI' : '') : 'GECERSIZ'));
        }

        return 0;
    }

    $lockDir = VNL_ROOT . '/files/cache';
    if (!is_dir($lockDir) && !@mkdir($lockDir, 0750, true) && !is_dir($lockDir)) {
        fwrite(STDERR, "  Kilit dizini olusturulamadi: {$lockDir}\n");

        return 1;
    }

    $logDir = LogModule::path();
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0750, true);
    }
    $logFile = $logDir . '/schedule-' . date('Y-m-d') . '.log';

    $now      = time();
    $minute   = date('Y-m-d H:i', $now);
    $running  = [];   // [proc, lock handle, name]
    $failures = 0;

    foreach ($entries as $i => $entry) {
        if (!is_array($entry) || empty($entry['cron'])) {
            $say("#{$i} atlandi: 'cron' yok");
            $failures++;
            continue;
        }

        $cron = (string)$entry['cron'];

        if (!VnlCron::isValid($cron)) {
            $say("#{$i} atlandi: gecersiz cron ifadesi '{$cron}'");
            $failures++;
            continue;
        }

        if (!VnlCron::isDue($cron, $now)) {
            continue;
        }

        $name = isset($entry['command']) ? 'command:' . $entry['command'] : 'job:' . ($entry['job'] ?? '');
        $lock = fopen($lockDir . '/schedule-' . sha1($cron . '|' . json_encode($entry)) . '.lock', 'c+');

        if ($lock === false) {
            $say("{$name}: kilit dosyasi acilamadi");
            $failures++;
            continue;
        }

        /* Önceki çalıştırma sürüyorsa atla (üst üste binme). */
        if (!flock($lock, LOCK_EX | LOCK_NB)) {
            $say("{$name}: onceki calistirma suruyor, atlandi");
            fclose($lock);
            continue;
        }

        /* Bu dakika zaten çalıştıysa atla (çift cron satırı). */
        rewind($lock);
        if (trim((string)stream_get_contents($lock)) === $minute) {
            flock($lock, LOCK_UN);
            fclose($lock);
            continue;
        }

        ftruncate($lock, 0);
        rewind($lock);
        fwrite($lock, $minute);
        fflush($lock);

        if (isset($entry['job'])) {
            try {
                $id = Queue::push((string)$entry['job'], (array)($entry['payload'] ?? []), [
                    'queue'        => (string)($entry['queue'] ?? 'default'),
                    'max_attempts' => (int)($entry['max_attempts'] ?? 1),
                ]);
                $say("{$name}: kuyruga yazildi (#{$id})");
            } catch (Throwable $e) {
                $say("{$name}: kuyruga yazilamadi — " . $e->getMessage());
                $failures++;
            }

            flock($lock, LOCK_UN);
            fclose($lock);
            continue;
        }

        if (!isset($entry['command'])) {
            $say("#{$i} atlandi: 'command' ya da 'job' gerekli");
            $failures++;
            flock($lock, LOCK_UN);
            fclose($lock);
            continue;
        }

        $command = (string)$entry['command'];
        if (!preg_match('/^[a-z0-9][a-z0-9:_-]*$/', $command)) {
            $say("{$name}: gecersiz komut adi");
            $failures++;
            flock($lock, LOCK_UN);
            fclose($lock);
            continue;
        }

        $argv = [PHP_BINARY, VNL_ROOT . '/vnl.php', $command];
        foreach ((array)($entry['args'] ?? []) as $arg) {
            $argv[] = (string)$arg;
        }

        $proc = proc_open($argv, [
            0 => ['file', (DIRECTORY_SEPARATOR === '\\' ? 'NUL' : '/dev/null'), 'r'],
            1 => ['file', $logFile, 'a'],
            2 => ['file', $logFile, 'a'],
        ], $pipes, VNL_ROOT);

        if (!is_resource($proc)) {
            $say("{$name}: surec baslatilamadi");
            $failures++;
            flock($lock, LOCK_UN);
            fclose($lock);
            continue;
        }

        $say("{$name}: basladi");
        $running[] = [$proc, $lock, $name];
    }

    /* Komutlar paralel çalışır; kilitler her biri bitene kadar tutulur. */
    foreach ($running as [$proc, $lock, $name]) {
        $code = proc_close($proc);
        $say("{$name}: bitti (cikis kodu {$code})");

        if ($code !== 0) {
            $failures++;
        }

        flock($lock, LOCK_UN);
        fclose($lock);
    }

    return $failures > 0 ? 1 : 0;
};

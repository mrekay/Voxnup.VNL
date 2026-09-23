<?php
/*!
 * ============================================================
 *  Voxnup Not Laravel (VNL) — queue:work komutu
 *  Kuyruk işçisi: işleri ayırır, çalıştırır, sonucu yazar.
 *  Authors: Voxnup Authors
 * ============================================================
 *
 *   php vnl.php queue:work                         varsayılan kuyruk, sürekli
 *   php vnl.php queue:work --queue=system --sleep=2
 *   php vnl.php queue:work --once                  tek iş (ya da hiç) ve çık
 *
 * systemd altında çalışması için tasarlandı: --max-time dolunca ya da
 * SIGTERM/SIGINT gelince eldeki işi bitirip temiz çıkar (Restart=always
 * yeniden başlatır; bellek sızıntısı birikmez). pcntl yoksa sinyal
 * yakalanmaz, süre sınırı yine işler.
 *
 * İşleyici: VNL_ROOT/jobs/<tür>.php → function (array $payload, VnlJob $job): void
 * İşleyicinin echo çıktısı da işin output'una eklenir.
 */

/**
 * Tek işi çalıştırır ve sonucunu yazar. İşleyici istisnası işçiyi
 * durdurmaz: iş yeniden kuyruğa ya da failed'a düşer, işçi sürer.
 */
function vnl_queue_run_job(VnlJob $job, callable $say): void
{
    global $CurrentUser;

    /* Her iş temiz bir kimlikle başlar; denetim kaydı işi yazan kişiye düşer. */
    $CurrentUser = new User();
    Audit::actingAs($job->createdBy());

    $table = Queue::TABLE;
    $path  = Queue::handlerPath($job->type());

    ob_start();

    try {
        if ($path === null) {
            throw new RuntimeException('İşleyici bulunamadı: jobs/' . $job->type() . '.php');
        }

        $handler = require $path;

        if (!is_callable($handler)) {
            throw new RuntimeException('jobs/' . $job->type() . '.php bir fonksiyon döndürmüyor.');
        }

        $handler($job->payload(), $job);

        $echoed = (string)ob_get_clean();
        if ($echoed !== '') {
            $job->log(rtrim($echoed));
        }

        Database::table($table)->where('id', $job->id())->update([
            'status'      => 'done',
            'progress'    => 100,
            'finished_at' => Queue::now(),
            'reserved_by' => null,
            'updated_at'  => Queue::now(),
        ]);

        $say('#' . $job->id() . ' tamamlandi');
    } catch (Throwable $e) {
        $echoed = (string)ob_get_clean();

        try {
            if ($echoed !== '') {
                $job->log(rtrim($echoed));
            }

            $retry = $job->attempts() < $job->maxAttempts();
            $error = get_class($e) . ': ' . $e->getMessage();

            Database::table($table)->where('id', $job->id())->update([
                'status'       => $retry ? 'queued' : 'failed',
                'error'        => mb_substr($error, 0, 60000),
                'available_at' => $retry ? Queue::now(30 * max(1, $job->attempts())) : Queue::now(),
                'finished_at'  => $retry ? null : Queue::now(),
                'reserved_by'  => null,
                'reserved_at'  => null,
                'updated_at'   => Queue::now(),
            ]);

            $ref = vnl_log_exception($e, 'VNL][Queue');
            $say('#' . $job->id() . ($retry ? ' basarisiz, yeniden denenecek' : ' basarisiz (son deneme)')
                . " — {$error} (ref {$ref})");
        } catch (Throwable $inner) {
            /* Veritabanı gitti: iş 'running' kalır, --stale sonra kurtarır. */
            $say('#' . $job->id() . ' sonucu yazilamadi: ' . $inner->getMessage());
        }
    } finally {
        Audit::actingAs(null);
    }
}

return static function (array $args): int {
    $o = vnl_cli_options($args);

    if (isset($o['help'])) {
        fwrite(STDOUT, <<<TXT

  Kuyruk iscisini calistirir.

    php vnl.php queue:work [--queue=default] [--once] [--sleep=3]
                           [--max-time=3600] [--max-jobs=0] [--stale=3600]
                           [--memory=256]

  --queue      dinlenecek kuyruk
  --once       tek is (varsa) calistirip cikar
  --sleep      bos kuyrukta bekleme (sn)
  --max-time   bu kadar saniye sonra temiz cikis (0 = sinirsiz)
  --max-jobs   bu kadar is sonra temiz cikis (0 = sinirsiz)
  --stale      bundan eski 'running' isler coken isci sayilir (sn)
  --memory     bellek bu MB'yi asinca temiz cikis


TXT
        );

        return 0;
    }

    $queue    = (isset($o['queue']) && $o['queue'] !== true) ? (string)$o['queue'] : 'default';
    $once     = isset($o['once']);
    $sleep    = max(1, (int)($o['sleep'] ?? 3));
    $maxTime  = max(0, (int)($o['max-time'] ?? 3600));
    $maxJobs  = max(0, (int)($o['max-jobs'] ?? 0));
    $stale    = max(60, (int)($o['stale'] ?? 3600));
    $memoryMb = max(32, (int)($o['memory'] ?? 256));

    if (!preg_match('/^[a-z0-9][a-z0-9_-]{0,49}$/', $queue)) {
        fwrite(STDERR, "\n  Gecersiz kuyruk adi: {$queue}\n\n");

        return 1;
    }

    vnl_cli_boot();

    $stop = false;

    if (function_exists('pcntl_async_signals') && function_exists('pcntl_signal')) {
        pcntl_async_signals(true);
        $handler = static function () use (&$stop): void {
            $stop = true;
        };
        pcntl_signal(SIGTERM, $handler);
        pcntl_signal(SIGINT, $handler);
    }

    $say = static function (string $line): void {
        fwrite(STDOUT, '[' . date('Y-m-d H:i:s') . '] ' . $line . "\n");
    };

    $started   = time();
    $processed = 0;
    $lastStale = 0;

    $say("queue:work basladi (kuyruk: {$queue}, pid " . getmypid() . ')');

    while (!$stop) {
        /* Çöken işçinin bıraktığı işleri dakikada bir kurtar. */
        if (time() - $lastStale >= 60) {
            $released = Queue::releaseStale($queue, $stale);
            if ($released > 0) {
                $say("{$released} asili is kurtarildi");
            }
            $lastStale = time();
        }

        $job = Queue::reserve($queue);

        if ($job === null) {
            if ($once) {
                break;
            }

            sleep($sleep);
        } else {
            $say('#' . $job->id() . ' ' . $job->type() . ' basladi (deneme ' . $job->attempts() . '/' . $job->maxAttempts() . ')');

            vnl_queue_run_job($job, $say);
            $processed++;

            if ($once) {
                break;
            }
        }

        if ($maxTime > 0 && time() - $started >= $maxTime) {
            $say('max-time doldu, cikiliyor');
            break;
        }

        if ($maxJobs > 0 && $processed >= $maxJobs) {
            $say('max-jobs doldu, cikiliyor');
            break;
        }

        if (memory_get_usage(true) > $memoryMb * 1048576) {
            $say('bellek siniri asildi, cikiliyor');
            break;
        }
    }

    if ($stop) {
        $say('sinyal alindi, temiz cikis');
    }

    return 0;
};

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
 * systemd birimi — KillMode=mixed ŞART: varsayılan control-group, SIGTERM'i
 * işin alt süreçlerine de (useradd, certbot, rsync ...) aynı anda gönderir
 * ve onlar yarıda ölür; TimeoutStopSec en uzun işten uzun olmalı, yoksa
 * 90 sn sonra SIGKILL gelir:
 *
 *   # /etc/systemd/system/vnl-worker-system.service
 *   [Unit]
 *   Description=VNL kuyruk iscisi (system)
 *   After=network.target mysql.service
 *
 *   [Service]
 *   ExecStart=/usr/bin/php /opt/vnl-panel/vnl.php queue:work --queue=system
 *   Restart=always
 *   RestartSec=2
 *   KillMode=mixed
 *   KillSignal=SIGTERM
 *   TimeoutStopSec=3600
 *   # Ayrıcalıksız bir kuyruk için: User=www-data  Group=www-data
 *
 *   [Install]
 *   WantedBy=multi-user.target
 *
 * İşleyici: VNL_ROOT/jobs/<tür>.php → function (array $payload, VnlJob $job): void
 * İşleyicinin echo çıktısı da işin output'una eklenir. Dosya işçi süreci
 * başına BİR KEZ yüklenir (dosya düzeyindeki kod ve tanımlar bir kez
 * çalışır); kod değişikliği işçi yeniden başlayınca (--max-time) gelir.
 *
 * 3.4.0 (gözden geçirme):
 *  - Bütün sonuç yazmaları ayırma jetonuna bağlıdır (VnlJob::reservation):
 *    elinden alınan işin eski işçisi yeni sahibinin durumunu ezemez.
 *  - Asılı iş ölçüsü nabızdır (updated_at), başlangıç zamanı değil.
 *  - İşleyici die/exit çağırırsa ya da süreç ölümcül hatayla biterse (bellek
 *    dahil) kapanış işlevi eldeki işi yeniden kuyruğa ya da failed'a yazar;
 *    iş --stale boyunca 'running' kalmaz.
 */

/**
 * Şu an çalışan iş (kapanış işlevi için). $set=true ile yazılır.
 */
function vnl_queue_inflight(?VnlJob $job = null, bool $set = false): ?VnlJob
{
    static $current = null;

    if ($set) {
        $current = $job;
    }

    return $current;
}

/**
 * Başarısız çalıştırmayı yazar: deneme hakkı varsa geri çekilmeli olarak
 * yeniden kuyruğa, yoksa failed. Ayırmaya bağlıdır.
 *
 * @return bool|null  true yeniden denenecek, false son deneme, null ayırma
 *                    kaybedilmiş (hiçbir şey yazılmadı)
 */
function vnl_queue_record_failure(VnlJob $job, string $error): ?bool
{
    $retry = $job->attempts() < $job->maxAttempts();

    $affected = $job->reservation()->update([
        'status'       => $retry ? 'queued' : 'failed',
        'error'        => mb_substr($error, 0, 60000),
        'available_at' => $retry ? Queue::now(30 * max(1, $job->attempts())) : Queue::now(),
        'finished_at'  => $retry ? null : Queue::now(),
        'reserved_by'  => null,
        'reserved_at'  => null,
        'updated_at'   => Queue::now(),
    ]);

    return $affected > 0 ? $retry : null;
}

/**
 * Tek işi çalıştırır ve sonucunu yazar. İşleyici istisnası işçiyi
 * durdurmaz: iş yeniden kuyruğa ya da failed'a düşer, işçi sürer.
 */
function vnl_queue_run_job(VnlJob $job, callable $say): void
{
    global $CurrentUser;

    /** İşleyici kapanışları: dosya süreç başına bir kez yüklenir. */
    static $handlers = [];

    /* Her iş temiz bir kimlikle başlar; denetim kaydı işi yazan kişiye düşer.
       Yetki önbelleği de sıfırlanır: uzun yaşayan işçide web'den yapılan bir
       yetki geri alımı bir önceki işin cevabıyla gölgelenmesin. */
    $CurrentUser = new User();
    Audit::actingAs($job->createdBy());

    if (is_callable(['UserPermissions', 'clearCache'])) {
        UserPermissions::clearCache();
    }

    $path = Queue::handlerPath($job->type());

    vnl_queue_inflight($job, true);

    /* İşleyicinin echo çıktısı. Seviye tutulur: işleyici kendi tamponunu
       açık bırakmış olabilir, tampon da çoktan kapanmış olabilir. */
    $level   = ob_get_level();
    $collect = static function () use ($level): string {
        $out = '';
        while (ob_get_level() > $level) {
            $out = (string)ob_get_clean() . $out;
        }

        return $out;
    };

    ob_start();

    try {
        if ($path === null) {
            throw new RuntimeException('İşleyici bulunamadı: jobs/' . $job->type() . '.php');
        }

        /* Her işte yeniden require edilseydi dosya düzeyinde fonksiyon ya da
           sınıf tanımlayan bir işleyici ikinci çalıştırmada "Cannot redeclare"
           ile (yakalanamaz) işçiyi düşürürdü. require_once olmaz: ikinci
           çağrıda true döner. */
        $handler = $handlers[$path] ??= (require $path);

        if (!is_callable($handler)) {
            throw new RuntimeException('jobs/' . $job->type() . '.php bir fonksiyon döndürmüyor.');
        }

        $handler($job->payload(), $job);

        $echoed = $collect();
        if ($echoed !== '') {
            $job->log(rtrim($echoed));
        }

        /* 'error' önce: SET soldan sağa değerlendirilir, status henüz eski
           değerindeyken --stale'in yazdığı zaman aşımı notu temizlenir. */
        $done = $job->reservation()->update([
            'error'       => Database::raw("IF(`status` = 'failed', NULL, `error`)"),
            'status'      => 'done',
            'progress'    => 100,
            'finished_at' => Queue::now(),
            'reserved_by' => null,
            'updated_at'  => Queue::now(),
        ]);

        $say('#' . $job->id() . ($done > 0 ? ' tamamlandi' : ' tamamlandi ama ayirma kaybedilmis: sonuc yazilmadi'));
    } catch (VnlJobLostException $e) {
        $collect();
        $say('#' . $job->id() . ' birakildi — ' . $e->getMessage());
    } catch (Throwable $e) {
        $echoed = $collect();
        $error  = get_class($e) . ': ' . $e->getMessage();

        try {
            if ($echoed !== '') {
                $job->log(rtrim($echoed));
            }

            $retry = vnl_queue_record_failure($job, $error);
            $ref   = vnl_log_exception($e, 'VNL][Queue');

            $say('#' . $job->id() . match ($retry) {
                true    => ' basarisiz, yeniden denenecek',
                false   => ' basarisiz (son deneme)',
                default => ' basarisiz ama ayirma kaybedilmis: sonuc yazilmadi',
            } . " — {$error} (ref {$ref})");
        } catch (VnlJobLostException $lost) {
            $say('#' . $job->id() . ' basarisiz, ayirma kaybedilmis — ' . $error);
        } catch (Throwable $inner) {
            /* Veritabanı gitti: iş 'running' kalır, --stale sonra kurtarır. */
            $say('#' . $job->id() . ' sonucu yazilamadi: ' . $inner->getMessage());
        }
    } finally {
        vnl_queue_inflight(null, true);
        Audit::actingAs(null);
    }
}

/**
 * Kapanış güvenlik ağı: işleyici die/exit çağırdıysa (ör. web'e özgü
 * returnErrorMessage) ya da süreç ölümcül hatayla (bellek dahil) bittiyse
 * eldeki iş 'running' kalmasın. vnl_cli_boot()'tan ÖNCE kaydedilmelidir:
 * boot.php'nin ölümcül hata işleyicisi exit(1) çağırır ve ondan sonra
 * kaydedilen kapanış işlevleri hiç çalışmaz.
 */
function vnl_queue_shutdown(): void
{
    $job = vnl_queue_inflight();

    if ($job === null) {
        return;
    }

    vnl_queue_inflight(null, true);
    @ini_set('memory_limit', '-1');

    $echoed = '';
    while (ob_get_level() > 0) {
        $echoed = (string)ob_get_clean() . $echoed;
    }

    $last   = error_get_last();
    $fatal  = $last !== null && in_array($last['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR], true);
    $reason = $fatal
        ? $last['message'] . ' (' . basename((string)$last['file']) . ':' . $last['line'] . ')'
        : 'surec is bitmeden sonlandi (exit/die — isleyici exit, die ya da returnErrorMessage cagirmamali)';

    $err = defined('STDERR') ? STDERR : fopen('php://stderr', 'w');

    try {
        if ($echoed !== '') {
            try {
                $job->log(rtrim($echoed));
            } catch (Throwable $ignored) {
                /* çıktı yazılamadı; sonuç yine de denenir */
            }
        }

        $retry = vnl_queue_record_failure($job, 'İşçi süreci iş sırasında bitti: ' . $reason);

        fwrite($err, '[' . date('Y-m-d H:i:s') . '] #' . $job->id() . ' ' . match ($retry) {
            true    => 'yeniden kuyruga alindi',
            false   => 'failed',
            default => 'ayirma kaybedilmis, dokunulmadi',
        } . ' — ' . $reason . "\n");
    } catch (Throwable $e) {
        fwrite($err, '[' . date('Y-m-d H:i:s') . '] #' . $job->id() . ' sonucu yazilamadi: ' . $e->getMessage() . "\n");
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
  --stale      bu kadar sn nabiz atmayan 'running' is coken isci sayilir
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

    /* Önce kapanış ağı, sonra önyükleme (sıra önemli — bkz. vnl_queue_shutdown). */
    register_shutdown_function('vnl_queue_shutdown');

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

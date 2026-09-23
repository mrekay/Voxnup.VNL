<?php
/*!
 * ============================================================
 *  Voxnup Not Laravel (VNL) — Kuyruk İşi
 *  Çalışan işin işleyiciye verilen tanıtıcısı.
 *  Authors: Voxnup Authors
 * ============================================================
 */

/**
 * İşleyicinin ikinci argümanı:
 *
 *   return function (array $payload, VnlJob $job): void {
 *       $job->progress(50, 'Yapılandırma yazıldı');
 *       $job->log('nginx -t: ok');
 *
 *       $r = $job->exec(['nginx', '-t']);        // kabuksuz, beklerken nabız atar
 *       if ($r['code'] !== 0) {
 *           throw new RuntimeException('nginx -t: ' . $r['stderr']);
 *       }
 *   };
 *
 * progress() ve log() her çağrıda veritabanına yazar — ilerleme ekranı
 * (Queue::status) işi çalışırken izleyebilir. Çıktı MEDIUMTEXT'tir;
 * log() satırları sona eklenir.
 *
 * Nabız (3.4.0): progress(), log(), heartbeat() ve exec()'in bekleme
 * döngüsü satırın updated_at'ini yeniler. queue:work bir işi --stale
 * saniye boyunca nabız atmadıysa çökmüş sayar; uzun ve SESSİZ bir adım
 * (büyük bir mysqldump, rsync) exec() ile çalıştırılmalı ya da arada
 * heartbeat() çağırmalıdır.
 *
 * Ayırma çiti: bütün yazmalar işin ayırma jetonuna (reserved_by) bağlıdır.
 * İş bu işçinin elinden alınmışsa (başka işçi, yeniden kuyruk, yönetici
 * yeniden denemesi) yazma yapılmaz ve VnlJobLostException fırlatılır —
 * işleyici yan etkilerini sürdürmesin diye. Bu istisnayı yutmayın.
 */
class VnlJob
{
    /** exec() bekleme döngüsünde nabız aralığı (sn). */
    private const HEARTBEAT_EVERY = 30;

    /** exec() çıktı sınırı (bayt, akış başına; sonu tutulur). */
    private const EXEC_OUTPUT_LIMIT = 1048576;

    private array $row;

    public function __construct(array $row)
    {
        $this->row = $row;
    }

    public function id(): int
    {
        return (int)$this->row['id'];
    }

    public function type(): string
    {
        return (string)$this->row['type'];
    }

    public function queue(): string
    {
        return (string)$this->row['queue'];
    }

    public function payload(): array
    {
        $payload = json_decode((string)($this->row['payload'] ?? ''), true);

        return is_array($payload) ? $payload : [];
    }

    /** Bu çalıştırma dahil kaçıncı deneme. */
    public function attempts(): int
    {
        return (int)$this->row['attempts'];
    }

    public function maxAttempts(): int
    {
        return (int)$this->row['max_attempts'];
    }

    /** İşi kuyruğa yazan kullanıcı (web'den geldiyse). */
    public function createdBy(): ?int
    {
        return isset($this->row['created_by']) ? (int)$this->row['created_by'] : null;
    }

    /** Bu çalıştırmanın ayırma jetonu (Queue::reserve). */
    public function reservationToken(): ?string
    {
        $token = $this->row['reserved_by'] ?? null;

        return ($token === null || $token === '') ? null : (string)$token;
    }

    /**
     * Bu ayırmaya bağlı sorgu kurucusu: yalnız satır hâlâ BU jetonla
     * ayrılmışsa eşleşir. queue:work sonucu bununla yazar; 0 satır
     * etkilenirse ayırma kaybedilmiştir.
     *
     * 'failed' da kabul edilir: --stale işi deneme hakkı kalmadığı için
     * failed'a çektiğinde jetonu satırda bırakır; iş aslında sürüyorsa
     * geç gelen sonucu yine yazılabilsin (yeniden kuyruğa konan ya da
     * yeniden denenen işte jeton silinir, eski işçi hiçbir şey yazamaz).
     */
    public function reservation()
    {
        return Database::table(Queue::TABLE)
            ->where('id', $this->id())
            ->where('reserved_by', (string)$this->reservationToken())
            ->whereIn('status', ['running', 'failed']);
    }

    /** Satır hâlâ bu işçide mi? */
    public function owned(): bool
    {
        return $this->reservationToken() !== null && $this->reservation()->exists();
    }

    /** İlerleme yüzdesi (0–100); not verilirse çıktıya da bir satır düşer. */
    public function progress(int $percent, string $note = ''): void
    {
        $percent = max(0, min(100, $percent));

        $this->write(fn() => $this->reservation()->update([
            'progress'   => $percent,
            'updated_at' => Queue::now(),
        ]));

        if ($note !== '') {
            $this->log('[' . $percent . '%] ' . $note);
        }
    }

    /** Çıktıya bir satır ekler (zaman damgalı). */
    public function log(string $line): void
    {
        $text  = '[' . date('H:i:s') . '] ' . rtrim($line, "\r\n") . "\n";
        $table = Queue::rawTable();

        /* Çit reservation() ile aynı: id + jeton + running|failed. */
        $this->write(fn() => Database::connection()->update(
            "UPDATE `{$table}` SET `output` = CONCAT(COALESCE(`output`, ''), ?), `updated_at` = NOW()
              WHERE `id` = ? AND `reserved_by` = ? AND `status` IN ('running', 'failed')",
            [$text, $this->id(), (string)$this->reservationToken()]
        ));
    }

    /**
     * Nabız: iş canlı, ilerleme ya da çıktı olmasa da. Uzun ve sessiz bir
     * adımın döngüsünde --stale'den sık çağırın (exec() kendisi çağırır).
     */
    public function heartbeat(): void
    {
        $this->write(fn() => $this->reservation()->update(['updated_at' => Queue::now()]));
    }

    /**
     * Dış komutu KABUKSUZ çalıştırır (argüman dizisi) ve beklerken nabız atar.
     *
     *   $r = $job->exec(['useradd', '--create-home', $user]);
     *   $r = $job->exec(['mysqldump', $db], ['timeout' => 3600, 'log' => true]);
     *   // ['code' => 0, 'stdout' => '...', 'stderr' => '', 'timed_out' => false]
     *
     * $opts:
     *   cwd      çalışma dizini
     *   env      ortam değişkenleri (dizi; verilmezse işçininki)
     *   timeout  saniye (0 = sınırsız); aşılınca SIGTERM, 5 sn sonra SIGKILL
     *   log      true: çıktının sonunu (64 KB) işin çıktısına da yazar
     *
     * Çıkış kodu sinyalle bitişte 128 + sinyal numarasıdır. stdout/stderr
     * akış başına en fazla 1 MB tutulur (sonu). Linux içindir; Windows'ta
     * boru okumaları engelleyici olduğundan nabız süreç bitince atılır.
     *
     * @return array{code:int, stdout:string, stderr:string, timed_out:bool}
     */
    public function exec(array $argv, array $opts = []): array
    {
        $argv = array_map('strval', array_values($argv));

        if ($argv === [] || $argv[0] === '') {
            throw new InvalidArgumentException('VnlJob::exec: komut boş.');
        }

        $pipes = [];
        $proc  = proc_open($argv, [
            0 => ['file', DIRECTORY_SEPARATOR === '\\' ? 'NUL' : '/dev/null', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ], $pipes, $opts['cwd'] ?? null, $opts['env'] ?? null);

        if (!is_resource($proc)) {
            throw new RuntimeException('Süreç başlatılamadı: ' . $argv[0]);
        }

        stream_set_blocking($pipes[1], false);
        stream_set_blocking($pipes[2], false);

        $timeout  = max(0, (int)($opts['timeout'] ?? 0));
        $started  = time();
        $lastBeat = time();
        $timedOut = false;
        $buffers  = [1 => '', 2 => ''];
        $open     = [1 => $pipes[1], 2 => $pipes[2]];
        $code     = null;

        $beat = function () use (&$lastBeat): void {
            if (time() - $lastBeat >= self::HEARTBEAT_EVERY) {
                $this->heartbeat();
                $lastBeat = time();
            }
        };

        /* 1) Borular kapanana kadar oku. */
        while ($open !== []) {
            $read   = array_values($open);
            $write  = null;
            $except = null;

            if (@stream_select($read, $write, $except, 1) === false) {
                usleep(200000);   // sinyal kesintisi: yeniden dene
            }

            foreach ($open as $i => $pipe) {
                $chunk = fread($pipe, 65536);

                if (is_string($chunk) && $chunk !== '') {
                    $buffers[$i] .= $chunk;

                    if (strlen($buffers[$i]) > self::EXEC_OUTPUT_LIMIT * 2) {
                        $buffers[$i] = substr($buffers[$i], -self::EXEC_OUTPUT_LIMIT);
                    }
                }

                if (feof($pipe)) {
                    fclose($pipe);
                    unset($open[$i]);
                }
            }

            if ($timeout > 0 && time() - $started >= $timeout) {
                break;   // sonlandırma aşağıda: süreç hâlâ yaşıyorsa
            }

            $beat();
        }

        foreach ($open as $pipe) {
            fclose($pipe);
        }

        /* 2) Sürecin bitmesini bekle (zaman aşımında önce TERM, sonra KILL). */
        $termAt = null;

        while (true) {
            $status = proc_get_status($proc);

            if (!$status['running']) {
                /* Çıkış kodu yalnız "bitti"yi bildiren İLK çağrıda geçerlidir. */
                $code = !empty($status['signaled'])
                    ? 128 + (int)$status['termsig']
                    : (int)$status['exitcode'];
                break;
            }

            if ($timeout > 0 && $termAt === null && time() - $started >= $timeout) {
                $timedOut = true;
                proc_terminate($proc, 15);
                $termAt = time();
            }

            if ($termAt !== null && time() - $termAt >= 5) {
                proc_terminate($proc, 9);
            }

            usleep(200000);
            $beat();
        }

        proc_close($proc);

        $result = [
            'code'      => $code ?? -1,
            'stdout'    => substr($buffers[1], -self::EXEC_OUTPUT_LIMIT),
            'stderr'    => substr($buffers[2], -self::EXEC_OUTPUT_LIMIT),
            'timed_out' => $timedOut,
        ];

        if (!empty($opts['log'])) {
            $tail = trim($result['stdout'] . ($result['stderr'] !== '' ? "\n" . $result['stderr'] : ''));
            $this->log('$ ' . implode(' ', $argv) . ' → ' . $result['code']
                . ($timedOut ? ' (zaman aşımı)' : '')
                . ($tail !== '' ? "\n" . substr($tail, -65536) : ''));
        }

        return $result;
    }

    /** Satırın ham hali (tanılama için). */
    public function toArray(): array
    {
        return $this->row;
    }

    /* ---------------------------------------------------------------- */

    /**
     * Çitli yazma. MySQL "değişen" satırı sayar: aynı saniyede aynı değer
     * yazılırsa 0 döner — o yüzden 0'da sahiplik ayrıca sorulur.
     */
    private function write(callable $update): void
    {
        if ((int)$update() > 0) {
            return;
        }

        if (!$this->owned()) {
            throw new VnlJobLostException('#' . $this->id() . ': ayırma kaybedildi (iş başka bir işçide, yeniden kuyrukta ya da yeniden denendi).');
        }
    }
}

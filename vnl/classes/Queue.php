<?php
/*!
 * ============================================================
 *  Voxnup Not Laravel (VNL) — İş Kuyruğu
 *  Veritabanı tabanlı kuyruk: tbl_vnl_jobs.
 *  Authors: Voxnup Authors
 * ============================================================
 */

/**
 * İş kuyruğu.
 *
 * Web isteği uzun ya da ayrıcalık gerektiren işi KENDİSİ yapmaz, kuyruğa
 * yazar; ayrı bir süreç (php vnl.php queue:work) onu alıp çalıştırır.
 *
 *   $id = Queue::push('domain.provision', ['domain_id' => 7]);
 *   $id = Queue::push('backup.nightly', [], ['queue' => 'system', 'delay' => 60, 'max_attempts' => 3]);
 *
 *   Queue::status($id);   // ['status' => 'running', 'progress' => 40, 'output' => '...', 'error' => null]
 *   Queue::cancel($id);   // yalnız henüz başlamamış (queued) iş
 *
 * İşleyici: VNL_ROOT/jobs/<tür>.php — tür [a-z0-9._-]+ ve dosya bir kapanış döner:
 *
 *   // jobs/domain.provision.php
 *   return function (array $payload, VnlJob $job): void {
 *       $job->progress(10, 'Kullanıcı oluşturuluyor');
 *       $job->log('useradd tamam');
 *       // ... istisna fırlatmak = başarısızlık (max_attempts'e kadar yeniden denenir)
 *   };
 *
 * Durumlar: queued → running → done | failed | canceled.
 * Yeniden deneme: attempts < max_attempts iken istisna, işi
 * NOW() + 30 sn × attempts sonrasına yeniden kuyruğa koyar. max_attempts'i
 * yalnız ikinci kez çalıştırılması güvenli (idempotent) işlerde artırın.
 *
 * Uzun ve sessiz adımlar: işçi --stale saniye nabız atmayan işi çökmüş
 * sayar; dış komutları $job->exec([...]) ile çalıştırın (beklerken nabız
 * atar) ya da döngüde $job->heartbeat() çağırın.
 *
 * Yük (payload) JSON olarak saklanır: yalnız dizi/sayı/metin koyun;
 * parola ya da gizli anahtar koymayın (tablo yönetim ekranında görünür).
 */
class Queue
{
    public const TABLE = 'tbl_vnl_jobs';

    public const STATUSES = ['queued', 'running', 'done', 'failed', 'canceled'];

    /** Tür adı: dosya adına dönüştüğü için dar tutulur. */
    public const TYPE_PATTERN = '/^[a-z0-9][a-z0-9._-]{0,99}$/';

    /** Kuyruk adı. */
    public const QUEUE_PATTERN = '/^[a-z0-9][a-z0-9_-]{0,49}$/';

    /**
     * İş ekler; iş kimliğini döner.
     *
     * $opts: queue (varsayılan 'default'), delay (sn), max_attempts (1–255),
     *        created_by (varsayılan: oturumdaki kullanıcı).
     */
    public static function push(string $type, array $payload = [], array $opts = []): int
    {
        if (!preg_match(self::TYPE_PATTERN, $type)) {
            throw new InvalidArgumentException('Geçersiz iş türü: ' . $type . ' ([a-z0-9._-]+ bekleniyor)');
        }

        $queue = (string)($opts['queue'] ?? 'default');
        if (!preg_match(self::QUEUE_PATTERN, $queue)) {
            throw new InvalidArgumentException('Geçersiz kuyruk adı: ' . $queue);
        }

        $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            throw new InvalidArgumentException('İş yükü JSON\'a çevrilemedi: ' . json_last_error_msg());
        }

        $delay       = max(0, (int)($opts['delay'] ?? 0));
        $maxAttempts = min(255, max(1, (int)($opts['max_attempts'] ?? 1)));
        $createdBy   = $opts['created_by'] ?? null;

        if ($createdBy === null && function_exists('vnl_user')) {
            $user      = vnl_user();
            $createdBy = $user !== null ? (int)$user->id : null;
        }

        /* Tablodaki bütün zamanlar veritabanının NOW()'ıdır: işçi
           available_at'i NOW() ile karşılaştırır; PHP ile MySQL saat dilimi
           farklıysa PHP date()'i gecikmeyi saatlerce kaydırırdı. */
        return (int)Database::table(self::TABLE)->insertGetId([
            'queue'        => $queue,
            'type'         => $type,
            'payload'      => $json,
            'status'       => 'queued',
            'attempts'     => 0,
            'max_attempts' => $maxAttempts,
            'progress'     => 0,
            'available_at' => self::now($delay),
            'created_by'   => $createdBy !== null ? (int)$createdBy : null,
            'created_at'   => self::now(),
            'updated_at'   => self::now(),
        ]);
    }

    /** İşin tüm satırı (payload çözülmüş) ya da null. */
    public static function find(int $id): ?array
    {
        $row = Database::table(self::TABLE)->where('id', $id)->first();

        if (!$row) {
            return null;
        }

        $job = (array)$row;
        $job['payload'] = json_decode((string)$job['payload'], true) ?? [];

        return $job;
    }

    /**
     * İlerleme ekranı için özet. İş yoksa status 'missing' döner.
     *
     * @return array{status:string, progress:int, output:?string, error:?string}
     */
    public static function status(int $id): array
    {
        $row = Database::table(self::TABLE)
            ->where('id', $id)
            ->first(['status', 'progress', 'output', 'error']);

        if (!$row) {
            return ['status' => 'missing', 'progress' => 0, 'output' => null, 'error' => null];
        }

        return [
            'status'   => (string)$row->status,
            'progress' => (int)$row->progress,
            'output'   => $row->output,
            'error'    => $row->error,
        ];
    }

    /** Henüz başlamamış işi iptal eder. Çalışan/bitmiş işe dokunmaz. */
    public static function cancel(int $id): bool
    {
        return Database::table(self::TABLE)
            ->where('id', $id)
            ->where('status', 'queued')
            ->update([
                'status'      => 'canceled',
                'finished_at' => self::now(),
                'updated_at'  => self::now(),
            ]) > 0;
    }

    /**
     * Başarısız ya da iptal edilmiş işi yeniden kuyruğa koyar. Ayırma jetonu
     * silinir: --stale ile failed'a çekilmiş ama aslında süren eski
     * çalıştırma artık satıra yazamaz.
     */
    public static function retry(int $id): bool
    {
        return Database::table(self::TABLE)
            ->where('id', $id)
            ->whereIn('status', ['failed', 'canceled'])
            ->update([
                'status'       => 'queued',
                'attempts'     => 0,
                'progress'     => 0,
                'error'        => null,
                'reserved_by'  => null,
                'reserved_at'  => null,
                'finished_at'  => null,
                'available_at' => self::now(),
                'updated_at'   => self::now(),
            ]) > 0;
    }

    /** Yönetim ekranları için sorgu kurucusu. */
    public static function query()
    {
        return Database::table(self::TABLE);
    }

    /* ---------------------------------------------------------------- *
     *  İşçi tarafı (queue:work kullanır)
     * ---------------------------------------------------------------- */

    /**
     * Sıradaki uygun işi atomik olarak ayırır ve döner; iş yoksa null.
     *
     * Tek bir UPDATE satırı kilitler ve işaretler: aynı kuyruğu dinleyen
     * iki işçi aynı işi alamaz. Ayırma jetonu her denemede yenidir, satır
     * ardından o jetonla okunur. Lehçeye göre (4.1.0):
     *
     *   mysql   UPDATE … ORDER BY id LIMIT 1
     *   pgsql   UPDATE … WHERE id = (SELECT … FOR UPDATE SKIP LOCKED)
     *   sqlsrv  WITH … SELECT TOP (1) … WITH (READPAST) UPDATE
     *   sqlite  UPDATE … WHERE id = (SELECT … LIMIT 1) — yazma kilidi tektir
     *
     * SKIP LOCKED / READPAST: başka işçinin o an kilitlediği satır atlanır;
     * işçiler birbirini beklemez ve aynı satırı ikisi birden alamaz.
     */
    public static function reserve(string $queue): ?VnlJob
    {
        $token = bin2hex(random_bytes(16));
        $table = VnlSql::table(self::TABLE);
        $now   = VnlSql::now();

        $affected = Database::connection()->update(match (VnlSql::dialect()) {
            'pgsql' => "UPDATE {$table}
                SET status = 'running', reserved_by = ?, reserved_at = LOCALTIMESTAMP(0),
                    attempts = attempts + 1, updated_at = LOCALTIMESTAMP(0)
              WHERE id = (SELECT id FROM {$table}
                           WHERE queue = ? AND status = 'queued' AND available_at <= LOCALTIMESTAMP(0)
                           ORDER BY id
                           LIMIT 1
                           FOR UPDATE SKIP LOCKED)",

            'sqlsrv' => "WITH next_job AS (
                SELECT TOP (1) * FROM {$table} WITH (UPDLOCK, READPAST, ROWLOCK)
                 WHERE [queue] = ? AND [status] = N'queued' AND [available_at] <= {$now}
                 ORDER BY [id])
              UPDATE next_job
                 SET [status] = N'running', [reserved_by] = ?, [reserved_at] = {$now},
                     [attempts] = [attempts] + 1, [updated_at] = {$now}",

            'sqlite' => "UPDATE {$table}
                SET status = 'running', reserved_by = ?, reserved_at = datetime('now'),
                    attempts = attempts + 1, updated_at = datetime('now')
              WHERE id = (SELECT id FROM {$table}
                           WHERE queue = ? AND status = 'queued' AND available_at <= datetime('now')
                           ORDER BY id
                           LIMIT 1)",

            default => "UPDATE {$table}
                SET `status` = 'running', `reserved_by` = ?, `reserved_at` = NOW(),
                    `attempts` = `attempts` + 1, `updated_at` = NOW()
              WHERE `queue` = ? AND `status` = 'queued' AND `available_at` <= NOW()
              ORDER BY `id`
              LIMIT 1",
        }, VnlSql::dialect() === 'sqlsrv' ? [$queue, $token] : [$token, $queue]);

        if ($affected < 1) {
            return null;
        }

        $row = Database::table(self::TABLE)->where('reserved_by', $token)->where('status', 'running')->first();

        return $row ? new VnlJob((array)$row) : null;
    }

    /**
     * Çöken işçinin 'running' bıraktığı işleri kurtarır: $staleSeconds
     * boyunca NABIZ atmamış (updated_at — VnlJob::progress/log/heartbeat/
     * exec yeniler) işler deneme hakkı varsa yeniden kuyruğa, yoksa failed'a.
     *
     * 3.4.0 (gözden geçirme): ölçü eskiden reserved_at'ti, yani işin
     * BAŞLADIĞI an; ilerleme bildiren sağlıklı uzun bir iş (büyük yedek,
     * rsync) --stale dolunca ikinci bir işçiye veriliyordu.
     *
     * Yeniden kuyruğa konan işin jetonu silinir: eski işçi (hâlâ yaşıyorsa)
     * artık hiçbir şey yazamaz, bir sonraki progress()/log() çağrısında
     * VnlJobLostException alır. failed'a çekilen işte jeton KALIR: iş
     * aslında sürüyorsa geç gelen sonucu yazılabilir; Queue::retry()
     * jetonu siler.
     *
     * Yeniden kuyruk yalnız tekrar çalıştırılması güvenli işler içindir:
     * max_attempts'i (varsayılan 1) yalnız idempotent işlerde artırın.
     */
    public static function releaseStale(string $queue, int $staleSeconds): int
    {
        $table   = VnlSql::table(self::TABLE);
        $stale   = max(60, $staleSeconds);
        $timeout = 'İşçi yanıt vermedi (zaman aşımı).';

        /* 4.1.0: MySQL dışında. SET'teki her ifade satırın eski değerini
           görür (standart SQL); notun metni bağlanır (SQL Server'da N''
           olmadan Türkçe karakter kod sayfasına düşerdi). */
        if (VnlSql::dialect() !== 'mysql') {
            $w = static fn(string $c): string => VnlSql::wrap($c);
            [$attempts, $max, $error] = [$w('attempts'), $w('max_attempts'), $w('error')];
            $done    = "{$attempts} >= {$max}";
            $concat  = match (VnlSql::dialect()) {
                'sqlsrv' => "COALESCE({$error} + CHAR(10), N'') + CAST(? AS NVARCHAR(200))",
                'pgsql'  => "COALESCE({$error} || CHR(10), '') || CAST(? AS TEXT)",
                default  => "COALESCE({$error} || CHAR(10), '') || CAST(? AS TEXT)",
            };
            $now = VnlSql::now();

            return Database::connection()->update(
                "UPDATE {$table}
                    SET {$w('status')}      = CASE WHEN {$done} THEN 'failed' ELSE 'queued' END,
                        {$error}            = CASE WHEN {$done} THEN {$concat} ELSE {$error} END,
                        {$w('finished_at')} = CASE WHEN {$done} THEN {$now} ELSE NULL END,
                        {$w('reserved_by')} = CASE WHEN {$done} THEN {$w('reserved_by')} ELSE NULL END,
                        {$w('reserved_at')} = CASE WHEN {$done} THEN {$w('reserved_at')} ELSE NULL END,
                        {$w('updated_at')}  = {$now}
                  WHERE {$w('queue')} = ? AND {$w('status')} = 'running'
                    AND COALESCE({$w('updated_at')}, {$w('reserved_at')}) < " . VnlSql::nowMinus($stale),
                [$timeout, $queue]
            );
        }

        /* SET soldan sağa değerlendirilir; hiçbir ifade önce atanan bir
           sütuna dayanmaz (attempts/max_attempts değişmez). */
        return Database::connection()->update(
            "UPDATE {$table}
                SET `status`      = IF(`attempts` >= `max_attempts`, 'failed', 'queued'),
                    `error`       = IF(`attempts` >= `max_attempts`,
                                       CONCAT(COALESCE(CONCAT(`error`, CHAR(10 USING utf8mb4)), ''), 'İşçi yanıt vermedi (zaman aşımı).'),
                                       `error`),
                    `finished_at` = IF(`attempts` >= `max_attempts`, NOW(), NULL),
                    `reserved_by` = IF(`attempts` >= `max_attempts`, `reserved_by`, NULL),
                    `reserved_at` = IF(`attempts` >= `max_attempts`, `reserved_at`, NULL),
                    `updated_at`  = NOW()
              WHERE `queue` = ? AND `status` = 'running'
                AND COALESCE(`updated_at`, `reserved_at`) < DATE_SUB(NOW(), INTERVAL ? SECOND)",
            [$queue, $stale]
        );
    }

    /** İşleyici dosyasının yolu (tür doğrulanmış olmalı). */
    public static function handlerPath(string $type): ?string
    {
        if (!preg_match(self::TYPE_PATTERN, $type)) {
            return null;
        }

        $path = VNL_ROOT . '/jobs/' . $type . '.php';

        return is_file($path) ? $path : null;
    }

    /** Veritabanı saatiyle "şimdi (+ sn)" ifadesi (sürücüye göre, VnlSql). */
    public static function now(int $plusSeconds = 0)
    {
        return Database::raw($plusSeconds > 0 ? VnlSql::nowPlus($plusSeconds) : VnlSql::now());
    }

    /** Ham SQL için önekli tablo adı (tırnaksız; tırnaklısı VnlSql::table()). */
    public static function rawTable(): string
    {
        return Database::connection()->getTablePrefix() . self::TABLE;
    }
}

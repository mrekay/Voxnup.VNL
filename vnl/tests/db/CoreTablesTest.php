<?php

use Illuminate\Database\Capsule\Manager as Capsule;

/**
 * Çekirdek tabloları ve onları kullanan sınıflar her sürücüde (4.1.0):
 * mysql, pgsql, sqlsrv, sqlite. compose.test.yml'deki test-pgsql,
 * test-sqlsrv ve test-sqlite servisleri bu dosyayı o sürücülerde koşar.
 *
 * Eşzamanlılık testleri gerçek alt süreçler açar (tests/lib/db-worker.php):
 * hız sınırlayıcının sayacı ve kuyruğun iş ayırması, aynı anda gelen
 * isteklerde kaçırmamalı ve aynı işi iki işçiye vermemelidir.
 */
final class CoreTablesTest extends VnlTestCase
{
    private string $queue = '';

    public function setUp(): void
    {
        $this->requireDatabase();

        foreach (['tbl_users', 'tbl_remember_tokens', RateLimiter::TABLE, Audit::TABLE, Queue::TABLE, ApiToken::TABLE] as $table) {
            if (!Capsule::schema()->hasTable($table)) {
                $this->skip("{$table} yok: php vnl.php migrate --run");
            }
        }

        $this->queue = 'vnltest' . bin2hex(random_bytes(4));
    }

    public function tearDown(): void
    {
        try {
            Capsule::table(Queue::TABLE)->where('queue', $this->queue)->delete();
            Capsule::table(RateLimiter::TABLE)->delete();
        } catch (Throwable $e) {
        }
    }

    /* ------------------------------------------------------------ *
     *  Şema
     * ------------------------------------------------------------ */

    public function testCoreMigrationsAreAllApplied(): void
    {
        require_once VNL_PATH . '/tools/migrate.php';

        $migrate = new VnlMigrate(new ConfigDefinitions());

        $this->assertSame(VnlSql::dialect(), $migrate->dialect());
        $this->assertSame([], $migrate->pending('vnl'), 'bekleyen cekirdek migrationi var');
    }

    public function testReplayAndStampColumnsExist(): void
    {
        $this->assertTrue(Capsule::schema()->hasColumn('tbl_users', 'totp_last_step'), '0007');
        $this->assertTrue(Capsule::schema()->hasColumn('tbl_remember_tokens', 'auth_stamp'), '0008');
        $this->assertTrue(Capsule::schema()->hasColumn('tbl_permissions', 'updated_at'), '0002');
    }

    public function testUsersPermissionSeededOnce(): void
    {
        $this->assertSame(1, Capsule::table('tbl_permissions')->where('code', 'Users')->count());
        $this->assertSame('Kullanıcılar', (string)Capsule::table('tbl_permissions')->where('code', 'Users')->value('name'),
            'Unicode metin bozulmamali (SQL Server: N\'\')');
    }

    /* ------------------------------------------------------------ *
     *  RateLimiter
     * ------------------------------------------------------------ */

    public function testRateLimiterCountsAndBlocks(): void
    {
        $key = 'test:' . bin2hex(random_bytes(6));

        $this->assertTrue(RateLimiter::hit($key, 3, 60));
        $this->assertTrue(RateLimiter::hit($key, 3, 60));
        $this->assertTrue(RateLimiter::hit($key, 3, 60));
        $this->assertFalse(RateLimiter::hit($key, 3, 60), 'dorduncu deneme sinirin disinda');
        $this->assertSame(4, RateLimiter::attempts($key));
        $this->assertTrue(RateLimiter::tooMany($key, 3));

        $wait = RateLimiter::availableIn($key);
        $this->assertTrue($wait > 50 && $wait <= 61, "kalan sure 60 civari olmali: {$wait}");

        RateLimiter::decrement($key);
        $this->assertSame(3, RateLimiter::attempts($key));

        RateLimiter::clear($key);
        $this->assertSame(0, RateLimiter::attempts($key));
        $this->assertSame(0, RateLimiter::availableIn($key));
    }

    public function testRateLimiterDecrementStopsAtZero(): void
    {
        $key = 'test:' . bin2hex(random_bytes(6));
        RateLimiter::hit($key, 5, 60);
        RateLimiter::decrement($key);
        RateLimiter::decrement($key);

        $this->assertSame(0, RateLimiter::attempts($key));
    }

    public function testRateLimiterWindowResetsAfterExpiry(): void
    {
        $key  = 'test:' . bin2hex(random_bytes(6));
        $hash = hash('sha256', $key);

        RateLimiter::hit($key, 2, 60);
        RateLimiter::hit($key, 2, 60);
        RateLimiter::hit($key, 2, 60);

        /* Pencereyi geçmişe al: bir sonraki hit sayacı 1'den başlatmalı. */
        Capsule::table(RateLimiter::TABLE)->where('key_hash', $hash)
            ->update(['reset_at' => Capsule::raw(VnlSql::nowMinus(5))]);

        $this->assertSame(0, RateLimiter::attempts($key), 'dolmus pencere sayilmaz');
        $this->assertSame(0, RateLimiter::availableIn($key));
        $this->assertTrue(RateLimiter::hit($key, 2, 60));
        $this->assertSame(1, RateLimiter::attempts($key));
        $this->assertSame(1, Capsule::table(RateLimiter::TABLE)->where('key_hash', $hash)->count(), 'satir yeniden kullanilir');
    }

    public function testRateLimiterPurgeRemovesOldRows(): void
    {
        $key  = 'test:' . bin2hex(random_bytes(6));
        $hash = hash('sha256', $key);

        RateLimiter::hit($key, 2, 60);
        Capsule::table(RateLimiter::TABLE)->where('key_hash', $hash)
            ->update(['reset_at' => Capsule::raw(VnlSql::nowMinus(2 * 86400))]);

        $this->assertTrue(RateLimiter::purge() >= 1);
        $this->assertSame(0, Capsule::table(RateLimiter::TABLE)->where('key_hash', $hash)->count());
    }

    /**
     * 12 süreç aynı 100 anahtarı aynı anda sayar: her anahtar tam 12 olmalı ve
     * hiçbir istek hata almamalı. Her anahtarın ilk sayımı yarışır — SQL
     * Server'da HOLDLOCK'suz MERGE orada birincil anahtar ihlaliyle düşer.
     * Yarış olasılıksaldır: kırık bir upsert her koşuda yakalanmayabilir
     * (HOLDLOCK'suz MERGE elle 16 × 300 sayımda birkaç kez düştü).
     */
    public function testRateLimiterIsAtomicUnderConcurrency(): void
    {
        $key     = 'concurrency:' . bin2hex(random_bytes(6));
        $results = $this->spawn(12, ['hit', $key, '100']);

        $errors = array_sum(array_column($results, 'errors'));
        $this->assertSame(0, $errors, "eszamanli hit hatasi ({$errors}): " . (string)($results[0]['first'] ?? ''));

        $counts = [];
        for ($i = 0; $i < 100; $i++) {
            $counts[] = RateLimiter::attempts($key . ':' . $i);
        }
        $this->assertSame(array_fill(0, 100, 12), $counts, 'kaybolan sayim var');
    }

    /* ------------------------------------------------------------ *
     *  Queue / VnlJob
     * ------------------------------------------------------------ */

    public function testQueueLifecycle(): void
    {
        $id = Queue::push('test.noop', ['ad' => 'Çağrı', 'n' => 3], ['queue' => $this->queue]);
        $this->assertTrue($id > 0);

        /* MySQL'in JSON türü anahtarları yeniden sıralar; içerik aynı olmalı. */
        $payload = Queue::find($id)['payload'];
        ksort($payload);
        $this->assertSame(['ad' => 'Çağrı', 'n' => 3], $payload, 'yuk JSON olarak aynen donmeli');
        $this->assertSame('queued', Queue::status($id)['status']);

        $reserved = Queue::reserve($this->queue);
        $this->assertNotNull($reserved, 'hazir is ayrilmali');
        $this->assertSame($id, $reserved->id());
        $this->assertSame(1, $reserved->attempts());
        $this->assertNull(Queue::reserve($this->queue), 'ayni is ikinci kez verilmez');

        $reserved->progress(40, 'yarı yolda');
        $reserved->log('ikinci satır');
        $reserved->heartbeat();

        $status = Queue::status($id);
        $this->assertSame('running', $status['status']);
        $this->assertSame(40, $status['progress']);
        $this->assertContains('[40%] yarı yolda', (string)$status['output']);
        $this->assertContains('ikinci satır', (string)$status['output']);
        $this->assertTrue($reserved->owned());
    }

    /**
     * Eklenen iş HEMEN alınabilmeli, saniyenin hangi anında eklenirse
     * eklensin. SQL Server'da yazılan zaman saniyeye yukarı yuvarlanıp
     * yuvarlanmamış GETDATE() ile karşılaştırıldığında iş ~yarım saniye
     * "gelecekte" kalıyordu (VnlSql::now()).
     */
    public function testJobIsReservableRightAfterPush(): void
    {
        for ($i = 0; $i < 12; $i++) {
            usleep(90000);
            $id  = Queue::push('test.noop', [], ['queue' => $this->queue]);
            $job = Queue::reserve($this->queue);

            $this->assertNotNull($job, "deneme {$i}: az once eklenen is alinamadi");
            $this->assertSame($id, $job->id());
        }
    }

    public function testDelayedJobIsNotReservedEarly(): void
    {
        Queue::push('test.noop', [], ['queue' => $this->queue, 'delay' => 3600]);

        $this->assertNull(Queue::reserve($this->queue));
    }

    public function testCancelAndRetry(): void
    {
        $id = Queue::push('test.noop', [], ['queue' => $this->queue]);

        $this->assertTrue(Queue::cancel($id));
        $this->assertSame('canceled', Queue::status($id)['status']);
        $this->assertTrue(Queue::retry($id));
        $this->assertSame('queued', Queue::status($id)['status']);
        $this->assertNotNull(Queue::reserve($this->queue));
    }

    public function testReleaseStaleRequeuesOrFails(): void
    {
        $retryId = Queue::push('test.noop', [], ['queue' => $this->queue, 'max_attempts' => 2]);
        $failId  = Queue::push('test.noop', [], ['queue' => $this->queue, 'max_attempts' => 1]);

        $this->assertNotNull(Queue::reserve($this->queue));
        $this->assertNotNull(Queue::reserve($this->queue));

        Capsule::table(Queue::TABLE)->where('queue', $this->queue)
            ->update(['updated_at' => Capsule::raw(VnlSql::nowMinus(3600))]);

        $this->assertSame(2, Queue::releaseStale($this->queue, 60));

        $retry = Queue::find($retryId);
        $this->assertSame('queued', $retry['status'], 'deneme hakki olan yeniden kuyruga');
        $this->assertNull($retry['reserved_by'], 'jeton silinir');

        $fail = Queue::find($failId);
        $this->assertSame('failed', $fail['status']);
        $this->assertNotNull($fail['reserved_by'], 'failed isinde jeton kalir');
        $this->assertNotNull($fail['finished_at']);
        $this->assertSame('İşçi yanıt vermedi (zaman aşımı).', (string)$fail['error'],
            'Turkce not bozulmadan yazilmali');
    }

    public function testRecordFailureAndDoneUpdateClearError(): void
    {
        require_once VNL_PATH . '/tools/queue-work.php';

        $id  = Queue::push('test.noop', [], ['queue' => $this->queue, 'max_attempts' => 2]);
        $job = Queue::reserve($this->queue);

        $this->assertTrue(vnl_queue_record_failure($job, 'ilk hata'), 'hakki var: yeniden denenecek');
        $row = Queue::find($id);
        $this->assertSame('queued', $row['status']);
        $this->assertSame('ilk hata', (string)$row['error']);

        /* queue:work'ün başarı yazımındaki CASE: failed değilse hata kalır. */
        Capsule::table(Queue::TABLE)->where('id', $id)->update([
            'error' => Capsule::raw('CASE WHEN ' . VnlSql::wrap('status') . " = 'failed' THEN NULL ELSE "
                . VnlSql::wrap('error') . ' END'),
            'status' => 'done',
        ]);
        $this->assertSame('ilk hata', (string)Queue::find($id)['error']);
    }

    /**
     * 150 iş, 8 işçi aynı anda: her iş tam bir kez ayrılmalı (MySQL'de
     * UPDATE … LIMIT 1, PostgreSQL'de SKIP LOCKED, SQL Server'da READPAST).
     */
    public function testReserveNeverHandsTheSameJobTwice(): void
    {
        $ids = [];
        for ($i = 0; $i < 150; $i++) {
            $ids[] = Queue::push('test.noop', ['i' => $i], ['queue' => $this->queue]);
        }

        $results  = $this->spawn(8, ['reserve', $this->queue]);
        $reserved = array_merge(...array_map(static fn($r) => $r['ids'] ?? [], $results));

        $this->assertSame(0, array_sum(array_column($results, 'errors')),
            'eszamanli reserve hatasi: ' . (string)($results[0]['first'] ?? ''));
        $this->assertSame(count($reserved), count(array_unique($reserved)), 'ayni is iki isciye verildi');

        sort($reserved);
        $this->assertSame($ids, $reserved, 'her is bir kez ayrilmali');
    }

    /* ------------------------------------------------------------ *
     *  Audit / ApiToken
     * ------------------------------------------------------------ */

    public function testAuditLogWritesJsonAndScrubs(): void
    {
        $action = 'test.audit_' . bin2hex(random_bytes(3));

        Audit::log($action, 'kayıt', 42, ['şehir' => 'İzmir', 'password' => 'gizli']);

        $row = Audit::query()->where('action', $action)->first();
        $this->assertNotNull($row, 'denetim kaydi yazilmali');
        $this->assertSame('kayıt', (string)$row->subject_type);
        $this->assertSame('42', (string)$row->subject_id);
        $data = json_decode((string)$row->data, true);
        ksort($data);
        $this->assertSame(['password' => '[redacted]', 'şehir' => 'İzmir'], $data);

        Audit::query()->where('action', $action)->delete();
    }

    public function testApiTokenRoundTrip(): void
    {
        $plain = ApiToken::issue(987654, 'test jetonu', ['jobs:*'], 1);

        try {
            $token = ApiToken::authenticate($plain);
            $this->assertNotNull($token);
            $this->assertSame(['jobs:*'], $token->abilities);
            $this->assertTrue(ApiToken::allows($token, 'jobs', 'status'));
            $this->assertFalse(ApiToken::allows($token, 'users', 'getList'));

            $this->assertTrue(ApiToken::revoke((int)$token->id));
            $this->assertNull(ApiToken::authenticate($plain), 'iptal edilen jeton gecmez');
        } finally {
            Capsule::table(ApiToken::TABLE)->where('user_id', 987654)->delete();
        }
    }

    /* ------------------------------------------------------------ */

    /**
     * $n alt süreç açar, hepsini aynı anda başlatır, JSON çıktılarını döner.
     */
    private function spawn(int $n, array $args): array
    {
        $start = sprintf('%.3f', microtime(true) + 2.0);
        $procs = [];

        for ($i = 0; $i < $n; $i++) {
            $proc = proc_open(
                array_merge([PHP_BINARY, VNL_PATH . '/tests/lib/db-worker.php'], $args, [$start]),
                [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
                $pipes,
                VNL_ROOT
            );

            if (!is_resource($proc)) {
                $this->fail('alt surec acilamadi');
            }

            fclose($pipes[0]);
            $procs[] = [$proc, $pipes];
        }

        $out = [];
        foreach ($procs as [$proc, $pipes]) {
            $stdout = (string)stream_get_contents($pipes[1]);
            $stderr = (string)stream_get_contents($pipes[2]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            $code = proc_close($proc);

            $lines = array_values(array_filter(array_map('trim', explode("\n", $stdout))));
            $json  = json_decode((string)end($lines), true);

            if ($code !== 0 || !is_array($json)) {
                $this->fail("alt surec dustu (cikis {$code}):\n" . substr($stdout . $stderr, 0, 1500));
            }

            $out[] = $json;
        }

        return $out;
    }
}

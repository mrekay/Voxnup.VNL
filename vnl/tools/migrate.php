<?php
/*!
 * ============================================================
 *  Voxnup Not Laravel (VNL) — Migration Çalıştırıcısı
 *  Bekleyen şema değişikliklerini sırayla uygular ve kaydeder.
 *  Authors: Voxnup Authors
 * ============================================================
 *
 *   php vnl.php migrate --status
 *   php vnl.php migrate --run
 *   php vnl.php migrate --run --dry-run
 *
 * İki ayrı sayaç yürür: çekirdeğinkiler vnl/migrations/, projeninkiler
 * migrations/. Numaraları birbirinden bağımsızdır — bir VNL yükseltmesi
 * sizin sıranızla çakışmaz. Çekirdek önce uygulanır, çünkü projenin
 * migration'ları çekirdeğin getirdiği tablolara dayanabilir.
 *
 * Uygulamayı ayağa kaldırmaz: oturum, kullanıcı ve yönlendirme makinesine
 * hiç dokunmadan yalnız .env + config/definitions.php okunur ve doğrudan
 * PDO açılır. Şemayı düzeltmeniz gereken an, çoğu zaman uygulamanın
 * açılmadığı andır.
 */

require_once VNL_PATH . '/env.php';
require_once VNL_ROOT . '/config/definitions.php';

class VnlMigrate
{
    private const TABLE = 'tbl_vnl_migrations';

    /** Dizin => kayıtlardaki kaynak adı. Sıra uygulama sırasıdır. */
    private const SOURCES = [
        'vnl' => 'vnl/migrations',
        'app' => 'migrations',
    ];

    private PDO $pdo;
    private string $table;
    private string $database;

    /** Son appliedRecords() sonucu (çakışma mesajı için). */
    private ?array $appliedCache = null;

    public function __construct(ConfigDefinitions $config)
    {
        $db = $config->getDBInfo();

        if (($db['driver'] ?? 'mysql') !== 'mysql') {
            $this->fail(
                "Bu arac su an yalnizca MySQL destekliyor (DB_DRIVER={$db['driver']}).\n" .
                "Migration dosyalari da MySQL soz dizimiyle yazilmis durumda."
            );
        }

        if (($db['database'] ?? '') === '') {
            $this->fail("DB_DATABASE bos. .env dosyanizi doldurun.");
        }

        /* 3.4.0: DB_PORT / DB_SOCKET (config/definitions.php'deki port ve
           unix_socket). Eski "sunucu:port" yazımı geri düşüş olarak durur. */
        $host   = (string)$db['host'];
        $port   = isset($db['port']) && (int)$db['port'] > 0 ? (int)$db['port'] : null;
        $socket = (string)($db['unix_socket'] ?? '');

        if (substr_count($host, ':') === 1) {
            [$host, $hostPort] = explode(':', $host, 2);
            if ((int)$hostPort > 0) {
                $port = (int)$hostPort;
            }
        }

        $dsn = ($socket !== ''
                ? 'mysql:unix_socket=' . $socket
                : 'mysql:host=' . $host . ($port !== null ? ';port=' . $port : ''))
            . ';dbname=' . $db['database']
            . ';charset=' . ($db['charset'] ?: 'utf8mb4');

        try {
            $this->pdo = new PDO($dsn, (string)$db['username'], (string)$db['password'], [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            $this->fail("Veritabanina baglanilamadi: " . $e->getMessage());
        }

        $this->database = (string)$db['database'];
        $this->table    = ($db['prefix'] ?? '') . self::TABLE;
    }

    /* ---------------------------------------------------------------- */
    /* Komutlar                                                          */
    /* ---------------------------------------------------------------- */

    public function status(): int
    {
        $applied = $this->appliedRecords();
        $pending = 0;
        $done    = 0;
        $issues  = 0;

        $this->line();
        $this->line("  VNL migration durumu  —  veritabani: {$this->database}");

        foreach (self::SOURCES as $source => $dir) {
            $files = $this->discover($source);

            $this->line();
            $this->line('  ' . ($source === 'vnl' ? 'CEKIRDEK' : 'PROJE') . "  {$dir}/");

            if ($files === []) {
                $this->line('    (dosya yok)');
                continue;
            }

            foreach ($files as $m) {
                $key = $source . ':' . $m['number'];

                if (!isset($applied[$key])) {
                    $pending++;
                    $this->line(sprintf('    [ ] %s  %-34s bekliyor', $m['number'], $m['name']));
                    continue;
                }

                $done++;
                $row  = $applied[$key];
                $same = hash_equals($row['checksum'], $m['checksum']);
                if (!$same) {
                    $issues++;
                }

                $this->line(sprintf(
                    '    [%s] %s  %-34s %s',
                    $same ? '+' : '!',
                    $m['number'],
                    $m['name'],
                    $same ? 'uygulandi  ' . $row['applied_at'] : 'UYGULANDI AMA DOSYA DEGISMIS'
                ));
            }
        }

        /* Kayıtta olup dosyası bulunmayanlar: farklı bir sürümden gelmiş olabilir. */
        $orphans = $this->orphans($applied);
        if ($orphans !== []) {
            $this->line();
            $this->line('  KAYITTA VAR, DOSYASI YOK');
            foreach ($orphans as $row) {
                $this->line(sprintf('    [?] %s  %-34s %s', $row['number'], $row['name'], $row['source']));
            }
        }

        $this->line();
        $this->line("  {$done} uygulandi, {$pending} bekliyor"
            . ($issues ? ", {$issues} dosya uygulandiktan sonra degismis" : ''));

        if ($issues) {
            $this->line();
            $this->line('  Yayimlanmis bir migration duzenlenmez. Ozet uyusmazligi, bu kurulumla');
            $this->line('  digerlerinin ayni semaya sahip olmayabilecegi anlamina gelir. Duzeltmeyi');
            $this->line('  yeni numarali bir migration ile yapin.');
        }

        $this->line();

        return 0;
    }

    public function run(bool $dryRun): int
    {
        $applied = $this->appliedRecords();
        $ran     = 0;

        $this->line();
        $this->line($dryRun
            ? "  KURU CALISMA — hicbir sey uygulanmayacak  (veritabani: {$this->database})"
            : "  Migration uygulaniyor  —  veritabani: {$this->database}");
        $this->line();

        foreach (self::SOURCES as $source => $dir) {
            foreach ($this->discover($source) as $m) {
                if (isset($applied[$source . ':' . $m['number']])) {
                    continue;
                }

                $statements = self::split(file_get_contents($m['path']));

                $this->line("  {$dir}/{$m['file']}  ({$m['number']}, "
                    . count($statements) . ' ifade)');

                if ($dryRun) {
                    foreach ($statements as $i => $sql) {
                        $this->line('    --- ifade ' . ($i + 1) . ' ---');
                        $this->line('    ' . str_replace("\n", "\n    ", $sql) . ';');
                    }
                    $ran++;
                    continue;
                }

                foreach ($statements as $i => $sql) {
                    try {
                        $this->execute($sql);
                    } catch (PDOException $e) {
                        /* MySQL'de DDL islem (transaction) icinde geri alinamaz:
                           basarisiz bir migration yariyolda kalabilir. Bu yuzden
                           kayit YAZILMAZ ve burada durulur; duzeltip yeniden
                           calistirdiginizda IF NOT EXISTS sozlesmesi sayesinde
                           tamamlananlar tekrar denenmis olsa da sorun cikarmaz. */
                        $this->line();
                        $this->line("  HATA — {$m['file']}, ifade " . ($i + 1));
                        $this->line('    ' . $e->getMessage());
                        $this->line();
                        $this->line('  Bu migration uygulanmis olarak KAYDEDILMEDI. Duzeltip tekrar');
                        $this->line('  calistirin. Sonraki migrationlar da islenmedi.');
                        $this->line();

                        return 1;
                    }
                }

                try {
                    $this->record($source, $m);
                } catch (PDOException $e) {
                    /* Ör. aynı anda çalışan ikinci bir migrate --run aynı
                       numarayı kaydetti. İfadeler uygulandı ama kayıt yok. */
                    $this->line();
                    $this->line("  HATA — {$m['file']} uygulandi ama KAYDEDILEMEDI:");
                    $this->line('    ' . $e->getMessage());
                    $this->line();
                    $this->line('  Ayni anda iki migrate --run calistirmayin. Durumu --status ile');
                    $this->line('  kontrol edin; sonraki migrationlar islenmedi.');
                    $this->line();

                    return 1;
                }

                $this->line('    uygulandi.');
                $ran++;
            }
        }

        $this->line();
        $this->line($ran === 0
            ? '  Bekleyen migration yok.'
            : "  {$ran} migration " . ($dryRun ? 'uygulanacakti.' : 'uygulandi.'));
        $this->line();

        if (!$dryRun) {
            $this->hintFirstAdmin();
        }

        return 0;
    }

    /**
     * Kullanıcı tablosu boşsa ilk yöneticinin nasıl oluşturulacağını söyler.
     * Parolalı bir tohum kaydı SQL'e gömülmez: herkesin bildiği bir parola
     * her kurulumda açık kapı olurdu.
     */
    private function hintFirstAdmin(): void
    {
        try {
            $count = (int)$this->pdo->query('SELECT COUNT(*) FROM `tbl_users`')->fetchColumn();
        } catch (PDOException $e) {
            return;
        }

        if ($count === 0) {
            $this->line('  Kullanici tablosu bos. Ilk yoneticiyi olusturun:');
            $this->line('    php vnl.php user:create --username=admin --super');
            $this->line();
        }
    }

    /* ---------------------------------------------------------------- */
    /* SQL ayrıştırma                                                    */
    /* ---------------------------------------------------------------- */

    /**
     * Dosyayı çalıştırılabilir ifadelere böler.
     *
     * Noktalı virgülden düz split etmek yetmez: ayraç bir dizginin, bir
     * yorumun ya da bir tanımlayıcının içinde geçebilir. Bu tarayıcı
     * durum tutar ve yalnız gerçekten ifade sonunda böler.
     *
     * Yorumlar atılır; TEK istisna MySQL'in /*! ... *​/ sürüm koşullu
     * blokları — onlar yorum değil çalıştırılabilir koddur.
     *
     * DELIMITER desteklenir: saklı yordam ve tetikleyici gövdelerinde
     * noktalı virgül ifade sonu anlamına gelmez.
     */
    public static function split(string $sql): array
    {
        $out       = [];
        $buffer    = '';
        $delimiter = ';';
        $i         = 0;
        $len       = strlen($sql);

        $flush = function () use (&$out, &$buffer) {
            if (trim($buffer) !== '') {
                $out[] = trim($buffer);
            }
            $buffer = '';
        };

        while ($i < $len) {
            $ch  = $sql[$i];
            $two = substr($sql, $i, 2);

            /* -- satır yorumu (ardından boşluk ya da satır sonu gelmeli:
               "--" bitişik yazıldığında MySQL onu yorum saymaz) */
            if ($two === '--') {
                $next = $sql[$i + 2] ?? "\n";
                if ($next === ' ' || $next === "\t" || $next === "\n" || $next === "\r") {
                    $nl      = strpos($sql, "\n", $i);
                    $i       = ($nl === false) ? $len : $nl + 1;
                    $buffer .= "\n";
                    continue;
                }
            }

            /* # satır yorumu */
            if ($ch === '#') {
                $nl      = strpos($sql, "\n", $i);
                $i       = ($nl === false) ? $len : $nl + 1;
                $buffer .= "\n";
                continue;
            }

            /* blok yorumu */
            if ($two === '/*') {
                $executable = substr($sql, $i, 3) === '/*!';
                $end        = strpos($sql, '*/', $i + 2);
                $stop       = ($end === false) ? $len : $end + 2;

                $buffer .= $executable ? substr($sql, $i, $stop - $i) : ' ';
                $i       = $stop;
                continue;
            }

            /* dizgi ve tırnaklı tanımlayıcı */
            if ($ch === "'" || $ch === '"' || $ch === '`') {
                $quote   = $ch;
                $buffer .= $ch;
                $i++;

                while ($i < $len) {
                    $c = $sql[$i];

                    /* ters bölü kaçışı backtick içinde geçerli değildir */
                    if ($c === '\\' && $quote !== '`' && $i + 1 < $len) {
                        $buffer .= substr($sql, $i, 2);
                        $i      += 2;
                        continue;
                    }

                    if ($c === $quote) {
                        /* '' ya da "" — kaçırılmış tırnak, dizgi sürüyor */
                        if (($sql[$i + 1] ?? '') === $quote) {
                            $buffer .= $quote . $quote;
                            $i      += 2;
                            continue;
                        }

                        $buffer .= $c;
                        $i++;
                        break;
                    }

                    $buffer .= $c;
                    $i++;
                }
                continue;
            }

            /* DELIMITER — yalnızca satır başında bir yönergedir */
            if (($i === 0 || $sql[$i - 1] === "\n")
                && strtoupper(substr($sql, $i, 10)) === 'DELIMITER ') {
                $nl   = strpos($sql, "\n", $i);
                $line = substr($sql, $i + 10, (($nl === false) ? $len : $nl) - ($i + 10));

                $flush();
                $delimiter = trim($line);
                $i         = ($nl === false) ? $len : $nl + 1;
                continue;
            }

            /* ifade sonu */
            if ($delimiter !== '' && substr($sql, $i, strlen($delimiter)) === $delimiter) {
                $flush();
                $i += strlen($delimiter);
                continue;
            }

            $buffer .= $ch;
            $i++;
        }

        $flush();

        return $out;
    }

    /* ---------------------------------------------------------------- */
    /* Yardımcılar                                                       */
    /* ---------------------------------------------------------------- */

    /**
     * NNNN_ad.sql biçimindeki dosyalar, numaraya göre sıralı.
     *
     * Aynı kaynakta aynı numara iki kez kullanılamaz (3.4.0): kayıt
     * (kaynak, numara) ile tutulur. Paralel iki dalın ikisi de 0010 eklerse
     * biri uygulanmış sayılıp SESSİZCE atlanırdı ya da ikincisinin DDL'i
     * çalışıp kaydı UNIQUE anahtarda patlardı. Araç her komutta (status,
     * run, dry-run) hiçbir şey çalıştırmadan durur.
     */
    private function discover(string $source): array
    {
        $dir   = VNL_ROOT . '/' . self::SOURCES[$source];
        $found = [];

        foreach (glob($dir . '/*.sql') ?: [] as $path) {
            $file = basename($path);

            if (!preg_match('/^(\d{4})_(.+)\.sql$/', $file, $m)) {
                continue;
            }

            $found[] = [
                'path'     => $path,
                'file'     => $file,
                'number'   => $m[1],
                'name'     => $m[2],
                'checksum' => hash('sha256', str_replace("\r\n", "\n", file_get_contents($path))),
            ];
        }

        usort($found, static fn($a, $b) => strcmp($a['number'], $b['number']));

        $byNumber = [];
        foreach ($found as $m) {
            $byNumber[$m['number']][] = $m['file'];
        }

        foreach ($byNumber as $number => $files) {
            if (count($files) < 2) {
                continue;
            }

            $recorded = $this->appliedCache[$source . ':' . $number]['name'] ?? null;

            $this->fail(
                'Ayni numarali birden fazla migration (' . self::SOURCES[$source] . "/):\n"
                . '  ' . implode("\n  ", $files) . "\n\n"
                . ($recorded !== null
                    ? "Veritabaninda {$number}_{$recorded}.sql uygulanmis olarak kayitli; DIGERLERINI\n"
                      . "yeni (bos) numaralara tasiyin. Uygulanmis olani yeniden numaralandirmayin:\n"
                      . "ikinci kez calisir."
                    : "Birini bos bir numaraya tasiyin (ornek: modul basina numara blogu).")
            );
        }

        return $found;
    }

    /** kaynak:numara => kayıt */
    private function appliedRecords(): array
    {
        $this->ensureTable();

        $rows = $this->pdo->query(
            "SELECT source, number, name, checksum, applied_at FROM `{$this->table}`"
        )->fetchAll();

        $map = [];
        foreach ($rows as $row) {
            $map[$row['source'] . ':' . $row['number']] = $row;
        }

        return $this->appliedCache = $map;
    }

    private function orphans(array $applied): array
    {
        $onDisk = [];
        foreach (array_keys(self::SOURCES) as $source) {
            foreach ($this->discover($source) as $m) {
                $onDisk[$source . ':' . $m['number']] = true;
            }
        }

        return array_values(array_filter(
            $applied,
            static fn($key) => !isset($onDisk[$key]),
            ARRAY_FILTER_USE_KEY
        ));
    }

    private function record(string $source, array $m): void
    {
        $this->pdo->prepare(
            "INSERT INTO `{$this->table}` (source, number, name, checksum, applied_at)
             VALUES (?, ?, ?, ?, ?)"
        )->execute([$source, $m['number'], $m['name'], $m['checksum'], date('Y-m-d H:i:s')]);
    }

    private function ensureTable(): void
    {
        $this->pdo->exec(
            "CREATE TABLE IF NOT EXISTS `{$this->table}` (
                `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `source`     VARCHAR(16)  NOT NULL,
                `number`     VARCHAR(16)  NOT NULL,
                `name`       VARCHAR(191) NOT NULL,
                `checksum`   CHAR(64)     NOT NULL,
                `applied_at` DATETIME     NOT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uq_vnl_migration` (`source`, `number`)
             ) ENGINE = InnoDB
               DEFAULT CHARSET = utf8mb4
               COLLATE = utf8mb4_unicode_ci"
        );
    }

    /**
     * Tek bir ifadeyi çalıştırır ve varsa sonuç kümesini tüketir.
     *
     * PDO::exec() burada yetmez: SELECT, SHOW ya da sonucu olan bir
     * EXECUTE'tan sonra satırlar okunmadan kalır ve bir SONRAKİ ifade
     * "2014 Cannot execute queries while other unbuffered queries are
     * active" ile düşer. Migration'da sorgu bulunması olağandır —
     * koşullu DDL çoğu zaman information_schema'ya bakarak yazılır.
     */
    private function execute(string $sql): void
    {
        $stmt = $this->pdo->query($sql);

        if (!$stmt instanceof PDOStatement) {
            return;
        }

        do {
            $stmt->fetchAll();
        } while ($this->nextRowset($stmt));

        $stmt->closeCursor();
    }

    /** CALL gibi çoklu küme döndüren ifadeler için; kümesi yoksa sessizce biter. */
    private function nextRowset(PDOStatement $stmt): bool
    {
        try {
            return $stmt->nextRowset();
        } catch (PDOException $e) {
            return false;
        }
    }

    private function line(string $text = ''): void
    {
        fwrite(STDOUT, $text . "\n");
    }

    private function fail(string $message): void
    {
        fwrite(STDERR, "\n  " . str_replace("\n", "\n  ", $message) . "\n\n");
        exit(1);
    }
}

/* ------------------------------------------------------------------ */

return static function (array $args): int {
    $status = in_array('--status', $args, true);
    $run    = in_array('--run', $args, true);
    $dry    = in_array('--dry-run', $args, true);

    if (in_array('--help', $args, true) || in_array('-h', $args, true) || (!$status && !$run)) {
        fwrite(STDOUT, <<<TXT

  Bekleyen sema degisikliklerini uygular.

    php vnl.php migrate --status         neyin uygulandigini gosterir
    php vnl.php migrate --run            bekleyenleri sirayla uygular
    php vnl.php migrate --run --dry-run  uygulamaz, calisacak SQL'i basar

  Cekirdegin migrationlari vnl/migrations/, projeninkiler migrations/
  altindadir. Numaralari birbirinden bagimsiz yurur; once cekirdek uygulanir.


TXT
        );

        return ($status || $run) ? 0 : 1;
    }

    $migrator = new VnlMigrate(new ConfigDefinitions());

    return $run ? $migrator->run($dry) : $migrator->status();
};

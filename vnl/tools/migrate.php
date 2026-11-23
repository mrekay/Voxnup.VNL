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
 *
 * Sürücüler (4.1.0): mysql (mariadb), pgsql, sqlsrv, sqlite. Bir numaranın
 * sürücüye özel hâli NNNN_ad.<sürücü>.sql adını taşır ve o sürücüde düz
 * NNNN_ad.sql'in yerine geçer:
 *
 *   0003_vnl_rate_limits.sql          MySQL (ve özel hâli olmayan her sürücü)
 *   0003_vnl_rate_limits.sqlsrv.sql   SQL Server
 *
 * Kayıt yine (kaynak, numara) iledir ve ad son eksiz tutulur: hangi dosyanın
 * seçildiği kimliği değiştirmez, yalnız özet seçilen dosyanınkidir. MySQL
 * kurulumu bugüne kadar uyguladığı dosyaları aynen görür.
 *
 * MySQL dışındaki sürücülerde bir migration'ın ifadeleri ve kaydı tek bir
 * işlem (transaction) içinde çalışır: yarıda düşen migration geri alınır.
 * MySQL'de DDL işlem içinde geri alınamadığı için orada eski davranış sürer.
 */

require_once VNL_PATH . '/env.php';
require_once VNL_ROOT . '/config/definitions.php';

/** Araç düzeyinde hata: komut yakalar, mesajı basar ve 1 ile çıkar. */
class VnlMigrateException extends RuntimeException {}

class VnlMigrate
{
    private const TABLE = 'tbl_vnl_migrations';

    /** Dizin => kayıtlardaki kaynak adı. Sıra uygulama sırasıdır. */
    private const SOURCES = [
        'vnl' => 'vnl/migrations',
        'app' => 'migrations',
    ];

    /** DB_DRIVER => SQL lehçesi (sürücüye özel dosyanın son eki). */
    public const DIALECTS = [
        'mysql'   => 'mysql',
        'mariadb' => 'mysql',
        'pgsql'   => 'pgsql',
        'sqlsrv'  => 'sqlsrv',
        'sqlite'  => 'sqlite',
    ];

    private PDO $pdo;
    private string $table;
    private string $database;
    private string $dialect;

    /** Son appliedRecords() sonucu (çakışma mesajı için). */
    private ?array $appliedCache = null;

    /**
     * $timeout > 0: bağlantı zaman aşımı (sn) — doctor beklemesin diye.
     *
     * @throws VnlMigrateException sürücü desteklenmiyorsa ya da bağlanılamazsa
     */
    public function __construct(ConfigDefinitions $config, int $timeout = 0)
    {
        $db     = $config->getDBInfo();
        $driver = strtolower((string)($db['driver'] ?? '')) ?: 'mysql';

        if (!isset(self::DIALECTS[$driver])) {
            $this->fail(
                "Bu arac DB_DRIVER={$driver} surucusunu tanimiyor.\n"
                . 'Desteklenenler: ' . implode(', ', array_keys(self::DIALECTS)) . '.'
            );
        }

        if (($db['database'] ?? '') === '') {
            $this->fail("DB_DATABASE bos. .env dosyanizi doldurun.");
        }

        $this->dialect  = self::DIALECTS[$driver];
        $this->database = (string)$db['database'];
        $this->table    = ($db['prefix'] ?? '') . self::TABLE;

        try {
            $this->pdo = $this->dialect === 'mysql'
                ? $this->connectMysql($db, $timeout)
                : $this->connectIlluminate($db, $timeout);
        } catch (PDOException | InvalidArgumentException $e) {
            $this->fail("Veritabanina baglanilamadi: " . $e->getMessage());
        }
    }

    /** Seçilen SQL lehçesi: mysql | pgsql | sqlsrv | sqlite. */
    public function dialect(): string
    {
        return $this->dialect;
    }

    /** MySQL bağlantısı 4.1.0 öncesiyle aynıdır (doğrudan PDO). */
    private function connectMysql(array $db, int $timeout): PDO
    {
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

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        if ($timeout > 0) {
            $options[PDO::ATTR_TIMEOUT] = $timeout;
        }

        return new PDO($dsn, (string)$db['username'], (string)$db['password'], $options);
    }

    /**
     * Öbür sürücüler uygulamanın kendi bağlantı kurucusuyla (Illuminate)
     * açılır: config/definitions.php'ye eklenmiş sürücüye özel anahtarlar
     * (encrypt, trust_server_certificate, sslmode, search_path …) burada da
     * geçerlidir; araç uygulamanın bağlandığı yere aynı biçimde bağlanır.
     */
    private function connectIlluminate(array $db, int $timeout): PDO
    {
        require_once VNL_PATH . '/vendor/autoload.php';

        if ($timeout > 0 && $this->dialect === 'pgsql') {
            $db['connect_timeout'] ??= $timeout;
        } elseif ($timeout > 0 && $this->dialect === 'sqlsrv') {
            $db['login_timeout'] ??= $timeout;
        }

        $factory = new \Illuminate\Database\Connectors\ConnectionFactory(new \Illuminate\Container\Container());
        $pdo     = $factory->make($db)->getPdo();

        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        return $pdo;
    }

    /** Lehçeye göre tırnaklı tanımlayıcı. */
    private function quote(string $identifier): string
    {
        return match ($this->dialect) {
            'mysql'  => '`' . str_replace('`', '``', $identifier) . '`',
            'sqlsrv' => '[' . str_replace(']', ']]', $identifier) . ']',
            default  => '"' . str_replace('"', '""', $identifier) . '"',
        };
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
        $this->line("  VNL migration durumu  —  veritabani: {$this->database}"
            . ($this->dialect !== 'mysql' ? "  (surucu: {$this->dialect})" : ''));

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

                $statements = self::split(file_get_contents($m['path']), $this->dialect);

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

                /* 4.1.0: MySQL dışında migration + kaydı tek işlemdir. */
                $transactional = $this->dialect !== 'mysql';
                if ($transactional) {
                    $this->pdo->beginTransaction();
                }

                foreach ($statements as $i => $sql) {
                    try {
                        $this->execute($sql);
                    } catch (PDOException $e) {
                        /* MySQL'de DDL islem (transaction) icinde geri alinamaz:
                           basarisiz bir migration yariyolda kalabilir. Bu yuzden
                           kayit YAZILMAZ ve burada durulur; duzeltip yeniden
                           calistirdiginizda IF NOT EXISTS sozlesmesi sayesinde
                           tamamlananlar tekrar denenmis olsa da sorun cikarmaz.
                           Öbür sürücülerde işlem geri alınır. */
                        $this->rollBack();
                        $this->line();
                        $this->line("  HATA — {$m['file']}, ifade " . ($i + 1));
                        $this->line('    ' . $e->getMessage());
                        $this->line();
                        $this->line('  Bu migration uygulanmis olarak KAYDEDILMEDI'
                            . ($transactional ? ' ve ifadeleri GERI ALINDI.' : '.') . ' Duzeltip tekrar');
                        $this->line('  calistirin. Sonraki migrationlar da islenmedi.');
                        $this->line();

                        return 1;
                    }
                }

                try {
                    $this->record($source, $m);

                    if ($transactional) {
                        $this->pdo->commit();
                    }
                } catch (PDOException $e) {
                    /* Ör. aynı anda çalışan ikinci bir migrate --run aynı
                       numarayı kaydetti. İfadeler uygulandı ama kayıt yok
                       (MySQL); öbür sürücülerde ikisi birlikte geri alınır. */
                    $this->rollBack();
                    $this->line();
                    $this->line($transactional
                        ? "  HATA — {$m['file']} KAYDEDILEMEDI, ifadeleri geri alindi:"
                        : "  HATA — {$m['file']} uygulandi ama KAYDEDILEMEDI:");
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
            $count = (int)$this->pdo->query('SELECT COUNT(*) FROM ' . $this->quote('tbl_users'))->fetchColumn();
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
     *
     * $dialect (4.1.0) lehçeye özgü kuralları seçer. MySQL'in kuralları
     * değişmedi. Öbürlerinde: # yorum değildir (SQL Server'da #gecici
     * tablo adıdır), dizgide ters bölü kaçış değildir, backtick tırnak
     * değildir; SQL Server'da [köşeli] tanımlayıcılar, PostgreSQL'de
     * $$ … $$ / $etiket$ … $etiket$ gövdeleri (DO bloğu, fonksiyon) tek
     * parça okunur.
     */
    public static function split(string $sql, string $dialect = 'mysql'): array
    {
        $out       = [];
        $buffer    = '';
        $delimiter = ';';
        $i         = 0;
        $len       = strlen($sql);
        $mysql     = $dialect === 'mysql';

        $flush = function () use (&$out, &$buffer) {
            if (trim($buffer) !== '') {
                $out[] = trim($buffer);
            }
            $buffer = '';
        };

        while ($i < $len) {
            $ch  = $sql[$i];
            $two = substr($sql, $i, 2);

            /* PostgreSQL dolar tırnağı: kapanan aynı etikete kadar her şey */
            if ($ch === '$' && $dialect === 'pgsql'
                && preg_match('/\G\$([A-Za-z_][A-Za-z0-9_]*)?\$/', $sql, $tag, 0, $i)) {
                $end  = strpos($sql, $tag[0], $i + strlen($tag[0]));
                $stop = ($end === false) ? $len : $end + strlen($tag[0]);

                $buffer .= substr($sql, $i, $stop - $i);
                $i       = $stop;
                continue;
            }

            /* SQL Server köşeli tanımlayıcısı ( ]] kaçırılmış ] ) */
            if ($ch === '[' && $dialect === 'sqlsrv') {
                $j = $i + 1;
                while ($j < $len) {
                    if ($sql[$j] === ']') {
                        if (($sql[$j + 1] ?? '') === ']') {
                            $j += 2;
                            continue;
                        }
                        break;
                    }
                    $j++;
                }
                $stop    = min($len, $j + 1);
                $buffer .= substr($sql, $i, $stop - $i);
                $i       = $stop;
                continue;
            }

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

            /* # satır yorumu (yalnız MySQL) */
            if ($ch === '#' && $mysql) {
                $nl      = strpos($sql, "\n", $i);
                $i       = ($nl === false) ? $len : $nl + 1;
                $buffer .= "\n";
                continue;
            }

            /* blok yorumu */
            if ($two === '/*') {
                $executable = $mysql && substr($sql, $i, 3) === '/*!';
                $end        = strpos($sql, '*/', $i + 2);
                $stop       = ($end === false) ? $len : $end + 2;

                $buffer .= $executable ? substr($sql, $i, $stop - $i) : ' ';
                $i       = $stop;
                continue;
            }

            /* dizgi ve tırnaklı tanımlayıcı */
            if ($ch === "'" || $ch === '"' || ($ch === '`' && $mysql)) {
                $quote   = $ch;
                $buffer .= $ch;
                $i++;

                while ($i < $len) {
                    $c = $sql[$i];

                    /* ters bölü kaçışı backtick içinde ve MySQL dışında
                       geçerli değildir */
                    if ($c === '\\' && $mysql && $quote !== '`' && $i + 1 < $len) {
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
     *
     * 4.1.0: NNNN_ad.<sürücü>.sql aynı migration'ın o sürücüye özel
     * hâlidir, çakışma sayılmaz — yeter ki adı (son eksiz) aynı olsun. Bu
     * sürücünün hâli varsa o, yoksa düz dosya seçilir; yalnız başka
     * sürücülere özel hâli olan numara bu kurulumda yoktur.
     */
    private function discover(string $source): array
    {
        $dir      = VNL_ROOT . '/' . self::SOURCES[$source];
        $suffixes = implode('|', array_unique(self::DIALECTS));
        $byNumber = [];

        foreach (glob($dir . '/*.sql') ?: [] as $path) {
            $file = basename($path);

            if (!preg_match('/^(\d{4})_(.+?)(?:\.(' . $suffixes . '))?\.sql$/', $file, $m)) {
                continue;
            }

            $byNumber[$m[1]][] = [
                'path'    => $path,
                'file'    => $file,
                'number'  => $m[1],
                'name'    => $m[2],
                'dialect' => $m[3] ?? '',
            ];
        }

        ksort($byNumber, SORT_STRING);

        $found = [];
        foreach ($byNumber as $number => $candidates) {
            $names    = array_unique(array_column($candidates, 'name'));
            $dialects = array_column($candidates, 'dialect');

            if (count($names) > 1 || count($dialects) !== count(array_unique($dialects))) {
                $this->duplicate($source, (string)$number, array_column($candidates, 'file'));
            }

            $chosen = null;
            foreach ($candidates as $c) {
                if ($c['dialect'] === $this->dialect) {
                    $chosen = $c;
                    break;
                }
                if ($c['dialect'] === '') {
                    $chosen ??= $c;
                }
            }

            if ($chosen === null) {
                continue;
            }

            unset($chosen['dialect']);
            $chosen['checksum'] = hash('sha256', str_replace("\r\n", "\n", file_get_contents($chosen['path'])));
            $found[] = $chosen;
        }

        return $found;
    }

    /** Aynı numaralı farklı migration'lar: hiçbir şey çalıştırmadan dur. */
    private function duplicate(string $source, string $number, array $files): never
    {
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

    /**
     * Bu kaynağın uygulanmamış migration dosyaları (doctor için).
     *
     * Kayıt tablosunu OLUŞTURMAZ: tablo yoksa hiçbir şey uygulanmamıştır.
     *
     * @return string[] dosya adları
     */
    public function pending(string $source = 'vnl'): array
    {
        try {
            $applied = $this->appliedRecords(false);
        } catch (PDOException $e) {
            $applied = [];
        }

        $out = [];
        foreach ($this->discover($source) as $m) {
            if (!isset($applied[$source . ':' . $m['number']])) {
                $out[] = $m['file'];
            }
        }

        return $out;
    }

    /** kaynak:numara => kayıt */
    private function appliedRecords(bool $create = true): array
    {
        if ($create) {
            $this->ensureTable();
        }

        $rows = $this->pdo->query(
            'SELECT source, number, name, checksum, applied_at FROM ' . $this->quote($this->table)
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
            'INSERT INTO ' . $this->quote($this->table) . ' (source, number, name, checksum, applied_at)
             VALUES (?, ?, ?, ?, ?)'
        )->execute([$source, $m['number'], $m['name'], $m['checksum'], date('Y-m-d H:i:s')]);
    }

    private function ensureTable(): void
    {
        $t = $this->quote($this->table);

        /* Benzersizlik kısıtının adı tablo adından türer: PostgreSQL'de ve
           SQL Server'da kısıt adları şema genelinde tekildir (önekli iki
           kurulum aynı şemayı paylaşabilir). */
        $uq = $this->quote('uq_' . $this->table);

        $this->pdo->exec(match ($this->dialect) {
            'mysql' => "CREATE TABLE IF NOT EXISTS {$t} (
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
               COLLATE = utf8mb4_unicode_ci",

            'pgsql' => "CREATE TABLE IF NOT EXISTS {$t} (
                id         INTEGER GENERATED BY DEFAULT AS IDENTITY PRIMARY KEY,
                source     VARCHAR(16)  NOT NULL,
                number     VARCHAR(16)  NOT NULL,
                name       VARCHAR(191) NOT NULL,
                checksum   CHAR(64)     NOT NULL,
                applied_at TIMESTAMP(0) NOT NULL,
                CONSTRAINT {$uq} UNIQUE (source, number)
             )",

            'sqlsrv' => "IF OBJECT_ID(N'" . str_replace("'", "''", $this->table) . "', N'U') IS NULL
             CREATE TABLE {$t} (
                [id]         INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
                [source]     NVARCHAR(16)  NOT NULL,
                [number]     NVARCHAR(16)  NOT NULL,
                [name]       NVARCHAR(191) NOT NULL,
                [checksum]   NCHAR(64)     NOT NULL,
                [applied_at] DATETIME2(0)  NOT NULL,
                CONSTRAINT {$uq} UNIQUE ([source], [number])
             )",

            'sqlite' => "CREATE TABLE IF NOT EXISTS {$t} (
                id         INTEGER PRIMARY KEY AUTOINCREMENT,
                source     VARCHAR(16)  NOT NULL,
                number     VARCHAR(16)  NOT NULL,
                name       VARCHAR(191) NOT NULL,
                checksum   CHAR(64)     NOT NULL,
                applied_at DATETIME     NOT NULL,
                UNIQUE (source, number)
             )",
        });
    }

    /** Açık işlem varsa geri alır (MySQL'de işlem açılmaz). */
    private function rollBack(): void
    {
        try {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
        } catch (PDOException $e) {
            // işlem sunucu tarafında zaten düşmüş olabilir
        }
    }

    /**
     * Tek bir ifadeyi çalıştırır ve varsa sonuç kümesini tüketir.
     *
     * PDO::exec() burada yetmez: SELECT, SHOW ya da sonucu olan bir
     * EXECUTE'tan sonra satırlar okunmadan kalır ve bir SONRAKİ ifade
     * "2014 Cannot execute queries while other unbuffered queries are
     * active" ile düşer. Migration'da sorgu bulunması olağandır —
     * koşullu DDL çoğu zaman information_schema'ya bakarak yazılır.
     *
     * Yalnız sütunu olan küme okunur (4.1.0): pdo_sqlsrv sonuç kümesi
     * olmayan ifadede fetchAll()'ı hata sayar.
     */
    private function execute(string $sql): void
    {
        $stmt = $this->pdo->query($sql);

        if (!$stmt instanceof PDOStatement) {
            return;
        }

        do {
            if ($stmt->columnCount() > 0) {
                $stmt->fetchAll();
            }
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

    /** 4.1.0: çıkmaz, fırlatır — doctor aynı sınıfı kullanır. */
    private function fail(string $message): never
    {
        throw new VnlMigrateException($message);
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
  Surucuye ozel hali olan migration (NNNN_ad.pgsql.sql gibi) o surucude
  duz dosyanin yerine gecer. Suruculer: mysql, mariadb, pgsql, sqlsrv, sqlite.


TXT
        );

        return ($status || $run) ? 0 : 1;
    }

    try {
        $migrator = new VnlMigrate(new ConfigDefinitions());

        return $run ? $migrator->run($dry) : $migrator->status();
    } catch (VnlMigrateException $e) {
        fwrite(STDERR, "\n  " . str_replace("\n", "\n  ", $e->getMessage()) . "\n\n");

        return 1;
    }
};

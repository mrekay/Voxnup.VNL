<?php
/*!
 * ============================================================
 *  Voxnup Not Laravel (VNL) — update komutu
 *  Çekirdeği yeni sürüme günceller.
 *  Authors: Voxnup Authors
 * ============================================================
 *
 *   php vnl.php update --check          yeni sürüm var mı
 *   php vnl.php update --verify         kurulu çekirdek kurcalanmış mı
 *   php vnl.php update --to 3.1.0       belirtilen sürüme geç
 *   php vnl.php update --file a.tar.gz  elde indirilmiş arşivden
 *   php vnl.php update --rollback       vnl.bak/ üzerinden geri dön
 *
 * 4.0.0: Arşiv açılmadan ÖNCE Ed25519 imzası kurulu çekirdeğin vnl/KEYS
 * dosyasına karşı doğrulanır (vnl/tools/signature.php). İmza yoksa, sodium
 * yoksa ya da KEYS boşsa güncelleme durur; --insecure bunları bilerek aşar.
 * GEÇERSİZ imzayı hiçbir bayrak aşamaz. MANIFEST'siz ya da MANIFEST'te
 * olmayan dosya taşıyan arşiv reddedilir. Uygulama yarıda kalırsa yedek
 * kendiliğinden geri yüklenir.
 *
 * Yalnız vnl/ değişir. Projenin hiçbir dosyasına dokunulmaz — sınır
 * zaten bunun için var (bkz. /docs/architecture).
 */

require_once VNL_PATH . '/tools/tar.php';
require_once VNL_PATH . '/tools/signature.php';

final class VnlUpdate
{
    private const DEFAULT_REPO = 'mrekay/Voxnup.VNL';

    /** İndirilen tek dosyanın (arşiv, imza, API yanıtı) üst sınırı. */
    public const MAX_DOWNLOAD = 67108864;   // 64 MB

    private string $repo;

    public function __construct()
    {
        $repo = (string)(getenv('VNL_UPDATE_REPO') ?: self::DEFAULT_REPO);

        /* 4.0.0: "sahip/ad" dışında bir değer API adresine yol parçası olarak
           girerdi (../, ?, #). */
        if (!preg_match('~^[A-Za-z0-9_.-]+/[A-Za-z0-9_.-]+$~', $repo) || str_contains($repo, '..')) {
            throw new RuntimeException("VNL_UPDATE_REPO gecersiz (sahip/depo bekleniyor): {$repo}");
        }

        $this->repo = $repo;
    }

    /**
     * Gelen sürüm kurulabilir mi? (4.0.0)
     *
     * İmza baytları korur, sürümü değil: imzalı ESKİ bir arşiv yeni bir
     * etiketin altına konarak bilinen bir açığı geri getirebilirdi. Bu
     * yüzden arşivdeki VERSION, istenen etiketle aynı ve kurulu sürümden
     * büyük olmalıdır. Bilerek geri dönmek için --allow-downgrade.
     */
    public function checkVersion(string $incoming, string $installed, ?string $expected, bool $allowDowngrade): void
    {
        if (!preg_match('/^\d+\.\d+\.\d+(-[0-9A-Za-z.]+)?$/', $incoming)) {
            throw new RuntimeException("Arsivdeki VERSION gecersiz: {$incoming}");
        }

        if ($expected !== null && $incoming !== $expected) {
            throw new RuntimeException(
                "Arsiv {$incoming} surumunu tasiyor ama {$expected} istendi; yayin varligi yanlis ya da degistirilmis."
            );
        }

        if (!$allowDowngrade && version_compare($incoming, $installed, '<=')) {
            throw new RuntimeException(
                "Arsiv ({$incoming}) kurulu surumden ({$installed}) yeni degil.\n"
                . "  Bilerek eski surume donmek icin: --allow-downgrade"
            );
        }
    }

    /* ---------------------------------------------------------------- */
    /* Sürüm                                                             */
    /* ---------------------------------------------------------------- */

    public function installedVersion(): string
    {
        $file = VNL_PATH . '/VERSION';

        return is_file($file) ? trim((string)file_get_contents($file)) : '0.0.0';
    }

    /** @return array{version:string,url:string,sig:string,notes:string} */
    public function release(?string $version): array
    {
        $endpoint = $version === null
            ? "https://api.github.com/repos/{$this->repo}/releases/latest"
            : "https://api.github.com/repos/{$this->repo}/releases/tags/v{$version}";

        $json = json_decode($this->httpGet($endpoint), true);

        if (!is_array($json) || !isset($json['tag_name'])) {
            throw new RuntimeException(
                $version === null
                    ? "Yayin bilgisi okunamadi. Depoda hic yayin olmayabilir: {$this->repo}"
                    : "v{$version} diye bir yayin bulunamadi: {$this->repo}"
            );
        }

        $tag  = ltrim((string)$json['tag_name'], 'v');
        $want = 'vnl-' . $tag . '.tar.gz';
        $url  = '';
        $sig  = '';

        foreach ($json['assets'] ?? [] as $asset) {
            if (($asset['name'] ?? '') === $want) {
                $url = (string)$asset['browser_download_url'];
            } elseif (($asset['name'] ?? '') === $want . '.sig') {
                $sig = (string)$asset['browser_download_url'];
            }
        }

        if ($url === '') {
            throw new RuntimeException(
                "v{$tag} yayininda {$want} arsivi yok.\n"
                . "  Yayini hazirlayan 'php vnl.php release' ciktisini eklemeyi atlamis olabilir."
            );
        }

        return ['version' => $tag, 'url' => $url, 'sig' => $sig, 'notes' => (string)($json['html_url'] ?? '')];
    }

    /* ---------------------------------------------------------------- */
    /* İmza (4.0.0)                                                      */
    /* ---------------------------------------------------------------- */

    /** Kurulu çekirdeğin güvendiği açık anahtarlar. */
    public function keys(?string $file = null): array
    {
        $file ??= VNL_PATH . '/KEYS';

        return is_file($file) ? VnlSignature::parseKeys((string)file_get_contents($file)) : [];
    }

    /**
     * Arşivin kaynağını doğrular; açılmadan önce çağrılır.
     *
     * Doğrulanamayan durumlar (imza yok, sodium yok, KEYS boş) $insecure ile
     * aşılabilir ve uyarı metni döner. Geçersiz imza VnlSignatureInvalid atar
     * ve hiçbir bayrakla aşılamaz: o arşivin kurcalandığı biliniyor.
     *
     * @param ?bool $sodium yalnız testler için; null ise gerçekten sorulur
     * @return array{verified:bool,message:string}
     */
    public function authenticate(string $blob, ?string $signature, array $keys, bool $insecure, ?bool $sodium = null): array
    {
        $sodium ??= VnlSignature::available();

        /* 4.0.0: Güvenilen anahtarı olan kurulumda imzasız arşiv, imzası
           SÖKÜLMÜŞ arşivdir; --insecure bunu aşamaz. --insecure yalnız
           doğrulamanın mümkün olmadığı durumlar (sodium yok, KEYS boş) içindir. */
        if (($signature === null || trim($signature) === '') && $keys !== []) {
            throw new VnlSignatureInvalid(
                "Arsivin imzasi (.sig) yok, ama bu kurulum imzali yayin bekliyor (vnl/KEYS dolu).\n"
                . "  Imzasi sokulmus bir arsiv olabilir; --insecure bunu asamaz."
            );
        }

        $cannot = match (true) {
            $signature === null || trim($signature) === '' => 'Arsivin imzasi (.sig) yok.',
            !$sodium                                         => 'PHP sodium eklentisi yok; imza dogrulanamiyor.',
            $keys === []                                     => 'vnl/KEYS bos ya da yok; guvenilen bir yayin anahtari tanimli degil.',
            default                                          => null,
        };

        if ($cannot !== null) {
            if (!$insecure) {
                throw new RuntimeException(
                    $cannot . "\n"
                    . "  Arsivin kimden geldigi dogrulanamadi; guncelleme durduruldu.\n"
                    . "  Kaynagindan eminseniz bilerek gecmek icin: --insecure"
                );
            }

            return ['verified' => false, 'message' => 'IMZA DOGRULANMADI (--insecure): ' . $cannot];
        }

        $id = VnlSignature::verify($blob, (string)$signature, $keys);

        return ['verified' => true, 'message' => 'Imza dogrulandi (anahtar ' . $id . ')'];
    }

    /* ---------------------------------------------------------------- */
    /* Bütünlük                                                          */
    /* ---------------------------------------------------------------- */

    /** MANIFEST'i okur: göreli yol => sha256. */
    public function manifest(string $path): ?array
    {
        if (!is_file($path)) {
            return null;
        }

        $map = [];
        foreach (explode("\n", (string)file_get_contents($path)) as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            $parts = preg_split('/\s+/', $line, 2);
            if (count($parts) === 2) {
                $map[$parts[1]] = $parts[0];
            }
        }

        return $map;
    }

    /**
     * Bir çekirdek ağacını MANIFEST'ine karşı sınar.
     *
     * @return array{changed:string[],missing:string[],extra:string[]}
     */
    public function compare(string $root, array $manifest): array
    {
        $changed = [];
        $missing = [];
        $seen    = [];

        foreach ($manifest as $relative => $expected) {
            $path = $root . '/' . $relative;

            if (!is_file($path)) {
                $missing[] = $relative;
                continue;
            }

            $seen[$relative] = true;

            /* Satır sonu normalize edilir: depo Windows'ta CRLF ile
               checkout edilmis olabilir, dosya yine de degismemistir. */
            $actual = hash('sha256', str_replace("\r\n", "\n", (string)file_get_contents($path)));

            if (!hash_equals($expected, $actual)) {
                $changed[] = $relative;
            }
        }

        $extra = [];
        foreach (VnlTar::scan($root, 'x') as $entry => $_) {
            $relative = substr($entry, 2);
            if ($relative !== 'MANIFEST' && !isset($seen[$relative]) && !isset($manifest[$relative])) {
                $extra[] = $relative;
            }
        }

        return ['changed' => $changed, 'missing' => $missing, 'extra' => $extra];
    }

    /**
     * Arşivi $temp'e açar ve içeriğini kendi MANIFEST'ine karşı sınar.
     * 4.0.0: MANIFEST zorunludur ve MANIFEST'te olmayan dosya kabul edilmez —
     * aksi halde imzasız kurulan (--insecure) bir arşive eklenen dosya sync ile
     * çekirdeğe girerdi.
     *
     * @return array{dir:string,version:string,files:int}
     */
    public function unpack(string $blob, string $temp, string $label = 'arsiv'): array
    {
        VnlTar::extract(VnlTar::gunzip($blob), $temp);

        $incoming = $temp . '/vnl';
        if (!is_dir($incoming) || !is_file($incoming . '/VERSION')) {
            throw new RuntimeException("Arsiv beklenen bicimde degil (vnl/ ve vnl/VERSION yok): {$label}");
        }

        $manifest = $this->manifest($incoming . '/MANIFEST');
        if ($manifest === null || $manifest === []) {
            throw new RuntimeException("Arsivde vnl/MANIFEST yok; icerigi sinanamiyor: {$label}");
        }

        $check = $this->compare($incoming, $manifest);
        if ($check['changed'] !== [] || $check['missing'] !== []) {
            throw new RuntimeException('Arsiv kendi MANIFEST\'iyle uyusmuyor; bozuk ya da eksik.');
        }
        if ($check['extra'] !== []) {
            throw new RuntimeException(
                'Arsivde MANIFEST\'te olmayan dosyalar var: ' . implode(', ', array_slice($check['extra'], 0, 5))
            );
        }

        return [
            'dir'     => $incoming,
            'version' => trim((string)file_get_contents($incoming . '/VERSION')),
            'files'   => count($manifest),
        ];
    }

    /* ---------------------------------------------------------------- */
    /* Uygulama                                                          */
    /* ---------------------------------------------------------------- */

    /**
     * Yedek alır ve yeni ağacı uygular. Uygulama yarıda kalırsa yedek
     * kendiliğinden geri yüklenir: yarım çekirdek, eski çekirdekten kötüdür.
     *
     * @param ?callable $sync yalnız testler için (yarıda kalmayı taklit eder)
     * @return array{written:int,deleted:int,skipped:int}
     */
    public function apply(string $incoming, string $target, string $backup, ?callable $sync = null): array
    {
        $sync ??= fn(string $from, string $to) => $this->sync($from, $to);

        $this->backup($target, $backup);

        try {
            return $sync($incoming, $target);
        } catch (Throwable $e) {
            try {
                $this->sync($backup, $target);
            } catch (Throwable $restore) {
                throw new RuntimeException(
                    'Uygulama yarida kaldi: ' . $e->getMessage() . "\n"
                    . '  Yedek de geri yuklenemedi: ' . $restore->getMessage() . "\n"
                    . '  Cekirdek YARIM durumda. vnl.bak/ dizinini elle vnl/ uzerine kopyalayin.',
                    0,
                    $e
                );
            }

            throw new RuntimeException(
                'Uygulama yarida kaldi: ' . $e->getMessage() . "\n"
                . '  Yedek kendiliginden geri yuklendi; cekirdek eski surumunde.',
                0,
                $e
            );
        }
    }

    /**
     * Kaynak ağacı hedefe yerinde uygular.
     *
     * Dizin yeniden ADLANDIRILMAZ: çalışmakta olan betik vnl/tools/
     * altındadır ve Windows açık dosyanın yeniden adlandırılmasına izin
     * vermez. Bunun yerine yalnız içeriği değişen dosyalar yazılır,
     * fazlalıklar silinir. Değişmeyen dosyaya hiç dokunulmaz — bu aynı
     * zamanda update.php'nin kendisini çoğu güncellemede korur.
     *
     * @return array{written:int,deleted:int,skipped:int}
     */
    public function sync(string $source, string $target): array
    {
        $written = $deleted = $skipped = 0;

        $incoming = VnlTar::scan($source, 'x');
        $wanted   = [];

        foreach ($incoming as $entry => $path) {
            $relative           = substr($entry, 2);
            $wanted[$relative]  = true;
            $destination        = $target . '/' . $relative;
            $new                = (string)file_get_contents($path);

            if (is_file($destination) && (string)file_get_contents($destination) === $new) {
                $skipped++;
                continue;
            }

            $dir = dirname($destination);
            if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
                throw new RuntimeException("Dizin olusturulamadi: {$dir}");
            }

            if (file_put_contents($destination, $new) === false) {
                throw new RuntimeException("Yazilamadi: {$destination}");
            }

            $written++;
        }

        /* Yeni surumde olmayan dosyalar silinir; aksi halde kaldirilan bir
           sinif eski haliyle yuklenmeye devam ederdi. */
        foreach (VnlTar::scan($target, 'x') as $entry => $path) {
            $relative = substr($entry, 2);
            if (!isset($wanted[$relative])) {
                @unlink($path);
                $deleted++;
            }
        }

        $this->pruneEmptyDirs($target);

        return ['written' => $written, 'deleted' => $deleted, 'skipped' => $skipped];
    }

    public function backup(string $source, string $target): int
    {
        $this->removeTree($target);

        $count = 0;
        foreach (VnlTar::scan($source, 'x') as $entry => $path) {
            $destination = $target . '/' . substr($entry, 2);
            $dir         = dirname($destination);

            if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
                throw new RuntimeException("Yedek dizini olusturulamadi: {$dir}");
            }

            if (!copy($path, $destination)) {
                throw new RuntimeException("Yedeklenemedi: {$path}");
            }

            $count++;
        }

        return $count;
    }

    public function removeTree(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }

        $items = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($items as $item) {
            $item->isDir() ? @rmdir($item->getPathname()) : @unlink($item->getPathname());
        }

        @rmdir($path);
    }

    private function pruneEmptyDirs(string $root): void
    {
        $items = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($items as $item) {
            if ($item->isDir()) {
                @rmdir($item->getPathname());   // boş değilse başarısız olur, sorun değil
            }
        }
    }

    /* ---------------------------------------------------------------- */

    public function httpGet(string $url): string
    {
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_TIMEOUT        => 120,
                CURLOPT_USERAGENT      => 'VNL-Updater',
                CURLOPT_HTTPHEADER     => ['Accept: application/vnd.github+json'],
                CURLOPT_MAXREDIRS      => 5,
                CURLOPT_PROTOCOLS      => CURLPROTO_HTTPS,
                CURLOPT_REDIR_PROTOCOLS => CURLPROTO_HTTPS,
                CURLOPT_MAXFILESIZE    => self::MAX_DOWNLOAD,
            ]);

            $body = curl_exec($ch);
            $code = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
            $err  = curl_error($ch);

            if ($body === false) {
                throw new RuntimeException("Indirilemedi: {$err}");
            }
            if ($code >= 400) {
                throw new RuntimeException("Indirilemedi (HTTP {$code}): {$url}");
            }
            if (strlen((string)$body) > self::MAX_DOWNLOAD) {
                throw new RuntimeException('Indirilen dosya cok buyuk (> 64 MB): ' . $url);
            }

            return (string)$body;
        }

        if (!ini_get('allow_url_fopen')) {
            throw new RuntimeException(
                "Ne curl ne de allow_url_fopen var; sunucu disariya baglanamiyor.\n"
                . "  Arsivi elle indirip: php vnl.php update --file vnl-X.Y.Z.tar.gz"
            );
        }

        $context = stream_context_create(['http' => [
            'header'  => "User-Agent: VNL-Updater\r\nAccept: application/vnd.github+json\r\n",
            'timeout' => 120,
        ]]);

        if (!str_starts_with(strtolower($url), 'https://')) {
            throw new RuntimeException("Yalniz https adresinden indirilir: {$url}");
        }

        $body = @file_get_contents($url, false, $context, 0, self::MAX_DOWNLOAD + 1);
        if ($body === false) {
            throw new RuntimeException("Indirilemedi: {$url}");
        }
        if (strlen($body) > self::MAX_DOWNLOAD) {
            throw new RuntimeException('Indirilen dosya cok buyuk (> 64 MB): ' . $url);
        }

        return (string)$body;
    }

    public function tempDir(): string
    {
        $path = sys_get_temp_dir() . '/vnl-update-' . bin2hex(random_bytes(6));

        if (!mkdir($path, 0700, true) && !is_dir($path)) {
            throw new RuntimeException("Gecici dizin olusturulamadi: {$path}");
        }

        return $path;
    }
}

/* ------------------------------------------------------------------ */

return static function (array $args) {
    $out = static fn(string $text = '') => fwrite(STDOUT, $text . "\n");
    $opt = static function (array $args, string $flag): ?string {
        $i = array_search($flag, $args, true);

        return ($i === false || !isset($args[$i + 1])) ? null : $args[$i + 1];
    };

    if (in_array('--help', $args, true) || in_array('-h', $args, true) || $args === []) {
        fwrite(STDOUT, <<<TXT

  Cekirdegi yeni surume gunceller. Yalniz vnl/ degisir.

    php vnl.php update --check           yeni surum var mi
    php vnl.php update --verify          kurulu cekirdek kurcalanmis mi
    php vnl.php update --to 3.1.0        belirtilen surume gec
    php vnl.php update --latest          en son surume gec
    php vnl.php update --file a.tar.gz   elde indirilmis arsivden
    php vnl.php update --rollback        vnl.bak/ uzerinden geri don

    --sig a.tar.gz.sig   imza dosyasi (varsayilan: arsivin yanindaki .sig)
    --force      kurcalanmis cekirdek uyarisini gec
    --insecure   imza DOGRULANAMIYORSA (sodium yok, KEYS bos; KEYS bosken
                 imza dosyasi yok) yine de kur. Gecersiz ya da sokulmus
                 imzayi asamaz.
    --allow-downgrade   kurulu surumden eski ya da ayni surumu kur

  Guncelleme sonrasi: php vnl.php doctor  ve  php vnl.php migrate --status


TXT
        );

        return $args === [] ? 1 : 0;
    }

    $updater = new VnlUpdate();
    $force    = in_array('--force', $args, true);
    $insecure = in_array('--insecure', $args, true);
    $downgrade = in_array('--allow-downgrade', $args, true);
    $backup  = VNL_ROOT . '/vnl.bak';

    try {
        /* ---- --check ---- */
        if (in_array('--check', $args, true)) {
            $installed = $updater->installedVersion();
            $release   = $updater->release(null);

            $out();
            $out("  Kurulu   {$installed}");
            $out("  Yayinda  {$release['version']}");
            $out();

            if (version_compare($release['version'], $installed, '<=')) {
                $out('  Guncelsiniz.');
                $out();

                return 0;
            }

            $out('  Guncelleme var. Once degisiklikleri okuyun:');
            if ($release['notes'] !== '') {
                $out('    ' . $release['notes']);
            }
            $out();
            $out("  Sonra:  php vnl.php update --to {$release['version']}");
            $out();

            return 0;
        }

        /* ---- --verify ---- */
        if (in_array('--verify', $args, true)) {
            $manifest = $updater->manifest(VNL_PATH . '/MANIFEST');

            if ($manifest === null) {
                $out();
                $out('  vnl/MANIFEST yok — bu cekirdek yayin arsivinden degil,');
                $out('  depodan gelmis olabilir. Butunluk sinanamiyor.');
                $out();

                return 1;
            }

            $diff = $updater->compare(VNL_PATH, $manifest);
            $out();
            $out('  ' . count($manifest) . ' dosya sinandi.');
            $out();

            foreach ([['changed', 'DEGISMIS'], ['missing', 'EKSIK'], ['extra', 'FAZLADAN']] as [$key, $label]) {
                if ($diff[$key] === []) {
                    continue;
                }
                $out('  ' . $label . ' (' . count($diff[$key]) . ')');
                foreach (array_slice($diff[$key], 0, 20) as $file) {
                    $out('    ' . $file);
                }
                if (count($diff[$key]) > 20) {
                    $out('    … (+' . (count($diff[$key]) - 20) . ')');
                }
                $out();
            }

            if ($diff['changed'] === [] && $diff['missing'] === [] && $diff['extra'] === []) {
                $out('  Cekirdek yayimlandigi gibi.');
                $out();

                return 0;
            }

            $out('  Cekirdege dokunulmus. Bu degisiklikler guncellemede kaybolur —');
            $out('  ezmek icin ayni adli dosyayi proje tarafina koyun (/docs/architecture).');
            $out();

            return 1;
        }

        /* ---- --rollback ---- */
        if (in_array('--rollback', $args, true)) {
            if (!is_dir($backup)) {
                $out();
                $out('  vnl.bak/ yok; geri donulecek bir surum bulunamadi.');
                $out();

                return 1;
            }

            $out();
            $out('  vnl.bak/ geri yukleniyor…');
            $result = $updater->sync($backup, VNL_PATH);
            $out('    ' . $result['written'] . ' yazildi, ' . $result['deleted'] . ' silindi.');
            $out('  Surum: ' . $updater->installedVersion());
            $out();
            $out('  Simdi: php vnl.php doctor');
            $out();

            return 0;
        }

        /* ---- güncelleme ---- */
        $file    = $opt($args, '--file');
        $to      = $opt($args, '--to');
        $latest  = in_array('--latest', $args, true);

        if ($file === null && $to === null && !$latest) {
            $out();
            $out('  Ne yapilacagi belirtilmemis. Secenekler: php vnl.php update --help');
            $out();

            return 1;
        }

        /* Kurulu cekirdek kurcalanmis mi? Uzerine yazmadan once sorulur. */
        $manifest = $updater->manifest(VNL_PATH . '/MANIFEST');
        if ($manifest !== null) {
            $diff = $updater->compare(VNL_PATH, $manifest);
            $dirty = count($diff['changed']) + count($diff['missing']);

            if ($dirty > 0 && !$force) {
                $out();
                $out("  DURDURULDU: kurulu cekirdekte {$dirty} dosya yayimlandigi gibi degil.");
                $out();
                foreach (array_slice(array_merge($diff['changed'], $diff['missing']), 0, 10) as $f) {
                    $out('    ' . $f);
                }
                $out();
                $out('  Guncelleme bunlarin uzerine yazar. Once "php vnl.php update --verify"');
                $out('  ciktisina bakin; degisiklikler gerekliyse proje tarafina tasiyin.');
                $out('  Yine de devam etmek icin --force.');
                $out();

                return 1;
            }
        }

        /* Arsivi getir */
        if ($file !== null) {
            if (!is_file($file)) {
                $out();
                $out("  Arsiv bulunamadi: {$file}");
                $out();

                return 1;
            }
            if (filesize($file) > VnlUpdate::MAX_DOWNLOAD) {
                $out();
                $out('  Arsiv cok buyuk (> 64 MB): ' . $file);
                $out();

                return 1;
            }
            $blob     = (string)file_get_contents($file);
            $label    = $file;
            $expected = null;
            $sigPath = $opt($args, '--sig') ?? $file . '.sig';
            $sig     = is_file($sigPath) ? (string)file_get_contents($sigPath) : null;
        } else {
            $release = $updater->release($latest ? null : $to);
            $out();
            $out("  {$release['version']} indiriliyor…");
            $blob     = $updater->httpGet($release['url']);
            $label    = $release['url'];
            $expected = $release['version'];
            $sig   = $release['sig'] !== '' ? $updater->httpGet($release['sig']) : null;
        }

        /* Kaynak: imza, arsiv ACILMADAN once (tar ayristiricisi da saldiri yuzeyidir). */
        $auth = $updater->authenticate($blob, $sig, $updater->keys(), $insecure);
        $out();
        $out('  ' . $auth['message']);

        /* Ac ve dogrula */
        $temp = $updater->tempDir();
        try {
            $unpacked = $updater->unpack($blob, $temp, $label);

            $updater->checkVersion($unpacked['version'], $updater->installedVersion(), $expected, $downgrade);

            $out('  Arsiv dogrulandi: ' . $unpacked['version'] . ' (' . $unpacked['files'] . ' dosya)');
            $out('  Yedek aliniyor ve uygulaniyor: vnl.bak/ <- vnl/');
            $result = $updater->apply($unpacked['dir'], VNL_PATH, $backup);

            $out();
            $out('  ' . $updater->installedVersion() . ' kuruldu.');
            $out('    ' . $result['written'] . ' yazildi, '
                . $result['deleted'] . ' silindi, '
                . $result['skipped'] . " degismedi.");
            $out();
            $out('  Simdi:');
            $out('    php vnl.php doctor');
            $out('    php vnl.php migrate --status');
            $out();
            $out('  Bir sorun cikarsa: php vnl.php update --rollback');
            $out();
        } finally {
            $updater->removeTree($temp);
        }

        return 0;
    } catch (Throwable $e) {
        fwrite(STDERR, "\n  " . str_replace("\n", "\n  ", $e->getMessage()) . "\n\n");

        if ($e instanceof VnlSignatureInvalid) {
            fwrite(STDERR, "  Arsiv REDDEDILDI; cekirdege dokunulmadi. Bu arsivi kurmayin, kaynagini sorgulayin.\n\n");
        } elseif (is_dir($backup) && !str_contains($e->getMessage(), 'kendiliginden geri yuklendi')) {
            fwrite(STDERR, "  Yedek duruyor: vnl.bak/  —  geri donmek icin:\n");
            fwrite(STDERR, "    php vnl.php update --rollback\n\n");
        }

        return 1;
    }
};

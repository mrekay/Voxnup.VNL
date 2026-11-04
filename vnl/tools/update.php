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
 * Yalnız vnl/ değişir. Projenin hiçbir dosyasına dokunulmaz — sınır
 * zaten bunun için var (bkz. /docs/architecture).
 */

require_once VNL_PATH . '/tools/tar.php';

final class VnlUpdate
{
    private const DEFAULT_REPO = 'mrekay/Voxnup.VNL';

    private string $repo;

    public function __construct()
    {
        $this->repo = (string)(getenv('VNL_UPDATE_REPO') ?: self::DEFAULT_REPO);
    }

    /* ---------------------------------------------------------------- */
    /* Sürüm                                                             */
    /* ---------------------------------------------------------------- */

    public function installedVersion(): string
    {
        $file = VNL_PATH . '/VERSION';

        return is_file($file) ? trim((string)file_get_contents($file)) : '0.0.0';
    }

    /** @return array{version:string,url:string,notes:string} */
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

        foreach ($json['assets'] ?? [] as $asset) {
            if (($asset['name'] ?? '') === $want) {
                $url = (string)$asset['browser_download_url'];
                break;
            }
        }

        if ($url === '') {
            throw new RuntimeException(
                "v{$tag} yayininda {$want} arsivi yok.\n"
                . "  Yayini hazirlayan 'php vnl.php release' ciktisini eklemeyi atlamis olabilir."
            );
        }

        return ['version' => $tag, 'url' => $url, 'notes' => (string)($json['html_url'] ?? '')];
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

    /* ---------------------------------------------------------------- */
    /* Uygulama                                                          */
    /* ---------------------------------------------------------------- */

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

        $body = @file_get_contents($url, false, $context);
        if ($body === false) {
            throw new RuntimeException("Indirilemedi: {$url}");
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

    --force   kurcalanmis cekirdek uyarisini gec

  Guncelleme sonrasi: php vnl.php doctor  ve  php vnl.php migrate --status


TXT
        );

        return $args === [] ? 1 : 0;
    }

    $updater = new VnlUpdate();
    $force   = in_array('--force', $args, true);
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
            $blob  = (string)file_get_contents($file);
            $label = $file;
        } else {
            $release = $updater->release($latest ? null : $to);
            $out();
            $out("  {$release['version']} indiriliyor…");
            $blob  = $updater->httpGet($release['url']);
            $label = $release['url'];
        }

        /* Ac ve dogrula */
        $temp = $updater->tempDir();
        try {
            VnlTar::extract(VnlTar::gunzip($blob), $temp);

            $incoming = $temp . '/vnl';
            if (!is_dir($incoming) || !is_file($incoming . '/VERSION')) {
                throw new RuntimeException("Arsiv beklenen bicimde degil (vnl/ ve vnl/VERSION yok): {$label}");
            }

            $newVersion  = trim((string)file_get_contents($incoming . '/VERSION'));
            $newManifest = $updater->manifest($incoming . '/MANIFEST');

            if ($newManifest !== null) {
                $check = $updater->compare($incoming, $newManifest);
                if ($check['changed'] !== [] || $check['missing'] !== []) {
                    throw new RuntimeException(
                        'Indirilen arsiv kendi MANIFEST\'iyle uyusmuyor; bozuk ya da eksik.'
                    );
                }
            }

            $out('  Arsiv dogrulandi: ' . $newVersion . ' (' . count($newManifest ?? []) . ' dosya)');
            $out('  Yedek aliniyor: vnl.bak/');
            $updater->backup(VNL_PATH, $backup);

            $out('  Uygulaniyor…');
            $result = $updater->sync($incoming, VNL_PATH);

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

        if (is_dir($backup)) {
            fwrite(STDERR, "  Yedek duruyor: vnl.bak/  —  geri donmek icin:\n");
            fwrite(STDERR, "    php vnl.php update --rollback\n\n");
        }

        return 1;
    }
};

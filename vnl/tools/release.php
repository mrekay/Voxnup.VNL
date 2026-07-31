<?php
/*!
 * ============================================================
 *  Voxnup Not Laravel (VNL) — release komutu
 *  vnl/ dizininden yayın arşivi üretir.
 *  Authors: Voxnup Authors
 * ============================================================
 *
 * Bakım komutudur; türev projelerde çalıştırılmaz.
 */

require_once VNL_PATH . '/tools/tar.php';

return static function (array $args): int {
    if (in_array('--help', $args, true) || in_array('-h', $args, true)) {
        fwrite(STDOUT, <<<TXT

  Yayin arsivi uretir (bakim icin).

    php vnl.php release
    php vnl.php release --out /yol/dist

  Once vnl/MANIFEST yazilir (yol => sha256), sonra vnl/ dizini
  vnl-<surum>.tar.gz olarak paketlenir. MANIFEST iki ise yarar:
  indirilen arsivin butunlugu ve kurulu cekirdegin kurcalanip
  kurcalanmadigi ondan anlasilir.

  MANIFEST YAYIN ANINDA uretilir; iki yayin arasindaki commitlerde
  eskir. Yayin sirasi icin /docs/versioning.


TXT
        );

        return 0;
    }

    $out = VNL_ROOT . '/dist';
    foreach ($args as $i => $arg) {
        if ($arg === '--out') {
            $out = rtrim($args[$i + 1] ?? $out, '/\\');
        }
    }

    $versionFile = VNL_PATH . '/VERSION';
    if (!is_file($versionFile)) {
        fwrite(STDERR, "\n  vnl/VERSION yok.\n\n");

        return 1;
    }

    $version = trim((string)file_get_contents($versionFile));
    if (!preg_match('/^\d+\.\d+\.\d+(-[0-9A-Za-z.]+)?$/', $version)) {
        fwrite(STDERR, "\n  vnl/VERSION icerigi surum numarasi gibi degil: {$version}\n\n");

        return 1;
    }

    /* 1) MANIFEST — kendi ozetini iceremeyecegi icin once o haric taranir. */
    $manifestPath = VNL_PATH . '/MANIFEST';
    @unlink($manifestPath);

    $files = VnlTar::scan(VNL_PATH, 'vnl');

    $lines = [];
    foreach ($files as $entry => $path) {
        /* Yol ayraci ve satir sonu farkliliklari ozeti degistirmesin:
           depo Windows'ta CRLF ile checkout edilmis olabilir. */
        $relative = substr($entry, strlen('vnl/'));
        $content  = str_replace("\r\n", "\n", (string)file_get_contents($path));
        $lines[]  = hash('sha256', $content) . '  ' . $relative;
    }

    file_put_contents($manifestPath, implode("\n", $lines) . "\n");

    /* 2) Arsiv — MANIFEST dahil. */
    $files = VnlTar::scan(VNL_PATH, 'vnl');

    if (!is_dir($out) && !mkdir($out, 0755, true) && !is_dir($out)) {
        fwrite(STDERR, "\n  Cikti dizini olusturulamadi: {$out}\n\n");

        return 1;
    }

    $archive = $out . '/vnl-' . $version . '.tar.gz';
    $blob    = VnlTar::gzip(VnlTar::create($files));

    if (file_put_contents($archive, $blob) === false) {
        fwrite(STDERR, "\n  Arsiv yazilamadi: {$archive}\n\n");

        return 1;
    }

    $sha = hash('sha256', $blob);
    file_put_contents($archive . '.sha256', $sha . '  ' . basename($archive) . "\n");

    $size = strlen($blob) >= 1048576
        ? round(strlen($blob) / 1048576, 1) . ' MB'
        : round(strlen($blob) / 1024) . ' KB';

    fwrite(STDOUT, "\n  VNL {$version} yayin arsivi\n\n");
    fwrite(STDOUT, '    ' . count($files) . " dosya, MANIFEST dahil\n");
    fwrite(STDOUT, '    ' . $archive . '  (' . $size . ")\n");
    fwrite(STDOUT, '    sha256  ' . $sha . "\n\n");
    fwrite(STDOUT, "  Simdi: arsivi ve .sha256 dosyasini v{$version} etiketinin\n");
    fwrite(STDOUT, "  yayin varliklari arasina yukleyin.\n\n");

    return 0;
};

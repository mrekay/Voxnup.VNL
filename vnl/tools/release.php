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
require_once VNL_PATH . '/tools/signature.php';

return static function (array $args): int {
    if (in_array('--help', $args, true) || in_array('-h', $args, true)) {
        fwrite(STDOUT, <<<TXT

  Yayin arsivi uretir (bakim icin).

    php vnl.php release
    php vnl.php release --out /yol/dist
    php vnl.php release --keygen       yeni yayin anahtari uret
    php vnl.php release --unsigned     imzasiz (yalniz deneme icin)

  Once vnl/MANIFEST yazilir (yol => sha256), sonra vnl/ dizini
  vnl-<surum>.tar.gz olarak paketlenir ve Ed25519 ile imzalanir
  (vnl-<surum>.tar.gz.sig). MANIFEST kurulu cekirdegin kurcalanip
  kurcalanmadigini, imza arsivin kimden geldigini soyler.

  Gizli anahtar VNL_RELEASE_KEY ortam degiskeninden (base64) ya da
  VNL_RELEASE_KEY_FILE'in gosterdigi dosyadan okunur; DEPOYA GIRMEZ.
  Acik anahtari vnl/KEYS'te olmayan bir anahtarla imzalanmis yayini
  kurulu cekirdekler reddeder; komut bu durumda durur.

  MANIFEST YAYIN ANINDA uretilir; iki yayin arasindaki commitlerde
  eskir. Yayin sirasi icin /docs/versioning.


TXT
        );

        return 0;
    }

    if (in_array('--keygen', $args, true)) {
        $key = VnlSignature::generate();

        fwrite(STDOUT, "\n  Yeni yayin anahtari ({$key['id']})\n\n");
        fwrite(STDOUT, "  1) GIZLI anahtar — parola yoneticisine / CI sirrina koyun,\n");
        fwrite(STDOUT, "     depoya, .env'e ya da sohbete YAZMAYIN:\n\n");
        fwrite(STDOUT, "       VNL_RELEASE_KEY={$key['secret']}\n\n");
        fwrite(STDOUT, "  2) Acik anahtar — vnl/KEYS'e bu satiri ekleyin:\n\n");
        fwrite(STDOUT, "       {$key['line']}\n\n");
        fwrite(STDOUT, "  Anahtar degistiriyorsaniz eski satiri bir surum daha tutun:\n");
        fwrite(STDOUT, "  kurulu cekirdekler yeni anahtari ancak onu tasiyan surumden sonra tanir.\n\n");

        return 0;
    }

    $unsigned = in_array('--unsigned', $args, true);
    $secret   = null;

    if (!$unsigned) {
        $secret = (string)(getenv('VNL_RELEASE_KEY') ?: '');
        $keyFile = (string)(getenv('VNL_RELEASE_KEY_FILE') ?: '');

        if ($secret === '' && $keyFile !== '') {
            if (!is_file($keyFile)) {
                fwrite(STDERR, "\n  VNL_RELEASE_KEY_FILE bulunamadi: {$keyFile}\n\n");

                return 1;
            }
            $secret = trim((string)file_get_contents($keyFile));
        }

        if ($secret === '') {
            fwrite(STDERR, "\n  Imza anahtari yok: VNL_RELEASE_KEY ya da VNL_RELEASE_KEY_FILE tanimlayin.\n");
            fwrite(STDERR, "  Anahtar uretmek icin: php vnl.php release --keygen\n");
            fwrite(STDERR, "  (Yalniz deneme icin: --unsigned — kurulu 4.x cekirdekler bunu reddeder.)\n\n");

            return 1;
        }

        try {
            $signer = VnlSignature::secretKey($secret);
        } catch (Throwable $e) {
            fwrite(STDERR, "\n  " . $e->getMessage() . "\n\n");

            return 1;
        }

        /* KEYS'te olmayan anahtarla imzalanan yayini hicbir kurulum kabul etmez. */
        $trusted = is_file(VNL_PATH . '/KEYS')
            ? VnlSignature::parseKeys((string)file_get_contents(VNL_PATH . '/KEYS'))
            : [];

        if (!isset($trusted[$signer['id']])) {
            fwrite(STDERR, "\n  Bu anahtarin ({$signer['id']}) acik yarisi vnl/KEYS'te yok.\n");
            fwrite(STDERR, "  Kurulu cekirdekler bu yayini reddeder. Once KEYS'e ekleyin:\n\n");
            fwrite(STDERR, '    ed25519 ' . $signer['id'] . ' ' . base64_encode($signer['public']) . "\n\n");

            return 1;
        }
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

    if ($secret !== null) {
        file_put_contents($archive . '.sig', VnlSignature::sign($blob, $secret));
    } else {
        @unlink($archive . '.sig');
    }

    $size = strlen($blob) >= 1048576
        ? round(strlen($blob) / 1048576, 1) . ' MB'
        : round(strlen($blob) / 1024) . ' KB';

    fwrite(STDOUT, "\n  VNL {$version} yayin arsivi\n\n");
    fwrite(STDOUT, '    ' . count($files) . " dosya, MANIFEST dahil\n");
    fwrite(STDOUT, '    ' . $archive . '  (' . $size . ")\n");
    fwrite(STDOUT, '    sha256  ' . $sha . "\n");
    fwrite(STDOUT, $secret !== null
        ? '    imza    ' . basename($archive) . '.sig  (anahtar ' . $signer['id'] . ")\n\n"
        : "    IMZASIZ (--unsigned): kurulu 4.x cekirdekler bunu --insecure olmadan kurmaz.\n\n");
    fwrite(STDOUT, "  Simdi: arsivi, .sig ve .sha256 dosyalarini v{$version} etiketinin\n");
    fwrite(STDOUT, "  yayin varliklari arasina yukleyin.\n\n");

    return 0;
};

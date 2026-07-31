<?php
/*!
 * ============================================================
 *  Voxnup Not Laravel (VNL) — Tar Okuyucu/Yazıcı
 *  Bağımlılıksız .tar.gz üretir ve açar.
 *  Authors: Voxnup Authors
 * ============================================================
 *
 * Neden elle: ZipArchive ve phar eklentileri paylaşımlı barındırmada
 * sıklıkla kapalıdır ve güncelleme, uygulamanın çalışmadığı anda
 * çalıştırılan bir iştir — o an eksik bir eklentiyle uğraşılmaz.
 * VnlTableExport de xlsx için aynı sebeple kendi zip kabını yazar.
 * Tek gerçek bağımlılık zlib (gzencode/gzdecode) ve o da neredeyse
 * her kurulumda vardır; yoksa sıkıştırmasız .tar'a düşülür.
 *
 * Biçim ustar. Uzun yollar için name (100) + prefix (155) bölmesi
 * uygulanır: bugün vnl/ altındaki en uzun yol 91 karakter, ama bir
 * bağımlılık eklendiğinde sessizce kırpılmasın.
 */

class VnlTar
{
    private const BLOCK = 512;

    /* ---------------------------------------------------------------- */
    /* Yazma                                                             */
    /* ---------------------------------------------------------------- */

    /**
     * @param array $files arşivdeki yol => diskteki mutlak yol
     */
    public static function create(array $files): string
    {
        $tar = '';

        foreach ($files as $entry => $path) {
            $data = (string)file_get_contents($path);
            $tar .= self::header($entry, strlen($data), fileperms($path) & 0777, filemtime($path) ?: time());
            $tar .= $data;

            /* Veri 512'nin katına tamamlanır. */
            $remainder = strlen($data) % self::BLOCK;
            if ($remainder !== 0) {
                $tar .= str_repeat("\0", self::BLOCK - $remainder);
            }
        }

        /* Arşiv sonu: iki boş blok. */
        return $tar . str_repeat("\0", self::BLOCK * 2);
    }

    /** Sıkıştırır; zlib yoksa ham tar döner. İkincisini gzip'siz açabiliriz. */
    public static function gzip(string $tar): string
    {
        return function_exists('gzencode') ? (string)gzencode($tar, 9) : $tar;
    }

    private static function header(string $name, int $size, int $mode, int $mtime): string
    {
        $prefix = '';

        if (strlen($name) > 100) {
            /* ustar: yol bir "/" sınırından bölünür; sol taraf prefix'e gider. */
            $split = strrpos(substr($name, 0, 155), '/');

            if ($split === false || strlen($name) - $split - 1 > 100) {
                throw new RuntimeException("Arsiv icin fazla uzun yol: {$name}");
            }

            $prefix = substr($name, 0, $split);
            $name   = substr($name, $split + 1);
        }

        $header = pack('a100', $name)
            . pack('a8',  sprintf('%07o', $mode))
            . pack('a8',  sprintf('%07o', 0))
            . pack('a8',  sprintf('%07o', 0))
            . pack('a12', sprintf('%011o', $size))
            . pack('a12', sprintf('%011o', $mtime))
            . str_repeat(' ', 8)          // sağlama, önce boşluk
            . '0'                          // düz dosya
            . pack('a100', '')             // linkname
            . pack('a6', 'ustar') . pack('a2', '00')
            . pack('a32', '') . pack('a32', '')
            . pack('a8', '') . pack('a8', '')
            . pack('a155', $prefix)
            . pack('a12', '');

        /* Sağlama, alanın kendisi boşluk sayılarak hesaplanır. */
        $checksum = 0;
        for ($i = 0; $i < self::BLOCK; $i++) {
            $checksum += ord($header[$i]);
        }

        return substr_replace($header, sprintf('%06o', $checksum) . "\0 ", 148, 8);
    }

    /* ---------------------------------------------------------------- */
    /* Okuma                                                             */
    /* ---------------------------------------------------------------- */

    /** gzip'li ya da ham tar kabul eder. */
    public static function gunzip(string $blob): string
    {
        /* gzip sihirli sayısı: 1f 8b */
        if (strncmp($blob, "\x1f\x8b", 2) !== 0) {
            return $blob;
        }

        if (!function_exists('gzdecode')) {
            throw new RuntimeException('Arsiv gzip ile sikistirilmis ama zlib (gzdecode) yok.');
        }

        $out = @gzdecode($blob);
        if ($out === false) {
            throw new RuntimeException('Arsiv acilamadi: gzip verisi bozuk.');
        }

        return $out;
    }

    /**
     * Arşivi diske açar.
     *
     * $destination altına çıkılamaz: mutlak yol, ".." ve sembolik bağ
     * hedefleri reddedilir. Arşiv güvenilir bir kaynaktan gelse de bu
     * denetim kalır — güncelleme, yazma yetkisinin en geniş olduğu andır.
     *
     * @return int açılan dosya sayısı
     */
    public static function extract(string $tar, string $destination): int
    {
        $destination = rtrim($destination, '/\\');
        $offset      = 0;
        $length      = strlen($tar);
        $count       = 0;
        $longName    = null;

        while ($offset + self::BLOCK <= $length) {
            $block   = substr($tar, $offset, self::BLOCK);
            $offset += self::BLOCK;

            /* Boş blok: arşivin sonu. */
            if (trim($block, "\0") === '') {
                break;
            }

            $name   = rtrim(substr($block, 0, 100), "\0");
            $size   = (int)octdec(trim(rtrim(substr($block, 124, 12), "\0")) ?: '0');
            $type   = substr($block, 156, 1);
            $prefix = rtrim(substr($block, 345, 155), "\0");

            if ($prefix !== '') {
                $name = $prefix . '/' . $name;
            }

            $data    = substr($tar, $offset, $size);
            $offset += (int)(ceil($size / self::BLOCK) * self::BLOCK);

            /* GNU uzun ad kaydı: adı bir sonraki girdiye taşır. */
            if ($type === 'L') {
                $longName = rtrim($data, "\0");
                continue;
            }

            if ($longName !== null) {
                $name     = $longName;
                $longName = null;
            }

            /* Dizinler ve dosya olmayan girdiler atlanır; dizinler
               dosya yazılırken zaten oluşturuluyor. */
            if ($type !== '0' && $type !== "\0" && $type !== '') {
                continue;
            }

            $target = self::safePath($destination, $name);
            $dir    = dirname($target);

            if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
                throw new RuntimeException("Dizin olusturulamadi: {$dir}");
            }

            if (file_put_contents($target, $data) === false) {
                throw new RuntimeException("Dosya yazilamadi: {$target}");
            }

            $count++;
        }

        return $count;
    }

    /** Arşiv içindeki adı hedefin altında güvenli bir yola çevirir. */
    private static function safePath(string $destination, string $name): string
    {
        $name = str_replace('\\', '/', $name);

        if ($name === '' || $name[0] === '/' || preg_match('#^[A-Za-z]:#', $name)) {
            throw new RuntimeException("Arsivde mutlak yol var: {$name}");
        }

        $parts = [];
        foreach (explode('/', $name) as $part) {
            if ($part === '' || $part === '.') {
                continue;
            }
            if ($part === '..') {
                throw new RuntimeException("Arsivde dizin disina cikan yol var: {$name}");
            }
            $parts[] = $part;
        }

        if ($parts === []) {
            throw new RuntimeException("Arsivde gecersiz yol: {$name}");
        }

        return $destination . '/' . implode('/', $parts);
    }

    /* ---------------------------------------------------------------- */

    /** Bir dizini özyinelemeli tarar: arşiv yolu => disk yolu. */
    public static function scan(string $root, string $prefix): array
    {
        $root  = rtrim($root, '/\\');
        $found = [];

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            if (!$item->isFile()) {
                continue;
            }

            $relative = str_replace('\\', '/', substr($item->getPathname(), strlen($root) + 1));
            $found[$prefix . '/' . $relative] = $item->getPathname();
        }

        /* Sıra, arşivin ve MANIFEST'in yeniden üretilebilir olması için sabit. */
        ksort($found);

        return $found;
    }
}

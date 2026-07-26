<?php

/**
 * VNL Şablon Derleyicisi
 *
 * View dosyalarındaki süslü parantez söz dizimini düz PHP'ye çevirir ve derlenmiş
 * çıktıyı önbelleğe alır. Şablon söz dizimi içermeyen dosyalara hiç dokunulmaz;
 * kaynak dosyanın kendi yolu döner, davranış birebir aynı kalır.
 *
 *   {{ $ad }}                → kaçışlı çıktı
 *   {{ lang('ERR_PERM') }}   → kaçışlı çıktı
 *   {!! $html !!}            → ham çıktı, kaçışlanmaz
 *   {{-- yorum --}}          → çıktıya hiç girmez
 *   @{{ ... }}               → literal {{ ... }} kalır (JS şablon motorları için)
 *
 * Derlenmiş dosyada satır numaraları kaynakla birebir örtüşür; hata sayfası
 * doğru satırı gösterir.
 */
class VnlTemplate
{
    /** Derlenmiş dosyaların yazıldığı dizin (document root'a göre). */
    private const CACHE_DIR = 'files/cache/views';

    /** Kaçış dizisi olarak kullanılan, şablonlarda geçmesi mümkün olmayan işaretçiler. */
    private const MARK_OPEN = '@@VNL_LITERAL_OPEN@@';
    private const MARK_RAW  = '@@VNL_LITERAL_RAW@@';

    /**
     * Şablonu (gerekiyorsa) derler ve require edilecek yolu döner.
     * Şablon söz dizimi yoksa kaynak dosyanın kendi yolu döner.
     */
    public static function resolve(string $path): string
    {
        if (!is_file($path)) {
            return $path;   // eksik dosya: require kendi hatasını üretsin
        }

        $compiled = self::cachePath($path);

        /* Güncel derleme varsa doğrudan kullan — kaynak dosya hiç okunmaz. */
        if (is_file($compiled) && filemtime($compiled) >= filemtime($path)) {
            return $compiled;
        }

        $source = @file_get_contents($path);
        if ($source === false) {
            return $path;
        }

        /* Şablon söz dizimi yoksa derlemeye hiç girme. */
        if (!self::hasSyntax($source)) {
            return $path;
        }

        self::write($compiled, self::compile($source));

        return $compiled;
    }

    /** Dosya şablon söz dizimi içeriyor mu? */
    public static function hasSyntax(string $source): bool
    {
        return str_contains($source, '{{') || str_contains($source, '{!!');
    }

    /** Şablon söz dizimini düz PHP'ye çevirir. Satır sayısı korunur. */
    public static function compile(string $source): string
    {
        /* @{{ ve @{!! kaçışlarını derleme dışında tut. */
        $source = str_replace(['@{{', '@{!!'], [self::MARK_OPEN, self::MARK_RAW], $source);

        /* {{-- yorum --}} → satır sayısı korunarak silinir. */
        $source = preg_replace_callback('/\{\{--.*?--\}\}/s', static function (array $m): string {
            return str_repeat("\n", substr_count($m[0], "\n"));
        }, $source);

        /* {!! ifade !!} → ham çıktı. */
        $source = preg_replace('/\{!!\s*(.+?)\s*!!\}/s', '<?= $1 ?>', $source);

        /* {{ ifade }} → kaçışlı çıktı. */
        $source = preg_replace('/\{\{\s*(.+?)\s*\}\}/s', '<?= vnl_e($1) ?>', $source);

        return str_replace([self::MARK_OPEN, self::MARK_RAW], ['{{', '{!!'], $source);
    }

    /** Derlenmiş dosyaların tamamını siler; dağıtım sonrası temizlik için. */
    public static function clearCache(): int
    {
        $removed = 0;

        foreach (glob(self::cacheDir() . '/*.php') ?: [] as $file) {
            if (@unlink($file)) {
                $removed++;
            }
        }

        return $removed;
    }

    /* ---------------------------------------------------------------- */

    private static function cacheDir(): string
    {
        $root = rtrim(GetDocumentRoot(), '/\\');

        if ($root === '') {
            $root = rtrim(getPath(), '/\\');
        }

        return $root . '/' . self::CACHE_DIR;
    }

    /** Okunabilir ad + yol özeti: hata izinde hangi şablon olduğu anlaşılsın. */
    private static function cachePath(string $path): string
    {
        $relative = str_replace([rtrim(GetDocumentRoot(), '/\\'), '\\'], ['', '/'], $path);
        $slug     = preg_replace('/[^A-Za-z0-9]+/', '_', trim($relative, '/'));
        $slug     = trim(substr($slug, -80), '_');

        return self::cacheDir() . '/' . $slug . '-' . substr(md5($path), 0, 8) . '.php';
    }

    /** Yarım yazılmış dosyanın require edilmemesi için geçici dosya üzerinden yazar. */
    private static function write(string $target, string $contents): void
    {
        $dir = dirname($target);

        if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new RuntimeException(
                "VNL şablon önbellek dizini oluşturulamadı: {$dir} — bu dizin web sunucusu tarafından yazılabilir olmalı."
            );
        }

        $tmp = $target . '.' . getmypid() . '.tmp';

        if (@file_put_contents($tmp, $contents, LOCK_EX) === false) {
            @unlink($tmp);
            throw new RuntimeException("VNL şablon önbelleğine yazılamadı: {$dir} yazılabilir değil.");
        }

        if (!@rename($tmp, $target)) {
            /* Windows'ta hedef dosya varsa rename başarısız olabilir. */
            @unlink($target);

            if (!@rename($tmp, $target)) {
                @unlink($tmp);
                throw new RuntimeException("VNL şablon önbelleği güncellenemedi: {$target}");
            }
        }
    }
}

/**
 * {{ }} çıktısının kaçış fonksiyonu.
 *
 * null boş string olur, diziler JSON olarak basılır, geri kalan her şey
 * HTML'e güvenli biçimde kaçışlanır.
 */
function vnl_e($value): string
{
    if ($value === null) {
        return '';
    }

    if (is_array($value)) {
        $value = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

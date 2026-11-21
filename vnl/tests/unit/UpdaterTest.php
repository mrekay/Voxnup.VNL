<?php

/**
 * Güncelleyicinin bütünlük denetimi (vnl/tools/update.php) ve arşiv
 * açıcısının yol güvenliği (vnl/tools/tar.php).
 */
final class UpdaterTest extends VnlTestCase
{
    private string $dir = '';

    public function setUp(): void
    {
        require_once VNL_PATH . '/tools/tar.php';

        if (!class_exists('VnlUpdate', false)) {
            /* update.php sınıfı tanımlar ve komut fonksiyonunu döndürür;
               dönen fonksiyon burada çağrılmaz. */
            require VNL_PATH . '/tools/update.php';
        }

        $this->dir = sys_get_temp_dir() . '/vnl-upd-' . bin2hex(random_bytes(4));
        mkdir($this->dir . '/sub', 0700, true);
    }

    public function tearDown(): void
    {
        if ($this->dir !== '' && is_dir($this->dir)) {
            (new VnlUpdate())->removeTree($this->dir);
        }
    }

    private function writeTree(): array
    {
        file_put_contents($this->dir . '/a.php', "<?php\n// a\n");
        file_put_contents($this->dir . '/sub/b.txt', "b\n");

        return [
            'a.php'     => hash('sha256', "<?php\n// a\n"),
            'sub/b.txt' => hash('sha256', "b\n"),
        ];
    }

    public function testManifestParsing(): void
    {
        $file = $this->dir . '/MANIFEST';
        file_put_contents($file, "abc123  a.php\n\n  def456  sub/b c.txt  \n");

        $this->assertSame(['a.php' => 'abc123', 'sub/b c.txt' => 'def456'], (new VnlUpdate())->manifest($file));
        $this->assertNull((new VnlUpdate())->manifest($this->dir . '/yok'));
    }

    public function testCompareCleanTree(): void
    {
        $manifest = $this->writeTree();

        $this->assertSame(['changed' => [], 'missing' => [], 'extra' => []], (new VnlUpdate())->compare($this->dir, $manifest));
    }

    public function testCompareIgnoresCrlfCheckout(): void
    {
        $manifest = $this->writeTree();
        file_put_contents($this->dir . '/a.php', "<?php\r\n// a\r\n");

        $this->assertSame([], (new VnlUpdate())->compare($this->dir, $manifest)['changed']);
    }

    public function testCompareDetectsChangedMissingExtra(): void
    {
        $manifest = $this->writeTree();
        file_put_contents($this->dir . '/a.php', "<?php\n// kurcalandi\n");
        unlink($this->dir . '/sub/b.txt');
        file_put_contents($this->dir . '/sub/yeni.php', '<?php');

        $result = (new VnlUpdate())->compare($this->dir, $manifest);

        $this->assertSame(['a.php'], $result['changed']);
        $this->assertSame(['sub/b.txt'], $result['missing']);
        $this->assertSame(['sub/yeni.php'], $result['extra']);
    }

    public function testTarRoundTrip(): void
    {
        $this->writeTree();
        $blob = VnlTar::gzip(VnlTar::create(VnlTar::scan($this->dir, 'vnl')));

        $out = $this->dir . '/out';
        $count = VnlTar::extract(VnlTar::gunzip($blob), $out);

        $this->assertSame(2, $count);
        $this->assertSame("b\n", file_get_contents($out . '/vnl/sub/b.txt'));
    }

    public function testTarRejectsPathTraversal(): void
    {
        foreach (['../kacis.php', 'vnl/../../kacis.php', '/etc/kacis', 'C:/kacis.php', '..\\kacis.php'] as $name) {
            $tar = self::tarWith($name, 'x');

            $this->assertThrows(
                fn() => VnlTar::extract($tar, $this->dir . '/out'),
                RuntimeException::class,
                'reddedilmeliydi: ' . $name
            );
        }

        $this->assertFalse(is_file(dirname($this->dir) . '/kacis.php'));
    }

    /** Tek girdili ham ustar (VnlTar::create diskten okur; burada ad serbest olmalı). */
    private static function tarWith(string $name, string $data): string
    {
        $header = str_pad($name, 100, "\0")
            . str_pad('0000644', 8, "\0")
            . str_pad('0000000', 8, "\0")
            . str_pad('0000000', 8, "\0")
            . str_pad(sprintf('%011o', strlen($data)), 12, "\0")
            . str_pad(sprintf('%011o', time()), 12, "\0")
            . str_repeat(' ', 8)
            . '0'
            . str_repeat("\0", 100)
            . "ustar\0" . '00'
            . str_repeat("\0", 247);

        $sum    = array_sum(array_map('ord', str_split($header)));
        $header = substr_replace($header, str_pad(sprintf('%06o', $sum), 7, "\0") . ' ', 148, 8);

        return $header . str_pad($data, 512, "\0") . str_repeat("\0", 1024);
    }
}

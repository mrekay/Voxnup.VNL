<?php

/**
 * İmzalı güncelleme (4.0.0): vnl/tools/signature.php ve VnlUpdate'in
 * authenticate / unpack / apply adımları.
 */
final class UpdateSignatureTest extends VnlTestCase
{
    private string $dir = '';

    public function setUp(): void
    {
        require_once VNL_PATH . '/tools/tar.php';
        require_once VNL_PATH . '/tools/signature.php';

        if (!class_exists('VnlUpdate', false)) {
            require VNL_PATH . '/tools/update.php';
        }

        if (!VnlSignature::available()) {
            $this->skip('sodium yok');
        }

        $this->dir = sys_get_temp_dir() . '/vnl-sig-' . bin2hex(random_bytes(4));
        mkdir($this->dir, 0700, true);
    }

    public function tearDown(): void
    {
        if ($this->dir !== '' && is_dir($this->dir)) {
            (new VnlUpdate())->removeTree($this->dir);
        }
    }

    /** @return array{0:array,1:array<string,string>} anahtar, KEYS */
    private function key(): array
    {
        $key = VnlSignature::generate();

        return [$key, VnlSignature::parseKeys("# yorum\n" . $key['line'] . " not\n")];
    }

    /* ---- imza ---- */

    public function testGeneratedLineIsTrusted(): void
    {
        [$key, $keys] = $this->key();

        $this->assertSame([$key['id']], array_keys($keys));
        $this->assertSame($key['id'], VnlSignature::secretKey($key['secret'])['id']);
    }

    public function testKeysLineWithWrongIdIsIgnored(): void
    {
        $key  = VnlSignature::generate();
        $fake = 'ed25519 0123456789abcdef ' . $key['public'];

        $this->assertSame([], VnlSignature::parseKeys($fake));
    }

    public function testSixtyFourByteSecretGivesSameKey(): void
    {
        $key  = VnlSignature::generate();
        $full = VnlSignature::secretKey($key['secret'])['secret'];

        $this->assertSame($key['id'], VnlSignature::secretKey(base64_encode($full))['id']);
    }

    public function testSignVerifyRoundTrip(): void
    {
        [$key, $keys] = $this->key();
        $sig = VnlSignature::sign('arsiv-baytlari', $key['secret']);

        $this->assertSame($key['id'], VnlSignature::verify('arsiv-baytlari', $sig, $keys));
    }

    public function testTamperedDataIsRejected(): void
    {
        [$key, $keys] = $this->key();
        $sig = VnlSignature::sign('arsiv-baytlari', $key['secret']);

        $this->assertThrows(fn() => VnlSignature::verify('arsiv-baytlarI', $sig, $keys), VnlSignatureInvalid::class);
    }

    public function testUnknownKeyIsRejected(): void
    {
        [, $keys] = $this->key();
        $other    = VnlSignature::generate();
        $sig      = VnlSignature::sign('x', $other['secret']);

        $this->assertThrows(fn() => VnlSignature::verify('x', $sig, $keys), VnlSignatureInvalid::class);
    }

    /* ---- authenticate ---- */

    public function testValidSignatureAuthenticates(): void
    {
        [$key, $keys] = $this->key();

        $res = (new VnlUpdate())->authenticate('blob', VnlSignature::sign('blob', $key['secret']), $keys, false);

        $this->assertTrue($res['verified']);
    }

    public function testUnverifiableStopsWithoutInsecure(): void
    {
        [$key, $keys] = $this->key();
        $sig = VnlSignature::sign('blob', $key['secret']);
        $u   = new VnlUpdate();

        $this->assertThrows(fn() => $u->authenticate('blob', null, $keys, false), RuntimeException::class, 'imza yok');
        $this->assertThrows(fn() => $u->authenticate('blob', $sig, $keys, false, false), RuntimeException::class, 'sodium yok');
        $this->assertThrows(fn() => $u->authenticate('blob', $sig, [], false), RuntimeException::class, 'KEYS bos');
    }

    public function testInsecureOnlyBypassesUnverifiable(): void
    {
        [$key, $keys] = $this->key();
        $sig = VnlSignature::sign('blob', $key['secret']);
        $u   = new VnlUpdate();

        $this->assertFalse($u->authenticate('blob', null, $keys, true)['verified']);
        $this->assertFalse($u->authenticate('blob', $sig, $keys, true, false)['verified']);
        $this->assertFalse($u->authenticate('blob', $sig, [], true)['verified']);

        /* Geçersiz imza --insecure ile de geçmez. */
        $this->assertThrows(fn() => $u->authenticate('kurcalanmis', $sig, $keys, true), VnlSignatureInvalid::class);
    }

    /* ---- unpack ---- */

    /** vnl/ ağacından arşiv; $manifest false ise MANIFEST yazılmaz, $extra MANIFEST'ten sonra eklenir. */
    private function archive(bool $manifest = true, array $extra = []): string
    {
        $src = $this->dir . '/src-' . bin2hex(random_bytes(3));
        mkdir($src . '/classes', 0700, true);
        file_put_contents($src . '/VERSION', "4.0.0\n");
        file_put_contents($src . '/classes/A.php', "<?php\n");

        if ($manifest) {
            $lines = [];
            foreach (['VERSION', 'classes/A.php'] as $rel) {
                $lines[] = hash('sha256', (string)file_get_contents($src . '/' . $rel)) . '  ' . $rel;
            }
            file_put_contents($src . '/MANIFEST', implode("\n", $lines) . "\n");
        }

        foreach ($extra as $rel => $content) {
            file_put_contents($src . '/' . $rel, $content);
        }

        return VnlTar::gzip(VnlTar::create(VnlTar::scan($src, 'vnl')));
    }

    public function testUnpackAcceptsCompleteArchive(): void
    {
        $res = (new VnlUpdate())->unpack($this->archive(), $this->dir . '/t1');

        $this->assertSame('4.0.0', $res['version']);
        $this->assertSame(2, $res['files']);
    }

    public function testUnpackRejectsArchiveWithoutManifest(): void
    {
        $blob = $this->archive(false);

        $this->assertThrows(fn() => (new VnlUpdate())->unpack($blob, $this->dir . '/t2'), RuntimeException::class);
    }

    public function testUnpackRejectsFilesOutsideManifest(): void
    {
        $blob = $this->archive(true, ['arka-kapi.php' => '<?php system($_GET["c"]);']);

        $this->assertThrows(fn() => (new VnlUpdate())->unpack($blob, $this->dir . '/t3'), RuntimeException::class);
    }

    /* ---- apply ---- */

    public function testApplyRestoresBackupWhenSyncFails(): void
    {
        $target = $this->dir . '/core';
        mkdir($target, 0700, true);
        file_put_contents($target . '/VERSION', "3.5.1\n");
        file_put_contents($target . '/eski.php', "<?php // eski\n");

        $incoming = $this->dir . '/incoming';
        mkdir($incoming, 0700, true);
        file_put_contents($incoming . '/VERSION', "4.0.0\n");

        $u = new VnlUpdate();
        $half = function (string $from, string $to) {
            /* Yarıda kalan uygulama: bir dosya yazıldı, biri silindi, sonra hata. */
            file_put_contents($to . '/VERSION', "4.0.0\n");
            unlink($to . '/eski.php');
            throw new RuntimeException('disk dolu');
        };

        $e = null;
        try {
            $u->apply($incoming, $target, $this->dir . '/bak', $half);
        } catch (RuntimeException $caught) {
            $e = $caught;
        }

        $this->assertNotNull($e, 'hata yukari iletilmeli');
        $this->assertContains('geri yuklendi', $e->getMessage());
        $this->assertSame("3.5.1\n", file_get_contents($target . '/VERSION'));
        $this->assertSame("<?php // eski\n", file_get_contents($target . '/eski.php'));
    }

    public function testApplySyncsOnSuccess(): void
    {
        $target = $this->dir . '/core2';
        mkdir($target, 0700, true);
        file_put_contents($target . '/VERSION', "3.5.1\n");
        file_put_contents($target . '/kaldirilan.php', '<?php');

        $incoming = $this->dir . '/incoming2';
        mkdir($incoming, 0700, true);
        file_put_contents($incoming . '/VERSION', "4.0.0\n");

        (new VnlUpdate())->apply($incoming, $target, $this->dir . '/bak2');

        $this->assertSame("4.0.0\n", file_get_contents($target . '/VERSION'));
        $this->assertFalse(is_file($target . '/kaldirilan.php'));
        $this->assertSame("3.5.1\n", file_get_contents($this->dir . '/bak2/VERSION'));
    }
}

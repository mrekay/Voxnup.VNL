<?php

/**
 * Ana sürüm geçişi (4.0.0): güncelleyici kendi ana sürümünde kalır, yeni
 * ana sürüme yalnız --major ile geçilir; yetersiz PHP'ye kurulmaz.
 */
final class UpdateMajorTest extends VnlTestCase
{
    public function setUp(): void
    {
        require_once VNL_PATH . '/tools/tar.php';
        if (!class_exists('VnlUpdate', false)) {
            require VNL_PATH . '/tools/update.php';
        }
    }

    private function list(): array
    {
        return array_map(fn($t) => ['tag_name' => $t], ['v3.5.1', 'v3.5.3', 'v4.0.0', 'v3.5.2', 'v4.1.0', 'v3.10.0']);
    }

    public function testPicksNewestInSameMajor(): void
    {
        $this->assertSame('v3.10.0', VnlUpdate::pickRelease($this->list(), 3)['tag_name'], 'surum karsilastirmasi metin degil');
        $this->assertSame('v4.1.0', VnlUpdate::pickRelease($this->list(), 4)['tag_name']);
        $this->assertNull(VnlUpdate::pickRelease($this->list(), 5));
    }

    public function testReportsHigherMajorSeparately(): void
    {
        $this->assertSame('4.1.0', VnlUpdate::newestHigherMajor($this->list(), 3));
        $this->assertNull(VnlUpdate::newestHigherMajor($this->list(), 4));
    }

    public function testPhpRequirementOfIncomingCore(): void
    {
        $dir = sys_get_temp_dir() . '/vnl-php-' . bin2hex(random_bytes(3));
        mkdir($dir);
        file_put_contents($dir . '/composer.json', json_encode(['require' => ['php' => '>=8.3']]));

        VnlUpdate::checkPhp($dir, '8.3.0');
        VnlUpdate::checkPhp($dir, '8.5.1');
        $this->assertThrows(fn() => VnlUpdate::checkPhp($dir, '8.2.30'), RuntimeException::class);

        unlink($dir . '/composer.json');
        VnlUpdate::checkPhp($dir, '8.2.30');   // bilgi yoksa engellenmez
        rmdir($dir);
    }
}

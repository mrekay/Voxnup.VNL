<?php

/**
 * Yükleme adı denetimi (3.5.2): FileManagerModule::IsSafeUploadName.
 * Bu kural $Allows = 'all' dahil her yüklemede uygulanır.
 */
final class UploadNameTest extends VnlTestCase
{
    public function testOrdinaryFilesAreAccepted(): void
    {
        foreach (['rapor.pdf', 'foto.JPG', 'e-fatura.xml', 'tablo.v2.xlsx', 'arşiv.tar.gz'] as $name) {
            $this->assertTrue(FileManagerModule::IsSafeUploadName($name), $name);
        }
    }

    public function testExecutableAndScriptNamesAreRejected(): void
    {
        $bad = [
            'a.php', 'a.PHP', 'a.pHp7', 'a.php5', 'a.phtml', 'a.phar', 'a.pht',
            'fatura.php.jpg',          // çift uzantı
            'x.svg', 'x.html', 'x.htm', 'x.shtml', 'x.cgi',
            '.htaccess', '.user.ini', '.env',
            'uzantisiz', 'a.jpg.', '../../a.php', 'a\\b.php', "a.php\0.jpg",
        ];

        foreach ($bad as $name) {
            $this->assertFalse(FileManagerModule::IsSafeUploadName($name), json_encode($name));
        }
    }
}

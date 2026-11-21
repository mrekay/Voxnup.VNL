<?php

/**
 * FileRequest::move (4.0.0): güvensiz hedef adı, dosya hiç bakılmadan
 * reddedilir — uzantı listesini unutan çağıranın ikinci savunma hattı.
 */
final class FileRequestMoveTest extends VnlTestCase
{
    public function testUnsafeDestinationIsRefused(): void
    {
        /* Gerçek bir yükleme test sürecinde üretilemez (is_uploaded_file);
           korumanın çalıştığı günlüğe yazdığı satırdan anlaşılır. */
        $log = tempnam(sys_get_temp_dir(), 'vnllog');
        $old = ini_set('error_log', $log);
        $req = new FileRequest();

        try {
            foreach (['/tmp/x/kabuk.php', '/tmp/x/a.php.jpg', '/tmp/x/.htaccess', 'C:\up\b.phtml', '/tmp/x/uzantisiz'] as $dest) {
                $this->assertFalse($req->move('avatar', $dest), $dest);
            }
        } finally {
            ini_set('error_log', (string)$old);
        }

        $this->assertSame(5, substr_count((string)file_get_contents($log), 'Güvensiz hedef adı reddedildi'));
        @unlink($log);
    }

    public function testSafeDestinationStillNeedsARealUpload(): void
    {
        /* Ad güvenli; ama yüklenmiş dosya yok → yine false (davranış aynı). */
        $this->assertFalse((new FileRequest())->move('avatar', sys_get_temp_dir() . '/resim.jpg'));
    }
}

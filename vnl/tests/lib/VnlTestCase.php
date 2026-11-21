<?php
/*!
 * ============================================================
 *  Voxnup Not Laravel (VNL) — Test Temeli
 *  Composer'sız, küçük bir doğrulama API'si.
 *  Authors: Voxnup Authors
 * ============================================================
 *
 * Test dosyası `*Test.php` adını taşır, VnlTestCase'ten türeyen bir sınıf
 * tanımlar; `test` ile başlayan public metotlar sırayla koşar:
 *
 *   final class CsrfTest extends VnlTestCase
 *   {
 *       public function testTokenIs64Hex(): void
 *       {
 *           $this->assertSame(64, strlen(vnl_csrf_token()));
 *       }
 *   }
 *
 * Her dosya ayrı bir PHP sürecinde, çekirdek vnl_cli_boot() ile açılmış
 * olarak çalışır. Bir test exit() ile biterse yalnız o dosya düşer;
 * çalıştırıcı hangi testte düştüğünü raporlar.
 */

final class VnlAssertionFailed extends Exception {}

final class VnlTestSkipped extends Exception {}

abstract class VnlTestCase
{
    /** Her testten önce. */
    public function setUp(): void {}

    /** Her testten sonra (test başarısız olsa da). */
    public function tearDown(): void {}

    /* ------------------------------------------------------------ *
     *  Doğrulamalar
     * ------------------------------------------------------------ */

    protected function assertSame($expected, $actual, string $message = ''): void
    {
        if ($expected !== $actual) {
            $this->fail(($message !== '' ? $message . "\n" : '')
                . 'beklenen: ' . self::export($expected) . "\n"
                . 'gelen:    ' . self::export($actual));
        }
    }

    protected function assertNotSame($unexpected, $actual, string $message = ''): void
    {
        if ($unexpected === $actual) {
            $this->fail(($message !== '' ? $message . "\n" : '') . 'beklenmeyen değer: ' . self::export($actual));
        }
    }

    protected function assertTrue($actual, string $message = ''): void
    {
        $this->assertSame(true, $actual, $message);
    }

    protected function assertFalse($actual, string $message = ''): void
    {
        $this->assertSame(false, $actual, $message);
    }

    protected function assertNull($actual, string $message = ''): void
    {
        $this->assertSame(null, $actual, $message);
    }

    protected function assertNotNull($actual, string $message = ''): void
    {
        if ($actual === null) {
            $this->fail($message !== '' ? $message : 'null olmamalıydı');
        }
    }

    /** Dizgede alt dizge ya da dizide değer (katı karşılaştırma). */
    protected function assertContains($needle, $haystack, string $message = ''): void
    {
        $found = is_array($haystack)
            ? in_array($needle, $haystack, true)
            : str_contains((string)$haystack, (string)$needle);

        if (!$found) {
            $this->fail(($message !== '' ? $message . "\n" : '')
                . self::export($needle) . ' bulunamadı: ' . self::export($haystack));
        }
    }

    protected function assertNotContains($needle, $haystack, string $message = ''): void
    {
        $found = is_array($haystack)
            ? in_array($needle, $haystack, true)
            : str_contains((string)$haystack, (string)$needle);

        if ($found) {
            $this->fail(($message !== '' ? $message . "\n" : '')
                . self::export($needle) . ' bulunmamalıydı: ' . self::export($haystack));
        }
    }

    /** $fn bir istisna fırlatmalı; fırlatılanı döner. */
    protected function assertThrows(callable $fn, string $class = Throwable::class, string $message = ''): Throwable
    {
        try {
            $fn();
        } catch (VnlAssertionFailed | VnlTestSkipped $e) {
            throw $e;
        } catch (Throwable $e) {
            if (!$e instanceof $class) {
                $this->fail(($message !== '' ? $message . "\n" : '')
                    . $class . ' bekleniyordu, ' . get_class($e) . ' geldi: ' . $e->getMessage());
            }

            return $e;
        }

        $this->fail(($message !== '' ? $message . "\n" : '') . $class . ' fırlatılmadı');
    }

    protected function fail(string $message): never
    {
        throw new VnlAssertionFailed($message);
    }

    protected function skip(string $reason): never
    {
        throw new VnlTestSkipped($reason);
    }

    /* ------------------------------------------------------------ *
     *  Ortam
     * ------------------------------------------------------------ */

    /**
     * Veritabanı bağlantısı yoksa testi atlar. db testleri ilk satırda çağırır.
     * Bağlantı bir kez denenir; sonuç süreç boyunca saklanır.
     */
    protected function requireDatabase(): void
    {
        static $error = null;

        if ($error === null) {
            try {
                \Illuminate\Database\Capsule\Manager::connection()->getPdo();
                $error = '';
            } catch (Throwable $e) {
                $error = $e->getMessage();
            }
        }

        if ($error !== '') {
            $this->skip('veritabanı yok: ' . $error);
        }
    }

    public static function export($value): string
    {
        if (is_string($value)) {
            $value = strlen($value) > 400 ? substr($value, 0, 400) . '…' : $value;

            return '"' . addcslashes($value, "\0..\37\"\\") . '"';
        }

        $out = var_export($value, true);

        return strlen($out) > 400 ? substr($out, 0, 400) . '…' : $out;
    }
}

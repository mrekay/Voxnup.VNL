<?php

/**
 * Proje günlük kancası (4.1.0): vnl_project_log_exception($e, $ref, $channel),
 * vnl_log_exception() içinden.
 *
 * Kanca bu dosyada tanımlanır; her test dosyası kendi sürecinde koştuğu için
 * başka testlere sızmaz. Proje kancayı zaten tanımlıyorsa testler atlanır.
 * error_log geçici bir dosyaya yönlendirilir, çekirdeğin satırı oradan okunur.
 */

$GLOBALS['vnlLogTestOwnsHook'] = !function_exists('vnl_project_log_exception');

if ($GLOBALS['vnlLogTestOwnsHook']) {
    function vnl_project_log_exception(Throwable $e, string $ref, string $channel): void
    {
        $GLOBALS['vnlLogTestCalls'][] = [$e, $ref, $channel];
        $hook = $GLOBALS['vnlLogTestHook'] ?? null;

        if (is_callable($hook)) {
            $hook($e, $ref, $channel);
        }
    }
}

final class LogExceptionHookTest extends VnlTestCase
{
    private string $logFile = '';
    private string|false $savedLog = false;

    public function setUp(): void
    {
        if (!$GLOBALS['vnlLogTestOwnsHook']) {
            $this->skip('proje vnl_project_log_exception() tanımlıyor');
        }

        $GLOBALS['vnlLogTestHook']  = null;
        $GLOBALS['vnlLogTestCalls'] = [];

        $this->logFile  = (string)tempnam(sys_get_temp_dir(), 'vnllog');
        $this->savedLog = ini_get('error_log');
        ini_set('error_log', $this->logFile);
    }

    public function tearDown(): void
    {
        ini_set('error_log', (string)$this->savedLog);

        if ($this->logFile !== '' && is_file($this->logFile)) {
            @unlink($this->logFile);
        }
    }

    private function log(): string
    {
        return (string)file_get_contents($this->logFile);
    }

    public function testHookReceivesExceptionRefAndChannel(): void
    {
        $e   = new RuntimeException('patladi');
        $ref = vnl_log_exception($e, 'VNL][Queue');

        $this->assertSame(1, count($GLOBALS['vnlLogTestCalls']));
        [$got, $gotRef, $gotChannel] = $GLOBALS['vnlLogTestCalls'][0];

        $this->assertSame($e, $got);
        $this->assertSame($ref, $gotRef);
        $this->assertSame('VNL][Queue', $gotChannel);
        $this->assertTrue((bool)preg_match('/^[0-9a-f]{12}$/', $ref), $ref);
    }

    public function testCoreLogIsWrittenEvenWithHook(): void
    {
        $ref = vnl_log_exception(new RuntimeException('cekirdek-satiri'));

        $this->assertTrue(str_contains($this->log(), "[VNL][{$ref}] RuntimeException: cekirdek-satiri"), $this->log());
    }

    public function testThrowingHookDoesNotEscapeAndIsLogged(): void
    {
        $GLOBALS['vnlLogTestHook'] = function () {
            throw new LogicException('kanca-bozuk');
        };

        $ref = vnl_log_exception(new RuntimeException('asil-hata'));

        $this->assertTrue(str_contains($this->log(), "[VNL][{$ref}] vnl_project_log_exception() hata verdi: LogicException: kanca-bozuk"), $this->log());
    }

    public function testHookIsNotReenteredFromItself(): void
    {
        /* Kanca kendi içinde vnl_log_exception çağırırsa sonsuz döngü olmamalı. */
        $GLOBALS['vnlLogTestHook'] = function () {
            vnl_log_exception(new RuntimeException('ic-hata'));
        };

        vnl_log_exception(new RuntimeException('dis-hata'));

        $this->assertSame(1, count($GLOBALS['vnlLogTestCalls']));
        $this->assertTrue(str_contains($this->log(), 'ic-hata'), 'iç hata yine de çekirdek günlüğüne yazılmalı');

        /* Sonraki çağrıda kanca yeniden çalışır (bayrak sıfırlanmış olmalı). */
        $GLOBALS['vnlLogTestHook'] = null;
        vnl_log_exception(new RuntimeException('sonraki'));
        $this->assertSame(2, count($GLOBALS['vnlLogTestCalls']));
    }
}

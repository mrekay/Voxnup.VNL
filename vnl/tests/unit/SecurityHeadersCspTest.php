<?php

/**
 * 4.0.0: Content-Security-Policy başlıkları (vnl_security_headers).
 *
 * Varsayılan politika bilerek dar: yalnız object-src ve base-uri. script-src
 * yazılırsa tema kırılır, frame-ancestors yazılırsa X-Frame-Options'ı kapatan
 * proje sessizce kilitli kalır — ikisinin de bir gün "sıkılaştırma" diye
 * eklenmemesi için burada sınanıyor. Gerekçe bootstrap.php'deki yorumda.
 */
final class SecurityHeadersCspTest extends VnlTestCase
{
    private array $set = [];

    private function env(string $key, string $value): void
    {
        $this->set[] = $key;
        putenv($key . '=' . $value);
    }

    public function tearDown(): void
    {
        foreach ($this->set as $key) {
            putenv($key);
        }
        $this->set = [];
    }

    public function testDefaultCspClosesPluginsAndBaseTag(): void
    {
        $h = vnl_security_headers(false);

        $this->assertSame("object-src 'none'; base-uri 'self'", $h['Content-Security-Policy']);
        $this->assertSame($h['Content-Security-Policy'], vnl_security_headers(true)['Content-Security-Policy'], 'TLS fark etmez');
    }

    public function testDefaultCspLeavesScriptsAndFramingAlone(): void
    {
        $csp = vnl_security_headers(false)['Content-Security-Policy'];

        foreach (['default-src', 'script-src', 'style-src', 'frame-ancestors'] as $directive) {
            $this->assertFalse(str_contains($csp, $directive), $directive . ' varsayılanda olmamalı');
        }
    }

    public function testReportOnlyIsOffByDefault(): void
    {
        $this->assertFalse(isset(vnl_security_headers(false)['Content-Security-Policy-Report-Only']));
    }

    public function testCspOverrideAndRemoval(): void
    {
        $this->env('VNL_HEADER_CSP', "default-src 'self'");
        $this->env('VNL_HEADER_CSP_REPORT_ONLY', "script-src 'self'");

        $h = vnl_security_headers(false);
        $this->assertSame("default-src 'self'", $h['Content-Security-Policy']);
        $this->assertSame("script-src 'self'", $h['Content-Security-Policy-Report-Only']);

        $this->env('VNL_HEADER_CSP', 'false');
        $this->assertFalse(isset(vnl_security_headers(false)['Content-Security-Policy']));
        $this->assertSame('nosniff', vnl_security_headers(false)['X-Content-Type-Options']);
    }

    public function testCspValueCannotInjectHeaders(): void
    {
        $this->env('VNL_HEADER_CSP', "object-src 'none'\r\nSet-Cookie: x=1");
        $this->env('VNL_HEADER_CSP_REPORT_ONLY', "script-src 'self'\nLocation: //evil");

        $h = vnl_security_headers(false);
        $this->assertNotContains("\n", $h['Content-Security-Policy']);
        $this->assertNotContains("\r", $h['Content-Security-Policy']);
        $this->assertNotContains("\n", $h['Content-Security-Policy-Report-Only']);
    }

    public function testMasterSwitchAlsoDropsCsp(): void
    {
        $this->env('VNL_HEADER_CSP_REPORT_ONLY', "script-src 'self'");
        $this->env('VNL_SECURITY_HEADERS', 'false');

        $this->assertSame([], vnl_security_headers(false));
    }
}

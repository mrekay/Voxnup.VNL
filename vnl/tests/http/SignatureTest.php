<?php

/**
 * VNL imzası (4.0.0): oturum çerezi VNLSESSID, X-Powered-By "VNL (Voxnup Not Laravel)".
 *
 * Proje .env'de VNL_SESSION_NAME / VNL_POWERED_BY'ı değiştirdiyse varsayılanı
 * sınamak anlamsızdır; o durumda test atlanır.
 */
final class SignatureTest extends VnlHttpTestCase
{
    private function overridden(string $key): bool
    {
        return class_exists('VnlEnv') && VnlEnv::has($key);
    }

    public function testSessionCookieIsNamedVnlsessid(): void
    {
        if ($this->overridden('VNL_SESSION_NAME') && VnlEnv::get('VNL_SESSION_NAME') !== 'VNLSESSID') {
            $this->skip('VNL_SESSION_NAME projede değiştirilmiş');
        }

        $res     = $this->get('/login');
        $cookies = implode("\n", $res->headers['set-cookie'] ?? []);

        $this->assertSame(1, preg_match('/^VNLSESSID=/m', $cookies), 'Set-Cookie: ' . $cookies);
        $this->assertSame(0, preg_match('/^PHPSESSID=/m', $cookies), 'PHPSESSID hâlâ yazılıyor');
    }

    public function testPoweredByIsVnlWithoutPhpVersion(): void
    {
        if ($this->overridden('VNL_POWERED_BY') && VnlEnv::get('VNL_POWERED_BY') !== 'VNL (Voxnup Not Laravel)') {
            $this->skip('VNL_POWERED_BY projede değiştirilmiş');
        }

        $res = $this->get('/login');

        $this->assertSame(['VNL (Voxnup Not Laravel)'], $res->headers['x-powered-by'] ?? []);
    }
}

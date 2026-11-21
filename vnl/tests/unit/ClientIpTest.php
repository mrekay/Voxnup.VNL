<?php

/**
 * İstemci adresi ve güvenilen vekiller (4.0.0: vnl_client_ip) ile denetim
 * kaydında maskelenen alanlar.
 */
final class ClientIpTest extends VnlTestCase
{
    public function tearDown(): void
    {
        putenv('VNL_TRUSTED_PROXIES');
        unset($_SERVER['HTTP_X_FORWARDED_FOR']);
    }

    private function request(string $remote, ?string $xff = null): string
    {
        $_SERVER['REMOTE_ADDR'] = $remote;
        if ($xff === null) {
            unset($_SERVER['HTTP_X_FORWARDED_FOR']);
        } else {
            $_SERVER['HTTP_X_FORWARDED_FOR'] = $xff;
        }

        return vnl_client_ip();
    }

    public function testHeaderIgnoredWithoutTrustedProxies(): void
    {
        $this->assertSame('203.0.113.9', $this->request('203.0.113.9', '1.2.3.4'));
    }

    public function testHeaderIgnoredWhenRemoteIsNotTrusted(): void
    {
        putenv('VNL_TRUSTED_PROXIES=10.0.0.0/8');

        $this->assertSame('203.0.113.9', $this->request('203.0.113.9', '1.2.3.4'));
    }

    public function testRightmostUntrustedHopIsClient(): void
    {
        putenv('VNL_TRUSTED_PROXIES=10.0.0.0/8, 192.168.1.5');

        /* İstemci "1.1.1.1" diye sahte bir sol kısım yazmış; vekiller sağa ekler. */
        $this->assertSame('198.51.100.7', $this->request('10.1.2.3', '1.1.1.1, 198.51.100.7, 192.168.1.5'));
        $this->assertSame('10.1.2.3', $this->request('10.1.2.3', 'cop, 10.9.9.9'), 'bozuk zincir');
    }

    public function testIpv6Cidr(): void
    {
        $this->assertTrue(vnl_ip_in_list('2001:db8::1', ['2001:db8::/32']));
        $this->assertFalse(vnl_ip_in_list('2001:db9::1', ['2001:db8::/32']));
        $this->assertTrue(vnl_ip_in_list('172.20.1.1', ['172.16.0.0/12']));
        $this->assertFalse(vnl_ip_in_list('172.32.0.1', ['172.16.0.0/12']));
        $this->assertFalse(vnl_ip_in_list('10.0.0.1', ['::/0']), 'aile karışmaz');
        $this->assertFalse(vnl_ip_in_list('10.0.0.1', ['10.0.0.0/99']), 'geçersiz önek');
    }

    public function testAuditScrubsTurkishAndHeaderNames(): void
    {
        $m = new ReflectionMethod(Audit::class, 'scrub');
        $out = $m->invoke(null, [
            'sifre' => 'a', 'yeni_parola' => 'b', 'otp_code' => 'c', 'api_key' => 'd',
            'Authorization' => 'e', 'cookie' => 'f', 'ad' => 'Ali',
        ]);

        foreach (['sifre', 'yeni_parola', 'otp_code', 'api_key', 'Authorization', 'cookie'] as $k) {
            $this->assertSame('[redacted]', $out[$k], $k);
        }
        $this->assertSame('Ali', $out['ad']);
    }
}

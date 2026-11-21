<?php

/**
 * Sayfa giriş kapısı (vnl/kernel.php): karar denetleyici ÇALIŞMADAN verilir.
 *
 * Test sunucusu VNL_PUBLIC_DOCS=false ile açılır; /docs/* girişe kapalıdır.
 */
final class LoginGateTest extends VnlHttpTestCase
{
    private function assertRedirectsToLogin(VnlTestResponse $res, string $path): void
    {
        $this->assertSame(302, $res->status, $path . ' → ' . $res->status);
        $this->assertSame(1, preg_match('~/login(\?|$)~', (string)$res->header('Location')), $path . ' → ' . $res->header('Location'));
    }

    public function testLoginPageIsPublicAndCarriesCsrfMeta(): void
    {
        $res = $this->get('/login');

        $this->assertSame(200, $res->status);
        $this->assertContains('<meta name="csrf-token"', $res->body);
    }

    public function testProtectedPagesRedirectToLogin(): void
    {
        foreach (['/', '/index', '/docs/overview', '/docs/security'] as $path) {
            $this->assertRedirectsToLogin($this->get($path), $path);
        }
    }

    /** Bilinmeyen adres de /login'e gider: 404 ile 302 farkı sayfaların varlığını ele verirdi. */
    public function testUnknownPageDoesNotLeakExistence(): void
    {
        foreach (['/yok-boyle-sayfa', '/docs/yok-boyle', '/stripe-*', '/a%2fb'] as $path) {
            $this->assertRedirectsToLogin($this->get($path), $path);
        }
    }

    public function testErrorPagesArePublicWhenRequestedDirectly(): void
    {
        foreach (['/401', '/403', '/404'] as $path) {
            $this->assertNotSame(302, $this->get($path)->status, $path);
        }
    }

    public function testAjaxGets401InsteadOfRedirect(): void
    {
        $res = $this->get('/index', ['X-Requested-With' => 'XMLHttpRequest']);

        $this->assertSame(401, $res->status);
        $this->assertSame('VNL_AUTH', $res->devcode());
    }

    /** Kapalı eklentinin denetleyicisi bulunamaz (eklenti etkinse sınama anlamsız). */
    public function testDisabledExtensionControllerIsUnreachable(): void
    {
        if (in_array('social-auth', vnl_enabled_extensions(), true)) {
            $this->skip('social-auth bu projede etkin');
        }

        $this->assertRedirectsToLogin($this->get('/oauth/google'), '/oauth/google');
    }

    public function testProtectedPageBodyIsNotSent(): void
    {
        $res = $this->get('/index');

        $this->assertNotContains('csrf-token', $res->body, 'korumalı sayfa yönlendirmeyle birlikte gövde göndermemeli');
    }
}

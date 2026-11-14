<?php

/**
 * Giriş ve yetki yardımcıları, herkese açık sayfa kaydı, girişten sonra
 * dönüş adresi (vnl/helpers/app.php).
 */
final class AccessTest extends VnlTestCase
{
    private $savedUser;

    public function setUp(): void
    {
        $this->savedUser = $GLOBALS['CurrentUser'] ?? null;
    }

    public function tearDown(): void
    {
        $GLOBALS['CurrentUser'] = $this->savedUser;
    }

    public function testAnonymousIsNotLoggedIn(): void
    {
        $this->assertFalse(vnl_logged_in());
        $this->assertNull(vnl_user());
    }

    public function testEmptyModelIsNotLoggedIn(): void
    {
        /* Nesne asla empty değildir: !empty($CurrentUser) bir giriş denetimi değil. */
        $GLOBALS['CurrentUser'] = new stdClass();
        $this->assertFalse(vnl_logged_in());

        $GLOBALS['CurrentUser']->id = 0;
        $this->assertFalse(vnl_logged_in());

        $GLOBALS['CurrentUser']->id = -3;
        $this->assertFalse(vnl_logged_in());
    }

    public function testPositiveIdIsLoggedIn(): void
    {
        $GLOBALS['CurrentUser'] = (object)['id' => 7];

        $this->assertTrue(vnl_logged_in());
        $this->assertSame(7, vnl_user()->id);
    }

    public function testEmptyPermissionCodeOpenForMenuClosedForGates(): void
    {
        $this->assertTrue(vnl_can(null));
        $this->assertTrue(vnl_can('  '));
        $this->assertFalse(vnl_can(null, 'view', true));
        $this->assertFalse(vnl_can('', 'edit', true));
    }

    public function testStrictGateDeniesAnonymous(): void
    {
        if (!function_exists('vnl_project_permission')) {
            $this->assertFalse(vnl_can('Users', 'view', true), 'kanca yokken katı kapı kapalı olmalı');

            return;
        }

        $this->assertFalse(vnl_can('Users', 'view', true));
        $this->assertFalse(vnl_can('Users', 'delete', true));
    }

    public function testPublicPageRegistry(): void
    {
        vnl_public_page('/vnl-test-sayfa/');
        vnl_public_page('vnl-test/alt');
        vnl_public_page('');
        vnl_public_page('/');

        $pages = vnl_public_pages();

        $this->assertContains('vnl-test-sayfa', $pages);
        $this->assertContains('vnl-test/alt', $pages);
        $this->assertNotContains('', $pages);
    }

    public function testSafeReturnUrlAcceptsSiteRelativePath(): void
    {
        $this->assertSame('/satis?yil=2026', vnl_safe_return_url('/satis?yil=2026'));
        $this->assertSame('/docs/overview#x', vnl_safe_return_url('/docs/overview#x'));
    }

    public function testSafeReturnUrlRejectsOpenRedirects(): void
    {
        $bad = [
            '//evil.test', '/\\evil.test', "/\t/evil.test", "/\n/evil.test",
            'https://evil.test', 'evil.test', '', ' /x',
            '/x/../login', '/%2e%2e/x', str_repeat('/a', 1001),
        ];

        foreach ($bad as $value) {
            $this->assertNull(vnl_safe_return_url($value), 'reddedilmeliydi: ' . self::export($value));
        }

        $this->assertNull(vnl_safe_return_url(['/x']));
        $this->assertNull(vnl_safe_return_url(null));
    }

    public function testSafeReturnUrlRejectsAuthFlowPages(): void
    {
        foreach (['/', '/login', '/LOGIN?x=1', '/logout', '/app/users/save', '/404'] as $value) {
            $this->assertNull(vnl_safe_return_url($value), 'reddedilmeliydi: ' . $value);
        }
    }
}

<?php

/**
 * CSRF jetonu ve köken denetimi (vnl/helpers/app.php).
 * Dağıtıcı her durum değiştiren isteği bu iki fonksiyonla süzer.
 */
final class CsrfTest extends VnlTestCase
{
    public function setUp(): void
    {
        unset($_SERVER['HTTP_X_CSRF_TOKEN'], $_SERVER['HTTP_ORIGIN']);
        $_POST = [];
        $_SERVER['HTTP_HOST'] = 'site.test';
    }

    public function testTokenIs64HexAndStableWithinSession(): void
    {
        $token = vnl_csrf_token();

        $this->assertSame(1, preg_match('/^[0-9a-f]{64}$/', $token));
        $this->assertSame($token, vnl_csrf_token(), 'aynı oturumda jeton değişmemeli');
    }

    public function testRotateIssuesNewToken(): void
    {
        $old = vnl_csrf_token();
        vnl_csrf_rotate();

        $this->assertNotSame($old, vnl_csrf_token());
    }

    public function testCheckAcceptsMatchingHeader(): void
    {
        $_SERVER['HTTP_X_CSRF_TOKEN'] = vnl_csrf_token();

        $this->assertTrue(vnl_csrf_check());
    }

    public function testCheckAcceptsFormField(): void
    {
        $_POST['_token'] = vnl_csrf_token();

        $this->assertTrue(vnl_csrf_check());
    }

    public function testCheckRejectsMissingWrongAndEmpty(): void
    {
        vnl_csrf_token();
        $this->assertFalse(vnl_csrf_check(), 'jetonsuz istek');

        $_SERVER['HTTP_X_CSRF_TOKEN'] = str_repeat('0', 64);
        $this->assertFalse(vnl_csrf_check(), 'yanlış jeton');

        $_SERVER['HTTP_X_CSRF_TOKEN'] = '';
        $this->assertFalse(vnl_csrf_check(), 'boş jeton');
    }

    public function testCheckRejectsWhenSessionHasNoToken(): void
    {
        $_SESSION = [];
        $_SERVER['HTTP_X_CSRF_TOKEN'] = '';

        $this->assertFalse(vnl_csrf_check());
    }

    public function testCheckRejectsNonStringField(): void
    {
        vnl_csrf_token();
        $_POST['_token'] = [vnl_csrf_token()];

        $this->assertFalse(vnl_csrf_check());
    }

    public function testFieldEscapesAndCarriesToken(): void
    {
        $this->assertContains('name="_token" value="' . vnl_csrf_token() . '"', vnl_csrf_field());
    }

    public function testSameOriginWithoutHeaderDefersToToken(): void
    {
        $this->assertTrue(vnl_same_origin());
    }

    public function testSameOriginMatchesHost(): void
    {
        $_SERVER['HTTP_ORIGIN'] = 'https://site.test';
        $this->assertTrue(vnl_same_origin());

        $_SERVER['HTTP_ORIGIN'] = 'https://SITE.test';
        $this->assertTrue(vnl_same_origin(), 'büyük/küçük harf');
    }

    public function testSameOriginDefaultPort(): void
    {
        $_SERVER['HTTP_ORIGIN'] = 'https://site.test:443';
        $this->assertTrue(vnl_same_origin());
    }

    public function testSameOriginRejectsForeignAndNull(): void
    {
        foreach (['https://evil.test', 'https://site.test.evil.test', 'https://site.test:8443', 'null', 'site.test'] as $origin) {
            $_SERVER['HTTP_ORIGIN'] = $origin;
            $this->assertFalse(vnl_same_origin(), 'reddedilmeliydi: ' . $origin);
        }
    }
}

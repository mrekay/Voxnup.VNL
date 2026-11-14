<?php

/**
 * /app/<modül>/<metot> dağıtıcısı (vnl/classes/VnlDispatcher.php), gerçek
 * isteklerle. Sıra: yöntem → ad → sınıf/metot → giriş → köken + CSRF.
 *
 * Fikstür projenin kendi action'larıdır: action_login (PUBLIC_METHODS:
 * login, totp, logout) ve action_users (girişli). Proje bunları ezdiyse
 * testler projenin hâlini sınar — amaç da bu.
 */
final class DispatcherTest extends VnlHttpTestCase
{
    public function testGetIsRejectedWith405(): void
    {
        $res = $this->get('/app/login/login', ['X-Requested-With' => 'XMLHttpRequest']);

        $this->assertSame(405, $res->status);
        $this->assertSame('VNL_METHOD', $res->devcode());
        $this->assertSame('POST', $res->header('Allow'));
    }

    public function testUnknownModuleOrMethodIs404(): void
    {
        foreach (['/app/yok_boyle_modul/x', '/app/login/yok_boyle_metot', '/app/login', '/app/lo-gin/login'] as $path) {
            $res = $this->post($path);
            $this->assertSame(404, $res->status, $path);
            $this->assertSame('VNL_NOT_FOUND', $res->devcode(), $path);
        }
    }

    public function testMagicAndNonCallableMethodsAre404(): void
    {
        foreach (['__construct', '__destruct', '__call'] as $method) {
            $res = $this->post('/app/login/' . $method);
            $this->assertSame(404, $res->status, $method);
        }
    }

    public function testProtectedActionRequiresLogin(): void
    {
        $res = $this->post('/app/users/getList');

        $this->assertSame(401, $res->status);
        $this->assertSame('VNL_AUTH', $res->devcode());
    }

    /** Giriş CSRF'ten önce bakılır: jetonsuz girişsiz istek 403 değil 401 almalı. */
    public function testLoginIsCheckedBeforeCsrf(): void
    {
        $token = $this->csrfToken();

        $this->assertSame(401, $this->post('/app/users/save', ['_token' => $token])->status);
        $this->assertSame(401, $this->post('/app/users/save')->status);
    }

    public function testPublicMethodStillRequiresCsrf(): void
    {
        $this->csrfToken();   // oturum açılsın; jeton gönderilmiyor

        $res = $this->post('/app/login/login', ['username' => 'x', 'password' => 'y']);

        $this->assertSame(403, $res->status);
        $this->assertSame('VNL_CSRF', $res->devcode());
    }

    public function testWrongCsrfTokenIsRejected(): void
    {
        $this->csrfToken();

        $res = $this->post('/app/login/login', ['username' => 'x'], ['X-CSRF-Token' => str_repeat('a', 64)]);

        $this->assertSame(403, $res->status);
        $this->assertSame('VNL_CSRF', $res->devcode());
    }

    public function testForeignOriginIsRejectedEvenWithValidToken(): void
    {
        $token = $this->csrfToken();

        $res = $this->post('/app/login/login', ['username' => 'x'], [
            'X-CSRF-Token' => $token,
            'Origin'       => 'https://evil.test',
        ]);

        $this->assertSame(403, $res->status);
        $this->assertSame('VNL_CSRF', $res->devcode());
    }

    /** Jeton başka bir oturumdan alınmışsa geçmemeli. */
    public function testTokenIsBoundToSession(): void
    {
        $token = $this->csrfToken();
        $this->cookies = [];
        $this->csrfToken();   // yeni oturum

        $res = $this->post('/app/login/login', ['username' => 'x'], ['X-CSRF-Token' => $token]);

        $this->assertSame(403, $res->status);
    }

    /** Doğru jeton + aynı köken: kapılar geçilir, istek action'a ulaşır. */
    public function testValidTokenReachesAction(): void
    {
        $token = $this->csrfToken();

        $res = $this->post('/app/login/login', ['username' => '', 'password' => ''], ['X-CSRF-Token' => $token]);

        $this->assertNotContains($res->status, [401, 403, 404, 405], 'kapıda takılmamalıydı: ' . $res->body);
        $this->assertNotContains((string)$res->devcode(), ['VNL_AUTH', 'VNL_CSRF', 'VNL_METHOD', 'VNL_NOT_FOUND']);
    }

    /** Jeton gövdedeki _token alanıyla da gelebilir (düz form). */
    public function testTokenInFormFieldIsAccepted(): void
    {
        $token = $this->csrfToken();

        $res = $this->post('/app/login/login', ['username' => '', 'password' => '', '_token' => $token]);

        $this->assertNotContains($res->status, [401, 403]);
    }

    public function testResponsesAreJson(): void
    {
        $res = $this->post('/app/users/getList');

        $this->assertContains('application/json', (string)$res->header('Content-Type'));
        $this->assertNotContains('<html', strtolower($res->body));
    }
}

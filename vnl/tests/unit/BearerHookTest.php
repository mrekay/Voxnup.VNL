<?php

/**
 * Proje jeton doğrulayıcı kancası (4.1.0):
 * vnl_project_authenticate_token(), VnlDispatcher::authenticateBearer(),
 * vnl_bearer() ve vnl_is_bearer_request_for().
 *
 * Kanca bu dosyada tanımlanır; her test dosyası kendi sürecinde koştuğu için
 * başka testlere sızmaz. Proje kancayı zaten tanımlıyorsa kancaya bağlı
 * testler atlanır: projenin kancası sınanamaz, üstüne yazılamaz.
 * Kullanıcılar model değil düz nesnedir; veritabanı gerekmez.
 */

$GLOBALS['vnlBearerTestOwnsHook'] = !function_exists('vnl_project_authenticate_token');

if ($GLOBALS['vnlBearerTestOwnsHook']) {
    function vnl_project_authenticate_token(string $bearer, string $module, string $method): mixed
    {
        $GLOBALS['vnlBearerTestCalls'][] = [$bearer, $module, $method];
        $hook = $GLOBALS['vnlBearerTestHook'] ?? null;

        return is_callable($hook) ? $hook($bearer, $module, $method) : null;
    }
}

final class BearerHookTest extends VnlTestCase
{
    /** Öneksiz, 64 hex: çekirdek ApiToken biçimi değil, veritabanına gidilmez. */
    private const PLAIN = '0123456789abcdef0123456789abcdef0123456789abcdef0123456789abcdef';

    private $savedUser;

    public function setUp(): void
    {
        $this->savedUser                  = $GLOBALS['CurrentUser'] ?? null;
        $GLOBALS['CurrentUser']           = (object)['id' => 0];
        $GLOBALS['vnlBearerTestHook']     = null;
        $GLOBALS['vnlBearerTestCalls']    = [];

        /* Kayıt istek ömürlüdür; testler aynı süreçte koştuğu için sıfırlanır. */
        (new ReflectionProperty(VnlDispatcher::class, 'bearer'))->setValue(null, null);
    }

    public function tearDown(): void
    {
        $GLOBALS['CurrentUser'] = $this->savedUser;
    }

    private function ownHook(): void
    {
        if (!$GLOBALS['vnlBearerTestOwnsHook']) {
            $this->skip('proje vnl_project_authenticate_token() tanımlıyor');
        }
    }

    /* ---- dönüş biçimleri ---- */

    public function testHookResultForms(): void
    {
        $user = (object)['id' => 9];

        $this->assertSame(['user' => 7, 'meta' => []], VnlDispatcher::normalizeHookResult(7));
        $this->assertSame(['user' => 7, 'meta' => []], VnlDispatcher::normalizeHookResult('7'));
        $this->assertSame(['user' => $user, 'meta' => []], VnlDispatcher::normalizeHookResult($user));
        $this->assertSame(
            ['user' => 7, 'meta' => ['device_id' => 'abc']],
            VnlDispatcher::normalizeHookResult(['user' => 7, 'meta' => ['device_id' => 'abc']])
        );
        $this->assertSame(['user' => $user, 'meta' => []], VnlDispatcher::normalizeHookResult(['user' => $user, 'meta' => 'x']));
    }

    public function testUnrecognisedHookResultsAreNoIdentity(): void
    {
        foreach ([null, false, true, 0, -4, '0', '', 'abc', '7a', 1.5, [], ['meta' => []], (object)[], (object)['id' => 0]] as $value) {
            $this->assertNull(VnlDispatcher::normalizeHookResult($value), self::export($value));
        }
    }

    /* ---- kancalı doğrulama ---- */

    public function testHookIdentityAuthenticatesRequest(): void
    {
        $this->ownHook();
        $GLOBALS['vnlBearerTestHook'] = fn() => ['user' => (object)['id' => 42, 'is_active' => 1], 'meta' => ['token_row' => 5]];

        $this->assertNull(VnlDispatcher::authenticateBearer(self::PLAIN, 'users', 'getList'));

        $this->assertSame([[self::PLAIN, 'users', 'getList']], $GLOBALS['vnlBearerTestCalls']);
        $this->assertSame(42, vnl_user()->id);
        $this->assertSame(
            ['via' => 'project', 'user_id' => 42, 'token_id' => null, 'meta' => ['token_row' => 5]],
            vnl_bearer()
        );
    }

    public function testHookNullIsTokenError(): void
    {
        $this->ownHook();

        $this->assertSame('VNL_TOKEN', VnlDispatcher::authenticateBearer(self::PLAIN, 'users', 'getList'));
        $this->assertFalse(vnl_logged_in());
        $this->assertNull(vnl_bearer());
    }

    public function testInactiveUserFromHookIsRejected(): void
    {
        $this->ownHook();

        foreach ([0, 2] as $state) {
            $GLOBALS['vnlBearerTestHook'] = fn() => (object)['id' => 42, 'is_active' => $state];

            $this->assertSame('VNL_TOKEN', VnlDispatcher::authenticateBearer(self::PLAIN, 'users', 'getList'), "is_active={$state}");
            $this->assertFalse(vnl_logged_in());
            $this->assertNull(vnl_bearer());
        }
    }

    public function testFailedAttemptClearsEarlierRecord(): void
    {
        $this->ownHook();
        $GLOBALS['vnlBearerTestHook'] = fn() => (object)['id' => 42];
        $this->assertNull(VnlDispatcher::authenticateBearer(self::PLAIN, 'a', 'b'));
        $this->assertNotNull(vnl_bearer());

        $GLOBALS['vnlBearerTestHook'] = fn() => null;
        $this->assertSame('VNL_TOKEN', VnlDispatcher::authenticateBearer(self::PLAIN, 'a', 'b'));
        $this->assertNull(vnl_bearer());
    }

    /* ---- vnl_is_bearer_request_for ---- */

    public function testBearerRequestForChecksOwnerAndSource(): void
    {
        $this->ownHook();
        $GLOBALS['vnlBearerTestHook'] = fn() => (object)['id' => 42];
        VnlDispatcher::authenticateBearer(self::PLAIN, 'users', 'deleteAccount');

        $this->assertTrue(vnl_is_bearer_request_for(42));
        $this->assertTrue(vnl_is_bearer_request_for(42, 'project'));
        $this->assertFalse(vnl_is_bearer_request_for(42, 'core'));
        $this->assertFalse(vnl_is_bearer_request_for(43));
        $this->assertFalse(vnl_is_bearer_request_for(0));

        /* Kayıt ile istekteki kullanıcı ayrışırsa güvenilmez. */
        $GLOBALS['CurrentUser'] = (object)['id' => 43];
        $this->assertFalse(vnl_is_bearer_request_for(42));
        $this->assertFalse(vnl_is_bearer_request_for(43));
    }

    public function testSessionRequestIsNotBearer(): void
    {
        $GLOBALS['CurrentUser'] = (object)['id' => 42];

        $this->assertNull(vnl_bearer());
        $this->assertFalse(vnl_is_bearer_request_for(42));
    }
}

<?php

use Illuminate\Database\Capsule\Manager as Capsule;

/**
 * Gerçek veritabanıyla giriş akışı, CSRF ve yetki kapısı.
 *
 * Veritabanı yoksa ya da şema kurulmamışsa atlanır. Kurmak için:
 *   php vnl.php migrate --run
 *
 * Her test kendi kullanıcılarını rastgele adla açar ve sonunda kalıcı
 * olarak siler. Test sunucusu aynı ortamı (.env + süreç değişkenleri)
 * kullandığı için aynı veritabanına bağlanır.
 */
final class AuthFlowTest extends VnlHttpTestCase
{
    private const PASSWORD = 'test-parola-1234';

    /** @var User[] */
    private array $created = [];

    public function setUp(): void
    {
        parent::setUp();
        $this->requireDatabase();

        foreach (['tbl_users', 'tbl_vnl_rate_limits', 'tbl_vnl_audit_log'] as $table) {
            if (!Capsule::schema()->hasTable($table)) {
                $this->skip("{$table} yok: php vnl.php migrate --run");
            }
        }

        /* Önceki koşulardan kalan sayaçlar giriş kapısını kapatmasın. */
        Capsule::table('tbl_vnl_rate_limits')->delete();
    }

    public function tearDown(): void
    {
        foreach ($this->created as $user) {
            try {
                $user->forceDelete();
            } catch (Throwable $e) {
                // tablo yoksa zaten atlanmıştı
            }
        }
        $this->created = [];
    }

    private function makeUser(?int $permId): User
    {
        $user = new User();
        $user->username  = 'vnltest_' . bin2hex(random_bytes(4));
        $user->name      = 'Test';
        $user->surname   = 'Kullanici';
        $user->email     = null;
        $user->perm_id   = $permId;
        $user->is_active = 1;
        $user->setStoredPassword(EncryptionModule::PasswordHash(self::PASSWORD));
        $user->save();

        return $this->created[] = $user;
    }

    private function login(User $user, string $password = self::PASSWORD): VnlTestResponse
    {
        $token = $this->csrfToken('/login');

        return $this->post('/app/login/login', [
            'email'    => $user->username,
            'password' => $password,
        ], ['X-CSRF-Token' => $token]);
    }

    public function testValidLoginOpensSession(): void
    {
        $user = $this->makeUser(User::SUPER_ADMIN);

        $res = $this->login($user);
        $this->assertSame(200, $res->status, $res->body);

        $page = $this->get('/index');
        $this->assertSame(200, $page->status, 'girişten sonra korumalı sayfa açılmalı');
    }

    public function testWrongPasswordKeepsSessionAnonymousAndIsAudited(): void
    {
        $user = $this->makeUser(User::SUPER_ADMIN);

        $res = $this->login($user, 'yanlis-parola-0000');
        $this->assertNotSame(200, $res->status);

        $this->assertSame(302, $this->get('/index')->status, 'oturum açılmamalıydı');

        $logged = Capsule::table('tbl_vnl_audit_log')
            ->where('action', 'auth.login_failed')
            ->where('subject_id', (string)$user->id)
            ->count();
        $this->assertSame(1, $logged, 'başarısız giriş denetim kaydına yazılmalı');
    }

    /** Girişte jeton döner: girişten önce alınan jeton artık geçmez (oturum sabitleme). */
    public function testLoginRotatesCsrfToken(): void
    {
        $user = $this->makeUser(User::SUPER_ADMIN);

        $before = $this->csrfToken('/login');
        $this->assertSame(200, $this->login($user)->status);

        $res = $this->post('/app/users/getList', [], ['X-CSRF-Token' => $before]);
        $this->assertSame(403, $res->status);
        $this->assertSame('VNL_CSRF', $res->devcode());

        $after = $this->csrfToken('/index');
        $this->assertNotSame($before, $after);
    }

    public function testLoggedInRequestWithoutTokenIsRejected(): void
    {
        $this->assertSame(200, $this->login($this->makeUser(User::SUPER_ADMIN))->status);

        $res = $this->post('/app/users/getList');

        $this->assertSame(403, $res->status);
        $this->assertSame('VNL_CSRF', $res->devcode());
    }

    public function testPermissionGateDeniesPlainUser(): void
    {
        $this->assertSame(200, $this->login($this->makeUser(null))->status);
        $token = $this->csrfToken('/index');

        $res = $this->post('/app/users/getList', [], ['X-CSRF-Token' => $token]);

        $this->assertSame(403, $res->status);
        $this->assertSame('VNL_PERM', $res->devcode());
    }

    public function testPermissionGateAllowsSuperAdmin(): void
    {
        $this->assertSame(200, $this->login($this->makeUser(User::SUPER_ADMIN))->status);
        $token = $this->csrfToken('/index');

        $res = $this->post('/app/users/getList', [], ['X-CSRF-Token' => $token]);

        $this->assertSame(200, $res->status, $res->body);
        $this->assertNotNull($res->json());
    }

    public function testLogoutEndsSession(): void
    {
        $this->assertSame(200, $this->login($this->makeUser(User::SUPER_ADMIN))->status);
        $token = $this->csrfToken('/index');

        $this->post('/app/login/logout', [], ['X-CSRF-Token' => $token]);

        $this->assertSame(302, $this->get('/index')->status);
    }

    public function testRateLimiterBlocksAfterLimit(): void
    {
        $key = 'vnltest:' . bin2hex(random_bytes(4));

        try {
            $this->assertTrue(RateLimiter::hit($key, 2, 60));
            $this->assertTrue(RateLimiter::hit($key, 2, 60));
            $this->assertFalse(RateLimiter::hit($key, 2, 60), 'üçüncü deneme reddedilmeli');
            $this->assertSame(3, RateLimiter::attempts($key));
        } finally {
            RateLimiter::clear($key);
        }

        $this->assertSame(0, RateLimiter::attempts($key));
    }

    public function testAuditScrubsSecrets(): void
    {
        $marker = 'vnltest.' . bin2hex(random_bytes(4));
        Audit::log($marker, 'test', null, ['password' => 'gizli', 'api_token' => 'gizli', 'ad' => 'açık']);

        $row = Capsule::table('tbl_vnl_audit_log')->where('action', $marker)->first();
        Capsule::table('tbl_vnl_audit_log')->where('action', $marker)->delete();

        $this->assertNotNull($row, 'kayıt yazılmalı');
        $this->assertNotContains('gizli', (string)$row->data);
        $this->assertContains('açık', (string)$row->data);
    }
}

<?php

use Illuminate\Database\Capsule\Manager as Capsule;

/**
 * Parola ya da 2FA değişince açık oturumlar ve "beni hatırla" jetonları
 * düşer (4.0.0: UserModule::AuthStamp, migration 0008).
 */
final class SessionRevocationTest extends VnlHttpTestCase
{
    private const PASSWORD = 'test-parola-1234';

    /** @var User[] */
    private array $created = [];

    public function setUp(): void
    {
        parent::setUp();
        $this->requireDatabase();

        foreach (['tbl_users', 'tbl_remember_tokens', 'tbl_vnl_rate_limits'] as $table) {
            if (!Capsule::schema()->hasTable($table)) {
                $this->skip("{$table} yok: php vnl.php migrate --run");
            }
        }

        Capsule::table('tbl_vnl_rate_limits')->delete();
    }

    public function tearDown(): void
    {
        foreach ($this->created as $user) {
            try {
                Capsule::table('tbl_remember_tokens')->where('user_id', $user->id)->delete();
                $user->forceDelete();
            } catch (Throwable $e) {
            }
        }
        $this->created = [];
    }

    private function makeUser(): User
    {
        $user = new User();
        $user->username  = 'vnltest_' . bin2hex(random_bytes(4));
        $user->name      = 'Test';
        $user->surname   = 'Kullanici';
        $user->email     = null;
        $user->perm_id   = User::SUPER_ADMIN;
        $user->is_active = 1;
        $user->setStoredPassword(EncryptionModule::PasswordHash(self::PASSWORD));
        $user->save();

        return $this->created[] = $user;
    }

    private function login(User $user, bool $remember = false): void
    {
        $res = $this->post('/app/login/login', array_filter([
            'email'      => $user->username,
            'password'   => self::PASSWORD,
            'rememberMe' => $remember ? '1' : null,
        ]), ['X-CSRF-Token' => $this->csrfToken('/login')]);

        $this->assertSame(200, $res->status, 'giris: ' . $res->body);
    }

    /**
     * Girişli mi? Korumalı sayfa (/) girişsize 302 döner; girişliyken
     * sayfanın CSRF jetonuyla bir action da (süper yönetici) 200 vermeli.
     */
    private function loggedIn(): bool
    {
        $page = $this->get('/');
        if ($page->status !== 200) {
            return false;
        }

        $this->assertSame(1, preg_match('/<meta name="csrf-token" content="([^"]+)"/', $page->body, $m), 'meta jeton');
        $res = $this->post('/app/users/getList', [], ['X-CSRF-Token' => html_entity_decode($m[1])]);

        return $res->status === 200;
    }

    private function changePassword(User $user): void
    {
        $fresh = User::find($user->id);
        $fresh->setStoredPassword(EncryptionModule::PasswordHash('yeni-parola-5678'));
        $fresh->save();
    }

    public function testStampDependsOnPasswordAndTotp(): void
    {
        $user = $this->makeUser();
        $a    = UserModule::AuthStamp($user);

        $this->assertSame($a, UserModule::AuthStamp(User::find($user->id)), 'ayni hesap ayni damga');

        $user->setStoredPassword(EncryptionModule::PasswordHash('baska'));
        $this->assertNotSame($a, UserModule::AuthStamp($user));

        $b = UserModule::AuthStamp($user);
        $user->totp_secret = 'sifreli-sir';
        $this->assertNotSame($b, UserModule::AuthStamp($user));
    }

    public function testPasswordChangeEndsOpenSession(): void
    {
        $user = $this->makeUser();
        $this->login($user);
        $this->assertTrue($this->loggedIn(), 'giristen sonra');

        $this->changePassword($user);

        $this->assertFalse($this->loggedIn(), 'parola degisti, oturum kapanmali');
    }

    public function testUnrelatedSaveKeepsSession(): void
    {
        $user = $this->makeUser();
        $this->login($user);

        $fresh = User::find($user->id);
        $fresh->name = 'Baska Ad';
        $fresh->save();

        $this->assertTrue($this->loggedIn(), 'ad degisikligi oturumu kapatmamali');
    }

    public function testRememberTokenFromBeforeChangeIsRejected(): void
    {
        if (!Capsule::schema()->hasColumn('tbl_remember_tokens', 'auth_stamp')) {
            $this->skip('0008 uygulanmamis');
        }
        if (UserModule::rememberDays() === 0) {
            $this->skip('VNL_REMEMBER_DAYS=0');
        }

        $user = $this->makeUser();
        $this->login($user, true);

        $remember = null;
        foreach (array_keys($this->cookies) as $name) {
            if (str_ends_with($name, 'remember')) {
                $remember = $name;
            }
        }
        $this->assertNotNull($remember, 'beni hatirla cerezi yazilmali');

        /* Yalnız jeton kalsın: oturum çerezi atılır (tarayıcı kapandı). */
        foreach (array_keys($this->cookies) as $name) {
            if ($name !== $remember) {
                unset($this->cookies[$name]);
            }
        }
        $this->assertTrue($this->loggedIn(), 'jetonla yeniden giris (degisiklikten once)');

        foreach (array_keys($this->cookies) as $name) {
            if ($name !== $remember) {
                unset($this->cookies[$name]);
            }
        }
        $this->changePassword($user);

        $this->assertFalse($this->loggedIn(), 'parola degisti, eski jeton gecmemeli');
        $this->assertSame(0, Capsule::table('tbl_remember_tokens')->where('user_id', $user->id)->count());
    }
}

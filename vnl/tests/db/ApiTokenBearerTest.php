<?php

use Illuminate\Database\Capsule\Manager as Capsule;

/**
 * Çekirdek API jetonu VnlDispatcher::authenticateBearer() üzerinden (4.1.0):
 * kayıt 'core' olarak düşer, yetenek dışı çağrı VNL_PERM, iptal edilen ve
 * etkin olmayan kullanıcının jetonu VNL_TOKEN.
 */
final class ApiTokenBearerTest extends VnlTestCase
{
    private ?User $user = null;
    private $savedUser;

    public function setUp(): void
    {
        $this->requireDatabase();

        foreach (['tbl_users', ApiToken::TABLE] as $table) {
            if (!Capsule::schema()->hasTable($table)) {
                $this->skip("{$table} yok: php vnl.php migrate --run");
            }
        }

        $this->savedUser = $GLOBALS['CurrentUser'] ?? null;
        $GLOBALS['CurrentUser'] = (object)['id' => 0];
        (new ReflectionProperty(VnlDispatcher::class, 'bearer'))->setValue(null, null);

        $user = new User();
        $user->username  = 'vnltest_' . bin2hex(random_bytes(4));
        $user->name      = 'Test';
        $user->surname   = 'Kullanici';
        $user->email     = null;
        $user->perm_id   = User::SUPER_ADMIN;
        $user->is_active = 1;
        $user->setStoredPassword(EncryptionModule::PasswordHash('test-parola-1234'));
        $user->save();

        $this->user = $user;
    }

    public function tearDown(): void
    {
        $GLOBALS['CurrentUser'] = $this->savedUser;

        if ($this->user !== null) {
            try {
                Capsule::table(ApiToken::TABLE)->where('user_id', $this->user->id)->delete();
                $this->user->forceDelete();
            } catch (Throwable $e) {
            }
            $this->user = null;
        }
    }

    public function testCoreTokenIsRecordedAsCore(): void
    {
        $plain = ApiToken::issue((int)$this->user->id, 'test', ['domains']);
        $id    = (int)Capsule::table(ApiToken::TABLE)->where('token_hash', hash('sha256', $plain))->value('id');

        $this->assertNull(VnlDispatcher::authenticateBearer($plain, 'domains', 'getList'));
        $this->assertSame(
            ['via' => 'core', 'user_id' => (int)$this->user->id, 'token_id' => $id, 'meta' => []],
            vnl_bearer()
        );
        $this->assertTrue(vnl_is_bearer_request_for((int)$this->user->id, 'core'));
    }

    public function testAbilityOutsideListIsPermError(): void
    {
        $plain = ApiToken::issue((int)$this->user->id, 'test', ['domains']);

        $this->assertSame('VNL_PERM', VnlDispatcher::authenticateBearer($plain, 'users', 'getList'));
        $this->assertNull(vnl_bearer());
        $this->assertFalse(vnl_logged_in());
    }

    public function testInactiveUserTokenIsRejected(): void
    {
        $plain = ApiToken::issue((int)$this->user->id, 'test');
        $this->user->is_active = 0;
        $this->user->save();

        $this->assertSame('VNL_TOKEN', VnlDispatcher::authenticateBearer($plain, 'domains', 'getList'));
        $this->assertNull(vnl_bearer());
    }
}

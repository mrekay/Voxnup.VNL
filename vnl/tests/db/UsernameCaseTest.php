<?php

use Illuminate\Database\Capsule\Manager as Capsule;

/**
 * Kullanıcı adı her sürücüde büyük/küçük harfe duyarsızdır (4.1.0).
 *
 * MySQL'de bunu utf8mb4_unicode_ci sağlar; PostgreSQL'de 0002 sütunu CITEXT,
 * SQLite'ta COLLATE NOCASE açar. Duyarlı olsaydı 'Admin' ile 'admin' iki ayrı
 * hesap olabilir ve giriş, kullanıcının yazdığı harf büyüklüğüne takılırdı.
 */
final class UsernameCaseTest extends VnlTestCase
{
    private string $username = '';

    public function setUp(): void
    {
        $this->requireDatabase();

        if (!Capsule::schema()->hasTable('tbl_users')) {
            $this->skip('tbl_users yok: php vnl.php migrate --run');
        }

        $this->username = 'VnlCase_' . bin2hex(random_bytes(3));

        $user = new User();
        $user->username  = $this->username;
        $user->name      = 'Test';
        $user->surname   = 'Kullanici';
        $user->email     = null;
        $user->perm_id   = User::SUPER_ADMIN;
        $user->is_active = 1;
        $user->setStoredPassword(EncryptionModule::PasswordHash('test-parola-1234'));
        $user->save();
    }

    public function tearDown(): void
    {
        if ($this->username !== '') {
            try {
                Capsule::table('tbl_users')->whereIn('username', [$this->username, strtolower($this->username)])->delete();
            } catch (Throwable $e) {
            }
            $this->username = '';
        }
    }

    public function testLookupIgnoresCase(): void
    {
        foreach ([strtolower($this->username), strtoupper($this->username)] as $typed) {
            $found = User::Select(['username' => $typed]);

            $this->assertNotNull($found, $typed);
            $this->assertSame($this->username, (string)$found->username, $typed);
        }
    }

    public function testDuplicateDifferingOnlyInCaseIsRejected(): void
    {
        $this->assertThrows(fn() => Capsule::table('tbl_users')->insert([
            'username' => strtolower($this->username),
            'password' => 'x',
            'name'     => '',
            'surname'  => '',
        ]), Throwable::class, 'harf farkıyla ikinci hesap açılabildi');
    }
}

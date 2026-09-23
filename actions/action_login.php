<?php
/*!
 * ============================================================
 *  Voxnup Not Laravel (VNL) — Giriş / Çıkış
 *  POST /app/login/login · /app/login/totp · /app/login/logout
 *  Authors: Voxnup Authors
 * ============================================================
 *
 * 3.4.0:
 *  - Hız sınırı (RateLimiter, 15 dk pencere, aşılınca 429 VNL_RATE):
 *      IP başına 20 deneme (IPv6'da /64 öneki — RateLimiter::ipKey),
 *      hesap + IP başına 5 deneme,
 *      hesap başına (bütün IP'ler) 100 deneme.
 *    Deneme kimlik bilgisi denetlenmeden ÖNCE sayılır ve kapı sayımın
 *    sonucuna bakar: paralel istekler sınırı aşamaz. Başarılı deneme
 *    IP bütçesinden geri düşülür, hesap sayaçları sıfırlanır.
 *  - Hesap anahtarı KAYITLI kullanıcı adına bağlanır, girilen metne değil:
 *    tbl_users.username utf8mb4_unicode_ci'dir, 'ädmin' gibi aksanlı ya da
 *    tam genişlikli yazımlar da 'admin' satırını bulur. Anahtar girilen
 *    metinden üretilseydi her yazım ayrı bir 5 deneme hakkı kazanırdı.
 *  - Sıkı kilit (5) hesap + IP'dir: başka bir adresten 5 yanlış parola
 *    gerçek yöneticiyi kilitleyemez. Hesap geneli sınır (100) dağıtık
 *    tahmini frenler; bir adres ona en fazla 5 katkı yapabilir.
 *  - Pasif hesap (is_active = 0) ERR_LOGIN_PASSIVE ile reddedilir — ama
 *    yalnız parola DOĞRUYSA; yanlış parolayla hesabın durumu öğrenilemez.
 *  - İki adımlı doğrulama: totp_enabled kullanıcıda parola adımı oturumu
 *    AÇMAZ, {"data":{"step":"totp"}} döner; kod /app/login/totp'a gelir.
 *    Aynı kod ikinci kez kabul edilmez (RFC 6238 §5.2): eşleşen adım
 *    tbl_users.totp_last_step'e yazılır (vnl/migrations/0007). Sütun
 *    yoksa ikinci adım kapalıdır (fail closed) ve sebep günlüğe düşer.
 *  - Çıkış POST'tur (CSRF'li). GET /logout yalnız onay sayfasıdır.
 *  - Her giriş, başarısız deneme ve çıkış denetim kaydına (Audit) düşer.
 */

use ActionGlobal\Auth;

class action_login
{
    /** Girişsiz çağrılabilen metotlar (VnlDispatcher). CSRF yine zorunlu. */
    public const PUBLIC_METHODS = ['login', 'totp', 'logout'];

    private const IP_MAX_ATTEMPTS      = 20;
    private const USER_MAX_ATTEMPTS    = 5;     // hesap + IP
    private const ACCOUNT_MAX_ATTEMPTS = 100;   // hesap, bütün IP'ler
    private const TOTP_MAX_ATTEMPTS    = 5;
    private const WINDOW_SECONDS       = 900;

    /** Parola ikinci adımı en fazla bu kadar bekler (sn). */
    private const TOTP_PENDING_TTL = 300;

    /**
     * Kullanıcı bulunamadığında da bir bcrypt doğrulaması yapılır: yanıt
     * süresi "böyle bir kullanıcı var mı" sorusunu yanıtlamasın.
     */
    private const TIMING_DUMMY_HASH = '$2y$10$gcW7oR.FEk.O76A.RKd0/OcgsQuk2vLPq1M9qK24pqa9nELgVW1rq';

    public function login($req)
    {
        $username = trim((string)$req->post('email', ''));
        $password = (string)$req->post('password', '');

        LoginValidator::validateUsername($username);
        LoginValidator::validatePassword($password);

        $ip    = RateLimiter::ipKey(UserModule::CurrentIp());
        $ipKey = 'login:ip:' . $ip;

        /* IP kapısı kullanıcı aranmadan önce: sayılır, sonra karar verilir. */
        $this->consume($ipKey, self::IP_MAX_ATTEMPTS);

        /* password_hash her çağrıda farklı tuz ürettiği için hash ile
           sorgulanamaz: önce kullanıcı çekilir, sonra doğrulanır. */
        $user_data = User::Select(["username" => $username]);

        /* Hesap anahtarları kayıtlı kimlikten: harmanlamaya (collation)
           göre eşdeğer her yazım aynı sayacı paylaşır. */
        [$accountKey, $accountIpKey] = $this->accountKeys(
            !empty($user_data) ? (string)$user_data->username : $username,
            $ip
        );

        $this->consume($accountIpKey, self::USER_MAX_ATTEMPTS);
        $this->consume($accountKey, self::ACCOUNT_MAX_ATTEMPTS);

        $valid = empty($user_data)
            ? (EncryptionModule::PasswordVerify($password, self::TIMING_DUMMY_HASH) && false)
            : EncryptionModule::PasswordVerify($password, $user_data->storedPassword());

        if (!$valid) {
            Audit::log('auth.login_failed', 'user', $user_data->id ?? null, ['username' => mb_substr($username, 0, 64)]);

            throw new StandartException(lang('ERR_LOGIN_EXCEPTION'));
        }

        /* 2.x'ten kalan sha1(md5()) hash'i sessizce bcrypt'e yükseltilir;
           kullanıcının parolasını sıfırlaması gerekmez. */
        if (EncryptionModule::PasswordNeedsRehash($user_data->storedPassword())) {
            $newHash = EncryptionModule::PasswordHash($password);
            $user_data->setStoredPassword($newHash);
            $user_data->save();

            /* 2.x'te sha1 için varchar(40) yetiyordu; bcrypt 60 karakter.
               Sütun dar kalmışsa hash sessizce kırpılır ve kullanıcı bir
               daha giremez. Yükseltme kullanıcı başına bir kez olduğu için
               burada bir kereye mahsus doğrulanır. */
            $written = $user_data->fresh()?->storedPassword();
            if ($written !== $newHash) {
                error_log('[VNL][Auth] Parola sütunu bcrypt için dar görünüyor: '
                    . strlen($newHash) . ' karakter yazıldı, '
                    . strlen((string)$written) . ' karakter geri okundu. '
                    . 'tbl_users.password en az VARCHAR(255) olmalı.');
            }
        }

        LoginValidator::validateUser($user_data);
        LoginValidator::validateActive($user_data);

        /* Parola doğru: hesap sayaçları sıfırlanır; bu deneme paylaşılan IP
           bütçesinden geri düşülür (aynı NAT arkasındaki kullanıcılar
           birbirinin başarılı girişini ödemesin). */
        RateLimiter::clear($accountIpKey);
        RateLimiter::clear($accountKey);
        RateLimiter::decrement($ipKey);

        $remember = !empty($req->post('rememberMe')) && UserModule::rememberDays() > 0;

        /* İkinci adım: oturum henüz AÇILMAZ. Bekleyen giriş, kimliği
           yenilenmiş oturumda 5 dakika tutulur. */
        if (method_exists($user_data, 'hasTotp') && $user_data->hasTotp()) {
            Session::regenerate();
            Session::set('_2fa_pending', [
                'user_id'  => (int)$user_data->id,
                'remember' => $remember,
                'at'       => time(),
            ]);

            returnSuccessMessage(lang('LOGIN_TOTP_REQUIRED'), ['step' => 'totp']);

            return;
        }

        UserModule::CompleteLogin($user_data, $remember);

        returnSuccessMessage(lang('SUC_LOGIN'), ['url' => '/']);
    }

    /** İki adımlı doğrulamanın ikinci adımı: 6 haneli kod. */
    public function totp($req)
    {
        $pending = Session::get('_2fa_pending');

        if (!is_array($pending) || empty($pending['user_id'])
            || time() - (int)($pending['at'] ?? 0) > self::TOTP_PENDING_TTL) {
            Session::delete('_2fa_pending');
            $this->deny(lang('ERR_TOTP_EXPIRED'), 401, 'VNL_TOTP_EXPIRED');
        }

        $userId  = (int)$pending['user_id'];
        $ip      = RateLimiter::ipKey(UserModule::CurrentIp());
        $ipKey   = 'login:ip:' . $ip;
        $totpKey = 'login:totp:' . $userId;

        /* Kod denetlenmeden önce sayılır (paralel istekler sınırı aşamaz). */
        $this->consume($ipKey, self::IP_MAX_ATTEMPTS);
        $this->consume($totpKey, self::TOTP_MAX_ATTEMPTS);

        $user = User::Select(['id' => $userId]);

        if (empty($user) || !UserModule::IsActiveUser($user)) {
            Session::delete('_2fa_pending');
            $this->deny(lang('ERR_LOGIN_EXCEPTION'), 400);
        }

        /* Tekrar kullanım koruması olmadan kod kabul edilmez (fail closed). */
        if (!method_exists($user, 'claimTotpStep') || !$user->hasTotpReplayGuard()) {
            error_log('[VNL][Auth] tbl_users.totp_last_step yok ya da classes/User.php eski: '
                . 'iki adımlı giriş kapalı. Çalıştırın: php vnl.php migrate --run');
            $this->deny(lang('ERR_GENERAL'), 500, 'VNL_FATAL');
        }

        $secret = Totp::decryptSecret($user->totp_secret ?? null);
        $code   = (string)$req->post('code', '');
        $step   = $secret !== null ? Totp::match($secret, $code) : null;

        /* Eşleşen adım koşullu UPDATE ile tüketilir: aynı kod (ya da daha
           eski bir adımın kodu) ikinci kez geçmez, paralel iki istekten
           yalnız biri kazanır. Tekrar, başarısız deneme sayılır (sayaçlar
           yukarıda zaten artırıldı). */
        if ($step === null || !$user->claimTotpStep($step)) {
            Audit::log('auth.totp_failed', 'user', $userId, $step !== null ? ['reason' => 'replay'] : []);

            $this->deny(lang('ERR_TOTP_INVALID'), 400, 'VNL_TOTP');
        }

        [$accountKey, $accountIpKey] = $this->accountKeys((string)$user->username, $ip);

        RateLimiter::clear($totpKey);
        RateLimiter::clear($accountKey);
        RateLimiter::clear($accountIpKey);
        RateLimiter::decrement($ipKey);

        UserModule::CompleteLogin($user, !empty($pending['remember']));

        returnSuccessMessage(lang('SUC_LOGIN'), ['url' => '/']);
    }

    /**
     * Çıkış (POST + CSRF). Girişsiz çağrı da başarılıdır: sonuç zaten
     * "oturum yok" — istemci her durumda /login'e gider.
     */
    public function logout($req)
    {
        $user = vnl_user();

        if ($user !== null) {
            Audit::log('auth.logout', 'user', $user->id);
        }

        UserModule::LogOut();

        returnSuccessMessage('', ['url' => '/login']);
    }

    /* ---------------------------------------------------------------- */

    /**
     * Denemeyi sayar; sayımdan sonra sınır aşıldıysa 429 ile bitirir.
     *
     * Önce say, sonra karar ver: "oku, başarısızlıkta say" kalıbında aynı
     * anda gelen istekler aynı düşük sayıyı okuyup hepsi geçerdi.
     */
    private function consume(string $key, int $maxAttempts): void
    {
        if (!RateLimiter::hit($key, $maxAttempts, self::WINDOW_SECONDS)) {
            $minutes = max(1, (int)ceil(RateLimiter::availableIn($key) / 60));

            $this->deny(lang('ERR_TOO_MANY_ATTEMPTS', $minutes), 429, 'VNL_RATE');
        }
    }

    /**
     * Hesap sayaç anahtarları: [hesap geneli, hesap + IP].
     *
     * $identity mümkünse veritabanındaki kullanıcı adıdır (girilen metin
     * değil); totp() aynı anahtarları aynı kimlikten üretir.
     */
    private function accountKeys(string $identity, string $ip): array
    {
        $account = 'login:user:' . mb_strtolower($identity);

        return [$account, $account . '|' . $ip];
    }

    private function deny(string $message, int $status, string $devcode = ''): never
    {
        returnErrorMessage($message, $status, $devcode);

        exit;
    }
}

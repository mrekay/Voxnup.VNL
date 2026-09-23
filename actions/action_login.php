<?php
/*!
 * ============================================================
 *  Voxnup Not Laravel (VNL) — Giriş / Çıkış
 *  POST /app/login/login · /app/login/totp · /app/login/logout
 *  Authors: Voxnup Authors
 * ============================================================
 *
 * 3.4.0:
 *  - Hız sınırı: IP başına 20, kullanıcı adı başına 5 başarısız deneme /
 *    15 dk (RateLimiter). Aşılınca 429 VNL_RATE.
 *  - Pasif hesap (is_active = 0) ERR_LOGIN_PASSIVE ile reddedilir — ama
 *    yalnız parola DOĞRUYSA; yanlış parolayla hesabın durumu öğrenilemez.
 *  - İki adımlı doğrulama: totp_enabled kullanıcıda parola adımı oturumu
 *    AÇMAZ, {"data":{"step":"totp"}} döner; kod /app/login/totp'a gelir.
 *  - Çıkış POST'tur (CSRF'li). GET /logout yalnız onay sayfasıdır.
 *  - Her giriş, başarısız deneme ve çıkış denetim kaydına (Audit) düşer.
 */

use ActionGlobal\Auth;

class action_login
{
    /** Girişsiz çağrılabilen metotlar (VnlDispatcher). CSRF yine zorunlu. */
    public const PUBLIC_METHODS = ['login', 'totp', 'logout'];

    private const IP_MAX_ATTEMPTS   = 20;
    private const USER_MAX_ATTEMPTS = 5;
    private const TOTP_MAX_ATTEMPTS = 5;
    private const WINDOW_SECONDS    = 900;

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

        $ipKey   = 'login:ip:' . UserModule::CurrentIp();
        $userKey = 'login:user:' . mb_strtolower($username);

        $this->throttle($ipKey, self::IP_MAX_ATTEMPTS);
        $this->throttle($userKey, self::USER_MAX_ATTEMPTS);

        /* password_hash her çağrıda farklı tuz ürettiği için hash ile
           sorgulanamaz: önce kullanıcı çekilir, sonra doğrulanır. */
        $user_data = User::Select(["username" => $username]);

        $valid = empty($user_data)
            ? (EncryptionModule::PasswordVerify($password, self::TIMING_DUMMY_HASH) && false)
            : EncryptionModule::PasswordVerify($password, $user_data->storedPassword());

        if (!$valid) {
            RateLimiter::hit($ipKey, self::IP_MAX_ATTEMPTS, self::WINDOW_SECONDS);
            RateLimiter::hit($userKey, self::USER_MAX_ATTEMPTS, self::WINDOW_SECONDS);
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

        RateLimiter::clear($userKey);

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
        $ipKey   = 'login:ip:' . UserModule::CurrentIp();
        $totpKey = 'login:totp:' . $userId;

        $this->throttle($ipKey, self::IP_MAX_ATTEMPTS);
        $this->throttle($totpKey, self::TOTP_MAX_ATTEMPTS);

        $user = User::Select(['id' => $userId]);

        if (empty($user) || !UserModule::IsActiveUser($user)) {
            Session::delete('_2fa_pending');
            $this->deny(lang('ERR_LOGIN_EXCEPTION'), 400);
        }

        $secret = Totp::decryptSecret($user->totp_secret ?? null);
        $code   = (string)$req->post('code', '');

        if ($secret === null || !Totp::verify($secret, $code)) {
            RateLimiter::hit($ipKey, self::IP_MAX_ATTEMPTS, self::WINDOW_SECONDS);
            RateLimiter::hit($totpKey, self::TOTP_MAX_ATTEMPTS, self::WINDOW_SECONDS);
            Audit::log('auth.totp_failed', 'user', $userId);

            $this->deny(lang('ERR_TOTP_INVALID'), 400, 'VNL_TOTP');
        }

        RateLimiter::clear($totpKey);
        RateLimiter::clear('login:user:' . mb_strtolower((string)$user->username));

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

    /** Sınır dolduysa 429 ile bitirir. */
    private function throttle(string $key, int $maxAttempts): void
    {
        if (RateLimiter::tooMany($key, $maxAttempts)) {
            $minutes = max(1, (int)ceil(RateLimiter::availableIn($key) / 60));

            $this->deny(lang('ERR_TOO_MANY_ATTEMPTS', $minutes), 429, 'VNL_RATE');
        }
    }

    private function deny(string $message, int $status, string $devcode = ''): never
    {
        returnErrorMessage($message, $status, $devcode);

        exit;
    }
}

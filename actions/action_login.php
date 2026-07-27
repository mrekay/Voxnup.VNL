<?php
class action_login
{

    public function login($req)
    {
        $data = [];

        $username = $req->post('email');
        $password = $req->post('password');

        LoginValidator::validateUsername($username);
        LoginValidator::validatePassword($password);

        /* password_hash her çağrıda farklı tuz ürettiği için hash ile
           sorgulanamaz: önce kullanıcı çekilir, sonra doğrulanır. */
        $user_data = User::select(["username" => $username]);

        if (empty($user_data) || !EncryptionModule::PasswordVerify($password, $user_data->storedPassword())) {
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

        UserModule::SetUser($user_data);

        LoginValidator::validateSession(Session::checkSession());
        UserModule::SetCurrentUserInfo();

        /* Kalıcı giriş: rastgele jeton üretilip çereze yazılır, veritabanında
           yalnızca özeti saklanır. 2.x burada md5(kullanıcıadı+salt+parola+time)
           yazıyordu ve o çerezi okuyan hiçbir kod yoktu. */
        if (!empty($req->post('rememberMe'))) {
            UserModule::RememberMe($user_data);
        }

//        LoginValidator::validateActive($CurrentUser->is_active);
        returnSuccessMessage(lang('SUC_LOGIN'), $data);

    }

}
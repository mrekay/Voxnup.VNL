<?php

class UserValidator extends DefaultValidator
{

    static function validatePassword($password)
    {
        global $CurrentUser;
        if (empty($password) || !EncryptionModule::PasswordVerify($password, $CurrentUser->storedPassword())) {
            throw new EmptyDataException(lang('ERR_PASSWORD_VALIDATION'));
        }
    }


}

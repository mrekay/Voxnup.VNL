<?php

class UserValidator extends DefaultValidator
{

    static function validatePassword($password)
    {
        global $CurrentUser;
        if (empty($password) || $CurrentUser->password != EncryptionModule::PasswordEncrypt($password)) {
            throw new EmptyDataException(lang('ERR_PASSWORD_VALIDATION'));
        }
    }


}

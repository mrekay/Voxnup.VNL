<?php
class action_login
{

    public function login($req)
    {
        global $cDefinitions;

        $data = [];

        $username = $req->post('email');
        $password = $req->post('password');

        LoginValidator::validateUsername($username);
        LoginValidator::validatePassword($password);

        $sessionId = "";
        if (!empty($req->post('rememberMe'))) {
            $sessionId = md5($username . $cDefinitions->getSaltText() . $password . time());
        }

        $user_data = User::select(["username" => $username, "password" => EncryptionModule::PasswordEncrypt($password)]);
        LoginValidator::validateUser($user_data);

        UserModule::SetUser($user_data);

        LoginValidator::validateSession(Session::checkSession());
        UserModule::SetCurrentUserInfo();

        if ($req->postHas('rememberMe') && !empty($req->post('rememberMe'))) {
            setcookie($cDefinitions->getCookiePrefix() . 'sessionId', $sessionId, time() + 3600 * 24 * 30, '/');
        }

//        LoginValidator::validateActive($CurrentUser->is_active);
        returnSuccessMessage(lang('SUC_LOGIN'), $data);

    }

}
<?php

class EncryptionModule
{
public static function PasswordEncrypt($pass)
    {
        return sha1(md5($pass));
    }

    public static function safeEncrypt($data)
    {
        return str_replace(array('/', '+'), array('_', '-'), @openssl_encrypt(json_encode($data), 'aes-128-cbc', '12?28a**038ss.y'));
    }

    public static function safeDecrypt($encrypted)
    {
        try {
            $result = json_decode(@openssl_decrypt(str_replace(array('_', '-'), array('/', '+'), $encrypted), 'aes-128-cbc', '12?28a**038ss.y'), true);
        } catch (Exception $e) {
            $result = false;
        }

        return $result;
    }
}
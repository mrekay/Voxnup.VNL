<?php

class CurlModule
{
    static function Curl($url, $post_params, $content_type = 'application/x-www-form-urlencoded', $basic_auth = ['username' => '', 'password' => ''])
    {

        switch ($content_type) {
            case 'application/json':
                $content = json_encode($post_params);
                break;
            default:
                $content = http_build_query($post_params);
                break;
        }

        $content_length = strlen($content);

        $options = array(
            'http' => array(
                'header' => ["Content-type: " . $content_type,(!empty($basic_auth) ?
                    "Authorization: Basic " . base64_encode($basic_auth['username'] . ":" . $basic_auth['password']):"")],
                'method' => 'GET',
                'content' => $content,
                "Content-Length: " => $content_length,
                'timeout' => 2000,  //1200 Seconds is 20 Minutes
                'ignore_errors' => true
            )
        );

        $context = stream_context_create($options);

        $result = @file_get_contents($url, false, $context);
        return $result;
    }

    static function Post($url, $post_params, $content_type = 'application/x-www-form-urlencoded', $basic_auth = ['username' => '', 'password' => ''])
    {

        switch ($content_type) {
            case 'application/json':
                $content = json_encode($post_params);
                break;
            default:
                $content = http_build_query($post_params);
                break;
        }

        $headers = ["Content-Type: $content_type", "Content-Length: " . strlen($content)];
        if (!empty($basic_auth['username'])) {
            $headers[] = "Authorization: Basic " . base64_encode($basic_auth['username'] . ":" . $basic_auth['password']);
        }

        $options = array(
            'http' => array(
                'header'  => $headers,
                'method'  => 'POST',
                'content' => $content,
                'timeout' => 2000,
                'ignore_errors' => true
            )
        );

        $context = stream_context_create($options);
        return @file_get_contents($url, false, $context);
    }

    static function GetCurlPage($url = '', $timeout = 5,$is_json = true)
    {
        if (empty($url)) return false;

        /* url'ye istek atıp dönen cevabı $result'a bas*/
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        $result = curl_exec($ch);
        curl_close($ch);

        if ($is_json)
        $result = json_decode($result, true);

        return $result;
    }


}
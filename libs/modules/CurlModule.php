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
                'method' => 'POST',
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

}
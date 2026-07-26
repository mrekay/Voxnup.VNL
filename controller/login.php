<?php

$meta = [
    'title' => lang('LOGIN_TITLE'),
    'description' => '',
    'keywords' => ''
];

$page_config = [
    'layout' => 'focus',
];

if ($CurrentUser->id > 0) {
    header('Location:' . SITE_URL . '/');
    exit;
}

require view('static/header');
require view('login');
require view('static/footer');

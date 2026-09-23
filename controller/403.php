<?php
/*
 * 403 — giriş yapılmış ama yetkisi olmayan kullanıcı (3.4.0).
 * vnl_require_permission() bu sayfayı çizer (yoksa 401'e düşer).
 */

http_response_code(403);

$meta = [
    'title' => lang('ERR_403_TITLE'),
    'description' => '',
    'keywords' => ''
];

$page_config = [
    'layout' => 'focus',
];

require view('static/header');
require view('403');
require view('static/footer');

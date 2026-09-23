<?php
/*
 * GET /logout — yalnız ONAY sayfası (3.4.0).
 *
 * 3.3'e kadar bu adres oturumu doğrudan kapatıyordu: başka bir sitedeki
 * <img src="https://panel/logout"> bile kullanıcıyı çıkarabiliyordu.
 * Çıkışın kendisi artık POST /app/login/logout (CSRF'li); bu sayfa ve
 * üst çubuktaki düğme onu çağırır.
 */

if (!vnl_logged_in()) {
    vnl_redirect_login();
}

$meta = [
    'title'       => lang('LOGOUT_TITLE'),
    'description' => '',
    'keywords'    => '',
];

$page_config = [
    'layout' => 'focus',
];

require view('static/header');
require view('logout');
require view('static/footer');

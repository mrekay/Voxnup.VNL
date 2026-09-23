<?php
$meta = [
    'title'       => 'Hata Yönetimi',
    'upper_title' => 'Dökümantasyon',
    'description' => 'VNL hata sayfası, exception sınıfları ve üretim yapılandırması',
    'keywords'    => '',
];

$page_config = [
    'topbar'        => true,
    'leftsidebar'   => true,
    'contentheader' => true,
];

require view('static/header');
require view('docs/errors');
require view('static/footer');

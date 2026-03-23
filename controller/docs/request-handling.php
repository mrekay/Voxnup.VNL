<?php
$meta = [
    'title'       => 'İstek Yönetimi ve Action\'lar',
    'upper_title' => 'Dökümantasyon',
    'description' => '',
    'keywords'    => '',
];

$page_config = [
    'topbar'        => true,
    'leftsidebar'   => true,
    'contentheader' => true,
    'public'        => true,
];

require view('static/header');
require view('docs/request-handling');
require view('static/footer');

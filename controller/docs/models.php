<?php
$meta = [
    'title'       => 'Model ve Veritabanı',
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
require view('docs/models');
require view('static/footer');

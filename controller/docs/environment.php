<?php
$meta = [
    'title'       => 'Ortam Yapılandırması ve Migrasyonlar',
    'upper_title' => 'Dökümantasyon',
    'description' => '.env anahtarları, VnlEnv okuyucusu ve veritabanı migrasyonları',
    'keywords'    => '',
];

$page_config = [
    'topbar'        => true,
    'leftsidebar'   => true,
    'contentheader' => true,
];

require view('static/header');
require view('docs/environment');
require view('static/footer');

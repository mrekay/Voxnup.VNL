<?php
$meta = [
    'title'       => 'Şablon Söz Dizimi',
    'upper_title' => 'Dökümantasyon',
    'description' => 'View dosyalarındaki {{ }} söz dizimi, direktifler ve şablon önbelleği',
    'keywords'    => '',
];

$page_config = [
    'topbar'        => true,
    'leftsidebar'   => true,
    'contentheader' => true,
];

require view('static/header');
require view('docs/templating');
require view('static/footer');

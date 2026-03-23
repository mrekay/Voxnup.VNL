<?php
$meta = [
    'title'       => 'Modüller (libs/modules)',
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
require view('docs/modules');
require view('static/footer');

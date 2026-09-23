<?php
$meta = [
    'title'       => 'Dil Sistemi (i18n)',
    'upper_title' => 'Dökümantasyon',
    'description' => '',
    'keywords'    => '',
];

$page_config = [
    'topbar'        => true,
    'leftsidebar'   => true,
    'contentheader' => true,
];

require view('static/header');
require view('docs/languages');
require view('static/footer');

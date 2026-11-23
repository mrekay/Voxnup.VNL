<?php
$meta = [
    'title'       => 'Modal Sistemi',
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
require view('docs/modals');
require view('docs/partials/pager');
require view('static/footer');

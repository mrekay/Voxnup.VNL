<?php
$meta = [
    'title'       => 'Konsol, Kuyruk ve Zamanlayıcı',
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
require view('docs/cli-queue');
require view('docs/partials/pager');
require view('static/footer');

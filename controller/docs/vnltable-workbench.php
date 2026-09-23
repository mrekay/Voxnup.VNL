<?php
$meta = [
    'title'       => 'VNL Table — Workbench',
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
require view('docs/vnltable-workbench');
require view('static/footer');

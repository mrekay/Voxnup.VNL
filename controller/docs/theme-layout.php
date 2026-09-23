<?php

$meta = [
    'title'       => 'Layout ve Preset\'ler',
    'upper_title' => 'VNL Theme El Kitabı',
    'description' => 'Layout preset\'leri ve $page_config referansı',
    'keywords'    => 'vnl theme, layout, preset',
];

$page_config = [
    'topbar'        => true,
    'leftsidebar'   => true,
    'contentheader' => true,
];

require view('static/header');
require view('docs/theme-layout');
require view('static/footer');

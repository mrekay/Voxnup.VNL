<?php

$meta = [
    'title'       => 'VNL Theme',
    'upper_title' => 'Dökümantasyon',
    'description' => 'VNL Theme — Bootstrap 5.3 tabanlı tema mimarisi, layout preset\'leri, token\'lar ve utility ekleri',
    'keywords'    => 'vnl theme, bootstrap, tema, layout, dark mode',
];

$page_config = [
    'topbar'        => true,
    'leftsidebar'   => true,
    'contentheader' => true,
    'public'        => true,
];

require view('static/header');
require view('docs/theme');
require view('static/footer');

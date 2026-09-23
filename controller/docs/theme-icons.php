<?php

$meta = [
    'title'       => 'İkonlar',
    'upper_title' => 'VNL Theme El Kitabı',
    'description' => 'Bootstrap Icons + Font Awesome birlikte kullanımı',
    'keywords'    => 'vnl theme, ikon, bootstrap icons, font awesome',
];

$page_config = [
    'topbar'        => true,
    'leftsidebar'   => true,
    'contentheader' => true,
];

require view('static/header');
require view('docs/theme-icons');
require view('static/footer');

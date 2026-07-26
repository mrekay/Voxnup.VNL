<?php

$meta = [
    'title'       => 'Metronic\'ten Geçiş',
    'upper_title' => 'VNL Theme El Kitabı',
    'description' => 'Metronic 8 projelerini VNL Theme\'e taşıma rehberi',
    'keywords'    => 'vnl theme, metronic, geçiş',
];

$page_config = [
    'topbar'        => true,
    'leftsidebar'   => true,
    'contentheader' => true,
    'public'        => true,
];

require view('static/header');
require view('docs/theme-migration');
require view('static/footer');

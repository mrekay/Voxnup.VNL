<?php

$meta = [
    'title'       => 'Bileşenler',
    'upper_title' => 'VNL Theme El Kitabı',
    'description' => 'VNL Theme bileşen ekleri — canlı örneklerle',
    'keywords'    => 'vnl theme, bileşen, badge, kart',
];

$page_config = [
    'topbar'        => true,
    'leftsidebar'   => true,
    'contentheader' => true,
    'public'        => true,
];

require view('static/header');
require view('docs/theme-components');
require view('static/footer');

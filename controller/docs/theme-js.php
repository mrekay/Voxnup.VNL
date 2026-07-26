<?php

$meta = [
    'title'       => 'JavaScript API',
    'upper_title' => 'VNL Theme El Kitabı',
    'description' => 'Tema ve VNL çekirdeğinin istemci API\'leri',
    'keywords'    => 'vnl theme, javascript, api',
];

$page_config = [
    'topbar'        => true,
    'leftsidebar'   => true,
    'contentheader' => true,
    'public'        => true,
];

require view('static/header');
require view('docs/theme-js');
require view('static/footer');

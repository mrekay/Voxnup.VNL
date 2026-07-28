<?php

$meta = [
    'title'       => 'Modallar',
    'upper_title' => 'VNL Theme El Kitabı',
    'description' => 'Modal şekilleri: boyutlar, scrollable, fullscreen, sağdan kayan sheet ve onay deseni',
    'keywords'    => 'vnl theme, modal, sheet, dialog',
];

$page_config = [
    'topbar'        => true,
    'leftsidebar'   => true,
    'contentheader' => true,
    'public'        => true,
];

require view('static/header');
require view('docs/theme-modals');
require view('static/footer');

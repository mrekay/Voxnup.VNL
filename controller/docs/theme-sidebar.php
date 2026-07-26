<?php

$meta = [
    'title'       => 'Sidebar ve Menü',
    'upper_title' => 'VNL Theme El Kitabı',
    'description' => 'Sidebar modları, flyout/accordion ve $SidebarMenu sözleşmesi',
    'keywords'    => 'vnl theme, sidebar, menü',
];

$page_config = [
    'topbar'        => true,
    'leftsidebar'   => true,
    'contentheader' => true,
    'public'        => true,
];

require view('static/header');
require view('docs/theme-sidebar');
require view('static/footer');

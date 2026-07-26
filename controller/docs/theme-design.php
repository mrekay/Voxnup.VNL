<?php

$meta = [
    'title'       => 'Renkler, Tema Modu ve Tipografi',
    'upper_title' => 'VNL Theme El Kitabı',
    'description' => 'Tasarım token\'ları: renkler, yüzeyler, tema modu, yazı ve boşluk ölçekleri',
    'keywords'    => 'vnl theme, renk, token, tipografi',
];

$page_config = [
    'topbar'        => true,
    'leftsidebar'   => true,
    'contentheader' => true,
    'public'        => true,
];

require view('static/header');
require view('docs/theme-design');
require view('static/footer');

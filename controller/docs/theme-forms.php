<?php

$meta = [
    'title'       => 'Form ve Select',
    'upper_title' => 'VNL Theme El Kitabı',
    'description' => 'Form varyantları ve SlimSelect köprüsü',
    'keywords'    => 'vnl theme, form, select, slimselect',
];

$page_config = [
    'topbar'        => true,
    'leftsidebar'   => true,
    'contentheader' => true,
];

require view('static/header');
require view('docs/theme-forms');
require view('static/footer');

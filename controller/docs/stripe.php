<?php

$meta = [
    'title'       => 'Stripe Extension',
    'upper_title' => 'Dökümantasyon',
    'description' => '',
    'keywords'    => '',
];

$page_config = [
    'topbar'        => true,
    'leftsidebar'   => true,
    'contentheader' => true,
];

require view('static/header');
require view('docs/stripe');
require view('docs/partials/pager');
require view('static/footer');

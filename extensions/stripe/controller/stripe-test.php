<?php

$meta = [
    'title'       => 'Stripe Test — Fiyatlandırma',
    'upper_title' => 'Stripe Extension',
    'description' => '',
    'keywords'    => '',
];

$page_config = [
    'topbar'        => true,
    'leftsidebar'   => true,
    'contentheader' => true,
];

require view('static/header');
require STRIPE_EXT_PATH . '/view/stripe-test.php';
require view('static/footer');

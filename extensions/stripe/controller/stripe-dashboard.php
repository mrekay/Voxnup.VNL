<?php

$meta = [
    'title'       => 'Abonelik Yönetimi',
    'upper_title' => 'Stripe Extension',
    'description' => '',
    'keywords'    => '',
];

$page_config = [
    'topbar'        => true,
    'leftsidebar'   => true,
    'contentheader' => true,
    'toolbaritems'  => [
        [
            'type'   => 'a',
            'text'   => 'Fiyatlandırma',
            'class'  => 'btn btn-sm btn-light-primary fw-bold',
            'custom' => 'href="/stripe-test"',
            'icon'   => 'ki-duotone ki-price-tag',
        ],
    ],
];

require view('static/header');
require STRIPE_EXT_PATH . '/view/stripe-dashboard.php';
require view('static/footer');

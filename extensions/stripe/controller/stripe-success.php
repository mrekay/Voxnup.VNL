<?php

$meta = [
    'title'       => 'Ödeme Başarılı',
    'upper_title' => 'Stripe Extension',
    'description' => '',
    'keywords'    => '',
];

$page_config = [
    'topbar'        => true,
    'leftsidebar'   => true,
    'contentheader' => false,
];

require view('static/header');
require STRIPE_EXT_PATH . '/view/stripe-success.php';
require view('static/footer');

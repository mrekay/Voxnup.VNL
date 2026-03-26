<?php

/**
 * Stripe Extension Bootstrap
 *
 * VNL'in extension loader'ı tarafından otomatik çalıştırılır.
 * Buraya dokunmayın — ayarlar için config.php'yi düzenleyin.
 */

defined('STRIPE_EXT_PATH') || define('STRIPE_EXT_PATH', __DIR__);

// ─── 1. Stripe PHP SDK kontrolü ───────────────────────────────────────────────
if (!class_exists(\Stripe\Stripe::class)) {
    throw new RuntimeException(
        '[Stripe Extension] stripe/stripe-php paketi bulunamadı. ' .
        'libs/ klasöründe `composer require stripe/stripe-php` komutunu çalıştırın.'
    );
}

// ─── 2. Config yükle ──────────────────────────────────────────────────────────
$_stripeConfig = require STRIPE_EXT_PATH . '/config.php';

$_stripeMode   = $_stripeConfig['test_mode'] ? 'test' : 'live';
define('STRIPE_SECRET_KEY',     $_stripeConfig[$_stripeMode . '_secret_key']);
define('STRIPE_PUBLIC_KEY',     $_stripeConfig[$_stripeMode . '_public_key']);
define('STRIPE_WEBHOOK_SECRET', $_stripeConfig[$_stripeMode . '_webhook_secret']);
define('STRIPE_TEST_MODE',      (bool)$_stripeConfig['test_mode']);

$GLOBALS['stripe_plans'] = $_stripeConfig['plans'] ?? [];

unset($_stripeConfig, $_stripeMode);

// ─── 3. Extension sınıflarını yükle ──────────────────────────────────────────
require_once STRIPE_EXT_PATH . '/src/StripeModule.php';
require_once STRIPE_EXT_PATH . '/src/StripePayment.php';
require_once STRIPE_EXT_PATH . '/src/StripeSubscription.php';

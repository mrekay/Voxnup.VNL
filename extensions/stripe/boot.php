<?php

/**
 * Stripe Extension Bootstrap
 *
 * VNL'in extension loader'ı tarafından otomatik çalıştırılır.
 * Buraya dokunmayın — ayarlar için config.php'yi düzenleyin.
 */

defined('STRIPE_EXT_PATH') || define('STRIPE_EXT_PATH', __DIR__);

// ─── 1. Stripe PHP SDK ────────────────────────────────────────────────────────
// VNL 3.0: SDK çekirdek vendor'dan çıkarıldı, eklenti kendi bağımlılığını
// taşıyor. Eklenti kapalıyken bu dosya hiç yüklenmediği için 2.4 MB'lık
// paket, Stripe kullanmayan projelere de girmiyor.
if (!class_exists(\Stripe\Stripe::class)) {
    $_stripeAutoload = STRIPE_EXT_PATH . '/vendor/autoload.php';

    if (!is_file($_stripeAutoload)) {
        throw new RuntimeException(
            '[Stripe Extension] Bağımlılıklar kurulmamış. ' .
            'extensions/stripe/ dizininde `composer install` çalıştırın.'
        );
    }

    require_once $_stripeAutoload;
    unset($_stripeAutoload);
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

// ─── 3. Herkese açık sayfalar (VNL 3.4.0) ────────────────────────────────────
// Çekirdek, girişsiz isteğe denetleyiciyi ÇALIŞTIRMADAN karar verir;
// $page_config['public'] artık yetmez. Stripe'ın sunucudan sunucuya çağırdığı
// webhook girişsiz açılır — imzayı denetleyicinin kendisi doğrular.
// (success/cancel sayfalarına kullanıcı kendi oturumuyla döner; açılmaz.)
vnl_public_page('stripe-webhook');

// ─── 4. Extension sınıflarını yükle ──────────────────────────────────────────
require_once STRIPE_EXT_PATH . '/src/StripeModule.php';
require_once STRIPE_EXT_PATH . '/src/StripePayment.php';
require_once STRIPE_EXT_PATH . '/src/StripeSubscription.php';

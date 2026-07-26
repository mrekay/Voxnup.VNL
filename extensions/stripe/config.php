<?php

/**
 * Stripe Extension Config
 *
 * Test  → sk_test_ / pk_test_
 * Live  → sk_live_ / pk_live_
 *
 * Webhook secret:
 *   Local : stripe listen --forward-to https://site.com/stripe-webhook
 *   Prod  : Stripe Dashboard → Developers → Webhooks → Endpoint secret
 */
return [
    'test_mode' => true,

    'test_secret_key'      => 'sk_test_',
    'test_public_key'      => 'pk_test_',
    'test_webhook_secret'  => 'whsec_',

    'live_secret_key'      => 'sk_live_',
    'live_public_key'      => 'pk_live_',
    'live_webhook_secret'  => 'whsec_',

    /*
     * Pricing sayfasında gösterilecek planlar.
     * price_id → Stripe Dashboard > Products > Price ID
     * mode     → 'subscription' | 'payment'
     */
    'plans' => [
        [
            'id'          => 'starter',
            'name'        => 'Başlangıç',
            'price'       => '₺99',
            'period'      => '/ay',
            'price_id'    => 'price_REPLACE_ME',   // ← Stripe Price ID
            'mode'        => 'subscription',
            'description' => 'Bireysel kullanım için ideal',
            'features'    => [
                '5 Proje',
                '10 GB Depolama',
                'E-posta Desteği',
            ],
            'popular'     => false,
            'btn_text'    => 'Başla',
            'btn_class'   => 'btn-soft-primary',
        ],
        [
            'id'          => 'pro',
            'name'        => 'Pro',
            'price'       => '₺299',
            'period'      => '/ay',
            'price_id'    => 'price_REPLACE_ME',   // ← Stripe Price ID
            'mode'        => 'subscription',
            'description' => 'Büyüyen ekipler için',
            'features'    => [
                'Sınırsız Proje',
                '100 GB Depolama',
                'Öncelikli Destek',
                'API Erişimi',
            ],
            'popular'     => true,
            'btn_text'    => 'Pro\'ya Geç',
            'btn_class'   => 'btn-primary',
        ],
        [
            'id'          => 'lifetime',
            'name'        => 'Ömür Boyu',
            'price'       => '₺2.499',
            'period'      => ' tek seferlik',
            'price_id'    => 'price_REPLACE_ME',   // ← Stripe Price ID
            'mode'        => 'payment',
            'description' => 'Bir kez öde, sonsuza kullan',
            'features'    => [
                'Sınırsız Her Şey',
                'Tüm Güncellemeler',
                'VIP Destek',
                'Erken Erişim',
            ],
            'popular'     => false,
            'btn_text'    => 'Satın Al',
            'btn_class'   => 'btn-soft-success',
        ],
    ],
];

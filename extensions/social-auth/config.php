<?php

/**
 * Social Auth Extension Config
 *
 * Her sağlayıcı yalnız anahtarları doluysa etkin. Geri dönüş adresi her sağlayıcıda:
 *   {site}/oauth/{apple|microsoft|facebook}/callback
 * Kurulum adımları: README.md.
 */
return [
    'providers' => [
        // Google Cloud → Credentials → OAuth client (Web). Yalnız Client ID.
        'google' => [
            'client_ids' => [(string)VnlEnv::get('GOOGLE_OAUTH_CLIENT_ID', '')],
        ],
        // Apple Developer → Identifiers → Services ID (ör. com.example.web).
        'apple' => [
            'client_ids' => [(string)VnlEnv::get('APPLE_SERVICES_ID', '')],
        ],
        // Microsoft Entra → App registrations. tenant: common (kişisel + iş),
        // consumers (yalnız kişisel) ya da organizations.
        'microsoft' => [
            'client_id' => (string)VnlEnv::get('MICROSOFT_CLIENT_ID', ''),
            'tenant'    => 'common',
        ],
        // Meta for Developers → App → Facebook Login. App Secret sunucuda kalır.
        'facebook' => [
            'app_id'               => (string)VnlEnv::get('FACEBOOK_APP_ID', ''),
            'app_secret'           => (string)VnlEnv::get('FACEBOOK_APP_SECRET', ''),
            'graph_version'        => 'v26.0',
            'deletion_status_path' => '/privacy',
        ],
    ],

    // Düğmelerin gösterilmeyeceği istemciler (regex): sağlayıcılar gömülü WebView'de
    // girişi reddeder.
    'blocked_user_agents' => [
        // '/myapp/i',                     // kendi masaüstü/mobil kabuğunuzun UA imzası
        '/; wv\)/',                        // Android WebView
        '/FBAN|FBAV|FB_IAB|Instagram|Line\/|Snapchat|TikTok|musical_ly/i', // uygulama içi tarayıcılar
    ],

    // Geri dönüş adreslerinin kökü. Boşsa istekten (şema + Host) türetilir.
    'base_url' => '',

    // Dönüş yolu yoksa/geçersizse.
    'default_return' => '/login',

    // İmza anahtarı (JWKS) önbelleği; boşsa sistem tmp.
    'cache_dir' => '',

    // null → VNL RateLimiter (tbl_vnl_rate_limits; vnl/migrations/0003), IP başına
    // 10 dakikada 30 deneme. Kendi sayacınız varsa: fn(string $ip): bool (true = geçebilir).
    'rate_limit' => null,
];

<?php

/**
 * Social Auth Extension Bootstrap — Google, Apple, Microsoft, Facebook ile giriş.
 *
 * extensions/extensions.php içinde 'social-auth' => true. Ayarlar config.php'de;
 * anahtarı tanımlı olmayan sağlayıcının düğmesi çıkmaz, ucu 404 döner.
 *
 * Projenin sağlaması gereken kancalar (bkz. README.md):
 *   vnl_social_sign_in(array $profile): array          zorunlu
 *   vnl_social_forget(string $provider, string $sub)   Facebook veri silme (isteğe bağlı)
 */

defined('SOCIAL_AUTH_EXT_PATH') || define('SOCIAL_AUTH_EXT_PATH', __DIR__);

$GLOBALS['vnl_social_auth_config'] = require SOCIAL_AUTH_EXT_PATH . '/config.php';

require_once SOCIAL_AUTH_EXT_PATH . '/src/OidcVerifier.php';
require_once SOCIAL_AUTH_EXT_PATH . '/src/SocialProvider.php';
require_once SOCIAL_AUTH_EXT_PATH . '/src/SocialAuth.php';
require_once SOCIAL_AUTH_EXT_PATH . '/src/providers/GoogleProvider.php';
require_once SOCIAL_AUTH_EXT_PATH . '/src/providers/AppleProvider.php';
require_once SOCIAL_AUTH_EXT_PATH . '/src/providers/MicrosoftProvider.php';
require_once SOCIAL_AUTH_EXT_PATH . '/src/providers/FacebookProvider.php';

// /oauth/... giriş yapmamış kullanıcıya açık.
if (function_exists('vnl_public_page')) {
    vnl_public_page('oauth');
}

<?php
/**
 * Yerel geliştirme/smoke-test router'ı (php -S için).
 *
 *   docker run --rm -p 8080:8080 -v "%cd%":/app -w /app php:8.2-cli \
 *     php -S 0.0.0.0:8080 theme/tools/dev-router.php
 *
 * Gerçek dosyalar (assets/…) doğrudan servis edilir; diğer her istek
 * .htaccess'teki gibi index.php'ye düşer.
 */

if (php_sapi_name() === 'cli-server') {
    $url = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $file = __DIR__ . '/../..' . $url;
    if ($url !== '/' && file_exists($file) && !is_dir($file)) {
        return false;
    }
}

require __DIR__ . '/../../index.php';

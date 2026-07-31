<?php
/*
 * ============================================================
 *  Voxnup Not Laravel (VNL)
 *  Yerel gelistirme/smoke-test router'i (php -S icin)
 *  Authors: Voxnup Authors
 * ============================================================
 */
/**
 * Yerel geliştirme/smoke-test router'ı (php -S için).
 *
 *   docker run --rm -p 8080:8080 -v "%cd%":/app -w /app php:8.2-cli \
 *     php -S 0.0.0.0:8080 vnl/tools/dev-router.php
 *
 * Gerçek dosyalar (assets/…, vnl/assets/…) doğrudan servis edilir; diğer
 * her istek .htaccess'teki gibi index.php'ye düşer.
 */

$root = dirname(__DIR__, 2);

if (php_sapi_name() === 'cli-server') {
    $url = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

    /* .env, .git, .htaccess … — .htaccess bunları engelliyor, yerel sunucu
       engellemiyordu ve düz metin olarak servis ediyordu. Konsol giriş
       noktası da üretimde olduğu gibi burada da kapalı. */
    foreach (explode('/', trim((string)$url, '/')) as $segment) {
        if ($segment !== '' && ($segment[0] === '.' || $segment === 'vnl.php' || $segment === 'vnl.cmd')) {
            http_response_code(403);
            die('403');
        }
    }

    $file = $root . $url;
    if ($url !== '/' && file_exists($file) && !is_dir($file)) {
        return false;
    }
}

require $root . '/index.php';

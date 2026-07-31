<?php
/*!
 * ============================================================
 *  Voxnup Not Laravel (VNL) — Konsol Dağıtıcısı
 *  Komutu bulur, çalıştırır, çıkış kodunu döndürür.
 *  Authors: Voxnup Authors
 * ============================================================
 *
 * Komutlar vnl/tools/ altında birer dosyadır ve her biri
 * `return function (array $args): int { ... };` ile biter. Yeni komut
 * eklemek için dosyayı açıp aşağıdaki listeye bir satır yazmak yeter.
 *
 * Çekirdeği ayağa kaldırmaz. Komutların çoğu (migrate, update, doctor)
 * uygulamanın açılmadığı anda çalıştırılır; oturum, kullanıcı ve
 * yönlendirme makinesine bağlanmaları onları işe yaramaz hale getirirdi.
 * İhtiyacı olan komut kendi bağlantısını kendi kurar.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    die("VNL konsolu yalnizca komut satirindan calisir.\n");
}

if (!defined('VNL_ROOT')) {
    define('VNL_ROOT', dirname(__DIR__));
}

define('VNL_PATH', __DIR__);

/** komut => [dosya, tek satırlık açıklama] */
const VNL_COMMANDS = [
    'migrate' => ['migrate.php', 'Bekleyen sema degisikliklerini uygular'],
    'update'  => ['update.php',  'Cekirdegi yeni surume gunceller'],
    'doctor'  => ['doctor.php',  'Kurulumu bu cekirdegin beklentisine karsi sinar'],
    'release' => ['release.php', 'Yayin arsivi uretir (bakim icin)'],
    'version' => ['version.php', 'Cekirdek ve proje surumlerini gosterir'],
    'serve'   => ['serve.php',   'Yerel gelistirme sunucusunu baslatir'],
];

$argv    = $_SERVER['argv'] ?? [];
$command = $argv[1] ?? '';
$args    = array_slice($argv, 2);

if ($command === '' || $command === 'help' || $command === '--help' || $command === '-h') {
    $version = is_file(VNL_PATH . '/VERSION')
        ? trim((string)file_get_contents(VNL_PATH . '/VERSION'))
        : '?';

    fwrite(STDOUT, "\n  VNL {$version}\n\n    php vnl.php <komut> [secenekler]\n\n");
    foreach (VNL_COMMANDS as $name => [$file, $summary]) {
        fwrite(STDOUT, sprintf("    %-9s %s\n", $name, $summary));
    }
    fwrite(STDOUT, "\n  Bir komutun secenekleri icin: php vnl.php <komut> --help\n\n");

    exit($command === '' ? 1 : 0);
}

if (!isset(VNL_COMMANDS[$command])) {
    fwrite(STDERR, "\n  Bilinmeyen komut: {$command}\n  Komut listesi: php vnl.php\n\n");
    exit(1);
}

$handler = require VNL_PATH . '/tools/' . VNL_COMMANDS[$command][0];

exit((int)$handler($args));

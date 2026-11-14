<?php
/*!
 * ============================================================
 *  Voxnup Not Laravel (VNL) — Konsol Dağıtıcısı
 *  Komutu bulur, çalıştırır, çıkış kodunu döndürür.
 *  Authors: Voxnup Authors
 * ============================================================
 *
 * Çekirdek komutları vnl/tools/ altında birer dosyadır ve her biri
 * `return function (array $args): int { ... };` ile biter.
 *
 * Proje komutları (3.4.0): VNL_ROOT/commands/<ad>.php — aynı sözleşme.
 * Komut adında ':' kullanılabilir, dosya adında '-' olur:
 *
 *   php vnl.php backup:prune   →  commands/backup-prune.php
 *
 * Dosyanın içinde `@summary Tek satır açıklama` geçerse yardım listesinde
 * görünür. Çekirdek komut adları ezilemez.
 *
 * Dağıtıcı çekirdeği KENDİLİĞİNDEN ayağa kaldırmaz. Komutların çoğu
 * (migrate, update, doctor) uygulamanın açılmadığı anda çalıştırılır.
 * Uygulamaya (Eloquent modelleri, Queue, Audit, lang()) ihtiyacı olan komut
 * ilk satırında vnl_cli_boot() çağırır.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    die("VNL konsolu yalnizca komut satirindan calisir.\n");
}

if (!defined('VNL_ROOT')) {
    define('VNL_ROOT', dirname(__DIR__));
}

if (!defined('VNL_PATH')) {
    define('VNL_PATH', __DIR__);
}

/** komut => [dosya, tek satırlık açıklama] */
const VNL_COMMANDS = [
    'migrate'       => ['migrate.php',       'Bekleyen sema degisikliklerini uygular'],
    'update'        => ['update.php',        'Cekirdegi yeni surume gunceller'],
    'doctor'        => ['doctor.php',        'Kurulumu bu cekirdegin beklentisine karsi sinar'],
    'release'       => ['release.php',       'Yayin arsivi uretir (bakim icin)'],
    'version'       => ['version.php',       'Cekirdek ve proje surumlerini gosterir'],
    'serve'         => ['serve.php',         'Yerel gelistirme sunucusunu baslatir'],
    'user:create'   => ['user-create.php',   'Kullanici olusturur (--super ile yonetici)'],
    'user:password' => ['user-password.php', 'Kullanicinin parolasini degistirir'],
    'user:totp'     => ['user-totp.php',     'Iki adimli dogrulamayi acar/kapatir'],
    'token:create'  => ['token-create.php',  'API jetonu uretir'],
    'token:revoke'  => ['token-revoke.php',  'API jetonunu iptal eder'],
    'queue:work'    => ['queue-work.php',    'Kuyruk iscisini calistirir'],
    'schedule:run'  => ['schedule-run.php',  'Zamani gelen gorevleri calistirir (cron: her dakika)'],
    'test'          => ['test.php',          'Cekirdek ve proje testlerini calistirir'],
];

require_once __DIR__ . '/cli.php';

/** Proje komutunun dosyası; ad geçersizse ya da dosya yoksa null. */
function vnl_project_command_path(string $name): ?string
{
    if (!preg_match('/^[a-z0-9][a-z0-9:_-]*$/', $name)) {
        return null;
    }

    $path = VNL_ROOT . '/commands/' . str_replace(':', '-', $name) . '.php';

    return is_file($path) ? $path : null;
}

/** commands/ altındaki proje komutları: ad => açıklama */
function vnl_project_commands(): array
{
    $commands = [];

    foreach (glob(VNL_ROOT . '/commands/*.php') ?: [] as $path) {
        $file = basename($path, '.php');

        if (!preg_match('/^[a-z0-9][a-z0-9_-]*$/', $file)) {
            continue;
        }

        /* Dosya adındaki ilk '-' komutta ':' olur: backup-prune → backup:prune.
           Aynı ad dosyada doğrudan da çağrılabilir (backup-prune). */
        $name    = preg_replace('/-/', ':', $file, 1);
        $summary = '';

        $head = (string)@file_get_contents($path, false, null, 0, 4096);
        if (preg_match('/@summary\s+(.+)/', $head, $m)) {
            $summary = trim($m[1], " \t*/");
        }

        if (!isset(VNL_COMMANDS[$name])) {
            $commands[$name] = $summary;
        }
    }

    ksort($commands);

    return $commands;
}

$argv    = $_SERVER['argv'] ?? [];
$command = $argv[1] ?? '';
$args    = array_slice($argv, 2);

if ($command === '' || $command === 'help' || $command === '--help' || $command === '-h') {
    $version = is_file(VNL_PATH . '/VERSION')
        ? trim((string)file_get_contents(VNL_PATH . '/VERSION'))
        : '?';

    fwrite(STDOUT, "\n  VNL {$version}\n\n    php vnl.php <komut> [secenekler]\n\n  CEKIRDEK\n");
    foreach (VNL_COMMANDS as $name => [$file, $summary]) {
        fwrite(STDOUT, sprintf("    %-14s %s\n", $name, $summary));
    }

    $project = vnl_project_commands();
    if ($project !== []) {
        fwrite(STDOUT, "\n  PROJE  (commands/)\n");
        foreach ($project as $name => $summary) {
            fwrite(STDOUT, sprintf("    %-14s %s\n", $name, $summary));
        }
    }

    fwrite(STDOUT, "\n  Bir komutun secenekleri icin: php vnl.php <komut> --help\n\n");

    exit($command === '' ? 1 : 0);
}

if (isset(VNL_COMMANDS[$command])) {
    $handler = require VNL_PATH . '/tools/' . VNL_COMMANDS[$command][0];
} elseif (($projectPath = vnl_project_command_path($command)) !== null) {
    $handler = require $projectPath;
} else {
    fwrite(STDERR, "\n  Bilinmeyen komut: {$command}\n  Komut listesi: php vnl.php\n\n");
    exit(1);
}

if (!is_callable($handler)) {
    fwrite(STDERR, "\n  Komut dosyasi bir fonksiyon dondurmuyor: {$command}\n"
        . "  Sozlesme: return function (array \$args): int { ... };\n\n");
    exit(1);
}

exit((int)$handler($args));

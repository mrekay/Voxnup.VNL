<?php
/*!
 * ============================================================
 *  Voxnup Not Laravel (VNL) — Çekirdek Kurulumu
 *  Oturum, otomatik yükleyici, sabitler ve kademeli yükleme.
 *  Authors: Voxnup Authors
 * ============================================================
 */

ini_set('display_errors', 1);
error_reporting(E_ALL ^ E_WARNING);
ini_set('session.gc_maxlifetime', 3600);
set_time_limit(20);

/**
 * Oturum çerezi.
 *
 * secure ve samesite SABİT olamaz: SameSite=None, Secure'u zorunlu kılar
 * ve Secure çerez HTTP üzerinde tarayıcı tarafından hiç saklanmaz. İkisi
 * birden açıkken yerel http geliştirmede oturum hiç kurulmuyordu — giriş
 * dahil. Bağlantı gerçekten TLS ise eski davranış aynen sürer.
 */
$isSecure = (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off')
    || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')
    || ((int)($_SERVER['SERVER_PORT'] ?? 0) === 443);

session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '', // Gerekirse belirtebilirsiniz
    'secure' => $isSecure,
    'httponly' => true,
    'samesite' => $isSecure ? 'None' : 'Lax',
]);

$cDefinitions = new ConfigDefinitions();

/* ------------------------------------------------------------------ *
 *  Kademeli yükleme — çekirdek/proje sınırının çalışan tarafı
 * ------------------------------------------------------------------ */

/**
 * Bir dizinin çekirdek ve proje sürümlerini DOSYA ADINA GÖRE birleştirip yükler.
 *
 * "İkisini de yükle, sonuncusu kazansın" işlemez: PHP'de aynı sınıfı ikinci
 * kez tanımlamak ölümcül hatadır. Bu yüzden önce birleştirip her addan tek
 * dosya seçiyoruz — projedeki varsa o, yoksa çekirdekteki.
 *
 * Yani vnl/modules/MailModule.php'yi değiştirmek için çekirdeğe dokunmak
 * gerekmez; modules/MailModule.php açmak yeter ve güncelleme onu silmez.
 */
function vnl_load_dir(string $relative): void
{
    $files = [];

    foreach ([VNL_PATH, VNL_ROOT] as $base) {
        foreach (glob($base . '/' . trim($relative, '/') . '/*.php') ?: [] as $path) {
            $files[basename($path)] = $path;
        }
    }

    ksort($files);

    foreach ($files as $path) {
        require_once $path;
    }
}

/**
 * Sınıf otomatik yükleyicisi.
 *
 * Önce proje, sonra çekirdek. VNL 3.0'a kadar ikinci sırada $maindocumentRoot
 * yoklanıyordu; o değişken hiçbir yerde tanımlanmamıştı — yani ikinci kök
 * fikri yarım kalmıştı. Yeri artık vnl/classes/.
 */
function loadClass($class)
{
    $folder = explode('_', $class)[0];
    $folder = $folder === $class ? '' : $folder . '/';

    foreach ([VNL_ROOT . '/classes/', VNL_PATH . '/classes/'] as $base) {
        $path = $base . $folder . $class . '.php';

        if (is_file($path)) {
            require_once $path;

            return;
        }
    }
}

spl_autoload_register('loadClass');

/* Yardımcılar: controller(), view(), lang() ve arkadaşları. Projenin kendi
   yardımcıları helpers/ altındadır; eski libs/helper/special/ oraya taşındı. */
vnl_load_dir('helpers');

ob_start();
date_default_timezone_set('Europe/Istanbul');//'America/Los_Angeles'

$assets_address = $cDefinitions->getAssetsUrl();
define('SITE_HTTP', (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off' ? 'https://' : 'http://'));
define('SITE_URL_NON_HTTP', $_SERVER["HTTP_HOST"] ?? '');
define('MAIN_SITE_URL', $cDefinitions->getMasterSiteUrl());
define('SITE_URL', $cDefinitions->getSiteUrl());

/* VNL'in kendi varlıkları. Proje onları barındırmaz, yalnızca servis eder;
   CDN'e taşınabilsinler diye adres ortamdan okunur. vnl_asset() bunu kullanır. */
define('VNL_ASSETS_URL', (string)VnlEnv::get('VNL_ASSETS_URL', '/vnl/assets'));

/* CLI'da session_start() başlık gönderemez. Eskiden bu, DOCUMENT_ROOT boş mu
   diye bakılarak dolaylı yoldan anlaşılıyordu; artık doğrudan soruluyor. */
if (PHP_SAPI !== 'cli') {
    Session::init();
}

$req = new Request();
Language::set(Language::GetLanguage());

$PdfPrintEnabled = true;

vnl_load_dir('modules');
vnl_load_dir('exceptions');
vnl_load_dir('validators');

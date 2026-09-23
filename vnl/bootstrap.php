<?php
/*!
 * ============================================================
 *  Voxnup Not Laravel (VNL) — Çekirdek Kurulumu
 *  Oturum, otomatik yükleyici, sabitler ve kademeli yükleme.
 *  Authors: Voxnup Authors
 * ============================================================
 */

/**
 * Hata gösterimi (3.4.0).
 *
 * VNL 3.4'e kadar display_errors koşulsuz açıktı: canlıda her uyarı, dosya
 * yoluyla birlikte sayfaya ve JSON yanıtlarının ortasına basılıyordu. Artık
 * yalnız APP_DEBUG=true iken gösterilir; kapalıyken her şey günlüğe gider.
 * PHP 8.4'te vendor'ın E_DEPRECATED uyarıları da canlıda susturulur.
 */
ini_set('display_errors', vnl_debug() ? '1' : '0');
ini_set('log_errors', '1');
error_reporting(vnl_debug() ? E_ALL : (E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED & ~E_NOTICE));

/* Boşta kalma sınırı (UserModule::SetCurrentUserInfo uygular). Çöp toplayıcı
   oturumu bu süreden önce silmemeli, yoksa sınır anlamını yitirir. */
ini_set('session.gc_maxlifetime', (string)max(3600, (int)VnlEnv::get('VNL_SESSION_IDLE', 3600)));

/* Web isteği başına 20 sn PHP çalışma süresi. Uzun işler kuyruğa gider
   (php vnl.php queue:work); konsol sınırı vnl_cli_boot() kaldırır. */
if (PHP_SAPI !== 'cli') {
    set_time_limit(20);
}

/**
 * Bağlantı TLS mi? Vekil sunucu arkasında X-Forwarded-Proto okunur.
 * Sonuç VNL_HTTPS sabitinde durur; çerez yazan her yer ona bakar.
 */
$isSecure = (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off')
    || (strtolower($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')
    || ((int)($_SERVER['SERVER_PORT'] ?? 0) === 443);

if (!defined('VNL_HTTPS')) {
    define('VNL_HTTPS', $isSecure);
}

/**
 * Oturum çerezi (3.4.0).
 *
 * SameSite varsayılanı her zaman Lax. VNL 3.3'e kadar TLS'te 'None'
 * yazılıyordu: tarayıcı oturum çerezini BAŞKA sitelerden gelen POST'lara
 * da ekliyordu ve CSRF koruması olmadığı için herhangi bir site, yöneticinin
 * oturumuyla panelde işlem yaptırabiliyordu. Lax, üst düzey GET
 * gezinmelerinde çerezi göndermeye devam eder (dış bağlantıdan gelen
 * kullanıcı girişli kalır), siteler arası POST/fetch'te göndermez.
 *
 * VNL_SESSION_SAMESITE=Strict|Lax|None ile değiştirilebilir. None, Secure
 * olmadan tarayıcıda reddedildiği için düz HTTP'de Lax'a düşer.
 *
 * use_strict_mode: sunucuda karşılığı olmayan, dışarıdan dayatılmış bir
 * oturum kimliği kabul edilmez; PHP yenisini üretir (session fixation).
 */
$vnl_samesite = ucfirst(strtolower((string)VnlEnv::get('VNL_SESSION_SAMESITE', 'Lax')));
if (!in_array($vnl_samesite, ['Lax', 'Strict', 'None'], true) || ($vnl_samesite === 'None' && !$isSecure)) {
    $vnl_samesite = 'Lax';
}

if (PHP_SAPI !== 'cli') {
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.use_trans_sid', '0');
    ini_set('session.cookie_httponly', '1');

    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'domain'   => '', // Gerekirse belirtebilirsiniz
        'secure'   => $isSecure,
        'httponly' => true,
        'samesite' => $vnl_samesite,
    ]);
}
unset($vnl_samesite);

/**
 * Güvenlik başlıkları (3.4.0) — VNL_SECURITY_HEADERS=false ile kapatılır.
 *
 * CSP bilerek YOK: tema ve sayfalar satır içi <script> kullanıyor; nonce'suz
 * bir CSP ya hiçbir şeyi engellemez ya da her sayfayı kırar. İleri iş.
 */
if (PHP_SAPI !== 'cli' && !headers_sent() && VnlEnv::get('VNL_SECURITY_HEADERS', true) !== false) {
    header('X-Frame-Options: SAMEORIGIN');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: same-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=()');

    if ($isSecure) {
        header('Strict-Transport-Security: max-age=31536000');
    }
}

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

/* PHP saat dilimi (.env: APP_TIMEZONE). MySQL oturumunun saat dilimine
   dokunulmaz: ikisi bağımsızdır; NOW() ile date() farklı dilimde olabilir. */
$vnl_tz = (string)VnlEnv::get('APP_TIMEZONE', 'Europe/Istanbul');
if (!@date_default_timezone_set($vnl_tz)) {
    error_log('[VNL][Boot] APP_TIMEZONE geçersiz (' . $vnl_tz . '); Europe/Istanbul kullanılıyor.');
    date_default_timezone_set('Europe/Istanbul');
}
unset($vnl_tz);

$assets_address = $cDefinitions->getAssetsUrl();
/* TLS'i sonlandıran bir vekil (nginx → php-fpm) arkasında da doğru şema. */
define('SITE_HTTP', VNL_HTTPS ? 'https://' : 'http://');
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

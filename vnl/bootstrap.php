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

/* Web isteği başına PHP çalışma süresi (sn). Uzun işler kuyruğa gider
   (php vnl.php queue:work); konsol sınırı vnl_cli_boot() kaldırır.
   4.0.0: VNL_TIME_LIMIT ile ayarlanır (0 = sınırsız; geçersizse 20). */
if (PHP_SAPI !== 'cli') {
    $vnl_time_limit = (string)VnlEnv::get('VNL_TIME_LIMIT', '20');
    set_time_limit(ctype_digit($vnl_time_limit) ? (int)$vnl_time_limit : 20);
    unset($vnl_time_limit);
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
    /* Çerez adı (4.0.0): PHPSESSID yerine VNLSESSID. Aynı alan adında başka
       bir PHP uygulamasının oturumuyla çakışmaz. Geçersiz ad PHP'yi uyarıya
       düşürdüğü için yalnız harf/rakam kabul edilir, değilse varsayılan. */
    $vnl_session_name = (string)VnlEnv::get('VNL_SESSION_NAME', 'VNLSESSID');
    session_name(preg_match('/^[A-Za-z][A-Za-z0-9]{0,63}$/', $vnl_session_name) ? $vnl_session_name : 'VNLSESSID');
    unset($vnl_session_name);

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
 * Güvenlik başlıkları (3.4.0) — VNL_SECURITY_HEADERS=false hepsini kapatır.
 *
 * 4.0.0: Her başlığın değeri ayrı ayarlanır; boş ya da false o başlığı
 * göndermez. 3.x yalnız toptan kapatmaya izin veriyordu — mikrofon isteyen
 * bir proje bütün başlıklardan vazgeçmek zorunda kalıyordu. nosniff
 * ayarlanamaz: kapatılmasını gerektiren meşru bir durum yok.
 *
 * Referrer-Policy varsayılanı same-origin → strict-origin-when-cross-origin
 * (tarayıcıların kendi varsayılanı). same-origin dış sitelere hiç Referer
 * göndermiyordu; YouTube gibi gömmeler bu yüzden oynatmayı reddediyor.
 * Karşı siteye yalnız köken (https://alan.com) gider, yol ve sorgu gitmez.
 *
 * 4.0.0: Content-Security-Policy. Tema ve sayfalar satır içi <script> ve
 * onclick kullanıyor; nonce'suz bir script-src ya hiçbir şeyi engellemez
 * ('unsafe-inline') ya da her sayfayı kırar. Bu yüzden varsayılan CSP
 * script'e DOKUNMAZ, yalnız temanın hiç kullanmadığı iki kapıyı kapatır:
 *
 *   object-src 'none'  <object>/<embed> ile eklenti içeriği. Temada, view/
 *                      altında ve uzantılarda tek kullanım yok; enjekte
 *                      edilmiş bir <embed> script-src'yi dolanabiliyordu.
 *   base-uri 'self'    Enjekte edilmiş <base href="//saldirgan"> bütün göreli
 *                      <script src>'leri karşı siteye çevirir. Hiçbir sayfa
 *                      <base> yazmıyor; jQuery'nin parseHTML'de kurduğu
 *                      <base> ayrı bir belgede ve zaten kendi adresimiz.
 *
 * frame-ancestors bilerek YOK: aynı işi X-Frame-Options: SAMEORIGIN yapıyor
 * ve tarayıcıda CSP ondan önce gelir. İkisini birden göndermek, çerçeveye
 * izin vermek için VNL_HEADER_FRAME_OPTIONS=false yazan projeyi sessizce
 * kilitli bırakırdı — tek iş için tek anahtar.
 *
 * Report-Only varsayılanı BOŞ: VNL'de rapor toplayan bir uç yok, raporlar
 * yalnız tarayıcı konsoluna düşer ve canlıda kimse okumaz. script-src içeren
 * anlamlı bir politika ise Google Sign-In gibi meşru dış script'ler için
 * her sayfada uyarı basar — sinyali gürültüye gömer. Sıkı politikaya geçmek
 * isteyen proje önce VNL_HEADER_CSP_REPORT_ONLY ile dener (.env.example).
 */
function vnl_security_headers(bool $secure): array
{
    if (VnlEnv::get('VNL_SECURITY_HEADERS', true) === false) {
        return [];
    }

    $spec = [
        'X-Frame-Options'    => ['VNL_HEADER_FRAME_OPTIONS', 'SAMEORIGIN'],
        'Referrer-Policy'    => ['VNL_HEADER_REFERRER_POLICY', 'strict-origin-when-cross-origin'],
        'Permissions-Policy' => ['VNL_HEADER_PERMISSIONS_POLICY', 'camera=(), microphone=(), geolocation=()'],
        'Content-Security-Policy'             => ['VNL_HEADER_CSP', "object-src 'none'; base-uri 'self'"],
        'Content-Security-Policy-Report-Only' => ['VNL_HEADER_CSP_REPORT_ONLY', ''],
    ];
    if ($secure) {
        $spec['Strict-Transport-Security'] = ['VNL_HEADER_HSTS', 'max-age=31536000'];
    }

    $headers = ['X-Content-Type-Options' => 'nosniff'];

    foreach ($spec as $name => [$key, $default]) {
        $value = VnlEnv::get($key, $default);
        $value = $value === false || $value === null ? '' : trim(str_replace(["\r", "\n"], '', (string)$value));

        if ($value !== '') {
            $headers[$name] = $value;
        }
    }

    return $headers;
}

if (PHP_SAPI !== 'cli' && !headers_sent()) {
    foreach (vnl_security_headers($isSecure) as $vnl_name => $vnl_value) {
        header($vnl_name . ': ' . $vnl_value);
    }
    unset($vnl_name, $vnl_value);
}

/* İmza (4.0.0): PHP'nin "X-Powered-By: PHP/8.x.y" başlığı yerine
   "VNL (Voxnup Not Laravel)". Sürüm bilerek yazılmaz — numara, saldırgana
   hangi açığı deneyeceğini söyler. VNL_POWERED_BY= (boş) başlığı kaldırır. */
if (PHP_SAPI !== 'cli' && !headers_sent()) {
    header_remove('X-Powered-By');
    $vnl_powered_by = VnlEnv::get('VNL_POWERED_BY', true);
    $vnl_powered_by = $vnl_powered_by === true ? 'VNL (Voxnup Not Laravel)' : trim((string)$vnl_powered_by);
    if ($vnl_powered_by !== '') {
        header('X-Powered-By: ' . str_replace(["\r", "\n"], '', $vnl_powered_by));
    }
    unset($vnl_powered_by);
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

/* Projenin enum'ları (4.0.0). Çekirdek enums/ taşımaz; proje koyarsa
   yardımcılardan hemen sonra, modüllerden önce yüklenir. */
vnl_load_dir('enums');

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

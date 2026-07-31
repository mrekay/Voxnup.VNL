<?php
/*!
 * ============================================================
 *  Voxnup Not Laravel (VNL) — Çekirdek Önyüklemesi
 *  Çekirdeği ayağa kaldırır. Proje kodu buraya girmez.
 *  Authors: Voxnup Authors
 * ============================================================
 */

/**
 * İki kök.
 *
 *   VNL_PATH — çekirdek (bu dizin). Güncellemede komple değişir; proje
 *              buraya hiçbir şey yazmaz.
 *   VNL_ROOT — proje kökü. Güncelleme buraya dokunmaz.
 *
 * Sınırın tamamı bu ikisinin arasından geçer. Yol çözümlemesi artık
 * DOCUMENT_ROOT'a ya da çalışma dizinine bakmıyor: ikisi de sürecin
 * nasıl başlatıldığına göre değişiyordu, bu ikisi değişmiyor.
 */
define('VNL_PATH', __DIR__);

if (!defined('VNL_ROOT')) {
    define('VNL_ROOT', dirname(__DIR__));
}

/* Doğrudan çağrılırsa hiçbir şey yapmadan çıkar: burası bir giriş noktası
   değil, ön denetleyicinin yüklediği dosyadır. */
if (PHP_SAPI !== 'cli' && realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
    header('location: /');
    die;
}

/* Ölümcül hatada PHP kendi "Fatal error" metnini bizden önce basar; buffer'a alıp
   vnl_render_error içinde temizliyoruz ki JSON/HTML yanıtı bozulmasın. */
ob_start();

/* VNL Hata Sayfası — bootstrap hatalarının da yakalanabilmesi için her şeyden önce yüklenir. */
require_once VNL_PATH . '/error.php';

/* VNL Şablon Derleyicisi — view() ve modal() bunun üzerinden çalışır. */
require_once VNL_PATH . '/template.php';

/* Ortam değişkenleri — config/definitions.php env() çağırdığı için ondan önce yüklenir. */
require_once VNL_PATH . '/env.php';

/* Global exception handler — yakalanmamış tüm istisnalar VNL hata sayfasına düşer */
set_exception_handler(function (Throwable $e) {
    vnl_render_error($e);
});

/* Ölümcül hatalar exception handler'a düşmez; shutdown üzerinden yakalanır. */
register_shutdown_function(function () {
    $fatal = error_get_last();
    if ($fatal && in_array($fatal['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR], true)) {
        vnl_render_error(new ErrorException($fatal['message'], 0, $fatal['type'], $fatal['file'], $fatal['line']));
    }
});

/* Projenin yapılandırması. Çekirdek onu okur ama sahibi değildir. */
require_once VNL_ROOT . '/config/definitions.php';

require_once VNL_PATH . '/bootstrap.php';
require_once VNL_PATH . '/vendor/autoload.php';

require_once VNL_ROOT . '/config/database.php';

/* Extension loader */
$_extManifest     = VNL_ROOT . '/extensions/extensions.php';
$_enabledExtensions = file_exists($_extManifest) ? (require $_extManifest) : [];

foreach (glob(VNL_ROOT . '/extensions/*/boot.php') as $_extBoot) {
    $_extName = basename(dirname($_extBoot));
    if (isset($_enabledExtensions[$_extName]) && $_enabledExtensions[$_extName] === true) {
        try {
            require_once $_extBoot;
        } catch (Throwable $_extErr) {
            vnl_render_error($_extErr);
        }
    }
}
unset($_extManifest, $_enabledExtensions, $_extBoot, $_extName);

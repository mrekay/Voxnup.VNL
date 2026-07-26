<?php

/* Ölümcül hatada PHP kendi "Fatal error" metnini bizden önce basar; buffer'a alıp
   vnl_render_error içinde temizliyoruz ki JSON/HTML yanıtı bozulmasın. */
ob_start();

/* VNL Hata Sayfası — bootstrap hatalarının da yakalanabilmesi için her şeyden önce yüklenir. */
require_once($_SERVER["DOCUMENT_ROOT"].'/libs/vnl_error.php');

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

require_once($_SERVER["DOCUMENT_ROOT"].'/libs/config/config_definitions.php');
require_once($_SERVER["DOCUMENT_ROOT"].'/libs/config/config_soft.php');
require_once($_SERVER["DOCUMENT_ROOT"].'/libs/vendor/autoload.php');
require_once($_SERVER["DOCUMENT_ROOT"].'/libs/config/config_db.php');

/* Extension loader */
$_extManifest     = getDocumentRoot() . 'extensions/extensions.php';
$_enabledExtensions = file_exists($_extManifest) ? (require $_extManifest) : [];

foreach (glob(getDocumentRoot() . 'extensions/*/boot.php') as $_extBoot) {
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

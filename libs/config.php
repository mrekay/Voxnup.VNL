<?php
require_once($_SERVER["DOCUMENT_ROOT"].'/libs/config/config_definitions.php');
require_once($_SERVER["DOCUMENT_ROOT"].'/libs/config/config_soft.php');
require_once($_SERVER["DOCUMENT_ROOT"].'/libs/vendor/autoload.php');
require_once($_SERVER["DOCUMENT_ROOT"].'/libs/config/config_db.php');

/* VNL Hata Sayfası */
require_once($_SERVER["DOCUMENT_ROOT"].'/libs/vnl_error.php');

/* Global exception handler — yakalanmamış tüm istisnalar VNL hata sayfasına düşer */
set_exception_handler(function (Throwable $e) {
    vnl_render_error($e);
});

/* Extension loader — extensions/*/boot.php otomatik yüklenir */
foreach (glob(getDocumentRoot() . 'extensions/*/boot.php') as $_extBoot) {
    try {
        require_once $_extBoot;
    } catch (Throwable $_extErr) {
        vnl_render_error($_extErr);
    }
}
unset($_extBoot);

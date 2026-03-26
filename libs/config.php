<?php
require_once($_SERVER["DOCUMENT_ROOT"].'/libs/config/config_definitions.php');
require_once($_SERVER["DOCUMENT_ROOT"].'/libs/config/config_soft.php');
require_once($_SERVER["DOCUMENT_ROOT"].'/libs/vendor/autoload.php');
require_once($_SERVER["DOCUMENT_ROOT"].'/libs/config/config_db.php');

/* Extension loader — extensions/*/boot.php otomatik yüklenir */
foreach (glob(getDocumentRoot() . 'extensions/*/boot.php') as $_extBoot) {
    require_once $_extBoot;
}
unset($_extBoot);

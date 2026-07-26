<?php
ini_set('display_errors', 1);
error_reporting(E_ALL ^ E_WARNING);
ini_set('session.gc_maxlifetime', 3600);
set_time_limit(20);

session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '', // Gerekirse belirtebilirsiniz
    'secure' => true,
    'httponly' => true,
    'samesite' => 'None' // 'None', 'Lax' veya 'Strict'
]);

$cDefinitions = new ConfigDefinitions();

/* helper'ları yükle (app,base_functions vs.)*/
foreach (glob(__DIR__ . '/../helper/*.php') as $helperFile) {
    require_once $helperFile;
}

$filename = basename($_SERVER['PHP_SELF']);

if ('config.php' == $filename) {
    header('location:../');
    die();
}

ob_start();
date_default_timezone_set('Europe/Istanbul');//'America/Los_Angeles'

$assets_address = $cDefinitions->getAssetsUrl();
define('SITE_HTTP', (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off' ? 'https://' : 'http://'));
define('SITE_URL_NON_HTTP', $_SERVER["HTTP_HOST"]);
define('MAIN_SITE_URL', $cDefinitions->getMasterSiteUrl());
define('SITE_URL', $cDefinitions->getSiteUrl());

function loadClass($class)
{
    $loaded = false;
    global $cDefinitions;
    //$class
    $class_folder = explode("_", $class)[0];
    $class_folder = $class_folder == $class ? '' : $class_folder . '/';
    $path =  getDocumentRoot() . 'classes/' . $class_folder . $class . '.php';


    if (getDocumentRoot() != '' || (getDocumentRoot() == '' && $class != 'Session')) {
        if (file_exists($path)) {

            $loaded = true;
            require_once $path;

        }
    }
    if (!$loaded) {
        global $maindocumentRoot;
        $path = $maindocumentRoot . 'classes/' . $class_folder . $class . '.php';
        if ($maindocumentRoot != '' || ($maindocumentRoot == '' && $class != 'Session'))
            if (file_exists($path)) {
                require_once $path;
            }
    }
}

spl_autoload_register('loadClass');

if (getDocumentRoot() != '')
    Session::init();



$req = new Request();
Language::set(Language::GetLanguage());

$PdfPrintEnabled = true;

foreach (glob(getDocumentRoot() .'/libs/modules/*.php') as $moduleFile) {
    require_once $moduleFile;
}

foreach (glob(getDocumentRoot() . '/exceptions/*.php') as $exceptionFile) {
    require_once $exceptionFile;
}

foreach (glob(getDocumentRoot() . '/validators/*.php') as $validateFile) {
    require_once $validateFile;
}

foreach (glob(getDocumentRoot() . '/libs/helper/special/*.php') as $helperFile) {
    require_once $helperFile;
}


?>

<?php
require_once('libs/config.php');
$assets_address = SITE_URL.'/assets/';
UserModule::LogOut();
Session::destroy();
header("Location:/login");
exit;
?>

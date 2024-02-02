<?php
require_once('libs/config.php');
$assets_address = SITE_URL.'/assets/';
LogOut();
Session::destroy();
header("Location:/login");
exit;
?>

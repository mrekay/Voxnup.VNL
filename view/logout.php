<?php
/* Buraya kadar geldiyse çekirdek zaten yüklüdür: bir görünüm önyüklemeyi
   yeniden çağırmaz. VNL 3.0'a kadar burada require_once('libs/config.php')
   duruyordu; require_once yinelenen yüklemeyi yuttuğu için etkisizdi. */
$assets_address = SITE_URL.'/assets/';
UserModule::LogOut();
Session::destroy();
header("Location:/login");
exit;
?>

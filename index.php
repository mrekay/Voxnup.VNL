<?php
/*!
 * ============================================================
 *  Voxnup Not Laravel (VNL) — Ön Denetleyici
 *  Authors: Voxnup Authors
 * ============================================================
 *
 * Bu dosya PROJENİNDİR ama içinde proje mantığı yoktur: çekirdeği yükler
 * ve isteği ona devreder. Sayfa eklemek için buraya değil controller/
 * altına dosya açılır.
 *
 * Yönlendirme vnl/kernel.php'dedir. Orayı ezmek isterseniz bu dosyada
 * kernel.php yerine kendi dosyanızı çağırın; çekirdeğe dokunmanız gerekmez.
 */

define('VNL_ROOT', __DIR__);

require VNL_ROOT . '/vnl/boot.php';
require VNL_PATH . '/kernel.php';

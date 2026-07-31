#!/usr/bin/env php
<?php
/*!
 * ============================================================
 *  Voxnup Not Laravel (VNL) — Konsol
 *  Authors: Voxnup Authors
 * ============================================================
 *
 *   php vnl.php migrate --run     her yerde
 *   vnl migrate --run             Windows (vnl.cmd üzerinden)
 *   ./vnl.php migrate --run       Unix (chmod +x vnl.php)
 *
 * index.php'nin komut satırı karşılığı: PROJENİNDİR ama içinde proje
 * mantığı yoktur, çekirdeği yükleyip isteği ona devreder.
 *
 * Adı neden düz "vnl" değil: bir dizinde aynı adı taşıyan bir dosya ve
 * bir dizin bir arada bulunamaz; kökte zaten vnl/ var.
 */

define('VNL_ROOT', __DIR__);

require VNL_ROOT . '/vnl/console.php';

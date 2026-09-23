<?php
/*!
 * ============================================================
 *  Voxnup Not Laravel (VNL) — Action Uç Noktası
 *  POST /app/<modül>/<metot>
 *  Authors: Voxnup Authors
 * ============================================================
 *
 * 3.4.0: dağıtıcı mantığı çekirdeğe taşındı (vnl/classes/VnlDispatcher.php):
 * yalnız POST, CSRF, varsayılan olarak giriş zorunlu, güvenli metot
 * çözümlemesi, durum kodu korunur, istisna ayrıntısı sızmaz. Bu dosya
 * PROJENİNDİR ama içinde mantık tutmaz; çekirdekteki düzeltmeler böylece
 * her projeye güncellemeyle ulaşır.
 *
 * Action sınıfı sözleşmesi (isteğe bağlı sabitler):
 *   public const PUBLIC_METHODS = ['login'];   // girişsiz çağrılabilir
 *   public const CSRF_EXEMPT    = ['webhook']; // imzasını kendisi doğrular
 */

global $req;

VnlDispatcher::handle($req);

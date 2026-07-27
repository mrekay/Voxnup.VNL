<?php
/*
 * ============================================================
 *  Voxnup Not Laravel (VNL) — VNL Theme 1.0
 *  Standart script seti: vendor/tema/cekirdek JS
 *  Authors: Voxnup Authors
 * ============================================================
 */
?>
<script>
    $SITE_URL = '<?php echo SITE_URL; ?>';
    // vnl.js 401 yanıtında yalnızca oturumu düşmüş kullanıcıyı yeniden yükler; girişsizde 0 kalır.
    var VNL_USER_ID = <?= (int)$CurrentUser->id ?>;
</script>

<!-- VNL Theme: vendor paketi (jquery + bootstrap + sweetalert2 + slim-select),
     tema davranışları ve VNL çekirdek script'i. Script'ler head'de kalır:
     view/modal dosyaları body içinde satır arası $(...) çalıştırıyor. -->
<script src="<?= $assets_address ?>/vnl/js/vendors.min.js"></script>
<script src="<?= $assets_address ?>/vnl/js/vnl.min.js"></script>
<script src="<?= $assets_address ?>/scripts/vnl.js"></script>
<script src="<?= $assets_address ?>/scripts/vnltable.js"></script>



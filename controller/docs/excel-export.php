<?php
/**
 * Excel Export — kaldırıldı, VNL Table'a yönlendirir.
 *
 * Sayfa SDTable'ın export mekanizmasını ve libs/excel içindeki PHPExcel'i
 * anlatıyordu; VNL 3.0'da ikisi de kaldırıldı. Excel çıktısı artık VNL
 * Table'ın parçası (VnlTableExport), konu orada belgeleniyor.
 *
 * Rota kalıcı yönlendirme olarak korunuyor: eski bağlantılar ve yer
 * imleri 404 yerine doğru sayfaya düşsün.
 */

header('Location: /docs/vnltable', true, 301);
exit;

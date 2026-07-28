<?php
/**
 * SDTable — kaldırıldı, VNL Table'a yönlendirir.
 *
 * SDTable, DataTables üzerine kurulu tablo yardımcısıydı. DataTables
 * VNL Theme 1.0 ile söküldü, yerini VNL Table aldı; sınıf da bu sürümde
 * kaldırıldı.
 *
 * Rota kalıcı yönlendirme olarak korunuyor: eski bağlantılar ve yer
 * imleri 404 yerine doğru sayfaya düşsün.
 */

header('Location: /docs/vnltable', true, 301);
exit;

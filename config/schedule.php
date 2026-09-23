<?php
/*!
 * ============================================================
 *  Voxnup Not Laravel (VNL) — Zamanlanmış Görevler
 *  php vnl.php schedule:run her dakika bu listeye bakar.
 *  Authors: Voxnup Authors
 * ============================================================
 *
 * PROJENİNDİR. Sistem cron'una tek satır yazılır:
 *
 *   * * * * * <kullanıcı> php /yol/proje/vnl.php schedule:run >> /dev/null 2>&1
 *
 * Girdi biçimleri:
 *
 *   ['cron' => '*\/5 * * * *', 'command' => 'backup:prune', 'args' => ['--days=7']]
 *       commands/backup-prune.php ayrı süreçte çalışır; önceki çalıştırma
 *       sürüyorsa bu dakika atlanır.
 *
 *   ['cron' => '0 3 * * *', 'job' => 'backup.nightly', 'payload' => [], 'queue' => 'system']
 *       Queue::push — işi kuyruk işçisi (queue:work) yapar.
 *
 * Cron: dakika saat gün ay haftanın-günü; '*', 'n', 'a-b', '*\/n', 'a,b'
 * ve @hourly/@daily/@weekly/@monthly/@yearly. `php vnl.php schedule:run --list`
 * listeyi ve şu an çalışacakları gösterir.
 */

return [
    // ['cron' => '0 4 * * *', 'command' => 'backup:prune', 'args' => ['--days=14']],
];

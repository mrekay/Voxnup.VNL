<?php
/*!
 * ============================================================
 *  Voxnup Not Laravel (VNL) — Kuyruk: Ayırma Kaybı
 *  İş bu işçinin elinden alındığında fırlatılır.
 *  Authors: Voxnup Authors
 * ============================================================
 */

/**
 * İşin ayırması (reserved_by jetonu) artık bu işçide değil.
 *
 * VnlJob::progress(), log() ve heartbeat() bu istisnayı fırlatır: iş
 * --stale ile başka bir işçiye verilmiş, yeniden kuyruğa konmuş ya da bir
 * yönetici tarafından yeniden denenmiştir. İşleyicinin YAN ETKİLERİNİ
 * sürdürmemesi için (iki işçi aynı useradd'i çalıştırmasın) akış burada
 * kesilir. İşleyici bu istisnayı yakalayıp yutmamalıdır; queue:work onu
 * tanır ve satıra hiçbir şey yazmadan bir sonraki işe geçer.
 */
class VnlJobLostException extends RuntimeException
{
}

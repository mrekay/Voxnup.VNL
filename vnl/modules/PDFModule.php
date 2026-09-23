<?php
/*!
 * ============================================================
 *  Voxnup Not Laravel (VNL) — PDF Modülü (kullanımdan kalktı)
 *  Authors: Voxnup Authors
 * ============================================================
 */

/**
 * @deprecated 3.4.0 — hiçbir zaman çalışmadı.
 *
 * CreatePDF() kendi sunucusundaki /pdf/printurl.php adresine POST ediyordu;
 * VNL o dosyayı hiçbir sürümde taşımadı. Çağrı 800 sn'lik bir zaman aşımına
 * kadar bekleyip boş dönüyordu. Sessiz boşluk yerine açık bir hata verilir.
 * PDF üretimi gereken proje kendi modülünü (modules/PDFModule.php) yazar;
 * uzun sürecek üretim kuyruğa (Queue::push) verilir.
 */
class PDFModule
{
    /** @deprecated 3.4.0 */
    static function CreatePDF($orientation, $printurl, $title, $prefix, $printfilename, $queue_id = 0)
    {
        throw new RuntimeException(
            'PDFModule::CreatePDF() kaldırıldı (VNL 3.4.0): hedef /pdf/printurl.php hiçbir zaman var olmadı. '
            . 'Proje tarafında modules/PDFModule.php ile kendi uygulamanızı yazın.'
        );
    }
}

<?php
/*!
 * ============================================================
 *  Voxnup Not Laravel (VNL) — Mail Nesnesi
 *  MailModule::SendMail() bu nesneyi alır.
 *  Authors: Voxnup Authors
 * ============================================================
 */

/**
 * Gönderilecek maili tarif eden basit veri nesnesi.
 *
 * VNL 3.0'a kadar MailModule::SendMail() bu sınıfı tip olarak istiyordu
 * ama sınıf hiçbir yerde tanımlı değildi; modül çağrıldığı anda ölüyordu.
 *
 * $media alanı gövdeye gömülecek görselleri tarif eder:
 *   ['logo' => ['path' => '/abs/yol/logo.png', 'content_type' => 'image/png']]
 * İçerikte <img src="cid:logo"> ile kullanılır.
 */
class MailObject
{
    public function __construct(
        public string $to,
        public string $title,
        public string $content,
        public array  $media = [],
        public string $toName = ''
    ) {
    }
}

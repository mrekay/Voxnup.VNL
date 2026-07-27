<?php
/*!
 * ============================================================
 *  Voxnup Not Laravel (VNL) 3.0.0 — Mail Modülü
 *  SMTP üzerinden mail gönderimi; ayarlar .env'den okunur.
 *  Authors: Voxnup Authors
 * ============================================================
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class MailModule
{
    /**
     * Maili gönderir. Başarılıysa true, aksi halde false döner ve hata
     * error_log'a yazılır.
     *
     * VNL 2.x burada SMTP bilgilerini boş string olarak sabit tutuyordu
     * (Host, Username, Password hepsi ""), yani hiçbir mail gidemezdi.
     * Ayrıca hata durumunda mesajı doğrudan ekrana echo ediyordu — AJAX
     * yanıtının ortasına düşüp JSON'u bozuyordu.
     */
    static function SendMail(MailObject $mailObject): bool
    {
        if (empty($mailObject->to)) {
            return false;
        }

        $host = (string)VnlEnv::get('MAIL_HOST', '');
        if ($host === '') {
            error_log('[VNL][Mail] MAIL_HOST tanımlı değil; mail gönderilmedi.');

            return false;
        }

        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->SMTPDebug  = SMTP::DEBUG_OFF;
            $mail->Host       = $host;
            $mail->Port       = (int)VnlEnv::get('MAIL_PORT', 465);
            $mail->CharSet    = 'UTF-8';
            $mail->SMTPAuth   = true;
            $mail->Username   = (string)VnlEnv::get('MAIL_USERNAME', '');
            $mail->Password   = (string)VnlEnv::get('MAIL_PASSWORD', '');

            $encryption = strtolower((string)VnlEnv::get('MAIL_ENCRYPTION', 'ssl'));
            if ($encryption === 'tls') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            } elseif ($encryption === 'ssl') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            } else {
                $mail->SMTPSecure = false;
                $mail->SMTPAutoTLS = false;
            }

            /* Sertifika doğrulaması varsayılan olarak AÇIK. 2.x bunu koşulsuz
               kapatıyordu, bu da SMTP bağlantısını ortadaki adama açıyordu.
               Kendi imzalı sertifikalı sunucular için .env'den gevşetilebilir. */
            if (VnlEnv::get('MAIL_VERIFY_PEER', true) === false) {
                $mail->SMTPOptions = ['ssl' => [
                    'verify_peer'       => false,
                    'verify_peer_name'  => false,
                    'allow_self_signed' => true,
                ]];
            }

            $mail->setFrom(
                (string)VnlEnv::get('MAIL_FROM_ADDRESS', $mail->Username),
                (string)VnlEnv::get('MAIL_FROM_NAME', '')
            );
            $mail->addAddress($mailObject->to, $mailObject->toName ?: $mailObject->to);

            $mail->isHTML(true);
            $mail->Subject = $mailObject->title;
            $mail->Body    = $mailObject->content;
            $mail->AltBody = trim(strip_tags(str_replace(['<br>', '<br />', '</p>'], "\n", $mailObject->content)));

            foreach ($mailObject->media as $cid => $item) {
                if (empty($item['path']) || !is_file($item['path'])) {
                    continue;
                }

                /* Ad boş bırakılır; doldurulursa PHPMailer görseli ek olarak da listeler. */
                $mail->addEmbeddedImage($item['path'], (string)$cid, '', 'base64', $item['content_type'] ?? '', 'inline');
            }

            return $mail->send();
        } catch (Throwable $e) {
            error_log('[VNL][Mail] Gönderilemedi: ' . ($mail->ErrorInfo ?: $e->getMessage()));

            return false;
        }
    }

    static function isValidEmail($email): bool
    {
        return filter_var((string)$email, FILTER_VALIDATE_EMAIL) !== false;
    }
}

<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class MailModule
{
    static function SendMail(MailObject $mailObject, $isCron = false)
    {
//        ini_set('display_errors',1);
        $smtpssl = 1;
        $smtpNeeded = $smtpssl == 1 ? true : false;

        $mail = new PHPMailer(true);

//        $sender_address = $BrandParameters->Get('smtp_address');
//        $sender_password = $BrandParameters->Get('smtp_password');
//        $sender_name = $BrandParameters->Get('smtp_name');
//        $sender_host = $BrandParameters->Get('smtp_host');
//        $sender_smtp_port = $BrandParameters->Get('smtp_port');
        $mail_title = $mailObject->title;

//        echo $sender_address.'<br />';
//        echo $sender_password.'<br />';
//        echo $sender_name.'<br />';
//        echo $sender_host.'<br />';
//        echo $sender_smtp_port.'<br />';
//        echo $mail_title.'<br />';
        //Gönderilecek Kisi
        $sending_address = $mailObject->to;//$BrandParameters->Get('smtp_default_address');
        if (empty($sending_address)) return;

        //Mailimizin gövdesi: (HTML ile)
        $body = $mailObject->content;

        // HTML okuyamayan mail okuyucularda görünecek düz metin:
        $textBody = str_replace('<br />', ' | ', $mailObject->content);

        if (true || (isLocalHost() && !$isCron)) {
            $mail->IsSMTP();
        }
        $mail->SMTPDebug = 0;
        $mail->SMTPAuth = true;
        if ($smtpNeeded) {
            $mail->SMTPSecure = "ssl";
        } else {
//		$mail->SMTPSecure = "tls";
        }
        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );
        $mail->Host = "";
        $mail->Port = "465";
        $mail->IsHTML(true);
        $mail->SetLanguage("tr", "phpmailer/language");
        $mail->CharSet = 'utf-8';
        $mail->Username = "";
        $mail->Password = '';
        $mail->SetFrom("", "");
        $mail->AddAddress($sending_address, $sending_address);
        $mail->Subject = $mail_title;
        $mail->Body = $body;
        $mail->AltBody = $textBody;

        if (!empty($mailObject->media_data)){
            foreach ($mailObject->media_data as $key=>$media_datum) {

                /* name kısmı boş çünkü eklere ekliyor onu yazınca*/
                $mail->addEmbeddedImage($media_datum["path"],$key,"","base64",$media_datum["content_type"],"inline");

            }
        }

        if (!$mail->Send()) {
            //$mail->ClearAddresses();
            //$mail->ClearAttachments();
            echo 'Mailer Error: ' . $mail->ErrorInfo;

            return false;
        } else {
            //$mail->ClearAddresses();
            //$mail->ClearAttachments();

            return true;
        }
    }

    static function PrepareMail($mail_id, $email): MailObject
    {
        global $cDefinitions;
        $mail = Mail::Select(["id" => $mail_id]);
        $mail_template = MailTemplate::Select(["id" => $mail->template_id]);

        $medias_raw = !empty($mail_template->static_media) ? json_decode($mail_template->static_media, true) : [];
        $medias = []; // json
        if (!empty($medias_raw)) {

            foreach ($medias_raw as $key => $raw_media) {
                $media_data = StaticMedia::Select(["unique_code"=>$raw_media]);

                $media_name = $media_data["original_name"].".".$media_data["type"];
                $medias[$key] = ["path" => $cDefinitions->getCdnPath()."/assets/".$media_data["assets_path"].'/'.$media_name,"name"=>$media_name,"content_type"=>$media_data["content_type"]];
            }

        }

        $datas = json_decode($mail->data, true);
        $datas["key"] = $mail->unique_code;

        /* başlık replace'i*/
        $title = $mail_template->title;
        preg_match_all('/\{(.*?)\}/', $title, $eslesmeler);

        /* sadece başlıklar */
        foreach ($eslesmeler[1] as $eslesme) {
            if (isset($datas[$eslesme])) {
                $title = str_replace("{" . $eslesme . "}", $datas[$eslesme], $title);
            }
        }

        /* aşağıda tekrar kullanılacak sıfırla*/
        $eslesmeler = [];

        /* içerik replace'i */
        $content = $mail_template->content;
        preg_match_all('/\{(.*?)\}/', $content, $eslesmeler);

        /* sadece başlıklar */
        foreach ($eslesmeler[1] as $eslesme) {
            if (isset($datas[$eslesme])) {
                $content = str_replace("{" . $eslesme . "}", $datas[$eslesme], $content);
            }
        }

        $mail_object = new MailObject($mail->id, $mail->unique_code, $title, $content, $email,$medias);

        return $mail_object;

    }

    static function isValidEmail($email, $region = ''): bool
    {

        // E-posta adresinin geçerli olup olmadığını kontrol et
        return filter_var($email, FILTER_VALIDATE_EMAIL);

    }
}
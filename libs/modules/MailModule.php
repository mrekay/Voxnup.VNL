<?php

require_once(__DIR__ . "/../phpmailer/PHPMailerAutoload.php");

class MailModule
{
    static function SendMail($title, $content, $address = '', $isCron = false)
    {
        global $BrandParameters;
        $smtpssl = 0;
        $smtpNeeded = $smtpssl == 1 ? true : false;
        $mail = new PHPMailer();

        $sender_address = $BrandParameters->Get('smtp_address');
        $sender_password = $BrandParameters->Get('smtp_password');
        $sender_name = $BrandParameters->Get('smtp_name');
        $sender_host = $BrandParameters->Get('smtp_host');
        $sender_smtp_port = $BrandParameters->Get('smtp_port');
        $mail_title = $title;

        //echo $sender_address.'<br />';
        //echo $sender_password.'<br />';
        //echo $sender_name.'<br />';
        //echo $sender_host.'<br />';
        //echo $sender_smtp_port.'<br />';
        //echo $mail_title.'<br />';

        //Gönderilecek Kisi
        $sending_address = !empty($address) ? $address : $address;//$BrandParameters->Get('smtp_default_address');
        if (empty($sending_address)) return;

        //Mailimizin gövdesi: (HTML ile)
        $body = $content;

        // HTML okuyamayan mail okuyucularda görünecek düz metin:
        $textBody = str_replace('<br />', ' | ', $content);

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
        $mail->Host = $sender_host;
        $mail->Port = $sender_smtp_port;
        $mail->IsHTML(true);
        $mail->SetLanguage("tr", "phpmailer/language");
        $mail->CharSet = 'utf-8';
        $mail->Username = $sender_address;
        $mail->Password = $sender_password;
        $mail->SetFrom($sender_address, $sender_name);
        $mail->AddAddress($sending_address, $sending_address);
        $mail->Subject = $mail_title;
        $mail->Body = $body;
        $mail->AltBody = $textBody;
        //	$mail->AddAddress('info@erkayteknoloji.com');

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
}
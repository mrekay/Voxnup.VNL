<?php
/*!
 * ============================================================
 *  Voxnup Not Laravel (VNL) — Mail Modülü
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
            $mail->Body    = self::embedRemoteImages($mail, $mailObject->content);
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

    /* ---------------------------------------------------------------- */
    /* Uzak görselleri gömme (4.0.0)                                     */
    /* ---------------------------------------------------------------- */

    /** Gömülen tek görselin üst sınırı (bayt). */
    public const EMBED_MAX_BYTES = 5242880;

    /**
     * HTML'deki <img src="https://izinli-alan/..."> görsellerini indirip
     * maile gömer (cid:). Birçok posta istemcisi uzak görseli varsayılan
     * olarak engellediği için gömülü görsel her yerde görünür.
     *
     *   VNL_MAIL_EMBED_HOSTS=cdn.example.com,static.example.com
     *
     * Boşsa (varsayılan) hiçbir şey yapılmaz. Yalnız listedeki alan adları
     * indirilir: içerik kullanıcıdan gelebileceği için serbest bir adres
     * sunucuyu iç ağa istek atan bir vekile çevirirdi (SSRF). Yönlendirme
     * izlenmez, görsel 5 MB'ı aşamaz, görsel olmayan yanıt gömülmez.
     * İndirilemeyen görsel uzak adresiyle kalır; gönderim düşmez.
     */
    public static function embedRemoteImages(PHPMailer $mail, string $body): string
    {
        $hosts = self::embedHosts();
        if ($hosts === []) {
            return $body;
        }

        foreach (self::embeddableUrls($body, $hosts) as $url) {
            $data = self::fetchImage($url);
            if ($data === null) {
                continue;
            }

            $info = @getimagesizefromstring($data);
            if (empty($info['mime']) || !str_starts_with($info['mime'], 'image/')) {
                continue;
            }

            $cid  = 'vnlimg' . substr(hash('sha256', $url), 0, 16);
            $name = basename((string)parse_url($url, PHP_URL_PATH)) ?: 'image';

            if ($mail->addStringEmbeddedImage($data, $cid, $name, PHPMailer::ENCODING_BASE64, $info['mime'], 'inline')) {
                $body = str_replace($url, 'cid:' . $cid, $body);
            }
        }

        return $body;
    }

    /** @return string[] VNL_MAIL_EMBED_HOSTS, küçük harfle */
    public static function embedHosts(): array
    {
        $raw = VnlEnv::get('VNL_MAIL_EMBED_HOSTS', '');
        $raw = is_string($raw) ? $raw : '';

        return array_values(array_filter(array_map(
            static fn($h) => strtolower(trim($h)),
            explode(',', $raw)
        )));
    }

    /**
     * src özniteliğindeki, alan adı listede olan http(s) adresleri (tekil).
     * Alan adı TAM eşleşir: "cdn.example.com" listedeyse "cdn.example.com.evil.io"
     * ya da "evil.io/?cdn.example.com" eşleşmez. Kullanıcı adı ya da port
     * taşıyan adres hiç gömülmez.
     *
     * @return string[]
     */
    public static function embeddableUrls(string $body, array $hosts): array
    {
        if (!preg_match_all('/\bsrc\s*=\s*(["\'])(https?:\/\/[^"\'\s<>]+)\1/i', $body, $m)) {
            return [];
        }

        $urls = [];
        foreach ($m[2] as $url) {
            $parts = parse_url(html_entity_decode($url, ENT_QUOTES | ENT_HTML5, 'UTF-8'));

            if (!is_array($parts) || isset($parts['user']) || isset($parts['pass']) || isset($parts['port'])) {
                continue;
            }
            if (in_array(strtolower((string)($parts['host'] ?? '')), $hosts, true)) {
                $urls[$url] = true;
            }
        }

        return array_keys($urls);
    }

    private static function fetchImage(string $url): ?string
    {
        $context = stream_context_create(['http' => [
            'method'          => 'GET',
            'timeout'         => 10,
            'follow_location' => 0,
            'user_agent'      => 'VNL-Mail',
        ]]);

        $data = @file_get_contents(
            html_entity_decode($url, ENT_QUOTES | ENT_HTML5, 'UTF-8'),
            false,
            $context,
            0,
            self::EMBED_MAX_BYTES + 1
        );

        if (!is_string($data) || $data === '' || strlen($data) > self::EMBED_MAX_BYTES) {
            return null;
        }

        return $data;
    }

    static function isValidEmail($email): bool
    {
        return filter_var((string)$email, FILTER_VALIDATE_EMAIL) !== false;
    }
}

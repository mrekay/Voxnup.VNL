<?php

/**
 * Dış servislere HTTP isteği.
 *
 * 4.0.0 güvenlik sertleştirmesi (imzalar değişmedi, davranış değişti):
 *
 * - Yalnız http:// ve https:// adreslerine istek gider. file://, php://,
 *   phar://, ftp://, gopher:// vb. adreste istek atılmaz, false döner.
 *   Adres çoğu zaman bir ayardan ya da kullanıcıdan gelir; sarmalayıcıların
 *   (file_get_contents ve curl) yerel dosya okuyabilmesi, adres denetlenmeden
 *   bırakıldığında SSRF'i yerel dosya sızdırmaya çevirir.
 * - Authorization başlığı yalnız kullanıcı adı GERÇEKTEN verildiyse gider.
 *   Curl()'ün eski hâli varsayılan boş diziyle bile "Basic Og==" yolluyordu.
 * - Kimlik bilgisi verilen istekte yönlendirme İZLENMEZ; 3xx yanıtın kendisi
 *   döner. PHP'nin http akış sarmalayıcısı başlıkları Location'daki hedefe
 *   olduğu gibi tekrar gönderir ve "yalnız aynı sunucuya" diye bir seçeneği
 *   yoktur (curl'ün CURLOPT_UNRESTRICTED_AUTH'unun karşılığı yok). Elle,
 *   sunucu karşılaştırarak izlemek mümkündü ama her atlamada şema/port/alt
 *   alan kuralı yazmak demekti; kimlikli bir API'nin yönlendirmesi zaten
 *   nadirdir ve çağıranın görmesi gereken bir şeydir.
 * - Curl() (GET) gövde göndermez; $post_params sorgu dizesine eklenir.
 */
class CurlModule
{
    /**
     * GET isteği.
     *
     * 4.0.0: $post_params artık gövde olarak değil, adresin sorgu dizesine
     * (http_build_query) eklenerek gider; $content_type yok sayılır ve
     * yalnız imza uyumu için duruyor. GET'in gövdesi HTTP'de anlamsızdır:
     * sunucuların çoğu (PHP'nin $_GET'i dahil) onu görmez, araya giren
     * vekil sunucular ise atabilir.
     */
    static function Curl($url, $post_params, $content_type = 'application/x-www-form-urlencoded', $basic_auth = ['username' => '', 'password' => ''])
    {
        if (!self::isHttpUrl($url)) return false;

        if (!empty($post_params)) {
            $query = http_build_query($post_params);
            if ($query !== '') {
                $url .= (str_contains($url, '?') ? '&' : '?') . $query;
            }
        }

        $headers = [];
        $auth    = self::authHeader($basic_auth);
        if ($auth !== null) $headers[] = $auth;

        $options = array(
            'http' => array(
                'header'          => $headers,
                'method'          => 'GET',
                'timeout'         => 2000,
                'ignore_errors'   => true,
                'follow_location' => $auth === null ? 1 : 0,
            )
        );

        $context = stream_context_create($options);

        return @file_get_contents($url, false, $context);
    }

    static function Post($url, $post_params, $content_type = 'application/x-www-form-urlencoded', $basic_auth = ['username' => '', 'password' => ''])
    {
        if (!self::isHttpUrl($url)) return false;

        switch ($content_type) {
            case 'application/json':
                $content = json_encode($post_params);
                break;
            default:
                $content = http_build_query($post_params);
                break;
        }

        $headers = ["Content-Type: $content_type", "Content-Length: " . strlen($content)];
        $auth    = self::authHeader($basic_auth);
        if ($auth !== null) $headers[] = $auth;

        $options = array(
            'http' => array(
                'header'          => $headers,
                'method'          => 'POST',
                'content'         => $content,
                'timeout'         => 2000,
                'ignore_errors'   => true,
                'follow_location' => $auth === null ? 1 : 0,
            )
        );

        $context = stream_context_create($options);
        return @file_get_contents($url, false, $context);
    }

    /**
     * $timeout bağlantı kurma süresidir (saniye). 4.0.0: isteğin tamamı için
     * de bir üst sınır var — en az 30 sn, $timeout daha büyükse o. Önceden
     * yalnız bağlantı süresi sınırlıydı; bağlanıp yanıtı damla damla veren
     * bir sunucu PHP işçisini süresiz tutabiliyordu.
     */
    static function GetCurlPage($url = '', $timeout = 5,$is_json = true)
    {
        if (empty($url) || !self::isHttpUrl($url)) return false;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, (int)$timeout);
        curl_setopt($ch, CURLOPT_TIMEOUT, max(30, (int)$timeout));

        /* isHttpUrl() adresi zaten süzdü; bu ikinci kat curl'ün kendi
           düzeyinde (yönlendirme açılırsa Location dahil) http(s) dışını
           reddeder. */
        if (defined('CURLOPT_PROTOCOLS_STR')) {
            curl_setopt($ch, CURLOPT_PROTOCOLS_STR, 'http,https');
            curl_setopt($ch, CURLOPT_REDIR_PROTOCOLS_STR, 'http,https');
        } else {
            curl_setopt($ch, CURLOPT_PROTOCOLS, CURLPROTO_HTTP | CURLPROTO_HTTPS);
            curl_setopt($ch, CURLOPT_REDIR_PROTOCOLS, CURLPROTO_HTTP | CURLPROTO_HTTPS);
        }

        $result = curl_exec($ch);

        if ($is_json)
        $result = json_decode((string)$result, true);

        return $result;
    }

    /** Yalnız http(s) ve sunucu adı olan adres. */
    private static function isHttpUrl($url): bool
    {
        if (!is_string($url) || $url === '') return false;

        $scheme = strtolower((string)parse_url($url, PHP_URL_SCHEME));
        $host   = (string)parse_url($url, PHP_URL_HOST);

        return in_array($scheme, ['http', 'https'], true) && $host !== '';
    }

    /** Kullanıcı adı boşsa null: başlık hiç gönderilmez. */
    private static function authHeader($basic_auth): ?string
    {
        if (!is_array($basic_auth)) return null;

        $username = (string)($basic_auth['username'] ?? '');
        if ($username === '') return null;

        return "Authorization: Basic " . base64_encode($username . ":" . (string)($basic_auth['password'] ?? ''));
    }
}

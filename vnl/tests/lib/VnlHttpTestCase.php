<?php
/*!
 * ============================================================
 *  Voxnup Not Laravel (VNL) — HTTP Test Temeli
 *  Çalıştırıcının açtığı yerel sunucuya gerçek istek atar.
 *  Authors: Voxnup Authors
 * ============================================================
 *
 * Güvenlik kapıları (giriş, CSRF, dağıtıcı) exit ile biter; süreç içinde
 * yakalanamazlar. Gerçek yolu sınamanın yolu gerçek istektir. Sunucuyu
 * `php vnl.php test` açar ve adresini VNL_TEST_BASE_URL ile verir; yoksa
 * testler atlanır.
 *
 * Çerezler test nesnesi boyunca saklanır (oturum sürer); setUp() her
 * testten önce sıfırlar. Yönlendirmeler İZLENMEZ: 302'nin kendisi sınanır.
 */

final class VnlTestResponse
{
    /** @param array<string, string[]> $headers küçük harfli ad => değerler */
    public function __construct(
        public readonly int $status,
        public readonly array $headers,
        public readonly string $body
    ) {}

    public function header(string $name): ?string
    {
        return $this->headers[strtolower($name)][0] ?? null;
    }

    /** Gövde JSON ise dizi, değilse null. */
    public function json(): ?array
    {
        $data = json_decode($this->body, true);

        return is_array($data) ? $data : null;
    }

    public function devcode(): ?string
    {
        return $this->json()['devcode'] ?? null;
    }
}

abstract class VnlHttpTestCase extends VnlTestCase
{
    /** @var array<string, string> */
    protected array $cookies = [];

    public function setUp(): void
    {
        if ($this->baseUrl() === '') {
            $this->skip('VNL_TEST_BASE_URL yok (php vnl.php test sunucuyu kendisi açar)');
        }

        $this->cookies = [];
    }

    protected function baseUrl(): string
    {
        return rtrim((string)getenv('VNL_TEST_BASE_URL'), '/');
    }

    /** Sunucunun Host değeri (Origin başlığı için). */
    protected function host(): string
    {
        return (string)parse_url($this->baseUrl(), PHP_URL_HOST) . ':' . (string)parse_url($this->baseUrl(), PHP_URL_PORT);
    }

    protected function get(string $path, array $headers = []): VnlTestResponse
    {
        return $this->request('GET', $path, $headers);
    }

    /** Form gövdeli POST. Varsayılan olarak AJAX gibi görünür ve aynı kökenden gelir. */
    protected function post(string $path, array $form = [], array $headers = []): VnlTestResponse
    {
        $headers += [
            'X-Requested-With' => 'XMLHttpRequest',
            'Accept'           => 'application/json',
            'Origin'           => 'http://' . $this->host(),
        ];

        return $this->request('POST', $path, $headers, http_build_query($form), 'application/x-www-form-urlencoded');
    }

    /**
     * Girişli ve CSRF'li bir action adresi (4.0.1). Testler bunu yalnız
     * kapıda takılan isteklerde kullanır (401, 403 VNL_CSRF): action hiç
     * çalışmaz, o yüzden projenin herhangi bir metodu olabilir.
     *
     * Şablonun /app/users/getList'i varsa o; yoksa projenin actions/
     * dizininden PUBLIC_METHODS ve CSRF_EXEMPT dışında kalan ilk public
     * metot (metin taraması: dosya yüklenmez, yan etki yok). Hiçbiri yoksa
     * test atlanır — çekirdek testleri şablonun action'larına bağlı değil.
     */
    protected function protectedAction(): string
    {
        static $found = null;

        if ($found === null) {
            $found = '';
            $files = glob(VNL_ROOT . '/actions/action_*.php') ?: [];
            usort($files, static fn($a, $b) => (basename($b) === 'action_users.php') <=> (basename($a) === 'action_users.php') ?: strcmp($a, $b));

            foreach ($files as $file) {
                $code   = (string)file_get_contents($file);
                $module = substr(basename($file, '.php'), strlen('action_'));

                if (!preg_match('/^[a-z0-9_]+$/i', $module) || !preg_match('/\bclass\s+action_' . preg_quote($module, '/') . '\b/i', $code)) {
                    continue;
                }

                $open = [];
                foreach (['PUBLIC_METHODS', 'CSRF_EXEMPT'] as $const) {
                    if (preg_match('/\b' . $const . '\s*=\s*\[([^\]]*)\]/', $code, $m)) {
                        preg_match_all('/[\'"]([a-z0-9_]+)[\'"]/i', $m[1], $names);
                        $open = array_merge($open, array_map('strtolower', $names[1]));
                    }
                }

                preg_match_all('/^\s*(?:final\s+)?public\s+function\s+([a-z0-9_]+)\s*\(/im', $code, $methods);
                $candidates = array_values(array_filter($methods[1],
                    static fn($m) => !str_starts_with($m, '__') && !in_array(strtolower($m), $open, true)));

                if ($candidates !== []) {
                    $method = in_array('getList', $candidates, true) ? 'getList' : $candidates[0];
                    $found  = '/app/' . $module . '/' . $method;
                    break;
                }
            }
        }

        if ($found === '') {
            $this->skip('projede girişli bir action metodu bulunamadı (actions/action_*.php)');
        }

        return $found;
    }

    /**
     * Testin gerçekten ÇALIŞTIRDIĞI şablon action'ı projede yoksa atlar.
     * Örn. izin kapısı testleri /app/users/getList'in Users iznine bağlı
     * davranışını sınar; proje o metodu kaldırdıysa sınanacak bir şey yok.
     */
    protected function requireTemplateAction(string $module, string $method): void
    {
        $file = VNL_ROOT . '/actions/action_' . $module . '.php';

        if (!is_file($file) || !preg_match('/public\s+function\s+' . preg_quote($method, '/') . '\s*\(/i', (string)file_get_contents($file))) {
            $this->skip("şablonun /app/{$module}/{$method} action'ı projede yok");
        }
    }

    /**
     * Oturumun CSRF jetonunu girişsiz açılan bir sayfanın meta etiketinden okur.
     * Oturum çerezi saklanır; sonraki istekler aynı oturumla gider.
     */
    protected function csrfToken(string $page = '/login'): string
    {
        $res = $this->get($page);

        if (!preg_match('/<meta name="csrf-token" content="([^"]*)"/', $res->body, $m)) {
            $this->fail($page . ' sayfasında <meta name="csrf-token"> yok (durum ' . $res->status . ')');
        }

        return html_entity_decode($m[1], ENT_QUOTES);
    }

    protected function request(string $method, string $path, array $headers = [], ?string $body = null, ?string $contentType = null): VnlTestResponse
    {
        $lines = [];

        if ($body !== null) {
            $lines[] = 'Content-Type: ' . ($contentType ?? 'application/octet-stream');
            $lines[] = 'Content-Length: ' . strlen($body);
        }

        if ($this->cookies !== []) {
            $pairs = [];
            foreach ($this->cookies as $name => $value) {
                $pairs[] = $name . '=' . $value;
            }
            $lines[] = 'Cookie: ' . implode('; ', $pairs);
        }

        foreach ($headers as $name => $value) {
            $lines[] = $name . ': ' . $value;
        }

        $context = stream_context_create(['http' => [
            'method'          => $method,
            'header'          => implode("\r\n", $lines),
            'content'         => $body ?? '',
            'ignore_errors'   => true,
            'follow_location' => 0,
            'max_redirects'   => 1,
            'timeout'         => 15,
            'protocol_version' => 1.1,
        ]]);

        $http_response_header = [];
        $raw = @file_get_contents($this->baseUrl() . $path, false, $context);

        /* PHP 8.5: yerel $http_response_header kullanımdan kalktı. */
        if (function_exists('http_get_last_response_headers')) {
            $http_response_header = http_get_last_response_headers() ?? [];
        }

        if ($raw === false && $http_response_header === []) {
            $this->fail($method . ' ' . $path . ': sunucuya ulaşılamadı');
        }

        $status  = 0;
        $parsed  = [];

        foreach ($http_response_header as $line) {
            if (preg_match('~^HTTP/\S+\s+(\d{3})~', $line, $m)) {
                $status = (int)$m[1];
                $parsed = [];   // ara yanıt (100 Continue) varsa sonuncusu geçerli
                continue;
            }

            $pos = strpos($line, ':');
            if ($pos === false) {
                continue;
            }

            $name  = strtolower(trim(substr($line, 0, $pos)));
            $value = trim(substr($line, $pos + 1));
            $parsed[$name][] = $value;

            if ($name === 'set-cookie') {
                $this->storeCookie($value);
            }
        }

        return new VnlTestResponse($status, $parsed, (string)$raw);
    }

    private function storeCookie(string $header): void
    {
        $pair = explode(';', $header, 2)[0];
        $pos  = strpos($pair, '=');

        if ($pos === false) {
            return;
        }

        $name  = trim(substr($pair, 0, $pos));
        $value = trim(substr($pair, $pos + 1));

        /* Silme: boş değer ya da geçmiş tarih. */
        if ($value === '' || $value === 'deleted' || preg_match('/max-age=0\b|expires=Thu, 01[ -]Jan[ -]1970/i', $header)) {
            unset($this->cookies[$name]);

            return;
        }

        $this->cookies[$name] = $value;
    }
}

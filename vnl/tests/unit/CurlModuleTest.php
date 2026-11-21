<?php

/**
 * CurlModule güvenlik davranışı (4.0.0).
 *
 * Dış ağa çıkılmaz: dosya kendi yankı sunucusunu (php -S) açar. Sunucu
 * aldığı isteği JSON olarak geri yazar; /redirect ise BAŞKA bir sunucu
 * adına (127.0.0.1 → localhost) yönlendirir — kimlik bilgisinin oraya
 * taşınıp taşınmadığı böyle görülür.
 */
final class CurlModuleTest extends VnlTestCase
{
    /** @var resource|null */
    private static $server = null;
    private static int $port = 0;
    private static string $dir = '';

    public function setUp(): void
    {
        if (self::$server !== null) {
            return;
        }

        self::$dir = sys_get_temp_dir() . '/vnl-curl-test-' . bin2hex(random_bytes(4));
        mkdir(self::$dir);
        file_put_contents(self::$dir . '/router.php', <<<'PHP'
<?php
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if ($path === '/redirect') {
    header('Location: http://localhost:' . $_SERVER['SERVER_PORT'] . '/echo', true, 302);
    echo 'REDIRECT-BODY';
    return true;
}
header('Content-Type: application/json');
echo json_encode([
    'method' => $_SERVER['REQUEST_METHOD'],
    'host'   => $_SERVER['HTTP_HOST'] ?? '',
    'auth'   => $_SERVER['HTTP_AUTHORIZATION'] ?? null,
    'query'  => $_GET,
    'body'   => file_get_contents('php://input'),
]);
return true;
PHP);

        $probe = @stream_socket_server('tcp://127.0.0.1:0');
        if (!$probe) {
            $this->skip('boş port yok');
        }
        self::$port = (int)substr(strrchr((string)stream_socket_get_name($probe, false), ':'), 1);
        fclose($probe);

        /* localhost'un da dinlenmesi için 0.0.0.0. */
        $server = proc_open(
            [PHP_BINARY, '-S', '0.0.0.0:' . self::$port, self::$dir . '/router.php'],
            [0 => ['pipe', 'r'], 1 => ['file', self::$dir . '/out', 'w'], 2 => ['file', self::$dir . '/err', 'w']],
            $pipes
        );

        for ($i = 0; $i < 100; $i++) {
            $conn = @fsockopen('127.0.0.1', self::$port, $errno, $errstr, 0.2);
            if ($conn) {
                fclose($conn);
                self::$server = $server;
                register_shutdown_function(static function (): void {
                    if (is_resource(self::$server)) {
                        proc_terminate(self::$server);
                        proc_close(self::$server);
                    }
                    @array_map('unlink', glob(self::$dir . '/*') ?: []);
                    @rmdir(self::$dir);
                });
                return;
            }
            usleep(50000);
        }

        $this->skip('yankı sunucusu açılamadı');
    }

    private function url(string $path): string
    {
        return 'http://127.0.0.1:' . self::$port . $path;
    }

    private function decode($raw): array
    {
        $data = is_string($raw) ? json_decode($raw, true) : null;
        if (!is_array($data)) {
            $this->fail('yankı JSON değil: ' . self::export($raw));
        }

        return $data;
    }

    public function testGetSendsNoAuthorizationWithDefaultCredentials(): void
    {
        $echo = $this->decode(CurlModule::Curl($this->url('/echo'), []));
        $this->assertNull($echo['auth'], 'boş kimlikle Authorization gitmemeli');
    }

    public function testGetSendsAuthorizationWhenGiven(): void
    {
        $echo = $this->decode(CurlModule::Curl($this->url('/echo'), [], 'application/x-www-form-urlencoded', ['username' => 'u', 'password' => 'p']));
        $this->assertSame('Basic ' . base64_encode('u:p'), $echo['auth']);
    }

    public function testGetHasNoBodyAndParamsGoToQuery(): void
    {
        $echo = $this->decode(CurlModule::Curl($this->url('/echo?a=1'), ['b' => '2', 'c' => 'x y']));
        $this->assertSame('GET', $echo['method']);
        $this->assertSame('', $echo['body']);
        $this->assertSame(['a' => '1', 'b' => '2', 'c' => 'x y'], $echo['query']);
    }

    public function testPostSendsNoAuthorizationWithDefaultCredentials(): void
    {
        $echo = $this->decode(CurlModule::Post($this->url('/echo'), ['k' => 'v']));
        $this->assertSame('POST', $echo['method']);
        $this->assertSame('k=v', $echo['body']);
        $this->assertNull($echo['auth']);
    }

    public function testRedirectIsNotFollowedWhenCredentialsAreSent(): void
    {
        $auth = ['username' => 'u', 'password' => 'p'];

        $this->assertSame('REDIRECT-BODY', CurlModule::Curl($this->url('/redirect'), [], 'application/x-www-form-urlencoded', $auth));
        $this->assertSame('REDIRECT-BODY', CurlModule::Post($this->url('/redirect'), ['k' => 'v'], 'application/x-www-form-urlencoded', $auth));
    }

    public function testRedirectIsStillFollowedWithoutCredentials(): void
    {
        $echo = $this->decode(CurlModule::Curl($this->url('/redirect'), []));
        $this->assertSame('localhost:' . self::$port, $echo['host']);
        $this->assertNull($echo['auth']);
    }

    public function testNonHttpSchemesAreRefused(): void
    {
        $secret = self::$dir . '/router.php';

        foreach (['file://' . $secret, 'php://filter/resource=' . $secret, 'phar://' . $secret . '/x', 'ftp://127.0.0.1/x', '/etc/hostname', '//127.0.0.1/x'] as $url) {
            $this->assertFalse(CurlModule::Curl($url, []), 'Curl ' . $url);
            $this->assertFalse(CurlModule::Post($url, []), 'Post ' . $url);
            $this->assertFalse(CurlModule::GetCurlPage($url, 5, false), 'GetCurlPage ' . $url);
        }
    }

    public function testGetCurlPageStillWorks(): void
    {
        $data = CurlModule::GetCurlPage($this->url('/echo?q=1'), 5, true);
        $this->assertSame('GET', $data['method'] ?? null);
        $this->assertSame(['q' => '1'], $data['query'] ?? null);
    }
}

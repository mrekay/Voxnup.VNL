<?php

/**
 * 4.0.0 Aşama 3b: projelerin çekirdek dosyası kopyalamadan değiştirebildiği
 * ayarlar ve kancalar (güvenlik başlıkları, JSON bayrakları, hata ayrıntısı
 * kancası, parola kuralı, mail görsel gömme, mb_strimwidth yedeği).
 *
 * Ortam değişkeni putenv ile verilir; VnlEnv süreç ortamını .env'in önüne
 * koyar. Her dosya ayrı süreçte koştuğu için başka testlere sızmaz.
 */
final class ProjectSettingsTest extends VnlTestCase
{
    private array $set = [];

    private function env(string $key, string $value): void
    {
        $this->set[] = $key;
        putenv($key . '=' . $value);
    }

    public function tearDown(): void
    {
        foreach ($this->set as $key) {
            putenv($key);
        }
        $this->set = [];
    }

    /* ---- güvenlik başlıkları ---- */

    public function testSecurityHeaderDefaults(): void
    {
        /* Proje .env'de bir başlığı değiştirdiyse varsayılan sınanamaz (4.0.1). */
        $defaults = [
            'VNL_SECURITY_HEADERS'          => true,
            'VNL_HEADER_FRAME_OPTIONS'      => 'SAMEORIGIN',
            'VNL_HEADER_REFERRER_POLICY'    => 'strict-origin-when-cross-origin',
            'VNL_HEADER_PERMISSIONS_POLICY' => 'camera=(), microphone=(), geolocation=()',
            'VNL_HEADER_HSTS'               => 'max-age=31536000',
        ];
        foreach ($defaults as $key => $default) {
            if (VnlEnv::get($key, $default) !== $default) {
                $this->skip("proje {$key} ayarını değiştirmiş; varsayılan sınanamaz");
            }
        }

        $h = vnl_security_headers(false);

        $this->assertSame('nosniff', $h['X-Content-Type-Options']);
        $this->assertSame('SAMEORIGIN', $h['X-Frame-Options']);
        $this->assertSame('strict-origin-when-cross-origin', $h['Referrer-Policy']);
        $this->assertSame('camera=(), microphone=(), geolocation=()', $h['Permissions-Policy']);
        $this->assertFalse(isset($h['Strict-Transport-Security']), 'HSTS yalnız TLS');
        $this->assertSame('max-age=31536000', vnl_security_headers(true)['Strict-Transport-Security']);
    }

    public function testSecurityHeaderOverrideAndRemoval(): void
    {
        $this->env('VNL_HEADER_PERMISSIONS_POLICY', 'camera=(self), microphone=(self)');
        $this->env('VNL_HEADER_FRAME_OPTIONS', 'false');

        $h = vnl_security_headers(false);

        $this->assertSame('camera=(self), microphone=(self)', $h['Permissions-Policy']);
        $this->assertFalse(isset($h['X-Frame-Options']));
        $this->assertSame('nosniff', $h['X-Content-Type-Options']);
    }

    public function testSecurityHeaderValueCannotInjectHeaders(): void
    {
        $this->env('VNL_HEADER_REFERRER_POLICY', "no-referrer\r\nSet-Cookie: x=1");

        $this->assertNotContains("\n", vnl_security_headers(false)['Referrer-Policy']);
    }

    public function testSecurityHeadersMasterSwitch(): void
    {
        $this->env('VNL_SECURITY_HEADERS', 'false');

        $this->assertSame([], vnl_security_headers(true));
    }

    /* ---- JSON bayrakları ---- */

    public function testJsonDataFlags(): void
    {
        /* vnl_json_data_flags() süreç başına bir kez okur; bu dosyada tek çağrı. */
        $this->env('VNL_JSON_DATA_FLAGS', 'NUMERIC_CHECK, JSON_PRESERVE_ZERO_FRACTION, BILINMEYEN');

        $this->assertSame(JSON_NUMERIC_CHECK | JSON_PRESERVE_ZERO_FRACTION, vnl_json_data_flags());
    }

    /* ---- hata ayrıntısı kancası ---- */

    public function testErrorDetailHook(): void
    {
        if (function_exists('vnl_project_error_detail')) {
            $this->skip('proje vnl_project_error_detail() tanımlamış; kancasız davranış sınanamaz');
        }

        $e = new RuntimeException('x');

        $this->assertFalse(vnl_error_detail_visible($e), 'kanca yokken kapalı');

        if (!function_exists('vnl_project_error_detail')) {
            function vnl_project_error_detail(Throwable $e)
            {
                return $GLOBALS['vnl_test_detail']($e);
            }
        }

        $GLOBALS['vnl_test_detail'] = fn() => true;
        $this->assertTrue(vnl_error_detail_visible($e));

        $GLOBALS['vnl_test_detail'] = fn() => 1;
        $this->assertFalse(vnl_error_detail_visible($e), 'yalnız true açar');

        $GLOBALS['vnl_test_detail'] = fn() => throw new LogicException('oturum yok');
        $this->assertFalse(vnl_error_detail_visible($e), 'fırlatan kanca kapalı sayılır');
    }

    /* ---- parola kuralı ---- */

    public function testPasswordDefaultsMatch3x(): void
    {
        $this->assertSame(['min' => 8, 'require' => ['upper', 'lower', 'digit', 'symbol']], DefaultValidator::passwordPolicy());

        DefaultValidator::validateValidPassword('Guclu.Parola1');

        foreach (['Kisa.1a', 'kucuk.harf1', 'BUYUK.HARF1', 'Rakamsiz.Parola', 'Sembolsuz1Parola'] as $bad) {
            $this->assertThrows(fn() => DefaultValidator::validateValidPassword($bad), StandartException::class, $bad);
        }
    }

    public function testPasswordCountsCharactersAndTurkishLetters(): void
    {
        /* 7 karakter, 3.x strlen ile 9 bayt sayıp geçiriyordu. */
        $this->assertThrows(fn() => DefaultValidator::validateValidPassword('Şğ.1abc'), StandartException::class);

        /* Ş büyük harf sayılır; 3.x yalnız A-Z'ye bakıyordu. */
        DefaultValidator::validateValidPassword('Şifre.123');
    }

    public function testPasswordPolicyIsConfigurable(): void
    {
        $this->env('VNL_PASSWORD_MIN_LENGTH', '12');
        $this->env('VNL_PASSWORD_REQUIRE', 'digit');

        $this->assertThrows(fn() => DefaultValidator::validateValidPassword('kisa1'), StandartException::class);
        DefaultValidator::validateValidPassword('sadecekucuk1');
        DefaultValidator::requireValidPassword('sadecekucuk1');
    }

    /* ---- mail görsel gömme ---- */

    public function testEmbedHostsOffByDefault(): void
    {
        $this->assertSame([], MailModule::embedHosts());
    }

    public function testEmbeddableUrlsMatchHostExactly(): void
    {
        $hosts = ['cdn.example.com'];
        $html  = '<img src="https://cdn.example.com/a.png">'
            . '<img src=\'http://CDN.example.com/b.png\'>'
            . '<img src="https://cdn.example.com/a.png">'                      // tekrar
            . '<img src="https://cdn.example.com.evil.io/c.png">'
            . '<img src="https://evil.io/?u=cdn.example.com">'
            . '<img src="https://cdn.example.com@evil.io/d.png">'
            . '<img src="https://user@cdn.example.com/e.png">'
            . '<img src="https://cdn.example.com:8080/f.png">'
            . '<img src="http://169.254.169.254/latest/meta-data">'
            . '<a href="https://cdn.example.com/link.png">bağlantı</a>';

        $this->assertSame(
            ['https://cdn.example.com/a.png', 'http://CDN.example.com/b.png'],
            MailModule::embeddableUrls($html, $hosts)
        );
    }

    /* ---- mb_strimwidth ---- */

    public function testStrimwidthMatchesNative(): void
    {
        if (!extension_loaded('mbstring')) {
            $this->skip('karşılaştırılacak gerçek mbstring yok');
        }

        $cases = [
            ['Merhaba dünya', 0, 8, '…'],
            ['Merhaba dünya', 0, 40, '…'],
            ['Merhaba dünya', 3, 5, ''],
            ['Merhaba dünya', -5, 3, '.'],
            ['Şişli ğüöç', 0, 6, '...'],
            ['abc', 0, 2, '...'],
            ['abcdef', 0, -2, ''],
        ];

        foreach ($cases as [$s, $start, $width, $marker]) {
            $this->assertSame(
                mb_strimwidth($s, $start, $width, $marker, 'UTF-8'),
                vnl_mb_strimwidth($s, $start, $width, $marker, 'UTF-8'),
                json_encode([$s, $start, $width, $marker], JSON_UNESCAPED_UNICODE)
            );
        }
    }
}

<?php

/**
 * İstek gövdesi uçtan uca (4.0.0). Gövde giriş denetiminden önce okunduğu
 * için girişsiz istekle sınanır.
 *
 * JSON'un çözülüp çözülmediği CSRF jetonuyla gözlenir: jeton gövdedeki
 * _token alanında gelir; gövde çözülmezse dağıtıcı 403 VNL_CSRF der.
 */
final class HttpRequestBodyTest extends VnlHttpTestCase
{
    private function headers(): array
    {
        return [
            'X-Requested-With' => 'XMLHttpRequest',
            'Accept'           => 'application/json',
            'Origin'           => 'http://' . $this->host(),
        ];
    }

    private function postRaw(string $body, string $contentType)
    {
        return $this->request('POST', '/app/login/logout', $this->headers(), $body, $contentType);
    }

    public function testJsonBodyIsDecodedWithJsonContentType(): void
    {
        $token = $this->csrfToken();
        $res   = $this->postRaw(json_encode(['_token' => $token]), 'application/json; charset=utf-8');

        $this->assertNotSame('VNL_CSRF', $res->devcode(), 'JSON gövdesi çözülmedi: ' . $res->status);
    }

    public function testJsonBodyIsIgnoredWithoutJsonContentType(): void
    {
        $token = $this->csrfToken();
        $res   = $this->postRaw(json_encode(['_token' => $token]), 'text/plain');

        $this->assertSame(403, $res->status);
        $this->assertSame('VNL_CSRF', $res->devcode());
    }

    public function testOversizedJsonBodyIs413(): void
    {
        $max = (int)(getenv('VNL_MAX_JSON_BODY') ?: Request::MAX_JSON_BODY);
        $res = $this->postRaw('{"x":"' . str_repeat('a', $max) . '"}', 'application/json');

        $this->assertSame(413, $res->status);
        $this->assertSame('VNL_BODY_TOO_LARGE', $res->devcode());
    }

    public function testLargeNonJsonBodyIsNotRead(): void
    {
        $res = $this->postRaw(str_repeat('a', Request::MAX_JSON_BODY + 10), 'application/octet-stream');

        $this->assertNotSame(413, $res->status);
    }

    public function testTooDeepJsonIsTreatedAsEmpty(): void
    {
        $token = $this->csrfToken();
        $deep  = str_repeat('[', 100) . str_repeat(']', 100);
        $res   = $this->postRaw('{"_token":"' . $token . '","x":' . $deep . '}', 'application/json');

        $this->assertSame('VNL_CSRF', $res->devcode());
    }
}

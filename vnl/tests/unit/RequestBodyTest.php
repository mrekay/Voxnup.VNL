<?php

/**
 * Request gövde kuralları (4.0.0): JSON yalnız Content-Type söylüyorsa,
 * derinlik sınırlı, boyut VNL_MAX_JSON_BODY. Uçtan uca hâli http/RequestBodyTest.
 */
final class RequestBodyTest extends VnlTestCase
{
    public function testJsonContentTypes(): void
    {
        $this->assertTrue(Request::isJsonContentType('application/json'));
        $this->assertTrue(Request::isJsonContentType('Application/JSON; charset=utf-8'));
        $this->assertTrue(Request::isJsonContentType('application/merge-patch+json'));
    }

    public function testNonJsonContentTypes(): void
    {
        $this->assertFalse(Request::isJsonContentType(''));
        $this->assertFalse(Request::isJsonContentType('text/plain'));
        $this->assertFalse(Request::isJsonContentType('application/x-www-form-urlencoded'));
        $this->assertFalse(Request::isJsonContentType('text/json+x'));
        $this->assertFalse(Request::isJsonContentType('application/jsonp'));
    }

    public function testDecodeKeepsTypes(): void
    {
        $this->assertSame(['a' => 0, 'b' => false, 'c' => ['d' => null]], Request::decodeJson('{"a":0,"b":false,"c":{"d":null}}'));
    }

    public function testDecodeRejectsScalarsAndGarbage(): void
    {
        $this->assertSame([], Request::decodeJson(''));
        $this->assertSame([], Request::decodeJson('   '));
        $this->assertSame([], Request::decodeJson('"metin"'));
        $this->assertSame([], Request::decodeJson('42'));
        $this->assertSame([], Request::decodeJson('{bozuk'));
    }

    public function testDecodeDepthLimit(): void
    {
        $ok  = str_repeat('[', Request::JSON_DEPTH - 2) . str_repeat(']', Request::JSON_DEPTH - 2);   // nesne + 62 dizi
        $bad = str_repeat('[', Request::JSON_DEPTH + 1) . str_repeat(']', Request::JSON_DEPTH + 1);

        $this->assertNotSame([], Request::decodeJson('{"x":' . $ok . '}'));
        $this->assertSame([], Request::decodeJson($bad));
    }

    public function testMaxJsonBodyDefault(): void
    {
        if (VnlEnv::get('VNL_MAX_JSON_BODY') !== null) {
            $this->skip('VNL_MAX_JSON_BODY projede ayarlı');
        }

        $this->assertSame(Request::MAX_JSON_BODY, Request::maxJsonBody());
    }
}

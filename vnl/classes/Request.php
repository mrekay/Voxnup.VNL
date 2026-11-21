<?php
/*!
 * ============================================================
 *  Voxnup Not Laravel (VNL) — İstek Nesnesi
 *  GET/POST ve JSON gövdesini tek arayüzde toplar.
 *  Authors: Voxnup Authors
 * ============================================================
 */

/**
 * İstek verisine erişim.
 *
 * VNL 3.0'da giriş tarafındaki htmlspecialchars KALDIRILDI. Veri ne
 * gönderildiyse o şekilde okunur ve o şekilde saklanır; kaçış artık
 * çıkış katmanının işidir:
 *
 *     {{ $ad }}          şablonda otomatik kaçışlar
 *     vnl_e($ad)         düz PHP'de aynı işi yapar
 *
 * 2.x'te kaçış girişte yapılıyordu; bu, veritabanına "Ali &amp; Veli"
 * yazılmasına, parolaların hash'lenmeden önce bozulmasına ve JSON
 * gövdesindeki sayı/boolean değerlerin string'e dönmesine yol açıyordu.
 */
class Request
{
    /** @var array GET verisi — her zaman dizi. */
    private array $get = [];

    /** @var array POST ya da JSON gövdesi — her zaman dizi. */
    private array $post = [];

    public function __construct()
    {
        $this->get  = is_array($_GET) ? $_GET : [];
        $this->post = $this->readBody();

        /* Superglobal'ler nesneyle aynı veriyi taşır. 2.x bunları bazı
           isteklerde null'a çeviriyordu; artık daima dizi. */
        $_GET  = $this->get;
        $_POST = $this->post;
    }

    /** GET ile gelen değer. Anahtar yoksa $default döner. */
    public function get($key, $default = null)
    {
        return array_key_exists($key, $this->get) ? $this->get[$key] : $default;
    }

    /** POST ya da JSON gövdesinden gelen değer. Anahtar yoksa $default döner. */
    public function post($key, $default = null)
    {
        return array_key_exists($key, $this->post) ? $this->post[$key] : $default;
    }

    /**
     * Anahtar gönderildi mi?
     *
     * array_key_exists kullanılır: değeri null olan bir anahtar da
     * gönderilmiş sayılır. 2.x isset() kullandığı için "0" ve boş
     * string gönderimlerini görünmez yapıyordu.
     */
    public function getHas($key): bool
    {
        return array_key_exists($key, $this->get);
    }

    public function postHas($key): bool
    {
        return array_key_exists($key, $this->post);
    }

    /** GET verisinin tamamı. */
    public function getAll(): array
    {
        return $this->get;
    }

    /** POST/JSON verisinin tamamı. */
    public function postAll(): array
    {
        return $this->post;
    }

    public function setGet($key, $value)
    {
        $this->get[$key] = $value;
        $_GET[$key]      = $value;

        return $value;
    }

    public function setPost($key, $value)
    {
        $this->post[$key] = $value;
        $_POST[$key]      = $value;

        return $value;
    }

    public function isPost(): bool
    {
        return strtoupper($_SERVER['REQUEST_METHOD'] ?? '') === 'POST';
    }

    public function method(): string
    {
        return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    }

    /* ---------------------------------------------------------------- */

    /** JSON gövdesinin varsayılan üst sınırı (bayt). VNL_MAX_JSON_BODY ezer. */
    public const MAX_JSON_BODY = 1048576;

    /** json_decode iç içe geçme sınırı. */
    public const JSON_DEPTH = 64;

    /**
     * Gövdeyi okur.
     *
     * Form gönderimi zaten $_POST'u doldurur (sınırı PHP'nin post_max_size'ı).
     * Boşsa ve istek **Content-Type: application/json** ise gövde JSON olarak
     * çözülür; çözülemezse boş dizi döner.
     *
     * 4.0.0: Bu okuma giriş denetiminden ÖNCE, her istekte yapılır. Bu yüzden
     *  - JSON yalnız Content-Type söylüyorsa çözülür (3.x her gövdeyi denerdi),
     *  - gövde en fazla VNL_MAX_JSON_BODY bayt okunur, aşarsa 413,
     *  - iç içe geçme JSON_DEPTH ile sınırlıdır; aşan gövde boş sayılır.
     * Gövdeyi kendisi okuyanlar (Stripe webhook gibi) etkilenmez: Content-Type
     * JSON değilse burada hiç okunmaz.
     *
     * JSON'daki tipler korunur: 0 sıfır kalır, false false kalır,
     * iç içe nesneler dizi olarak gelir.
     */
    private function readBody(): array
    {
        if (!empty($_POST) && is_array($_POST)) {
            return $_POST;
        }

        if (!self::isJsonContentType($_SERVER['CONTENT_TYPE'] ?? $_SERVER['HTTP_CONTENT_TYPE'] ?? '')) {
            return [];
        }

        $max = self::maxJsonBody();

        /* Başlık zaten fazlasını söylüyorsa gövdeye hiç dokunulmaz. */
        $declared = $_SERVER['CONTENT_LENGTH'] ?? $_SERVER['HTTP_CONTENT_LENGTH'] ?? null;
        if ($declared !== null && ctype_digit((string)$declared) && (int)$declared > $max) {
            self::tooLarge();
        }

        /* Content-Length yalan söyleyebilir (chunked): bir bayt fazlası okunur. */
        $body = @file_get_contents('php://input', false, null, 0, $max + 1);
        if ($body === false) {
            return [];
        }
        if (strlen($body) > $max) {
            self::tooLarge();
        }

        return self::decodeJson($body);
    }

    /** application/json ya da +json (ör. application/merge-patch+json); parametreler yok sayılır. */
    public static function isJsonContentType(string $contentType): bool
    {
        $type = strtolower(trim(explode(';', $contentType, 2)[0]));

        return $type === 'application/json' || (str_starts_with($type, 'application/') && str_ends_with($type, '+json'));
    }

    /** Gövdeyi diziye çevirir; boş, bozuk, skaler ya da fazla derin gövde → []. */
    public static function decodeJson(string $body): array
    {
        if (trim($body) === '') {
            return [];
        }

        $decoded = json_decode($body, true, self::JSON_DEPTH);

        return is_array($decoded) ? $decoded : [];
    }

    /** VNL_MAX_JSON_BODY (bayt); geçersiz ya da pozitif değilse varsayılan. */
    public static function maxJsonBody(): int
    {
        $value = class_exists('VnlEnv') ? VnlEnv::get('VNL_MAX_JSON_BODY', self::MAX_JSON_BODY) : self::MAX_JSON_BODY;

        return (is_int($value) || (is_string($value) && ctype_digit($value))) && (int)$value > 0
            ? (int)$value
            : self::MAX_JSON_BODY;
    }

    private static function tooLarge(): never
    {
        http_response_code(413);
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
        }
        echo json_encode(['message' => 'Request body too large', 'devcode' => 'VNL_BODY_TOO_LARGE']);

        exit;
    }
}

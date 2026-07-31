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

    /**
     * Gövdeyi okur.
     *
     * Form gönderimi zaten $_POST'u doldurur. Boşsa gövde JSON olarak
     * çözülmeye çalışılır; çözülemezse boş dizi döner — 2.x burada
     * $_POST'u null yapıyordu.
     *
     * JSON'daki tipler korunur: 0 sıfır kalır, false false kalır,
     * iç içe nesneler dizi olarak gelir.
     */
    private function readBody(): array
    {
        if (!empty($_POST) && is_array($_POST)) {
            return $_POST;
        }

        $body = @file_get_contents('php://input');
        if ($body === false || trim($body) === '') {
            return [];
        }

        $decoded = json_decode($body, true);

        return is_array($decoded) ? $decoded : [];
    }
}

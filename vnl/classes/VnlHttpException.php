<?php
/*!
 * ============================================================
 *  Voxnup Not Laravel (VNL) — HTTP Hatası
 *  Durum kodunu ve geliştirici kodunu taşıyan istisna.
 *  Authors: Voxnup Authors
 * ============================================================
 */

/**
 * throwErrorMessage()'ın fırlattığı istisna.
 *
 * Exception'dan türer: `catch (Exception $e)` yazan eski kod aynen yakalar.
 * Dağıtıcı (VnlDispatcher) bu sınıfı tanır ve yanıtı istisnanın kendi
 * durum koduyla ve mesajıyla yazar; başka bir istisna türü "beklenmeyen
 * hata" sayılır ve mesajı kullanıcıya gösterilmez (SQL metni sızmasın).
 *
 * 3.4.1: gövdeye ek alan ve yanıta başlık taşıyabilir.
 *
 *   throw new VnlHttpException('Form hatalı.', 422, 'VALIDATION', null,
 *       ['errors' => [['field' => 'name', 'message' => 'Zorunlu.']]]);
 *   // → 422 {"message":"Form hatalı.","devcode":"VALIDATION","errors":[…]}
 *
 *   throw new VnlHttpException('Meşgul.', 503, 'BUSY', null, [], ['Retry-After' => 5]);
 *   // → 503, Retry-After: 5
 *
 * Projenin kendi istisna sınıfları extra() / headers() metotlarını ezebilir;
 * ikisi de yanıt yazılırken çağrılır. Ek alanlar `message` ve `devcode`'u
 * ezemez; Content-Type her zaman JSON'dur.
 */
class VnlHttpException extends Exception
{
    private string $devcode;

    /** @var array<string, mixed> */
    private array $extra;

    /** @var array<string, string|int|float|list<string|int|float>> */
    private array $headers;

    public function __construct(
        string $message,
        int $status = 400,
        string $devcode = '',
        ?Throwable $previous = null,
        array $extra = [],
        array $headers = []
    ) {
        parent::__construct($message, $status, $previous);

        $this->devcode = $devcode;
        $this->extra   = $extra;
        $this->headers = $headers;
    }

    /** HTTP durum kodu (400–599). */
    public function status(): int
    {
        $code = (int)$this->getCode();

        return ($code >= 400 && $code <= 599) ? $code : 400;
    }

    /** İstemcinin makineyle okuyacağı kısa kod: VNL_AUTH, VNL_PERM ... */
    public function devcode(): string
    {
        return $this->devcode;
    }

    /**
     * JSON gövdesine eklenecek alanlar (3.4.1): ['errors' => [...], 'limit' => [...]].
     * `message` ve `devcode` anahtarları yok sayılır.
     *
     * @return array<string, mixed>
     */
    public function extra(): array
    {
        return $this->extra;
    }

    /**
     * Gövdeden önce gönderilecek başlıklar (3.4.1): ['Retry-After' => 5].
     * Değer bir listeyse aynı başlık her öğe için ayrı satır olarak gider.
     *
     * @return array<string, string|int|float|list<string|int|float>>
     */
    public function headers(): array
    {
        return $this->headers;
    }
}

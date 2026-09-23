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
 */
class VnlHttpException extends Exception
{
    private string $devcode;

    public function __construct(string $message, int $status = 400, string $devcode = '', ?Throwable $previous = null)
    {
        parent::__construct($message, $status, $previous);

        $this->devcode = $devcode;
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
}

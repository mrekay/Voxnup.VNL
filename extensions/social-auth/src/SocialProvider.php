<?php

/**
 * Yönlendirmeli (popup / tam sayfa) sosyal giriş sağlayıcısı.
 *
 * Akış: /oauth/{id} → authorizeUrl() → sağlayıcı → /oauth/{id}/callback → callback().
 * callback() normalleştirilmiş profil döner (bkz. SocialAuth::profile) ya da
 * SocialAuthException fırlatır.
 */
interface SocialProvider
{
    public function id(): string;

    /** Gerekli anahtarlar tanımlı mı? Değilse düğme çıkmaz, uç 404 döner. */
    public function enabled(): bool;

    public function authorizeUrl(string $state, string $nonce, string $redirectUri): string;

    /**
     * @param array $input  GET + POST (sağlayıcının döndüğü alanlar)
     * @param array $state  /oauth/{id}'de kurulan durum (n: nonce)
     */
    public function callback(array $input, array $state, string $redirectUri): array;
}

final class SocialAuthException extends RuntimeException
{
    public bool $cancelled;
    public int $status;

    public function __construct(string $reason, int $status = 400, bool $cancelled = false)
    {
        parent::__construct($reason);
        $this->status    = $status;
        $this->cancelled = $cancelled;
    }

    /** Hook sonucu biçiminde hata (istemciye giden). */
    public function result(): array
    {
        if ($this->cancelled) {
            return ['ok' => false, 'status' => 400, 'cancelled' => true, 'message' => '', 'data' => []];
        }
        $key = $this->status === 429 ? 'TOO_MANY_ATTEMPTS' : 'SOCIAL_AUTH_FAILED';
        $fallback = $this->status === 429 ? 'Too many attempts. Please try again later.' : 'Sign-in failed. Please try again.';
        return ['ok' => false, 'status' => $this->status, 'message' => SocialAuth::t($key, $fallback), 'data' => []];
    }
}

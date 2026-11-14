<?php

/**
 * Google — Google Identity Services düğmesi (popup, id_token). Yönlendirme akışı
 * kullanılmaz: GIS kendi popup'ını açar ve jetonu sayfaya verir, sayfa
 * POST /app/social/google'a yollar. Nonce oturumda (SameSite sorunu yok: aynı site).
 */
final class GoogleProvider
{
    public const ISSUERS  = ['accounts.google.com', 'https://accounts.google.com'];
    public const JWKS_URI = 'https://www.googleapis.com/oauth2/v3/certs';

    private const NONCE_KEY = '_vnl_google_nonce';

    public function id(): string
    {
        return 'google';
    }

    public function clientIds(): array
    {
        $cfg = SocialAuth::config()['providers']['google'] ?? [];
        return array_values(array_filter(array_map('trim', (array)($cfg['client_ids'] ?? [])), 'strlen'));
    }

    public function enabled(): bool
    {
        return !empty($this->clientIds());
    }

    /** Oturumun bekleyen nonce'u; yoksa üretilir. */
    public function nonce(): string
    {
        $nonce = (string)(Session::get(self::NONCE_KEY) ?? '');
        if ($nonce === '') {
            $nonce = bin2hex(random_bytes(16));
            Session::set(self::NONCE_KEY, $nonce);
        }
        return $nonce;
    }

    /** Jetonu doğrular, nonce'u tüketir. Normalleştirilmiş profil ya da hata. */
    public function verifyCredential(string $credential): array
    {
        $expected = (string)(Session::get(self::NONCE_KEY) ?? '');
        Session::delete(self::NONCE_KEY);
        if ($expected === '' || $credential === '') {
            throw new SocialAuthException('google:nonce', 401);
        }

        $c = OidcVerifier::verify($credential, [
            'jwks_uri'  => self::JWKS_URI,
            'issuers'   => self::ISSUERS,
            'audience'  => $this->clientIds(),
            'nonce'     => $expected,
            'cache_dir' => SocialAuth::config()['cache_dir'] ?? null,
        ]);
        if ($c === null || empty($c['sub'])) {
            throw new SocialAuthException('google:' . OidcVerifier::lastError(), 401);
        }

        return SocialAuth::profile('google', (string)$c['sub'], [
            'email'          => $c['email'] ?? null,
            'email_verified' => ($c['email_verified'] ?? false) === true,
            'name'           => $c['name'] ?? ($c['given_name'] ?? ''),
            'picture'        => $c['picture'] ?? null,
        ]);
    }
}

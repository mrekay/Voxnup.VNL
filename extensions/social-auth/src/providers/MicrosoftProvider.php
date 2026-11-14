<?php

/**
 * Microsoft (kişisel + iş/okul hesapları) — Microsoft identity platform v2,
 * response_type=id_token, response_mode=form_post. Gizli anahtar gerekmez; uygulama
 * kaydında "ID tokens" (implicit) işaretli olmalı.
 *
 * E-POSTAYA GÜVENME (nOAuth): iş hesabında "email" kiracı yöneticisinin elle yazdığı,
 * doğrulanmamış bir alan olabilir. E-posta yalnız şu durumda doğrulanmış sayılır:
 *   - kişisel Microsoft hesabı (tid = 9188040d-...), ya da
 *   - xms_edov === true (alan adı sahibi doğrulanmış; uygulama kaydında isteğe bağlı
 *     claim olarak "xms_edov" eklenmeli).
 * Aksi halde e-posta profile hiç girmez (hesap eşleştirmede kullanılamaz).
 */
final class MicrosoftProvider implements SocialProvider
{
    public const CONSUMER_TENANT = '9188040d-6c67-4c5b-b812-170bac2c2c8e';

    public function id(): string
    {
        return 'microsoft';
    }

    private function cfg(): array
    {
        return SocialAuth::config()['providers']['microsoft'] ?? [];
    }

    private function clientId(): string
    {
        return trim((string)($this->cfg()['client_id'] ?? ''));
    }

    private function tenant(): string
    {
        $t = (string)($this->cfg()['tenant'] ?? 'common');
        return preg_match('/^[A-Za-z0-9.-]+$/', $t) ? $t : 'common';
    }

    public function enabled(): bool
    {
        return $this->clientId() !== '';
    }

    public function authorizeUrl(string $state, string $nonce, string $redirectUri): string
    {
        return 'https://login.microsoftonline.com/' . $this->tenant() . '/oauth2/v2.0/authorize?' . http_build_query([
            'client_id'     => $this->clientId(),
            'response_type' => 'id_token',
            'redirect_uri'  => $redirectUri,
            'response_mode' => 'form_post',
            'scope'         => 'openid profile email',
            'state'         => $state,
            'nonce'         => $nonce,
            'prompt'        => 'select_account',
        ], '', '&', PHP_QUERY_RFC3986);
    }

    public function callback(array $input, array $state, string $redirectUri): array
    {
        if (!empty($input['error'])) {
            throw new SocialAuthException('microsoft:' . $input['error'], 400, $input['error'] === 'access_denied');
        }

        $token = (string)($input['id_token'] ?? '');
        // iss kiracıya göre değişir: tid önce okunur, verify'da beklenen iss olarak sınanır.
        $tid = (string)(OidcVerifier::peek($token)['tid'] ?? '');
        if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/', $tid)) {
            throw new SocialAuthException('microsoft:tid', 401);
        }

        $c = OidcVerifier::verify($token, [
            'jwks_uri'  => 'https://login.microsoftonline.com/' . $this->tenant() . '/discovery/v2.0/keys',
            'issuers'   => ['https://login.microsoftonline.com/' . $tid . '/v2.0'],
            'audience'  => [$this->clientId()],
            'nonce'     => (string)($state['n'] ?? ''),
            'cache_dir' => SocialAuth::config()['cache_dir'] ?? null,
        ]);
        if ($c === null || empty($c['sub'])) {
            throw new SocialAuthException('microsoft:' . OidcVerifier::lastError(), 401);
        }

        $edov = $c['xms_edov'] ?? null;
        $verified = $tid === self::CONSUMER_TENANT || $edov === true || $edov === 1 || $edov === '1' || $edov === 'true';

        return SocialAuth::profile('microsoft', (string)$c['sub'], [
            'email'          => $c['email'] ?? null,
            'email_verified' => $verified,
            'name'           => $c['name'] ?? '',
            'picture'        => null,
        ]);
    }
}

<?php

/**
 * Sign in with Apple — web, response_mode=form_post.
 *
 * Apple dönüşü siteler arası POST'tur: oturum çerezi (SameSite=Lax) gelmez, durum
 * SocialAuth'un SameSite=None çerezinde taşınır. id_token Apple'ın JWKS'iyle doğrulanır;
 * kod takası (client_secret JWT'si) gerekmez.
 *
 * Ad yalnız İLK yetkilendirmede, imzasız "user" alanında gelir (jetonda yoktur).
 * E-posta "Hide My Email" ise @privaterelay.appleid.com adresidir: o adrese mail
 * gidebilmesi için Apple Developer'da gönderen alan adı kayıtlı olmalı.
 */
final class AppleProvider implements SocialProvider
{
    private const AUTHORIZE = 'https://appleid.apple.com/auth/authorize';
    private const JWKS_URI  = 'https://appleid.apple.com/auth/keys';
    private const ISSUER    = 'https://appleid.apple.com';

    public function id(): string
    {
        return 'apple';
    }

    private function clientIds(): array
    {
        $cfg = SocialAuth::config()['providers']['apple'] ?? [];
        return array_values(array_filter(array_map('trim', (array)($cfg['client_ids'] ?? [])), 'strlen'));
    }

    public function enabled(): bool
    {
        return !empty($this->clientIds());
    }

    public function authorizeUrl(string $state, string $nonce, string $redirectUri): string
    {
        return self::AUTHORIZE . '?' . http_build_query([
            'client_id'     => $this->clientIds()[0],
            'redirect_uri'  => $redirectUri,
            'response_type' => 'code id_token',
            'response_mode' => 'form_post',
            'scope'         => 'name email',
            'state'         => $state,
            'nonce'         => $nonce,
        ], '', '&', PHP_QUERY_RFC3986);
    }

    public function callback(array $input, array $state, string $redirectUri): array
    {
        if (!empty($input['error'])) {
            throw new SocialAuthException('apple:' . $input['error'], 400, $input['error'] === 'user_cancelled_authorize');
        }

        $nonce = (string)($state['n'] ?? '');
        $c = OidcVerifier::verify((string)($input['id_token'] ?? ''), [
            'jwks_uri'  => self::JWKS_URI,
            'issuers'   => [self::ISSUER],
            'audience'  => $this->clientIds(),
            'nonce'     => [$nonce, hash('sha256', $nonce)],
            'cache_dir' => SocialAuth::config()['cache_dir'] ?? null,
        ]);
        if ($c === null || empty($c['sub'])) {
            throw new SocialAuthException('apple:' . OidcVerifier::lastError(), 401);
        }

        $user = json_decode((string)($input['user'] ?? ''), true);
        $name = '';
        if (is_array($user['name'] ?? null)) {
            $name = trim(($user['name']['firstName'] ?? '') . ' ' . ($user['name']['lastName'] ?? ''));
        }

        $verified = $c['email_verified'] ?? false;
        return SocialAuth::profile('apple', (string)$c['sub'], [
            'email'          => $c['email'] ?? null,
            'email_verified' => $verified === true || $verified === 'true',
            'name'           => $name,
            'picture'        => null,
        ]);
    }
}

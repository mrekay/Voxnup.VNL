<?php

/**
 * Facebook (Meta) Login — authorization code akışı. Facebook web'de OIDC id_token
 * vermez: kod sunucuda App Secret'la erişim jetonuna çevrilir, profil Graph API'den
 * (appsecret_proof ile) okunur.
 *
 * E-posta: Graph yalnız doğrulanmış birincil adresi döner; kullanıcı izin vermezse ya da
 * hesabı telefonla açılmışsa hiç gelmez. "email" izni canlıda Advanced Access ister
 * (Meta Business Verification).
 *
 * Meta veri silme şartı: /oauth/facebook/delete (signed_request) → deletion().
 */
final class FacebookProvider implements SocialProvider
{
    public function id(): string
    {
        return 'facebook';
    }

    private function cfg(): array
    {
        return SocialAuth::config()['providers']['facebook'] ?? [];
    }

    private function appId(): string
    {
        return trim((string)($this->cfg()['app_id'] ?? ''));
    }

    private function secret(): string
    {
        return trim((string)($this->cfg()['app_secret'] ?? ''));
    }

    private function version(): string
    {
        $v = (string)($this->cfg()['graph_version'] ?? 'v26.0');
        return preg_match('/^v\d+\.\d+$/', $v) ? $v : 'v26.0';
    }

    public function enabled(): bool
    {
        return $this->appId() !== '' && $this->secret() !== '';
    }

    public function authorizeUrl(string $state, string $nonce, string $redirectUri): string
    {
        return 'https://www.facebook.com/' . $this->version() . '/dialog/oauth?' . http_build_query([
            'client_id'     => $this->appId(),
            'redirect_uri'  => $redirectUri,
            'state'         => $state,
            'response_type' => 'code',
            'scope'         => 'email,public_profile',
        ], '', '&', PHP_QUERY_RFC3986);
    }

    public function callback(array $input, array $state, string $redirectUri): array
    {
        if (!empty($input['error'])) {
            throw new SocialAuthException('facebook:' . $input['error'], 400, $input['error'] === 'access_denied');
        }
        $code = (string)($input['code'] ?? '');
        if ($code === '') {
            throw new SocialAuthException('facebook:code', 400);
        }

        $graph = 'https://graph.facebook.com/' . $this->version();
        $tok = SocialAuth::httpJson($graph . '/oauth/access_token?' . http_build_query([
            'client_id'     => $this->appId(),
            'client_secret' => $this->secret(),
            'redirect_uri'  => $redirectUri,
            'code'          => $code,
        ]));
        $access = (string)($tok['access_token'] ?? '');
        if ($access === '') {
            throw new SocialAuthException('facebook:token', 401);
        }

        $me = SocialAuth::httpJson($graph . '/me?' . http_build_query([
            'fields'          => 'id,name,email,picture.width(256).height(256)',
            'access_token'    => $access,
            'appsecret_proof' => hash_hmac('sha256', $access, $this->secret()),
        ]));
        if (empty($me['id'])) {
            throw new SocialAuthException('facebook:me', 401);
        }

        return SocialAuth::profile('facebook', (string)$me['id'], [
            'email'          => $me['email'] ?? null,
            'email_verified' => !empty($me['email']),
            'name'           => $me['name'] ?? '',
            'picture'        => $me['picture']['data']['url'] ?? null,
        ]);
    }

    /**
     * Meta'nın veri silme geri çağrısı. signed_request App Secret'la (HMAC-SHA256)
     * doğrulanır; projenin vnl_social_forget('facebook', $id) kancası çağrılır.
     * Yanıt Meta'nın beklediği JSON: {url, confirmation_code}.
     */
    public function deletion(string $signedRequest): ?array
    {
        $parts = explode('.', $signedRequest, 2);
        if (count($parts) !== 2 || $this->secret() === '') {
            return null;
        }
        [$sig64, $payload64] = $parts;
        $sig  = OidcVerifier::b64url($sig64);
        $data = json_decode((string)OidcVerifier::b64url($payload64), true);
        if ($sig === null || !is_array($data) || strtoupper((string)($data['algorithm'] ?? '')) !== 'HMAC-SHA256') {
            return null;
        }
        if (!hash_equals(hash_hmac('sha256', $payload64, $this->secret(), true), $sig)) {
            return null;
        }
        $userId = (string)($data['user_id'] ?? '');
        if ($userId === '') {
            return null;
        }

        if (function_exists('vnl_social_forget')) {
            vnl_social_forget('facebook', $userId);
        }

        $code = bin2hex(random_bytes(8));
        return [
            'url'               => SocialAuth::baseUrl() . (string)($this->cfg()['deletion_status_path'] ?? '/privacy'),
            'confirmation_code' => $code,
        ];
    }
}

<?php

/**
 * POST /app/social/google  — credential: Google Identity Services'in id_token'ı
 */
class action_social
{
    /* Giriş sayfasından, oturum açılmadan çağrılır (VnlDispatcher varsayılanı:
       giriş zorunlu). CSRF denetimi açık kalır: istemci X-CSRF-Token gönderir. */
    public const PUBLIC_METHODS = ['google'];

    public function google($req)
    {
        $google = SocialAuth::provider('google');
        if (!$google->enabled()) {
            returnErrorMessage(SocialAuth::t('SOCIAL_AUTH_FAILED', 'Sign-in failed.'), 404);
        }

        try {
            if (!SocialAuth::limit()) {
                throw new SocialAuthException('google:rate', 429);
            }
            $result = SocialAuth::finish($google->verifyCredential((string)$req->post('credential')));
        } catch (SocialAuthException $e) {
            error_log('social-auth: ' . $e->getMessage());
            $result = $e->result();
        }

        if ($result['ok']) {
            returnSuccessMessage($result['message'], $result['data']);
            return;
        }
        returnErrorMessage($result['message'], $result['status']);
    }
}

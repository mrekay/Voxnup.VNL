<?php

/**
 * Yönlendirmeli sosyal giriş uçları (Apple, Microsoft, Facebook).
 *
 *   GET       /oauth/{id}?ret=/login[&popup=1]   durumu kur, sağlayıcıya yönlendir
 *   GET|POST  /oauth/{id}/callback                sağlayıcının dönüşü (Apple/Microsoft: form_post)
 *   POST      /oauth/facebook/delete              Meta veri silme geri çağrısı (signed_request)
 *
 * Giriş gerektirmez (boot.php: vnl_public_page('oauth')).
 */

$vnl_oauth_id     = (string)route(1);
$vnl_oauth_action = (string)(route(2) ?: 'start');
$vnl_oauth        = in_array($vnl_oauth_id, ['apple', 'microsoft', 'facebook'], true) ? SocialAuth::provider($vnl_oauth_id) : null;

if ($vnl_oauth === null || !$vnl_oauth->enabled()) {
    http_response_code(404);
    exit;
}

switch ($vnl_oauth_action) {
    case 'start':
        [$vnl_state, $vnl_nonce] = SocialAuth::beginState(
            $vnl_oauth_id,
            !empty($_GET['popup']),
            SocialAuth::safeReturn((string)($_GET['ret'] ?? ''))
        );
        header('Location: ' . $vnl_oauth->authorizeUrl($vnl_state, $vnl_nonce, SocialAuth::redirectUri($vnl_oauth_id)));
        exit;

    case 'callback':
        $vnl_input = array_merge($_GET, $_POST);
        $vnl_state = SocialAuth::takeState($vnl_oauth_id);
        try {
            if ($vnl_state === null || !hash_equals((string)$vnl_state['s'], (string)($vnl_input['state'] ?? ''))) {
                // Kullanıcı iptal ettiyse sağlayıcı yine de state döner; durum yoksa sessizce kapat.
                throw new SocialAuthException($vnl_oauth_id . ':state', 400, !empty($vnl_input['error']));
            }
            if (!SocialAuth::limit()) {
                throw new SocialAuthException($vnl_oauth_id . ':rate', 429);
            }
            $vnl_result = SocialAuth::finish(
                $vnl_oauth->callback($vnl_input, $vnl_state, SocialAuth::redirectUri($vnl_oauth_id))
            );
        } catch (SocialAuthException $e) {
            if (!$e->cancelled) error_log('social-auth: ' . $e->getMessage());
            $vnl_result = $e->result();
        }
        SocialAuth::deliver($vnl_result, $vnl_state);
        exit;

    case 'delete':
        header('Content-Type: application/json; charset=utf-8');
        $vnl_del = $vnl_oauth instanceof FacebookProvider
            ? $vnl_oauth->deletion((string)($_POST['signed_request'] ?? ''))
            : null;
        if ($vnl_del === null) {
            http_response_code(400);
            echo json_encode(['error' => 'invalid signed_request']);
            exit;
        }
        echo json_encode($vnl_del);
        exit;
}

http_response_code(404);
exit;

<?php

/**
 * Sosyal giriş: Google, Apple, Microsoft, Facebook.
 *
 *  - Google: GIS düğmesi → POST /app/social/google (id_token).
 *  - Diğerleri: /oauth/{id} (popup ya da tam sayfa) → sağlayıcı → /oauth/{id}/callback.
 *    Popup sonucu açan sayfaya BroadcastChannel + localStorage + postMessage ile iletir
 *    (Apple/Microsoft sayfaları COOP ile window.opener'ı koparabilir). Popup
 *    açılamazsa tam sayfa yönlendirme: sonuç oturumda bir kez okunur (takeFlash).
 *
 * Doğrulanan profil projenin kancasına gider:
 *
 *   function vnl_social_sign_in(array $profile): array
 *     $profile = [provider, sub, email (yalnız DOĞRULANMIŞSA, yoksa null), name, picture]
 *     dönüş    = [ok => bool, status => int, message => string, data => array]
 *
 * Sayfa sonucu document üzerindeki "vnl:social-auth" olayıyla alır
 * (detail: {ok, status, message, data, cancelled?}).
 */
final class SocialAuth
{
    public const STATE_COOKIE = '__vnl_oauth';
    public const CHANNEL      = 'vnl-social-auth';
    private const FLASH_KEY   = '_vnl_social_flash';
    private const ORDER       = ['google', 'apple', 'microsoft', 'facebook'];

    private static array $instances = [];

    public static function config(): array
    {
        return $GLOBALS['vnl_social_auth_config'] ?? [];
    }

    /** @return GoogleProvider|SocialProvider|null */
    public static function provider(string $id)
    {
        if (!isset(self::$instances[$id])) {
            self::$instances[$id] = match ($id) {
                'google'    => new GoogleProvider(),
                'apple'     => new AppleProvider(),
                'microsoft' => new MicrosoftProvider(),
                'facebook'  => new FacebookProvider(),
                default     => null,
            };
        }
        return self::$instances[$id];
    }

    /** @return string[] Etkin sağlayıcılar, gösterim sırasıyla. */
    public static function enabledProviders(): array
    {
        return array_values(array_filter(self::ORDER, fn($id) => self::provider($id)->enabled()));
    }

    /**
     * Bu istemcide düğmeler gösterilmeli mi? Sağlayıcılar gömülü WebView'lerde
     * (uygulama kabukları, Instagram/Facebook tarayıcısı) girişi reddeder.
     */
    public static function available(?string $userAgent = null): bool
    {
        if (empty(self::enabledProviders())) return false;
        $ua = $userAgent ?? (string)($_SERVER['HTTP_USER_AGENT'] ?? '');
        foreach ((array)(self::config()['blocked_user_agents'] ?? []) as $pattern) {
            if (@preg_match($pattern, $ua) === 1) return false;
        }
        return true;
    }

    /** Sitenin kök adresi (geri dönüş adresleri için). config base_url, yoksa istek. */
    public static function baseUrl(): string
    {
        $base = self::config()['base_url'] ?? '';
        if (is_callable($base)) $base = $base();
        if (is_string($base) && $base !== '') return rtrim($base, '/');

        $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || strtolower((string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https';
        return ($https ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
    }

    public static function redirectUri(string $id): string
    {
        return self::baseUrl() . '/oauth/' . $id . '/callback';
    }

    /**
     * Yalnız göreli, site içi dönüş yolu ("/panel?ref=x"); aksi halde varsayılan.
     *
     * 3.5.2: Çekirdeğin vnl_safe_return_url()'ine devredildi. Eski denetim
     * yalnız \\, CR ve LF'yi reddediyordu; "/\t/evil.com" (ret=/%09/evil.com)
     * geçiyordu ve tarayıcı sekmeyi atıp //evil.com'a gidiyordu.
     */
    public static function safeReturn(?string $ret): string
    {
        $default = (string)(self::config()['default_return'] ?? '/');

        if (function_exists('vnl_safe_return_url')) {
            return vnl_safe_return_url((string)$ret) ?? $default;
        }

        $ret = (string)$ret;
        if ($ret === '' || $ret[0] !== '/' || ($ret[1] ?? '') === '/' || str_contains($ret, '\\')
            || preg_match('/[\x00-\x20\x7F]/', $ret)) {
            return $default;
        }
        return $ret;
    }

    /* ------------------------------------------------------------------ *
     *  Yönlendirme durumu (SameSite=None çerezi; dönüş siteler arası POST)
     * ------------------------------------------------------------------ */

    /** @return array{0:string,1:string} [state, nonce] */
    public static function beginState(string $provider, bool $popup, string $ret): array
    {
        $state = bin2hex(random_bytes(16));
        $nonce = bin2hex(random_bytes(16));
        $value = rtrim(strtr(base64_encode(json_encode([
            'p' => $provider, 's' => $state, 'n' => $nonce, 'pop' => $popup, 'ret' => $ret, 't' => time(),
        ])), '+/', '-_'), '=');
        self::stateCookie($value, time() + 600);
        return [$state, $nonce];
    }

    /** Durumu okur ve siler. Sağlayıcı tutmuyor ya da 10 dk geçmişse null. */
    public static function takeState(string $provider): ?array
    {
        $raw = (string)($_COOKIE[self::STATE_COOKIE] ?? '');
        self::stateCookie('', time() - 3600);
        if ($raw === '') return null;
        $data = json_decode((string)OidcVerifier::b64url($raw), true);
        if (!is_array($data) || ($data['p'] ?? '') !== $provider || (time() - (int)($data['t'] ?? 0)) > 600) {
            return null;
        }
        return $data;
    }

    private static function stateCookie(string $value, int $expires): void
    {
        setcookie(self::STATE_COOKIE, $value, [
            'expires'  => $expires,
            'path'     => '/oauth/',
            'secure'   => true,       // SameSite=None şartı; localhost'ta tarayıcılar kabul eder
            'httponly' => true,
            'samesite' => 'None',
        ]);
    }

    /* ------------------------------------------------------------------ *
     *  Sonuç
     * ------------------------------------------------------------------ */

    /** Sağlayıcıların ortak profil biçimi. Doğrulanmamış e-posta profile GİRMEZ. */
    public static function profile(string $provider, string $sub, array $f): array
    {
        $email = trim((string)($f['email'] ?? ''));
        $verified = !empty($f['email_verified']) && $email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL);
        return [
            'provider' => $provider,
            'sub'      => $sub,
            'email'    => $verified ? $email : null,
            'name'     => trim((string)($f['name'] ?? '')),
            'picture'  => $f['picture'] ?? null,
        ];
    }

    /** Profili projenin kancasına verir; sonucu normalleştirir. */
    public static function finish(array $profile): array
    {
        if (!function_exists('vnl_social_sign_in')) {
            error_log('social-auth: vnl_social_sign_in() kancası tanımlı değil');
            return ['ok' => false, 'status' => 500, 'message' => self::t('SOCIAL_AUTH_FAILED', 'Sign-in failed.'), 'data' => []];
        }
        $r = (array)vnl_social_sign_in($profile);
        return [
            'ok'      => !empty($r['ok']),
            'status'  => (int)($r['status'] ?? (!empty($r['ok']) ? 200 : 400)),
            'message' => (string)($r['message'] ?? ''),
            'data'    => (array)($r['data'] ?? []),
        ];
    }

    /** Hız sınırı (config rate_limit callable, yoksa VNL RateLimiter). */
    public static function limit(): bool
    {
        $ip = (string)UserModule::CurrentIp();
        $limiter = self::config()['rate_limit'] ?? null;
        return is_callable($limiter)
            ? (bool)$limiter($ip)
            : RateLimiter::hit('social-auth:ip:' . RateLimiter::ipKey($ip), 30, 600);
    }

    /** Callback sonucunu teslim eder: popup sayfası ya da oturum + yönlendirme. */
    public static function deliver(array $result, ?array $state): void
    {
        $ret = self::safeReturn($state['ret'] ?? null);

        if ($state !== null && empty($state['pop'])) {
            Session::set(self::FLASH_KEY, $result);
            header('Location: ' . $ret);
            return;
        }

        $msg = json_encode(['type' => self::CHANNEL, 'id' => bin2hex(random_bytes(8)), 'result' => $result],
            JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
        $close = htmlspecialchars(self::t('SOCIAL_AUTH_CLOSE_WINDOW', 'You can close this window.'), ENT_QUOTES);
        $back  = htmlspecialchars($ret, ENT_QUOTES);
        // Popup kapanamazsa (opener kopmuş) kullanıcı bağlantıyla devam eder.
        header('Content-Type: text/html; charset=utf-8');
        echo '<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width">'
            . '<meta name="robots" content="noindex"><title>…</title></head>'
            . '<body style="font:15px system-ui,sans-serif;padding:32px;text-align:center;color:#555">'
            . '<p>' . $close . '</p><p><a href="' . $back . '">' . $back . '</a></p><script>(function(){'
            . 'var m=' . $msg . ';'
            . 'try{if(window.opener&&!window.opener.closed)window.opener.postMessage(m,location.origin);}catch(e){}'
            . 'try{var b=new BroadcastChannel(' . json_encode(self::CHANNEL) . ');b.postMessage(m);b.close();}catch(e){}'
            . 'try{localStorage.setItem(' . json_encode(self::CHANNEL) . ',JSON.stringify(m));}catch(e){}'
            . 'setTimeout(function(){window.close();},80);'
            . '})();</script></body></html>';
    }

    /** Tam sayfa yönlendirmede bekleyen sonuç (bir kez). */
    public static function takeFlash(): ?array
    {
        $r = Session::get(self::FLASH_KEY);
        if ($r === null) return null;
        Session::delete(self::FLASH_KEY);
        return is_array($r) ? $r : null;
    }

    public static function t(string $key, string $fallback): string
    {
        return (class_exists('Language') && Language::has($key)) ? lang($key) : $fallback;
    }

    /** HTTPS GET, JSON yanıt. */
    public static function httpJson(string $url): ?array
    {
        if (!function_exists('curl_init')) return null;
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 8,
            CURLOPT_CONNECTTIMEOUT => 4,
            CURLOPT_PROTOCOLS      => CURLPROTO_HTTPS,
            CURLOPT_HTTPHEADER     => ['Accept: application/json'],
        ]);
        $body = curl_exec($ch);
        $json = is_string($body) ? json_decode($body, true) : null;
        return is_array($json) ? $json : null;
    }

    /* ------------------------------------------------------------------ *
     *  Düğmeler
     * ------------------------------------------------------------------ */

    /**
     * Etkin sağlayıcıların düğmeleri. Seçenekler:
     *   context  'signin' | 'signup'   (Google penceresindeki metin)
     *   locale   'tr', 'en' ...
     *   ret      dönüş yolu (yönlendirme modunda), varsayılan şu anki adres
     *   labels   ['apple' => 'Apple', ...]   (düğme metni)
     *   aria     '%s ile devam et'          (ekran okuyucu; %s = sağlayıcı adı)
     *   theme    'auto' (varsayılan: sayfanın color-scheme'ine göre) | 'light' | 'dark'
     */
    public static function buttons(array $opts = []): string
    {
        if (!self::available()) return '';

        $labels = array_merge(['apple' => 'Apple', 'microsoft' => 'Microsoft', 'facebook' => 'Facebook'], (array)($opts['labels'] ?? []));
        $aria   = (string)($opts['aria'] ?? 'Continue with %s');
        $ret    = self::safeReturn((string)($opts['ret'] ?? ($_SERVER['REQUEST_URI'] ?? '/')));
        $out    = '<div class="vnl-social">';

        $google = self::provider('google');
        if ($google->enabled()) {
            $cfg = [
                'clientId' => $google->clientIds()[0],
                'nonce'    => $google->nonce(),
                'context'  => (string)($opts['context'] ?? 'signin'),
                'locale'   => (string)($opts['locale'] ?? ''),
                'theme'    => (string)($opts['theme'] ?? 'auto'),
            ];
            $out .= '<div class="vnl-gauth" data-vnl-gauth="' . htmlspecialchars(json_encode($cfg), ENT_QUOTES) . '"><div class="vnl-gauth-btn"></div></div>';
        }

        $row = '';
        foreach (['apple', 'microsoft', 'facebook'] as $id) {
            if (!self::provider($id)->enabled()) continue;
            $label = (string)$labels[$id];
            $row .= '<a class="vnl-social-btn is-' . $id . '" href="/oauth/' . $id . '?ret=' . rawurlencode($ret) . '"'
                . ' data-vnl-social-provider="' . $id . '" aria-label="' . htmlspecialchars(sprintf($aria, $label), ENT_QUOTES) . '">'
                . self::logo($id) . '<span>' . htmlspecialchars($label, ENT_QUOTES) . '</span></a>';
        }
        if ($row !== '') {
            $out .= '<div class="vnl-social-row">' . $row . '</div>';
        }
        $out .= '</div>';

        static $assets = false;
        if (!$assets) {
            $assets = true;
            $out .= '<style>' . self::css() . '</style>';
            if ($google->enabled()) {
                $out .= '<script src="https://accounts.google.com/gsi/client" async defer></script>';
            }
            $out .= '<script>' . self::script() . '</script>';
        }
        return $out;
    }

    private static function logo(string $id): string
    {
        $svg = [
            'apple'     => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M19.665 16.811a10.316 10.316 0 0 1-1.021 1.837c-.537.767-.978 1.297-1.316 1.592-.525.482-1.089.73-1.692.744-.432 0-.954-.123-1.562-.373-.61-.249-1.17-.371-1.683-.371-.537 0-1.113.122-1.73.371-.616.25-1.114.381-1.495.393-.577.025-1.154-.229-1.729-.764-.367-.32-.826-.87-1.377-1.648-.59-.829-1.075-1.794-1.455-2.891-.407-1.187-.611-2.335-.611-3.447 0-1.273.275-2.372.826-3.292a4.857 4.857 0 0 1 1.73-1.751 4.65 4.65 0 0 1 2.34-.662c.46 0 1.063.142 1.81.422s1.227.422 1.436.422c.158 0 .689-.167 1.593-.498.853-.307 1.573-.434 2.163-.384 1.6.129 2.801.759 3.6 1.895-1.43.867-2.137 2.08-2.123 3.637.012 1.213.453 2.222 1.317 3.023a4.33 4.33 0 0 0 1.315.863c-.106.307-.218.6-.336.882zM15.998 2.38c0 .95-.348 1.838-1.039 2.659-.836.976-1.846 1.541-2.941 1.452a2.955 2.955 0 0 1-.021-.36c0-.913.396-1.889 1.103-2.688.352-.404.8-.741 1.343-1.009.542-.264 1.054-.41 1.536-.435.013.128.019.255.019.381z"/></svg>',
            'microsoft' => '<svg viewBox="0 0 24 24"><path fill="#F25022" d="M3 3h8.55v8.55H3z"/><path fill="#7FBA00" d="M12.45 3H21v8.55h-8.55z"/><path fill="#00A4EF" d="M3 12.45h8.55V21H3z"/><path fill="#FFB900" d="M12.45 12.45H21V21h-8.55z"/></svg>',
            'facebook'  => '<svg viewBox="0 0 24 24"><path fill="#0866FF" d="M12.001 2.002c-5.522 0-9.999 4.477-9.999 9.999 0 4.99 3.656 9.126 8.437 9.879v-6.988h-2.54v-2.891h2.54V9.798c0-2.508 1.493-3.891 3.776-3.891 1.094 0 2.24.195 2.24.195v2.459h-1.264c-1.24 0-1.628.772-1.628 1.563v1.875h2.771l-.443 2.891h-2.328v6.988C18.344 21.129 22 16.992 22 12.001c0-5.522-4.477-9.999-9.999-9.999z"/></svg>',
        ];
        return str_replace('<svg ', '<svg aria-hidden="true" width="18" height="18" ', $svg[$id] ?? '');
    }

    private static function css(): string
    {
        return '.vnl-social{display:grid;gap:10px}'
            . '.vnl-gauth{display:flex;justify-content:center;min-height:44px;transition:opacity .2s}'
            . '.vnl-gauth.is-busy{opacity:.5;pointer-events:none}'
            // GIS iframe'i açık şemada çizer; koyu (color-scheme: dark) sayfada şemalar uyuşmazsa
            // Chrome iframe arka planını opak beyaz boyar. Eşitlenince şeffaf kalır.
            . '.vnl-gauth iframe{color-scheme:light}'
            . '.vnl-social-row{display:grid;grid-auto-flow:column;grid-auto-columns:minmax(0,1fr);gap:8px}'
            . '.vnl-social-btn{display:flex;align-items:center;justify-content:center;gap:8px;height:40px;padding:0 10px;'
            . 'border:1px solid #dadce0;border-radius:20px;background:#fff;color:#1f1f1f;'
            . 'font:500 14px/1 "Google Sans",Roboto,system-ui,sans-serif;text-decoration:none;white-space:nowrap;overflow:hidden;'
            . 'transition:background-color .15s,border-color .15s}'
            . '.vnl-social-btn:hover{background:#f7f8f8;border-color:#d2e3fc}'
            . '.vnl-social-btn:focus-visible{outline:2px solid #4285f4;outline-offset:2px}'
            . '.vnl-social-btn svg{flex:none}'
            . '.vnl-social-btn span{overflow:hidden;text-overflow:ellipsis}'
            // Koyu şema: Google'ın koyu düğme renkleri (GIS filled_black ile aynı aile).
            . '.vnl-social[data-scheme="dark"] .vnl-social-btn{background:#131314;border-color:#8e918f;color:#e3e3e3}'
            . '.vnl-social[data-scheme="dark"] .vnl-social-btn:hover{background:#1f1f20;border-color:#a8aba9}'
            . '@media (max-width:380px){.vnl-social-btn span{display:none}}';
    }

    private static function script(): string
    {
        $channel = json_encode(self::CHANNEL);
        return <<<JS
(function () {
    'use strict';
    var CH = {$channel};
    var seen = {};
    function emit(name, detail) { document.dispatchEvent(new CustomEvent(name, { detail: detail || {} })); }
    // Sayfanın renk şeması: önce kökün color-scheme'i (temalı siteler), yoksa sistem tercihi.
    function scheme(pref) {
        if (pref === 'light' || pref === 'dark') return pref;
        var cs = '';
        try { cs = getComputedStyle(document.documentElement).colorScheme || ''; } catch (e) {}
        if (/dark/.test(cs) && !/light/.test(cs)) return 'dark';
        if (/light/.test(cs) && !/dark/.test(cs)) return 'light';
        return window.matchMedia && matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
    }
    document.querySelectorAll('.vnl-social').forEach(function (r) {
        var g = r.querySelector('[data-vnl-gauth]');
        r.dataset.scheme = scheme(g ? JSON.parse(g.dataset.vnlGauth).theme : 'auto');
    });
    function result(m) {
        if (!m || m.type !== CH || !m.id || seen[m.id]) return;
        seen[m.id] = 1;
        try { localStorage.removeItem(CH); } catch (e) {}
        emit('vnl:social-auth', m.result);
    }
    try { var bc = new BroadcastChannel(CH); bc.onmessage = function (e) { result(e.data); }; } catch (e) {}
    window.addEventListener('message', function (e) { if (e.origin === location.origin) result(e.data); });
    window.addEventListener('storage', function (e) {
        if (e.key === CH && e.newValue) { try { result(JSON.parse(e.newValue)); } catch (x) {} }
    });

    // Apple / Microsoft / Facebook: popup; açılamazsa tam sayfa.
    document.addEventListener('click', function (e) {
        var a = e.target.closest && e.target.closest('[data-vnl-social-provider]');
        if (!a) return;
        e.preventDefault();
        var url = a.getAttribute('href');
        var w = 520, h = 680;
        var x = Math.max(0, (window.screenX || 0) + ((window.outerWidth || w) - w) / 2);
        var y = Math.max(0, (window.screenY || 0) + ((window.outerHeight || h) - h) / 2);
        var win = null;
        try { win = window.open(url + '&popup=1', 'vnl-social', 'popup=yes,width=' + w + ',height=' + h + ',left=' + x + ',top=' + y); } catch (x2) {}
        if (!win) { location.href = url; return; }
        try { win.focus(); } catch (x3) {}
        emit('vnl:social-auth-open', { provider: a.dataset.vnlSocialProvider });
    });

    // Google Identity Services
    function initGoogle(root) {
        if (root.dataset.ready) return;
        root.dataset.ready = '1';
        var cfg = JSON.parse(root.dataset.vnlGauth);
        window.google.accounts.id.initialize({
            client_id: cfg.clientId, nonce: cfg.nonce, context: cfg.context,
            ux_mode: 'popup', itp_support: true, use_fedcm_for_button: true,
            callback: function (resp) {
                root.classList.add('is-busy');
                emit('vnl:social-auth-start', { provider: 'google' });
                var body = new FormData();
                body.append('credential', resp.credential || '');
                // VnlDispatcher CSRF ister: jeton <meta name="csrf-token">'de (vnl.js okuyucusu varsa o).
                var tokenEl = document.querySelector('meta[name="csrf-token"]');
                var token = window.vnlCsrfToken ? window.vnlCsrfToken() : (tokenEl ? tokenEl.getAttribute('content') : '');
                fetch('/app/social/google', {
                    method: 'POST', body: body, credentials: 'same-origin',
                    headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json', 'X-CSRF-Token': token || '' }
                }).then(function (res) {
                    return res.json().catch(function () { return null; }).then(function (j) {
                        root.classList.remove('is-busy');
                        emit('vnl:social-auth', { ok: res.ok, status: res.status, message: (j && j.message) || '', data: (j && j.data) || {} });
                    });
                }, function () {
                    root.classList.remove('is-busy');
                    emit('vnl:social-auth', { ok: false, status: 0, message: '', data: {} });
                });
            }
        });
        var width = Math.max(200, Math.min(400, Math.floor(root.getBoundingClientRect().width || 320)));
        window.google.accounts.id.renderButton(root.querySelector('.vnl-gauth-btn'), {
            type: 'standard', size: 'large', text: 'continue_with',
            theme: scheme(cfg.theme) === 'dark' ? 'filled_black' : 'outline',
            shape: 'pill', logo_alignment: 'center', width: width, locale: cfg.locale || undefined
        });
    }
    var tries = 0;
    (function wait() {
        var roots = document.querySelectorAll('[data-vnl-gauth]');
        if (!roots.length) return;
        if (window.google && window.google.accounts && window.google.accounts.id) { roots.forEach(initGoogle); return; }
        if (++tries > 80) { roots.forEach(function (r) { r.hidden = true; }); return; }
        setTimeout(wait, 125);
    })();
})();
JS;
    }
}

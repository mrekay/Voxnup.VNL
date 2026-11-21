<div class="d-flex flex-column flex-column-fluid">
    <div id="kt_app_content" class="app-content flex-column-fluid">
        <div id="kt_app_content_container" class="app-container container-xxl">

            <div class="vnl-page-hero mb-12 mt-6">
                <div class="vnl-overline mb-3">// docs/social-auth</div>
                <h1 class="fs-2hx fw-bold mb-4 text-body-emphasis">Social Auth Extension</h1>
                <div class="vnl-lead">Google, Apple, Microsoft ve Facebook ile giriş. Bağımlılık yok: yalnız
                    <code>openssl</code> ve <code>curl</code>; sağlayıcı SDK'sı yüklenmez. Eklenti kimliği doğrular,
                    hesabı ne yapacağınıza <b>proje</b> karar verir: doğrulanmış profil tek bir kancaya gelir.
                    Tüm dosyalar <code>extensions/social-auth/</code> altında; ayrıntılı sağlayıcı kurulumu
                    klasördeki <code>README.md</code>'de.</div>
            </div>

            <!--begin::Akışlar-->
            <div class="card mb-10">
                <div class="card-header border-0 pt-8">
                    <h2 class="card-title fw-bold text-body-emphasis">Sağlayıcılar</h2>
                </div>
                <div class="card-body pt-2">
                    <div class="table-responsive border rounded bg-body-tertiary mb-6">
                        <table class="table align-middle table-row-dashed fs-6 gy-3 mb-0">
                            <thead>
                                <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0 border-bottom">
                                    <th class="ps-5 min-w-125px">Sağlayıcı</th>
                                    <th>Akış</th>
                                    <th>.env</th>
                                    <th>Geri dönüş adresi</th>
                                </tr>
                            </thead>
                            <tbody class="text-body fw-semibold">
                                <tr><td class="ps-5">Google</td><td>GIS düğmesi → <code>POST /app/social/google</code> (id_token)</td><td><code>GOOGLE_OAUTH_CLIENT_ID</code></td><td>— (JS origin)</td></tr>
                                <tr><td class="ps-5">Apple</td><td>popup → <code>/oauth/apple</code> → form_post id_token</td><td><code>APPLE_SERVICES_ID</code></td><td><code>/oauth/apple/callback</code></td></tr>
                                <tr><td class="ps-5">Microsoft</td><td>popup → <code>/oauth/microsoft</code> → form_post id_token</td><td><code>MICROSOFT_CLIENT_ID</code></td><td><code>/oauth/microsoft/callback</code></td></tr>
                                <tr><td class="ps-5">Facebook</td><td>popup → <code>/oauth/facebook</code> → code → Graph API</td><td><code>FACEBOOK_APP_ID</code>, <code>FACEBOOK_APP_SECRET</code></td><td><code>/oauth/facebook/callback</code></td></tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="alert alert-primary d-flex align-items-center p-4 mb-0">
                        <i class="fa-solid fa-circle-info fs-2 text-primary me-3"></i>
                        <span class="text-body fs-6">Anahtarı boş olan sağlayıcının düğmesi çıkmaz, ucu 404 döner. Gömülü WebView'lerde
                            (<code>config.php</code> → <code>blocked_user_agents</code>) hiçbir düğme çıkmaz: sağlayıcılar orada girişi reddeder.</span>
                    </div>
                </div>
            </div>
            <!--end::Akışlar-->

            <!--begin::Kurulum-->
            <div class="card mb-10">
                <div class="card-header border-0 pt-8">
                    <h2 class="card-title fw-bold text-body-emphasis">Kurulum</h2>
                </div>
                <div class="card-body pt-2">
                    <div class="d-flex flex-column gap-5 mb-8">
                        <div class="d-flex align-items-start">
                            <span class="badge badge-circle badge-primary me-4 mt-1 fs-6 flex-shrink-0">1</span>
                            <div>
                                <div class="fw-bold text-body mb-1">Eklentiyi aç</div>
                                <div class="text-body-secondary fs-6 mt-1"><code>extensions/extensions.php</code> → <code>'social-auth' =&gt; true</code>. Anahtarları <code>.env</code>'e yaz.</div>
                            </div>
                        </div>
                        <div class="d-flex align-items-start">
                            <span class="badge badge-circle badge-primary me-4 mt-1 fs-6 flex-shrink-0">2</span>
                            <div>
                                <div class="fw-bold text-body mb-1">Hız sınırı tablosu</div>
                                <div class="text-body-secondary fs-6 mt-1">Varsayılan sayaç çekirdeğin <code>RateLimiter</code>'ıdır (<code>tbl_vnl_rate_limits</code>):
                                    <code class="text-warning">php vnl.php migrate --run</code>. Kendi sayacınız varsa <code>config.php</code> → <code>rate_limit</code>.</div>
                            </div>
                        </div>
                        <div class="d-flex align-items-start">
                            <span class="badge badge-circle badge-primary me-4 mt-1 fs-6 flex-shrink-0">3</span>
                            <div>
                                <div class="fw-bold text-body mb-1">Kancayı tanımla</div>
                                <div class="text-body-secondary fs-6 mt-1">Projenin <code>helpers/</code> klasöründe, ör. <code>helpers/social_auth.php</code>. Tanımlı değilse giriş 500 ile reddedilir ve günlüğe yazılır.</div>
                            </div>
                        </div>
                        <div class="d-flex align-items-start">
                            <span class="badge badge-circle badge-primary me-4 mt-1 fs-6 flex-shrink-0">4</span>
                            <div>
                                <div class="fw-bold text-body mb-1">Düğmeleri bas, sonucu dinle</div>
                                <div class="text-body-secondary fs-6 mt-1">Sayfanın başlığında <code>&lt;meta name="csrf-token"&gt;</code> bulunmalı (<code>head-standard.php</code> basar).</div>
                            </div>
                        </div>
                    </div>

                    <div class="vnl-code mb-6">
                        <pre class="mb-0" style="background:transparent; white-space:pre-wrap;"><code>&lt;?php
// helpers/social_auth.php

/**
 * $profile = [provider, sub, email, name, picture]
 *   sub    sağlayıcının kalıcı kimliği — hesabı BUNUNLA eşleştir (provider + sub)
 *   email  YALNIZ sağlayıcı doğruladıysa dolu, yoksa null
 */
function vnl_social_sign_in(array $profile): array
{
    // Hesap sağlayıcı + sub ile bulunur (e-postayla DEĞİL). Sorgu modelde.
    $user = User::findBySocial($profile['provider'], $profile['sub']);
    if (!$user || !UserModule::IsActiveUser($user)) {
        return ['ok' =&gt; false, 'status' =&gt; 404, 'message' =&gt; 'Bu hesap bağlı değil.'];
    }

    // 2FA açıksa oturum AÇILMAZ: parola girişiyle aynı ikinci adım.
    // Atlanırsa sosyal giriş 2FA'yı devre dışı bırakan bir arka kapı olur.
    if ($user-&gt;hasTotp()) {
        Session::regenerate();
        Session::set('_2fa_pending', ['user_id' =&gt; (int)$user-&gt;id, 'remember' =&gt; false, 'at' =&gt; time()]);

        return ['ok' =&gt; true, 'message' =&gt; '', 'data' =&gt; ['step' =&gt; 'totp']];
    }

    // Oturum kimliği yenilenir, CSRF döner, denetim kaydı yazılır.
    UserModule::CompleteLogin($user);

    return ['ok' =&gt; true, 'message' =&gt; '', 'data' =&gt; ['status' =&gt; 'login']];
}

/** Facebook veri silme isteği (isteğe bağlı). */
function vnl_social_forget(string $provider, string $sub): void { /* bağlantıyı sil */ }
</code></pre>
                    </div>

                    <div class="vnl-code mb-0">
                        <pre class="mb-0" style="background:transparent; white-space:pre-wrap;"><code>&lt;?= class_exists('SocialAuth') ? SocialAuth::buttons(['context' =&gt; 'signin', 'locale' =&gt; 'tr', 'aria' =&gt; '%s ile devam et']) : '' ?&gt;

&lt;script&gt;
document.addEventListener('vnl:social-auth', (e) =&gt; {
    const r = e.detail; // {ok, status, message, data, cancelled?}
    if (r.cancelled) return;
    if (r.ok &amp;&amp; r.data.step === 'totp') return showTotpForm();  // kod: POST /app/login/totp
    if (r.ok) location.href = '/';
});
&lt;/script&gt;
</code></pre>
                    </div>
                </div>
            </div>
            <!--end::Kurulum-->

            <!--begin::Güvenlik-->
            <div class="card mb-10">
                <div class="card-header border-0 pt-8">
                    <h2 class="card-title fw-bold text-body-emphasis">Güvenlik</h2>
                </div>
                <div class="card-body pt-2">
                    <ul class="text-body fs-6 mb-0">
                        <li class="mb-2">İmzalı jetonlar (Google/Apple/Microsoft) RS256'ya sabittir; imza sağlayıcının JWKS'iyle doğrulanır; <code>iss</code>, <code>aud</code>, <code>exp/iat/nbf</code> ve <b>nonce</b> denetlenir.</li>
                        <li class="mb-2">Doğrulanmamış e-posta profile hiç girmez (Microsoft iş hesapları: <code>xms_edov</code>). E-postayla hesap bağlamak bu yüzden güvenlidir.</li>
                        <li class="mb-2">Yönlendirme durumu (state + nonce) 10 dakikalık <code>__vnl_oauth</code> çerezindedir; state tutmazsa giriş reddedilir (giriş CSRF'i).</li>
                        <li class="mb-2"><code>POST /app/social/google</code> girişsiz çağrılabilir (<code>PUBLIC_METHODS</code>) ama CSRF denetiminden geçer.</li>
                        <li class="mb-2">Dönüş yolu (<code>ret</code>) çekirdeğin <code>vnl_safe_return_url()</code> fonksiyonundan geçer. Yalnız site içi göreli adres kabul edilir; boşluk ya da kontrol karakteri taşıyan adresler reddedilir (3.5.2'ye kadar <code>/%09/evil.com</code> geçiyordu).</li>
                        <li><code>/oauth/...</code> uçları <code>boot.php</code>'de <code>vnl_public_page('oauth')</code> ile girişe açılır.</li>
                    </ul>
                </div>
            </div>
            <!--end::Güvenlik-->

        </div>
    </div>
</div>

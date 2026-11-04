<div class="d-flex flex-column flex-column-fluid">
    <div id="kt_app_content" class="app-content flex-column-fluid">
        <div id="kt_app_content_container" class="app-container container-xxl">

            <div class="vnl-page-hero mb-12 mt-6">
                <div class="vnl-overline mb-3">// docs/security</div>
                <h1 class="fs-2hx fw-bold mb-4 text-body-emphasis">Güvenlik (3.4.0)</h1>
                <div class="vnl-lead">VNL 3.4.0 ile şablon <b>varsayılan olarak kapalı</b> gelir: sayfalar girişe, action'lar POST + CSRF + girişe, yetkiler koda bağlıdır. Bu sayfa kapıları, yetki modelini, oturum kurallarını, hız sınırını, iki adımlı doğrulamayı, denetim kaydını ve API jetonlarını tek yerde toplar.</div>
            </div>

            <div class="card mb-10">
                <div class="card-body p-10">

                    <!--begin::Section 1-->
                    <div class="mb-10">
                        <h3 class="fw-bold text-body-emphasis mb-4">1. Kapılar</h3>
                        <div class="table-responsive border rounded bg-body-tertiary mb-5">
                            <table class="table align-middle table-row-dashed fs-6 gy-4 mb-0">
                                <thead>
                                <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0 border-bottom">
                                    <th class="ps-5 min-w-200px">Nerede</th>
                                    <th class="min-w-350px">Kural</th>
                                    <th class="min-w-150px">Yanıt</th>
                                </tr>
                                </thead>
                                <tbody class="text-body fw-semibold">
                                <tr>
                                    <td class="ps-5">Sayfa (<code>vnl/kernel.php</code>)</td>
                                    <td>Girişsiz istek, sayfa <code>$login_except_pages</code>'de ya da <code>vnl_public_page()</code> ile kayıtlı değilse denetleyici <b>çalıştırılmadan</b> reddedilir.</td>
                                    <td>302 <code>/login</code> (<code>$login_return_param</code> açıksa GET isteğinin adresiyle) · AJAX: 401 <code>VNL_AUTH</code></td>
                                </tr>
                                <tr>
                                    <td class="ps-5">Sayfa yetkisi</td>
                                    <td><code>vnl_require_permission('Domains', 'view');</code> — denetleyicinin ilk satırı.</td>
                                    <td>403 sayfası (<code>controller/403.php</code>)</td>
                                </tr>
                                <tr>
                                    <td class="ps-5">Action yöntemi</td>
                                    <td>Yalnız <code>POST</code>.</td>
                                    <td>405 <code>VNL_METHOD</code></td>
                                </tr>
                                <tr>
                                    <td class="ps-5">Action girişi</td>
                                    <td>Varsayılan zorunlu; sınıfın <code>PUBLIC_METHODS</code> listesi hariç. CSRF'ten <b>önce</b> bakılır: oturumu biten sekmenin jetonu da eskidir, 403 yerine 401 alır ve vnl.js onu <code>/login</code>'e götürür.</td>
                                    <td>401 <code>VNL_AUTH</code></td>
                                </tr>
                                <tr>
                                    <td class="ps-5">CSRF</td>
                                    <td><code>X-CSRF-Token</code> başlığı ya da <code>_token</code> alanı; <code>Origin</code> varsa bu sunucu olmalı. Sınıfın <code>CSRF_EXEMPT</code> listesi hariç. Jeton eskidiyse (başka sekmede giriş, "beni hatırla" yenilemesi) vnl.js mesajı gösterip sayfayı bir kez yeniler.</td>
                                    <td>403 <code>VNL_CSRF</code></td>
                                </tr>
                                <tr>
                                    <td class="ps-5">Action yetkisi</td>
                                    <td><code>Auth::CheckPermission('Domains', 'edit');</code> — action'ın ilk satırı.</td>
                                    <td>403 <code>VNL_PERM</code></td>
                                </tr>
                                <tr>
                                    <td class="ps-5">Metot çözümü</td>
                                    <td>Yalnız <code>public</code>, static olmayan, <code>__</code> ile başlamayan metotlar; ad <code>[a-z0-9_]+</code>.</td>
                                    <td>404 <code>VNL_NOT_FOUND</code></td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="vnl-code">
                            <pre class="mb-0" style="background: transparent; white-space: pre-wrap;"><code>// actions/action_domains.php
use ActionGlobal\Auth;

class action_domains
{
    public const PUBLIC_METHODS = [];            // girişsiz çağrılabilecekler
    public const CSRF_EXEMPT    = [];            // imzasını kendisi doğrulayan webhook'lar

    public function save($req)
    {
        Auth::CheckPermission('Domains', (int)$req-&gt;post('id') &gt; 0 ? 'edit' : 'insert');
        // ...
        Audit::log('domain.update', 'domain', $id, ['name' =&gt; $name]);
        returnSuccessMessage(lang('SUC_GENERAL'));
    }
}</code></pre>
                        </div>
                    </div>
                    <!--end::Section 1-->

                    <div class="separator separator-dashed my-8"></div>

                    <!--begin::Section 2-->
                    <div class="mb-10">
                        <h3 class="fw-bold text-body-emphasis mb-4">2. CSRF jetonu</h3>
                        <p class="fs-5 text-body mb-4">
                            Jeton oturum başına bir tanedir, girişte ve çıkışta yenilenir. <code>head-standard.php</code> onu
                            <code>&lt;meta name="csrf-token"&gt;</code> olarak basar; <code>vnl.js</code> aynı kökene giden her jQuery
                            isteğine, <code>vnltable.js</code> her <code>fetch</code>'ine <code>X-CSRF-Token</code> başlığını ekler.
                            Düz form için <code>&lt;?= vnl_csrf_field() ?&gt;</code>.
                        </p>
                        <div class="table-responsive border rounded bg-body-tertiary">
                            <table class="table align-middle table-row-dashed fs-6 gy-3 mb-0">
                                <tbody class="text-body fw-semibold">
                                <tr><td class="ps-5"><code>vnl_csrf_token()</code></td><td>Oturumun jetonu (yoksa üretir).</td></tr>
                                <tr><td class="ps-5"><code>vnl_csrf_field()</code></td><td>Gizli <code>_token</code> alanı.</td></tr>
                                <tr><td class="ps-5"><code>vnl_csrf_check()</code></td><td>İstekle gelen jeton doğru mu (sabit zamanlı).</td></tr>
                                <tr><td class="ps-5"><code>vnl_csrf_rotate()</code></td><td>Yeni jeton.</td></tr>
                                <tr><td class="ps-5"><code>vnlCsrfToken()</code> (JS)</td><td>Meta etiketindeki jeton.</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <!--end::Section 2-->

                    <div class="separator separator-dashed my-8"></div>

                    <!--begin::Section 3-->
                    <div class="mb-10">
                        <h3 class="fw-bold text-body-emphasis mb-4">3. Yetki modeli</h3>
                        <p class="fs-5 text-body mb-4">
                            Menü (<code>'permission'</code> anahtarı), sayfa ve action aynı soruyu sorar:
                            <code>vnl_can($kod, $yetenek)</code> → projenin <code>vnl_project_permission()</code> kancası
                            (<code>helpers/permissions.php</code>). Şablonun kancası: girişsiz ya da pasif kullanıcı hiçbir şey,
                            <code>perm_id = 1</code> (süper yönetici) her şey, diğerleri <code>tbl_user_permissions</code>'taki
                            <code>can_view/can_insert/can_edit/can_delete</code>.
                        </p>
                        <div class="vnl-code mb-4">
                            <pre class="mb-0" style="background: transparent; white-space: pre-wrap;"><code>UserPermissions::Grant($userId, 'Domains', ['view', 'edit']);   // kodu yoksa oluşturur
UserPermissions::Can($userId, 'Domains', 'edit');                  // bool
UserPermissions::Revoke($userId, 'Domains');
UserPermissions::ForUser($userId);   // ['Domains' =&gt; ['view' =&gt; true, 'insert' =&gt; false, ...]]
Permission::EnsureCode('Domains', 'Domainler');</code></pre>
                        </div>
                        <div class="alert alert-warning d-flex align-items-center p-4 mb-0">
                            <i class="fa-solid fa-circle-info fs-2 text-warning me-3"></i>
                            <span class="text-body fs-6"><b>Katı ve gevşek soru.</b> Yetki <b>kapıları</b> — <code>vnl_require_permission()</code>, <code>Auth::CheckPermission()</code>, <code>Auth::Can()</code> — <code>vnl_can($kod, $yetenek, true)</code> ile katı sorar: kanca tanımlı değilse ya da kod boşsa cevap <b>hayır</b>dır ve eksik kanca günlüğe yazılır. Yalnız menü görünürlüğü 3.1 uyumlu gevşek soruyu kullanır (kanca yoksa her öğe görünür). <code>php vnl.php doctor</code> kancanın varlığını sınar. Kullanıcı yönetimi uçları <code>actions/action_users.php</code>'de (<code>getList</code>, <code>get</code>, <code>save</code>, <code>delete</code>, <code>permissions</code>, <code>setPermissions</code>; yetki kodu <code>Users</code>): süper yönetici atamak yalnız süper yöneticinin, kendi hesabını değiştirmek kimsenin işi değildir; süper olmayan yönetici yalnız kendisinde olan yetenekleri dağıtabilir ve <b>yalnız bütün yetenekleri kendisinde de olan</b> bir kullanıcıyı düzenleyebilir, parolasını sıfırlayabilir, silebilir ya da yetkilerini değiştirebilir — aksi halde <code>Users:edit</code> ile daha yetkili birinin parolasını sıfırlayıp onun yerine girmek mümkün olurdu.</span>
                        </div>
                    </div>
                    <!--end::Section 3-->

                    <div class="separator separator-dashed my-8"></div>

                    <!--begin::Section 4-->
                    <div class="mb-10">
                        <h3 class="fw-bold text-body-emphasis mb-4">4. Oturum ve giriş</h3>
                        <ul class="fs-6 text-body mb-0">
                            <li class="mb-2">Oturum çerezi <code>HttpOnly</code>, <code>SameSite=Lax</code> (<code>VNL_SESSION_SAMESITE</code>), TLS'te <code>Secure</code>; <code>session.use_strict_mode</code> açık.</li>
                            <li class="mb-2">Girişte oturum kimliği yenilenir (<code>Session::regenerate()</code>) ve CSRF jetonu döner.</li>
                            <li class="mb-2"><code>VNL_SESSION_IDLE</code> saniye (varsayılan 3600) istek gelmezse oturum biter ve bu cihazın "beni hatırla" jetonu da iptal edilir; oturum dosyası önceden silinmişse jetonun son kullanımı (<code>used_at</code>) aynı sınırla denetlenir. <code>VNL_IDLE_KEEPS_REMEMBER=true</code> eski davranışı açar (cihaz aynı istekte sessizce yeniden girer). <code>VNL_REMEMBER_DAYS=0</code> kalıcı girişi tamamen kapatır.</li>
                            <li class="mb-2">Hesap pasifleşirse (<code>is_active = 0</code>) açık oturum bir sonraki istekte kapanır.</li>
                            <li class="mb-2"><b>Hız sınırı</b> (15 dk pencere → 429 <code>VNL_RATE</code>): IP başına 20 deneme (IPv6'da <code>/64</code> öneki; <code>::ffff:</code> ile gelen IPv4 tam adres), hesap + IP başına 5, hesap başına bütün adreslerden 100. Deneme parola denetlenmeden <b>önce</b> sayılır ve kapı sayımın sonucuna bakar — paralel istekler sınırı aşamaz; başarılı deneme IP bütçesinden geri düşülür. Hesap anahtarı <b>kayıtlı</b> kullanıcı adından üretilir: harmanlama (<code>utf8mb4_unicode_ci</code>) <code>ädmin</code>'i de <code>admin</code>'e eşlediği için yazım değiştirmek yeni hak kazandırmaz; sıkı kilit hesap + IP olduğundan başka bir adresten 5 yanlış parola gerçek yöneticiyi kilitleyemez.
                                <br>Genel API: <code>if (!RateLimiter::hit($anahtar, $max, $sn)) { /* 429 */ }</code> — önce say, sonra karar ver; <code>RateLimiter::ipKey($ip)</code>, <code>decrement()</code>, <code>clear()</code>, <code>availableIn()</code>. <code>tooMany()</code> yalnız ön kontroldür, tek kapı olamaz. IP'nin gerçek istemci olması sunucuya bağlıdır: nginx → Apache arkasında <code>mod_remoteip</code> yoksa herkes vekilin adresinden gelir.</li>
                            <li class="mb-2"><b>Çıkış</b> <code>POST /app/login/logout</code>'tur; <code>data-vnl-logout</code> taşıyan her öğe onu çağırır. <code>GET /logout</code> yalnız onay sayfasıdır.</li>
                            <li>İlk yönetici: <code>php vnl.php user:create --username=admin --super</code> (parola bir kez basılır).</li>
                        </ul>
                    </div>
                    <!--end::Section 4-->

                    <div class="separator separator-dashed my-8"></div>

                    <!--begin::Section 5-->
                    <div class="mb-10">
                        <h3 class="fw-bold text-body-emphasis mb-4">5. İki adımlı doğrulama (TOTP)</h3>
                        <p class="fs-5 text-body mb-4">
                            RFC 6238 (SHA1, 30 sn, 6 hane, ±1 adım). <code>totp_enabled</code> kullanıcıda parola adımı oturumu açmaz,
                            <code>{"data":{"step":"totp"}}</code> döner; giriş ekranı kod alanını gösterir ve kod
                            <code>POST /app/login/totp</code>'a gider (5 dk içinde, kullanıcı başına 5 deneme).
                            Sır <code>APP_ENCRYPTION_KEY</code> ile şifreli saklanır. <b>Aynı kod ikinci kez kabul edilmez</b>
                            (RFC 6238 §5.2): eşleşen adım <code>tbl_users.totp_last_step</code>'e koşullu bir UPDATE ile yazılır
                            (<code>User::claimTotpStep()</code>, migration <code>0007</code>); tekrar başarısız deneme sayılır.
                            Sütun yoksa ikinci adım <b>kapalıdır</b> (fail closed) — <code>php vnl.php migrate --run</code>.
                            Kendi doğrulama akışınızda <code>Totp::verify()</code> değil <code>Totp::match()</code> + adım saklama kullanın.
                        </p>
                        <div class="vnl-code">
                            <pre class="mb-0" style="background: transparent; white-space: pre-wrap;"><code>php vnl.php user:totp --username=admin            # sır + otpauth:// adresi (bir kez)
php vnl.php user:totp --username=admin --disable

$secret = Totp::generateSecret();
$uri    = Totp::uri($secret, $user-&gt;username, 'VNL Panel');   // QR içeriği
Totp::verify($secret, $code);                                 // bool — tekrarı ENGELLEMEZ
$step = Totp::match($secret, $code);                          // ?int — kapıda bunu kullanın
if ($step === null || !$user-&gt;claimTotpStep($step)) { /* red */ }
$user-&gt;totp_secret  = Totp::encryptSecret($secret);
$user-&gt;totp_enabled = 1;</code></pre>
                        </div>
                    </div>
                    <!--end::Section 5-->

                    <div class="separator separator-dashed my-8"></div>

                    <!--begin::Section 6-->
                    <div class="mb-10">
                        <h3 class="fw-bold text-body-emphasis mb-4">6. Denetim kaydı</h3>
                        <p class="fs-5 text-body mb-4">
                            <code>Audit::log($eylem, $tur = null, $kimlik = null, $veri = [])</code> kullanıcıyı, IP'yi, tarayıcıyı ve
                            zamanı kendisi doldurur, <b>asla istisna fırlatmaz</b> ve adı <code>password</code>/<code>token</code>/<code>secret</code>
                            içeren anahtarların değerini (dizi de olsa tamamını) <code>[redacted]</code> olarak yazar. <b><code>$veri</code>'nin
                            anahtarları alan adıdır, veri değil:</b> izin kodu, alan adı gibi veriden türeyen tanımlayıcılar kayıt listesi
                            olarak yazılır (<code>['permissions' =&gt; [['code' =&gt; 'ApiTokens', 'abilities' =&gt; ['view']]]]</code>) — anahtar
                            yapılsaydı <code>ApiTokens</code> gibi bir kod süzgece takılırdı. Yerleşik olaylar: <code>auth.login</code>, <code>auth.login_failed</code>,
                            <code>auth.logout</code>, <code>auth.password_changed</code>, <code>auth.totp_*</code>, <code>user.*</code>,
                            <code>api.token_*</code>. Yönetim ekranı: <code>Audit::query()</code>; konsolda işlemi yapan:
                            <code>Audit::actingAs($userId)</code>.
                        </p>
                    </div>
                    <!--end::Section 6-->

                    <div class="separator separator-dashed my-8"></div>

                    <!--begin::Section 7-->
                    <div class="mb-10">
                        <h3 class="fw-bold text-body-emphasis mb-4">7. API jetonları</h3>
                        <p class="fs-5 text-body mb-4">
                            <code>Authorization: Bearer vnl_…</code> başlığı <code>/app/*</code> çağrısında oturum ve CSRF'in yerine geçer.
                            Jeton tabloda SHA-256 özeti olarak durur; yetenekler modül düzeyinde dar bir listedir
                            (<code>*</code>, <code>domains</code>, <code>domains:getList</code>, <code>jobs:*</code>) ve kullanıcının kendi
                            yetkileri ayrıca uygulanır. İptal edilmiş ya da süresi geçmiş jeton → 401 <code>VNL_TOKEN</code>.
                        </p>
                        <div class="alert alert-warning d-flex align-items-start p-4 mb-4">
                            <i class="fa-solid fa-triangle-exclamation fs-2 text-warning me-3 mt-1"></i>
                            <span class="text-body fs-6"><b>Apache + PHP-FPM:</b> <code>mod_proxy_fcgi</code> <code>Authorization</code> başlığını PHP'ye iletmez; jeton hiç görünmez ve istek 401 <code>VNL_AUTH</code> alır. Şablonun <code>.htaccess</code>'i başlığı <code>RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]</code> ile iletir (eski <code>.htaccess</code>'e bu iki satırı ekleyin; <code>php vnl.php doctor</code> denetler). Alternatif: vhost'ta <code>CGIPassAuth On</code> (Apache 2.4.13+). nginx'te <code>fastcgi_params</code> başlığı zaten iletir.</span>
                        </div>
                        <div class="vnl-code">
                            <pre class="mb-0" style="background: transparent; white-space: pre-wrap;"><code>php vnl.php token:create --user=admin --name=izleme --abilities=jobs:status --days=90
php vnl.php token:revoke --list --user=admin
php vnl.php token:revoke --id=12

ApiToken::issue($userId, 'ad', ['domains'], 90);   // düz jeton — bir kez gösterin</code></pre>
                        </div>
                    </div>
                    <!--end::Section 7-->

                    <div class="separator separator-dashed my-8"></div>

                    <!--begin::Section 8-->
                    <div class="mb-0">
                        <h3 class="fw-bold text-body-emphasis mb-4">8. Sunucu tarafı</h3>
                        <ul class="fs-6 text-body mb-0">
                            <li class="mb-2"><code>APP_DEBUG=false</code> canlıda: hata ayrıntısı günlüğe, kullanıcıya başvuru kodu.</li>
                            <li class="mb-2">Güvenlik başlıkları (<code>VNL_SECURITY_HEADERS</code>): <code>X-Frame-Options: SAMEORIGIN</code>, <code>nosniff</code>, <code>Referrer-Policy: same-origin</code>, <code>Permissions-Policy</code>, TLS'te HSTS. CSP henüz yok (satır içi betikler).</li>
                            <li class="mb-2">nginx: <code>nginx.conf.example</code> — yalnız <code>index.php</code> çalışır, yalnız <code>assets/</code> ve <code>vnl/assets/</code> servis edilir. Apache: <code>.htaccess</code> çekirdek ve proje dizinlerindeki dosyaları reddeder.</li>
                            <li>Günlükler <code>LOG_PATH</code> (varsayılan <code>files/logs</code>, dizin 0750, dosya 0640): <code>LogModule::info/warning/error($kanal, $mesaj, $bağlam)</code>.</li>
                        </ul>
                    </div>
                    <!--end::Section 8-->

                </div>
            </div>

        </div>
    </div>
</div>

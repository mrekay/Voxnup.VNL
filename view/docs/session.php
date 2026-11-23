<div class="d-flex flex-column flex-column-fluid">
    <div id="kt_app_content" class="app-content flex-column-fluid">
        <div id="kt_app_content_container" class="app-container container-xxl">

            <div class="vnl-page-hero mb-12 mt-6">
                <div class="vnl-overline mb-3">// docs/session</div>
                <h1 class="fs-2hx fw-bold mb-4 text-body-emphasis">Oturum Yönetimi ve Kimlik Doğrulama</h1>
                <div class="vnl-lead">VNL, oturum açma/kapama işlemlerini <code>Session</code> sınıfı ve <code>UserModule</code> modülü aracılığıyla yönetir. Her istekte kullanıcı kimliği doğrulanır; korunan sayfalara giriş yapılmadan erişim engellenir.</div>
            </div>

            <div class="card mb-10">
                <div class="card-body p-10">

                    <!--begin::Section 1-->
                    <div class="mb-10">
                        <h3 class="fw-bold text-body-emphasis mb-4">1. Oturum Akışı (Request Lifecycle)</h3>
                        <p class="fs-5 text-body mb-5">Her HTTP isteğinde sistem şu sırayla oturumu doğrular:</p>

                        <div class="border rounded p-5 bg-body-tertiary mb-5">
                            <div class="d-flex align-items-start mb-5">
                                <span class="badge badge-circle badge-primary me-4 mt-1 flex-shrink-0">1</span>
                                <div>
                                    <span class="fw-bold text-body">Çerez ile Otomatik Giriş</span>
                                    <p class="text-body-secondary fs-6 mb-0 mt-1"><code>UserModule::CheckCookieAuth()</code> — Aktif oturum yoksa fakat <code>{prefix}remember</code> çerezi varsa, jeton doğrulanarak kullanıcı otomatik giriş yaptırılır ve jeton yenilenir. Ayrıntı için <b>7. bölüme</b> bakın.</p>
                                </div>
                            </div>
                            <div class="d-flex align-items-start mb-5">
                                <span class="badge badge-circle badge-primary me-4 mt-1 flex-shrink-0">2</span>
                                <div>
                                    <span class="fw-bold text-body">Kullanıcı Bilgisi Yükleme</span>
                                    <p class="text-body-secondary fs-6 mb-0 mt-1"><code>UserModule::SetCurrentUserInfo()</code> — Session'da <code>user_id</code> varsa veritabanından kullanıcı nesnesi çekilerek global <code>$CurrentUser</code>'a atanır. Kullanıcı bulunamazsa (silinmiş/deaktif) otomatik çıkış yapılır. 4.0.0: oturumdaki <code>auth_stamp</code> parola hash'i ve TOTP sırrından yeniden hesaplanır; tutmazsa (parola ya da 2FA bu oturumdan sonra değişti) oturum kapanır ve <code>auth.session_revoked</code> denetim kaydı yazılır.</p>
                                </div>
                            </div>
                            <div class="d-flex align-items-start mb-5">
                                <span class="badge badge-circle badge-primary me-4 mt-1 flex-shrink-0">3</span>
                                <div>
                                    <span class="fw-bold text-body">Oturum Tutarlılık Kontrolü</span>
                                    <p class="text-body-secondary fs-6 mb-0 mt-1"><code>UserModule::CheckUser()</code> — Session'da <code>login = true</code> varken <code>$CurrentUser</code> boşsa (tutarsız durum) session temizlenerek <code>/login</code>'e yönlendirilir.</p>
                                </div>
                            </div>
                            <div class="d-flex align-items-start mb-0">
                                <span class="badge badge-circle badge-primary me-4 mt-1 flex-shrink-0">4</span>
                                <div>
                                    <span class="fw-bold text-body">Sayfa Erişim Kontrolü (<code>vnl/kernel.php</code>)</span>
                                    <p class="text-body-secondary fs-6 mb-0 mt-1">İstenen sayfa <code>$login_except_pages</code>'de (ya da <code>vnl_public_page()</code> ile kayıtlı) değilse ve kullanıcı giriş yapmamışsa denetleyici <b>hiç çalıştırılmadan</b> <code>/login</code>'e yönlendirilir (AJAX isteğine 401 <code>VNL_AUTH</code>). Boşta kalma sınırı (<code>VNL_SESSION_IDLE</code>) aşılmışsa ya da hesap pasifleşmişse oturum burada kapanır; boşta kalmada bu cihazın "beni hatırla" jetonu da iptal edilir (<code>VNL_IDLE_KEEPS_REMEMBER=true</code> eski sessiz yeniden girişi açar).</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!--end::Section 1-->

                    <div class="separator separator-dashed my-8"></div>

                    <!--begin::Section 2-->
                    <div class="mb-10">
                        <h3 class="fw-bold text-body-emphasis mb-4">2. <code>Session</code> Sınıfı</h3>
                        <p class="fs-5 text-body mb-4">
                            PHP'nin <code>$_SESSION</code> süper globalinin güvenli sarmalayıcısıdır. Session yazma/okuma işlemleri arasında <code>session_write_close()</code> çağrılarak eş zamanlı istek sorunları önlenir.
                        </p>

                        <div class="table-responsive border rounded bg-body-tertiary mb-5">
                            <table class="table align-middle table-row-dashed fs-6 gy-4 mb-0">
                                <thead>
                                <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0 border-bottom">
                                    <th class="ps-5 min-w-280px">Metot</th>
                                    <th class="min-w-350px">Açıklama</th>
                                </tr>
                                </thead>
                                <tbody class="text-body fw-semibold">
                                <tr>
                                    <td class="ps-5"><code>Session::set('anahtar', $deger)</code></td>
                                    <td>Session'a veri yazar. Her türlü değer (string, int, array, object) desteklenir.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>Session::get('anahtar')</code></td>
                                    <td>Session'dan veri okur. Anahtar yoksa <code>false</code> döner.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>Session::delete('anahtar')</code></td>
                                    <td>Tek bir session anahtarını siler. Başarıda <code>true</code>, bulunamazsa <code>false</code> döner.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>Session::destroy()</code></td>
                                    <td>Tüm session verisini yok eder. Çıkış işlemlerinde kullanılır.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>Session::setMany([...])</code></td>
                                    <td>Birden çok anahtarı tek açılışta yazar (tek kilit, tek dosya yazımı).</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>Session::regenerate()</code></td>
                                    <td>Oturum kimliğini yeniler; veriler korunur, eski kimlik geçersizleşir (session fixation). Girişte <code>SetUser()</code> çağırır.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>Session::id()</code></td>
                                    <td>Geçerli oturum kimliğini döndürür.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>Session::checkSession()</code></td>
                                    <td>Oturum durumunu döner: <code>1</code> = giriş yapılmış, <code>0</code> = kilitli, <code>-1</code> = giriş yok.</td>
                                </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="vnl-code">
                            <pre class=" mb-0" style="background: transparent; white-space: pre-wrap;"><code>// Oturum açmak için anahtarları elle yazmayın: UserModule::CompleteLogin($user)
// kimliği yeniler, CSRF jetonunu döndürür, auth_stamp ve denetim kaydını yazar.

// Veri okuma (herhangi bir yerden)
$kullanici_id = Session::get('user_id');

// Tek anahtar silme
Session::delete('gecici_veri');

// Çıkış: Session::destroy() "beni hatırla" jetonunu iptal etmez.
// Çıkış POST'tur: POST /app/login/logout ya da data-vnl-logout
// taşıyan düğme; sunucu tarafında UserModule::LogOut() çağrılır.
UserModule::LogOut();
</code></pre>
                        </div>
                    </div>
                    <!--end::Section 2-->

                    <div class="separator separator-dashed my-8"></div>

                    <!--begin::Section 3-->
                    <div class="mb-10">
                        <h3 class="fw-bold text-body-emphasis mb-4">3. Giriş Akışı — <code>UserModule::SetUser()</code></h3>
                        <p class="fs-5 text-body mb-4">
                            Kullanıcı kimlik bilgileri doğrulandıktan sonra <code>UserModule::SetUser($user)</code> çağrısı session'ı aşağıdaki anahtarlarla doldurur:
                        </p>

                        <div class="row g-5 mb-5">
                            <div class="col-md-6">
                                <div class="border border-dashed rounded p-5 h-100">
                                    <h6 class="fw-bold mb-4">SetUser() Sonrası Session Anahtarları</h6>
                                    <ul class="list-unstyled fs-6 mb-0">
                                        <li class="mb-2"><code>login</code> → <span class="badge badge-soft-success">true</span></li>
                                        <li class="mb-2"><code>lock</code> → <span class="badge badge-soft-danger">false</span></li>
                                        <li class="mb-2"><code>user_id</code> → <code>$user->id</code></li>
                                        <li class="mb-2"><code>login_time</code> → <code>time()</code></li>
                                        <li class="mb-2"><code>last_seen</code> → <code>time()</code> (boşta kalma ölçüsü)</li>
                                        <li class="mb-0"><code>auth_stamp</code> → <code>UserModule::AuthStamp($user)</code> (4.0.0)</li>
                                    </ul>
                                    <p class="fs-7 text-muted mt-3 mb-0">Yazmadan önce oturum kimliği yenilenir (<code>Session::regenerate()</code>), bekleyen 2FA adımı silinir; yazdıktan sonra CSRF jetonu döndürülür (<code>vnl_csrf_rotate()</code>). Oturum çerezinin adı 4.0.0'dan beri <code>VNLSESSID</code>'dir (<code>VNL_SESSION_NAME</code>).</p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="border border-dashed rounded p-5 h-100">
                                    <h6 class="fw-bold mb-4">Çerez ile Kalıcı Giriş</h6>
                                    <p class="fs-6 text-body mb-0">
                                        <code>SetUser()</code> yalnızca oturumu doldurur; tarayıcı kapanınca biter. Kalıcı giriş isteniyorsa ayrıca <code>UserModule::RememberMe($user)</code> çağrılır — rastgele bir jeton üretilip <code>{prefix}remember</code> çerezine yazılır, veritabanında yalnızca özeti saklanır. <b>7. bölümde</b> ayrıntılandırılmıştır.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="vnl-code">
                            <pre class=" mb-0" style="background: transparent; white-space: pre-wrap;"><code>&lt;?php
// actions/action_login.php — şablondaki dosyanın kısaltılmış hâli.
// Hız sınırı sayaçları (RateLimiter) burada gösterilmiyor; asıl dosyaya bakın.

class action_login
{
    // Girişsiz çağrılan metotlar; yoksa dağıtıcı 401 döner
    public const PUBLIC_METHODS = ['login', 'totp', 'logout'];

    public function login($req)
    {
        $username = trim((string)$req->post('email', ''));
        $password = (string)$req->post('password', '');

        // Hash ile SORGULANMAZ: password_hash her çağrıda farklı tuz üretir.
        // Önce kullanıcı çekilir, sonra doğrulanır.
        $user = User::Select(['username' => $username]);

        // Kullanıcı yoksa da aynı maliyette bcrypt işi yapılır:
        // yanıt süresi kullanıcı adının varlığını ele vermesin.
        $valid = empty($user)
            ? (EncryptionModule::PasswordHash($password) === '' && false)
            : EncryptionModule::PasswordVerify($password, $user->storedPassword());

        if (!$valid) {
            Audit::log('auth.login_failed', 'user', $user->id ?? null);
            throw new StandartException(lang('ERR_LOGIN_EXCEPTION'));
        }

        // 2.x'ten kalan sha1(md5()) hash'ini sessizce bcrypt'e yükselt
        if (EncryptionModule::PasswordNeedsRehash($user->storedPassword())) {
            $user->setStoredPassword(EncryptionModule::PasswordHash($password));
            $user->save();
        }

        $remember = !empty($req->post('rememberMe')) && UserModule::rememberDays() > 0;

        // 2FA açıksa oturum henüz AÇILMAZ: ikinci adım POST /app/login/totp
        if ($user->hasTotp()) {
            Session::regenerate();
            Session::set('_2fa_pending', ['user_id' => (int)$user->id, 'remember' => $remember, 'at' => time()]);
            returnSuccessMessage(lang('LOGIN_TOTP_REQUIRED'), ['step' => 'totp']);
        }

        // SetUser() + son giriş bilgisi + isteğe bağlı RememberMe() + Audit
        UserModule::CompleteLogin($user, $remember);

        returnSuccessMessage(lang('SUC_LOGIN'), ['url' => '/']);
    }
}
</code></pre>
                        </div>
                    </div>
                    <!--end::Section 3-->

                    <div class="separator separator-dashed my-8"></div>

                    <!--begin::Section 4-->
                    <div class="mb-10">
                        <h3 class="fw-bold text-body-emphasis mb-4">4. <code>$CurrentUser</code> Nesnesi</h3>
                        <p class="fs-5 text-body mb-4">
                            <code>$CurrentUser</code>, <code>User</code> sınıfının bir örneğidir ve her istekte global olarak kullanılabilir. Giriş yapılmamışsa boş bir <code>User</code> nesnesidir (<code>id = 0</code> veya <code>null</code>).
                        </p>

                        <div class="vnl-code mb-5">
                            <pre class=" mb-0" style="background: transparent; white-space: pre-wrap;"><code>&lt;?php
// Controller veya View içinden kullanım
global $CurrentUser;

if ($CurrentUser->id > 0) {
    echo "Hoş geldiniz, " . $CurrentUser->fullname();
    echo "Yetki ID: " . $CurrentUser->perm_id;
    echo "Kullanıcı adı: " . $CurrentUser->username;
}
</code></pre>
                        </div>

                        <div class="alert alert-info d-flex align-items-center p-4">
                            <i class="fa-solid fa-circle-info fs-2 text-info me-3"></i>
                            <span class="text-body fs-6">Action dosyalarında <code>$CurrentUser</code>'a <code>ActionGlobal\Auth::User()</code> üzerinden erişilir. Controller ve view dosyalarında ise global olarak doğrudan kullanılabilir.</span>
                        </div>
                    </div>
                    <!--end::Section 4-->

                    <div class="separator separator-dashed my-8"></div>

                    <!--begin::Section 5-->
                    <div class="mb-10">
                        <h3 class="fw-bold text-body-emphasis mb-4">5. Public (Giriş Gerektirmeyen) Sayfalar</h3>
                        <p class="fs-5 text-body mb-4">
                            VNL'de bir sayfayı giriş yapmadan erişilebilir yapmanın iki yöntemi vardır:
                        </p>

                        <div class="row g-6 mb-6">
                            <div class="col-md-6">
                                <div class="card border border-dashed h-100">
                                    <div class="card-body p-6">
                                        <div class="d-flex align-items-center mb-4">
                                            <span class="badge badge-circle badge-primary me-3">A</span>
                                            <h5 class="mb-0 fw-bold">Yöntem 1 — <code>login_except_pages</code></h5>
                                        </div>
                                        <p class="fs-6 text-body mb-3">
                                            <code>config/definitions.php</code> dosyasındaki <code>$login_except_pages</code> dizisine sayfa adını ekleyin. Controller hiç yüklenmeden doğrudan erişim verilir.
                                        </p>
                                        <div class="bg-body-tertiary rounded p-3">
                                            <code class="fs-7">private $login_except_pages = [<br>&nbsp;&nbsp;"app", "login", "logout",   // varsayılanlar — silmeyin<br>&nbsp;&nbsp;"register", "hakkimizda"<br>];</code>
                                        </div>
                                        <p class="text-muted fs-7 mt-3 mb-0">Gruplu sayfa için: <code>"docs/getting-started"</code> formatı kullanın.</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card border border-dashed h-100">
                                    <div class="card-body p-6">
                                        <div class="d-flex align-items-center mb-4">
                                            <span class="badge badge-circle badge-primary me-3">B</span>
                                            <h5 class="mb-0 fw-bold">Yöntem 2 — <code>vnl_public_page()</code></h5>
                                        </div>
                                        <p class="fs-6 text-body mb-3">
                                            Listeye yazamayan kod (eklentinin <code>boot.php</code>'si) sayfayı çalışma anında kaydeder. Karar yine denetleyici <b>çalışmadan</b> verilir.
                                        </p>
                                        <div class="bg-body-tertiary rounded p-3">
                                            <code class="fs-7">vnl_public_page('stripe-webhook');</code>
                                        </div>
                                        <p class="text-muted fs-7 mt-3 mb-0"><code>$page_config['public'] = true</code> artık bir şey açmaz (geçiş kipi: <code>VNL_PUBLIC_CAPTURE=true</code>).</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="alert alert-warning d-flex align-items-center p-4">
                            <i class="fa-solid fa-circle-info fs-2 text-warning me-3"></i>
                            <div>
                                <span class="text-body fs-6 fw-bold">İki Yöntem Arasındaki Fark:</span>
                                <span class="text-body fs-6 ms-2">İkisinde de girişsiz istek korumalı denetleyiciye <b>hiç ulaşmaz</b> — denetleyicideki yan etkiler anonim ziyaretçi için gerçekleşmez. Giriş yapılmışsa <code>$CurrentUser</code> açık sayfalarda da dolu gelir. Ayrıntı: <a href="/docs/security">/docs/security</a>.</span>
                            </div>
                        </div>
                    </div>
                    <!--end::Section 5-->

                    <div class="separator separator-dashed my-8"></div>

                    <!--begin::Section 6-->
                    <div class="mb-10">
                        <h3 class="fw-bold text-body-emphasis mb-4">6. <code>UserModule</code> Yardımcı Metotları</h3>

                        <div class="table-responsive border rounded bg-body-tertiary">
                            <table class="table align-middle table-row-dashed fs-6 gy-4 mb-0">
                                <thead>
                                <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0 border-bottom">
                                    <th class="ps-5 min-w-300px">Metot</th>
                                    <th class="min-w-350px">Açıklama</th>
                                </tr>
                                </thead>
                                <tbody class="text-body fw-semibold">
                                <tr>
                                    <td class="ps-5"><code>UserModule::CompleteLogin($user, $remember = false)</code></td>
                                    <td>Parola (ve varsa ikinci adım) doğrulandıktan sonra girişi tamamlar: <code>SetUser()</code>, son giriş bilgisi, istenirse <code>RememberMe()</code> ve <code>auth.login</code> denetim kaydı. Girişi bitirmenin önerilen yolu.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>UserModule::SetUser($user)</code></td>
                                    <td>Oturum kimliğini yeniler ve session'ı doldurur. Tek başına denetim kaydı yazmaz; <code>CompleteLogin()</code> kullanın.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>UserModule::AuthStamp($user)</code></td>
                                    <td>Parola hash'i ile TOTP sırrından türetilen damga (4.0.0). Her istekte oturumdakiyle karşılaştırılır.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>UserModule::RefreshAuthStamp($user)</code></td>
                                    <td>Kendi parolasını ya da 2FA'sını değiştiren kullanıcının <b>bu</b> oturumu açık kalsın diye çağrılır; diğer oturumları yine kapanır (4.0.0).</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>UserModule::SetCurrentUserInfo()</code></td>
                                    <td>Session'daki <code>user_id</code>'yi kullanarak <code>$CurrentUser</code>'ı veritabanından yükler.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>UserModule::CheckCookieAuth()</code></td>
                                    <td>Geçerli bir "beni hatırla" çerezi varsa oturumu açar ve jetonu yeniler. Her istekte otomatik çağrılır.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>UserModule::RememberMe($user)</code></td>
                                    <td>Bu cihaz için kalıcı giriş jetonu üretir ve çereze yazar. Girişten sonra, kullanıcı istediyse çağırın.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>UserModule::ForgetMe()</code></td>
                                    <td>Yalnızca bu cihazın jetonunu iptal eder ve çerezi siler.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>UserModule::ForgetAllDevices($userId)</code></td>
                                    <td>Kullanıcının tüm cihazlardaki jetonlarını iptal eder. Parola değişiminde çağırın.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>UserModule::CheckUser()</code></td>
                                    <td>Session tutarsızsa session'ı temizler ve login'e yönlendirir.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>UserModule::LogOut()</code></td>
                                    <td>Bu cihazın jetonunu iptal eder, tüm çerezleri temizler ve session'ı yok eder.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>UserModule::CurrentIp()</code></td>
                                    <td>Kullanıcının IP adresini döndürür.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>UserModule::CurrentBrowser()</code></td>
                                    <td>Kullanıcının tarayıcı user-agent bilgisini döndürür.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>UserModule::BrowserName()</code></td>
                                    <td>Tarayıcı adını okunabilir biçimde döndürür (Chrome, Firefox vb.).</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>UserModule::CurrentOs()</code></td>
                                    <td>İşletim sistemi adını döndürür (Windows, Mac, Android vb.).</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>UserModule::IsMobile()</code></td>
                                    <td>Mobil cihazdan erişilip erişilmediğini döndürür (<code>bool</code>).</td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <!--end::Section 6-->

                    <div class="separator separator-dashed my-8"></div>

                    <!--begin::Section 7-->
                    <div class="mb-0">
                        <h3 class="fw-bold text-body-emphasis mb-4">7. "Beni Hatırla" — Kalıcı Giriş</h3>
                        <p class="fs-5 text-body mb-5">
                            Oturum çerezi tarayıcı kapanınca biter. Kullanıcının günlerce giriş yapmadan dönebilmesi için VNL ayrı bir jeton çerezi kullanır: <code>{prefix}remember</code>. Jetonun kendisi yalnızca çerezde durur; veritabanında <b>SHA-256 özeti</b> saklanır.
                        </p>

                        <div class="row g-5 mb-6">
                            <div class="col-md-6">
                                <div class="border border-dashed rounded p-5 h-100">
                                    <div class="d-flex align-items-center mb-3">
                                        <i class="fa-solid fa-fingerprint fs-2 text-primary me-3"></i>
                                        <h6 class="mb-0 fw-bold">Jeton, parola değil</h6>
                                    </div>
                                    <span class="text-body fs-6">Çerezde 32 rastgele bayt taşınır. Tablo sızsa bile özetten geçerli çerez üretilemez. Jeton kaba kuvvete kapalı olduğu için bcrypt yerine hızlı ve indekslenebilir bir özet kullanılır.</span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="border border-dashed rounded p-5 h-100">
                                    <div class="d-flex align-items-center mb-3">
                                        <i class="fa-solid fa-rotate fs-2 text-primary me-3"></i>
                                        <h6 class="mb-0 fw-bold">Her kullanımda yenilenir</h6>
                                    </div>
                                    <span class="text-body fs-6">Çerez bir kez kullanılır: doğrulanan jeton silinir, yerine yenisi yazılır. Çalınan bir çerez, meşru kullanıcı siteye girdiği anda geçersiz kalır.</span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="border border-dashed rounded p-5 h-100">
                                    <div class="d-flex align-items-center mb-3">
                                        <i class="fa-solid fa-mobile-screen fs-2 text-primary me-3"></i>
                                        <h6 class="mb-0 fw-bold">Cihaz başına bir satır</h6>
                                    </div>
                                    <span class="text-body fs-6">Her cihaz kendi jetonunu taşır; <code>user_agent</code> ve <code>ip_address</code> kaydedilir. Bir cihazdan çıkış diğerlerini etkilemez.</span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="border border-dashed rounded p-5 h-100">
                                    <div class="d-flex align-items-center mb-3">
                                        <i class="fa-solid fa-lock fs-2 text-primary me-3"></i>
                                        <h6 class="mb-0 fw-bold">Çerez bayrakları</h6>
                                    </div>
                                    <span class="text-body fs-6"><code>httponly</code> (JavaScript okuyamaz), <code>samesite=Lax</code>, HTTPS üzerinden geldiyse <code>secure</code>. Ömür <code>VNL_REMEMBER_DAYS</code> (varsayılan 30 gün; <code>0</code> kapatır); boşta kalma sınırı jetona da uygulanır.</span>
                                </div>
                            </div>
                        </div>

                        <h5 class="fw-bold text-body-emphasis mb-3">Otomatik giriş akışı</h5>
                        <div class="border rounded p-5 bg-body-tertiary mb-6">
                            <div class="d-flex align-items-start mb-4">
                                <span class="badge badge-circle badge-primary me-4 mt-1 flex-shrink-0">1</span>
                                <div><span class="fw-bold text-body">Oturum zaten açıksa hiçbir şey yapılmaz.</span></div>
                            </div>
                            <div class="d-flex align-items-start mb-4">
                                <span class="badge badge-circle badge-primary me-4 mt-1 flex-shrink-0">2</span>
                                <div><span class="fw-bold text-body">Çerezdeki jetonun özeti aranır.</span>
                                    <p class="text-body-secondary fs-6 mb-0 mt-1">Bulunamazsa çerez silinir — eski, iptal edilmiş ya da uydurma demektir.</p></div>
                            </div>
                            <div class="d-flex align-items-start mb-4">
                                <span class="badge badge-circle badge-primary me-4 mt-1 flex-shrink-0">3</span>
                                <div><span class="fw-bold text-body">Süre, boşta kalma ve kullanıcı doğrulanır.</span>
                                    <p class="text-body-secondary fs-6 mb-0 mt-1">Jeton süresi geçmişse, kullanıcı silinmiş ya da pasifse satır ve çerez birlikte temizlenir. Jetonun son kullanımı (<code>used_at</code> — girişli istek dakikada bir yeniler; hiç yenilenmediyse <code>created_at</code>) <code>VNL_SESSION_IDLE</code>'dan eskiyse de jeton reddedilir. Oturum dosyası silinmiş olsa bile (tarayıcı kapandı, GC) gözetimsiz bir tarayıcı 30 gün açık kalmaz. Eski davranış: <code>VNL_IDLE_KEEPS_REMEMBER=true</code> ya da <code>VNL_SESSION_IDLE=0</code>.</p></div>
                            </div>
                            <div class="d-flex align-items-start mb-0">
                                <span class="badge badge-circle badge-success me-4 mt-1 flex-shrink-0">4</span>
                                <div><span class="fw-bold text-body">Oturum açılır, jeton döndürülür.</span>
                                    <p class="text-body-secondary fs-6 mb-0 mt-1"><code>SetUser()</code> çağrılır, eski satır silinir ve <code>RememberMe()</code> ile yeni jeton yazılır.</p></div>
                            </div>
                        </div>

                        <div class="vnl-code mb-6">
                            <pre class="mb-0" style="background: transparent; white-space: pre-wrap;"><code>&lt;?php
// Çıkış — bu cihazın jetonu iptal edilir
UserModule::LogOut();          // ForgetMe() dahili olarak çağrılır

// Yalnızca "beni hatırla"yı iptal et, oturumu kapatma
UserModule::ForgetMe();

// Parola değişimi (4.0.0): auth_stamp değiştiği için diğer oturumlar ve damgalı
// "beni hatırla" jetonları kendiliğinden düşer. Jeton satırlarını hemen silmek
// için ForgetAllDevices; kullanıcının kendi oturumu açık kalsın diye RefreshAuthStamp.
$user->setStoredPassword(EncryptionModule::PasswordHash($yeniParola));
$user->save();
UserModule::ForgetAllDevices($user->id);
UserModule::RefreshAuthStamp($user);

// Bakım: süresi dolmuş jetonları temizle (cron ya da bakım action'ı)
$silinen = RememberToken::purgeExpired();
</code></pre>
                        </div>

                        <div class="alert alert-danger d-flex align-items-center p-5 mb-5">
                            <i class="fa-solid fa-database fs-2hx text-danger me-4"></i>
                            <div class="d-flex flex-column">
                                <h5 class="mb-1 text-danger"><code>tbl_remember_tokens</code> tablosu gerekir</h5>
                                <span class="text-body">Tablo yoksa kalıcı giriş çalışmaz. <code>vnl/migrations/0001_vnl_3_0_upgrade.sql</code> bu tabloyu oluşturur, <code>0008</code> ona <code>auth_stamp</code> sütununu ekler (4.0.0). Her migration'ın PostgreSQL, SQL Server ve SQLite hâli de vardır; <code>php vnl.php migrate --run</code> sürücüye göre seçer — bkz. <a href="/docs/environment" class="fw-bold">Ortam Yapılandırması ve Migrasyonlar</a>.</span>
                            </div>
                        </div>

                        <div class="alert alert-warning d-flex align-items-center p-4">
                            <i class="fa-solid fa-clock-rotate-left fs-2 text-warning me-3"></i>
                            <span class="text-body fs-6"><b>VNL 2.x'ten geçiş:</b> eski sürüm kullanıcı adını ve <b>düz parolayı</b> <code>{prefix}auth</code> çerezine yazıyordu. <code>CheckCookieAuth()</code> bu çerezi gördüğü yerde tarayıcıdan siler; eski çerezle otomatik giriş yapılmaz.</span>
                        </div>
                    </div>
                    <!--end::Section 7-->

                </div>
            </div>

        </div>
    </div>
</div>

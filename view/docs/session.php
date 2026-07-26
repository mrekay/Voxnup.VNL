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
                                    <span class="fw-bold text-body">Cookie ile Otomatik Giriş</span>
                                    <p class="text-body-secondary fs-6 mb-0 mt-1"><code>UserModule::CheckCookieAuth()</code> — Aktif session yoksa fakat <code>{prefix}auth</code> cookie'si mevcutsa, şifrelenmiş cookie çözülerek kullanıcı otomatik giriş yaptırılır. Cookie 30 günden eskiyse silinir.</p>
                                </div>
                            </div>
                            <div class="d-flex align-items-start mb-5">
                                <span class="badge badge-circle badge-primary me-4 mt-1 flex-shrink-0">2</span>
                                <div>
                                    <span class="fw-bold text-body">Kullanıcı Bilgisi Yükleme</span>
                                    <p class="text-body-secondary fs-6 mb-0 mt-1"><code>UserModule::SetCurrentUserInfo()</code> — Session'da <code>user_id</code> varsa veritabanından kullanıcı nesnesi çekilerek global <code>$CurrentUser</code>'a atanır. Kullanıcı bulunamazsa (silinmiş/deaktif) otomatik çıkış yapılır.</p>
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
                                    <span class="fw-bold text-body">Sayfa Erişim Kontrolü (<code>index.php</code>)</span>
                                    <p class="text-body-secondary fs-6 mb-0 mt-1">İstenen sayfa <code>$login_except_pages</code>'de değilse ve <code>$CurrentUser->id</code> sıfır veya negatifse, controller çalıştırılır ama çıktı yakalanır. <code>$page_config['public']</code> tanımlı değilse kullanıcı <code>/login</code>'e yönlendirilir.</p>
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
                                    <td class="ps-5"><code>Session::checkSession()</code></td>
                                    <td>Oturum durumunu döner: <code>1</code> = giriş yapılmış, <code>0</code> = kilitli, <code>-1</code> = giriş yok.</td>
                                </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="vnl-code">
                            <pre class=" mb-0" style="background: transparent; white-space: pre-wrap;"><code>// Oturum açma (action içinde)
Session::set('login', true);
Session::set('user_id', $kullanici->id);

// Veri okuma (herhangi bir yerden)
$kullanici_id = Session::get('user_id');

// Tek anahtar silme
Session::delete('gecici_veri');

// Tam oturum kapatma
Session::destroy();
header('Location: /login');
exit;
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
                                        <li class="mb-0"><code>login_time</code> → <code>time()</code></li>
                                    </ul>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="border border-dashed rounded p-5 h-100">
                                    <h6 class="fw-bold mb-4">Cookie ile Kalıcı Giriş</h6>
                                    <p class="fs-6 text-body mb-0">
                                        "Beni hatırla" seçeneği için kullanıcı bilgileri <code>EncryptionModule::safeEncrypt()</code> ile AES-128-CBC şifrelenerek <code>{prefix}auth</code> cookie'sine yazılır. Geçerlilik süresi <b>30 gündür</b>; her istekte <code>CheckCookieAuth()</code> tarafından kontrol edilir.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="vnl-code">
                            <pre class=" mb-0" style="background: transparent; white-space: pre-wrap;"><code>&lt;?php
// actions/action_login.php örnek kalıbı

class action_login
{
    public function Login($req)
    {
        $username = $req->post('username');
        $password = $req->post('password');
        $remember = $req->post('remember'); // "1" veya boş

        // Kullanıcıyı doğrula (User::Login kendi hash kontrolünü yapar)
        $user = User::Login($username, $password);

        if (empty($user) || $user->id <= 0) {
            throwErrorMessage("Kullanıcı adı veya şifre hatalı.", 401);
        }

        // Session'ı doldur
        UserModule::SetUser($user);

        // Kalıcı giriş istendiyse cookie yaz
        if ($remember == '1') {
            $cookieData = json_encode([
                'username' => $username,
                'password' => $password,  // zaten hash'li
                'time'     => date('Y-m-d H:i:s'),
            ]);
            $encrypted = EncryptionModule::safeEncrypt($cookieData);
            setcookie($cDefinitions->getCookiePrefix() . 'auth', $encrypted, time() + 3600 * 24 * 30, '/');
        }

        returnSuccessMessage("Giriş başarılı.", ['redirect' => '/']);
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
                                            <code>libs/config/config_definitions.php</code> dosyasındaki <code>$login_except_pages</code> dizisine sayfa adını ekleyin. Controller hiç yüklenmeden doğrudan erişim verilir.
                                        </p>
                                        <div class="bg-body-tertiary rounded p-3">
                                            <code class="fs-7">private $login_except_pages = [<br>&nbsp;&nbsp;"login", "register", "hakkimizda"<br>];</code>
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
                                            <h5 class="mb-0 fw-bold">Yöntem 2 — <code>$page_config['public']</code></h5>
                                        </div>
                                        <p class="fs-6 text-body mb-3">
                                            Controller dosyanızda <code>$page_config['public'] = true</code> tanımlayın. <code>index.php</code> controller çıktısını yakalar; <code>public</code> tanımlıysa ekrana basar, yoksa login'e yönlendirir.
                                        </p>
                                        <div class="bg-body-tertiary rounded p-3">
                                            <code class="fs-7">$page_config = [<br>&nbsp;&nbsp;'public' => true,<br>&nbsp;&nbsp;'leftsidebar' => false,<br>];</code>
                                        </div>
                                        <p class="text-muted fs-7 mt-3 mb-0">Kullanıcı giriş yapmışsa <code>$CurrentUser</code> bu sayfada da dolu gelir.</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="alert alert-warning d-flex align-items-center p-4">
                            <i class="fa-solid fa-circle-info fs-2 text-warning me-3"></i>
                            <div>
                                <span class="text-body fs-6 fw-bold">İki Yöntem Arasındaki Fark:</span>
                                <span class="text-body fs-6 ms-2"><code>login_except_pages</code> controller çalıştırılmadan erişim verir. <code>public=true</code> ise controller'ı çalıştırır ve çıktıyı yakalar — bu yöntem <code>$CurrentUser</code>'ın dolu gelmesi gereken yarı-korumalı sayfalar için uygundur.</span>
                            </div>
                        </div>
                    </div>
                    <!--end::Section 5-->

                    <div class="separator separator-dashed my-8"></div>

                    <!--begin::Section 6-->
                    <div class="mb-0">
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
                                    <td class="ps-5"><code>UserModule::SetUser($user)</code></td>
                                    <td>Başarılı girişten sonra session'ı doldurur.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>UserModule::SetCurrentUserInfo()</code></td>
                                    <td>Session'daki <code>user_id</code>'yi kullanarak <code>$CurrentUser</code>'ı veritabanından yükler.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>UserModule::CheckCookieAuth()</code></td>
                                    <td>Şifreli cookie varsa otomatik giriş sağlar.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>UserModule::CheckUser()</code></td>
                                    <td>Session tutarsızsa session'ı temizler ve login'e yönlendirir.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>UserModule::LogOut()</code></td>
                                    <td>Tüm cookie'leri temizler ve session'ı yok eder.</td>
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

                </div>
            </div>

        </div>
    </div>
</div>

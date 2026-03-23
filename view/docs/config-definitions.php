<div class="d-flex flex-column flex-column-fluid">
    <div id="kt_app_content" class="app-content flex-column-fluid">
        <div id="kt_app_content_container" class="app-container container-xxl">

            <div class="text-center mb-15 mt-10">
                <h1 class="fs-2hx fw-bold mb-5 text-gray-900">Config Definitions</h1>
                <div class="fs-5 text-muted fw-semibold w-lg-75 mx-auto">
                    <code>libs/config/config_definitions.php</code> dosyası, VNL uygulamasının tüm temel yapılandırmasını barındırır. Her yeni projede ilk doldurulması gereken dosyadır.
                </div>
            </div>

            <div class="alert bg-light-warning border border-warning d-flex align-items-center p-5 mb-10">
                <i class="ki-duotone ki-shield fs-2hx text-warning me-4"><span class="path1"></span><span class="path2"></span></i>
                <div class="d-flex flex-column">
                    <h5 class="mb-1 text-dark">Güvenlik Uyarısı</h5>
                    <span class="text-gray-700"><code>$salt_text</code> ve veritabanı bilgilerini asla kod deposuna (<code>git</code>) gerçek değerleriyle commit etmeyin. Üretim ortamı için environment variable veya ayrı bir ignored config dosyası kullanmayı değerlendirin.</span>
                </div>
            </div>

            <div class="card shadow-sm border-0 mb-10">
                <div class="card-body p-10">

                    <!--begin::Section 1 - testMode-->
                    <div class="mb-10">
                        <h3 class="fw-bolder text-dark mb-4">1. Test Modu (<code>$testMode</code>)</h3>
                        <p class="fs-5 text-gray-700 mb-4">
                            <code>ConfigDefinitions</code> sınıfı iki bağımsız yapılandırma bloğuna sahiptir: geliştirme (<code>true</code>) ve üretim (<code>false</code>). <code>$testMode</code> değeri hangi bloğun aktif olacağını belirler.
                        </p>

                        <div class="row g-5 mb-5">
                            <div class="col-md-6">
                                <div class="border border-dashed border-success rounded p-5 h-100">
                                    <div class="d-flex align-items-center mb-3">
                                        <span class="badge badge-light-success me-3">true</span>
                                        <h6 class="mb-0 fw-bold">Geliştirme Ortamı</h6>
                                    </div>
                                    <ul class="list-unstyled fs-6 text-gray-700 mb-0">
                                        <li class="mb-2"><i class="ki-duotone ki-right fs-4 text-success me-2"></i>Genellikle <code>localhost</code> veritabanı kullanılır</li>
                                        <li class="mb-2"><i class="ki-duotone ki-right fs-4 text-success me-2"></i>Hata mesajları ekrana yansır</li>
                                        <li class="mb-0"><i class="ki-duotone ki-right fs-4 text-success me-2"></i>Test kullanıcı verisi güvenli</li>
                                    </ul>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="border border-dashed border-primary rounded p-5 h-100">
                                    <div class="d-flex align-items-center mb-3">
                                        <span class="badge badge-light-primary me-3">false</span>
                                        <h6 class="mb-0 fw-bold">Üretim Ortamı</h6>
                                    </div>
                                    <ul class="list-unstyled fs-6 text-gray-700 mb-0">
                                        <li class="mb-2"><i class="ki-duotone ki-right fs-4 text-primary me-2"></i>Canlı veritabanı bilgileri doldurulur</li>
                                        <li class="mb-2"><i class="ki-duotone ki-right fs-4 text-primary me-2"></i>Gerçek domain ve cookie ayarları</li>
                                        <li class="mb-0"><i class="ki-duotone ki-right fs-4 text-primary me-2"></i>Deploy sonrası aktif olması gereken mod</li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <div class="bg-dark rounded p-5">
                            <pre class="text-white fs-6 font-monospace mb-0" style="background: transparent; white-space: pre-wrap;"><code>private $testMode = false; // true = geliştirme, false = üretim

// Constructor içinde bu anahtar seçimi yapar:
switch ($this->testMode) {
    case true:  /* geliştirme ayarları */ break;
    case false: /* üretim ayarları */     break;
}
</code></pre>
                        </div>
                    </div>
                    <!--end::Section 1-->

                    <div class="separator separator-dashed my-8"></div>

                    <!--begin::Section 2 - Uygulama-->
                    <div class="mb-10">
                        <h3 class="fw-bolder text-dark mb-4">2. Uygulama Bilgileri</h3>

                        <div class="table-responsive border rounded bg-light mb-5">
                            <table class="table align-middle table-row-dashed fs-6 gy-4 mb-0">
                                <thead>
                                <tr class="text-start text-muted fw-bolder fs-7 text-uppercase gs-0 border-bottom">
                                    <th class="ps-5 min-w-200px">Alan</th>
                                    <th class="min-w-120px">Varsayılan</th>
                                    <th class="min-w-350px">Açıklama</th>
                                </tr>
                                </thead>
                                <tbody class="text-gray-800 fw-semibold">
                                <tr>
                                    <td class="ps-5"><code>$app_name</code></td>
                                    <td><code>"VNL"</code></td>
                                    <td>Uygulamanın adı. Toolbar breadcrumb'da ve sayfa başlıklarında <code>$cDefinitions->appName()</code> ile kullanılır.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>$app_logo</code></td>
                                    <td><code>"VNL.png"</code></td>
                                    <td>Logo dosyasının adı. <code>$app_logo_path</code> ile birleştirilerek tam yola ulaşılır.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>$app_logo_path</code></td>
                                    <td><code>"/assets/media/logos/"</code></td>
                                    <td>Logo dosyasının bulunduğu dizin. <code>$cDefinitions->appLogo()</code> bu iki alanı birleştirir.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>$version</code></td>
                                    <td><code>"2.2.0"</code></td>
                                    <td>Uygulamanızın sürüm numarası. <code>$cDefinitions->version()</code> ile okunur; sekme başlığı gibi yerlerde kullanılabilir.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>$vnl_version</code></td>
                                    <td><code>"2.2.0"</code></td>
                                    <td>VNL framework'ünün sürüm numarasıdır. <b>Değiştirmeyin.</b> Framework takibi için ayrı tutulmuştur.</td>
                                </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="alert bg-light-info border border-info d-flex align-items-center p-4 mb-4">
                            <i class="ki-duotone ki-information-5 fs-2 text-info me-3"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
                            <span class="text-gray-800 fs-6"><code>$app_name</code>, şirket adı değil <b>uygulamanın adıdır</b>. Örneğin "Stok Takip", "Satış Portalı", "CRM" gibi uygulamanın işlevini yansıtan bir isim olmalıdır.</span>
                        </div>

                        <div class="bg-dark rounded p-5">
                            <pre class="text-white fs-6 font-monospace mb-0" style="background: transparent; white-space: pre-wrap;"><code>private $app_name      = "Stok Takip";   // Uygulama adı — şirket adı değil
private $app_logo      = "logo.png";
private $app_logo_path = "/assets/media/logos/";
private $version       = "1.0.0";
// $vnl_version değiştirmeyin
</code></pre>
                        </div>
                    </div>
                    <!--end::Section 2-->

                    <div class="separator separator-dashed my-8"></div>

                    <!--begin::Section 3 - URL-->
                    <div class="mb-10">
                        <h3 class="fw-bolder text-dark mb-4">3. URL Yapılandırması</h3>

                        <div class="table-responsive border rounded bg-light mb-5">
                            <table class="table align-middle table-row-dashed fs-6 gy-4 mb-0">
                                <thead>
                                <tr class="text-start text-muted fw-bolder fs-7 text-uppercase gs-0 border-bottom">
                                    <th class="ps-5 min-w-200px">Alan</th>
                                    <th class="min-w-200px">Örnek Değer</th>
                                    <th class="min-w-350px">Açıklama</th>
                                </tr>
                                </thead>
                                <tbody class="text-gray-800 fw-semibold">
                                <tr>
                                    <td class="ps-5"><code>$site_url</code></td>
                                    <td><code>"myapp.com"</code></td>
                                    <td>Sitenin domain adresi, <code>http://</code> veya <code>https://</code> olmadan. Oturum açma yönlendirmelerinde <code>getSiteUrl()</code> ile <code>SITE_HTTP</code> protokolü eklenerek kullanılır.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>$master_site_url</code></td>
                                    <td><code>"myapp.com"</code></td>
                                    <td>Uygulamanın ana domain'i. Alt alan adlarının (subdomain) olduğu çok kiracılı yapılarda merkezi domain'i tanımlar.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>$assets_url</code></td>
                                    <td><code>"/assets"</code></td>
                                    <td>CSS/JS/medya dosyalarının kök yolu. <code>$cDefinitions->getAssetsUrl()</code> ile okunur. CDN kullanıyorsanız buraya CDN URL'ini yazabilirsiniz.</td>
                                </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="alert bg-light-info border border-info d-flex align-items-center p-4">
                            <i class="ki-duotone ki-information-5 fs-2 text-info me-3"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
                            <span class="text-gray-800 fs-6"><code>getSiteUrl()</code>, isteğin HTTPS üzerinden gelip gelmediğini otomatik algılar ve <code>SITE_HTTP</code> sabitini buna göre ayarlar (<code>http://</code> veya <code>https://</code>). <code>$site_url</code>'e protokol yazmayın.</span>
                        </div>
                    </div>
                    <!--end::Section 3-->

                    <div class="separator separator-dashed my-8"></div>

                    <!--begin::Section 4 - DB-->
                    <div class="mb-10">
                        <h3 class="fw-bolder text-dark mb-4">4. Veritabanı Yapılandırması (<code>$db_info</code>)</h3>
                        <p class="fs-5 text-gray-700 mb-4">
                            VNL, <b>Illuminate Database (Eloquent/Capsule)</b> kullanır. <code>$db_info</code> dizisi Capsule bağlantısını yapılandırır.
                        </p>

                        <div class="table-responsive border rounded bg-light mb-5">
                            <table class="table align-middle table-row-dashed fs-6 gy-4 mb-0">
                                <thead>
                                <tr class="text-start text-muted fw-bolder fs-7 text-uppercase gs-0 border-bottom">
                                    <th class="ps-5 min-w-180px">Anahtar</th>
                                    <th class="min-w-200px">Örnek Değer</th>
                                    <th class="min-w-300px">Açıklama</th>
                                </tr>
                                </thead>
                                <tbody class="text-gray-800 fw-semibold">
                                <tr>
                                    <td class="ps-5"><code>driver</code></td>
                                    <td><code>"mysql"</code></td>
                                    <td>Veritabanı sürücüsü. <code>mysql</code>, <code>pgsql</code>, <code>sqlsrv</code> desteklenir.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>host</code></td>
                                    <td><code>"localhost"</code></td>
                                    <td>Veritabanı sunucu adresi.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>database</code></td>
                                    <td><code>"myapp_db"</code></td>
                                    <td>Kullanılacak veritabanı adı.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>username</code></td>
                                    <td><code>"root"</code></td>
                                    <td>Veritabanı kullanıcı adı.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>password</code></td>
                                    <td><code>"şifre"</code></td>
                                    <td>Veritabanı şifresi.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>charset</code></td>
                                    <td><code>"utf8mb4"</code></td>
                                    <td>Bağlantı karakter seti. Emoji desteği için <code>utf8mb4</code> önerilir.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>collation</code></td>
                                    <td><code>"utf8mb4_general_ci"</code></td>
                                    <td>Karakter sıralama kuralı. <code>charset</code> ile uyumlu olmalıdır.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>prefix</code></td>
                                    <td><code>""</code></td>
                                    <td>Tablo adı öneki. Örneğin <code>"app_"</code> yazılırsa <code>users</code> → <code>app_users</code> olur.</td>
                                </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="bg-dark rounded p-5">
                            <pre class="text-white fs-6 font-monospace mb-0" style="background: transparent; white-space: pre-wrap;"><code>// Üretim (case false) bloğu örneği
$this->db_info = [
    'driver'    => 'mysql',
    'host'      => 'localhost',
    'database'  => 'myapp_production',
    'username'  => 'db_user',
    'password'  => 'güçlü_şifre',
    'charset'   => 'utf8mb4',
    'collation' => 'utf8mb4_general_ci',
    'prefix'    => '',
];
</code></pre>
                        </div>
                    </div>
                    <!--end::Section 4-->

                    <div class="separator separator-dashed my-8"></div>

                    <!--begin::Section 5 - Güvenlik-->
                    <div class="mb-10">
                        <h3 class="fw-bolder text-dark mb-4">5. Güvenlik ve Oturum Ayarları</h3>

                        <div class="table-responsive border rounded bg-light mb-5">
                            <table class="table align-middle table-row-dashed fs-6 gy-4 mb-0">
                                <thead>
                                <tr class="text-start text-muted fw-bolder fs-7 text-uppercase gs-0 border-bottom">
                                    <th class="ps-5 min-w-200px">Alan</th>
                                    <th class="min-w-200px">Örnek Değer</th>
                                    <th class="min-w-350px">Açıklama</th>
                                </tr>
                                </thead>
                                <tbody class="text-gray-800 fw-semibold">
                                <tr>
                                    <td class="ps-5"><code>$cookie_prefix</code></td>
                                    <td><code>"myapp"</code></td>
                                    <td>Oturum cookie'lerinin öneki. Tek sunucuda birden fazla VNL uygulaması çalışıyorsa her birine benzersiz önek verin; aksi hâlde cookie'ler çakışır.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>$salt_text</code></td>
                                    <td><code>"abc?123!@#"</code></td>
                                    <td>Şifre ve hash işlemlerinde kullanılan gizli metin. En az 12 karakter, büyük/küçük harf + özel karakter içermesi önerilir. <b>Değiştirirseniz mevcut tüm şifreler geçersiz kalır.</b></td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>$master_db</code></td>
                                    <td><code>""</code></td>
                                    <td>Çok kiracılı yapılarda merkezi veritabanı adı. Tek veritabanı kullanan uygulamalarda boş bırakılır.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>$product_id</code></td>
                                    <td><code>0</code></td>
                                    <td>Ürün veya uygulama tanımlayıcısı. Lisanslama veya çok kiracılı sistemlerde kullanılabilir.</td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <!--end::Section 5-->

                    <div class="separator separator-dashed my-8"></div>

                    <!--begin::Section 6 - login_except_pages-->
                    <div class="mb-10">
                        <h3 class="fw-bolder text-dark mb-4">6. Erişim İstisnaları (<code>$login_except_pages</code>)</h3>
                        <p class="fs-5 text-gray-700 mb-4">
                            Giriş yapılmadan erişilmesine izin verilen sayfa listesidir. <code>index.php</code> her istekte bu listeyi kontrol eder. İki format desteklenir:
                        </p>

                        <div class="row g-5 mb-5">
                            <div class="col-md-6">
                                <div class="border border-dashed rounded p-5 h-100">
                                    <h6 class="fw-bold mb-3">Tekil Sayfa</h6>
                                    <p class="fs-6 text-gray-700 mb-2"><code>route(0)</code> değeri eşleştirilir.</p>
                                    <code class="fs-7">"login"</code> &nbsp;→&nbsp; <code class="fs-7">site.com/login</code>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="border border-dashed rounded p-5 h-100">
                                    <h6 class="fw-bold mb-3">Gruplu Sayfa</h6>
                                    <p class="fs-6 text-gray-700 mb-2"><code>route(0)/route(1)</code> birleşimi eşleştirilir.</p>
                                    <code class="fs-7">"docs/getting-started"</code> &nbsp;→&nbsp; <code class="fs-7">site.com/docs/getting-started</code>
                                </div>
                            </div>
                        </div>

                        <div class="bg-dark rounded p-5 mb-4">
                            <pre class="text-white fs-6 font-monospace mb-0" style="background: transparent; white-space: pre-wrap;"><code>private $login_except_pages = [
    // Sistem sayfaları (değiştirmeyin)
    "app",          // AJAX/Action endpoint
    "login",        // Giriş sayfası
    "logout",       // Çıkış
    "register",     // Kayıt sayfası
    "index",        // Ana sayfa (giriş öncesi)

    // Özel public sayfalar (buraya ekleyin)
    "hakkimizda",                   // site.com/hakkimizda
    "docs/getting-started",         // site.com/docs/getting-started
    "blog/detay",                   // site.com/blog/detay
];
</code></pre>
                        </div>

                        <div class="alert bg-light-info border border-info d-flex align-items-center p-4">
                            <i class="ki-duotone ki-information-5 fs-2 text-info me-3"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
                            <span class="text-gray-800 fs-6">Sadece belirli bir controller dosyasını public yapmak istiyorsanız <code>$login_except_pages</code> yerine o controller'da <code>$page_config['public'] = true</code> kullanmak daha temiz bir yaklaşımdır. İkisi de çalışır ama <code>public=true</code> yapılandırmayı controller'da localize eder.</span>
                        </div>
                    </div>
                    <!--end::Section 6-->

                    <div class="separator separator-dashed my-8"></div>

                    <!--begin::Section 7 - Public Getter Metotları-->
                    <div class="mb-0">
                        <h3 class="fw-bolder text-dark mb-4">7. Public Getter Metotları</h3>
                        <p class="fs-5 text-gray-700 mb-4">
                            <code>ConfigDefinitions</code> sınıfının private alanlarına <code>$cDefinitions</code> global nesnesi üzerinden aşağıdaki metotlarla erişilir. Bu nesne sistem başlangıcında otomatik oluşturulur.
                        </p>

                        <div class="table-responsive border rounded bg-light">
                            <table class="table align-middle table-row-dashed fs-6 gy-4 mb-0">
                                <thead>
                                <tr class="text-start text-muted fw-bolder fs-7 text-uppercase gs-0 border-bottom">
                                    <th class="ps-5 min-w-260px">Metot</th>
                                    <th class="min-w-120px">Dönen Tip</th>
                                    <th class="min-w-300px">Açıklama</th>
                                </tr>
                                </thead>
                                <tbody class="text-gray-800 fw-semibold">
                                <tr>
                                    <td class="ps-5"><code>$cDefinitions->appName()</code></td>
                                    <td><code>string</code></td>
                                    <td>Uygulama adını döndürür.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>$cDefinitions->appLogo()</code></td>
                                    <td><code>string</code></td>
                                    <td>Logo'nun tam yolunu döndürür (<code>path + dosya adı</code>).</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>$cDefinitions->appLogoPath()</code></td>
                                    <td><code>string</code></td>
                                    <td>Sadece logo dizin yolunu döndürür.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>$cDefinitions->version()</code></td>
                                    <td><code>string</code></td>
                                    <td>Uygulama sürüm numarasını döndürür.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>$cDefinitions->getSiteUrl()</code></td>
                                    <td><code>string</code></td>
                                    <td>Protokol dahil tam site URL'ini döndürür (ör: <code>https://myapp.com</code>).</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>$cDefinitions->getMasterSiteUrl()</code></td>
                                    <td><code>string</code></td>
                                    <td>Ana domain URL'ini döndürür.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>$cDefinitions->getAssetsUrl()</code></td>
                                    <td><code>string</code></td>
                                    <td>Asset kök yolunu döndürür (ör: <code>/assets</code>).</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>$cDefinitions->getDBInfo()</code></td>
                                    <td><code>array</code></td>
                                    <td>Veritabanı bağlantı bilgilerini dizi olarak döndürür.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>$cDefinitions->getCookiePrefix()</code></td>
                                    <td><code>string</code></td>
                                    <td>Cookie önekini döndürür.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>$cDefinitions->getSaltText()</code></td>
                                    <td><code>string</code></td>
                                    <td>Hash salt metnini döndürür.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>$cDefinitions->getLoginExceptPages()</code></td>
                                    <td><code>array</code></td>
                                    <td>Giriş gerektirmeyen sayfalar listesini döndürür.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>$cDefinitions->getProductId()</code></td>
                                    <td><code>string</code></td>
                                    <td>Ürün/uygulama ID'sini döndürür.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>$cDefinitions->getMasterDb()</code></td>
                                    <td><code>string</code></td>
                                    <td>Ana veritabanı adını döndürür.</td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <!--end::Section 7-->

                </div>
            </div>

        </div>
    </div>
</div>

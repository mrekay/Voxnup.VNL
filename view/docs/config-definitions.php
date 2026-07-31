<div class="d-flex flex-column flex-column-fluid">
    <div id="kt_app_content" class="app-content flex-column-fluid">
        <div id="kt_app_content_container" class="app-container container-xxl">

            <div class="vnl-page-hero mb-12 mt-6">
                <div class="vnl-overline mb-3">// docs/config-definitions</div>
                <h1 class="fs-2hx fw-bold mb-4 text-body-emphasis">Config Definitions</h1>
                <div class="vnl-lead"><code>config/definitions.php</code> dosyası, VNL uygulamasının tüm temel yapılandırmasını barındırır. Her yeni projede ilk doldurulması gereken dosyadır.</div>
            </div>

            <div class="alert alert-warning d-flex align-items-center p-5 mb-10">
                <i class="fa-solid fa-shield fs-2hx text-warning me-4"></i>
                <div class="d-flex flex-column">
                    <h5 class="mb-1 text-body-emphasis">Güvenlik Uyarısı</h5>
                    <span class="text-body">VNL 3.0'dan itibaren <code>$salt_text</code> ve veritabanı bilgileri <code>.env</code> dosyasından okunur; bu dosya <code>.gitignore</code>'dadır ve web sunucusu tarafından servis edilmez. Bu değerleri <code>config/definitions.php</code> içine geri yazmayın.</span>
                </div>
            </div>

            <div class="card mb-10">
                <div class="card-body p-10">

                    <!--begin::Section 1 - .env-->
                    <div class="mb-10">
                        <h3 class="fw-bold text-body-emphasis mb-4">1. Ortam Yapılandırması (<code>.env</code>)</h3>
                        <p class="fs-5 text-body mb-4">
                            Ortama göre değişen ve gizli olan her şey proje kökündeki <code>.env</code> dosyasından okunur. <code>ConfigDefinitions</code> bu değerleri <code>env()</code> ile alır; sınıfın kendisinde gizli bilgi bulunmaz.
                        </p>

                        <div class="alert alert-info d-flex align-items-start p-4 mb-5">
                            <i class="fa-solid fa-circle-info fs-2 text-info me-3"></i>
                            <span class="text-body fs-6">
                                VNL 2.x'teki <code>$testMode</code> anahtarı <b>kaldırıldı</b>. İki sabit blok arasında seçim yapmak yerine her ortam kendi <code>.env</code> dosyasını taşır; böylece geliştirme ve üretim değerleri aynı dosyada yan yana durmaz.
                            </span>
                        </div>

                        <div class="row g-5 mb-5">
                            <div class="col-md-4">
                                <div class="border border-dashed rounded p-5 h-100 text-center">
                                    <i class="fa-solid fa-file-shield fs-2x text-success mb-3 d-block"></i>
                                    <h6 class="fw-bold mb-2">.env</h6>
                                    <span class="text-muted fs-7">Gerçek değerler. <code>.gitignore</code>'dadır, commit edilmez, web sunucusu servis etmez.</span>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="border border-dashed rounded p-5 h-100 text-center">
                                    <i class="fa-solid fa-file-lines fs-2x text-primary mb-3 d-block"></i>
                                    <h6 class="fw-bold mb-2">.env.example</h6>
                                    <span class="text-muted fs-7">Anahtar listesi ve açıklamalar. Commit edilir; kurulumda <code>.env</code> olarak kopyalanır.</span>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="border border-dashed rounded p-5 h-100 text-center">
                                    <i class="fa-solid fa-server fs-2x text-warning mb-3 d-block"></i>
                                    <h6 class="fw-bold mb-2">Sunucu değişkeni</h6>
                                    <span class="text-muted fs-7">Sunucuda tanımlı gerçek ortam değişkeni <code>.env</code>'i ezer — konteyner ve CI için.</span>
                                </div>
                            </div>
                        </div>

                        <div class="vnl-code mb-5">
                            <pre class=" mb-0" style="background: transparent; white-space: pre-wrap;"><code># .env — proje kökünde
APP_URL=ornek.com
APP_SALT=uzun-rastgele-gizli-deger
DB_HOST=localhost
DB_DATABASE=ornek_db
DB_USERNAME=ornek_user
DB_PASSWORD="p#ss w0rd"      # boşluk veya # içeriyorsa tırnak kullanın

# Kodda okunuşu
$host = env('DB_HOST', 'localhost');   // ikinci parametre varsayılan
$debug = env('APP_DEBUG', false);      // true/false/null/empty çevrilir
</code></pre>
                        </div>

                        <div class="alert alert-warning d-flex align-items-start p-4">
                            <i class="fa-solid fa-triangle-exclamation fs-2 text-warning me-3"></i>
                            <span class="text-body fs-6">
                                Kurulumda <code>.env.example</code>'ı <code>.env</code> olarak kopyalayıp doldurun. <code>APP_ENCRYPTION_KEY</code> her kurulumda farklı ve gizli olmalıdır &mdash; <code>safeEncrypt()</code> onu kullanır ve değiştirmek daha önce şifrelenmiş her veriyi çözülemez hâle getirir. <code>APP_SALT</code> ise <b>ayrılmış bir alandır: VNL çekirdeği onu hiçbir yerde okumaz</b> (parolalar bcrypt'in kendi tuzuyla, &laquo;beni hatırla&raquo; jetonları SHA-256 ile korunur); kendi imzalama ihtiyacınız için kullanabilirsiniz.
                            </span>
                        </div>
                    </div>
                    <!--end::Section 1-->

                    <div class="separator separator-dashed my-8"></div>

                    <!--begin::Section 2 - Uygulama-->
                    <div class="mb-10">
                        <h3 class="fw-bold text-body-emphasis mb-4">2. Uygulama Bilgileri</h3>

                        <div class="table-responsive border rounded bg-body-tertiary mb-5">
                            <table class="table align-middle table-row-dashed fs-6 gy-4 mb-0">
                                <thead>
                                <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0 border-bottom">
                                    <th class="ps-5 min-w-200px">Alan</th>
                                    <th class="min-w-120px">Varsayılan</th>
                                    <th class="min-w-350px">Açıklama</th>
                                </tr>
                                </thead>
                                <tbody class="text-body fw-semibold">
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
                                    <td><code>"3.0.0"</code></td>
                                    <td><b>Projenizin</b> sürüm numarası. <code>$cDefinitions->version()</code> ile okunur; sekme başlığı gibi yerlerde kullanılabilir. Çekirdeğin sürümüyle karıştırmayın.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>$version</code> <span class="text-muted">(çekirdek)</span></td>
                                    <td><code>vnl/VERSION</code></td>
                                    <td>VNL 3.0'a kadar burada bir <code>$vnl_version</code> alanı vardı ve yanında &laquo;değiştirmeyin&raquo; yazıyordu &mdash; proje dosyasında duran bir şeyi proje değiştirmesin diye rica etmek gerekiyordu. Artık kaynağı <code>vnl/VERSION</code>; okumak için yine <code>$cDefinitions-&gt;vnl_version()</code>.</td>
                                </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="alert alert-info d-flex align-items-center p-4 mb-4">
                            <i class="fa-solid fa-circle-info fs-2 text-info me-3"></i>
                            <span class="text-body fs-6"><code>$app_name</code>, şirket adı değil <b>uygulamanın adıdır</b>. Örneğin "Stok Takip", "Satış Portalı", "CRM" gibi uygulamanın işlevini yansıtan bir isim olmalıdır.</span>
                        </div>

                        <div class="vnl-code">
                            <pre class=" mb-0" style="background: transparent; white-space: pre-wrap;"><code>private $app_name      = "Stok Takip";   // Uygulama adı — şirket adı değil
private $app_logo      = "logo.png";
private $app_logo_path = "/assets/media/logos/";
private $version       = "1.0.0";   // projenin sürümü; çekirdeğinki vnl/VERSION'da
</code></pre>
                        </div>
                    </div>
                    <!--end::Section 2-->

                    <div class="separator separator-dashed my-8"></div>

                    <!--begin::Section 3 - URL-->
                    <div class="mb-10">
                        <h3 class="fw-bold text-body-emphasis mb-4">3. URL Yapılandırması</h3>

                        <div class="table-responsive border rounded bg-body-tertiary mb-5">
                            <table class="table align-middle table-row-dashed fs-6 gy-4 mb-0">
                                <thead>
                                <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0 border-bottom">
                                    <th class="ps-5 min-w-200px">Alan</th>
                                    <th class="min-w-200px">Örnek Değer</th>
                                    <th class="min-w-350px">Açıklama</th>
                                </tr>
                                </thead>
                                <tbody class="text-body fw-semibold">
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

                        <div class="alert alert-info d-flex align-items-center p-4">
                            <i class="fa-solid fa-circle-info fs-2 text-info me-3"></i>
                            <span class="text-body fs-6"><code>getSiteUrl()</code>, isteğin HTTPS üzerinden gelip gelmediğini otomatik algılar ve <code>SITE_HTTP</code> sabitini buna göre ayarlar (<code>http://</code> veya <code>https://</code>). <code>$site_url</code>'e protokol yazmayın.</span>
                        </div>
                    </div>
                    <!--end::Section 3-->

                    <div class="separator separator-dashed my-8"></div>

                    <!--begin::Section 4 - DB-->
                    <div class="mb-10">
                        <h3 class="fw-bold text-body-emphasis mb-4">4. Veritabanı Yapılandırması (<code>$db_info</code>)</h3>
                        <p class="fs-5 text-body mb-4">
                            VNL, <b>Illuminate Database (Eloquent/Capsule)</b> kullanır. <code>$db_info</code> dizisi Capsule bağlantısını yapılandırır.
                        </p>

                        <div class="table-responsive border rounded bg-body-tertiary mb-5">
                            <table class="table align-middle table-row-dashed fs-6 gy-4 mb-0">
                                <thead>
                                <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0 border-bottom">
                                    <th class="ps-5 min-w-180px">Anahtar</th>
                                    <th class="min-w-200px">Örnek Değer</th>
                                    <th class="min-w-300px">Açıklama</th>
                                </tr>
                                </thead>
                                <tbody class="text-body fw-semibold">
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

                        <div class="vnl-code">
                            <pre class=" mb-0" style="background: transparent; white-space: pre-wrap;"><code>// Üretim (case false) bloğu örneği
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
                        <h3 class="fw-bold text-body-emphasis mb-4">5. Güvenlik ve Oturum Ayarları</h3>

                        <div class="table-responsive border rounded bg-body-tertiary mb-5">
                            <table class="table align-middle table-row-dashed fs-6 gy-4 mb-0">
                                <thead>
                                <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0 border-bottom">
                                    <th class="ps-5 min-w-200px">Alan</th>
                                    <th class="min-w-200px">Örnek Değer</th>
                                    <th class="min-w-350px">Açıklama</th>
                                </tr>
                                </thead>
                                <tbody class="text-body fw-semibold">
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
                        <h3 class="fw-bold text-body-emphasis mb-4">6. Erişim İstisnaları (<code>$login_except_pages</code>)</h3>
                        <p class="fs-5 text-body mb-4">
                            Giriş yapılmadan erişilmesine izin verilen sayfa listesidir. <code>vnl/kernel.php</code> her istekte bu listeyi kontrol eder. İki format desteklenir:
                        </p>

                        <div class="row g-5 mb-5">
                            <div class="col-md-6">
                                <div class="border border-dashed rounded p-5 h-100">
                                    <h6 class="fw-bold mb-3">Tekil Sayfa</h6>
                                    <p class="fs-6 text-body mb-2"><code>route(0)</code> değeri eşleştirilir.</p>
                                    <code class="fs-7">"login"</code> &nbsp;→&nbsp; <code class="fs-7">site.com/login</code>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="border border-dashed rounded p-5 h-100">
                                    <h6 class="fw-bold mb-3">Gruplu Sayfa</h6>
                                    <p class="fs-6 text-body mb-2"><code>route(0)/route(1)</code> birleşimi eşleştirilir.</p>
                                    <code class="fs-7">"docs/getting-started"</code> &nbsp;→&nbsp; <code class="fs-7">site.com/docs/getting-started</code>
                                </div>
                            </div>
                        </div>

                        <div class="vnl-code mb-4">
                            <pre class=" mb-0" style="background: transparent; white-space: pre-wrap;"><code>private $login_except_pages = [
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

                        <div class="alert alert-info d-flex align-items-center p-4">
                            <i class="fa-solid fa-circle-info fs-2 text-info me-3"></i>
                            <span class="text-body fs-6">Sadece belirli bir controller dosyasını public yapmak istiyorsanız <code>$login_except_pages</code> yerine o controller'da <code>$page_config['public'] = true</code> kullanmak daha temiz bir yaklaşımdır. İkisi de çalışır ama <code>public=true</code> yapılandırmayı controller'da localize eder.</span>
                        </div>
                    </div>
                    <!--end::Section 6-->

                    <div class="separator separator-dashed my-8"></div>

                    <!--begin::Section 7 - Public Getter Metotları-->
                    <div class="mb-0">
                        <h3 class="fw-bold text-body-emphasis mb-4">7. Public Getter Metotları</h3>
                        <p class="fs-5 text-body mb-4">
                            <code>ConfigDefinitions</code> sınıfının private alanlarına <code>$cDefinitions</code> global nesnesi üzerinden aşağıdaki metotlarla erişilir. Bu nesne sistem başlangıcında otomatik oluşturulur.
                        </p>

                        <div class="table-responsive border rounded bg-body-tertiary">
                            <table class="table align-middle table-row-dashed fs-6 gy-4 mb-0">
                                <thead>
                                <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0 border-bottom">
                                    <th class="ps-5 min-w-260px">Metot</th>
                                    <th class="min-w-120px">Dönen Tip</th>
                                    <th class="min-w-300px">Açıklama</th>
                                </tr>
                                </thead>
                                <tbody class="text-body fw-semibold">
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

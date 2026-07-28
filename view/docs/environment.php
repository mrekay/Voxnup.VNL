<div class="d-flex flex-column flex-column-fluid">
    <div id="kt_app_content" class="app-content flex-column-fluid">
        <div id="kt_app_content_container" class="app-container container-xxl">

            <div class="vnl-page-hero mb-12 mt-6">
                <div class="vnl-overline mb-3">// docs/environment</div>
                <h1 class="fs-2hx fw-bold mb-4 text-body-emphasis">Ortam Yapılandırması ve Migrasyonlar</h1>
                <div class="vnl-lead">Ortama göre değişen ve gizli olan her şey proje kökündeki <code>.env</code> dosyasından okunur; veritabanı şeması <code>migrations/</code> altında numaralı SQL dosyalarıyla yönetilir. Yeni bir kurulumda ilk yapacağınız iki iş budur.</div>
            </div>

            <div class="alert alert-danger d-flex align-items-center p-5 mb-10">
                <i class="fa-solid fa-key fs-2hx text-danger me-4"></i>
                <div class="d-flex flex-column">
                    <h4 class="mb-1 text-danger">.env olmadan uygulama çalışmaz</h4>
                    <span class="text-body">Veritabanı bilgileri, mail ayarları ve şifreleme anahtarı buradan gelir. Özellikle <code>APP_ENCRYPTION_KEY</code> tanımsızsa <code>EncryptionModule::safeEncrypt()</code> açık bir <code>RuntimeException</code> fırlatır — zayıf bir anahtara sessizce düşmez.</span>
                </div>
            </div>

            <div class="card mb-10">
                <div class="card-body p-10">

                    <!--begin::Section 1-->
                    <div class="mb-10">
                        <h3 class="fw-bold text-body-emphasis mb-4">1. Kurulum</h3>

                        <div class="d-flex flex-column gap-5 mb-6">
                            <div class="d-flex align-items-start">
                                <span class="badge badge-circle badge-primary me-4 mt-1">1</span>
                                <div class="flex-grow-1">
                                    <h6 class="fw-bold mb-2">Şablonu kopyalayın</h6>
                                    <div class="vnl-code">
                                        <pre class="mb-0" style="background: transparent; white-space: pre-wrap;"><code>cp .env.example .env</code></pre>
                                    </div>
                                </div>
                            </div>
                            <div class="d-flex align-items-start">
                                <span class="badge badge-circle badge-primary me-4 mt-1">2</span>
                                <div class="flex-grow-1">
                                    <h6 class="fw-bold mb-2">Şifreleme anahtarını üretin</h6>
                                    <p class="text-body fs-6 mb-2">base64 kodlanmış 32 bayt olmalıdır; başka uzunluk kabul edilmez.</p>
                                    <div class="vnl-code">
                                        <pre class="mb-0" style="background: transparent; white-space: pre-wrap;"><code>php -r "echo base64_encode(random_bytes(32));"</code></pre>
                                    </div>
                                </div>
                            </div>
                            <div class="d-flex align-items-start">
                                <span class="badge badge-circle badge-primary me-4 mt-1">3</span>
                                <div class="flex-grow-1">
                                    <h6 class="fw-bold mb-2">Veritabanı bilgilerini doldurun</h6>
                                    <p class="text-body fs-6 mb-0"><code>DB_HOST</code>, <code>DB_DATABASE</code>, <code>DB_USERNAME</code>, <code>DB_PASSWORD</code>.</p>
                                </div>
                            </div>
                            <div class="d-flex align-items-start">
                                <span class="badge badge-circle badge-success me-4 mt-1">4</span>
                                <div class="flex-grow-1">
                                    <h6 class="fw-bold mb-2">Migrasyonları çalıştırın</h6>
                                    <p class="text-body fs-6 mb-0">Aşağıdaki <b>6. bölüme</b> bakın — <code>0001</code> her kurulumda zorunludur.</p>
                                </div>
                            </div>
                        </div>

                        <div class="row g-5">
                            <div class="col-md-4">
                                <div class="border border-dashed rounded p-5 h-100">
                                    <h6 class="fw-bold mb-2"><code>.env</code></h6>
                                    <span class="text-muted fs-7">Gerçek değerler. <code>.gitignore</code>'dadır, commit edilmez; <code>.htaccess</code> ve <code>web.config</code> servis edilmesini engeller.</span>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="border border-dashed rounded p-5 h-100">
                                    <h6 class="fw-bold mb-2"><code>.env.example</code></h6>
                                    <span class="text-muted fs-7">Anahtar listesi ve açıklamalar. Commit edilir. Yeni bir anahtar eklediğinizde buraya da ekleyin.</span>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="border border-dashed rounded p-5 h-100">
                                    <h6 class="fw-bold mb-2">Sunucu değişkeni</h6>
                                    <span class="text-muted fs-7">Sunucuda tanımlı gerçek ortam değişkeni <code>.env</code>'i <b>ezer</b> — konteyner ve CI yapılandırması için.</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!--end::Section 1-->

                    <div class="separator separator-dashed my-8"></div>

                    <!--begin::Section 2-->
                    <div class="mb-10">
                        <h3 class="fw-bold text-body-emphasis mb-4">2. Anahtarların Tamamı</h3>
                        <p class="fs-5 text-body mb-5">
                            <code>ConfigDefinitions</code> bu değerleri constructor'ında okur; sınıfın kendisinde gizli bilgi bulunmaz. Okunan değerlere <code>$cDefinitions</code> getter'ları üzerinden erişilir (bkz. <a href="/docs/config-definitions" class="fw-bold">Config Definitions</a>).
                        </p>

                        <h5 class="fw-bold text-body-emphasis mb-3">Uygulama</h5>
                        <div class="table-responsive border rounded bg-body-tertiary mb-6">
                            <table class="table align-middle table-row-dashed fs-6 gy-4 mb-0">
                                <thead>
                                <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0 border-bottom">
                                    <th class="ps-5 min-w-250px">Anahtar</th>
                                    <th class="min-w-150px">Varsayılan</th>
                                    <th class="min-w-350px">Açıklama</th>
                                </tr>
                                </thead>
                                <tbody class="text-body fw-semibold">
                                <tr>
                                    <td class="ps-5"><code>APP_URL</code></td>
                                    <td><code>""</code></td>
                                    <td>Protokolsüz domain (<code>ornek.com</code>). <code>getSiteUrl()</code> protokolü isteğe bakarak kendisi ekler.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>APP_MASTER_URL</code></td>
                                    <td><code>""</code></td>
                                    <td>Çok kiracılı yapılarda merkezi domain. Tek domainli uygulamalarda boş bırakılır.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>APP_ASSETS_URL</code></td>
                                    <td><code>/assets</code></td>
                                    <td>CSS/JS/medya kök yolu. CDN kullanıyorsanız tam URL yazabilirsiniz.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>APP_COOKIE_PREFIX</code></td>
                                    <td><code>vnl</code></td>
                                    <td>Çerez öneki. Aynı sunucuda birden fazla VNL uygulaması varsa <b>her birine benzersiz verin</b>; aksi hâlde "beni hatırla" çerezleri çakışır.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>APP_ENCRYPTION_KEY</code></td>
                                    <td><em>Zorunlu</em></td>
                                    <td><span class="badge badge-soft-danger me-2">Kritik</span><code>safeEncrypt()</code> / <code>safeDecrypt()</code> anahtarı — base64 kodlanmış 32 bayt. Değiştirmek daha önce şifrelenmiş <b>her veriyi</b> çözülemez hâle getirir.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>APP_SALT</code></td>
                                    <td><code>""</code></td>
                                    <td>Ayrılmış alan; <code>$cDefinitions->getSaltText()</code> ile okunabilir. <b>VNL çekirdeği bu değeri hiçbir yerde kullanmaz</b> — parolalar bcrypt'in kendi tuzuyla, "beni hatırla" jetonları SHA-256 ile korunur. Kendi imzalama ihtiyacınız için kullanabilirsiniz.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>APP_PRODUCT_ID</code></td>
                                    <td><code>0</code></td>
                                    <td>Lisanslama veya çok kiracılı sistemlerde ürün tanımlayıcı.</td>
                                </tr>
                                </tbody>
                            </table>
                        </div>

                        <h5 class="fw-bold text-body-emphasis mb-3">Veritabanı</h5>
                        <div class="table-responsive border rounded bg-body-tertiary mb-6">
                            <table class="table align-middle table-row-dashed fs-6 gy-4 mb-0">
                                <thead>
                                <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0 border-bottom">
                                    <th class="ps-5 min-w-250px">Anahtar</th>
                                    <th class="min-w-150px">Varsayılan</th>
                                    <th class="min-w-350px">Açıklama</th>
                                </tr>
                                </thead>
                                <tbody class="text-body fw-semibold">
                                <tr>
                                    <td class="ps-5"><code>DB_DRIVER</code></td>
                                    <td><code>mysql</code></td>
                                    <td><code>mysql</code>, <code>pgsql</code>, <code>sqlsrv</code>.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>DB_HOST</code></td>
                                    <td><code>localhost</code></td>
                                    <td>Veritabanı sunucusu.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>DB_DATABASE</code> · <code>DB_USERNAME</code> · <code>DB_PASSWORD</code></td>
                                    <td><code>""</code></td>
                                    <td>Bağlantı kimlik bilgileri. Şifre boşluk veya <code>#</code> içeriyorsa <b>tırnak içine alın</b>.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>DB_CHARSET</code> · <code>DB_COLLATION</code></td>
                                    <td><code>utf8</code> · <code>utf8_unicode_ci</code></td>
                                    <td>Emoji ve tam Unicode desteği için <code>utf8mb4</code> / <code>utf8mb4_unicode_ci</code> önerilir.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>DB_PREFIX</code></td>
                                    <td><code>""</code></td>
                                    <td>Tablo adı öneki.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>DB_MASTER</code></td>
                                    <td><code>""</code></td>
                                    <td>Çok kiracılı yapılarda merkezi veritabanı adı.</td>
                                </tr>
                                </tbody>
                            </table>
                        </div>

                        <h5 class="fw-bold text-body-emphasis mb-3">Mail (SMTP)</h5>
                        <div class="table-responsive border rounded bg-body-tertiary mb-5">
                            <table class="table align-middle table-row-dashed fs-6 gy-4 mb-0">
                                <thead>
                                <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0 border-bottom">
                                    <th class="ps-5 min-w-250px">Anahtar</th>
                                    <th class="min-w-150px">Varsayılan</th>
                                    <th class="min-w-350px">Açıklama</th>
                                </tr>
                                </thead>
                                <tbody class="text-body fw-semibold">
                                <tr>
                                    <td class="ps-5"><code>MAIL_HOST</code></td>
                                    <td><code>""</code></td>
                                    <td>Boşsa <code>MailModule::SendMail()</code> gönderim yapmaz, durumu <code>error_log</code>'a yazar ve <code>false</code> döner.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>MAIL_PORT</code></td>
                                    <td><code>465</code></td>
                                    <td>SSL için 465, TLS için genelde 587.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>MAIL_USERNAME</code> · <code>MAIL_PASSWORD</code></td>
                                    <td><code>""</code></td>
                                    <td>SMTP kimlik bilgileri.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>MAIL_ENCRYPTION</code></td>
                                    <td><code>ssl</code></td>
                                    <td><code>ssl</code> · <code>tls</code> · <code>none</code>.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>MAIL_FROM_ADDRESS</code> · <code>MAIL_FROM_NAME</code></td>
                                    <td><code>""</code></td>
                                    <td>Gönderen kimliği.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>MAIL_VERIFY_PEER</code></td>
                                    <td><code>true</code></td>
                                    <td>Kendi imzalı sertifikalı SMTP sunucusu için <code>false</code> yapın; aksi hâlde açık kalmalıdır.</td>
                                </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="alert alert-primary d-flex align-items-center p-4">
                            <i class="fa-solid fa-lightbulb fs-2 text-primary me-3"></i>
                            <span class="text-body fs-6">SMTP ayarları VNL 2.x'te <code>MailModule</code> içinde sabit yazılıydı ve boştu — hiçbir mail gidemezdi. VNL 3.0'da tamamı <code>.env</code>'e taşındı.</span>
                        </div>
                    </div>
                    <!--end::Section 2-->

                    <div class="separator separator-dashed my-8"></div>

                    <!--begin::Section 3-->
                    <div class="mb-10">
                        <h3 class="fw-bold text-body-emphasis mb-4">3. Dosya Söz Dizimi</h3>

                        <div class="vnl-code mb-5">
                            <pre class="mb-0" style="background: transparent; white-space: pre-wrap;"><code># Yorum satırı — satır başındaki # ile

APP_URL=ornek.com                 # tırnaksız değer
DB_PASSWORD="p#ss w0rd"           # boşluk veya # içeriyorsa çift tırnak
API_TOKEN='ham $degerdir'         # tek tırnak: hiçbir kaçış çözülmez
export MAIL_HOST=smtp.ornek.com   # export öneki yok sayılır

APP_DEBUG=false                   # true / false / null / empty özel değerler
COMMENT_SAMPLE=deger # buradan sonrası atılır</code></pre>
                        </div>

                        <div class="table-responsive border rounded bg-body-tertiary mb-5">
                            <table class="table align-middle table-row-dashed fs-6 gy-4 mb-0">
                                <thead>
                                <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0 border-bottom">
                                    <th class="ps-5 min-w-250px">Yazılan</th>
                                    <th class="min-w-300px">PHP'de dönen</th>
                                </tr>
                                </thead>
                                <tbody class="text-body fw-semibold">
                                <tr>
                                    <td class="ps-5"><code>true</code> · <code>(true)</code></td>
                                    <td><code>true</code> <span class="text-muted">(bool)</span></td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>false</code> · <code>(false)</code></td>
                                    <td><code>false</code> <span class="text-muted">(bool)</span></td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>null</code> · <code>(null)</code></td>
                                    <td><code>null</code></td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>empty</code> · <code>(empty)</code></td>
                                    <td>Boş string</td>
                                </tr>
                                <tr>
                                    <td class="ps-5">Çift tırnaklı değer</td>
                                    <td><code>\n</code>, <code>\r</code>, <code>\t</code>, <code>\"</code>, <code>\\</code> kaçışları çözülür</td>
                                </tr>
                                <tr>
                                    <td class="ps-5">Tek tırnaklı değer</td>
                                    <td>Ham metin; hiçbir dönüşüm uygulanmaz</td>
                                </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="alert alert-warning d-flex align-items-center p-4">
                            <i class="fa-solid fa-circle-info fs-2 text-warning me-3"></i>
                            <span class="text-body fs-6">Anahtar adı <code>[A-Za-z_][A-Za-z0-9_.]*</code> kalıbına uymalıdır; uymayan satırlar sessizce atlanır. <code>=</code> içermeyen satırlar da yok sayılır.</span>
                        </div>
                    </div>
                    <!--end::Section 3-->

                    <div class="separator separator-dashed my-8"></div>

                    <!--begin::Section 4-->
                    <div class="mb-10">
                        <h3 class="fw-bold text-body-emphasis mb-4">4. Değerleri Okumak</h3>

                        <div class="table-responsive border rounded bg-body-tertiary mb-6">
                            <table class="table align-middle table-row-dashed fs-6 gy-4 mb-0">
                                <thead>
                                <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0 border-bottom">
                                    <th class="ps-5 min-w-300px">Çağrı</th>
                                    <th class="min-w-350px">Açıklama</th>
                                </tr>
                                </thead>
                                <tbody class="text-body fw-semibold">
                                <tr>
                                    <td class="ps-5"><code>VnlEnv::get('DB_HOST', 'localhost')</code></td>
                                    <td>Değeri döner. Sunucu ortam değişkeni varsa <code>.env</code>'in önüne geçer. Anahtar yoksa ikinci parametre döner.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>VnlEnv::has('MAIL_HOST')</code></td>
                                    <td>Anahtar tanımlı mı? <code>bool</code>.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>VnlEnv::file()</code></td>
                                    <td>Okunan dosyanın yolu; <code>.env</code> bulunamadıysa <code>null</code>. Kurulum tanılaması için.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>env('APP_DEBUG', false)</code></td>
                                    <td>Proje kodu için kısayol. <b>Çekirdek bunu kullanmaz</b> — nedeni aşağıda.</td>
                                </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="alert alert-warning d-flex align-items-center p-5 mb-5">
                            <i class="fa-solid fa-triangle-exclamation fs-2hx text-warning me-4"></i>
                            <div class="d-flex flex-column">
                                <h5 class="mb-1 text-warning"><code>env()</code> çakışabilir</h5>
                                <span class="text-body"><code>illuminate/support</code> da aynı adda bir fonksiyon tanımlar ve o sürüm <code>.env</code> dosyasını değil yalnızca <code>$_ENV</code>/<code>$_SERVER</code>'ı okur. VNL kendi tanımını vendor'dan önce yükler, ama vendor'ı önce yükleyen bir betikte Illuminate'inki kazanır ve <b>ayarlar sessizce kaybolur</b>. Bu yüzden çekirdek doğrudan <code>VnlEnv::get()</code> kullanır; çakışma tespit edilirse <code>error_log</code>'a uyarı düşer. Kritik yerlerde siz de <code>VnlEnv::get()</code> tercih edin.</span>
                            </div>
                        </div>

                        <div class="vnl-code">
                            <pre class="mb-0" style="background: transparent; white-space: pre-wrap;"><code>&lt;?php
// Kendi ayarınızı eklemek: önce .env ve .env.example'a yazın
// ODEME_SAGLAYICI_ANAHTAR=sk_live_...

$anahtar = VnlEnv::get('ODEME_SAGLAYICI_ANAHTAR', '');

if ($anahtar === '') {
    throw new RuntimeException('ODEME_SAGLAYICI_ANAHTAR tanımlı değil. .env dosyanıza ekleyin.');
}
</code></pre>
                        </div>
                    </div>
                    <!--end::Section 4-->

                    <div class="separator separator-dashed my-8"></div>

                    <!--begin::Section 5-->
                    <div class="mb-10">
                        <h3 class="fw-bold text-body-emphasis mb-4">5. Dosya Güvenliği</h3>
                        <p class="fs-5 text-body mb-5">
                            <code>.env</code> web kökünde durur; servis edilmemesi sunucu yapılandırmasına bağlıdır. VNL her iki sunucu için de koruma getirir:
                        </p>

                        <div class="table-responsive border rounded bg-body-tertiary mb-5">
                            <table class="table align-middle table-row-dashed fs-6 gy-4 mb-0">
                                <thead>
                                <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0 border-bottom">
                                    <th class="ps-5 min-w-200px">Dosya</th>
                                    <th class="min-w-400px">Koruma</th>
                                </tr>
                                </thead>
                                <tbody class="text-body fw-semibold">
                                <tr>
                                    <td class="ps-5"><code>.htaccess</code></td>
                                    <td>Nokta ile başlayan her yol parçası engellenir (<code>.env</code>, <code>.git</code>, <code>.idea</code>) — <code>.well-known</code> hariç. Kural, yönlendirmelerden <b>önce</b> gelir; aksi hâlde var olan dosya düz metin olarak servis edilirdi. Ayrıca <code>.sql</code>, <code>.log</code>, <code>.lock</code>, <code>.ini</code>, <code>.sh</code>, <code>.bak</code>, <code>.yml</code> uzantıları kapatılır.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>web.config</code></td>
                                    <td>IIS için <code>hiddenSegments</code> ve <code>fileExtensions</code> aynı korumayı sağlar.</td>
                                </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="alert alert-danger d-flex align-items-center p-4">
                            <i class="fa-solid fa-circle-exclamation fs-2 text-danger me-3"></i>
                            <span class="text-body fs-6">Nginx kullanıyorsanız bu iki dosya <b>hiçbir işe yaramaz</b>. <code>location ~ /\. { deny all; }</code> benzeri bir kuralı kendiniz eklemelisiniz.</span>
                        </div>
                    </div>
                    <!--end::Section 5-->

                    <div class="separator separator-dashed my-8"></div>

                    <!--begin::Section 6-->
                    <div class="mb-0">
                        <h3 class="fw-bold text-body-emphasis mb-4">6. Migrasyonlar</h3>
                        <p class="fs-5 text-body mb-5">
                            Veritabanı değişiklikleri <code>migrations/</code> altında <b>numara sırasıyla</b> çalıştırılan SQL dosyalarıdır. Otomatik bir çalıştırıcı yoktur; dağıtım sırasında elle uygulanırlar.
                        </p>

                        <div class="vnl-code mb-6">
                            <pre class="mb-0" style="background: transparent; white-space: pre-wrap;"><code>migrations/
├── README.md
├── 0001_vnl_3_0_upgrade.sql
└── 0002_siparis_tablosu.sql</code></pre>
                        </div>

                        <h5 class="fw-bold text-body-emphasis mb-3">Kurallar</h5>
                        <div class="table-responsive border rounded bg-body-tertiary mb-6">
                            <table class="table align-middle table-row-dashed fs-6 gy-4 mb-0">
                                <thead>
                                <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0 border-bottom">
                                    <th class="ps-5 min-w-200px">Kural</th>
                                    <th class="min-w-400px">Açıklama</th>
                                </tr>
                                </thead>
                                <tbody class="text-body fw-semibold">
                                <tr>
                                    <td class="ps-5">Adlandırma</td>
                                    <td><code>NNNN_kisa_aciklama.sql</code> — numara dört haneli ve artan.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5">Değiştirilmezlik</td>
                                    <td>Yayımlanmış bir migration <b>düzenlenmez</b>; düzeltme için yeni numara açılır. Başkasının veritabanında zaten çalışmış olabilir.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5">Yeniden çalıştırılabilirlik</td>
                                    <td>Hem sıfırdan kurulumda hem mevcut kurulumda çalışmalıdır: <code>CREATE TABLE IF NOT EXISTS</code> kullanın.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5">Sütun sözleşmesi</td>
                                    <td><code>snake_case</code>, birincil anahtar <code>id</code>, yumuşak silme <code>deleted_at</code>.</td>
                                </tr>
                                </tbody>
                            </table>
                        </div>

                        <h5 class="fw-bold text-body-emphasis mb-3">Çalıştırma</h5>
                        <div class="vnl-code mb-6">
                            <pre class="mb-0" style="background: transparent; white-space: pre-wrap;"><code>mysql -u KULLANICI -p VERITABANI &lt; migrations/0001_vnl_3_0_upgrade.sql</code></pre>
                        </div>

                        <div class="alert alert-danger d-flex align-items-center p-5 mb-0">
                            <i class="fa-solid fa-circle-exclamation fs-2hx text-danger me-4"></i>
                            <div class="d-flex flex-column">
                                <h5 class="mb-1 text-danger"><code>0001</code> zorunludur</h5>
                                <span class="text-body">Sıfırdan kurulumda da, VNL 2.x'ten yükseltmede de bir kez çalıştırın. İki kritik değişiklik içerir:
                                    <br>• <code>tbl_users.password</code> → <code>VARCHAR(255)</code>. VNL 3.0 bcrypt kullanır (60 karakter); 2.x'in <code>sha1(md5())</code> hash'ine 40 karakter yetiyordu. Sütun dar kalırsa <b>hash sessizce kırpılır</b> ve kullanıcı bir sonraki girişinde içeri giremez.
                                    <br>• <code>tbl_remember_tokens</code> tablosu — bu tablo yoksa <a href="/docs/session" class="fw-bold">"beni hatırla"</a> çalışmaz.</span>
                            </div>
                        </div>
                    </div>
                    <!--end::Section 6-->

                </div>
            </div>

        </div>
    </div>
</div>

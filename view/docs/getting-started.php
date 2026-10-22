<div class="d-flex flex-column flex-column-fluid">
    <div id="kt_app_content" class="app-content flex-column-fluid">
        <div id="kt_app_content_container" class="app-container container-xxl">

            <div class="vnl-page-hero mb-12 mt-6">
                <div class="vnl-overline mb-3">// docs/getting-started</div>
                <h1 class="fs-2hx fw-bold mb-4 text-body-emphasis">VNL'ye Giriş</h1>
                <div class="vnl-lead">VNL, PHP tabanlı, Illuminate (Eloquent/Capsule) ORM kullanan, Bootstrap 5.3 üzerine inşa edilen VNL Theme ile gelen bir uygulama şablonudur.
                    Rota tanımlaması, ORM kurulumu ve frontend entegrasyonu hazır gelir — siz doğrudan iş mantığınızı yazmaya başlarsınız.</div>
            </div>

            <!--begin::Gereksinimler-->
            <div class="row g-5 mb-10">
                <div class="col-md-4">
                    <div class="card border border-dashed h-100">
                        <div class="card-body p-6 d-flex align-items-center">
                            <div class="symbol symbol-50px me-4">
                                <span class="symbol-label bg-primary-subtle">
                                    <i class="fa-solid fa-gear fs-2x text-primary"></i>
                                </span>
                            </div>
                            <div>
                                <div class="fw-bold text-body fs-5">PHP 8.2+</div>
                                <div class="text-muted fs-7">Minimum gereksinim — güvenlik yamasıyla: 8.2.35 / 8.3.36 / 8.4.27 / 8.5.12+</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border border-dashed h-100">
                        <div class="card-body p-6 d-flex align-items-center">
                            <div class="symbol symbol-50px me-4">
                                <span class="symbol-label bg-success-subtle">
                                    <i class="fa-solid fa-database fs-2x text-success"></i>
                                </span>
                            </div>
                            <div>
                                <div class="fw-bold text-body fs-5">MySQL / MariaDB</div>
                                <div class="text-muted fs-7">PostgreSQL & MSSQL de desteklenir</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border border-dashed h-100">
                        <div class="card-body p-6 d-flex align-items-center">
                            <div class="symbol symbol-50px me-4">
                                <span class="symbol-label bg-info-subtle">
                                    <i class="fa-solid fa-cloud fs-2x text-info"></i>
                                </span>
                            </div>
                            <div>
                                <div class="fw-bold text-body fs-5">Apache / Nginx</div>
                                <div class="text-muted fs-7">mod_rewrite aktif olmalı</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!--end::Gereksinimler-->

            <div class="card mb-10">
                <div class="card-body p-10">

                    <!--begin::Section 1-->
                    <div class="mb-10">
                        <h3 class="fw-bold text-body-emphasis mb-4">1. Kurulum</h3>
                        <p class="fs-5 text-body mb-4">
                            Projeyi web sunucunuzun kök dizinine klonlayın. <code>composer</code> gerekmez; vendor bağımlılıkları (<code>vnl/vendor/</code>) repoya dahildir.
                        </p>

                        <div class="d-flex align-items-center bg-body-secondary border rounded p-5 mb-4">
                            <i class="fa-solid fa-terminal fs-2 text-body-emphasis me-3"></i>
                            <span class="text-body-emphasis fs-4 fw-bold font-monospace user-select-all">git clone https://github.com/mrekay/VNL-TEMPLATE.git .</span>
                        </div>

                        <p class="fs-5 text-body mb-4">
                            Ardından ortam dosyasını oluşturun. <code>.env</code> commit edilmez ve web sunucusu tarafından servis edilmez; veritabanı bilgileri, adresler ve şifreleme anahtarı oradadır.
                        </p>

                        <div class="d-flex align-items-center bg-body-secondary border rounded p-5 mb-4">
                            <i class="fa-solid fa-terminal fs-2 text-body-emphasis me-3"></i>
                            <span class="text-body-emphasis fs-4 fw-bold font-monospace user-select-all">cp .env.example .env</span>
                        </div>

                        <p class="fs-5 text-body mb-4">
                            Şemayı kurun ve kurulumu doğrulayın:
                        </p>

                        <div class="vnl-code mb-4"><pre class="mb-0" style="background:transparent; white-space:pre-wrap;"><code>php vnl.php migrate --run   # bekleyen şema değişikliklerini uygula
php vnl.php doctor          # eksik bir şey kalmış mı</code></pre></div>

                        <div class="alert alert-warning d-flex align-items-center p-4">
                            <i class="fa-solid fa-circle-info fs-2 text-warning me-3"></i>
                            <span class="text-body fs-6">Alt dizine kuruyorsanız <code>.htaccess</code>'deki HTTPS yönlendirme kuralını ve <code>.env</code> içindeki <code>APP_URL</code> değerini buna göre güncelleyin.</span>
                        </div>
                    </div>
                    <!--end::Section 1-->

                    <div class="separator separator-dashed my-8"></div>

                    <!--begin::Section 2-->
                    <div class="mb-10">
                        <h3 class="fw-bold text-body-emphasis mb-4">2. Yapılandırma</h3>
                        <p class="fs-5 text-body mb-4">
                            Yapılandırma <b>iki yere</b> ayrılır. Ortama göre değişen ve gizli olan her şey <code>.env</code>'dedir (veritabanı, adresler, SMTP, şifreleme anahtarı) &mdash; tam listesi <a href="/docs/environment">Ortam Değişkenleri</a> sayfasında. Uygulamanın kimliği ise <code>config/definitions.php</code>'de durur ve depoya girer:
                        </p>
                        <div class="d-flex align-items-center bg-body-tertiary border border-dashed rounded p-4 mb-5">
                            <i class="fa-solid fa-file fs-1 text-primary me-3"></i>
                            <span class="fs-5 fw-bold text-body font-monospace">config/definitions.php</span>
                        </div>

                        <div class="table-responsive border rounded bg-body-tertiary mb-5">
                            <table class="table align-middle table-row-dashed fs-6 gy-4 mb-0">
                                <thead>
                                <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0 border-bottom">
                                    <th class="ps-5 min-w-180px">Alan</th>
                                    <th class="min-w-120px">Örnek</th>
                                    <th class="min-w-300px">Açıklama</th>
                                </tr>
                                </thead>
                                <tbody class="text-body fw-semibold">
                                <tr>
                                    <td class="ps-5"><code>$app_name</code></td>
                                    <td><code>"Uygulamam"</code></td>
                                    <td>Breadcrumb ve başlıklarda görünen uygulama adı.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>$site_url</code></td>
                                    <td><code>"myapp.com"</code></td>
                                    <td>Protokolsüz domain. Login yönlendirmesi ve linklerde kullanılır.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>$db_info</code></td>
                                    <td><code>host, db, user, pass</code></td>
                                    <td>Illuminate/Capsule bağlantı bilgileri.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>$cookie_prefix</code></td>
                                    <td><code>"myapp"</code></td>
                                    <td>Oturum cookie öneki. Aynı sunucuda birden fazla VNL varsa benzersiz yapın.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>$salt_text</code></td>
                                    <td><code>"abc?123!@#"</code></td>
                                    <td>Şifre hash salt'ı. Sonradan değiştirmeyin; mevcut şifreler geçersiz kalır.</td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                        <p class="fs-6 text-muted">Tüm yapılandırma alanları için <a href="/docs/config-definitions" class="text-primary fw-bold">Config Definitions</a> sayfasına bakın.</p>
                    </div>
                    <!--end::Section 2-->

                    <div class="separator separator-dashed my-8"></div>

                    <!--begin::Section 3 — Yükleme sırası-->
                    <div class="mb-10">
                        <h3 class="fw-bold text-body-emphasis mb-4">3. Her İstekteki Yükleme Sırası</h3>
                        <p class="fs-5 text-body mb-4">
                            Her HTTP isteğinde sistem şu zinciri çalıştırır. Bu sırayı bilmek; hangi değişkenin nerede hazır olduğunu anlamanızı sağlar.
                        </p>

                        <div class="border rounded p-6 bg-body-tertiary">
                            <div class="d-flex align-items-start mb-5">
                                <span class="badge badge-circle badge-primary me-4 mt-1 flex-shrink-0">1</span>
                                <div>
                                    <code class="fw-bold text-body">.htaccess</code>
                                    <p class="text-muted fs-7 mb-0 mt-1">Gelen her isteği (dosya/klasör değilse) <code>index.php</code>'ye yönlendirir. HTTPS zorlaması da burada yapılır.</p>
                                </div>
                            </div>
                            <div class="d-flex align-items-start mb-5">
                                <span class="badge badge-circle badge-primary me-4 mt-1 flex-shrink-0">2</span>
                                <div>
                                    <code class="fw-bold text-body">index.php</code>
                                    <p class="text-muted fs-7 mb-0 mt-1">Tek işi <code>VNL_ROOT</code>'u sabitleyip <code>vnl/boot.php</code> ile <code>vnl/kernel.php</code>'yi çağırmak. Yönlendirme mantığı VNL 3.0'da buradan çekirdeğe taşındı; artık her projede bir kopyası durmuyor.</p>
                                </div>
                            </div>
                            <div class="d-flex align-items-start mb-5">
                                <span class="badge badge-circle badge-primary me-4 mt-1 flex-shrink-0">3</span>
                                <div>
                                    <code class="fw-bold text-body">vnl/boot.php</code>
                                    <p class="text-muted fs-7 mb-0 mt-1">Hata sayfası, şablon derleyicisi ve <code>.env</code> okuyucu → <code>config/definitions.php</code> (sizin) → <code>vnl/bootstrap.php</code> → <code>vnl/vendor</code> → <code>config/database.php</code> (sizin) → eklentiler.</p>
                                </div>
                            </div>
                            <div class="d-flex align-items-start mb-5">
                                <span class="badge badge-circle badge-primary me-4 mt-1 flex-shrink-0">4</span>
                                <div>
                                    <code class="fw-bold text-body">vnl/bootstrap.php</code>
                                    <p class="text-muted fs-7 mb-0 mt-1">Otomatik yükleyici (önce <code>classes/</code>, sonra <code>vnl/classes/</code>), yardımcılar, oturum, <code>$req</code> ve dil. <code>modules/</code>, <code>exceptions/</code>, <code>validators/</code> iki taraftan birleştirilerek taranır; aynı adlı dosyada sizinki kazanır.</p>
                                </div>
                            </div>
                            <div class="d-flex align-items-start mb-5">
                                <span class="badge badge-circle badge-primary me-4 mt-1 flex-shrink-0">5</span>
                                <div>
                                    <code class="fw-bold text-body">config/database.php</code>
                                    <p class="text-muted fs-7 mb-0 mt-1"><code>Database</code> (Capsule) başlatılır → <code>$CurrentUser</code> nesnesi hazırlanır → cookie ile otomatik giriş kontrolü → kullanıcı bilgisi yüklenir.</p>
                                </div>
                            </div>
                            <div class="d-flex align-items-start mb-0">
                                <span class="badge badge-circle badge-primary me-4 mt-1 flex-shrink-0">6</span>
                                <div>
                                    <code class="fw-bold text-body">controller/{sayfa}.php</code>
                                    <p class="text-muted fs-7 mb-0 mt-1"><code>vnl/kernel.php</code> adresi denetleyiciye çevirir ve giriş denetimini uygular; ilgili controller çalışır: <code>$meta</code> ve <code>$page_config</code> tanımlanır, DB sorguları yapılır, view dosyaları ekrana basılır.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!--end::Section 3-->

                    <div class="separator separator-dashed my-8"></div>

                    <!--begin::Section 4 — Klasör yapısı-->
                    <div class="mb-10">
                        <h3 class="fw-bold text-body-emphasis mb-4">4. Klasör Yapısı</h3>

                        <div class="border rounded p-6 bg-body-tertiary">
                            <div class="fw-bold text-body-emphasis mb-4"><i class="bi bi-person-gear text-primary me-2"></i>Sizin — güncelleme buraya dokunmaz</div>
                            <ul class="list-unstyled fs-6 text-body fw-semibold mb-0">
                                <li class="mb-3 d-flex align-items-start">
                                    <i class="bi bi-folder2-open text-warning fs-3 me-3 flex-shrink-0 mt-1"></i>
                                    <div><b>actions/</b><span class="text-muted fw-normal ms-2">— AJAX/form işlemcileri. <code>action_</code> önekli dosyalar, her biri bir PHP sınıfı içerir. <code>/app/</code> ön ekiyle çağrılır.</span></div>
                                </li>
                                <li class="mb-3 d-flex align-items-start">
                                    <i class="bi bi-folder2-open text-warning fs-3 me-3 flex-shrink-0 mt-1"></i>
                                    <div><b>api/</b><span class="text-muted fw-normal ms-2">— Ayrı bir giriş noktası açmak isterseniz.</span></div>
                                </li>
                                <li class="mb-3 d-flex align-items-start">
                                    <i class="bi bi-folder2-open text-warning fs-3 me-3 flex-shrink-0 mt-1"></i>
                                    <div><b>assets/</b><span class="text-muted fw-normal ms-2">— SİZİN CSS/JS/görselleriniz. VNL'in kendi varlıkları burada değil, <code>vnl/assets/</code> altındadır.</span></div>
                                </li>
                                <li class="mb-3 d-flex align-items-start">
                                    <i class="bi bi-folder2-open text-warning fs-3 me-3 flex-shrink-0 mt-1"></i>
                                    <div><b>classes/</b><span class="text-muted fw-normal ms-2">— OOP sınıflarınız. Otomatik yüklenir. Bir çekirdek sınıfıyla <b>aynı adı</b> verirseniz sizinki geçerli olur.</span></div>
                                </li>
                                <li class="mb-3 d-flex align-items-start">
                                    <i class="bi bi-folder2-open text-warning fs-3 me-3 flex-shrink-0 mt-1"></i>
                                    <div><b>config/</b><span class="text-muted fw-normal ms-2">— <code>definitions.php</code> (uygulama tanımları) ve <code>database.php</code> (bağlantı açılışı).</span></div>
                                </li>
                                <li class="mb-3 d-flex align-items-start">
                                    <i class="bi bi-folder2-open text-warning fs-3 me-3 flex-shrink-0 mt-1"></i>
                                    <div><b>controller/</b><span class="text-muted fw-normal ms-2">— Her URL segmentine karşılık gelen düz PHP dosyaları. Hata sayfaları da (<code>401.php</code>, <code>404.php</code>) sizindir.</span></div>
                                </li>
                                <li class="mb-3 d-flex align-items-start">
                                    <i class="bi bi-folder2-open text-warning fs-3 me-3 flex-shrink-0 mt-1"></i>
                                    <div><b>helpers/</b><span class="text-muted fw-normal ms-2">— Kendi global fonksiyonlarınız. Çekirdeğinkilerle birlikte yüklenir.</span></div>
                                </li>
                                <li class="mb-3 d-flex align-items-start">
                                    <i class="bi bi-folder2-open text-warning fs-3 me-3 flex-shrink-0 mt-1"></i>
                                    <div><b>languages/</b><span class="text-muted fw-normal ms-2">— JSON dil dosyaları. Metinler projeden projeye değiştiği için çekirdekte tutulmaz.</span></div>
                                </li>
                                <li class="mb-3 d-flex align-items-start">
                                    <i class="bi bi-folder2-open text-warning fs-3 me-3 flex-shrink-0 mt-1"></i>
                                    <div><b>migrations/</b><span class="text-muted fw-normal ms-2">— Kendi şema değişiklikleriniz. VNL'inkiler <code>vnl/migrations/</code> altındadır, numaralar çakışmaz.</span></div>
                                </li>
                                <li class="mb-3 d-flex align-items-start">
                                    <i class="bi bi-folder2-open text-warning fs-3 me-3 flex-shrink-0 mt-1"></i>
                                    <div><b>validators/</b><span class="text-muted fw-normal ms-2">— Giriş verisi doğrulama kurallarınız.</span></div>
                                </li>
                                <li class="mb-3 d-flex align-items-start">
                                    <i class="bi bi-folder2-open text-warning fs-3 me-3 flex-shrink-0 mt-1"></i>
                                    <div><b>view/</b><span class="text-muted fw-normal ms-2">— Şablonlar. <code>view/static/</code> düzen parçaları, <code>view/includes/</code> modal ve sayfa başı/sonu dosyaları. <b>Çekirdek tek bir görünüm bile taşımaz.</b></span></div>
                                </li>
                                <li class="mb-3 d-flex align-items-start">
                                    <i class="bi bi-folder2-open text-warning fs-3 me-3 flex-shrink-0 mt-1"></i>
                                    <div><b>files/</b><span class="text-muted fw-normal ms-2">— Kullanıcı yüklemeleri, Excel çıktıları, şablon önbelleği.</span></div>
                                </li>
                                <li class="mb-3 d-flex align-items-start">
                                    <i class="bi bi-folder2-open text-warning fs-3 me-3 flex-shrink-0 mt-1"></i>
                                    <div><b>extensions/</b><span class="text-muted fw-normal ms-2">— Eklentiler. Manifest'te açık olanlar yüklenir.</span></div>
                                </li>
                                <li class="mb-2 pt-4 border-top d-flex align-items-start">
                                    <i class="bi bi-filetype-php text-primary fs-3 me-3 flex-shrink-0 mt-1"></i>
                                    <div><b>index.php</b><span class="text-muted fw-normal ms-2">— Ön denetleyici. Çekirdeği yükler ve isteği ona devreder; içinde yönlendirme mantığı yoktur.</span></div>
                                </li>
                                <li class="mb-2 d-flex align-items-start">
                                    <i class="bi bi-terminal text-primary fs-3 me-3 flex-shrink-0 mt-1"></i>
                                    <div><b>vnl.php</b> <span class="text-body-secondary">· vnl.cmd</span><span class="text-muted fw-normal ms-2">— Konsol giriş noktası; <code>index.php</code>'nin komut satırı karşılığı ve onun gibi ince. <code>php vnl.php migrate</code>, <code>… update</code>, <code>… doctor</code>. <code>vnl.cmd</code> Windows kısayoludur.</span></div>
                                </li>
                                <li class="mb-0 d-flex align-items-start">
                                    <i class="bi bi-file-earmark-code text-primary fs-3 me-3 flex-shrink-0 mt-1"></i>
                                    <div><b>.htaccess</b><span class="text-muted fw-normal ms-2">— Apache rewrite kuralları. Her isteği <code>index.php</code>'ye yönlendirir; HTTPS zorlamasını içerir. <code>.env</code> ve konsol dosyalarını web'den engeller.</span></div>
                                </li>
                            </ul>
                        </div>
                        <div class="border rounded p-6 bg-body-tertiary mt-5">
                            <div class="fw-bold text-body-emphasis mb-4"><i class="bi bi-box-seam text-primary me-2"></i>VNL'in — güncellemede komple değişir</div>
                            <ul class="list-unstyled fs-6 text-body fw-semibold mb-0">
                                <li class="mb-3 pb-3 border-bottom d-flex align-items-start">
                                    <i class="bi bi-folder2 text-primary fs-3 me-3 flex-shrink-0 mt-1"></i>
                                    <div><b>vnl/</b><span class="text-muted fw-normal ms-2">— Kökteki <b>tek</b> çekirdek dizini; aşağıdakilerin hepsi bunun altındadır. Buraya hiçbir şey yazmayın: güncelleme bu dizini komple değiştirir. Bir çekirdek dosyasını değiştirmek isterseniz aynı adlısını kendi tarafınıza koyun.</span></div>
                                </li>
                                <li class="mb-3 d-flex align-items-start">
                                    <i class="bi bi-box text-primary fs-3 me-3 flex-shrink-0 mt-1"></i>
                                    <div><b>vnl/classes/</b><span class="text-muted fw-normal ms-2">— Request, Session, Language, TableModel, VnlTable ailesi.</span></div>
                                </li>
                                <li class="mb-3 d-flex align-items-start">
                                    <i class="bi bi-box text-primary fs-3 me-3 flex-shrink-0 mt-1"></i>
                                    <div><b>vnl/modules/</b><span class="text-muted fw-normal ms-2">— UserModule, MailModule, EncryptionModule ve arkadaşları.</span></div>
                                </li>
                                <li class="mb-3 d-flex align-items-start">
                                    <i class="bi bi-box text-primary fs-3 me-3 flex-shrink-0 mt-1"></i>
                                    <div><b>vnl/helpers/</b><span class="text-muted fw-normal ms-2">— <code>controller()</code>, <code>view()</code>, <code>lang()</code>, <code>vnl_asset()</code>.</span></div>
                                </li>
                                <li class="mb-3 d-flex align-items-start">
                                    <i class="bi bi-box text-primary fs-3 me-3 flex-shrink-0 mt-1"></i>
                                    <div><b>vnl/assets/</b><span class="text-muted fw-normal ms-2">— VNL Theme derleme çıktısı (<code>theme/</code>) ile <code>vnl.js</code> ve <code>vnltable</code>.</span></div>
                                </li>
                                <li class="mb-3 d-flex align-items-start">
                                    <i class="bi bi-box text-primary fs-3 me-3 flex-shrink-0 mt-1"></i>
                                    <div><b>vnl/vendor/</b><span class="text-muted fw-normal ms-2">— Illuminate/Eloquent, PHPMailer. Composer gerektirmez, repoda gelir.</span></div>
                                </li>
                                <li class="mb-3 d-flex align-items-start">
                                    <i class="bi bi-box text-primary fs-3 me-3 flex-shrink-0 mt-1"></i>
                                    <div><b>vnl/boot.php</b><span class="text-muted fw-normal ms-2">— Çekirdek önyüklemesi.</span></div>
                                </li>
                                <li class="mb-3 d-flex align-items-start">
                                    <i class="bi bi-box text-primary fs-3 me-3 flex-shrink-0 mt-1"></i>
                                    <div><b>vnl/kernel.php</b><span class="text-muted fw-normal ms-2">— Yönlendirme ve giriş denetimi.</span></div>
                                </li>
                                <li class="mb-3 d-flex align-items-start">
                                    <i class="bi bi-box text-primary fs-3 me-3 flex-shrink-0 mt-1"></i>
                                    <div><b>vnl/VERSION</b><span class="text-muted fw-normal ms-2">— Çekirdeğin sürümü. Projenin sürümü ayrı: <code>config/definitions.php</code>.</span></div>
                                </li>
                            </ul>
                        </div>
                    </div>
                    <!--end::Section 4-->

                    <div class="separator separator-dashed my-8"></div>

                    <!--begin::Section 5 — İlk Sayfa-->
                    <div class="mb-10">
                        <h3 class="fw-bold text-body-emphasis mb-4">5. İlk Sayfanı Oluştur</h3>
                        <p class="fs-5 text-body mb-4">Yapılandırma tamamlandıktan sonra yeni bir sayfa eklemek için sadece iki dosya gerekir:</p>

                        <div class="row g-5 mb-5">
                            <div class="col-md-6">
                                <div class="border border-dashed rounded p-5 h-100">
                                    <div class="d-flex align-items-center mb-3">
                                        <span class="badge badge-soft-primary me-3">1</span>
                                        <h6 class="mb-0 fw-bold">Controller</h6>
                                    </div>
                                    <code class="fs-7 text-muted d-block mb-1">controller/musteriler.php</code>
                                    <div class="vnl-code mt-3">
                                        <pre class="mb-0" style="background:transparent;white-space:pre-wrap"><code>&lt;?php
$meta = [
    'title'       => 'Müşteriler',
    'upper_title' => 'CRM',
];
$page_config = [
    'topbar'      => true,
    'leftsidebar' => true,
    'contentheader' => true,
];

$musteriler = Capsule::table('tbl_musteriler')
    ->get();

require view('static/header');
require view('musteriler/liste');
require view('static/footer');</code></pre>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="border border-dashed rounded p-5 h-100">
                                    <div class="d-flex align-items-center mb-3">
                                        <span class="badge badge-soft-primary me-3">2</span>
                                        <h6 class="mb-0 fw-bold">View</h6>
                                    </div>
                                    <code class="fs-7 text-muted d-block mb-1">view/musteriler/liste.php</code>
                                    <div class="vnl-code mt-3">
                                        <pre class="mb-0" style="background:transparent;white-space:pre-wrap"><code>&lt;div class="app-content flex-column-fluid"&gt;
  &lt;div class="app-container container-xxl"&gt;
    &lt;?php foreach ($musteriler as $m): ?&gt;
      &lt;div class="card mb-3"&gt;
        &lt;div class="card-body"&gt;
          &lt;?= $m-&gt;ad ?&gt;
        &lt;/div&gt;
      &lt;/div&gt;
    &lt;?php endforeach; ?&gt;
  &lt;/div&gt;
&lt;/div&gt;</code></pre>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <p class="fs-6 text-body">Artık <code>site.com/musteriler</code> adresine gittiğinizde sayfa çalışır. Detaylar için <a href="/docs/routing" class="text-primary fw-bold">Routing ve Controller</a> sayfasına bakın.</p>
                    </div>
                    <!--end::Section 5-->

                    <div class="separator separator-dashed my-8"></div>

                    <!--begin::Section 6 — Sonraki adımlar-->
                    <div class="mb-0">
                        <h3 class="fw-bold text-body-emphasis mb-5">6. Sonraki Adımlar</h3>
                        <div class="row g-4">
                            <div class="col-md-4">
                                <a href="/docs/routing" class="card border border-dashed card-flush h-100 text-decoration-none hover-elevate-up">
                                    <div class="card-body p-6">
                                        <div class="d-flex align-items-center mb-3">
                                            <i class="fa-solid fa-inbox fs-2x text-primary me-3"></i>
                                            <h5 class="mb-0 fw-bold text-body">Routing & Controller</h5>
                                        </div>
                                        <p class="text-muted fs-6 mb-0">URL yapısını, <code>$meta</code>, <code>$page_config</code> ve <code>view()</code> kullanımını öğren.</p>
                                    </div>
                                </a>
                            </div>
                            <div class="col-md-4">
                                <a href="/docs/request-handling" class="card border border-dashed card-flush h-100 text-decoration-none hover-elevate-up">
                                    <div class="card-body p-6">
                                        <div class="d-flex align-items-center mb-3">
                                            <i class="fa-solid fa-paper-plane fs-2x text-success me-3"></i>
                                            <h5 class="mb-0 fw-bold text-body">Action'lar & AJAX</h5>
                                        </div>
                                        <p class="text-muted fs-6 mb-0">Form ve AJAX isteklerini <code>actions/</code> ile güvenli şekilde işle.</p>
                                    </div>
                                </a>
                            </div>
                            <div class="col-md-4">
                                <a href="/docs/models" class="card border border-dashed card-flush h-100 text-decoration-none hover-elevate-up">
                                    <div class="card-body p-6">
                                        <div class="d-flex align-items-center mb-3">
                                            <i class="fa-solid fa-database fs-2x text-warning me-3"></i>
                                            <h5 class="mb-0 fw-bold text-body">Model & Veritabanı</h5>
                                        </div>
                                        <p class="text-muted fs-6 mb-0">TableModel ile Eloquent sınıflarını nasıl oluşturacağını öğren.</p>
                                    </div>
                                </a>
                            </div>
                            <div class="col-md-4">
                                <a href="/docs/validators" class="card border border-dashed card-flush h-100 text-decoration-none hover-elevate-up">
                                    <div class="card-body p-6">
                                        <div class="d-flex align-items-center mb-3">
                                            <i class="fa-solid fa-shield-halved fs-2x text-danger me-3"></i>
                                            <h5 class="mb-0 fw-bold text-body">Validators</h5>
                                        </div>
                                        <p class="text-muted fs-6 mb-0">Gelen veriyi <code>DefaultValidator</code> ile tek satırda doğrula.</p>
                                    </div>
                                </a>
                            </div>
                            <div class="col-md-4">
                                <a href="/docs/session" class="card border border-dashed card-flush h-100 text-decoration-none hover-elevate-up">
                                    <div class="card-body p-6">
                                        <div class="d-flex align-items-center mb-3">
                                            <i class="fa-solid fa-lock fs-2x text-info me-3"></i>
                                            <h5 class="mb-0 fw-bold text-body">Oturum Yönetimi</h5>
                                        </div>
                                        <p class="text-muted fs-6 mb-0">Login akışı, cookie otomatik giriş ve public sayfa yönetimi.</p>
                                    </div>
                                </a>
                            </div>
                            <div class="col-md-4">
                                <a href="/docs/languages" class="card border border-dashed card-flush h-100 text-decoration-none hover-elevate-up">
                                    <div class="card-body p-6">
                                        <div class="d-flex align-items-center mb-3">
                                            <i class="fa-solid fa-message fs-2x text-primary me-3"></i>
                                            <h5 class="mb-0 fw-bold text-body">Dil Sistemi</h5>
                                        </div>
                                        <p class="text-muted fs-6 mb-0">JSON tabanlı çoklu dil desteği ve LSL Studio entegrasyonu.</p>
                                    </div>
                                </a>
                            </div>
                        </div>
                    </div>
                    <!--end::Section 6-->

                </div>
            </div>

        </div>
    </div>
</div>

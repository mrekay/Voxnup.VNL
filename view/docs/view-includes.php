<div class="d-flex flex-column flex-column-fluid">
    <div id="kt_app_content" class="app-content flex-column-fluid">
        <div id="kt_app_content_container" class="app-container container-xxl">

            <div class="vnl-page-hero mb-12 mt-6">
                <div class="vnl-overline mb-3">// docs/view-includes</div>
                <h1 class="fs-2hx fw-bold mb-4 text-body-emphasis">View Includes Sistemi</h1>
                <div class="vnl-lead"><code>view/includes/</code> dizini, sayfalara özgü CSS, JS ve modal dosyalarını
                    organize etmek için kullanılır. VNL bu dizini otomatik olarak yükler — siz yalnızca doğru klasöre doğru dosyayı koyarsınız.</div>
            </div>

            <!--begin::Dizin yapısı kartları-->
            <div class="row g-5 mb-10">
                <div class="col-md-3">
                    <div class="card border border-dashed h-100">
                        <div class="card-body p-6 d-flex align-items-center">
                            <div class="symbol symbol-50px me-4">
                                <span class="symbol-label bg-primary-subtle">
                                    <i class="fas fa-file-code fs-2 text-primary"></i>
                                </span>
                            </div>
                            <div>
                                <div class="fw-bold text-body fs-5">header/</div>
                                <div class="text-muted fs-7">Sayfa başı CSS dosyaları</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border border-dashed h-100">
                        <div class="card-body p-6 d-flex align-items-center">
                            <div class="symbol symbol-50px me-4">
                                <span class="symbol-label bg-success-subtle">
                                    <i class="fas fa-file-code fs-2 text-success"></i>
                                </span>
                            </div>
                            <div>
                                <div class="fw-bold text-body fs-5">footer/</div>
                                <div class="text-muted fs-7">Sayfa sonu JS dosyaları</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border border-dashed h-100">
                        <div class="card-body p-6 d-flex align-items-center">
                            <div class="symbol symbol-50px me-4">
                                <span class="symbol-label bg-warning-subtle">
                                    <i class="fas fa-layer-group fs-2 text-warning"></i>
                                </span>
                            </div>
                            <div>
                                <div class="fw-bold text-body fs-5">static/</div>
                                <div class="text-muted fs-7">Her sayfaya otomatik yüklenenler</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border border-dashed h-100">
                        <div class="card-body p-6 d-flex align-items-center">
                            <div class="symbol symbol-50px me-4">
                                <span class="symbol-label bg-info-subtle">
                                    <i class="fas fa-window-restore fs-2 text-info"></i>
                                </span>
                            </div>
                            <div>
                                <div class="fw-bold text-body fs-5">modals/</div>
                                <div class="text-muted fs-7">Sayfalara ait modal şablonları</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!--end::Dizin yapısı kartları-->

            <div class="card shadow-sm border-0 mb-10">
                <div class="card-body p-10">

                    <!--begin::Section 1 - Dizin Yapısı-->
                    <div class="mb-15">
                        <h3 class="fw-bold text-body-emphasis mb-4">1. Tam Dizin Yapısı</h3>
                        <p class="fs-5 text-body mb-4">
                            <code>view/includes/</code> altındaki tüm dizinler ve bunların amacı:
                        </p>

                        <pre class="vnl-code mb-4"><code>view/
└── includes/
    ├── static/
    │   ├── head-standard.php       ← Her sayfada &lt;head&gt;'e eklenir (CSS, meta)
    │   └── script-standard.php     ← Her sayfada &lt;head&gt;'e eklenir (JS global değişkenler)
    │
    ├── header/                     ← Sayfa başına otomatik/manuel yüklenen CSS
    │   ├── head-index.php          ← Otomatik: sadece "index" route için
    │   ├── head-login.php          ← Otomatik: sadece "login" route için
    │   └── proje-app/
    │       └── head-proje-list.php ← Manuel: $page_config['css_files'] ile
    │
    ├── footer/                     ← Sayfa sonuna otomatik/manuel yüklenen JS
    │   ├── script-index.php        ← Otomatik: sadece "index" route için
    │   ├── script-login.php        ← Otomatik: sadece "login" route için
    │   └── proje-app/
    │       └── script-proje-list.php ← Manuel: $page_config['js_files'] ile
    │
    └── modals/                     ← Sayfalara ait modal HTML şablonları
        ├── kullanici/
        │   ├── KullaniciEkle.php
        │   └── KullaniciDuzenle.php
        └── urun/
            └── UrunEkle.php</code></pre>
                    </div>
                    <!--end::Section 1-->

                    <!--begin::Section 2 - static/ otomatik yükleme-->
                    <div class="mb-15">
                        <h3 class="fw-bold text-body-emphasis mb-4">2. <code>static/</code> — Her Sayfaya Otomatik Yüklenenler</h3>
                        <p class="fs-5 text-body mb-4">
                            Bu iki dosya <strong>her sayfa yüklendiğinde otomatik olarak</strong> <code>&lt;head&gt;</code> içine eklenir.
                            Hiçbir controller veya view dosyasında bunları çağırmanız gerekmez.
                        </p>

                        <div class="table-responsive mb-6">
                            <table class="table table-bordered table-striped align-middle fs-6">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Dosya</th>
                                        <th>İçerik</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td><code>static/head-standard.php</code></td>
                                        <td>Meta etiketleri, pre-paint tema modu script'i, <code>vnl.min.css</code> ve <code>fa.min.css</code></td>
                                    </tr>
                                    <tr>
                                        <td><code>static/script-standard.php</code></td>
                                        <td><code>$SITE_URL</code>, <code>VNL_USER_ID</code>, <code>VNL_LANG</code> dil köprüsü, vendor paketi (jQuery + Bootstrap + SweetAlert2 + SlimSelect), <code>vnl.min.js</code>, <code>vnl.js</code>, <code>showMessage()</code> ve <code>showModalMessage()</code> yardımcıları</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="alert alert-info d-flex align-items-center p-4 mb-0">
                            <i class="fa-solid fa-circle-info fs-2 text-info me-3"></i>
                            <span class="text-body fs-6">
                                <code>static/</code> içindeki dosyalar <code>view/static/header.php</code> tarafından yüklenir.
                                Buraya koyduğunuz dosyaların tüm sayfaları etkileyeceğini unutmayın —
                                sayfa genelindeki CSS/JS buraya, sayfaya özgü olanlar <code>header/</code> veya <code>footer/</code> klasörüne gider.
                            </span>
                        </div>
                    </div>
                    <!--end::Section 2-->

                    <!--begin::Section 3 - header/ otomatik yükleme-->
                    <div class="mb-15">
                        <h3 class="fw-bold text-body-emphasis mb-4">3. <code>header/</code> — Sayfa Başı CSS (Otomatik Yükleme)</h3>
                        <p class="fs-5 text-body mb-4">
                            <code>header.php</code>, <code>view/includes/header/head-{route(0)}.php</code> dosyasını
                            <strong>eğer mevcutsa otomatik olarak yükler</strong>.
                        </p>

                        <pre class="vnl-code mb-4"><code>// view/static/header.php içinde:
if (file_exists(view('includes/header/head-' . route(0))))
    require_once view('includes/header/head-' . route(0));</code></pre>

                        <p class="fs-5 text-body mb-4">
                            <code>route(0)</code>, URL'nin ilk segmentini döndürür.
                            Örneğin URL <code>/index</code> ise <code>route(0)</code> = <code>index</code> olur
                            ve <code>includes/header/head-index.php</code> otomatik yüklenir.
                        </p>

                        <div class="alert alert-primary d-flex align-items-center p-4 mb-4">
                            <i class="fa-solid fa-circle-info fs-2 text-primary me-3"></i>
                            <div class="text-body fs-6">
                                <strong>Nasıl kullanılır?</strong><br>
                                <code>/kullanici</code> route'u için sayfa başına özel CSS eklemek istiyorsanız
                                <code>view/includes/header/head-kullanici.php</code> dosyasını oluşturun — otomatik yüklenecektir.
                            </div>
                        </div>

                        <p class="fs-5 text-body mb-2">
                            Aynı route altında birden fazla sayfanın farklı CSS ihtiyacı varsa
                            <strong>manuel yükleme</strong> kullanın (bkz. Bölüm 5).
                        </p>
                    </div>
                    <!--end::Section 3-->

                    <!--begin::Section 4 - footer/ otomatik yükleme-->
                    <div class="mb-15">
                        <h3 class="fw-bold text-body-emphasis mb-4">4. <code>footer/</code> — Sayfa Sonu JS (Otomatik Yükleme)</h3>
                        <p class="fs-5 text-body mb-4">
                            <code>footer.php</code>, <code>view/includes/footer/script-{route(0)}.php</code> dosyasını
                            <strong>eğer mevcutsa otomatik olarak yükler</strong>.
                        </p>

                        <pre class="vnl-code mb-4"><code>// view/static/footer.php içinde:
if (file_exists(view('includes/footer/script-' . route(0))))
    require_once view('includes/footer/script-' . route(0));</code></pre>

                        <p class="fs-5 text-body mb-4">
                            Sayfa başı otomatik yüklemesiyle aynı mantık çalışır; fark olarak bu dosyalar
                            <code>&lt;/body&gt;</code>'den hemen önce yüklenir — dolayısıyla DOM hazır olduğundan
                            jQuery event listener ve AJAX kodları buraya yazılır.
                        </p>

                        <h5 class="fw-bold text-body-emphasis mb-3">Örnek: script-kullanici.php</h5>
                        <pre class="vnl-code mb-0"><code>&lt;script&gt;
    // Bu kod sadece /kullanici route'u yüklendiğinde çalışır
    $('#btnKullaniciEkle').click(function () {
        showModal('ModalKullaniciEkle');
    });

    function kullaniciSil(id) {
        $.ajax({
            type: 'POST',
            url: 'app/kullanici/sil',
            data: { id: id },
            success: function (res) {
                showMessage('Kullanıcı silindi.', 'success');
                KullaniciTable.ajax.reload();
            },
            error: function (err) {
                showMessage(err.responseJSON.message, 'error');
            }
        });
    }
&lt;/script&gt;</code></pre>
                    </div>
                    <!--end::Section 4-->

                    <!--begin::Section 5 - Manuel yükleme-->
                    <div class="mb-15">
                        <h3 class="fw-bold text-body-emphasis mb-4">5. Manuel Yükleme — <code>css_files</code> ve <code>js_files</code></h3>
                        <p class="fs-5 text-body mb-4">
                            Bir route altındaki farklı sayfaların farklı CSS/JS ihtiyacı olduğunda,
                            controller dosyasında <code>$page_config</code> içine dosya listesi ekleyebilirsiniz.
                        </p>

                        <pre class="vnl-code mb-4"><code>// controller/kullanici/liste.php
$page_config = [
    'topbar'      => true,
    'leftsidebar' => true,
    'css_files'   => ['kullanici/head-liste.php'],       // view/includes/header/ altında aranır
    'js_files'    => ['kullanici/script-liste.php'],     // view/includes/footer/ altında aranır
];</code></pre>

                        <div class="table-responsive mb-6">
                            <table class="table table-bordered table-striped align-middle fs-6">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Config Anahtarı</th>
                                        <th>Arama Yolu</th>
                                        <th>Yüklendiği Yer</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td><code>css_files</code></td>
                                        <td><code>view/includes/header/{dosya}</code></td>
                                        <td><code>&lt;head&gt;</code> içi (sayfa başı)</td>
                                    </tr>
                                    <tr>
                                        <td><code>js_files</code></td>
                                        <td><code>view/includes/footer/{dosya}</code></td>
                                        <td><code>&lt;/body&gt;</code> öncesi (sayfa sonu)</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="alert alert-warning d-flex align-items-center p-4 mb-0">
                            <i class="fa-solid fa-circle-info fs-2 text-warning me-3"></i>
                            <span class="text-body fs-6">
                                Dosya bulunamazsa sessizce atlanır — PHP hatası oluşmaz. Yolu doğru yazdığınızdan emin olun.
                            </span>
                        </div>
                    </div>
                    <!--end::Section 5-->

                    <!--begin::Section 6 - modals/-->
                    <div class="mb-15">
                        <h3 class="fw-bold text-body-emphasis mb-4">6. <code>modals/</code> — Modal Şablonları</h3>
                        <p class="fs-5 text-body mb-4">
                            Modal HTML şablonları <code>view/includes/modals/</code> altında tutulur.
                            Felsefe gereği her sayfanın modalleri <strong>kendi alt klasöründe gruplandırılır</strong>.
                        </p>

                        <pre class="vnl-code mb-4"><code>view/includes/modals/
├── kullanici/
│   ├── KullaniciEkle.php
│   └── KullaniciDuzenle.php
└── urun/
    └── UrunEkle.php</code></pre>

                        <p class="fs-5 text-body mb-4">
                            Modal dosyaları <code>view/includes/modals.php</code> üzerinden yüklenir.
                            Bu dosya her sayfada <code>footer.php</code> tarafından otomatik çağrılır.
                            Siz sadece <code>modals.php</code>'ye hangi modal dosyalarını dahil edeceğinizi belirtirsiniz:
                        </p>

                        <pre class="vnl-code mb-4"><code>// view/static/modals.php
require_once view('includes/modals/kullanici/KullaniciEkle');
require_once view('includes/modals/kullanici/KullaniciDuzenle');
require_once view('includes/modals/urun/UrunEkle');</code></pre>

                        <div class="alert alert-success d-flex align-items-center p-4 mb-0">
                            <i class="fa-solid fa-circle-info fs-2 text-success me-3"></i>
                            <span class="text-body fs-6">
                                <strong>Neden alt klasör?</strong> Onlarca modal tek dosyada yönetildiğinde bakımı zorlaşır.
                                Klasör bazlı gruplama; hangi modalın hangi sayfaya ait olduğunu açıkça ortaya koyar
                                ve ekipler arasındaki çakışmaları önler.
                            </span>
                        </div>
                    </div>
                    <!--end::Section 6-->

                    <!--begin::Section 7 - Yükleme sırası özeti-->
                    <div class="mb-10">
                        <h3 class="fw-bold text-body-emphasis mb-4">7. Yükleme Sırası — Tam Akış</h3>
                        <p class="fs-5 text-body mb-6">
                            Bir sayfa açıldığında <code>includes/</code> sistemi şu sırayla çalışır:
                        </p>

                        <div class="timeline">
                            <div class="timeline-item mb-4">
                                <div class="timeline-line w-40px"></div>
                                <div class="timeline-icon symbol symbol-circle symbol-40px">
                                    <div class="symbol-label bg-primary-subtle">
                                        <i class="fas fa-1 text-primary fs-5"></i>
                                    </div>
                                </div>
                                <div class="timeline-content mb-5 mt-n2 ms-4">
                                    <div class="fw-bold fs-5 text-body-emphasis mb-1">head-standard.php yüklenir</div>
                                    <div class="text-muted fs-6">Meta etiketleri ve global CSS paketleri <code>&lt;head&gt;</code>'e eklenir.</div>
                                </div>
                            </div>
                            <div class="timeline-item mb-4">
                                <div class="timeline-line w-40px"></div>
                                <div class="timeline-icon symbol symbol-circle symbol-40px">
                                    <div class="symbol-label bg-success-subtle">
                                        <i class="fas fa-2 text-success fs-5"></i>
                                    </div>
                                </div>
                                <div class="timeline-content mb-5 mt-n2 ms-4">
                                    <div class="fw-bold fs-5 text-body-emphasis mb-1">script-standard.php yüklenir</div>
                                    <div class="text-muted fs-6">Global JS değişkenleri ve VNL Theme vendor paketi <code>&lt;head&gt;</code>'e eklenir.</div>
                                </div>
                            </div>
                            <div class="timeline-item mb-4">
                                <div class="timeline-line w-40px"></div>
                                <div class="timeline-icon symbol symbol-circle symbol-40px">
                                    <div class="symbol-label bg-warning-subtle">
                                        <i class="fas fa-3 text-warning fs-5"></i>
                                    </div>
                                </div>
                                <div class="timeline-content mb-5 mt-n2 ms-4">
                                    <div class="fw-bold fs-5 text-body-emphasis mb-1"><code>head-{route(0)}.php</code> yüklenir (varsa)</div>
                                    <div class="text-muted fs-6">O route'a ait otomatik CSS dosyası <code>&lt;head&gt;</code>'e eklenir.</div>
                                </div>
                            </div>
                            <div class="timeline-item mb-4">
                                <div class="timeline-line w-40px"></div>
                                <div class="timeline-icon symbol symbol-circle symbol-40px">
                                    <div class="symbol-label bg-info-subtle">
                                        <i class="fas fa-4 text-info fs-5"></i>
                                    </div>
                                </div>
                                <div class="timeline-content mb-5 mt-n2 ms-4">
                                    <div class="fw-bold fs-5 text-body-emphasis mb-1"><code>css_files</code> listesi yüklenir (varsa)</div>
                                    <div class="text-muted fs-6">Controller'da tanımlanan manuel CSS dosyaları <code>&lt;head&gt;</code>'e eklenir.</div>
                                </div>
                            </div>
                            <div class="timeline-item mb-4">
                                <div class="timeline-line w-40px"></div>
                                <div class="timeline-icon symbol symbol-circle symbol-40px">
                                    <div class="symbol-label bg-danger-subtle">
                                        <i class="fas fa-5 text-danger fs-5"></i>
                                    </div>
                                </div>
                                <div class="timeline-content mb-5 mt-n2 ms-4">
                                    <div class="fw-bold fs-5 text-body-emphasis mb-1">Sayfa içeriği render edilir</div>
                                    <div class="text-muted fs-6">Controller, view dosyasını çağırır; HTML gövdesi oluşturulur.</div>
                                </div>
                            </div>
                            <div class="timeline-item mb-4">
                                <div class="timeline-line w-40px"></div>
                                <div class="timeline-icon symbol symbol-circle symbol-40px">
                                    <div class="symbol-label bg-primary-subtle">
                                        <i class="fas fa-6 text-primary fs-5"></i>
                                    </div>
                                </div>
                                <div class="timeline-content mb-5 mt-n2 ms-4">
                                    <div class="fw-bold fs-5 text-body-emphasis mb-1">Modal şablonları yüklenir</div>
                                    <div class="text-muted fs-6"><code>view/static/modals.php</code> üzerinden tüm modal HTML'leri sayfaya eklenir.</div>
                                </div>
                            </div>
                            <div class="timeline-item mb-4">
                                <div class="timeline-line w-40px"></div>
                                <div class="timeline-icon symbol symbol-circle symbol-40px">
                                    <div class="symbol-label bg-success-subtle">
                                        <i class="fas fa-7 text-success fs-5"></i>
                                    </div>
                                </div>
                                <div class="timeline-content mb-5 mt-n2 ms-4">
                                    <div class="fw-bold fs-5 text-body-emphasis mb-1"><code>script-{route(0)}.php</code> yüklenir (varsa)</div>
                                    <div class="text-muted fs-6">O route'a ait otomatik JS dosyası <code>&lt;/body&gt;</code> öncesine eklenir.</div>
                                </div>
                            </div>
                            <div class="timeline-item">
                                <div class="timeline-icon symbol symbol-circle symbol-40px">
                                    <div class="symbol-label bg-warning-subtle">
                                        <i class="fas fa-8 text-warning fs-5"></i>
                                    </div>
                                </div>
                                <div class="timeline-content mt-n2 ms-4">
                                    <div class="fw-bold fs-5 text-body-emphasis mb-1"><code>js_files</code> listesi yüklenir (varsa)</div>
                                    <div class="text-muted fs-6">Controller'da tanımlanan manuel JS dosyaları <code>&lt;/body&gt;</code> öncesine eklenir.</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!--end::Section 7-->

                </div>
            </div>

        </div>
    </div>
</div>

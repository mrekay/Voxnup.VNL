<div class="d-flex flex-column flex-column-fluid">
    <div id="kt_app_content" class="app-content flex-column-fluid">
        <div id="kt_app_content_container" class="app-container container-xxl">

            <div class="vnl-page-hero mb-12 mt-6">
                <div class="vnl-overline mb-3">// docs/routing</div>
                <h1 class="fs-2hx fw-bold mb-4 text-body-emphasis">Yönlendirme (Routing) ve Controller</h1>
                <div class="vnl-lead">VNL'de karmaşık rota tanımlamaları (<code>Route::get</code>) yoktur. URL yapısı otomatik olarak parçalanır ve doğrudan <code>controller/</code> klasöründeki ilgili PHP dosyasını çalıştırır.</div>
            </div>

            <div class="alert bg-primary-subtle border border-primary d-flex align-items-center p-5 mb-10">
                <i class="fa-solid fa-lightbulb fs-2hx text-primary me-4"></i>
                <div class="d-flex flex-column text-primary">
                    <h4 class="mb-1 text-primary">Temel Kural</h4>
                    <span>URL'deki ilk segment her zaman çalıştırılacak <b>Controller</b> dosyasının adını belirler. Dosya yoksa <code>controller/404.php</code> devreye girer.</span>
                </div>
            </div>

            <div class="card shadow-sm border-0 mb-10">
                <div class="card-body p-10">

                    <!--begin::Section 1-->
                    <div class="mb-10">
                        <h3 class="fw-bold text-body-emphasis mb-4">1. URL Parçalama Mantığı</h3>
                        <p class="fs-5 text-body mb-5">
                            Kullanıcı bir adrese girdiğinde, <code>index.php</code> URL'yi <code>/</code> ile bölerek bir <code>$route</code> dizisi oluşturur. Bu dizinin elemanlarına herhangi bir yerden <code>route(index)</code> yardımcı fonksiyonu ile ulaşılabilir. Proje bir alt klasörde çalışıyorsa alt klasör adı otomatik olarak diziden çıkarılır.
                        </p>

                        <div class="table-responsive border rounded bg-body-tertiary mb-5">
                            <table class="table align-middle table-row-dashed fs-6 gy-4 mb-0">
                                <thead>
                                <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0 border-bottom">
                                    <th class="ps-5 min-w-250px">Örnek URL İsteği</th>
                                    <th class="min-w-150px">route(0)</th>
                                    <th class="min-w-150px">route(1)</th>
                                    <th class="min-w-150px">route(2)</th>
                                </tr>
                                </thead>
                                <tbody class="text-body fw-semibold">
                                <tr>
                                    <td class="ps-5"><code>site.com/kullanici/profil/15</code></td>
                                    <td><span class="badge badge-soft-primary">kullanici</span><br><small class="text-muted">(Controller dosyası)</small></td>
                                    <td><span class="badge badge-soft-success">profil</span><br><small class="text-muted">(Aksiyon / Sayfa)</small></td>
                                    <td><span class="badge badge-soft-info">15</span><br><small class="text-muted">(ID / Parametre)</small></td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>site.com/docs/routing</code></td>
                                    <td><span class="badge badge-soft-primary">docs</span><br><small class="text-muted">(Klasör adı)</small></td>
                                    <td><span class="badge badge-soft-success">routing</span><br><small class="text-muted">(Klasör içi dosya)</small></td>
                                    <td><span class="text-muted">—</span></td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <!--end::Section 1-->

                    <div class="separator separator-dashed my-8"></div>

                    <!--begin::Section 2-->
                    <div class="mb-10">
                        <h3 class="fw-bold text-body-emphasis mb-4">2. Controller Anatomisi</h3>
                        <p class="fs-5 text-body mb-4">
                            Her controller dosyası aynı temel yapıyı izler: önce <code>$meta</code> ve <code>$page_config</code> tanımlanır, ardından veritabanı işlemleri yapılır, son olarak <code>header</code> + <code>içerik</code> + <code>footer</code> view'ları birleştirilerek ekrana basılır. Sınıf tanımlamasına (<code>class</code>) ya da constructor'a gerek yoktur; dosya düz PHP olarak çalışır.
                        </p>

                        <div class="vnl-code mb-5">
                            <pre class=" mb-0" style="background: transparent; white-space: pre-wrap;"><code>&lt;?php
// Dosya: controller/kullanici.php
// Erişim: site.com/kullanici  veya  site.com/kullanici/profil/15

// 1. META — Sekme başlığı ve breadcrumb bilgileri
$meta = [
    'title'       => 'Kullanıcılar',
    'upper_title' => 'Yönetim',     // breadcrumb üst başlık (opsiyonel)
    'description' => '',
    'keywords'    => '',
];

// 2. PAGE CONFIG — Layout bileşenlerini kontrol eder
$page_config = [
    'topbar'        => true,
    'leftsidebar'   => true,
    'contentheader' => true,
];

// 3. VERİTABANI — route() ile hangi sayfada olduğumuzu anlıyoruz
$islem = route(1) ?: 'liste';

if ($islem === 'profil') {
    $kullanici = Capsule::table('tbl_users')
        ->where('id', route(2))
        ->first();

    // 4. VIEW — Header + sayfa içeriği + footer birleştirilir
    require view('static/header');
    require view('kullanici/profil');
    require view('static/footer');

} else {
    $kullanicilar = Capsule::table('tbl_users')->get();

    require view('static/header');
    require view('kullanici/liste');
    require view('static/footer');
}
</code></pre>
                        </div>

                        <div class="alert bg-warning-subtle border border-warning d-flex align-items-center p-4">
                            <i class="fa-solid fa-circle-info fs-2 text-warning me-3"></i>
                            <span class="text-body fs-6">Controller'da tanımlanan tüm değişkenler (örn. <code>$kullanici</code>) view dosyasında doğrudan kullanılabilir. <code>require view('...')</code> çağrısı aynı PHP kapsamında çalışır.</span>
                        </div>
                    </div>
                    <!--end::Section 2-->

                    <div class="separator separator-dashed my-8"></div>

                    <!--begin::Section 3 — meta-->
                    <div class="mb-10">
                        <h3 class="fw-bold text-body-emphasis mb-4">3. <code>$meta</code> Dizisi</h3>
                        <p class="fs-5 text-body mb-4">
                            <code>$meta</code>, sayfanın <code>&lt;head&gt;</code> etiketi ile üst toolbar alanını besler. <code>static/header</code> view'u yüklenmeden önce tanımlanmış olmalıdır.
                        </p>

                        <div class="table-responsive border rounded bg-body-tertiary mb-5">
                            <table class="table align-middle table-row-dashed fs-6 gy-4 mb-0">
                                <thead>
                                <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0 border-bottom">
                                    <th class="ps-5 min-w-180px">Alan</th>
                                    <th class="min-w-100px">Tip</th>
                                    <th class="min-w-120px">Varsayılan</th>
                                    <th class="min-w-300px">Açıklama</th>
                                </tr>
                                </thead>
                                <tbody class="text-body fw-semibold">
                                <tr>
                                    <td class="ps-5"><code>title</code></td>
                                    <td>string</td>
                                    <td><code>""</code></td>
                                    <td>Tarayıcı sekme başlığı (<code>&lt;title&gt;</code>) ve toolbar'da gösterilen sayfa başlığı.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>upper_title</code></td>
                                    <td>string</td>
                                    <td><em>Yok</em></td>
                                    <td>Breadcrumb'da üst kategori olarak gösterilir. Tanımlanmazsa breadcrumb sadece uygulama adı + sayfa başlığını gösterir.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>description</code></td>
                                    <td>string</td>
                                    <td><code>""</code></td>
                                    <td><code>&lt;meta name="description"&gt;</code> etiketini doldurur.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>keywords</code></td>
                                    <td>string</td>
                                    <td><code>""</code></td>
                                    <td><code>&lt;meta name="keywords"&gt;</code> etiketini doldurur.</td>
                                </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="vnl-code">
                            <pre class=" mb-0" style="background: transparent; white-space: pre-wrap;"><code>$meta = [
    'title'       => 'Proje Listesi',
    'upper_title' => 'Proje Yönetimi', // breadcrumb: Uygulama > Proje Yönetimi > Proje Listesi
    'description' => 'Aktif projelerin listesi',
    'keywords'    => 'proje, yönetim',
];
</code></pre>
                        </div>
                    </div>
                    <!--end::Section 3-->

                    <div class="separator separator-dashed my-8"></div>

                    <!--begin::Section 4 — page_config-->
                    <div class="mb-10">
                        <h3 class="fw-bold text-body-emphasis mb-4">4. <code>$page_config</code> Dizisi</h3>
                        <p class="fs-5 text-body mb-4">
                            <code>$page_config</code>, layout bileşenlerinin (sidebar, topbar, footer vb.) görünürlüğünü ve davranışını yönetir. Tanımlanmayan anahtarlar <code>static/header</code> tarafından aşağıdaki varsayılanlarla doldurulur.
                        </p>

                        <div class="table-responsive border rounded bg-body-tertiary mb-6">
                            <table class="table align-middle table-row-dashed fs-6 gy-4 mb-0">
                                <thead>
                                <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0 border-bottom">
                                    <th class="ps-5 min-w-200px">Anahtar</th>
                                    <th class="min-w-100px">Varsayılan</th>
                                    <th class="min-w-100px">Değerler</th>
                                    <th class="min-w-300px">Açıklama</th>
                                </tr>
                                </thead>
                                <tbody class="text-body fw-semibold">
                                <tr>
                                    <td class="ps-5"><code>leftsidebar</code></td>
                                    <td><span class="badge badge-soft-success">true</span></td>
                                    <td><code>true / false</code></td>
                                    <td>Sol menü sidebar'ını gösterir veya gizler. <code>false</code> yapıldığında sayfa tam genişliğe geçer.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>topbar</code></td>
                                    <td><span class="badge badge-soft-success">true</span></td>
                                    <td><code>true / false</code></td>
                                    <td>Üst navbar'ı (kullanıcı menüsü, bildirimler) gösterir veya gizler.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>contentheader</code></td>
                                    <td><span class="badge badge-soft-success">true</span></td>
                                    <td><code>true / false</code></td>
                                    <td>Sayfa başlığı ve breadcrumb içeren toolbar alanını gösterir veya gizler.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>footer</code></td>
                                    <td><span class="badge badge-soft-danger">false</span></td>
                                    <td><code>true / false</code></td>
                                    <td>Alt footer alanını gösterir. Varsayılan olarak kapalıdır.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>loader</code></td>
                                    <td><span class="badge badge-soft-success">true</span></td>
                                    <td><code>true / false</code></td>
                                    <td>Sayfa yüklenirken gösterilen yükleme animasyonunu etkinleştirir.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>public</code></td>
                                    <td><em>Tanımsız</em></td>
                                    <td><code>true</code></td>
                                    <td>Giriş yapılmadan erişime izin verir. Tanımlanmazsa veya <code>false</code> ise login gerekir. Detay için <a href="/docs/session" class="text-primary fw-bold">Oturum Yönetimi</a> sayfasına bakın.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>toolbaritems</code></td>
                                    <td><em>Tanımsız</em></td>
                                    <td>Dizi</td>
                                    <td>Toolbar'a buton veya dropdown ekler. Aşağıda detaylandırılmıştır.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>css_files</code></td>
                                    <td><em>Tanımsız</em></td>
                                    <td>string[]</td>
                                    <td><code>view/includes/header/</code> altındaki sayfa özel CSS dosyalarını <code>&lt;head&gt;</code>'e ekler.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>js_files</code></td>
                                    <td><em>Tanımsız</em></td>
                                    <td>string[]</td>
                                    <td><code>view/includes/footer/</code> altındaki sayfa özel JS dosyalarını <code>&lt;/body&gt;</code> öncesine ekler.</td>
                                </tr>
                                </tbody>
                            </table>
                        </div>

                        <h5 class="fw-bold text-body-emphasis mb-3">Toolbar Butonları (<code>toolbaritems</code>)</h5>
                        <p class="fs-6 text-body mb-4">
                            <code>contentheader</code> alanının sağ kısmına buton veya dropdown eklemek için <code>toolbaritems</code> kullanılır. Her eleman bir buton veya dropdown tanımlar.
                        </p>

                        <div class="vnl-code mb-4">
                            <pre class=" mb-0" style="background: transparent; white-space: pre-wrap;"><code>$page_config = [
    'topbar'        => true,
    'leftsidebar'   => true,
    'contentheader' => true,
    'toolbaritems'  => [
        // Standart buton
        [
            'type'   => 'button',
            'id'     => 'btn_yeni_ekle',
            'text'   => 'Yeni Ekle',
            'class'  => 'btn btn-primary btn-sm',
            'icon'   => 'fa-solid fa-plus',
            'custom' => 'data-bs-toggle="modal" data-bs-target="#modal_kullanici_ekle"',
        ],
        // Dropdown buton
        [
            'type'  => 'button',
            'text'  => 'Dışa Aktar',
            'class' => 'btn btn-soft-primary btn-sm dropdown-toggle',
            'icon'  => 'fa-solid fa-file-arrow-down',
            'items' => [
                [
                    'type'   => 'a',
                    'text'   => 'Excel',
                    'class'  => 'dropdown-item',
                    'custom' => 'href="#" id="btn_export_excel"',
                ],
                [
                    'type'   => 'a',
                    'text'   => 'PDF',
                    'class'  => 'dropdown-item',
                    'custom' => 'href="#" id="btn_export_pdf"',
                ],
            ],
        ],
    ],
];
</code></pre>
                        </div>
                    </div>
                    <!--end::Section 4-->

                    <div class="separator separator-dashed my-8"></div>

                    <!--begin::Section 5 — Grouped Controller-->
                    <div class="mb-10">
                        <h3 class="fw-bold text-body-emphasis mb-4">5. Gruplu Controller (Alt Klasör Desteği)</h3>
                        <p class="fs-5 text-body mb-4">
                            <code>route(0)</code> değeri bir <b>klasöre</b> karşılık geliyorsa sistem otomatik olarak <code>controller/{route(0)}/{route(1)}.php</code> dosyasını arar. İlgili controller'ları alt klasörler halinde gruplandırabilirsiniz.
                        </p>

                        <div class="row g-5 mb-5">
                            <div class="col-md-4">
                                <div class="border border-dashed rounded p-5 h-100">
                                    <div class="d-flex align-items-center mb-3">
                                        <i class="fa-solid fa-cubes fs-2 text-primary me-3"></i>
                                        <h6 class="mb-0 fw-bold">URL</h6>
                                    </div>
                                    <code class="fs-6">site.com/docs/routing</code>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="border border-dashed rounded p-5 h-100">
                                    <div class="d-flex align-items-center mb-3">
                                        <i class="fa-solid fa-file fs-2 text-warning me-3"></i>
                                        <h6 class="mb-0 fw-bold">Çalışan Dosya</h6>
                                    </div>
                                    <code class="fs-6">controller/docs/routing.php</code>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="border border-dashed rounded p-5 h-100">
                                    <div class="d-flex align-items-center mb-3">
                                        <i class="fa-solid fa-arrow-right fs-2 text-success me-3"></i>
                                        <h6 class="mb-0 fw-bold">Parametreler</h6>
                                    </div>
                                    <code class="fs-6">route(2) ve üzeri</code>
                                </div>
                            </div>
                        </div>
                        <div class="alert bg-warning-subtle border border-warning d-flex align-items-center p-4">
                            <i class="fa-solid fa-circle-info fs-2 text-warning me-3"></i>
                            <span class="text-body fs-6">Klasör içinde dosya bulunamazsa sistem otomatik olarak <code>controller/404.php</code>'ye yönlendirir.</span>
                        </div>
                    </div>
                    <!--end::Section 5-->

                    <div class="separator separator-dashed my-8"></div>

                    <!--begin::Section 6 — View-->
                    <div class="mb-10">
                        <h3 class="fw-bold text-body-emphasis mb-4">6. <code>view()</code> Fonksiyonu</h3>
                        <p class="fs-5 text-body mb-4">
                            Controller içinden HTML çıktısı vermek için <code>require view('...')</code> kullanılır. Fonksiyon, <code>setting('theme')</code> ile belirlenen aktif tema klasörüne göre tam dosya yolunu oluşturur.
                        </p>

                        <div class="vnl-code mb-4">
                            <pre class=" mb-0" style="background: transparent; white-space: pre-wrap;"><code>// Tam yol: view/{tema}/kullanici/profil.php
require view('kullanici/profil');

// Tipik controller şablonu
require view('static/header');   // Layout başlangıcı
require view('kullanici/profil'); // Sayfa içeriği
require view('static/footer');   // Layout sonu
</code></pre>
                        </div>
                    </div>
                    <!--end::Section 6-->

                    <div class="separator separator-dashed my-8"></div>

                    <!--begin::Section 7 — modal-->
                    <div class="mb-0">
                        <h3 class="fw-bold text-body-emphasis mb-4">7. <code>modal()</code> Fonksiyonu</h3>
                        <p class="fs-5 text-body mb-4">
                            Bootstrap 5 modal'larını ayrı PHP dosyaları olarak yönetmek için <code>modal()</code> fonksiyonu kullanılır. Bu fonksiyon <code>view/includes/modals/</code> altındaki dosyaya gider; tema klasöründen bağımsızdır.
                        </p>

                        <div class="row g-5 mb-5">
                            <div class="col-md-6">
                                <div class="border border-dashed rounded p-5 h-100">
                                    <h6 class="fw-bold mb-3">Tekil Modal</h6>
                                    <code class="d-block mb-2">require modal('KullaniciEkle');</code>
                                    <span class="text-muted fs-7">→ <code>view/includes/modals/KullaniciEkle.php</code></span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="border border-dashed rounded p-5 h-100">
                                    <h6 class="fw-bold mb-3">Alt Klasör</h6>
                                    <code class="d-block mb-2">require modal('KullaniciEkle', 'kullanici');</code>
                                    <span class="text-muted fs-7">→ <code>view/includes/modals/kullanici/KullaniciEkle.php</code></span>
                                </div>
                            </div>
                        </div>

                        <p class="fs-6 text-body mb-3">Modal'lar genellikle <code>static/footer</code> <b>öncesinde</b> yüklenir, böylece içerik view'unun içinde veya sonrasında jQuery/JS ile tetiklenebilirler:</p>

                        <div class="vnl-code">
                            <pre class=" mb-0" style="background: transparent; white-space: pre-wrap;"><code>require view('static/header');
require view('kullanici/liste');
require modal('KullaniciEkle', 'kullanici');   // Modal DOM'a eklendi
require modal('KullaniciSil', 'kullanici');
require view('static/footer');
</code></pre>
                        </div>
                        <p class="fs-6 text-muted mt-3">Modal dosyalarının yapısı ve AJAX entegrasyonu için <a href="/docs/modals" class="text-primary fw-bold">Modal Sistemi</a> sayfasına bakın.</p>
                    </div>
                    <!--end::Section 7-->

                </div>
            </div>

        </div>
    </div>
</div>

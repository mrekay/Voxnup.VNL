<div class="d-flex flex-column flex-column-fluid">
    <div id="kt_app_content" class="app-content flex-column-fluid">
        <div id="kt_app_content_container" class="app-container container-xxl">

            <div class="vnl-page-hero mb-12 mt-6">
                <div class="vnl-overline mb-3">// docs/theme</div>
                <h1 class="fs-2hx fw-bold mb-4 text-body-emphasis">VNL Theme</h1>
                <div class="vnl-lead">Bootstrap 5.3 üzerine inşa edilen, VNL'in view sistemiyle tam entegre tema katmanı.
                    Varsayılan kimlik koyudur; açık tema birinci sınıf desteklenir. Aynı tema bir ERP'yi,
                    bir pazarlama sayfasını ve bir hesap aracını layout preset'leriyle taşır.</div>
            </div>

            <div class="alert alert-primary d-flex align-items-center p-5 mb-10">
                <i class="fa-solid fa-circle-info fs-2hx text-primary me-4"></i>
                <div class="d-flex flex-column">
                    <span class="fw-bold">Kaynak <code>theme/</code> dizininde, derlenmiş çıktı <code>assets/vnl/</code> altındadır.</span>
                    <span class="text-body-secondary">Çıktı repoya commit edilir — template kullanıcısının Node/npm kurması ya da build alması gerekmez.
                    Temayı özelleştirmek isteyenler için: <code>cd theme && npm ci && npm run build</code></span>
                </div>
            </div>

            <div class="card mb-10">
                <div class="card-body p-10">

                    <!--begin::Section-->
                    <div class="mb-10">
                        <h3 class="fw-bold text-body-emphasis mb-4">1. Dosya Mimarisi</h3>
                        <p class="text-body mb-4">
                            Tema iki parçadan oluşur: <b>kaynak</b> (<code>theme/</code> — SCSS, JS modülleri, build araçları)
                            ve <b>derlenmiş çıktı</b> (<code>assets/vnl/</code>). Sayfalar yalnız çıktıyı yükler;
                            asset yükleme noktaları <code>view/includes/static/head-standard.php</code> ve
                            <code>script-standard.php</code> dosyalarıdır.
                        </p>
                        <div class="vnl-code"><pre><code>theme/
├── scss/            → vnl.scss (Bootstrap + tema) ve fa.scss (Font Awesome)
├── js/              → vnl-theme.js + modules/ (tema modu, sidebar, tooltip, lightbox)
├── tools/           → tek seferlik migrasyon codemod'ları
└── build.mjs        → vendor concat + esbuild + font kopyalama

assets/vnl/
├── css/vnl.min.css  → Bootstrap 5.3.8 + VNL Theme + SweetAlert2/SlimSelect skin'leri
├── css/bi.min.css   → Bootstrap Icons 1.13
├── css/fa.min.css   → Font Awesome Free (solid/regular/brands + v4 shim)
├── js/vendors.min.js→ jQuery 3.7.1 + Bootstrap bundle + SweetAlert2 v11 + SlimSelect
├── js/vnl.min.js    → tema davranışları
└── fonts/           → Inter variable (latin + latin-ext) ve FA webfont'ları</code></pre></div>
                    </div>
                    <!--end::Section-->

                    <div class="separator separator-dashed my-8"></div>

                    <!--begin::Section-->
                    <div class="mb-10">
                        <h3 class="fw-bold text-body-emphasis mb-4">2. Layout Preset'leri</h3>
                        <p class="text-body mb-4">
                            Controller'da <code>$page_config['layout']</code> ile sayfanın iskeleti seçilir.
                            Eski bayraklar (<code>leftsidebar</code>, <code>topbar</code>, <code>contentheader</code>,
                            <code>footer</code>) çalışmaya devam eder ve preset'i <b>ezebilir</b> —
                            merge sırası: varsayılanlar → preset → sizin <code>$page_config</code>'iniz.
                        </p>
                        <div class="table-responsive mb-5">
                            <table class="table table-row-dashed align-middle fs-7 gy-4">
                                <thead>
                                <tr class="text-start text-muted fw-bold fs-8 text-uppercase">
                                    <th>Preset</th>
                                    <th>Sidebar</th>
                                    <th>Topbar</th>
                                    <th>İçerik Başlığı</th>
                                    <th>Footer</th>
                                    <th>Tipik Kullanım</th>
                                </tr>
                                </thead>
                                <tbody class="text-body fw-semibold">
                                <tr>
                                    <td><code>app</code> <span class="badge badge-soft-primary ms-1">varsayılan</span></td>
                                    <td>✓</td><td>✓</td><td>✓</td><td>—</td>
                                    <td>ERP / yönetim paneli</td>
                                </tr>
                                <tr>
                                    <td><code>top-nav</code></td>
                                    <td>—</td><td>✓</td><td>✓</td><td>✓</td>
                                    <td>Araç / SaaS sayfaları</td>
                                </tr>
                                <tr>
                                    <td><code>landing</code></td>
                                    <td>—</td><td>✓</td><td>—</td><td>✓</td>
                                    <td>Pazarlama / tanıtım</td>
                                </tr>
                                <tr>
                                    <td><code>focus</code></td>
                                    <td>—</td><td>—</td><td>—</td><td>—</td>
                                    <td>Login, hata sayfaları (ortalanmış tek kolon)</td>
                                </tr>
                                <tr>
                                    <td><code>blank</code></td>
                                    <td>—</td><td>—</td><td>—</td><td>—</td>
                                    <td>Boş kanvas (yalnız head + asset'ler)</td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="vnl-code"><pre><code>// controller/rapor.php
$page_config = [
    'layout' => 'top-nav',          // preset
    'footer' => false,              // preset'in tek bayrağını ez
];

// Eski kullanım da aynen çalışır:
$page_config = ['topbar' => true, 'leftsidebar' => true, 'contentheader' => true];</code></pre></div>
                    </div>
                    <!--end::Section-->

                    <div class="separator separator-dashed my-8"></div>

                    <!--begin::Section-->
                    <div class="mb-10">
                        <h3 class="fw-bold text-body-emphasis mb-4">3. Tema Modu (Koyu / Açık / Sistem)</h3>
                        <p class="text-body mb-4">
                            Varsayılan mod <b>koyu</b>dur. Kullanıcı tercihi <code>localStorage["data-bs-theme"]</code>
                            anahtarında saklanır ve <code>head-standard.php</code>'nin en başındaki satır içi script
                            CSS yüklenmeden attribute'u yazar — hiçbir modda açılışta yanıp sönme (FOUC) olmaz.
                            Bootstrap 5.3'ün doğal <code>data-bs-theme</code> mekanizması kullanılır; özel bir
                            renk sistemi yoktur.
                        </p>
                        <div class="vnl-code"><pre><code>&lt;!-- Kendi arayüzünüze mod seçici koymak için --&gt;
&lt;button type="button" data-vnl-theme-value="light"&gt;Açık&lt;/button&gt;
&lt;button type="button" data-vnl-theme-value="dark"&gt;Koyu&lt;/button&gt;
&lt;button type="button" data-vnl-theme-value="system"&gt;Sistem&lt;/button&gt;

// Mod değişimini dinlemek:
document.addEventListener('vnl.theme.changed', function (e) {
    console.log(e.detail.mode, e.detail.resolved);
});</code></pre></div>
                        <p class="text-body-secondary mb-0 mt-4">
                            Yalnız tek modda görünmesi gereken içerik için <code>.theme-light-show</code> /
                            <code>.theme-dark-show</code> yardımcıları vardır. Sidebar bilinçli olarak iki modda da
                            koyudur (<code>data-bs-theme="dark"</code> attribute'u ile) — açık temada da
                            mühendislik-araç kimliği korunur.
                        </p>
                    </div>
                    <!--end::Section-->

                    <div class="separator separator-dashed my-8"></div>

                    <!--begin::Section-->
                    <div class="mb-10">
                        <h3 class="fw-bold text-body-emphasis mb-4">4. Sidebar ve Menü</h3>
                        <p class="text-body mb-4">
                            <code>view/static/leftSidebar.php</code> içindeki <code>$SidebarMenu</code> dizi sözleşmesi
                            VNL 2.x ile <b>birebir aynıdır</b> (<code>text</code>, <code>url</code>, <code>icon</code>,
                            <code>permission</code>, <code>showmenu</code> 0|1|2, <code>child</code>). Extension'lar
                            <code>$customSideBar</code> ile menüye öğe eklemeye devam eder. <code>icon</code> alanı FA kısa adı (<code>'home'</code> → <code>fa-solid fa-home</code>)
                            ya da boşluklu tam class'tır (<code>'bi bi-house'</code> olduğu gibi basılır) —
                            <b>Bootstrap Icons ve Font Awesome birlikte yüklüdür</b>, ikisi de kullanılabilir.
                        </p>
                        <ul class="text-body mb-0">
                            <li class="mb-2"><b>Mobil:</b> sidebar, Bootstrap Offcanvas olarak açılır (backdrop, ESC, odak yönetimi dahil).</li>
                            <li class="mb-2"><b>Desktop — iki daraltma modu:</b> marka satırındaki <i class="bi bi-chevron-double-left"></i> düğmesi menüyü daraltır; tercih <code>localStorage["vnl-sidebar-mini"]</code>'de kalıcıdır ve sayfa boyanmadan geri uygulanır.
                                <ul class="mt-2">
                                    <li class="mb-1"><code>'mini-hover'</code> <span class="badge badge-soft-primary ms-1">varsayılan</span> — ikon rayı; üzerine gelince ya da içine tıklayınca (dokunmatik dahil) içeriği itmeden üstüne doğru genişler.</li>
                                    <li><code>'mini'</code> — sabit ikon rayı; genişlemez. Controller'da <code>$page_config['leftsidebar'] = 'mini'</code> ile seçilir.</li>
                                </ul>
                            </li>
                            <li><b>Flyout alt menüler:</b> alt menüler alt alta açılmaz — normal/mini fark etmeksizin öğenin üzerine gelince ya da tıklayınca sağda panel olarak çıkar; ESC, dış tıklama ve fare ayrılması kapatır. Mobil offcanvas'ta yerinde açılır.</li>
                        </ul>
                    </div>
                    <!--end::Section-->

                    <div class="separator separator-dashed my-8"></div>

                    <!--begin::Section-->
                    <div class="mb-10">
                        <h3 class="fw-bold text-body-emphasis mb-4">5. Renkler ve Token'lar</h3>
                        <p class="text-body mb-4">
                            Marka rengi Voxnup yeşilidir (<code>$primary: #17a65c</code>); durum rengi olarak
                            <code>$success</code> bilinçli şekilde teal'e (<code>#0d9488</code>) kaydırılmıştır ki
                            rozetler primary'den ayrışsın. Yüzeyler üç katmandır ve bileşenler
                            <code>--vnl-*</code> token'larını tüketir:
                        </p>
                        <div class="row g-5 mb-5">
                            <div class="col-md-4">
                                <div class="p-5 rounded border" style="background: var(--bs-body-bg)">
                                    <div class="fw-semibold mb-1">Zemin</div>
                                    <code class="fs-8">--bs-body-bg</code>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="p-5 rounded border" style="background: var(--vnl-surface)">
                                    <div class="fw-semibold mb-1">Yüzey (kart, modal)</div>
                                    <code class="fs-8">--vnl-surface</code>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="p-5 rounded border" style="background: var(--vnl-surface-raised)">
                                    <div class="fw-semibold mb-1">Yükselen yüzey (dropdown, toast)</div>
                                    <code class="fs-8">--vnl-surface-raised</code>
                                </div>
                            </div>
                        </div>
                        <div class="d-flex flex-wrap gap-3 mb-5">
                            <span class="badge badge-soft-primary">badge-soft-primary</span>
                            <span class="badge badge-soft-success">badge-soft-success</span>
                            <span class="badge badge-soft-warning">badge-soft-warning</span>
                            <span class="badge badge-soft-danger">badge-soft-danger</span>
                            <span class="badge badge-soft-info">badge-soft-info</span>
                            <button type="button" class="btn btn-sm btn-soft-primary">btn-soft-primary</button>
                            <button type="button" class="btn btn-sm btn-soft-danger">btn-soft-danger</button>
                        </div>
                        <p class="text-body-secondary mb-0">
                            Metronic'in <code>badge-light-*</code> / <code>btn-light-*</code> sınıflarının yerini
                            <code>badge-soft-*</code> / <code>btn-soft-*</code> aldı. Metin renkleri Bootstrap 5.3'ün
                            adaptif ailesindedir: <code>text-body</code>, <code>text-body-secondary</code>,
                            <code>text-body-tertiary</code>, <code>text-body-emphasis</code> — iki temada da doğru kontrast.
                        </p>
                    </div>
                    <!--end::Section-->

                    <div class="separator separator-dashed my-8"></div>

                    <!--begin::Section-->
                    <div class="mb-10">
                        <h3 class="fw-bold text-body-emphasis mb-4">6. Tipografi ve Ölçekler</h3>
                        <p class="text-body mb-4">
                            Gövde fontu self-host <b>Inter variable</b>'dır (Google Fonts bağımlılığı yoktur;
                            Türkçe glifler için latin-ext dahildir). Taban boyut 14px'tir (araç yoğunluğu).
                            Metronic alışkanlıkları tema tarafından sağlanır:
                        </p>
                        <div class="table-responsive">
                            <table class="table table-row-dashed align-middle fs-7 gy-4">
                                <thead>
                                <tr class="text-start text-muted fw-bold fs-8 text-uppercase">
                                    <th class="min-w-200px">Aile</th>
                                    <th>Örnekler</th>
                                    <th>Not</th>
                                </tr>
                                </thead>
                                <tbody class="text-body fw-semibold">
                                <tr>
                                    <td>Yazı ölçeği</td>
                                    <td><code>fs-1…fs-9</code>, <code>fs-2x</code>, <code>fs-2hx</code>, <code>fs-3x</code></td>
                                    <td><code>fs-7/8/9</code> ve <code>*x</code> boyutları tema ekidir; büyükler RFS ile responsive'dir</td>
                                </tr>
                                <tr>
                                    <td>Boşluk ölçeği</td>
                                    <td><code>p-0…p-20</code>, <code>m-0…m-20</code>, negatifler (<code>mt-n5</code>)</td>
                                    <td>0–20 arası .25rem adımlı (<code>mb-10</code> = 2.5rem) — Metronic ile aynı ritim</td>
                                </tr>
                                <tr>
                                    <td>Piksel boyutları</td>
                                    <td><code>w-250px</code>, <code>w-lg-500px</code>, <code>min-w-125px</code>, <code>mw-650px</code>, <code>mh-300px</code>, <code>h-45px</code></td>
                                    <td>Liste <code>theme/scss/_utilities.scss</code> içindedir; yeni değer gerekiyorsa ekleyip build alın</td>
                                </tr>
                                <tr>
                                    <td>Sayısal hizalama</td>
                                    <td><code>numeric-tabular</code></td>
                                    <td>Tablo/veri hücrelerinde eş genişlikli rakamlar (<code>font-variant-numeric</code>)</td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <!--end::Section-->

                    <div class="separator separator-dashed my-8"></div>

                    <!--begin::Section-->
                    <div class="mb-10">
                        <h3 class="fw-bold text-body-emphasis mb-4">7. Bileşen Ekleri</h3>
                        <div class="table-responsive">
                            <table class="table table-row-dashed align-middle fs-7 gy-4">
                                <thead>
                                <tr class="text-start text-muted fw-bold fs-8 text-uppercase">
                                    <th class="min-w-200px">Bileşen</th>
                                    <th>Kullanım</th>
                                </tr>
                                </thead>
                                <tbody class="text-body fw-semibold">
                                <tr>
                                    <td><code>.vnl-code</code></td>
                                    <td>Kod bloğu — iki temada da koyu yüzey. <code>&lt;pre class="vnl-code"&gt;</code> ya da <code>&lt;div class="vnl-code"&gt;&lt;pre&gt;</code></td>
                                </tr>
                                <tr>
                                    <td><code>.symbol</code></td>
                                    <td>Avatar/işaret kutusu: <code>symbol symbol-40px symbol-circle</code> + <code>img</code> veya <code>.symbol-label</code></td>
                                </tr>
                                <tr>
                                    <td><code>.separator</code></td>
                                    <td>Yatay ayraç; <code>.separator-dashed</code> kesikli varyant</td>
                                </tr>
                                <tr>
                                    <td><code>.table-row-dashed</code></td>
                                    <td>Kesikli satır ayraçlı tablo</td>
                                </tr>
                                <tr>
                                    <td><code>.btn-icon</code></td>
                                    <td>Kare ikon butonu (<code>btn-sm</code>/<code>btn-lg</code> ile ölçeklenir)</td>
                                </tr>
                                <tr>
                                    <td><code>.indicator-label</code> / <code>.indicator-progress</code></td>
                                    <td>Buton yükleniyor deseni; <code>data-vnl-indicator="on"</code> (eski <code>data-kt-indicator</code> da çalışır)</td>
                                </tr>
                                <tr>
                                    <td><code>vnlLightbox(src, caption)</code></td>
                                    <td>Tam ekran görsel önizleme; bir <code>&lt;img&gt;</code>'e <code>.modal-image</code> vermek de yeterli</td>
                                </tr>
                                <tr>
                                    <td><code>showSpinner()</code> / <code>hideSpinner()</code></td>
                                    <td>Tam sayfa yükleniyor katmanı (saf CSS — gif bağımlılığı yok)</td>
                                </tr>
                                <tr>
                                    <td><code>.scroll-y</code> + <code>mh-*</code></td>
                                    <td>İnce kaydırma çubuklu dikey bölge</td>
                                </tr>
                                <tr>
                                    <td><code>.hover-elevate-up</code></td>
                                    <td>Hover'da yükselen kart</td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <!--end::Section-->

                    <div class="separator separator-dashed my-8"></div>

                    <!--begin::Section-->
                    <div class="mb-10">
                        <h3 class="fw-bold text-body-emphasis mb-4">8. Form ve Select (SlimSelect)</h3>
                        <p class="text-body mb-4">
                            Select2 kaldırıldı; yerinde <b>SlimSelect</b> var. Sözleşme değişmedi:
                            <code>select.select2</code> sınıflı öğeler otomatik başlatılır,
                            <code>action</code> attribute'lu select'ler <code>select2Reload()</code> ile
                            AJAX'tan doldurulur, <code>prm_*</code> attribute'ları POST parametresi olur.
                        </p>
                        <div class="vnl-code"><pre><code>&lt;select class="select2" action="app/kullanici/listele" prm_rol="admin"&gt;&lt;/select&gt;

&lt;script&gt;
    select2Reload($('select.select2[action]'));   // VNL 2.x çağrısı aynen çalışır
&lt;/script&gt;</code></pre></div>
                        <p class="text-body-secondary mb-0 mt-4">
                            Dolgulu input görünümü için <code>.form-control-solid</code> /
                            <code>.form-select-solid</code>; parola alanlarında göster/gizle için sarmalayıcıya
                            <code>.show-password</code> vermek yeterlidir.
                        </p>
                    </div>
                    <!--end::Section-->

                    <div class="separator separator-dashed my-8"></div>

                    <!--begin::Section-->
                    <div class="mb-0">
                        <h3 class="fw-bold text-body-emphasis mb-4">9. Metronic'ten Neler Değişti?</h3>
                        <div class="table-responsive">
                            <table class="table table-row-dashed align-middle fs-7 gy-4">
                                <thead>
                                <tr class="text-start text-muted fw-bold fs-8 text-uppercase">
                                    <th class="min-w-200px">Eski (Metronic 8)</th>
                                    <th>Yeni (VNL Theme)</th>
                                </tr>
                                </thead>
                                <tbody class="text-body fw-semibold">
                                <tr><td><code>assets/v1</code> bundle'ları (~7 MB)</td><td><code>assets/vnl</code> (~0.9 MB, font'lar dahil)</td></tr>
                                <tr><td>keenicons (<code>ki-duotone ki-*</code> + path span'ları)</td><td>Bootstrap Icons + Font Awesome Free 7 birlikte (<code>bi bi-*</code> ve <code>fa-solid fa-*</code>; <code>fas fa-*</code> de çalışır)</td></tr>
                                <tr><td><code>badge-light-*</code>, <code>btn-light-*</code></td><td><code>badge-soft-*</code>, <code>btn-soft-*</code></td></tr>
                                <tr><td><code>text-gray-800</code> vb. sabit griler</td><td><code>text-body*</code> adaptif aile</td></tr>
                                <tr><td>KTMenu / KTDrawer / KTScroll runtime'ı</td><td>Bootstrap Collapse / Offcanvas / Dropdown + ~3 KB tema JS'i</td></tr>
                                <tr><td>DataTables + SDTable</td><td>Söküldü — <code>vnltable</code> (VNL 3.0 yol haritası) gelene dek ölü kod olarak durur</td></tr>
                                <tr><td>Select2 (<code>theme: bootstrap4</code>)</td><td>SlimSelect (aynı <code>select2Reload()</code> sözleşmesi)</td></tr>
                                <tr><td>SweetAlert2 (eski)</td><td>SweetAlert2 v11 (<code>showMessage</code>/<code>showModalMessage</code> aynı)</td></tr>
                                <tr><td>Google Fonts CDN (Inter)</td><td>Self-host Inter variable (latin + latin-ext)</td></tr>
                                <tr><td>moment + daterangepicker + noUiSlider + draggable</td><td>Söküldü (DataTables filtreleriyle birlikte ölü kod)</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <!--end::Section-->

                </div>
            </div>

        </div>
    </div>
</div>

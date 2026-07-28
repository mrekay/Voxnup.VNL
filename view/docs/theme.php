<div class="d-flex flex-column flex-column-fluid">
    <div id="kt_app_content" class="app-content flex-column-fluid">
        <div id="kt_app_content_container" class="app-container container-xxl">

            <div class="vnl-page-hero mb-12 mt-6">
                <div class="vnl-overline mb-3">// docs/theme</div>
                <h1 class="fs-2hx fw-bold mb-4 text-body-emphasis">VNL Theme — Genel Bakış</h1>
                <div class="vnl-lead">
                    Bootstrap 5.3 üzerine inşa edilen, VNL'in view sistemiyle tam entegre tema katmanı.
                    Varsayılan kimlik koyudur; açık tema birinci sınıf desteklenir. Aynı tema bir ERP'yi,
                    bir pazarlama sayfasını ve bir hesap aracını layout preset'leriyle taşır.
                    Bu el kitabı temanın tüm API'sini bölüm bölüm anlatır.
                </div>
            </div>

            <div class="alert alert-primary d-flex align-items-center p-5 mb-10">
                <i class="bi bi-info-circle fs-2hx text-primary me-4"></i>
                <div class="d-flex flex-column">
                    <span class="fw-bold">Kaynak <code>theme/</code> dizininde, derlenmiş çıktı <code>assets/vnl/</code> altındadır.</span>
                    <span class="text-body-secondary">Çıktı repoya commit edilir — template kullanıcısının Node/npm kurması ya da build alması gerekmez.
                    Temayı özelleştirmek isteyenler için: <code>cd theme && npm ci && npm run build</code></span>
                </div>
            </div>

            <!--begin::El kitabı haritası-->
            <?php
            $vnl_handbook = [
                ['docs/theme-layout', 'bi bi-columns-gap', "Layout ve Preset'ler", "app / top-nav / landing / focus / blank preset'leri, \$page_config referansı ve uygulama geneli varsayılanlar."],
                ['docs/theme-sidebar', 'bi bi-layout-sidebar', 'Sidebar ve Menü', "\$SidebarMenu sözleşmesi, daraltma modları (mini / mini-hover), flyout ve accordion alt menüler."],
                ['docs/theme-design', 'bi bi-palette', 'Renkler ve Tipografi', "Koyu/açık tema mekanizması, yüzey katmanları, --vnl-* token'ları, yazı ve boşluk ölçekleri."],
                ['docs/theme-components', 'bi bi-grid-1x2', 'Bileşenler', 'Kartlar, soft rozet/butonlar, symbol, kod bloğu, spinner, lightbox — canlı örneklerle.'],
                ['docs/theme-forms', 'bi bi-input-cursor-text', 'Form ve Select', 'Solid form varyantları, parola göster/gizle, SlimSelect köprüsü ve AJAX select sözleşmesi.'],
                ['docs/theme-icons', 'bi bi-stars', 'İkonlar', "Bootstrap Icons + Font Awesome birlikte: kullanım, menü passthrough ve tekleştirme yolu."],
                ['docs/theme-modals', 'bi bi-window-stack', 'Modallar', 'Boyutlar, scrollable, fullscreen, sağdan kayan sheet paneli ve ikonlu onay deseni — canlı demolarla.'],
                ['docs/theme-js', 'bi bi-braces', 'JavaScript API', "Tema davranışları, global yardımcılar, data attribute API'leri ve olaylar."],
                ['docs/theme-migration', 'bi bi-signpost-split', "Metronic'ten Geçiş", 'Eski projeleri taşıma rehberi: sınıf eşleme tabloları ve codemod araçları.'],
            ];
            ?>
            <div class="row g-5 mb-10">
                <?php foreach ($vnl_handbook as [$vnl_hb_url, $vnl_hb_icon, $vnl_hb_title, $vnl_hb_text]) { ?>
                    <div class="col-md-6 col-xl-4">
                        <a href="/<?= $vnl_hb_url ?>" class="card h-100 hover-elevate-up text-decoration-none">
                            <div class="card-body p-7">
                                <div class="vnl-icon-box bg-primary-subtle text-primary mb-4">
                                    <i class="<?= $vnl_hb_icon ?>"></i>
                                </div>
                                <h3 class="fs-5 fw-bold mb-2 text-body-emphasis"><?= $vnl_hb_title ?></h3>
                                <p class="text-body-secondary fs-7 mb-0"><?= $vnl_hb_text ?></p>
                            </div>
                        </a>
                    </div>
                <?php } ?>
            </div>
            <!--end::El kitabı haritası-->

            <div class="card mb-10">
                <div class="card-body p-10">

                    <div class="mb-10">
                        <h3 class="fw-bold text-body-emphasis mb-4">Dosya Mimarisi</h3>
                        <p class="text-body mb-4">
                            Tema iki parçadan oluşur: <b>kaynak</b> (<code>theme/</code> — SCSS, JS modülleri, build araçları)
                            ve <b>derlenmiş çıktı</b> (<code>assets/vnl/</code>). Sayfalar yalnız çıktıyı yükler;
                            asset yükleme noktaları <code>view/includes/static/head-standard.php</code> ve
                            <code>script-standard.php</code> dosyalarıdır.
                        </p>
                        <div class="vnl-code"><pre><code>theme/
├── scss/            → vnl.scss (Bootstrap + tema), bi.scss, fa.scss
├── js/              → vnl-theme.js + modules/ (tema modu, sidebar, flyout, tooltip, lightbox)
├── tools/           → tek seferlik migrasyon codemod'ları + dev-router
└── build.mjs        → vendor concat + esbuild + font kopyalama

assets/vnl/
├── css/vnl.min.css  → Bootstrap 5.3.8 + VNL Theme + SweetAlert2/SlimSelect skin'leri
├── css/bi.min.css   → Bootstrap Icons 1.13
├── css/fa.min.css   → Font Awesome Free (solid/regular/brands + v4 shim)
├── js/vendors.min.js→ jQuery 3.7.1 + Bootstrap bundle + SweetAlert2 v11 + SlimSelect
├── js/vnl.min.js    → tema davranışları
└── fonts/           → Inter variable, JetBrains Mono, FA ve BI webfont'ları</code></pre></div>
                    </div>

                    <div class="separator separator-dashed my-8"></div>

                    <div class="mb-0">
                        <h3 class="fw-bold text-body-emphasis mb-4">Sürümler</h3>
                        <div class="table-responsive">
                            <table class="table table-row-dashed align-middle fs-7 gy-4">
                                <thead>
                                <tr class="text-start text-muted fw-bold fs-8 text-uppercase">
                                    <th class="min-w-200px">Paket</th>
                                    <th>Sürüm</th>
                                    <th>Rol</th>
                                </tr>
                                </thead>
                                <tbody class="text-body fw-semibold">
                                <tr><td>Bootstrap</td><td><code>5.3.8</code></td><td>Çekirdek çerçeve (SCSS'ten derlenir)</td></tr>
                                <tr><td>jQuery</td><td><code>3.7.1</code></td><td>VNL çekirdek script'leri için</td></tr>
                                <tr><td>SweetAlert2</td><td><code>11.x</code></td><td><code>showMessage</code> / <code>showModalMessage</code> altyapısı</td></tr>
                                <tr><td>SlimSelect</td><td><code>3.4</code></td><td>Select bileşeni (Select2'nin yerine)</td></tr>
                                <tr><td>Bootstrap Icons</td><td><code>1.13</code></td><td>Tema arayüzünün ikon seti</td></tr>
                                <tr><td>Font Awesome Free</td><td><code>7.3</code></td><td>Uyumluluk seti (<code>fas fa-*</code> dahil)</td></tr>
                                <tr><td>Inter variable</td><td><code>latin + latin-ext</code></td><td>Gövde yazı tipi (self-host)</td></tr>
                                <tr><td>JetBrains Mono</td><td><code>latin + latin-ext</code></td><td>Mono aksan: kod, breadcrumb, overline (self-host)</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>
            </div>

        </div>
    </div>
</div>

<div class="d-flex flex-column flex-column-fluid">
    <div id="kt_app_content" class="app-content flex-column-fluid">
        <div id="kt_app_content_container" class="app-container container-xxl">

            <div class="vnl-page-hero mb-12 mt-6">
                <div class="vnl-overline mb-3">// docs/theme-migration</div>
                <h1 class="fs-2hx fw-bold mb-4 text-body-emphasis">Metronic'ten Geçiş</h1>
                <div class="vnl-lead">
                    VNL 2.x + Metronic 8 üzerinde koşan projeleri VNL Theme'e taşımak için rehber.
                    Bu template'in kendi geçişinde kullanılan codemod'lar tema deposunda
                    (<a href="https://github.com/mrekay/VNL-THEME" target="_blank" rel="noopener">VNL-THEME</a>)
                    <code>tools/codemods/</code> altında durur ve türev projelerde yeniden kullanılabilir.
                </div>
            </div>

            <div class="card mb-10">
                <div class="card-body p-10">

                    <div class="mb-10">
                        <h3 class="fw-bold text-body-emphasis mb-4">1. Ne Değişti?</h3>
                        <div class="table-responsive">
                            <table class="table table-row-dashed align-middle fs-7 gy-4">
                                <thead>
                                <tr class="text-start text-muted fw-bold fs-8 text-uppercase">
                                    <th class="min-w-250px">Eski (Metronic 8)</th><th>Yeni (VNL Theme)</th>
                                </tr>
                                </thead>
                                <tbody class="text-body fw-semibold">
                                <tr><td><code>assets/v1</code> bundle'ları (~7 MB)</td><td><code>vnl/assets/theme</code> (~1 MB, font'lar dahil)</td></tr>
                                <tr><td>keenicons (<code>ki-duotone ki-*</code> + path span'ları)</td><td>Bootstrap Icons + Font Awesome Free 7 birlikte</td></tr>
                                <tr><td><code>badge-light-*</code>, <code>btn-light-*</code></td><td><code>badge-soft-*</code>, <code>btn-soft-*</code></td></tr>
                                <tr><td><code>text-gray-800</code> vb. sabit griler</td><td><code>text-body*</code> adaptif aile</td></tr>
                                <tr><td>KTMenu / KTDrawer / KTScroll runtime'ı</td><td>Bootstrap Collapse / Offcanvas / Dropdown + ~4 KB tema JS'i</td></tr>
                                <tr><td>DataTables + SDTable</td><td>Kaldırıldı — yerine <a href="/docs/vnltable" class="text-primary fw-bold">VNL Table</a></td></tr>
                                <tr><td>Select2 (<code>theme: bootstrap4</code>, <code>select2Reload()</code>)</td><td>SlimSelect — <code>selectReload()</code> (aynı sözleşme; <code>select2Reload</code> adı kaldırıldı, <code>.select2</code> sınıfı hâlâ tanınır)</td></tr>
                                <tr><td>SweetAlert2 (eski, <code>swal.mixin</code>)</td><td>SweetAlert2 v11 (<code>Swal.mixin</code> + <code>didOpen</code>; <code>showMessage</code> API'si aynı)</td></tr>
                                <tr><td>Google Fonts CDN (Inter)</td><td>Self-host Inter variable + JetBrains Mono (latin-ext dahil)</td></tr>
                                <tr><td><code>loadingButton()</code> / <code>loadingButtonDisable()</code> (<code>.loadingbutton</code>)</td><td><code>vnlBtnLoading()</code> / <code>vnlBtnReset()</code> + deklaratif <code>data-vnl-loading</code></td></tr>
                                <tr><td>moment + daterangepicker + noUiSlider + draggable</td><td>Söküldü (DataTables filtreleriyle birlikte ölü kod)</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="separator separator-dashed my-8"></div>

                    <div class="mb-10">
                        <h3 class="fw-bold text-body-emphasis mb-4">2. Sınıf Eşleme Tablosu</h3>
                        <div class="table-responsive">
                            <table class="table table-row-dashed align-middle fs-7 gy-4">
                                <thead>
                                <tr class="text-start text-muted fw-bold fs-8 text-uppercase">
                                    <th class="min-w-250px">Metronic sınıfı</th><th>VNL Theme karşılığı</th>
                                </tr>
                                </thead>
                                <tbody class="text-body fw-semibold">
                                <tr><td><code>text-gray-900</code> / <code>text-dark</code></td><td><code>text-body-emphasis</code></td></tr>
                                <tr><td><code>text-gray-800/-700</code></td><td><code>text-body</code></td></tr>
                                <tr><td><code>text-gray-600/-500</code></td><td><code>text-body-secondary</code></td></tr>
                                <tr><td><code>text-gray-400/-300</code></td><td><code>text-body-tertiary</code></td></tr>
                                <tr><td><code>fw-bolder</code></td><td><code>fw-bold</code></td></tr>
                                <tr><td><code>bg-light-primary</code> vb.</td><td><code>bg-primary-subtle</code> vb.</td></tr>
                                <tr><td><code>bg-light</code> / <code>bg-secondary</code></td><td><code>bg-body-tertiary</code> / <code>bg-body-secondary</code></td></tr>
                                <tr><td><code>badge-light-X</code> / <code>btn-light-X</code></td><td><code>badge-soft-X</code> / <code>btn-soft-X</code></td></tr>
                                <tr><td><code>border-gray-N</code></td><td>silinir (kenarlıklar zaten adaptif)</td></tr>
                                <tr><td><code>&lt;div class="bg-dark rounded p-5"&gt;&lt;pre&gt;</code></td><td><code>&lt;div class="vnl-code"&gt;&lt;pre&gt;</code></td></tr>
                                <tr><td><code>fs-7/8/9</code>, <code>fs-2x/2hx/3x</code>, <code>w-*px</code>, <code>mb-10</code>…</td><td>Değişmez — tema aynı isimlerle sağlar</td></tr>
                                <tr><td><code>symbol</code>, <code>separator</code>, <code>btn-icon</code>, <code>indicator-*</code>, <code>scroll-y</code>…</td><td>Değişmez — tema aynı isimlerle sağlar</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="separator separator-dashed my-8"></div>

                    <div class="mb-10">
                        <h3 class="fw-bold text-body-emphasis mb-4">3. Codemod Araçları</h3>
                        <p class="text-body mb-4">
                            Bu template'in geçişini yapan Node script'leri. Tema deposundadır, <b>hedef projenin
                            kökünde</b> çalıştırılır; taranacak dosyalar glob olarak verilir (<code>--root=&lt;dizin&gt;</code>
                            ile kök değiştirilebilir, hepsi <code>--dry</code> ile önizleme destekler):
                        </p>
                        <div class="table-responsive mb-0">
                            <table class="table table-row-dashed align-middle fs-7 gy-4">
                                <thead>
                                <tr class="text-start text-muted fw-bold fs-8 text-uppercase">
                                    <th class="min-w-300px">Araç</th><th>İş</th>
                                </tr>
                                </thead>
                                <tbody class="text-body fw-semibold">
                                <tr><td><code>codemods/migrate-icons.mjs</code></td><td>138 keenicon'u <code>fa-*</code> karşılığına çevirir, duotone path span'larını siler (HTML-escape'li örnekler dahil)</td></tr>
                                <tr><td><code>codemods/sweep-utilities.mjs a|b</code></td><td>Sınıf eşleme tablosunu uygular (FAZ A: adaptif aileler; FAZ B: soft/vnl-code)</td></tr>
                                <tr><td><code>codemods/sweep-hero.mjs</code></td><td>Ortalanmış eski hero bloklarını overline'lı sola yaslı düzene çevirir</td></tr>
                                <tr><td><code>vnl/tools/dev-router.php</code> <span class="text-body-secondary">(bu repo)</span></td><td>Yerel smoke test: <code>php vnl.php serve</code></td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="separator separator-dashed my-8"></div>

                    <div class="mb-0">
                        <h3 class="fw-bold text-body-emphasis mb-4">4. Önerilen Sıra</h3>
                        <ol class="text-body mb-0">
                            <li class="mb-2"><b>Guard'lar:</b> <code>vnl.js</code>'inizi template'teki sürümle eşitleyin (moment/DataTables guard'ları + SlimSelect köprüsü + Swal v11).</li>
                            <li class="mb-2"><b>Asset takası:</b> <code>vnl/assets/theme/</code> dizinini ve <code>view/includes/static/</code> + <code>view/static/</code> dosyalarını template'ten alın; <code>themeDefaults()</code>'u config'inize ekleyin.</li>
                            <li class="mb-2"><b>Sweep:</b> codemod'ları kendi <code>view/</code> dizininize koşun; kalan <code>ki-*</code> / <code>badge-light-*</code> / <code>text-gray-*</code> kullanımlarını grep ile sıfırlayın.</li>
                            <li class="mb-2"><b>Metronic'i silin:</b> <code>assets/v1/</code> ve Metronic'e özel include'lar.</li>
                            <li><b>Doğrulama:</b> <code>php -l</code> + dev-router ile sayfa sayfa gezin; console'da hata ve 404 asset kalmamalı.</li>
                        </ol>
                    </div>

                </div>
            </div>

        </div>
    </div>
</div>

<div class="d-flex flex-column flex-column-fluid">
    <div id="kt_app_content" class="app-content flex-column-fluid">
        <div id="kt_app_content_container" class="app-container container-xxl">

            <div class="vnl-page-hero mb-12 mt-6">
                <div class="vnl-overline mb-3">// docs/theme-components</div>
                <h1 class="fs-2hx fw-bold mb-4 text-body-emphasis">Bileşenler</h1>
                <div class="vnl-lead">
                    Bootstrap'in tüm bileşenleri temayla gelir; bu sayfa yalnız VNL Theme'in
                    <b>eklediklerini</b> canlı örneklerle anlatır. Kartlar ve yükselen yüzeyler
                    çizgisizdir — derinliği gölge (koyuda + ince ışık halkası) verir.
                </div>
            </div>

            <div class="card mb-10">
                <div class="card-body p-10">

                    <div class="mb-10">
                        <h3 class="fw-bold text-body-emphasis mb-4">1. Soft Rozetler ve Butonlar</h3>
                        <p class="text-body mb-4">
                            Metronic'in <code>badge-light-*</code> / <code>btn-light-*</code> ailesinin yerine:
                            subtle zemin + emphasis metin, iki temada da otomatik doğru.
                        </p>
                        <div class="d-flex flex-wrap align-items-center gap-3 mb-5">
                            <span class="badge badge-soft-primary">badge-soft-primary</span>
                            <span class="badge badge-soft-success">badge-soft-success</span>
                            <span class="badge badge-soft-warning">badge-soft-warning</span>
                            <span class="badge badge-soft-danger">badge-soft-danger</span>
                            <span class="badge badge-soft-info">badge-soft-info</span>
                            <span class="badge badge-soft-secondary">badge-soft-secondary</span>
                        </div>
                        <div class="d-flex flex-wrap align-items-center gap-3 mb-5">
                            <button type="button" class="btn btn-sm btn-soft-primary">btn-soft-primary</button>
                            <button type="button" class="btn btn-sm btn-soft-success">btn-soft-success</button>
                            <button type="button" class="btn btn-sm btn-soft-danger">btn-soft-danger</button>
                            <button type="button" class="btn btn-sm btn-icon btn-soft-primary" aria-label="örnek"><i class="bi bi-gear"></i></button>
                            <span class="text-body-secondary fs-8">← <code>.btn-icon</code>: kare ikon butonu</span>
                        </div>
                        <div class="vnl-code"><pre><code>&lt;span class="badge badge-soft-success"&gt;Aktif&lt;/span&gt;
&lt;button class="btn btn-sm btn-soft-primary"&gt;Düzenle&lt;/button&gt;
&lt;button class="btn btn-icon btn-soft-danger"&gt;&lt;i class="bi bi-trash"&gt;&lt;/i&gt;&lt;/button&gt;</code></pre></div>
                    </div>

                    <div class="separator separator-dashed my-8"></div>

                    <div class="mb-10">
                        <h3 class="fw-bold text-body-emphasis mb-4">2. İkon Kutusu ve Symbol</h3>
                        <div class="d-flex flex-wrap align-items-center gap-5 mb-5">
                            <div class="vnl-icon-box bg-primary-subtle text-primary"><i class="bi bi-journal-text"></i></div>
                            <div class="vnl-icon-box bg-success-subtle text-success"><i class="bi bi-check-circle"></i></div>
                            <div class="vnl-icon-box bg-warning-subtle text-warning"><i class="bi bi-bell"></i></div>
                            <span class="text-body-secondary fs-8">← <code>.vnl-icon-box</code> (kart/istatistik başları)</span>
                        </div>
                        <div class="d-flex flex-wrap align-items-center gap-5 mb-5">
                            <span class="symbol symbol-30px"><span class="symbol-label">A</span></span>
                            <span class="symbol symbol-40px symbol-circle"><span class="symbol-label">VN</span></span>
                            <span class="symbol symbol-50px symbol-circle"><img src="<?= $cDefinitions->getAssetsUrl() ?>/media/images/user.png" alt=""/></span>
                            <span class="text-body-secondary fs-8">← <code>.symbol</code> avatar/işaret kutusu (20–150px boyutları)</span>
                        </div>
                        <div class="vnl-code"><pre><code>&lt;div class="vnl-icon-box bg-primary-subtle text-primary"&gt;&lt;i class="bi bi-cpu"&gt;&lt;/i&gt;&lt;/div&gt;

&lt;span class="symbol symbol-40px symbol-circle"&gt;
    &lt;img src="..." alt=""/&gt;              &lt;!-- ya da: --&gt;
    &lt;span class="symbol-label"&gt;EK&lt;/span&gt;
&lt;/span&gt;</code></pre></div>
                    </div>

                    <div class="separator separator-dashed my-8"></div>

                    <div class="mb-10">
                        <h3 class="fw-bold text-body-emphasis mb-4">3. Kod Bloğu, Ayraç ve Tablolar</h3>
                        <p class="text-body mb-4">
                            <code>.vnl-code</code> iki temada da koyu yüzeylidir (kod her zaman koyu zeminde okunur);
                            hem <code>&lt;pre class="vnl-code"&gt;</code> hem <code>&lt;div class="vnl-code"&gt;&lt;pre&gt;</code>
                            biçimini destekler. Ayraç için <code>.separator</code> / <code>.separator-dashed</code>,
                            veri tabloları için <code>.table-row-dashed</code> (kesikli satırlar + sakin hover +
                            eş genişlikli rakamlar) kullanılır — bu sayfadaki tablolar canlı örnektir.
                        </p>
                        <div class="separator separator-dashed mb-5"></div>
                        <div class="table-responsive mb-0">
                            <table class="table table-row-dashed align-middle fs-7 gy-4 mb-0">
                                <thead>
                                <tr class="text-start text-muted fw-bold fs-8 text-uppercase">
                                    <th>Örnek</th><th>Durum</th><th class="text-end">Tutar</th>
                                </tr>
                                </thead>
                                <tbody class="text-body fw-semibold">
                                <tr><td>VNL-1042</td><td><span class="badge badge-soft-success">Tamamlandı</span></td><td class="text-end">1.250,00</td></tr>
                                <tr><td>VNL-1043</td><td><span class="badge badge-soft-warning">Beklemede</span></td><td class="text-end">840,50</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="separator separator-dashed my-8"></div>

                    <div class="mb-10">
                        <h3 class="fw-bold text-body-emphasis mb-4">4. Spinner, Lightbox ve Yükleniyor Deseni</h3>
                        <div class="d-flex flex-wrap align-items-center gap-3 mb-5">
                            <button type="button" class="btn btn-soft-primary" onclick="showSpinner(); setTimeout(hideSpinner, 1500);">
                                <i class="bi bi-arrow-repeat me-2"></i>Tam sayfa spinner (1.5 sn)
                            </button>
                            <button type="button" class="btn btn-soft-primary" onclick="vnlLightbox('<?= $cDefinitions->appLogoPath() ?>VNLBIG.png', 'VNL maskotu — vnlLightbox() örneği')">
                                <i class="bi bi-image me-2"></i>Lightbox örneği
                            </button>
                            <button type="button" class="btn btn-primary" id="vnl_demo_indicator"
                                    onclick="this.setAttribute('data-vnl-indicator','on'); var b=this; setTimeout(function(){ b.removeAttribute('data-vnl-indicator'); }, 1500);">
                                <span class="indicator-label">Kaydet</span>
                                <span class="indicator-progress"><?= lang('BTN_LOADING') ?>
                                    <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                            </button>
                        </div>
                        <div class="vnl-code"><pre><code>showSpinner();  hideSpinner();          // #loadingSpinner katmanı (saf CSS)
vnlLightbox(src, caption);              // tam ekran görsel; &lt;img class="modal-image"&gt; de yeterli

&lt;button data-vnl-indicator="off"&gt;       // yükleniyor deseni (eski data-kt-indicator da çalışır)
    &lt;span class="indicator-label"&gt;Kaydet&lt;/span&gt;
    &lt;span class="indicator-progress"&gt;Lütfen bekleyin…&lt;/span&gt;
&lt;/button&gt;</code></pre></div>
                    </div>

                    <div class="separator separator-dashed my-8"></div>

                    <div class="mb-0">
                        <h3 class="fw-bold text-body-emphasis mb-4">5. Küçük Yardımcılar</h3>
                        <div class="table-responsive">
                            <table class="table table-row-dashed align-middle fs-7 gy-4">
                                <thead>
                                <tr class="text-start text-muted fw-bold fs-8 text-uppercase">
                                    <th class="min-w-250px">Sınıf</th><th>İş</th>
                                </tr>
                                </thead>
                                <tbody class="text-body fw-semibold">
                                <tr><td><code>.hover-elevate-up</code></td><td>Hover'da yükselen kart (Genel Bakış'taki harita kartları)</td></tr>
                                <tr><td><code>.scroll-y</code> + <code>mh-*</code></td><td>İnce kaydırma çubuklu dikey bölge</td></tr>
                                <tr><td><code>.vnl-error-code</code></td><td>401/404 sayfalarındaki dev mono kod</td></tr>
                                <tr><td><code>.theme-light-show</code> / <code>.theme-dark-show</code></td><td>Yalnız tek temada görünen içerik</td></tr>
                                <tr><td><code>.rotate-180</code>, <code>.animation-blink</code>, <code>.bullet(-dot)</code></td><td>Mikro görsel yardımcılar</td></tr>
                                <tr><td><code>.flex-root/-column-fluid/-row-fluid/-center/-stack</code></td><td>Sayfa iskeletlerinin flex kısayolları</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>
            </div>

        </div>
    </div>
</div>

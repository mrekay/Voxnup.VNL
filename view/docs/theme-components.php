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
                        <h3 class="fw-bold text-body-emphasis mb-4">4. Yükleniyor: Spinner, Düğmeler ve Lightbox</h3>
                        <p class="text-body mb-4">
                            Yükleniyor düğmeleri için tek yapı vardır: <code>vnlBtnLoading()</code> /
                            <code>vnlBtnReset()</code>. İçerik, genişlik sabitlenerek spinner + metinle
                            takas edilir; düğmede <code>.indicator-*</code> span'ları varsa otomatik o desen
                            kullanılır. <b>Deklaratif kullanımda</b> (<code>data-vnl-loading</code>) düğme
                            tıklanınca kendini yükler, sayfadaki AJAX trafiği bitince kendini sıfırlar —
                            "yenile" düğmeleri için sıfır kod.
                        </p>
                        <div class="d-flex flex-wrap align-items-center gap-3 mb-5">
                            <button type="button" class="btn btn-soft-primary"
                                    onclick="vnlBtnLoading(this, 'Yenileniyor…'); var b=this; setTimeout(function(){ vnlBtnReset(b); }, 1500);">
                                <i class="bi bi-arrow-clockwise me-2"></i>Yenile (içerik takası)
                            </button>
                            <button type="button" class="btn btn-primary"
                                    onclick="vnlBtnLoading(this); var b=this; setTimeout(function(){ vnlBtnReset(b); }, 1500);">
                                <span class="indicator-label">Kaydet (indicator deseni)</span>
                                <span class="indicator-progress"><?= lang('BTN_LOADING') ?>
                                    <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                            </button>
                            <button type="button" class="btn btn-soft-primary" onclick="showSpinner(); setTimeout(hideSpinner, 1500);">
                                <i class="bi bi-arrow-repeat me-2"></i>Tam sayfa spinner
                            </button>
                            <button type="button" class="btn btn-soft-primary" onclick="vnlLightbox('<?= $cDefinitions->appLogoPath() ?>VNLBIG.png', 'VNL maskotu — vnlLightbox() örneği')">
                                <i class="bi bi-image me-2"></i>Lightbox
                            </button>
                        </div>
                        <div class="vnl-code"><pre><code>&lt;!-- Deklaratif: tıklanınca yüklenir, AJAX bitince kendini sıfırlar --&gt;
&lt;button class="btn btn-soft-primary" data-vnl-loading data-loading-text="Yenileniyor…"
        onclick="listeyiYenile()"&gt;Yenile&lt;/button&gt;

// Programatik:
vnlBtnLoading(btn);          // Element, jQuery ya da seçici; opsiyonel 2. argüman: metin
vnlBtnReset(btn);

showSpinner();  hideSpinner();          // #loadingSpinner katmanı (saf CSS)
vnlLightbox(src, caption);              // tam ekran görsel; &lt;img class="modal-image"&gt; de yeterli</code></pre></div>
                    </div>

                    <div class="separator separator-dashed my-8"></div>

                    <div class="mb-10">
                        <h3 class="fw-bold text-body-emphasis mb-4">5. Accordion ve Sekmeler</h3>
                        <p class="text-body mb-4">
                            İkisi de Bootstrap davranışıyla çalışır (<code>data-bs-toggle</code>), görünüm VNL'indir.
                            Accordion'da aktif başlık sol emerald rail alır (sidebar'ın aktif diliyle akraba);
                            <code>.vnl-accordion</code> varyantı maddeleri ayrık kartlara çevirir.
                            Sekmelerde önerilen biçim <code>.nav-underline</code>'dır; <code>.vnl-tabs-solid</code>
                            ise araç panelleri için segmented-control varyantıdır.
                        </p>

                        <div class="row g-8 mb-6">
                            <div class="col-lg-6">
                                <div class="fs-8 text-uppercase text-body-secondary fw-bold mb-3">Accordion (base)</div>
                                <div class="accordion" id="vnl_demo_acc1">
                                    <div class="accordion-item">
                                        <h2 class="accordion-header">
                                            <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#vnl_acc1_a">Kurulum</button>
                                        </h2>
                                        <div id="vnl_acc1_a" class="accordion-collapse collapse show" data-bs-parent="#vnl_demo_acc1">
                                            <div class="accordion-body text-body-secondary">Projeyi klonlayın, config tanımlarını doldurun — build gerekmez.</div>
                                        </div>
                                    </div>
                                    <div class="accordion-item">
                                        <h2 class="accordion-header">
                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#vnl_acc1_b">Yapılandırma</button>
                                        </h2>
                                        <div id="vnl_acc1_b" class="accordion-collapse collapse" data-bs-parent="#vnl_demo_acc1">
                                            <div class="accordion-body text-body-secondary">Sistem geneli varsayılanlar <code>themeDefaults()</code> içindedir.</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="fs-8 text-uppercase text-body-secondary fw-bold mb-3">.vnl-accordion (ayrık kartlar)</div>
                                <div class="accordion vnl-accordion" id="vnl_demo_acc2">
                                    <div class="accordion-item">
                                        <h2 class="accordion-header">
                                            <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#vnl_acc2_a">Sık sorulan</button>
                                        </h2>
                                        <div id="vnl_acc2_a" class="accordion-collapse collapse show" data-bs-parent="#vnl_demo_acc2">
                                            <div class="accordion-body text-body-secondary">Her madde kendi yüzeyinde, gölgeyle yüzer — kart diliyle aynı.</div>
                                        </div>
                                    </div>
                                    <div class="accordion-item">
                                        <h2 class="accordion-header">
                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#vnl_acc2_b">Bir madde daha</button>
                                        </h2>
                                        <div id="vnl_acc2_b" class="accordion-collapse collapse" data-bs-parent="#vnl_demo_acc2">
                                            <div class="accordion-body text-body-secondary">Ayrık görünüm SSS ve ayar gruplarında iyi durur.</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row g-8 mb-6">
                            <div class="col-lg-6">
                                <div class="fs-8 text-uppercase text-body-secondary fw-bold mb-3">.nav-underline (önerilen sekme)</div>
                                <ul class="nav nav-underline mb-4" role="tablist">
                                    <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#vnl_tab_u1" type="button" role="tab">Genel</button></li>
                                    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#vnl_tab_u2" type="button" role="tab">Yetkiler</button></li>
                                    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#vnl_tab_u3" type="button" role="tab">Geçmiş</button></li>
                                </ul>
                                <div class="tab-content text-body-secondary">
                                    <div class="tab-pane fade show active" id="vnl_tab_u1" role="tabpanel">Aktif sekme emerald alt çizgi + emphasis metin alır.</div>
                                    <div class="tab-pane fade" id="vnl_tab_u2" role="tabpanel">Sekme davranışı Bootstrap'in tab API'sidir.</div>
                                    <div class="tab-pane fade" id="vnl_tab_u3" role="tabpanel">Üçüncü panel içeriği.</div>
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="fs-8 text-uppercase text-body-secondary fw-bold mb-3">.vnl-tabs-solid (segmented)</div>
                                <ul class="nav vnl-tabs-solid mb-4" role="tablist">
                                    <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#vnl_tab_s1" type="button" role="tab">Günlük</button></li>
                                    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#vnl_tab_s2" type="button" role="tab">Haftalık</button></li>
                                    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#vnl_tab_s3" type="button" role="tab">Aylık</button></li>
                                </ul>
                                <div class="tab-content text-body-secondary">
                                    <div class="tab-pane fade show active" id="vnl_tab_s1" role="tabpanel">Kapsül zemin üzerinde aktif sekme yüzeyde yüzer.</div>
                                    <div class="tab-pane fade" id="vnl_tab_s2" role="tabpanel">Rapor aralığı seçimleri gibi araçlara uygundur.</div>
                                    <div class="tab-pane fade" id="vnl_tab_s3" role="tabpanel">Üçüncü panel içeriği.</div>
                                </div>
                            </div>
                        </div>

                        <div class="vnl-code"><pre><code>&lt;!-- Ayrık kart accordion --&gt;
&lt;div class="accordion vnl-accordion" id="sss"&gt;…&lt;/div&gt;

&lt;!-- Önerilen sekme --&gt;
&lt;ul class="nav nav-underline" role="tablist"&gt;…&lt;/ul&gt;

&lt;!-- Segmented sekme --&gt;
&lt;ul class="nav vnl-tabs-solid" role="tablist"&gt;…&lt;/ul&gt;</code></pre></div>
                    </div>

                    <div class="separator separator-dashed my-8"></div>

                    <div class="mb-0">
                        <h3 class="fw-bold text-body-emphasis mb-4">6. Küçük Yardımcılar</h3>
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

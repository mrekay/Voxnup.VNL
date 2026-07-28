<div class="d-flex flex-column flex-column-fluid">
    <div id="kt_app_content" class="app-content flex-column-fluid">
        <div id="kt_app_content_container" class="app-container container-xxl">

            <div class="vnl-page-hero mb-12 mt-6">
                <div class="vnl-overline mb-3">// docs/theme-modals</div>
                <h1 class="fs-2hx fw-bold mb-4 text-body-emphasis">Modallar</h1>
                <div class="vnl-lead">
                    Davranış Bootstrap'indir (<code>data-bs-toggle="modal"</code>), görünüm VNL'indir:
                    çizgisiz yüzey, gölgeyle derinlik. Bu sayfa şekilleri anlatır; modal
                    <b>dosyalarının</b> projede nasıl organize edildiği için bkz.
                    <a href="/docs/modals">Modal Sistemi</a>.
                </div>
            </div>

            <div class="card mb-10">
                <div class="card-body p-10">

                    <div class="mb-10">
                        <h3 class="fw-bold text-body-emphasis mb-4">1. Şekiller</h3>
                        <p class="text-body mb-4">
                            Hepsi Bootstrap'in standart sınıflarıdır; tema hepsini aynı dille giydirir.
                            Canlı denemek için:
                        </p>
                        <div class="d-flex flex-wrap gap-3 mb-5">
                            <button type="button" class="btn btn-soft-primary" data-bs-toggle="modal" data-bs-target="#vnl_m_centered">Ortalanmış</button>
                            <button type="button" class="btn btn-soft-primary" data-bs-toggle="modal" data-bs-target="#vnl_m_scroll">Scrollable</button>
                            <button type="button" class="btn btn-soft-primary" data-bs-toggle="modal" data-bs-target="#vnl_m_lg">Geniş (modal-lg)</button>
                            <button type="button" class="btn btn-soft-primary" data-bs-toggle="modal" data-bs-target="#vnl_m_full">Fullscreen</button>
                            <button type="button" class="btn btn-soft-primary" data-bs-toggle="modal" data-bs-target="#vnl_m_static">Statik backdrop</button>
                        </div>
                        <div class="vnl-code"><pre><code>&lt;div class="modal fade" id="ornek"&gt;
  &lt;div class="modal-dialog modal-dialog-centered"&gt;        &lt;!-- ortalanmış --&gt;
  &lt;div class="modal-dialog modal-dialog-scrollable"&gt;      &lt;!-- gövde kayar --&gt;
  &lt;div class="modal-dialog modal-lg"&gt;                     &lt;!-- sm | lg | xl --&gt;
  &lt;div class="modal-dialog modal-fullscreen"&gt;             &lt;!-- tam ekran --&gt;

&lt;div class="modal fade" data-bs-backdrop="static" data-bs-keyboard="false"&gt;  &lt;!-- dışarı tıklamayla kapanmaz --&gt;</code></pre></div>
                    </div>

                    <div class="separator separator-dashed my-8"></div>

                    <div class="mb-10">
                        <h3 class="fw-bold text-body-emphasis mb-4">2. Sağdan Kayan Panel (vnl-modal-sheet)</h3>
                        <p class="text-body mb-4">
                            VNL'e özgü şekil: kayıt inceleme/düzenleme için tam boy sağ panel.
                            Normal bir modaldır — yalnız <code>.vnl-modal-sheet</code> sınıfı eklenir;
                            sağdan kayarak açılır, gövdesi kendi içinde kayar.
                            <code>.vnl-modal-sheet-lg</code> genişliği 680px'e çıkarır.
                        </p>
                        <div class="d-flex flex-wrap gap-3 mb-5">
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#vnl_m_sheet">
                                <i class="bi bi-layout-sidebar-inset-reverse me-2"></i>Sheet'i aç
                            </button>
                        </div>
                        <div class="vnl-code"><pre><code>&lt;div class="modal fade vnl-modal-sheet" id="kayitPaneli" tabindex="-1"&gt;
    &lt;div class="modal-dialog"&gt;
        &lt;div class="modal-content"&gt;…&lt;/div&gt;
    &lt;/div&gt;
&lt;/div&gt;</code></pre></div>
                    </div>

                    <div class="separator separator-dashed my-8"></div>

                    <div class="mb-0">
                        <h3 class="fw-bold text-body-emphasis mb-4">3. İkonlu Onay Deseni</h3>
                        <p class="text-body mb-4">
                            Başlıksız gövde + <code>.vnl-icon-box</code> + aksiyonlar: yumuşak bir onay/bilgi
                            kompozisyonu. (JS akışlı onaylar için <code>deleteItemFromDB()</code> Swal ile
                            hazırdır — bkz. <a href="/docs/theme-js">JavaScript API</a>.)
                        </p>
                        <div class="d-flex flex-wrap gap-3">
                            <button type="button" class="btn btn-soft-danger" data-bs-toggle="modal" data-bs-target="#vnl_m_confirm">Onay örneği</button>
                        </div>
                    </div>

                </div>
            </div>

        </div>
    </div>
</div>

<!-- ── Demo modalları ── -->

<div class="modal fade" id="vnl_m_centered" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Ortalanmış modal</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= lang('LBL_CLOSE') ?>"></button>
            </div>
            <div class="modal-body text-body-secondary">Dikeyde ortalanır; kısa onay ve bilgi pencereleri için uygundur.</div>
            <div class="modal-footer">
                <button type="button" class="btn btn-soft-secondary" data-bs-dismiss="modal"><?= lang('LBL_CLOSE') ?></button>
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Tamam</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="vnl_m_scroll" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Scrollable modal</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= lang('LBL_CLOSE') ?>"></button>
            </div>
            <div class="modal-body text-body-secondary">
                <?php for ($vnl_i = 1; $vnl_i <= 12; $vnl_i++) { ?>
                    <p class="mb-4">Uzun içerik satırı <?= $vnl_i ?> — başlık ve altbilgi sabit kalır, yalnız gövde kayar.</p>
                <?php } ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Tamam</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="vnl_m_lg" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Geniş modal (modal-lg)</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= lang('LBL_CLOSE') ?>"></button>
            </div>
            <div class="modal-body text-body-secondary">Boyutlar Bootstrap'indir: <code>modal-sm</code> (300px) · varsayılan (500px) · <code>modal-lg</code> (800px) · <code>modal-xl</code> (1140px).</div>
        </div>
    </div>
</div>

<div class="modal fade" id="vnl_m_full" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-fullscreen">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Fullscreen modal</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= lang('LBL_CLOSE') ?>"></button>
            </div>
            <div class="modal-body text-body-secondary">Tam ekran; karmaşık düzenleme ekranları için. <code>modal-fullscreen-lg-down</code> gibi kesme noktalı türevleri de vardır.</div>
        </div>
    </div>
</div>

<div class="modal fade" id="vnl_m_static" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Statik backdrop</h5>
            </div>
            <div class="modal-body text-body-secondary">Dışarı tıklamayla ve ESC ile kapanmaz — yalnız aksiyonla çıkılır. Kaydedilmemiş form verisi taşıyan modallar için.</div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Anladım</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade vnl-modal-sheet" id="vnl_m_sheet" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <div class="vnl-overline mb-1">// kayıt #1042</div>
                    <h5 class="modal-title mb-0">Kayıt Detayı</h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= lang('LBL_CLOSE') ?>"></button>
            </div>
            <div class="modal-body">
                <div class="mb-5">
                    <label class="form-label fs-8 text-uppercase text-body-secondary fw-bold">Başlık</label>
                    <input type="text" class="form-control form-control-solid" value="Örnek kayıt"/>
                </div>
                <div class="mb-5">
                    <label class="form-label fs-8 text-uppercase text-body-secondary fw-bold">Durum</label>
                    <select class="vnl-select form-select">
                        <option value="0">Seçiniz</option>
                        <option value="1" selected>Aktif</option>
                        <option value="2">Pasif</option>
                    </select>
                </div>
                <div class="mb-0">
                    <label class="form-label fs-8 text-uppercase text-body-secondary fw-bold">Not</label>
                    <textarea class="form-control form-control-solid" rows="4">Sheet gövdesi kendi içinde kayar; başlık ve altbilgi sabittir.</textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-soft-secondary" data-bs-dismiss="modal"><?= lang('LBL_CLOSE') ?></button>
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Kaydet</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="vnl_m_confirm" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="modal-body text-center pt-8 pb-6">
                <div class="vnl-icon-box bg-danger-subtle text-danger mx-auto mb-5"><i class="bi bi-trash"></i></div>
                <h5 class="fw-bold mb-2">Kayıt silinsin mi?</h5>
                <p class="text-body-secondary mb-0">Bu işlem geri alınamaz.</p>
            </div>
            <div class="modal-footer justify-content-center">
                <button type="button" class="btn btn-soft-secondary" data-bs-dismiss="modal">Vazgeç</button>
                <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Sil</button>
            </div>
        </div>
    </div>
</div>

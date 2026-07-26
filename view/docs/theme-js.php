<div class="d-flex flex-column flex-column-fluid">
    <div id="kt_app_content" class="app-content flex-column-fluid">
        <div id="kt_app_content_container" class="app-container container-xxl">

            <div class="vnl-page-hero mb-12 mt-6">
                <div class="vnl-overline mb-3">// docs/theme-js</div>
                <h1 class="fs-2hx fw-bold mb-4 text-body-emphasis">JavaScript API</h1>
                <div class="vnl-lead">
                    İstemci katmanı üç dosyadan oluşur: <code>vendors.min.js</code> (jQuery, Bootstrap,
                    SweetAlert2, SlimSelect), <code>vnl.min.js</code> (tema davranışları) ve
                    <code>assets/scripts/vnl.js</code> (VNL çekirdeği). Hepsi <code>head</code>'de yüklenir;
                    sayfa içi satır arası <code>$(...)</code> kullanımı güvenlidir.
                </div>
            </div>

            <div class="card mb-10">
                <div class="card-body p-10">

                    <div class="mb-10">
                        <h3 class="fw-bold text-body-emphasis mb-4">1. Mesajlar ve Yükleniyor</h3>
                        <div class="d-flex flex-wrap gap-3 mb-5">
                            <button type="button" class="btn btn-sm btn-soft-success" onclick="showMessage('Kayıt güncellendi', false)">Toast (başarı)</button>
                            <button type="button" class="btn btn-sm btn-soft-danger" onclick="showMessage('Bir şeyler ters gitti', true)">Toast (hata)</button>
                            <button type="button" class="btn btn-sm btn-soft-warning" onclick="showModalMessage('Bu işlem geri alınamaz.', 2)">Modal (uyarı)</button>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-row-dashed align-middle fs-7 gy-4">
                                <thead>
                                <tr class="text-start text-muted fw-bold fs-8 text-uppercase">
                                    <th class="min-w-300px">Çağrı</th><th>İş</th>
                                </tr>
                                </thead>
                                <tbody class="text-body fw-semibold">
                                <tr><td><code>showMessage(mesaj, hataMi)</code></td><td>Sağ altta toast (SweetAlert2)</td></tr>
                                <tr><td><code>showModalMessage(mesaj, mod)</code></td><td>Modal; mod: <code>0/false</code> başarı · <code>1/true</code> hata · <code>2</code> uyarı · <code>3</code> bilgi</td></tr>
                                <tr><td><code>showSpinner()</code> / <code>hideSpinner()</code></td><td>Tam sayfa yükleniyor katmanı (<code>#loadingSpinner</code>)</td></tr>
                                <tr><td><code>deleteItemFromDB({url, data, …})</code></td><td>Onaylı silme akışı (Swal onayı + AJAX)</td></tr>
                                <tr><td><code>copyToClipboard(metin)</code></td><td>Panoya kopyala + <code>LBL_COPIED</code> bildirimi</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="separator separator-dashed my-8"></div>

                    <div class="mb-10">
                        <h3 class="fw-bold text-body-emphasis mb-4">2. Tema Davranışları</h3>
                        <div class="table-responsive">
                            <table class="table table-row-dashed align-middle fs-7 gy-4">
                                <thead>
                                <tr class="text-start text-muted fw-bold fs-8 text-uppercase">
                                    <th class="min-w-300px">API</th><th>İş</th>
                                </tr>
                                </thead>
                                <tbody class="text-body fw-semibold">
                                <tr><td><code>vnlLightbox(src, altyazı)</code></td><td>Tam ekran görsel; bir <code>&lt;img&gt;</code>'e <code>.modal-image</code> vermek de yeterli</td></tr>
                                <tr><td><code>vnlTooltips(kapsam)</code></td><td>Tooltip'leri başlatır (<code>data-bs-toggle</code> ve eski <code>data-toggle</code>); AJAX sonrası otomatik çağrılır</td></tr>
                                <tr><td><code>vnlLang('ANAHTAR')</code> / <code>VNL_LANG</code></td><td>Sunucudan <code>lang()</code> ile beslenen JS dil köprüsü</td></tr>
                                <tr><td><code>select2Reload($(…))</code></td><td>AJAX select doldurma — bkz. <a href="/docs/theme-forms">Form ve Select</a></td></tr>
                                <tr><td><code>ClearForm($form)</code> / <code>checkValidity($form)</code></td><td>Form temizleme (SlimSelect dahil) ve basit doğrulama</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="separator separator-dashed my-8"></div>

                    <div class="mb-10">
                        <h3 class="fw-bold text-body-emphasis mb-4">3. Data Attribute API'leri</h3>
                        <div class="table-responsive">
                            <table class="table table-row-dashed align-middle fs-7 gy-4">
                                <thead>
                                <tr class="text-start text-muted fw-bold fs-8 text-uppercase">
                                    <th class="min-w-300px">Attribute</th><th>İş</th>
                                </tr>
                                </thead>
                                <tbody class="text-body fw-semibold">
                                <tr><td><code>data-vnl-theme-value="light|dark|system"</code></td><td>Tıklanınca tema modunu değiştirir ve kalıcılaştırır</td></tr>
                                <tr><td><code>data-vnl-toggle="sidebar-mini"</code></td><td>Sidebar'ı daraltır/açar (modu <code>body[data-vnl-sidebar-mode]</code>'dan alır)</td></tr>
                                <tr><td><code>data-vnl-indicator="on"</code></td><td>Buton yükleniyor deseni (eski <code>data-kt-indicator</code> da çalışır)</td></tr>
                                <tr><td><code>html[data-vnl-sidebar="mini|hover"]</code></td><td>Daraltılmış sidebar durumu (pre-paint restore edilir)</td></tr>
                                <tr><td><code>body[data-vnl-submenu="flyout|accordion"]</code></td><td>Alt menü davranışı — bkz. <a href="/docs/theme-sidebar">Sidebar ve Menü</a></td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="separator separator-dashed my-8"></div>

                    <div class="mb-0">
                        <h3 class="fw-bold text-body-emphasis mb-4">4. Olaylar</h3>
                        <div class="vnl-code"><pre><code>// Tema modu değişince (document üzerinde)
document.addEventListener('vnl.theme.changed', function (e) {
    // e.detail.mode: 'light' | 'dark' | 'system'
    // e.detail.resolved: gerçekte uygulanan 'light' | 'dark'
});</code></pre></div>
                        <p class="text-body-secondary mb-0 mt-4">
                            AJAX yaşam döngüsü (global hata yönetimi, 401 yenilemesi, <code>elementReady()</code>)
                            için bkz. <a href="/docs/request-handling">İstek Yönetimi</a>.
                        </p>
                    </div>

                </div>
            </div>

        </div>
    </div>
</div>

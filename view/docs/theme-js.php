<div class="d-flex flex-column flex-column-fluid">
    <div id="kt_app_content" class="app-content flex-column-fluid">
        <div id="kt_app_content_container" class="app-container container-xxl">

            <div class="vnl-page-hero mb-12 mt-6">
                <div class="vnl-overline mb-3">// docs/theme-js</div>
                <h1 class="fs-2hx fw-bold mb-4 text-body-emphasis">JavaScript API</h1>
                <div class="vnl-lead">
                    İstemci katmanının sırası çekirdekten gelir: <code>vnl_core_scripts()</code> →
                    <code>vendors.min.js</code> (jQuery, Bootstrap, SweetAlert2, SlimSelect),
                    <code>vnl.min.js</code> (tema davranışları), <code>vnl.js</code> (VNL çekirdeği),
                    <code>vnltable.js</code>, <code>flatpickr.min.js</code> ve <code>vnl-date.js</code>.
                    Önlerinde, CSRF meta etiketinin hemen altında <code>vnl-csrf.js</code> yüklenir.
                    Hepsi <code>head</code>'dedir; sayfa içi satır arası <code>$(...)</code> kullanımı güvenlidir.
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
                                <tr><td><code>vnlBtnLoading(btn, metin?)</code> / <code>vnlBtnReset(btn)</code></td><td>Yükleniyor düğmeleri; deklaratif <code>data-vnl-loading</code> jQuery AJAX trafiği bitince (<code>ajaxStop</code>) kendini sıfırlar; <code>fetch</code> ile çalışan düğmede <code>vnlBtnReset(btn)</code>'i kendiniz çağırın — bkz. <a href="/docs/theme-components">Bileşenler</a></td></tr>
                                <tr><td><code>showSpinner()</code> / <code>hideSpinner()</code></td><td>Tam sayfa yükleniyor katmanı (<code>#loadingSpinner</code>)</td></tr>
                                <tr><td><code>deleteItemFromDB({url, data, …})</code></td><td>Onaylı silme akışı (Swal onayı + AJAX) — seçenekler aşağıda</td></tr>
                                <tr><td><code>copyToClipboard(metin, bildir?)</code></td><td>Panoya kopyala; Clipboard API, güvenli bağlam yoksa execCommand'e düşer. Bildirim yalnızca kopyalama başarılıysa gösterilir</td></tr>
                                <tr><td><code>vnl-csrf.js</code></td><td>jQuery'siz istekler için CSRF: düz <code>fetch</code>, ham <code>XMLHttpRequest</code> ve <code>navigator.sendBeacon</code> çağrılarını sarar. Yalnız aynı kökene ve yazan yöntemlere (GET/HEAD/OPTIONS dışı) <code>X-CSRF-Token</code> ekler; <code>sendBeacon</code> başlık taşıyamadığı için jeton gövdeye <code>_token</code> olarak girer. Çağrı noktası değişmez. Kendi <code>&lt;head&gt;</code>'ini yazan proje: <code>&lt;script src="&lt;?= vnl_e(vnl_asset('js/vnl-csrf.js')) ?&gt;"&gt;&lt;/script&gt;</code></td></tr>
                                </tbody>
                            </table>
                        </div>

                        <h5 class="fw-bold text-body-emphasis mt-8 mb-3"><code>deleteItemFromDB()</code></h5>
                        <p class="text-body mb-4">
                            Onay kutusu açar, onaylanırsa AJAX isteğini gönderir, başarıda toast basar.
                            Hata yolu bilinçli olarak boştur: <code>400/401/406/410/500</code> yanıtları zaten
                            genel AJAX kancasından geçip mesajı basıyor, ikinci bir modal aynı hatayı iki kez
                            gösterirdi. Swal'ın vaadini döndürür.
                        </p>
                        <div class="vnl-code mb-5">
                            <pre class="mb-0" style="background: transparent; white-space: pre-wrap;"><code>deleteItemFromDB({
    url:   '/app/kayit/sil',     // zorunlu
    data:  { id: 42 },
    table: 'kayitlar'            // silme sonrası VNL Table'ı tazele
});</code></pre>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-row-dashed align-middle fs-7 gy-4">
                                <thead>
                                <tr class="text-start text-muted fw-bold fs-8 text-uppercase">
                                    <th class="min-w-175px">Seçenek</th><th>İş</th>
                                </tr>
                                </thead>
                                <tbody class="text-body fw-semibold">
                                <tr><td><code>url</code></td><td><b>Zorunlu.</b> İsteğin gideceği action</td></tr>
                                <tr><td><code>data</code></td><td>Gönderilecek gövde</td></tr>
                                <tr><td><code>method</code></td><td>Varsayılan <code>POST</code></td></tr>
                                <tr><td><code>title</code> / <code>text</code></td><td>Onay kutusunun başlığı ve metni</td></tr>
                                <tr><td><code>confirmText</code> / <code>cancelText</code></td><td>Düğme yazıları</td></tr>
                                <tr><td><code>successMessage</code></td><td>Sunucu <code>message</code> döndürmezse basılacak metin</td></tr>
                                <tr><td><code>table</code></td><td>VNL Table kimliği; başarıda <code>reload()</code> edilir</td></tr>
                                <tr><td><code>reload</code></td><td><code>true</code> ise sayfa yeniden yüklenir</td></tr>
                                <tr><td><code>onSuccess</code></td><td>Başarı geri çağrısı; yanıtı alır</td></tr>
                                <tr><td><code>customErrMessage</code></td><td><code>true</code> ise hata modalını çekirdek basmaz</td></tr>
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
                                <tr><td><code>selectReload($(…))</code></td><td>AJAX select doldurma — bkz. <a href="/docs/theme-forms">Form ve Select</a></td></tr>
                                <tr><td><code>clearForm($form)</code> / <code>validateForm($form)</code></td><td>Form temizleme (SlimSelect ve VNL Date dahil) ve basit doğrulama. VNL 3.0'dan önce (2.x) adlar <code>ClearForm</code> / <code>checkValidity</code> idi</td></tr>
                                <tr><td><code>vnlDate(el)</code> · <code>vnlDate.set/clear/init/parse/format</code></td><td>Tarih ve saat seçici; sayfa <code>&lt;input data-vnl-date&gt;</code> yazar, JS gerekmez — bkz. <a href="/docs/theme-dates">Tarih ve Saat</a></td></tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="alert alert-secondary d-flex align-items-start p-4 mt-5">
                            <i class="fa-solid fa-circle-info fs-2 text-body-secondary me-3"></i>
                            <span class="text-body fs-6">
                                <b>Genel bir JS dil köprüsü yoktur.</b> <code>vnlLang('ANAHTAR')</code> ya da
                                <code>VNL_LANG</code> gibi bir API tanımlı değildir. Sözlüğün tamamını tarayıcıya indirmek yerine
                                <b>her bileşen kendi metinlerini kendi tanımıyla taşır</b> — VNL Table'ın
                                yaptığı budur (<code>VnlTable::lang()</code>, metinler tablo tanımının
                                <code>lang</code> bloğunda gelir). Kendi bileşeninizde de aynısını yapın:
                                gereken anahtarları sunucuda <code>lang()</code> ile toplayıp veriyle
                                birlikte gönderin.
                            </span>
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
});

// Tarih seçici — input üzerinde yayılır, kabarır (bkz. /docs/theme-dates)
document.addEventListener('vnl.date.change', function (e) {
    // e.detail.value: sunucuya gidecek değer · e.detail.text: ekrandaki metin · e.detail.dates: [Date]
});
// vnl.date.open · vnl.date.close · vnl.date.ready</code></pre></div>
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

<div class="d-flex flex-column flex-column-fluid">
    <div id="kt_app_content" class="app-content flex-column-fluid">
        <div id="kt_app_content_container" class="app-container container-xxl">

            <div class="vnl-page-hero mb-12 mt-6">
                <div class="vnl-overline mb-3">// docs/theme-forms</div>
                <h1 class="fs-2hx fw-bold mb-4 text-body-emphasis">Form ve Select</h1>
                <div class="vnl-lead">
                    Form kontrolleri Bootstrap'tir; tema dolgun ölçüler, solid varyant ve parola
                    göster/gizle ekler. Select tarafında Select2'nin yerini SlimSelect aldı —
                    <b>VNL 2.x sözleşmesi değişmeden</b>.
                </div>
            </div>

            <div class="card mb-10">
                <div class="card-body p-10">

                    <div class="mb-10">
                        <h3 class="fw-bold text-body-emphasis mb-4">1. Form Varyantları</h3>
                        <div class="row g-5 mb-5">
                            <div class="col-md-4">
                                <label class="form-label fs-8 text-uppercase text-body-secondary fw-bold">Standart</label>
                                <input type="text" class="form-control" placeholder="form-control"/>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fs-8 text-uppercase text-body-secondary fw-bold">Solid</label>
                                <input type="text" class="form-control form-control-solid" placeholder="form-control-solid"/>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fs-8 text-uppercase text-body-secondary fw-bold">Parola (show-password)</label>
                                <div class="show-password">
                                    <input type="password" class="form-control form-control-solid" placeholder="••••••••"/>
                                </div>
                            </div>
                        </div>
                        <div class="vnl-code"><pre><code>&lt;input class="form-control form-control-solid" /&gt;     &lt;!-- dolgulu varyant --&gt;
&lt;select class="form-select form-select-solid"&gt;…&lt;/select&gt;

&lt;div class="show-password"&gt;                            &lt;!-- göz düğmesi otomatik eklenir --&gt;
    &lt;input type="password" class="form-control" /&gt;
&lt;/div&gt;</code></pre></div>
                        <p class="text-body-secondary mb-0 mt-4">
                            Karakter sayacı için <code>.length-counter</code> + <code>length-target-class</code>
                            deseni korunmuştur; buton yükleniyor deseni için bkz.
                            <a href="/docs/theme-components">Bileşenler</a>.
                        </p>
                    </div>

                    <div class="separator separator-dashed my-8"></div>

                    <div class="mb-10">
                        <h3 class="fw-bold text-body-emphasis mb-4">2. SlimSelect Köprüsü</h3>
                        <p class="text-body mb-4">
                            <code>select.select2</code> sınıflı öğeler otomatik SlimSelect'e dönüşür
                            (isim tarihseldir — dış projelerdeki çağrılar kırılmasın diye korunur).
                            Arama kutusu açıktır; <code>tags</code> attribute'u serbest değer eklemeyi açar.
                        </p>
                        <div class="row g-5 mb-5">
                            <div class="col-md-6">
                                <label class="form-label fs-8 text-uppercase text-body-secondary fw-bold">Canlı örnek</label>
                                <select class="select2 form-select">
                                    <option value="0">Seçiniz</option>
                                    <option value="1">Voxnup İletişim</option>
                                    <option value="2">ERP</option>
                                    <option value="3">Gayrimenkul</option>
                                    <option value="4">Mühendislik Hesap Motoru</option>
                                </select>
                            </div>
                        </div>
                        <div class="vnl-code"><pre><code>&lt;select class="select2"&gt;…&lt;/select&gt;                      &lt;!-- otomatik başlatılır --&gt;
&lt;select class="select2" tags&gt;…&lt;/select&gt;                 &lt;!-- serbest değer eklenebilir --&gt;</code></pre></div>
                    </div>

                    <div class="separator separator-dashed my-8"></div>

                    <div class="mb-0">
                        <h3 class="fw-bold text-body-emphasis mb-4">3. AJAX ile Doldurma (select2Reload)</h3>
                        <p class="text-body mb-4">
                            <code>action</code> attribute'lu select'ler <code>select2Reload()</code> ile
                            sunucudan doldurulur. Sözleşme VNL 2.x ile birebir aynıdır:
                        </p>
                        <div class="table-responsive mb-5">
                            <table class="table table-row-dashed align-middle fs-7 gy-4">
                                <thead>
                                <tr class="text-start text-muted fw-bold fs-8 text-uppercase">
                                    <th class="min-w-160px">Attribute</th><th>Anlamı</th>
                                </tr>
                                </thead>
                                <tbody class="text-body fw-semibold">
                                <tr><td><code>action</code></td><td>POST atılacak uç: <code>app/{modül}/{metod}</code>; <code>{id, text}</code> dizisi döner</td></tr>
                                <tr><td><code>prm_*</code></td><td><code>prm_rol="admin"</code> → POST'a <code>rol=admin</code> olarak gider</td></tr>
                                <tr><td><code>no-option</code> / <code>multiple</code></td><td>Verilmemişse başa <code>value="0"</code> "Seçiniz" eklenir (<code>ClearForm()</code> buna dayanır)</td></tr>
                                <tr><td><code>async</code></td><td>Varsa istek senkron yerine asenkron çalışır (tarihsel davranış korunur)</td></tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="vnl-code"><pre><code>&lt;select class="select2" action="app/kullanici/listele" prm_rol="admin"&gt;&lt;/select&gt;

&lt;script&gt;
    select2Reload($('select.select2[action]'));   // VNL 2.x çağrısı aynen çalışır
    // ClearForm($form) SlimSelect örneklerini de "Seçiniz"e sıfırlar
&lt;/script&gt;</code></pre></div>
                    </div>

                </div>
            </div>

        </div>
    </div>
</div>

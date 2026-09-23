<div class="d-flex flex-column flex-column-fluid">
    <div id="kt_app_content" class="app-content flex-column-fluid">
        <div id="kt_app_content_container" class="app-container container-xxl">

            <div class="vnl-page-hero mb-12 mt-6">
                <div class="vnl-overline mb-3">// docs/theme-dates</div>
                <h1 class="fs-2hx fw-bold mb-4 text-body-emphasis">Tarih ve Saat</h1>
                <div class="vnl-lead">
                    Tarih, saat, tarih+saat, ay ve aralık için tek bileşen: <b>VNL Date</b>.
                    Bir <code>&lt;input&gt;</code>'a <code>data-vnl-date</code> yazmak yeter — sayfa başına
                    JavaScript gerekmez. Ekranda Türkçe biçim, sunucuya ISO gider; eski
                    <code>gg/aa/yyyy</code> sözleşmeleri için metin kipi vardır. Altyapı
                    <a href="https://flatpickr.js.org/" target="_blank" rel="noopener">flatpickr</a>'dır
                    (MIT, bağımlılıksız, çekirdekle gelir); renkler tema token'larından okunur,
                    koyu tema ve skin kendiliğinden uyar.
                </div>
            </div>

            <div class="card mb-10">
                <div class="card-body p-10">

                    <div class="mb-10">
                        <h3 class="fw-bold text-body-emphasis mb-4">1. Beş Tür, Tek Nitelik</h3>
                        <p class="text-body mb-5">
                            Türü niteliğin değeri seçer; boş bırakılırsa <code>date</code>. Aşağıdakiler
                            canlıdır — açın, seçin, yazın (klavyeyle giriş açıktır).
                        </p>
                        <div class="row g-5 mb-5">
                            <div class="col-md-4 col-xl">
                                <label class="form-label fs-8 text-uppercase text-body-secondary fw-bold" for="vnl_dt_date">date</label>
                                <input type="text" id="vnl_dt_date" name="tarih" class="form-control" data-vnl-date value="2026-09-14" placeholder="gg.aa.yyyy"/>
                            </div>
                            <div class="col-md-4 col-xl">
                                <label class="form-label fs-8 text-uppercase text-body-secondary fw-bold" for="vnl_dt_time">time</label>
                                <input type="text" id="vnl_dt_time" name="saat" class="form-control" data-vnl-date="time" value="09:30:00" placeholder="ss:dd"/>
                            </div>
                            <div class="col-md-4 col-xl">
                                <label class="form-label fs-8 text-uppercase text-body-secondary fw-bold" for="vnl_dt_datetime">datetime</label>
                                <input type="text" id="vnl_dt_datetime" name="zaman" class="form-control" data-vnl-date="datetime" value="" placeholder="gg.aa.yyyy ss:dd"/>
                            </div>
                            <div class="col-md-4 col-xl">
                                <label class="form-label fs-8 text-uppercase text-body-secondary fw-bold" for="vnl_dt_month">month</label>
                                <input type="text" id="vnl_dt_month" name="donem" class="form-control" data-vnl-date="month" value="2026-09" placeholder="aa.yyyy"/>
                            </div>
                            <div class="col-md-4 col-xl">
                                <label class="form-label fs-8 text-uppercase text-body-secondary fw-bold" for="vnl_dt_range">range</label>
                                <input type="text" id="vnl_dt_range" name="aralik" class="form-control" data-vnl-date="range" value="" placeholder="başlangıç - bitiş"/>
                            </div>
                        </div>
                        <div class="vnl-code"><pre><code>&lt;input data-vnl-date            name="tarih"  class="form-control"&gt;   &lt;!-- 14.09.2026      → 2026-09-14 --&gt;
&lt;input data-vnl-date="time"     name="saat"   class="form-control"&gt;   &lt;!-- 09:30           → 09:30:00 --&gt;
&lt;input data-vnl-date="datetime" name="zaman"  class="form-control"&gt;   &lt;!-- 14.09.2026 09:30 → 2026-09-14 09:30:00 --&gt;
&lt;input data-vnl-date="month"    name="donem"  class="form-control"&gt;   &lt;!-- 09.2026         → 2026-09 --&gt;
&lt;input data-vnl-date="range"    name="aralik" class="form-control"&gt;   &lt;!-- 01.09.2026 - 14.09.2026 → 2026-09-01 - 2026-09-14 --&gt;</code></pre></div>
                        <p class="text-body-secondary mb-0 mt-4">
                            Takvim ikonu isteyen sayfa için <code>.input-group</code> deseni: ikonlu
                            <code>span</code>'a <code>data-vnl-date-toggle</code> verilirse tıklama aynı
                            gruptaki seçiciyi açar. <code>&lt;label for&gt;</code> da çalışır — gizlenmiş
                            asıl input yerine biçimli kopyayı odaklar.
                        </p>
                        <div class="row g-5 mt-1">
                            <div class="col-md-4">
                                <div class="input-group">
                                    <input type="text" id="vnl_dt_icon" name="ikonlu" class="form-control" data-vnl-date placeholder="gg.aa.yyyy"/>
                                    <span class="input-group-text" data-vnl-date-toggle role="button" title="Takvimi aç"><i class="bi bi-calendar3"></i></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="separator separator-dashed my-8"></div>

                    <div class="mb-10">
                        <h3 class="fw-bold text-body-emphasis mb-4">2. Değer Sözleşmesi: Ekran ve Sunucu Ayrı</h3>
                        <p class="text-body mb-4">
                            HTML'in kendi <code>&lt;input type="date"&gt;</code>'i ne yapıyorsa VNL Date de onu yapar:
                            kullanıcı yerel biçimi görür, <code>value</code> her zaman ISO'dur. Bunun için asıl
                            input <code>type="hidden"</code> olur ve ISO değeri taşır (<code>name</code>, <code>id</code>
                            onda kalır); kullanıcı hemen yanına konan biçimli bir kopyada çalışır. Form
                            gönderildiğinde sunucuya giden, gizli olandır. Sonuç: action'da dönüşüm yok,
                            <code>Y-m-d</code> doğrudan modele ve MySQL'e gider.
                        </p>
                        <div class="table-responsive mb-5">
                            <table class="table table-row-dashed align-middle fs-7 gy-4">
                                <thead>
                                <tr class="text-start text-muted fw-bold fs-8 text-uppercase">
                                    <th class="min-w-100px">Tür</th><th>Ekran (varsayılan)</th><th><code>value</code> — sunucuya giden</th>
                                </tr>
                                </thead>
                                <tbody class="text-body fw-semibold">
                                <tr><td><code>date</code></td><td><code>d.m.Y</code> → 14.09.2026</td><td><code>Y-m-d</code> → 2026-09-14</td></tr>
                                <tr><td><code>time</code></td><td><code>H:i</code> → 09:30</td><td><code>H:i:S</code> → 09:30:00</td></tr>
                                <tr><td><code>datetime</code></td><td><code>d.m.Y H:i</code> → 14.09.2026 09:30</td><td><code>Y-m-d H:i:S</code> → 2026-09-14 09:30:00</td></tr>
                                <tr><td><code>month</code></td><td><code>m.Y</code> → 09.2026</td><td><code>Y-m</code> → 2026-09</td></tr>
                                <tr><td><code>range</code></td><td><code>d.m.Y</code> - <code>d.m.Y</code></td><td><code>Y-m-d - Y-m-d</code> (ayraç: boşluk, tire, boşluk — <code>explode(' - ', …)</code>)</td></tr>
                                </tbody>
                            </table>
                        </div>
                        <p class="text-body mb-4">
                            <b>Metin kipi.</b> Sunucusu ekrandaki metni bekleyen bir sayfa (eski
                            <code>gg/aa/yyyy</code> sözleşmesi, <code>IsDateValid($x, 'd/m/Y')</code> gibi denetimler)
                            <code>data-vnl-date-value="text"</code> der: kopya yaratılmaz, input'un değeri
                            ekrandaki metnin kendisidir. Ekran biçimini <code>data-vnl-date-format</code> seçer;
                            metin kipinde bu aynı zamanda sunucuya giden biçimdir.
                        </p>
                        <div class="row g-5 mb-5 align-items-end">
                            <div class="col-md-4">
                                <label class="form-label fs-8 text-uppercase text-body-secondary fw-bold" for="vnl_dt_text">Metin kipi, d/m/Y</label>
                                <input type="text" id="vnl_dt_text" name="eski_tarih" class="form-control" data-vnl-date data-vnl-date-value="text" data-vnl-date-format="d/m/Y" value="14/09/2026" placeholder="gg/aa/yyyy"/>
                            </div>
                            <div class="col-md-8">
                                <div class="fs-7 text-body-secondary">
                                    Seçim yapın, iki kutunun <code>value</code>'sunu izleyin:
                                    <code>#vnl_dt_date</code> = <span class="fw-bold text-body" id="vnl_dt_watch_iso">—</span>,
                                    <code>#vnl_dt_text</code> = <span class="fw-bold text-body" id="vnl_dt_watch_text">—</span>
                                </div>
                            </div>
                        </div>
                        <div class="vnl-code"><pre><code>&lt;!-- Sunucuya ekrandaki metin gider: 14/09/2026 --&gt;
&lt;input data-vnl-date data-vnl-date-value="text" data-vnl-date-format="d/m/Y" name="tarih"&gt;

&lt;!-- Ekran değişir, kablo değişmez: "14 Eylül 2026" görünür, 2026-09-14 gider --&gt;
&lt;input data-vnl-date data-vnl-date-format="j F Y" name="tarih"&gt;</code></pre></div>
                        <p class="text-body-secondary mb-0 mt-4">
                            Proje genelinde farklı bir varsayılan isteniyorsa tek yer <code>script-standard.php</code>:
                            <code>vnlDate.defaults({value: 'text', format: {date: 'd/m/Y', datetime: 'd/m/Y H:i:S', month: 'm/Y'}})</code>.
                            Nitelik her zaman varsayılanı ezer. Girişteki değer hangi biçimde olursa olsun
                            okunur (ISO, ekran biçimi, ayraç farkı); okunamayan değer flatpickr'ın esnek
                            ayrıştırıcısına bırakılmaz, temizlenir ve konsola yazılır.
                        </p>
                    </div>

                    <div class="separator separator-dashed my-8"></div>

                    <div class="mb-10">
                        <h3 class="fw-bold text-body-emphasis mb-4">3. Nitelikler</h3>
                        <div class="table-responsive mb-5">
                            <table class="table table-row-dashed align-middle fs-7 gy-4">
                                <thead>
                                <tr class="text-start text-muted fw-bold fs-8 text-uppercase">
                                    <th class="min-w-250px">Nitelik</th><th>Değer</th><th>Anlamı</th>
                                </tr>
                                </thead>
                                <tbody class="text-body fw-semibold">
                                <tr><td><code>data-vnl-date</code></td><td><code>date</code> · <code>time</code> · <code>datetime</code> · <code>month</code> · <code>range</code></td><td>Tür. Boş = <code>date</code>.</td></tr>
                                <tr><td><code>data-vnl-date-format</code></td><td>flatpickr token'ları: <code>d m Y j n y H i S F M l D</code></td><td>Ekran biçimi. Metin kipinde sunucuya giden biçim de budur. <code>S</code> geçiyorsa saniye kendiliğinden açılır.</td></tr>
                                <tr><td><code>data-vnl-date-value</code></td><td><code>iso</code> (varsayılan) · <code>text</code></td><td>Kablo sözleşmesi — bkz. §2.</td></tr>
                                <tr><td><code>data-vnl-date-min</code> / <code>-max</code></td><td><code>today</code> · ISO · ekran biçimi</td><td>Seçilebilir en erken / en geç değer. Çalışma anında: <code>vnlDate(el).set('minDate', …)</code>.</td></tr>
                                <tr><td><code>data-vnl-date-step</code></td><td>dakika, varsayılan <code>1</code></td><td>Saat şeridinde dakika adımı (<code>10</code>, <code>15</code>, <code>30</code>).</td></tr>
                                <tr><td><code>data-vnl-date-seconds</code></td><td>—</td><td>Saniye kutusunu açar.</td></tr>
                                <tr><td><code>data-vnl-date-inline</code></td><td>—</td><td>Takvim açılır pencere yerine sayfada, input'un altında durur.</td></tr>
                                <tr><td><code>data-vnl-date-locale</code></td><td><code>tr</code> (varsayılan) · <code>en</code></td><td>Ay/gün adları ve haftanın ilk günü. Başka bir dil için flatpickr'ın <code>l10n/</code> dosyası ayrıca yüklenir.</td></tr>
                                <tr><td><code>data-vnl-date-input</code></td><td><code>off</code></td><td>Klavyeyle yazmayı kapatır; kutu salt okunur, yalnız takvimden seçilir.</td></tr>
                                <tr><td><code>data-vnl-date-toggle</code></td><td>boş ya da seçici</td><td>Herhangi bir elemanda: tıklanınca hedef seçiciyi açar. Boşsa aynı <code>.input-group</code> içindeki.</td></tr>
                                <tr><td><code>readonly</code> · <code>disabled</code> (HTML)</td><td>—</td><td>Salt okunur alanda takvim açılmaz, yazılamaz; değer yalnız <code>vnlDate.set()</code> ile değişir. Devre dışı alan hiç açılmaz. İkisi de kurulum anında okunur.</td></tr>
                                </tbody>
                            </table>
                        </div>
                        <p class="text-body mb-4">
                            Sınır ve adım örnekleri — soldaki yalnız geçmişi (bugüne kadar), ortadaki
                            bu yılı kabul eder, sağdaki saati onar dakika atlar:
                        </p>
                        <div class="row g-5 mb-5">
                            <div class="col-md-4">
                                <label class="form-label fs-8 text-uppercase text-body-secondary fw-bold" for="vnl_dt_nofuture">max="today"</label>
                                <input type="text" id="vnl_dt_nofuture" class="form-control" data-vnl-date data-vnl-date-max="today" placeholder="bugün ya da öncesi"/>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fs-8 text-uppercase text-body-secondary fw-bold" for="vnl_dt_bounds">min / max</label>
                                <input type="text" id="vnl_dt_bounds" class="form-control" data-vnl-date data-vnl-date-min="<?= date('Y') ?>-01-01" data-vnl-date-max="<?= date('Y') ?>-12-31" placeholder="<?= date('Y') ?> içinde"/>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fs-8 text-uppercase text-body-secondary fw-bold" for="vnl_dt_step">time, step="10"</label>
                                <input type="text" id="vnl_dt_step" class="form-control" data-vnl-date="time" data-vnl-date-step="10" placeholder="ss:dd"/>
                            </div>
                        </div>
                        <div class="row g-5 mb-5">
                            <div class="col-md-4">
                                <label class="form-label fs-8 text-uppercase text-body-secondary fw-bold" for="vnl_dt_en">locale="en", format="M j, Y"</label>
                                <input type="text" id="vnl_dt_en" class="form-control" data-vnl-date data-vnl-date-locale="en" data-vnl-date-format="M j, Y" value="2026-09-14"/>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fs-8 text-uppercase text-body-secondary fw-bold" for="vnl_dt_ro">input="off" (yalnız takvim)</label>
                                <input type="text" id="vnl_dt_ro" class="form-control" data-vnl-date data-vnl-date-input="off" placeholder="seçmek için tıklayın"/>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fs-8 text-uppercase text-body-secondary fw-bold" for="vnl_dt_dis">disabled</label>
                                <input type="text" id="vnl_dt_dis" class="form-control" data-vnl-date value="2026-09-14" disabled/>
                            </div>
                        </div>
                        <div class="vnl-code"><pre><code>&lt;input data-vnl-date data-vnl-date-max="today"&gt;                              &lt;!-- geleceğe kapalı --&gt;
&lt;input data-vnl-date data-vnl-date-min="2026-01-01" data-vnl-date-max="2026-12-31"&gt;
&lt;input data-vnl-date="time" data-vnl-date-step="10"&gt;                          &lt;!-- 09:00, 09:10, … --&gt;
&lt;input data-vnl-date="datetime" data-vnl-date-seconds&gt;                        &lt;!-- ss:dd:sn --&gt;
&lt;input data-vnl-date data-vnl-date-inline&gt;                                    &lt;!-- takvim sayfada --&gt;</code></pre></div>
                    </div>

                    <div class="separator separator-dashed my-8"></div>

                    <div class="mb-10">
                        <h3 class="fw-bold text-body-emphasis mb-4">4. Modal ve Offcanvas İçinde</h3>
                        <p class="text-body mb-4">
                            Bootstrap 5'in modalı odağı içeride tutar; <code>body</code>'ye eklenen bir takvimin
                            saat kutusuna yazılamazdı. VNL Date modal (<code>.modal-content</code>) ya da
                            offcanvas içindeki bir input için takvimi <b>o kabın içine</b> ekler ve kabın
                            koordinatlarında konumlar — kap kayarsa takvim girişin peşinden gider. Sayfa
                            yazarının yapacağı bir şey yoktur.
                        </p>
                        <button type="button" class="btn btn-sm btn-soft-primary" data-bs-toggle="modal" data-bs-target="#vnl_dt_modal">
                            <i class="bi bi-window"></i> Modalda dene
                        </button>
                    </div>

                    <div class="separator separator-dashed my-8"></div>

                    <div class="mb-10">
                        <h3 class="fw-bold text-body-emphasis mb-4">5. JavaScript API ve Olaylar</h3>
                        <p class="text-body mb-4">
                            Her şey <code>vnlDate</code> altında. <code>vnlDate(el)</code> flatpickr örneğini verir;
                            oradan ötesi flatpickr'ın kendi API'sidir (<code>setDate</code>, <code>set</code>,
                            <code>open</code>, <code>clear</code>, <code>destroy</code>, <code>selectedDates</code>).
                        </p>
                        <div class="table-responsive mb-5">
                            <table class="table table-row-dashed align-middle fs-7 gy-4">
                                <thead>
                                <tr class="text-start text-muted fw-bold fs-8 text-uppercase">
                                    <th class="min-w-300px">Çağrı</th><th>İş</th>
                                </tr>
                                </thead>
                                <tbody class="text-body fw-semibold">
                                <tr><td><code>vnlDate(el)</code></td><td>Örnek; <code>el</code> eleman, seçici ya da jQuery. Kurulmamışsa <code>null</code>.</td></tr>
                                <tr><td><code>vnlDate.set(el, değer, biçim?)</code></td><td>Yazar. <code>Date</code>, ISO, ekran biçimi ya da <code>null</code>; aralık için dizi. Okunamazsa <code>false</code> döner, değere dokunmaz.</td></tr>
                                <tr><td><code>vnlDate.clear(el)</code> / <code>vnlDate.value(el)</code></td><td>Temizler / sunucuya gidecek değeri okur.</td></tr>
                                <tr><td><code>vnlDate.init(kök?)</code> / <code>vnlDate.destroy(kök)</code></td><td>Kök altındaki kurulmamış input'ları kurar / söker. Sonradan eklenen içerik zaten kendiliğinden kurulur (§6).</td></tr>
                                <tr><td><code>vnlDate.parse(metin, biçim)</code> · <code>vnlDate.format(tarih, biçim)</code></td><td>Katı ayrıştırma (eşleşmeyen metne <code>null</code>) ve Türkçe biçimleme: <code>vnlDate.format('2026-09-14', 'l, j F Y')</code> → "Pazartesi, 14 Eylül 2026".</td></tr>
                                <tr><td><code>vnlDate.defaults({…})</code></td><td>Proje geneli varsayılanlar: <code>value</code>, <code>format</code>, <code>wire</code>, <code>step</code>, <code>locale</code>, <code>allowInput</code>.</td></tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="vnl-code mb-5"><pre><code>vnlDate.set('#tarih', '2026-09-14');          // ISO
vnlDate.set('#tarih', '14.09.2026');          // ekran biçimi de olur
vnlDate('#tarih').set('minDate', 'today');    // flatpickr API'si: sınırı sonradan değiştir
vnlDate('#aralik').selectedDates;             // [Date, Date]

// Olaylar input üzerinde yayılır ve kabarır; detail: {dates, value, text, instance}
document.addEventListener('vnl.date.change', function (e) {
    console.log(e.target.name, e.detail.value, e.detail.text);
});
// vnl.date.open · vnl.date.close · vnl.date.ready — aynı biçimde.
// Yerli 'change' olayı da asıl input'ta tetiklenir: $('#tarih').on('change', …) çalışır.</code></pre></div>
                        <div class="d-flex flex-wrap gap-3 align-items-center">
                            <button type="button" class="btn btn-sm btn-soft-primary" onclick="vnlDate.set('#vnl_dt_date', 'today')">set('#vnl_dt_date', 'today')</button>
                            <button type="button" class="btn btn-sm btn-soft-primary" onclick="vnlDate.set('#vnl_dt_range', ['2026-09-01', '2026-09-14'])">set(range, [1 Eyl, 14 Eyl])</button>
                            <button type="button" class="btn btn-sm btn-soft-warning" onclick="vnlDate.clear('#vnl_dt_date'); vnlDate.clear('#vnl_dt_range')">clear()</button>
                            <span class="fs-7 text-body-secondary">Son olay: <span class="fw-bold text-body" id="vnl_dt_last_event">—</span></span>
                        </div>
                    </div>

                    <div class="separator separator-dashed my-8"></div>

                    <div class="mb-10">
                        <h3 class="fw-bold text-body-emphasis mb-4">6. Sonradan Gelen İçerik</h3>
                        <p class="text-body mb-4">
                            AJAX ile gelen bir modal gövdesi ya da tabloya eklenen satır <code>data-vnl-date</code>
                            taşıyorsa kendiliğinden kurulur (belge bir <code>MutationObserver</code> ile izlenir);
                            DOM'dan çıkan input'un takvimi de toplanır, sızıntı olmaz. <code>elementReady()</code>
                            çağırmak gerekmez ama zararı da yoktur. İstisnasız çalışsın istiyorsanız
                            <code>vnlDate.init(kapsayıcı)</code> açık çağrıdır.
                        </p>
                        <div class="d-flex flex-wrap gap-3 align-items-center mb-4">
                            <button type="button" class="btn btn-sm btn-soft-primary" id="vnl_dt_add_row"><i class="bi bi-plus-lg"></i> Satır ekle</button>
                            <button type="button" class="btn btn-sm btn-soft-danger" id="vnl_dt_remove_row"><i class="bi bi-dash-lg"></i> Son satırı kaldır</button>
                            <span class="fs-7 text-body-secondary">Body'deki takvim sayısı: <span class="fw-bold text-body" id="vnl_dt_cal_count">—</span></span>
                        </div>
                        <div id="vnl_dt_rows" class="d-flex flex-column gap-3"></div>
                    </div>

                    <div class="separator separator-dashed my-8"></div>

                    <div class="mb-0">
                        <h3 class="fw-bold text-body-emphasis mb-4">7. Eski Seçicilerden Geçiş</h3>
                        <p class="text-body mb-4">
                            bootstrap-datepicker, bootstrap-datetimepicker (moment) ve bootstrap-timepicker
                            çağrılarının nitelik karşılığı. Kural: <b>seçenek nitelik olur, çağrı silinir.</b>
                            Sunucu ekrandaki metni bekliyorsa <code>data-vnl-date-value="text"</code> ile biçim
                            korunur; aksi halde ISO'ya geçmek için yalnız action'daki dönüşüm satırı kalkar.
                        </p>
                        <div class="table-responsive mb-5">
                            <table class="table table-row-dashed align-middle fs-7 gy-4">
                                <thead>
                                <tr class="text-start text-muted fw-bold fs-8 text-uppercase">
                                    <th class="min-w-300px">Eski</th><th>VNL Date</th>
                                </tr>
                                </thead>
                                <tbody class="text-body fw-semibold">
                                <tr><td><code>$(x).datepicker({format: 'dd/mm/yyyy'})</code></td><td><code>data-vnl-date data-vnl-date-format="d/m/Y"</code> (+ <code>-value="text"</code> metin sözleşmesi için)</td></tr>
                                <tr><td><code>datepicker({startDate, endDate})</code></td><td><code>data-vnl-date-min</code> / <code>data-vnl-date-max</code>; <code>endDate: today</code> → <code>-max="today"</code></td></tr>
                                <tr><td><code>datepicker({minViewMode: 'months', format: 'mm/yyyy'})</code></td><td><code>data-vnl-date="month" data-vnl-date-format="m/Y"</code></td></tr>
                                <tr><td><code>datepicker({minViewMode: 2})</code> (yalnız yıl)</td><td><b>Karşılığı yok.</b> flatpickr yıl-tek görünüm sunmaz; <code>&lt;select&gt;</code> ya da <code>type="number"</code> kullanın.</td></tr>
                                <tr><td><code>datepicker({language, weekStart: 1})</code></td><td>Varsayılan (tr, Pazartesi). Gerekirse <code>data-vnl-date-locale</code>.</td></tr>
                                <tr><td><code>datetimepicker({format: 'DD/MM/YYYY HH:mm:SS'})</code></td><td><code>data-vnl-date="datetime" data-vnl-date-format="d/m/Y H:i:S"</code> (saniye biçimden açılır)</td></tr>
                                <tr><td><code>datetimepicker({pickTime: false})</code></td><td><code>data-vnl-date</code> — düz tarih</td></tr>
                                <tr><td><code>timepicker({showMeridian: false, minuteStep: 10, showSeconds: false})</code></td><td><code>data-vnl-date="time" data-vnl-date-step="10"</code> (24 saat varsayılan; saniye için <code>-seconds</code>)</td></tr>
                                <tr><td><code>timepicker({defaultTime: false})</code></td><td>Varsayılan davranış: boş kutu açılınca değer yazılmaz.</td></tr>
                                <tr><td><code>datepicker('setDate', v)</code> / <code>timepicker('setTime', v)</code></td><td><code>vnlDate.set(el, v)</code></td></tr>
                                <tr><td><code>datepicker('update')</code> / <code>('setDate', null)</code></td><td><code>vnlDate.clear(el)</code>; <code>clearForm($form)</code> de temizler</td></tr>
                                <tr><td><code>.data('DateTimePicker').destroy()</code> + yeniden kurulum (sınır değişti)</td><td><code>vnlDate(el).set('minDate', v)</code> — sökmek gerekmez</td></tr>
                                <tr><td><code>.on('changeDate', fn)</code> / <code>.on('hide', fn)</code></td><td><code>vnl.date.change</code> / <code>vnl.date.close</code> olayları (ya da yerli <code>change</code>)</td></tr>
                                <tr><td><code>moment(v, 'DD/MM/YYYY').format('YYYY-MM-DD')</code></td><td><code>vnlDate.format(vnlDate.parse(v, 'd/m/Y'), 'Y-m-d')</code>; ISO kipinde zaten gerekmez — <code>value</code> ISO'dur</td></tr>
                                <tr><td><code>moment(d).format('dddd LL')</code></td><td><code>vnlDate.format(d, 'l j F Y')</code> → "Pazartesi 14 Eylül 2026"</td></tr>
                                <tr><td><code>daterangepicker({ranges: {…}})</code> (hazır aralıklar)</td><td><code>data-vnl-date="range"</code> aralığı verir; <b>hazır aralık düğmeleri yoktur</b> — sayfa <code>vnlDate.set(el, [a, b])</code> ile kendi düğmelerini kurar.</td></tr>
                                </tbody>
                            </table>
                        </div>
                        <p class="text-body-secondary mb-0">
                            flatpickr telefonlarda yerli <code>&lt;input type="date"&gt;</code>'e düşer (dokunmatikte
                            daha iyi); değer sözleşmesi orada da aynıdır. Ay ve aralık seçici bunun
                            dışındadır — ayın yerli karşılığı Firefox ve Safari'de yoktur, aralığın hiçbir yerde.
                        </p>
                    </div>

                </div>
            </div>

        </div>
    </div>
</div>

<!-- §4 modal örneği -->
<div class="modal fade" id="vnl_dt_modal" tabindex="-1" aria-labelledby="vnl_dt_modal_title" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="vnl_dt_modal_title">Modal içinde tarih ve saat</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Kapat"></button>
            </div>
            <div class="modal-body">
                <div class="mb-5">
                    <label class="form-label" for="vnl_dt_modal_dt">Başlangıç (datetime)</label>
                    <input type="text" id="vnl_dt_modal_dt" class="form-control" data-vnl-date="datetime" placeholder="gg.aa.yyyy ss:dd"/>
                </div>
                <div class="mb-5">
                    <label class="form-label" for="vnl_dt_modal_time">Mesai (time)</label>
                    <input type="text" id="vnl_dt_modal_time" class="form-control" data-vnl-date="time" value="09:00:00"/>
                </div>
                <div class="mb-0">
                    <label class="form-label" for="vnl_dt_modal_month">Dönem (month, metin kipi m/Y)</label>
                    <input type="text" id="vnl_dt_modal_month" class="form-control" data-vnl-date="month" data-vnl-date-value="text" data-vnl-date-format="m/Y" value="09/2026"/>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Kapat</button>
            </div>
        </div>
    </div>
</div>

<script>
    /* Sayfa örneklerinin izleme kodu — bileşenin parçası değildir. */
    $(function () {
        function watch() {
            $('#vnl_dt_watch_iso').text($('#vnl_dt_date').val() || '(boş)');
            $('#vnl_dt_watch_text').text($('#vnl_dt_text').val() || '(boş)');
            $('#vnl_dt_cal_count').text(document.querySelectorAll('body > .flatpickr-calendar').length);
        }

        document.addEventListener('vnl.date.change', function (e) {
            $('#vnl_dt_last_event').text('change · ' + (e.target.id || e.target.name) + ' = "' + e.detail.value + '" (ekran: "' + e.detail.text + '")');
            watch();
        });
        document.addEventListener('vnl.date.close', function (e) {
            $('#vnl_dt_last_event').text('close · ' + (e.target.id || e.target.name));
        });

        var rowNo = 0;
        $('#vnl_dt_add_row').on('click', function () {
            rowNo++;
            $('#vnl_dt_rows').append(
                '<div class="d-flex gap-3 align-items-center">' +
                '<span class="fs-7 text-body-secondary" style="width:4rem">Satır ' + rowNo + '</span>' +
                '<input type="text" class="form-control form-control-sm" style="max-width:12rem" data-vnl-date placeholder="gg.aa.yyyy">' +
                '<input type="text" class="form-control form-control-sm" style="max-width:8rem" data-vnl-date="time" placeholder="ss:dd">' +
                '</div>'
            );
            setTimeout(watch, 0);
        });
        $('#vnl_dt_remove_row').on('click', function () {
            $('#vnl_dt_rows > div').last().remove();
            setTimeout(watch, 0);
        });

        watch();
    });
</script>

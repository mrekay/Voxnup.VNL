<div class="d-flex flex-column flex-column-fluid">
    <div id="kt_app_content" class="app-content flex-column-fluid">
        <div id="kt_app_content_container" class="app-container container-xxl">

            <div class="vnl-page-hero mb-12 mt-6">
                <div class="vnl-overline mb-3">// docs/vnltable-expert</div>
                <h1 class="fs-2hx fw-bold mb-4 text-body-emphasis">VNL Table — Expert</h1>
                <div class="vnl-lead">
                    <code>basic</code>'in üstüne sütun filtreleri gelir. Her başlıkta bir huni vardır;
                    açılan panelde Excel'deki gibi <b>değer listesi</b> ve bir <b>koşul</b> bulunur.
                    Temel kullanım <a href="/docs/vnltable" class="text-primary fw-bold">VNL Table</a>
                    sayfasında anlatılır; burada yalnızca expert'e eklenenler var.
                </div>
            </div>

            <!--begin::Canlı örnek-->
            <div class="card mb-10">
                <div class="card-body p-10">
                    <h3 class="fw-bold text-body-emphasis mb-4">1. Canlı Örnek</h3>
                    <p class="fs-5 text-body mb-6">
                        Başlığın üzerine gelin, beliren huniye tıklayın. <b>Şehir</b> ve <b>Durum</b>
                        değer listesi gösterir. <b>Ad Soyad</b>, <b>Tutar</b> ve <b>Tarih</b>
                        yalnızca koşul alır — 213 satırın her birinde ayrı bir değer olduğu için
                        onay kutusu listesi işe yaramazdı. Filtre uygulanmış sütunun hunisi dolu
                        ve vurgulu kalır.
                    </p>

                    <?= VnlTable::make('demoExpert')
                        ->url('app/vnltable/getList')
                        ->mode(VnlTable::EXPERT)
                        ->key('id')
                        ->columns([
                            ['name' => 'id',    'title' => 'No',       'type' => 'number', 'width' => '80px', 'filter' => false],
                            ['name' => 'ad',    'title' => 'Ad Soyad', 'filter' => ['values' => false]],
                            ['name' => 'sehir', 'title' => 'Şehir'],
                            ['name' => 'durum', 'title' => 'Durum',    'filter' => 'set'],
                            ['name' => 'tutar', 'title' => 'Tutar',    'type' => 'number'],
                            ['name' => 'tarih', 'title' => 'Tarih',    'type' => 'date'],
                        ])
                        ->options([
                            'pageSize' => 10,
                            'order'    => [['col' => 'id', 'dir' => 'asc']],
                            'height'   => '420px',
                            'striped'  => true,
                        ])
                        ->render() ?>

                    <div class="alert alert-info d-flex align-items-start p-4 mt-6 mb-0">
                        <i class="fa-solid fa-lightbulb fs-2 text-info me-3"></i>
                        <span class="text-body fs-6">
                            Önce <b>Şehir</b>'den Ankara'yı seçin, sonra <b>Durum</b>'un panelini açın:
                            listede yalnızca Ankara'da geçen durumlar ve kaç kayıtta geçtikleri görünür.
                            Şehir panelini tekrar açtığınızda ise bütün şehirler yerinde durur —
                            sütunun kendi seçimi listesini daraltmaz.
                        </span>
                    </div>
                </div>
            </div>
            <!--end::Canlı örnek-->

            <div class="card mb-10">
                <div class="card-body p-10">

                    <!--begin::Açma-->
                    <div class="mb-10">
                        <h3 class="fw-bold text-body-emphasis mb-4">2. Expert'i Açmak</h3>
                        <p class="fs-5 text-body mb-4">
                            Tek satır: <code>mode()</code>. Sütun başına filtre yazmak zorunda değilsiniz —
                            verilmediğinde sütunun <code>type</code>'ından türetilir.
                        </p>

                        <div class="vnl-code mb-5">
                            <pre class="mb-0" style="background: transparent; white-space: pre-wrap;"><code>VnlTable::make('kayitlar')
    -&gt;url('app/kayit/getList')
    -&gt;mode(VnlTable::EXPERT)        // basic | expert | expert_plus
    -&gt;columns([
        ['name' =&gt; 'id',    'title' =&gt; 'No',    'type' =&gt; 'number', 'filter' =&gt; false],
        ['name' =&gt; 'ad',    'title' =&gt; 'Ad'],                        // text  → türetilir
        ['name' =&gt; 'sehir', 'title' =&gt; 'Şehir', 'filter' =&gt; 'set'],  // yalnız değer listesi
        ['name' =&gt; 'tutar', 'title' =&gt; 'Tutar', 'type' =&gt; 'number'], // number → türetilir
    ])
    -&gt;render();</code></pre>
                        </div>

                        <div class="alert alert-warning d-flex align-items-start p-4 mb-0">
                            <i class="fa-solid fa-triangle-exclamation fs-2 text-warning me-3"></i>
                            <span class="text-body fs-6">
                                <code>basic</code> modunda sütunlara yazdığınız <code>filter</code>
                                tanımları <b>yok sayılır</b> — panel çizilmez, istemciye de gitmez.
                                Filtre istiyorsanız <code>mode()</code> vermeyi unutmayın.
                            </span>
                        </div>
                    </div>
                    <!--end::Açma-->

                    <div class="separator separator-dashed my-8"></div>

                    <!--begin::filter-->
                    <div class="mb-10">
                        <h3 class="fw-bold text-body-emphasis mb-4">3. Sütun <code>filter</code> Seçeneği</h3>

                        <div class="table-responsive border rounded bg-body-tertiary mb-5">
                            <table class="table align-middle table-row-dashed fs-6 gy-4 mb-0">
                                <thead>
                                <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0 border-bottom">
                                    <th class="ps-5 min-w-250px">Yazım</th>
                                    <th class="min-w-350px">Anlamı</th>
                                </tr>
                                </thead>
                                <tbody class="text-body fw-semibold">
                                <tr>
                                    <td class="ps-5">verilmedi</td>
                                    <td><code>type</code>'tan türetilir: <code>number</code>→sayı, <code>date</code>/<code>datetime</code>→tarih, <code>bool</code>→bool, kalanı metin.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>false</code></td>
                                    <td>Sütunda huni çıkmaz. Kimlik ve işlem sütunları için.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>'set'</code></td>
                                    <td>Yalnız değer listesi; koşul bölümü hiç çizilmez.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>'text'</code> · <code>'number'</code> · <code>'date'</code> · <code>'bool'</code></td>
                                    <td>Türü elle verir. Karşılaştırmanın metin mi sayı mı tarih mi olduğunu bu belirler.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>['values' =&gt; false]</code></td>
                                    <td>Değer listesini kapatır, koşul kalır. Her satırda ayrı bir değer taşıyan sütunlarda kullanın.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>['ops' =&gt; ['equals','between']]</code></td>
                                    <td>Koşul listesini daraltır. Verilmeyen koşullar açılır listede görünmez ve sunucuda da kabul edilmez.</td>
                                </tr>
                                </tbody>
                            </table>
                        </div>

                        <p class="fs-6 text-body mb-3">
                            <code>values</code> — sütunun benzersiz değerlerinin panelde onay kutusu olarak
                            listelenmesi. Metin, bool ve set'te <b>açık</b>, sayı ve tarihte <b>kapalıdır</b>:
                            her satırın ayrı bir tutar taşıdığı bir sütunda 500 kutuluk liste işe yaramaz.
                        </p>

                        <div class="table-responsive border rounded bg-body-tertiary mb-0">
                            <table class="table align-middle table-row-dashed fs-6 gy-4 mb-0">
                                <thead>
                                <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0 border-bottom">
                                    <th class="ps-5 min-w-100px">Tür</th>
                                    <th class="min-w-100px">Değer listesi</th>
                                    <th class="min-w-350px">Koşullar</th>
                                </tr>
                                </thead>
                                <tbody class="text-body fw-semibold">
                                <tr>
                                    <td class="ps-5"><code>text</code></td>
                                    <td><span class="badge badge-soft-success">açık</span></td>
                                    <td><code>contains</code> <code>notContains</code> <code>equals</code> <code>notEquals</code> <code>startsWith</code> <code>endsWith</code> <code>empty</code> <code>notEmpty</code></td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>number</code></td>
                                    <td><span class="badge badge-soft-secondary">kapalı</span></td>
                                    <td><code>equals</code> <code>notEquals</code> <code>gt</code> <code>gte</code> <code>lt</code> <code>lte</code> <code>between</code> <code>empty</code> <code>notEmpty</code></td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>date</code></td>
                                    <td><span class="badge badge-soft-secondary">kapalı</span></td>
                                    <td><code>equals</code> <code>before</code> <code>after</code> <code>between</code> <code>empty</code> <code>notEmpty</code></td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>bool</code></td>
                                    <td><span class="badge badge-soft-success">açık</span></td>
                                    <td><code>empty</code> <code>notEmpty</code></td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>set</code></td>
                                    <td><span class="badge badge-soft-success">açık</span></td>
                                    <td>—</td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <!--end::filter-->

                    <div class="separator separator-dashed my-8"></div>

                    <!--begin::getList-->
                    <div class="mb-10">
                        <h3 class="fw-bold text-body-emphasis mb-4">4. <code>getList</code> Tarafı</h3>
                        <p class="fs-5 text-body mb-4">
                            İki iş eklenir: filtreleri sorguya uygulamak ve panelin değer listesini
                            (<code>op = distinct</code>) döndürmek. İkisi de aynı action'da olur.
                        </p>

                        <div class="vnl-code mb-5">
                            <pre class="mb-0" style="background: transparent; white-space: pre-wrap;"><code>public function getList($req)
{
    Auth::CheckLogin();

    $t = VnlTableRequest::from($req);

    // İstemciden gelen ad ile gerçek sütunu bu liste bağlar.
    // Listede olmayan hiçbir ad sorguya girmez.
    $alanlar = ['ad' =&gt; 'name', 'sehir' =&gt; 'city', 'tutar', 'tarih'];

    // Değer listesi: hedef sütunun KENDİ filtresi hariç tutulur
    if ($t-&gt;op === VnlTableRequest::OP_DISTINCT) {
        $q = Kayit::query()-&gt;select($t-&gt;column)-&gt;distinct();
        $t-&gt;applyFilters($q, $alanlar, $t-&gt;filtersExcept());

        $t-&gt;respondDistinct($q-&gt;limit(501)-&gt;pluck($t-&gt;column)-&gt;all());
    }

    $q = Kayit::query();
    $t-&gt;applyFilters($q, $alanlar);

    $filtered = $q-&gt;count();
    $rows     = $q-&gt;orderBy(...)-&gt;offset($t-&gt;offset())-&gt;limit($t-&gt;size)-&gt;get();

    $t-&gt;respond($rows-&gt;toArray(), Kayit::query()-&gt;count(), $filtered);
}</code></pre>
                        </div>

                        <div class="alert alert-danger d-flex align-items-start p-4 mb-5">
                            <i class="fa-solid fa-shield fs-2 text-danger me-3"></i>
                            <span class="text-body fs-6">
                                <code>applyFilters()</code>'ın sütun listesi <b>isteğe bağlı değildir</b>.
                                Filtre adları istemciden gelir; doğrudan sorguya verilmeleri sütun adı
                                enjeksiyonuna açık olurdu. Listede olmayan ad sessizce atlanır.
                            </span>
                        </div>

                        <h6 class="fw-bold mb-3">Bellekteki veriyle</h6>
                        <p class="fs-6 text-body mb-3">
                            Küçük listelerde ya da veritabanı olmayan kaynaklarda aynı filtreler
                            PHP tarafında uygulanabilir. Bu sayfadaki demo da böyle çalışır.
                        </p>

                        <div class="vnl-code mb-5">
                            <pre class="mb-0" style="background: transparent; white-space: pre-wrap;"><code>if ($t-&gt;op === VnlTableRequest::OP_DISTINCT) {
    $t-&gt;distinctFrom($satirlar);   // daraltma + arama + sayım + yanıt
}

$satirlar = $t-&gt;filterRows($satirlar);   // dizi ya da nesne satırlar
$tek      = $t-&gt;matches($satir);         // tek satır sınaması</code></pre>
                        </div>

                        <div class="table-responsive border rounded bg-body-tertiary mb-0">
                            <table class="table align-middle table-row-dashed fs-6 gy-4 mb-0">
                                <thead>
                                <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0 border-bottom">
                                    <th class="ps-5 min-w-250px">Üye</th>
                                    <th class="min-w-350px">İş</th>
                                </tr>
                                </thead>
                                <tbody class="text-body fw-semibold">
                                <tr><td class="ps-5"><code>$t-&gt;filters</code></td><td>Çözülmüş filtreler: <code>ad =&gt; ['kind','set','cond']</code></td></tr>
                                <tr><td class="ps-5"><code>$t-&gt;column</code></td><td><code>distinct</code> isteğinde değer listesi istenen sütun</td></tr>
                                <tr><td class="ps-5"><code>$t-&gt;query</code></td><td>Panelin kendi arama kutusundaki metin</td></tr>
                                <tr><td class="ps-5"><code>$t-&gt;hasFilters()</code></td><td>Herhangi bir sütun filtresi var mı</td></tr>
                                <tr><td class="ps-5"><code>$t-&gt;filtersExcept($col)</code></td><td>Hedef sütunun kendi filtresi hariç hepsi</td></tr>
                                <tr><td class="ps-5"><code>$t-&gt;applyFilters($q, $alanlar)</code></td><td>Eloquent / Query Builder'a uygular</td></tr>
                                <tr><td class="ps-5"><code>$t-&gt;filterRows($rows)</code></td><td>Bellekteki satırlara uygular</td></tr>
                                <tr><td class="ps-5"><code>$t-&gt;respondDistinct($values)</code></td><td>Değer listesi yanıtı</td></tr>
                                <tr><td class="ps-5"><code>$t-&gt;distinctFrom($rows)</code></td><td>Bellekten listeyi üretir ve yanıtlar</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <!--end::getList-->

                    <div class="separator separator-dashed my-8"></div>

                    <!--begin::Sözleşme-->
                    <div class="mb-10">
                        <h3 class="fw-bold text-body-emphasis mb-4">5. Tel Üzerindeki Biçim</h3>

                        <div class="row g-5 mb-6">
                            <div class="col-md-6">
                                <h6 class="fw-bold mb-3"><span class="badge badge-soft-primary me-2">list</span> istek</h6>
                                <div class="vnl-code">
                                    <pre class="mb-0" style="background: transparent; white-space: pre-wrap;"><code>{
  "op": "list",
  "page": 1, "size": 25,
  "search": "",
  "sort": [{"col":"tutar","dir":"desc"}],
  "filters": {
    "sehir": {
      "kind": "text",
      "set": ["Ankara","İzmir"]
    },
    "ad": {
      "kind": "text",
      "cond": {"op":"contains","value":"kayıt"}
    },
    "tutar": {
      "kind": "number",
      "cond": {"op":"between",
               "value":"1000","value2":"5000"}
    }
  },
  "context": {}
}</code></pre>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <h6 class="fw-bold mb-3"><span class="badge badge-soft-primary me-2">distinct</span> istek ve yanıt</h6>
                                <div class="vnl-code mb-4">
                                    <pre class="mb-0" style="background: transparent; white-space: pre-wrap;"><code>{
  "op": "distinct",
  "col": "durum",
  "q": "on",
  "filters": { ...durum HARİÇ hepsi... },
  "context": {}
}</code></pre>
                                </div>
                                <div class="vnl-code">
                                    <pre class="mb-0" style="background: transparent; white-space: pre-wrap;"><code>{
  "col": "durum",
  "values": [
    {"v":"Onaylandı","n":43},
    {"v":"Beklemede","n":12}
  ],
  "truncated": false
}</code></pre>
                                </div>
                            </div>
                        </div>

                        <div class="table-responsive border rounded bg-body-tertiary mb-0">
                            <table class="table align-middle table-row-dashed fs-6 gy-4 mb-0">
                                <thead>
                                <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0 border-bottom">
                                    <th class="ps-5 min-w-150px">Alan</th>
                                    <th class="min-w-350px">Kural</th>
                                </tr>
                                </thead>
                                <tbody class="text-body fw-semibold">
                                <tr><td class="ps-5"><code>kind</code></td><td>Karşılaştırmanın biçimi. Tanınmayan tür metne düşer; sorguya isim taşımaz.</td></tr>
                                <tr><td class="ps-5"><code>set</code></td><td>Değer listesinden seçilenler. <b>Hepsi</b> seçiliyse gönderilmez — filtre değildir. Değerler metindir; boş hücreler burada değil <code>empty</code> koşuluyla aranır.</td></tr>
                                <tr><td class="ps-5"><code>cond</code></td><td>Tek koşul. <code>empty</code>/<code>notEmpty</code> değer almaz, <code>between</code> iki değer alır. Değeri eksik koşul sunucuda düşürülür.</td></tr>
                                <tr><td class="ps-5"><code>set</code> + <code>cond</code></td><td>Aynı sütunda ikisi birden olabilir; <b>VE</b> ile bağlanır.</td></tr>
                                <tr><td class="ps-5"><code>n</code></td><td>Kayıt sayısı isteğe bağlıdır. <code>null</code> verirseniz panelde yalnızca değer görünür.</td></tr>
                                <tr><td class="ps-5"><code>truncated</code></td><td>Liste 500'de kesildi. İstemci bunu kullanıcıya söyler ve panel aramasını istemcide değil sunucuda çalıştırır.</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <!--end::Sözleşme-->

                    <div class="separator separator-dashed my-8"></div>

                    <!--begin::Davranış-->
                    <div class="mb-0">
                        <h3 class="fw-bold text-body-emphasis mb-4">6. Davranış Notları</h3>

                        <div class="row g-5">
                            <div class="col-md-6">
                                <h6 class="fw-bold mb-2">Değer listesi neye göre daralır</h6>
                                <p class="fs-6 text-body mb-5">
                                    Diğer sütunların filtrelerine ve düz aramaya göre; sütunun
                                    <b>kendi</b> seçimine göre değil. Kendi seçimi de sayılsaydı liste
                                    her uygulamada küçülür, kullanıcı az önce kapattığı değeri geri
                                    açmak için bulamazdı. Excel de böyle yapar.
                                </p>

                                <h6 class="fw-bold mb-2">Boş hücreler</h6>
                                <p class="fs-6 text-body mb-5">
                                    <code>NULL</code> ile boş metin kullanıcı için aynı şeydir; ikisi de
                                    <code>empty</code> koşuluyla bulunur. Değer listesine girmezler.
                                    <code>notContains</code> ve <code>notEquals</code> boş hücreleri
                                    <b>kapsar</b> — SQL'de <code>NOT LIKE</code> <code>NULL</code>'a
                                    <code>false</code> döndüğü için sorguya açıkça eklenir.
                                </p>

                                <h6 class="fw-bold mb-2">Büyük/küçük harf</h6>
                                <p class="fs-6 text-body mb-0">
                                    SQL tarafında sütunun collation'ına bağlıdır (MySQL'in
                                    <code>_ci</code> collation'larında duyarsız). Bellekte
                                    <code>TextGeneralModule::trToLower</code> ile katlanır; istemcideki
                                    liste araması da aynı eşlemeyi yapar — <code>toLowerCase()</code>
                                    Türkçe'de <code>I</code>'yı <code>i</code> yapıp "İZMİR" aramasını
                                    boşa çıkarıyordu.
                                </p>
                            </div>
                            <div class="col-md-6">
                                <h6 class="fw-bold mb-2">Aranan metindeki joker karakterler</h6>
                                <p class="fs-6 text-body mb-5">
                                    <code>%</code> ve <code>_</code> kaçışlanır: "50%" yazan kullanıcı
                                    yüzde işaretini arar, "her şey"i değil.
                                </p>

                                <h6 class="fw-bold mb-2">Tarihte saat</h6>
                                <p class="fs-6 text-body mb-5">
                                    Tarih koşulları gün bazında karşılaştırılır. <code>datetime</code>
                                    sütununda 5 Mart aranınca o günün <b>tamamı</b> eşleşir; aralığın
                                    üst sınırı da gün sonuna kadar kapsar.
                                </p>

                                <h6 class="fw-bold mb-2">Excel çıktısı</h6>
                                <p class="fs-6 text-body mb-5">
                                    Aktif filtrelerin tamamını uygular, görünen sayfayı değil.
                                    <code>op = export</code> isteği <code>filters</code> alanını
                                    aynen taşır.
                                </p>

                                <h6 class="fw-bold mb-2">Sayısal görünen metinler</h6>
                                <p class="fs-6 text-body mb-0">
                                    Tablonun yanıtları <code>returnSuccessDataOnly()</code> yerine
                                    kendi JSON yayıcısını kullanır: o, <code>JSON_NUMERIC_CHECK</code>
                                    ile "007" stok kodunu <code>7</code> yapıyordu ve istemci onu geri
                                    gönderdiğinde hiçbir satırla eşleşmiyordu. Hücre ne ise o gider.
                                </p>
                            </div>
                        </div>
                    </div>
                    <!--end::Davranış-->

                </div>
            </div>

            <div class="alert alert-secondary d-flex flex-column flex-sm-row p-5 mb-10">
                <i class="fa-solid fa-hourglass-half fs-2hx text-body-tertiary me-4 mb-5 mb-sm-0"></i>
                <div class="d-flex flex-column pe-0 pe-sm-10">
                    <h5 class="mb-1"><code>expert_plus</code> sırada</h5>
                    <span>Accordion (satır altı detay), pivot ve hücre içi düzenleme.</span>
                </div>
            </div>

        </div>
    </div>
</div>

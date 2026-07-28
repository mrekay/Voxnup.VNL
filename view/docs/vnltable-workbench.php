<div class="d-flex flex-column flex-column-fluid">
    <div id="kt_app_content" class="app-content flex-column-fluid">
        <div id="kt_app_content_container" class="app-container container-xxl">

            <div class="vnl-page-hero mb-12 mt-6">
                <div class="vnl-overline mb-3">// docs/vnltable-workbench</div>
                <h1 class="fs-2hx fw-bold mb-4 text-body-emphasis">VNL Table — Workbench</h1>
                <div class="vnl-lead">
                    <code>grid</code>'de veriyi <b>süzersin</b>; workbench'te <b>şekillendirirsin</b> —
                    hangi sütun nerede duracak, satırlar neye göre kümelenecek.
                    Öncesi için <a href="/docs/vnltable" class="text-primary fw-bold">VNL Table</a> ve
                    <a href="/docs/vnltable-grid" class="text-primary fw-bold">Grid</a>.
                </div>
            </div>

            <div class="alert alert-secondary d-flex flex-column flex-sm-row p-5 mb-10">
                <i class="fa-solid fa-hammer fs-2hx text-body-tertiary me-4 mb-5 mb-sm-0"></i>
                <div class="d-flex flex-column pe-0 pe-sm-10">
                    <h5 class="mb-1">Yapım hâlinde</h5>
                    <span>
                        <b>Sütun sabitleme</b> ve <b>satır gruplama</b> hazır. Sırada detay satırı
                        (accordion), seçim ve toplu işlem; sonra hücre içi düzenleme, sütun sürükleyip
                        yeniden sıralama, pivot.
                    </span>
                </div>
            </div>

            <!--begin::Canlı örnek-->
            <div class="card mb-10">
                <div class="card-body p-10">
                    <h3 class="fw-bold text-body-emphasis mb-4">1. Canlı Örnek</h3>
                    <p class="fs-5 text-body mb-6">
                        Üç şeyi birden deneyin. <b>Başlığın yazısını</b> üstteki kesik çizgili alana
                        sürükleyin — tablo o sütuna göre gruplanır; bir tane daha sürükleyin, iç içe
                        iki seviye olur. <b>Şehir</b>, <b>Durum</b> ve <b>Tutar</b> hücrelerine
                        <b>çift tıklayın</b> — yerinde düzenlenip kaydedilir. Tabloyu yatay
                        kaydırın — <b>No</b> ile <b>Ad Soyad</b> solda, <b>Tarih</b> sağda kalır.
                    </p>

                    <?= VnlTable::make('demoWorkbench')
                        ->url('app/vnltable/getList')
                        ->mode(VnlTable::WORKBENCH)
                        ->key('id')
                        ->columns([
                            ['name' => 'id',    'title' => 'No',       'type' => 'number', 'width' => '90px',
                             'pin' => 'left', 'filter' => false, 'groupable' => false],
                            ['name' => 'ad',    'title' => 'Ad Soyad', 'width' => '220px', 'editable' => true,
                             'pin' => 'left', 'filter' => ['values' => false], 'groupable' => false],
                            ['name' => 'sehir', 'title' => 'Şehir',    'width' => '260px', 'editable' => true],
                            ['name' => 'durum', 'title' => 'Durum',    'width' => '260px', 'filter' => 'set',
                             'editable' => true, 'editOptions' => ['Onaylandı', 'Beklemede', 'İptal']],
                            ['name' => 'tutar', 'title' => 'Tutar',    'type' => 'number', 'width' => '260px',
                             'agg' => 'sum', 'editable' => true],
                            ['name' => 'tarih', 'title' => 'Tarih',    'type' => 'date',   'width' => '160px',
                             'pin' => 'right', 'groupable' => false],
                        ])
                        ->options([
                            'pageSize' => 10,
                            'order'    => [['col' => 'id', 'dir' => 'asc']],
                            'height'   => '460px',
                            'striped'  => true,
                            'group'    => ['mode' => 'auto', 'limit' => 5000],
                        ])
                        ->render() ?>
                </div>
            </div>
            <!--end::Canlı örnek-->

            <div class="card mb-10">
                <div class="card-body p-10">

                    <!--begin::Sabitleme-->
                    <div class="mb-10">
                        <h3 class="fw-bold text-body-emphasis mb-4">2. Sütun Sabitleme</h3>
                        <p class="fs-5 text-body mb-4">
                            Sütun tanımına tek anahtar: <code>'pin' =&gt; 'left'</code> ya da
                            <code>'right'</code>. Bir sunum özelliğidir — <code>width</code> ve
                            <code>align</code> gibi <b>her modda</b> çalışır, workbench şartı yoktur.
                        </p>

                        <div class="vnl-code mb-5">
                            <pre class="mb-0" style="background: transparent; white-space: pre-wrap;"><code>['name' =&gt; 'id',    'title' =&gt; 'No',    'pin' =&gt; 'left',  'width' =&gt; '90px'],
['name' =&gt; 'ad',    'title' =&gt; 'Ad',    'pin' =&gt; 'left'],
['name' =&gt; 'islem', 'title' =&gt; 'İşlem', 'pin' =&gt; 'right', 'render' =&gt; 'html'],</code></pre>
                        </div>

                        <div class="table-responsive border rounded bg-body-tertiary mb-0">
                            <table class="table align-middle table-row-dashed fs-6 gy-4 mb-0">
                                <thead>
                                <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0 border-bottom">
                                    <th class="ps-5 min-w-200px">Davranış</th>
                                    <th class="min-w-350px">Ayrıntı</th>
                                </tr>
                                </thead>
                                <tbody class="text-body fw-semibold">
                                <tr>
                                    <td class="ps-5">Kenara uzaklık</td>
                                    <td>Sütun genişlikleri içerikle değiştiği için her çizimden sonra ölçülür; pencere boyu değişince de yenilenir. Sütun tanımına <code>width</code> vermek yerleşimi kararlı tutar.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5">Sıra</td>
                                    <td>Sabitleme sütunun yerini <b>değiştirmez</b>. Sola sabitlenecek sütunları soldaki, sağa sabitlenecekleri sağdaki uçta tanımlayın.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5">Gizleme ile birlikte</td>
                                    <td>Sabit bir sütun menüden gizlenirse kalanların uzaklığı yeniden hesaplanır; dikiş gölgesi de yeni kenar sütununa geçer.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5">Şerit ve hover</td>
                                    <td>Sabit hücre saydam olamaz, altından veri geçerdi. Şerit ve hover renkleri opak zeminin üstüne ayrı bir katman olarak bindirilir.</td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <!--end::Sabitleme-->

                    <div class="separator separator-dashed my-8"></div>

                    <!--begin::Gruplama-->
                    <div class="mb-10">
                        <h3 class="fw-bold text-body-emphasis mb-4">3. Satır Gruplama</h3>

                        <div class="vnl-code mb-5">
                            <pre class="mb-0" style="background: transparent; white-space: pre-wrap;"><code>-&gt;options(['group' =&gt; [
    'by'    =&gt; 'sehir',   // başlangıç sütunu; '' ise gruplama kapalı başlar
    'mode'  =&gt; 'auto',    // auto | client | server
    'limit' =&gt; 5000,      // auto/client eşiği
    'menu'  =&gt; true,      // araç çubuğunda gruplama düğmesi
]])

// kısa yazım
-&gt;options(['group' =&gt; 'sehir'])   // ya da sadece true</code></pre>
                        </div>

                        <h6 class="fw-bold mb-3">Grup listesi her zaman sunucudan</h6>
                        <p class="fs-6 text-body mb-4">
                            Sunucu sayfalı veri döndürdüğü için istemcide gruplamak işe yaramaz:
                            25 satırlık bir sayfayı gruplarsanız gruplar sayfa sınırında kopar.
                            Bu yüzden <b>grup listesi</b> her zaman <code>op = group</code> ile gelir;
                            alt toplamlar da orada hesaplanır. <code>mode</code> yalnız
                            <b>satırların</b> nasıl çekileceğini belirler.
                        </p>

                        <div class="table-responsive border rounded bg-body-tertiary mb-5">
                            <table class="table align-middle table-row-dashed fs-6 gy-4 mb-0">
                                <thead>
                                <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0 border-bottom">
                                    <th class="ps-5 min-w-100px">mode</th>
                                    <th class="min-w-350px">Satırlar nereden gelir</th>
                                </tr>
                                </thead>
                                <tbody class="text-body fw-semibold">
                                <tr>
                                    <td class="ps-5"><code>client</code></td>
                                    <td>Tek istekte hepsi iner (<code>size = limit</code>) ve gruplara dağıtılır. Grup açmak anında olur, ek istek yok. Büyük tabloda kullanılamaz.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>server</code></td>
                                    <td>Grup açıldıkça o gruba ait <code>op = list</code> isteği atılır. Grup anahtarı <b>sıradan bir filtre</b> olarak gider, yani sunucu tarafında ek bir yol tutmanız gerekmez. Grup <code>size</code>'dan uzunsa altında “Daha fazla yükle” çıkar.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>auto</code></td>
                                    <td><b>Varsayılan.</b> Grup sayılarının toplamı <code>limit</code>'in altındaysa client, üstündeyse server. Karar grup listesi geldikten sonra verilir; fazladan istek maliyeti yoktur.</td>
                                </tr>
                                </tbody>
                            </table>
                        </div>

                        <h6 class="fw-bold mb-3"><code>getList</code> tarafı</h6>
                        <div class="vnl-code mb-5">
                            <pre class="mb-0" style="background: transparent; white-space: pre-wrap;"><code>if ($t-&gt;op === VnlTableRequest::OP_GROUP) {
    // Bellekteki veriyle:
    $t-&gt;groupFrom($filtrelenmisSatirlar, ['tutar' =&gt; 'sum']);

    // SQL ile:
    $q = Kayit::query()-&gt;selectRaw("$t-&gt;groupBy as k, count(*) as n, sum(tutar) as toplam");
    $t-&gt;applyFilters($q, $alanlar);

    $t-&gt;respondGroups($q-&gt;groupBy($t-&gt;groupBy)-&gt;get()-&gt;map(fn($r) =&gt; [
        'k' =&gt; $r-&gt;k, 'n' =&gt; $r-&gt;n, 'totals' =&gt; ['tutar' =&gt; $r-&gt;toplam],
    ])-&gt;all());
}</code></pre>
                        </div>

                        <div class="row g-5">
                            <div class="col-md-6">
                                <h6 class="fw-bold mb-3"><span class="badge badge-soft-primary me-2">group</span> istek</h6>
                                <div class="vnl-code">
                                    <pre class="mb-0" style="background: transparent; white-space: pre-wrap;"><code>{
  "op": "group",
  "by": "sehir",
  "filters": { ...hepsi... },
  "context": {}
}</code></pre>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <h6 class="fw-bold mb-3"><span class="badge badge-soft-success me-2">yanıt</span></h6>
                                <div class="vnl-code">
                                    <pre class="mb-0" style="background: transparent; white-space: pre-wrap;"><code>{
  "by": "sehir",
  "groups": [
    {"k":"Ankara", "n":43,
     "totals":{"tutar":1090182}},
    {"k":"İzmir",  "n":43,
     "totals":{"tutar":1107179}}
  ]
}</code></pre>
                                </div>
                            </div>
                        </div>

                        <div class="alert alert-info d-flex align-items-start p-4 mt-6 mb-0">
                            <i class="fa-solid fa-circle-info fs-2 text-info me-3"></i>
                            <span class="text-body fs-6">
                                <b>Değer listesinden farkı:</b> <code>op = distinct</code> hedef sütunun
                                kendi filtresini <b>dışarıda bırakır</b> (kullanıcı kapattığı değeri geri
                                açabilsin diye). <code>op = group</code> ise <b>tüm filtreleri</b>
                                uygular — gruplar, kullanıcının süzdüğü kümenin özetidir.
                                Gruplu görünümde sayfalanan birim de satır değil <b>gruptur</b>.
                            </span>
                        </div>
                    </div>
                    <!--end::Gruplama-->

                </div>
            </div>

        </div>
    </div>
</div>

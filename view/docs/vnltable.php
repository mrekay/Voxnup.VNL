<div class="d-flex flex-column flex-column-fluid">
    <div id="kt_app_content" class="app-content flex-column-fluid">
        <div id="kt_app_content_container" class="app-container container-xxl">

            <div class="vnl-page-hero mb-12 mt-6">
                <div class="vnl-overline mb-3">// docs/vnltable</div>
                <h1 class="fs-2hx fw-bold mb-4 text-body-emphasis">VNL Table</h1>
                <div class="vnl-lead">
                    Sunucu tarafında tanımlanan, istemcide çizilen tablo sistemi. Tanım <code>VnlTable</code> ile
                    kurulur, veri <code>getList</code> action'ından gelir. Üç model vardır:
                    <span class="badge badge-soft-primary">basic</span>
                    <span class="badge badge-soft-secondary">expert</span>
                    <span class="badge badge-soft-secondary">expert_plus</span>
                </div>
            </div>

            <div class="alert alert-info d-flex flex-column flex-sm-row p-5 mb-10">
                <i class="fa-solid fa-circle-info fs-2hx text-info me-4 mb-5 mb-sm-0"></i>
                <div class="d-flex flex-column pe-0 pe-sm-10">
                    <h5 class="mb-1">Şu an yalnızca <code>basic</code> hazır</h5>
                    <span>
                        Sıralama, sayfalama, düz arama, filtre temizleme ve Excel çıktısı çalışır.
                        <code>expert</code> (sütun filtre panelleri, Excel tarzı değer seçimi) ve
                        <code>expert_plus</code> (accordion, pivot, hücre düzenleme) sırada.
                    </span>
                </div>
            </div>

            <!--begin::Canlı örnek-->
            <div class="card mb-10">
                <div class="card-body p-10">
                    <h3 class="fw-bold text-body-emphasis mb-4">1. Canlı Örnek</h3>
                    <p class="fs-5 text-body mb-6">
                        213 kayıt. Başlığa tıklayarak sıralayın, arama kutusuna <code>Ankara</code> yazın,
                        Excel çıktısını indirin. Veri <code>actions/action_vnltable.php</code> içinde bellekte
                        üretilir; veritabanı gerektirmez.
                    </p>

                    <?= VnlTable::make('demo')
                        ->url('app/vnltable/getList')
                        ->key('id')
                        ->columns([
                            ['name' => 'id',    'title' => 'No',       'type' => 'number', 'width' => '80px'],
                            ['name' => 'ad',    'title' => 'Ad Soyad'],
                            ['name' => 'sehir', 'title' => 'Şehir'],
                            ['name' => 'durum', 'title' => 'Durum'],
                            ['name' => 'tutar', 'title' => 'Tutar',    'type' => 'number'],
                            ['name' => 'tarih', 'title' => 'Tarih',    'type' => 'date'],
                        ])
                        ->options([
                            'pageSize' => 10,
                            'order'    => [['col' => 'id', 'dir' => 'asc']],
                            'height'   => '420px',
                            'skin'     => 'default',
                            'striped'  => true,
                        ])
                        ->render() ?>
                </div>
            </div>
            <!--end::Canlı örnek-->

            <!--begin::Skinler-->
            <div class="card mb-10">
                <div class="card-body p-10">
                    <h3 class="fw-bold text-body-emphasis mb-4">2. Skinler ve Şerit</h3>
                    <p class="fs-5 text-body mb-6">
                        <code>skin</code> tablonun görünüm katmanıdır. <code>striped</code> ise ondan
                        bağımsız bir anahtardır: hangi skin seçilirse seçilsin satırları dönüşümlü
                        renklendirir, rengi de o skin'den alır.
                    </p>

                    <div class="vnl-code mb-6">
                        <pre class="mb-0" style="background: transparent; white-space: pre-wrap;"><code>-&gt;options([
    'skin'    =&gt; 'modern',   // default | modern | compact
    'striped' =&gt; true,       // skin'den bağımsız
])</code></pre>
                    </div>

                    <h6 class="fw-bold mb-3"><span class="badge badge-soft-primary me-2">modern</span> şeritli</h6>
                    <div class="mb-8">
                        <?= VnlTable::make('demoModern')
                            ->url('app/vnltable/getList')
                            ->key('id')
                            ->columns([
                                ['name' => 'ad',    'title' => 'Ad Soyad'],
                                ['name' => 'sehir', 'title' => 'Şehir'],
                                ['name' => 'durum', 'title' => 'Durum'],
                                ['name' => 'tutar', 'title' => 'Tutar', 'type' => 'number'],
                            ])
                            ->options([
                                'pageSize' => 5, 'pageSizes' => [5, 10, 25],
                                'skin' => 'modern', 'striped' => true,
                            ])
                            ->render() ?>
                    </div>

                    <h6 class="fw-bold mb-3"><span class="badge badge-soft-secondary me-2">compact</span> şeritsiz</h6>
                    <?= VnlTable::make('demoCompact')
                        ->url('app/vnltable/getList')
                        ->key('id')
                        ->columns([
                            ['name' => 'id',    'title' => 'No',    'type' => 'number', 'width' => '70px'],
                            ['name' => 'ad',    'title' => 'Ad Soyad'],
                            ['name' => 'sehir', 'title' => 'Şehir'],
                            ['name' => 'durum', 'title' => 'Durum'],
                            ['name' => 'tutar', 'title' => 'Tutar', 'type' => 'number'],
                        ])
                        ->options([
                            'pageSize' => 8, 'pageSizes' => [8, 20, 50],
                            'skin' => 'compact',
                            'search' => false, 'excel' => false, 'clear' => false,
                        ])
                        ->render() ?>
                </div>
            </div>
            <!--end::Skinler-->

            <div class="card mb-10">
                <div class="card-body p-10">

                    <!--begin::Tanım-->
                    <div class="mb-10">
                        <h3 class="fw-bold text-body-emphasis mb-4">3. Tablo Tanımı</h3>
                        <p class="fs-5 text-body mb-4">
                            <code>VnlTable</code> zincirleme kurulur ve <code>render()</code> ile basılır.
                            HTML tablo değil, bir bağlama noktası ve JSON tanım üretir; çizimi
                            <code>vnltable.js</code> yapar.
                        </p>

                        <div class="vnl-code mb-5">
                            <pre class="mb-0" style="background: transparent; white-space: pre-wrap;"><code>echo VnlTable::make('kayitlar')      // sayfada benzersiz HTML kimliği
    -&gt;url('app/kayit/getList')       // veriyi veren action
    -&gt;key('id')                      // satır kimliği
    -&gt;columns([
        ['name' =&gt; 'id',    'title' =&gt; 'No',    'type' =&gt; 'number', 'width' =&gt; '80px'],
        ['name' =&gt; 'ad',    'title' =&gt; 'Ad'],
        ['name' =&gt; 'tutar', 'title' =&gt; 'Tutar', 'type' =&gt; 'number'],
        ['name' =&gt; 'tarih', 'title' =&gt; 'Tarih', 'type' =&gt; 'date'],
    ])
    -&gt;options([
        'pageSize' =&gt; 25,
        'order'    =&gt; [['col' =&gt; 'id', 'dir' =&gt; 'desc']],
    ])
    -&gt;render();</code></pre>
                        </div>

                        <div class="alert alert-warning d-flex align-items-start p-4">
                            <i class="fa-solid fa-triangle-exclamation fs-2 text-warning me-3"></i>
                            <span class="text-body fs-6">
                                <b>SDTable'dan gelenler için:</b> filtre artık ayrı bir dizi değil, sütunun bir
                                özelliği. SDTable'da <code>$columns</code> ve <code>$filters</code> konum konum
                                hizalanmak zorundaydı; araya sütun eklendiğinde sonraki tüm filtreler sessizce
                                kayıyordu.
                            </span>
                        </div>
                    </div>
                    <!--end::Tanım-->

                    <div class="separator separator-dashed my-8"></div>

                    <!--begin::Sütun-->
                    <div class="mb-10">
                        <h3 class="fw-bold text-body-emphasis mb-4">4. Sütun Seçenekleri</h3>

                        <div class="table-responsive border rounded bg-body-tertiary mb-5">
                            <table class="table align-middle table-row-dashed fs-6 gy-4 mb-0">
                                <thead>
                                <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0 border-bottom">
                                    <th class="ps-5 min-w-125px">Anahtar</th>
                                    <th class="min-w-125px">Varsayılan</th>
                                    <th class="min-w-350px">Açıklama</th>
                                </tr>
                                </thead>
                                <tbody class="text-body fw-semibold">
                                <tr>
                                    <td class="ps-5"><code>name</code></td>
                                    <td><span class="badge badge-soft-danger">Zorunlu</span></td>
                                    <td>Satır verisindeki anahtar. <code>getList</code> bu adla değer döndürmelidir.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>title</code></td>
                                    <td><code>name</code></td>
                                    <td>Başlıkta görünen metin.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>type</code></td>
                                    <td><code>text</code></td>
                                    <td><code>text</code> · <code>number</code> · <code>date</code> · <code>datetime</code> · <code>bool</code>. Hizalamayı belirler.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>render</code></td>
                                    <td><code>text</code></td>
                                    <td><code>html</code> verilirse değer HTML olarak basılır. <b>Kaçışından sunucu sorumludur.</b></td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>sortable</code></td>
                                    <td><code>true</code></td>
                                    <td>Başlığa tıklayarak sıralanabilir mi.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>visible</code></td>
                                    <td><code>true</code></td>
                                    <td><code>false</code> verilirse sütun tanımdan tamamen çıkar — istemciye hiç gitmez, menüde de görünmez.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>hideable</code></td>
                                    <td><code>true</code></td>
                                    <td>Kullanıcı sütunu araç çubuğundaki menüden gizleyebilir mi. Kimlik/işlem sütunları için <code>false</code> verin.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>width</code> / <code>align</code></td>
                                    <td>—</td>
                                    <td>Genişlik (<code>'80px'</code>) ve hizalama; hizalama verilmezse <code>type</code>'tan türetilir.</td>
                                </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="alert alert-danger d-flex align-items-start p-4">
                            <i class="fa-solid fa-shield fs-2 text-danger me-3"></i>
                            <span class="text-body fs-6">
                                Hücreler <b>ham değer</b> taşır. SDTable her hücreye HTML gönderiyordu; sıralama,
                                pivot ve Excel çıktısı ham değer olmadan doğru çalışamaz. Gerçekten markup gereken
                                sütunu <code>'render' =&gt; 'html'</code> ile açıkça işaretleyin.
                            </span>
                        </div>
                    </div>
                    <!--end::Sütun-->

                    <div class="separator separator-dashed my-8"></div>

                    <!--begin::getList-->
                    <div class="mb-10">
                        <h3 class="fw-bold text-body-emphasis mb-4">5. <code>getList</code> Sözleşmesi</h3>
                        <p class="fs-5 text-body mb-4">
                            <code>getList</code> adı VNL 2.x'ten korunur, sözleşme tamamen değişti.
                            <code>VnlTableRequest</code> gelen isteği çözer ve yanıtı biçimlendirir.
                            Tek action üç işi görür; hangisi olduğunu <code>op</code> alanı söyler.
                        </p>

                        <div class="vnl-code mb-5">
                            <pre class="mb-0" style="background: transparent; white-space: pre-wrap;"><code>public function getList($req)
{
    Auth::CheckLogin();

    $t = VnlTableRequest::from($req);

    // $t-&gt;op       list | distinct | export
    // $t-&gt;page     $t-&gt;size     $t-&gt;offset()
    // $t-&gt;sort     [['col' =&gt; 'ad', 'dir' =&gt; 'asc']]
    // $t-&gt;search   düz arama metni
    // $t-&gt;context  tanımdaki context() ile gönderilen sabitler

    $extras = $t-&gt;extras('created_at', 'desc');   // TableModel için hazır dizi
    $rows   = Kayit::SelectList($t-&gt;modelFilters(), $extras)-&gt;get();

    if ($t-&gt;op === VnlTableRequest::OP_EXPORT) {
        $t-&gt;exportCsv($tumSatirlar, ['id' =&gt; 'No', 'ad' =&gt; 'Ad'], 'kayit');
    }

    $t-&gt;respond(
        $rows-&gt;map(fn($r) =&gt; ['id' =&gt; $r-&gt;id, 'ad' =&gt; $r-&gt;ad])-&gt;all(),
        Kayit::SelectList(null, null)-&gt;count(),      // toplam
        Kayit::SelectList($t-&gt;modelFilters(), null)-&gt;count()  // filtrelenmiş
    );
}</code></pre>
                        </div>

                        <div class="row g-5">
                            <div class="col-md-6">
                                <h6 class="fw-bold mb-3"><span class="badge badge-soft-primary me-2">İstek</span></h6>
                                <div class="vnl-code">
                                    <pre class="mb-0" style="background: transparent; white-space: pre-wrap;"><code>{
  "op": "list",
  "page": 1,
  "size": 25,
  "search": "ankara",
  "sort": [{"col":"tutar","dir":"desc"}],
  "filters": {},
  "context": {}
}</code></pre>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <h6 class="fw-bold mb-3"><span class="badge badge-soft-success me-2">Yanıt</span></h6>
                                <div class="vnl-code">
                                    <pre class="mb-0" style="background: transparent; white-space: pre-wrap;"><code>{
  "rows": [
    {"id":1, "ad":"Örnek", "tutar":1500}
  ],
  "total": 213,
  "filtered": 42,
  "page": 1,
  "size": 25
}</code></pre>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!--end::getList-->

                    <div class="separator separator-dashed my-8"></div>

                    <!--begin::Seçenekler-->
                    <div class="mb-10">
                        <h3 class="fw-bold text-body-emphasis mb-4">6. Tablo Seçenekleri</h3>

                        <div class="table-responsive border rounded bg-body-tertiary mb-5">
                            <table class="table align-middle table-row-dashed fs-6 gy-4 mb-0">
                                <thead>
                                <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0 border-bottom">
                                    <th class="ps-5 min-w-150px">Seçenek</th>
                                    <th class="min-w-125px">Varsayılan</th>
                                    <th class="min-w-300px">Açıklama</th>
                                </tr>
                                </thead>
                                <tbody class="text-body fw-semibold">
                                <tr><td class="ps-5"><code>pageSize</code></td><td><code>25</code></td><td>Başlangıç sayfa boyu.</td></tr>
                                <tr><td class="ps-5"><code>pageSizes</code></td><td><code>[10,25,50,100]</code></td><td>Sayfa boyu seçenekleri. Sunucu 500'ü tavan kabul eder.</td></tr>
                                <tr><td class="ps-5"><code>order</code></td><td><code>[]</code></td><td>Başlangıç sıralaması: <code>[['col'=&gt;'id','dir'=&gt;'desc']]</code></td></tr>
                                <tr><td class="ps-5"><code>search</code></td><td><code>true</code></td><td>Düz arama kutusu.</td></tr>
                                <tr><td class="ps-5"><code>excel</code></td><td><code>true</code></td><td>Excel çıktı düğmesi. Aktif aramayı uygular, görünen sayfayı değil.</td></tr>
                                <tr><td class="ps-5"><code>clear</code></td><td><code>true</code></td><td>Filtreleri temizle düğmesi.</td></tr>
                                <tr><td class="ps-5"><code>columns</code></td><td><code>true</code></td><td>Sütun göster/gizle menüsü. Tercih tarayıcıda tablo kimliğiyle kalıcıdır; sütun tanımında <code>'hideable' =&gt; false</code> verilen sütun menüde listelenmez. Excel çıktısı gizlemeden etkilenmez.</td></tr>
                                <tr><td class="ps-5"><code>height</code></td><td>—</td><td>Gövde yüksekliği (<code>'420px'</code>); başlık yapışkan kalır.</td></tr>
                                <tr><td class="ps-5"><code>skin</code></td><td><code>'default'</code></td><td><code>default</code> · <code>modern</code> · <code>compact</code>. Kendi skin'inizi <code>.vnl-skin-adiniz</code> sınıfıyla CSS'te tanımlayabilirsiniz.</td></tr>
                                <tr><td class="ps-5"><code>striped</code></td><td><code>false</code></td><td>Satırları dönüşümlü renklendirir. Skin'den bağımsızdır; rengi seçili skin sağlar.</td></tr>
                                </tbody>
                            </table>
                        </div>

                        <p class="fs-6 text-body mb-3">JavaScript tarafından erişim:</p>
                        <div class="vnl-code">
                            <pre class="mb-0" style="background: transparent; white-space: pre-wrap;"><code>vnlTable('kayitlar').reload();   // veriyi yeniden çek
vnlTable.init(kapsam);           // AJAX ile eklenen tabloları başlat</code></pre>
                        </div>
                    </div>
                    <!--end::Seçenekler-->

                </div>
            </div>

        </div>
    </div>
</div>

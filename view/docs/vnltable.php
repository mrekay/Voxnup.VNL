<div class="d-flex flex-column flex-column-fluid">
    <div id="kt_app_content" class="app-content flex-column-fluid">
        <div id="kt_app_content_container" class="app-container container-xxl">

            <div class="vnl-page-hero mb-12 mt-6">
                <div class="vnl-overline mb-3">// docs/vnltable</div>
                <h1 class="fs-2hx fw-bold mb-4 text-body-emphasis">VNL Table</h1>
                <div class="vnl-lead">
                    Sunucu tarafında tanımlanan, istemcide çizilen tablo sistemi. Tanım <code>VnlTable</code> ile
                    kurulur, veri <code>getList</code> action'ından gelir. Üç model vardır:
                    <span class="badge badge-soft-primary">list</span>
                    <span class="badge badge-soft-primary">grid</span>
                    <span class="badge badge-soft-secondary">workbench</span>
                </div>
            </div>

            <div class="alert alert-info d-flex flex-column flex-sm-row p-5 mb-10">
                <i class="fa-solid fa-circle-info fs-2hx text-info me-4 mb-5 mb-sm-0"></i>
                <div class="d-flex flex-column pe-0 pe-sm-10">
                    <h5 class="mb-1">Bu sayfa <code>list</code>'i anlatır</h5>
                    <span>
                        Sıralama, sayfalama, düz arama, filtre temizleme ve Excel çıktısı. Sütun filtre
                        panelleri (başlıktaki huni, Excel tarzı değer seçimi) <code>grid</code> ile
                        gelir ve <a href="/docs/vnltable-grid" class="fw-bold">VNL Table — Grid</a>
                        sayfasında anlatılır. <code>workbench</code> (sütun sabitleme, gruplama,
                        hücre düzenleme, pivot) sırada.
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
                                    <td><code>html</code> verilirse değer HTML olarak basılır. <b>Kaçışından sunucu sorumludur.</b> Biçimlendirici geldiğinden beri son çaredir — bkz. §5.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>format</code></td>
                                    <td>—</td>
                                    <td>Hücre biçimlendirici: <code>badge</code> · <code>dot</code> · <code>icon</code> · <code>bool</code> · <code>link</code>. Değer ham kalır, çizimi istemci yapar — bkz. <a href="#" onclick="document.querySelectorAll('h3')[4].scrollIntoView();return false">§5</a>.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>formatter</code></td>
                                    <td>—</td>
                                    <td>Sayfanın <code>vnlTable.formatter()</code> ile kaydettiği biçimlendiricinin adı. Sözlüğün yetmediği hâller için.</td>
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
                                pivot ve Excel çıktısı ham değer olmadan doğru çalışamaz. Rozet, durum noktası,
                                evet/hayır ya da bağlantı için <b>sunucunun HTML üretmesine gerek yok</b>:
                                <code>'format'</code> ile niyeti bildirin, değer ham kalsın (§5). Hiçbir biçime
                                sığmayan sütunu <code>'render' =&gt; 'html'</code> ile açıkça işaretleyin.
                            </span>
                        </div>
                    </div>
                    <!--end::Sütun-->

                    <div class="separator separator-dashed my-8"></div>

                    <!--begin::Biçimlendirici-->
                    <div class="mb-10">
                        <h3 class="fw-bold text-body-emphasis mb-4">5. Hücre Biçimlendiricileri</h3>

                        <p class="fs-5 text-body mb-4">
                            Hücre ham değer taşır — bu kuralın bedeli VNL 3.1'e kadar şuydu: bir hücrede
                            rozet göstermek isteyen sunucu, <b>değerin yerine HTML göndermek</b> zorundaydı
                            (<code>render =&gt; 'html'</code>). Veri biçime feda oluyor, kaçış sorumluluğu da
                            her eylem dosyasına dağılıyordu.
                        </p>
                        <p class="fs-5 text-body mb-6">
                            <code>format</code> dengeyi tersine çevirir: <b>değer ham kalır</b>, sunucu yalnız
                            niyeti bildirir, çizimi <code>vnltable.js</code> yapar. Üretilen düğümlerin hiçbiri
                            <code>innerHTML</code> kullanmaz ve renk adları sunucuda beyaz listeye karşı
                            doğrulanır — bu yolda XSS yüzeyi yoktur.
                        </p>

                        <?= VnlTable::make('demoFormat')
                            ->url('app/vnltable/getList')
                            ->key('id')
                            ->columns([
                                ['name' => 'id', 'title' => 'No', 'type' => 'number', 'width' => '80px',
                                 'format' => ['kind' => 'link', 'href' => '/docs/vnltable?kayit={id}']],
                                ['name' => 'ad', 'title' => 'Ad Soyad'],
                                ['name' => 'sehir', 'title' => 'Şehir',
                                 'format' => ['kind' => 'dot', 'map' => [
                                     'İstanbul' => 'primary', 'Ankara' => 'info', 'İzmir' => 'success',
                                     'Bursa' => 'warning', 'Antalya' => 'danger',
                                 ]]],
                                ['name' => 'durum', 'title' => 'Durum',
                                 'format' => ['kind' => 'badge', 'map' => [
                                     'Onaylandı' => 'success', 'Beklemede' => 'warning', 'İptal' => 'danger',
                                 ]]],
                                ['name' => 'aktif', 'title' => 'Aktif', 'type' => 'bool',
                                 'format' => ['kind' => 'bool', 'onLabel' => 'Evet', 'offLabel' => 'Hayır']],
                                ['name' => 'tutar', 'title' => 'Tutar', 'type' => 'number',
                                 'formatter' => 'paraTr'],
                            ])
                            ->options([
                                'pageSize' => 8, 'pageSizes' => [8, 25, 50],
                                'order' => [['col' => 'id', 'dir' => 'asc']],
                                'striped' => true,
                            ])
                            ->render() ?>

                        <script>
                            /* Bildirimsel sözlüğün yetmediği yer: sayfanın kendi
                               biçimlendiricisi. Metin ya da DOM düğümü döndürür. */
                            vnlTable.formatter('paraTr', function (value) {
                                var n = Number(value);

                                return isNaN(n) ? value : n.toLocaleString('tr-TR') + ' ₺';
                            });
                        </script>

                        <p class="text-body-secondary mt-6 mb-5">
                            Yukarıdaki tabloda <b>sunucu tek bir HTML etiketi üretmiyor</b>. Sütuna göre
                            sıralayın, Excel çıktısını indirin: sıralama ve dosya <code>1</code>/<code>0</code>
                            ve <code>5279</code> gibi ham değerleri görmeye devam ediyor.
                        </p>

                        <h5 class="fw-bold text-body-emphasis mb-3">Bildirimsel biçimler</h5>
                        <div class="table-responsive border rounded bg-body-tertiary mb-6">
                            <table class="table align-middle table-row-dashed fs-6 gy-4 mb-0">
                                <thead>
                                <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0 border-bottom">
                                    <th class="ps-5 min-w-100px">kind</th>
                                    <th class="min-w-125px">Görünüm</th>
                                    <th class="min-w-350px">Ek anahtarlar</th>
                                </tr>
                                </thead>
                                <tbody class="text-body fw-semibold">
                                <tr>
                                    <td class="ps-5"><code>badge</code></td>
                                    <td><span class="badge badge-soft-success">Onaylandı</span></td>
                                    <td><code>soft</code> — <code>false</code> verilirse dolu rozet (<code>bg-*</code>).</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>dot</code></td>
                                    <td><span class="vnl-cell-dot-wrap"><span class="vnl-cell-dot text-primary"></span><span>İstanbul</span></span></td>
                                    <td>Renkli nokta + metin. Durum göstergesi için rozetten sessiz bir seçenek.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>icon</code></td>
                                    <td><span class="vnl-cell-icon text-warning"><i class="bi bi-lock"></i></span></td>
                                    <td><code>icons</code> (değere göre) ya da <code>icon</code> (sabit). Boşluklu sınıf olduğu gibi basılır, tek kelime FA kısa adı sayılır.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>bool</code></td>
                                    <td><span class="vnl-cell-bool text-success"><i class="bi bi-check-lg"></i><span>Evet</span></span></td>
                                    <td><code>onLabel</code> · <code>offLabel</code> · <code>onVariant</code> · <code>offVariant</code>. <code>0</code>, <code>''</code>, <code>false</code>, <code>'hayır'</code> yanlış sayılır.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>link</code></td>
                                    <td><a href="#" class="vnl-cell-link" onclick="return false">42</a></td>
                                    <td><code>href</code> zorunlu; <code>{sutun}</code> yer tutucuları <b>satırın</b> değerleriyle dolar ve URL-kodlanır. <code>target</code> verilirse <code>rel="noopener"</code> eklenir.</td>
                                </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="table-responsive border rounded bg-body-tertiary mb-6">
                            <table class="table align-middle table-row-dashed fs-6 gy-4 mb-0">
                                <thead>
                                <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0 border-bottom">
                                    <th class="ps-5 min-w-125px">Ortak anahtar</th>
                                    <th class="min-w-350px">Açıklama</th>
                                </tr>
                                </thead>
                                <tbody class="text-body fw-semibold">
                                <tr>
                                    <td class="ps-5"><code>map</code></td>
                                    <td>Değer → renk. Renkler beyaz listedir: <code>primary</code> <code>secondary</code> <code>success</code> <code>danger</code> <code>warning</code> <code>info</code> <code>light</code> <code>dark</code>. Yazım hatası tanımda hata verir, ekranda renksiz bir rozete dönüşmez.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>labels</code></td>
                                    <td>Değer → gösterilecek metin. Verilmezse değerin kendisi basılır.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>default</code></td>
                                    <td>Eşlemede karşılığı olmayan değerin rengi. Varsayılan <code>secondary</code>.</td>
                                </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="vnl-code mb-6">
                            <pre class="mb-0" style="background: transparent; white-space: pre-wrap;"><code>-&gt;columns([
    // Kısa yazım: değeri nötr renkli rozet olarak bas
    ['name' =&gt; 'etiket', 'title' =&gt; 'Etiket', 'format' =&gt; 'badge'],

    // Değere göre renk ve metin
    ['name' =&gt; 'durum', 'title' =&gt; 'Durum', 'format' =&gt; [
        'kind'    =&gt; 'badge',
        'map'     =&gt; ['1' =&gt; 'success', '2' =&gt; 'warning', '3' =&gt; 'danger'],
        'labels'  =&gt; ['1' =&gt; 'Onaylandı', '2' =&gt; 'Beklemede', '3' =&gt; 'İptal'],
        'default' =&gt; 'secondary',
    ]],

    // type:'bool' artık hücreyi de biçimlendirebiliyor
    ['name' =&gt; 'aktif', 'title' =&gt; 'Aktif', 'type' =&gt; 'bool',
     'format' =&gt; ['kind' =&gt; 'bool', 'onLabel' =&gt; 'Evet', 'offLabel' =&gt; 'Hayır']],

    // Satırdan adres kuran bağlantı
    ['name' =&gt; 'kod', 'title' =&gt; 'Kod',
     'format' =&gt; ['kind' =&gt; 'link', 'href' =&gt; '/kayit/{id}', 'target' =&gt; '_blank']],
])</code></pre>
                        </div>

                        <h5 class="fw-bold text-body-emphasis mb-3">Adlandırılmış biçimlendirici</h5>
                        <p class="text-body mb-4">
                            Sözlük yetmediğinde sütun bir <b>ada</b> işaret eder; adı sayfa kaydeder.
                            Fonksiyon <b>metin</b> ya da <b>DOM düğümü</b> döndürür — <b>HTML metni
                            döndüremez</b>. Bu kasıtlıdır: markup üretecek biçimlendirici düğümü kendisi
                            kursun, kaçış sorusu hiç doğmasın.
                        </p>
                        <div class="vnl-code mb-5">
                            <pre class="mb-0" style="background: transparent; white-space: pre-wrap;"><code>// Sunucu: sütun yalnız adı söyler
['name' =&gt; 'tutar', 'title' =&gt; 'Tutar', 'type' =&gt; 'number', 'formatter' =&gt; 'paraTr']

// Sayfa: adı kaydeder (tablo kurulmadan önce)
&lt;script&gt;
vnlTable.formatter('paraTr', function (value, row, col) {
    return Number(value).toLocaleString('tr-TR') + ' ₺';        // metin
});

// Satır işlemi düğmesi: onclick dizesi yerine gerçek dinleyici
vnlTable.formatter('sil', function (value, row) {
    var b = document.createElement('button');
    b.className = 'btn btn-sm btn-soft-danger';
    b.textContent = 'Sil';
    b.addEventListener('click', function () {
        deleteItemFromDB({ url: '/app/kayit/sil', data: { id: row.id }, table: 'kayitlar' });
    });

    return b;
});
&lt;/script&gt;</code></pre>
                        </div>

                        <div class="alert alert-primary d-flex align-items-start p-4 mb-5">
                            <i class="fa-solid fa-circle-info fs-2 text-primary me-3"></i>
                            <span class="text-body fs-6">
                                Biçimlendirici <b>yalnız ekrandır</b>. Satırdaki değere, sıralamaya, filtreye,
                                gruplamaya, pivota ve Excel çıktısına dokunmaz — Excel'de <code>Evet</code>
                                değil <code>1</code> görürsünüz. Çıktıda da etiket isteniyorsa dönüşüm
                                <code>exportXlsx()</code>'e verilen satırlarda yapılır.
                            </span>
                        </div>

                        <div class="alert alert-warning d-flex align-items-start p-4">
                            <i class="fa-solid fa-triangle-exclamation fs-2 text-warning me-3"></i>
                            <span class="text-body fs-6">
                                <code>format</code>, <code>formatter</code> ve <code>render =&gt; 'html'</code>
                                aynı hücreyi yazar; ikisini birden vermek tanım hatasıdır ve sayfa yüklenirken
                                hata verir. <code>render =&gt; 'html'</code> yerinde duruyor, sözleşmesi
                                değişmedi — ama artık son çaredir.
                            </span>
                        </div>
                    </div>
                    <!--end::Biçimlendirici-->

                    <div class="separator separator-dashed my-8"></div>

                    <!--begin::getList-->
                    <div class="mb-10">
                        <h3 class="fw-bold text-body-emphasis mb-4">6. <code>getList</code> Sözleşmesi</h3>
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
        $t-&gt;exportXlsx($tumSatirlar, ['id' =&gt; 'No', 'ad' =&gt; 'Ad'], 'kayit');
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

                    <!--begin::Excel-->
                    <div class="mb-10">
                        <h3 class="fw-bold text-body-emphasis mb-4">7. Excel Çıktısı</h3>
                        <p class="fs-5 text-body mb-4">
                            Çıktı <b>xlsx</b>'tir. <code>VnlTableExport</code> dosyayı bağımlılıksız üretir:
                            PhpSpreadsheet ve eski PHPExcel, xlsx için <code>ext-zip</code> ister; bu sınıf
                            eklenti yoksa sıkıştırmasız zip kabını kendisi yazar. Başlık satırı kalın ve
                            donuk, sütunlara süzme okları eklenir, genişlikler içeriğe göre ayarlanır.
                        </p>

                        <div class="vnl-code mb-5">
                            <pre class="mb-0" style="background: transparent; white-space: pre-wrap;"><code>// Varsayilan: getList icinde uret
if ($t-&gt;op === VnlTableRequest::OP_EXPORT) {
    // Sayfalamadan ONCE cagirin: kullanici gordugu sayfayi degil,
    // filtrenin tamamini indirsin.
    $t-&gt;exportXlsx($tumSatirlar, ['id' =&gt; 'No', 'ad' =&gt; 'Ad'], 'kayit');
}

// Dosyayi diske yazmak / maile eklemek icin
$binary = VnlTableExport::build($satirlar, ['id' =&gt; 'No', 'ad' =&gt; 'Ad']);</code></pre>
                        </div>

                        <h6 class="fw-bold mb-3">Kendi çıktı sayfanız</h6>
                        <p class="fs-6 text-body mb-4">
                            Rapor şablonu doldurmak, birden çok sayfa yazmak ya da özel biçimlendirme
                            gerekiyorsa <code>exportUrl()</code> ile kendi action'ınızı verin. İstek aynı
                            gövdeyle — filtre, sıralama, arama, <code>context</code> — oraya düşer;
                            <code>getList</code> hiç çağrılmaz.
                        </p>

                        <div class="vnl-code">
                            <pre class="mb-0" style="background: transparent; white-space: pre-wrap;"><code>VnlTable::make('satislar')
    -&gt;url('app/satis/getList')
    -&gt;exportUrl('app/rapor/aylikSatis')   // Excel dugmesi buraya gider
    -&gt;columns([...])
    -&gt;render();

// actions/action_rapor.php
public function aylikSatis($req)
{
    $t = VnlTableRequest::from($req);     // ayni sozlesme
    // ... sablonu doldur, istediginiz dosyayi akitin ...
}</code></pre>
                        </div>
                    </div>
                    <!--end::Excel-->

                    <div class="separator separator-dashed my-8"></div>

                    <!--begin::Seçenekler-->
                    <div class="mb-10">
                        <h3 class="fw-bold text-body-emphasis mb-4">8. Tablo Seçenekleri</h3>

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

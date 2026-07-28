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
                    <h5 class="mb-1">Workbench tamam</h5>
                    <span>
                        Sütun sabitleme ve <b>sürükleyip yeniden sıralama</b>, çok seviyeli satır
                        gruplama, hücre düzenleme, detay satırı, seçim ve toplu işlem,
                        <b>çapraz tablo</b>. Hepsi aşağıdaki canlı örnekte denenebilir.
                    </span>
                </div>
            </div>

            <!--begin::Canlı örnek-->
            <div class="card mb-10">
                <div class="card-body p-10">
                    <h3 class="fw-bold text-body-emphasis mb-4">1. Canlı Örnek</h3>
                    <p class="fs-5 text-body mb-6">
                        <b>Başlığın yazısını</b> üstteki kesik çizgili alana sürükleyin — tablo o
                        sütuna göre gruplanır; bir tane daha sürükleyin, iç içe iki seviye olur.
                        <b>Şehir</b>, <b>Durum</b> ve <b>Tutar</b> hücrelerine <b>çift tıklayın</b> —
                        yerinde düzenlenip kaydedilir. Soldaki <b>oka</b> basın — satırın altında
                        detayı açılır. <b>Onay kutularından</b> birkaç satır seçin, sonra sayfanın
                        tamamını seçip beliren "filtreye uyan 213 kaydın tümünü seç"e basın.
                        Tabloyu yatay kaydırın — seçim, ok, <b>No</b> ve <b>Ad Soyad</b> solda,
                        <b>Tarih</b> sağda kalır.
                    </p>

                    <?= VnlTable::make('demoWorkbench')
                        ->url('app/vnltable/getList')
                        ->detailUrl('app/vnltable/getList')
                        ->mode(VnlTable::WORKBENCH)
                        ->key('id')
                        ->actions([
                            ['name' => 'onayla', 'title' => 'Onayla', 'variant' => 'primary',
                             'confirm' => 'Seçili kayıtların durumu "Onaylandı" olacak. Devam edilsin mi?'],
                            ['name' => 'say', 'title' => 'Topla', 'variant' => 'soft-secondary'],
                        ])
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

                        <h6 class="fw-bold mb-3">Sürükleyerek gruplama</h6>
                        <p class="fs-6 text-body mb-4">
                            Başlığın <b>yazısı</b> sürüklenebilir, hücrenin tamamı değil — hücre
                            sıralamayı tıklıyor, huni kendi panelini açıyor. Bırakma alanına atılan
                            her başlık <b>sona bir seviye ekler</b>; rozetlerdeki numara sırayı
                            gösterir. Sıra önemlidir: Şehir → Durum ile Durum → Şehir bambaşka iki
                            ağaçtır. Araç çubuğundaki menü aynı işi yapar ve dokunmatikte de
                            çalışır (sürükle-bırak orada yoktur).
                        </p>

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
                                Gruplu görünümde sayfalanan birim de satır değil <b>üst seviyedeki
                                gruplardır</b>; alt seviyeler ve satırlar kendi içinde açılır.
                            </span>
                        </div>
                    </div>
                    <!--end::Gruplama-->

                    <div class="separator separator-dashed my-8"></div>

                    <!--begin::Duzenleme-->
                    <div class="mb-10">
                        <h3 class="fw-bold text-body-emphasis mb-4">4. Hücre Düzenleme</h3>
                        <p class="fs-5 text-body mb-4">
                            <code>'editable' =&gt; true</code> verilen hücre çift tıkla açılır.
                            <b>Enter</b> kaydeder, <b>Escape</b> eski değere döner.
                            <code>editOptions</code> verilirse serbest metin yerine açılır liste
                            gelir; kutu türü sütunun <code>type</code>'ından çıkar (sayıda sayı,
                            tarihte takvim).
                        </p>

                        <div class="vnl-code mb-5">
                            <pre class="mb-0" style="background: transparent; white-space: pre-wrap;"><code>['name' =&gt; 'sehir', 'title' =&gt; 'Şehir', 'editable' =&gt; true],
['name' =&gt; 'durum', 'title' =&gt; 'Durum', 'editable' =&gt; true,
 'editOptions' =&gt; ['Onaylandı', 'Beklemede', 'İptal']],
// ilişkisel dizide anahtar kaydedilen, değer gösterilendir
['name' =&gt; 'tip', 'editable' =&gt; true, 'editOptions' =&gt; ['A' =&gt; 'Bireysel', 'K' =&gt; 'Kurumsal']],</code></pre>
                        </div>

                        <h6 class="fw-bold mb-3"><code>getList</code> tarafı</h6>
                        <div class="vnl-code mb-5">
                            <pre class="mb-0" style="background: transparent; white-space: pre-wrap;"><code>if ($t-&gt;op === VnlTableRequest::OP_SAVE) {
    Auth::CheckPermission('KayitDuzenle');

    // Yazilabilir sutunlarin BEYAZ LISTESI. Listede olmayan ad
    // sessizce atlanmaz, istek hatayla biter.
    $sutun = $t-&gt;editColumn(['ad' =&gt; 'name', 'sehir' =&gt; 'city', 'durum' =&gt; 'status']);

    $deger = trim((string)$t-&gt;value);
    if ($deger === '') throwErrorMessage('Bos birakilamaz.');

    Kayit::where('id', $t-&gt;rowKey)-&gt;update([$sutun =&gt; $deger]);

    // Deger dondurursen istemci hucreyi onunla tazeler: kirpilmis,
    // bicimlenmis ya da buyuk harfe cevrilmis hali gorunsun.
    $t-&gt;respondSaved($deger);
}</code></pre>
                        </div>

                        <div class="alert alert-danger d-flex align-items-start p-4 mb-5">
                            <i class="fa-solid fa-shield fs-2 text-danger me-3"></i>
                            <span class="text-body fs-6">
                                <code>editColumn()</code>'un listesi <b>zorunludur</b> ve filtredekinden
                                daha kritiktir: filtrede en kötü ihtimalle yanlış satır listelenir,
                                burada <b>yanlış sütuna yazılır</b>. Sütun adı da satır kimliği de
                                istemciden gelir — yetki kontrolünü ve doğrulamayı burada yapın.
                                <code>'editable' =&gt; true</code> yalnız arayüzü açar, sunucuda
                                hiçbir şeyi yetkilendirmez.
                            </span>
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
                                    <td class="ps-5">Tek hücre, tek istek</td>
                                    <td>Kirli satır biriktirilmez. Yazılan ya kaydedilir ya eski değerine döner — yarım kalmış bir düzenleme sayfa yenilenince sessizce kaybolmaz.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5">Hata</td>
                                    <td>Sunucu reddederse hücre eski değerine döner, kırmızı yanıp söner ve mesaj gösterilir. İyimser güncelleme geri alınır.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5">Satır kimliği</td>
                                    <td><code>key()</code> ile bildirdiğiniz sütundan gelir. Kimliği olmayan satırın hücresi düzenlenebilir olsa da açılmaz.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>saveUrl()</code></td>
                                    <td>Yazmayı okuma action'ından ayırır — yetki, kayıt tutma ve doğrulama genelde ayrı yerde durur. Verilmezse <code>url()</code>'e <code>op = save</code> ile gider.</td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <!--end::Duzenleme-->

                    <div class="separator separator-dashed my-8"></div>

                    <!--begin::Detay-->
                    <div class="mb-10">
                        <h3 class="fw-bold text-body-emphasis mb-4">5. Detay Satırı</h3>
                        <p class="fs-5 text-body mb-4">
                            <code>detailUrl()</code> verildiğinde satırların soluna bir açma oku
                            gelir. Ok tıklanınca o action'a <code>op = detail</code> ve satırın
                            kimliği gider; yanıt <b>JSON değil HTML'dir</b> ve satırın hemen altına
                            basılır. Birden çok satır aynı anda açık kalabilir; kapatıp açmak
                            sunucuya yeniden gitmez, gövde bellekte durur.
                        </p>

                        <div class="vnl-code mb-5">
                            <pre class="mb-0" style="background: transparent; white-space: pre-wrap;"><code>VnlTable::make('satislar')
    -&gt;url('app/satis/getList')
    -&gt;detailUrl('app/satis/detay')   // ayni action da olabilir
    -&gt;key('id')

// actions/action_satis.php
public function detay($req)
{
    Auth::CheckLogin();
    $t = VnlTableRequest::from($req);

    $satis = Satis::find($t-&gt;rowKey);
    if (!$satis) throwErrorMessage('Kayit bulunamadi.');

    $t-&gt;respondDetail(render('parts/satis-detay', ['satis' =&gt; $satis]));
}</code></pre>
                        </div>

                        <div class="alert alert-danger d-flex align-items-start p-4 mb-0">
                            <i class="fa-solid fa-shield fs-2 text-danger me-3"></i>
                            <span class="text-body fs-6">
                                Detay gövdesi <code>innerHTML</code> ile basılır — kaçış
                                <b>sizdedir</b>, sütunlardaki <code>'render' =&gt; 'html'</code> ile
                                aynı sözleşme. Kullanıcı girdisini <code>htmlspecialchars()</code>
                                olmadan buraya koymayın. Satır kimliği de istemciden gelir: yetki
                                kontrolünü <code>detay</code> içinde yapın, listede olması yetmez.
                            </span>
                        </div>
                    </div>
                    <!--end::Detay-->

                    <div class="separator separator-dashed my-8"></div>

                    <!--begin::Secim-->
                    <div class="mb-10">
                        <h3 class="fw-bold text-body-emphasis mb-4">6. Seçim ve Toplu İşlem</h3>
                        <p class="fs-5 text-body mb-4">
                            <code>actions()</code> tanımlandığında onay kutusu sütunu ve seçim
                            çubuğu kendiliğinden gelir. Yalnız seçim isteyip işlem istemiyorsanız
                            <code>'selection' =&gt; true</code> verin.
                        </p>

                        <div class="vnl-code mb-5">
                            <pre class="mb-0" style="background: transparent; white-space: pre-wrap;"><code>-&gt;actions([
    ['name' =&gt; 'sil', 'title' =&gt; 'Sil', 'variant' =&gt; 'danger',
     'confirm' =&gt; 'Secili kayitlar silinecek. Emin misiniz?'],
    ['name' =&gt; 'onayla', 'title' =&gt; 'Onayla', 'variant' =&gt; 'primary'],
])</code></pre>
                        </div>

                        <div class="alert alert-warning d-flex align-items-start p-4 mb-5">
                            <i class="fa-solid fa-triangle-exclamation fs-2 text-warning me-3"></i>
                            <span class="text-body fs-6">
                                <b>"Tümünü seç" bir kimlik listesi değildir.</b> Kullanıcı 4200
                                kaydı seçerken tarayıcıya 25 tanesi inmiş oluyor; kimlikleri
                                toplamaya kalkmak ya eksik bir liste ya da 4200 satırlık gereksiz
                                bir indirme demekti. Bunun yerine istemci
                                <code>mode: "all"</code> gönderir ve yalnız <b>neyi dışarıda
                                bıraktığını</b> söyler; kümeyi filtrelerden sunucu çözer.
                            </span>
                        </div>

                        <div class="row g-5 mb-6">
                            <div class="col-md-6">
                                <h6 class="fw-bold mb-3"><span class="badge badge-soft-primary me-2">bulk</span> istek</h6>
                                <div class="vnl-code">
                                    <pre class="mb-0" style="background: transparent; white-space: pre-wrap;"><code>{
  "op": "bulk",
  "action": "onayla",
  "selection": {
    "mode": "all",
    "except": ["1", "7"]
  },
  "filters": { ... },
  "search": "",
  "context": {}
}

// ya da tek tek secilmisse
"selection": {"mode":"some","ids":["3","9"]}</code></pre>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <h6 class="fw-bold mb-3"><span class="badge badge-soft-success me-2">getList</span> tarafı</h6>
                                <div class="vnl-code">
                                    <pre class="mb-0" style="background: transparent; white-space: pre-wrap;"><code>if ($t-&gt;op === VnlTableRequest::OP_BULK) {
    Auth::CheckPermission('KayitSil');

    if (!$t-&gt;hasSelection()) {
        throwErrorMessage('Hicbir kayit secilmedi.');
    }

    $q = Kayit::query();
    $t-&gt;applyFilters($q, $alanlar);   // ONCE filtreler
    $t-&gt;applySelection($q, 'id');     // SONRA secim

    $adet = $q-&gt;count();
    $q-&gt;delete();

    $t-&gt;respondBulk($adet . ' kayit silindi.');
}</code></pre>
                                </div>
                            </div>
                        </div>

                        <div class="table-responsive border rounded bg-body-tertiary mb-5">
                            <table class="table align-middle table-row-dashed fs-6 gy-4 mb-0">
                                <thead>
                                <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0 border-bottom">
                                    <th class="ps-5 min-w-250px">Üye</th>
                                    <th class="min-w-350px">İş</th>
                                </tr>
                                </thead>
                                <tbody class="text-body fw-semibold">
                                <tr><td class="ps-5"><code>$t-&gt;hasSelection()</code></td><td>Hiçbir şey seçilmemişse toplu işlem çalışmamalı.</td></tr>
                                <tr><td class="ps-5"><code>$t-&gt;selectedAll()</code></td><td>Kullanıcı "filtreye uyan hepsi"ni mi seçti.</td></tr>
                                <tr><td class="ps-5"><code>$t-&gt;selectedIds()</code></td><td>Tek tek seçilmiş kimlikler ("all" modunda boş).</td></tr>
                                <tr><td class="ps-5"><code>$t-&gt;excludedIds()</code></td><td>"all" modunda kullanıcının çıkardıkları.</td></tr>
                                <tr><td class="ps-5"><code>$t-&gt;applySelection($q, 'id')</code></td><td>Seçimi sorguya uygular. <b>applyFilters()'tan sonra</b> çağırın — "hepsi" zaten filtrelenmiş kümeyi kasteder.</td></tr>
                                <tr><td class="ps-5"><code>$t-&gt;isSelected($row, 'id')</code></td><td>Bellekteki satır için aynı sınama.</td></tr>
                                <tr><td class="ps-5"><code>$t-&gt;respondBulk($mesaj, $reload)</code></td><td><code>$reload = false</code> verirseniz tablo yenilenmez ve seçim korunur — listeyi değiştirmeyen işlemler (rapor, toplam alma) için.</td></tr>
                                </tbody>
                            </table>
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
                                    <td class="ps-5">Başlıktaki kutu</td>
                                    <td><b>Görünen sayfayı</b> seçer, tabloyu değil. Sayfanın tamamı seçildiğinde ve filtrede daha fazlası olduğunda çubukta "filtreye uyan N kaydın tümünü seç" belirir.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5">Sayfa değişimi</td>
                                    <td>Seçim korunur — sayfalama ve sıralama onu bozmaz.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5">Filtre değişimi</td>
                                    <td>Seçim <b>düşer</b>. "Filtreye uyan hepsi" artık başka bir kümeyi kastediyor olurdu; sessizce başka kayıtlara işlem yapmaktansa seçimi sıfırlamak doğru.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5">Kimliksiz satır</td>
                                    <td>Kutusu kapalı gelir. <code>key()</code> ile bildirdiğiniz sütun olmadan hangi kaydın seçildiği söylenemez.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5">Gruplu görünüm</td>
                                    <td>Kutular veri satırlarındadır; grup satırının kendi kutusu <b>henüz yok</b>. Sunucu stratejisinde grubun bütün üyeleri yüklenmiş olmayabiliyor.</td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <!--end::Secim-->

                    <div class="separator separator-dashed my-8"></div>

                    <!--begin::Sira-->
                    <div class="mb-10">
                        <h3 class="fw-bold text-body-emphasis mb-4">7. Sütun Sırası</h3>
                        <p class="fs-5 text-body mb-4">
                            Başlığın yazısını <b>başka bir başlığın üzerine</b> sürüklemek sütunun
                            yerini değiştirir. Aynı sürükleme gruplama alanına bırakılırsa gruplar;
                            hedef neyse o olur. İmleç başlığın hangi yarısındaysa çizgi o kenara
                            düşer, yani sütunun öncesine mi sonrasına mı gireceği bırakmadan önce
                            görünür.
                        </p>

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
                                    <td class="ps-5">Sabitleme kümeleri</td>
                                    <td>Sola sabit sütunlar başta, sağa sabitler sonda kalır. Bir sütunu kendi kümesinin dışına bırakmak düzeni bozardı; reddedilmez, sessizce kümesine çekilir.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5">Kalıcılık</td>
                                    <td>Tercih tarayıcıda, tablo kimliğiyle saklanır — gizleme tercihiyle aynı yerde. <b>Adrese yazılmaz:</b> paylaşılan bir bağlantının karşı tarafın sütun düzenini değiştirmesi istenmez.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5">Yeni sütunlar</td>
                                    <td>Tanıma sonradan eklenen sütun, kayıtlı sırada olmadığı için sona gelir. Eski bir tercih yeni bir sütunu gizlemez.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5">Sıfırlama</td>
                                    <td>Sütun menüsünün altındaki <b>Sütun sırasını sıfırla</b>.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5">Kapatmak</td>
                                    <td><code>'reorder' =&gt; false</code>. Grid ve list'te zaten kapalıdır.</td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <!--end::Sira-->

                    <div class="separator separator-dashed my-8"></div>

                    <!--begin::Pivot-->
                    <div class="mb-10">
                        <h3 class="fw-bold text-body-emphasis mb-4">8. Çapraz Tablo</h3>
                        <p class="fs-5 text-body mb-4">
                            Araç çubuğundaki ızgara düğmesi tabloyu çapraz görünüme çevirir:
                            bir kırılım satırlara, bir kırılım sütunlara, aralarındaki hücrelerde
                            tek bir ölçü. Eksenler üstteki şeritten değiştirilir.
                        </p>

                        <div class="vnl-code mb-5">
                            <pre class="mb-0" style="background: transparent; white-space: pre-wrap;"><code>-&gt;options(['pivot' =&gt; [
    'rows' =&gt; 'sehir',   // satir kirilimi
    'cols' =&gt; 'durum',   // sutun kirilimi
    'col'  =&gt; 'tutar',   // olculen alan; bos birakilirsa kayit SAYISI
    'fn'   =&gt; 'sum',     // sum | avg | min | max | count
    'on'   =&gt; false,     // true ise tablo dogrudan pivot acilir
]])

// sadece dugme yeter, eksenleri kullanici secsin:
-&gt;options(['pivot' =&gt; true])</code></pre>
                        </div>

                        <h6 class="fw-bold mb-3"><code>getList</code> tarafı</h6>
                        <div class="vnl-code mb-5">
                            <pre class="mb-0" style="background: transparent; white-space: pre-wrap;"><code>if ($t-&gt;op === VnlTableRequest::OP_PIVOT) {
    // Bellekteki veriyle (filtrelerden gecmis satirlar):
    $t-&gt;pivotFrom($satirlar);

    // Matrisi kendiniz kullanmak isterseniz (ornegin Excel'e):
    $matris = $t-&gt;pivot($satirlar);
    // ['cols' =&gt; [...], 'rows' =&gt; [...], 'totals' =&gt; [...], 'grand' =&gt; ...]
}</code></pre>
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
                                    <td class="ps-5">Sütunlar veriden gelir</td>
                                    <td>Tanımdan değil. Sıralama, sütun filtreleri, seçim, gruplama ve düzenleme çapraz görünümde <b>kapalıdır</b> — tutunacakları bir sütun tanımı yok. Denetimleri de gizlenir, yanlış bir şey vaat etmesinler.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5">Filtreler geçerli</td>
                                    <td>Düz arama ve sütun filtreleri matrise <b>uygulanır</b>; yalnız görünüm değişir, veri kümesi değişmez.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5">Boş hücre</td>
                                    <td>Sıfır değil, <b>yok</b> demektir: o kırılımda hiç kayıt bulunmuyor. Sıfır basmak "0 TL satış" gibi okunurdu.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5">Ölçü seçilmezse</td>
                                    <td>Ölçülen şey <b>kayıt sayısıdır</b> ve işlev <code>count</code>'a kilitlenir; sayarken toplam ya da ortalamanın anlamı yok.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5">Kendiliğinden seçim</td>
                                    <td>Eksen verilmediyse ilk iki kırılabilir sütun ve <b><code>agg</code> tanımlı</b> ilk sayısal alan seçilir. <code>agg</code> olmasaydı ölçü çoğu tabloda kimlik sütununa düşer, kimlikleri toplamak hiçbir şey anlatmazdı.</td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <!--end::Pivot-->

                    <div class="separator separator-dashed my-8"></div>

                    <!--begin::Gorunum-->
                    <div class="mb-0">
                        <h3 class="fw-bold text-body-emphasis mb-4">9. Görünüm</h3>
                        <p class="fs-5 text-body mb-4">
                            Workbench'in başlık şeridi <b>mavidir</b> ve bu <code>skin</code>'den
                            bağımsızdır: hangi skin seçilirse seçilsin, workbench başka bir alet
                            olduğunu ilk bakışta söyler. Renk zemine karıştırılarak üretilir, o
                            yüzden koyu temada da kendiliğinden oturur — sabit bir mavi orada
                            parlıyordu.
                        </p>

                        <div class="vnl-code mb-4">
                            <pre class="mb-0" style="background: transparent; white-space: pre-wrap;"><code>/* Kendi maviniz: tablonun kapsayicisina yazin */
.benim-sayfam { --vnl-bench-accent: #7c3aed; }</code></pre>
                        </div>

                        <p class="fs-6 text-body mb-0">
                            Vurgu rengi (yeşil) yerinde kalır — filtre çipleri, sayfalama ve
                            <code>--vnl-table-accent</code> kullanan her şey onu kullanmayı sürdürür.
                            Mavi yalnız başlık şeridinin, grup okunun ve gruplama denetimlerinin
                            işaretidir.
                        </p>
                    </div>
                    <!--end::Gorunum-->

                </div>
            </div>

        </div>
    </div>
</div>

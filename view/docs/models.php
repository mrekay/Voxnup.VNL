<div class="d-flex flex-column flex-column-fluid">
    <div id="kt_app_content" class="app-content flex-column-fluid">
        <div id="kt_app_content_container" class="app-container container-xxl">

            <div class="vnl-page-hero mb-12 mt-6">
                <div class="vnl-overline mb-3">// docs/models</div>
                <h1 class="fs-2hx fw-bold mb-4 text-body-emphasis">Veritabanı Katmanı ve Modeller</h1>
                <div class="vnl-lead">VNL'de veritabanı işlemleri her zaman model sınıfları üzerinden yapılır. Her varlık için bir model sınıfı oluşturmak sistem bütünlüğünün temelidir; spagetti kodu önler ve tutarlı bir kod tabanı sağlar.</div>
            </div>

            <div class="alert alert-danger d-flex align-items-center p-5 mb-10">
                <i class="fa-solid fa-circle-xmark fs-2hx text-danger me-4"></i>
                <div class="d-flex flex-column">
                    <h5 class="mb-1 text-danger">Capsule:: Doğrudan Kullanımı Yasak Gibidir</h5>
                    <span class="text-body"><code>Capsule::table('tbl_x')->...</code> çağrısı teknik olarak çalışsa da VNL felsefesi gereği <b>kesinlikle önerilmez</b>. Veritabanına erişen her işlem için bir model sınıfı oluşturun. Bu kural spagetti kodu engeller ve sistem bütünlüğünü korur. İstisna yoktur.</span>
                </div>
            </div>

            <div class="card mb-10">
                <div class="card-body p-10">

                    <!--begin::Section 1-->
                    <div class="mb-10">
                        <h3 class="fw-bold text-body-emphasis mb-4">1. Veritabanı Katmanına Genel Bakış</h3>
                        <p class="fs-5 text-body mb-4">
                            <code>Database</code> sınıfı, <code>\Illuminate\Database\Capsule\Manager</code>'ı genişletir. Yapılandırma bilgilerini <code>$cDefinitions->getDBInfo()</code> üzerinden okur ve bağlantıyı global olarak ayarlar. Bu altyapı sayesinde tüm model sınıfları otomatik olarak veritabanına bağlıdır.
                        </p>

                        <div class="row g-5">
                            <div class="col-md-4">
                                <div class="border border-dashed rounded p-5 h-100 text-center">
                                    <i class="fa-solid fa-cubes fs-3x text-primary mb-3"></i>
                                    <h6 class="fw-bold mb-2">Illuminate\Database</h6>
                                    <span class="text-muted fs-7">Laravel'in ORM motoru. Eloquent model desteği ve sorgu oluşturucu içerir.</span>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="border border-dashed rounded p-5 h-100 text-center">
                                    <i class="fa-solid fa-database fs-3x text-success mb-3"></i>
                                    <h6 class="fw-bold mb-2">TableModel</h6>
                                    <span class="text-muted fs-7">Tüm model sınıflarının türediği soyut temel sınıf. Soft delete ve filtreleme desteği sunar.</span>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="border border-dashed rounded p-5 h-100 text-center">
                                    <i class="fa-solid fa-gear fs-3x text-warning mb-3"></i>
                                    <h6 class="fw-bold mb-2">Config Entegrasyonu</h6>
                                    <span class="text-muted fs-7"><code>getDBInfo()</code> ile bağlantı bilgileri okunur; test/üretim moduna göre doğru veritabanı seçilir.</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!--end::Section 1-->

                    <div class="separator separator-dashed my-8"></div>

                    <!--begin::Section 2-->
                    <div class="mb-10">
                        <h3 class="fw-bold text-body-emphasis mb-4">2. TableModel — Temel Model Sınıfı</h3>
                        <p class="fs-5 text-body mb-4">
                            <code>TableModel</code>, <code>\Illuminate\Database\Eloquent\Model</code>'i genişleten soyut bir sınıftır. Projenizdeki tüm model sınıfları bu sınıfı miras alır. Aşağıdaki alanlar VNL felsefesi gereği zorunlu veya zorunlu gibi olarak kabul edilir.
                        </p>

                        <h5 class="fw-bold text-body-emphasis mb-3">Temel Özellikler ve Alanlar</h5>
                        <div class="table-responsive border rounded bg-body-tertiary mb-6">
                            <table class="table align-middle table-row-dashed fs-6 gy-4 mb-0">
                                <thead>
                                <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0 border-bottom">
                                    <th class="ps-5 min-w-220px">Alan / Özellik</th>
                                    <th class="min-w-120px">Durum</th>
                                    <th class="min-w-350px">Açıklama</th>
                                </tr>
                                </thead>
                                <tbody class="text-body fw-semibold">
                                <tr>
                                    <td class="ps-5"><code>$table</code></td>
                                    <td><span class="badge badge-soft-danger">Zorunlu</span></td>
                                    <td>Modelin bağlı olduğu veritabanı tablo adı. Örn: <code>'tbl_urunler'</code></td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>$fillable</code></td>
                                    <td><span class="badge badge-soft-warning">Zorunlu Gibi</span></td>
                                    <td>Toplu atama ile doldurulabilecek sütun adları. Felsefe gereği <b>şiddetle önerilir</b>; tüm yazılabilir sütunlar burada tanımlanmalıdır.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>$foreign_keys</code></td>
                                    <td><span class="badge badge-soft-warning">Zorunlu Gibi</span></td>
                                    <td>İlişki tanımları. Felsefe gereği <b>şiddetle önerilir</b>. Format: <code>['kategori' => ['class' => Kategori::class, 'column' => 'kategori_id']]</code></td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>protected static $columns</code></td>
                                    <td><span class="badge badge-soft-danger">Kesinlikle Zorunlu</span></td>
                                    <td><code>SelectList</code> içinde filtrelenecek ve seçilecek sütunların listesi. Bu alan <b>doldurulmak zorundadır</b>, boş bırakılmamalıdır.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>SoftDeletes trait</code></td>
                                    <td><span class="badge badge-soft-warning">Zorunlu Gibi</span></td>
                                    <td>Silme işlemini fiziksel silme yerine <code>deleted_at</code> doldurarak yapar. Felsefe gereği tüm modellerde kullanılmalıdır.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>$primaryKey</code></td>
                                    <td><span class="badge badge-soft-success">Opsiyonel</span></td>
                                    <td>Birincil anahtar sütunu. Varsayılan: <code>'id'</code>. Farklıysa belirtin.</td>
                                </tr>
                                </tbody>
                            </table>
                        </div>

                        <h5 class="fw-bold text-body-emphasis mb-3">Metotlar</h5>
                        <div class="table-responsive border rounded bg-body-tertiary mb-6">
                            <table class="table align-middle table-row-dashed fs-6 gy-4 mb-0">
                                <thead>
                                <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0 border-bottom">
                                    <th class="ps-5 min-w-280px">Metot İmzası</th>
                                    <th class="min-w-350px">Açıklama</th>
                                </tr>
                                </thead>
                                <tbody class="text-body fw-semibold">
                                <tr>
                                    <td class="ps-5"><code>tableName(string $as)</code></td>
                                    <td><code>'tbl_urunler as u'</code> biçiminde join için hazır tablo adı döndürür.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>getBelongTable($key)</code></td>
                                    <td><code>$foreign_keys</code> tanımına göre ilişkili kaydı getirir. Örn: <code>$urun->getBelongTable('kategori')</code></td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>Select($filter)</code></td>
                                    <td>Tek kayıt döndürür. Statik olarak çağrılır: <code>Urun::Select(['id' => $id])</code></td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>SelectList($filters, $extras)</code></td>
                                    <td>Her modelde implement edilmesi gereken metot. Statik çağrı, sonuna <b>mutlaka</b> <code>->get()</code> eklenir: <code>Urun::SelectList($f, $e)->get()</code></td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>selectListQuery($columns, $filters, $extras)</code></td>
                                    <td>Filtreleme, sayfalama ve sıralama desteği. <code>SelectList</code> içinden çağrılır, sorgu builder döner.</td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <!--end::Section 2-->

                    <div class="separator separator-dashed my-8"></div>

                    <!--begin::Section 3-->
                    <div class="mb-10">
                        <h3 class="fw-bold text-body-emphasis mb-4">3. Model Oluşturma Standardı</h3>
                        <p class="fs-5 text-body mb-4">
                            Yeni bir model oluştururken <code>classes/</code> klasörü altında bir PHP dosyası açın ve <code>TableModel</code>'i genişletin. <code>SoftDeletes</code>, <code>$fillable</code>, <code>$foreign_keys</code> ve <code>static $columns</code> alanlarının tamamı eksiksiz doldurulmalıdır.
                        </p>

                        <div class="d-flex align-items-center bg-body-tertiary border border-dashed rounded p-4 mb-4">
                            <i class="fa-solid fa-file fs-1 text-primary me-3"></i>
                            <span class="fs-5 fw-bold text-body font-monospace">classes/Urun.php</span>
                        </div>

                        <div class="vnl-code mb-5">
                            <pre class=" mb-0" style="background: transparent; white-space: pre-wrap;"><code>&lt;?php

class Urun extends TableModel
{
    use \Illuminate\Database\Eloquent\SoftDeletes; // Felsefe gereği zorunlu

    protected $table = 'tbl_urunler';

    // Felsefe gereği zorunlu gibi — tüm yazılabilir sütunlar tanımlanmalı
    protected $fillable = [
        'kategori_id',
        'ad',
        'aciklama',
        'fiyat',
        'stok',
        'aktif',
        'InsertDate',
        'InsertUser',
        'UpdateDate',
        'UpdateUser',
    ];

    // Felsefe gereği zorunlu gibi — ilişkisel sınıflar tanımlanmalı
    protected $foreign_keys = [
        'kategori' => [
            'class'  => Kategori::class,
            'column' => 'kategori_id',
        ],
    ];

    // Kesinlikle zorunlu. Bu bir sütun LİSTESİ DEĞİL, filtre HARİTASIDIR:
    // anahtar dışarıdan gelen filtre adı, 'name' gerçek sütun, 'operator'
    // o filtrenin varsayılan karşılaştırması.
    protected static $columns = [
        'id'       => ['name' => 'tbl_urunler.id'],
        'ad'       => ['name' => 'tbl_urunler.ad', 'operator' => 'like'],
        'fiyat'    => ['name' => 'tbl_urunler.fiyat'],
        'stok'     => ['name' => 'tbl_urunler.stok'],
        'aktif'    => ['name' => 'tbl_urunler.aktif', 'zero' => true],   // 0 da geçerli filtre (3.4.0)
        'kategori' => ['name' => 'tbl_urunler.kategori_id'],
    ];

    // TableModel'in TEK soyut metodu budur; implement etmezseniz sınıf
    // örneklenemez. Select() zaten TableModel'den gelir, yeniden yazmayın.
    public static function SelectList($filters = [], $extras = [])
    {
        return self::selectListQuery(self::$columns, $filters, $extras)
            ->select('tbl_urunler.*', 'k.ad as kategori_ad')
            ->leftJoin(Kategori::tableName('k'), 'kategori_id', '=', 'k.id');
    }
}
</code></pre>
                        </div>

                        <h5 class="fw-bold text-body-emphasis mb-3">Statik Kullanım — <code>->get()</code> Zorunludur</h5>
                        <div class="alert alert-warning d-flex align-items-center p-4 mb-4">
                            <i class="fa-solid fa-circle-info fs-2 text-warning me-3"></i>
                            <span class="text-body fs-6"><code>SelectList</code> bir query builder döndürür. Sonuçları almak için mutlaka <code>->get()</code> çağrılmalıdır. Bu kural olmadan veri gelmez.</span>
                        </div>

                        <div class="vnl-code mb-4">
                            <pre class=" mb-0" style="background: transparent; white-space: pre-wrap;"><code>// Tek kayıt. Anahtar SÜTUN ADI DEĞİL, $columns'taki filtre adıdır.
$urun = Urun::Select(['id' => 42]);

// Filtrelenmiş liste — sayfalı — ->get() ZORUNLU
$liste = Urun::SelectList(
    ['ad' => 'bisiklet'],                      // operatör $columns'tan: like
    ['limit' => 20, 'begin' => 0, 'order' => 'ad', 'order_by' => 'asc']
)->get();

// Sayım — sayfalama için
$filtreliSayisi = Urun::SelectList($filters)->count();
$toplamSayisi   = Urun::SelectList([])->count();

// Tüm kayıtlar: negatif limit uygulanmaz, zaten varsayılan da budur
$tumListe = Urun::SelectList($filters, ['limit' => -1])->get();
</code></pre>
                        </div>
                    </div>
                    <!--end::Section 3-->

                    <div class="separator separator-dashed my-8"></div>

                    <!--begin::Section 4-->
                    <div class="mb-10">
                        <h3 class="fw-bold text-body-emphasis mb-4">4. <code>selectListQuery</code> ile Filtreleme</h3>
                        <p class="fs-5 text-body mb-4">
                            <code>selectListQuery</code>, <code>$filters</code> ve <code>$extras</code> dizilerini alarak Eloquent sorgusuna WHERE koşulları, limit, offset ve ORDER BY ekler.
                        </p>

                        <h5 class="fw-bold text-body-emphasis mb-3"><code>$filters</code> Formatı</h5>
                        <div class="vnl-code mb-5">
                            <pre class=" mb-0" style="background: transparent; white-space: pre-wrap;"><code>// Kısa biçim — değer doğrudan; operatör $columns'tan gelir
$filters = [
    'aktif' => 1,
    'ad'    => 'bisiklet',
];

// Uzun biçim — operatörü çağrı yerinde ez
$filters = [
    'ad'    => ['value' => 'bisiklet', 'operator' => 'like'],
    'fiyat' => ['value' => '100|500',  'operator' => 'between'],
];</code></pre>
                        </div>

                        <div class="alert alert-warning d-flex align-items-start p-4 mb-5">
                            <i class="fa-solid fa-triangle-exclamation fs-2 text-warning me-3 mt-1"></i>
                            <span class="text-body fs-6"><b>Filtre anahtarı <code>$columns</code>'ta tanımlı değilse istek düşer.</b> <code>setFilterDefault()</code> tanımadığı her anahtar için <code>throwErrorMessage("İç Veritabanı Hatası : Geçerli Filtre Gelmedi (...)")</code> fırlatır. Bu bilinçlidir: dışarıdan gelen bir ad doğrudan sütun adına çevrilseydi, filtre listesi bir SQL enjeksiyon yüzeyi olurdu. Değeri boş olan filtreler ise sessizce atılır, sorguya hiç girmez — <code>null</code>, <code>''</code> ve <code>[]</code> her zaman; <code>0</code>, <code>'0'</code> ve <code>false</code> ise anahtarda <code>'zero' =&gt; true</code> yoksa (aşağıya bakın).</span>
                        </div>

                        <h5 class="fw-bold text-body-emphasis mb-3">Sıfır değerli filtreler — <code>'zero' =&gt; true</code> (3.4.0)</h5>
                        <p class="fs-6 text-body mb-4">
                            3.3'e kadar <code>['aktif' =&gt; 0]</code> filtresi "boş" sayılıp atılıyor ve <b>bütün</b> satırlar dönüyordu. Seçim kutularının çoğu "hepsi" için <code>"0"</code> gönderdiğinden kural genel olarak değiştirilmedi; sıfırın gerçek bir değer olduğu anahtar filtre haritasında bunu kendisi söyler. O anahtar için <code>0</code>, <code>'0'</code> ve <code>false</code> geçerli değerdir; <code>null</code>, <code>''</code> ve <code>[]</code> yine atılır. Karar <code>TableModel::filterHasValue()</code>'dadır.
                        </p>
                        <div class="vnl-code mb-5">
                            <pre class=" mb-0" style="background: transparent; white-space: pre-wrap;"><code>protected static $columns = [
    'status' =&gt; ['name' =&gt; 'tbl_x.status', 'zero' =&gt; true],
];

X::SelectList(['status' =&gt; 0])-&gt;get();   // yalnız status = 0 olanlar
X::SelectList(['status' =&gt; ''])-&gt;get();  // filtre yok — hepsi</code></pre>
                        </div>

                        <h5 class="fw-bold text-body-emphasis mb-3">Desteklenen Operatörler</h5>
                        <div class="table-responsive border rounded bg-body-tertiary mb-6">
                            <table class="table align-middle table-row-dashed fs-6 gy-4 mb-0">
                                <thead>
                                <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0 border-bottom">
                                    <th class="ps-5 min-w-150px">Operatör</th>
                                    <th class="min-w-200px">Değer Formatı</th>
                                    <th class="min-w-300px">Açıklama</th>
                                </tr>
                                </thead>
                                <tbody class="text-body fw-semibold">
                                <tr>
                                    <td class="ps-5"><code>=</code></td>
                                    <td>Herhangi bir değer</td>
                                    <td>Tam eşitlik. Varsayılan operatördür; belirtilmezse kullanılır.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>like</code></td>
                                    <td><code>'bisiklet'</code></td>
                                    <td><code>LOWER(sutun) LIKE '%bisiklet%'</code> üretir &mdash; iki taraf da küçültüldüğü için MySQL'de büyük/küçük harf duyarsızdır.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>ilike</code></td>
                                    <td><code>'bisiklet'</code></td>
                                    <td>PostgreSQL'e özgü büyük/küçük harf duyarsız arama.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>between</code></td>
                                    <td><code>'2024-01-01|2024-12-31'</code></td>
                                    <td>Pipe (<code>|</code>) ile ayrılmış iki değer arasındaki kayıtlar.</td>
                                </tr>
                                </tbody>
                            </table>
                        </div>

                        <h5 class="fw-bold text-body-emphasis mb-3"><code>$extras</code> Formatı</h5>
                        <div class="table-responsive border rounded bg-body-tertiary mb-6">
                            <table class="table align-middle table-row-dashed fs-6 gy-4 mb-0">
                                <thead>
                                <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0 border-bottom">
                                    <th class="ps-5 min-w-150px">Anahtar</th>
                                    <th class="min-w-120px">Varsayılan</th>
                                    <th class="min-w-300px">Açıklama</th>
                                </tr>
                                </thead>
                                <tbody class="text-body fw-semibold">
                                <tr>
                                    <td class="ps-5"><code>limit</code></td>
                                    <td><code>-1</code></td>
                                    <td>Sayfa başına kayıt sayısı. Negatif değerlerde limit uygulanmaz &mdash; <b>varsayılan da budur</b>, yani <code>$extras</code> vermezseniz tüm kayıtlar döner.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>begin</code></td>
                                    <td><code>0</code></td>
                                    <td>Başlangıç offset'i.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>order</code></td>
                                    <td><code>''</code></td>
                                    <td>Sıralanacak <b>filtre adı</b> &mdash; ham sütun adı değil, <code>$columns</code>'taki anahtar. Orada bulunmayan bir değer <b>sessizce yok sayılır</b> ve sorgu sırasız kalır. Boş bırakılırsa <code>ORDER BY</code> hiç eklenmez.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>order_by</code></td>
                                    <td><code>'asc'</code></td>
                                    <td>Sıralama yönü: <code>'asc'</code> veya <code>'desc'</code>.</td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <!--end::Section 4-->

                    <div class="separator separator-dashed my-8"></div>

                    <!--begin::Section 5-->
                    <div class="mb-10">
                        <h3 class="fw-bold text-body-emphasis mb-4">5. Soft Delete ve Update — Felsefe Gereği Zorunlu</h3>
                        <p class="fs-5 text-body mb-4">
                            VNL felsefesinde <code>delete()</code> ve <code>update()</code> işlemleri <b>model sınıfı üzerinden</b> yapılmalıdır. Soft delete tüm modellerde kullanılmalı; fiziksel silme <code>forceDelete()</code> ile yalnızca özel durumlarda tercih edilmelidir.
                        </p>

                        <div class="row g-5 mb-6">
                            <div class="col-md-4">
                                <div class="border border-dashed rounded p-5 h-100">
                                    <span class="badge badge-soft-danger mb-3">Soft Delete (Standart)</span>
                                    <div class="vnl-code">
                                        <pre class="mb-0" style="background: transparent; white-space: pre-wrap;"><code>// deleted_at doldurulur
// Kayıt hâlâ tabloda durur
Urun::where('Id', $id)
    ->update(['DeleteUser' => Auth::User()->Id]);
Urun::where('Id', $id)->delete();</code></pre>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="border border-dashed rounded p-5 h-100">
                                    <span class="badge badge-soft-primary mb-3">Update (Standart)</span>
                                    <div class="vnl-code">
                                        <pre class="mb-0" style="background: transparent; white-space: pre-wrap;"><code>$urun = Urun::Select(['Id' => $id]);
$urun->ad = $yeniAd;
$urun->fiyat = $yeniFiyat;
$urun->UpdateDate = now();
$urun->UpdateUser = Auth::User()->Id;
$urun->save();</code></pre>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="border border-dashed rounded p-5 h-100">
                                    <span class="badge badge-soft-success mb-3">Insert (Standart)</span>
                                    <div class="vnl-code">
                                        <pre class="mb-0" style="background: transparent; white-space: pre-wrap;"><code>$urun = new Urun();
$urun->ad = $ad;
$urun->fiyat = $fiyat;
$urun->InsertDate = now();
$urun->InsertUser = Auth::User()->Id;
$urun->save();</code></pre>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="alert alert-info d-flex flex-column flex-sm-row p-5 mb-0">
                            <i class="fa-solid fa-shield-halved fs-2hx text-info me-4 mb-5 mb-sm-0"></i>
                            <div class="d-flex flex-column pe-0 pe-sm-10">
                                <h5 class="mb-1">Soft Delete için Tablo Gereksinimi</h5>
                                <span>Tablonuzda <code>deleted_at TIMESTAMP NULL DEFAULT NULL</code> sütununun bulunması gerekir. Ayrıca silme işleminden önce <code>DeleteUser</code> alanını doldurmak iyi pratiktir — kimin sildiği takip edilebilir.</span>
                            </div>
                        </div>
                    </div>
                    <!--end::Section 5-->

                    <div class="separator separator-dashed my-8"></div>

                    <!--begin::Section 6-->
                    <div class="mb-0">
                        <h3 class="fw-bold text-body-emphasis mb-4">6. Neden Model? — Felsefe</h3>
                        <p class="fs-5 text-body mb-4">
                            <code>Capsule::</code> ile doğrudan sorgu yazmak mümkündür ancak bu yaklaşım VNL felsefesine aykırıdır. Her veritabanı erişimi model sınıfından geçmelidir.
                        </p>

                        <div class="row g-5 mb-0">
                            <div class="col-md-6">
                                <div class="card border border-dashed border-danger h-100">
                                    <div class="card-body p-5">
                                        <div class="d-flex align-items-center mb-3">
                                            <i class="fa-solid fa-circle-xmark fs-2 text-danger me-3"></i>
                                            <h6 class="mb-0 fw-bold text-danger">Yapma — Spagetti Kodu</h6>
                                        </div>
                                        <div class="vnl-code">
                                            <pre class="mb-0" style="background: transparent; white-space: pre-wrap;"><code>// Controller içinde doğrudan sorgu
$list = Capsule::table('tbl_urunler')
    ->where('aktif', 1)
    ->get();</code></pre>
                                        </div>
                                        <p class="text-muted fs-7 mt-3 mb-0">Yeniden kullanılamaz, test edilemez, bakımı zorlaşır.</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card border border-dashed border-success h-100">
                                    <div class="card-body p-5">
                                        <div class="d-flex align-items-center mb-3">
                                            <i class="fa-solid fa-circle-check fs-2 text-success me-3"></i>
                                            <h6 class="mb-0 fw-bold text-success">Yap — VNL Standardı</h6>
                                        </div>
                                        <div class="vnl-code">
                                            <pre class="mb-0" style="background: transparent; white-space: pre-wrap;"><code>// Model üzerinden statik çağrı
$list = Urun::SelectList(
    ['u.aktif' => 1], []
)->get();</code></pre>
                                        </div>
                                        <p class="text-muted fs-7 mt-3 mb-0">Merkezi, yeniden kullanılabilir, filtreleye ve genişletilebilir.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!--end::Section 6-->

                </div>
            </div>

        </div>
    </div>
</div>

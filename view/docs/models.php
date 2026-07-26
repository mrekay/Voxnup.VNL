<div class="d-flex flex-column flex-column-fluid">
    <div id="kt_app_content" class="app-content flex-column-fluid">
        <div id="kt_app_content_container" class="app-container container-xxl">

            <div class="text-center mb-15 mt-10">
                <h1 class="fs-2hx fw-bold mb-5 text-body-emphasis">Veritabanı Katmanı ve Modeller</h1>
                <div class="fs-5 text-muted fw-semibold w-lg-75 mx-auto">
                    VNL'de veritabanı işlemleri her zaman model sınıfları üzerinden yapılır. Her varlık için bir model sınıfı oluşturmak sistem bütünlüğünün temelidir; spagetti kodu önler ve tutarlı bir kod tabanı sağlar.
                </div>
            </div>

            <div class="alert bg-danger-subtle border border-danger d-flex align-items-center p-5 mb-10">
                <i class="fa-solid fa-circle-xmark fs-2hx text-danger me-4"></i>
                <div class="d-flex flex-column">
                    <h5 class="mb-1 text-danger">Capsule:: Doğrudan Kullanımı Yasak Gibidir</h5>
                    <span class="text-body"><code>Capsule::table('tbl_x')->...</code> çağrısı teknik olarak çalışsa da VNL felsefesi gereği <b>kesinlikle önerilmez</b>. Veritabanına erişen her işlem için bir model sınıfı oluşturun. Bu kural spagetti kodu engeller ve sistem bütünlüğünü korur. İstisna yoktur.</span>
                </div>
            </div>

            <div class="card shadow-sm border-0 mb-10">
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

    // Kesinlikle zorunlu — boş bırakılamaz
    protected static $columns = [
        'u.Id',
        'u.ad',
        'u.fiyat',
        'u.stok',
        'u.aktif',
        'k.ad as kategori_ad',
    ];

    public static function Select($filter)
    {
        return static::SelectList($filter, ['limit' => 1])->first();
    }

    public static function SelectList(array $filters = [], array $extras = [])
    {
        $query = static::from((new static)->tableName('u'))
            ->join(
                (new Kategori)->tableName('k'),
                'k.Id', '=', 'u.kategori_id'
            )
            ->select(static::$columns);

        return (new static)->selectListQuery(static::$columns, $filters, $extras, $query);
    }
}
</code></pre>
                        </div>

                        <h5 class="fw-bold text-body-emphasis mb-3">Statik Kullanım — <code>->get()</code> Zorunludur</h5>
                        <div class="alert bg-warning-subtle border border-warning d-flex align-items-center p-4 mb-4">
                            <i class="fa-solid fa-circle-info fs-2 text-warning me-3"></i>
                            <span class="text-body fs-6"><code>SelectList</code> bir query builder döndürür. Sonuçları almak için mutlaka <code>->get()</code> çağrılmalıdır. Bu kural olmadan veri gelmez.</span>
                        </div>

                        <div class="vnl-code mb-4">
                            <pre class=" mb-0" style="background: transparent; white-space: pre-wrap;"><code>// Tek kayıt — id ile
$urun = Urun::Select(['u.Id' => 42]);

// Filtrelenmiş liste — sayfalı — ->get() ZORUNLU
$liste = Urun::SelectList(
    ['u.ad' => ['value' => 'bisiklet', 'operator' => 'like']],
    ['limit' => 20, 'begin' => 0, 'order' => 'u.ad', 'order_by' => 'asc']
)->get();

// Sayım — sayfalama için
$filtreliSayisi = Urun::SelectList($filters, null)->count();
$toplamSayisi   = Urun::SelectList([], null)->count();

// Tüm kayıtlar (limit yok) — Excel export için
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
                            <pre class=" mb-0" style="background: transparent; white-space: pre-wrap;"><code>// Kısa biçim — eşitlik kontrolü
$filters = [
    'u.aktif' => 1,
    'u.ad'    => 'bisiklet',
];

// Uzun biçim — operatör belirtme
$filters = [
    'u.ad'    => ['value' => 'bisiklet', 'operator' => 'like'],
    'u.fiyat' => ['value' => '100|500',  'operator' => 'between'],
];</code></pre>
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
                                    <td>SQL <code>LIKE '%bisiklet%'</code> sorgusu üretir.</td>
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
                                    <td><code>10</code></td>
                                    <td>Sayfa başına kayıt sayısı. <code>-1</code> verilirse limit uygulanmaz (tüm kayıtlar).</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>begin</code></td>
                                    <td><code>0</code></td>
                                    <td>Başlangıç offset'i. DataTables'ın <code>start</code> parametresi ile eşleşir.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>order</code></td>
                                    <td><code>'Id'</code></td>
                                    <td>Sıralama yapılacak sütun adı.</td>
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

                        <div class="alert bg-info-subtle border border-info d-flex flex-column flex-sm-row p-5 mb-0">
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

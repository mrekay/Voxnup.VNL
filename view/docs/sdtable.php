<div class="d-flex flex-column flex-column-fluid">
    <div id="kt_app_content" class="app-content flex-column-fluid">
        <div id="kt_app_content_container" class="app-container container-xxl">

            <div class="text-center mb-15 mt-10">
                <h1 class="fs-2hx fw-bold mb-5 text-gray-900">SDTable — Sunucu Taraflı DataTables</h1>
                <div class="fs-5 text-muted fw-semibold w-lg-75 mx-auto">
                    <code>SDTable</code> sınıfı, jQuery DataTables için sunucu taraflı işleme (server-side processing) desteğiyle HTML tablo yapısını PHP tarafında üretir. View'da tek satır çağrısıyla tam işlevsel, AJAX destekli, filtrelenebilir ve sıralanabilir bir tablo oluşturursunuz.
                </div>
            </div>

            <div class="alert bg-light-primary border border-primary d-flex align-items-center p-5 mb-10">
                <i class="ki-duotone ki-abstract-26 fs-2hx text-primary me-4"><span class="path1"></span><span class="path2"></span></i>
                <div class="d-flex flex-column">
                    <h5 class="mb-1 text-primary">Nasıl Çalışır?</h5>
                    <span class="text-gray-700">PHP tarafı HTML yapısını ve DataTables konfigürasyonunu üretir. Sayfa yüklendiğinde JS DataTables'ı otomatik başlatır; her sayfalama, sıralama veya filtreleme işleminde <code>$url</code> parametresindeki action endpoint'ine AJAX isteği gönderilir.</span>
                </div>
            </div>

            <div class="card shadow-sm border-0 mb-10">
                <div class="card-body p-10">

                    <!--begin::Section 1-->
                    <div class="mb-10">
                        <h3 class="fw-bolder text-dark mb-4">1. SDTable::CreateDatable() Parametreleri</h3>
                        <p class="fs-5 text-gray-700 mb-4">
                            <code>SDTable::CreateDatable()</code> statik metodu beş parametre alır ve HTML string döndürür:
                        </p>

                        <div class="table-responsive border rounded bg-light mb-6">
                            <table class="table align-middle table-row-dashed fs-6 gy-4 mb-0">
                                <thead>
                                <tr class="text-start text-muted fw-bolder fs-7 text-uppercase gs-0 border-bottom">
                                    <th class="ps-5 min-w-150px">Parametre</th>
                                    <th class="min-w-100px">Tip</th>
                                    <th class="min-w-100px">Zorunlu</th>
                                    <th class="min-w-300px">Açıklama</th>
                                </tr>
                                </thead>
                                <tbody class="text-gray-800 fw-semibold">
                                <tr>
                                    <td class="ps-5"><code>$url</code></td>
                                    <td>string</td>
                                    <td><span class="badge badge-light-danger">Zorunlu</span></td>
                                    <td>AJAX isteklerinin gönderileceği action endpoint. Örn: <code>'app/kullanici/liste'</code></td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>$columns</code></td>
                                    <td>array</td>
                                    <td><span class="badge badge-light-danger">Zorunlu</span></td>
                                    <td>Tablo sütun tanımları dizisi. Her eleman bir sütunu temsil eder.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>$filters</code></td>
                                    <td>array</td>
                                    <td><span class="badge badge-light-warning">Önerilen</span></td>
                                    <td>Filtre satırı tanımları dizisi. Boş dizi geçilebilir.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>$options</code></td>
                                    <td>array</td>
                                    <td><span class="badge badge-light-danger">Zorunlu</span></td>
                                    <td>Tablo seçenekleri. En az <code>dt-id</code> anahtarı içermelidir.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>$passdata</code></td>
                                    <td>array</td>
                                    <td><span class="badge badge-light-success">Opsiyonel</span></td>
                                    <td>Her AJAX isteğiyle gönderilecek gizli veriler. Bağlam ID'leri için kullanılır.</td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <!--end::Section 1-->

                    <div class="separator separator-dashed my-8"></div>

                    <!--begin::Section 2-->
                    <div class="mb-10">
                        <h3 class="fw-bolder text-dark mb-4">2. <code>$columns</code> Dizisi</h3>
                        <p class="fs-5 text-gray-700 mb-4">
                            Her sütun bir ilişkisel dizi ile tanımlanır. İlk sütun (aksiyonlar) <code>SDTable</code> tarafından otomatik eklenir; onu tanımlamaya gerek yoktur.
                        </p>

                        <div class="table-responsive border rounded bg-light mb-5">
                            <table class="table align-middle table-row-dashed fs-6 gy-4 mb-0">
                                <thead>
                                <tr class="text-start text-muted fw-bolder fs-7 text-uppercase gs-0 border-bottom">
                                    <th class="ps-5 min-w-200px">Anahtar</th>
                                    <th class="min-w-100px">Zorunlu</th>
                                    <th class="min-w-300px">Açıklama</th>
                                </tr>
                                </thead>
                                <tbody class="text-gray-800 fw-semibold">
                                <tr>
                                    <td class="ps-5"><code>data-name</code></td>
                                    <td><span class="badge badge-light-danger">Zorunlu</span></td>
                                    <td>Action'ın döndürdüğü JSON'daki alan adı. DataTables bu key'e göre veriyi yerleştirir.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>data-visible-name</code></td>
                                    <td><span class="badge badge-light-danger">Zorunlu</span></td>
                                    <td>Tablonun başlık satırında (<code>&lt;th&gt;</code>) gösterilecek metin.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>data-orderable</code></td>
                                    <td><span class="badge badge-light-success">Opsiyonel</span></td>
                                    <td>Bu sütuna göre sıralama yapılabilsin mi? <code>1</code> = evet, <code>0</code> = hayır.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>data-visibilty</code></td>
                                    <td><span class="badge badge-light-success">Opsiyonel</span></td>
                                    <td>Sütun görünür mü? <code>1</code> = görünür, <code>0</code> = gizli.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>class</code></td>
                                    <td><span class="badge badge-light-success">Opsiyonel</span></td>
                                    <td>Sütun hücresine uygulanacak CSS sınıfları.</td>
                                </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="bg-dark rounded p-5 mb-4">
                            <pre class="text-white fs-6 font-monospace mb-0" style="background: transparent; white-space: pre-wrap;"><code>$columns = [
    [
        'data-name'         => 'ad',
        'data-visible-name' => 'Ad Soyad',
        'data-orderable'    => 1,
        'data-visibilty'    => 1,
        'class'             => '',
    ],
    [
        'data-name'         => 'email',
        'data-visible-name' => 'E-posta',
        'data-orderable'    => 1,
        'data-visibilty'    => 1,
        'class'             => '',
    ],
    [
        'data-name'         => 'telefon',
        'data-visible-name' => 'Telefon',
        'data-orderable'    => 0,
        'data-visibilty'    => 1,
        'class'             => 'text-center',
    ],
    [
        'data-name'         => 'aktif',
        'data-visible-name' => 'Durum',
        'data-orderable'    => 1,
        'data-visibilty'    => 1,
        'class'             => 'text-center',
    ],
];
</code></pre>
                        </div>
                    </div>
                    <!--end::Section 2-->

                    <div class="separator separator-dashed my-8"></div>

                    <!--begin::Section 3-->
                    <div class="mb-10">
                        <h3 class="fw-bolder text-dark mb-4">3. <code>$filters</code> Dizisi</h3>
                        <p class="fs-5 text-gray-700 mb-4">
                            Filtre satırı, tablonun üstünde gösterilen arama alanlarını tanımlar. Her filtre bir input veya select olabilir.
                        </p>

                        <div class="table-responsive border rounded bg-light mb-5">
                            <table class="table align-middle table-row-dashed fs-6 gy-4 mb-0">
                                <thead>
                                <tr class="text-start text-muted fw-bolder fs-7 text-uppercase gs-0 border-bottom">
                                    <th class="ps-5 min-w-160px">Anahtar</th>
                                    <th class="min-w-100px">Zorunlu</th>
                                    <th class="min-w-300px">Açıklama</th>
                                </tr>
                                </thead>
                                <tbody class="text-gray-800 fw-semibold">
                                <tr>
                                    <td class="ps-5"><code>filter_type</code></td>
                                    <td><span class="badge badge-light-danger">Zorunlu</span></td>
                                    <td><code>'input'</code> veya <code>'select'</code>. Filtrenin tipini belirler.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>name</code></td>
                                    <td><span class="badge badge-light-danger">Zorunlu</span></td>
                                    <td>HTML <code>name</code> niteliği. Action'da <code>$req->post('ad-filter')</code> biçiminde okunur (<code>-filter</code> eklenir).</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>placeholder</code></td>
                                    <td><span class="badge badge-light-success">Opsiyonel</span></td>
                                    <td>Input alanı için placeholder metni.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>class</code></td>
                                    <td><span class="badge badge-light-success">Opsiyonel</span></td>
                                    <td>Ek CSS sınıfları.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>id</code></td>
                                    <td><span class="badge badge-light-success">Opsiyonel</span></td>
                                    <td>HTML <code>id</code> niteliği. JS ile erişim için kullanılır.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>options</code></td>
                                    <td><span class="badge badge-light-warning">Select için</span></td>
                                    <td>Select tipi filtreler için seçenek listesi: <code>[['value' => '1', 'text' => 'Aktif']]</code></td>
                                </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="bg-dark rounded p-5 mb-4">
                            <pre class="text-white fs-6 font-monospace mb-0" style="background: transparent; white-space: pre-wrap;"><code>$filters = [
    // Text input filtresi
    [
        'filter_type' => 'input',
        'name'        => 'ad',
        'placeholder' => 'Ad Soyad ara...',
        'class'       => '',
        'id'          => 'filter_ad',
    ],
    // Select filtresi
    [
        'filter_type' => 'select',
        'name'        => 'aktif',
        'class'       => '',
        'id'          => 'filter_aktif',
        'options'     => [
            ['value' => '',  'text' => 'Tümü'],
            ['value' => '1', 'text' => 'Aktif'],
            ['value' => '0', 'text' => 'Pasif'],
        ],
    ],
];
</code></pre>
                        </div>
                    </div>
                    <!--end::Section 3-->

                    <div class="separator separator-dashed my-8"></div>

                    <!--begin::Section 4-->
                    <div class="mb-10">
                        <h3 class="fw-bolder text-dark mb-4">4. <code>$options</code> ve <code>$passdata</code></h3>

                        <div class="row g-5 mb-5">
                            <div class="col-md-6">
                                <h5 class="fw-bold text-dark mb-3"><code>$options</code> Dizisi</h5>
                                <div class="table-responsive border rounded bg-light mb-4">
                                    <table class="table align-middle table-row-dashed fs-6 gy-3 mb-0">
                                        <thead>
                                        <tr class="text-start text-muted fw-bolder fs-7 text-uppercase gs-0 border-bottom">
                                            <th class="ps-4 min-w-120px">Anahtar</th>
                                            <th class="min-w-200px">Açıklama</th>
                                        </tr>
                                        </thead>
                                        <tbody class="text-gray-800 fw-semibold">
                                        <tr>
                                            <td class="ps-4"><code>dt-id</code></td>
                                            <td>Tablonun HTML <code>id</code>'si. <span class="badge badge-light-danger">Zorunlu</span></td>
                                        </tr>
                                        <tr>
                                            <td class="ps-4"><code>dt-class</code></td>
                                            <td>Tabloya eklenecek CSS sınıfları.</td>
                                        </tr>
                                        <tr>
                                            <td class="ps-4"><code>width</code></td>
                                            <td>Tablo genişliği. Örn: <code>'100%'</code></td>
                                        </tr>
                                        <tr>
                                            <td class="ps-4"><code>cellspacing</code></td>
                                            <td>Hücre aralığı değeri.</td>
                                        </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="bg-dark rounded p-4">
                                    <pre class="text-white fs-7 font-monospace mb-0" style="background: transparent; white-space: pre-wrap;"><code>$options = [
    'dt-id'      => 'tablo_kullanici',
    'dt-class'   => 'table table-row-dashed',
    'width'      => '100%',
    'cellspacing' => '0',
];</code></pre>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <h5 class="fw-bold text-dark mb-3"><code>$passdata</code> Dizisi</h5>
                                <p class="text-gray-700 fs-6 mb-3">Her AJAX isteğiyle birlikte gönderilecek gizli veri alanlarıdır. Örneğin bir proje ID'sini her istekte action'a iletmek için kullanılır.</p>
                                <div class="bg-dark rounded p-4">
                                    <pre class="text-white fs-7 font-monospace mb-0" style="background: transparent; white-space: pre-wrap;"><code>$passdata = [
    [
        'id'    => 'project_id',
        'name'  => 'project_id',
        'value' => $proje->id,
    ],
    [
        'id'    => 'departman_id',
        'name'  => 'departman_id',
        'value' => $departman->id,
    ],
];</code></pre>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!--end::Section 4-->

                    <div class="separator separator-dashed my-8"></div>

                    <!--begin::Section 5-->
                    <div class="mb-10">
                        <h3 class="fw-bolder text-dark mb-4">5. View'da Kullanım</h3>
                        <p class="fs-5 text-gray-700 mb-4">
                            <code>SDTable::CreateDatable()</code> HTML string döndürür. <code>echo</code> ile view içine yazdırın.
                        </p>

                        <div class="bg-dark rounded p-5 mb-4">
                            <pre class="text-white fs-6 font-monospace mb-0" style="background: transparent; white-space: pre-wrap;"><code>&lt;?php
// view/kullanici/liste.php

$columns = [
    ['data-name' => 'ad',    'data-visible-name' => 'Ad Soyad', 'data-orderable' => 1, 'data-visibilty' => 1, 'class' => ''],
    ['data-name' => 'email', 'data-visible-name' => 'E-posta',  'data-orderable' => 1, 'data-visibilty' => 1, 'class' => ''],
    ['data-name' => 'aktif', 'data-visible-name' => 'Durum',    'data-orderable' => 1, 'data-visibilty' => 1, 'class' => 'text-center'],
];

$filters = [
    ['filter_type' => 'input',  'name' => 'ad',    'placeholder' => 'Ad ara...', 'id' => 'filter_ad'],
    ['filter_type' => 'select', 'name' => 'aktif', 'id' => 'filter_aktif', 'options' => [
        ['value' => '',  'text' => 'Tümü'],
        ['value' => '1', 'text' => 'Aktif'],
        ['value' => '0', 'text' => 'Pasif'],
    ]],
];

$options  = ['dt-id' => 'tablo_kullanici', 'dt-class' => 'table table-row-dashed'];
$passdata = [];

echo SDTable::CreateDatable('app/kullanici/getList', $columns, $filters, $options, $passdata);
?&gt;
</code></pre>
                        </div>
                    </div>
                    <!--end::Section 5-->

                    <div class="separator separator-dashed my-8"></div>

                    <!--begin::Section 6-->
                    <div class="mb-10">
                        <h3 class="fw-bolder text-dark mb-4">6. Action Tarafı — <code>getList</code></h3>
                        <p class="fs-5 text-gray-700 mb-4">
                            DataTables verisi çeken action metodu felsefe gereği <b>her zaman <code>getList</code></b> olarak adlandırılır. DataTables parametreleri (<code>columns</code>, <code>order</code>, <code>start</code>, <code>length</code>) standart biçimde okunur; model <b>statik olarak</b> çağrılır ve sonuna <b>mutlaka <code>->get()</code></b> eklenir.
                        </p>

                        <div class="bg-dark rounded p-5 mb-5">
                            <pre class="text-white fs-6 font-monospace mb-0" style="background: transparent; white-space: pre-wrap;"><code>&lt;?php
// actions/action_kullanici.php

use ActionGlobal\Auth;

class action_kullanici
{
    public function getList($req)
    {
        Auth::CheckLogin();

        $page_length = intval($req->post('length'));
        $page_start  = $req->post('start');
        $columns     = $req->post('columns');
        $orders      = $req->post('order');

        $order_by = 'InsertDate';
        $order    = 'Desc';
        if (!empty($orders)) {
            $order_by = $columns[intval($orders[0]['column'])]['name'];
            $order    = $orders[0]['dir'];
        }

        $extras = [
            'begin'    => $page_start,
            'limit'    => intval($page_length),
            'order'    => $order_by,
            'order_by' => $order,
        ];

        // Column arama değerlerinden filtre oluştur
        $filters = [];
        foreach ($columns as $col) {
            if (isset($col['search']['value']) && $col['search']['value'] != null) {
                $filters[$col['name']] = $col['search']['value'];
            }
        }

        // Model statik çağrılır, ->get() zorunlu
        $liste         = Kullanici::SelectList($filters, $extras)->get();
        $filter_count  = Kullanici::SelectList($filters, null)->count();
        $total_count   = Kullanici::SelectList(null, null)->count();

        $data = [];
        $i = $page_start + 1;
        foreach ($liste as $k) {
            $data[] = [
                'no'      => '&lt;span class="ps-10"&gt;' . $i . '&lt;/span&gt; &lt;input type="hidden" class="row_id" value="' . $k->Id . '"&gt;',
                'ad'      => htmlspecialchars($k->Name . ' ' . $k->Surname),
                'email'   => htmlspecialchars($k->Email),
                'aktif'   => $k->IsActive
                    ? '&lt;span class="badge badge-light-success"&gt;Aktif&lt;/span&gt;'
                    : '&lt;span class="badge badge-light-danger"&gt;Pasif&lt;/span&gt;',
            ];
            $i++;
        }

        returnSuccessDataOnly([
            'recordsTotal'    => $total_count,
            'recordsFiltered' => $filter_count,
            'data'            => $data,
        ]);
    }
}
</code></pre>
                        </div>

                        <div class="alert bg-light-info border border-info d-flex align-items-center p-4 mb-5">
                            <i class="ki-duotone ki-information-5 fs-2 text-info me-3"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
                            <span class="text-gray-800 fs-6">Sistem hata durumunda HTTP 400 döner. AJAX tarafında yanıt <code>response.result</code> gibi alanlarla değil, jQuery'nin <code>success</code> ve <code>error</code> callback'leriyle yakalanır.</span>
                        </div>

                        <h5 class="fw-bold text-dark mb-3">JS Tarafı — AJAX Örneği</h5>
                        <div class="bg-dark rounded p-5">
                            <pre class="text-white fs-6 font-monospace mb-0" style="background: transparent; white-space: pre-wrap;"><code>$.ajax({
    type: 'POST',
    url: '/app/kullanici/kaydet',
    data: {
        ad:    $('#inp_ad').val(),
        email: $('#inp_email').val(),
    },
    success: function (response) {
        // HTTP 200 — işlem başarılı
        $('#modal_kullanici').modal('hide');
        toastr.success(response.message ?? 'Kaydedildi.');
    },
    error: function (xhr) {
        // HTTP 400 — hata (validation, exception vb.)
        toastr.error(xhr.responseJSON?.message ?? 'Bir hata oluştu.');
    }
});
</code></pre>
                        </div>
                    </div>
                    <!--end::Section 6-->

                    <div class="separator separator-dashed my-8"></div>

                    <!--begin::Section 7-->
                    <div class="mb-10">
                        <h3 class="fw-bolder text-dark mb-4">7. Excel Export</h3>
                        <p class="fs-5 text-gray-700 mb-4">
                            SDTable, yerleşik Excel export desteği sunar. Tablo başlığındaki export butonu, mevcut filtre durumunu koruyarak <code>datatableToExcel.php</code> dosyasına istek gönderir; tüm kayıtlar (limit=-1) action'dan alınır ve Excel dosyası oluşturulur.
                        </p>

                        <div class="alert bg-light-primary border border-primary d-flex align-items-center p-4">
                            <i class="ki-duotone ki-file-down fs-2 text-primary me-3"><span class="path1"></span><span class="path2"></span></i>
                            <span class="text-gray-800 fs-6">Excel export detayları ve manuel Excel oluşturma için <a href="/docs/excel-export" class="text-primary fw-bold">Excel Export</a> sayfasına bakın.</span>
                        </div>
                    </div>
                    <!--end::Section 7-->

                    <div class="separator separator-dashed my-8"></div>

                    <!--begin::Section 8 — Tam örnek-->
                    <div class="mb-0">
                        <h3 class="fw-bolder text-dark mb-4">8. Tam Örnek — Controller + View + Action</h3>

                        <h5 class="fw-bold text-dark mb-3">Controller (<code>controller/kullanici.php</code>)</h5>
                        <div class="bg-dark rounded p-5 mb-5">
                            <pre class="text-white fs-6 font-monospace mb-0" style="background: transparent; white-space: pre-wrap;"><code>&lt;?php
$meta = [
    'title'       => 'Kullanıcı Listesi',
    'upper_title' => 'Yönetim',
];

$page_config = [
    'topbar'        => true,
    'leftsidebar'   => true,
    'contentheader' => true,
    'toolbaritems'  => [
        [
            'type'   => 'button',
            'text'   => 'Yeni Kullanıcı',
            'class'  => 'btn btn-primary btn-sm',
            'icon'   => 'ki-duotone ki-plus',
            'custom' => 'data-bs-toggle="modal" data-bs-target="#modal_kullanici_ekle"',
        ],
    ],
];

require view('static/header');
require view('kullanici/liste');
require modal('KullaniciEkle', 'kullanici');
require view('static/footer');
</code></pre>
                        </div>

                        <h5 class="fw-bold text-dark mb-3">View (<code>view/kullanici/liste.php</code>)</h5>
                        <div class="bg-dark rounded p-5 mb-5">
                            <pre class="text-white fs-6 font-monospace mb-0" style="background: transparent; white-space: pre-wrap;"><code>&lt;?php
$columns = [
    ['data-name' => 'ad',     'data-visible-name' => 'Ad Soyad',  'data-orderable' => 1, 'data-visibilty' => 1, 'class' => ''],
    ['data-name' => 'email',  'data-visible-name' => 'E-posta',   'data-orderable' => 1, 'data-visibilty' => 1, 'class' => ''],
    ['data-name' => 'rol',    'data-visible-name' => 'Rol',       'data-orderable' => 1, 'data-visibilty' => 1, 'class' => 'text-center'],
    ['data-name' => 'aktif',  'data-visible-name' => 'Durum',     'data-orderable' => 1, 'data-visibilty' => 1, 'class' => 'text-center'],
];

$filters = [
    ['filter_type' => 'input',  'name' => 'ad',    'placeholder' => 'Ad Soyad...', 'id' => 'f_ad'],
    ['filter_type' => 'input',  'name' => 'email', 'placeholder' => 'E-posta...',  'id' => 'f_email'],
    ['filter_type' => 'select', 'name' => 'aktif', 'id' => 'f_aktif', 'options' => [
        ['value' => '',  'text' => 'Tümü'],
        ['value' => '1', 'text' => 'Aktif'],
        ['value' => '0', 'text' => 'Pasif'],
    ]],
];

$options  = ['dt-id' => 'dt_kullanici', 'dt-class' => 'table table-row-dashed align-middle'];
$passdata = [];

echo SDTable::CreateDatable('app/kullanici/getList', $columns, $filters, $options, $passdata);
?&gt;
</code></pre>
                        </div>
                    </div>
                    <!--end::Section 8-->

                </div>
            </div>

        </div>
    </div>
</div>

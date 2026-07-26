<div class="d-flex flex-column flex-column-fluid">
    <div id="kt_app_content" class="app-content flex-column-fluid">
        <div id="kt_app_content_container" class="app-container container-xxl">

            <div class="vnl-page-hero mb-12 mt-6">
                <div class="vnl-overline mb-3">// docs/extensions</div>
                <h1 class="fs-2hx fw-bold mb-4 text-body-emphasis">Extension Sistemi</h1>
                <div class="vnl-lead">VNL, <code>extensions/</code> klasörü altında bağımsız eklentiler destekler. Her eklenti kendi controller, action, view ve kaynak dosyalarını barındırır; VNL core'una tek satır dokunmadan çalışır.</div>
            </div>

            <!--begin::Nasıl Çalışır-->
            <div class="card shadow-sm border-0 mb-10">
                <div class="card-header border-0 pt-8">
                    <h2 class="card-title fw-bold text-body-emphasis">Nasıl Çalışır?</h2>
                </div>
                <div class="card-body pt-2">
                    <p class="fs-5 text-body mb-6">
                        VNL başlangıcında (<code>libs/config.php</code>) aşağıdaki glob çalışır ve bulunan tüm <code>boot.php</code> dosyaları sırayla yüklenir:
                    </p>
                    <div class="vnl-code mb-8">
                        <pre class="mb-0" style="background:transparent; white-space:pre-wrap;"><code>// libs/config.php
foreach (glob(getDocumentRoot() . 'extensions/*/boot.php') as $_extBoot) {
    require_once $_extBoot;
}
</code></pre>
                    </div>

                    <p class="fs-5 text-body mb-6">
                        Action ve controller routing'i de extension klasörlerine fallback yapar:
                    </p>
                    <div class="vnl-code mb-8">
                        <pre class="mb-0" style="background:transparent; white-space:pre-wrap;"><code>// controller/app.php — action fallback
if (!file_exists($action_path)) {
    foreach (glob(getDocumentRoot() . 'extensions/*/actions/action_' . route(1) . '.php') as $_extAction) {
        $action_path = $_extAction;
        break;
    }
}

// index.php — controller fallback
foreach (glob(getPath() . '/extensions/*/controller/' . route(0) . '.php') as $_extCtrl) {
    $controllerPath = $_extCtrl;
    break;
}
</code></pre>
                    </div>
                </div>
            </div>
            <!--end::Nasıl Çalışır-->

            <!--begin::Klasör Yapısı-->
            <div class="card shadow-sm border-0 mb-10">
                <div class="card-header border-0 pt-8">
                    <h2 class="card-title fw-bold text-body-emphasis">Standart Klasör Yapısı</h2>
                </div>
                <div class="card-body pt-2">
                    <p class="fs-5 text-body mb-5">Her eklenti <code>extensions/{eklenti-adi}/</code> altında şu yapıyı takip eder:</p>
                    <div class="vnl-code mb-8">
                        <pre class="mb-0" style="background:transparent; white-space:pre-wrap;"><code>extensions/
└── my-extension/
    ├── boot.php            ← Otomatik yüklenir. Autoloader, config, sınıf yükleme buraya.
    ├── config.php          ← Eklentiye özgü ayarlar (kullanıcı düzenler)
    ├── src/                ← PHP sınıfları (model, modül vb.)
    │   ├── MyModel.php
    │   └── MyModule.php
    ├── actions/
    │   └── action_myext.php    ← /app/myext/* endpoint'leri
    ├── controller/
    │   ├── my-page.php         ← /my-page rotası
    │   └── my-other-page.php
    ├── view/
    │   ├── my-page.php
    │   └── my-other-page.php
    └── setup.sql           ← Gerekli DB tablolarını içerir
</code></pre>
                    </div>
                </div>
            </div>
            <!--end::Klasör Yapısı-->

            <!--begin::boot.php-->
            <div class="card shadow-sm border-0 mb-10">
                <div class="card-header border-0 pt-8">
                    <h2 class="card-title fw-bold text-body-emphasis">boot.php</h2>
                </div>
                <div class="card-body pt-2">
                    <p class="fs-5 text-body mb-5">
                        <code>boot.php</code>, VNL'in config pipeline'ı tamamlandıktan sonra çalışır. Bu noktada Eloquent, Composer autoloader ve tüm VNL yardımcıları kullanılabilir durumdadır.
                    </p>
                    <div class="vnl-code mb-8">
                        <pre class="mb-0" style="background:transparent; white-space:pre-wrap;"><code>&lt;?php

defined('MY_EXT_PATH') || define('MY_EXT_PATH', __DIR__);

// 1. Config yükle
$_config = require MY_EXT_PATH . '/config.php';
define('MY_EXT_SETTING', $_config['some_setting']);
unset($_config);

// 2. Sınıfları yükle
require_once MY_EXT_PATH . '/src/MyModule.php';
require_once MY_EXT_PATH . '/src/MyModel.php';
</code></pre>
                    </div>

                    <div class="alert bg-info-subtle border border-info d-flex align-items-center p-5 mb-5">
                        <i class="fa-solid fa-circle-info fs-2hx text-info me-4"></i>
                        <div>
                            <strong>Önemli:</strong> <code>boot.php</code>, <code>config_db.php</code>'den sonra çalışır.
                            Eloquent modeller, <code>$CurrentUser</code> ve tüm VNL modülleri bu noktada hazırdır.
                        </div>
                    </div>
                </div>
            </div>
            <!--end::boot.php-->

            <!--begin::Controller & View-->
            <div class="card shadow-sm border-0 mb-10">
                <div class="card-header border-0 pt-8">
                    <h2 class="card-title fw-bold text-body-emphasis">Controller ve View</h2>
                </div>
                <div class="card-body pt-2">
                    <p class="fs-5 text-body mb-5">
                        Extension controller'ları standart VNL controller'larıyla özdeştir. VNL layout bileşenleri (<code>view('static/header')</code> vb.) kullanılabilir, ancak sayfa içeriği için extension'ın kendi view klasörü kullanılır.
                    </p>
                    <div class="vnl-code mb-8">
                        <pre class="mb-0" style="background:transparent; white-space:pre-wrap;"><code>&lt;?php
// extensions/my-extension/controller/my-page.php

$meta = [
    'title'       => 'Sayfa Başlığı',
    'upper_title' => 'Eklenti Adı',
];

$page_config = [
    'topbar'      => true,
    'leftsidebar' => true,
];

require view('static/header');                              // VNL layout
require MY_EXT_PATH . '/view/my-page.php';                  // Extension view
require view('static/footer');                              // VNL layout
</code></pre>
                    </div>

                    <div class="alert bg-warning-subtle border border-warning d-flex align-items-center p-5">
                        <i class="fa-solid fa-circle-info fs-2hx text-warning me-4"></i>
                        <div>
                            <strong>Public sayfalar için</strong> (auth gerektirmeyen, örn. webhook):
                            <code>$page_config = ['public' => true];</code> ayarlanmalıdır.
                            Login except listesine eklemeye gerek yoktur.
                        </div>
                    </div>
                </div>
            </div>
            <!--end::Controller & View-->

            <!--begin::Action-->
            <div class="card shadow-sm border-0 mb-10">
                <div class="card-header border-0 pt-8">
                    <h2 class="card-title fw-bold text-body-emphasis">Action (AJAX Endpoint)</h2>
                </div>
                <div class="card-body pt-2">
                    <p class="fs-5 text-body mb-5">
                        Action dosyaları standart VNL action'larıyla aynı kurallara uyar. <code>/app/{module}/{method}</code> pattern'ı extension action'larında da çalışır.
                    </p>
                    <div class="vnl-code mb-8">
                        <pre class="mb-0" style="background:transparent; white-space:pre-wrap;"><code>&lt;?php
// extensions/my-extension/actions/action_myext.php

use ActionGlobal\Auth;

class action_myext
{
    public function getData($req)
    {
        Auth::CheckLogin();
        // ...
        returnSuccessDataOnly(['items' => $data]);
    }
}

// JavaScript:
// $.post('/app/myext/getData', {}, res => console.log(res.data));
</code></pre>
                    </div>
                </div>
            </div>
            <!--end::Action-->

            <!--begin::Model-->
            <div class="card shadow-sm border-0 mb-10">
                <div class="card-header border-0 pt-8">
                    <h2 class="card-title fw-bold text-body-emphasis">Model (Eloquent)</h2>
                </div>
                <div class="card-body pt-2">
                    <p class="fs-5 text-body mb-5">
                        Extension modelleri <code>TableModel</code>'i extend eder; Eloquent'in tüm özellikleri ve VNL'in <code>selectListQuery</code> yardımcısı kullanılabilir.
                    </p>
                    <div class="vnl-code mb-8">
                        <pre class="mb-0" style="background:transparent; white-space:pre-wrap;"><code>&lt;?php
// extensions/my-extension/src/MyModel.php

use Illuminate\Database\Eloquent\SoftDeletes;

class MyModel extends TableModel
{
    use SoftDeletes;

    public $timestamps = false;
    protected $table   = 'tbl_my_records';

    protected static $columns = [
        'UserId'     => ['name' => 'UserId'],
        'Status'     => ['name' => 'Status'],
        'InsertDate' => ['name' => 'InsertDate'],
    ];

    public static function SelectList($filters = [], $extras = [])
    {
        return self::selectListQuery(self::$columns, $filters, $extras);
    }
}
</code></pre>
                    </div>
                </div>
            </div>
            <!--end::Model-->

            <!--begin::Kurulum Adımları-->
            <div class="card shadow-sm border-0 mb-10">
                <div class="card-header border-0 pt-8">
                    <h2 class="card-title fw-bold text-body-emphasis">Eklenti Kurulum Adımları</h2>
                </div>
                <div class="card-body pt-2">
                    <div class="d-flex flex-column gap-5">
                        <div class="d-flex align-items-start">
                            <span class="badge badge-circle badge-primary me-4 mt-1 fs-6 flex-shrink-0">1</span>
                            <div>
                                <div class="fw-bold text-body mb-1">Klasörü yerleştir</div>
                                <div class="text-body-secondary fs-6"><code>extensions/eklenti-adi/</code> klasörünü projeye ekle.</div>
                            </div>
                        </div>
                        <div class="d-flex align-items-start">
                            <span class="badge badge-circle badge-primary me-4 mt-1 fs-6 flex-shrink-0">2</span>
                            <div>
                                <div class="fw-bold text-body mb-1">Config'i düzenle</div>
                                <div class="text-body-secondary fs-6">Eklentinin <code>config.php</code> dosyasındaki ayarları doldur.</div>
                            </div>
                        </div>
                        <div class="d-flex align-items-start">
                            <span class="badge badge-circle badge-primary me-4 mt-1 fs-6 flex-shrink-0">3</span>
                            <div>
                                <div class="fw-bold text-body mb-1">Gerekli bağımlılıkları yükle</div>
                                <div class="text-body-secondary fs-6">Eklentinin talep ettiği Composer paketlerini <code>libs/</code> altında kur. Örn: <code>composer require stripe/stripe-php</code></div>
                            </div>
                        </div>
                        <div class="d-flex align-items-start">
                            <span class="badge badge-circle badge-primary me-4 mt-1 fs-6 flex-shrink-0">4</span>
                            <div>
                                <div class="fw-bold text-body mb-1">DB tablolarını oluştur</div>
                                <div class="text-body-secondary fs-6">Eklentinin <code>setup.sql</code> dosyasını veritabanında çalıştır.</div>
                            </div>
                        </div>
                        <div class="d-flex align-items-start">
                            <span class="badge badge-circle badge-success me-4 mt-1 fs-6 flex-shrink-0">✓</span>
                            <div>
                                <div class="fw-bold text-body mb-1">Hazır</div>
                                <div class="text-body-secondary fs-6">VNL bir sonraki istekte <code>boot.php</code>'yi otomatik yükler. Başka bir şeye gerek yok.</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!--end::Kurulum Adımları-->

            <!--begin::Kaldırma-->
            <div class="card shadow-sm border-0 mb-10">
                <div class="card-header border-0 pt-8">
                    <h2 class="card-title fw-bold text-body-emphasis">Eklenti Kaldırma</h2>
                </div>
                <div class="card-body pt-2">
                    <p class="fs-5 text-body mb-5">
                        Eklentiyi devre dışı bırakmak için <code>extensions/{eklenti-adi}/</code> klasörünü silmek veya yeniden adlandırmak yeterlidir. VNL core'una hiçbir şey yazılmadığından temizlik yapmaya gerek yoktur.
                    </p>
                    <div class="alert bg-danger-subtle border border-danger d-flex align-items-center p-5">
                        <i class="fa-solid fa-circle-info fs-2hx text-danger me-4"></i>
                        <div>Eklenti kaldırılmadan önce DB tablolarını ve Composer bağımlılıklarını manuel olarak temizlemeyi unutmayın.</div>
                    </div>
                </div>
            </div>
            <!--end::Kaldırma-->

        </div>
    </div>
</div>

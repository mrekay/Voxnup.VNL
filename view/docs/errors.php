<div class="d-flex flex-column flex-column-fluid">
    <div id="kt_app_content" class="app-content flex-column-fluid">
        <div id="kt_app_content_container" class="app-container container-xxl">

            <div class="vnl-page-hero mb-12 mt-6">
                <div class="vnl-overline mb-3">// docs/errors</div>
                <h1 class="fs-2hx fw-bold mb-4 text-body-emphasis">Hata Yönetimi</h1>
                <div class="vnl-lead">VNL'de hiçbir hata sessizce kaybolmaz. Yakalanmamış istisnalar ve ölümcül hatalar tek bir kapıya — <code>vnl_render_error()</code>'a — düşer; istek türüne göre ayrıntılı bir HTML hata sayfası ya da JSON yanıtı üretilir.</div>
            </div>

            <div class="alert alert-primary d-flex align-items-center p-5 mb-10">
                <i class="fa-solid fa-triangle-exclamation fs-2hx text-primary me-4"></i>
                <div class="d-flex flex-column text-primary">
                    <h4 class="mb-1 text-primary">İki Tür Hata</h4>
                    <span><b>Beklenen hatalar</b> (boş alan, yetkisiz erişim, bulunamayan kayıt) exception sınıflarıyla yönetilir ve ön yüze düzgün bir mesaj döner. <b>Beklenmeyen hatalar</b> (tanımsız metot, sözdizimi hatası, veritabanı bağlantı sorunu) hata sayfasına düşer ve geliştiriciye tam bağlamı gösterir.</span>
                </div>
            </div>

            <div class="card mb-10">
                <div class="card-body p-10">

                    <!--begin::Section 1-->
                    <div class="mb-10">
                        <h3 class="fw-bold text-body-emphasis mb-4">1. Yakalama Katmanları</h3>
                        <p class="fs-5 text-body mb-5">
                            <code>vnl/boot.php</code> her şeyden önce <code>vnl/error.php</code>'yi yükler — böylece bootstrap sırasında oluşan hatalar bile yakalanır. Üç ayrı ağ kurulur:
                        </p>

                        <div class="table-responsive border rounded bg-body-tertiary mb-6">
                            <table class="table align-middle table-row-dashed fs-6 gy-4 mb-0">
                                <thead>
                                <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0 border-bottom">
                                    <th class="ps-5 min-w-250px">Katman</th>
                                    <th class="min-w-200px">Yakaladığı</th>
                                    <th class="min-w-300px">Açıklama</th>
                                </tr>
                                </thead>
                                <tbody class="text-body fw-semibold">
                                <tr>
                                    <td class="ps-5"><code>set_exception_handler</code></td>
                                    <td><span class="badge badge-soft-primary">Throwable</span></td>
                                    <td>Kodun herhangi bir yerinden fırlatılan ve hiçbir <code>catch</code> tarafından tutulmayan tüm istisnalar.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>register_shutdown_function</code></td>
                                    <td><span class="badge badge-soft-danger">Fatal</span></td>
                                    <td><code>E_ERROR</code>, <code>E_PARSE</code>, <code>E_COMPILE_ERROR</code> — bunlar exception handler'a düşmez; PHP kapanırken yakalanır. Tanımsız metot ve sınıf hataları buraya girer.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>index.php</code> / <code>controller/app.php</code></td>
                                    <td><span class="badge badge-soft-warning">Controller & Action</span></td>
                                    <td>Controller <code>try/catch</code> içinde çalıştırılır. Action tarafında <code>app.php</code> istisnayı JSON'a çevirir.</td>
                                </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="alert alert-success d-flex align-items-center p-4">
                            <i class="fa-solid fa-circle-check fs-2 text-success me-3"></i>
                            <span class="text-body fs-6">PHP'nin kendi "Fatal error" metnini bastığı durumlar için <code>vnl/boot.php</code> en başta <code>ob_start()</code> çağırır; hata sayfası çıktı tamponunu temizleyerek devralır. Böylece JSON yanıtının başına PHP uyarısı karışmaz.</span>
                        </div>
                    </div>
                    <!--end::Section 1-->

                    <div class="separator separator-dashed my-8"></div>

                    <!--begin::Section 2-->
                    <div class="mb-10">
                        <h3 class="fw-bold text-body-emphasis mb-4">2. HTML mi, JSON mu?</h3>
                        <p class="fs-5 text-body mb-5">
                            Bir AJAX çağrısına HTML hata sayfası dönerse ön yüz sessizce takılır. <code>vnl_wants_json()</code> isteğin ne beklediğini şu sırayla belirler:
                        </p>

                        <div class="table-responsive border rounded bg-body-tertiary mb-6">
                            <table class="table align-middle table-row-dashed fs-6 gy-4 mb-0">
                                <thead>
                                <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0 border-bottom">
                                    <th class="ps-5 min-w-100px">Sıra</th>
                                    <th class="min-w-300px">Koşul</th>
                                    <th class="min-w-250px">Kim gönderir</th>
                                </tr>
                                </thead>
                                <tbody class="text-body fw-semibold">
                                <tr>
                                    <td class="ps-5">1</td>
                                    <td><code>X-Requested-With: XMLHttpRequest</code></td>
                                    <td>jQuery <code>$.ajax</code>, DataTables — VNL'in tüm AJAX çağrıları</td>
                                </tr>
                                <tr>
                                    <td class="ps-5">2</td>
                                    <td><code>Sec-Fetch-Dest: empty</code></td>
                                    <td>Modern tarayıcılarda <code>fetch()</code> ve XHR (sayfa gezinmesi <code>document</code> gönderir)</td>
                                </tr>
                                <tr>
                                    <td class="ps-5">3</td>
                                    <td><code>Accept</code> JSON içerip <code>text/html</code> içermiyorsa</td>
                                    <td>Harici API istemcileri, Postman, curl</td>
                                </tr>
                                <tr>
                                    <td class="ps-5">—</td>
                                    <td>Hiçbiri eşleşmezse</td>
                                    <td><span class="badge badge-soft-primary">HTML hata sayfası</span></td>
                                </tr>
                                </tbody>
                            </table>
                        </div>

                        <h5 class="fw-bold text-body-emphasis mb-3">JSON hata biçimi</h5>
                        <p class="fs-6 text-body mb-3">HTTP durumu <code>500</code> döner. Gövde, ön yüzün beklediği <code>message</code> alanını her zaman içerir:</p>

                        <div class="vnl-code mb-5">
                            <pre class="mb-0" style="background: transparent; white-space: pre-wrap;"><code>{
  "message": "Error: Call to undefined method User::SetOnlineToLastActivity()",
  "devcode": "VNL_FATAL",
  "error": {
    "type":  "Error",
    "text":  "Call to undefined method ...",
    "file":  "/vnl/modules/UserModule.php",
    "line":  21,
    "trace": [
      { "file": "/config/database.php", "line": 16, "function": "UserModule::SetCurrentUserInfo" }
    ]
  }
}</code></pre>
                        </div>

                        <div class="alert alert-primary d-flex align-items-center p-4">
                            <i class="fa-solid fa-lightbulb fs-2 text-primary me-3"></i>
                            <span class="text-body fs-6"><code>devcode</code> alanı <code>VNL_FATAL</code> ise hata beklenmeyen bir çökmedir — uygulama hatası değil. Ön yüzde bu ayrımı yaparak kullanıcıya farklı bir mesaj gösterebilirsiniz: <code>if (xhr.responseJSON?.devcode === 'VNL_FATAL') { ... }</code></span>
                        </div>
                    </div>
                    <!--end::Section 2-->

                    <div class="separator separator-dashed my-8"></div>

                    <!--begin::Section 3-->
                    <div class="mb-10">
                        <h3 class="fw-bold text-body-emphasis mb-4">3. HTML Hata Sayfası</h3>
                        <p class="fs-5 text-body mb-5">
                            Sayfa isteklerinde gösterilen ekran, VNL'in view sistemine ve <code>assets/</code> dizinine <b>bağımlı değildir</b> — tema derlenmemişken ya da veritabanı kapalıyken de açılır. Tek dosyada duran, gömülü CSS/JS kullanan bağımsız bir sayfadır.
                        </p>

                        <div class="row g-5 mb-6">
                            <div class="col-md-6">
                                <div class="border border-dashed rounded p-5 h-100">
                                    <div class="d-flex align-items-center mb-3">
                                        <i class="fa-solid fa-code fs-2 text-primary me-3"></i>
                                        <h6 class="mb-0 fw-bold">Kaynak kod bağlamı</h6>
                                    </div>
                                    <span class="text-body fs-6">Hatanın oluştuğu satırın 8 satır öncesi ve 6 satır sonrası, hatalı satır vurgulanmış olarak gösterilir.</span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="border border-dashed rounded p-5 h-100">
                                    <div class="d-flex align-items-center mb-3">
                                        <i class="fa-solid fa-layer-group fs-2 text-primary me-3"></i>
                                        <h6 class="mb-0 fw-bold">Gezinilebilir stack trace</h6>
                                    </div>
                                    <span class="text-body fs-6">Sol taraftaki listeden çağrı zincirindeki her kareye atlanır; dosya yolları document root'a göre kısaltılır.</span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="border border-dashed rounded p-5 h-100">
                                    <div class="d-flex align-items-center mb-3">
                                        <i class="fa-solid fa-circle-info fs-2 text-primary me-3"></i>
                                        <h6 class="mb-0 fw-bold">Ortam bilgisi</h6>
                                    </div>
                                    <span class="text-body fs-6">İstisna sınıfı, PHP sürümü ve VNL sürümü üst bantta yer alır — hata bildirirken bu bilgiyi paylaşın.</span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="border border-dashed rounded p-5 h-100">
                                    <div class="d-flex align-items-center mb-3">
                                        <i class="fa-solid fa-shield fs-2 text-primary me-3"></i>
                                        <h6 class="mb-0 fw-bold">Döngü koruması</h6>
                                    </div>
                                    <span class="text-body fs-6">Hata sayfasının kendisi hata verirse süreç sessizce sonlanır; sonsuz döngü oluşmaz.</span>
                                </div>
                            </div>
                        </div>

                        <div class="alert alert-danger d-flex align-items-center p-5 mb-0">
                            <i class="fa-solid fa-circle-exclamation fs-2hx text-danger me-4"></i>
                            <div class="d-flex flex-column">
                                <h5 class="mb-1 text-danger">Üretimde açık bırakmayın</h5>
                                <span class="text-body">Hata sayfası <b>ortam ayrımı yapmaz</b>: geliştirme ile üretim arasında fark gözetmeden dosya yollarını, kaynak kod parçalarını ve çağrı zincirini gösterir. Bu, geliştirme sırasında büyük kolaylık, canlıda ise bilgi sızıntısıdır. Canlıya çıkarken <code>vnl/boot.php</code>'deki handler'ı kendi ortam bayrağınızla sarın (aşağıdaki örnek) ve hatayı <code>error_log</code>'a yazdırın.</span>
                            </div>
                        </div>
                    </div>
                    <!--end::Section 3-->

                    <div class="separator separator-dashed my-8"></div>

                    <!--begin::Section 4-->
                    <div class="mb-10">
                        <h3 class="fw-bold text-body-emphasis mb-4">4. Üretim İçin Kapatma</h3>
                        <p class="fs-5 text-body mb-4">
                            <code>.env</code>'e bir bayrak ekleyip <code>vnl/boot.php</code>'deki iki handler'ı buna göre kurun. Ayrıntılı sayfa yalnızca geliştirmede açılır, canlıda kullanıcı sade bir mesaj görür, geliştirici log'a bakar:
                        </p>

                        <div class="vnl-code mb-5">
                            <pre class="mb-0" style="background: transparent; white-space: pre-wrap;"><code># .env
APP_DEBUG=false</code></pre>
                        </div>

                        <div class="vnl-code">
                            <pre class="mb-0" style="background: transparent; white-space: pre-wrap;"><code>&lt;?php
// vnl/boot.php — mevcut handler'ların yerine

$_vnlDebug = (bool)VnlEnv::get('APP_DEBUG', false);

set_exception_handler(function (Throwable $e) use ($_vnlDebug) {
    if ($_vnlDebug) {
        vnl_render_error($e);        // ayrıntılı sayfa / JSON
    }

    error_log('[VNL][Fatal] ' . get_class($e) . ': ' . $e->getMessage()
        . ' @ ' . $e->getFile() . ':' . $e->getLine());

    http_response_code(500);

    if (vnl_wants_json()) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['message' => 'Beklenmeyen bir hata oluştu.', 'devcode' => 'VNL_FATAL']);
    } else {
        echo 'Beklenmeyen bir hata oluştu.';
    }

    exit(1);
});
</code></pre>
                        </div>
                    </div>
                    <!--end::Section 4-->

                    <div class="separator separator-dashed my-8"></div>

                    <!--begin::Section 5-->
                    <div class="mb-10">
                        <h3 class="fw-bold text-body-emphasis mb-4">5. Beklenen Hatalar — Exception Sınıfları</h3>
                        <p class="fs-5 text-body mb-5">
                            <code>exceptions/</code> klasöründeki tüm dosyalar uygulama başlangıcında otomatik yüklenir. Bu sınıfların ortak bir özelliği vardır ve bu özellik davranışlarını anlamanın anahtarıdır:
                        </p>

                        <div class="alert alert-warning d-flex align-items-center p-5 mb-6">
                            <i class="fa-solid fa-circle-exclamation fs-2hx text-warning me-4"></i>
                            <div class="d-flex flex-column">
                                <h5 class="mb-1 text-warning">Constructor yanıtı basar ve süreci bitirir</h5>
                                <span class="text-body">Bu sınıflar <b>constructor'ında</b> <code>returnErrorMessage()</code> çağırır; o fonksiyon da JSON'u basıp <code>die</code> eder. Yani <code>new StandartException('...')</code> yazdığınız an — <code>throw</code> etmeseniz bile — yanıt gönderilmiş ve istek sonlanmış olur. Sonuç: <b>bu istisnaları <code>try/catch</code> ile yakalayamazsınız.</b> Yakalamak istediğiniz bir hata varsa <code>throwErrorMessage()</code> kullanın.</span>
                            </div>
                        </div>

                        <div class="table-responsive border rounded bg-body-tertiary mb-6">
                            <table class="table align-middle table-row-dashed fs-6 gy-4 mb-0">
                                <thead>
                                <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0 border-bottom">
                                    <th class="ps-5 min-w-200px">Sınıf</th>
                                    <th class="min-w-100px">HTTP</th>
                                    <th class="min-w-350px">Kullanım</th>
                                </tr>
                                </thead>
                                <tbody class="text-body fw-semibold">
                                <tr>
                                    <td class="ps-5"><code>StandartException</code></td>
                                    <td><span class="badge badge-soft-danger">400</span></td>
                                    <td>Genel uygulama hatası. İkinci parametreyle kod değiştirilebilir: <code>new StandartException($msg, 422)</code>. Validator'ların tamamı bunu kullanır.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>EmptyDataException</code></td>
                                    <td><span class="badge badge-soft-danger">400</span></td>
                                    <td>Boş veri durumları için ayrılmıştır. Davranışı <code>StandartException</code> ile aynıdır.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>AuthException</code></td>
                                    <td><span class="badge badge-soft-warning">401</span></td>
                                    <td>Kimlik doğrulama hataları.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>ErrorMessage</code></td>
                                    <td>—</td>
                                    <td>İstisna değil, basit bir veri taşıyıcı: <code>message</code> + <code>error_code</code>.</td>
                                </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="alert alert-warning d-flex align-items-center p-4">
                            <i class="fa-solid fa-triangle-exclamation fs-2 text-warning me-3"></i>
                            <span class="text-body fs-6"><code>AuthException</code>, mesaj yerine bir <code>ErrorMessage</code> <b>nesnesi</b> gönderir; JSON çıktısı <code>{"message":{"message":"...","error_code":401}}</code> biçiminde iç içe olur. <code>res.message</code> bekleyen ön yüz kodu bu yanıtta düz metin bulamaz — <code>StandartException</code> tercih edin.</span>
                        </div>
                    </div>
                    <!--end::Section 5-->

                    <div class="separator separator-dashed my-8"></div>

                    <!--begin::Section 6-->
                    <div class="mb-0">
                        <h3 class="fw-bold text-body-emphasis mb-4">6. <code>throwErrorMessage()</code> mi, Exception mı?</h3>

                        <div class="row g-5 mb-6">
                            <div class="col-md-6">
                                <div class="border border-dashed rounded p-5 h-100">
                                    <h6 class="fw-bold mb-3"><code>throwErrorMessage($msg, $kod)</code></h6>
                                    <span class="text-body fs-6 d-block mb-3">HTTP kodunu ayarlar ve gerçek bir <code>Exception</code> fırlatır — hiçbir çıktı basmaz. <code>controller/app.php</code>'nin <code>catch</code> bloğu bunu yakalar ve JSON'a çevirir.</span>
                                    <span class="badge badge-soft-success">Yakalanabilir</span>
                                    <span class="badge badge-soft-success">Action içinde tercih edilen</span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="border border-dashed rounded p-5 h-100">
                                    <h6 class="fw-bold mb-3"><code>throw new StandartException($msg)</code></h6>
                                    <span class="text-body fs-6 d-block mb-3">Yanıtı anında basar ve <code>die</code> eder. Validator'lar bunu kullanır; çağıran koda dönüş yoktur.</span>
                                    <span class="badge badge-soft-danger">Yakalanamaz</span>
                                    <span class="badge badge-soft-primary">Validator'larda standart</span>
                                </div>
                            </div>
                        </div>

                        <p class="fs-6 text-body mb-3"><code>controller/app.php</code> yakaladığı istisnanın kaynağına bakarak durum kodunu belirler:</p>

                        <div class="vnl-code mb-5">
                            <pre class="mb-0" style="background: transparent; white-space: pre-wrap;"><code>// controller/app.php
} catch (Exception $e) {
    $last_call = $e->getTrace()[0];

    if ($last_call["function"] == "throwErrorMessage") {
        returnErrorMessage($e->getMessage());        // 400 — beklenen hata
    } else {
        returnErrorMessage($e->getMessage(), 500);   // 500 — beklenmeyen
    }
}</code></pre>
                        </div>

                        <div class="vnl-code">
                            <pre class="mb-0" style="background: transparent; white-space: pre-wrap;"><code>&lt;?php
// Pratikte: action içinde beklenen hatalar için throwErrorMessage
use ActionGlobal\Auth;

class action_siparis
{
    public function iptalEt($req)
    {
        Auth::CheckLogin();

        $siparis = Siparis::Select(['id' => $req->post('id')]);

        if (empty($siparis)) {
            throwErrorMessage("Sipariş bulunamadı.", 404);
        }

        if ($siparis->durum === 'kargoda') {
            throwErrorMessage("Kargoya verilmiş sipariş iptal edilemez.");
        }

        // Validator'lar kendi hatalarını StandartException ile basar
        DefaultValidator::validateEmpty($req->post('sebep'), "İptal sebebi");

        $siparis->durum = 'iptal';
        $siparis->save();

        returnSuccessMessage("Sipariş iptal edildi.");
    }
}
</code></pre>
                        </div>
                    </div>
                    <!--end::Section 6-->

                </div>
            </div>

        </div>
    </div>
</div>

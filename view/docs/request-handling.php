<div class="d-flex flex-column flex-column-fluid">
    <div id="kt_app_content" class="app-content flex-column-fluid">
        <div id="kt_app_content_container" class="app-container container-xxl">

            <div class="vnl-page-hero mb-12 mt-6">
                <div class="vnl-overline mb-3">// docs/request-handling</div>
                <h1 class="fs-2hx fw-bold mb-4 text-body-emphasis">İstek Yönetimi ve Action'lar</h1>
                <div class="vnl-lead">VNL'de form gönderimleri ve AJAX istekleri, controller'lardan bağımsız olarak <code>actions/</code> klasöründe işlenir. Gelen veriler güvenlik filtrelerinden geçirilerek JSON formatında yanıtlanır.</div>
            </div>

            <div class="alert alert-primary d-flex align-items-center p-5 mb-10">
                <i class="fa-solid fa-shield-halved fs-2hx text-primary me-4"></i>
                <div class="d-flex flex-column text-primary">
                    <h4 class="mb-1 text-primary">Otomatik Güvenlik ve İzolasyon</h4>
                    <span><b>VNL 3.0 değişikliği:</b> gelen veri artık girişte kaçışlanmaz; ne gönderildiyse o okunur ve o saklanır. XSS koruması çıkış katmanına taşındı — şablonda <code>@{{ }}</code>, düz PHP'de <code>vnl_e()</code>. JSON body (<code>application/json</code>) da dahil olmak üzere geleneksel <code class="text-body-emphasis">$_POST</code> yerine sadece <code>$req</code> nesnesi, dosyalar için ise <code>FileRequest</code> sınıfı kullanılır.</span>
                </div>
            </div>

            <div class="alert alert-warning d-flex align-items-center p-5 mb-10">
                <i class="fa-solid fa-lock fs-2hx text-warning me-4"></i>
                <div class="d-flex flex-column">
                    <h4 class="mb-1">3.4.0 — Dağıtıcının kuralları</h4>
                    <span class="text-body"><code>/app/*</code> yalnız <b>POST</b> kabul eder (405), her istekte <b>CSRF jetonu</b> ister (<code>X-CSRF-Token</code> başlığı ya da <code>_token</code> alanı; vnl.js ve VNL Table kendiliğinden ekler — 403 <code>VNL_CSRF</code>) ve <b>girişi varsayılan olarak zorunlu</b> kılar (401 <code>VNL_AUTH</code> — girişsiz istekte CSRF'ten önce bakılır: süresi dolan sekme 403 değil 401 alır ve <code>/login</code>'e gider). Girişsiz çağrılacak metot sınıfta <code>public const PUBLIC_METHODS = ['login'];</code>, imzasını kendisi doğrulayan webhook <code>public const CSRF_EXEMPT = ['webhook'];</code> ile listelenir. Yalnız <code>public</code>, static olmayan ve <code>__</code> ile başlamayan metotlar çağrılabilir. Her action'ın ilk satırı: <code>Auth::CheckPermission('Kod', 'view|insert|edit|delete');</code>. Ayrıntı: <a href="/docs/security">/docs/security</a>.</span>
                </div>
            </div>

            <div class="card mb-10">
                <div class="card-body p-10">

                    <div class="mb-10">
                        <h3 class="fw-bold text-body-emphasis mb-4">1. Action URL Yapısı</h3>
                        <p class="fs-5 text-body mb-5">
                            Tüm AJAX istekleri <code>/app/</code> ön ekiyle gönderilir. <code>controller/app.php</code> bu istekleri karşılar ve URL'yi aşağıdaki kurala göre parçalar:
                        </p>

                        <div class="d-flex align-items-center bg-body-tertiary border border-dashed rounded p-4 mb-6">
                            <i class="fa-solid fa-arrow-right fs-2 text-primary me-3"></i>
                            <span class="fs-5 text-body"><code>/app/<b>{route(1)}</b>/<b>{route(2)}</b></code> &nbsp;→&nbsp; <code>actions/action_<b>{route(1)}</b>.php</code> dosyasındaki <code>action_<b>{route(1)}</b></code> sınıfının <code><b>{route(2)}</b>($req)</code> metodunu çağırır.</span>
                        </div>

                        <div class="table-responsive border rounded bg-body-tertiary mb-5">
                            <table class="table align-middle table-row-dashed fs-6 gy-4 mb-0">
                                <thead>
                                <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0 border-bottom">
                                    <th class="ps-5 min-w-250px">İstek URL'si</th>
                                    <th class="min-w-200px">Hedef Dosya</th>
                                    <th class="min-w-150px">Çalışan Metot</th>
                                </tr>
                                </thead>
                                <tbody class="text-body fw-semibold">
                                <tr>
                                    <td class="ps-5"><code>/app/kullanici/kaydet</code></td>
                                    <td><span class="badge badge-soft-primary">actions/action_kullanici.php</span></td>
                                    <td><span class="badge badge-soft-success">kaydet($req)</span></td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>/app/urun/sil</code></td>
                                    <td><span class="badge badge-soft-primary">actions/action_urun.php</span></td>
                                    <td><span class="badge badge-soft-success">sil($req)</span></td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>/app/profil/guncelle</code></td>
                                    <td><span class="badge badge-soft-primary">actions/action_profil.php</span></td>
                                    <td><span class="badge badge-soft-success">guncelle($req)</span></td>
                                </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="alert alert-warning d-flex align-items-center p-4">
                            <i class="fa-solid fa-circle-info fs-2 text-warning me-3"></i>
                            <span class="text-body fs-6">Action dosyaları <b>mutlaka</b> <code>action_</code> önekiyle adlandırılmalıdır ve içinde aynı adda bir PHP sınıfı bulunmalıdır. Dosya veya metot bulunamazsa sistem JSON hata mesajı döndürür.</span>
                        </div>
                    </div>

                    <div class="separator separator-dashed my-8"></div>

                    <div class="mb-10">
                        <h3 class="fw-bold text-body-emphasis mb-4">2. Action Dosyası Oluşturma</h3>
                        <p class="fs-5 text-body mb-4">
                            Her action dosyası tek bir sınıf içerir. Sistem, metodlara otomatik olarak global <code>$req</code> nesnesini iletir — ayrıca parametre tanımlamanıza gerek yoktur, sadece <code>$req</code> alırsınız.
                        </p>
                        <div class="vnl-code mb-0">
                            <pre class=" mb-0" style="background: transparent; white-space: pre-wrap;"><code>&lt;?php
// Dosya: actions/action_kullanici.php

class action_kullanici
{
    public function kaydet($req)
    {
        // $req buraya otomatik gelir
        $ad    = $req->post('ad');
        $email = $req->post('email');

        // ... işlemler ...

        returnSuccessMessage("Kullanıcı kaydedildi.");
    }

    public function sil($req)
    {
        $id = $req->post('id');
        // ...
    }
}
</code></pre>
                        </div>

                        <div class="alert alert-danger d-flex align-items-center p-5 mt-6 mb-5">
                            <i class="fa-solid fa-circle-xmark fs-2hx text-danger me-4"></i>
                            <div class="d-flex flex-column">
                                <h5 class="mb-1 text-danger">Action'da SQL Yazılmaz — Felsefe</h5>
                                <span class="text-body">Action ince bir kapıdır: isteği karşılar, doğrular, işi modele devreder ve yanıtı döndürür. Çok ağır bir zorunluluk olmadıkça action içinde sorgu kurulmaz — ne <code>Capsule::table()</code> ne ham SQL. <b>Tüm sorgular class'larda toplanır</b> (bkz. <a href="/docs/models">Model ve Veritabanı</a> sayfasındaki "Capsule:: Doğrudan Kullanımı Yasak Gibidir" kuralı). Bu kural sorguları tek yerde tutar: aynı liste üç action'da üç kez yazılmaz, sütun değişince tek sınıf güncellenir.</span>
                            </div>
                        </div>

                        <p class="text-body mb-3">
                            İzin verilen tek esneklik: <b>sınıftan başlayan zinciri</b> action'da uzatmak.
                            Bu durumda bile join edilen taraf başka bir sınıf olmak zorundadır —
                            tablo adı elle yazılmaz, karşılık gelen modelden alınır:
                        </p>

                        <div class="vnl-code">
                            <pre class="mb-0" style="background: transparent; white-space: pre-wrap;"><code>// ❌ Yanlış: sorgu action'da kuruluyor
$rows = Capsule::table('tbl_users')
    ->leftJoin('tbl_roller', 'tbl_roller.id', '=', 'tbl_users.rol_id')
    ->get();

// ✅ İzinli: zincir sınıftan başlar; join edilen taraf da bir sınıftır
$rows = Users::SelectList($filters, $extras)
    ->leftJoin((new Roller)->getTable(), 'tbl_roller.id', '=', 'tbl_users.rol_id')
    ->get();

// ✅ En iyisi: join'in kendisi de modelde yaşar
$rows = Roller::UsersWithRoles($filters, $extras)->get();</code></pre>
                        </div>
                    </div>

                    <div class="separator separator-dashed my-8"></div>

                    <div class="mb-10">
                        <h3 class="fw-bold text-body-emphasis mb-4">3. Gelen Verileri Okuma (<code>Request</code>)</h3>
                        <p class="fs-5 text-body mb-4">
                            Global <code>$req</code> nesnesi sistem başlangıcında otomatik oluşturulur. POST, GET ve JSON body verilerini destekler. Veri ham gelir: <code>"0"</code> ve boş string korunur, JSON'daki sayı ve boolean tipleri bozulmaz. Ekrana basarken <code>@{{ }}</code> kullanın.
                        </p>

                        <div class="table-responsive border rounded bg-body-tertiary mb-5">
                            <table class="table align-middle table-row-dashed fs-6 gy-4 mb-0">
                                <thead>
                                <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0 border-bottom">
                                    <th class="ps-5 min-w-280px">Metot</th>
                                    <th class="min-w-300px">Açıklama</th>
                                </tr>
                                </thead>
                                <tbody class="text-body fw-semibold">
                                <tr>
                                    <td class="ps-5"><code>$req->post('anahtar')</code></td>
                                    <td>POST veya JSON body'den veri okur. JSON body otomatik algılanır.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>$req->get('anahtar')</code></td>
                                    <td>URL query string'den (<code>?id=5</code>) veri okur.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>$req->postHas('anahtar')</code></td>
                                    <td>İlgili POST anahtarının gönderilip gönderilmediğini kontrol eder. <code>bool</code> döner.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>$req->getHas('anahtar')</code></td>
                                    <td>İlgili GET anahtarının var olup olmadığını kontrol eder. <code>bool</code> döner.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>$req->isPost()</code></td>
                                    <td>İsteğin POST metoduyla gelip gelmediğini kontrol eder. <code>bool</code> döner.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>$req->setPost('anahtar', $deger)</code></td>
                                    <td>POST verisini programatik olarak ayarlar (test/mock işlemleri için).</td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="separator separator-dashed my-8"></div>

                    <div class="mb-10">
                        <h3 class="fw-bold text-body-emphasis mb-4">4. Dosya Yüklemeleri (<code>FileRequest</code>)</h3>
                        <p class="fs-5 text-body mb-4">
                            Dosya yüklemeleri için <code>FileRequest</code> sınıfı kullanılır. Bu sınıf PHP'nin karmaşık <code>$_FILES</code> yapısını normalize eder; dosya adlarını Null Byte, Path Traversal ve XSS saldırılarına karşı otomatik temizler. <b>Manuel olarak <code>new FileRequest()</code> ile başlatmanız gerekir.</b>
                        </p>
                        <div class="table-responsive border rounded bg-body-tertiary mb-5">
                            <table class="table align-middle table-row-dashed fs-6 gy-4 mb-0">
                                <thead>
                                <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0 border-bottom">
                                    <th class="ps-5 min-w-300px">Metot</th>
                                    <th class="min-w-300px">Açıklama</th>
                                </tr>
                                </thead>
                                <tbody class="text-body fw-semibold">
                                <tr>
                                    <td class="ps-5"><code>$fileReq->has('input_adi')</code></td>
                                    <td>Kullanıcının o form alanına dosya seçip seçmediğini kontrol eder.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>$fileReq->getName('input_adi')</code></td>
                                    <td>Yüklenen dosyanın temizlenmiş orijinal adını döndürür.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>$fileReq->getExtension('input_adi')</code></td>
                                    <td>Dosyanın uzantısını küçük harfle döndürür (ör: <code>jpg</code>, <code>pdf</code>).</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>$fileReq->getSize('input_adi')</code></td>
                                    <td>Dosyanın bayt cinsinden boyutunu döndürür.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>$fileReq->getRealMimeType('input_adi')</code></td>
                                    <td>Sunucu tarafında gerçek MIME türünü okur — istemci gönderimindeki spoofing'e karşı güvenlidir.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>$fileReq->generateRandomName('input_adi', 'prefix_')</code></td>
                                    <td>İsim çakışmalarını önlemek için eşsiz, rastgele bir dosya adı üretir. İkinci parametre opsiyonel önek.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>$fileReq->move('input_adi', $hedef_tam_yol)</code></td>
                                    <td>Dosyayı kalıcı hedefe taşır. Hedef tam dosya yolu olmalıdır. Başarı/başarısızlık için <code>bool</code> döner.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>$fileReq->isMultiple('input_adi')</code></td>
                                    <td>Form alanının çoklu (<code>multiple</code>) dosya yüklemesi olup olmadığını kontrol eder.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>$fileReq->all()</code></td>
                                    <td>Normalize ve temizlenmiş tüm dosya verisini dizi olarak döndürür.</td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="separator separator-dashed my-8"></div>

                    <div class="mb-10">
                        <h3 class="fw-bold text-body-emphasis mb-4">5. Kimlik Doğrulama (<code>Auth</code>)</h3>
                        <p class="fs-5 text-body mb-4">
                            Her action isteğinde <code>actions/Auth.php</code> dosyası (<code>ActionGlobal\Auth</code> sınıfı) otomatik olarak yüklenir. Bu sınıfla login ve izin kontrollerini kısa yoldan yapabilirsiniz.
                        </p>
                        <div class="table-responsive border rounded bg-body-tertiary mb-5">
                            <table class="table align-middle table-row-dashed fs-6 gy-4 mb-0">
                                <thead>
                                <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0 border-bottom">
                                    <th class="ps-5 min-w-300px">Metot</th>
                                    <th class="min-w-300px">Açıklama</th>
                                </tr>
                                </thead>
                                <tbody class="text-body fw-semibold">
                                <tr>
                                    <td class="ps-5"><code>Auth::CheckPermission('Kod', 'edit')</code></td>
                                    <td><b>3.4.0 — her action'ın ilk satırı.</b> Girişsiz → 401 <code>VNL_AUTH</code>, yetkisiz → 403 <code>VNL_PERM</code>; ikisi de isteği koşulsuz bitirir. Yetenekler: <code>view</code>, <code>insert</code>, <code>edit</code>, <code>delete</code>. Karar <code>vnl_can($kod, $yetenek, true)</code> (katı) → <code>helpers/permissions.php</code>; kanca yoksa kimse geçemez.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>Auth::Can('Kod', 'edit')</code></td>
                                    <td>Durdurmayan hali: <code>bool</code> döner (düğmeyi gizlemek gibi).</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>Auth::CheckLogin()</code></td>
                                    <td>Kullanıcı giriş yapmamışsa hata fırlatır ve işlemi durdurur (3.4.0: 401 <code>VNL_AUTH</code>). Ölçüt <code>vnl_logged_in()</code>. Dağıtıcı girişi zaten varsayılan olarak ister; yetki gereken yerde <code>CheckPermission</code> kullanın.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>Auth::User()</code></td>
                                    <td>Aktif <code>$CurrentUser</code> nesnesini döndürür. <b>Girişsiz istekte de bir nesne döner</b> — boş bir <code>User</code>. Giriş denetimi için kullanmayın.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>Auth::HasPermis([1, 2])</code></td>
                                    <td>Kullanıcının rolü (<code>perm_id</code>) verilen dizide yoksa hata fırlatır (3.4.0: 403). Girişsiz istek de elenir.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>Auth::UserPermis()</code></td>
                                    <td>Aktif kullanıcının <code>perm_id</code> değerini döndürür.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>vnl_logged_in()</code> / <code>vnl_user()</code></td>
                                    <td>Çekirdeğin karşılıkları; her yerde çağrılabilir. <code>vnl_user()</code> girişsizde <b><code>null</code></b> döner, boş model değil.</td>
                                </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="alert alert-danger d-flex align-items-start p-5 mb-5">
                            <i class="fa-solid fa-shield-halved fs-2hx text-danger me-4"></i>
                            <div class="d-flex flex-column">
                                <h5 class="mb-1 text-danger">VNL 3.2 güvenlik düzeltmesi — <code>CheckLogin()</code> hiç denetlemiyordu</h5>
                                <span class="text-body">
                                    VNL 3.1'e kadar ölçüt <code>!empty(self::User())</code> idi.
                                    <code>$CurrentUser</code> her istekte koşulsuz kurulur
                                    (<code>config/database.php</code>) ve PHP'de bir nesne hiçbir zaman
                                    <code>empty</code> olmaz — yani denetim <b>her zaman <code>true</code></b>
                                    dönüyordu. Bu sayfa onu "giriş yapılmamışsa hata fırlatır" diye tarif
                                    ettiği için action'lar o satıra güveniyordu.
                                    <br><br>
                                    <b><code>actions/Auth.php</code> projenindir; güncelleme ona dokunamaz.</b>
                                    Mevcut kurulumlarda dosyayı şablondaki sürümle değiştirin.
                                    <code>php vnl.php doctor</code> bu tuzağı artık ayrıca sınıyor.
                                </span>
                            </div>
                        </div>
                        <div class="vnl-code">
                            <pre class=" mb-0" style="background: transparent; white-space: pre-wrap;"><code>use ActionGlobal\Auth;

class action_kullanici
{
    public function sil($req)
    {
        Auth::CheckLogin();          // Giriş zorunluluğu
        Auth::HasPermis([1, 2]);     // Sadece 1 ve 2 nolu roller
        // ...
    }
}
</code></pre>
                        </div>
                    </div>

                    <div class="separator separator-dashed my-8"></div>

                    <div class="mb-10">
                        <h3 class="fw-bold text-body-emphasis mb-4">6. JSON Yanıt Fonksiyonları</h3>
                        <p class="fs-5 text-body mb-4">
                            Tüm action metodları JSON döndürmelidir. Manuel <code>json_encode</code> yazmak yerine aşağıdaki yardımcı fonksiyonları kullanın. Bu fonksiyonlar uygun HTTP durum kodunu otomatik ayarlar.
                        </p>

                        <div class="table-responsive border rounded bg-body-tertiary mb-5">
                            <table class="table align-middle table-row-dashed fs-6 gy-4 mb-0">
                                <thead>
                                <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0 border-bottom">
                                    <th class="ps-5 min-w-320px">Fonksiyon</th>
                                    <th class="min-w-80px">HTTP Kodu</th>
                                    <th class="min-w-300px">Kullanım Amacı</th>
                                </tr>
                                </thead>
                                <tbody class="text-body fw-semibold">
                                <tr>
                                    <td class="ps-5"><code>returnSuccessMessage($mesaj, $data = [])</code></td>
                                    <td><span class="badge badge-soft-success">200</span></td>
                                    <td>İşlem başarıyla tamamlandı. İsteğe bağlı <code>$data</code> dizisi de döndürülebilir.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>returnSuccessDataOnly($data)</code></td>
                                    <td><span class="badge badge-soft-success">200</span></td>
                                    <td>Sadece veri döndürür (mesajsız). Datatable ve liste API'leri için idealdir.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>returnErrorMessage($mesaj, $kod = 400)</code></td>
                                    <td><span class="badge badge-soft-danger">400+</span></td>
                                    <td>Hata mesajı ile birlikte işlemi sonlandırır (<code>die</code>).</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>returnWarningMessage($mesaj, $kod = 406)</code></td>
                                    <td><span class="badge badge-soft-warning">406</span></td>
                                    <td>Uyarı mesajı döndürür ve işlemi sonlandırır.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>returnInfoMessage($mesaj, $kod = 410)</code></td>
                                    <td><span class="badge badge-soft-info">410</span></td>
                                    <td>Bilgi mesajı döndürür ve işlemi sonlandırır.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>throwErrorMessage($mesaj, $kod = 400, $devcode = '')</code></td>
                                    <td><span class="badge badge-soft-danger">400+</span></td>
                                    <td><code>VnlHttpException</code> fırlatır (<code>Exception</code>'dan türer). <code>VnlDispatcher</code> yakalar ve kendi durum koduyla JSON'a dönüştürür. Kodun herhangi bir yerinden hata fırlatmak için idealdir. Gövdeye ek alan (<code>errors[]</code>) ya da yanıta başlık (<code>Retry-After</code>) gerekiyorsa <code>VnlHttpException</code>'ı doğrudan fırlatın — 3.4.1, <a href="/docs/errors">/docs/errors</a> §7.</td>
                                </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="row g-5">
                            <div class="col-md-6">
                                <div class="border border-success border-dashed rounded p-5 h-100">
                                    <div class="d-flex align-items-center mb-3">
                                        <i class="fa-solid fa-circle-check fs-1 text-success me-3"></i>
                                        <h5 class="mb-0 fw-bold">Başarılı Yanıt</h5>
                                    </div>
                                    <code class="d-block bg-body-tertiary p-3 rounded mb-2 fs-7">returnSuccessMessage("Kayıt oluşturuldu.", ['id' => $yeni_id]);</code>
                                    <span class="text-muted fs-7">Dönen JSON: <code>&#123;"message":"Kayıt oluşturuldu.","data":&#123;"id":42&#125;&#125;</code></span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="border border-danger border-dashed rounded p-5 h-100">
                                    <div class="d-flex align-items-center mb-3">
                                        <i class="fa-solid fa-circle-xmark fs-1 text-danger me-3"></i>
                                        <h5 class="mb-0 fw-bold">Hata Fırlatma</h5>
                                    </div>
                                    <code class="d-block bg-body-tertiary p-3 rounded mb-2 fs-7">throwErrorMessage("E-posta adresi geçersiz.", 400);</code>
                                    <span class="text-muted fs-7">İşlemi anında keser; <code>app.php</code>'deki catch bloğu JSON olarak döndürür.</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="separator separator-dashed my-8"></div>

                    <div class="mb-0">
                        <h3 class="fw-bold text-body-emphasis mb-4">7. Tam Kapsamlı Örnek</h3>
                        <p class="fs-5 text-body mb-4">
                            <code>/app/profil/guncelle</code> adresine gönderilen form verisi ve dosya yüklemesini işleyen eksiksiz bir action örneği:
                        </p>

                        <div class="vnl-code">
<pre class=" mb-0" style="background: transparent; white-space: pre-wrap;"><code>&lt;?php
// Dosya: actions/action_profil.php

use ActionGlobal\Auth;

class action_profil
{
    public function guncelle($req)
    {
        // 1. Login ve yetki kontrolü
        Auth::CheckLogin();

        // 2. POST verilerini al (ham gelir; ekrana basarken @{{ }} kaçışlar)
        $kullanici_id = $req->post('user_id');
        $biyografi    = $req->post('bio');

        if (empty($kullanici_id)) {
            throwErrorMessage("Kullanıcı kimliği eksik!", 400);
        }

        // 3. Dosya yükleme (FileRequest manuel başlatılır)
        $fileReq = new FileRequest();
        $yeni_avatar = null;

        if ($fileReq->has('avatar')) {
            $uzanti = $fileReq->getExtension('avatar');

            if (!in_array($uzanti, ['jpg', 'jpeg', 'png', 'webp'])) {
                throwErrorMessage("Sadece JPG, PNG ve WebP yüklenebilir.", 400);
            }

            // Gerçek MIME kontrolü (spoofing'e karşı)
            $mime = $fileReq->getRealMimeType('avatar');
            if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp'])) {
                throwErrorMessage("Geçersiz dosya türü.", 400);
            }

            $yeni_avatar = $fileReq->generateRandomName('avatar', 'usr_');
            $hedef = getDocumentRoot() . 'files/avatars/' . $yeni_avatar;

            if (!$fileReq->move('avatar', $hedef)) {
                throwErrorMessage("Dosya sunucuya kaydedilemedi.", 500);
            }
        }

        // 4. Veritabanını güncelle
        $veri = ['bio' => $biyografi];
        if ($yeni_avatar) {
            $veri['avatar'] = $yeni_avatar;
        }

        Capsule::table('users')->where('id', $kullanici_id)->update($veri);

        // 5. Başarı yanıtı
        returnSuccessMessage("Profil güncellendi.", ['avatar' => $yeni_avatar]);
    }
}
</code></pre>
                        </div>
                    </div>

                </div>
            </div>

        </div>
    </div>
</div>

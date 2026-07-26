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
                    <span>VNL, gelen tüm POST/GET verilerini (XSS) otomatik olarak <code>htmlspecialchars</code> ile temizler. JSON body (<code>application/json</code>) da dahil olmak üzere geleneksel <code class="text-body-emphasis">$_POST</code> yerine sadece <code>$req</code> nesnesi, dosyalar için ise <code>FileRequest</code> sınıfı kullanılır.</span>
                </div>
            </div>

            <div class="card shadow-sm border-0 mb-10">
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
                    </div>

                    <div class="separator separator-dashed my-8"></div>

                    <div class="mb-10">
                        <h3 class="fw-bold text-body-emphasis mb-4">3. Gelen Verileri Okuma (<code>Request</code>)</h3>
                        <p class="fs-5 text-body mb-4">
                            Global <code>$req</code> nesnesi sistem başlangıcında otomatik oluşturulur. POST, GET ve JSON body verilerini destekler. Tüm veriler <code>htmlspecialchars</code> ile temizlenmiş olarak gelir.
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
                                    <td class="ps-5"><code>Auth::CheckLogin()</code></td>
                                    <td>Kullanıcı giriş yapmamışsa otomatik hata fırlatır ve işlemi durdurur.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>Auth::User()</code></td>
                                    <td>Aktif <code>$CurrentUser</code> nesnesini döndürür.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>Auth::HasPermis([1, 2])</code></td>
                                    <td>Kullanıcının izin ID'si verilen dizide yoksa hata fırlatır.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>Auth::UserPermis()</code></td>
                                    <td>Aktif kullanıcının <code>perm_id</code> değerini döndürür.</td>
                                </tr>
                                </tbody>
                            </table>
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
                                    <td class="ps-5"><code>throwErrorMessage($mesaj, $kod = 400)</code></td>
                                    <td><span class="badge badge-soft-danger">400+</span></td>
                                    <td><code>Exception</code> fırlatır. <code>app.php</code>'nin try-catch bloğu yakalar ve JSON'a dönüştürür. Kodun herhangi bir yerinden hata fırlatmak için idealdir.</td>
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

        // 2. POST verilerini al (htmlspecialchars ile temizlenmiş gelir)
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

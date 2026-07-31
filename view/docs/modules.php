<div class="d-flex flex-column flex-column-fluid">
    <div id="kt_app_content" class="app-content flex-column-fluid">
        <div id="kt_app_content_container" class="app-container container-xxl">

            <div class="vnl-page-hero mb-12 mt-6">
                <div class="vnl-overline mb-3">// docs/modules</div>
                <h1 class="fs-2hx fw-bold mb-4 text-body-emphasis">Modüller (vnl/modules)</h1>
                <div class="vnl-lead">VNL, tekrar kullanılabilir yardımcı işlemleri <code>vnl/modules/</code> dizinindeki statik sınıflar aracılığıyla sunar. Tüm modüller uygulama başlangıcında otomatik yüklenir; herhangi bir yerden <code>ModulAdi::Metot()</code> biçiminde statik olarak çağrılır.</div>
            </div>

            <div class="alert alert-primary d-flex align-items-center p-5 mb-10">
                <i class="fa-solid fa-cubes fs-2hx text-primary me-4"></i>
                <div class="d-flex flex-column">
                    <h5 class="mb-1 text-primary">Otomatik Yükleme ve Statik Kullanım</h5>
                    <span class="text-body"><code>vnl/modules/</code> dizinindeki tüm PHP dosyaları uygulama başlangıcında otomatik olarak dahil edilir. Tüm modüller statik çağrı ile kullanılır: <code>EncryptionModule::PasswordHash($sifre)</code></span>
                </div>
            </div>

            <div class="card mb-10">
                <div class="card-body p-10">

                    <!--begin::EncryptionModule-->
                    <div class="mb-10">
                        <div class="d-flex align-items-center mb-5">
                            <span class="badge badge-circle badge-primary me-3 fs-5">E</span>
                            <h3 class="fw-bold text-body-emphasis mb-0">EncryptionModule</h3>
                            <span class="text-muted fs-7 ms-3 font-monospace">vnl/modules/EncryptionModule.php</span>
                        </div>
                        <p class="fs-5 text-body mb-4">Şifreleme ve deşifreleme işlemleri. Parola hash'leme ve cookie verisi için AES şifreleme sağlar.</p>

                        <div class="table-responsive border rounded bg-body-tertiary mb-5">
                            <table class="table align-middle table-row-dashed fs-6 gy-3 mb-0">
                                <thead>
                                <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0 border-bottom">
                                    <th class="ps-5 min-w-300px">Metot</th>
                                    <th class="min-w-350px">Açıklama</th>
                                </tr>
                                </thead>
                                <tbody class="text-body fw-semibold">
                                <tr>
                                    <td class="ps-5"><code>EncryptionModule::PasswordHash($pass)</code></td>
                                    <td>Parolayı <code>password_hash()</code> ile hash'ler (bcrypt, rastgele tuz). Her çağrıda farklı sonuç verir; <b>sorguda karşılaştırma ölçütü olarak kullanılamaz</b>.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>EncryptionModule::PasswordVerify($pass, $hash)</code></td>
                                    <td>Parolayı saklanan hash ile karşılaştırır. VNL 2.x'ten kalan <code>sha1(md5())</code> hash'lerini de tanır.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>EncryptionModule::PasswordNeedsRehash($hash)</code></td>
                                    <td>Hash eski biçimdeyse ya da maliyet düştüyse <code>true</code>. Başarılı girişten sonra çağırıp hash'i tazeleyin.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>EncryptionModule::safeEncrypt($data)</code></td>
                                    <td>Veriyi AES-256-GCM ile şifreler; URL güvenli string döndürür. Anahtar <code>.env</code>'deki <code>APP_ENCRYPTION_KEY</code>'den gelir, her çağrıda yeni nonce üretilir.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>EncryptionModule::safeDecrypt($encrypted)</code></td>
                                    <td>Şifreli metni çözer. Anahtar yanlışsa, <b>veri kurcalanmışsa</b> ya da biçim tanınmıyorsa <code>false</code> döner.</td>
                                </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="vnl-code">
                            <pre class="mb-0" style="background: transparent; white-space: pre-wrap;"><code>// Parola kaydetme
$kullanici->setStoredPassword(EncryptionModule::PasswordHash($req->post('password')));

// Parola doğrulama — hash ile SORGULANMAZ, önce kullanıcı çekilir
$kullanici = User::select(['username' => $username]);
if (!EncryptionModule::PasswordVerify($password, $kullanici->storedPassword())) {
    throw new StandartException(lang('ERR_LOGIN_EXCEPTION'));
}

// 2.x'ten kalan sha1(md5()) hash'ini sessizce bcrypt'e yükselt
if (EncryptionModule::PasswordNeedsRehash($kullanici->storedPassword())) {
    $kullanici->setStoredPassword(EncryptionModule::PasswordHash($password));
    $kullanici->save();
}

// Genel amaçlı şifreleme — .env'deki APP_ENCRYPTION_KEY kullanılır
$jeton = EncryptionModule::safeEncrypt(['user_id' => $user->Id, 'time' => time()]);

$veri = EncryptionModule::safeDecrypt($jeton);
if ($veri === false) { /* kurcalanmış, anahtar değişmiş veya geçersiz */ }
</code></pre>
                        </div>
                    </div>
                    <!--end::EncryptionModule-->

                    <div class="separator separator-dashed my-8"></div>

                    <!--begin::DateTimeModule-->
                    <div class="mb-10">
                        <div class="d-flex align-items-center mb-5">
                            <span class="badge badge-circle badge-success me-3 fs-5">D</span>
                            <h3 class="fw-bold text-body-emphasis mb-0">DateTimeModule</h3>
                            <span class="text-muted fs-7 ms-3 font-monospace">vnl/modules/DateTimeModule.php</span>
                        </div>
                        <p class="fs-5 text-body mb-4">Tarih ve zaman işlemleri; format dönüştürme, fark hesaplama ve "X zaman önce" gösterimi.</p>

                        <div class="table-responsive border rounded bg-body-tertiary mb-5">
                            <table class="table align-middle table-row-dashed fs-6 gy-3 mb-0">
                                <thead>
                                <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0 border-bottom">
                                    <th class="ps-5 min-w-350px">Metot</th>
                                    <th class="min-w-350px">Açıklama</th>
                                </tr>
                                </thead>
                                <tbody class="text-body fw-semibold">
                                <tr>
                                    <td class="ps-5"><code>DateTimeModule::ConvertTrDate($date, $hasClock=true)</code></td>
                                    <td><code>Y-m-d H:i:s</code> formatını <code>d.m.Y H:i:s</code> (Türk tarih formatı) olarak döndürür.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>DateTimeModule::ago($date, $lang='tr')</code></td>
                                    <td>"5 dakika önce", "2 gün sonra" gibi okunabilir zaman farkı döndürür. TR ve EN dil desteği.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>DateTimeModule::FormatDate($date, $format, $lang='tr')</code></td>
                                    <td>Tarihi istenilen formata ve dile göre biçimlendirir (TR/EN ay isimleri).</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>DateTimeModule::DiffDay($date_first, $date_last, $now=0)</code></td>
                                    <td>İki tarih arasındaki gün farkını döndürür. <code>$now=1</code> ise bugünden fark hesaplar.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>DateTimeModule::CalculateTotalTime($array)</code></td>
                                    <td><code>['hour'=>x, 'minute'=>y, 'second'=>z]</code> dizisindeki süreyi <code>HH:MM:SS</code> formatına çevirir.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>DateTimeModule::dateRangeConvertFormat($date_range)</code></td>
                                    <td><code>dd/mm/yyyy-dd/mm/yyyy</code> tarih aralığını <code>yyyy-mm-dd|yyyy-mm-dd</code> formatına dönüştürür.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>DateTimeModule::isBetweenDate($date, $start, $end)</code></td>
                                    <td>Tarihin iki tarih arasında olup olmadığını kontrol eder. <code>1</code> veya <code>0</code> döner.</td>
                                </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="vnl-code">
                            <pre class="mb-0" style="background: transparent; white-space: pre-wrap;"><code>echo DateTimeModule::ConvertTrDate('2024-06-15 10:30:00');
// → "15.06.2024 10:30:00"

echo DateTimeModule::ago('2024-06-10 08:00:00', 'tr');
// → "5 gün önce"

$gg = DateTimeModule::DiffDay('2024-01-01', '2024-12-31');
// → 364
</code></pre>
                        </div>
                    </div>
                    <!--end::DateTimeModule-->

                    <div class="separator separator-dashed my-8"></div>

                    <!--begin::TextGeneralModule-->
                    <div class="mb-10">
                        <div class="d-flex align-items-center mb-5">
                            <span class="badge badge-circle badge-info me-3 fs-5">T</span>
                            <h3 class="fw-bold text-body-emphasis mb-0">TextGeneralModule</h3>
                            <span class="text-muted fs-7 ms-3 font-monospace">vnl/modules/TextGeneralModule.php</span>
                        </div>
                        <p class="fs-5 text-body mb-4">Metin işleme, sayı formatlama ve URL temizleme yardımcıları.</p>

                        <div class="table-responsive border rounded bg-body-tertiary mb-5">
                            <table class="table align-middle table-row-dashed fs-6 gy-3 mb-0">
                                <thead>
                                <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0 border-bottom">
                                    <th class="ps-5 min-w-320px">Metot</th>
                                    <th class="min-w-350px">Açıklama</th>
                                </tr>
                                </thead>
                                <tbody class="text-body fw-semibold">
                                <tr>
                                    <td class="ps-5"><code>TextGeneralModule::trToUpper($text)</code></td>
                                    <td>Türkçe karakter duyarlı büyük harfe çevirir (ı→I, i→İ vb.).</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>TextGeneralModule::trToLower($text)</code></td>
                                    <td>Türkçe karakter duyarlı küçük harfe çevirir.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>TextGeneralModule::SafeURL($text)</code></td>
                                    <td>Metni URL güvenli slug'a dönüştürür. Türkçe karakterleri İngilizce karşılıklarına çevirir, boşlukları <code>-</code> yapar.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>TextGeneralModule::LimitString($str, $length)</code></td>
                                    <td>Metni belirtilen uzunlukta keser ve <code>...</code> ekler.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>TextGeneralModule::NumberFormatTR($val, $decimals)</code></td>
                                    <td>Sayıyı Türk formatında döndürür: <code>1.234,56</code></td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>TextGeneralModule::NumberFormatEN($val, $decimals)</code></td>
                                    <td>Sayıyı İngiliz formatında döndürür: <code>1,234.56</code></td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>TextGeneralModule::FloatFormat($val, $digits, ...)</code></td>
                                    <td>Ondalıklı sayıyı belirtilen basamakla biçimlendirir.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>TextGeneralModule::AsInt($val, $default=0)</code></td>
                                    <td>Değer geçerli bir sayı değilse <code>$default</code> döndürür.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>TextGeneralModule::AsString($val, $default='')</code></td>
                                    <td>Değer null/boş ise <code>$default</code> döndürür.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>TextGeneralModule::RandomTextGenerator($len, $count, $chars)</code></td>
                                    <td>Rastgele metin üretir. <code>$chars</code>: <code>'l,u,n,s'</code> (küçük, büyük, sayı, sembol).</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>TextGeneralModule::ConvertBytesToReadableFormat($bytes)</code></td>
                                    <td>Byte değerini okunabilir formata çevirir: <code>1.25 MB</code>, <code>3.40 GB</code>.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>TextGeneralModule::SafeText($content)</code></td>
                                    <td>HTML entity decode + stripslashes uygular. Veritabanından gelen veriyi view'a basmadan önce kullanın.</td>
                                </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="vnl-code">
                            <pre class="mb-0" style="background: transparent; white-space: pre-wrap;"><code>// URL slug oluşturma
$slug = TextGeneralModule::SafeURL('Ürün Adı & Açıklama');
// → "urun-adi-aciklama"

// Metin kısaltma
echo TextGeneralModule::LimitString($uzun_metin, 100);

// Sayı formatı
echo TextGeneralModule::NumberFormatTR(1234567.89, 2);
// → "1.234.567,89"
</code></pre>
                        </div>
                    </div>
                    <!--end::TextGeneralModule-->

                    <div class="separator separator-dashed my-8"></div>

                    <!--begin::FileManagerModule-->
                    <div class="mb-10">
                        <div class="d-flex align-items-center mb-5">
                            <span class="badge badge-circle badge-warning me-3 fs-5">F</span>
                            <h3 class="fw-bold text-body-emphasis mb-0">FileManagerModule</h3>
                            <span class="text-muted fs-7 ms-3 font-monospace">vnl/modules/FileManagerModule.php</span>
                        </div>
                        <p class="fs-5 text-body mb-4">Dosya yükleme, silme ve disk alanı işlemleri.</p>

                        <div class="table-responsive border rounded bg-body-tertiary mb-5">
                            <table class="table align-middle table-row-dashed fs-6 gy-3 mb-0">
                                <thead>
                                <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0 border-bottom">
                                    <th class="ps-5 min-w-380px">Metot</th>
                                    <th class="min-w-300px">Açıklama</th>
                                </tr>
                                </thead>
                                <tbody class="text-body fw-semibold">
                                <tr>
                                    <td class="ps-5"><code>FileManagerModule::UploadFile($dest, $file, $allows, $newName)</code></td>
                                    <td>Dosyayı yükler. Dizin yoksa oluşturur. <code>$allows</code>: <code>'pdf,xlsx'</code> veya <code>'all'</code>. Başarıda dosya adı, hata durumunda boş string döner.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>FileManagerModule::DeleteFile($location)</code></td>
                                    <td>Dosyayı fiziksel olarak siler. Başarıda <code>true</code> döner.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>FileManagerModule::IFExistsChangeFileName($dest, $file)</code></td>
                                    <td>Aynı isimde dosya varsa otomatik <code>dosya-1.pdf</code>, <code>dosya-2.pdf</code> şeklinde isim üretir.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>FileManagerModule::GetDiskSpaceText($mb)</code></td>
                                    <td>MB değerini okunabilir formata çevirir: 1024 MB üstü GB olarak gösterilir.</td>
                                </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="vnl-code">
                            <pre class="mb-0" style="background: transparent; white-space: pre-wrap;"><code>// Action içinde dosya yükleme
$fileReq = new FileRequest();   // $_FILES'i normalize eder, dosya adini temizler
$dosya   = $fileReq->get('dosya');
$hedef = getDocumentRoot() . 'files/belgeler/' . Auth::User()->Id . '/';

$dosyaAdi = FileManagerModule::UploadFile($hedef, $dosya, 'pdf,docx,xlsx');
if (empty($dosyaAdi)) {
    returnErrorMessage('Geçersiz dosya formatı.');
}

// Kaydet
$belge->DosyaYolu = 'files/belgeler/' . Auth::User()->Id . '/' . $dosyaAdi;
$belge->save();

// Silme
FileManagerModule::DeleteFile(getDocumentRoot() . $belge->DosyaYolu);
</code></pre>
                        </div>
                    </div>
                    <!--end::FileManagerModule-->

                    <div class="separator separator-dashed my-8"></div>

                    <!--begin::CurlModule-->
                    <div class="mb-10">
                        <div class="d-flex align-items-center mb-5">
                            <span class="badge badge-circle badge-danger me-3 fs-5">C</span>
                            <h3 class="fw-bold text-body-emphasis mb-0">CurlModule</h3>
                            <span class="text-muted fs-7 ms-3 font-monospace">vnl/modules/CurlModule.php</span>
                        </div>
                        <p class="fs-5 text-body mb-4">Dış servislere HTTP isteği göndermek için kullanılır. Form-encoded, JSON ve Basic Auth destekler.</p>

                        <div class="table-responsive border rounded bg-body-tertiary mb-5">
                            <table class="table align-middle table-row-dashed fs-6 gy-3 mb-0">
                                <thead>
                                <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0 border-bottom">
                                    <th class="ps-5 min-w-340px">Metot</th>
                                    <th class="min-w-300px">Açıklama</th>
                                </tr>
                                </thead>
                                <tbody class="text-body fw-semibold">
                                <tr>
                                    <td class="ps-5"><code>CurlModule::Post($url, $params, $content_type, $auth)</code></td>
                                    <td>HTTP POST isteği gönderir. Content-type: <code>application/x-www-form-urlencoded</code> veya <code>application/json</code>. Ham yanıt string döner.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>CurlModule::Curl($url, $params, $content_type, $auth)</code></td>
                                    <td>HTTP GET isteği gönderir.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>CurlModule::GetCurlPage($url, $timeout, $is_json)</code></td>
                                    <td>URL'den içerik çeker. <code>$is_json=true</code> ise JSON decode ederek array döndürür.</td>
                                </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="vnl-code">
                            <pre class="mb-0" style="background: transparent; white-space: pre-wrap;"><code>// JSON API'ye POST
$yanit = CurlModule::Post(
    'https://api.example.com/orders',
    ['order_id' => 123, 'status' => 'confirmed'],
    'application/json'
);
$sonuc = json_decode($yanit, true);

// Basic Auth ile
$yanit = CurlModule::Post(
    'https://api.example.com/data',
    ['key' => 'value'],
    'application/x-www-form-urlencoded',
    ['username' => 'user', 'password' => 'pass']
);

// JSON endpoint'ten veri çekme
$data = CurlModule::GetCurlPage('https://api.example.com/products', 5, true);
</code></pre>
                        </div>
                    </div>
                    <!--end::CurlModule-->

                    <div class="separator separator-dashed my-8"></div>

                    <!--begin::LogModule-->
                    <div class="mb-10">
                        <div class="d-flex align-items-center mb-5">
                            <span class="badge badge-circle badge-dark me-3 fs-5">L</span>
                            <h3 class="fw-bold text-body-emphasis mb-0">LogModule</h3>
                            <span class="text-muted fs-7 ms-3 font-monospace">vnl/modules/LogModule.php</span>
                        </div>
                        <p class="fs-5 text-body mb-4">Günlük bazında log dosyası oluşturur. Her gün ayrı bir <code>log_Y-m-d.log</code> dosyası oluşturulur.</p>

                        <div class="table-responsive border rounded bg-body-tertiary mb-5">
                            <table class="table align-middle table-row-dashed fs-6 gy-3 mb-0">
                                <thead>
                                <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0 border-bottom">
                                    <th class="ps-5 min-w-320px">Metot</th>
                                    <th class="min-w-120px">Hedef Klasör</th>
                                    <th class="min-w-250px">Açıklama</th>
                                </tr>
                                </thead>
                                <tbody class="text-body fw-semibold">
                                <tr>
                                    <td class="ps-5"><code>LogModule::SetMysqErrorlLog($msg)</code></td>
                                    <td><code>mysql-log/</code></td>
                                    <td>MySQL hata loglarını yazar.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>LogModule::SetMysqlLog($msg)</code></td>
                                    <td><code>mysql-log/execution/</code></td>
                                    <td>SQL sorgu execution loglarını yazar.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>LogModule::SetEventLog($msg)</code></td>
                                    <td><code>event-log/</code></td>
                                    <td>Genel olay loglarını yazar. Kullanıcı aksiyonları, sistem olayları için uygundur.</td>
                                </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="vnl-code">
                            <pre class="mb-0" style="background: transparent; white-space: pre-wrap;"><code>// Önemli bir olayı logla
LogModule::SetEventLog(
    '[' . date('Y-m-d H:i:s') . '] User #' . Auth::User()->Id . ' deleted product #' . $product_id
);

// Hata logla
LogModule::SetMysqErrorlLog(
    '[' . date('H:i:s') . '] Sorgu hatası: ' . $e->getMessage()
);
</code></pre>
                        </div>
                    </div>
                    <!--end::LogModule-->

                    <div class="separator separator-dashed my-8"></div>

                    <!--begin::URLModule-->
                    <div class="mb-10">
                        <div class="d-flex align-items-center mb-5">
                            <span class="badge badge-circle badge-secondary me-3 fs-5">U</span>
                            <h3 class="fw-bold text-body-emphasis mb-0">URLModule</h3>
                            <span class="text-muted fs-7 ms-3 font-monospace">vnl/modules/URLModule.php</span>
                        </div>
                        <p class="fs-5 text-body mb-4">Mevcut sayfa URL bilgisine erişmek için yardımcılar.</p>

                        <div class="table-responsive border rounded bg-body-tertiary mb-5">
                            <table class="table align-middle table-row-dashed fs-6 gy-3 mb-0">
                                <thead>
                                <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0 border-bottom">
                                    <th class="ps-5 min-w-280px">Metot</th>
                                    <th class="min-w-350px">Açıklama</th>
                                </tr>
                                </thead>
                                <tbody class="text-body fw-semibold">
                                <tr>
                                    <td class="ps-5"><code>URLModule::getPageName()</code></td>
                                    <td>Mevcut sayfanın adını döndürür. <code>route(0)</code> ile aynı sonucu verir.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>URLModule::getCurrentURL()</code></td>
                                    <td>Tam URL'yi döndürür: <code>https://site.com/kullanici</code></td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <!--end::URLModule-->

                    <div class="separator separator-dashed my-8"></div>

                    <!--begin::ArrayModule-->
                    <div class="mb-10">
                        <div class="d-flex align-items-center mb-5">
                            <span class="badge badge-circle badge-soft-secondary me-3 fs-5">A</span>
                            <h3 class="fw-bold text-body-emphasis mb-0">ArrayModule</h3>
                            <span class="text-muted fs-7 ms-3 font-monospace">vnl/modules/ArrayModule.php</span>
                        </div>
                        <p class="fs-5 text-body mb-4">Dizi işleme yardımcıları.</p>

                        <div class="table-responsive border rounded bg-body-tertiary mb-5">
                            <table class="table align-middle table-row-dashed fs-6 gy-3 mb-0">
                                <thead>
                                <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0 border-bottom">
                                    <th class="ps-5 min-w-380px">Metot</th>
                                    <th class="min-w-300px">Açıklama</th>
                                </tr>
                                </thead>
                                <tbody class="text-body fw-semibold">
                                <tr>
                                    <td class="ps-5"><code>ArrayModule::array_merge_intersect_key($arr1, $arr2)</code></td>
                                    <td>İki dizideki ortak anahtarlar için <code>$arr2</code>'nin değerleriyle <code>$arr1</code>'i günceller. <code>$arr2</code> boşsa <code>$arr1</code> olduğu gibi döner.</td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <!--end::ArrayModule-->

                    <div class="separator separator-dashed my-8"></div>

                    <!--begin::PDFModule-->
                    <div class="mb-10">
                        <div class="d-flex align-items-center mb-5">
                            <span class="badge badge-circle badge-danger me-3 fs-5">P</span>
                            <h3 class="fw-bold text-body-emphasis mb-0">PDFModule</h3>
                            <span class="text-muted fs-7 ms-3 font-monospace">vnl/modules/PDFModule.php</span>
                        </div>
                        <p class="fs-5 text-body mb-4">PDF oluşturma servisiyle entegrasyon. Uzak print URL'ini çağırarak PDF üretir.</p>

                        <div class="table-responsive border rounded bg-body-tertiary mb-5">
                            <table class="table align-middle table-row-dashed fs-6 gy-3 mb-0">
                                <thead>
                                <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0 border-bottom">
                                    <th class="ps-5 min-w-420px">Metot</th>
                                    <th class="min-w-300px">Açıklama</th>
                                </tr>
                                </thead>
                                <tbody class="text-body fw-semibold">
                                <tr>
                                    <td class="ps-5"><code>PDFModule::CreatePDF($orientation, $printurl, $title, $prefix, $filename, $queue_id)</code></td>
                                    <td><code>$orientation</code>: <code>'Portrait'</code> veya <code>'Landscape'</code>. Print URL'i çağırarak PDF üretir ve dosya adını döndürür.</td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <!--end::PDFModule-->

                    <div class="separator separator-dashed my-8"></div>

                    <!--begin::SocialLinkModule-->
                    <div class="mb-10">
                        <div class="d-flex align-items-center mb-5">
                            <span class="badge badge-circle badge-primary me-3 fs-5">S</span>
                            <h3 class="fw-bold text-body-emphasis mb-0">SocialLinkModule</h3>
                            <span class="text-muted fs-7 ms-3 font-monospace">vnl/modules/SocialLinkModule.php</span>
                        </div>
                        <p class="fs-5 text-body mb-4">Sosyal medya link işlemleri.</p>

                        <div class="table-responsive border rounded bg-body-tertiary mb-5">
                            <table class="table align-middle table-row-dashed fs-6 gy-3 mb-0">
                                <thead>
                                <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0 border-bottom">
                                    <th class="ps-5 min-w-300px">Metot</th>
                                    <th class="min-w-350px">Açıklama</th>
                                </tr>
                                </thead>
                                <tbody class="text-body fw-semibold">
                                <tr>
                                    <td class="ps-5"><code>SocialLinkModule::IsYoutubeLink($link)</code></td>
                                    <td>YouTube linki geçerliyse video ID'sini döndürür, değilse boş string döner. Embed iframe oluşturmak için kullanılır.</td>
                                </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="vnl-code">
                            <pre class="mb-0" style="background: transparent; white-space: pre-wrap;"><code>$videoId = SocialLinkModule::IsYoutubeLink($url);
if (!empty($videoId)) {
    echo '&lt;iframe src="https://www.youtube.com/embed/' . $videoId . '"&gt;&lt;/iframe&gt;';
}
</code></pre>
                        </div>
                    </div>
                    <!--end::SocialLinkModule-->

                    <div class="separator separator-dashed my-8"></div>

                    <!--begin::MailModule-->
                    <div class="mb-0">
                        <div class="d-flex align-items-center mb-5">
                            <span class="badge badge-circle badge-success me-3 fs-5">M</span>
                            <h3 class="fw-bold text-body-emphasis mb-0">MailModule</h3>
                            <span class="text-muted fs-7 ms-3 font-monospace">vnl/modules/MailModule.php</span>
                        </div>
                        <p class="fs-5 text-body mb-4">PHPMailer üzerinden SMTP e-posta gönderimi. SMTP ayarları modül içinde yapılandırılır.</p>

                        <div class="table-responsive border rounded bg-body-tertiary mb-5">
                            <table class="table align-middle table-row-dashed fs-6 gy-3 mb-0">
                                <thead>
                                <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0 border-bottom">
                                    <th class="ps-5 min-w-320px">Metot</th>
                                    <th class="min-w-350px">Açıklama</th>
                                </tr>
                                </thead>
                                <tbody class="text-body fw-semibold">
                                <tr>
                                    <td class="ps-5"><code>MailModule::SendMail(MailObject $mailObject)</code></td>
                                    <td>SMTP üzerinden e-posta gönderir. <code>MailObject</code>: <code>to, title, content, media, toName</code> alanlarını içerir. <code>MAIL_HOST</code> boşsa gönderim yapılmaz, hata loglanır.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>MailModule::isValidEmail($email)</code></td>
                                    <td>E-posta adresinin geçerli olup olmadığını <code>filter_var</code> ile doğrular. <code>bool</code> döner.</td>
                                </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="alert alert-warning d-flex align-items-center p-4">
                            <i class="fa-solid fa-circle-info fs-2 text-warning me-3"></i>
                            <span class="text-body fs-6">SMTP bilgilerini (<code>Host</code>, <code>Username</code>, <code>Password</code>, <code>Port</code>) modül içinde yapılandırın. Üretim için bu değerleri config sistemi üzerinden okumanız önerilir.</span>
                        </div>
                    </div>
                    <!--end::MailModule-->

                </div>
            </div>

        </div>
    </div>
</div>

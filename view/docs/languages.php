<div class="d-flex flex-column flex-column-fluid">
    <div id="kt_app_content" class="app-content flex-column-fluid">
        <div id="kt_app_content_container" class="app-container container-xxl">

            <div class="vnl-page-hero mb-12 mt-6">
                <div class="vnl-overline mb-3">// docs/languages</div>
                <h1 class="fs-2hx fw-bold mb-4 text-body-emphasis">Dil ve Çoklu Dil Desteği (i18n)</h1>
                <div class="vnl-lead">VNL, JSON tabanlı bir dil sistemi kullanır. Dil dosyaları <code>languages/</code> klasöründe tutulur ve <strong>tek doğruluk kaynağıdır</strong> — ana dosya (<code>languages/tr.json</code>) artı 3.4.0'dan beri modül başına parçalar (<code>languages/tr/*.json</code>); aktif dil session üzerinden yönetilir. <code>lang('ANAHTAR')</code> söz dizimi ile herhangi bir yerden dil anahtarlarına erişilebilir.</div>
            </div>

            <div class="alert alert-info d-flex flex-column flex-sm-row p-5 mb-10">
                <i class="fa-solid fa-globe fs-2hx text-info me-4 mb-5 mb-sm-0"></i>
                <div class="d-flex flex-column pe-0 pe-sm-10">
                    <h5 class="mb-1">Desteklenen Diller</h5>
                    <span>Varsayılan olarak <span class="badge badge-soft-primary">Türkçe (tr)</span> ve <span class="badge badge-soft-success">İngilizce (en)</span> desteklenir. Yeni bir dil eklemek için JSON dosyası oluşturup <code>Languages</code> enum'una değer eklemek yeterlidir.</span>
                </div>
            </div>

            <div class="card mb-10">
                <div class="card-body p-10">

                    <!--begin::Section 1-->
                    <div class="mb-10">
                        <h3 class="fw-bold text-body-emphasis mb-4">1. Genel Bakış</h3>
                        <p class="fs-5 text-body mb-4">
                            Dil sistemi uygulama başlangıcında <code>vnl/bootstrap.php</code> içinde başlatılır. Session'da kayıtlı dil tercihine göre ilgili JSON dosyası okunur ve bellekte bir sözlük olarak tutulur. Anahtarlar sınıfta ayrıca tanımlanmaz — bu yüzden dosya ile kod arasında kayma oluşamaz.
                        </p>

                        <div class="vnl-code mb-5">
                            <pre class=" mb-0" style="background: transparent; white-space: pre-wrap;"><code>// vnl/bootstrap.php içinde otomatik başlatma
Language::set(Language::GetLanguage());

// Language::GetLanguage() — session'daki dil tercihi
$mevcutDil = Language::GetLanguage();  // Languages::TR veya Languages::EN

// Language::current() — o an yüklü olan dil
$yuklu = Language::current();
</code></pre>
                        </div>

                        <div class="row g-5">
                            <div class="col-md-4">
                                <div class="border border-dashed rounded p-5 h-100 text-center">
                                    <i class="bi bi-filetype-json fs-2x text-warning mb-3 d-block"></i>
                                    <h6 class="fw-bold mb-2">JSON Dosyaları</h6>
                                    <code class="fs-7">languages/tr.json</code><br>
                                    <code class="fs-7">languages/en.json</code><br>
                                    <code class="fs-7">languages/&lt;dil&gt;/*.json</code> <span class="text-muted fs-8">(parçalar, 3.4.0)</span>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="border border-dashed rounded p-5 h-100 text-center">
                                    <i class="fa-solid fa-cubes fs-2x text-primary mb-3"></i>
                                    <h6 class="fw-bold mb-2">Language Sınıfı</h6>
                                    <span class="text-muted fs-7">JSON anahtarları bellekte sözlük olarak tutulur. <code>lang('ANAHTAR')</code> ile erişilir.</span>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="border border-dashed rounded p-5 h-100 text-center">
                                    <i class="fa-solid fa-gear fs-2x text-success mb-3"></i>
                                    <h6 class="fw-bold mb-2">Session Entegrasyonu</h6>
                                    <span class="text-muted fs-7">Kullanıcı dil tercihi session'da saklanır; her istekte doğru dil yüklenir.</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!--end::Section 1-->

                    <div class="separator separator-dashed my-8"></div>

                    <!--begin::Section 2-->
                    <div class="mb-10">
                        <h3 class="fw-bold text-body-emphasis mb-4">2. <code>Languages</code> Enum</h3>
                        <p class="fs-5 text-body mb-4">
                            <code>Languages</code> enum'u desteklenen dilleri tanımlar. <code>Language::base_language</code> sabit değeri temel dili belirtir; session'da dil bilgisi yoksa bu dil kullanılır.
                        </p>

                        <div class="vnl-code mb-5">
                            <pre class=" mb-0" style="background: transparent; white-space: pre-wrap;"><code>enum Languages: string
{
    case TR = 'tr';
    case EN = 'en';
}

// Language sınıfında temel dil
Language::base_language = Languages::TR;

// Kullanım
Language::set(Languages::TR); // Türkçe yükle
Language::set(Languages::EN); // İngilizce yükle
</code></pre>
                        </div>
                    </div>
                    <!--end::Section 2-->

                    <div class="separator separator-dashed my-8"></div>

                    <!--begin::Section 3-->
                    <div class="mb-10">
                        <h3 class="fw-bold text-body-emphasis mb-4">3. Dil Dosyası Yapısı</h3>
                        <p class="fs-5 text-body mb-4">
                            JSON dosyaları anahtar-değer çiftlerinden oluşan tek bir dizi içerir. Anahtarlar başka hiçbir yerde tanımlanmaz — dosyaya yazdığınız an kullanılabilir hale gelir.
                        </p>

                        <div class="row g-5 mb-5">
                            <div class="col-md-6">
                                <h6 class="fw-bold mb-3"><span class="badge badge-soft-primary me-2">tr.json</span></h6>
                                <div class="vnl-code">
                                    <pre class="mb-0" style="background: transparent; white-space: pre-wrap;"><code>[
    {
        "ERR_GENERAL": "Bir hata oluştu.",
        "PLEASE_FILL_THE_REQUIRED_FIELD": "%s alanını doldurunuz.",
        "DATATABLES_PROCESSING": "Yükleniyor...",
        "SUCCESS": "İşlem başarılı.",
        "DELETE_SUCCESS": "Kayıt silindi.",
        "UPDATE_SUCCESS": "Güncelleme yapıldı."
    }
]</code></pre>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <h6 class="fw-bold mb-3"><span class="badge badge-soft-success me-2">en.json</span></h6>
                                <div class="vnl-code">
                                    <pre class="mb-0" style="background: transparent; white-space: pre-wrap;"><code>[
    {
        "ERR_GENERAL": "An error occurred.",
        "PLEASE_FILL_THE_REQUIRED_FIELD": "Please fill in %s.",
        "DATATABLES_PROCESSING": "Loading...",
        "SUCCESS": "Operation successful.",
        "DELETE_SUCCESS": "Record deleted.",
        "UPDATE_SUCCESS": "Update completed."
    }
]</code></pre>
                                </div>
                            </div>
                        </div>

                        <div class="alert alert-warning d-flex align-items-center p-4">
                            <i class="fa-solid fa-circle-info fs-2 text-warning me-3"></i>
                            <span class="text-body fs-6">Bir anahtar ikinci dilde eksikse metin <strong>temel dilden</strong> karşılanır ve durum hata günlüğüne yazılır — boş metin görünmez. Ayrıntı için 9. bölüme bakın.</span>
                        </div>

                        <h5 class="fw-bold text-body-emphasis mt-8 mb-3">Dil parçaları — <code>languages/&lt;dil&gt;/*.json</code> (3.4.0)</h5>
                        <p class="fs-6 text-body mb-4">
                            Tek dil dosyası, paralel çalışan her modülün aynı dosyada çakışması demekti. <code>Language</code> ana dosyayı (<code>languages/tr.json</code>) okuduktan sonra <code>languages/tr/</code> altındaki her <code>*.json</code>'u <b>ad sırasıyla</b> sözlüğe ekler. Biçim ana dosyayla aynıdır. Aynı anahtar iki yerde varsa <b>sonra okunan dosya kazanır</b> ve günlüğe <code>[VNL][Language]</code> uyarısı düşer. Her parçanın öbür dillerde de karşılığı olmalıdır (<code>languages/en/domains.json</code>).
                        </p>
                        <div class="vnl-code mb-0">
                            <pre class="mb-0" style="background: transparent; white-space: pre-wrap;"><code>languages/
├── tr.json            ← ortak anahtarlar (BTN_SAVE, ERR_CSRF ...)
├── en.json
├── tr/
│   └── domains.json   ← { "DOMAINS_TITLE": "Alan adları", ... }
└── en/
    └── domains.json   ← { "DOMAINS_TITLE": "Domains", ... }</code></pre>
                        </div>
                    </div>
                    <!--end::Section 3-->

                    <div class="separator separator-dashed my-8"></div>

                    <!--begin::Section 4-->
                    <div class="mb-10">
                        <h3 class="fw-bold text-body-emphasis mb-4">4. Dil Anahtarlarına Erişim</h3>
                        <p class="fs-5 text-body mb-4">
                            Ana erişim biçimi <code>lang()</code> yardımcı fonksiyonudur. Ek argümanlar <code>sprintf</code> yer tutucularını doldurur; ayrıca <code>sprintf</code> çağırmanız gerekmez.
                        </p>

                        <div class="vnl-code mb-5">
                            <pre class=" mb-0" style="background: transparent; white-space: pre-wrap;"><code>// Basit kullanım
echo lang('ERR_GENERAL');
// Türkçe: "Bir hata oluştu."

// Parametreli mesaj — argümanlar sprintf yer tutucularına gider
echo lang('PLEASE_FILL_IN_FIELD', 'Ad Soyad');
// Türkçe: "Lütfen Ad Soyad alanını doldurun."

echo lang('PLEASE_DO_NOT_PASS_X_CHAR_LIMIT_IN_FIELD', 'Açıklama', 255);
// Türkçe: "Açıklama alanında en fazla 255 karakter girebilirsiniz."

// Validator içinde kullanım
if (empty($param)) {
    throw new StandartException(
        $region
            ? lang('PLEASE_FILL_IN_FIELD', $region)
            : lang('PLEASE_FILL_THE_REQUIRED_FIELD')
    );
}

// returnErrorMessage ile
returnErrorMessage(lang('ERR_PERM'));

// Sınıf üzerinden eşdeğer biçimler
echo Language::get('ERR_GENERAL');
echo Language::PLEASE_FILL_IN_FIELD('Ad Soyad');
</code></pre>
                        </div>

                        <div class="alert alert-primary d-flex align-items-start p-4">
                            <i class="fa-solid fa-circle-info fs-2 text-primary me-3"></i>
                            <span class="text-body fs-6">
                                <code>Language::$ANAHTAR</code> statik özellik söz dizimi <strong>VNL 3.0 ile kaldırılmıştır</strong>. Eski projelerde <code>Language::$X</code> → <code>lang('X')</code> ve <code>sprintf(Language::$X, $a)</code> → <code>lang('X', $a)</code> dönüşümü yapılmalıdır.
                            </span>
                        </div>
                    </div>
                    <!--end::Section 4-->

                    <div class="separator separator-dashed my-8"></div>

                    <!--begin::Section 5-->
                    <div class="mb-10">
                        <h3 class="fw-bold text-body-emphasis mb-4">5. Dil Anahtarları</h3>
                        <p class="fs-5 text-body mb-4">
                            Sözlüğün tek doğruluk kaynağı dosyanın kendisidir; <code>languages/tr.json</code> şu an <b>171 anahtar</b> taşıyor (3.4.0). Aşağıdaki tablo onların tamamı değil, <b>çekirdeğin kendi çağırdıklarından</b> bir seçki — bunlar bir projede eksik kalırsa doğrulama ve hata mesajları anahtar adını basar.
                        </p>

                        <div class="alert alert-warning d-flex align-items-start p-4 mb-5">
                            <i class="fa-solid fa-triangle-exclamation fs-2 text-warning me-3 mt-1"></i>
                            <span class="text-body fs-6"><b>VNL 3.0'da <code>Language::$ANAHTAR</code> yazımı kaldırıldı.</b> Statik özellikler yerine sözlük API'si geldi: <code>lang('ANAHTAR')</code>. Eski yazımı kullanan kod, sınıfta o özellik artık tanımlı olmadığı için çalışmaz.</span>
                        </div>

                        <div class="table-responsive border rounded bg-body-tertiary mb-5">
                            <table class="table align-middle table-row-dashed fs-6 gy-4 mb-0">
                                <thead>
                                <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0 border-bottom">
                                    <th class="ps-5 min-w-280px">Anahtar</th>
                                    <th class="min-w-250px">tr.json karşılığı</th>
                                    <th class="min-w-300px">Nereden çağrılır</th>
                                </tr>
                                </thead>
                                <tbody class="text-body fw-semibold">
                                <tr>
                                    <td class="ps-5"><code>ERR_GENERAL</code></td>
                                    <td class="text-body-secondary">Bir hata oluştu.</td>
                                    <td>Spesifik bir mesaj yoksa kullanılan genel hata.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>ERR_PERM</code></td>
                                    <td class="text-body-secondary">Bu işlem için yetkiniz bulunmamaktadır.</td>
                                    <td>Yetki reddi.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>FUNCTION_NOT_FOUND</code></td>
                                    <td class="text-body-secondary">İşlev bulunamadı.</td>
                                    <td>Eylem dağıtıcısı: istenen metot yok.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>PLEASE_FILL_THE_REQUIRED_FIELD</code></td>
                                    <td class="text-body-secondary">Lütfen gerekli alanları doldurunuz.</td>
                                    <td><code>DefaultValidator::validateEmpty()</code> alan adı verilmeden çağrıldığında.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>PLEASE_FILL_IN_FIELD</code></td>
                                    <td class="text-body-secondary">Lütfen %s alanını doldurun.</td>
                                    <td>Aynısı, alan adıyla. <code>%s</code> yer tutucusunu <code>lang()</code>'in ek argümanı doldurur.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>PLEASE_SELECT_VALID_OPTION</code></td>
                                    <td class="text-body-secondary">Lütfen geçerli seçenekleri seçiniz.</td>
                                    <td><code>validateEmptyOption()</code> / <code>validateEmptyID()</code>.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>PLEASE_ENTER_VALID_DATE</code></td>
                                    <td class="text-body-secondary">Lütfen geçerli tarih giriniz.</td>
                                    <td><code>validateDate()</code> ve türevleri.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>PLEASE_ENTER_VALID_PHONE_NUMBER</code></td>
                                    <td class="text-body-secondary">Lütfen geçerli bir telefon numarası giriniz.</td>
                                    <td><code>validatePhone()</code>.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>EMAIL_ADDRESS_INVALID</code></td>
                                    <td class="text-body-secondary">Eposta adresi geçersiz.</td>
                                    <td><code>validateEmptyMail()</code>.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>PLEASE_DO_NOT_PASS_X_CHAR_LIMIT</code></td>
                                    <td class="text-body-secondary">Lütfen %s karaketer limitini geçmeyin.</td>
                                    <td><code>validateTextLength()</code>. Sınırı argüman olarak alır.</td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="separator separator-dashed my-8"></div>

                    <!--end::Section 5-->

                    <div class="separator separator-dashed my-8"></div>

                    <!--begin::Section 6-->
                    <div class="mb-10">
                        <h3 class="fw-bold text-body-emphasis mb-4">6. Dil Değiştirme</h3>
                        <p class="fs-5 text-body mb-4">
                            Kullanıcı dil tercihini değiştirmek için <code>Language::set(Languages::EN)</code> çağrısı yapılır. Bu çağrı session'ı günceller ve belirtilen dilin JSON dosyasını yükler.
                        </p>

                        <div class="vnl-code mb-5">
                            <pre class=" mb-0" style="background: transparent; white-space: pre-wrap;"><code>// İngilizceye geç
Language::set(Languages::EN);

// Türkçeye geri dön
Language::set(Languages::TR);

// Mevcut dili oku (session'dan)
$mevcutDil = Language::GetLanguage();
// Dönüş: Languages::TR veya Languages::EN
// Session yoksa Language::base_language döner (Languages::TR)

// Action içinde dil değiştirme örneği
public function dilDegistir(): void
{
    global $req;
    $dil = $req->post('dil'); // 'tr' veya 'en'

    $secilen = Languages::tryFrom($dil);
    if (!$secilen) throwErrorMessage(lang('ERR_INVALID_PARAMETERS_SENT'));

    Language::set($secilen);

    returnSuccessMessage(lang('SUC_GENERAL'));
}
</code></pre>
                        </div>
                    </div>
                    <!--end::Section 6-->

                    <div class="separator separator-dashed my-8"></div>

                    <!--begin::Section 7 — LSL Language Studio (prominent)-->
                    <div class="mb-10">
                        <div class="border border-2 border-primary rounded p-8 bg-primary-subtle mb-6">
                            <div class="d-flex align-items-center mb-5">
                                <i class="fa-solid fa-palette fs-3x text-primary me-4"></i>
                                <div>
                                    <h3 class="fw-bold text-primary mb-1">LSL Language Studio</h3>
                                    <span class="text-body-secondary fs-6">Dil dosyalarını görsel olarak yönetmek için önerilen araç</span>
                                </div>
                            </div>

                            <p class="fs-5 text-body mb-5">
                                Dil dosyalarını (<code>tr.json</code>, <code>en.json</code>) elle düzenlemek yerine <strong>LSL Language Studio</strong> uygulamasını kullanmanızı öneririz. Bu uygulama, VNL projelerine özel geliştirilmiş bir masaüstü dil editörüdür.
                            </p>

                            <div class="row g-5 mb-6">
                                <div class="col-md-4">
                                    <div class="bg-white rounded p-5 h-100 border border-dashed">
                                        <i class="fa-solid fa-eye fs-2x text-success mb-3"></i>
                                        <h6 class="fw-bold mb-2">Görsel Editör</h6>
                                        <span class="text-muted fs-7">JSON dosyalarını tablo formatında görüntüleyin ve düzenleyin. JSON söz dizimi bilgisi gerekmez.</span>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="bg-white rounded p-5 h-100 border border-dashed">
                                        <i class="fa-solid fa-arrows-rotate fs-2x text-primary mb-3"></i>
                                        <h6 class="fw-bold mb-2">Yan Yana Yönetim</h6>
                                        <span class="text-muted fs-7">Birden fazla dili aynı anda yan yana görüntüleyin; eksik çevirileri kolayca tespit edin.</span>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="bg-white rounded p-5 h-100 border border-dashed">
                                        <i class="fa-solid fa-file-arrow-down fs-2x text-warning mb-3"></i>
                                        <h6 class="fw-bold mb-2">VNL'ye Hazır Export</h6>
                                        <span class="text-muted fs-7">Düzenlenen dosyaları VNL'nin beklediği JSON formatında dışa aktarın.</span>
                                    </div>
                                </div>
                            </div>

                            <a href="https://github.com/mrekay/LS-Language-Studio" target="_blank" class="btn btn-primary btn-lg">
                                <i class="bi bi-github me-2"></i>
                                GitHub'dan İndir — mrekay/LS-Language-Studio
                            </a>
                        </div>
                    </div>
                    <!--end::Section 7-->

                    <div class="separator separator-dashed my-8"></div>

                    <!--begin::Section 8-->
                    <div class="mb-0">
                        <h3 class="fw-bold text-body-emphasis mb-4">8. Yeni Dil Anahtarı Ekleme</h3>
                        <p class="fs-5 text-body mb-4">
                            VNL 3.0 ile anahtar eklemek iki adıma indi — artık <code>Language</code> sınıfına dokunulmuyor:
                        </p>

                        <div class="border rounded p-5 bg-body-tertiary mb-5">
                            <div class="d-flex align-items-start mb-5">
                                <span class="badge badge-circle badge-primary me-4 mt-1 flex-shrink-0">1</span>
                                <div>
                                    <span class="fw-bold text-body">Tüm JSON dosyalarına anahtarı ekleyin</span>
                                    <div class="vnl-code mt-2">
                                        <pre class="mb-0" style="background: transparent; white-space: pre-wrap;"><code>// tr.json
"STOCK_INSUFFICIENT": "Yeterli stok bulunmamaktadır."

// en.json
"STOCK_INSUFFICIENT": "Insufficient stock available."</code></pre>
                                    </div>
                                </div>
                            </div>
                            <div class="d-flex align-items-start mb-0">
                                <span class="badge badge-circle badge-primary me-4 mt-1 flex-shrink-0">2</span>
                                <div>
                                    <span class="fw-bold text-body">Herhangi bir yerde kullanın</span>
                                    <div class="vnl-code mt-2">
                                        <pre class="mb-0" style="background: transparent; white-space: pre-wrap;"><code>throw new StandartException(lang('STOCK_INSUFFICIENT'));</code></pre>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="alert alert-success d-flex align-items-center p-4">
                            <i class="fa-solid fa-circle-check fs-2 text-success me-3"></i>
                            <span class="text-body fs-6">LSL Language Studio kullanıyorsanız tüm süreci uygulama üzerinden yürütebilirsiniz; kod tarafında yapılacak bir işlem kalmadı.</span>
                        </div>
                    </div>
                    <!--end::Section 8-->

                    <div class="separator separator-dashed my-8"></div>

                    <!--begin::Section 9-->
                    <div class="mb-0">
                        <h3 class="fw-bold text-body-emphasis mb-4">9. Eksik Anahtarlar ve Hata Dayanıklılığı</h3>
                        <p class="fs-5 text-body mb-4">
                            Dil sistemi hiçbir koşulda isteği düşürmez. Eksik anahtar, eksik çeviri, kayıp veya bozuk dosya — hepsi kademeli olarak karşılanır ve durum PHP hata günlüğüne <code>[VNL][Language]</code> etiketiyle yazılır.
                        </p>

                        <div class="table-responsive border rounded bg-body-tertiary mb-5">
                            <table class="table align-middle table-row-dashed fs-6 gy-4 mb-0">
                                <thead>
                                <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0 border-bottom">
                                    <th class="ps-5 min-w-300px">Durum</th>
                                    <th class="min-w-350px">Davranış</th>
                                </tr>
                                </thead>
                                <tbody class="text-body fw-semibold">
                                <tr>
                                    <td class="ps-5">Anahtar aktif dilde yok, temel dilde var</td>
                                    <td>Metin temel dilden karşılanır</td>
                                </tr>
                                <tr>
                                    <td class="ps-5">Anahtar hiçbir dilde yok (yazım hatası)</td>
                                    <td>Anahtarın kendisi döner: <code>STOCK_INSUFFICIENT</code></td>
                                </tr>
                                <tr>
                                    <td class="ps-5">Aktif dilin dosyası yok veya bozuk</td>
                                    <td>Tamamen temel dile düşülür</td>
                                </tr>
                                <tr>
                                    <td class="ps-5">Hiçbir dil dosyası okunamıyor</td>
                                    <td>Tüm anahtarlar kendi adlarını döner, istek çalışmaya devam eder</td>
                                </tr>
                                <tr>
                                    <td class="ps-5">Yer tutucu sayısı argümanlarla uyuşmuyor</td>
                                    <td>Metin ham haliyle döner, <code>ValueError</code> fırlatılmaz</td>
                                </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="vnl-code mb-5">
                            <pre class=" mb-0" style="background: transparent; white-space: pre-wrap;"><code>// Anahtar var mı?
if (lang_has('STOCK_INSUFFICIENT')) { /* ... */ }

// Kendi varsayılanınızla
$mesaj = lang_has('STOCK_INSUFFICIENT') ? lang('STOCK_INSUFFICIENT') : 'Stok yetersiz.';

// Tüm sözlük — tanılama ve ön yüze aktarım için
$sozluk = Language::all();

// Yükleme ve erişim sırasında tespit edilen sorunlar
print_r(Language::issues());</code></pre>
                        </div>

                        <div class="alert alert-success d-flex align-items-start p-4">
                            <i class="fa-solid fa-shield-halved fs-2 text-success me-3"></i>
                            <span class="text-body fs-6">
                                <strong>VNL 3.0 değişikliği:</strong> Anahtarlar artık statik özellik olarak tanımlanmadığı için dil dosyası ile kod arasında kayma oluşamaz. 2.4.0'a kadar tanımsız bir anahtar <code>Access to undeclared static property</code> ile isteği düşürüyordu; bu hata sınıfı tamamen ortadan kalktı.
                            </span>
                        </div>
                    </div>
                    <!--end::Section 9-->

                </div>
            </div>

        </div>
    </div>
</div>

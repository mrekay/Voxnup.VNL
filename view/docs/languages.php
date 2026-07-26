<div class="d-flex flex-column flex-column-fluid">
    <div id="kt_app_content" class="app-content flex-column-fluid">
        <div id="kt_app_content_container" class="app-container container-xxl">

            <div class="text-center mb-15 mt-10">
                <h1 class="fs-2hx fw-bold mb-5 text-gray-900">Dil ve Çoklu Dil Desteği (i18n)</h1>
                <div class="fs-5 text-muted fw-semibold w-lg-75 mx-auto">
                    VNL, JSON tabanlı bir dil sistemi kullanır. Dil dosyaları <code>libs/languages/</code> klasöründe tutulur ve <strong>tek doğruluk kaynağıdır</strong>; aktif dil session üzerinden yönetilir. <code>lang('ANAHTAR')</code> söz dizimi ile herhangi bir yerden dil anahtarlarına erişilebilir.
                </div>
            </div>

            <div class="alert bg-light-info border border-info d-flex flex-column flex-sm-row p-5 mb-10">
                <i class="ki-duotone ki-globe fs-2hx text-info me-4 mb-5 mb-sm-0"><span class="path1"></span><span class="path2"></span></i>
                <div class="d-flex flex-column pe-0 pe-sm-10">
                    <h5 class="mb-1">Desteklenen Diller</h5>
                    <span>Varsayılan olarak <span class="badge badge-light-primary">Türkçe (tr)</span> ve <span class="badge badge-light-success">İngilizce (en)</span> desteklenir. Yeni bir dil eklemek için JSON dosyası oluşturup <code>Languages</code> enum'una değer eklemek yeterlidir.</span>
                </div>
            </div>

            <div class="card shadow-sm border-0 mb-10">
                <div class="card-body p-10">

                    <!--begin::Section 1-->
                    <div class="mb-10">
                        <h3 class="fw-bolder text-dark mb-4">1. Genel Bakış</h3>
                        <p class="fs-5 text-gray-700 mb-4">
                            Dil sistemi uygulama başlangıcında <code>config_soft.php</code> içinde başlatılır. Session'da kayıtlı dil tercihine göre ilgili JSON dosyası okunur ve bellekte bir sözlük olarak tutulur. Anahtarlar sınıfta ayrıca tanımlanmaz — bu yüzden dosya ile kod arasında kayma oluşamaz.
                        </p>

                        <div class="bg-dark rounded p-5 mb-5">
                            <pre class="text-white fs-6 font-monospace mb-0" style="background: transparent; white-space: pre-wrap;"><code>// config_soft.php içinde otomatik başlatma
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
                                    <code class="fs-7">libs/languages/tr.json</code><br>
                                    <code class="fs-7">libs/languages/en.json</code>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="border border-dashed rounded p-5 h-100 text-center">
                                    <i class="ki-duotone ki-abstract-26 fs-2x text-primary mb-3"><span class="path1"></span><span class="path2"></span></i>
                                    <h6 class="fw-bold mb-2">Language Sınıfı</h6>
                                    <span class="text-muted fs-7">JSON anahtarları bellekte sözlük olarak tutulur. <code>lang('ANAHTAR')</code> ile erişilir.</span>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="border border-dashed rounded p-5 h-100 text-center">
                                    <i class="ki-duotone ki-setting-2 fs-2x text-success mb-3"><span class="path1"></span><span class="path2"></span></i>
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
                        <h3 class="fw-bolder text-dark mb-4">2. <code>Languages</code> Enum</h3>
                        <p class="fs-5 text-gray-700 mb-4">
                            <code>Languages</code> enum'u desteklenen dilleri tanımlar. <code>Language::base_language</code> sabit değeri temel dili belirtir; session'da dil bilgisi yoksa bu dil kullanılır.
                        </p>

                        <div class="bg-dark rounded p-5 mb-5">
                            <pre class="text-white fs-6 font-monospace mb-0" style="background: transparent; white-space: pre-wrap;"><code>enum Languages: string
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
                        <h3 class="fw-bolder text-dark mb-4">3. Dil Dosyası Yapısı</h3>
                        <p class="fs-5 text-gray-700 mb-4">
                            JSON dosyaları anahtar-değer çiftlerinden oluşan tek bir dizi içerir. Anahtarlar başka hiçbir yerde tanımlanmaz — dosyaya yazdığınız an kullanılabilir hale gelir.
                        </p>

                        <div class="row g-5 mb-5">
                            <div class="col-md-6">
                                <h6 class="fw-bold mb-3"><span class="badge badge-light-primary me-2">tr.json</span></h6>
                                <div class="bg-dark rounded p-5">
                                    <pre class="text-white fs-7 font-monospace mb-0" style="background: transparent; white-space: pre-wrap;"><code>[
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
                                <h6 class="fw-bold mb-3"><span class="badge badge-light-success me-2">en.json</span></h6>
                                <div class="bg-dark rounded p-5">
                                    <pre class="text-white fs-7 font-monospace mb-0" style="background: transparent; white-space: pre-wrap;"><code>[
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

                        <div class="alert bg-light-warning border border-warning d-flex align-items-center p-4">
                            <i class="ki-duotone ki-information-5 fs-2 text-warning me-3"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
                            <span class="text-gray-800 fs-6">Bir anahtar ikinci dilde eksikse metin <strong>temel dilden</strong> karşılanır ve durum hata günlüğüne yazılır — boş metin görünmez. Ayrıntı için 9. bölüme bakın.</span>
                        </div>
                    </div>
                    <!--end::Section 3-->

                    <div class="separator separator-dashed my-8"></div>

                    <!--begin::Section 4-->
                    <div class="mb-10">
                        <h3 class="fw-bolder text-dark mb-4">4. Dil Anahtarlarına Erişim</h3>
                        <p class="fs-5 text-gray-700 mb-4">
                            Ana erişim biçimi <code>lang()</code> yardımcı fonksiyonudur. Ek argümanlar <code>sprintf</code> yer tutucularını doldurur; ayrıca <code>sprintf</code> çağırmanız gerekmez.
                        </p>

                        <div class="bg-dark rounded p-5 mb-5">
                            <pre class="text-white fs-6 font-monospace mb-0" style="background: transparent; white-space: pre-wrap;"><code>// Basit kullanım
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

                        <div class="alert bg-light-primary border border-primary d-flex align-items-start p-4">
                            <i class="ki-duotone ki-information-5 fs-2 text-primary me-3"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
                            <span class="text-gray-800 fs-6">
                                <code>Language::$ANAHTAR</code> statik özellik söz dizimi <strong>VNL 3.0 ile kaldırılmıştır</strong>. Eski projelerde <code>Language::$X</code> → <code>lang('X')</code> ve <code>sprintf(Language::$X, $a)</code> → <code>lang('X', $a)</code> dönüşümü yapılmalıdır.
                            </span>
                        </div>
                        </div>
                    </div>
                    <!--end::Section 4-->

                    <div class="separator separator-dashed my-8"></div>

                    <!--begin::Section 5-->
                    <div class="mb-10">
                        <h3 class="fw-bolder text-dark mb-4">5. Mevcut Dil Anahtarları</h3>

                        <div class="table-responsive border rounded bg-light mb-5">
                            <table class="table align-middle table-row-dashed fs-6 gy-4 mb-0">
                                <thead>
                                <tr class="text-start text-muted fw-bolder fs-7 text-uppercase gs-0 border-bottom">
                                    <th class="ps-5 min-w-280px">Anahtar</th>
                                    <th class="min-w-350px">Amaç / Açıklama</th>
                                </tr>
                                </thead>
                                <tbody class="text-gray-800 fw-semibold">
                                <tr>
                                    <td class="ps-5"><code>$ERR_GENERAL</code></td>
                                    <td>Genel hata mesajı. Spesifik bir mesaj yoksa kullanılır.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>$PLEASE_FILL_THE_REQUIRED_FIELD</code></td>
                                    <td>Boş alan uyarısı. <code>%s</code> ile alan adını alır.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>$SUCCESS</code></td>
                                    <td>Genel başarı mesajı.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>$DELETE_SUCCESS</code></td>
                                    <td>Silme işlemi başarı mesajı.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>$UPDATE_SUCCESS</code></td>
                                    <td>Güncelleme başarı mesajı.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>$SAVE_SUCCESS</code></td>
                                    <td>Kaydetme başarı mesajı.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>$DATATABLES_PROCESSING</code></td>
                                    <td>DataTables yüklenirken gösterilen metin.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>$DATATABLES_EMPTY</code></td>
                                    <td>DataTables sonuç bulunamadı metni.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>$INVALID_DATE</code></td>
                                    <td>Geçersiz tarih formatı hata mesajı.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>$INVALID_EMAIL</code></td>
                                    <td>Geçersiz e-posta adresi hata mesajı.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>$INVALID_PHONE</code></td>
                                    <td>Geçersiz telefon numarası hata mesajı.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>$INVALID_PASSWORD</code></td>
                                    <td>Şifre kurallarına uymuyor hata mesajı.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>$AGE_RESTRICTION</code></td>
                                    <td>Yaş kısıtlaması hata mesajı (16 yaş altı).</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>$FUTURE_DATE_NOT_ALLOWED</code></td>
                                    <td>Gelecek tarih girilmesine izin verilmez mesajı.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>$TEXT_TOO_SHORT</code></td>
                                    <td>Metin çok kısa hata mesajı.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>$TEXT_TOO_LONG</code></td>
                                    <td>Metin çok uzun hata mesajı.</td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <!--end::Section 5-->

                    <div class="separator separator-dashed my-8"></div>

                    <!--begin::Section 6-->
                    <div class="mb-10">
                        <h3 class="fw-bolder text-dark mb-4">6. Dil Değiştirme</h3>
                        <p class="fs-5 text-gray-700 mb-4">
                            Kullanıcı dil tercihini değiştirmek için <code>Language::set(Languages::EN)</code> çağrısı yapılır. Bu çağrı session'ı günceller ve belirtilen dilin JSON dosyasını yükler.
                        </p>

                        <div class="bg-dark rounded p-5 mb-5">
                            <pre class="text-white fs-6 font-monospace mb-0" style="background: transparent; white-space: pre-wrap;"><code>// İngilizceye geç
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
                        <div class="border border-2 border-primary rounded p-8 bg-light-primary mb-6">
                            <div class="d-flex align-items-center mb-5">
                                <i class="ki-duotone ki-color-swatch fs-3x text-primary me-4"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
                                <div>
                                    <h3 class="fw-bolder text-primary mb-1">LSL Language Studio</h3>
                                    <span class="text-gray-600 fs-6">Dil dosyalarını görsel olarak yönetmek için önerilen araç</span>
                                </div>
                            </div>

                            <p class="fs-5 text-gray-700 mb-5">
                                Dil dosyalarını (<code>tr.json</code>, <code>en.json</code>) elle düzenlemek yerine <strong>LSL Language Studio</strong> uygulamasını kullanmanızı öneririz. Bu uygulama, VNL projelerine özel geliştirilmiş bir masaüstü dil editörüdür.
                            </p>

                            <div class="row g-5 mb-6">
                                <div class="col-md-4">
                                    <div class="bg-white rounded p-5 h-100 border border-dashed">
                                        <i class="ki-duotone ki-eye fs-2x text-success mb-3"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
                                        <h6 class="fw-bold mb-2">Görsel Editör</h6>
                                        <span class="text-muted fs-7">JSON dosyalarını tablo formatında görüntüleyin ve düzenleyin. JSON söz dizimi bilgisi gerekmez.</span>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="bg-white rounded p-5 h-100 border border-dashed">
                                        <i class="ki-duotone ki-arrows-loop fs-2x text-primary mb-3"><span class="path1"></span><span class="path2"></span></i>
                                        <h6 class="fw-bold mb-2">Yan Yana Yönetim</h6>
                                        <span class="text-muted fs-7">Birden fazla dili aynı anda yan yana görüntüleyin; eksik çevirileri kolayca tespit edin.</span>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="bg-white rounded p-5 h-100 border border-dashed">
                                        <i class="ki-duotone ki-file-down fs-2x text-warning mb-3"><span class="path1"></span><span class="path2"></span></i>
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
                        <h3 class="fw-bolder text-dark mb-4">8. Yeni Dil Anahtarı Ekleme</h3>
                        <p class="fs-5 text-gray-700 mb-4">
                            VNL 3.0 ile anahtar eklemek iki adıma indi — artık <code>Language</code> sınıfına dokunulmuyor:
                        </p>

                        <div class="border rounded p-5 bg-light mb-5">
                            <div class="d-flex align-items-start mb-5">
                                <span class="badge badge-circle badge-primary me-4 mt-1 flex-shrink-0">1</span>
                                <div>
                                    <span class="fw-bold text-gray-800">Tüm JSON dosyalarına anahtarı ekleyin</span>
                                    <div class="bg-dark rounded p-3 mt-2">
                                        <pre class="text-white fs-7 font-monospace mb-0" style="background: transparent; white-space: pre-wrap;"><code>// tr.json
"STOCK_INSUFFICIENT": "Yeterli stok bulunmamaktadır."

// en.json
"STOCK_INSUFFICIENT": "Insufficient stock available."</code></pre>
                                    </div>
                                </div>
                            </div>
                            <div class="d-flex align-items-start mb-0">
                                <span class="badge badge-circle badge-primary me-4 mt-1 flex-shrink-0">2</span>
                                <div>
                                    <span class="fw-bold text-gray-800">Herhangi bir yerde kullanın</span>
                                    <div class="bg-dark rounded p-3 mt-2">
                                        <pre class="text-white fs-7 font-monospace mb-0" style="background: transparent; white-space: pre-wrap;"><code>throw new StandartException(lang('STOCK_INSUFFICIENT'));</code></pre>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="alert bg-light-success border border-success d-flex align-items-center p-4">
                            <i class="ki-duotone ki-check-circle fs-2 text-success me-3"><span class="path1"></span><span class="path2"></span></i>
                            <span class="text-gray-800 fs-6">LSL Language Studio kullanıyorsanız tüm süreci uygulama üzerinden yürütebilirsiniz; kod tarafında yapılacak bir işlem kalmadı.</span>
                        </div>
                    </div>
                    <!--end::Section 8-->

                    <div class="separator separator-dashed my-8"></div>

                    <!--begin::Section 9-->
                    <div class="mb-0">
                        <h3 class="fw-bolder text-dark mb-4">9. Eksik Anahtarlar ve Hata Dayanıklılığı</h3>
                        <p class="fs-5 text-gray-700 mb-4">
                            Dil sistemi hiçbir koşulda isteği düşürmez. Eksik anahtar, eksik çeviri, kayıp veya bozuk dosya — hepsi kademeli olarak karşılanır ve durum PHP hata günlüğüne <code>[VNL][Language]</code> etiketiyle yazılır.
                        </p>

                        <div class="table-responsive border rounded bg-light mb-5">
                            <table class="table align-middle table-row-dashed fs-6 gy-4 mb-0">
                                <thead>
                                <tr class="text-start text-muted fw-bolder fs-7 text-uppercase gs-0 border-bottom">
                                    <th class="ps-5 min-w-300px">Durum</th>
                                    <th class="min-w-350px">Davranış</th>
                                </tr>
                                </thead>
                                <tbody class="text-gray-800 fw-semibold">
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

                        <div class="bg-dark rounded p-5 mb-5">
                            <pre class="text-white fs-6 font-monospace mb-0" style="background: transparent; white-space: pre-wrap;"><code>// Anahtar var mı?
if (lang_has('STOCK_INSUFFICIENT')) { /* ... */ }

// Kendi varsayılanınızla
$mesaj = lang_has('STOCK_INSUFFICIENT') ? lang('STOCK_INSUFFICIENT') : 'Stok yetersiz.';

// Tüm sözlük — tanılama ve ön yüze aktarım için
$sozluk = Language::all();

// Yükleme ve erişim sırasında tespit edilen sorunlar
print_r(Language::issues());</code></pre>
                        </div>

                        <div class="alert bg-light-success border border-success d-flex align-items-start p-4">
                            <i class="ki-duotone ki-shield-tick fs-2 text-success me-3"><span class="path1"></span><span class="path2"></span></i>
                            <span class="text-gray-800 fs-6">
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

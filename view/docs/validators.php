<div class="d-flex flex-column flex-column-fluid">
    <div id="kt_app_content" class="app-content flex-column-fluid">
        <div id="kt_app_content_container" class="app-container container-xxl">

            <div class="vnl-page-hero mb-12 mt-6">
                <div class="vnl-overline mb-3">// docs/validators</div>
                <h1 class="fs-2hx fw-bold mb-4 text-body-emphasis">Validator Sistemi</h1>
                <div class="vnl-lead">VNL'nin validator sistemi, gelen verilerin doğruluğunu action sınıflarında kontrol etmek için kullanılır. Doğrulama başarısız olduğunda <code>StandartException</code> fırlatılır ve otomatik olarak JSON hata yanıtı döndürülür.</div>
            </div>

            <div class="alert alert-primary d-flex align-items-center p-5 mb-10">
                <i class="fa-solid fa-shield-halved fs-2hx text-primary me-4"></i>
                <div class="d-flex flex-column">
                    <h5 class="mb-1 text-primary">Otomatik Yükleme</h5>
                    <span class="text-body">Çekirdeğin <code>vnl/validators/</code> dizini ile projenin <code>validators/</code> dizini uygulama başlarken birleştirilerek yüklenir. Yeni bir validator için proje tarafına dosya eklemeniz yeterlidir; <code>DefaultValidator</code>'ı ezmek isterseniz aynı adı verin.</span>
                </div>
            </div>

            <div class="card mb-10">
                <div class="card-body p-10">

                    <!--begin::Section 1-->
                    <div class="mb-10">
                        <h3 class="fw-bold text-body-emphasis mb-4">1. Validator Nedir?</h3>
                        <p class="fs-5 text-body mb-4">
                            Validatorlar, action sınıflarında veritabanı işlemlerinden önce çalıştırılan doğrulama sınıflarıdır. Bir doğrulama başarısız olduğunda <code>StandartException</code> fırlatılır. Bu exception kendi constructor'ında <code>returnErrorMessage()</code> fonksiyonunu çağırır; dolayısıyla action metodu anında sonlanır ve istemciye JSON hata mesajı iletilir.
                        </p>

                        <div class="row g-5">
                            <div class="col-md-6">
                                <div class="border border-dashed rounded p-5 h-100">
                                    <div class="d-flex align-items-center mb-3">
                                        <i class="fa-solid fa-circle-check fs-2 text-success me-3"></i>
                                        <h6 class="mb-0 fw-bold">Doğrulama Başarılı</h6>
                                    </div>
                                    <span class="text-muted fs-7">Metot çalışmaya devam eder, DB işlemi yapılır, başarı yanıtı döndürülür.</span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="border border-dashed rounded p-5 h-100">
                                    <div class="d-flex align-items-center mb-3">
                                        <i class="fa-solid fa-circle-xmark fs-2 text-danger me-3"></i>
                                        <h6 class="mb-0 fw-bold">Doğrulama Başarısız</h6>
                                    </div>
                                    <span class="text-muted fs-7"><code>StandartException</code> fırlatılır → <code>returnErrorMessage()</code> çağrılır → JSON hata yanıtı → Action durur.</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!--end::Section 1-->

                    <div class="separator separator-dashed my-8"></div>

                    <!--begin::Section 2-->
                    <div class="mb-10">
                        <h3 class="fw-bold text-body-emphasis mb-4">2. DefaultValidator — Tüm Metotlar</h3>
                        <p class="fs-5 text-body mb-4">
                            <code>DefaultValidator</code> sınıfı, yaygın kullanım senaryoları için hazır doğrulama metotlarını içerir. Tüm özel validator sınıfları bu sınıfı genişletir.
                        </p>

                        <div class="table-responsive border rounded bg-body-tertiary mb-5">
                            <table class="table align-middle table-row-dashed fs-6 gy-4 mb-0">
                                <thead>
                                <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0 border-bottom">
                                    <th class="ps-5 min-w-320px">Metot İmzası</th>
                                    <th class="min-w-350px">Açıklama</th>
                                </tr>
                                </thead>
                                <tbody class="text-body fw-semibold">
                                <tr>
                                    <td class="ps-5"><code>validateEmptyString($param, $region='')</code></td>
                                    <td>Değer boş string veya <code>null</code> ise hata fırlatır.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>validateEmptyData($param, $region='')</code></td>
                                    <td>Genel veri boşluk kontrolü. <code>validateEmptyString</code> ile aynı davranışı gösterir.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>validateEmptyOption($param, $region='')</code></td>
                                    <td>Select/dropdown alanları için. Değer <code>0</code>, <code>null</code> veya boş ise hata verir.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>validateEmptyID($param, $region='')</code></td>
                                    <td>Pozitif tam sayı olmalıdır. ID alanları için zorunlu kontrol.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>validateEmptyIDArray($param, $region='')</code></td>
                                    <td>Dizi içindeki her elemanın geçerli pozitif ID olduğunu doğrular.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>validateInt($param, $region='')</code></td>
                                    <td>Değer set edilmişse tam sayı olmalıdır. Boş değere izin verir.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>validateEmptyInt($param, $region='')</code></td>
                                    <td>Değer set edilmişse <code>0</code>'dan büyük tam sayı olmalıdır.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>validatePhone($param, $region='')</code></td>
                                    <td>Opsiyonel telefon numarası doğrulaması. Değer girilmişse formata uygun olmalıdır.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>validateEmptyPhone($param, $region='')</code></td>
                                    <td>Zorunlu telefon numarası. Boş bırakılamaz ve formata uygun olmalıdır.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>validateEmptyMail($param)</code></td>
                                    <td>Zorunlu geçerli e-posta adresi. Boş bırakılamaz ve <code>filter_var</code> ile doğrulanır.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>validateDate($date, $region='', $format='Y-m-d')</code></td>
                                    <td>Opsiyonel tarih. Girilmişse belirtilen formata uygun olmalıdır.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>validateEmptyDate($date, $region='', $format='Y-m-d')</code></td>
                                    <td>Zorunlu tarih. Boş bırakılamaz ve formata uygun olmalıdır.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>validateDateNow($date, $region='', $format='Y-m-d')</code></td>
                                    <td>Tarih, bugünden ileri olamaz. Gelecek tarihleri reddeder.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>validateAge($dateOfBirth, $region='', $format='Y-m-d')</code></td>
                                    <td>Doğum tarihine göre yaş kontrolü. 16 yaşından küçükse hata fırlatır.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>validateTextLength($param, $maxlength=255, $minlength=3, $region='')</code></td>
                                    <td>Metin uzunluğunun belirtilen min-max aralığında olduğunu doğrular.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>validateValidPassword($password, $region='')</code></td>
                                    <td>Minimum 8 karakter, en az bir büyük harf, bir küçük harf, bir rakam ve bir özel karakter zorunludur.</td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <!--end::Section 2-->

                    <div class="separator separator-dashed my-8"></div>

                    <!--begin::Section 3-->
                    <div class="mb-10">
                        <h3 class="fw-bold text-body-emphasis mb-4">3. <code>$region</code> Parametresi</h3>
                        <p class="fs-5 text-body mb-4">
                            Çoğu metotta bulunan <code>$region</code> parametresi, hata mesajına alan adını eklemek için kullanılır. Dil sistemi ile entegre çalışır; hata mesajı <code>sprintf</code> ile formatlanır.
                        </p>

                        <div class="vnl-code mb-4">
                            <pre class=" mb-0" style="background: transparent; white-space: pre-wrap;"><code>// Tüm validatörler statik olarak çağrılır

// $region belirtilmeden — genel mesaj
DefaultValidator::validateEmptyString($ad);
// Hata: "Bu alanı doldurunuz"

// $region ile — alana özel mesaj
DefaultValidator::validateEmptyString($ad, 'Ad Soyad');
// Hata: "Ad Soyad alanını doldurunuz"

DefaultValidator::validateEmptyID($kullanici_id, 'Kullanıcı');
// Hata: "Kullanıcı seçimi zorunludur"

DefaultValidator::validateEmptyDate($tarih, 'Başlangıç Tarihi');
// Hata: "Başlangıç Tarihi alanını doldurunuz"
</code></pre>
                        </div>

                        <div class="alert alert-info d-flex align-items-center p-4">
                            <i class="fa-solid fa-circle-info fs-2 text-info me-3"></i>
                            <span class="text-body fs-6">Hata mesajları <code>lang('PLEASE_FILL_IN_FIELD', $region)</code> gibi dil anahtarları üzerinden gelir ve aktif dile göre dinamik olarak üretilir.</span>
                        </div>
                    </div>
                    <!--end::Section 3-->

                    <div class="separator separator-dashed my-8"></div>

                    <!--begin::Section 4-->
                    <div class="mb-10">
                        <h3 class="fw-bold text-body-emphasis mb-4">4. Özel Validator Oluşturma</h3>
                        <p class="fs-5 text-body mb-4">
                            Projenize özgü doğrulama kuralları için <code>validators/</code> klasörüne yeni bir dosya ekleyin. Sınıfınız <code>DefaultValidator</code>'ı genişletmeli ve özel metotlarınızı eklemelidir.
                        </p>

                        <div class="d-flex align-items-center bg-body-tertiary border border-dashed rounded p-4 mb-4">
                            <i class="fa-solid fa-file fs-1 text-primary me-3"></i>
                            <span class="fs-5 fw-bold text-body font-monospace">validators/UserValidator.php</span>
                        </div>

                        <div class="vnl-code mb-4">
                            <pre class=" mb-0" style="background: transparent; white-space: pre-wrap;"><code>&lt;?php

class UserValidator extends DefaultValidator
{
    /**
     * Kullanıcı adının benzersizliğini kontrol eder.
     * Veri erişimi model sınıfı üzerinden yapılır.
     */
    public static function validateUniqueUsername(string $username, int $excludeId = 0): void
    {
        $filters = ['Username' => $username];
        $count = User::SelectList($filters, null)->count();

        if ($count > 0 && $excludeId === 0) {
            throw new StandartException('Bu kullanıcı adı zaten kullanılıyor.');
        }
    }

    /**
     * TC kimlik numarası formatını doğrular.
     */
    public function validateTCKN(string $tckn, string $region = ''): void
    {
        if (!preg_match('/^[1-9][0-9]{10}$/', $tckn)) {
            $msg = $region
                ? lang('PLEASE_FILL_IN_FIELD', $region)
                : lang('ERR_GENERAL');
            throw new StandartException($msg);
        }
    }
}
</code></pre>
                        </div>

                        <div class="alert alert-success d-flex align-items-center p-4">
                            <i class="fa-solid fa-circle-check fs-2 text-success me-3"></i>
                            <span class="text-body fs-6">Dosyayı <code>validators/</code> klasörüne kaydetmeniz yeterlidir. Uygulama başlangıcında otomatik yükleneceği için ek kayıt veya <code>require</code> gerekmez.</span>
                        </div>
                    </div>
                    <!--end::Section 4-->

                    <div class="separator separator-dashed my-8"></div>

                    <!--begin::Section 5-->
                    <div class="mb-10">
                        <h3 class="fw-bold text-body-emphasis mb-4">5. Action İçinde Kullanım</h3>
                        <p class="fs-5 text-body mb-4">
                            Validator metotları action sınıflarında doğrudan çağrılır. <code>StandartException</code> otomatik olarak işlendiğinden <code>try-catch</code> bloğuna gerek yoktur.
                        </p>

                        <div class="vnl-code mb-4">
                            <pre class=" mb-0" style="background: transparent; white-space: pre-wrap;"><code>&lt;?php
// Dosya: actions/action_kullanici.php
// Validatörler ve modüller statik olarak çağrılır

class action_kullanici
{
    public function kaydet($req): void
    {
        Auth::CheckLogin();

        $ad       = $req->post('ad');
        $soyad    = $req->post('soyad');
        $email    = $req->post('email');
        $sifre    = $req->post('sifre');
        $dogum    = $req->post('dogum_tarihi');
        $username = $req->post('username');
        $user_id  = $req->post('user_id');

        // Doğrulamalar — statik çağrı, hata olursa otomatik JSON yanıtı döner
        DefaultValidator::validateEmptyString($ad,    'Ad');
        DefaultValidator::validateEmptyString($soyad, 'Soyad');
        DefaultValidator::validateEmptyMail($email);
        DefaultValidator::validateValidPassword($sifre, 'Şifre');
        DefaultValidator::validateEmptyDate($dogum,   'Doğum Tarihi');
        DefaultValidator::validateAge($dogum,         'Doğum Tarihi');
        UserValidator::validateUniqueUsername($username);

        // Tüm doğrulamalar geçtiyse DB işlemi — model üzerinden
        $kullanici = empty($user_id) ? new User() : User::Select(['Id' => $user_id]);

        $kullanici->Name     = $ad;
        $kullanici->Surname  = $soyad;
        $kullanici->Email    = $email;
        $kullanici->Username = $username;
        if (!empty($sifre)) {
            $kullanici->setStoredPassword(EncryptionModule::PasswordHash($sifre));
        }
        if (empty($user_id)) {
            $kullanici->InsertDate = date('Y-m-d H:i:s');
            $kullanici->InsertUser = Auth::User()->Id;
        } else {
            $kullanici->UpdateDate = date('Y-m-d H:i:s');
            $kullanici->UpdateUser = Auth::User()->Id;
        }
        $kullanici->save();

        returnSuccessMessage('Kullanıcı başarıyla kaydedildi.');
    }
}
</code></pre>
                        </div>
                    </div>
                    <!--end::Section 5-->

                    <div class="separator separator-dashed my-8"></div>

                    <!--begin::Section 6-->
                    <div class="mb-10">
                        <h3 class="fw-bold text-body-emphasis mb-4">6. StandartException ve EmptyDataException</h3>
                        <p class="fs-5 text-body mb-4">
                            VNL'de iki temel hata sınıfı bulunur. İkisi de istemciye JSON formatında hata yanıtı döndürmek için tasarlanmıştır.
                        </p>

                        <div class="row g-5 mb-5">
                            <div class="col-md-6">
                                <div class="border border-dashed border-danger rounded p-5 h-100">
                                    <span class="badge badge-soft-danger mb-3 fs-7">StandartException</span>
                                    <p class="text-body fs-6 mb-3">Constructor'da doğrudan <code>returnErrorMessage()</code> çağırır. Validator metotlarından ve action sınıflarından fırlatılır.</p>
                                    <div class="vnl-code">
                                        <pre class="mb-0" style="background: transparent; white-space: pre-wrap;"><code>throw new StandartException(
    'Hata mesajı buraya'
);</code></pre>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="border border-dashed border-warning rounded p-5 h-100">
                                    <span class="badge badge-soft-warning mb-3 fs-7">EmptyDataException</span>
                                    <p class="text-body fs-6 mb-3">Boş veri durumları için özelleştirilmiş hata sınıfı. <code>ErrorMessage</code> sınıfı üzerinden <code>returnErrorMessage()</code>'ı çağırır.</p>
                                    <div class="vnl-code">
                                        <pre class="mb-0" style="background: transparent; white-space: pre-wrap;"><code>throw new EmptyDataException(
    'Ad', // alan adı
);</code></pre>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="alert alert-info d-flex align-items-center p-4">
                            <i class="fa-solid fa-circle-info fs-2 text-info me-3"></i>
                            <span class="text-body fs-6">Her iki exception da <code>returnErrorMessage()</code> üzerinden <code>{"message": "...", "devcode": "..."}</code> gövdesini basar, HTTP durum kodunu ayarlar (varsayılan 400) ve PHP çalışmasını durdurur. <code>success</code> diye bir alan yoktur. AJAX çağrılarında istemci tarafı bunu <code>error</code> callback'inde yakalar.</span>
                        </div>
                    </div>
                    <!--end::Section 6-->

                    <div class="separator separator-dashed my-8"></div>

                    <!--begin::Section 7-->
                    <div class="mb-0">
                        <h3 class="fw-bold text-body-emphasis mb-4">7. Kendi Validator Dosyanızı Oluşturma</h3>
                        <p class="fs-5 text-body mb-4">
                            Adım adım özel validator oluşturma:
                        </p>

                        <div class="border rounded p-5 bg-body-tertiary mb-5">
                            <div class="d-flex align-items-start mb-5">
                                <span class="badge badge-circle badge-primary me-4 mt-1 flex-shrink-0">1</span>
                                <div>
                                    <span class="fw-bold text-body">Dosyayı oluşturun</span>
                                    <div class="text-muted fs-6 mt-1"><code>validators/SiparisValidator.php</code></div>
                                </div>
                            </div>
                            <div class="d-flex align-items-start mb-5">
                                <span class="badge badge-circle badge-primary me-4 mt-1 flex-shrink-0">2</span>
                                <div>
                                    <span class="fw-bold text-body"><code>DefaultValidator</code>'ı genişletin</span>
                                    <div class="text-muted fs-6 mt-1"><code>class SiparisValidator extends DefaultValidator { ... }</code></div>
                                </div>
                            </div>
                            <div class="d-flex align-items-start mb-5">
                                <span class="badge badge-circle badge-primary me-4 mt-1 flex-shrink-0">3</span>
                                <div>
                                    <span class="fw-bold text-body">Özel metotlarınızı ekleyin</span>
                                    <div class="text-muted fs-6 mt-1">Her metot başarısız olduğunda <code>throw new StandartException('mesaj')</code> fırlatmalıdır.</div>
                                </div>
                            </div>
                            <div class="d-flex align-items-start mb-0">
                                <span class="badge badge-circle badge-primary me-4 mt-1 flex-shrink-0">4</span>
                                <div>
                                    <span class="fw-bold text-body">Action'da kullanın</span>
                                    <div class="text-muted fs-6 mt-1"><code>$this->siparisValidator = new SiparisValidator();</code> şeklinde action constructor'ında başlatın.</div>
                                </div>
                            </div>
                        </div>

                        <div class="alert alert-warning d-flex align-items-center p-4">
                            <i class="fa-solid fa-circle-info fs-2 text-warning me-3"></i>
                            <span class="text-body fs-6">Dosya adı sınıf adıyla eşleşmelidir: <code>SiparisValidator.php</code> dosyasında <code>class SiparisValidator</code> bulunmalıdır. Bu kural <code>spl_autoload</code> yükleyicisinin gerektirdiği standarttır.</span>
                        </div>
                    </div>
                    <!--end::Section 7-->

                </div>
            </div>

        </div>
    </div>
</div>

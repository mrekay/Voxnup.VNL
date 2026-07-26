<div class="d-flex flex-column flex-column-fluid">
    <div id="kt_app_content" class="app-content flex-column-fluid">
        <div id="kt_app_content_container" class="app-container container-xxl">

            <div class="text-center mb-15 mt-10">
                <h1 class="fs-2hx fw-bold mb-5 text-body-emphasis">Modal Sistemi</h1>
                <div class="fs-5 text-muted fw-semibold w-lg-75 mx-auto">
                    VNL, Bootstrap 5 modal'larını kendi dosya sistemiyle yönetir. Her modal bağımsız bir PHP dosyasıdır; HTML yapısını ve AJAX işlemini bir arada barındırır.
                </div>
            </div>

            <div class="alert bg-primary-subtle border border-primary d-flex align-items-center p-5 mb-10">
                <i class="fa-solid fa-circle-info fs-2hx text-primary me-4"></i>
                <div class="d-flex flex-column">
                    <h4 class="mb-1 text-primary">Neden Ayrı Dosya?</h4>
                    <span class="text-body">Modal HTML'ini view dosyasının içine gömmek yerine ayrı PHP dosyalarına ayırmak, aynı modal'ı birden fazla sayfada yeniden kullanmanızı, PHP değişkeni geçirmenizi ve bakımını kolaylaştırmanızı sağlar.</span>
                </div>
            </div>

            <div class="card shadow-sm border-0 mb-10">
                <div class="card-body p-10">

                    <!--begin::Section 1-->
                    <div class="mb-10">
                        <h3 class="fw-bold text-body-emphasis mb-4">1. Dosya Konumu</h3>
                        <p class="fs-5 text-body mb-4">
                            Tüm modal dosyaları <code>view/includes/modals/</code> altında yaşar. Bu konum <code>view()</code> fonksiyonundan farklı olarak tema alt klasörüne gitmez; yolu sabit ve globaldir.
                        </p>

                        <div class="border rounded p-5 bg-body-tertiary mb-5">
                            <ul class="list-unstyled fs-6 text-body fw-semibold mb-0">
                                <li class="mb-3 d-flex align-items-center"><i class="bi bi-folder2-open text-warning fs-3 me-3"></i> <b>view/includes/modals/</b></li>
                                <li class="mb-2 ms-8 d-flex align-items-center"><i class="bi bi-filetype-php text-primary fs-5 me-2"></i> KullaniciEkle.php</li>
                                <li class="mb-2 ms-8 d-flex align-items-center"><i class="bi bi-filetype-php text-primary fs-5 me-2"></i> KullaniciSil.php</li>
                                <li class="mb-2 ms-8 d-flex align-items-center"><i class="bi bi-folder2-open text-warning fs-5 me-2"></i> <b>proje/</b></li>
                                <li class="mb-0 ms-14 d-flex align-items-center"><i class="bi bi-filetype-php text-primary fs-5 me-2"></i> ProjeOlustur.php</li>
                            </ul>
                        </div>

                        <div class="table-responsive border rounded bg-body-tertiary mb-0">
                            <table class="table align-middle table-row-dashed fs-6 gy-4 mb-0">
                                <thead>
                                <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0 border-bottom">
                                    <th class="ps-5 min-w-280px">PHP Kodu</th>
                                    <th class="min-w-350px">Yüklenen Dosya</th>
                                </tr>
                                </thead>
                                <tbody class="text-body fw-semibold">
                                <tr>
                                    <td class="ps-5"><code>require modal('KullaniciEkle');</code></td>
                                    <td><code>view/includes/modals/KullaniciEkle.php</code></td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>require modal('ProjeOlustur', 'proje');</code></td>
                                    <td><code>view/includes/modals/proje/ProjeOlustur.php</code></td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <!--end::Section 1-->

                    <div class="separator separator-dashed my-8"></div>

                    <!--begin::Section 2-->
                    <div class="mb-10">
                        <h3 class="fw-bold text-body-emphasis mb-4">2. Controller'a Dahil Etme</h3>
                        <p class="fs-5 text-body mb-4">
                            Modal'lar, sayfanın içerik view'larından <b>sonra</b> ve <code>static/footer</code>'dan <b>önce</b> yüklenir. Bu sıra, JavaScript'in modal DOM'una erişebilmesi için önemlidir.
                        </p>

                        <div class="bg-dark rounded p-5 mb-4">
                            <pre class="text-white fs-6 font-monospace mb-0" style="background: transparent; white-space: pre-wrap;"><code>&lt;?php
// Dosya: controller/kullanici.php

$meta        = ['title' => 'Kullanıcılar', 'upper_title' => 'Yönetim'];
$page_config = [
    'topbar'        => true,
    'leftsidebar'   => true,
    'contentheader' => true,
    'toolbaritems'  => [
        [
            'type'   => 'button',
            'text'   => 'Yeni Kullanıcı',
            'class'  => 'btn btn-primary btn-sm',
            'icon'   => 'fa-solid fa-plus',
            'custom' => 'data-bs-toggle="modal" data-bs-target="#modal_kullanici_ekle"',
        ],
    ],
];

$kullanicilar = User::SelectList([], [])->get();

require view('static/header');
require view('kullanici/liste');

// Modal'lar içerik sonrası, footer öncesi yüklenir
require modal('KullaniciEkle', 'kullanici');
require modal('KullaniciSil', 'kullanici');

require view('static/footer');
</code></pre>
                        </div>
                    </div>
                    <!--end::Section 2-->

                    <div class="separator separator-dashed my-8"></div>

                    <!--begin::Section 3-->
                    <div class="mb-10">
                        <h3 class="fw-bold text-body-emphasis mb-4">3. Modal Dosyası Yapısı</h3>
                        <p class="fs-5 text-body mb-4">
                            Her modal dosyası Bootstrap 5 modal HTML'i ve isteğe bağlı olarak bu modal'a özel JavaScript/AJAX kodunu içerir. PHP değişkenleri (controller'dan gelenler dahil) modal dosyasında kullanılabilir.
                        </p>

                        <div class="bg-dark rounded p-5 mb-4">
                            <pre class="text-white fs-6 font-monospace mb-0" style="background: transparent; white-space: pre-wrap;"><code>&lt;!-- Dosya: view/includes/modals/kullanici/KullaniciEkle.php --&gt;

&lt;!--begin::Modal - Kullanıcı Ekle--&gt;
&lt;div class="modal fade" id="modal_kullanici_ekle" tabindex="-1" aria-hidden="true"&gt;
    &lt;div class="modal-dialog modal-dialog-centered mw-650px"&gt;
        &lt;div class="modal-content"&gt;

            &lt;!--begin::Modal header--&gt;
            &lt;div class="modal-header"&gt;
                &lt;h2 class="fw-bold"&gt;Yeni Kullanıcı Ekle&lt;/h2&gt;
                &lt;div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal"&gt;
                    &lt;i class="fa-solid fa-xmark fs-1"&gt;
                    &lt;/i&gt;
                &lt;/div&gt;
            &lt;/div&gt;
            &lt;!--end::Modal header--&gt;

            &lt;!--begin::Modal body--&gt;
            &lt;div class="modal-body px-lg-10 py-lg-10"&gt;
                &lt;div class="mb-5"&gt;
                    &lt;label class="form-label required"&gt;Ad Soyad&lt;/label&gt;
                    &lt;input type="text" class="form-control form-control-solid" id="inp_kullanici_ad" placeholder="Ad Soyad"&gt;
                &lt;/div&gt;
                &lt;div class="mb-5"&gt;
                    &lt;label class="form-label required"&gt;E-posta&lt;/label&gt;
                    &lt;input type="email" class="form-control form-control-solid" id="inp_kullanici_email" placeholder="ornek@mail.com"&gt;
                &lt;/div&gt;
            &lt;/div&gt;
            &lt;!--end::Modal body--&gt;

            &lt;!--begin::Modal footer--&gt;
            &lt;div class="modal-footer"&gt;
                &lt;button type="button" class="btn btn-light" data-bs-dismiss="modal"&gt;İptal&lt;/button&gt;
                &lt;button type="button" class="btn btn-primary" id="btn_kullanici_kaydet"&gt;
                    &lt;span class="indicator-label"&gt;Kaydet&lt;/span&gt;
                    &lt;span class="indicator-progress"&gt;
                        Kaydediliyor... &lt;span class="spinner-border spinner-border-sm align-middle ms-2"&gt;&lt;/span&gt;
                    &lt;/span&gt;
                &lt;/button&gt;
            &lt;/div&gt;
            &lt;!--end::Modal footer--&gt;

        &lt;/div&gt;
    &lt;/div&gt;
&lt;/div&gt;
&lt;!--end::Modal - Kullanıcı Ekle--&gt;

&lt;script&gt;
$('#btn_kullanici_kaydet').on('click', function () {
    var btn = $(this);
    btn.attr('data-kt-indicator', 'on');

    $.ajax({
        type: 'POST',
        url: '/app/kullanici/kaydet',
        data: {
            ad:    $('#inp_kullanici_ad').val(),
            email: $('#inp_kullanici_email').val(),
        },
        success: function (response) {
            btn.removeAttr('data-kt-indicator');
            $('#modal_kullanici_ekle').modal('hide');
            // Sayfayı yenile veya tabloyu güncelle
            location.reload();
        },
        error: function (xhr) {
            btn.removeAttr('data-kt-indicator');
            // Hata mesajını göster
            alert(xhr.responseJSON?.message ?? 'Bir hata oluştu.');
        }
    });
});
&lt;/script&gt;
</code></pre>
                        </div>
                    </div>
                    <!--end::Section 3-->

                    <div class="separator separator-dashed my-8"></div>

                    <!--begin::Section 4-->
                    <div class="mb-10">
                        <h3 class="fw-bold text-body-emphasis mb-4">4. Modal'a PHP Verisi Geçirme</h3>
                        <p class="fs-5 text-body mb-4">
                            Controller'da tanımlanan değişkenlere modal dosyasından doğrudan erişilebilir. Bu sayede dropdown'ları veritabanından doldurabilir ya da düzenleme modal'larına mevcut veriyi aktarabilirsiniz.
                        </p>

                        <div class="bg-dark rounded p-5 mb-0">
                            <pre class="text-white fs-6 font-monospace mb-0" style="background: transparent; white-space: pre-wrap;"><code>&lt;?php
// controller/kullanici.php

$roller       = Permission::SelectList([], [])->get(); // Modal'da kullanılacak veri
$kullanicilar = User::SelectList([], [])->get();

require view('static/header');
require view('kullanici/liste');
require modal('KullaniciEkle', 'kullanici'); // $roller bu dosyada erişilebilir
require view('static/footer');
</code></pre>
                        </div>

                        <div class="bg-dark rounded p-5 mt-4">
                            <pre class="text-white fs-6 font-monospace mb-0" style="background: transparent; white-space: pre-wrap;"><code>&lt;!-- view/includes/modals/kullanici/KullaniciEkle.php --&gt;
&lt;select class="form-select form-select-solid" id="inp_kullanici_rol"&gt;
    &lt;option value=""&gt;Rol Seçin&lt;/option&gt;
    &lt;?php foreach ($roller as $rol): ?&gt;
        &lt;option value="&lt;?= $rol-&gt;id ?&gt;"&gt;&lt;?= $rol-&gt;name ?&gt;&lt;/option&gt;
    &lt;?php endforeach; ?&gt;
&lt;/select&gt;
</code></pre>
                        </div>
                    </div>
                    <!--end::Section 4-->

                    <div class="separator separator-dashed my-8"></div>

                    <!--begin::Section 5-->
                    <div class="mb-0">
                        <h3 class="fw-bold text-body-emphasis mb-4">5. Global Modals (<code>view/static/modals.php</code>)</h3>
                        <p class="fs-5 text-body mb-4">
                            Tüm sayfalarda ortak kullanılan modal'lar (örneğin resim önizleme kutusu) <code>view/static/modals.php</code> dosyasında tanımlanır ve <code>static/footer</code> tarafından her sayfaya otomatik dahil edilir. Bu dosyaya eklediğiniz her modal uygulamanın tamamında kullanılabilir olur.
                        </p>

                        <div class="alert bg-info-subtle border border-info d-flex align-items-center p-4">
                            <i class="fa-solid fa-circle-info fs-2 text-info me-3"></i>
                            <span class="text-body fs-6">Sadece giriş yapmış kullanıcılar için görünmesi istenen global modal'lar <code>if (!empty($CurrentUser->id))</code> kontrolüyle sarılmalıdır. Mevcut <code>modals.php</code> bu kontrolü zaten uygulamaktadır.</span>
                        </div>
                    </div>
                    <!--end::Section 5-->

                    <div class="separator separator-dashed my-8"></div>

                    <!--begin::Section 6-->
                    <div class="mb-0">
                        <h3 class="fw-bold text-body-emphasis mb-4">6. Modal Dosyalarının Klasörlenmesi — Felsefe</h3>
                        <p class="fs-5 text-body mb-4">
                            VNL felsefesi gereği modal dosyaları sayfalara göre alt klasörlere gruplanmalıdır. Bu yapı, modal dosyalarının hangi sayfaya ait olduğunu net olarak gösterir ve büyüyen projelerde yönetimi kolaylaştırır.
                        </p>

                        <div class="border rounded p-5 bg-body-tertiary mb-5">
                            <ul class="list-unstyled fs-6 text-body fw-semibold mb-0">
                                <li class="mb-3 d-flex align-items-center"><i class="bi bi-folder2-open text-warning fs-3 me-3"></i> <b>view/includes/modals/</b></li>
                                <li class="mb-2 ms-8 d-flex align-items-center"><i class="bi bi-folder2-open text-warning fs-5 me-2"></i> <b>kullanici/</b> <span class="text-muted fs-7 ms-2">— kullanıcı sayfasına ait modaller</span></li>
                                <li class="mb-2 ms-14 d-flex align-items-center"><i class="bi bi-filetype-php text-primary fs-5 me-2"></i> KullaniciEkle.php</li>
                                <li class="mb-2 ms-14 d-flex align-items-center"><i class="bi bi-filetype-php text-primary fs-5 me-2"></i> KullaniciDuzenle.php</li>
                                <li class="mb-2 ms-14 d-flex align-items-center"><i class="bi bi-filetype-php text-primary fs-5 me-2"></i> KullaniciSil.php</li>
                                <li class="mb-2 ms-8 d-flex align-items-center"><i class="bi bi-folder2-open text-warning fs-5 me-2"></i> <b>urun/</b> <span class="text-muted fs-7 ms-2">— ürün sayfasına ait modaller</span></li>
                                <li class="mb-2 ms-14 d-flex align-items-center"><i class="bi bi-filetype-php text-primary fs-5 me-2"></i> UrunEkle.php</li>
                                <li class="mb-0 ms-14 d-flex align-items-center"><i class="bi bi-filetype-php text-primary fs-5 me-2"></i> UrunSil.php</li>
                            </ul>
                        </div>

                        <div class="bg-dark rounded p-5 mb-4">
                            <pre class="text-white fs-6 font-monospace mb-0" style="background: transparent; white-space: pre-wrap;"><code>&lt;?php
// controller/kullanici.php — modal'lar sayfa klasöründen yüklenir

require view('static/header');
require view('kullanici/liste');

// Klasör adı ikinci parametre olarak geçilir
require modal('KullaniciEkle',    'kullanici');
require modal('KullaniciDuzenle', 'kullanici');
require modal('KullaniciSil',     'kullanici');

require view('static/footer');
</code></pre>
                        </div>

                        <div class="alert bg-warning-subtle border border-warning d-flex align-items-center p-4">
                            <i class="fa-solid fa-circle-info fs-2 text-warning me-3"></i>
                            <span class="text-body fs-6">Klasörleme zorunlu değildir ancak VNL felsefesi gereği <b>şiddetle önerilir</b>. Klasörsüz kullanımda tüm modaller tek dizinde birikir ve büyüyen projelerde yönetimi zorlaşır.</span>
                        </div>
                    </div>
                    <!--end::Section 6-->

                </div>
            </div>

        </div>
    </div>
</div>

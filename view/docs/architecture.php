<div class="d-flex flex-column flex-column-fluid">
    <div id="kt_app_content" class="app-content flex-column-fluid">
        <div id="kt_app_content_container" class="app-container container-xxl">

            <div class="vnl-page-hero mb-12 mt-6">
                <div class="vnl-overline mb-3">// docs/architecture</div>
                <h1 class="fs-2hx fw-bold mb-4 text-body-emphasis">Çekirdek ve Proje</h1>
                <div class="vnl-lead">VNL 3.0 ile depodaki her dosyanın <b>tek bir sahibi</b> var ve sahibi yolundan belli. <code>vnl/</code> altındaki her şey çekirdeğindir; kökteki geri kalan her şey sizindir. Güncelleme tek bir dizini değiştirmekten ibarettir — birleştirme yok, çakışma yok.</div>
            </div>

            <div class="alert alert-primary d-flex align-items-center p-5 mb-10">
                <i class="fa-solid fa-code-branch fs-2hx text-primary me-4"></i>
                <div class="d-flex flex-column text-primary">
                    <h4 class="mb-1 text-primary">Tek Kural</h4>
                    <span><code>vnl/</code> VNL'indir, <b>oraya hiçbir şey yazmayın</b>. Kökteki geri kalan her şey sizindir, VNL oraya hiç dokunmaz. Laravel'de bu sınırın adı <code>vendor/</code>'dır; VNL'de composer olmadığı için sınırı dizinin kendisi taşır.</span>
                </div>
            </div>

            <div class="card mb-10">
                <div class="card-body p-10">

                    <!--begin::Section 1-->
                    <div class="mb-10">
                        <h3 class="fw-bold text-body-emphasis mb-4">1. Sınır Nereden Geçiyor?</h3>

                        <div class="row g-5">
                            <div class="col-md-6">
                                <div class="border border-dashed rounded p-5 h-100">
                                    <div class="d-flex align-items-center mb-4">
                                        <i class="bi bi-person-gear text-primary fs-2 me-3"></i>
                                        <span class="fw-bold fs-5 text-body-emphasis">Sizin</span>
                                    </div>
                                    <div class="vnl-code"><pre class="mb-0" style="background:transparent; white-space:pre-wrap;"><code>index.php          ön denetleyici
config/            definitions.php, database.php
classes/           kendi modelleriniz
controller/        sayfalar + 401/403/404
view/              şablonlar, düzen parçaları
actions/           AJAX işlemcileri
validators/        doğrulama kuralları
helpers/           kendi fonksiyonlarınız
languages/         JSON dil dosyaları
migrations/        kendi şema değişiklikleriniz
assets/            kendi CSS/JS/görselleriniz
commands/  jobs/     proje komutları, kuyruk işleyicileri
tests/             projenin testleri
files/  api/  extensions/
.env  .htaccess  web.config  nginx.conf.example
compose.test.yml  docker/</code></pre></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="border border-dashed rounded p-5 h-100">
                                    <div class="d-flex align-items-center mb-4">
                                        <i class="bi bi-box-seam text-primary fs-2 me-3"></i>
                                        <span class="fw-bold fs-5 text-body-emphasis">VNL'in</span>
                                    </div>
                                    <div class="vnl-code"><pre class="mb-0" style="background:transparent; white-space:pre-wrap;"><code>vnl/VERSION        çekirdeğin sürümü
vnl/boot.php       önyükleme
vnl/bootstrap.php  otomatik yükleyici, oturum
vnl/kernel.php     yönlendirme, giriş denetimi
vnl/env.php        .env okuyucu
vnl/error.php      hata sayfası
vnl/template.php   &#123;&#123; }} derleyicisi
vnl/console.php    komut dağıtıcısı (cli.php: ortak CLI yardımcıları)
vnl/KEYS           güncelleme imzasının açık anahtarları
vnl/MANIFEST       çekirdeğin dosya özetleri
vnl/classes/       Request, Session, VnlDispatcher, VnlSql, VnlTable…
vnl/modules/       UserModule, MailModule…
vnl/helpers/       controller(), view(), lang()
vnl/validators/    DefaultValidator
vnl/exceptions/
vnl/assets/        tema çıktısı + vnl.js
vnl/migrations/    VNL'in şeması
vnl/vendor/        Illuminate, PHPMailer
vnl/tests/         çekirdeğin testleri (php vnl.php test)
vnl/tools/         migrate, update, doctor, release…</code></pre></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!--end::Section 1-->

                    <div class="separator separator-dashed my-8"></div>

                    <!--begin::Section 2-->
                    <div class="mb-10">
                        <h3 class="fw-bold text-body-emphasis mb-4">2. Çekirdek Neden Görünüm Taşımıyor?</h3>
                        <p class="fs-5 text-body mb-5">
                            <code>vnl/</code> altında tek bir <code>.php</code> şablonu yoktur — hata sayfaları bile değil. Bunun sebebi şu: her proje düzenini, menüsünü, 404'ünü kendine göre kuruyor. Çekirdekte &laquo;varsayılan&raquo; bir görünüm tutmak, herkesin ezdiği bir şeyin sahibiymiş gibi davranmak olurdu. Aynısı <b>dil dosyaları</b> için de geçerli: metinler projeden projeye değiştiği için <code>languages/</code> sizdedir.
                        </p>

                        <div class="alert alert-warning d-flex align-items-start p-4 mb-5">
                            <i class="fa-solid fa-triangle-exclamation fs-2 text-warning me-3 mt-1"></i>
                            <span class="text-body fs-6">Bunun bir bedeli var, bilerek kabul edildi: çekirdek de <code>lang()</code> çağırır (<code>DefaultValidator</code>, eylem dağıtıcısı). VNL yeni bir dil anahtarı getirdiğinde siz onu <code>languages/</code>'a ekleyene kadar ekranda <b>anahtarın kendisi</b> görünür. İstek düşmez, sayfa açılır.</span>
                        </div>

                        <p class="fs-5 text-body mb-0">
                            Buna karşılık <b>hangi</b> çekirdek varlıklarının yükleneceği bilgisi çekirdekte kaldı. <code>view/includes/static/head-standard.php</code> sizindir ama listeyi <code>vnl_core_styles()</code>'tan alır; VNL yeni bir CSS eklediğinde her projenin <code>&lt;head&gt;</code>'ini elle düzeltmek gerekmez.
                        </p>
                    </div>
                    <!--end::Section 2-->

                    <div class="separator separator-dashed my-8"></div>

                    <!--begin::Section 3-->
                    <div class="mb-10">
                        <h3 class="fw-bold text-body-emphasis mb-4">3. Çekirdeği Ezmek</h3>
                        <p class="fs-5 text-body mb-5">
                            Bir çekirdek sınıfını ya da modülünü değiştirmek isterseniz <code>vnl/</code> altına <b>dokunmayın</b> — güncelleme onu silecektir. Bunun yerine kendi tarafınıza <b>aynı adlı</b> dosyayı koyun; çözümleme önce size bakar.
                        </p>

                        <div class="vnl-code mb-5"><pre class="mb-0" style="background:transparent; white-space:pre-wrap;"><code># VNL'in MailModule'ü yerine kendinizinki geçsin:
cp vnl/modules/MailModule.php modules/MailModule.php
# artık modules/MailModule.php yükleniyor, vnl/modules/MailModule.php hiç okunmuyor</code></pre></div>

                        <div class="table-responsive mb-5">
                            <table class="table table-row-dashed align-middle fs-6">
                                <thead>
                                <tr class="fw-bold text-muted">
                                    <th>Ne</th>
                                    <th>Nasıl ezilir</th>
                                    <th>Davranış</th>
                                </tr>
                                </thead>
                                <tbody>
                                <tr>
                                    <td><b>Sınıf</b></td>
                                    <td><code>classes/Request.php</code></td>
                                    <td>Aynı adlı dosya varsa çekirdeğinki <b>hiç yüklenmez</b></td>
                                </tr>
                                <tr>
                                    <td><b>Modül</b></td>
                                    <td><code>modules/MailModule.php</code></td>
                                    <td>Dosya adına göre birleşir, sizinki kazanır</td>
                                </tr>
                                <tr>
                                    <td><b>Yardımcı</b></td>
                                    <td><code>helpers/app.php</code></td>
                                    <td>Aynı — ama tüm dosyayı devralırsınız</td>
                                </tr>
                                <tr>
                                    <td><b>Doğrulayıcı</b></td>
                                    <td><code>validators/DefaultValidator.php</code></td>
                                    <td>Aynı</td>
                                </tr>
                                <tr>
                                    <td><b>Yönlendirme</b></td>
                                    <td><code>index.php</code> içinde <code>kernel.php</code> yerine kendi dosyanız</td>
                                    <td>Ön denetleyici sizin, çağrıyı değiştirebilirsiniz</td>
                                </tr>
                                <tr>
                                    <td><b>Görünüm</b></td>
                                    <td>&mdash;</td>
                                    <td>Zaten sizin; ezilecek bir çekirdek görünümü yok</td>
                                </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="alert alert-warning d-flex align-items-start p-4 mb-0">
                            <i class="fa-solid fa-triangle-exclamation fs-2 text-warning me-3 mt-1"></i>
                            <span class="text-body fs-6">Ezme <b>dosya bazındadır, satır bazında değil</b>. Bir modülün tek metodunu değiştirmek için dosyanın tamamını devralırsınız; o dosyaya gelen VNL düzeltmeleri artık size ulaşmaz. Bu yüzden ezmeyi son çare olarak kullanın.</span>
                        </div>
                    </div>
                    <!--end::Section 3-->

                    <div class="separator separator-dashed my-8"></div>

                    <!--begin::Section 4-->
                    <div class="mb-10">
                        <h3 class="fw-bold text-body-emphasis mb-4">4. İki Sürüm Numarası</h3>
                        <p class="fs-5 text-body mb-5">
                            Çekirdeğin sürümü <code>vnl/VERSION</code>'da, projenin sürümü <code>config/definitions.php</code>'dedir. VNL 3.0'a kadar ikisi de aynı dosyada duruyordu ve çekirdeğinkinin yanında &laquo;burayı değiştirmeyin&raquo; yazıyordu — proje dosyasında duran bir şeye dokunulmaması için rica etmek gerekiyordu. Artık dosya proje tarafında olmadığı için rica da gerekmiyor.
                        </p>
                        <div class="vnl-code"><pre class="mb-0" style="background:transparent; white-space:pre-wrap;"><code>$cDefinitions-&gt;version()       // projenizin sürümü
$cDefinitions-&gt;vnl_version()   // vnl/VERSION'dan okunur</code></pre></div>
                    </div>
                    <!--end::Section 4-->

                    <div class="separator separator-dashed my-8"></div>

                    <!--begin::Section 5-->
                    <div class="mb-0">
                        <h3 class="fw-bold text-body-emphasis mb-4">5. Güncelleme</h3>
                        <p class="fs-5 text-body mb-5">
                            Sınırın tek amacı bu: yeni bir VNL sürümü çıktığında yalnız <code>vnl/</code> dizini değişir; <code>php vnl.php update</code> bunu sizin yerinize yapar. Kendi kodunuza ait hiçbir şey o dizinde olmadığı için birleştirme, çakışma çözümü ya da &laquo;acaba burayı ben mi değiştirmiştim&raquo; sorusu yoktur.
                        </p>
                        <div class="vnl-code mb-5"><pre class="mb-0" style="background:transparent; white-space:pre-wrap;"><code>php vnl.php update --check    # yeni sürüm var mı (kendi ana sürümünüzde)
php vnl.php update --latest   # vnl/'yi değiştirir; eskisi vnl.bak/'a yedeklenir
php vnl.php doctor            # proje tarafı yeni çekirdeğe uygun mu
php vnl.php migrate --run      # bekleyen şema değişiklikleri (her sürücüde)</code></pre></div>
                        <p class="fs-6 text-body mb-5">
                            Güncelleyici arşivi açmadan önce Ed25519 imzasını kurulu <code>vnl/KEYS</code>'e karşı doğrular (4.0.0), içeriği arşivin <code>MANIFEST</code>'ine göre sınar ve yarıda kalırsa yedeği kendiliğinden geri yükler. <code>vnl/</code>'yi elle silip yenisini koymak da çalışır, ama bu denetimlerin hiçbiri yapılmaz. Ana sürüm geçişi ayrı bir komuttur (<code>--major</code>); ayrıntı <a href="/docs/versioning">Sürümleme</a> sayfasında.
                        </p>
                        <div class="alert alert-info d-flex align-items-start p-4 mb-0">
                            <i class="fa-solid fa-circle-info fs-2 text-info me-3 mt-1"></i>
                            <span class="text-body fs-6">Kendi migration'larınız <code>migrations/</code> altında, VNL'inkiler <code>vnl/migrations/</code> altındadır. İkisi ayrı numaralandığı için bir VNL yükseltmesi sizin sıranızla çakışmaz.</span>
                        </div>
                    </div>
                    <!--end::Section 5-->

                </div>
            </div>

        </div>
    </div>
</div>

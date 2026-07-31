<div class="d-flex flex-column flex-column-fluid">
    <div id="kt_app_content" class="app-content flex-column-fluid">
        <div id="kt_app_content_container" class="app-container container-xxl">

            <div class="vnl-page-hero mb-12 mt-6">
                <div class="vnl-overline mb-3">// docs/versioning</div>
                <h1 class="fs-2hx fw-bold mb-4 text-body-emphasis">Sürümleme ve Migration</h1>
                <div class="vnl-lead">Bir kurulumun iki sürümü vardır: <b>kodun</b> sürümü ve <b>şemanın</b> sürümü. Birincisi <code>vnl/VERSION</code> ile etiketlerden, ikincisi veritabanındaki migration kaydından okunur. İkisi ayrı yürür çünkü kodu güncellemek ile şemayı güncellemek ayrı adımlardır.</div>
            </div>

            <div class="card mb-10">
                <div class="card-body p-10">
                    <h3 class="fw-bold text-body-emphasis mb-4">Konsol</h3>
                    <p class="fs-5 text-body mb-4">
                        Bu sayfadaki her şey tek bir giriş noktasından çalışır. <code>vnl.php</code> projenizindir ve <code>index.php</code> gibi incedir: çekirdeği yükleyip komutu ona devreder.
                    </p>
                    <div class="vnl-code mb-4"><pre class="mb-0" style="background:transparent; white-space:pre-wrap;"><code>php vnl.php                 komut listesi
php vnl.php migrate --run   bekleyen şema değişikliklerini uygular
php vnl.php update --check  yeni çekirdek sürümü var mı
php vnl.php doctor          kurulumu bu çekirdeğin beklentisine karşı sınar
php vnl.php version         çekirdek ve proje sürümleri
php vnl.php serve           yerel geliştirme sunucusu
php vnl.php release         yayın arşivi üretir (bakım için)</code></pre></div>
                    <p class="fs-6 text-muted mb-0">
                        Windows'ta <code>vnl.cmd</code> kısayolu vardır: <code>vnl migrate --run</code> (PowerShell'de <code>.\vnl</code>). Unix'te <code>chmod +x vnl.php</code> sonrası <code>./vnl.php</code>. Adı düz <code>vnl</code> olamaz: bir dizinde aynı adı taşıyan dosya ve dizin bir arada bulunamaz, kökte zaten <code>vnl/</code> var.
                    </p>
                </div>
            </div>

            <div class="card mb-10">
                <div class="card-body p-10">

                    <!--begin::Section 1-->
                    <div class="mb-10">
                        <h3 class="fw-bold text-body-emphasis mb-4">1. Sürüm Numarası Ne Anlatır?</h3>
                        <p class="fs-5 text-body mb-5">
                            VNL <a href="https://semver.org/lang/tr/" target="_blank" rel="noopener">Semantic Versioning</a> kullanır: <code>ANA.YAN.YAMA</code>. Soyut kalmaması için VNL'e özel karşılıkları:
                        </p>

                        <div class="table-responsive mb-5">
                            <table class="table table-row-dashed align-middle fs-6">
                                <thead>
                                <tr class="fw-bold text-muted">
                                    <th style="width:110px">Artan</th>
                                    <th>Ne olduğunda</th>
                                    <th>Yükseltirken</th>
                                </tr>
                                </thead>
                                <tbody>
                                <tr>
                                    <td><b>ANA</b><br><span class="text-muted fs-7">3.0.0 → 4.0.0</span></td>
                                    <td>Var olan bir çağrının adı, imzası ya da davranışı değişti; bir dosya yer değiştirdi; bir özellik kaldırıldı. Şema değişikliği <b>zorunlu</b> hale geldi.</td>
                                    <td>CHANGELOG'un <b>Kırıcı Değişiklikler</b> bölümü baştan sona okunur; kod düzeltmesi gerekir.</td>
                                </tr>
                                <tr>
                                    <td><b>YAN</b><br><span class="text-muted fs-7">3.0.0 → 3.1.0</span></td>
                                    <td>Yeni bir özellik, yeni bir sınıf/metot/ayar geldi. Var olan hiçbir çağrı bozulmadı. Yeni migration gelebilir ama eski kod onsuz da çalışır.</td>
                                    <td><code>vnl/</code> değiştirilir, migration çalıştırılır. Kod düzeltmesi gerekmez.</td>
                                </tr>
                                <tr>
                                    <td><b>YAMA</b><br><span class="text-muted fs-7">3.0.0 → 3.0.1</span></td>
                                    <td>Hata düzeltmesi. Davranış yalnızca <b>zaten yanlış olan</b> yerde değişir.</td>
                                    <td><code>vnl/</code> değiştirilir. Başka bir şey gerekmez.</td>
                                </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="alert alert-info d-flex align-items-start p-4 mb-0">
                            <i class="fa-solid fa-circle-info fs-2 text-info me-3 mt-1"></i>
                            <span class="text-body fs-6">Bu sözleşme <b>yalnızca çekirdeği</b> bağlar. Projenizin kendi sürümü <code>config/definitions.php</code>'dedir ve onu istediğiniz gibi yönetirsiniz. Ayrım için <a href="/docs/architecture" class="fw-bold">Çekirdek ve Proje</a> sayfasına bakın.</span>
                        </div>
                    </div>
                    <!--end::Section 1-->

                    <div class="separator separator-dashed my-8"></div>

                    <!--begin::Section 2-->
                    <div class="mb-10">
                        <h3 class="fw-bold text-body-emphasis mb-4">2. Sürüm Nerede Yazar?</h3>

                        <div class="table-responsive mb-5">
                            <table class="table table-row-dashed align-middle fs-6">
                                <tbody>
                                <tr>
                                    <td style="width:230px"><code>vnl/VERSION</code></td>
                                    <td><b>Tek doğruluk kaynağı.</b> Çekirdeğin sürümü yalnızca burada yazar. Kodda <code>$cDefinitions-&gt;vnl_version()</code> ile okunur; hata sayfası da buradan gösterir.</td>
                                </tr>
                                <tr>
                                    <td><code>CHANGELOG.md</code></td>
                                    <td>Sürüm başına ne değiştiği. Kırıcı bölümü aynı zamanda göç rehberidir.</td>
                                </tr>
                                <tr>
                                    <td><code>git tag v3.0.0</code></td>
                                    <td>Yayın işareti. Etiket adı <code>v</code> önekiyle yazılır, dosyadaki numarayla birebir aynıdır.</td>
                                </tr>
                                <tr>
                                    <td><code>config/definitions.php</code></td>
                                    <td><code>$version</code> — <b>projenizin</b> sürümü. Çekirdeğinkiyle ilgisi yoktur.</td>
                                </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="alert alert-warning d-flex align-items-start p-4 mb-0">
                            <i class="fa-solid fa-triangle-exclamation fs-2 text-warning me-3 mt-1"></i>
                            <span class="text-body fs-6">Sürüm numarası dosya başlıklarına <b>yazılmaz</b>. VNL 3.0'a kadar on iki dosyanın başında elle yazılmış bir numara duruyordu; ilk yan sürümde hepsi yalan olurdu.</span>
                        </div>
                    </div>
                    <!--end::Section 2-->

                    <div class="separator separator-dashed my-8"></div>

                    <!--begin::Section 3-->
                    <div class="mb-10">
                        <h3 class="fw-bold text-body-emphasis mb-4">3. Yayın Sırası</h3>
                        <p class="fs-5 text-body mb-4">Bakım tarafıdır; türev projelerde çalıştırılmaz.</p>
                        <div class="vnl-code mb-5"><pre class="mb-0" style="background:transparent; white-space:pre-wrap;"><code>1. CHANGELOG.md         — "yayımlanmadı" başlığına tarih yazılır
2. vnl/VERSION          — yeni numara
3. README.md            — sürüm rozeti
4. php vnl.php release  — vnl/MANIFEST + dist/vnl-3.1.0.tar.gz üretir
5. git commit -m "Release 3.1.0"
6. git tag -a v3.1.0 -m "VNL 3.1.0"
7. dist/ çıktısını etiketin yayın varlıklarına yükle</code></pre></div>
                        <div class="alert alert-warning d-flex align-items-start p-4 mb-0">
                            <i class="fa-solid fa-triangle-exclamation fs-2 text-warning me-3 mt-1"></i>
                            <span class="text-body fs-6"><b>4. adım atlanırsa güncelleyici o sürüme geçemez</b> — arşivi yayın varlıkları arasında bulamaz. <code>vnl/MANIFEST</code> ayrıca yayın anında üretilir; iki yayın arasındaki commit'lerde eskir, o yüzden geliştirme sırasında <code>update --verify</code> değişmiş dosya bildirebilir. Bu doğrudur: çekirdek gerçekten yayımlandığı gibi değildir.</span>
                        </div>
                    </div>
                    <!--end::Section 3-->

                    <div class="separator separator-dashed my-8"></div>

                    <!--begin::Section 4-->
                    <div class="mb-10">
                        <h3 class="fw-bold text-body-emphasis mb-4">4. Migration Sözleşmesi</h3>

                        <div class="row g-5 mb-5">
                            <div class="col-md-6">
                                <div class="border border-dashed rounded p-5 h-100">
                                    <div class="fw-bold text-body-emphasis mb-2"><code>vnl/migrations/</code></div>
                                    <div class="text-muted fs-6">VNL'in şeması. Güncellemede yenilenir.</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="border border-dashed rounded p-5 h-100">
                                    <div class="fw-bold text-body-emphasis mb-2"><code>migrations/</code></div>
                                    <div class="text-muted fs-6">Sizin şemanız. VNL buraya hiç dokunmaz.</div>
                                </div>
                            </div>
                        </div>

                        <p class="fs-5 text-body mb-5">
                            İki sayaç <b>birbirinden bağımsız</b> yürür: <code>vnl/0001</code> ile <code>migrations/0001</code> yan yana durabilir, bir VNL yükseltmesi sizin numaralarınızla çakışmaz. Uygulama sırasında önce çekirdek gelir, çünkü sizin migration'larınız onun getirdiği tablolara dayanabilir.
                        </p>

                        <ul class="fs-5 text-body mb-5">
                            <li class="mb-2">Dosya adı <code>NNNN_kisa_aciklama.sql</code>; numara dört haneli ve artan.</li>
                            <li class="mb-2">Yayımlanmış bir migration <b>düzenlenmez</b>. Düzeltme için yeni numara açılır — araç dosya özetini saklar ve sonradan değişeni bildirir.</li>
                            <li class="mb-2">Her migration hem sıfırdan kurulumda hem mevcut kurulumda çalışabilmelidir: <code>CREATE TABLE IF NOT EXISTS</code> kullanın.</li>
                            <li class="mb-0">Sütun sözleşmesi: <code>snake_case</code>, birincil anahtar <code>id</code>, yumuşak silme <code>deleted_at</code>.</li>
                        </ul>

                        <div class="alert alert-warning d-flex align-items-start p-4 mb-0">
                            <i class="fa-solid fa-triangle-exclamation fs-2 text-warning me-3 mt-1"></i>
                            <span class="text-body fs-6"><b>MySQL'de <code>ALTER TABLE</code>'ın <code>IF EXISTS</code>'i yoktur.</b> Var olmayabilecek bir tabloyu değiştiriyorsanız koşullu yazın — aksi halde migration sıfırdan kurulumda çöker. Örneği <code>vnl/migrations/0001</code>'in başındadır: <code>information_schema</code>'ya bakılıp <code>PREPARE</code>/<code>EXECUTE</code> ile çalıştırılır.</span>
                        </div>
                    </div>
                    <!--end::Section 4-->

                    <div class="separator separator-dashed my-8"></div>

                    <!--begin::Section 5-->
                    <div class="mb-10">
                        <h3 class="fw-bold text-body-emphasis mb-4">5. Çalıştırıcı</h3>

                        <div class="vnl-code mb-5"><pre class="mb-0" style="background:transparent; white-space:pre-wrap;"><code>php vnl.php migrate --status         neyin uygulandığını gösterir
php vnl.php migrate --run            bekleyenleri sırayla uygular
php vnl.php migrate --run --dry-run  uygulamaz, çalışacak SQL'i basar

# PHP CLI yoksa
docker run --rm -v "$PWD":/app -w /app php:8.2-cli php vnl.php migrate --status</code></pre></div>

                        <p class="fs-5 text-body mb-4">Uygulananlar veritabanında <code>tbl_vnl_migrations</code> tablosunda tutulur. <code>--status</code> çıktısındaki işaretler:</p>

                        <div class="table-responsive mb-5">
                            <table class="table table-row-dashed align-middle fs-6">
                                <tbody>
                                <tr><td style="width:70px"><code>[+]</code></td><td>Uygulandı, dosya o günden beri değişmemiş.</td></tr>
                                <tr><td><code>[ ]</code></td><td>Bekliyor.</td></tr>
                                <tr><td><code>[!]</code></td><td>Uygulandı <b>ama dosya sonradan değişmiş</b>. Bu kurulum ile diğerleri aynı şemaya sahip olmayabilir; düzeltmeyi yeni numarayla yapın.</td></tr>
                                <tr><td><code>[?]</code></td><td>Kayıtta var, dosyası yok. Başka bir sürümden gelmiş olabilir.</td></tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="alert alert-warning d-flex align-items-start p-4 mb-0">
                            <i class="fa-solid fa-triangle-exclamation fs-2 text-warning me-3 mt-1"></i>
                            <span class="text-body fs-6"><b>MySQL'de DDL işlem (transaction) içinde geri alınamaz.</b> Bir migration ortasında çökerse ondan önceki ifadeler uygulanmış kalır. Araç böyle bir durumda kaydı <b>yazmaz</b> ve durur; hatayı düzeltip yeniden çalıştırdığınızda dosya baştan işlenir — bu yüzden <code>IF NOT EXISTS</code> sözleşmesi bir üslup tercihi değil, kurtarma mekanizmasıdır.</span>
                        </div>
                    </div>
                    <!--end::Section 5-->

                    <div class="separator separator-dashed my-8"></div>

                    <!--begin::Section 6-->
                    <div class="mb-0">
                        <h3 class="fw-bold text-body-emphasis mb-4">6. Yükseltme</h3>

                        <div class="vnl-code mb-5"><pre class="mb-0" style="background:transparent; white-space:pre-wrap;"><code>php vnl.php update --check      yeni sürüm var mı, neler değişti
php vnl.php update --to 3.1.0   çekirdeği değiştir
php vnl.php doctor              proje tarafı yeni çekirdeğe uygun mu
php vnl.php migrate --run       şemayı güncelle</code></pre></div>

                        <p class="fs-5 text-body mb-4">
                            <code>update</code> yalnızca <code>vnl/</code> dizinini değiştirir; projenin hiçbir dosyasına dokunmaz. Sırayla: arşivi indirir, kendi <code>MANIFEST</code>'ine karşı doğrular, mevcut çekirdeği <code>vnl.bak/</code> altına yedekler, sonra <b>yalnız içeriği değişen dosyaları</b> yazıp yeni sürümde bulunmayanları siler. Bir şey ters giderse <code>php vnl.php update --rollback</code>.
                        </p>

                        <div class="table-responsive mb-5">
                            <table class="table table-row-dashed align-middle fs-6">
                                <tbody>
                                <tr>
                                    <td style="width:210px"><code>--verify</code></td>
                                    <td>Kurulu çekirdeği <code>vnl/MANIFEST</code>'e karşı sınar. <b>Güncellemeden önce kendiliğinden çalışır</b> ve kurcalanmış dosya bulursa durur — aksi halde o değişiklikler sessizce silinirdi. Bilerek yapıldıysa <code>--force</code>.</td>
                                </tr>
                                <tr>
                                    <td><code>--file a.tar.gz</code></td>
                                    <td>Sunucu dışarıya çıkamıyorsa: arşivi elde indirip yolunu verin.</td>
                                </tr>
                                <tr>
                                    <td><code>--rollback</code></td>
                                    <td><code>vnl.bak/</code> üzerinden bir önceki sürüme döner.</td>
                                </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="alert alert-info d-flex align-items-start p-4 mb-5">
                            <i class="fa-solid fa-circle-info fs-2 text-info me-3 mt-1"></i>
                            <span class="text-body fs-6"><code>doctor</code> adımı atlanmamalı. Güncelleme projenin dosyalarına dokunamaz ama bazı sürümler proje tarafında bir şey bekler — 3.0'ın <code>index.php</code>'si <code>VNL_ROOT</code> tanımlamak zorundadır ve 2.x'ten gelen bir <code>index.php</code> çekirdeği hiç yükleyemez. CHANGELOG bunu yazar; <code>doctor</code> aynı şeyi <b>sorar</b>.</span>
                        </div>

                        <p class="fs-5 text-body mb-0">
                            Kod ve şema ayrı adımlar olduğu için sıra önemlidir: önce kod, sonra şema. Yeni kod eski şemayla kısa süre çalışabilmelidir; tersi genellikle çalışmaz.
                        </p>
                    </div>
                    <!--end::Section 6-->

                </div>
            </div>

        </div>
    </div>
</div>

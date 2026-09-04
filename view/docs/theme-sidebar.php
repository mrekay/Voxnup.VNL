<div class="d-flex flex-column flex-column-fluid">
    <div id="kt_app_content" class="app-content flex-column-fluid">
        <div id="kt_app_content_container" class="app-container container-xxl">

            <div class="vnl-page-hero mb-12 mt-6">
                <div class="vnl-overline mb-3">// docs/theme-sidebar</div>
                <h1 class="fs-2hx fw-bold mb-4 text-body-emphasis">Sidebar ve Menü</h1>
                <div class="vnl-lead">
                    Menü tanımı VNL 2.x'ten beri aynı dizidir: <code>view/static/leftSidebar.php</code>
                    içindeki <code>$SidebarMenu</code>. Tema yalnız üretilen markup'ı ve davranışı yeniler:
                    iki daraltma modu, iki alt menü modu ve mobilde native offcanvas.
                </div>
            </div>

            <div class="card mb-10">
                <div class="card-body p-10">

                    <div class="mb-10">
                        <h3 class="fw-bold text-body-emphasis mb-4">1. $SidebarMenu Sözleşmesi</h3>
                        <div class="table-responsive mb-5">
                            <table class="table table-row-dashed align-middle fs-7 gy-4">
                                <thead>
                                <tr class="text-start text-muted fw-bold fs-8 text-uppercase">
                                    <th class="min-w-125px">Alan</th><th>Açıklama</th>
                                </tr>
                                </thead>
                                <tbody class="text-body fw-semibold">
                                <tr><td><code>text</code></td><td>Menüde görünen ad</td></tr>
                                <tr><td><code>url</code></td><td>Bağlantı (başında <code>/</code> olmadan); child'lı öğelerde boş bırakılır</td></tr>
                                <tr><td><code>icon</code></td><td>FA kısa adı (<code>'home'</code> → <code>fa-solid fa-home</code>) <b>ya da</b> boşluklu tam class (<code>'bi bi-house'</code> olduğu gibi basılır). <b>Alt öğelerde de geçerlidir</b> — flyout panelde ve accordion listede basılır; boş bırakılırsa yalnız başlık görünür</td></tr>
                                <tr><td><code>permission</code></td><td>Yetki kodu. <code>vnl_can()</code> üzerinden projeye sorulur — bkz. §4. Boş bırakılırsa öğe herkese görünür</td></tr>
                                <tr><td><code>showmenu</code></td><td><code>0</code> her zaman gizli · <code>1</code> her zaman görünür · <code>2</code> yalnız oturum açıkken</td></tr>
                                <tr><td><code>child</code></td><td>Alt öğe dizisi — varlığında öğe alt menü başlığına dönüşür</td></tr>
                                <tr><td><code>qs_active</code> / <code>subs</code></td><td>Aktiflik eşleşmesine query-string / ek URL'ler dahil etme</td></tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="vnl-code"><pre><code>$SidebarMenu[] = [
    'text' => 'Raporlar',
    'icon' => 'bi bi-graph-up',      // tam class passthrough
    'showmenu' => 2,                 // yalnız girişte görünür
    'child' => [
        ['text' => 'Aylık', 'url' => 'rapor/aylik', 'icon' => 'clock', 'showmenu' => 1, 'permission' => '', 'child' => []],
        ['text' => 'Yıllık', 'url' => 'rapor/yillik', 'icon' => 'clock', 'showmenu' => 1, 'permission' => '', 'child' => []],
    ],
];</code></pre></div>
                        <p class="text-body-secondary mb-0 mt-4">
                            Extension'lar <code>$customSideBar</code> globali ile menüye öğe ekler
                            (bkz. <a href="/docs/extensions">Extension Sistemi</a>). Aktif sayfa tespiti
                            URL üzerinden otomatiktir; aktif dalın başlığı ve linki işaretlenir.
                        </p>
                    </div>

                    <div class="separator separator-dashed my-8"></div>

                    <div class="mb-10">
                        <h3 class="fw-bold text-body-emphasis mb-4">2. Daraltma Modları</h3>
                        <p class="text-body mb-4">
                            Marka satırındaki <i class="bi bi-chevron-double-left"></i> düğmesi sidebar'ı daraltır;
                            tercih <code>localStorage["vnl-sidebar-mini"]</code>'de kalıcıdır ve sayfa boyanmadan
                            geri uygulanır (yenilemede zıplama olmaz).
                        </p>
                        <div class="table-responsive mb-5">
                            <table class="table table-row-dashed align-middle fs-7 gy-4">
                                <thead>
                                <tr class="text-start text-muted fw-bold fs-8 text-uppercase">
                                    <th class="min-w-160px">Mod</th><th>Davranış</th><th class="min-w-200px">Seçim</th>
                                </tr>
                                </thead>
                                <tbody class="text-body fw-semibold">
                                <tr>
                                    <td><code>mini-hover</code> <span class="badge badge-soft-primary ms-1">varsayılan</span></td>
                                    <td>İkon rayı; üzerine gelince ya da içine tıklayınca içeriği <b>itmeden üstüne doğru</b> genişler, ayrılınca daralır</td>
                                    <td><code>themeDefaults()</code> ya da <code>'leftsidebar' => 'mini-hover'</code></td>
                                </tr>
                                <tr>
                                    <td><code>mini</code></td>
                                    <td>Sabit ikon rayı; genişlemez (alt menülere flyout ile erişilir)</td>
                                    <td><code>'leftsidebar' => 'mini'</code></td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                        <p class="text-body-secondary mb-0">
                            Uygulama genelinde hangi modun geçerli olacağı
                            <code>ConfigDefinitions::themeDefaults()</code> ile belirlenir — bkz.
                            <a href="/docs/theme-layout">Layout ve Preset'ler</a>. Mobilde (lg altı) sidebar
                            her zaman Bootstrap Offcanvas çekmecesidir; menü linkine tıklanınca kendini kapatır.
                        </p>
                    </div>

                    <div class="separator separator-dashed my-8"></div>

                    <div class="mb-10">
                        <h3 class="fw-bold text-body-emphasis mb-4">3. Alt Menü Modları</h3>
                        <div class="table-responsive mb-5">
                            <table class="table table-row-dashed align-middle fs-7 gy-4">
                                <thead>
                                <tr class="text-start text-muted fw-bold fs-8 text-uppercase">
                                    <th class="min-w-160px">Mod</th><th>Davranış</th>
                                </tr>
                                </thead>
                                <tbody class="text-body fw-semibold">
                                <tr>
                                    <td><code>flyout</code> <span class="badge badge-soft-primary ms-1">varsayılan</span></td>
                                    <td>Üzerine gelince ya da tıklayınca alt menü <b>sağda panel</b> olarak açılır; panel açıkken sidebar'ın kenarını canlı izler (daraltma/genişleme animasyonlarında birlikte kayar). <b>Uzun menüler kaydırılmaz</b> — dikey dolup yana yeni sütuna kırılır, sütunlar dengeli dağıtılır. ESC, dış tıklama ve fare ayrılması kapatır.</td>
                                </tr>
                                <tr>
                                    <td><code>accordion</code></td>
                                    <td>Alt alta liste; tıklamayla açılır/kapanır, aktif dal sayfa açılışında açık gelir. Daraltılmış rayda liste sığmayacağından geçici olarak flyout'a düşer.</td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="vnl-code"><pre><code>// Uygulama geneli (config/definitions.php):
private $theme_defaults = ['leftsidebar' => true, 'submenu' => 'accordion'];

// Ya da sayfa bazında:
$page_config = ['submenu' => 'accordion'];</code></pre></div>
                        <p class="text-body-secondary mb-0 mt-4">Mobil offcanvas'ta her iki mod da yerinde (inline) açılır.</p>
                    </div>

                    <div class="separator separator-dashed my-8"></div>

                    <div class="mb-10">
                        <h3 class="fw-bold text-body-emphasis mb-4">4. Yetkiye Göre Gizleme</h3>

                        <p class="fs-5 text-body mb-4">
                            Menü öğesinin <code>permission</code> anahtarı <code>vnl_can($kod)</code>'a sorulur.
                            Yetki <b>şeması projenindir</b> — VNL'in <code>tbl_permissions</code>'ı bir örnektir,
                            gerçek kurulumlar rol, kiracı, ürün gibi kendi eksenlerini katar. O yüzden çekirdek
                            burada karar vermez, projeye sorar:
                        </p>

                        <div class="vnl-code mb-5"><pre><code>// helpers/yetki.php — vnl_load_dir('helpers') kendiliğinden yükler,
// çekirdeğe dokunmak gerekmez.
function vnl_project_permission(string $code, $user): bool
{
    return $user !== null && UserPermissions::CanViewByCode($user->id, $code);
}</code></pre></div>

                        <div class="alert alert-info d-flex align-items-start p-4 mb-5">
                            <i class="fa-solid fa-circle-info fs-2 text-info me-3"></i>
                            <span class="text-body fs-6">
                                Bu fonksiyon <b>tanımlanmadığı sürece kapı açıktır</b>: her öğe görünür ve
                                davranış VNL 3.1 ile birebir aynıdır. Yeni bir çekirdek sürümü kimsenin
                                menüsünü boşaltmaz. Kanca istisna fırlatırsa yutulmaz — yetki sorgusundaki
                                bir hatayı sessizce "yetkisi yok"a çevirmek onu boş bir menünün arkasında
                                saklardı.
                            </span>
                        </div>

                        <p class="text-body mb-4">
                            <b>Alt öğeleri de kapsar.</b> Bir başlığın altındaki her öğe elenmişse başlık da
                            çizilmez (<code>hasVisibleChild()</code>); aksi halde kullanıcı ok işaretine
                            tıklar ve boş bir panel açılırdı. <code>showmenu</code> artık alt öğelerde de
                            geçerlidir.
                        </p>

                        <div class="alert alert-warning d-flex align-items-start p-4">
                            <i class="fa-solid fa-triangle-exclamation fs-2 text-warning me-3"></i>
                            <span class="text-body fs-6">
                                <b>VNL 3.1 ve öncesinde bu denetim hiç çalışmıyordu.</b>
                                <code>leftSidebar.php</code> içinde yetki satırları yoruma alınmış,
                                <code>$pagePermission = true;</code> ile kısa devre edilmişti;
                                <code>hasVisibleChild()</code> tanımlıydı ama hiçbir yerden çağrılmıyordu.
                                <code>view/static/leftSidebar.php</code> <b>projenindir</b>, güncelleme ona
                                dokunmaz: mevcut kurulumlarda dosyayı şablondaki sürümle değiştirin.
                                <code>php vnl.php doctor</code> bunu artık ayrıca sınıyor.
                            </span>
                        </div>
                    </div>

                    <div class="separator separator-dashed my-8"></div>

                    <div class="mb-0">
                        <h3 class="fw-bold text-body-emphasis mb-4">5. Topbar</h3>
                        <p class="text-body mb-4">
                            Üst çubuk iki temada da koyudur — sidebar ile kesintisiz tek çerçeve oluşturur.
                            İçeriği <code>view/static/topNavbar.php</code>'dedir:
                        </p>
                        <ul class="text-body mb-0">
                            <li class="mb-2"><b>Marka:</b> kare maskot + uygulama adı (<code>appName()</code>) + mono sürüm chip'i (<code>version()</code>). Marka metni bulunduğu zemine göre renk alır — logo varyantı gerekmez.</li>
                            <li class="mb-2"><b>Tema modu menüsü:</b> Açık / Koyu / Sistem — bkz. <a href="/docs/theme-design">Renkler ve Tipografi</a>.</li>
                            <li class="mb-2"><b>Bildirimler:</b> statik örnek dropdown; projeler <code>#notification_list</code>, <code>#notification_counts</code> ve <code>#notification_dot</code> id'lerini doldurur.</li>
                            <li><b>Kullanıcı menüsü:</b> girişliyse avatar + ad + çıkış; girişsiz sayfalarda "Giriş Yap" düğmesi.</li>
                        </ul>
                    </div>

                </div>
            </div>

        </div>
    </div>
</div>

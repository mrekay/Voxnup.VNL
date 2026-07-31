<div class="d-flex flex-column flex-column-fluid">
    <div id="kt_app_content" class="app-content flex-column-fluid">
        <div id="kt_app_content_container" class="app-container container-xxl">

            <div class="vnl-page-hero mb-12 mt-6">
                <div class="vnl-overline mb-3">// docs/theme-design</div>
                <h1 class="fs-2hx fw-bold mb-4 text-body-emphasis">Renkler, Tema Modu ve Tipografi</h1>
                <div class="vnl-lead">
                    Tema token bazlıdır: bileşenler <code>--vnl-*</code> ve Bootstrap 5.3'ün adaptif
                    <code>--bs-*</code> değişkenlerini tüketir. Renk, yüzey ve ölçek kararları tema
                    deposunda tek yerden (<code>scss/_variables.scss</code> ve <code>_tokens.scss</code>)
                    yönetilir.
                </div>
            </div>

            <div class="card mb-10">
                <div class="card-body p-10">

                    <div class="mb-10">
                        <h3 class="fw-bold text-body-emphasis mb-4">1. Tema Modu (Koyu / Açık / Sistem)</h3>
                        <p class="text-body mb-4">
                            Varsayılan mod <b>koyu</b>dur. Tercih <code>localStorage["data-bs-theme"]</code>'de saklanır;
                            <code>head-standard.php</code>'nin en başındaki satır içi script CSS yüklenmeden attribute'u
                            yazar — hiçbir modda açılışta yanıp sönme (FOUC) olmaz. Bootstrap'in doğal
                            <code>data-bs-theme</code> mekanizması kullanılır; özel bir renk sistemi yoktur.
                        </p>
                        <div class="vnl-code"><pre><code>&lt;!-- Kendi arayüzünüze mod seçici koymak için --&gt;
&lt;button type="button" data-vnl-theme-value="light"&gt;Açık&lt;/button&gt;
&lt;button type="button" data-vnl-theme-value="dark"&gt;Koyu&lt;/button&gt;
&lt;button type="button" data-vnl-theme-value="system"&gt;Sistem&lt;/button&gt;

// Mod değişimini dinlemek:
document.addEventListener('vnl.theme.changed', function (e) {
    console.log(e.detail.mode, e.detail.resolved);
});</code></pre></div>
                        <p class="text-body-secondary mb-0 mt-4">
                            Tek modda görünmesi gereken içerik için <code>.theme-light-show</code> /
                            <code>.theme-dark-show</code> yardımcıları vardır. Sidebar ve topbar bilinçli olarak
                            iki modda da koyudur (<code>data-bs-theme="dark"</code> attribute'u ile) —
                            koyu çerçeve, kimliğin parçasıdır.
                        </p>
                    </div>

                    <div class="separator separator-dashed my-8"></div>

                    <div class="mb-10">
                        <h3 class="fw-bold text-body-emphasis mb-4">2. Renkler ve Yüzeyler</h3>
                        <p class="text-body mb-4">
                            Marka rengi Voxnup yeşilidir (<code>$primary: #17a65c</code>); durum rengi
                            <code>$success</code> bilinçli olarak teal'e (<code>#0d9488</code>) kaydırılmıştır ki
                            rozetler primary'den ayrışsın. Yüzeyler üç katmandır ve derinlik çizgiyle değil
                            gölgeyle anlatılır (koyu temada ek olarak 1px ışık halkası):
                        </p>
                        <div class="row g-5 mb-5">
                            <div class="col-md-4">
                                <div class="p-5 rounded" style="background: var(--bs-body-bg); box-shadow: inset 0 0 0 1px var(--bs-border-color)">
                                    <div class="fw-semibold mb-1">Zemin</div>
                                    <code class="fs-8">--bs-body-bg</code>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="p-5 rounded" style="background: var(--vnl-surface); box-shadow: var(--vnl-card-shadow), 0 0 0 1px var(--vnl-card-ring)">
                                    <div class="fw-semibold mb-1">Yüzey (kart, modal)</div>
                                    <code class="fs-8">--vnl-surface</code>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="p-5 rounded" style="background: var(--vnl-surface-raised); box-shadow: var(--vnl-raised-shadow), 0 0 0 1px var(--vnl-raised-ring)">
                                    <div class="fw-semibold mb-1">Yükselen yüzey (dropdown, toast)</div>
                                    <code class="fs-8">--vnl-surface-raised</code>
                                </div>
                            </div>
                        </div>
                        <p class="text-body mb-4">
                            Metin renkleri Bootstrap'in adaptif ailesindedir — iki temada da doğru kontrast:
                            <code>text-body</code>, <code>text-body-secondary</code>, <code>text-body-tertiary</code>,
                            <code>text-body-emphasis</code>. Soft zeminler için <code>bg-*-subtle</code> +
                            <code>text-*-emphasis</code> çifti kullanılır.
                        </p>
                        <div class="table-responsive">
                            <table class="table table-row-dashed align-middle fs-7 gy-4">
                                <thead>
                                <tr class="text-start text-muted fw-bold fs-8 text-uppercase">
                                    <th class="min-w-250px">Token</th><th>Rol</th>
                                </tr>
                                </thead>
                                <tbody class="text-body fw-semibold">
                                <tr><td><code>--vnl-surface</code> / <code>--vnl-surface-raised</code></td><td>Kart/modal ve dropdown/toast zeminleri</td></tr>
                                <tr><td><code>--vnl-card-shadow</code> / <code>--vnl-card-ring</code></td><td>Kart derinliği (açıkta gölge, koyuda + ışık halkası)</td></tr>
                                <tr><td><code>--vnl-raised-shadow</code> / <code>--vnl-raised-ring</code></td><td>Yükselen yüzey derinliği</td></tr>
                                <tr><td><code>--vnl-sidebar-bg</code> / <code>--vnl-sidebar-*</code></td><td>Koyu çerçeve: zemin, metin, aktif renk/zemin</td></tr>
                                <tr><td><code>--vnl-topbar-height</code></td><td>Üst çubuk yüksekliği (3.5rem)</td></tr>
                                <tr><td><code>--vnl-sidebar-width</code> / <code>--vnl-sidebar-width-mini</code></td><td>Sidebar genişlikleri (16.25rem / 4.25rem)</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="separator separator-dashed my-8"></div>

                    <div class="mb-10">
                        <h3 class="fw-bold text-body-emphasis mb-4">3. Tipografi ve Ölçekler</h3>
                        <p class="text-body mb-4">
                            Gövde fontu self-host <b>Inter variable</b> (Türkçe glifler için latin-ext dahil),
                            aksan sesi <b>JetBrains Mono</b>'dur: kod blokları, breadcrumb (dosya yolu gibi),
                            overline'lar ve sürüm chip'i mono dizilir. Taban boyut 14px'tir.
                        </p>
                        <div class="table-responsive">
                            <table class="table table-row-dashed align-middle fs-7 gy-4">
                                <thead>
                                <tr class="text-start text-muted fw-bold fs-8 text-uppercase">
                                    <th class="min-w-200px">Aile</th><th>Örnekler</th><th>Not</th>
                                </tr>
                                </thead>
                                <tbody class="text-body fw-semibold">
                                <tr>
                                    <td>Yazı ölçeği</td>
                                    <td><code>fs-1…fs-9</code>, <code>fs-2x</code>, <code>fs-2hx</code>, <code>fs-3x</code></td>
                                    <td><code>fs-7/8/9</code> ve <code>*x</code> boyutları tema ekidir; büyükler RFS ile responsive'dir</td>
                                </tr>
                                <tr>
                                    <td>Boşluk ölçeği</td>
                                    <td><code>p-0…p-20</code>, <code>m-0…m-20</code>, negatifler (<code>mt-n5</code>)</td>
                                    <td>0–20 arası .25rem adımlı (<code>mb-10</code> = 2.5rem) — Metronic ile aynı ritim</td>
                                </tr>
                                <tr>
                                    <td>Piksel boyutları</td>
                                    <td><code>w-250px</code>, <code>w-lg-500px</code>, <code>min-w-125px</code>, <code>mw-650px</code>, <code>mh-300px</code>, <code>h-45px</code></td>
                                    <td>Liste <code>scss/_utilities.scss</code> içindedir; yeni değer gerekiyorsa ekleyip build alın</td>
                                </tr>
                                <tr>
                                    <td>Editoryal parçalar</td>
                                    <td><code>.vnl-overline</code>, <code>.vnl-page-hero</code>, <code>.vnl-lead</code></td>
                                    <td>Sayfa üst etiketi (<span class="vnl-overline">// örnek</span>), hero düzeni ve ölçülü giriş metni</td>
                                </tr>
                                <tr>
                                    <td>Sayısal hizalama</td>
                                    <td><code>numeric-tabular</code></td>
                                    <td>Eş genişlikli rakamlar; tablo gövdeleri bunu otomatik alır</td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="separator separator-dashed my-8"></div>

                    <div class="mb-0">
                        <h3 class="fw-bold text-body-emphasis mb-4">4. Skin Yazmak</h3>
                        <p class="text-body mb-4">
                            Skin, Bootstrap'i yeniden derlemeyen <b>hafif bir renk katmanıdır</b>
                            (Dracula örneği ~7 KB): <code>vnl.min.css</code>'in üstüne yüklenir.
                            Yazar yalnız paleti tanımlar; <code>scss/_skin-engine.scss</code>
                            subtle/emphasis türevlerini, link/focus zincirini, dolu buton durumlarını,
                            yüzeyleri ve sidebar/kod token'larını otomatik üretir.
                        </p>
                        <div class="d-flex flex-wrap align-items-center gap-3 mb-5">
                            <button type="button" class="btn btn-soft-primary" id="vnl_skin_demo_btn"
                                    onclick="vnlSkinDemo('dracula', this)">
                                <i class="bi bi-magic me-2"></i>Dracula'yı canlı dene
                            </button>
                            <span class="text-body-secondary fs-8">(yalnız bu sekmede, geçicidir — tekrar tıklayınca döner)</span>
                        </div>
                        <p class="text-body fw-semibold mb-2">1. Palet dosyasını oluşturun — <code>scss/skins/ornekskin.scss</code>:</p>
                        <div class="vnl-code mb-5"><pre><code>@use "../skin-engine" as vnl;

@include vnl.skin((
    primary: #bd93f9,          // marka rengi — link, focus, dolu/soft butonlar otomatik
    success: #50fa7b,
    info:    #8be9fd,
    warning: #ffb86c,
    danger:  #ff5555,

    sidebar: ( bg: #191a21, active: #bd93f9 ),          // koyu şerit (iki modda da)

    dark:  ( body-bg: #21222c, surface: #282a36, surface-raised: #343746,
             body-color: #f8f8f2, border: #44475a, code-bg: #282a36 ),
    light: ( body-bg: #f5f4fa, surface: #ffffff ),      // açık mod karşılıkları
));</code></pre></div>
                        <p class="text-body fw-semibold mb-2">2. Derleyin ve etkinleştirin:</p>
                        <div class="vnl-code"><pre><code># tema deposunda: scss/skins/ altındaki her dosya → vnl/assets/theme/css/skins/&lt;ad&gt;.min.css
VNL_OUT=/yol/VNL-TEMPLATE/vnl/assets/theme npm run build

// config/definitions.php — uygulama geneli:
private $theme_defaults = [ ..., 'skin' => 'ornekskin' ];

// ya da sayfa bazlı:
$page_config = ['skin' => 'ornekskin'];</code></pre></div>
                        <p class="text-body-secondary mb-0 mt-4">
                            Her anahtar opsiyoneldir — verilmeyen değer varsayılan kimlikten kalır.
                            Hazır örnek: tema deposunda <code>scss/skins/dracula.scss</code> (resmî Dracula paleti).
                            Radius/font gibi yapısal değişiklikler skin kapsamı dışındadır — onlar için
                            <code>_variables.scss</code> ile kendi build'inizi alın.
                        </p>
                    </div>

                    <script>
                        function vnlSkinDemo(name, btn) {
                            var existing = document.getElementById('vnl_skin_demo_css');
                            if (existing) {
                                existing.remove();
                                btn.innerHTML = '<i class="bi bi-magic me-2"></i>' + name.charAt(0).toUpperCase() + name.slice(1) + "'yı canlı dene";
                                return;
                            }
                            var link = document.createElement('link');
                            link.id = 'vnl_skin_demo_css';
                            link.rel = 'stylesheet';
                            link.href = '<?= $assets_address ?>/vnl/css/skins/' + name + '.min.css';
                            document.head.appendChild(link);
                            btn.innerHTML = '<i class="bi bi-arrow-counterclockwise me-2"></i>Varsayılana dön';
                        }
                    </script>

                </div>
            </div>

        </div>
    </div>
</div>

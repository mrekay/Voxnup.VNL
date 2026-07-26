<div class="d-flex flex-column flex-column-fluid">
    <div id="kt_app_content" class="app-content flex-column-fluid">
        <div id="kt_app_content_container" class="app-container container-xxl">

            <div class="vnl-page-hero mb-12 mt-6">
                <div class="vnl-overline mb-3">// docs/theme-design</div>
                <h1 class="fs-2hx fw-bold mb-4 text-body-emphasis">Renkler, Tema Modu ve Tipografi</h1>
                <div class="vnl-lead">
                    Tema token bazlıdır: bileşenler <code>--vnl-*</code> ve Bootstrap 5.3'ün adaptif
                    <code>--bs-*</code> değişkenlerini tüketir. Renk, yüzey ve ölçek kararları tek yerden
                    (<code>theme/scss/_variables.scss</code> ve <code>_tokens.scss</code>) yönetilir.
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

                    <div class="mb-0">
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
                                    <td>Liste <code>theme/scss/_utilities.scss</code> içindedir; yeni değer gerekiyorsa ekleyip build alın</td>
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

                </div>
            </div>

        </div>
    </div>
</div>

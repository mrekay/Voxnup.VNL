<div class="d-flex flex-column flex-column-fluid">
    <div id="kt_app_content" class="app-content flex-column-fluid">
        <div id="kt_app_content_container" class="app-container container-xxl">

            <div class="vnl-page-hero mb-12 mt-6">
                <div class="vnl-overline mb-3">// docs/theme-icons</div>
                <h1 class="fs-2hx fw-bold mb-4 text-body-emphasis">İkonlar</h1>
                <div class="vnl-lead">
                    İki set birlikte yüklüdür: temanın kendi arayüzü <b>Bootstrap Icons</b> kullanır,
                    <b>Font Awesome Free</b> ise proje kodlarındaki mevcut <code>fa-*</code> kullanımları
                    için uyumluluk katmanı olarak durur. Keenicons (<code>ki-*</code>) tamamen kalktı.
                </div>
            </div>

            <div class="card mb-10">
                <div class="card-body p-10">

                    <div class="mb-10">
                        <h3 class="fw-bold text-body-emphasis mb-4">1. Kullanım</h3>
                        <div class="d-flex flex-wrap align-items-center gap-4 fs-3 text-body mb-5">
                            <i class="bi bi-house"></i>
                            <i class="bi bi-gear"></i>
                            <i class="bi bi-bell"></i>
                            <i class="bi bi-graph-up"></i>
                            <i class="bi bi-cloud-arrow-up"></i>
                            <span class="fs-8 text-body-secondary align-self-center">← Bootstrap Icons (<code>bi bi-*</code>)</span>
                        </div>
                        <div class="d-flex flex-wrap align-items-center gap-4 fs-3 text-body mb-5">
                            <i class="fa-solid fa-house"></i>
                            <i class="fa-solid fa-gear"></i>
                            <i class="fas fa-bell"></i>
                            <i class="fa fa-clock"></i>
                            <span class="fs-8 text-body-secondary align-self-center">← Font Awesome (<code>fa-solid fa-*</code>; eski <code>fas</code>/<code>fa</code> önekleri de çalışır)</span>
                        </div>
                        <div class="vnl-code"><pre><code>&lt;i class="bi bi-house"&gt;&lt;/i&gt;              &lt;!-- Bootstrap Icons — temanın tercihi --&gt;
&lt;i class="fa-solid fa-house"&gt;&lt;/i&gt;        &lt;!-- Font Awesome 7 --&gt;
&lt;i class="fas fa-home"&gt;&lt;/i&gt;              &lt;!-- v5 söz dizimi compat ile çalışır --&gt;</code></pre></div>
                    </div>

                    <div class="separator separator-dashed my-8"></div>

                    <div class="mb-10">
                        <h3 class="fw-bold text-body-emphasis mb-4">2. Sidebar Menüsünde İkon</h3>
                        <p class="text-body mb-4">
                            <code>$SidebarMenu</code>'daki <code>icon</code> alanı iki biçimi de kabul eder:
                        </p>
                        <div class="vnl-code"><pre><code>'icon' => 'home',           // kısa ad → fa-solid fa-home (FA)
'icon' => 'bi bi-house',    // boşluklu tam class → olduğu gibi basılır (BI ya da başka set)</code></pre></div>
                    </div>

                    <div class="separator separator-dashed my-8"></div>

                    <div class="mb-0">
                        <h3 class="fw-bold text-body-emphasis mb-4">3. Tek Sete İnmek</h3>
                        <p class="text-body mb-4">
                            Her sayfada yüklenen stil dosyaları <code>fa.min.css</code> ~<b>122 KB</b> ve
                            <code>bi.min.css</code> ~<b>81 KB</b>'tır. Font dosyaları (woff2: FA solid ~116 KB,
                            brands ~112 KB, regular ~19 KB; BI ~131 KB) tarayıcı o setten bir ikon çizdiğinde
                            indirilir.
                        </p>
                        <ul class="text-body mb-4">
                            <li class="mb-2"><b>Yalnız Bootstrap Icons (desteklenen yol):</b> koddaki <code>fa-*</code> kullanımlarını <code>bi-*</code> karşılıklarına çevirin (menü kısa adları dahil — kısa ad <code>fa-solid fa-*</code> üretir, tam class yazın). Sonra <code>fa.min.css</code>'i yüklemeyi bırakın: <code>head-standard.php</code>'de ayrı bir satır yoktur, liste çekirdekten gelir (<code>vnl_core_styles()</code>); döngü projenindir ve süzülebilir:</li>
                            <li><b>Yalnız Font Awesome: yapılamaz.</b> Bootstrap Icons çekirdeğin bir parçasıdır: parola göster/gizle düğmesi (<code>vnl.min.js</code>), VNL Table'ın sayfalama, filtre ve dışa aktarma düğmeleri (<code>vnltable.js</code>) ve <code>vnl.js</code> <code>bi-*</code> basar. Bu dosyalar <code>vnl/</code> altındadır, değiştirilemez; <code>bi.min.css</code> kalkarsa bu düğmeler ikonsuz kalır.</li>
                        </ul>
                        <div class="vnl-code"><pre><code>&lt;?php // view/includes/static/head-standard.php
foreach (vnl_core_styles($vnl_skin) as $vnl_css) {
    if (basename(parse_url($vnl_css, PHP_URL_PATH)) === 'fa.min.css') continue;   // yalnız BI
    ?&gt;
&lt;link href="&lt;?= $vnl_css ?&gt;" rel="stylesheet" type="text/css"/&gt;
&lt;?php } ?&gt;</code></pre></div>
                    </div>

                </div>
            </div>

        </div>
    </div>
</div>

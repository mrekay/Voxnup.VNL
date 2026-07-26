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
                            İki set birlikte ~<b>370 KB</b> CSS + font yüküdür. Projeniz tek sete karar verdiğinde:
                        </p>
                        <ul class="text-body mb-0">
                            <li class="mb-2"><b>Yalnız Bootstrap Icons:</b> <code>head-standard.php</code>'den <code>fa.min.css</code> satırını silin; koddaki <code>fa-*</code> kullanımlarını <code>bi-*</code> karşılıklarına çevirin (menü kısa adları dahil). ~245 KB kazanç.</li>
                            <li><b>Yalnız Font Awesome:</b> <code>bi.min.css</code> satırını silin; tema iskeleti <code>view/static/</code> içindeki <code>bi-*</code> ikonlarını <code>fa-*</code> ile değiştirin. ~125 KB kazanç.</li>
                        </ul>
                    </div>

                </div>
            </div>

        </div>
    </div>
</div>

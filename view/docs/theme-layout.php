<div class="d-flex flex-column flex-column-fluid">
    <div id="kt_app_content" class="app-content flex-column-fluid">
        <div id="kt_app_content_container" class="app-container container-xxl">

            <div class="vnl-page-hero mb-12 mt-6">
                <div class="vnl-overline mb-3">// docs/theme-layout</div>
                <h1 class="fs-2hx fw-bold mb-4 text-body-emphasis">Layout ve Preset'ler</h1>
                <div class="vnl-lead">
                    Her sayfanın iskeleti controller'daki <code>$page_config</code> ile kurulur.
                    Preset'ler yaygın iskeletleri tek anahtarla verir; uygulama geneli varsayılanlar
                    <code>ConfigDefinitions::themeDefaults()</code> ile sistemden sisteme değişir.
                </div>
            </div>

            <div class="card mb-10">
                <div class="card-body p-10">

                    <div class="mb-10">
                        <h3 class="fw-bold text-body-emphasis mb-4">1. Layout Preset'leri</h3>
                        <p class="text-body mb-4">
                            <code>$page_config['layout']</code> ile seçilir. Merge sırası:
                            <b>varsayılanlar → preset → sizin <code>$page_config</code>'iniz</b> —
                            yani preset'in her bayrağını sayfa bazında ezebilirsiniz.
                        </p>
                        <div class="table-responsive mb-5">
                            <table class="table table-row-dashed align-middle fs-7 gy-4">
                                <thead>
                                <tr class="text-start text-muted fw-bold fs-8 text-uppercase">
                                    <th>Preset</th><th>Sidebar</th><th>Topbar</th><th>İçerik Başlığı</th><th>Footer</th><th>Tipik Kullanım</th>
                                </tr>
                                </thead>
                                <tbody class="text-body fw-semibold">
                                <tr><td><code>app</code> <span class="badge badge-soft-primary ms-1">varsayılan</span></td><td>✓ (sistem varsayılanı)</td><td>✓</td><td>✓</td><td>—</td><td>ERP / yönetim paneli</td></tr>
                                <tr><td><code>top-nav</code></td><td>—</td><td>✓</td><td>✓</td><td>✓</td><td>Araç / SaaS sayfaları</td></tr>
                                <tr><td><code>landing</code></td><td>—</td><td>✓</td><td>—</td><td>✓</td><td>Pazarlama / tanıtım</td></tr>
                                <tr><td><code>focus</code></td><td>—</td><td>—</td><td>—</td><td>—</td><td>Login, hata sayfaları (ortalanmış tek kolon)</td></tr>
                                <tr><td><code>blank</code></td><td>—</td><td>—</td><td>—</td><td>—</td><td>Boş kanvas (yalnız head + asset'ler)</td></tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="vnl-code"><pre><code>// controller/rapor.php
$page_config = [
    'layout' => 'top-nav',   // preset
    'footer' => false,       // preset'in tek bayrağını ez
];

// Eski kullanım da aynen çalışır:
$page_config = ['topbar' => true, 'leftsidebar' => true, 'contentheader' => true];</code></pre></div>
                    </div>

                    <div class="separator separator-dashed my-8"></div>

                    <div class="mb-10">
                        <h3 class="fw-bold text-body-emphasis mb-4">2. Uygulama Geneli Varsayılanlar</h3>
                        <p class="text-body mb-4">
                            Sidebar'ın açılış durumu ve alt menü davranışı <b>sistemden sisteme değişir</b>:
                            bir ERP daraltılmış (mini-hover) başlamak isterken bir pazarlama sitesi tam açık ister.
                            Bu yüzden bu iki varsayılan <code>libs/config/config_definitions.php</code> içindeki
                            <code>themeDefaults()</code> ile uygulama genelinde belirlenir; sayfalar yine
                            <code>$page_config</code> ile ezebilir.
                        </p>
                        <div class="vnl-code"><pre><code>// libs/config/config_definitions.php
private $theme_defaults = [
    'leftsidebar' => true,       // true | 'mini' | 'mini-hover'
    'submenu'     => 'flyout',   // 'flyout' | 'accordion'
    'skin'        => null,       // null | 'dracula' | skins/ altındaki herhangi bir ad
];</code></pre></div>
                        <p class="text-body-secondary mb-0 mt-4">
                            <code>app</code> preset'i sidebar durumunu bu varsayılandan alır; diğer preset'lerde
                            sidebar zaten kapalıdır. Eski türev projelerde <code>themeDefaults()</code> tanımlı
                            değilse tema kendi güvenli varsayılanlarına düşer — güncelleme kırmaz.
                        </p>
                    </div>

                    <div class="separator separator-dashed my-8"></div>

                    <div class="mb-10">
                        <h3 class="fw-bold text-body-emphasis mb-4">3. $page_config Tam Referansı</h3>
                        <div class="table-responsive">
                            <table class="table table-row-dashed align-middle fs-7 gy-4">
                                <thead>
                                <tr class="text-start text-muted fw-bold fs-8 text-uppercase">
                                    <th class="min-w-160px">Anahtar</th><th class="min-w-200px">Değerler</th><th>Açıklama</th>
                                </tr>
                                </thead>
                                <tbody class="text-body fw-semibold">
                                <tr><td><code>layout</code></td><td><code>app</code> | <code>top-nav</code> | <code>landing</code> | <code>focus</code> | <code>blank</code></td><td>İskelet preset'i (varsayılan <code>app</code>)</td></tr>
                                <tr><td><code>leftsidebar</code></td><td><code>true</code> | <code>false</code> | <code>'mini'</code> | <code>'mini-hover'</code></td><td>Sidebar durumu; string değerler daraltılmış başlatır</td></tr>
                                <tr><td><code>submenu</code></td><td><code>'flyout'</code> | <code>'accordion'</code></td><td>Alt menü davranışı — bkz. <a href="/docs/theme-sidebar">Sidebar ve Menü</a></td></tr>
                                <tr><td><code>skin</code></td><td><code>null</code> | skin adı</td><td>Renk katmanı — bkz. <a href="/docs/theme-design">Renkler</a> §4 Skin Yazmak</td></tr>
                                <tr><td><code>topbar</code></td><td><code>true</code> | <code>false</code></td><td>Üst çubuk</td></tr>
                                <tr><td><code>contentheader</code></td><td><code>true</code> | <code>false</code></td><td>Sayfa başlığı + breadcrumb + toolbar aksiyonları bandı</td></tr>
                                <tr><td><code>footer</code></td><td><code>true</code> | <code>false</code></td><td>Alt bilgi</td></tr>
                                <tr><td><code>loader</code></td><td><code>true</code> | <code>false</code></td><td><code>#loadingSpinner</code> katmanının basılması</td></tr>
                                <tr><td><code>toolbaritems</code></td><td>dizi</td><td>İçerik başlığının sağındaki buton/dropdown'lar (aşağıda)</td></tr>
                                <tr><td><code>css_files</code> / <code>js_files</code></td><td>dizi</td><td>Route'a özel ek include'lar — bkz. <a href="/docs/view-includes">View Includes</a></td></tr>
                                <tr><td><code>public</code></td><td><code>true</code></td><td>Login zorunluluğundan muaf sayfa (bkz. <a href="/docs/routing">Routing</a>)</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="separator separator-dashed my-8"></div>

                    <div class="mb-0">
                        <h3 class="fw-bold text-body-emphasis mb-4">4. Toolbar Aksiyonları (toolbaritems)</h3>
                        <p class="text-body mb-4">
                            İçerik başlığının sağına buton ya da dropdown yerleştirir. <code>items</code> verilen
                            öğe otomatik dropdown olur (<code>data-bs-toggle</code> tema tarafından eklenir).
                        </p>
                        <div class="vnl-code"><pre><code>$page_config['toolbaritems'] = [
    [
        'type'   => 'a',
        'text'   => 'Fiyatlandırma',
        'class'  => 'btn btn-sm btn-soft-primary fw-bold',
        'custom' => 'href="/stripe-test"',
        'icon'   => 'bi bi-tag',
    ],
    [
        'type'  => 'button',
        'text'  => 'İşlemler',
        'class' => 'btn btn-sm btn-primary',
        'items' => [
            ['type' => 'button', 'text' => 'Dışa Aktar', 'class' => '', 'icon' => 'bi bi-download', 'custom' => 'onclick="exportAll()"'],
            ['type' => 'a', 'text' => 'Ayarlar', 'class' => '', 'icon' => 'bi bi-gear', 'custom' => 'href="/ayarlar"'],
        ],
    ],
];</code></pre></div>
                    </div>

                </div>
            </div>

        </div>
    </div>
</div>

<div class="d-flex flex-column flex-column-fluid">
    <div id="kt_app_content" class="app-content flex-column-fluid">
        <div id="kt_app_content_container" class="app-container container-xxl">

            <div class="vnl-page-hero mb-12 mt-6">
                <div class="vnl-overline mb-3">// docs/templating</div>
                <h1 class="fs-2hx fw-bold mb-4 text-body-emphasis">Şablon Söz Dizimi</h1>
                <div class="vnl-lead">View dosyaları <code>&#123;&#123; $degisken }}</code> gibi kısa bir söz dizimi kullanabilir. VNL bunu düz PHP'ye çevirir, derlenmiş çıktıyı önbelleğe alır ve <code>require</code> eder. Söz dizimi kullanmayan dosyalara <b>hiç dokunulmaz</b> — mevcut view'larınız olduğu gibi çalışmaya devam eder.</div>
            </div>

            <div class="alert alert-primary d-flex align-items-center p-5 mb-10">
                <i class="fa-solid fa-shield-halved fs-2hx text-primary me-4"></i>
                <div class="d-flex flex-column text-primary">
                    <h4 class="mb-1 text-primary">Kaçış Artık Çıkış Katmanının İşi</h4>
                    <span>VNL 3.0'da gelen veri girişte kaçışlanmaz (bkz. <a href="/docs/request-handling" class="fw-bold">İstek Yönetimi</a>). Veritabanına ne gönderildiyse o yazılır; XSS koruması ekrana basarken yapılır. <code>&#123;&#123; }}</code> bunu otomatik yapar, düz PHP'de karşılığı <code>vnl_e()</code>'dir. <code>&lt;?= $veri ?&gt;</code> yazmak artık <b>kaçışsız çıktı</b> demektir.</span>
                </div>
            </div>

            <div class="card mb-10">
                <div class="card-body p-10">

                    <!--begin::Section 1-->
                    <div class="mb-10">
                        <h3 class="fw-bold text-body-emphasis mb-4">1. Nasıl Çalışır?</h3>
                        <p class="fs-5 text-body mb-5">
                            <code>view()</code> ve <code>modal()</code> fonksiyonları dosya yolunu doğrudan döndürmez; önce <code>VnlTemplate::resolve()</code>'dan geçirir. Derleyici dosyada şablon söz dizimi arar:
                        </p>

                        <div class="row g-5 mb-6">
                            <div class="col-md-6">
                                <div class="border border-dashed rounded p-5 h-100">
                                    <div class="d-flex align-items-center mb-3">
                                        <i class="fa-solid fa-file-code fs-2 text-success me-3"></i>
                                        <h6 class="mb-0 fw-bold">Söz dizimi yok</h6>
                                    </div>
                                    <span class="text-body fs-6">Kaynak dosyanın <b>kendi yolu</b> döner. Derleme yapılmaz, önbelleğe hiçbir şey yazılmaz. Eski view'larınızın davranışı birebir aynı kalır.</span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="border border-dashed rounded p-5 h-100">
                                    <div class="d-flex align-items-center mb-3">
                                        <i class="fa-solid fa-wand-magic-sparkles fs-2 text-primary me-3"></i>
                                        <h6 class="mb-0 fw-bold">Söz dizimi var</h6>
                                    </div>
                                    <span class="text-body fs-6">Dosya düz PHP'ye çevrilip <code>files/cache/views/</code> altına yazılır; <code>require</code> edilen o dosyadır. Kaynak değişince yeniden derlenir.</span>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex align-items-center bg-body-tertiary border border-dashed rounded p-4 mb-6">
                            <i class="fa-solid fa-arrow-right fs-2 text-primary me-3"></i>
                            <span class="fs-5 text-body"><code>require view('kullanici/liste')</code> &nbsp;→&nbsp; <code>VnlTemplate::resolve()</code> &nbsp;→&nbsp; <code>files/cache/views/view_kullanici_liste_php-3f2a1b9c.php</code></span>
                        </div>

                        <div class="alert alert-success d-flex align-items-center p-4">
                            <i class="fa-solid fa-circle-check fs-2 text-success me-3"></i>
                            <span class="text-body fs-6">Derlenmiş dosyada <b>satır numaraları kaynakla birebir örtüşür</b>. Hata sayfası ve stack trace her zaman view dosyanızın doğru satırını gösterir.</span>
                        </div>
                    </div>
                    <!--end::Section 1-->

                    <div class="separator separator-dashed my-8"></div>

                    <!--begin::Section 2-->
                    <div class="mb-10">
                        <h3 class="fw-bold text-body-emphasis mb-4">2. Çıktı Alma</h3>

                        <div class="table-responsive border rounded bg-body-tertiary mb-6">
                            <table class="table align-middle table-row-dashed fs-6 gy-4 mb-0">
                                <thead>
                                <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0 border-bottom">
                                    <th class="ps-5 min-w-200px">Söz Dizimi</th>
                                    <th class="min-w-250px">Karşılığı</th>
                                    <th class="min-w-300px">Açıklama</th>
                                </tr>
                                </thead>
                                <tbody class="text-body fw-semibold">
                                <tr>
                                    <td class="ps-5"><code>&#123;&#123; $ad }}</code></td>
                                    <td><code>&lt;?= vnl_e($ad) ?&gt;</code></td>
                                    <td><span class="badge badge-soft-success me-2">Güvenli</span> HTML'e kaçışlanmış çıktı. Günlük kullanımda tercih edeceğiniz biçim.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>&#123;!! $html !!}</code></td>
                                    <td><code>&lt;?= $html ?&gt;</code></td>
                                    <td><span class="badge badge-soft-danger me-2">Kaçışsız</span> Ham çıktı. Yalnızca HTML'ini kendiniz ürettiğiniz ya da temizlediğiniz veride kullanın.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>&#123;&#123;-- yorum --}}</code></td>
                                    <td><em>Çıktıya hiç girmez</em></td>
                                    <td>Şablon yorumu. HTML yorumundan farkı: tarayıcıya <b>gönderilmez</b>. Satır sayısı korunur.</td>
                                </tr>
                                </tbody>
                            </table>
                        </div>

                        <p class="fs-6 text-body mb-3">İfade yazabildiğiniz her yerde fonksiyon çağırabilirsiniz — süslü parantezin içi düz PHP ifadesidir:</p>

                        <div class="vnl-code mb-6">
                            <pre class="mb-0" style="background: transparent; white-space: pre-wrap;"><code>&#123;&#123; $kullanici->fullname() }}
&#123;&#123; lang('ERR_PERM') }}
&#123;&#123; lang('PLEASE_FILL_IN_FIELD', 'Ad Soyad') }}
&#123;&#123; DateTimeModule::ConvertTrDate($kayit->InsertDate) }}
&#123;&#123; $fiyat > 0 ? TextGeneralModule::NumberFormatTR($fiyat, 2) : '-' }}

&#123;&#123;-- Bu satır çıktıya girmez, tarayıcı kaynağında da görünmez --}}

&#123;!! $editorIcerigi !!}   &#123;&#123;-- ham HTML: içeriği siz temizlemiş olmalısınız --}}
</code></pre>
                        </div>

                        <h5 class="fw-bold text-body-emphasis mb-3"><code>vnl_e()</code> ne yapar?</h5>
                        <div class="table-responsive border rounded bg-body-tertiary mb-5">
                            <table class="table align-middle table-row-dashed fs-6 gy-4 mb-0">
                                <thead>
                                <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0 border-bottom">
                                    <th class="ps-5 min-w-200px">Değer</th>
                                    <th class="min-w-300px">Çıktı</th>
                                </tr>
                                </thead>
                                <tbody class="text-body fw-semibold">
                                <tr>
                                    <td class="ps-5"><code>null</code></td>
                                    <td>Boş string. <code>"" </code>&nbsp;— PHP 8.1+ deprecation uyarısı üretmez.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5">Dizi / nesne dizisi</td>
                                    <td>JSON olarak basılır (<code>JSON_UNESCAPED_UNICODE</code>) — <code>Array to string conversion</code> hatası çıkmaz.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5">Metin, sayı, bool</td>
                                    <td><code>htmlspecialchars(ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')</code> — tek ve çift tırnaklar da kaçışlanır, bozuk UTF-8 baytları çıktıyı düşürmez.</td>
                                </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="alert alert-warning d-flex align-items-center p-4">
                            <i class="fa-solid fa-triangle-exclamation fs-2 text-warning me-3"></i>
                            <span class="text-body fs-6"><code>&#123;&#123; }}</code> HTML gövdesi için tasarlanmıştır. Değeri <b>JavaScript içine</b> basacaksanız <code>&lt;script&gt;var d = &lt;?= json_encode($veri) ?&gt;;&lt;/script&gt;</code> kullanın; HTML kaçışı JS bağlamında yeterli koruma değildir.</span>
                        </div>
                    </div>
                    <!--end::Section 2-->

                    <div class="separator separator-dashed my-8"></div>

                    <!--begin::Section 3-->
                    <div class="mb-10">
                        <h3 class="fw-bold text-body-emphasis mb-4">3. Direktifler</h3>
                        <p class="fs-5 text-body mb-5">
                            Kontrol yapıları için <code>&lt;?php ... ?&gt;</code> açıp kapamak yerine kısa direktifler kullanılabilir. Desteklenen direktifler <b>tam olarak</b> aşağıdakilerdir — liste bilinçli olarak kısa tutulmuştur:
                        </p>

                        <div class="table-responsive border rounded bg-body-tertiary mb-6">
                            <table class="table align-middle table-row-dashed fs-6 gy-4 mb-0">
                                <thead>
                                <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0 border-bottom">
                                    <th class="ps-5 min-w-250px">Direktif</th>
                                    <th class="min-w-350px">Karşılığı</th>
                                </tr>
                                </thead>
                                <tbody class="text-body fw-semibold">
                                <tr>
                                    <td class="ps-5"><code>&#64;if ($k->id > 0)</code></td>
                                    <td><code>&lt;?php if ($k->id > 0): ?&gt;</code></td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>&#64;elseif ($k->id === 0)</code></td>
                                    <td><code>&lt;?php elseif ($k->id === 0): ?&gt;</code></td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>&#64;else</code></td>
                                    <td><code>&lt;?php else: ?&gt;</code></td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>&#64;endif</code></td>
                                    <td><code>&lt;?php endif; ?&gt;</code></td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>&#64;foreach ($liste as $k)</code></td>
                                    <td><code>&lt;?php foreach ($liste as $k): ?&gt;</code></td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>&#64;endforeach</code></td>
                                    <td><code>&lt;?php endforeach; ?&gt;</code></td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>&#64;include('static/header')</code></td>
                                    <td><code>&lt;?php require view('static/header'); ?&gt;</code></td>
                                </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="row g-5 mb-6">
                            <div class="col-md-6">
                                <h6 class="fw-bold mb-3">Şablon söz dizimiyle</h6>
                                <div class="vnl-code">
                                    <pre class="mb-0" style="background: transparent; white-space: pre-wrap;"><code>&#64;if (empty($kullanicilar))
    &lt;div class="alert alert-info"&gt;Kayıt yok.&lt;/div&gt;
&#64;else
    &lt;table class="table"&gt;
        &#64;foreach ($kullanicilar as $k)
            &lt;tr&gt;
                &lt;td&gt;&#123;&#123; $k->Username }}&lt;/td&gt;
                &lt;td&gt;&#123;&#123; $k->fullname() }}&lt;/td&gt;
            &lt;/tr&gt;
        &#64;endforeach
    &lt;/table&gt;
&#64;endif
</code></pre>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <h6 class="fw-bold mb-3">Düz PHP ile (aynı sonuç)</h6>
                                <div class="vnl-code">
                                    <pre class="mb-0" style="background: transparent; white-space: pre-wrap;"><code>&lt;?php if (empty($kullanicilar)): ?&gt;
    &lt;div class="alert alert-info"&gt;Kayıt yok.&lt;/div&gt;
&lt;?php else: ?&gt;
    &lt;table class="table"&gt;
        &lt;?php foreach ($kullanicilar as $k): ?&gt;
            &lt;tr&gt;
                &lt;td&gt;&lt;?= vnl_e($k->Username) ?&gt;&lt;/td&gt;
                &lt;td&gt;&lt;?= vnl_e($k->fullname()) ?&gt;&lt;/td&gt;
            &lt;/tr&gt;
        &lt;?php endforeach; ?&gt;
    &lt;/table&gt;
&lt;?php endif; ?&gt;
</code></pre>
                                </div>
                            </div>
                        </div>

                        <div class="alert alert-warning d-flex align-items-center p-4 mb-5">
                            <i class="fa-solid fa-circle-info fs-2 text-warning me-3"></i>
                            <span class="text-body fs-6"><code>&#64;if</code>, <code>&#64;elseif</code>, <code>&#64;foreach</code> ve <code>&#64;include</code> için <b>parantez zorunludur</b>. Bu, metin içindeki e-posta adreslerinin (<code>info&#64;ifade.com</code>) yanlışlıkla direktif sanılmasını engeller. <code>&#64;for</code>, <code>&#64;while</code>, <code>&#64;switch</code>, <code>&#64;php</code> gibi direktifler <b>yoktur</b> — bunlar için düz PHP yazın.</span>
                        </div>

                        <div class="alert alert-primary d-flex align-items-center p-4">
                            <i class="fa-solid fa-lightbulb fs-2 text-primary me-3"></i>
                            <span class="text-body fs-6">İki söz dizimi <b>aynı dosyada karışık</b> kullanılabilir. Direktifler düz PHP'ye çevrildiği için <code>&#64;foreach</code> ile açılan bir döngüyü <code>&lt;?php endforeach; ?&gt;</code> ile kapatmak bile çalışır — ama okunabilirlik için tek biçimde kalın.</span>
                        </div>
                    </div>
                    <!--end::Section 3-->

                    <div class="separator separator-dashed my-8"></div>

                    <!--begin::Section 4-->
                    <div class="mb-10">
                        <h3 class="fw-bold text-body-emphasis mb-4">4. Kaçış — JS Şablon Motorları İçin</h3>
                        <p class="fs-5 text-body mb-5">
                            Vue, Handlebars ve Mustache gibi kütüphaneler de <code>&#123;&#123; }}</code> söz dizimi kullanır. VNL'in bunlara dokunmaması için ifadenin başına <code>&#64;</code> koyun:
                        </p>

                        <div class="table-responsive border rounded bg-body-tertiary mb-6">
                            <table class="table align-middle table-row-dashed fs-6 gy-4 mb-0">
                                <thead>
                                <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0 border-bottom">
                                    <th class="ps-5 min-w-200px">Yazdığınız</th>
                                    <th class="min-w-200px">Çıktıya giden</th>
                                    <th class="min-w-250px">Kullanım</th>
                                </tr>
                                </thead>
                                <tbody class="text-body fw-semibold">
                                <tr>
                                    <td class="ps-5"><code>&#64;&#123;&#123; mesaj }}</code></td>
                                    <td><code>&#123;&#123; mesaj }}</code></td>
                                    <td>Vue / Handlebars ifadesi</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>&#64;&#123;!! html !!}</code></td>
                                    <td><code>&#123;!! html !!}</code></td>
                                    <td>Ham çıktı söz dizimini literal basmak</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>&#64;&#64;if</code></td>
                                    <td><code>&#64;if</code></td>
                                    <td>Direktifin kendisini metin olarak göstermek</td>
                                </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="vnl-code mb-5">
                            <pre class="mb-0" style="background: transparent; white-space: pre-wrap;"><code>&lt;div id="app"&gt;
    &lt;!-- VNL dokunmaz, Vue işler --&gt;
    &lt;span&gt;&#64;&#123;&#123; kullanici.ad }}&lt;/span&gt;

    &lt;!-- VNL sunucuda basar --&gt;
    &lt;span&gt;&#123;&#123; $CurrentUser->fullname() }}&lt;/span&gt;
&lt;/div&gt;
</code></pre>
                        </div>

                        <div class="alert alert-warning d-flex align-items-center p-4">
                            <i class="fa-solid fa-circle-info fs-2 text-warning me-3"></i>
                            <span class="text-body fs-6">Bu dokümantasyon sayfasının kendisi de bir view dosyasıdır. Söz dizimi örneklerinin derlenmemesi için burada <b>HTML entity</b> (<code>&amp;#123;&amp;#123;</code>, <code>&amp;#64;</code>) kullanılmıştır — kaçış işaretçisi yerine bu yöntemi de tercih edebilirsiniz.</span>
                        </div>
                    </div>
                    <!--end::Section 4-->

                    <div class="separator separator-dashed my-8"></div>

                    <!--begin::Section 5-->
                    <div class="mb-10">
                        <h3 class="fw-bold text-body-emphasis mb-4">5. Önbellek</h3>
                        <p class="fs-5 text-body mb-5">
                            Derlenmiş dosyalar <code>files/cache/views/</code> altında tutulur. Dosya adı okunabilir bir slug ve yol özetinden oluşur (<code>view_kullanici_liste_php-3f2a1b9c.php</code>) — hata izinde hangi şablon olduğu anlaşılır.
                        </p>

                        <div class="table-responsive border rounded bg-body-tertiary mb-6">
                            <table class="table align-middle table-row-dashed fs-6 gy-4 mb-0">
                                <thead>
                                <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0 border-bottom">
                                    <th class="ps-5 min-w-200px">Konu</th>
                                    <th class="min-w-400px">Davranış</th>
                                </tr>
                                </thead>
                                <tbody class="text-body fw-semibold">
                                <tr>
                                    <td class="ps-5">Tazeleme</td>
                                    <td>Kaynak dosyanın <code>mtime</code>'ı derlenmişten yeniyse <b>veya eşitse</b> yeniden derlenir. Eşitlik de derlemeyi tetikler: dosya sistemi çözünürlüğü 1 saniye olduğu için aynı saniyede yapılan değişiklik aksi hâlde bayat kalırdı.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5">Sürüm kontrolü</td>
                                    <td><code>files/cache/</code> <code>.gitignore</code>'dadır. Derlenmiş çıktı commit edilmez, sunucuda kendiliğinden oluşur.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5">Yazma güvenliği</td>
                                    <td>Önce geçici dosyaya yazılıp <code>rename</code> edilir; yarım yazılmış bir dosya hiçbir zaman <code>require</code> edilmez.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5">Dizin izni</td>
                                    <td>Dizin oluşturulamaz veya yazılamazsa açık bir <code>RuntimeException</code> fırlatılır. <b>Web sunucusu kullanıcısının <code>files/</code> altına yazma izni olmalıdır.</b></td>
                                </tr>
                                <tr>
                                    <td class="ps-5">Toplu temizlik</td>
                                    <td><code>VnlTemplate::clearCache()</code> tüm derlenmiş dosyaları siler ve silinen sayısını döndürür. Dağıtım sonrası çalıştırın.</td>
                                </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="vnl-code">
                            <pre class="mb-0" style="background: transparent; white-space: pre-wrap;"><code>&lt;?php
// Dağıtım sonrası önbelleği temizleyen küçük bir action
use ActionGlobal\Auth;

class action_bakim
{
    public function sablonOnbelleginiTemizle($req)
    {
        Auth::CheckLogin();
        Auth::HasPermis([1]);          // yalnızca yönetici

        $silinen = VnlTemplate::clearCache();

        returnSuccessMessage("Şablon önbelleği temizlendi.", ['silinen' => $silinen]);
    }
}
</code></pre>
                        </div>
                    </div>
                    <!--end::Section 5-->

                    <div class="separator separator-dashed my-8"></div>

                    <!--begin::Section 6-->
                    <div class="mb-0">
                        <h3 class="fw-bold text-body-emphasis mb-4">6. Ne Zaman Hangisi?</h3>

                        <div class="row g-5">
                            <div class="col-md-4">
                                <div class="border border-dashed rounded p-5 h-100">
                                    <div class="d-flex align-items-center mb-3">
                                        <i class="fa-solid fa-circle-check fs-2 text-success me-3"></i>
                                        <h6 class="mb-0 fw-bold">Varsayılan</h6>
                                    </div>
                                    <code class="d-block mb-2">&#123;&#123; $veri }}</code>
                                    <span class="text-muted fs-7">Kullanıcıdan veya veritabanından gelen her değer. Düşünmeden bunu kullanın.</span>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="border border-dashed rounded p-5 h-100">
                                    <div class="d-flex align-items-center mb-3">
                                        <i class="fa-solid fa-triangle-exclamation fs-2 text-warning me-3"></i>
                                        <h6 class="mb-0 fw-bold">Bilinçli tercih</h6>
                                    </div>
                                    <code class="d-block mb-2">&#123;!! $html !!}</code>
                                    <span class="text-muted fs-7">HTML'i siz ürettiyseniz (kod içinde oluşturulan rozet, ikon) ya da kaydederken temizlediyseniz.</span>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="border border-dashed rounded p-5 h-100">
                                    <div class="d-flex align-items-center mb-3">
                                        <i class="fa-solid fa-code fs-2 text-primary me-3"></i>
                                        <h6 class="mb-0 fw-bold">Düz PHP</h6>
                                    </div>
                                    <code class="d-block mb-2">&lt;?= vnl_e($veri) ?&gt;</code>
                                    <span class="text-muted fs-7">Şablon söz diziminin kapsamadığı durumlar: <code>for</code>, <code>while</code>, <code>switch</code>, karmaşık hesaplama.</span>
                                </div>
                            </div>
                        </div>

                        <div class="alert alert-danger d-flex align-items-center p-5 mt-6 mb-0">
                            <i class="fa-solid fa-circle-xmark fs-2hx text-danger me-4"></i>
                            <div class="d-flex flex-column">
                                <h5 class="mb-1 text-danger">Kaçışsız çıktı bir XSS kapısıdır</h5>
                                <span class="text-body"><code>&#123;!! !!}</code> ve <code>&lt;?= $veri ?&gt;</code> aynı şeydir: veri ne ise o basılır. Kullanıcının yazdığı bir metni bu şekilde basarsanız <code>&lt;script&gt;</code> etiketi çalışır. VNL 3.0 girişte kaçışlamayı bıraktığı için <b>bu sorumluluk tamamen view katmanındadır</b>.</span>
                            </div>
                        </div>
                    </div>
                    <!--end::Section 6-->

                </div>
            </div>

        </div>
    </div>
</div>

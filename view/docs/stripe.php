<div class="d-flex flex-column flex-column-fluid">
    <div id="kt_app_content" class="app-content flex-column-fluid">
        <div id="kt_app_content_container" class="app-container container-xxl">

            <div class="vnl-page-hero mb-12 mt-6">
                <div class="vnl-overline mb-3">// docs/stripe</div>
                <h1 class="fs-2hx fw-bold mb-4 text-body-emphasis">Stripe Extension</h1>
                <div class="vnl-lead">VNL için Stripe ödeme eklentisi. Tek seferlik ödeme (<code>one-time payment</code>) ve
                    tekrarlayan abonelik (<code>subscription</code>) modellerini destekler.
                    Tüm dosyalar <code>extensions/stripe/</code> altında bağımsız yaşar.</div>
            </div>

            <!--begin::Kurulum-->
            <div class="card shadow-sm border-0 mb-10">
                <div class="card-header border-0 pt-8">
                    <h2 class="card-title fw-bold text-body-emphasis">Kurulum</h2>
                </div>
                <div class="card-body pt-2">
                    <div class="d-flex flex-column gap-5 mb-8">
                        <div class="d-flex align-items-start">
                            <span class="badge badge-circle badge-primary me-4 mt-1 fs-6 flex-shrink-0">1</span>
                            <div>
                                <div class="fw-bold text-body mb-1">Stripe PHP SDK'yı yükle</div>
                                <div class="vnl-code mt-2">
                                    <code class="text-warning">cd libs &amp;&amp; composer require stripe/stripe-php</code>
                                </div>
                            </div>
                        </div>
                        <div class="d-flex align-items-start">
                            <span class="badge badge-circle badge-primary me-4 mt-1 fs-6 flex-shrink-0">2</span>
                            <div>
                                <div class="fw-bold text-body mb-1">Veritabanı tablolarını oluştur</div>
                                <div class="text-body-secondary fs-6 mt-1"><code>extensions/stripe/setup.sql</code> dosyasını veritabanında çalıştır.</div>
                            </div>
                        </div>
                        <div class="d-flex align-items-start">
                            <span class="badge badge-circle badge-primary me-4 mt-1 fs-6 flex-shrink-0">3</span>
                            <div>
                                <div class="fw-bold text-body mb-1">Config'i düzenle</div>
                                <div class="text-body-secondary fs-6 mt-1"><code>extensions/stripe/config.php</code> dosyasına Stripe key'lerini ve planları gir.</div>
                            </div>
                        </div>
                        <div class="d-flex align-items-start">
                            <span class="badge badge-circle badge-primary me-4 mt-1 fs-6 flex-shrink-0">4</span>
                            <div>
                                <div class="fw-bold text-body mb-1">Webhook endpoint'ini kaydet</div>
                                <div class="text-body-secondary fs-6 mt-1">
                                    Stripe Dashboard → Developers → Webhooks → Add endpoint:
                                    <code>https://siteniz.com/stripe-webhook</code>
                                </div>
                                <div class="text-body-secondary fs-6 mt-1">
                                    Local test için: <code class="text-warning">stripe listen --forward-to https://siteniz.com/stripe-webhook</code>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!--end::Kurulum-->

            <!--begin::Config-->
            <div class="card shadow-sm border-0 mb-10">
                <div class="card-header border-0 pt-8">
                    <h2 class="card-title fw-bold text-body-emphasis">Config (<code>extensions/stripe/config.php</code>)</h2>
                </div>
                <div class="card-body pt-2">
                    <div class="table-responsive border rounded bg-body-tertiary mb-6">
                        <table class="table align-middle table-row-dashed fs-6 gy-3 mb-0">
                            <thead>
                                <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0 border-bottom">
                                    <th class="ps-5 min-w-200px">Alan</th>
                                    <th>Açıklama</th>
                                </tr>
                            </thead>
                            <tbody class="text-body fw-semibold">
                                <tr><td class="ps-5"><code>test_mode</code></td><td><code>true</code> → test key'ler, <code>false</code> → live key'ler kullanılır</td></tr>
                                <tr><td class="ps-5"><code>test_secret_key</code></td><td>Stripe test gizli anahtarı (<code>sk_test_...</code>)</td></tr>
                                <tr><td class="ps-5"><code>test_public_key</code></td><td>Stripe test yayın anahtarı (<code>pk_test_...</code>)</td></tr>
                                <tr><td class="ps-5"><code>test_webhook_secret</code></td><td>Stripe test webhook imza anahtarı (<code>whsec_...</code>)</td></tr>
                                <tr><td class="ps-5"><code>live_*</code></td><td>Canlı ortam karşılıkları</td></tr>
                                <tr><td class="ps-5"><code>plans</code></td><td>Pricing sayfasında gösterilecek plan dizisi</td></tr>
                            </tbody>
                        </table>
                    </div>

                    <p class="fw-bold text-body mb-3">Plan Tanımı</p>
                    <div class="vnl-code mb-5">
                        <pre class="mb-0" style="background:transparent; white-space:pre-wrap;"><code>'plans' => [
    [
        'id'          => 'pro',
        'name'        => 'Pro',
        'price'       => '₺299',
        'period'      => '/ay',
        'price_id'    => 'price_xxx',    // Stripe Dashboard > Products > Price ID
        'mode'        => 'subscription', // 'subscription' | 'payment'
        'description' => 'Büyüyen ekipler için',
        'features'    => ['Sınırsız Proje', 'API Erişimi'],
        'popular'     => true,
        'btn_text'    => 'Pro\'ya Geç',
        'btn_class'   => 'btn-primary',
    ],
],
</code></pre>
                    </div>
                </div>
            </div>
            <!--end::Config-->

            <!--begin::Dosya Yapısı-->
            <div class="card shadow-sm border-0 mb-10">
                <div class="card-header border-0 pt-8">
                    <h2 class="card-title fw-bold text-body-emphasis">Dosya Yapısı</h2>
                </div>
                <div class="card-body pt-2">
                    <div class="vnl-code mb-5">
                        <pre class="mb-0" style="background:transparent; white-space:pre-wrap;"><code>extensions/stripe/
├── boot.php                    ← Otomatik yüklenir. SDK kontrolü, config, sabit tanımları.
├── config.php                  ← API key'leri ve plan tanımları
├── setup.sql                   ← DB kurulum scripti
│
├── src/
│   ├── StripeModule.php        ← Tüm Stripe SDK işlemleri (statik metodlar)
│   ├── StripePayment.php       ← Eloquent model — tbl_stripe_payments
│   └── StripeSubscription.php  ← Eloquent model — tbl_stripe_subscriptions
│
├── actions/
│   └── action_stripe.php       ← /app/stripe/* AJAX endpoint'leri
│
├── controller/
│   ├── stripe-test.php         ← /stripe-test  (pricing sayfası)
│   ├── stripe-success.php      ← /stripe-success
│   ├── stripe-cancel.php       ← /stripe-cancel
│   ├── stripe-dashboard.php    ← /stripe-dashboard
│   └── stripe-webhook.php      ← /stripe-webhook (Stripe'dan gelen event'ler)
│
└── view/
    ├── stripe-test.php
    ├── stripe-success.php
    ├── stripe-cancel.php
    └── stripe-dashboard.php
</code></pre>
                    </div>
                </div>
            </div>
            <!--end::Dosya Yapısı-->

            <!--begin::Sabit Değerler-->
            <div class="card shadow-sm border-0 mb-10">
                <div class="card-header border-0 pt-8">
                    <h2 class="card-title fw-bold text-body-emphasis">Sabit Değerler (Constants)</h2>
                </div>
                <div class="card-body pt-2">
                    <p class="fs-5 text-body mb-5">
                        <code>boot.php</code> yüklendiğinde aşağıdaki sabitler global olarak tanımlanır:
                    </p>
                    <div class="table-responsive border rounded bg-body-tertiary">
                        <table class="table align-middle table-row-dashed fs-6 gy-3 mb-0">
                            <thead>
                                <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0 border-bottom">
                                    <th class="ps-5">Sabit</th>
                                    <th>Açıklama</th>
                                </tr>
                            </thead>
                            <tbody class="text-body fw-semibold">
                                <tr><td class="ps-5"><code>STRIPE_EXT_PATH</code></td><td>Extension klasörünün mutlak yolu</td></tr>
                                <tr><td class="ps-5"><code>STRIPE_SECRET_KEY</code></td><td>Aktif ortama göre secret key</td></tr>
                                <tr><td class="ps-5"><code>STRIPE_PUBLIC_KEY</code></td><td>Aktif ortama göre publishable key</td></tr>
                                <tr><td class="ps-5"><code>STRIPE_WEBHOOK_SECRET</code></td><td>Webhook imza doğrulama anahtarı</td></tr>
                                <tr><td class="ps-5"><code>STRIPE_TEST_MODE</code></td><td><code>bool</code> — test modunda mı?</td></tr>
                                <tr><td class="ps-5"><code>$GLOBALS['stripe_plans']</code></td><td>config.php'deki plan dizisi</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <!--end::Sabit Değerler-->

            <!--begin::StripeModule-->
            <div class="card shadow-sm border-0 mb-10">
                <div class="card-header border-0 pt-8">
                    <h2 class="card-title fw-bold text-body-emphasis">StripeModule — Metodlar</h2>
                </div>
                <div class="card-body pt-2">
                    <div class="table-responsive border rounded bg-body-tertiary mb-8">
                        <table class="table align-middle table-row-dashed fs-6 gy-3 mb-0">
                            <thead>
                                <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0 border-bottom">
                                    <th class="ps-5 min-w-350px">Metot</th>
                                    <th>Açıklama</th>
                                </tr>
                            </thead>
                            <tbody class="text-body fw-semibold">
                                <tr><td class="ps-5"><code>getOrCreateCustomer($userId, $email, $name)</code></td><td>DB'ye bakarak mevcut Stripe Customer'ı döndürür, yoksa oluşturur.</td></tr>
                                <tr><td class="ps-5"><code>findCustomerId($userId)</code></td><td>DB'de kayıtlı Customer ID'yi arar (abonelik veya ödeme tablosundan).</td></tr>
                                <tr><td class="ps-5"><code>createCheckoutSession($params)</code></td><td>Checkout oturumu oluşturur. <code>mode: 'subscription'|'payment'</code> destekler.</td></tr>
                                <tr><td class="ps-5"><code>createBillingPortalSession($customerId, $returnUrl)</code></td><td>Stripe Müşteri Portalı bağlantısı oluşturur.</td></tr>
                                <tr><td class="ps-5"><code>cancelSubscription($subscriptionId, $immediately)</code></td><td>Aboneliği iptal eder. <code>$immediately=false</code> → dönem sonunda iptal.</td></tr>
                                <tr><td class="ps-5"><code>resumeSubscription($subscriptionId)</code></td><td>Dönem sonu iptalini geri alır.</td></tr>
                                <tr><td class="ps-5"><code>getSubscription($subscriptionId)</code></td><td>Abonelik detaylarını Stripe'dan çeker.</td></tr>
                                <tr><td class="ps-5"><code>constructWebhookEvent($payload, $sigHeader)</code></td><td>Webhook imzasını doğrular ve Stripe Event nesnesi döndürür.</td></tr>
                            </tbody>
                        </table>
                    </div>

                    <p class="fw-bold text-body mb-3">Örnek Kullanım</p>
                    <div class="vnl-code">
                        <pre class="mb-0" style="background:transparent; white-space:pre-wrap;"><code>// Checkout başlat (subscription)
$customerId = StripeModule::getOrCreateCustomer($user->Id, $user->Email, $user->fullname());
$session    = StripeModule::createCheckoutSession([
    'mode'        => 'subscription',
    'price_id'    => 'price_xxx',
    'customer_id' => $customerId,
    'success_url' => SITE_URL . '/stripe-success',
    'cancel_url'  => SITE_URL . '/stripe-test',
    'metadata'    => ['user_id' => $user->Id],
]);
header('Location: ' . $session->url);

// Checkout başlat (tek seferlik ödeme)
$session = StripeModule::createCheckoutSession([
    'mode'     => 'payment',
    'price_id' => 'price_xxx',
    // ...
]);
</code></pre>
                    </div>
                </div>
            </div>
            <!--end::StripeModule-->

            <!--begin::AJAX Endpoints-->
            <div class="card shadow-sm border-0 mb-10">
                <div class="card-header border-0 pt-8">
                    <h2 class="card-title fw-bold text-body-emphasis">AJAX Endpoint'leri (<code>/app/stripe/*</code>)</h2>
                </div>
                <div class="card-body pt-2">
                    <div class="table-responsive border rounded bg-body-tertiary mb-8">
                        <table class="table align-middle table-row-dashed fs-6 gy-3 mb-0">
                            <thead>
                                <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0 border-bottom">
                                    <th class="ps-5 min-w-300px">Endpoint</th>
                                    <th class="min-w-200px">POST Parametreleri</th>
                                    <th>Açıklama</th>
                                </tr>
                            </thead>
                            <tbody class="text-body fw-semibold">
                                <tr>
                                    <td class="ps-5"><code>POST /app/stripe/createCheckoutSession</code></td>
                                    <td><code>mode, price_id, success_url, cancel_url</code></td>
                                    <td>Stripe Checkout oturumu başlatır, <code>data.url</code> döner.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>POST /app/stripe/cancelSubscription</code></td>
                                    <td><code>immediately</code> (0/1, opsiyonel)</td>
                                    <td>Aktif aboneliği iptal eder.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>POST /app/stripe/resumeSubscription</code></td>
                                    <td>—</td>
                                    <td>Dönem sonu iptalini geri alır.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>POST /app/stripe/getBillingPortal</code></td>
                                    <td><code>return_url</code> (opsiyonel)</td>
                                    <td>Stripe Müşteri Portalı URL'i döner.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>POST /app/stripe/getSubscriptionStatus</code></td>
                                    <td>—</td>
                                    <td>Kullanıcının abonelik durumunu döner.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>POST /app/stripe/getPayments</code></td>
                                    <td>—</td>
                                    <td>Kullanıcının ödeme geçmişini listeler.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="vnl-code">
                        <pre class="mb-0" style="background:transparent; white-space:pre-wrap;"><code>// JavaScript örneği
$.post('/app/stripe/createCheckoutSession', {
    mode:        'subscription',
    price_id:    'price_xxx',
    success_url: window.location.origin + '/stripe-success',
    cancel_url:  window.location.origin + '/stripe-test',
}, function (res) {
    if (res.url) window.location.href = res.url;
});
</code></pre>
                    </div>
                </div>
            </div>
            <!--end::AJAX Endpoints-->

            <!--begin::Webhook-->
            <div class="card shadow-sm border-0 mb-10">
                <div class="card-header border-0 pt-8">
                    <h2 class="card-title fw-bold text-body-emphasis">Webhook (<code>/stripe-webhook</code>)</h2>
                </div>
                <div class="card-body pt-2">
                    <p class="fs-5 text-body mb-5">
                        <code>$page_config['public'] = true</code> sayesinde auth bypass otomatiktir.
                        Stripe imzası <code>STRIPE_WEBHOOK_SECRET</code> ile doğrulanır.
                    </p>

                    <div class="table-responsive border rounded bg-body-tertiary mb-8">
                        <table class="table align-middle table-row-dashed fs-6 gy-3 mb-0">
                            <thead>
                                <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0 border-bottom">
                                    <th class="ps-5 min-w-350px">Stripe Event</th>
                                    <th>Tetiklenen İşlem</th>
                                </tr>
                            </thead>
                            <tbody class="text-body fw-semibold">
                                <tr><td class="ps-5"><code>checkout.session.completed</code></td><td>Ödeme veya abonelik kaydını oluşturur</td></tr>
                                <tr><td class="ps-5"><code>invoice.payment_succeeded</code></td><td>Yenileme ödemesini kaydeder, abonelik periyodunu günceller</td></tr>
                                <tr><td class="ps-5"><code>invoice.payment_failed</code></td><td>Abonelik durumunu <code>past_due</code> yapar</td></tr>
                                <tr><td class="ps-5"><code>customer.subscription.updated</code></td><td>Abonelik kaydını senkronize eder</td></tr>
                                <tr><td class="ps-5"><code>customer.subscription.deleted</code></td><td>Abonelik durumunu <code>canceled</code> yapar</td></tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="alert alert-info d-flex align-items-center p-5">
                        <i class="fa-solid fa-circle-info fs-2hx text-info me-4"></i>
                        <div>
                            <strong>Local test:</strong>
                            <code class="ms-1">stripe listen --forward-to https://siteniz.com/stripe-webhook</code>
                            komutu çıktısındaki <code>whsec_...</code> değerini <code>config.php → test_webhook_secret</code> alanına yapıştırın.
                        </div>
                    </div>
                </div>
            </div>
            <!--end::Webhook-->

            <!--begin::Test Sayfaları-->
            <div class="card shadow-sm border-0 mb-10">
                <div class="card-header border-0 pt-8">
                    <h2 class="card-title fw-bold text-body-emphasis">Test Sayfaları</h2>
                </div>
                <div class="card-body pt-2">
                    <div class="table-responsive border rounded bg-body-tertiary mb-6">
                        <table class="table align-middle table-row-dashed fs-6 gy-3 mb-0">
                            <thead>
                                <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0 border-bottom">
                                    <th class="ps-5 min-w-200px">URL</th>
                                    <th>Açıklama</th>
                                </tr>
                            </thead>
                            <tbody class="text-body fw-semibold">
                                <tr><td class="ps-5"><a href="/stripe-test" class="link-primary">/stripe-test</a></td><td>Pricing sayfası. <code>config.php → plans</code> dizisinden otomatik render edilir.</td></tr>
                                <tr><td class="ps-5"><a href="/stripe-success" class="link-primary">/stripe-success</a></td><td>Stripe checkout başarıyla tamamlandığında yönlendirilen sayfa.</td></tr>
                                <tr><td class="ps-5"><a href="/stripe-cancel" class="link-primary">/stripe-cancel</a></td><td>Kullanıcı checkout'tan vazgeçtiğinde yönlendirilen sayfa.</td></tr>
                                <tr><td class="ps-5"><a href="/stripe-dashboard" class="link-primary">/stripe-dashboard</a></td><td>Abonelik durumu, ödeme geçmişi, iptal/sürdür, Stripe Portalı.</td></tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="alert alert-warning d-flex align-items-center p-5">
                        <i class="fa-solid fa-circle-info fs-2hx text-warning me-4"></i>
                        <div>
                            Test modunda <code>4242 4242 4242 4242</code> kartını kullanın.
                            Tarih: herhangi bir gelecek tarih. CVV: herhangi 3 hane.
                        </div>
                    </div>
                </div>
            </div>
            <!--end::Test Sayfaları-->

            <!--begin::DB Tabloları-->
            <div class="card shadow-sm border-0 mb-10">
                <div class="card-header border-0 pt-8">
                    <h2 class="card-title fw-bold text-body-emphasis">Veritabanı Tabloları</h2>
                </div>
                <div class="card-body pt-2">

                    <p class="fw-bold text-body mb-3">tbl_stripe_payments</p>
                    <div class="table-responsive border rounded bg-body-tertiary mb-8">
                        <table class="table align-middle table-row-dashed fs-7 gy-2 mb-0">
                            <thead>
                                <tr class="text-start text-muted fw-bold text-uppercase gs-0 border-bottom">
                                    <th class="ps-5">Alan</th><th>Tip</th><th>Açıklama</th>
                                </tr>
                            </thead>
                            <tbody class="fw-semibold text-body">
                                <tr><td class="ps-5"><code>UserId</code></td><td>INT</td><td>Kullanıcı ID</td></tr>
                                <tr><td class="ps-5"><code>StripeCustomerId</code></td><td>VARCHAR</td><td>Stripe Customer ID (<code>cus_...</code>)</td></tr>
                                <tr><td class="ps-5"><code>StripeSessionId</code></td><td>VARCHAR</td><td>Checkout Session ID (<code>cs_...</code>)</td></tr>
                                <tr><td class="ps-5"><code>StripePaymentIntentId</code></td><td>VARCHAR</td><td>Payment Intent ID (<code>pi_...</code>)</td></tr>
                                <tr><td class="ps-5"><code>Amount</code></td><td>INT</td><td>Tutar (kuruş cinsinden, örn: 9900 = 99.00 TL)</td></tr>
                                <tr><td class="ps-5"><code>Currency</code></td><td>VARCHAR</td><td>Para birimi (TRY, USD vb.)</td></tr>
                                <tr><td class="ps-5"><code>Status</code></td><td>VARCHAR</td><td><code>pending</code> / <code>paid</code> / <code>failed</code> / <code>refunded</code></td></tr>
                            </tbody>
                        </table>
                    </div>

                    <p class="fw-bold text-body mb-3">tbl_stripe_subscriptions</p>
                    <div class="table-responsive border rounded bg-body-tertiary">
                        <table class="table align-middle table-row-dashed fs-7 gy-2 mb-0">
                            <thead>
                                <tr class="text-start text-muted fw-bold text-uppercase gs-0 border-bottom">
                                    <th class="ps-5">Alan</th><th>Tip</th><th>Açıklama</th>
                                </tr>
                            </thead>
                            <tbody class="fw-semibold text-body">
                                <tr><td class="ps-5"><code>UserId</code></td><td>INT</td><td>Kullanıcı ID</td></tr>
                                <tr><td class="ps-5"><code>StripeCustomerId</code></td><td>VARCHAR</td><td>Stripe Customer ID</td></tr>
                                <tr><td class="ps-5"><code>StripeSubscriptionId</code></td><td>VARCHAR</td><td>Abonelik ID (<code>sub_...</code>)</td></tr>
                                <tr><td class="ps-5"><code>StripePriceId</code></td><td>VARCHAR</td><td>Price ID (<code>price_...</code>)</td></tr>
                                <tr><td class="ps-5"><code>PlanName</code></td><td>VARCHAR</td><td>Stripe'daki ürün adı</td></tr>
                                <tr><td class="ps-5"><code>Status</code></td><td>VARCHAR</td><td><code>active</code> / <code>trialing</code> / <code>past_due</code> / <code>canceled</code> vb.</td></tr>
                                <tr><td class="ps-5"><code>CurrentPeriodEnd</code></td><td>DATETIME</td><td>Mevcut ödeme döneminin bitiş tarihi</td></tr>
                                <tr><td class="ps-5"><code>CancelAtPeriodEnd</code></td><td>TINYINT</td><td><code>1</code> → dönem sonunda iptal edilecek</td></tr>
                            </tbody>
                        </table>
                    </div>

                </div>
            </div>
            <!--end::DB Tabloları-->

        </div>
    </div>
</div>

<?php
/**
 * Stripe Test — Pricing Page
 * URL: /stripe-test
 *
 * config.php'deki 'plans' dizisinden planları okur.
 * price_id alanlarını kendi Stripe Price ID'lerinizle değiştirin.
 */
$plans       = $GLOBALS['stripe_plans'] ?? [];
$isTestMode  = defined('STRIPE_TEST_MODE') && STRIPE_TEST_MODE;
$successUrl  = SITE_URL . '/stripe-success';
$cancelUrl   = SITE_URL . '/stripe-test';
?>

<div class="d-flex flex-column flex-column-fluid">
    <div id="kt_app_content" class="app-content flex-column-fluid">
        <div id="kt_app_content_container" class="app-container container-xxl">

            <?php if ($isTestMode): ?>
            <!--begin::Test Mode Banner-->
            <div class="notice d-flex bg-warning-subtle rounded border-warning border border-dashed mb-9 p-6">
                <i class="fa-solid fa-circle-info fs-2tx text-warning me-4">
                </i>
                <div class="d-flex flex-stack flex-grow-1">
                    <div class="fw-semibold">
                        <h4 class="text-body-emphasis fw-bold">Test Modu Aktif</h4>
                        <div class="fs-6 text-body">
                            Gerçek ödeme alınmaz. Test kartı: <code>4242 4242 4242 4242</code> — Tarih: herhangi gelecek tarih — CVV: herhangi 3 hane.
                        </div>
                    </div>
                </div>
            </div>
            <!--end::Test Mode Banner-->
            <?php endif; ?>

            <!--begin::Heading-->
            <div class="text-center mb-17">
                <h1 class="fs-2hx fw-bold text-body-emphasis mb-5">Planınızı Seçin</h1>
                <div class="fs-5 text-muted fw-semibold">
                    İhtiyacınıza en uygun planı seçin. İstediğiniz zaman değiştirebilirsiniz.
                </div>
            </div>
            <!--end::Heading-->

            <!--begin::Pricing Cards-->
            <div class="row g-10 justify-content-center mb-10">
                <?php foreach ($plans as $plan): ?>
                <div class="col-xl-4">
                    <div class="d-flex h-100 align-items-center">
                        <div class="w-100 d-flex flex-column flex-center rounded-3 bg-body py-15 px-10 <?= $plan['popular'] ? 'border border-2 border-primary shadow' : '' ?>">
                            <?php if ($plan['popular']): ?>
                            <div class="mb-7 text-center">
                                <span class="badge badge-light-primary py-2 px-4 fs-7 fw-semibold">En Popüler</span>
                            </div>
                            <?php endif; ?>

                            <div class="text-center mb-7">
                                <h1 class="text-body-emphasis fw-bold mb-3 fs-2"><?= htmlspecialchars($plan['name']) ?></h1>
                                <div class="text-body-tertiary fw-semibold mb-5">
                                    <?= htmlspecialchars($plan['description']) ?>
                                </div>
                                <div class="d-flex align-items-center justify-content-center">
                                    <span class="fs-3x fw-bold text-body-emphasis"><?= htmlspecialchars($plan['price']) ?></span>
                                    <span class="text-muted fs-7 fw-semibold ms-1"><?= htmlspecialchars($plan['period']) ?></span>
                                </div>
                            </div>

                            <div class="w-100 mb-10">
                                <?php foreach ($plan['features'] as $feature): ?>
                                <div class="d-flex align-items-center mb-3">
                                    <span class="fw-semibold fs-6 text-body flex-grow-1 pe-3">
                                        <?= htmlspecialchars($feature) ?>
                                    </span>
                                    <i class="fa-solid fa-circle-check fs-1 text-success">
                                    </i>
                                </div>
                                <?php endforeach; ?>
                            </div>

                            <button
                                class="btn <?= htmlspecialchars($plan['btn_class']) ?> w-100 btn-checkout"
                                data-price-id="<?= htmlspecialchars($plan['price_id']) ?>"
                                data-mode="<?= htmlspecialchars($plan['mode']) ?>"
                                data-plan="<?= htmlspecialchars($plan['name']) ?>"
                                data-success-url="<?= htmlspecialchars($successUrl) ?>"
                                data-cancel-url="<?= htmlspecialchars($cancelUrl) ?>"
                            >
                                <?= htmlspecialchars($plan['btn_text']) ?>
                            </button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <!--end::Pricing Cards-->

            <!--begin::FAQ-->
            <div class="card mt-10">
                <div class="card-header border-0">
                    <div class="card-title">
                        <h3 class="fw-bold text-body-emphasis">Sık Sorulan Sorular</h3>
                    </div>
                </div>
                <div class="card-body pt-2">
                    <div class="row g-10">
                        <div class="col-md-6">
                            <div class="d-flex mb-7">
                                <i class="fa-solid fa-circle-question fs-2 text-primary me-5 mt-1">
                                </i>
                                <div>
                                    <div class="fw-bold text-body mb-1">Aboneliğimi iptal edebilir miyim?</div>
                                    <div class="text-body-secondary fs-7">Evet, istediğiniz zaman iptal edebilirsiniz. Mevcut dönem sonuna kadar erişiminiz devam eder.</div>
                                </div>
                            </div>
                            <div class="d-flex mb-7">
                                <i class="fa-solid fa-circle-question fs-2 text-primary me-5 mt-1">
                                </i>
                                <div>
                                    <div class="fw-bold text-body mb-1">Hangi ödeme yöntemlerini kabul ediyorsunuz?</div>
                                    <div class="text-body-secondary fs-7">Visa, Mastercard, American Express ve diğer tüm Stripe destekli kartlar.</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex mb-7">
                                <i class="fa-solid fa-circle-question fs-2 text-primary me-5 mt-1">
                                </i>
                                <div>
                                    <div class="fw-bold text-body mb-1">Plan değiştirebilir miyim?</div>
                                    <div class="text-body-secondary fs-7">Evet, istediğiniz zaman plan yükseltebilir veya düşürebilirsiniz.</div>
                                </div>
                            </div>
                            <div class="d-flex mb-7">
                                <i class="fa-solid fa-circle-question fs-2 text-primary me-5 mt-1">
                                </i>
                                <div>
                                    <div class="fw-bold text-body mb-1">Faturalarıma nasıl ulaşabilirim?</div>
                                    <div class="text-body-secondary fs-7"><a href="/stripe-dashboard" class="link-primary">Abonelik Yönetimi</a> sayfasından tüm geçmişinize ulaşabilirsiniz.</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!--end::FAQ-->

        </div>
    </div>
</div>

<script>
$(document).on('click', '.btn-checkout', function () {
    const btn       = $(this);
    const priceId   = btn.data('price-id');
    const mode      = btn.data('mode');
    const planName  = btn.data('plan');
    const successUrl = btn.data('success-url');
    const cancelUrl  = btn.data('cancel-url');

    if (!priceId || priceId === 'price_REPLACE_ME') {
        Swal.fire({
            icon: 'warning',
            title: 'Henüz Yapılandırılmadı',
            html: '<code>extensions/stripe/config.php</code> dosyasındaki <strong>price_id</strong> değerlerini Stripe\'dan alarak güncelleyin.',
        });
        return;
    }

    Swal.fire({
        icon: 'question',
        title: planName + ' Planı',
        text: 'Bu plana abone olmak istediğinizden emin misiniz?',
        showCancelButton: true,
        confirmButtonText: 'Devam Et',
        cancelButtonText: 'Vazgeç',
        confirmButtonColor: '#009ef7',
    }).then(result => {
        if (!result.isConfirmed) return;

        btn.prop('disabled', true).prepend('<span class="spinner-border spinner-border-sm me-2"></span>');

        $.post('/app/stripe/createCheckoutSession', {
            mode:        mode,
            price_id:    priceId,
            success_url: successUrl,
            cancel_url:  cancelUrl,
        })
        .done(function (res) {
            if (res.url) {
                window.location.href = res.url;
            } else {
                showMessage('Bir hata oluştu.', true);
                btn.prop('disabled', false).find('.spinner-border').remove();
            }
        })
        .fail(function (xhr) {
            const msg = xhr.responseJSON?.message ?? 'Bağlantı hatası.';
            showMessage(msg, true);
            btn.prop('disabled', false).find('.spinner-border').remove();
        });
    });
});
</script>

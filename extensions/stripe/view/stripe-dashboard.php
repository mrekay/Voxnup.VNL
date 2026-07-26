<?php
/**
 * Stripe Dashboard
 * URL: /stripe-dashboard
 * Kullanıcının abonelik durumu, ödeme geçmişi ve yönetim işlemleri.
 */
?>

<div class="d-flex flex-column flex-column-fluid">
    <div id="kt_app_content" class="app-content flex-column-fluid">
        <div id="kt_app_content_container" class="app-container container-xxl">

            <!--begin::Row-->
            <div class="row g-5 g-xl-10 mb-5">

                <!--begin::Subscription Status Card-->
                <div class="col-xl-6">
                    <div class="card h-100" id="card-subscription-status">
                        <div class="card-header border-0">
                            <h3 class="card-title fw-bold text-body-emphasis">Abonelik Durumu</h3>
                            <div class="card-toolbar">
                                <button type="button" class="btn btn-sm btn-soft-primary" id="btn-refresh-status">
                                    <i class="fa-solid fa-arrows-rotate fs-4">
                                    </i>
                                    Yenile
                                </button>
                            </div>
                        </div>
                        <div class="card-body pt-2" id="subscription-status-body">
                            <div class="d-flex align-items-center justify-content-center py-10">
                                <span class="spinner-border text-primary"></span>
                            </div>
                        </div>
                    </div>
                </div>
                <!--end::Subscription Status Card-->

                <!--begin::Quick Actions Card-->
                <div class="col-xl-6">
                    <div class="card h-100">
                        <div class="card-header border-0">
                            <h3 class="card-title fw-bold text-body-emphasis">Hızlı İşlemler</h3>
                        </div>
                        <div class="card-body pt-2">

                            <div class="d-flex flex-column gap-3">

                                <a href="/stripe-test" class="btn btn-soft-primary d-flex align-items-center justify-content-between p-5">
                                    <div class="d-flex align-items-center">
                                        <i class="fa-solid fa-tag fs-2 text-primary me-3">
                                        </i>
                                        <div class="text-start">
                                            <div class="fw-bold text-body">Plan Değiştir / Yükselt</div>
                                            <div class="fs-7 text-muted">Mevcut planları görüntüle</div>
                                        </div>
                                    </div>
                                    <i class="fa-solid fa-arrow-right fs-3 text-body-secondary">
                                    </i>
                                </a>

                                <button type="button" class="btn btn-soft-info d-flex align-items-center justify-content-between p-5" id="btn-billing-portal">
                                    <div class="d-flex align-items-center">
                                        <i class="fa-solid fa-credit-card fs-2 text-info me-3">
                                        </i>
                                        <div class="text-start">
                                            <div class="fw-bold text-body">Stripe Müşteri Portalı</div>
                                            <div class="fs-7 text-muted">Faturalar, kart bilgileri, iptal</div>
                                        </div>
                                    </div>
                                    <i class="fa-solid fa-arrow-right fs-3 text-body-secondary">
                                    </i>
                                </button>

                                <button type="button" class="btn btn-soft-danger d-flex align-items-center justify-content-between p-5" id="btn-cancel-subscription">
                                    <div class="d-flex align-items-center">
                                        <i class="fa-solid fa-circle-xmark fs-2 text-danger me-3">
                                        </i>
                                        <div class="text-start">
                                            <div class="fw-bold text-body">Aboneliği İptal Et</div>
                                            <div class="fs-7 text-muted">Dönem sonunda iptal edilir</div>
                                        </div>
                                    </div>
                                    <i class="fa-solid fa-arrow-right fs-3 text-body-secondary">
                                    </i>
                                </button>

                                <button type="button" class="btn btn-soft-success d-flex align-items-center justify-content-between p-5 d-none" id="btn-resume-subscription">
                                    <div class="d-flex align-items-center">
                                        <i class="fa-solid fa-circle-check fs-2 text-success me-3">
                                        </i>
                                        <div class="text-start">
                                            <div class="fw-bold text-body">İptali Geri Al</div>
                                            <div class="fs-7 text-muted">Aboneliği sürdür</div>
                                        </div>
                                    </div>
                                    <i class="fa-solid fa-arrow-right fs-3 text-body-secondary">
                                    </i>
                                </button>

                            </div>

                        </div>
                    </div>
                </div>
                <!--end::Quick Actions Card-->

            </div>
            <!--end::Row-->

            <!--begin::Payment History-->
            <div class="card">
                <div class="card-header border-0">
                    <h3 class="card-title fw-bold text-body-emphasis">Ödeme Geçmişi</h3>
                </div>
                <div class="card-body pt-0">
                    <div class="table-responsive">
                        <table class="table table-row-dashed table-row-gray-300 align-middle gs-0 gy-4" id="payments-table">
                            <thead>
                                <tr class="fw-bold text-muted">
                                    <th>Tarih</th>
                                    <th>Tutar</th>
                                    <th>Para Birimi</th>
                                    <th>Açıklama</th>
                                    <th>Durum</th>
                                </tr>
                            </thead>
                            <tbody id="payments-tbody">
                                <tr>
                                    <td colspan="5" class="text-center py-10">
                                        <span class="spinner-border spinner-border-sm text-primary"></span>
                                        <span class="ms-2 text-muted">Yükleniyor...</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <!--end::Payment History-->

        </div>
    </div>
</div>

<script>
const STRIPE_DASHBOARD = {

    statusBadge: {
        active:              '<span class="badge badge-soft-success">Aktif</span>',
        trialing:            '<span class="badge badge-soft-info">Deneme</span>',
        past_due:            '<span class="badge badge-soft-danger">Gecikmiş Ödeme</span>',
        canceled:            '<span class="badge badge-soft-secondary">İptal Edildi</span>',
        unpaid:              '<span class="badge badge-soft-danger">Ödenmedi</span>',
        incomplete:          '<span class="badge badge-soft-warning">Tamamlanmadı</span>',
        incomplete_expired:  '<span class="badge badge-soft-secondary">Süresi Doldu</span>',
        paused:              '<span class="badge badge-soft-warning">Duraklatıldı</span>',
    },

    paymentBadge: {
        paid:     '<span class="badge badge-soft-success">Ödendi</span>',
        pending:  '<span class="badge badge-soft-warning">Bekliyor</span>',
        failed:   '<span class="badge badge-soft-danger">Başarısız</span>',
        refunded: '<span class="badge badge-soft-info">İade Edildi</span>',
    },

    loadStatus() {
        $.post('/app/stripe/getSubscriptionStatus')
        .done(res => {
            let html = '';

            if (!res.has_subscription) {
                html = `
                    <div class="text-center py-8">
                        <i class="fa-solid fa-circle-info fs-3x text-muted mb-5">
                        </i>
                        <div class="text-muted fs-6 mb-5">Henüz aktif bir aboneliğiniz yok.</div>
                        <a href="/stripe-test" class="btn btn-sm btn-primary">Plan Seç</a>
                    </div>`;
                $('#btn-cancel-subscription, #btn-resume-subscription').addClass('d-none');
            } else {
                const badge      = STRIPE_DASHBOARD.statusBadge[res.status] ?? `<span class="badge badge-soft-secondary">${res.status}</span>`;
                const periodEnd  = res.current_period_end ? new Date(res.current_period_end).toLocaleDateString('tr-TR') : '—';
                const cancelNote = res.is_canceling
                    ? `<div class="notice d-flex bg-warning-subtle rounded border-warning border border-dashed mt-4 p-4">
                           <i class="fa-solid fa-circle-info fs-2 text-warning me-3 mt-1"></i>
                           <div class="fs-7 text-body">Aboneliğiniz <strong>${periodEnd}</strong> tarihinde iptal edilecek.</div>
                       </div>`
                    : '';

                html = `
                    <div class="d-flex flex-column gap-4">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-body-secondary fw-semibold">Durum</span>
                            <span>${badge}</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-body-secondary fw-semibold">Plan</span>
                            <span class="fw-bold text-body">${res.plan || '—'}</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-body-secondary fw-semibold">Dönem Sonu</span>
                            <span class="fw-bold text-body">${periodEnd}</span>
                        </div>
                        ${cancelNote}
                    </div>`;

                // Butonları abonelik durumuna göre göster/gizle
                if (res.is_active) {
                    $('#btn-cancel-subscription').removeClass('d-none');
                    if (res.is_canceling) {
                        $('#btn-resume-subscription').removeClass('d-none');
                    }
                } else {
                    $('#btn-cancel-subscription').addClass('d-none');
                }
            }

            $('#subscription-status-body').html(html);
        })
        .fail(() => {
            $('#subscription-status-body').html('<div class="text-center text-danger py-8">Yüklenirken hata oluştu.</div>');
        });
    },

    loadPayments() {
        $.post('/app/stripe/getPayments')
        .done(res => {
            const payments = res.payments ?? [];
            if (payments.length === 0) {
                $('#payments-tbody').html('<tr><td colspan="5" class="text-center text-muted py-8">Henüz ödeme kaydı yok.</td></tr>');
                return;
            }

            let rows = '';
            payments.forEach(p => {
                const amount  = (p.Amount / 100).toFixed(2);
                const badge   = STRIPE_DASHBOARD.paymentBadge[p.Status] ?? `<span class="badge badge-soft-secondary">${p.Status}</span>`;
                const date    = p.InsertDate ? new Date(p.InsertDate).toLocaleDateString('tr-TR') : '—';
                const desc    = p.Description || '—';

                rows += `<tr>
                    <td class="fw-semibold text-body">${date}</td>
                    <td class="fw-bold text-body-emphasis">${amount}</td>
                    <td class="text-muted">${p.Currency}</td>
                    <td class="text-muted">${desc}</td>
                    <td>${badge}</td>
                </tr>`;
            });

            $('#payments-tbody').html(rows);
        })
        .fail(() => {
            $('#payments-tbody').html('<tr><td colspan="5" class="text-center text-danger py-8">Yüklenirken hata oluştu.</td></tr>');
        });
    },
};

// ─── Init ────────────────────────────────────────────────────────────────────

STRIPE_DASHBOARD.loadStatus();
STRIPE_DASHBOARD.loadPayments();

// ─── Events ──────────────────────────────────────────────────────────────────

$('#btn-refresh-status').on('click', function () {
    $('#subscription-status-body').html('<div class="d-flex align-items-center justify-content-center py-10"><span class="spinner-border text-primary"></span></div>');
    STRIPE_DASHBOARD.loadStatus();
});

$('#btn-billing-portal').on('click', function () {
    const btn = $(this);
    btn.prop('disabled', true).prepend('<span class="spinner-border spinner-border-sm me-2"></span>');

    $.post('/app/stripe/getBillingPortal', { return_url: window.location.href })
    .done(res => {
        if (res.url) {
            window.location.href = res.url;
        } else {
            showMessage('Portal URL alınamadı.', true);
        }
    })
    .fail(xhr => {
        showMessage(xhr.responseJSON?.message ?? 'Hata oluştu.', true);
    })
    .always(() => {
        btn.prop('disabled', false).find('.spinner-border').remove();
    });
});

$('#btn-cancel-subscription').on('click', function () {
    Swal.fire({
        icon: 'warning',
        title: 'Aboneliği İptal Et',
        html: 'Aboneliğiniz mevcut dönem sonunda iptal edilecektir. Dönem sonuna kadar erişiminiz devam eder.<br><br>Devam etmek istiyor musunuz?',
        showCancelButton: true,
        confirmButtonText: 'Evet, İptal Et',
        cancelButtonText: 'Vazgeç',
        confirmButtonColor: '#f1416c',
    }).then(result => {
        if (!result.isConfirmed) return;

        $.post('/app/stripe/cancelSubscription', { immediately: 0 })
        .done(res => {
            showMessage(res.message, false);
            setTimeout(() => STRIPE_DASHBOARD.loadStatus(), 800);
        })
        .fail(xhr => {
            showMessage(xhr.responseJSON?.message ?? 'Hata oluştu.', true);
        });
    });
});

$('#btn-resume-subscription').on('click', function () {
    Swal.fire({
        icon: 'question',
        title: 'Aboneliği Sürdür',
        text: 'Aboneliğinizin iptalini geri almak istediğinizden emin misiniz?',
        showCancelButton: true,
        confirmButtonText: 'Evet, Sürdür',
        cancelButtonText: 'Vazgeç',
        confirmButtonColor: '#50cd89',
    }).then(result => {
        if (!result.isConfirmed) return;

        $.post('/app/stripe/resumeSubscription')
        .done(res => {
            showMessage(res.message, false);
            $('#btn-resume-subscription').addClass('d-none');
            setTimeout(() => STRIPE_DASHBOARD.loadStatus(), 800);
        })
        .fail(xhr => {
            showMessage(xhr.responseJSON?.message ?? 'Hata oluştu.', true);
        });
    });
});
</script>

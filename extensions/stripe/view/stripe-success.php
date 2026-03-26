<?php
/**
 * Stripe Success Page
 * URL: /stripe-success
 * Stripe checkout tamamlandığında bu sayfaya yönlendirir.
 */
?>

<div class="d-flex flex-column flex-column-fluid">
    <div id="kt_app_content" class="app-content flex-column-fluid">
        <div id="kt_app_content_container" class="app-container container-xxl">

            <div class="card">
                <div class="card-body py-20">
                    <div class="text-center">

                        <!--begin::Icon-->
                        <div class="mb-10">
                            <i class="ki-duotone ki-check-circle text-success" style="font-size: 80px;">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>
                        </div>
                        <!--end::Icon-->

                        <!--begin::Heading-->
                        <h1 class="fw-bolder text-gray-900 mb-5">Ödeme Başarılı!</h1>
                        <div class="fs-5 text-muted fw-semibold mb-10">
                            Ödemeniz başarıyla alındı. Hesabınız aktive edildi.<br>
                            Teşekkür ederiz!
                        </div>
                        <!--end::Heading-->

                        <!--begin::Actions-->
                        <div class="d-flex justify-content-center gap-3 flex-wrap">
                            <a href="/stripe-dashboard" class="btn btn-primary">
                                <i class="ki-duotone ki-abstract-26 fs-2 me-1">
                                    <span class="path1"></span><span class="path2"></span>
                                </i>
                                Aboneliğimi Görüntüle
                            </a>
                            <a href="/" class="btn btn-light">
                                <i class="ki-duotone ki-home fs-2 me-1">
                                    <span class="path1"></span><span class="path2"></span>
                                </i>
                                Ana Sayfaya Dön
                            </a>
                        </div>
                        <!--end::Actions-->

                    </div>
                </div>
            </div>

            <!--begin::Info Note-->
            <div class="notice d-flex bg-light-info rounded border-info border border-dashed mt-8 p-6">
                <i class="ki-duotone ki-information fs-2tx text-info me-4">
                    <span class="path1"></span><span class="path2"></span><span class="path3"></span>
                </i>
                <div class="d-flex flex-stack flex-grow-1">
                    <div class="fw-semibold">
                        <div class="fs-6 text-gray-700">
                            Stripe webhook'u aktifse aboneliğiniz otomatik olarak kaydedilmiştir.
                            Webhook henüz yapılandırılmadıysa <a href="/stripe-dashboard" class="link-info fw-bold">Abonelik Yönetimi</a> sayfasında durumunuzu kontrol edin.
                        </div>
                    </div>
                </div>
            </div>
            <!--end::Info Note-->

        </div>
    </div>
</div>

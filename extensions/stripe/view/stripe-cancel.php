<?php
/**
 * Stripe Cancel Page
 * URL: /stripe-cancel
 * Kullanıcı Stripe checkout'tan vazgeçtiğinde bu sayfaya yönlendirir.
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
                            <i class="ki-duotone ki-cross-circle text-danger" style="font-size: 80px;">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>
                        </div>
                        <!--end::Icon-->

                        <!--begin::Heading-->
                        <h1 class="fw-bolder text-gray-900 mb-5">Ödeme İptal Edildi</h1>
                        <div class="fs-5 text-muted fw-semibold mb-10">
                            Ödeme işlemi iptal edildi. Herhangi bir ücret alınmadı.<br>
                            İstediğiniz zaman tekrar deneyebilirsiniz.
                        </div>
                        <!--end::Heading-->

                        <!--begin::Actions-->
                        <div class="d-flex justify-content-center gap-3 flex-wrap">
                            <a href="/stripe-test" class="btn btn-primary">
                                <i class="ki-duotone ki-price-tag fs-2 me-1">
                                    <span class="path1"></span><span class="path2"></span><span class="path3"></span>
                                </i>
                                Planlara Geri Dön
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

        </div>
    </div>
</div>

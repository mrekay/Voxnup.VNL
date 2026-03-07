<?php

if ($page_config['topbar'] != false){

    ?>
    <div id="kt_header" style="" class="header align-items-stretch">
        <!--begin::Container-->
        <div class="container-fluid d-flex align-items-stretch justify-content-between">
            <!--begin::Aside mobile toggle-->
            <div class="d-flex align-items-center d-lg-none ms-n4 me-1" title="Show aside menu">
                <div class="btn btn-icon btn-active-color-white" id="kt_aside_mobile_toggle">
                    <i class="ki-outline ki-burger-menu fs-1"></i>
                </div>
            </div>
            <!--end::Aside mobile toggle-->
            <!--begin::Mobile logo-->
            <div class="d-flex align-items-center flex-grow-1 flex-lg-grow-0">
                <a href="/" class="d-lg-none">
                    <img alt="Logo" src="<?=$cDefinitions->appLogo()?>" class="h-25px" />
                </a>
            </div>
            <!--end::Mobile logo-->
            <!--begin::Wrapper-->
            <div class="d-flex align-items-stretch justify-content-between flex-lg-grow-1">
                <!--begin::Navbar-->
                <div class="d-flex align-items-center" id="kt_header_nav">

                    <img alt="Logo" src="<?=$cDefinitions->appLogo()?>" class="logo" style="height: 32px" />
                    <span class="text-light text-bold" style="margin-left: 8px"><?=$cDefinitions->appName() . ' - ' . $cDefinitions->version()?></span>
                </div>
                <!--end::Navbar-->
                <!--begin::Toolbar wrapper-->
                <div class="topbar d-flex align-items-stretch flex-shrink-0">
                    <!--begin::Global Search-->
                    <div class="d-flex align-items-stretch" id="kt_global_search_wrapper">
                        <div id="kt_global_search" class="header-search d-flex align-items-stretch" data-kt-menu-trigger="manual" data-kt-menu-attach="parent" data-kt-menu-placement="bottom-end" data-kt-menu-permanent="true">
                            <!--begin::Search toggle-->
                            <div class="d-flex align-items-center" id="kt_global_search_toggle">
                                <div class="topbar-item px-3 px-lg-4 cursor-pointer">
                                    <i class="ki-outline ki-magnifier fs-1"></i>
                                </div>
                            </div>
                            <!--end::Search toggle-->
                            <!--begin::Menu-->
                            <div class="menu menu-sub menu-sub-dropdown p-7 w-350px w-md-450px" data-kt-menu="true" id="kt_global_search_menu" data-kt-menu-permanent="true">
                                <!--begin::Form-->
                                <form class="w-100 position-relative mb-3" autocomplete="off" onsubmit="return false;">
                                    <i class="ki-outline ki-magnifier fs-2 text-gray-500 position-absolute top-50 translate-middle-y ms-0"></i>
                                    <input type="text" class="form-control form-control-flush ps-10" id="global_search_input" placeholder="İş Emri veya Stok Kodu Ara..." />
                                    <span class="position-absolute top-50 end-0 translate-middle-y lh-0 d-none me-1" id="global_search_spinner">
                                        <span class="spinner-border h-15px w-15px align-middle text-gray-500"></span>
                                    </span>
                                    <span class="btn btn-flush btn-active-color-primary position-absolute top-50 end-0 translate-middle-y lh-0 d-none" id="global_search_clear">
                                        <i class="ki-outline ki-cross fs-2 fs-lg-1 me-0"></i>
                                    </span>
                                </form>
                                <!--end::Form-->
                                <div class="separator border-gray-200 mb-4"></div>
                                <!--begin::Results-->
                                <div id="global_search_results" class="scroll-y mh-350px">
                                    <!--begin::Initial Message-->
                                    <div id="global_search_initial" class="text-center py-5">
                                        <i class="ki-outline ki-search-list fs-3x text-gray-400 mb-3"></i>
                                        <div class="text-muted fs-7">En az 2 karakter yazın</div>
                                    </div>
                                    <!--end::Initial Message-->
                                    <!--begin::Empty-->
                                    <div id="global_search_empty" class="text-center py-5 d-none">
                                        <i class="ki-outline ki-search-list fs-3x text-gray-400 mb-3"></i>
                                        <div class="text-muted fs-7">Sonuç bulunamadı</div>
                                    </div>
                                    <!--end::Empty-->
                                    <!--begin::Work Orders Section-->
                                    <div id="global_search_workorders" class="d-none">
                                        <div class="d-flex flex-stack fw-semibold mb-3">
                                            <span class="text-primary fs-6"><i class="fa fa-clipboard-list me-2"></i>İş Emirleri</span>
                                        </div>
                                        <div id="global_search_workorders_list"></div>
                                    </div>
                                    <!--end::Work Orders Section-->
                                    <!--begin::Stocks Section-->
                                    <div id="global_search_stocks" class="d-none">
                                        <div class="separator border-gray-200 my-4"></div>
                                        <div class="d-flex flex-stack fw-semibold mb-3">
                                            <span class="text-success fs-6"><i class="fa fa-barcode me-2"></i>Stok Kodları</span>
                                        </div>
                                        <div id="global_search_stocks_list"></div>
                                    </div>
                                    <!--end::Stocks Section-->
                                </div>
                                <!--end::Results-->
                            </div>
                            <!--end::Menu-->
                        </div>
                    </div>
                    <!--end::Global Search-->
                    <!--begin::Theme mode-->
                    <div class="d-flex align-items-center">
                        <!--begin::Menu toggle-->
                        <a href="#" class="topbar-item px-3 px-lg-4" data-kt-menu-trigger="{default:'click', lg: 'hover'}" data-kt-menu-attach="parent" data-kt-menu-placement="bottom-end">
                            <i class="ki-outline ki-night-day theme-light-show fs-1"></i>
                            <i class="ki-outline ki-moon theme-dark-show fs-1"></i>
                        </a>
                        <!--begin::Menu toggle-->
                        <!--begin::Menu-->
                        <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-title-gray-700 menu-icon-gray-500 menu-active-bg menu-state-color fw-semibold py-4 fs-base w-150px" data-kt-menu="true" data-kt-element="theme-mode-menu">
                            <!--begin::Menu item-->
                            <div class="menu-item px-3 my-0">
                                <a href="#" class="menu-link px-3 py-2" data-kt-element="mode" data-kt-value="light">
                                    <span class="menu-icon" data-kt-element="icon">
                                        <i class="ki-outline ki-night-day fs-2"></i>
                                    </span>
                                    <span class="menu-title">Light</span>
                                </a>
                            </div>
                            <!--end::Menu item-->
                            <!--begin::Menu item-->
                            <div class="menu-item px-3 my-0">
                                <a href="#" class="menu-link px-3 py-2" data-kt-element="mode" data-kt-value="dark">
                                    <span class="menu-icon" data-kt-element="icon">
                                        <i class="ki-outline ki-moon fs-2"></i>
                                    </span>
                                    <span class="menu-title">Dark</span>
                                </a>
                            </div>
                            <!--end::Menu item-->
                            <!--begin::Menu item-->
                            <div class="menu-item px-3 my-0">
                                <a href="#" class="menu-link px-3 py-2" data-kt-element="mode" data-kt-value="system">
                                    <span class="menu-icon" data-kt-element="icon">
                                        <i class="ki-outline ki-screen fs-2"></i>
                                    </span>
                                    <span class="menu-title">System</span>
                                </a>
                            </div>
                            <!--end::Menu item-->
                        </div>
                        <!--end::Menu-->
                    </div>
                    <!--end::Theme mode-->
                    <div class="d-flex align-items-center">
                        <div class="topbar-item px-3 px-lg-4" data-kt-menu-trigger="click" data-kt-menu-attach="parent" data-kt-menu-placement="bottom-end" data-kt-menu-flip="bottom">
                            <i class="ki-outline ki-notification-on fs-1"></i>
                            <span class="bullet bullet-dot bg-danger h-6px w-6px position-absolute translate-middle top-0 start-50 animation-blink d-none" id="notification_dot"></span>
                        </div>
                        <div class="menu menu-sub menu-sub-dropdown menu-column w-350px w-lg-375px" data-kt-menu="true">
                            <div class="d-flex justify-content-between align-items-center px-9 pt-8 pb-4">
                                <h3 class="text-gray-900 fw-bold m-0">Bildirimler</h3>
                                <span class="badge badge-light-primary fs-7" id="notification_counts">Yükleniyor...</span>
                            </div>
                            <div class="separator border-gray-200"></div>

                            <div class="scroll-y mh-325px my-5 px-8" id="notification_list">
                                <div class="d-flex flex-column align-items-center justify-content-center py-5">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="visually-hidden">Yükleniyor...</span>
                                    </div>
                                </div>
                            </div>
                            <div class="py-3 text-center border-top">
                                <a href="/notifications" class="btn btn-color-gray-600 btn-active-color-primary">
                                    Tümünü Gör <i class="ki-outline ki-arrow-right fs-5"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                    <!--begin::User-->
                    <div class="d-flex align-items-stretch" id="kt_header_user_menu_toggle">
                        <!--begin::Menu wrapper-->
                        <div class="topbar-item cursor-pointer symbol px-3 px-lg-5 me-n3 me-lg-n5 symbol-30px symbol-md-35px" data-kt-menu-trigger="click" data-kt-menu-attach="parent" data-kt-menu-placement="bottom-end" data-kt-menu-flip="bottom">
                            <img src="assets/media/images/user.png" alt="VNL" />
                        </div>
                        <!--begin::User account menu-->
                        <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-800 menu-state-bg menu-state-color fw-semibold py-4 fs-6 w-275px" data-kt-menu="true">
                            <!--begin::Menu item-->
                            <div class="menu-item px-3">
                                <div class="menu-content d-flex align-items-center px-3">
                                    <!--begin::Avatar-->
                                    <div class="symbol symbol-50px me-5">
                                        <img alt="Logo" src="assets/media/avatars/user.png" />
                                    </div>
                                    <!--end::Avatar-->
                                    <!--begin::Username-->
                                    <div class="d-flex flex-column">
                                        <div class="fw-bold d-flex align-items-center fs-5"><?=$CurrentUser->fullname()?></div>
                                        <a href="#" class="fw-semibold text-muted text-hover-primary fs-7"><?=$CurrentUser->Username?></a>
                                    </div>
                                    <!--end::Username-->
                                </div>
                            </div>
                            <!--end::Menu item-->

                            <!--begin::Menu item-->
                            <div class="menu-item px-5">
                                <a href="/logout" class="menu-link px-5">Çıkış Yap</a>
                            </div>
                            <!--end::Menu item-->
                        </div>
                        <!--end::User account menu-->
                        <!--end::Menu wrapper-->
                    </div>
                    <!--end::User -->
                    <!--begin::Heaeder menu toggle-->
                    <div class="d-flex align-items-stretch d-lg-none px-3 me-n3" title="Show header menu">
                        <div class="topbar-item" id="kt_header_menu_mobile_toggle">
                            <i class="ki-outline ki-burger-menu-2 fs-1"></i>
                        </div>
                    </div>
                    <!--end::Heaeder menu toggle-->
                </div>
                <!--end::Toolbar wrapper-->
            </div>
            <!--end::Wrapper-->
        </div>
        <!--end::Container-->
    </div>

    <?php
}
?>
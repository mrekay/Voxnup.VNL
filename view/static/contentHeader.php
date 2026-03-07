<!-- Content Header (Page header) -->
<?php if ($page_config['contentheader'] != false) { ?>
    <!--begin::Toolbar-->
    <div class="toolbar" id="kt_toolbar">
        <!--begin::Container-->
        <div id="kt_toolbar_container" class="container-fluid d-flex flex-stack">
            <!--begin::Page title-->
            <div data-kt-swapper="true" data-kt-swapper-mode="prepend"
                 data-kt-swapper-parent="{default: '#kt_content_container', 'lg': '#kt_toolbar_container'}"
                 class="page-title d-flex align-items-center me-3 flex-wrap lh-1">
                <!--begin::Title-->
                <h1 class="d-flex align-items-center text-gray-900 fw-bold my-1 fs-3"><?= $meta["title"] ?></h1>
                <!--end::Title-->
                <!--begin::Separator-->
                <span class="h-20px border-gray-200 border-start mx-4"></span>
                <!--end::Separator-->
                <!--begin::Breadcrumb-->
                <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-1">
                    <!--begin::Item-->
                    <li class="breadcrumb-item text-muted">
                        <a href="/" class="text-muted text-hover-primary">Üretim Kontrol Sistemi</a>
                    </li>
                    <!--end::Item-->

                    <?php if (!empty($meta["upper_title"])) { ?>
                        <!--begin::Item-->
                        <li class="breadcrumb-item">
                            <span class="bullet bg-gray-300 w-5px h-2px"></span>
                        </li>
                        <!--end::Item-->
                        <!--begin::Item-->
                        <li class="breadcrumb-item text-muted"><?= $meta["upper_title"] ?></li>
                        <!--end::Item-->
                        <!--begin::Item-->
                        <li class="breadcrumb-item">
                            <span class="bullet bg-gray-300 w-5px h-2px"></span>
                        </li>
                        <!--end::Item-->
                        <!--begin::Item-->
                        <li class="breadcrumb-item text-gray-900"><?= $meta["title"] ?></li>
                        <!--end::Item-->

                    <?php } ?>
                </ul>
                <!--end::Breadcrumb-->
            </div>
            <!--end::Page title-->
            <!--begin::Actions-->
            <div class="d-flex align-items-center py-1">
                <!--begin::Wrapper-->
                <div class="me-4">
                    <?php

                    if (!empty($page_config["toolbaritems"])) { ?>

                    <div class="d-flex align-items-center gap-2 gap-lg-3">
                        <?php foreach ($page_config["toolbaritems"] as $item) {

                        // 1. Durum: Dropdown Menü Var mı?
                        if (isset($item['items']) && is_array($item['items'])) { ?>

                        <div class="dropdown">
                            <<?= $item["type"] ?>
                            id="<?= $item["id"] ?? '' ?>"
                            class="<?= $item["class"] ?>"
                            <?= $item["custom"] ?? '' ?>
                            >
                            <?php if (!empty($item["icon"])) { ?>
                                <i class="<?= $item["icon"] ?> fs-5 me-1"></i>
                            <?php } ?>
                            <?= $item["text"] ?>
                        </<?= $item["type"] ?>>

                        <ul class="dropdown-menu menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-600 menu-state-bg-light-primary fw-semibold fs-7 w-200px py-4">
                            <?php foreach ($item['items'] as $subItem) { ?>
                                <li>
                                <<?= $subItem["type"] ?>
                                class="<?= $subItem["class"] ?> px-3"
                                <?= $subItem["custom"] ?? '' ?>
                                >
                                <?php if (!empty($subItem["icon"])) { ?>
                                    <span class="menu-icon me-2">
                                            <i class="<?= $subItem["icon"] ?>"></i>
                                        </span>
                                <?php } ?>
                                <?= $subItem["text"] ?>
                                </<?= $subItem["type"] ?>>
                                </li>
                            <?php } ?>
                        </ul>
                    </div>

                    <?php }
                    // 2. Durum: Standart Buton
                    else { ?>

                    <<?= $item["type"] ?>
                    id="<?= $item["id"] ?? '' ?>"
                    class="<?= $item["class"] ?>"
                    <?= $item["custom"] ?? '' ?>
                    >
                    <?php if (!empty($item["icon"])) { ?>
                        <i class="<?= $item["icon"] ?> fs-5 me-1"></i>
                    <?php } ?>
                    <?= $item["text"] ?>
                </<?= $item["type"] ?>>

                <?php }
                } ?>
            </div>

            <?php } ?>
            <!--begin::Menu-->


            <!--end::Menu-->
        </div>
        <!--end::Wrapper-->
    </div>
    <!--end::Actions-->
    </div>
    <!--end::Container-->
    </div>
    <!--end::Toolbar-->
<?php } ?>
<!-- /.content-header -->

<?php
if ($page_config['topbar'] != false){

    ?>
    <header class="vnl-topbar">

        <?php if ($page_config['leftsidebar'] != false) { ?>
            <!-- Mobil: sidebar çekmecesi -->
            <button type="button" class="vnl-topbar-item d-lg-none" data-bs-toggle="offcanvas"
                    data-bs-target="#vnlSidebar" aria-controls="vnlSidebar" aria-label="<?= lang('LBL_MENU') ?>">
                <i class="bi bi-list fs-3"></i>
            </button>
            <!-- Desktop: sidebar daralt/genişlet -->
            <button type="button" class="vnl-topbar-item d-none d-lg-inline-flex"
                    data-vnl-toggle="sidebar-mini" aria-label="<?= lang('LBL_MENU') ?>">
                <i class="bi bi-chevron-double-left"></i>
            </button>
        <?php } ?>

        <a href="/" class="vnl-topbar-brand">
            <?php if ($page_config['leftsidebar'] == false) { ?>
                <img alt="" src="<?= $cDefinitions->appLogoPath() . 'VNL.png' ?>"/>
            <?php } ?>
            <span><?= $cDefinitions->appName() ?></span>
            <span class="vnl-topbar-version"><?= $cDefinitions->version() ?></span>
        </a>

        <div class="vnl-topbar-spacer"></div>

        <!-- Tema modu -->
        <div class="dropdown">
            <button type="button" class="vnl-topbar-item" data-bs-toggle="dropdown" aria-expanded="false"
                    aria-label="Tema">
                <i class="bi bi-circle-half"></i>
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                <li>
                    <button type="button" class="dropdown-item d-flex align-items-center gap-2" data-vnl-theme-value="light">
                        <i class="bi bi-sun"></i><?= lang('LBL_THEME_LIGHT') ?>
                    </button>
                </li>
                <li>
                    <button type="button" class="dropdown-item d-flex align-items-center gap-2" data-vnl-theme-value="dark">
                        <i class="bi bi-moon-stars"></i><?= lang('LBL_THEME_DARK') ?>
                    </button>
                </li>
                <li>
                    <button type="button" class="dropdown-item d-flex align-items-center gap-2" data-vnl-theme-value="system">
                        <i class="bi bi-display"></i><?= lang('LBL_THEME_SYSTEM') ?>
                    </button>
                </li>
            </ul>
        </div>

        <!-- Bildirimler (statik örnek; projeler notification_list'i doldurur) -->
        <div class="dropdown">
            <button type="button" class="vnl-topbar-item" data-bs-toggle="dropdown" aria-expanded="false"
                    aria-label="<?= lang('LBL_NOTIFICATIONS') ?>">
                <i class="bi bi-bell"></i>
                <span class="vnl-topbar-dot animation-blink d-none" id="notification_dot"></span>
            </button>
            <div class="dropdown-menu dropdown-menu-end w-300px p-0">
                <div class="d-flex flex-stack px-5 py-4 border-bottom">
                    <span class="fw-semibold"><?= lang('LBL_NOTIFICATIONS') ?></span>
                    <span class="badge badge-soft-primary d-none" id="notification_counts"></span>
                </div>
                <div class="scroll-y mh-300px p-5" id="notification_list">
                    <div class="text-center text-body-secondary py-5">
                        <i class="bi bi-bell-slash fs-2x d-block mb-3 text-body-tertiary"></i>
                        <?= lang('LBL_NOTIFICATIONS_EMPTY') ?>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($CurrentUser->id > 0) { ?>
            <!-- Kullanıcı -->
            <div class="dropdown">
                <button type="button" class="vnl-topbar-item" data-bs-toggle="dropdown" aria-expanded="false"
                        aria-label="<?= $CurrentUser->fullname() ?>">
                    <span class="symbol symbol-circle symbol-30px">
                        <img src="<?= $cDefinitions->getAssetsUrl() ?>/media/images/user.png" alt=""/>
                    </span>
                </button>
                <div class="dropdown-menu dropdown-menu-end w-250px">
                    <div class="d-flex align-items-center gap-3 px-4 py-2">
                        <span class="symbol symbol-circle symbol-40px">
                            <img src="<?= $cDefinitions->getAssetsUrl() ?>/media/images/user.png" alt=""/>
                        </span>
                        <div class="min-w-0">
                            <div class="fw-semibold text-truncate"><?= $CurrentUser->fullname() ?></div>
                            <div class="fs-8 text-body-secondary text-truncate"><?= $CurrentUser->Username ?></div>
                        </div>
                    </div>
                    <hr class="dropdown-divider">
                    <a class="dropdown-item d-flex align-items-center gap-2" href="/logout">
                        <i class="bi bi-box-arrow-right"></i><?= lang('LBL_SIGN_OUT') ?>
                    </a>
                </div>
            </div>
        <?php } else { ?>
            <a href="/login" class="btn btn-sm btn-primary ms-1"><?= lang('LOGIN_TITLE') ?></a>
        <?php } ?>

    </header>
    <?php
}
?>

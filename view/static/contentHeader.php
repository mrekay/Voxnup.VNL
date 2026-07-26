<?php if ($page_config['contentheader'] != false) { ?>
    <div class="vnl-toolbar">
        <div class="app-container container-fluid d-flex flex-stack flex-wrap gap-3">

            <div class="vnl-page-title">
                <h1><?= $meta["title"] ?></h1>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item">
                            <a href="/" class="text-body-secondary text-hover-primary"><?= $cDefinitions->appName() ?></a>
                        </li>
                        <?php if (!empty($meta["upper_title"])) { ?>
                            <li class="breadcrumb-item text-body-secondary"><?= $meta["upper_title"] ?></li>
                        <?php } ?>
                        <li class="breadcrumb-item active" aria-current="page"><?= $meta["title"] ?></li>
                    </ol>
                </nav>
            </div>

            <?php if (!empty($page_config["toolbaritems"])) { ?>
                <div class="vnl-toolbar-actions">
                    <?php foreach ($page_config["toolbaritems"] as $item) {

                        // 1. Durum: Dropdown menülü öğe
                        if (isset($item['items']) && is_array($item['items'])) {
                            $vnl_dd_toggle = (strpos($item['custom'] ?? '', 'data-bs-toggle') === false)
                                ? 'data-bs-toggle="dropdown" aria-expanded="false"' : '';
                            ?>

                            <div class="dropdown">
                                <<?= $item["type"] ?>
                                id="<?= $item["id"] ?? '' ?>"
                                class="<?= $item["class"] ?>"
                                <?= $vnl_dd_toggle ?>
                                <?= $item["custom"] ?? '' ?>
                                >
                                <?php if (!empty($item["icon"])) { ?>
                                    <i class="<?= $item["icon"] ?> fs-7 me-1"></i>
                                <?php } ?>
                                <?= $item["text"] ?>
                            </<?= $item["type"] ?>>

                            <ul class="dropdown-menu dropdown-menu-end">
                                <?php foreach ($item['items'] as $subItem) { ?>
                                    <li>
                                    <<?= $subItem["type"] ?>
                                    class="dropdown-item <?= $subItem["class"] ?>"
                                    <?= $subItem["custom"] ?? '' ?>
                                    >
                                    <?php if (!empty($subItem["icon"])) { ?>
                                        <i class="<?= $subItem["icon"] ?> fa-fw me-2"></i>
                                    <?php } ?>
                                    <?= $subItem["text"] ?>
                                    </<?= $subItem["type"] ?>>
                                    </li>
                                <?php } ?>
                            </ul>
                        </div>

                        <?php }
                        // 2. Durum: Standart buton
                        else { ?>

                        <<?= $item["type"] ?>
                        id="<?= $item["id"] ?? '' ?>"
                        class="<?= $item["class"] ?>"
                        <?= $item["custom"] ?? '' ?>
                        >
                        <?php if (!empty($item["icon"])) { ?>
                            <i class="<?= $item["icon"] ?> fs-7 me-1"></i>
                        <?php } ?>
                        <?= $item["text"] ?>
                    </<?= $item["type"] ?>>

                    <?php }
                    } ?>
                </div>
            <?php } ?>

        </div>
    </div>
<?php } ?>

<?php
$_uri = $_SERVER['REQUEST_URI'];
if (!empty($_SERVER['QUERY_STRING'])) {
    $_uri = strstr($_uri, '?', true) ?: $_uri;
}
$fileName = ltrim($_uri, '/');
$qs = (isset($_SERVER['QUERY_STRING']) && !empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '');


//show menu kullanım şekilleri:
// 0: Her zaman gizli, 1: Her zaman açık, 2: Oturum açtığında açık
//UYARI TRUE veya FALSE kullanmayın en iyi ihtimalle çalışmayacaktır...
// icon: FA kısa adı ("home" → fa-solid fa-home) YA DA boşluklu tam class
// ("bi bi-house" → olduğu gibi basılır). v5 fa adları FA7 compat ile çalışır.
$SidebarMenu = array(
        [
                "text" => "Anasayfa",
                'url' => 'index',
                'page_name' => 'index.php',
                'permission' => 'WebDashboard',
                'icon' => 'home',
                'showmenu' => 1,
                "child" => []
        ],
        [
                "text" => "Dokümantasyon",
                'icon' => 'tasks',
                'showmenu' => 1,
                "child" => [
                        [
                                "text" => "Başlangıç",
                                'url' => 'docs/getting-started',
                                'page_name' => 'getting-started.php',
                                'permission' => '',
                                'icon' => 'clock', // Zaman / bekleyen
                                'showmenu' => 1,
                                "child" => []
                        ],
                        [
                                "text" => "VNL Theme",
                                'url' => 'docs/theme',
                                'page_name' => 'theme.php',
                                'permission' => '',
                                'icon' => 'clock',
                                'showmenu' => 1,
                                "child" => []
                        ],
                        [
                                "text" => "Yönlendirme (Routing) ve Controller",
                                'url' => 'docs/routing',
                                'page_name' => 'routing.php',
                                'permission' => '',
                                'icon' => 'clock', // Zaman / bekleyen
                                'showmenu' => 1,
                                "child" => []
                        ],
                        [
                                "text" => "İstek Yönetimi ve Action'lar",
                                'url' => 'docs/request-handling',
                                'page_name' => 'request-handling.php',
                                'permission' => '',
                                'icon' => 'clock',
                                'showmenu' => 1,
                                "child" => []
                        ],
                        [
                                "text" => "Modal Sistemi",
                                'url' => 'docs/modals',
                                'page_name' => 'modals.php',
                                'permission' => '',
                                'icon' => 'clock',
                                'showmenu' => 1,
                                "child" => []
                        ],
                        [
                                "text" => "Oturum Yönetimi",
                                'url' => 'docs/session',
                                'page_name' => 'session.php',
                                'permission' => '',
                                'icon' => 'clock',
                                'showmenu' => 1,
                                "child" => []
                        ],
                        [
                                "text" => "Config Definitions",
                                'url' => 'docs/config-definitions',
                                'page_name' => 'config-definitions.php',
                                'permission' => '',
                                'icon' => 'clock',
                                'showmenu' => 1,
                                "child" => []
                        ],
                        [
                                "text" => "Model ve Veritabanı",
                                'url' => 'docs/models',
                                'page_name' => 'models.php',
                                'permission' => '',
                                'icon' => 'clock',
                                'showmenu' => 1,
                                "child" => []
                        ],
                        [
                                "text" => "Validators",
                                'url' => 'docs/validators',
                                'page_name' => 'validators.php',
                                'permission' => '',
                                'icon' => 'clock',
                                'showmenu' => 1,
                                "child" => []
                        ],
                        [
                                "text" => "Dil Sistemi (i18n)",
                                'url' => 'docs/languages',
                                'page_name' => 'languages.php',
                                'permission' => '',
                                'icon' => 'clock',
                                'showmenu' => 1,
                                "child" => []
                        ],
                        [
                                "text" => "SDTable — DataTables",
                                'url' => 'docs/sdtable',
                                'page_name' => 'sdtable.php',
                                'permission' => '',
                                'icon' => 'clock',
                                'showmenu' => 1,
                                "child" => []
                        ],
                        [
                                "text" => "Excel Export",
                                'url' => 'docs/excel-export',
                                'page_name' => 'excel-export.php',
                                'permission' => '',
                                'icon' => 'clock',
                                'showmenu' => 1,
                                "child" => []
                        ],
                        [
                                "text" => "Modüller",
                                'url' => 'docs/modules',
                                'page_name' => 'modules.php',
                                'permission' => '',
                                'icon' => 'clock',
                                'showmenu' => 1,
                                "child" => []
                        ],
                        [
                                "text" => "View Includes Sistemi",
                                'url' => 'docs/view-includes',
                                'page_name' => 'view-includes.php',
                                'permission' => '',
                                'icon' => 'clock',
                                'showmenu' => 1,
                                "child" => []
                        ],
                        [
                                "text" => "Extension Sistemi",
                                'url' => 'docs/extensions',
                                'page_name' => 'extensions.php',
                                'permission' => '',
                                'icon' => 'clock',
                                'showmenu' => 1,
                                "child" => []
                        ],
                        [
                                "text" => "Stripe Extension",
                                'url' => 'docs/stripe',
                                'page_name' => 'stripe.php',
                                'permission' => '',
                                'icon' => 'clock',
                                'showmenu' => 1,
                                "child" => []
                        ],

                ]
        ],
        [
                "text" => "Örnek Sayfa",
                'icon' => 'tasks',
                'showmenu' => 1,
                "child" => [
                        [
                                "text" => "Örnek Alt Sayfa",
                                'url' => 'example-1',
                                'page_name' => 'example-1.php',
                                'permission' => 'ExamplePermPage1',
                                'icon' => 'clock', // Zaman / bekleyen
                                'showmenu' => 1,
                                "child" => []
                        ],
                        [
                                "text" => "Örnek Alt Sayfa 2",
                                'url' => 'example-2',
                                'page_name' => 'example-2.php',
                                'permission' => 'ExamplePermPage2',
                                'icon' => 'clock', // Zaman / bekleyen
                                'showmenu' => 1,
                                "child" => []
                        ],
                        [
                                "text" => "Örnek Alt Aynı Yetkide Sayfa ",
                                'url' => 'example-1',
                                'page_name' => 'example-1.php',
                                'permission' => 'ExamplePermPage1',
                                'icon' => 'clock', // Zaman / bekleyen
                                'showmenu' => 1,
                                "child" => []
                        ],
                        [
                                "text" => "Örnek Alt Aynı Yetkide Sayfa ",
                                'url' => 'example-1',
                                'page_name' => 'example-1.php',
                                'permission' => 'ExamplePermPage1',
                                'icon' => 'clock', // Zaman / bekleyen
                                'showmenu' => 1,
                                "child" => []
                        ],

                ]
        ],
        [
                "text" => "Oturum Açıldığında Gözükecek Sayfa",
                'url' => 'example-login-page',
                'page_name' => 'Example-Login-Page',
                'permission' => 'ExampleLoginPage',
                'icon' => 'home',
                'showmenu' => 2,
                "child" => []
        ],
        [
                "text" => "Gizli Sayfa",
                'url' => 'example-hidden',
                'page_name' => 'example-hidden.php',
                'permission' => 'ExampleHidden',
                'icon' => 'home',
                'showmenu' => 0,
                "child" => []
        ],

);

$NumberTexts = array('first', 'second', 'third', 'fourth', 'fifth', 'sixth', 'seventh', 'eighth', 'nineth');

if (!empty($customSideBar)) {
    $SidebarMenu[] = $customSideBar[0];
}

if ($page_config['leftsidebar'] != false) {
    ?>

    <aside id="vnlSidebar" class="vnl-sidebar offcanvas-lg offcanvas-start" tabindex="-1"
           aria-label="<?= lang('LBL_MENU') ?>" data-bs-theme="dark">
        <div class="vnl-sidebar-brand">
            <a href="/" class="d-inline-flex align-items-center gap-2 text-decoration-none">
                <img alt="" src="<?= $cDefinitions->appLogoPath() . 'VNL.png' ?>" style="height: 30px"/>
                <span class="vnl-brand-text"><?= $cDefinitions->appName() ?></span>
            </a>
            <button type="button" class="vnl-topbar-item ms-auto d-none d-lg-inline-flex"
                    data-vnl-toggle="sidebar-mini" aria-label="<?= lang('LBL_MENU') ?>">
                <i class="bi bi-chevron-double-left"></i>
            </button>
            <button type="button" class="btn-close btn-close-white ms-auto d-lg-none"
                    data-bs-dismiss="offcanvas" data-bs-target="#vnlSidebar"
                    aria-label="<?= lang('LBL_CLOSE') ?>"></button>
        </div>

        <nav class="vnl-sidebar-nav">
            <div class="vnl-menu">
                <?php ShowSidebarMenu($SidebarMenu, 0); ?>
            </div>
        </nav>

        <div class="vnl-sidebar-footer"></div>
    </aside>

    <?php
}

/* Alt seviye menü öğeleri (accordion içeriği). Fonksiyon imzaları ve
   $SidebarMenu dizi sözleşmesi VNL 2.x ile birebir aynıdır; yalnızca
   üretilen markup VNL Theme'e aittir. */
function showSideBar($array, $level)
{
    global $fileName, $meta;

    $friendly_name = isset($meta['friendly_name']) ? $meta['friendly_name'] : '';

    foreach ($array as $item) {


        //TODO : SİSTEMİ ENTEGRE ETTİKTEN SONRA AKTİF EDİN
//        if (!empty($item['permission'])) {
//            if (!UserPermissions::CanViewByCode($GLOBALS['CurrentUser']->Id, $item['permission'])) {
//                continue;
//            }
//        }

        if (!empty($item["text"])) { ?>
            <div class="vnl-menu-item">
                <a class="vnl-menu-link <?= ($item["url"] == $fileName || $item["url"] == $friendly_name) ? 'active' : '' ?>"
                   href="/<?= $item["url"] ?>">
                    <span class="vnl-menu-title"><?= $item["text"] ?></span>
                </a>
            </div>
        <?php }

        if (!empty($item['child'])) {
            ShowSidebarMenu($item['child'], $level);
        }
    }
}


function ShowSidebarMenu($array, $level)
{
    global $fileName, $NumberTexts, $CurrentUser, $ActiveProductId, $meta;

    $friendly_name = isset($meta['friendly_name']) ? $meta['friendly_name'] : '';


    $fileName = empty($fileName) ? '/' : $fileName;

    foreach ($array as $menu) {


        // TODO: SİSTEMİ ENTEGRE ETTİKTEN SONRA AKTİF EDİN
//        $pagePermission = !empty($menu['permission']) ? UserPermissions::CanViewByCode($CurrentUser->Id, $menu['permission']) : true;
        $pagePermission = true;

        $showmenu = canShowMenu($menu['showmenu'], $pagePermission);
        if ($showmenu) {
            $urls = SidebarMenuGetSubUrls($menu, (isset($menu['qs_active']) ? $menu['qs_active'] : true));

            $isActive = in_array($fileName, $urls);

            $hasChild = isset($menu['child']) && !empty($menu['child']);

            if ($hasChild) {
                /* Alt menü sağda flyout panel olarak açılır (hover ya da tıklama;
                   mobil offcanvas'ta yerinde). Davranış: theme/js/modules/flyout.js */
                ?>
                <div class="vnl-menu-item vnl-has-sub">
                    <button type="button"
                            class="vnl-menu-link <?= $isActive ? 'active' : '' ?>"
                            aria-haspopup="true" aria-expanded="false">
                        <span class="vnl-menu-icon"><i class="<?= strpos($menu['icon'], ' ') !== false ? $menu['icon'] : 'fa-solid fa-' . $menu['icon']; ?>"></i></span>
                        <span class="vnl-menu-title"><?= $menu['text']; ?></span>
                        <span class="vnl-menu-arrow"><i class="bi bi-chevron-right"></i></span>
                    </button>
                    <div class="vnl-menu-sub">
                        <?php showSideBar($menu['child'], $level + 1); ?>
                    </div>
                </div>
                <?php
            } else {
                ?>
                <div class="vnl-menu-item">
                    <a class="vnl-menu-link <?= !empty($menu["url"]) && ($menu["url"] == $fileName || $menu["url"] == $friendly_name) ? 'active' : '' ?>"
                       target="_self" href="/<?= $menu["url"] ?>">
                        <span class="vnl-menu-icon"><i class="<?= strpos($menu['icon'], ' ') !== false ? $menu['icon'] : 'fa-solid fa-' . $menu['icon']; ?>"></i></span>
                        <span class="vnl-menu-title"><?= $menu['text']; ?></span>
                    </a>
                </div>
                <?php
            }
        }
    }
}

function SidebarMenuGetSubUrls($array, $qs_active = true)
{
    global $qs;
    $urls = array();
    $urls[] = $array['url'] ?? '';
    if ($qs_active) {
        $urls[] = ($array['url'] ?? '') . $qs;
    }
    if (isset($array['subs'])) {
        foreach ($array['subs'] as $sub) {
            $urls[] = $sub;
            $urls[] = $sub . $qs;
        }
    }
    if (isset($array['child']) && !empty($array['child'])) {
        foreach ($array['child'] as $menu) {
            $url_child = SidebarMenuGetSubUrls($menu);
            foreach ($url_child as $u) {
                $urls[] = $u;
            }
        }
    }

    return $urls;
}

function canShowMenu($showmenu, $permission = true): bool
{
    global $CurrentUser;

    $_showmenu = true;
    switch ($showmenu) {
        case 0:
            $_showmenu = false;
            break;
        case 1:
            $_showmenu = true;
            break;
        case 2:
            $_showmenu = !empty($CurrentUser->id) && $CurrentUser->id > 0;
            break;
    }

    return $permission && $_showmenu;

}

function hasVisibleChild($children)
{
    global $CurrentUser;

    if (empty($children)) return false;

    foreach ($children as $child) {


        //TODO : BURAYI SİSTEMİ ENTEGRE ETTİKTEN SONRA AKTİF EDİN
//        $permission = !empty($child['permission'])
//                ? UserPermissions::CanViewByCode($CurrentUser->Id, $child['permission'])
//                : true;

        $permission = true;

        $showmenu = canShowMenu($child["showmenu"], $permission);

        if ($showmenu) {
            return true;
        }

        // Altın kural: child'ın child'ı olabilir
        if (!empty($child['child']) && hasVisibleChild($child['child'])) {
            return true;
        }
    }

    return false;
}

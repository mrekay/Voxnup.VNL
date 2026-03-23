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

    <!--begin::Aside-->
    <div id="kt_aside" class="aside aside-dark aside-hoverable" data-kt-drawer="true" data-kt-drawer-name="aside"
         data-kt-drawer-activate="{default: true, lg: false}" data-kt-drawer-overlay="true"
         data-kt-drawer-width="{default:'200px', '300px': '250px'}" data-kt-drawer-direction="start"
         data-kt-drawer-toggle="#kt_aside_mobile_toggle">
        <!--begin::Brand-->
        <div class="aside-logo flex-column-auto" id="kt_aside_logo">
            <!--begin::Logo-->
            <a href="/">

                <img alt="Logo" src="<?= $cDefinitions->appLogoPath() . 'VNLBIG.png' ?>" class="logo"
                     style="height: 32px"/>
            </a>
            <!--end::Logo-->
            <!--begin::Aside toggler-->
            <div id="kt_aside_toggle" class="btn btn-icon w-auto px-0 btn-active-color-primary aside-toggle me-n2"
                 data-kt-toggle="true" data-kt-toggle-state="active" data-kt-toggle-target="body"
                 data-kt-toggle-name="aside-minimize">
                <i class="ki-outline ki-double-left fs-1 rotate-180"></i>
            </div>
            <!--end::Aside toggler-->
        </div>
        <!--end::Brand-->

        <!--begin::Aside menu-->
        <div class="aside-menu flex-column-fluid">
            <!--begin::Aside Menu-->
            <div class="hover-scroll-overlay-y" id="kt_aside_menu_wrapper" data-kt-scroll="true"
                 data-kt-scroll-activate="{default: false, lg: true}" data-kt-scroll-height="auto"
                 data-kt-scroll-dependencies="#kt_aside_logo, #kt_aside_footer" data-kt-scroll-wrappers="#kt_aside_menu"
                 data-kt-scroll-offset="0">
                <!--begin::Menu-->
                <div class="menu menu-column menu-title-gray-800 menu-state-title-primary menu-state-icon-primary menu-state-bullet-primary menu-arrow-gray-500"
                     id="#kt_aside_menu" data-kt-menu="true">
                    <?php ShowSidebarMenu($SidebarMenu, 0); ?>
                </div>
                <!--end::Menu-->
            </div>
        </div>
        <!--end::Aside menu-->
        <!--begin::Footer-->
        <div class="aside-footer flex-column-auto pb-7 px-5" id="kt_aside_footer">

        </div>
        <!--end::Footer-->
    </div>
    <!--end::Aside-->

    <?php
}
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
            <div class="menu-item">
                <a class="menu-link <?= ($item["url"] == $fileName || $item["url"] == $friendly_name) ? 'active' : '' ?>"
                   href="/<?= $item["url"] ?>">
                    <span class="menu-bullet">
                        <span class="bullet bullet-dot"></span>
                    </span>
                    <span class="menu-title"><?= $item["text"] ?></span>
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
//        if ($menu['showmenu'] == 2) _print($showmenu,1);
        if ($showmenu) {
            $urls = SidebarMenuGetSubUrls($menu, (isset($menu['qs_active']) ? $menu['qs_active'] : true));

            $isActive = in_array($fileName, $urls);


            ?>

            <!--begin:Menu item-->
            <div <?= !empty($menu['child']) ? 'data-kt-menu-trigger="click"' : "" ?>
                    class="menu-item <?= isset($menu['child']) && !empty($menu['child']) ? "menu-accordion" . ($isActive ? 'hover show' : '') : "" ?>">
                <!--begin:Menu link-->
                <a <?= !empty($menu['child']) ? "menu-accordion" : ' target="_self" href="/' . $menu["url"] . '"' ?>
                        class="menu-link <?= !empty($menu["url"]) && ($menu["url"] == $fileName || $menu["url"] == $friendly_name) ? 'active' : '' ?>">
                    <div class="menu-icon">
                        <i class="fas fa-<?= $menu['icon']; ?>"></i>
                    </div>
                    <span class="menu-title"><?= $menu['text']; ?></span>
                    <?php if (!empty($menu['child'])) { ?>
                        <span class="menu-arrow"></span>
                    <?php } ?>
                </a>
                <!--end:Menu link-->
                <?php
                if (isset($menu['child']) && !empty($menu['child'])) { ?>
                    <!--begin:Menu sub-->
                    <div class="menu-sub menu-sub-accordion">
                        <?php showSideBar($menu['child'], $level + 1); ?>
                    </div>
                    <!--end:Menu sub-->
                <?php } ?>
            </div>
            <!--end:Menu item-->
            <?php
        }
    }
}

function SidebarMenuGetSubUrls($array, $qs_active = true)
{
    global $qs;
    $urls = array();
    $urls[] = $array['url'];
    if ($qs_active) {
        $urls[] = $array['url'] . $qs;
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
    $_showmenu = true;
    switch ($showmenu) {
        case 0:
            $_showmenu = false;
            break;
        case 1:
            $_showmenu = true;
            break;
        case 2:
            $_showmenu = $CurrentUser->id > 0;
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

        // Altın kural: child’ın child’ı olabilir
        if (!empty($child['child']) && hasVisibleChild($child['child'])) {
            return true;
        }
    }

    return false;
}

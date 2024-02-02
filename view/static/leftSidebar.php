<?php
$fileName = basename($_SERVER['REQUEST_URI'], '?' . (isset($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : ''));
$qs = (isset($_SERVER['QUERY_STRING']) && !empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '');
$SidebarMenu = array(
    [
        "text" => "Pages",
        "pages" => [
            array(
                'text' => "Projects",
                'url' => 'project-manager',
                'page_name' => 'project-manager.php',
                'icon' => 'calendar',
                'showmenu' => ($CurrentUser->id > 0),
            ),
        ]
    ]
);
$NumberTexts = array('first', 'second', 'third', 'fourth', 'fifth', 'sixth', 'seventh', 'eighth', 'nineth');

if (!empty($customSideBar)) {
    $SidebarMenu[] = $customSideBar[0];
}


if ($page_config['leftsidebar'] != false) {
    ?>

    <!--begin::Sidebar-->
    <div id="kt_app_sidebar" class="app-sidebar flex-column" data-kt-drawer="true" data-kt-drawer-name="app-sidebar"
         data-kt-drawer-activate="{default: true, lg: false}" data-kt-drawer-overlay="true" data-kt-drawer-width="225px"
         data-kt-drawer-direction="start" data-kt-drawer-toggle="#kt_app_sidebar_mobile_toggle">
        <!--begin::Logo-->
        <div class="app-sidebar-logo px-6" id="kt_app_sidebar_logo">
            <!--begin::Logo image-->
            <a href="/">
                <img alt="Logo" src="assets/images/logos/Subtract_dark.png" class=" h-25px me-2"/>
                <img alt="Logo" src="assets/images/logos/latestset_black.svg" class="h-10px"/>
            </a>
            <!--end::Logo image-->
            <!--begin::Sidebar toggle-->
            <!--begin::Minimized sidebar setup:
    if (isset($_COOKIE["sidebar_minimize_state"]) && $_COOKIE["sidebar_minimize_state"] === "on") {
    1. "src/js/layout/sidebar.js" adds "sidebar_minimize_state" cookie value to save the sidebar minimize state.
    2. Set data-kt-app-sidebar-minimize="on" attribute for body tag.
    3. Set data-kt-toggle-state="active" attribute to the toggle element with "kt_app_sidebar_toggle" id.
    4. Add "active" class to to sidebar toggle element with "kt_app_sidebar_toggle" id.
    }
    -->
            <div id="kt_app_sidebar_toggle"
                 class="app-sidebar-toggle btn btn-icon btn-shadow btn-sm btn-color-muted btn-active-color-primary h-30px w-30px position-absolute top-50 start-100 translate-middle rotate"
                 data-kt-toggle="true" data-kt-toggle-state="active" data-kt-toggle-target="body"
                 data-kt-toggle-name="app-sidebar-minimize">
                <i class="ki-duotone ki-black-left-line fs-3 rotate-180">
                    <span class="path1"></span>
                    <span class="path2"></span>
                </i>
            </div>
            <!--end::Sidebar toggle-->
        </div>
        <!--end::Logo-->
        <!--begin::sidebar menu-->
        <div class="app-sidebar-menu overflow-hidden flex-column-fluid">
            <!--begin::Menu wrapper-->
            <div id="kt_app_sidebar_menu_wrapper" class="app-sidebar-wrapper">
                <!--begin::Scroll wrapper-->
                <div id="kt_app_sidebar_menu_scroll" class="scroll-y my-5 mx-3" data-kt-scroll="true"
                     data-kt-scroll-activate="true" data-kt-scroll-height="auto"
                     data-kt-scroll-dependencies="#kt_app_sidebar_logo, #kt_app_sidebar_footer"
                     data-kt-scroll-wrappers="#kt_app_sidebar_menu" data-kt-scroll-offset="5px"
                     data-kt-scroll-save-state="true">
                    <!--begin::Menu-->
                    <div class="menu menu-column menu-rounded menu-sub-indention fw-semibold fs-6"
                         id="kt_app_sidebar_menu"
                         data-kt-menu="true" data-kt-menu-expand="false">
                        <?php showSideBar($SidebarMenu, 1); ?>
                    </div>
                    <!--end::Menu-->
                </div>
                <!--end::Scroll wrapper-->
            </div>
            <!--end::Menu wrapper-->
        </div>
        <!--end::sidebar menu-->
    </div>
    <!--end::Sidebar-->

    <?php
}
function showSideBar($array, $level)
{
    foreach ($array as $item) {
        if (!empty($item["text"])) { ?>

            <!--begin:Menu item-->
            <div class="menu-item pt-5">
                <!--begin:Menu content-->
                <div class="menu-content">
                    <span class="menu-heading fw-bold text-uppercase fs-7"><?= $item["text"] ?></span>
                </div>
                <!--end:Menu content-->
            </div>
            <!--end:Menu item-->

        <?php }
        ShowSidebarMenu($item["pages"], $level);
    }
}

function ShowSidebarMenu($array, $level)
{
    global $fileName, $NumberTexts, $CurrentUser, $ActiveProductId;
    foreach ($array as $menu) {
        $pagePermission = $CurrentUser->getPagePermission($menu['page_name'], $ActiveProductId);

        if ((!isset($menu['showmenu']) || (isset($menu['showmenu']) && $menu['showmenu']))) {
            $urls = SidebarMenuGetSubUrls($menu, (isset($menu['qs_active']) ? $menu['qs_active'] : true));
            ?>

            <!--begin:Menu item-->
            <div <?= isset($menu['child']) && !empty($menu['child']) ? 'data-kt-menu-trigger="click"' : "" ?>
                    class="menu-item <?= isset($menu['child']) && !empty($menu['child']) ? "menu-accordion" : "" ?>">
                <!--begin:Menu link-->
                <a target="_self" href="/<?= $menu["url"] ?>" class="menu-link">
                    <div class="menu-icon">
                        <i class="fas fa-<?= $menu['icon']; ?>"></i>
                    </div>
                    <span class="menu-title"><?= $menu['text']; ?></span>
                    <?php if (isset($menu['child']) && !empty($menu['child'])) { ?>
                        <span class="menu-arrow"></span>
                    <?php } ?>
                </a>
                <!--end:Menu link-->
                <?php if (isset($menu['child']) && !empty($menu['child'])) { ?>
                    <!--begin:Menu sub-->
                    <div class="menu-sub menu-sub-accordion">
                        <?php ShowSidebarMenu($menu['child'], $level + 1); ?>
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
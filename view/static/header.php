<?php
$page_config_default = array(
    'leftsidebar' => true,//true,false ,'mini'
    'topbar' => true,//true,false
    'footer' => false,
    'loader' => true,
    'contentheader' => true,
);
$page_config = !isset($page_config) || empty($page_config) ? $page_config_default : array_merge($page_config_default, $page_config);
?>

<!doctype html>
<html lang="en">
<head>
    <?php
    require_once view('includes/static/head-standard');
    require_once view('includes/static/script-standard');
    if (file_exists(view('includes/header/head-' . route(0))))
        require_once view('includes/header/head-' . route(0));

    if (isset($css_files) && !empty($css_files)) {

        foreach ($css_files as $item) {

            if (file_exists(view('includes/header/' . $item)))
                require_once view('includes/header/' . $item);
        }
    }
    ?>


</head>
<body id="kt_app_body" data-kt-app-layout="<?=$page_config["leftsidebar"] ? "dark-sidebar" : ""?>" data-kt-app-header-fixed="true"
      data-kt-app-sidebar-enabled="<?=$page_config["leftsidebar"] ? "true" : "false"?>" data-kt-app-sidebar-fixed="<?=$page_config["leftsidebar"] ? "true" : "false"?>" data-kt-app-sidebar-hoverable="<?=$page_config["leftsidebar"] ? "true" : "false"?>"
      data-kt-app-sidebar-push-header="<?=$page_config["leftsidebar"] ? "true" : "false"?>" data-kt-app-sidebar-push-toolbar="<?=$page_config["leftsidebar"] ? "true" : "false"?>"
      data-kt-app-sidebar-push-footer="true" data-kt-app-toolbar-enabled="true" class="app-default">
<!--begin::Theme mode setup on page load-->
<script>
    var defaultThemeMode = "light";
    var themeMode;
    if (document.documentElement) {
        if (document.documentElement.hasAttribute("data-bs-theme-mode")) {
            themeMode = document.documentElement.getAttribute("data-bs-theme-mode");
        } else {
            if (localStorage.getItem("data-bs-theme") !== null) {
                themeMode = localStorage.getItem("data-bs-theme");
            } else {
                themeMode = defaultThemeMode;
            }
        }
        if (themeMode === "system") {
            themeMode = window.matchMedia("(prefers-color-scheme: dark)").matches ? "dark" : "light";
        }
        document.documentElement.setAttribute("data-bs-theme", themeMode);
    }
</script>
<!--end::Theme mode setup on page load-->
<!--begin::App-->
<div class="d-flex flex-column flex-root app-root" id="kt_app_root">
    <!--begin::Page-->
    <div class="app-page flex-column flex-column-fluid" id="kt_app_page">
        <div class="loading-overlay" id="loadingSpinner" style="display: none">
            <img src="/assets/images/loading3_black.gif" class="theme-dark-show">
            <img src="/assets/images/loading3.gif" class="theme-light-show">
        </div>
        <?php
        if ($page_config['topbar'])
            require_once view('static/topNavbar');
        ?>

        <!--begin::Wrapper-->
        <div class="app-wrapper flex-column flex-row-fluid" id="kt_app_wrapper">

            <?php
            if ($page_config['leftsidebar'])
                require_once view('static/leftSidebar');
            ?>
            <div class="app-main flex-column flex-row-fluid" id="kt_app_main">
                <!--begin::Content wrapper-->
                <div class="d-flex flex-column flex-column-fluid">


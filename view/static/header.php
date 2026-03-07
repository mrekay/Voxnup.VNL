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
<body id="kt_app_body" data-kt-app-layout="<?= $page_config["leftsidebar"] ? "dark-sidebar" : "" ?>"
      data-kt-app-header-fixed="true"
      data-kt-app-sidebar-enabled="<?= $page_config["leftsidebar"] ? "true" : "false" ?>"
      data-kt-app-sidebar-fixed="<?= $page_config["leftsidebar"] ? "true" : "false" ?>"
      data-kt-app-sidebar-hoverable="<?= $page_config["leftsidebar"] ? "true" : "false" ?>"
      data-kt-app-sidebar-push-header="<?= $page_config["leftsidebar"] ? "true" : "false" ?>"
      data-kt-app-sidebar-push-toolbar="<?= $page_config["leftsidebar"] ? "true" : "false" ?>"
      data-kt-app-sidebar-push-footer="true" data-kt-app-toolbar-enabled="true" class="header-fixed header-tablet-and-mobile-fixed toolbar-enabled toolbar-fixed toolbar-tablet-and-mobile-fixed aside-enabled aside-fixed">
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
<div class="d-flex flex-column flex-root">
    <!--begin::Page-->
    <div class="page d-flex flex-row flex-column-fluid">
        <div class="loading-overlay" id="loadingSpinner" style="display: none">
            <img src="/assets/images/loading3_black.gif" class="theme-dark-show">
            <img src="/assets/images/loading3.gif" class="theme-light-show">
        </div>

        <?php


        require_once view('static/leftSidebar');

        ?>

        <div class="wrapper d-flex flex-column flex-row-fluid" id="kt_wrapper">
            <?php
            if ($page_config['topbar'])
                require_once view('static/topNavbar');
            ?>
            <div class="content d-flex flex-column flex-column-fluid" id="kt_content">
                <?php
                require_once view('static/contentHeader');
                ?>


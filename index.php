<?php

require __DIR__ . '/libs/config.php';

$routeExplode = explode('?', $_SERVER['REQUEST_URI']);
$route = array_values(array_filter(explode('/', $routeExplode[0])));
if (getSubFolderName() != '/' && getSubFolderName() != '\\') {
    array_shift($route);
}

if (!route(0)) {
    $route[0] = 'index';
}

// Login kontrolü için orijinal route değerlerini sakla ($route[0] aşağıda değişebilir)
$originalSegment0 = route(0);
$originalSegment1 = route(1);

// Alt klasör desteği: /docs/overview → controller/docs/overview.php
$controllerPath = controller(route(0));
if (is_dir(getPath() . '/controller/' . route(0)) && route(1)) {
    $groupedController = route(0) . '/' . route(1);
    if (file_exists(controller($groupedController))) {
        $controllerPath = controller($groupedController);
    } else {
        $controllerPath = controller('404');
    }
} elseif (!file_exists($controllerPath)) {
    $route[0] = '404';
    $controllerPath = controller('404');
}

// currentPage: gruplu sayfalarda "docs/overview", tekil sayfalarda "index" gibi
$loginExceptPages = $cDefinitions->getLoginExceptPages();
$currentPage = ($originalSegment1 && is_dir(getPath() . '/controller/' . $originalSegment0))
    ? $originalSegment0 . '/' . $originalSegment1
    : $originalSegment0;
$isExceptPage = in_array($currentPage, $loginExceptPages) || in_array($originalSegment0, $loginExceptPages);

if (!$isExceptPage && $CurrentUser->id <= 0) {
    // Controller'ı çalıştır, output'u yakala; $page_config['public'] varsa geç
    ob_start();
    require $controllerPath;
    $output = ob_get_clean();

    if (!empty($page_config['public'])) {
        echo $output;
    } else {
        header('location:' . $cDefinitions->getSiteUrl() . '/login');
        exit;
    }
} else {
    require $controllerPath;
}

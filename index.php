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

if (!file_exists(controller(route(0)))) {
    $route[0] = '404';
}

if (!in_array($route[0], $cDefinitions->getLoginExceptPages() )) {
    if ($CurrentUser->id <= 0) {
        header('location:' . $cDefinitions->getSiteUrl() . '/login');
    }
}

require controller(route(0));

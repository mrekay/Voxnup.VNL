<?php

require __DIR__ . '/libs/config.php';

$routeExplode = explode('?', $_SERVER['REQUEST_URI']);
$route = array_values(array_filter(explode('/', $routeExplode[0])));
if (SUBFOLDER_NAME != '/' && SUBFOLDER_NAME != '\\') {
    array_shift($route);
}

if (!route(0)) {
    $route[0] = 'index';
}

if (!file_exists(controller(route(0)))) {
    $route[0] = '404';
}

if (!in_array($route[0], array('app','login', 'logout'))) {
    if ($CurrentUser->id <= 0) {
        header('location:' . $cDefinitions->getSiteUrl() . '/login');
    }
}

require controller(route(0));

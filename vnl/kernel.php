<?php
/*!
 * ============================================================
 *  Voxnup Not Laravel (VNL) — Yönlendirme
 *  Adresi denetleyiciye çevirir, giriş denetimini uygular.
 *  Authors: Voxnup Authors
 * ============================================================
 *
 * VNL 3.0'a kadar bu kod kökteki index.php'nin içindeydi; yani her projede
 * bir kopyası vardı ve VNL'de düzeltilen bir yönlendirme hatası türev
 * projelere hiç ulaşmıyordu. Artık çekirdeğin.
 */

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

/* Alt klasör desteği: /docs/overview → controller/docs/overview.php
   Dizin proje tarafında da çekirdekte de olabilir; controller() ikisine de bakar. */
$controllerPath = controller(route(0));
if (vnl_resolve_dir('/controller/' . route(0)) !== null && route(1)) {
    $groupedController = route(0) . '/' . route(1);
    if (file_exists(controller($groupedController))) {
        $controllerPath = controller($groupedController);
    } else {
        $controllerPath = controller('404');
    }
} elseif (!file_exists($controllerPath)) {
    /* Extension controller fallback */
    $_extCtrlFound = false;
    foreach (glob(VNL_ROOT . '/extensions/*/controller/' . route(0) . '.php') as $_extCtrl) {
        $controllerPath = $_extCtrl;
        $_extCtrlFound  = true;
        break;
    }
    if (!$_extCtrlFound) {
        $route[0] = '404';
        $controllerPath = controller('404');
    }
    unset($_extCtrlFound, $_extCtrl);
}

// currentPage: gruplu sayfalarda "docs/overview", tekil sayfalarda "index" gibi
$loginExceptPages = $cDefinitions->getLoginExceptPages();
$currentPage = ($originalSegment1 && vnl_resolve_dir('/controller/' . $originalSegment0) !== null)
    ? $originalSegment0 . '/' . $originalSegment1
    : $originalSegment0;
$isExceptPage = in_array($currentPage, $loginExceptPages) || in_array($originalSegment0, $loginExceptPages);

if (!$isExceptPage && $CurrentUser->id <= 0) {
    // Controller'ı çalıştır, output'u yakala; $page_config['public'] varsa geç
    ob_start();
    try { require $controllerPath; } catch (Throwable $e) { ob_end_clean(); vnl_render_error($e); }
    $output = ob_get_clean();

    if (!empty($page_config['public'])) {
        echo $output;
    } else {
        header('location:' . $cDefinitions->getSiteUrl() . '/login');
        exit;
    }
} else {
    try { require $controllerPath; } catch (Throwable $e) { vnl_render_error($e); }
}

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

$routeExplode = explode('?', $_SERVER['REQUEST_URI'] ?? '/');
$route = array_values(array_filter(explode('/', $routeExplode[0]), static fn($s) => $s !== ''));
if (getSubFolderName() != '/' && getSubFolderName() != '\\') {
    array_shift($route);
}

if (!route(0)) {
    $route[0] = 'index';
}

/**
 * Dosya çözümlemesine giren parçalar yalnız güvenli karakterler taşıyabilir.
 *
 * Parça doğrudan dosya yoluna ve eklenti aramasındaki glob() desenine
 * giriyordu: "/stripe-*" gibi bir adres glob joker karakteriyle başka bir
 * denetleyiciye, ".." ise üst dizine uzanabilirdi. Parametre parçaları
 * (route(2) ve sonrası; gruplu değilse route(1) ve sonrası) serbesttir.
 */
$vnlSafeSegment = static fn($s): bool => is_string($s) && preg_match('/^[A-Za-z0-9_][A-Za-z0-9_.\-]*$/', $s) === 1;

/* İstenen ilk parça (temizlenmeden önce): hata sayfasının kendisi mi
   istendi, yoksa bilinmeyen bir adres mi 404'e düştü? */
$vnlRequestedPage = (string)route(0);

if (!$vnlSafeSegment(route(0))) {
    $route = ['404'];
}

// Login kontrolü için orijinal route değerlerini sakla ($route[0] aşağıda değişebilir)
$originalSegment0 = route(0);
$originalSegment1 = route(1);

/* Alt klasör desteği: /docs/overview → controller/docs/overview.php
   Dizin proje tarafında da çekirdekte de olabilir; controller() ikisine de bakar. */
$vnlIsGrouped   = false;
$controllerPath = controller(route(0));
if (vnl_resolve_dir('/controller/' . route(0)) !== null && route(1)) {
    $vnlIsGrouped      = true;
    $groupedController = route(0) . '/' . route(1);
    if ($vnlSafeSegment(route(1)) && file_exists(controller($groupedController))) {
        $controllerPath = controller($groupedController);
    } else {
        $controllerPath = controller('404');
        $route[0]       = '404';
    }
} elseif (!file_exists($controllerPath)) {
    /* Extension controller fallback — 3.4.0: yalnız ETKİN eklentilerde
       aranır (extensions/extensions.php). Parça yukarıda doğrulandı. */
    $_extCtrlFound = false;
    foreach (vnl_enabled_extensions() as $_extName) {
        $_extCtrl = VNL_ROOT . '/extensions/' . $_extName . '/controller/' . route(0) . '.php';
        if (is_file($_extCtrl)) {
            $controllerPath = $_extCtrl;
            $_extCtrlFound  = true;
            break;
        }
    }
    unset($_extName);
    if (!$_extCtrlFound) {
        $route[0] = '404';
        $controllerPath = controller('404');
    }
    unset($_extCtrlFound, $_extCtrl);
}

/**
 * Giriş kapısı (3.4.0).
 *
 * Karar denetleyici ÇALIŞMADAN verilir. VNL 3.3'e kadar girişsiz istekte
 * korumalı denetleyici önce çalıştırılıyor, çıktısı yakalanıyor, ancak
 * sonra $page_config['public'] okunup yönlendirme yapılıyordu — yani
 * denetleyicideki her yan etki (dosya yazmak, kayıt açmak) anonim
 * ziyaretçi için de gerçekleşiyordu.
 *
 * Herkese açık sayılanlar:
 *   - config/definitions.php → $login_except_pages ('sayfa' ya da 'grup/sayfa')
 *   - vnl_public_page() ile kaydedilenler (eklenti boot.php'leri)
 *   - projenin hata sayfaları (401, 403, 404)
 */
$loginExceptPages = array_merge($cDefinitions->getLoginExceptPages(), vnl_public_pages());
$currentPage = ($originalSegment1 && vnl_resolve_dir('/controller/' . $originalSegment0) !== null)
    ? $originalSegment0 . '/' . $originalSegment1
    : $originalSegment0;
/* İlk parçanın listede olması (ör. 'app', ya da bir grubun adı) o adresin
   altındaki her şeyi açar — 3.3 ile aynı anlam.

   Hata sayfaları yalnız DOĞRUDAN istendiğinde açıktır. Bilinmeyen bir
   adres girişsiz ziyaretçiyi 404'e değil /login'e götürür: aksi halde
   404 ile 302 arasındaki fark, hangi sayfaların var olduğunu ele verirdi. */
$isExceptPage = in_array($currentPage, $loginExceptPages, true)
    || in_array($originalSegment0, $loginExceptPages, true)
    || in_array($vnlRequestedPage, ['401', '403', '404'], true);

unset($vnlSafeSegment, $vnlIsGrouped, $vnlRequestedPage);

if (!$isExceptPage && !vnl_logged_in()) {

    /* Uyumluluk kipi: VNL_PUBLIC_CAPTURE=true iken eski davranış
       (denetleyiciyi çalıştır, $page_config['public'] varsa çıktıyı bas).
       Yalnız geçiş dönemi içindir; yan etkili denetleyicide güvenli değildir. */
    if (VnlEnv::get('VNL_PUBLIC_CAPTURE', false) === true) {
        ob_start();
        try { require $controllerPath; } catch (Throwable $e) { ob_end_clean(); vnl_render_error($e); }
        $output = ob_get_clean();

        if (!empty($page_config['public'])) {
            error_log('[VNL][Kernel] ' . $currentPage . ': $page_config[public] is deprecated; '
                . 'use login_except_pages / vnl_public_page()');
            echo $output;
            return;
        }
    }

    /* AJAX isteği yönlendirmeyi HTML olarak alırdı; ona durum kodu verilir. */
    if (vnl_wants_json()) {
        http_response_code(401);
        vnl_json_header();
        echo json_encode(['message' => lang('ERR_LOGIN_FIRST'), 'devcode' => 'VNL_AUTH']);
        exit;
    }

    vnl_redirect_login();
}

try {
    require $controllerPath;
} catch (Throwable $e) {
    vnl_render_error($e);
}

/* 3.4.0 öncesi public kalıbını kullanan korumalı sayfa: davranış artık
   login_except_pages'e bağlı; geliştiriciye iz bırakılır. APP_DEBUG'dan
   bağımsızdır: canlıda sessizce girişe kapanan bir sayfanın tek geçiş
   işareti bu satırdır (F05.3). İstek başına bir kez. */
if (!$isExceptPage && !empty($page_config['public'])) {
    error_log('[VNL][Kernel] ' . $currentPage . ': $page_config[public] is deprecated; '
        . 'use login_except_pages / vnl_public_page()');
}

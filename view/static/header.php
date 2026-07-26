<?php
/**
 * VNL Theme sayfa iskeleti — üst yarı.
 *
 * Layout preset'i $page_config['layout'] ile seçilir; eski bayraklar
 * (leftsidebar/topbar/contentheader/footer) preset'i EZEBİLİR.
 * Merge sırası: varsayılanlar → preset → controller'ın $page_config'i.
 */

$page_config_default = array(
        'layout' => 'app',
        'leftsidebar' => true,   // true, false, 'mini' (sabit dar), 'mini-hover' (dar + hover'da genişler)
        'topbar' => true,
        'footer' => false,
        'loader' => true,
        'contentheader' => true,
);

$vnl_layout_presets = array(
        'app'     => array('leftsidebar' => true,  'topbar' => true,  'contentheader' => true,  'footer' => false),
        'top-nav' => array('leftsidebar' => false, 'topbar' => true,  'contentheader' => true,  'footer' => true),
        'landing' => array('leftsidebar' => false, 'topbar' => true,  'contentheader' => false, 'footer' => true),
        'focus'   => array('leftsidebar' => false, 'topbar' => false, 'contentheader' => false, 'footer' => false),
        'blank'   => array('leftsidebar' => false, 'topbar' => false, 'contentheader' => false, 'footer' => false, 'loader' => false),
);

$page_config = !isset($page_config) || !is_array($page_config) ? array() : $page_config;
$vnl_layout = $page_config['layout'] ?? 'app';
$vnl_preset = $vnl_layout_presets[$vnl_layout] ?? $vnl_layout_presets['app'];
$page_config = array_merge($page_config_default, $vnl_preset, $page_config);
$vnl_layout = $page_config['layout'];
?>
<!doctype html>
<html lang="tr" data-bs-theme="dark">
<head>
    <?php
    require_once view('includes/static/head-standard');
    require_once view('includes/static/script-standard');
    if (file_exists(view('includes/header/head-' . route(0))))
        require_once view('includes/header/head-' . route(0));

    /* Manuel CSS include'ları: dokümante edilmiş $page_config['css_files']
       ve tarihsel $css_files globali birlikte desteklenir. */
    $vnl_css_files = $page_config['css_files'] ?? ($css_files ?? array());
    foreach ($vnl_css_files as $item) {
        if (file_exists(view('includes/header/' . $item)))
            require_once view('includes/header/' . $item);
    }
    ?>
</head>
<?php
/* Daraltma düğmesinin hedef modu: 'mini' sabit dar, 'hover' üzerine
   gelince/tıklayınca genişleyen dar (varsayılan). */
$vnl_sidebar_mode = $page_config['leftsidebar'] === 'mini' ? 'mini' : 'hover';

/* Alt menü davranışı: 'flyout' sağda panel (varsayılan), 'accordion'
   alt alta liste. Daraltılmış rayda accordion da flyout'a düşer. */
$vnl_submenu = ($page_config['submenu'] ?? 'flyout') === 'accordion' ? 'accordion' : 'flyout';
?>
<body class="vnl-app" data-vnl-layout="<?= $vnl_layout ?>" data-vnl-sidebar-mode="<?= $vnl_sidebar_mode ?>"
      data-vnl-submenu="<?= $vnl_submenu ?>">

<?php if ($page_config['loader'] != false) { ?>
    <div id="loadingSpinner" class="vnl-spinner-overlay" style="display: none">
        <div class="vnl-spinner-box">
            <div class="vnl-spinner" role="status"></div>
            <div class="spinner-text"></div>
        </div>
    </div>
<?php } ?>

<?php
if ($page_config['leftsidebar'] != false) {
    /* 'mini' / 'mini-hover' başlangıcı: kullanıcı tercihi (localStorage)
       head'deki inline script ile zaten uygulandı; tercih yoksa controller'ın
       istediği daraltılmış modda başlat. */
    if ($page_config['leftsidebar'] === 'mini' || $page_config['leftsidebar'] === 'mini-hover') { ?>
        <script>
            if (!document.documentElement.hasAttribute('data-vnl-sidebar')) {
                document.documentElement.setAttribute('data-vnl-sidebar', '<?= $vnl_sidebar_mode ?>');
            }
        </script>
    <?php }
    require_once view('static/leftSidebar');
}
?>

<div class="vnl-main">
    <?php
    if ($page_config['topbar'] != false)
        require_once view('static/topNavbar');
    ?>
    <main class="vnl-content">
        <?php
        if ($page_config['contentheader'] != false)
            require_once view('static/contentHeader');

        if ($vnl_layout === 'focus') { ?>
            <div class="vnl-focus">
        <?php } ?>

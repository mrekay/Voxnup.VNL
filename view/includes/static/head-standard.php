<?php
/*
 * ============================================================
 *  Voxnup Not Laravel (VNL) — VNL Theme 1.0
 *  Standart <head>: pre-paint tema kurulumu + tema CSS'leri
 *  Authors: Voxnup Authors
 * ============================================================
 */
$vnl_theme_path = $assets_address . '/vnl';
?>
<!-- Pre-paint tema kurulumu: CSS'ten ÖNCE, senkron. localStorage anahtarı
     "data-bs-theme" Metronic dönemiyle aynıdır; kayıtlı tercih korunur.
     Kayıt yoksa VNL Theme varsayılanı KOYU'dur. -->
<script>
    (function () {
        var mode = null;
        try { mode = localStorage.getItem("data-bs-theme"); } catch (e) {}
        if (!mode) mode = "dark";
        if (mode === "system") {
            mode = window.matchMedia("(prefers-color-scheme: dark)").matches ? "dark" : "light";
        }
        document.documentElement.setAttribute("data-bs-theme", mode);
        try {
            var mini = localStorage.getItem("vnl-sidebar-mini");
            if (mini === "1") mini = "mini"; // eski kayıt biçimiyle uyum
            if (mini === "mini" || mini === "hover") {
                document.documentElement.setAttribute("data-vnl-sidebar", mini);
            }
        } catch (e) {}
    })();
</script>

<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="color-scheme" content="dark light">

<title><?php echo $meta['title'] ?? $cDefinitions->appName(); ?></title>
<meta name="keywords" content="<?php echo $meta['keywords'] ?? ''; ?>">
<meta name="description" content="<?php echo $meta['description'] ?? ''; ?>">

<link rel="icon" href="<?= $cDefinitions->appLogoPath() . 'VNL.png' ?>" type="image/png">
<link href="<?= $vnl_theme_path ?>/css/vnl.min.css" rel="stylesheet" type="text/css"/>
<?php
/* Skin: vnl.min.css üstüne yüklenen hafif renk katmanı. Uygulama geneli
   themeDefaults()['skin'] ya da sayfa bazında $page_config['skin'] ile. */
$vnl_skin = $page_config['skin'] ?? ($vnl_theme_defaults['skin'] ?? null);
if (!empty($vnl_skin)) { ?>
    <link href="<?= $vnl_theme_path ?>/css/skins/<?= htmlspecialchars($vnl_skin) ?>.min.css" rel="stylesheet" type="text/css"/>
<?php } ?>
<link href="<?= $vnl_theme_path ?>/css/bi.min.css" rel="stylesheet" type="text/css"/>
<link href="<?= $vnl_theme_path ?>/css/fa.min.css" rel="stylesheet" type="text/css"/>

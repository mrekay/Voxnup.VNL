<?php
/*
 * ============================================================
 *  Voxnup Not Laravel (VNL) — VNL Theme 1.0
 *  Standart <head>: pre-paint tema kurulumu + tema CSS'leri
 *  Authors: Voxnup Authors
 * ============================================================
 */
/* Bu dosya PROJENİNDİR; VNL güncellemesi ona dokunmaz. Yüklenecek çekirdek
   dosyalarının listesi ise çekirdekten gelir (vnl_core_styles), böylece VNL'e
   yeni bir CSS eklendiğinde burayı elle düzeltmek gerekmez. Sırayı ya da
   etiketleri değiştirmek isterseniz aşağıdaki döngü sizindir. */
$vnl_theme_path = vnl_asset('theme');

/* Skin: vnl.min.css üstüne yüklenen hafif renk katmanı. Uygulama geneli
   themeDefaults()['skin'] ya da sayfa bazında $page_config['skin'] ile. */
$vnl_skin = $page_config['skin'] ?? ($vnl_theme_defaults['skin'] ?? null);
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
<!-- CSRF jetonu (3.4.0): vnl.js ve VNL Table her POST'a X-CSRF-Token olarak ekler. -->
<meta name="csrf-token" content="<?= vnl_e(vnl_csrf_token()) ?>">

<title><?php echo $meta['title'] ?? $cDefinitions->appName(); ?></title>
<meta name="keywords" content="<?php echo $meta['keywords'] ?? ''; ?>">
<meta name="description" content="<?php echo $meta['description'] ?? ''; ?>">

<link rel="icon" href="<?= $cDefinitions->appLogoPath() . 'VNL.png' ?>" type="image/png">
<?php foreach (vnl_core_styles($vnl_skin) as $vnl_css) { ?>
<link href="<?= $vnl_css ?>" rel="stylesheet" type="text/css"/>
<?php } ?>

<?php
/*
 * Doküman sayfalarının altındaki "önceki / sonraki" bağlantıları.
 *
 * Sıra ayrıca tutulmaz: soldaki menüden ($SidebarMenu, view/static/leftSidebar.php)
 * okunur — menüde yer değiştiren sayfa burada da yer değiştirir. Menüde olmayan
 * ya da menünün çizilmediği bir sayfada hiçbir şey basmaz.
 */
$vnl_docs_flat = [];
$vnl_docs_walk = function (array $items) use (&$vnl_docs_walk, &$vnl_docs_flat) {
    foreach ($items as $item) {
        $url = (string)($item['url'] ?? '');
        if (str_starts_with($url, 'docs/') && (int)($item['showmenu'] ?? 0) !== 0) {
            $vnl_docs_flat[] = ['url' => $url, 'text' => (string)($item['text'] ?? $url)];
        }
        if (!empty($item['child']) && is_array($item['child'])) {
            $vnl_docs_walk($item['child']);
        }
    }
};
$vnl_docs_walk(isset($SidebarMenu) && is_array($SidebarMenu) ? $SidebarMenu : []);

$vnl_docs_current = trim((string)parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH), '/');
$vnl_docs_index   = array_search($vnl_docs_current, array_column($vnl_docs_flat, 'url'), true);

if ($vnl_docs_index !== false) {
    $vnl_docs_prev = $vnl_docs_flat[$vnl_docs_index - 1] ?? null;
    $vnl_docs_next = $vnl_docs_flat[$vnl_docs_index + 1] ?? null;
    ?>
    <div class="app-container container-xxl">
        <nav class="d-flex flex-stack flex-wrap gap-4 mb-12" aria-label="Doküman sayfaları">
            <div class="flex-grow-1" style="flex-basis: 0">
                <?php if ($vnl_docs_prev) { ?>
                    <a href="/<?= vnl_e($vnl_docs_prev['url']) ?>" class="card card-body p-6 text-start h-100 text-decoration-none">
                        <span class="text-muted fs-7 mb-1"><i class="bi bi-arrow-left me-1"></i>Önceki</span>
                        <span class="fw-bold fs-5 text-body-emphasis"><?= vnl_e($vnl_docs_prev['text']) ?></span>
                    </a>
                <?php } ?>
            </div>
            <div class="flex-grow-1" style="flex-basis: 0">
                <?php if ($vnl_docs_next) { ?>
                    <a href="/<?= vnl_e($vnl_docs_next['url']) ?>" class="card card-body p-6 text-end h-100 text-decoration-none">
                        <span class="text-muted fs-7 mb-1">Sonraki<i class="bi bi-arrow-right ms-1"></i></span>
                        <span class="fw-bold fs-5 text-body-emphasis"><?= vnl_e($vnl_docs_next['text']) ?></span>
                    </a>
                <?php } ?>
            </div>
        </nav>
    </div>
    <?php
}
unset($vnl_docs_flat, $vnl_docs_walk, $vnl_docs_current, $vnl_docs_index, $vnl_docs_prev, $vnl_docs_next);

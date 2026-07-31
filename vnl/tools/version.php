<?php
/*!
 * ============================================================
 *  Voxnup Not Laravel (VNL) — version komutu
 *  Authors: Voxnup Authors
 * ============================================================
 */

return static function (array $args): int {
    if (in_array('--help', $args, true) || in_array('-h', $args, true)) {
        fwrite(STDOUT, "\n  Cekirdek ve proje surumlerini gosterir.\n\n    php vnl.php version\n\n");

        return 0;
    }

    $core = is_file(VNL_PATH . '/VERSION')
        ? trim((string)file_get_contents(VNL_PATH . '/VERSION'))
        : '(vnl/VERSION yok)';

    /* Projenin sürümü ConfigDefinitions'ta ve o .env'e bağlı; yapılandırma
       eksikse bile bu komut çalışabilmeli, o yüzden hata yutuluyor. */
    $project = '(okunamadi)';
    try {
        require_once VNL_PATH . '/env.php';
        require_once VNL_ROOT . '/config/definitions.php';
        $project = (new ConfigDefinitions())->version();
    } catch (Throwable $e) {
        // yapılandırma yoksa proje sürümü de yoktur; çekirdeğinki yine de gösterilir
    }

    fwrite(STDOUT, "\n  Cekirdek (vnl/VERSION)      {$core}\n");
    fwrite(STDOUT, "  Proje   (config/…)          {$project}\n");
    fwrite(STDOUT, '  PHP                         ' . PHP_VERSION . "\n\n");

    return 0;
};

<?php
/*!
 * ============================================================
 *  Voxnup Not Laravel (VNL) — Komut Satırı Yardımcıları
 *  Konsol dağıtıcısı ve test alt süreçleri ortak kullanır.
 *  Authors: Voxnup Authors
 * ============================================================
 */

/**
 * Uygulamayı komut satırında ayağa kaldırır (bir kez).
 *
 * vnl/boot.php web ile aynıdır: .env, yapılandırma, otomatik yükleyici,
 * veritabanı (tembel bağlanır), modüller, dil. Oturum deposu kullanılmaz,
 * yönlendirme çalışmaz. Ardından çıktı tamponları boşaltılır, süre sınırı
 * kalkar ve hatalar stderr'e yazılır.
 *
 * Önyükleme bir FONKSİYONUN içinden yapıldığı için dosya kapsamında kurulan
 * değişkenler ($cDefinitions, $db, $CurrentUser, $req ...) global'e
 * bağlanır; projenin config/ dosyalarında kurduğu başka değişkenler de
 * sonradan $GLOBALS'a aktarılır.
 */
function vnl_cli_boot(): void
{
    static $__vnlBooted = false;

    if ($__vnlBooted) {
        return;
    }
    $__vnlBooted = true;

    global $cDefinitions, $db, $CurrentUser, $req, $route, $settings, $assets_address,
           $PdfPrintEnabled, $isSecure, $customSideBar;

    require_once VNL_PATH . '/boot.php';

    foreach (get_defined_vars() as $__name => $__value) {
        if (!str_starts_with($__name, '__') && !array_key_exists($__name, $GLOBALS)) {
            $GLOBALS[$__name] = $__value;
        }
    }

    while (ob_get_level() > 0) {
        ob_end_flush();
    }

    set_time_limit(0);
    ini_set('display_errors', 'stderr');
}

/** --anahtar=deger ve --bayrak seceneklerini diziye cevirir. */
function vnl_cli_options(array $args): array
{
    $options = [];

    foreach ($args as $arg) {
        if (!is_string($arg) || !str_starts_with($arg, '--')) {
            continue;
        }

        $pair = explode('=', substr($arg, 2), 2);
        $options[$pair[0]] = $pair[1] ?? true;
    }

    return $options;
}

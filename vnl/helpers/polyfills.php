<?php
/*!
 * ============================================================
 *  Voxnup Not Laravel (VNL) — Eksik PHP fonksiyonlarının yedekleri
 *  Authors: Voxnup Authors
 * ============================================================
 *
 * Paylaşımlı barındırmada mbstring kapalı olabiliyor. vnl/vendor'daki
 * symfony/polyfill-mbstring mb_substr/mb_strlen ve çoğu mb_* fonksiyonunu
 * sağlar, ama mb_strimwidth'i SAĞLAMAZ. Her yedek function_exists ile
 * korunur: gerçek eklenti varsa hiçbiri tanımlanmaz.
 */

/**
 * mb_strimwidth'in saf PHP karşılığı (testler gerçeğiyle karşılaştırır).
 *
 * Fark: genişlik KARAKTER sayısıdır. Gerçek fonksiyon tam genişlikli
 * (Doğu Asya) karakterleri 2 sayar; Latin/Türkçe metinde sonuç aynıdır.
 */
function vnl_mb_strimwidth(string $string, int $start, int $width, string $trim_marker = '', ?string $encoding = null): string
{
    $encoding ??= 'UTF-8';
    $length   = mb_strlen($string, $encoding);

    if ($start < 0) {
        $start = max(0, $length + $start);
    }

    $rest      = mb_substr($string, $start, null, $encoding);
    $restWidth = mb_strlen($rest, $encoding);

    if ($width < 0) {
        $width = max(0, $restWidth + $width);
    }

    if ($restWidth <= $width) {
        return $rest;
    }

    $markerWidth = mb_strlen($trim_marker, $encoding);
    if ($markerWidth >= $width) {
        return $trim_marker;   // gerçek fonksiyon da işareti kırpmaz
    }

    return mb_substr($rest, 0, $width - $markerWidth, $encoding) . $trim_marker;
}

if (!function_exists('mb_strimwidth')) {
    function mb_strimwidth(string $string, int $start, int $width, string $trim_marker = '', ?string $encoding = null): string
    {
        return vnl_mb_strimwidth($string, $start, $width, $trim_marker, $encoding);
    }
}

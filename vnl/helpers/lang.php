<?php
/*!
 * ============================================================
 *  Voxnup Not Laravel (VNL) — Dil Yardımcıları
 *  Dil anahtarlarına kısa erişim: lang() ve lang_has().
 *  Authors: Voxnup Authors
 * ============================================================
 */

/**
 * Dil anahtarını okur.
 *
 * Ek argümanlar sprintf yer tutucularını doldurur. Anahtar hiçbir dil dosyasında
 * yoksa anahtarın kendisi döner; hiçbir koşulda ölümcül hata vermez.
 *
 *   lang('ERR_GENERAL')                        → "Bir hata oluştu."
 *   lang('PLEASE_FILL_IN_FIELD', 'Ad Soyad')   → "Lütfen Ad Soyad alanını doldurun."
 *   lang('OLMAYAN_ANAHTAR')                    → "OLMAYAN_ANAHTAR"
 */
function lang(string $key, ...$args): string
{
    return Language::get($key, ...$args);
}

/** Anahtarın aktif dilde ya da temel dilde karşılığı var mı? */
function lang_has(string $key): bool
{
    return Language::has($key);
}

<?php
/*!
 * ============================================================
 *  Voxnup Not Laravel (VNL) — Yetki Kancası
 *  vnl_can() ve dolayısıyla menü, sayfa ve action kapıları
 *  bu fonksiyona sorar.
 *  Authors: Voxnup Authors
 * ============================================================
 *
 * PROJENİNDİR: yetki şeması projeden projeye değişir (rol, kiracı, ürün
 * ekseni ...). Bu dosya VNL'in varsayılan şemasını uygular:
 *
 *   - girişsiz ya da pasif kullanıcı: hiçbir yetki yok
 *   - perm_id = 1 (User::SUPER_ADMIN): her yetki
 *   - diğerleri: tbl_user_permissions'taki can_<yetenek> bayrağı
 *
 * Yetenekler: view, insert, edit, delete.
 *
 * Bu dosyayı silmek kapıyı AÇAR (kanca yoksa vnl_can() her koda true
 * döner — 3.1 uyumluluğu). `php vnl.php doctor` kancanın varlığını sınar.
 */

function vnl_project_permission(string $code, $user, string $ability = 'view'): bool
{
    if ($user === null || empty($user->id) || !UserModule::IsActiveUser($user)) {
        return false;
    }

    if ((int)$user->perm_id === User::SUPER_ADMIN) {
        return true;   // süper yönetici
    }

    return UserPermissions::Can((int)$user->id, $code, $ability);
}

<?php

/**
 * Extension Manifest
 *
 * Hangi extension'ların yükleneceğini buradan yönetin.
 *   true  → aktif, boot.php yüklenir
 *   false → pasif, hiçbir şey yüklenmez
 *
 * Klasör adı ile eşleşmelidir: extensions/{key}/boot.php
 */
return [
    'stripe'      => false,
    'social-auth' => false,  // Google/Apple/Microsoft/Facebook ile giriş — kanca: vnl_social_sign_in()
];

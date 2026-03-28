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
    'stripe' => false,
];

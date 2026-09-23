# Menü parçaları

Her `*.php` dosyası kenar çubuğuna bir öğe (ya da öğe listesi) ekler
(VNL 3.4.0). Dosyalar ad sırasıyla okunur: `100-domains.php`, `200-mail.php` ...

```php
<?php
return [
    "text"       => lang('DOMAINS_TITLE'),
    'url'        => 'domains',
    'page_name'  => 'domains.php',
    'permission' => 'Domains',       // vnl_can() — yetkisi olmayana görünmez
    'icon'       => 'bi bi-globe2',
    'showmenu'   => 2,               // 0 gizli · 1 her zaman · 2 girişliyken
    "child"      => [],
];
```

Sözleşme `view/static/leftSidebar.php`'deki `$SidebarMenu` öğesinin aynısıdır.
Modül başına ayrı dosya: paralel çalışan geliştiriciler tek menü dosyasında çakışmaz.

# Proje komutları

Bu dizin **projenindir**. Her dosya bir konsol komutudur (VNL 3.4.0):

```
commands/backup-prune.php   →   php vnl.php backup:prune
```

Dosya adındaki ilk `-` komut adında `:` olur; iki yazım da çalışır. Çekirdek
komut adları (`migrate`, `queue:work` ...) ezilemez.

```php
<?php
/** @summary Eski yedekleri siler */

return function (array $args): int {
    vnl_cli_boot();                       // modeller, Queue, Audit, lang() için

    $o    = vnl_cli_options($args);       // --days=7 → ['days' => '7']
    $days = (int)($o['days'] ?? 7);

    // ... iş ...

    fwrite(STDOUT, "tamam\n");

    return 0;                             // çıkış kodu
};
```

- `@summary` satırı `php vnl.php` yardım listesinde görünür.
- Uygulamaya ihtiyaç duymayan komut `vnl_cli_boot()` çağırmak zorunda değildir.
- Zamanlamak için `config/schedule.php`.

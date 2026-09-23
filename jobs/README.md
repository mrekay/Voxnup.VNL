# Kuyruk işleyicileri

Bu dizin **projenindir**. Her dosya bir iş türünün işleyicisidir (VNL 3.4.0):

```
jobs/domain.provision.php   ←   Queue::push('domain.provision', ['domain_id' => 7])
```

Tür adı `[a-z0-9._-]+`; dosya adı türün kendisidir.

```php
<?php

return function (array $payload, VnlJob $job): void {
    $job->progress(10, 'Başladı');        // 0–100, ilerleme ekranı Queue::status() ile okur
    $job->log('adım 1 tamam');            // çıktıya satır ekler

    // Hata = istisna. max_attempts dolana kadar 30 sn × deneme sonra yeniden
    // denenir, sonra 'failed'.
    if (!$ok) {
        throw new RuntimeException('Açıklayıcı hata');
    }
};
```

İşçi: `php vnl.php queue:work [--queue=default]` (systemd altında sürekli).

Kurallar:

- İşleyici **exit/die çağırmaz**, `returnErrorMessage()` kullanmaz — işçiyi öldürür.
- Dış komut gerekiyorsa `proc_open([...argüman dizisi...])` — asla kabuk dizgisi.
- Yük (payload) yönetim ekranında görünür: parola ya da gizli anahtar koymayın.
- `$job->createdBy()` işi kuyruğa yazan kullanıcıdır; `Audit::log()` kaydı ona düşer.

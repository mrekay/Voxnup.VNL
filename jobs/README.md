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

    // Dış komut: kabuksuz, beklerken nabız atar (uzun adımlar --stale'e takılmaz)
    $r = $job->exec(['nginx', '-t'], ['timeout' => 60]);
    if ($r['code'] !== 0) {
        throw new RuntimeException('nginx -t: ' . $r['stderr']);
    }

    // Hata = istisna. max_attempts dolana kadar 30 sn × deneme sonra yeniden
    // denenir, sonra 'failed'.
    if (!$ok) {
        throw new RuntimeException('Açıklayıcı hata');
    }
};
```

İşçi: `php vnl.php queue:work [--queue=default]` (systemd altında sürekli).

Kurallar:

- İşleyici **exit/die çağırmaz**, `returnErrorMessage()` kullanmaz — işçiyi öldürür
  (işçi kapanırken işi başarısız deneme olarak yazar, ama işleyicinin geri kalanı
  çalışmaz). Doğrulayıcılar komut satırında istisna fırlatır; kullanmak güvenlidir.
- Dış komut gerekiyorsa `$job->exec([...])` ya da `proc_open([...argüman dizisi...])`
  — asla kabuk dizgisi. Uzun ve sessiz bir adımın döngüsünde `$job->heartbeat()`:
  `--stale` saniye nabız atmayan iş çökmüş sayılır.
- `VnlJobLostException` yutulmaz: iş başka bir işçiye geçmiştir, yan etkiler durmalı.
- `max_attempts > 1` yalnız ikinci kez çalıştırılması güvenli (idempotent) işlerde.
- Dosya işçi süreci başına **bir kez** yüklenir: dosya düzeyindeki kod bir kez çalışır,
  fonksiyon/sınıf tanımları güvenlidir; değişiklik işçi yeniden başlayınca gelir.
- Yük (payload) yönetim ekranında görünür: parola ya da gizli anahtar koymayın.
- `$job->createdBy()` işi kuyruğa yazan kullanıcıdır; `Audit::log()` kaydı ona düşer.

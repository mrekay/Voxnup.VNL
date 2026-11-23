# Projenin testleri

`php vnl.php test` iki yerdeki testleri koşar:

| Yer | Sahibi |
| --- | --- |
| `vnl/tests/<takım>/*Test.php` | Çekirdek. Projeye kurulu hâliyle, projenin ezdiği dosyalara karşı koşar. |
| `tests/<takım>/*Test.php` | Proje (bu dizin). Güncelleme dokunmaz. |

Takımlar:

- **`unit`**: süreç içinde, veritabanı olmadan çalışır.
- **`http`**: komutun açtığı yerel sunucuya gerçek istek atar.
- **`db`**: veritabanı yoksa testler atlanır.

Her test dosyası ayrı bir PHP sürecinde koşar. Bir test `exit` ile biterse yalnızca o
dosya düşer, çalıştırıcı da hangi testte düştüğünü söyler.

```
php vnl.php test                     hepsi
php vnl.php test --suite=unit        yalnızca bir takım
php vnl.php test --filter=Fatura     adında bu geçenler
php vnl.php test --project           yalnızca bu dizindekiler
```

Bu makinede PHP yoksa ya da veritabanıyla birlikte koşmak istiyorsanız:

```
docker compose -f compose.test.yml run --rm test           # MySQL 8.4
docker compose -f compose.test.yml run --rm test-pgsql     # PostgreSQL 17
docker compose -f compose.test.yml run --rm test-sqlsrv    # SQL Server 2022
docker compose -f compose.test.yml run --rm test-sqlite    # SQLite
```

## Test yazmak

```php
<?php
// tests/unit/FaturaTest.php

final class FaturaTest extends VnlTestCase
{
    public function testKdvHesabi(): void
    {
        $this->assertSame(120.0, Fatura::kdvli(100.0));
    }
}
```

`http` testleri `VnlHttpTestCase`'ten türer. `$this->get()`, `$this->post()` ve
`$this->csrfToken()` metotları oturum çerezini taşır. `db` testleri ilk satırda
`$this->requireDatabase()` çağırır. Örnekler `vnl/tests/` altında, temel sınıflar
`vnl/tests/lib/` altındadır.

Doğrulamalar: `assertSame`, `assertNotSame`, `assertTrue`, `assertFalse`,
`assertNull`, `assertNotNull`, `assertContains`, `assertNotContains`,
`assertThrows`. Yardımcılar: `fail`, `skip`.

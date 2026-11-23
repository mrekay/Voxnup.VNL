# Migrations — çekirdeğin şeması

Bu dizin **VNL'indir**; güncellemede yenilenir. Kendi şema değişiklikleriniz
kök dizindeki `migrations/` altına girer. İki sayaç birbirinden bağımsız
yürür, numaralarınız çakışmaz.

## Çalıştırma

```bash
php vnl.php migrate --status   # ne uygulanmış, ne bekliyor
php vnl.php migrate --run      # bekleyenleri sırayla uygula
```

Araç uygulananları veritabanında `tbl_vnl_migrations` tablosunda tutar; aynı
migration ikinci kez çalıştırılmaz. Ayrıntı için uygulama içindeki
**/docs/versioning** sayfasına bakın.

## Sürücüler (4.1.0)

Her numaranın dört hâli vardır; araç `DB_DRIVER`'a göre seçer:

```
0003_vnl_rate_limits.sql          MySQL / MariaDB (4.1.0 öncesindeki dosyanın aynısı)
0003_vnl_rate_limits.pgsql.sql    PostgreSQL
0003_vnl_rate_limits.sqlsrv.sql   SQL Server
0003_vnl_rate_limits.sqlite.sql   SQLite
```

Sürücüye özel hâl (`NNNN_ad.<sürücü>.sql`) o sürücüde düz dosyanın yerine
geçer; hâli olmayan sürücüde düz dosya çalışır. Kayıt (kaynak, numara) ile
tutulur, ad son eksizdir: MySQL kurulumları uyguladıkları dosyaları aynen
görür. Düz `.sql` dosyaları **değişmedi** (özetleri aynı).

MySQL dışındaki sürücülerde bir migration ve kaydı tek işlemdir (transaction);
düşen migration geri alınır.

- **`0001` MySQL dışında:** 2.x'ten gelen bir kurulum yoktur ama projenin kendi
  `tbl_users`'ı olabilir. PostgreSQL ve SQL Server hâlleri parola sütununu
  yalnız varsa ve 255'ten darsa genişletir (türü ve NULL kabulü korunur);
  `tbl_remember_tokens` ve `tbl_permissions` yoksa açılır.
- **`0002` MySQL dışında:** tablo varsa dokunulmaz, eksik sütunlar eklenir
  (PostgreSQL `ADD COLUMN IF NOT EXISTS`, SQL Server `COL_LENGTH`). Var olan
  sütunun türü değişmez.
- **SQLite:** koşullu `ADD COLUMN` yoktur; SQLite hâlleri sıfırdan kuruluma göre
  yazılmıştır (`totp_last_step` ve `auth_stamp` tablolarla birlikte açılır,
  `0007`/`0008` boştur). Projenin önceden açtığı tablolara sütun eklenmez.

## Kurallar

- Dosya adı `NNNN_kisa_aciklama.sql` biçimindedir; numara dört haneli ve artan.
  Sürücüye özel hâl `NNNN_kisa_aciklama.<mysql|pgsql|sqlsrv|sqlite>.sql`.
- SQL Server dosyalarında `GO` yazılmaz; her ifade ayrı toplu iştir. Koşullu iş
  tek ifadede yapılır (`IF OBJECT_ID(…) IS NULL CREATE TABLE …`). Metin `N'…'`.
- Bir migration yayımlandıktan sonra **düzenlenmez**; düzeltme için yeni numara
  açılır. Araç dosya özetini saklar ve sonradan değişeni `[!]` ile bildirir.
- Her migration hem sıfırdan kurulumda hem mevcut kurulumda çalışabilmelidir:
  tablo oluştururken `CREATE TABLE IF NOT EXISTS` kullanın.
- **`ALTER TABLE`'ın MySQL'de `IF EXISTS`'i yoktur.** Var olmayabilecek bir
  tabloyu değiştiriyorsanız koşullu yazın; `0001` bunun örneğidir
  (`information_schema` + `PREPARE`/`EXECUTE`). Düz bir `ALTER`, migration'ı
  sıfırdan kurulumda çökertir.
- Sütun sözleşmesi: `snake_case`, birincil anahtar `id`, yumuşak silme `deleted_at`.

## VNL 3.0'a yükseltiyorsanız

`0001` zorunludur. İki kritik değişiklik içerir:

- `tbl_users.password` → `VARCHAR(255)`. VNL 3.0 bcrypt kullanır (60 karakter);
  2.x'in `sha1(md5())` hash'i için 40 karakter yetiyordu. Sütun dar kalırsa
  hash sessizce kırpılır ve kullanıcı bir sonraki girişinde içeri giremez.
- `tbl_remember_tokens` — "beni hatırla" jetonları. Tablo yoksa kalıcı giriş
  çalışmaz.

`.sql` dosyaları web sunucusu tarafından servis edilmez (`.htaccess` ve
`web.config` engeller).

## VNL 3.4.0'a yükseltiyorsanız

`0002`–`0007` hem sıfırdan hem mevcut kurulumda çalışır:

| No | Ne |
| --- | --- |
| `0002` | `tbl_users` (yoksa) + eksik sütunlar koşullu (`is_active`, `last_*`, `totp_*`, zaman damgaları), `tbl_permissions` zaman damgaları, `tbl_user_permissions`, `Users` yetki kodu |
| `0003` | `tbl_vnl_rate_limits` — giriş hız sınırı (`RateLimiter`) |
| `0004` | `tbl_vnl_audit_log` — denetim kaydı (`Audit::log`) |
| `0005` | `tbl_vnl_jobs` — iş kuyruğu (`Queue`, `queue:work`) |
| `0006` | `tbl_vnl_api_tokens` — API jetonları (`Authorization: Bearer`) |
| `0007` | `tbl_users.totp_last_step` — TOTP tekrar kullanım koruması (koşullu). Uygulanmadan iki adımlı giriş kapalı kalır |

İlk yönetici SQL ile tohumlanmaz (herkesin bildiği parola her kurulumda açık
kapı olurdu):

```bash
php vnl.php user:create --username=admin --super
```

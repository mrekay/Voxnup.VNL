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

## Kurallar

- Dosya adı `NNNN_kisa_aciklama.sql` biçimindedir; numara dört haneli ve artan.
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

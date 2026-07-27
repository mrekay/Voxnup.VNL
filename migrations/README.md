# Migrations

Veritabanı değişiklikleri burada, **numara sırasıyla** çalıştırılır.

```
0001_vnl_3_0_upgrade.sql
0002_...
```

## Kurallar

- Dosya adı `NNNN_kisa_aciklama.sql` biçimindedir; numara dört haneli ve artan.
- Bir migration yayımlandıktan sonra **düzenlenmez**; düzeltme için yeni numara açılır.
- Her migration hem sıfırdan kurulumda hem mevcut kurulumda çalışabilmelidir:
  tablo oluştururken `CREATE TABLE IF NOT EXISTS` kullanın.
- Sütun sözleşmesi: `snake_case`, birincil anahtar `id`, yumuşak silme `deleted_at`.

## Çalıştırma

```bash
mysql -u KULLANICI -p VERITABANI < migrations/0001_vnl_3_0_upgrade.sql
```

`.sql` dosyaları web sunucusu tarafından servis edilmez (`.htaccess` ve
`web.config` engeller), ama yine de yalnızca dağıtım sırasında erişilmeleri
beklenir.

## VNL 3.0'a yükseltiyorsanız

`0001` zorunludur. İki kritik değişiklik içerir:

- `tbl_users.password` → `VARCHAR(255)`. VNL 3.0 bcrypt kullanır (60 karakter);
  2.x'in `sha1(md5())` hash'i için 40 karakter yetiyordu. Sütun dar kalırsa
  hash sessizce kırpılır ve kullanıcı bir sonraki girişinde içeri giremez.
- `tbl_remember_tokens` — "beni hatırla" jetonları. Tablo yoksa kalıcı giriş
  çalışmaz.

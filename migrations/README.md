# Migrations — projenin şeması

Bu dizin **sizindir**. Kendi tablolarınızın değişiklikleri buraya, numara
sırasıyla girer:

```
0001_siparis_tablosu.sql
0002_...
```

**VNL'in kendi şeması burada değildir** — o `vnl/migrations/` altındadır ve
çekirdek güncellendiğinde yenilenir. İkisini ayrı tutmak, bir VNL yükseltmesinin
sizin migration numaralarınızla çakışmasını engeller.

## Kurallar

- Dosya adı `NNNN_kisa_aciklama.sql` biçimindedir; numara dört haneli ve artan.
- Bir migration yayımlandıktan sonra **düzenlenmez**; düzeltme için yeni numara açılır.
- Her migration hem sıfırdan kurulumda hem mevcut kurulumda çalışabilmelidir:
  tablo oluştururken `CREATE TABLE IF NOT EXISTS` kullanın.
- Sütun sözleşmesi: `snake_case`, birincil anahtar `id`, yumuşak silme `deleted_at`.

- **`ALTER TABLE`'ın MySQL'de `IF EXISTS`'i yoktur.** Var olmayabilecek bir
  tabloyu değiştiriyorsanız koşullu yazın; örneği `vnl/migrations/0001`'dedir.

## Çalıştırma

Elle `mysql <` ile değil, araçla — böylece neyin uygulandığı kayda geçer ve
aynı migration ikinci kez çalışmaz:

```bash
php vnl/tools/migrate.php --status   # ne uygulanmış, ne bekliyor
php vnl/tools/migrate.php --run      # bekleyenleri sırayla uygula
```

Ayrıntı için uygulama içindeki **/docs/versioning** sayfasına bakın.

`.sql` dosyaları web sunucusu tarafından servis edilmez (`.htaccess` ve
`web.config` engeller), ama yine de yalnızca dağıtım sırasında erişilmeleri
beklenir.

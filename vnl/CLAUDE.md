# Buradasınız: VNL çekirdeği

**Bu dizin projeye ait değildir.** `php vnl.php update` çalıştığında `vnl/` komple
değiştirilir; buraya yazdığınız her şey kaybolur.

## Bir çekirdek dosyasını değiştirmeniz gerekiyorsa

Burada düzenlemeyin. **Aynı adlı dosyayı proje tarafına koyun** — çözümleme önce
oraya bakar, buradaki hiç yüklenmez:

```
vnl/classes/Request.php    →  classes/Request.php
vnl/modules/MailModule.php →  modules/MailModule.php
vnl/helpers/app.php        →  helpers/app.php
vnl/validators/Foo.php     →  validators/Foo.php
```

`php vnl.php update --verify` kurulu çekirdeği `vnl/MANIFEST`'e karşı sınar ve
değiştirilmiş dosya bulursa güncellemeyi durdurur — yani buradaki bir düzenleme
sessizce kaybolmaz, ama güncellemeyi de bloke eder.

**Hata sayfası için dosya kopyalamayın.** Canlıda kullanıcıya ayrıntılı sayfa
yerine kendi ekranınızı bastırmak istiyorsanız `helpers/` altında
`vnl_project_error_page(Throwable $e)` tanımlayın: `error.php` çıktısını basmadan
önce onu çağırır, fonksiyon dönerse çekirdeğin sayfasına düşer. Sözleşmesi ve
örneği `/docs/errors` → *Üretim İçin*.

## VNL'in kendisi üzerinde çalışıyorsanız

O zaman burası doğru yer. İki şeyi unutmayın:

**Sürüm numarası dosya başlıklarına yazılmaz.** Tek doğruluk kaynağı `vnl/VERSION`.
Banner'lar `Voxnup Not Laravel (VNL) — <konu>` biçimindedir, numara taşımaz.

**`vnl/MANIFEST` yayın anında üretilir.** Buradaki herhangi bir dosyayı
değiştirdiğinizde eskir ve `update --verify` haklı olarak "değişmiş" der.
Yayına çıkarken `php vnl.php release` onu tazeler.

## Yerleşim

```
boot.php       önyükleme       kernel.php     yönlendirme + giriş denetimi (denetleyiciden ÖNCE)
bootstrap.php  otomatik yükleyici, oturum, kademeli yükleme
env.php        .env okuyucu    error.php      hata sayfası
template.php   {{ }} derleyicisi
console.php    komut dağıtıcısı — komutlar tools/ altında
classes/  modules/  helpers/  validators/  exceptions/
           classes/VnlDispatcher   /app/* dağıtıcısı (POST, CSRF, varsayılan giriş)
           classes/RateLimiter Audit Totp Queue VnlJob VnlCron ApiToken (3.4.0)
assets/   theme/ (Voxnup.VNL.Theme derleme çıktısı) + js/ + css/
migrations/    VNL'in şeması (projeninki kökteki migrations/)
tools/         migrate, update, doctor, release, version, serve, tar, dev-router
vendor/        Illuminate/Eloquent, PHPMailer
```

**Çekirdek görünüm ve dil dosyası taşımaz.** `view/` ve `languages/` burada yoktur ve
olmamalıdır: düzen ile metin her projede değişir, herkesin ezdiği bir varsayılanın
sahibi olmak sahte sahipliktir.

Yeni ÇEKİRDEK komutu eklemek için `tools/<ad>.php` açın (`return function (array $args): int`
döndürsün) ve `console.php`'deki listeye bir satır yazın. Uygulamaya ihtiyacı olan
komut ilk satırında `vnl_cli_boot()` çağırır. Proje komutları buraya değil kökteki
`commands/` dizinine girer (3.4.0).

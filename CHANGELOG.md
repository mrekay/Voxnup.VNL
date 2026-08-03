# Değişiklik Günlüğü

Bu dosya VNL **çekirdeğinin** sürüm geçmişidir. Türetilmiş projeler kendi
değişikliklerini kendi günlüklerinde tutar.

Biçim [Keep a Changelog](https://keepachangelog.com/tr/1.1.0/), sürümleme
[Semantic Versioning](https://semver.org/lang/tr/). Sürüm politikası ve neyin
kırıcı sayıldığı için **/docs/versioning** sayfasına bakın.

---

## [3.0.1] — 2026-08-03

### Değişenler

- **Yeni VNL logosu.** Maskotun yüzü yerini devre izleri arasına yerleşmiş "VNL"
  yazısına bıraktı; geniş kilit (ikon + *Voxnup*) da yeni ikonla yeniden üretildi.
  Dosyalar aynı adlarda kaldı, web boyutlarına indirildi ve palet PNG olarak
  yeniden kodlandı:

  | Dosya | Önce | Sonra |
  | --- | --- | --- |
  | `vnl/media/VNLMASCOT.png` | 1664², 92 KB | 256², 11 KB |
  | `vnl/media/VNLBIG.png` | 4500×1800, 168 KB | 1800×720, 63 KB |
  | `assets/media/logos/VNL.png` | 1668², 102 KB | 512², 30 KB |
  | `assets/media/logos/VNLBIG.png` | 4500×1800, 168 KB | 1800×720, 63 KB |

  `VNLMASCOT.png` hata sayfasına base64 olarak gömüldüğü için kazanç doğrudan
  yanıt boyutuna yansıyor: her hata sayfası ~110 KB hafifledi.

  **`assets/` proje tarafındadır, güncelleyici oraya dokunmaz.** `update` yalnız
  `vnl/media/` altındaki iki dosyayı yeniler; yeni logoyu uygulamanızın giriş,
  401/404 ve menü ekranlarında da görmek istiyorsanız `assets/media/logos/`
  altındaki iki dosyayı elle kopyalayın. Kendi logonuzu koyduysanız zaten bir şey
  yapmanız gerekmez.

---

## [3.0.0] — 2026-07-31

VNL 2.x'ten gelen kurulumlar için **kırıcı** bir sürüm. Yükseltmeden önce
aşağıdaki *Kırıcı Değişiklikler* bölümünü baştan sona okuyun; çoğu adım
mekaniktir ama birkaçı veri kaybına yol açabilir.

**Bu sürüme `php vnl.php update` ile geçilemez.** Güncelleyici 3.0 ile
*gelir*, 3.0'a *götürmez*: 2.x'te ne `vnl/` dizini, ne konsol, ne de
`MANIFEST` vardır. 2.x → 3.0 geçişi elle yapılır — aşağıdaki yol tablosunu
uygulayın, sonra `php vnl.php doctor` ile kontrol edin. 3.0'dan sonraki
sürümlere geçiş güncelleyiciyle olur.

**Şema:** `vnl/migrations/0001_vnl_3_0_upgrade.sql` zorunludur.
`php vnl.php migrate --run` ile uygulayın.

### Kırıcı Değişiklikler

**Dil sistemi** — `Language` sınıfındaki statik özellikler kaldırıldı.
Sözlük artık `languages/*.json` dosyalarından okunur ve tek doğruluk kaynağı
odur; sınıfın içinde anahtar tanımı tutulmaz.

```php
Language::$ERR_GENERAL      →  lang('ERR_GENERAL')
```

Tanımsız bir anahtar artık ölümcül hata vermez, anahtarın kendisini döndürür.

**Girdi kaçışı çıkışa taşındı** — `Request` gelen veriyi artık kaçışlamıyor.
Veritabanına ne gönderildiyse o yazılıyor; XSS koruması ekrana basarken
yapılıyor. Bunun doğrudan sonucu: **`<?= $veri ?>` artık kaçışsız çıktıdır.**

```php
<?= $veri ?>        →  {{ $veri }}     (ya da <?= vnl_e($veri) ?>)
```

Bu değişiklik sessizdir: eski kod çalışmaya devam eder ama kaçışsız basar.
Yükseltirken şablonlarınızı tarayın.

**Parola saklama** — `sha1(md5())` yerine `password_hash()` (bcrypt).

```php
EncryptionModule::PasswordEncrypt($p)   →  EncryptionModule::PasswordHash($p)
                                           EncryptionModule::PasswordVerify($p, $hash)
```

Eski hash'ler ilk başarılı girişte sessizce yenilenir. **`tbl_users.password`
sütunu `VARCHAR(255)` olmalıdır** — 40 karakterde kalırsa bcrypt hash'i
kırpılır ve kullanıcı bir daha giriş yapamaz. Migration `0001` bunu yapar.

**Simetrik şifreleme** — `safeEncrypt`/`safeDecrypt` kimliği doğrulanmış
AES-256-GCM'e geçti ve anahtarı `.env`'deki `APP_ENCRYPTION_KEY`'den alıyor.
**Eski yöntemle şifrelenmiş veriler çözülemez.** Yükseltmeden önce çözüp
yeniden şifreleyin.

**Çekirdek/proje ayrımı** — `libs/` dizini tamamen dağıldı. Çekirdeğe ait her
şey `vnl/` altına indi, geri kalanı proje tarafında toplandı:

| VNL 2.x | VNL 3.0 |
| --- | --- |
| `libs/config.php` | `vnl/boot.php` |
| `libs/config/config_soft.php` | `vnl/bootstrap.php` |
| `libs/config/config_definitions.php` | `config/definitions.php` |
| `libs/config/config_db.php` | `config/database.php` |
| `libs/helper/` | `vnl/helpers/` |
| `libs/helper/special/` | `helpers/` |
| `libs/modules/` | `vnl/modules/` |
| `libs/languages/` | `languages/` |
| `libs/vendor/` | `vnl/vendor/` |
| `classes/` (çekirdek olanlar) | `vnl/classes/` |
| `exceptions/` | `vnl/exceptions/` |
| `assets/vnl/` | `vnl/assets/theme/` |
| `assets/scripts/vnl.js` | `vnl/assets/js/vnl.js` |
| `assets/styles/vnltable.css` | `vnl/assets/css/vnltable.css` |

Yönlendirme `index.php`'den `vnl/kernel.php`'ye taşındı; kökteki `index.php`
artık yalnızca çekirdeği yükleyip isteği devrediyor. Kendi `index.php`'nizi
özelleştirdiyseniz o özelleştirmeyi taşımanız gerekir.

**Ortam yapılandırması** — `$testMode` anahtarı kaldırıldı. İki sabit blok
arasında seçim yapmak yerine her ortam kendi `.env` dosyasını taşıyor.
`.env.example`'ı kopyalayıp doldurun.

**Tema** — Metronic bağımlılığı tamamen kaldırıldı, yerine Bootstrap 5.3
üzerine kurulu VNL Theme geldi. Düzen parçaları (`view/static/`,
`view/includes/static/`) yeniden yazıldı. Metronic sınıf adlarına dayanan
özel sayfalarınız varsa **/docs/theme-migration** sayfasına bakın.

JS köprülerinde ad değişiklikleri:

```
select2Reload()  →  selectReload()      (.select2 sınıfı hâlâ tanınır)
ClearForm()      →  clearForm()
checkValidity()  →  validateForm()
checkMobileBrowser()  →  kaldırıldı; JS tarafında isMobile()
```

**SDTable ve Excel Export emekli** — yerlerini VNL Table aldı.
`/docs/sdtable` ve `/docs/excel-export` adresleri `/docs/vnltable`'a
yönlendiriyor. PHPExcel ve DataTables bağımlılıkları kaldırıldı; Excel
çıktısı artık bağımlılıksız bir xlsx yazıcısıyla üretiliyor.

### Eklenenler

- **VNL Table** — üç kademeli tablo sistemi: `list` (okunan liste),
  `grid` (başlıktaki huniden açılan Excel tarzı filtre panelleri, filtre
  çipleri, çoklu sıralama, alt toplamlar, URL senkronu) ve `workbench`
  (sütun sabitleme, çok seviyeli gruplama, hücre düzenleme, detay satırı,
  seçim + toplu işlem, sütun sürükleyip sıralama, çapraz tablo).
- **VNL Theme 1.0** — koyu varsayılan kimlik, birinci sınıf açık tema, layout
  preset'leri, skin sistemi ve uygulama içi el kitabı. Kaynağı ayrı depoda
  ([VNL-THEME](https://github.com/mrekay/VNL-THEME)); derlenmiş çıktı bu
  repoda gelir, kurulumda Node/npm gerekmez.
- **`{{ }}` şablon söz dizimi** — otomatik kaçışlı çıktı, `@if`, `@foreach`,
  `@include` direktifleri. Söz dizimi kullanmayan dosyalara dokunulmaz.
- **`.env` yapılandırması** — dış bağımlılıksız okuyucu. Sunucu seviyesinde
  tanımlı gerçek ortam değişkeni `.env`'i ezer.
- **"Beni hatırla" jetonu** — çerezde jetonun kendisi, veritabanında yalnızca
  SHA-256 özeti durur. Her kullanımda yenilenir.
- **Çekirdek/proje ayrımı** — `vnl/` ile kök arasındaki sınır; aynı adlı
  dosyayla çekirdeği ezme. Bkz. **/docs/architecture**.
- **Konsol** — `php vnl.php`. Tek giriş noktası: `migrate`, `update`,
  `doctor`, `version`, `serve`, `release`. Windows için `vnl.cmd` kısayolu.
- **Migration çalıştırıcısı** — `php vnl.php migrate`; uygulananları
  veritabanında tutar, bekleyenleri sırayla uygular.
- **Güncelleyici** — `php vnl.php update`. Yayın arşivini indirir,
  `MANIFEST`'e karşı doğrular, `vnl.bak/` yedeğini alır ve yalnız içeriği
  değişen dosyaları yazar. Kurulu çekirdek kurcalanmışsa üzerine yazmadan
  önce durur. `--rollback` ile geri dönülür.
- **`php vnl.php doctor`** — projenin tohumlanmış dosyalarını (index.php,
  config/, languages/, view/static/) çekirdeğin beklentisine karşı sınar.
  Güncelleme proje tarafına dokunamadığı için bu boşluğu o kapatır.
- **Hata sayfası** — kaynak kod bağlamı, çağrı zinciri ve ortam bilgisiyle;
  AJAX isteklerinde JSON döner.
- **`Permission` ve `MailObject` sınıfları** — çağrılıyor ama tanımlı
  olmadıkları için ölümcül hata veriyorlardı.

### Değişenler

- Sürüm numarası ikiye ayrıldı: çekirdeğinki `vnl/VERSION`, projeninki
  `config/definitions.php`. Eskiden ikisi aynı dosyadaydı ve çekirdeğinkinin
  yanında "değiştirmeyin" yazıyordu.
- `AsInt` ayrıştırması `TextGeneralModule` üzerinden tek noktaya toplandı.
- `vnl.js` 452 satırdan 230 satıra indi; tema davranışları tema paketine geçti.
- Sol menü alt öğeleri sağ tarafta açılan panel (flyout) ya da akordeon
  olarak yapılandırılabiliyor.

### Düzeltilenler

- **Oturum düz HTTP üzerinde hiç kurulmuyordu.** Çerez `secure` +
  `SameSite=None` olarak sabitlenmişti; `SameSite=None` `Secure`'u zorunlu
  kılıyor, `Secure` çerez de HTTP üzerinde tarayıcıda saklanmıyor. Giriş
  dahil hiçbir oturum çalışmıyordu. Artık bağlantıya göre belirleniyor.
- Şablonun taşıdığı tanımsız referanslar: `UserFriends::ReloadFriends()`
  (hiç var olmamıştı, girişli her istekte ölümcül hataya düşüyordu),
  `Permission`, `MailObject`, `validateEmpty()`.
- `Language` dil dosyasını eski dizinden okumaya devam ediyordu; anahtar
  bulunamayınca ham anahtar basılıyordu.
- `site_url()` ve `public_url()` tanımsız bir `URL` sabitine dayanıyordu —
  çağrıldıkları anda ölümcül hata veriyorlardı.
- `view/logout.php` bir görünümün içinden önyüklemeyi yeniden yüklüyordu.
- Excel çıktısında `JSON_NUMERIC_CHECK` "007" gibi baştan sıfırlı kodları
  sayıya çeviriyordu.

### Kaldırılanlar

- Metronic paketleri ve öksüz CSS (~7 MB)
- DataTables (`datatable.js`, 56 KB) ve PHPExcel
- SDTable sınıfı ve `/docs/sdtable`, `/docs/excel-export` sayfaları
- `checkMobileBrowser()`
- `$testMode` yapılandırma anahtarı
- Öksüz script'ler: `assets/scripts/jquery-standalone.js` (85 KB — tema
  paketi kendi jQuery'sini taşıyor), `price-input-mask.js`, `panel-fixed.js`.
  Hiçbirinin çağıranı yoktu.
- `actions/action_datatableMultipleSelect.php` ve `files/template/GenelDataTable.xlsx`
  — DataTables kalıntısı. Bu dosya `PHPEXCELMODE` sabitini **tanımlayan tek
  yerdi**; sabiti okuyan denetimler `vnl/helpers/app.php`'de duruyor, artık
  isteyen projenin kendisi tanımlar.

### Güvenlik

- Parolalar bcrypt ile saklanıyor; eski hash'ler girişte yenileniyor.
- `safeEncrypt` kimliği doğrulanmış AES-256-GCM kullanıyor.
- "Beni hatırla" çerezi artık düz parola taşımıyor. **VNL 2.x'in `{prefix}auth`
  çerezi kullanıcı adını ve DÜZ PAROLAYI taşıyordu.** Kod o çerezi her istekte
  siliyor, ama o parolalar tarayıcılarda dolaştığı için kullanıcılara parola
  değiştirtmek doğru olur.
- `.htaccess` nokta ile başlayan yolları ve `.sql`/`.log`/`.lock` uzantılarını
  engelliyor.

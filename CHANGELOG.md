# Değişiklik Günlüğü

Bu dosya VNL **çekirdeğinin** sürüm geçmişidir. Türetilmiş projeler kendi
değişikliklerini kendi günlüklerinde tutar.

Biçim [Keep a Changelog](https://keepachangelog.com/tr/1.1.0/), sürümleme
[Semantic Versioning](https://semver.org/lang/tr/). Sürüm politikası ve neyin
kırıcı sayıldığı için **/docs/versioning** sayfasına bakın.

---

## [3.2.0] — 2026-09-04

Üç gerçek hata düzeltildi (ikisi güvenlik), bir eksik kapatıldı ve dokümanda
var olup kodda olmayan üç API'nin hesabı görüldü. **Kırıcı değişiklik yoktur:**
yeni bir şey tanımlanmadığı sürece davranış 3.1.0 ile birebir aynıdır — tek
istisna aşağıda "boş üst menü" başlığında açıkça yazılıdır.

### Eklenenler

- **VNL Table hücre biçimlendiricileri — `format`.** Sütun artık bir hücrenin
  nasıl görüneceğini *bildirebiliyor*: `badge`, `dot`, `icon`, `bool`, `link`.

  **Neden.** VNL Table'ın kurucu kuralı "hücre ham değer taşır"dır — sıralama,
  filtre, gruplama, pivot ve Excel çıktısı ham değer olmadan doğru çalışamaz.
  Ama sütun sözleşmesinde hücre biçimlendirici *yoktu*: `formatter`,
  `cellRender`, `cellRenderer` üçü de sıfır kez geçiyor, `render` yalnız
  `text|html` alıyordu. Sonuç, kuralın kendi kendini yemesiydi — bir hücrede
  rozet göstermenin **tek** yolu değerin YERİNE HTML göndermekti
  (`render => 'html'`). Veri biçime feda oluyor, kaçış sorumluluğu da her
  eylem dosyasına dağılıyordu. Bu şablonun taşındığı 12 ürünlük bir pakette
  bunun ölçüsü şu: 90 dosyada 328 rozet, 93 dosyada satır kimliğini hücreye
  gizli `<input>` olarak sokan bir kaçamak.

  `format` dengeyi tersine çevirir: **değer ham kalır**, sunucu yalnız NİYETİ
  bildirir, çizimi `vnltable.js` yapar.

  ```php
  ['name' => 'durum', 'title' => 'Durum', 'format' => [
      'kind'    => 'badge',
      'map'     => ['1' => 'success', '2' => 'warning', '3' => 'danger'],
      'labels'  => ['1' => 'Onaylandı', '2' => 'Beklemede', '3' => 'İptal'],
      'default' => 'secondary',
  ]],
  ```

  **Neden ham HTML genişletilmedi.** Sorun zaten sunucunun HTML üretmek zorunda
  kalmasıydı; `render => 'html'`'in kapsamını büyütmek onu derinleştirirdi.
  **Neden bildirimsel yol asıl yol.** Üretilen düğümlerin hiçbiri `innerHTML`
  kullanmaz — metin her zaman `textContent`'ten geçer, renk adları sunucuda
  beyaz listeye (`VnlTable::VARIANTS`) karşı doğrulanır, `link`'in adresi hem
  sunucuda hem istemcide şema beyaz listesinden geçer. Yani bu yolda XSS
  yüzeyi yoktur. Yazım hatası da ekranda renksiz bir rozete dönüşmez, tanımda
  hata verir.

  Biçimlendirici **yalnız ekrandır**: satırdaki değere, sıralamaya, filtreye,
  gruplamaya, pivota ve Excel çıktısına dokunmaz. `render => 'html'` yerinde
  duruyor ve sözleşmesi değişmedi — ama artık son çaredir. Üçünü aynı sütunda
  birden vermek tanım hatasıdır ve sayfa yüklenirken durur.

- **Adlandırılmış biçimlendirici — `formatter` + `vnlTable.formatter()`.**
  Bildirimsel sözlüğün yetmediği yer için ikinci bir yol. Sütun bir **ad**
  söyler, adı sayfa kaydeder:

  ```php
  ['name' => 'tutar', 'type' => 'number', 'formatter' => 'paraTr']
  ```
  ```js
  vnlTable.formatter('paraTr', function (value, row, col) {
      return Number(value).toLocaleString('tr-TR') + ' ₺';
  });
  ```

  Fonksiyon **metin** ya da **DOM düğümü** döndürür, **HTML metni döndüremez**.
  Bu kasıtlıdır: markup üretecek biçimlendirici düğümü kendisi kursun, kaçış
  sorusu hiç doğmasın. Satır işlemi düğmeleri de buraya aittir — `onclick`
  dizesi kurmak yerine düğüme gerçek bir dinleyici bağlanır, satırın tamamı
  zaten fonksiyonun elindedir. İki yolun sözlüğü ve örnekleri **/docs/vnltable
  §5**'te.

- **Menü yetki kancası — `vnl_can()` ve `vnl_project_permission()`.** Yetki
  ŞEMASI projenindir; VNL'in `tbl_permissions`'ı bir örnektir, gerçek
  kurulumlar rol, kiracı, ürün gibi kendi eksenlerini katar. O yüzden çekirdek
  karar vermez, projeye sorar:

  ```php
  // helpers/yetki.php — vnl_load_dir('helpers') kendiliğinden yükler
  function vnl_project_permission(string $code, $user): bool
  {
      return $user !== null && UserPermissions::CanViewByCode($user->id, $code);
  }
  ```

  Proje bu fonksiyonu tanımlamadığı sürece kapı açıktır: her öğe görünür ve
  davranış 3.1.0 ile aynıdır — yeni bir çekirdek sürümü kimsenin menüsünü
  boşaltmaz. 3.1.0'daki hata ekranı kancasının (`vnl_project_error_page()`)
  kurduğu düzenin aynısı.

- **`vnl_logged_in()` ve `vnl_user()`.** "Giriş yapılmış mı" sorusunun tek
  cevabı. `$CurrentUser` her istekte koşulsuz kurulduğu için ölçüt nesnenin
  varlığı olamaz; ölçüt kimliğin kendisidir ve `vnl/kernel.php` girişsizi
  zaten böyle tanıyordu. `vnl_user()` girişsizde **`null`** döner — boş model
  değil; `vnl_user()?->id` yazan kod isteği düşürmez.

- **`deleteItemFromDB()`.** `/docs/theme-js` bunu VNL 3.0'dan beri anlatıyordu
  ama hiçbir JS dosyasında tanımlı değildi. Yazıldı: SweetAlert2 onayı + AJAX,
  başarıda toast, `table` verilirse VNL Table'ı tazeleme. Hata yolu bilinçli
  olarak boş — `400/401/406/410/500` yanıtları zaten genel AJAX kancasından
  geçip mesajı basıyor, ikinci bir modal aynı hatayı iki kez gösterirdi.
  Seçeneklerin tamamı **/docs/theme-js §1**'de.

- **`php vnl.php doctor` — iki yeni denetim.** Aşağıdaki iki düzeltme
  **proje** dosyalarında; güncelleme onlara dokunamaz, yani mevcut
  kurulumlara kendiliğinden ulaşmazlar. `doctor` artık `actions/Auth.php`'nin
  kırık giriş denetimini ve `view/static/leftSidebar.php`'deki kısa devreyi
  ayrı ayrı sınıyor.

### Düzeltilenler

- **`Auth::CheckLogin()` hiçbir zaman denetlemiyordu. (GÜVENLİK)** Ölçüt
  `!empty(self::User())` idi; `self::User()` `$CurrentUser` global'ini
  döndürüyor, o da `config/database.php`'de koşulsuz `new User()` ile
  kuruluyor. PHP'de bir nesne hiçbir zaman `empty` olmaz ⇒ metot **her zaman
  `true`** dönüyordu. `/docs/request-handling` §5 onu "giriş yapılmamışsa hata
  fırlatır ve işlemi durdurur" diye tarif ettiği için action'lar o satıra
  güveniyordu. Ölçüt artık `vnl_logged_in()`.

  Durum kodu bilerek 400'de bırakıldı: `vnl.js`'in 401 dalı oturumu DÜŞMÜŞ
  kullanıcı için yazılmıştır ve `VNL_USER_ID` sıfırken hiçbir şey göstermez —
  girişsiz kullanıcı ekranda sessizlik görürdü.

- **`Auth::HasPermis()` girişsiz isteği eleyemiyordu.** Boş modelin `null`
  `perm_id`'si gevşek karşılaştırmada listedeki `0` ya da `''` ile
  eşleşebiliyordu. Artık önce giriş sorulur.

- **Kenar çubuğu yetki denetimi ölü koddu. (GÜVENLİK)** Üç ayrı kırık parça:
  yetki satırları hem `showSideBar()` hem `ShowSidebarMenu()` içinde yoruma
  alınmış ve `$pagePermission = true;` ile kısa devre edilmişti;
  `hasVisibleChild()` tanımlıydı ama **hiçbir yerden çağrılmıyordu**; alt
  menü öğelerinde `showmenu` hiç okunmuyordu, yani bir alt sayfaya
  `'showmenu' => 0` vermek işe yaramıyordu. Üçü de düzeltildi ve denetim
  yukarıdaki `vnl_can()` kancasına bağlandı.

  **Boş üst menü — 3.1.0'a göre tek gözle görülür fark.** Artık alt
  öğelerinin tamamı gizli olan bir üst başlık hiç çizilmiyor. 3.1.0'da o
  başlık görünüyor, tıklanıyor ve boş bir panel açıyordu. Menüsünde böyle bir
  dal olmayan bir kurulumda hiçbir şey değişmez.

- **`$vnl_submenu` fonksiyon kapsamında tanımsızdı.** `view/static/header.php`
  onu kuruyor ama `ShowSidebarMenu()`'nün `global` listesinde yoktu; sonuç
  olarak `($vnl_submenu ?? 'flyout')` her zaman `'flyout'` dönüyor ve
  `accordion` düzeninin "aktif dal sayfa açılışında açık gelir" davranışı
  **hiç çalışmıyordu**. Değişken `global` listesine eklendi; `aria-expanded`
  de artık gerçek durumu söylüyor.

- **`type => 'bool'` hücreyi hiç etkilemiyordu.** Yalnız hizalamayı `center`
  yapıyor ve filtre panelinin türünü belirliyordu; hücrede ham `1`/`0`
  görünüyordu. `'format' => ['kind' => 'bool']` artık evet/hayır çizimini de
  veriyor — ham değere dokunmadan.

### Kaldırılanlar

- **`vnlLang('ANAHTAR')` ve `VNL_LANG` doküman girdileri.** `/docs/theme-js`
  §2 ve `/docs/view-includes` §2 bunları "sunucudan `lang()` ile beslenen JS
  dil köprüsü" diye anlatıyordu; ikisi de hiçbir dosyada tanımlı değildi.
  `view/includes/footer/script-login.php`'deki "JS dil köprüsü kaldırıldı"
  notu köprünün 3.0'da **bilinçli olarak** söküldüğünü, dokümanın
  güncellenmediğini gösteriyor.

  **Neden yeniden yazılmadı.** Dokümanın tarifi bir sözleşme değil, bir
  cümleydi: hangi anahtarların istemciye ineceğini söylemiyordu. Sözlüğün
  tamamını göndermek hem gereksiz hem sızıntı olurdu; anahtar seçimi için bir
  kural uydurmak da tam olarak "uydurma API" demekti. Üstelik çekirdeğin bu
  soruna kendi cevabı zaten var ve daha iyisi: **her bileşen kendi metinlerini
  kendi tanımıyla taşır** — `VnlTable::lang()` metinleri tablo tanımının
  `lang` bloğunda gönderir, istemcide ayrı bir köprü gerekmez. Doküman koda
  uyduruldu ve bu desen `/docs/theme-js` §2'de yazıldı.

  `deleteItemFromDB()` ile neden farklı karar verildiği: onun tarifi
  (`{url, data, …}` + "Swal onayı + AJAX") uygulanabilecek kadar somuttu ve
  çevresindeki sözleşmeler — `returnSuccessMessage()` biçimi, genel
  `ajaxComplete` hata yönetimi — geri kalanını zaten belirliyordu.

- **`/docs/view-includes`**'taki `script-standard.php` satırı düzeltildi:
  dosya `VNL_LANG` tanımlamıyor ve `showMessage()`/`showModalMessage()`
  `vnl.js`'te duruyor.

### Güvenlik

- Giriş denetimi (`Auth::CheckLogin()`) ve menü yetki denetimi gerçekten
  çalışıyor. İkisi de **proje dosyalarındadır** — aşağıdaki yükseltme notuna
  bakın.
- Hücre biçimlendiricisinin bildirimsel yolunda XSS yüzeyi yoktur: metin
  `textContent`'ten geçer, sınıf adları beyaz listelidir, `link`'in adresi hem
  sunucuda hem istemcide şema beyaz listesinden (`http`, `https`, `mailto`,
  `tel`, göreli) geçer ve satırdan gelen yer tutucular URL-kodlanır. `target`
  verilen bağlantıya `rel="noopener noreferrer"` eklenir.
- Adlandırılmış biçimlendirici HTML metni kabul etmez; markup isteyen düğümü
  kendisi kurar.

### Yükseltme

`php vnl.php update` `vnl/` dizinini değiştirir ve **çekirdek tarafında başka
bir şey gerekmez**. Şema değişikliği yoktur, migration yoktur.

Ama bu sürümdeki iki güvenlik düzeltmesi **proje** dosyalarındadır;
güncelleyici oraya dokunamaz. Mevcut kurulumlarda ikisini elle alın:

| Dosya | Neden |
| --- | --- |
| `actions/Auth.php` | `CheckLogin()` hiç denetlemiyordu |
| `view/static/leftSidebar.php` | menü yetki denetimi kısa devre edilmişti; `$vnl_submenu` eksikti |

`php vnl.php doctor` ikisini de ayrı ayrı sınar ve hangisinin eski kaldığını
söyler. `leftSidebar.php`'yi kendi menünüzle özelleştirdiyseniz dosyayı
komple değiştirmek yerine yalnız alttaki fonksiyonları (`showSideBar`,
`ShowSidebarMenu`, `canShowMenu`, `hasVisibleChild`) şablondakiyle
karşılaştırın; `$SidebarMenu` dizi sözleşmesi değişmedi.

Menüde `permission` kullanmıyorsanız yapacak bir şey yok: kanca
tanımlanmadığı sürece kapı açıktır.

---

## [3.1.0] — 2026-08-24

### Eklenenler

- **Proje hata ekranı kancası.** `vnl_render_error()` artık çıktısını basmadan
  önce projede `vnl_project_error_page(Throwable $e)` diye bir fonksiyon var mı
  diye bakar. Varsa ekranı proje üretir ve istek orada biter; fonksiyon
  **dönerse** çekirdeğin ayrıntılı sayfası basılır — geliştirme makinesinde
  ayrıntı böyle korunur. Fonksiyon `helpers/` altında tanımlanır,
  `vnl_load_dir('helpers')` onu kendiliğinden yükler: çekirdeğe dokunmak
  gerekmez.

  Kanca üç yakalama katmanının da tek geçtiği yerdedir — yakalanmamış
  istisnalar, ölümcül hatalar ve **denetleyicide oluşan istisnalar**. Sonuncusu
  bu kancanın asıl sebebi: `vnl/kernel.php` denetleyiciyi kendi `try/catch`'i
  içinde çalıştırıp hatayı doğrudan `vnl_render_error()`'a verir, yani yalnız
  `set_exception_handler`'ı değiştiren bir proje sayfa hatalarını — en sık
  görülenleri — kaçırıyordu.

  Sözleşme: kanca çıktı tamponları temizlendikten **sonra**, durum kodu
  yazılmadan **önce** çağrılır (durum kodu ve HTML/JSON kararı projenindir,
  istek türü için `vnl_wants_json()` vardır); istisna fırlatırsa yutulur ve
  çekirdeğin sayfası basılır — hata ekranının hatası kullanıcıyı boş sayfada
  bırakmaz.

### Değişenler

- **`/docs/errors` → "Üretim İçin Kapatma" bölümü yeniden yazıldı.** Eski reçete
  `vnl/boot.php`'deki handler'ları elle düzenletiyordu: hem çekirdeğe dokunuyor
  (güncellemede kayboluyor) hem de yukarıdaki sebeple denetleyici hatalarını
  kaçırıyordu. Yerini yeni kancanın sözleşmesi ve çalışan bir örnek aldı.

### Yükseltme

`vnl/` değiştirilir, başka bir şey gerekmez. Kanca tanımlanmadığı sürece
davranış 3.0.1 ile birebir aynıdır.

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

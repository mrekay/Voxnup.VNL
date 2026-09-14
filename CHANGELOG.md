# Değişiklik Günlüğü

Bu dosya VNL **çekirdeğinin** sürüm geçmişidir. Türetilmiş projeler kendi
değişikliklerini kendi günlüklerinde tutar.

Biçim [Keep a Changelog](https://keepachangelog.com/tr/1.1.0/), sürümleme
[Semantic Versioning](https://semver.org/lang/tr/). Sürüm politikası ve neyin
kırıcı sayıldığı için **/docs/versioning** sayfasına bakın.

---

## [3.3.1] — 2026-09-14

Tek düzeltme: **`.app-content` içine yazılan modal backdrop'un altında
kalıyordu.** Kırıcı değişiklik yoktur; giriş animasyonu görünüm olarak aynıdır,
değişen yalnız `vnl.min.css`'teki iki kelimedir.

### Düzeltilenler

- **Giriş animasyonu bittikten sonra `.app-content` üzerinde kalıcı bir
  transform bırakıyordu; içerideki `position: fixed` öğeler (Bootstrap modal)
  viewport'a değil `.app-content`'e göre konumlanıyor ve backdrop'un altında
  kalıyordu.**

  **Ne.** `.app-content, .vnl-focus { animation: vnl-page-in .3s ease both }`.
  `both` doldurma kipi animasyon bitince son keyframe'i uygulamaya devam eder.
  Keyframe `transform: translateY(.375rem) → none` olduğundan tarayıcı son
  kare olarak `none`'ı değil, interpolasyonun sonucu olan kimlik matrisini
  (`matrix(1, 0, 0, 1, 0, 0)`) tutar. CSS Transforms kuralı gereği `none`
  dışında transform taşıyan her öğe, `position: fixed` torunları için
  containing block ve yeni bir stacking context olur. Sonuç: `.app-content`
  içine yazılan bir modal `.app-content` kutusuna göre konumlanır, `z-index:
  1055`'i `.app-content`'in stacking context'inde hapsolur ve `body`'ye eklenen
  `.modal-backdrop` (`z-index: 1050`) modalin **üstüne** çizilir;
  `elementFromPoint` backdrop'u döndürür, modal tıklanamaz. IKYSIS taşımasında
  bulundu; orada modaller içeriğin dışına taşınarak dolaşıldı — ama "modali
  içeriğin içine yaz" diye yazan her sayfa için tuzak çekirdekteydi.

  **Ölçüm** (Chrome 151, `/docs/theme-components`, modal `.app-content`
  içinde). Önce: `getComputedStyle(appContent).transform` =
  `matrix(1, 0, 0, 1, 0, 0)`; `.modal` kutusu = `.app-content` kutusu
  (260,0 · 1020×3556), viewport değil; `elementFromPoint` = `.modal-backdrop`;
  fare tıklaması düğmeye ulaşmıyor. Sonra: `transform` = `none`; `.modal`
  kutusu = viewport (0,0 · 1280×900); `elementFromPoint` = `.modal-body` /
  düğme; tıklama ulaşıyor. `.vnl-focus` (login) için aynı: kimlik matrisi →
  `none`.

  **Düzeltme.** Doldurma kipi `both` → `backwards`. `backwards` ilk kareden
  önce `from` keyframe'ini uygular (ilk boyamada içerik görünmez, sıçrama yok),
  animasyon bitince kuralı düşürür; öğe taban stiline (`opacity: 1`,
  `transform: none`) döner. `to` keyframe'i taban stile eşit olduğundan bitişte
  de sıçrama yoktur. Aynı kalıp içerik barındıran akordeon gövdesinde de vardı
  (`.accordion-collapse.show .accordion-body`, `vnl-acc-body-in`): akordeon
  içine yazılan modal aynı tuzağa düşerdi, o da `backwards` yapıldı.

  **Neden bu, diğerleri değil.** Keyframe'den `transform`'u çıkarmak yukarı
  kaymayı kaldırır, görünüm değişirdi. Animasyon bitince sınıfı JS ile
  düşürmek `.app-content` düzen sınıfı olduğu için ayrı bir sınıf ve betik
  isterdi. `both`'u tutup transform'u etkisizleştirmenin CSS'te yolu yoktur:
  `none` dışındaki her değer containing block'tur. Seçilen değişiklik
  keyframe'lere, süreye ve easing'e dokunmaz; 0–300 ms arası çizim kare kare
  aynıdır (aynı animasyon anında alınan karelerde 0 piksel fark).

  **Görünür tek fark — olumlu.** Kalıcı transform `.app-content`'i ayrı bir
  kompozit katmana alıyor, içindeki yazı gri tonlu antialiasing ile
  çiziliyordu; şimdi sayfanın geri kalanı gibi alt piksel (LCD) antialiasing
  kullanır. Yalnız yazı kenarlarındadır.

  **Bilerek dokunulmayanlar.** `.dropdown-menu.show` (`vnl-dropdown-in`)
  `margin-top` animasyonudur ve transform içermediği için containing block
  oluşturmaz; `both` orada kalır — taban `margin-top`
  (`--bs-dropdown-spacer`) keyframe'in sonundan farklıdır, `backwards` görünür
  sıçrama yapardı. Kenar çubuğu alt menüleri (`vnl-flyout-in`, `vnl-sub-in`)
  içerik barındırmaz, olduğu gibi kaldı. `prefers-reduced-motion` dalı
  değişmedi (`animation: none`).

  **Etki.** `.app-content` (app, top-nav, landing düzenleri), `.vnl-focus`
  (focus düzeni) ve akordeon gövdesi içine yazılan modal, offcanvas, toast ve
  her `position: fixed` öğe artık viewport'a göre konumlanır. Modalini bu
  yüzden içeriğin dışına taşımış sayfalar öyle kalabilir; ikisi de çalışır.

### Yükseltme

`vnl/` değiştirilir, başka bir şey gerekmez. Şema değişikliği yoktur.

---

## [3.3.0] — 2026-09-14

Tek konu: çekirdeğin **tarih ve saat seçicisi yoktu**. Bu sürüm onu ekler.
**Kırıcı değişiklik yoktur:** `data-vnl-date` yazmayan bir sayfada davranış
3.2.0 ile birebir aynıdır; değişen yalnız `<head>`'e giren üç varlık etiketidir.

### Eklenenler

- **VNL Date — `<input data-vnl-date>`.** Tarih, saat, tarih+saat, ay ve
  aralık için tek bileşen. Sayfa yazarı bir nitelik yazar, çekirdek açılışta
  kurar; sayfa başına JavaScript gerekmez:

  ```html
  <input data-vnl-date            name="tarih">    <!-- 14.09.2026 → 2026-09-14 -->
  <input data-vnl-date="time"     name="saat">     <!-- 09:30 → 09:30:00 -->
  <input data-vnl-date="datetime" name="zaman">    <!-- → 2026-09-14 09:30:00 -->
  <input data-vnl-date="month"    name="donem">    <!-- 09.2026 → 2026-09 -->
  <input data-vnl-date="range"    name="aralik">   <!-- → 2026-09-01 - 2026-09-14 -->
  ```

  **Neden.** VNL Theme'in vendor paketinde tarih girişine dair hiçbir şey
  yoktu — ne flatpickr, ne datepicker, ne moment. Şablonun taşındığı 12 ürünlük
  pakette ise 31 kullanım noktası bootstrap-datepicker + bootstrap-timepicker +
  bootstrap-datetimepicker + moment 2.4.0 (2013) zincirine bağlıydı ve bu
  sayfalar VNL kabuğuna geçemiyordu. Boşluk sayfa sayfa yamayla değil,
  çekirdeği genişleterek kapatıldı: bir kez yazılır, her proje `update` ile alır.

  **Neden yerli `<input type="date">` değil.** Sıfır bağımlılık cazipti ama
  üç şey karşılanamıyordu: ay seçici Firefox ve Safari masaüstünde yoktur,
  yalnız-yıl görünümü hiçbir yerde yoktur, saatin 24 saat gösterimi ve dakika
  adımı işletim sistemi yereline bağlıdır ve sayfadan denetlenemez. Görünüm de
  tarayıcıdan tarayıcıya değişir; "VNL ölçüttür" ilkesiyle çelişir. İkisini
  birden sunmak (yerli varsayılan + kütüphane) aynı formda iki görsel dil ve
  iki değer sözleşmesi demekti; reddedildi.

  **Neden flatpickr.** Vanilla (jQuery'siz), bağımlılıksız, MIT, ~50 KB
  (gzip ~16), Türkçe yerel ayarı ve ay eklentisi resmî dağıtımda; tarih, saat,
  aralık, min/max, klavye erişimi bir arada. Bakımı yavaş (son sürüm 4.6.13),
  bu bilinçli kabul edildi: kod küçük, kapalı ve çekirdekle taşınıyor; VNL
  jQuery 3.7.1 ve Bootstrap'ı da aynı yolla taşıyor.

  **Değer sözleşmesi — HTML'in kendi kuralı.** Kullanıcı yerel biçimi görür
  (`14.09.2026`), `value` her zaman ISO'dur (`2026-09-14`): asıl input
  gizlenir ve ISO taşır, kullanıcı biçimli bir kopyada çalışır. Action'da
  dönüşüm yok; `Y-m-d` doğrudan modele gider. Sunucusu ekrandaki metni bekleyen
  sayfa `data-vnl-date-value="text"` der — input'un değeri ekrandakinin
  kendisidir; eski `gg/aa/yyyy` sözleşmeleri böyle korunur. Girişteki değer
  ISO da olsa ekran biçiminde de olsa okunur; okunamayan değer flatpickr'ın
  esnek ayrıştırıcısına bırakılmaz (`2026-09-14`'ü `d.m.Y` ile okuyup 20
  Haziran çıkarırdı), temizlenir ve konsola yazılır.

  **Tema.** Takvimin her rengi `--bs-*` / `--vnl-*` token'ından okunur:
  koyu temada VNL'in koyusu, aydınlıkta skin. Modal ve offcanvas içindeki
  seçici takvimi `body`'ye değil o kabın içine ekler — Bootstrap 5'in odak
  tuzağı body'deki takvimin saat kutusuna yazmayı engelliyordu.

  Nitelikler (`-format -value -min -max -step -seconds -inline -locale -input
  -toggle`), `vnlDate` API'si, `vnl.date.*` olayları, eski seçicilerden geçiş
  tablosu ve canlı örnekler **/docs/theme-dates**'te.

- **`vnl_core_styles()` / `vnl_core_scripts()`** listesine `css/vnl-date.css`,
  `js/flatpickr.min.js` ve `js/vnl-date.js` eklendi. `head-standard.php`
  listeyi çekirdekten aldığı için projede dokunulacak yer yoktur — 3.0'da bu
  yüzden çekirdeğe konmuştu.

- **`clearForm($form)`** artık VNL Date örneklerini de sıfırlıyor: `val("")`
  yalnız metni siler, seçili gün örnekte kalır ve takvim yeniden açılınca
  eski günü gösterirdi.

### Yükseltme

`vnl/` değiştirilir, başka bir şey gerekmez. Şema değişikliği yoktur.
Kullanmayan sayfa etkilenmez; yeni bir tarih alanı için tek satır:
`<input data-vnl-date name="...">`.

Eski bir seçiciden geçiyorsanız **/docs/theme-dates §7**'deki tablo çağrıyı
niteliğe çevirir. İki şeyin karşılığı bilerek yoktur: yalnız-yıl görünümü
(`minViewMode: 2`) ve daterangepicker'ın hazır aralık düğmeleri; ilki için
`<select>`, ikincisi için sayfanın kendi düğmeleri + `vnlDate.set(el, [a, b])`.

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

# Değişiklik Günlüğü

Bu dosya VNL **çekirdeğinin** sürüm geçmişidir. Türetilmiş projeler kendi
değişikliklerini kendi günlüklerinde tutar.

Biçim [Keep a Changelog](https://keepachangelog.com/tr/1.1.0/), sürümleme
[Semantic Versioning](https://semver.org/lang/tr/). Sürüm politikası ve neyin
kırıcı sayıldığı için **/docs/versioning** sayfasına bakın.

---

## [3.5.0] — 2026-11-04

**Girişe atılan kullanıcı girişten sonra istediği sayfaya dönebilir.** Özellik
isteğe bağlıdır; ayarı açmayan projede yönlendirme adresi değişmez. Kırıcı
değişiklik ve migration yoktur. Değişen çekirdek dosyalar:
`vnl/helpers/app.php`, `vnl/modules/UserModule.php`.

### Eklenenler

- **`$login_return_param` / `getLoginReturnParam()`.** Parametre adı
  `config/definitions.php`'de verilir, boş dizge kapalıdır. Bu dosya güncellemede
  değişmediği için var olan proje alanı ve erişimciyi kendisi ekler; erişimci
  yoksa özellik kapalı sayılır.

  ```php
  private $login_return_param = "donus";

  public function getLoginReturnParam(): string
  {
      return $this->login_return_param;
  }
  ```

  Açıkken `/satis?yil=2026` isteği `/login?donus=%2Fsatis%3Fyil%3D2026` adresine
  gider. Bu yol `vnl/kernel.php`, `vnl_require_permission()` ve
  `UserModule::CheckUser()` için geçerlidir. Yalnız GET sayfa isteği taşınır; POST
  ve AJAX istekleri, kök adres, giriş, çıkış ve `$login_except_pages` sayfaları
  düz `/login`'e gider.
- **`vnl_safe_return_url($value): ?string`.** Kullanıcıdan gelen dönüş adresini
  doğrular, açık yönlendirmeyi engeller. Geçerli adresi ya da `null` döndürür.
  Reddettikleri: tek `/` ile başlamayan her değer (`//x`, tam URL), ters bölü,
  kontrol karakteri ve boşluk, `.`/`..` parçası, geçersiz UTF-8, 2000 bayttan uzun
  değer ve yukarıdaki kapalı adresler.
- **`vnl_login_return_query(): string`.** Giriş yönlendirmesinin sorgu dizgesini
  üretir. Kendi `CheckUser`'ını taşıyan proje `/login`'in sonuna bunu ekler.

  Giriş ekranı ve action projenindir; örnek için **/docs/config-definitions** §6.

### Düzeltilenler

- **`vnl_redirect_login()` şemasız site adresini bozmaz.** `getSiteUrl()` şema
  döndürmüyorsa (`localhost:5408`) göreli `/login` kullanılır. Eskiden tarayıcı
  `localhost:` kısmını şema sayıyor, yönlendirme çalışmıyordu.

---

## [3.4.1] — 2026-09-23

Küçük, geriye uyumlu yama: **`VnlHttpException` artık JSON gövdesine ek alan
ve yanıta başlık taşıyabilir** (F30). Kırıcı değişiklik yoktur; 3.4.0'daki her
çağrı (`throwErrorMessage()`, `new VnlHttpException($m, $kod, $devcode,
$previous)`) aynı durum kodunu, aynı başlıkları ve bayt bayt aynı gövdeyi
üretir. Değişen dosyalar: `vnl/classes/VnlHttpException.php`,
`vnl/classes/VnlDispatcher.php`.

Madde numarası (F30) VNL Panel geliştirici kılavuzunun §12 düzeltme listesine
karşılık gelir.

### Eklenenler

- **`VnlHttpException` — `$extra` ve `$headers` (F30).** Kurucuya
  `$previous`'tan sonra iki isteğe bağlı parametre, sınıfa ezilebilir iki
  metot eklendi:

  ```php
  new VnlHttpException(string $message, int $status = 400, string $devcode = '',
                       ?Throwable $previous = null, array $extra = [], array $headers = []);

  $e->extra();    // array — JSON gövdesine eklenir: ['errors' => [...], 'limit' => [...]]
  $e->headers();  // array — gövdeden önce gönderilir: ['Retry-After' => 5]
  ```

  `VnlDispatcher` bir `VnlHttpException` yakaladığında önce `headers()`'ı
  gönderir, sonra gövdeyi `['message' => …, 'devcode' => …] + extra()` olarak
  yazar. Proje kendi alan istisnalarını (422 + `errors[]`, 409 + `limit`,
  503 + `Retry-After`) dağıtıcıyı kopyalamadan yanıta çevirebilir:

  ```php
  class ValidationError extends VnlHttpException
  {
      public function __construct(private array $fieldErrors)
      {
          parent::__construct('Form hatalı.', 422, 'VALIDATION');
      }

      public function extra(): array
      {
          return ['errors' => $this->fieldErrors];
      }
  }
  // → 422 {"message":"Form hatalı.","devcode":"VALIDATION","errors":[...]}
  ```

  Kurallar:
  - `extra()` ve `headers()` **yanıt yazılırken** çağrılır; alt sınıf ikisini
    de ezebilir. Metodun kendisi istisna fırlatırsa hata beklenmeyen sayılır:
    500 `VNL_FATAL` + `ref`, ayrıntı günlüğe.
  - Ek alanlar `message` ve `devcode`'u **ezemez** (dizi birleşimi soldakini
    korur).
  - Adı RFC 9110 token'ı olmayan başlık atlanır; değerdeki CR/LF boşluğa
    çevrilir (başlık enjeksiyonu yok). Değer liste ise aynı başlık her öğe için
    ayrı satır gider; `null` ve `bool` atlanır. `Content-Type` her durumda
    `application/json`'dur.
  - Proje `classes/VnlHttpException.php` ile 3.4.0 sınıfını ezmişse (metotlar
    yok) dağıtıcı eskisi gibi yalnız `{message, devcode}` yazar.

### Değişenler

- `VnlDispatcher`'ın hata gövdesi `JSON_INVALID_UTF8_SUBSTITUTE` ile
  kodlanır: geçersiz UTF-8 taşıyan bir mesaj artık boş gövde değil, `U+FFFD`
  içeren geçerli bir JSON üretir. Geçerli metinde çıktı değişmez.
- `/docs/errors` §6 3.4.0 dağıtıcısına göre düzeltildi (eski
  `controller/app.php` örneği kaldırıldı), §7 eklendi; `/docs/request-handling`
  `throwErrorMessage()` satırı güncellendi.

### Yükseltme

`php vnl.php update --to 3.4.1`. Dağıtıcıyı `classes/VnlDispatcher.php` ile
ezen proje yeni alanları yazmak için `catch (VnlHttpException $e)` bloğunda
`$e->headers()` ve `$e->extra()`'yı kendisi kullanmalıdır; ezmeyen projede
yapılacak bir şey yoktur.

---

## [3.4.0] — 2026-09-23

Güvenlik ve işletim sürümü. Şablon artık **varsayılan olarak kapalı** gelir:
sayfalar girişe, action'lar POST + CSRF + girişe, yetkiler koda bağlıdır;
canlıda hata ayrıntısı sızmaz. Bir barındırma panelinin üzerine kurulabilmesi
için eksik olan altyapı eklendi: kullanıcı/yetki şeması, hız sınırı, iki adımlı
doğrulama, denetim kaydı, iş kuyruğu, zamanlayıcı, proje komutları, API
jetonları. 3.3.1'de girişli **her** isteği 500'e düşüren hata da kapandı.

Madde numaraları (F01–F29) VNL Panel geliştirici kılavuzunun §12 düzeltme
listesine karşılık gelir.

### Kırıcı değişiklikler — yükseltmeden önce okuyun

1. **Action'lar yalnız POST + CSRF kabul eder (F03, F04).** `GET /app/...` →
   405. Jetonsuz POST → 403 `VNL_CSRF`. vnl.js (`$.ajax`, `selectReload`,
   `deleteItemFromDB`) ve VNL Table jetonu kendiliğinden ekler; kendi
   `fetch`/form gönderiminiz `X-CSRF-Token: vnlCsrfToken()` başlığını ya da
   `<?= vnl_csrf_field() ?>` alanını eklemelidir. `head-standard.php`'de
   `<meta name="csrf-token">` olmalıdır.
2. **Action'lar girişi varsayılan olarak zorunlu kılar (F04).** Girişsiz
   çağrılacak metot sınıfta `public const PUBLIC_METHODS = ['login'];` ile
   listelenmelidir; imzasını kendisi doğrulayan webhook
   `public const CSRF_EXEMPT = ['webhook'];` ile CSRF'ten muaf tutulur.
   Yalnız `public`, static olmayan, `__` ile başlamayan metotlar çağrılır.
3. **Girişsiz istekte korumalı denetleyici artık ÇALIŞMAZ (F05).**
   `$page_config['public'] = true` bir sayfayı açmaz; sayfa
   `$login_except_pages`'e yazılır ya da `vnl_public_page('ad')` ile
   kaydedilir. Geçiş kipi: `VNL_PUBLIC_CAPTURE=true` (eski davranış, yan
   etkili denetleyicide güvenli değildir).
4. **Varsayılan açık sayfalar daraldı (F18).** `$login_except_pages` =
   `["app", "login", "logout"]`. `index` ve `docs/*` giriş ister; dokümanlar
   `VNL_PUBLIC_DOCS=true` ile açılır.
5. **Çıkış POST'tur (F17).** `GET /logout` yalnız onay sayfasıdır; oturumu
   `POST /app/login/logout` (ya da `data-vnl-logout` taşıyan düğme) kapatır.
6. **Hata ayrıntısı yalnız `APP_DEBUG=true` iken (F09).** Varsayılan kapalı:
   `display_errors=0`, hata sayfası/JSON kaynak kod, yol ve yığın izi
   göstermez, başvuru kodu (`ref`) gösterir. Geliştirmede `.env`'e
   `APP_DEBUG=true` yazın.
7. **`returnSuccessDataOnly()` sayıya çevirmez (F12).** `JSON_NUMERIC_CHECK`
   kaldırıldı: `"0755"` dizge kalır. Sayı bekleyen istemci kodu için değeri
   sunucuda `(int)` ile döndürün ya da istemcide `Number()` kullanın.
8. **Durum kodları (F25, F08).** `throwErrorMessage($m, 404)` artık 404
   döner (3.3: hep 400). `Auth::CheckLogin()` 401 `VNL_AUTH`,
   `Auth::HasPermis()` 403 `VNL_PERM` döner (3.3: 400).
9. **Oturum çerezi `SameSite=Lax` (F03).** 3.3 TLS'te `None` yazıyordu.
   Siteler arası gömülü kullanım gerekiyorsa `VNL_SESSION_SAMESITE=None`.
10. **Eklenti denetleyici/action'ları yalnız etkinse çözülür.**
    `extensions/extensions.php`'de `false` olan eklentinin uçları 404 döner.
11. **`PDFModule::CreatePDF()` istisna fırlatır (F19)** — hiçbir zaman
    çalışmamıştı (hedef dosya yoktu).
12. **Varsayılan karakter kümesi `utf8mb4` (F14).** `.env`'de `DB_CHARSET`
    tanımlıysa o geçerlidir.
13. **Boşta kalma "beni hatırla"yı da bitirir (F06.3).** `VNL_SESSION_IDLE`
    aşılınca bu cihazın kalıcı jetonu iptal edilir; oturum dosyası silinmiş
    olsa bile jetonun son kullanımı (`used_at`) aynı sınırla denetlenir.
    Eski sessiz yeniden giriş: `VNL_IDLE_KEEPS_REMEMBER=true` (ya da
    `VNL_SESSION_IDLE=0`).
14. **Yetki kapıları kanca yokken KAPALI.** `vnl_require_permission()`,
    `Auth::CheckPermission()` ve `Auth::Can()` katı sorar
    (`vnl_can($kod, $yetenek, true)`): `helpers/permissions.php` yoksa ya da
    kod boşsa kimse geçemez. Menü görünürlüğü 3.1 uyumlu kalır.
15. **İki adımlı giriş `0007` ister.** `tbl_users.totp_last_step` yoksa
    TOTP'li kullanıcı ikinci adımda 500 alır (fail closed, sebep günlükte):
    `php vnl.php migrate --run`.
16. **Kuyruk: asılı iş ölçüsü nabızdır.** `--stale` artık işin başlangıcından
    değil son `progress()/log()/heartbeat()`'ten sayılır; eski işçinin
    yazmaları ayırma jetonuna bağlıdır (`VnlJobLostException`).

### Eklenenler

- **CSRF (F03):** `vnl_csrf_token()`, `vnl_csrf_field()`, `vnl_csrf_check()`,
  `vnl_csrf_rotate()`, `vnl_same_origin()`; JS `vnlCsrfToken()` +
  `$.ajaxPrefilter` (yalnız aynı köken) + VNL Table `fetch` başlığı.
- **Çekirdek dağıtıcı `VnlDispatcher` (F04, F25, F09, F29):**
  `controller/app.php` tek satırdır (`VnlDispatcher::handle($req)`); mantık
  çekirdekte olduğu için düzeltmeler güncellemeyle her projeye ulaşır.
  Sıra: yöntem → ad → sınıf/metot (Reflection) → jeton ya da CSRF + Origin →
  giriş → çağrı. `throwErrorMessage` kodunu korur; başka her istisna
  günlüğe yazılır, istemci `{"message","devcode":"VNL_FATAL","ref"}` alır.
- **Yetki kapıları (F08):** `vnl_can($kod, $yetenek = 'view')`,
  `vnl_require_permission($kod, $yetenek)` (sayfa; 403 sayfası),
  `Auth::CheckPermission($kod, $yetenek)` (action; 401/403, koşulsuz durur),
  `Auth::Can()`. Şablonda varsayılan kanca `helpers/permissions.php`
  (süper yönetici `perm_id = 1` her şey; diğerleri `can_<yetenek>`).
  `UserPermissions::Grant/Revoke/Can/ForUser/RevokeAll`,
  `Permission::EnsureCode()`. Yeni `controller/403.php` + `view/403.php`.
- **Kullanıcı şeması ve ilk yönetici (F02):** `vnl/migrations/0002` —
  `tbl_users` (+ mevcut kurulumda eksik sütunlar koşullu), `tbl_permissions`
  zaman damgaları, `tbl_user_permissions`, `Users` kodu. Komutlar:
  `user:create [--super]`, `user:password`. `User::$hidden` parola özeti ve
  TOTP sırrını JSON'dan dışlar.
- **Oturum sertleştirme (F06, F24):** girişte `Session::regenerate()` +
  CSRF döndürme; `VNL_SESSION_IDLE` boşta kalma sınırı; `VNL_REMEMBER_DAYS`
  (0 = kapalı); pasifleşen hesabın açık oturumu kapanır; `use_strict_mode`.
  `Session` yalnız statik (kurucu/yıkıcı yan etkisi yok), `setMany()`,
  `id()`, komut satırında oturum deposuna dokunmaz; dosya LF.
- **Hız sınırı (F07):** `RateLimiter::hit/tooMany/attempts/availableIn/clear/purge/decrement/ipKey`
  (`0003`, atomik upsert, anahtar özeti; kapı `hit()`'in dönüşüdür). Giriş:
  IP 20 (IPv6 /64) / hesap + IP 5 / hesap 100 deneme / 15 dk → 429 `VNL_RATE`. Kullanıcı yokken de bcrypt doğrulaması yapılır
  (süre farkı kullanıcının varlığını ele vermez).
- **Denetim kaydı (F10):** `Audit::log()`, `Audit::query()`,
  `Audit::actingAs()` (`0004`); asla istisna fırlatmaz, gizli anahtarları
  ayıklar. Giriş, başarısız giriş, çıkış, parola, TOTP, kullanıcı ve jeton
  olayları yazılır.
- **Kuyruk, işçi, zamanlayıcı, proje komutları (F11):** `Queue::push/find/
  status/cancel/retry/query`, `VnlJob` (`progress()`, `log()`, `heartbeat()`,
  `exec()`, `reservation()`), `VnlJobLostException`, `0005`;
  `php vnl.php queue:work` (atomik ayırma, yeniden deneme 30 sn × deneme,
  asılı iş kurtarma, SIGTERM'de temiz çıkış, süre/iş/bellek sınırları);
  `php vnl.php schedule:run` + `config/schedule.php` (`VnlCron`, paralel
  alt süreç, girdi başına kilit, dakikada bir); `commands/<ad>.php` proje
  komutları, `vnl_cli_boot()`, `vnl_cli_options()`.
- **İki adımlı doğrulama (F28):** `Totp::generateSecret/verify/match/code/uri/
  encryptSecret/decryptSecret` (RFC 6238); giriş iki adımlı
  (`/app/login/totp`, 5 dk, hız sınırlı, kod tek kullanımlık — `0007`,
  `User::claimTotpStep()`); `php vnl.php user:totp`.
- **API jetonları (F29):** `ApiToken::issue/authenticate/allows/revoke/forUser`
  (`0006`); `Authorization: Bearer vnl_…` dağıtıcıda oturum + CSRF yerine
  geçer; `token:create`, `token:revoke`.
- **Güvenlik başlıkları (F27):** `X-Frame-Options`, `nosniff`,
  `Referrer-Policy`, `Permissions-Policy`, TLS'te HSTS
  (`VNL_SECURITY_HEADERS`). CSP bilerek yok (satır içi betikler).
- **Günlük API'si (F15):** `LogModule::write/info/warning/error/debug`, JSON
  satırları `LOG_PATH` (varsayılan `files/logs`, 0750/0640). Eski
  `SetMysqlLog` vb. sarmalayıcı olarak duruyor.
- **`vnl_render($view, $vars)` (F21)**; dokümandaki `render()` örnekleri
  güncellendi.
- **Paralel geliştirme (F22):** menü parçaları `config/menu/*.php`, dil
  parçaları `languages/<dil>/*.json` (ad sırasıyla; çakışma günlüğe).
- **Ortak dil anahtarları (F20):** `BTN_SAVE/CANCEL/DELETE/EDIT/NEW`,
  `LBL_ACTIVE/PASSIVE/ACTIONS/YES/NO`, `MENU_HOSTING`, `ERR_CSRF`,
  `ERR_METHOD_NOT_ALLOWED`, `ERR_TOO_MANY_ATTEMPTS` ve 3.4 akışlarının
  anahtarları (iki dilde aynı küme).
- **`nginx.conf.example` (F23)**; dokümanlar: `/docs/security`,
  `/docs/cli-queue`.
- `.env`: `APP_DEBUG`, `APP_TIMEZONE`, `DB_PORT`, `DB_SOCKET`, `LOG_PATH`,
  `VNL_SESSION_IDLE`, `VNL_REMEMBER_DAYS`, `VNL_SESSION_SAMESITE`,
  `VNL_SECURITY_HEADERS`, `VNL_PUBLIC_DOCS`, `VNL_PUBLIC_CAPTURE`.
- `php vnl.php doctor`: dağıtıcı, `PUBLIC_METHODS`, CSRF meta, yetki kancası,
  açık `index`, GET çıkış, `APP_DEBUG`, `APP_ENCRYPTION_KEY`, eski
  `.htaccess`, web kökündeki günlük dizinleri, `pcntl` denetimleri.
  `migrate --run` kullanıcı tablosu boşsa ilk yönetici komutunu hatırlatır.

### Düzeltilenler

- **F01 — girişli her istek 500 veriyordu.** `UserModule` tanımsız
  `User::SetOnlineToLastActivity()`'yi çağırıyordu. Metot eklendi (dakikada
  bir, zaman damgasına dokunmadan) ve çağrı savunmacı yapıldı.
- **F04** — eksik parçada iki JSON gövdesi birleşiyordu; ham istisna mesajı
  (SQL) 500 ile istemciye gidiyordu; `__construct` çağrılabiliyordu.
- **F05** — adres parçası dosya yoluna ve `glob()` desenine süzülmeden
  giriyordu (`/stripe-*`); artık `[A-Za-z0-9_.-]` ile sınırlı. Bilinmeyen
  adres girişsiz ziyaretçiyi `/login`'e götürür (404/302 farkı sayfa
  varlığını ele vermez).
- **F13** — `TableModel` `0`/`'0'` filtrelerini atıyordu: filtre haritasında
  `'zero' => true` olan anahtar için artık geçerli değer.
- **F14** — `DB_PORT`/`DB_SOCKET` (web ve `migrate`), saat dilimi
  `APP_TIMEZONE`; `SITE_HTTP` vekil arkasında da doğru şema.
- **F16** — genel AJAX kancası: 403/404/405/419/422/429 da gösterilir, JSON
  olmayan gövde (nginx 502) konsolu düşürmez, sunucu metni HTML olarak
  değil düz metin olarak basılır, girişsiz 401'de `/login`'e gidilir,
  `deleteItemFromDB` `dataType: 'json'`; VNL Table hataları da aynı yoldan.
- **F19** — `LoginValidator` (`Session::GetSessionId()` → `Session::id()`,
  tanımsız `LogOut(false)`, hiç doğru olamayan `validateUser` koşulu),
  üst çubukta `Username` → `username`, `action_users.php` 0002 şeması ve
  VNL Table sözleşmesiyle yeniden yazıldı (yetki yükseltme korumalı),
  `TextGeneralModule` parola üretiminde `rand()` → `random_int()`.
- **F23** — `.htaccess` deseni `[a-zA-z0-9-_/]` noktalı adresleri dışarıda
  bırakıyordu; HTTPS kuralı hiçbir şey yapmıyordu; çekirdek/proje
  dizinlerindeki dosyalar (config, migrations, languages, files/cache …)
  artık reddedilir. Geliştirme yönlendiricisi yalnız varlık dizinlerini
  servis eder.
- **F26** — `Database::__construct(?Container $container = null)` (PHP 8.4).
- `UserModule::LogOut()` oturumu gerçekten kapatır (3.3'te `session_destroy`
  dalı hiç çalışmıyordu, temizliği görünüm yapıyordu) ve `$CurrentUser`'ı
  sıfırlar.

### Gözden geçirme düzeltmeleri (yayından önce)

Güvenlik ve işletim gözden geçirmesinde bulunanlar; hepsi bu sürümde.

- **Giriş hız sınırı eşzamanlı isteklerle aşılıyordu.** `tooMany()` yalnız
  okuyordu, sayaç başarısız bcrypt'ten sonra artıyordu: aynı anda gelen N
  istek N tahmin hakkı kazanıyordu. Artık deneme denetimden ÖNCE
  `RateLimiter::hit()` ile sayılır ve kapı sayımın sonucuna bakar (giriş ve
  TOTP). Başarılı deneme IP bütçesinden `RateLimiter::decrement()` ile geri
  düşülür.
- **Kullanıcı adı yazımlarıyla hesap sınırı atlatılıyordu.**
  `tbl_users.username` `utf8mb4_unicode_ci`'dir: `ädmin`, `ádmin`, tam
  genişlikli yazımlar `admin` satırını bulur ama her biri ayrı sayaç
  anahtarı alıyordu. Hesap anahtarı artık kayıtlı kullanıcı adından.
- **Başka bir adresten 5 yanlış parola yöneticiyi kilitliyordu.** Sıkı kilit
  (5) hesap + IP'ye, hesap geneli sınır 100'e (bir adres en fazla 5 katkı)
  taşındı.
- **IPv6 istemci bütün bir /64 ile IP sınırını atlatıyordu.**
  `RateLimiter::ipKey()`: IPv6 → /64 öneki, IPv4 ve `::ffff:` ile gelen IPv4
  → tam adres.
- **TOTP kodu pencere içinde yeniden kullanılabiliyordu** (RFC 6238 §5.2).
  `0007` + `User::claimTotpStep()` (koşullu UPDATE; paralel iki istekten
  biri kazanır); tekrar başarısız deneme sayılır.
- **`Users:edit` yetkili yönetici daha yetkili birinin parolasını
  sıfırlayıp onun yerine girebiliyordu.** `action_users::canManage()`:
  süper olmayan yönetici yalnız bütün yetenekleri kendisinde de olan
  kullanıcıyı düzenler, siler ya da yetkilerini değiştirir.
- **Yetki kancası silinince bütün kapılar açılıyordu.** Katı `vnl_can()`
  (bkz. kırıcı değişiklik 14); eksik kanca günlüğe yazılır.
- **Boşta kalma sınırı "beni hatırla" cihazlarında işlemiyordu** (F06.3;
  kırıcı değişiklik 13).
- **Denetim kaydı `ApiTokens`, `DbPasswords` gibi izin kodlarını düşürüyordu.**
  `user.permissions` artık `[{code, abilities}]` listesi yazar;
  `Audit` hassas anahtarın değerini düşürmek yerine `[redacted]` yazar
  (dizi değerler de).
- **Süresi dolan sekme 401 yerine 403 `VNL_CSRF` alıyordu.** Dağıtıcı girişi
  CSRF'ten önce denetler; vnl.js 403 `VNL_CSRF`'te sayfayı bir kez yeniler
  (başka sekmede giriş, "beni hatırla" yenilemesi).
- **`VNL_PUBLIC_DOCS=true` iken VNL Table demoları ziyaretçiyi `/login`'e
  atıyordu.** `action_vnltable::getList` `PUBLIC_METHODS`'ta; satır anahtarı
  ve değer uzunluğu denetlenir (oturum şişirilemez).
- **Apache + PHP-FPM'de Bearer jetonu PHP'ye ulaşmıyordu (F29).** `.htaccess`
  `Authorization` başlığını iletir; `doctor` denetler.
- **`$page_config['public']` uyarısı yalnız `APP_DEBUG=true` iken yazılıyordu
  (F05.3).** Artık her zaman.
- **Kuyruk: uzun iş ikinci işçiye veriliyor, eski işçinin sonucu yenisini
  eziyordu.** Asılı iş ölçüsü nabız (`updated_at`); bütün yazmalar ayırma
  jetonuna bağlı (`VnlJob::reservation()`, `VnlJobLostException`);
  `VnlJob::heartbeat()` ve beklerken nabız atan `VnlJob::exec()`.
- **Doğrulayıcılar kuyruk işçisini çıkış kodu 0 ile öldürüyordu.**
  `StandartException`, `EmptyDataException`, `AuthException` komut satırında
  yanıt basmaz, istisna fırlatır. İşçi ayrıca `exit`/`die`/ölümcül hata
  (bellek dahil) durumunda eldeki işi başarısız deneme olarak yazar.
- **İşleyici dosyası her işte yeniden `require` ediliyordu** — dosya
  düzeyinde fonksiyon tanımlayan işleyici ikinci işte "Cannot redeclare" ile
  işçiyi düşürüyordu. Artık süreç başına bir kez. Yetki önbelleği her işte
  sıfırlanır.
- **`schedule:run` kısa görevlerin kilidini öndeki uzun görev bitene kadar
  tutuyordu.** Her kilit kendi komutu biter bitmez bırakılır; kilit
  dosyaları close-on-exec.
- **`VnlCron` `*/n` gün alanını kısıtlı sayıyordu.** vixie/cronie gibi: `*`
  ile başlayan alan kısıtsız, `0 0 */2 * 1` yalnız tek günlere denk gelen
  pazartesiler.
- **`migrate` aynı numaralı ikinci migration'ı sessizce atlıyordu.** Aynı
  kaynakta tekrarlanan numara her komutu DDL çalışmadan durdurur ve
  uygulanmış olanı adıyla söyler; `record()` hatası ölümcül değil.
- **Belgelenen systemd birimi SIGTERM'i işin alt süreçlerine de
  gönderiyordu.** `KillMode=mixed`, `TimeoutStopSec`; `doctor` denetler.
- `doctor`: bekleyen çekirdek migration'ları (veritabanına bağlanabilirse),
  `.htaccess` Authorization iletimi, `queue:work` birimlerinde
  `KillMode=mixed`.
- Dokümanlar: `/docs/errors` (canlı/debug ayrımı), `/docs/models`
  (`'zero' => true`), `/docs/environment` (`APP_DEBUG`, `APP_TIMEZONE`,
  `DB_PORT`, `DB_SOCKET`, `LOG_PATH`, oturum anahtarları, `utf8mb4`),
  `/docs/theme-sidebar` (menü parçaları), `/docs/languages` (dil parçaları),
  `/docs/security`, `/docs/session`, `/docs/cli-queue`.

### Sunucu testi düzeltmeleri (yayından önce)

Ubuntu 24.04 üzerinde nginx + Apache (`.htaccess`) + PHP 8.3-FPM + MariaDB
10.11 ve systemd altındaki kuyruk işçisiyle yapılan uçtan uca testte
bulunanlar.

- **Apache'de dizin adıyla aynı sayfa adresi 403 dönüyordu.** `/jobs`,
  `/config`, `/commands` gibi adresler şablonda o adla bir dizin olduğu için
  engelleniyordu; `.htaccess`'teki açıklama ve nginx bunları sayfa olarak
  ön denetleyiciye veriyordu. Artık yalnız bu dizinlerin İÇİNDEKİ var olan
  dosya/dizinler engellenir, dizinin kendi adı `index.php`'ye gider (Apache
  eğik çizgisiz `/jobs`'u önce `/jobs/`'a yönlendirir).
- **Apache `web.config`'i düz metin olarak veriyordu.** `.htaccess`
  `FilesMatch` listesine eklendi.
- **Kullanıcı listesinde `perm_name` yanlıştı.** `User::SelectList()` rolü
  (`perm_id`) 3.4.0'da izin kodu tablosu olan `tbl_permissions` ile
  birleştiriyordu: yeni kurulumda süper yönetici "Kullanıcılar" rolünde
  görünüyordu. Birleştirme kaldırıldı; `action_users` satırı `perm_name`
  yerine `super` (bool) taşır.
- `doctor` ve `/docs/security`: `Authorization` iletilmeyen Apache +
  PHP-FPM'de Bearer isteği 403 `VNL_CSRF` değil 401 `VNL_AUTH` alır (giriş
  CSRF'ten önce denetlendiği için); metinler düzeltildi.

### Yükseltme (3.3.x → 3.4.0)

1. `vnl/` dizinini değiştirin (`php vnl.php update --to 3.4.0`).
2. Proje dosyalarını şablondakilerle değiştirin ya da farkı uygulayın —
   güncelleme bunlara dokunamaz, `php vnl.php doctor` eksikleri listeler:
   `controller/app.php`, `actions/Auth.php`, `actions/action_login.php`,
   `actions/action_users.php`, `validators/LoginValidator.php`,
   `classes/User.php`, `classes/Permission.php`, `classes/UserPermissions.php`,
   `helpers/permissions.php` (yeni), `config/definitions.php`
   (`login_except_pages`, DB), `config/schedule.php` (yeni),
   `controller/logout.php`, `view/logout.php`, `controller/401.php`,
   `controller/404.php`, `controller/403.php` + `view/403.php` (yeni),
   `view/login.php`, `view/includes/footer/script-login.php`,
   `view/includes/static/head-standard.php` (CSRF meta),
   `view/static/topNavbar.php`, `view/static/leftSidebar.php`,
   `languages/*.json`, `.htaccess`, `.env` (yeni anahtarlar).
3. Kendi action'larınız: her dosyanın başına `use ActionGlobal\Auth;`, her
   metodun ilk satırına `Auth::CheckPermission('Kod', 'yetenek');`; girişsiz
   çağrılan metotlar `PUBLIC_METHODS`'a; yardımcı metotlar `private`.
4. Kendi `fetch`/form POST'larınıza CSRF jetonu.
5. `$page_config['public']` kullanan sayfaları `$login_except_pages`'e taşıyın.
6. `php vnl.php migrate --run` (`0002`–`0007`; `0007` olmadan iki adımlı
   giriş kapalıdır), sonra kullanıcı yoksa
   `php vnl.php user:create --username=admin --super`.
7. `.env`: `APP_DEBUG=false` (canlı), `APP_ENCRYPTION_KEY` (TOTP için);
   "beni hatırla" boşta kalmada da sürsün isteniyorsa
   `VNL_IDLE_KEEPS_REMEMBER=true`.
8. İsteğe bağlı: `* * * * * php vnl.php schedule:run` cron satırı ve
   `php vnl.php queue:work` için bir systemd birimi — `KillMode=mixed` ve
   en uzun işten uzun `TimeoutStopSec` ile (`/docs/cli-queue`).
9. Apache + PHP-FPM'de API jetonları için `.htaccess`'teki
   `Authorization` iletim satırları (ya da `CGIPassAuth On`).

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

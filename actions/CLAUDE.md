# Eylem katmanı

`actions/action_<ad>.php` → `POST /app/<ad>/<metot>`. Dosya bir sınıf içerir,
sınıfın adı dosyanın adıyla aynıdır.

```php
use ActionGlobal\Auth;

class action_urun
{
    public function kaydet($req)
    {
        Auth::CheckPermission('Urunler', 'insert');   // 401 girişsiz, 403 yetkisiz
        DefaultValidator::validateEmpty($req->post('ad'), 'Ad');

        $urun = Urun::create(['ad' => $req->post('ad')]);
        Audit::log('urun.create', 'urun', $urun->id);

        returnSuccessMessage('Kaydedildi.');
    }
}
```

## Dağıtıcının kuralları (3.4.0, `vnl/classes/VnlDispatcher.php`)

- **Yalnız POST.** GET/PUT/… → 405 `VNL_METHOD`.
- **CSRF zorunlu.** `X-CSRF-Token` başlığı (vnl.js ve VNL Table her isteğe ekler)
  ya da gövdede `_token` (`<?= vnl_csrf_field() ?>`). Yoksa 403 `VNL_CSRF`.
- **Giriş varsayılan olarak zorunlu.** Girişsiz çağrılacak metot sınıf sabitinde
  listelenir; imzasını kendisi doğrulayan webhook CSRF'ten muaf tutulur:

  ```php
  public const PUBLIC_METHODS = ['login'];
  public const CSRF_EXEMPT    = ['webhook'];
  ```
- Yalnız `public`, static olmayan, `__` ile başlamayan metotlar çağrılabilir;
  yardımcı metotları `private` yazın.
- `throwErrorMessage($mesaj, $kod)` kodunu korur (422 → 422). Başka her istisna
  günlüğe yazılır, kullanıcı genel metin + başvuru kodu görür (500 `VNL_FATAL`).
- 3.4.1: `VnlHttpException` gövdeye ek alan ve yanıta başlık taşır —
  `new VnlHttpException($m, 422, 'VALIDATION', null, ['errors' => [...]], ['Retry-After' => 5])`
  ya da alt sınıfta `extra()` / `headers()`. Ek alanlar `message`/`devcode`'u ezemez.
- `Authorization: Bearer <jeton>` (API jetonu) oturum + CSRF yerine geçer.

## Kural: yetki modülü sorar, sahiplik kaydı (IDOR)

`Auth::CheckPermission('Faturalar', 'edit')` "bu kullanıcı faturaları düzenleyebilir
mi" sorusuna cevap verir; **"bu fatura bu kullanıcının mı"** sorusuna değil. İstekten
gelen bir kimlikle (`$req->post('id')`) kayıt açan her action, kaydın kullanıcıya
ait olduğunu da denetler — aksi halde `id=42` yerine `id=43` yazan başkasının
faturasını görür ya da değiştirir.

Denetim modelde, sorgunun içinde durur (aşağıdaki kural); ayrı bir `if` ile değil:

```php
// classes/Fatura.php
public static function findOwned(int $id, int $userId): ?self
{
    return self::where('id', $id)->where('user_id', $userId)->first();
}

// actions/action_fatura.php
Auth::CheckPermission('Faturalar', 'edit');
$fatura = Fatura::findOwned((int)$req->post('id'), (int)$CurrentUser->id)
    ?? throw new VnlHttpException('Kayıt bulunamadı.', 404, 'NOT_FOUND');
```

Başkasının kaydı için **404** dönün, 403 değil: 403 kaydın var olduğunu söyler.
Yöneticinin herkesin kaydını görmesi gerekiyorsa bu da modelde, açık bir dalla
yazılır (`findOwnedOrAdmin`), action'a dağılmaz.

## Kural: action içinde sorgu kurulmaz

**Action ince bir kapıdır** — isteği karşılar, doğrular, işi modele devreder, yanıtı
döndürür. Çok ağır bir zorunluluk olmadıkça burada sorgu yazılmaz ve `Capsule::`
doğrudan kullanılmaz.

```php
// YANLIŞ — sorgu action'da
$urunler = Capsule::table('tbl_urunler')->where('aktif', 1)->get();

// DOĞRU — sorgu modelde, action yalnız çağırır
$urunler = Urun::SelectList(['aktif' => 1], ['limit' => 20])->get();
```

Sebebi tek yerde tutmak: aynı liste üç action'da üç kez yazılmaz, sütun değiştiğinde
tek sınıf güncellenir. Model sözleşmesi için `classes/CLAUDE.md`.

## Yanıt fonksiyonları betiği bitirir

```php
returnSuccessMessage($mesaj, $data)   // 200  {message, data}
returnSuccessDataOnly($data)          // 200  ham veri (3.4: sayıya çevirmez, "0755" dizge kalır)
returnErrorMessage($mesaj, $kod)      // 400  {message, devcode}
throwErrorMessage($mesaj, $kod)       // istisna fırlatır, dağıtıcı JSON'a çevirir (kod korunur)
```

`StandartException` ve `EmptyDataException` da `returnErrorMessage()` üzerinden
`{message, devcode}` basıp durur. **`success` diye bir alan yoktur.**

Doğrulayıcılar bu istisnaları fırlatır, yani `DefaultValidator::validateEmpty()`
başarısız olduğunda alt satır hiç çalışmaz — `if` ile sarmanız gerekmez.

## Girdi

`$req` global olarak hazırdır; `$_POST` yerine onu kullanın. JSON gövde de aynı
nesneden okunur.

```php
$req->post('ad')      $req->get('id')       $req->postHas('ad')
$req->postAll()       $req->isPost()        $req->method()
```

**Gelen veri kaçışlanmaz** — ne gönderildiyse o okunur ve o saklanır. Kaçış ekrana
basarken yapılır (`{{ }}` ya da `vnl_e()`).

Dosya yüklemesi için `FileRequest` **elle** oluşturulur (`$req->file()` diye bir
metot yoktur):

```php
$fileReq = new FileRequest();          // $_FILES'i normalize eder, adı temizler
$dosya   = $fileReq->get('dosya');
FileManagerModule::UploadFile($hedef, $dosya, 'pdf,docx');
```

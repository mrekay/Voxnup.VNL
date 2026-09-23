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

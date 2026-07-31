# Eylem katmanı

`actions/action_<ad>.php` → `POST /app/<ad>/<metot>`. Dosya bir sınıf içerir,
sınıfın adı dosyanın adıyla aynıdır.

```php
class action_urun
{
    public function kaydet($req)
    {
        Auth::CheckLogin();
        DefaultValidator::validateEmpty($req->post('ad'), 'Ad');

        Urun::create(['ad' => $req->post('ad')]);

        returnSuccessMessage('Kaydedildi.');
    }
}
```

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
returnSuccessDataOnly($data)          // 200  ham veri
returnErrorMessage($mesaj, $kod)      // 400  {message, devcode}
throwErrorMessage($mesaj, $kod)       // istisna fırlatır, dağıtıcı JSON'a çevirir
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

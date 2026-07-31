# VNL

PHP 8.2 uygulama şablonu. Kurduğunuz bir kütüphane değil — klonlanıp üstüne yazılan
bir başlangıç noktası. Composer ve npm gerekmez; bağımlılıklar repoda gelir.

Bu dosya haritadır. Ayrıntı uygulamanın içindedir: `/docs/architecture`,
`/docs/versioning`, `/docs/getting-started` ve yanlarındakiler.

---

## Felsefe

Aşağıdakiler üslup tercihi değil; VNL'in kararlarının çoğu bunlardan türüyor.
Bir şeyi neden öyle yapmanız gerektiğini merak ederseniz cevap büyük ihtimalle burada.

**1. Sınır kutsaldır.** `vnl/` VNL'indir, kökteki geri kalan her şey projenindir.
Çekirdeği değiştirmek isterseniz `vnl/` altına dokunmayın — **aynı adlı dosyayı kendi
tarafınıza koyun**, çözümleme önce oraya bakar. Güncelleme `vnl/` dizinini komple
değiştirir; oradaki her düzenleme kaybolur.

**2. Herkesin ezdiği şeyin sahibi olunmaz.** Çekirdek tek bir görünüm ve tek bir dil
dosyası taşımaz — 401/404 dahil. Düzen ve metin her projede değişir; "varsayılan"
diye bir şey tutmak sahte sahiplik olurdu. Bunun bedeli bilinçli kabul edildi:
çekirdek de `lang()` çağırır, anahtar projede yoksa ekranda anahtarın adı görünür.

**3. Tek doğruluk kaynağı.** Sürüm yalnız `vnl/VERSION`'da (dosya başlıklarına
yazılmaz). Dil anahtarları yalnız `languages/*.json`'da — sınıfın içinde tanım
tutulmaz ki dosya ile kod arasında kayma oluşamasın. Çekirdeğin bütünlüğü yalnız
`vnl/MANIFEST`'te.

**4. Katman disiplini.** Action ince bir kapıdır: isteği karşılar, doğrular, işi
modele devreder, yanıtı döndürür. **Action içinde sorgu kurulmaz**, `Capsule::`
doğrudan kullanılmaz. Sorgular modelde tek yerde durur; sütun değişince tek sınıf
güncellenir.

**5. Kaçış çıkışta yapılır.** Gelen veri girişte bozulmaz — veritabanına ne
gönderildiyse o yazılır. XSS koruması ekrana basarken devreye girer. Bunun doğrudan
sonucu: **`<?= $x ?>` kaçışsızdır**, kaçışlı olan `{{ $x }}` ve `vnl_e($x)`.

**6. Bağımlılık, çalışmayacağı anda yanınızda olmaz.** Kurulumda composer ve npm
gerekmez. Excel çıktısı `ZipArchive` olmadan, güncelleme arşivi `phar` olmadan
çalışır — çünkü ikisi de paylaşımlı barındırmada kapalı olabiliyor ve güncelleme,
eksik bir eklentiyle uğraşılacak an değil.

**7. Sessiz bozulma yok; ama gürültü de doğru yerde.** Eksik dil anahtarı isteği
düşürmez, anahtarın kendisini basar. Buna karşılık `$columns`'ta tanımsız bir filtre
anahtarı isteği **düşürür** — çünkü dışarıdan gelen ad doğrudan sütuna çevrilseydi
filtre listesi bir enjeksiyon yüzeyi olurdu. Migration'ların hangisinin çalıştığı
veritabanında tutulur, tahmin edilmez.

---

## Sınır, somut olarak

| | |
| --- | --- |
| `vnl/` | **VNL'in.** Buraya yazmayın. |
| Kökteki geri kalan her şey | **Projenin.** Güncelleme dokunmaz. |

Laravel'de bu sınırın adı `vendor/`'dır; VNL kendi kodu için composer kullanmadığından
sınırı dizinin kendisi taşır.

```
classes/Request.php      →  vnl/classes/Request.php yerine geçer (o hiç yüklenmez)
modules/MailModule.php   →  vnl/modules/… yerine geçer
validators/  helpers/    →  çekirdekle birleşir, ad çakışırsa proje kazanır
```

Ezme **dosya bazındadır, satır bazında değil**: tek bir metot için dosyanın tamamını
devralırsınız ve o dosyaya gelen VNL düzeltmeleri artık size ulaşmaz. Son çare.

## Giriş noktaları

```
index.php    web     — VNL_ROOT'u tanımlar, vnl/boot.php + vnl/kernel.php'yi çağırır
vnl.php      konsol  — aynısının komut satırı karşılığı (Windows'ta vnl.cmd)
```

İkisi de projenindir ama içlerinde proje mantığı yoktur. **Yönlendirme
`vnl/kernel.php`'dedir**, `index.php`'de değil. Her yerde `VNL_PATH` (çekirdek) ve
`VNL_ROOT` (proje) sabitleri geçerlidir; `DOCUMENT_ROOT` ya da `realpath('.')`
kullanmayın.

## Sayfa ve eylem eklemek

Sayfa iki dosyadır, sınıf yoktur:

```php
// controller/urunler.php
$meta        = ['title' => 'Ürünler'];
$page_config = ['layout' => 'app'];      // app|top-nav|landing|focus|blank
require view('static/header');
require view('urunler');
require view('static/footer');
```

Eylem `/app/<dosya>/<metot>` adresine düşer — ayrıntı ve kurallar için
`actions/CLAUDE.md`, model sözleşmesi için `classes/CLAUDE.md`.

## Konsol

```
php vnl.php migrate --run     bekleyen şema değişikliklerini uygula
php vnl.php update --check    yeni çekirdek sürümü var mı
php vnl.php doctor            kurulumu çekirdeğin beklentisine karşı sına
php vnl.php serve             yerel geliştirme sunucusu
```

`vnl/migrations/` VNL'in şeması, `migrations/` sizinki; sayaçlar bağımsız yürür.
MySQL'de `ALTER TABLE`'ın `IF EXISTS`'i olmadığı için var olmayabilecek bir tabloyu
değiştiren migration koşullu yazılmalıdır — örneği `vnl/migrations/0001`'de.

## Doğrulama

**Bu makinede PHP CLI yok**; her şey Docker ile koşulur.

```bash
# Söz dizimi
docker run --rm -v "$PWD":/app -w /app php:8.2-cli \
  sh -c 'for f in $(find . -name "*.php" -not -path "./vnl/vendor/*"); do php -l $f; done' \
  | grep -v "^No syntax"

# Yerel sunucu (PHP_CLI_SERVER_WORKERS olmadan tarayıcı testleri zaman aşımına düşer)
docker run --rm -p 8091:8091 -e PHP_CLI_SERVER_WORKERS=4 -v "$PWD":/app -w /app \
  php:8.2-cli php -S 0.0.0.0:8091 vnl/tools/dev-router.php
```

Bir şeyi kırdıysanız ilk `php vnl.php doctor`'a bakın.

**Görünüm değiştirdiyseniz `files/cache/views/` temizlenmeli** — şablon derleyicisi
çıktıyı önbelleğe alır, düzenlemeniz görünmez.

Çalışma anında birleştirilen varlık yolları (`'…' + ad + '.css'`) metin aramasıyla
bulunmaz; taşıma sonrası tarayıcıyla 404 taraması yapın. Varlık adresleri için
`vnl_asset()` (çekirdeğin) ve `public_url()` (projenin) kullanılır.

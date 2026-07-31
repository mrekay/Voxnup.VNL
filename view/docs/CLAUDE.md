# Docs sayfaları

Her sayfa iki dosyadır: `controller/docs/<ad>.php` (meta + üç `require`) ve
`view/docs/<ad>.php` (içerik). Yeni sayfa eklerken üçüncü bir yer daha var —
`view/static/leftSidebar.php` menüsü ve `config/definitions.php`'deki
`$login_except_pages`; ikincisi olmadan sayfa girişe yönlenir.

## Süslü parantez sayfayı 500 yapar

Bu dosyalar `view()` üzerinden geçer, yani **şablon derleyicisinden** geçer. Metinde
`{{` yazarsanız derleyici onu ifade sanar ve kendi belgenizi derler:

```
YANLIŞ:  <code>{{ $ad }}</code>          → Too few arguments to function vnl_e()
DOĞRU:   <code>&#123;&#123; $ad }}</code>
```

Aynısı `@if`, `@foreach`, `@include` için de geçerli — kaçırmak için `&#64;`.
Bu oturumda iki sayfa bu yüzden düştü; sayfayı yazdıktan sonra **mutlaka açıp bakın**.

## Doğrulama

```bash
# Söz dizimi
docker run --rm -v "$PWD":/app -w /app php:8.2-cli php -l view/docs/<ad>.php

# Önbelleği temizleyin, yoksa düzenlemeniz görünmez
rm -f files/cache/views/*.php

# Sayfa gerçekten açılıyor mu
curl -s -o /dev/null -w '%{http_code}\n' http://localhost:8091/docs/<ad>
```

**`div` dengesini sayın.** Fazladan bir `</div>` `card-body p-10` kabını erken kapatır
ve sonraki bölümler sayfa kenarına yapışır — tarayıcıda fark edilmesi zor, aramayla
hiç bulunmaz:

```bash
python -c "import io,re;t=io.open('view/docs/<ad>.php',encoding='utf-8').read();\
print(len(re.findall(r'<div\b',t))-len(re.findall(r'</div>',t)))"   # 0 olmalı
```

## Yazarken

Belgelenen her iddiayı **koda karşı doğrulayın**. Bu sayfalarda geçmişte var olmayan
metotlar (`MailModule::PrepareMail()`, `$req->file()`), olmayan dil anahtarları ve
çalışmayan bir örnek model aylarca durdu — hepsi makul görünüyordu.

Varlık yolları için `vnl_asset()` kullanın, elle `/assets/...` yazmayın. Çalışma
anında birleştirilen yollar (`'…' + name + '.css'`) taşımalarda sessizce kırılır.

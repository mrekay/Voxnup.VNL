# social-auth — Google, Apple, Microsoft, Facebook ile giriş

Bağımlılık yok (openssl + curl). Sağlayıcı SDK'sı yüklenmez; yalnız Google'ın kendi
düğmesi (GIS) için `accounts.google.com/gsi/client`.

| Sağlayıcı | Akış | Gizli anahtar | Geri dönüş adresi |
|---|---|---|---|
| Google | GIS düğmesi → `POST /app/social/google` (id_token) | yok | — (JS origin) |
| Apple | popup → `/oauth/apple` → form_post id_token | yok | `/oauth/apple/callback` |
| Microsoft | popup → `/oauth/microsoft` → form_post id_token | yok | `/oauth/microsoft/callback` |
| Facebook | popup → `/oauth/facebook` → code → Graph API | App Secret | `/oauth/facebook/callback` |

Anahtarı boş olan sağlayıcının düğmesi çıkmaz, ucu 404 döner. Gömülü WebView'lerde
(`config.php` → `blocked_user_agents`) hiçbir düğme çıkmaz: sağlayıcılar orada girişi reddeder.

## Projeye bağlama

1. Klasörü `extensions/social-auth/` olarak kopyala; `extensions/extensions.php` →
   `'social-auth' => true`. Anahtarlar `.env`'de (bkz. `config.php`).
2. Kancayı tanımla (ör. `helpers/` altında):

```php
/**
 * $profile = [provider, sub, email, name, picture]
 *   sub   sağlayıcının kalıcı kimliği — hesabı BUNUNLA eşleştir (provider + sub)
 *   email YALNIZ sağlayıcı doğruladıysa dolu, yoksa null. Doğrulanmamış e-posta
 *         (Microsoft iş hesabı, nOAuth) hiç gelmez; e-postayla hesap bağlamak güvenli.
 */
function vnl_social_sign_in(array $profile): array
{
    // bul / bağla / aç, oturumu başlat
    return ['ok' => true, 'message' => '', 'data' => ['status' => 'login']];
    // hata: ['ok' => false, 'status' => 400, 'message' => '...']
}

/** Meta veri silme isteği (isteğe bağlı). */
function vnl_social_forget(string $provider, string $sub): void { /* bağlantıyı sil */ }
```

3. Düğmeleri bas ve sonucu dinle:

```php
<?= class_exists('SocialAuth') ? SocialAuth::buttons(['context' => 'signin', 'locale' => 'tr', 'aria' => '%s ile devam et']) : '' ?>
```

```js
document.addEventListener('vnl:social-auth', (e) => {
    const r = e.detail; // {ok, status, message, data, cancelled?} — data kancanın döndüğü
    if (r.cancelled) return;
    if (r.ok) location.href = '/';
});
```

Popup açılamazsa (engelleyici) tam sayfa yönlendirmeye düşer; dönüşte sonuç oturumda
bekler: sayfa `SocialAuth::takeFlash()`'i okuyup aynı işleyiciye vermeli.

Diğer olaylar: `vnl:social-auth-start` (Google jetonu sunucuya gidiyor),
`vnl:social-auth-open` (popup açıldı).

## Sağlayıcı kurulumları

Aşağıda `{site}` = sitenizin kökü, ör. `https://example.com`.

### Google — Google Cloud Console
- APIs & Services → Credentials → **OAuth client ID**, tür *Web application*.
- **Authorized JavaScript origins**: `{site}` (varsa `www` hâli), yerel `http://localhost:8080`.
- OAuth consent screen: ad, logo, gizlilik politikası, yetkili alan adı; **In production**.
- `.env`: `GOOGLE_OAUTH_CLIENT_ID` (Client ID, `…apps.googleusercontent.com`). Secret gerekmez.

### Apple — Apple Developer Program (yıllık ücretli)
- Identifiers → App ID'de *Sign in with Apple* açık.
- Identifiers → **Services ID** (ör. `com.example.web`) → Sign in with Apple → Configure:
  Domains `example.com`, Return URLs `{site}/oauth/apple/callback`.
  Apple **localhost kabul etmez**; test beta'da.
- **Hide My Email**: kullanıcı adresini gizlerse `@privaterelay.appleid.com` gelir. O adrese
  mail (parola sıfırlama, duyuru) gidebilmesi için *Certificates, Identifiers & Profiles →
  Services → Sign in with Apple for Email Communication*'da gönderen alan adı/adres
  kaydedilmeli (SPF ile).
- `.env`: `APPLE_SERVICES_ID`.
- Ad yalnız **ilk** girişte gelir (Apple bir daha göndermez).

### Microsoft — Microsoft Entra admin center (ücretsiz)
- App registrations → New: *Accounts in any organizational directory and personal
  Microsoft accounts*.
- Authentication → platform **Web**, Redirect URI `{site}/oauth/microsoft/callback`
  (yerel: `http://localhost:8080/oauth/microsoft/callback`); **ID tokens** kutusu işaretli.
- Token configuration → Add optional claim → ID → `email` ve **`xms_edov`**. xms_edov
  yoksa iş/okul hesaplarının e-postası doğrulanmamış sayılır (kişisel hesaplar etkilenmez).
- Branding: logo, ana sayfa, gizlilik adresi. **Publisher verification** yapılmazsa onay
  ekranında "doğrulanmamış" yazar (Microsoft Partner Network kimliği gerekir).
- `.env`: `MICROSOFT_CLIENT_ID` (Application/client ID). Secret gerekmez.

### Facebook — Meta for Developers
- App oluştur (use case: *Authenticate and request data from users with Facebook Login*).
- Facebook Login → Settings → **Valid OAuth Redirect URIs**: `{site}/oauth/facebook/callback`.
- App Settings → Basic: gizlilik politikası, uygulama ikonu, kategori ve **Data Deletion
  Request URL**: `{site}/oauth/facebook/delete` (signed_request'i App Secret'la doğrular,
  `vnl_social_forget` kancasını çağırır).
- `email` izni canlıda **Advanced Access** ister → **Business Verification** (şirket belgeleri).
  Onay çıkana kadar e-posta gelmez; hesap e-postasız açılır.
- Uygulamayı **Live** moda al (Development'ta yalnız uygulama rolündekiler girebilir).
- `.env`: `FACEBOOK_APP_ID`, `FACEBOOK_APP_SECRET`. Graph sürümü `config.php`'de
  (`v26.0`); Meta sürümleri ~2 yılda kaldırır.

## Güvenlik

- İmzalı jetonlar (Google/Apple/Microsoft): RS256'ya sabit; imza sağlayıcının JWKS'iyle
  (önbellekli); `iss`, `aud`, `exp/iat/nbf` (60 sn tolerans), **nonce**.
- Microsoft: `iss` jetonun kiracısına (tid) göre beklenir; e-posta yalnız kişisel hesapta ya
  da `xms_edov` doğrulanmışsa profile girer.
- Yönlendirme durumu (state + nonce) 10 dakikalık `__vnl_oauth` çerezinde
  (`SameSite=None; Secure; HttpOnly; Path=/oauth/`): Apple ve Microsoft dönüşü siteler arası
  POST olduğundan oturum çerezi gelmez. state tutmazsa giriş reddedilir (giriş CSRF'i).
- Google nonce'u oturumda, tek kullanımlık.
- `POST /app/social/google` girişsiz çağrılabilir (`PUBLIC_METHODS`), ama CSRF denetimi
  açıktır: düğme betiği jetonu `<meta name="csrf-token">`'den alıp `X-CSRF-Token` ile
  gönderir. Düğmenin basıldığı sayfanın başlığında bu meta bulunmalı (`head-standard.php`).
- Facebook: kod sunucuda takas edilir, Graph çağrıları `appsecret_proof` ile.
- Dönüş yolu (`ret`) yalnız site içi göreli adres olabilir (açık yönlendirme yok).
- IP başına 10 dakikada 30 deneme: varsayılan VNL `RateLimiter` (`tbl_vnl_rate_limits`);
  proje `config.php` → `rate_limit` (`fn(string $ip): bool`) ile kendi sayacını verebilir.

# VNL

![Version](https://img.shields.io/badge/version-3.5.0-blue.svg)
![License](https://img.shields.io/badge/license-MIT-green.svg)
![Build Status](https://img.shields.io/badge/build-passing-brightgreen.svg)

**VNL**, [Voxnup Not Laravel].

VNL, PHP 8.2 üzerine kurulu bir **uygulama şablonudur** — kurduğunuz bir kütüphane değil,
klonlayıp üstüne yazdığınız bir başlangıç noktası. Yönlendirme, Eloquent (Illuminate/Capsule)
ORM, `{{ }}` şablon derleyicisi, oturum ve yetkilendirme, dil sistemi, migration'lar, bir
konsol ve kendi tema katmanı hazır gelir; kurulumda ne composer ne npm gerekir.

## 📋 İçindekiler

- [Özellikler](#-özellikler)
- [VNL Theme](#-vnl-theme)
- [Çekirdek ve Proje](#-çekirdek-ve-proje)
- [Sürüm ve Şema](#️-sürüm-ve-şema)
- [Konsol](#️-konsol)
- [Gereksinimler](#-gereksinimler)
- [Kurulum](#-kurulum)
- [Dokümantasyon](#-dokümantasyon)

## ✨ Özellikler

* **Hızlı ve Hafif:** Minimum kaynak tüketimi ile maksimum performans.
* **Modüler Yapı:** İhtiyacınız olan bileşenleri kolayca ekleyip çıkarın.
* **Çekirdek/Proje Ayrımı:** `vnl/` VNL'in, geri kalan her şey sizin. Güncelleme
  tek bir dizini değiştirmekten ibarettir.
* **Hızlı Veritabanı Erişimi:** Eloquent ile hızlı kurulum ve kullanım.
* **VNL Theme:** Bootstrap 5.3 tabanlı, koyu-varsayılanlı, layout preset'li kendi tema katmanı.
* **Varsayılan olarak güvenli (3.4):** CSRF jetonu, yalnız-POST action'lar, girişi
  varsayılan olarak zorunlu kılan dağıtıcı, sayfa/action yetki kapıları
  (`vnl_require_permission()`, `Auth::CheckPermission()`), giriş hız sınırı, iki adımlı
  doğrulama (TOTP), denetim kaydı (`Audit::log()`), canlıda ayrıntısız hata sayfası.
* **Kuyruk ve zamanlayıcı (3.4):** `Queue::push()` + `php vnl.php queue:work`,
  `config/schedule.php` + `php vnl.php schedule:run`, projenin kendi konsol komutları
  (`commands/`).

## 🎨 VNL Theme

VNL 3.0 ile Metronic bağımlılığı tamamen kaldırıldı. Arayüz, Bootstrap 5.3.8 üzerine
inşa edilen **VNL Theme** ile gelir:

- **Koyu/teknik varsayılan kimlik** — açık tema birinci sınıf (`data-bs-theme`, kullanıcı
  tercihi `localStorage` ile kalıcı; JS kapalıyken de koyu açılır).
- **Layout preset'leri** — aynı tema bir ERP'yi (`app`), bir aracı (`top-nav`), bir pazarlama
  sayfasını (`landing`) ve login/hata ekranlarını (`focus`, `blank`) taşır:
  `$page_config['layout'] = 'app';`
- **Self-host her şey** — Inter variable (latin-ext dahil), Font Awesome Free 7, jQuery 3.7,
  SweetAlert2 v11, SlimSelect. Hiçbir CDN'e istek atılmaz.
- **Tarih ve saat seçici** — `<input data-vnl-date>` (tarih, saat, ay, aralık); ekranda Türkçe,
  sunucuya ISO. Altyapı flatpickr, çekirdekle gelir, tema token'larını okur.
- **Derlenmiş çıktı repodadır** (`vnl/assets/theme/`) — kurulumda Node/npm/build **gerekmez**.

Tema kaynağı ayrı bir depodadır: **[VNL-THEME](https://github.com/mrekay/VNL-THEME)**.
Temayı özelleştirmek isteyenler onu klonlayıp çıktıyı doğrudan bu projeye derler:

```bash
git clone https://github.com/mrekay/VNL-THEME.git
cd VNL-THEME && npm ci
VNL_OUT=/yol/VNL-TEMPLATE/vnl/assets/theme npm run build
```

Ayrıntılar için uygulama içindeki **/docs/theme** sayfasına bakın.

## 📁 Çekirdek ve Proje

VNL 3.0 ile depodaki her dosyanın **tek bir sahibi** var ve sahibi yolundan belli:

| | |
| --- | --- |
| `vnl/` | **VNL'in.** Buraya hiçbir şey yazmayın; güncellemede komple değişir. |
| Kökteki geri kalan her şey | **Sizin.** VNL güncellemesi buraya hiç dokunmaz. |

Laravel'de bu sınırın adı `vendor/`'dır. VNL kendi kodu için composer kullanmadığından
sınırı dizinin kendisi taşır — ve `vnl/` repoda gelir, indirilmez.

Çekirdek **tek bir görünüm bile taşımaz** (401/404 dahil) ve dil dosyaları da sizdedir:
düzen ve metin projeden projeye değişir, herkesin ezdiği bir varsayılanın sahibi
olmak anlamsızdır. Bir çekirdek sınıfını/modülünü değiştirmek isterseniz `vnl/` altına
dokunmayın, kendi tarafınıza **aynı adlı** dosyayı koyun — çözümleme önce size bakar.

```bash
# Güncelleme:
rm -rf vnl/            # ve yeni sürümün vnl/ dizinini yerine koyun
```

Ayrıntılar için uygulama içindeki **/docs/architecture** sayfasına bakın.

## 🏷️ Sürüm ve Şema

Bir kurulumun iki sürümü vardır ve ayrı yürürler:

| | |
| --- | --- |
| **Kod** | `vnl/VERSION` — çekirdeğin sürümü. Tek doğruluk kaynağı burasıdır; dosya başlıklarına yazılmaz. |
| **Şema** | Veritabanındaki `tbl_vnl_migrations` tablosu — hangi migration'ların uygulandığı. |

Sürüm numaralandırma [SemVer](https://semver.org/lang/tr/); ne değiştiği
[CHANGELOG.md](CHANGELOG.md)'de. Ana sürüm atlıyorsanız **Kırıcı Değişiklikler**
bölümü aynı zamanda göç rehberidir.

Şema değişiklikleri elle değil araçla uygulanır — böylece neyin çalıştığı
kayda geçer ve aynı migration ikinci kez işlenmez:

```bash
php vnl.php migrate --status         # ne uygulanmış, ne bekliyor
php vnl.php migrate --run            # bekleyenleri sırayla uygula
php vnl.php migrate --run --dry-run  # uygulamaz, çalışacak SQL'i basar
```

Çekirdeğin migration'ları `vnl/migrations/`, sizinkiler `migrations/`
altındadır; numaraları bağımsız yürür, bir VNL yükseltmesi sizin sıranızla
çakışmaz.

Çekirdeği güncellemek de aynı konsoldan:

```bash
php vnl.php update --check      # yeni sürüm var mı, neler değişti
php vnl.php update --to 3.5.0   # yalnız vnl/ değişir; vnl.bak/ yedeği kalır
php vnl.php doctor              # proje tarafı yeni çekirdeğe uygun mu
php vnl.php migrate --run       # şemayı güncelle
```

`update`, yazmadan önce kurulu çekirdeği `vnl/MANIFEST`'e karşı sınar; birileri
`vnl/` altını değiştirmişse durur, çünkü aksi halde o değişiklikler sessizce
silinirdi. Ters giderse `php vnl.php update --rollback`.

Ayrıntılar **/docs/versioning** sayfasında.

## 🖥️ Konsol

```bash
php vnl.php                                   # komut listesi (çekirdek + commands/)
php vnl.php serve                             # yerel geliştirme sunucusu
php vnl.php version                           # çekirdek ve proje sürümleri
php vnl.php user:create --username=admin --super   # ilk yönetici
php vnl.php queue:work --queue=default        # kuyruk işçisi (systemd altında)
php vnl.php schedule:run                      # cron: * * * * *
```

Projenin kendi komutları `commands/<ad>.php` dosyalarıdır (`backup:prune` →
`commands/backup-prune.php`); sözleşme `commands/README.md`'de.

Windows'ta `vnl.cmd` kısayolu vardır (`vnl migrate --run`; PowerShell'de
`.\vnl`), Unix'te `chmod +x vnl.php` sonrası `./vnl.php`. Adı düz `vnl` olamaz:
bir dizinde aynı adı taşıyan dosya ve dizin bir arada bulunamaz.

## 🚀 Gereksinimler

* PHP 8.2 veya üzeri (`mbstring`, `pdo_mysql`, `openssl`; kuyruk işçisi için `pcntl` önerilir).
  **Güvenlik yaması zorunlu:** 8.2.35 / 8.3.36 / 8.4.27 / 8.5.12 ve üstü. Daha eski
  sürümlerde iki kritik uzaktan kod çalıştırma açığı var (CVE-2026-109618,
  CVE-2026-109619); `php vnl.php doctor` bunu `[X]` olarak işaretler.
* MySQL 5.7+ / MariaDB 10.3+ (giriş, yetki, kuyruk ve denetim kaydı veritabanındadır)
* Node.js 20+ (opsiyonel — yalnızca [VNL-THEME](https://github.com/mrekay/VNL-THEME)
  deposundan temayı yeniden derlemek isteyenler için)

## 📦 Kurulum

```bash
# Projeyi klonlayın
git clone https://github.com/mrekay/VNL-TEMPLATE.git .

# Ortam: veritabanı, APP_URL, APP_ENCRYPTION_KEY (APP_DEBUG canlıda false)
cp .env.example .env

# Şema ve ilk yönetici (parola bir kez basılır)
php vnl.php migrate --run
php vnl.php user:create --username=admin --super

# Kurulum denetimi
php vnl.php doctor
```

Web sunucusu: Apache için `.htaccess` hazır gelir; nginx için `nginx.conf.example`
(yalnız `index.php` çalışır, yalnız `assets/` ve `vnl/assets/` servis edilir).
Doküman sayfaları varsayılan olarak giriş ister; `VNL_PUBLIC_DOCS=true` açar.

Yerelde hızlıca denemek için (PHP kuruluysa ya da Docker ile):

```bash
php vnl.php serve
# veya
docker run --rm -p 8080:8080 -v "$PWD":/app -w /app php:8.2-cli \
  php vnl.php serve
```

## 📚 Dokümantasyon

Uygulama, sol menüden erişilen kapsamlı bir dokümantasyon setiyle gelir:
routing, istek yönetimi, modal sistemi, oturum, modeller, validator'lar,
dil sistemi (i18n), modüller, view include sistemi, extension sistemi ve
**VNL Theme** (/docs/theme).

### 🤖 LLM'ler için

Aynı dokümantasyonun dil modelleri ve kod asistanları için hazırlanmış markdown hali
proje kökündedir; kurulu bir VNL'de `/llms.txt` ve `/llms-full.txt` adreslerinden servis edilir:

| Dosya | İçerik |
| --- | --- |
| `llms.txt` | [llms.txt](https://llmstxt.org) formatında dizin: proje özeti + tüm sayfaların listesi |
| `llms-full.txt` | Tüm dokümantasyonun tek dosyada tam markdown hali (~200 KB) |

Dokümantasyon sayfaları değiştiğinde yeniden üretin:

```bash
php tools/build-llms-docs.php
# PHP CLI yoksa
docker run --rm -v "$PWD":/app -w /app php:8.2-cli php tools/build-llms-docs.php
```

Üretici, sayfa listesini `view/static/leftSidebar.php` içindeki `$SidebarMenu`'den okur —
menüye eklenen yeni bir docs sayfası çıktıya kendiliğinden dahil olur.

# VNL

![Version](https://img.shields.io/badge/version-3.0.0--dev-blue.svg)
![License](https://img.shields.io/badge/license-MIT-green.svg)
![Build Status](https://img.shields.io/badge/build-passing-brightgreen.svg)

**VNL**, [Voxnup Not Laravel].

VNL klasik MVC üzerine inşa edilmiş Eloquent veritabanı sistemini kullanan bir Microframework'tür.

## 📋 İçindekiler

- [Özellikler](#-özellikler)
- [VNL Theme](#-vnl-theme)
- [Gereksinimler](#-gereksinimler)
- [Kurulum](#-kurulum)
- [Dokümantasyon](#-dokümantasyon)

## ✨ Özellikler

* **Hızlı ve Hafif:** Minimum kaynak tüketimi ile maksimum performans.
* **Modüler Yapı:** İhtiyacınız olan bileşenleri kolayca ekleyip çıkarın.
* **Hızlı Veritabanı Erişimi:** Eloquent ile hızlı kurulum ve kullanım.
* **VNL Theme:** Bootstrap 5.3 tabanlı, koyu-varsayılanlı, layout preset'li kendi tema katmanı.

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
- **Derlenmiş çıktı repodadır** (`assets/vnl/`) — kurulumda Node/npm/build **gerekmez**.
  Temayı özelleştirmek isteyenler için kaynak `theme/` altındadır:
  `cd theme && npm ci && npm run build`

Ayrıntılar için uygulama içindeki **/docs/theme** sayfasına bakın.

## 🚀 Gereksinimler

* PHP 8.2 veya üzeri
* Veritabanı (opsiyonel — dokümantasyon sayfaları veritabanısız da çalışır)
* Node.js 20+ (opsiyonel — yalnızca temayı yeniden derlemek isteyenler için)

## 📦 Kurulum

```bash
# Projeyi klonlayın
git clone https://github.com/mrekay/VNL-TEMPLATE.git .

# libs/config/config_definitions.php içindeki tanımları projenize göre düzenleyin
# ve web sunucunuzun kökünü proje dizinine yönlendirin.
```

Yerelde hızlıca denemek için (PHP kuruluysa ya da Docker ile):

```bash
php -S 0.0.0.0:8080 theme/tools/dev-router.php
# veya
docker run --rm -p 8080:8080 -v "$PWD":/app -w /app php:8.2-cli \
  php -S 0.0.0.0:8080 theme/tools/dev-router.php
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

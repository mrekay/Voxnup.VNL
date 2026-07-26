# VNL Theme — kaynak

VNL Theme'in SCSS/JS kaynakları. **Template kullanıcısının build yapması gerekmez** — derlenmiş çıktı `assets/vnl/` altına commit edilir. Bu dizin yalnızca temayı geliştirenler içindir.

## Build

```bash
cd theme
npm ci
npm run build        # css + js + fontlar → ../assets/vnl/
```

Geliştirme sırasında iki ayrı terminalde:

```bash
npm run watch:css
npm run watch:js
```

## İçerik

| Girdi | Çıktı |
|---|---|
| `scss/vnl.scss` | `assets/vnl/css/vnl.min.css` — Bootstrap 5.3.8 + VNL tema + SlimSelect/SweetAlert2 skin |
| `scss/fa.scss` | `assets/vnl/css/fa.min.css` — Font Awesome Free (solid/regular/brands + v4-shims) |
| vendor dist'leri | `assets/vnl/js/vendors.min.js` — jquery + bootstrap.bundle + sweetalert2 + slim-select (concat) |
| `js/vnl-theme.js` | `assets/vnl/js/vnl.min.js` — tema davranışları (esbuild IIFE) |
| Inter + FA woff2 | `assets/vnl/fonts/` |

## Bilinmesi gerekenler

- **Varsayılan tema koyudur** (`<html data-bs-theme="dark">`). Kullanıcı tercihi `localStorage['data-bs-theme']` anahtarında saklanır — Metronic dönemindeki anahtarla aynıdır; eski bir kullanıcıda `light` kayıtlıysa site açık temayla açılır. Bu bilinçli davranıştır (tercih korunur).
- Boşluk ölçeği Metronic uyumlu **0–20**'dir (`mb-10` = 2.5rem); Bootstrap'in 0–5 varsayılanı değildir.
- `fs-7/8/9` ve `fs-2x/2hx/3x` tema ekidir; `w-250px`, `min-w-125px`, `mw-650px`, `mh-300px` gibi piksel utility'leri `scss/_utilities.scss` içindeki listeden üretilir. Yeni bir değer gerekiyorsa listeye ekleyip build alın.
- View şablonlarında `{{ }}` VNL şablon derleyicisine aittir (`libs/vnl_template.php`); literal `{{` gerekiyorsa `@{{` yazın.
- `theme/` web köküne dağıtılıyorsa sunucuda erişime kapatılması önerilir (Apache'de `theme/.htaccess` ile geliyor; IIS'te web.config'e eşdeğer kural ekleyin).

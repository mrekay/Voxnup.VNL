/*!
 * ============================================================
 *  Voxnup Not Laravel (VNL) — VNL Date
 *  Tarih, saat, tarih+saat, ay ve aralık seçici.
 *  <input data-vnl-date> ile bildirimsel kurulur; flatpickr'ı VNL
 *  sözleşmesine bağlar: değer biçimi, Türkçe, tema token'ları,
 *  modal içi konumlama, olaylar ve dinamik içerik.
 *  Authors: Voxnup Authors
 * ============================================================
 *
 *  Sözleşme (ayrıntı /docs/theme-dates):
 *
 *    <input data-vnl-date            name="tarih">        gün        Y-m-d
 *    <input data-vnl-date="time"     name="saat">         saat       H:i:S
 *    <input data-vnl-date="datetime" name="zaman">        gün+saat   Y-m-d H:i:S
 *    <input data-vnl-date="month"    name="donem">        ay         Y-m
 *    <input data-vnl-date="range"    name="aralik">       aralık     Y-m-d - Y-m-d
 *
 *  Ekranda Türkçe biçim (14.09.2026), sunucuya ISO gider: asıl input
 *  gizlenir ve ISO değeri taşır, kullanıcı biçimli bir kopyada çalışır.
 *  data-vnl-date-value="text" ile bu ayrım kapanır — input'un değeri
 *  ekrandaki metnin kendisidir (eski dd/mm/yyyy sözleşmeleri için).
 *
 *  Nitelikler: -format -value -min -max -step -seconds -inline -locale -input
 *  Olaylar (input üzerinde, kabarır): vnl.date.change / open / close / ready
 *  API: vnlDate(el) → flatpickr örneği; vnlDate.init/destroy/set/clear/
 *       parse/format/defaults
 */
(function () {
    'use strict';

    if (typeof window.flatpickr !== 'function') {
        console.error('[VNL] vnl-date: flatpickr yüklü değil; vnl_core_scripts() sırasına bakın.');
        return;
    }

    var fp    = window.flatpickr;
    var SEL   = 'input[data-vnl-date]';
    var KINDS = ['date', 'time', 'datetime', 'month', 'range'];

    /* Kabuktaki modal/offcanvas: takvim body'ye değil buraya eklenir.
       Sebep: Bootstrap 5'in odak tuzağı modal dışına giden odağı geri
       çeker; body'deki takvimin saat kutusuna yazılamaz. */
    var HOSTS = '.modal-content, .offcanvas';

    /* ---------------------------------------------------------------- */
    /* Varsayılanlar — vnlDate.defaults({...}) ile proje genelinde ezilir */
    /* ---------------------------------------------------------------- */

    var defaults = {
        locale: 'tr',
        value:  'iso',                       // iso | text
        format: {                            // ekran biçimi (flatpickr token'ları)
            date: 'd.m.Y', time: 'H:i', datetime: 'd.m.Y H:i', month: 'm.Y', range: 'd.m.Y'
        },
        wire: {                              // sunucuya giden biçim (iso kipi)
            date: 'Y-m-d', time: 'H:i:S', datetime: 'Y-m-d H:i:S', month: 'Y-m', range: 'Y-m-d'
        },
        step:       1,                       // dakika adımı (flatpickr'ın 5'i sürpriz olurdu)
        allowInput: true,                    // klavyeyle yazılabilir
        rangeSeparator: ' - '                // aralık ayracı; yerel ayardan bağımsız sabit
    };

    /* Türkçe yerel ayar paketle gelir; genel varsayılan olarak da kurulur ki
       vnlDate.format() gibi çağrılar ay adlarını Türkçe versin. */
    if (fp.l10ns.tr) fp.localize(fp.l10ns.tr);

    /* ---------------------------------------------------------------- */
    /* Yardımcılar                                                       */
    /* ---------------------------------------------------------------- */

    function resolve(target) {
        if (!target) return null;
        if (target.jquery) return target[0] || null;
        if (typeof target === 'string') return document.querySelector(target);
        return target.nodeType === 1 ? target : null;
    }

    function attr(el, name) {
        var v = el.getAttribute('data-vnl-date-' + name);
        return v === null ? null : v.trim();
    }

    /* flatpickr'ın tr dosyası ekran okuyucu etiketlerini çevirmiyor; burada
       tamamlanır. Başka dil kendi dosyasından geleni kullanır. */
    var TR_ARIA = {hourAriaLabel: 'Saat', minuteAriaLabel: 'Dakika', yearAriaLabel: 'Yıl', monthAriaLabel: 'Ay'};

    function l10nOf(name) {
        name = (name && fp.l10ns[name]) ? name : 'default';
        var base = fp.l10ns[name];
        var out  = {};
        for (var k in base) if (Object.prototype.hasOwnProperty.call(base, k)) out[k] = base[k];
        if (name === 'tr') for (var a in TR_ARIA) out[a] = TR_ARIA[a];
        out.rangeSeparator = defaults.rangeSeparator;
        out.vnlName = name;
        return out;
    }

    /**
     * flatpickr ad token'larının (F, M, l, D, K) ayrıştırma desenini
     * kütüphane genelinde tek bir tabloda tutar ve onu EN SON kurulan
     * örneğin diliyle doldurur. Aynı sayfada tr ve en seçici varsa yazılan
     * "Sep" son kurulan tr'ye göre okunamazdı. Takvim açılırken örneğin
     * kendi dili tabloya yeniden yazdırılır; tek dilli sayfada hiç çalışmaz.
     */
    var tokensLocale = null;

    function syncLocaleTokens(inst) {
        var name = inst.l10n.vnlName || 'default';
        if (tokensLocale === name) return;
        tokensLocale = name;
        inst.set('locale', inst.config.locale);
    }

    function digits(str) {
        var m = String(str || '').match(/\d+/g);
        return m ? m.map(Number).join(',') : '';
    }

    /* flatpickr'ın token desenleri (ad token'ları gevşek). Ayrıştırıcıya
       sormadan "bu metin bu biçime hiç benziyor mu" sorusunu cevaplar;
       benzemeyen biçim atlanır ki flatpickr konsola uyarı düşürmesin. */
    var TOKEN_RE = {
        d: '(\\d\\d|\\d)', j: '(\\d\\d|\\d)', m: '(\\d\\d|\\d)', n: '(\\d\\d|\\d)',
        H: '(\\d\\d|\\d)', G: '(\\d\\d|\\d)', h: '(\\d\\d|\\d)', i: '(\\d\\d|\\d)',
        S: '(\\d\\d|\\d)', s: '(\\d\\d|\\d)', W: '(\\d\\d|\\d)', w: '(\\d\\d|\\d)',
        Y: '(\\d{4})', y: '(\\d{2})', J: '(\\d\\d|\\d)\\w+',
        U: '(.+)', u: '(.+)', Z: '(.+)',
        D: '(\\S+)', l: '(\\S+)', M: '(\\S+)', F: '(\\S+)', K: '(\\S+)'
    };

    var formatCache = {};

    function formatMatches(str, format) {
        var re = formatCache[format];
        if (!re) {
            var src = '';
            for (var i = 0; i < format.length; i++) {
                var t = format[i];
                if (t === '\\') {
                    src += (format[++i] || '').replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
                    continue;
                }
                src += TOKEN_RE[t] || '.';
            }
            re = formatCache[format] = new RegExp(src);
        }
        return re.test(str);
    }

    /**
     * Katı ayrıştırma. flatpickr'ın ayrıştırıcısı esnektir: biçimdeki
     * ayraçları joker sayar ve eşleşmeyen kalanı sessizce yutar —
     * "2026-09-14"'ü "d.m.Y" ile okuyunca 20 Haziran çıkar. Bu yüzden
     * sonuç aynı biçimle geri yazılıp sayı grupları karşılaştırılır;
     * uyuşmuyorsa o biçim değildir. Ayraç farkı (14/09 ↔ 14.09) ve baştaki
     * sıfırlar (4.9.2026) bilerek hoş görülür.
     */
    function parseStrict(str, formats, l10n) {
        if (str instanceof Date) return isNaN(str.getTime()) ? null : str;
        str = String(str === null || str === undefined ? '' : str).trim();
        if (str === '') return null;
        if (str === 'today') {
            var t = new Date();
            t.setHours(0, 0, 0, 0);
            return t;
        }

        for (var i = 0; i < formats.length; i++) {
            var f = formats[i];
            if (!f || !formatMatches(str, f)) continue;
            var d = fp.parseDate(str, f, undefined, l10n);
            if (!d || isNaN(d.getTime())) continue;
            if (digits(fp.formatDate(d, f, l10n)) === digits(str)) return d;
        }

        return null;
    }

    function unique(list) {
        var out = [];
        list.forEach(function (x) { if (x && out.indexOf(x) < 0) out.push(x); });
        return out;
    }

    /* Denenecek biçimler: önce beklenen ikisi, sonra ISO türevleri — ama
       türe göre: "00/00/0000" bir tarih kutusunda saat (H:i:S) diye
       okunmasın, "17:30" bir saat kutusunda tarih aranmasın. */
    var ISO_DATE = ['Y-m-d H:i:S', 'Y-m-d H:i', 'Y-m-d'];
    var ISO_TIME = ['H:i:S', 'H:i'];

    function candidateFormats(kind, primary, secondary) {
        var iso = kind === 'time' ? ISO_TIME : ISO_DATE;
        return unique([primary, secondary].concat(iso));
    }

    function emit(el, name, inst, extra) {
        var detail = {
            instance: inst,
            dates:    inst.selectedDates.slice(),
            value:    el.value,
            text:     inst._input ? inst._input.value : el.value
        };
        if (extra) for (var k in extra) detail[k] = extra[k];
        el.dispatchEvent(new CustomEvent(name, {bubbles: true, detail: detail}));
    }

    /* ---------------------------------------------------------------- */
    /* Modal/offcanvas içinde konumlama                                  */
    /* ---------------------------------------------------------------- */

    /**
     * flatpickr takvimi belgeye göre (pageYOffset) konumlar; appendTo ile
     * bir kaba alınınca o hesap kabın içinde kayar. Bu işlev aynı kararı
     * (yer yoksa üste aç) kabın koordinat sisteminde verir.
     */
    function positionInside(host) {
        return function (inst, customEl) {
            var cal = inst.calendarContainer;
            if (!cal) return;

            var ref = customEl || inst._positionElement;
            var ib  = ref.getBoundingClientRect();
            var hb  = host.getBoundingClientRect();
            var h   = cal.offsetHeight;
            var w   = cal.offsetWidth;

            var onTop = (window.innerHeight - ib.bottom) < h && ib.top > h;
            var top   = (onTop ? ib.top - h - 2 : ib.bottom + 2) - hb.top - host.clientTop + host.scrollTop;
            var left  = ib.left - hb.left - host.clientLeft + host.scrollLeft;
            var max   = host.clientWidth - w;
            if (left > max) left = Math.max(0, max);

            cal.classList.toggle('arrowTop', !onTop);
            cal.classList.toggle('arrowBottom', onTop);
            cal.classList.remove('rightMost');
            cal.classList.remove('centerMost');
            cal.style.top   = top + 'px';
            cal.style.left  = left + 'px';
            cal.style.right = 'auto';
        };
    }

    /* ---------------------------------------------------------------- */
    /* Kurulum                                                           */
    /* ---------------------------------------------------------------- */

    function readOptions(el) {
        var kind = (el.getAttribute('data-vnl-date') || '').trim().toLowerCase() || 'date';
        if (KINDS.indexOf(kind) < 0) {
            console.warn('[VNL] vnl-date: tanınmayan tür "' + kind + '", date sayıldı.', el);
            kind = 'date';
        }

        var value = (attr(el, 'value') || defaults.value).toLowerCase();
        if (value !== 'iso' && value !== 'text') value = defaults.value;

        return {
            kind:       kind,
            value:      value,
            format:     attr(el, 'format') || defaults.format[kind],
            wire:       defaults.wire[kind],
            min:        attr(el, 'min'),
            max:        attr(el, 'max'),
            step:       parseInt(attr(el, 'step'), 10) || defaults.step,
            seconds:    el.hasAttribute('data-vnl-date-seconds'),
            inline:     el.hasAttribute('data-vnl-date-inline'),
            locale:     attr(el, 'locale') || defaults.locale,
            allowInput: attr(el, 'input') === 'off' ? false : defaults.allowInput
        };
    }

    function build(el) {
        var o    = readOptions(el);
        var l10n = l10nOf(o.locale);
        var iso  = o.value === 'iso';
        var isTime = o.kind === 'time' || o.kind === 'datetime';

        /* Girişteki değer hangi biçimde olursa olsun okunur: önce beklenen,
           sonra öteki, sonra ISO'nun türevleri. Okunamayan değer flatpickr'a
           bırakılmaz (esnek ayrıştırıcı saçma bir tarih üretirdi) — temizlenir
           ve konsola yazılır. */
        var candidates = candidateFormats(o.kind, iso ? o.wire : o.format, iso ? o.format : o.wire);

        var initial = null;
        if (el.value.trim() !== '') {
            if (o.kind === 'range') {
                /* Ayraç boşluklu tire (Türkçe) ya da " to " (flatpickr'ın
                   İngilizce çıktısı); tarihin kendi ayracına dokunulmaz. */
                var parts = el.value.split(/\s+-\s+|\s+to\s+/).map(function (p) { return p.trim(); });
                if (parts.length === 2) {
                    var a = parseStrict(parts[0], candidates, l10n);
                    var b = parseStrict(parts[1], candidates, l10n);
                    if (a && b) initial = [a, b];
                } else {
                    initial = parseStrict(el.value, candidates, l10n);
                }
            } else {
                initial = parseStrict(el.value, candidates, l10n);
            }

            if (!initial) {
                console.warn('[VNL] vnl-date: değer okunamadı, temizlendi: "' + el.value + '"', el);
                el.value = '';
            }
        }

        var cfg = {
            locale:          l10n,
            allowInput:      o.allowInput,
            minuteIncrement: o.step,
            time_24hr:       true,
            ariaDateFormat:  'j F Y l',
            defaultDate:     initial || undefined,
            disableMobile:   o.kind === 'month',        // eklenti arayüzü yerli girişe çevrilemez
            plugins:         [],
            /* Kullanıcının yazdığı okunamayan metin bir hata değil, boşa
               dönen bir giriştir; flatpickr'ın uyarısı bastırılır. Kurulum
               hataları (geçersiz yerel ayar vb.) yine konsola düşer. */
            errorHandler: function (err) {
                if (!/Invalid date (provided|supplied)/.test(String(err && err.message))) {
                    console.warn('[VNL] vnl-date:', err && err.message ? err.message : err, el);
                }
            },
            onReady:  [function (dates, str, inst) { ready(el, inst, o); }],
            onChange: [function (dates, str, inst) { emit(el, 'vnl.date.change', inst); }],
            onOpen:   [function (dates, str, inst) { openInst = inst; syncLocaleTokens(inst); emit(el, 'vnl.date.open', inst); }],
            onClose:  [function (dates, str, inst) { if (openInst === inst) openInst = null; emit(el, 'vnl.date.close', inst); }]
        };

        if (iso) {
            cfg.altInput      = true;
            cfg.altInputClass = el.className;      /* flatpickr'ın "form-control input" eki değil, asıl sınıflar */
            cfg.altFormat     = o.format;
            cfg.dateFormat    = o.wire;
        } else {
            cfg.dateFormat = o.format;
        }

        /* readonly bir alan takvimden de değişmemeli: tıklama açmaz, yazılamaz.
           Değer yalnız vnlDate.set() ile değişir. Kurulum anında okunur. */
        if (el.readOnly) {
            cfg.clickOpens = false;
            cfg.allowInput = false;
        }

        if (isTime) {
            cfg.enableTime    = true;
            cfg.enableSeconds = o.seconds || /S/.test(o.format);
        }
        if (o.kind === 'time')  cfg.noCalendar = true;
        if (o.kind === 'range') cfg.mode = 'range';
        if (o.inline)           cfg.inline = true;

        if (o.kind === 'month' && typeof window.monthSelectPlugin === 'function') {
            cfg.plugins.push(window.monthSelectPlugin({
                shorthand:  false,
                dateFormat: iso ? o.wire : o.format,
                altFormat:  o.format,
                theme:      'light'                    // renkler vnl-date.css'ten gelir
            }));
        }

        var min = parseStrict(o.min, candidates, l10n);
        var max = parseStrict(o.max, candidates, l10n);
        if (o.min && !min) console.warn('[VNL] vnl-date: data-vnl-date-min okunamadı: "' + o.min + '"', el);
        if (o.max && !max) console.warn('[VNL] vnl-date: data-vnl-date-max okunamadı: "' + o.max + '"', el);
        /* "today" gece yarısıdır; saat de seçilen türde günün sonuna alınır,
           yoksa bugünün 10:00'ı "bugünden sonra" sayılırdı. */
        if (max && isTime && o.max === 'today') max.setHours(23, 59, 59, 0);
        if (min) cfg.minDate = min;
        if (max) cfg.maxDate = max;

        var host = o.inline ? null : el.closest(HOSTS);
        if (host) {
            cfg.appendTo = host;
            cfg.position = positionInside(host);
        }

        return cfg;
    }

    function ready(el, inst, o) {
        var visible = inst._input || el;
        visible.setAttribute('autocomplete', 'off');
        visible.classList.add('vnl-date-input');
        el.vnlDateKind = o.kind;
        tokensLocale   = inst.l10n.vnlName || 'default';   // flatpickr tabloyu bu dille doldurdu
        emit(el, 'vnl.date.ready', inst);
    }

    function init(scope) {
        var root = resolve(scope) || document;
        var list = [];

        if (root !== document && root.matches && root.matches(SEL)) list.push(root);
        Array.prototype.push.apply(list, root.querySelectorAll(SEL));

        var count = 0;
        list.forEach(function (el) {
            if (el._flatpickr) return;
            try {
                fp(el, build(el));
                count++;
            } catch (e) {
                console.error('[VNL] vnl-date: kurulamadı', el, e);
            }
        });

        return count;
    }

    function destroy(scope) {
        var root = resolve(scope);
        if (!root) return;

        var list = [];
        if (root.matches && root.matches(SEL)) list.push(root);
        if (root.querySelectorAll) Array.prototype.push.apply(list, root.querySelectorAll(SEL));

        list.forEach(function (el) {
            if (el._flatpickr) el._flatpickr.destroy();
        });
    }

    /* ---------------------------------------------------------------- */
    /* Genel API                                                         */
    /* ---------------------------------------------------------------- */

    function vnlDate(target) {
        var el = resolve(target);
        return (el && el._flatpickr) ? el._flatpickr : null;
    }

    vnlDate.init    = init;
    vnlDate.destroy = destroy;

    /** Proje geneli varsayılanlar; nesneler birleştirilir, skalerler ezilir. */
    vnlDate.defaults = function (obj) {
        if (!obj) return defaults;
        for (var k in obj) {
            if (!Object.prototype.hasOwnProperty.call(obj, k)) continue;
            if (obj[k] && typeof obj[k] === 'object' && defaults[k] && typeof defaults[k] === 'object') {
                for (var j in obj[k]) if (Object.prototype.hasOwnProperty.call(obj[k], j)) defaults[k][j] = obj[k][j];
            } else {
                defaults[k] = obj[k];
            }
        }
        return defaults;
    };

    /**
     * Değer yazar. Date, ISO metin, ekran biçiminde metin ya da null kabul
     * eder; aralık için iki elemanlı dizi. Üçüncü parametre verilirse yalnız
     * o biçim denenir.
     */
    vnlDate.set = function (target, value, format) {
        var el   = resolve(target);
        var inst = el && el._flatpickr;
        if (!inst) return false;

        if (value === null || value === undefined || value === '') {
            inst.clear();
            return true;
        }

        var formats = format ? [format] : candidateFormats(el.vnlDateKind, inst.config.dateFormat, inst.config.altFormat);
        var list    = Array.isArray(value) ? value : [value];
        var dates   = [];

        for (var i = 0; i < list.length; i++) {
            var d = parseStrict(list[i], formats, inst.l10n);
            if (!d) {
                console.warn('[VNL] vnl-date.set: değer okunamadı: "' + list[i] + '"', el);
                return false;
            }
            dates.push(d);
        }

        inst.setDate(inst.config.mode === 'range' ? dates : dates[0], true);
        return true;
    };

    vnlDate.clear = function (target) {
        var inst = vnlDate(target);
        if (inst) inst.clear();
    };

    /** Sunucuya gidecek değer (iso kipinde ISO, text kipinde ekrandaki metin). */
    vnlDate.value = function (target) {
        var el = resolve(target);
        return el ? el.value : '';
    };

    vnlDate.parse = function (str, format, locale) {
        return parseStrict(str, [format], l10nOf(locale || defaults.locale));
    };

    vnlDate.format = function (date, format, locale) {
        if (!(date instanceof Date)) date = parseStrict(date, ISO_DATE.concat(ISO_TIME), l10nOf(locale || defaults.locale));
        return date ? fp.formatDate(date, format, l10nOf(locale || defaults.locale)) : '';
    };

    window.vnlDate = vnlDate;

    /* ---------------------------------------------------------------- */
    /* Belge düzeyi davranışlar                                          */
    /* ---------------------------------------------------------------- */

    var openInst = null;

    /* Kaydırınca açık takvim girişin peşinden gider — modal gövdesi ve
       offcanvas gibi kendi içinde kayan kaplar dahil (capture). */
    document.addEventListener('scroll', function () {
        if (openInst && openInst.isOpen && !openInst.config.inline) openInst._positionCalendar();
    }, true);

    /* Kap kapanırken açık takvim de kapanır. */
    ['hide.bs.modal', 'hide.bs.offcanvas'].forEach(function (name) {
        document.addEventListener(name, function () {
            if (openInst && openInst.isOpen) openInst.close();
        });
    });

    /* <label for="x"> gizlenmiş asıl input'u gösterir; tıklama biçimli
       kopyaya yönlendirilir ki etiket yine odaklasın. */
    document.addEventListener('click', function (e) {
        var label = e.target.closest ? e.target.closest('label[for]') : null;
        if (!label) return;
        var el = document.getElementById(label.htmlFor);
        if (el && el._flatpickr && el._flatpickr.altInput) {
            e.preventDefault();
            el._flatpickr.altInput.focus();
        }
    });

    /* [data-vnl-date-toggle] → hedef seçiciyi açar. Değer verilmezse aynı
       .input-group içindeki seçici hedeftir (takvim ikonu deseni). */
    document.addEventListener('click', function (e) {
        var btn = e.target.closest ? e.target.closest('[data-vnl-date-toggle]') : null;
        if (!btn) return;
        var sel   = btn.getAttribute('data-vnl-date-toggle');
        var group = btn.closest('.input-group');
        var el    = sel ? document.querySelector(sel) : (group ? group.querySelector(SEL) : null);
        if (el && el._flatpickr) {
            e.preventDefault();
            el._flatpickr.toggle();
        }
    });

    /* Sonradan gelen içerik (AJAX modal, tablo satırı) kendiliğinden
       kurulur; DOM'dan çıkan input'un body'deki takvimi de toplanır. */
    function observe() {
        if (!window.MutationObserver || !document.body) return;

        new MutationObserver(function (records) {
            for (var i = 0; i < records.length; i++) {
                var r = records[i];
                for (var j = 0; j < r.addedNodes.length; j++) {
                    var n = r.addedNodes[j];
                    if (n.nodeType !== 1) continue;
                    if (n.matches(SEL) || n.querySelector(SEL)) init(n);
                }
                for (var k = 0; k < r.removedNodes.length; k++) {
                    var m = r.removedNodes[k];
                    if (m.nodeType !== 1 || m.isConnected) continue;
                    if (m.matches(SEL) || m.querySelector(SEL)) destroy(m);
                }
            }
        }).observe(document.body, {childList: true, subtree: true});
    }

    function boot() {
        init(document);
        observe();
    }

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot);
    else boot();
})();

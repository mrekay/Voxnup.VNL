/*!
 * ============================================================
 *  Voxnup Not Laravel (VNL) 3.0.0 — VNL Table
 *  Sunucudan gelen tanımı okur, tabloyu çizer ve yönetir.
 *  list  — sıralama, sayfalama, düz arama, Excel.
 *  grid  — başlıktaki huniden açılan sütun filtre panelleri, çipler,
 *          çoklu sıralama, alt toplamlar, URL senkronu.
 *  Authors: Voxnup Authors
 * ============================================================
 */
(function () {
    'use strict';

    var instances = {};

    /* Sunucudaki VnlTableRequest::OPS_NO_VALUE ve OPS_RANGE ile aynı. */
    var OPS_NO_VALUE = ['empty', 'notEmpty'];
    var OPS_RANGE    = ['between'];

    /* Filtre çipinde yer kazanmak için: "Büyük veya eşittir 40000" bir
       rozete sığmıyor, "≥ 40000" sığıyor. */
    var OP_SHORT = {
        equals: '=', notEquals: '≠',
        gt: '>', gte: '≥', lt: '<', lte: '≤'
    };

    /* ---------------------------------------------------------------- */
    /* Yardımcılar                                                       */
    /* ---------------------------------------------------------------- */

    function el(tag, cls, text) {
        var node = document.createElement(tag);
        if (cls) node.className = cls;
        if (text !== undefined && text !== null) node.textContent = String(text);
        return node;
    }

    /** Metin olarak basılacak her değer buradan geçer; HTML asla yorumlanmaz. */
    function asText(value) {
        if (value === null || value === undefined) return '';
        if (typeof value === 'object') return JSON.stringify(value);
        return String(value);
    }

    /** _TOTAL_ / _START_ / _END_ / _MAX_ yer tutucularını doldurur. */
    function fill(template, values) {
        var out = String(template || '');
        Object.keys(values).forEach(function (k) {
            out = out.split('_' + k.toUpperCase() + '_').join(values[k]);
        });
        return out;
    }

    /**
     * Türkçe küçültme.
     *
     * toLowerCase() 'I' harfini 'i' yapar; Türkçe'de karşılığı 'ı'dır ve
     * "İZMİR" araması "izmir" ile eşleşmez. Sunucudaki
     * TextGeneralModule::trToLower ile aynı eşlemeyi yapar.
     */
    function trLower(text) {
        return String(text === null || text === undefined ? '' : text)
            .replace(/I/g, 'ı')
            .replace(/İ/g, 'i')
            .toLowerCase();
    }

    function debounce(fn, wait) {
        var timer = null;
        return function () {
            var args = arguments, self = this;
            clearTimeout(timer);
            timer = setTimeout(function () { fn.apply(self, args); }, wait);
        };
    }

    /* ---------------------------------------------------------------- */
    /* İkonlar — Bootstrap Icons, yoksa Font Awesome, o da yoksa emoji    */
    /* ---------------------------------------------------------------- */

    var ICON_SETS = {
        bi: {
            probe:   'bi bi-chevron-left',
            first:   'bi bi-chevron-double-left',
            prev:    'bi bi-chevron-left',
            next:    'bi bi-chevron-right',
            last:    'bi bi-chevron-double-right',
            columns: 'bi bi-layout-three-columns',
            clear:   'bi bi-x-circle',
            excel:   'bi bi-file-earmark-spreadsheet',
            search:  'bi bi-search',
            filter:  'bi bi-funnel',
            filterOn: 'bi bi-funnel-fill',
            group:   'bi bi-diagram-2',
            pivot:   'bi bi-grid-3x3',
            /* bool biçimlendiricisi */
            check:   'bi bi-check-lg',
            cross:   'bi bi-x-lg'
        },
        fa: {
            probe:   'fa-solid fa-angle-left',
            first:   'fa-solid fa-angles-left',
            prev:    'fa-solid fa-angle-left',
            next:    'fa-solid fa-angle-right',
            last:    'fa-solid fa-angles-right',
            columns: 'fa-solid fa-table-columns',
            clear:   'fa-solid fa-circle-xmark',
            excel:   'fa-solid fa-file-excel',
            search:  'fa-solid fa-magnifying-glass',
            /* Font Awesome Free'de anahat huni yok; aktiflik renkle ayrılır. */
            filter:  'fa-solid fa-filter',
            filterOn: 'fa-solid fa-filter',
            group:   'fa-solid fa-layer-group',
            pivot:   'fa-solid fa-table-cells',
            check:   'fa-solid fa-check',
            cross:   'fa-solid fa-xmark'
        },
        emoji: {
            first: '«', prev: '‹', next: '›', last: '»',
            columns: '▦', clear: '✕', excel: '▤', search: '⌕',
            filter: '▽', filterOn: '▼', group: '☰', pivot: '▦',
            check: '✓', cross: '✕'
        }
    };

    /**
     * İkon seti tespiti.
     *
     * İkon fontlarının CSS'i ::before üzerinde content tanımlar. Stil dosyası
     * yüklenmemişse content boş kalır — font yüklenmesini beklemeye gerek
     * kalmadan, tek ölçümle hangi setin var olduğu anlaşılır.
     */
    var iconSet = (function () {
        function available(cls) {
            var probe = document.createElement('i');
            probe.className = cls;
            probe.style.cssText = 'position:absolute;left:-9999px;visibility:hidden';
            document.body.appendChild(probe);

            var content = '';
            try {
                content = window.getComputedStyle(probe, '::before').content || '';
            } catch (e) { /* ölçülemedi: yok say */ }

            document.body.removeChild(probe);

            return content !== '' && content !== 'none' && content !== 'normal' && content !== '""';
        }

        var cached = null;

        return function () {
            if (cached) return cached;
            if (!document.body) return ICON_SETS.emoji;

            cached = available(ICON_SETS.bi.probe) ? ICON_SETS.bi
                : available(ICON_SETS.fa.probe) ? ICON_SETS.fa
                    : ICON_SETS.emoji;

            return cached;
        };
    })();

    /**
     * Yalnızca ikon taşıyan düğme; metin tooltip ve erişilebilirlik etiketi olur.
     *
     * Tooltip temanın vnlTooltips() köprüsünden geçer. $legacy true ise
     * data-toggle kullanılır: data-bs-toggle o elemanda başka bir işi
     * (dropdown) tutuyorsa ikisi çakışmasın diye. Tema iki biçimi de tanır.
     */
    function iconButton(iconName, label, variant, legacyTooltip) {
        var b = el('button', 'btn btn-sm vnl-icon-btn ' + (variant || 'btn-outline-secondary'));
        b.type = 'button';
        b.appendChild(icon(iconName));
        b.title = label;
        b.setAttribute('aria-label', label);
        b.setAttribute(legacyTooltip ? 'data-toggle' : 'data-bs-toggle', 'tooltip');

        return b;
    }

    /** Tema köprüsü yoksa tarayıcının kendi title balonu devrede kalır. */
    function initTooltips(scope) {
        if (typeof window.vnlTooltips === 'function') {
            window.vnlTooltips(scope);
        }
    }

    /** Panel açılırken düğmenin balonu ekranda kalmasın. */
    function hideTooltip(node) {
        if (!window.bootstrap || !window.bootstrap.Tooltip) return;

        var tip = window.bootstrap.Tooltip.getInstance(node);
        if (tip) tip.hide();
    }

    /**
     * Kaldırılacak düğmelerin tooltip örneklerini serbest bırakır.
     * Sayfalama her yüklemede yeniden kurulduğu için gerekli: aksi halde
     * açık kalmış bir balon DOM'da öksüz kalıyor.
     */
    function disposeTooltips(scope) {
        if (!window.bootstrap || !window.bootstrap.Tooltip) return;

        scope.querySelectorAll('[data-bs-toggle="tooltip"],[data-toggle="tooltip"]').forEach(function (node) {
            var instance = window.bootstrap.Tooltip.getInstance(node);
            if (instance) instance.dispose();
        });
    }

    /** İkonlu düğme içeriği: font varsa <i>, yoksa emoji karakteri. */
    function icon(name) {
        var set = iconSet();

        if (set === ICON_SETS.emoji) {
            return el('span', 'vnl-icon vnl-icon-text', set[name] || '');
        }

        var node = el('i', 'vnl-icon ' + set[name]);
        node.setAttribute('aria-hidden', 'true');

        return node;
    }


    /* ---------------------------------------------------------------- */
    /* Hücre biçimlendiriciler                                           */
    /* ---------------------------------------------------------------- */

    /**
     * Neden burada.
     *
     * Hücre HAM DEĞER taşır — sıralama, filtre, gruplama, pivot ve Excel
     * çıktısı ham değer olmadan doğru çalışamaz. Rozet göstermek isteyen
     * sunucunun elinde bu yüzden tek bir yol vardı: değerin YERİNE HTML
     * göndermek (render:'html'). O yol veriyi biçime feda ediyor ve kaçış
     * sorumluluğunu her eylem dosyasına dağıtıyordu.
     *
     * Biçimlendirici o dengeyi tersine çevirir: satırdaki değer ham kalır,
     * sunucu yalnızca NİYETİ bildirir ("bu sütun bir durum rozetidir"),
     * çizimi istemci yapar. Üretilen düğümlerin hiçbiri innerHTML
     * kullanmaz; metin her zaman textContent'ten geçer, sınıf adları
     * sunucuda beyaz listeye karşı doğrulanır. Yani bu yolda XSS yüzeyi
     * yoktur — render:'html' ise olduğu gibi durur, sözleşmesi değişmedi.
     */

    /** Sayfanın kaydettiği adlandırılmış biçimlendiriciler. */
    var formatters = {};

    /** Bir değer "doğru" mu? bool biçimlendiricisinin ölçütü. */
    function truthy(value) {
        if (value === null || value === undefined || value === '') return false;
        if (typeof value === 'boolean') return value;

        var text = String(value).trim().toLowerCase();

        return !(text === '0' || text === 'false' || text === 'no' || text === 'hayir' || text === 'hayır');
    }

    /** Değere karşılık gelen eşleme girdisi; yoksa varsayılan. */
    function mapped(table, value, fallback) {
        if (!table) return fallback;

        var key = (value === null || value === undefined) ? '' : String(value);

        return Object.prototype.hasOwnProperty.call(table, key) ? table[key] : fallback;
    }

    /**
     * İkon sınıfı: boşluklu tam class olduğu gibi, tek kelime FA kısa adı
     * sayılır. Kenar çubuğundaki 'icon' anahtarıyla aynı kural.
     */
    function iconClass(name) {
        name = String(name || '');

        return name.indexOf(' ') !== -1 ? name : 'fa-solid fa-' + name;
    }

    /* Bağlantı biçimlendiricisinde izin verilen şemalar. */
    var SAFE_SCHEMES = ['http:', 'https:', 'mailto:', 'tel:'];

    /**
     * href şablonunu satırla doldurur: '/kayit/{id}' → '/kayit/42'.
     *
     * Değerler encodeURIComponent'ten geçer ve sonuçtaki şema beyaz listeye
     * karşı sınanır: 'javascript:' taşıyan bir satır değeri bağlantıyı
     * çalıştırılabilir hale getiremesin.
     */
    function safeHref(template, row) {
        var out = String(template || '').replace(/\{([A-Za-z0-9_.-]+)\}/g, function (all, key) {
            var v = row[key];

            return (v === null || v === undefined) ? '' : encodeURIComponent(String(v));
        });

        var scheme = /^\s*([A-Za-z][A-Za-z0-9+.-]*:)/.exec(out);

        if (scheme && SAFE_SCHEMES.indexOf(scheme[1].toLowerCase()) === -1) {
            return null;
        }

        return out;
    }

    /**
     * Bildirimsel biçimlendirici. Tanım sunucuda doğrulanmış olarak gelir
     * (VnlTable::normalizeFormat), burada yalnız çizilir.
     *
     * Dönüş: hücreye eklenecek düğüm ya da null (null → düz metin).
     */
    function formatNode(spec, value, row) {
        var label   = String(mapped(spec.labels, value, (value === null || value === undefined) ? '' : value));
        var variant = mapped(spec.map, value, spec['default'] || 'secondary');

        if (spec.kind === 'badge') {
            return el('span', (spec.soft === false ? 'badge bg-' : 'badge badge-soft-') + variant, label);
        }

        if (spec.kind === 'dot') {
            var wrap = el('span', 'vnl-cell-dot-wrap');
            wrap.appendChild(el('span', 'vnl-cell-dot text-' + variant));
            if (label !== '') wrap.appendChild(el('span', null, label));

            return wrap;
        }

        if (spec.kind === 'icon') {
            var name = mapped(spec.icons, value, spec.icon || '');
            if (!name) return null;

            var box   = el('span', 'vnl-cell-icon text-' + variant);
            var glyph = el('i', iconClass(name));
            glyph.setAttribute('aria-hidden', 'true');
            box.appendChild(glyph);
            if (label !== '' && spec.labels) box.appendChild(el('span', null, label));

            return box;
        }

        if (spec.kind === 'bool') {
            /* type:'bool' VNL 3.1'e kadar yalnız hizalamayı ve filtre
               türünü belirliyordu; hücrede yine düz "1"/"0" görünüyordu. */
            var on   = truthy(value);
            var cell = el('span', 'vnl-cell-bool text-' + (on ? (spec.onVariant || 'success') : (spec.offVariant || 'danger')));

            cell.appendChild(icon(on ? 'check' : 'cross'));

            var text = on ? spec.onLabel : spec.offLabel;
            if (text) cell.appendChild(el('span', null, text));

            return cell;
        }

        if (spec.kind === 'link') {
            if (label === '') return null;

            var href = safeHref(spec.href, row);
            if (href === null) return null;

            var a = el('a', 'vnl-cell-link', label);
            a.href = href;

            if (spec.target) {
                a.target = spec.target;
                /* Yeni sekmede açılan bağlantı açanı window.opener ile
                   yönlendirebilir; hedef adres satır verisinden geliyor. */
                a.rel = 'noopener noreferrer';
            }

            return a;
        }

        return null;
    }

    /**
     * Hücrenin içeriğini yazar.
     *
     * buildRow, hücre düzenleme ve kayıt sonrası boyama tek bu noktadan
     * geçer: biçimlendirici hepsinde aynı çalışsın, bir hücreyi düzenleyip
     * kaydetmek rozeti düz metne çevirmesin.
     */
    function paintCell(td, col, row) {
        var value = row[col.name];

        td.textContent = '';
        td.classList.remove('vnl-cell-formatted');

        /* Adlandırılmış biçimlendirici: sayfanın kaydettiği fonksiyon.
           Metin döndürürse metin olarak basılır, düğüm döndürürse eklenir.
           HTML metni asla yorumlanmaz. Kaydı olmayan ad sessizce düz metne
           düşer — tablo bir yazım hatası yüzünden boş kalmasın. */
        if (col.formatter) {
            var fn = formatters[col.formatter];

            if (typeof fn === 'function') {
                var out;

                try {
                    out = fn(value, row, col);
                } catch (e) {
                    console.error('[VNL Table] biçimlendirici "' + col.formatter + '":', e);
                    out = null;
                }

                if (out instanceof Node) {
                    td.appendChild(out);
                    td.classList.add('vnl-cell-formatted');

                    return;
                }

                if (out !== null && out !== undefined) {
                    td.textContent = asText(out);

                    return;
                }
            } else {
                console.warn('[VNL Table] kayıtlı olmayan biçimlendirici: ' + col.formatter);
            }
        }

        if (col.format) {
            var node = formatNode(col.format, value, row);

            if (node) {
                td.appendChild(node);
                td.classList.add('vnl-cell-formatted');

                return;
            }
        }

        if (col.render === 'html') {
            /* Sütun bilinçli olarak html işaretlenmiş: sunucu içeriği
               üretiyor ve kaçışından kendisi sorumlu. */
            td.innerHTML = asText(value);

            return;
        }

        td.textContent = asText(value);
    }

    /**
     * Henüz kaydedilmemiş bir değeri satırı bozmadan hücreye çizer.
     *
     * Düzenleme sırasında gerekli: kullanıcının yazdığı değer sunucudan
     * onay gelene kadar satıra işlenmez, ama hücrede biçimlendirilmiş
     * hâliyle görünmelidir.
     */
    function paintValue(td, col, row, value) {
        if (value === row[col.name]) {
            paintCell(td, col, row);

            return;
        }

        var shadow = {};
        Object.keys(row).forEach(function (k) { shadow[k] = row[k]; });
        shadow[col.name] = value;

        paintCell(td, col, shadow);
    }

    /* ---------------------------------------------------------------- */
    /* Tablo                                                             */
    /* ---------------------------------------------------------------- */

    function VnlTable(root, config) {
        this.root    = root;
        this.config  = config;
        this.columns = config.columns.filter(function (c) { return c.visible; });
        this.opt     = config.options || {};

        /* Seçim kutusu ve detay oku birer SÜTUNDUR. Satır başına iliştirilmiş
           kutucuklar olsaydı colspan hesabı, sabit sütun indeksleri ve toplam
           satırının hizası ayrı ayrı bozulurdu; sütun olarak mevcut makine
           onları kendiliğinden taşıyor. */
        var lead = [];
        if (this.opt.selection) lead.push(systemColumn('__select'));
        if (this.opt.detail)    lead.push(systemColumn('__detail'));

        this.lead  = lead;
        this.data  = this.columns;
        this.order = this.restoreOrder();
        this.columns = lead.concat(this.orderData());
        this.lang    = config.lang || {};

        /* Kullanıcının gizlediği sütunlar (name → true); tercih tarayıcıda
           tablo kimliğiyle saklanır. visible:false'tan farkı: bunlar tanımda
           durur ve sütun menüsünden geri açılabilir. */
        this.hiddenCols = this.restoreHidden();
        this.ths = {};
        this.filterBtns = {};

        this.pinned = this.columns.some(function (c) { return !!c.pin; });

        /* Açık filtre paneli. Panel body'ye takılır — tablo overflow:hidden
           taşıdığı için içeride kalsaydı kırpılırdı. */
        this.panel = null;

        this.state = {
            page:    1,
            size:    this.opt.pageSize || 25,
            sort:    (this.opt.order || []).slice(),
            search:  '',
            filters: {},
            groupBy: ((this.opt.group && this.opt.group.by) || []).slice(),
            pivot:   pivotState(this.opt.pivot),
            total:   0,
            filtered: 0,
            rows:    [],
            loading: false
        };

        /* Gruplama durumu.
             nodes    yol -> o seviyenin gruplari (server stratejisi)
             buckets  yol -> yaprak grubun yuklenmis satirlari
             allRows  client stratejisinde butun satirlar; doluysa her
                      seviye ve her toplam tarayicida hesaplanir
             expanded acik yollar */
        this.nodes    = {};
        this.buckets  = {};
        this.allRows  = null;
        this.expanded = {};

        /* Detay gövdeleri satır kimliğine göre saklanır: kapatıp açmak
           sunucuya yeniden gitmesin. */
        this.details    = {};
        this.detailOpen = {};

        /* Seçim. mode 'all' bir kimlik listesi DEĞİLDİR — "filtreye uyan
           hepsi" demektir ve except onun deliklerini tutar. Kullanıcı 4200
           kaydı seçerken tarayıcıya 25 tanesi inmiş oluyor. */
        this.selection = { mode: 'some', ids: {}, except: {} };
        this.selectionKey = '';

        /* Adresteki durum build()'den ÖNCE okunur: arama kutusu ve sayfa
           boyu seçimi kurulurken doğru değeri göstersin. */
        this.readUrl();

        this.build();
        this.load();

        /* Başka bir geçmiş kaydına gidildiğinde (paylaşılan bir linkten
           gelip geri dönmek gibi) durum adresten yeniden kurulur. */
        var self = this;
        this.onPop = function () {
            self.closeFilter();
            self.readUrl();
            self.syncControls();
            self.paintSort();
            self.paintFilters();
            self.paintGroupMenu();
            self.load(true);
        };

        if (this.opt.urlSync) window.addEventListener('popstate', this.onPop);
    }

    /** Adresten gelen durumu araç çubuğundaki denetimlere yansıtır. */
    VnlTable.prototype.syncControls = function () {
        if (this.searchInput) this.searchInput.value = this.state.search;
        if (this.sizeSelect) this.sizeSelect.value = this.state.size;
    };

    VnlTable.prototype.build = function () {
        var self = this;
        this.root.innerHTML = '';

        /* Görünüm sınıfları: skin bir CSS katmanı, şerit ondan bağımsız
           bir anahtar — hangi skin seçilirse seçilsin çalışır. */
        this.root.classList.add('vnl-skin-' + (this.opt.skin || 'default'));
        this.root.classList.add('vnl-mode-' + (this.config.mode || 'list'));
        this.root.classList.toggle('vnl-pivot-view', this.state.pivot.on);
        if (this.opt.striped) this.root.classList.add('vnl-striped');

        /* ---- Araç çubuğu ---- */
        var bar = el('div', 'vnl-table-toolbar');

        if (this.opt.search !== false) {
            var search = el('input', 'form-control form-control-sm vnl-table-search');
            search.type = 'search';
            search.placeholder = this.lang.search || '';
            search.value = this.state.search;
            search.addEventListener('input', debounce(function () {
                self.state.search = search.value.trim();
                self.state.page = 1;
                self.load();
            }, 300));
            /* İkon input'un içine değil üstüne konur; input'un solunda
               yer açılır, ikon tıklamayı yutmasın diye pointer-events kapalı. */
            var searchWrap = el('div', 'vnl-table-search-wrap');
            searchWrap.appendChild(icon('search'));
            searchWrap.appendChild(search);

            bar.appendChild(searchWrap);
            this.searchInput = search;
        }

        var actions = el('div', 'vnl-table-actions');

        if (this.opt.pivot) {
            var pivotBtn = iconButton('pivot', this.lang.pivot || '', 'btn-outline-secondary');
            pivotBtn.classList.toggle('vnl-pivot-on', this.state.pivot.on);
            pivotBtn.addEventListener('click', function () { self.togglePivot(); });
            actions.appendChild(pivotBtn);
        }

        if (this.opt.group && this.opt.group.menu) {
            actions.appendChild(this.buildGroupMenu());
        }

        if (this.opt.columns !== false && this.columns.some(function (c) { return c.hideable !== false; })) {
            actions.appendChild(this.buildColumnMenu());
        }

        if (this.opt.clear !== false) {
            var clear = iconButton('clear', this.lang.clear || '', 'btn-outline-secondary');
            clear.addEventListener('click', function () { self.clear(); });
            actions.appendChild(clear);
        }

        if (this.opt.excel !== false) {
            var excel = iconButton('excel', this.lang.excel || '', 'btn-success');
            excel.addEventListener('click', function () { self.exportExcel(excel); });
            actions.appendChild(excel);
        }

        bar.appendChild(actions);
        this.root.appendChild(bar);

        /* ---- Pivot eksenleri ----
           Görünüm açıkken beliren şerit: hangi kırılım satıra, hangisi
           sütuna, hangi alan ölçülüyor. */
        if (this.opt.pivot && this.state.pivot.on) this.buildPivotBar();

        /* ---- Gruplama bırakma alanı ----
           Menüden önce gelir: sürükle-bırak keşfedilebilir olan, menü ise
           dokunmatikte ve klavyeyle çalışan yol. İkisi aynı duruma yazar. */
        if (this.opt.group && this.opt.group.zone) {
            this.buildDropZone();
        }

        /* ---- Aktif filtre çipleri ----
           Hangi sütunun filtreli olduğunu görmek için başlıkları tek tek
           gezmek gerekiyordu; çipler bunu tek bakışa indirir. Filtre
           yokken satır hiç çizilmez, yer kaplamasın. */
        if (this.opt.chips) {
            this.chips = el('div', 'vnl-table-chips');
            this.chips.hidden = true;
            this.root.appendChild(this.chips);
        }

        /* ---- Seçim çubuğu ----
           Seçim varken belirir; kaç kayıt seçildiğini ve ne yapılabileceğini
           tek yerde söyler. */
        if (this.opt.selection) this.buildSelectionBar();

        /* ---- Tablo ----
           Kaydırma alanı ayrı bir kapsayıcıya alınır: yükleme katmanı
           kaydırmayla birlikte hareket etmesin, görünen alanda sabit kalsın. */
        var body = el('div', 'vnl-table-body');

        var wrap = el('div', 'vnl-table-scroll');
        if (this.opt.height) wrap.style.maxHeight = this.opt.height;

        var table = el('table', 'vnl-table-el');

        /* Tablo varsayılan olarak kabına sığar; sütun genişlikleri de öneri
           sayılıp sıkıştırılır. Sabit sütunun bir işe yaraması için tablonun
           kabından TAŞMASI gerekir — minWidth verilmediyse sütunların
           bildirdiği genişlikler toplamı taban kabul edilir. */
        var minWidth = this.opt.minWidth || (this.pinned ? this.declaredWidth() : null);
        if (minWidth) table.style.minWidth = minWidth;
        var thead = el('thead');
        var headRow = el('tr');

        this.columns.forEach(function (col) {
            var th = el('th', 'vnl-th vnl-align-' + col.align);
            if (col.width) th.style.width = col.width;

            if (col.system) {
                th.classList.add('vnl-sys');
                pinClass(th, col);
                if (col.name === '__select') th.appendChild(self.buildSelectAllBox());
                self.ths[col.name] = th;
                headRow.appendChild(th);

                return;
            }

            /* Başlık içeriği bir flex satırı: huni, hizalama ne olursa olsun
               etiketin peşinde kalsın. th'nin kendisi flex yapılamaz —
               tablo sütun genişliği hesabı bozuluyor. */
            var inner = el('span', 'vnl-th-inner');
            var label = el('span', 'vnl-th-label', col.title);
            inner.appendChild(label);

            /* Sürüklenebilir olan başlığın YAZISI, hücrenin tamamı değil:
               hücre sıralamayı tıklıyor, huni kendi panelini açıyor.
               Aynı sürükleme iki şey yapabilir: gruplama alanına bırakılırsa
               gruplar, başka bir başlığa bırakılırsa sırayı değiştirir. */
            if (self.dropZone || self.opt.reorder) {
                self.makeDraggable(label, col);
            }

            if (self.opt.reorder) self.makeDropTarget(th, col);

            if (col.sortable) {
                th.classList.add('vnl-sortable');
                inner.appendChild(el('span', 'vnl-sort-mark'));

                /* Numara yalnız birden çok anahtar varken dolar; tek
                   sıralamada boş kalır ve yer kaplamaz. */
                if (self.opt.multiSort) {
                    inner.appendChild(el('span', 'vnl-sort-order'));
                    th.title = self.lang.sortAdd || '';
                }

                th.addEventListener('click', function (e) {
                    /* Sürükleme bittikten sonra bazı tarayıcılar click de
                       gönderiyor; başlığı gruplamaya sürükleyen kullanıcı
                       sıralamayı değiştirmiş olmasın. */
                    if (self.dragging) return;

                    self.toggleSort(col.name, self.opt.multiSort && e.shiftKey);
                });
            }

            if (col.filter) {
                inner.appendChild(self.buildFilterButton(col));
            }

            th.appendChild(inner);
            pinClass(th, col);

            if (self.hiddenCols[col.name]) th.hidden = true;
            self.ths[col.name] = th;
            headRow.appendChild(th);
        });

        thead.appendChild(headRow);
        table.appendChild(thead);

        this.tbody = el('tbody');
        table.appendChild(this.tbody);

        /* ---- Alt toplam satırı ----
           agg tanımlı sütun varsa çizilir. Değerleri sunucu verir ve
           FİLTRENİN TAMAMINI kapsar: kullanıcı 25 satır görürken 4200
           satırın toplamını okur. Yapışkan: gövde kaydırılınca kaybolmaz. */
        this.aggCols = this.columns.filter(function (c) { return c.agg; });

        if (this.aggCols.length) {
            this.tfoot = el('tfoot');
            this.totalCells = {};

            var totalRow = el('tr');
            var labelled = false;

            this.columns.forEach(function (col) {
                var cell = el('th', 'vnl-tf vnl-align-' + col.align);

                if (col.agg) {
                    cell.title = (self.lang.aggs || {})[col.agg] || col.agg;
                    self.totalCells[col.name] = cell;
                } else if (!labelled && !col.system) {
                    /* İlk toplamsız sütun satırı adlandırır — hesap
                       tablolarındaki alışkanlık. */
                    cell.textContent = self.lang.totals || '';
                    cell.classList.add('vnl-tf-label');
                    labelled = true;
                }

                pinClass(cell, col);

                if (self.hiddenCols[col.name]) cell.hidden = true;
                self.tfootCells = self.tfootCells || {};
                self.tfootCells[col.name] = cell;
                totalRow.appendChild(cell);
            });

            this.tfoot.appendChild(totalRow);
            table.appendChild(this.tfoot);
        }

        wrap.appendChild(table);
        body.appendChild(wrap);

        /* Yükleme katmanı: satırlar silinmez, yerinde bulanıklaşır. */
        this.overlay = el('div', 'vnl-table-overlay');
        this.overlay.setAttribute('aria-hidden', 'true');
        this.overlay.appendChild(el('span', 'vnl-table-overlay-text', this.lang.loading || ''));
        body.appendChild(this.overlay);

        this.root.appendChild(body);

        /* ---- Alt bilgi ---- */
        var foot = el('div', 'vnl-table-footer');

        var sizeWrap = el('label', 'vnl-table-size');
        var sizeSelect = el('select', 'form-select form-select-sm');
        (this.opt.pageSizes || [10, 25, 50, 100]).forEach(function (n) {
            var o = el('option', null, n);
            o.value = n;
            if (n === self.state.size) o.selected = true;
            sizeSelect.appendChild(o);
        });
        sizeSelect.addEventListener('change', function () {
            self.state.size = parseInt(sizeSelect.value, 10);
            self.state.page = 1;
            self.load();
        });
        sizeWrap.appendChild(sizeSelect);
        foot.appendChild(sizeWrap);
        this.sizeSelect = sizeSelect;

        this.info = el('div', 'vnl-table-info');
        foot.appendChild(this.info);

        this.pager = el('div', 'vnl-table-pager');
        foot.appendChild(this.pager);

        this.root.appendChild(foot);
        this.table = table;

        this.paintSort();
        this.paintFilters();
        initTooltips(this.root);

        /* Sabit sütun ofsetleri gerçek genişliklerden gelir; pencere
           değişince sütunlar da değişir. */
        if (this.pinned) {
            this.onResize = debounce(function () { self.layoutPins(); }, 120);
            window.addEventListener('resize', this.onResize);
            this.layoutPins();
        }
    };

    /**
     * Sıralama: artan → azalan → yok.
     *
     * additive (shift) anahtarı listeye EKLER, öncekileri korur; shift'siz
     * tık listeyi tek anahtara indirir. Ekleme sırası öncelik sırasıdır ve
     * başlıkta numarayla görünür.
     */
    VnlTable.prototype.toggleSort = function (name, additive) {
        var at = -1;
        this.state.sort.forEach(function (s, i) { if (s.col === name) at = i; });

        var sort;

        if (!additive) {
            var current = (at === -1) ? null : this.state.sort[at];

            if (!current) sort = [{ col: name, dir: 'asc' }];
            else if (current.dir === 'asc') sort = [{ col: name, dir: 'desc' }];
            else sort = [];
        } else {
            sort = this.state.sort.slice();

            if (at === -1) sort.push({ col: name, dir: 'asc' });
            else if (sort[at].dir === 'asc') sort[at] = { col: name, dir: 'desc' };
            else sort.splice(at, 1);
        }

        this.state.sort = sort;
        this.state.page = 1;
        this.paintSort();
        this.load();
    };

    VnlTable.prototype.paintSort = function () {
        var self  = this;
        if (this.state.pivot.on) return;
        var many  = this.state.sort.length > 1;
        var index = {};

        this.state.sort.forEach(function (s, i) { index[s.col] = { dir: s.dir, n: i + 1 }; });

        this.columns.forEach(function (col) {
            var th = self.ths[col.name];
            if (!th) return;

            var hit = index[col.name];

            th.classList.remove('vnl-sort-asc', 'vnl-sort-desc');
            if (hit) th.classList.add(hit.dir === 'desc' ? 'vnl-sort-desc' : 'vnl-sort-asc');

            /* Numara tek anahtarlı sıralamada gürültü; yalnız birden
               çoğunda doldurulur. */
            var order = th.querySelector('.vnl-sort-order');
            if (order) order.textContent = (hit && many) ? hit.n : '';
        });
    };

    /* ---------------------------------------------------------------- */
    /* Sütun göster/gizle                                                */
    /* ---------------------------------------------------------------- */

    VnlTable.prototype.storageKey = function () {
        return 'vnltable-cols-' + this.config.id;
    };

    /**
     * Kullanicinin verdigi sutun sirasi.
     *
     * Gizleme tercihiyle ayni yerde durur: tarayicida, tablo kimligiyle.
     * Adrese yazilmaz — paylasilan bir baglantinin karsi tarafin sutun
     * duzenini degistirmesi istenmez, bu kisisel bir tercih.
     */
    VnlTable.prototype.orderKey = function () {
        return 'vnltable-order-' + this.config.id;
    };

    VnlTable.prototype.restoreOrder = function () {
        try {
            var saved = JSON.parse(localStorage.getItem(this.orderKey()) || '[]');

            return Array.isArray(saved) ? saved : [];
        } catch (e) {
            return [];   /* bozuk kayit yok sayilir */
        }
    };

    VnlTable.prototype.persistOrder = function () {
        try {
            if (this.order.length) localStorage.setItem(this.orderKey(), JSON.stringify(this.order));
            else localStorage.removeItem(this.orderKey());
        } catch (e) { /* localStorage kapaliysa tercih oturumluk kalir */ }
    };

    /**
     * Veri sutunlarini kullanicinin sirasina gore dizer.
     *
     * Sabitleme kumeleri korunur: sola sabitler basta, sagdakiler sonda.
     * Bir sutunu kendi kumesinin disina birakmak duzeni bozardi, o yuzden
     * kabul edilmez degil, sessizce kumesine geri cekilir.
     *
     * Tanimdaki yeni sutunlar (kayitli sirada olmayanlar) sona eklenir:
     * eski bir tercih yeni bir sutunu gizlememeli.
     */
    VnlTable.prototype.orderData = function () {
        var byName = {};
        var out    = [];

        this.data.forEach(function (col) { byName[col.name] = col; });

        this.order.forEach(function (name) {
            if (byName[name]) {
                out.push(byName[name]);
                delete byName[name];
            }
        });

        this.data.forEach(function (col) {
            if (byName[col.name]) out.push(col);
        });

        var rank = function (col) {
            return col.pin === 'left' ? 0 : (col.pin === 'right' ? 2 : 1);
        };

        /* Array.sort kararli: kume ici sira kullanicinin verdigi gibi kalir. */
        return out.sort(function (a, b) { return rank(a) - rank(b); });
    };

    /** Sutunu baska bir sutunun onune ya da arkasina tasir. */
    VnlTable.prototype.moveColumn = function (from, to, after) {
        if (from === to) return;

        var names = this.orderData().map(function (col) { return col.name; });
        var at    = names.indexOf(from);

        if (at === -1) return;
        names.splice(at, 1);

        var target = names.indexOf(to);
        if (target === -1) return;

        names.splice(after ? target + 1 : target, 0, from);

        this.order = names;
        this.persistOrder();
        this.rebuild();
    };

    VnlTable.prototype.resetOrder = function () {
        if (!this.order.length) return;

        this.order = [];
        this.persistOrder();
        this.rebuild();
    };

    /**
     * Tabloyu bastan kurar ve eldeki veriyi yeniden basar.
     *
     * Sutun sirasi degisince baslik, govde ve toplam satiri birlikte
     * degismek zorunda; parca parca yamamak yerine tumu yeniden kurulur.
     * Veri, filtreler ve secim state'te durdugu icin kaybolmaz.
     */
    VnlTable.prototype.rebuild = function () {
        disposeTooltips(this.root);
        this.closeFilter();

        /* build() yeni bir dinleyici takacak; eskisi birakilmazsa her
           yeniden kurulusta bir tane daha birikirdi. */
        if (this.onResize) {
            window.removeEventListener('resize', this.onResize);
            this.onResize = null;
        }

        this.ths        = {};
        this.filterBtns = {};
        this.tfootCells = null;
        this.totalCells = null;
        this.columns    = this.lead.concat(this.orderData());

        this.build();

        if (this.state.groupBy.length) this.renderGroups();
        else this.renderRows();

        this.renderTotals(this.lastTotals);
        this.renderFooter();
    };

    VnlTable.prototype.restoreHidden = function () {
        var known = {};
        this.columns.forEach(function (c) {
            if (c.hideable !== false) known[c.name] = true;
        });

        var hidden = {};
        try {
            (JSON.parse(localStorage.getItem(this.storageKey()) || '[]')).forEach(function (name) {
                /* Tanım değişmiş olabilir: yalnız hâlâ var olan ve
                   gizlenebilir sütunlar geri yüklenir. */
                if (known[name]) hidden[name] = true;
            });
        } catch (e) { /* bozuk kayıt yok sayılır */ }
        return hidden;
    };

    VnlTable.prototype.persistHidden = function () {
        var names = Object.keys(this.hiddenCols);
        try {
            if (names.length) localStorage.setItem(this.storageKey(), JSON.stringify(names));
            else localStorage.removeItem(this.storageKey());
        } catch (e) { /* localStorage kapalıysa tercih oturumluk kalır */ }
    };

    VnlTable.prototype.visibleCount = function () {
        var self = this;
        return this.columns.filter(function (c) { return !self.hiddenCols[c.name]; }).length;
    };

    /** Araç çubuğundaki sütun menüsü: her sütun için bir onay kutusu. */
    VnlTable.prototype.buildColumnMenu = function () {
        var self = this;

        var wrap = el('div', 'dropdown vnl-table-columns');

        /* data-bs-toggle dropdown'a ayrıldığı için tooltip eski biçimle bağlanır. */
        var btn = iconButton('columns', this.lang.columns || '', 'btn-outline-secondary', true);
        btn.setAttribute('data-bs-toggle', 'dropdown');
        /* İçerideki tıklamalar menüyü kapatmasın: art arda birkaç sütun
           gizlerken menü açık kalmalı. */
        btn.setAttribute('data-bs-auto-close', 'outside');
        btn.setAttribute('aria-expanded', 'false');
        wrap.appendChild(btn);

        var menu = el('div', 'dropdown-menu dropdown-menu-end vnl-table-columns-menu');
        this.checkboxes = {};

        this.columns.forEach(function (col) {
            if (col.hideable === false) return;

            var item = el('label', 'dropdown-item d-flex align-items-center gap-2 mb-0');

            var box = el('input', 'form-check-input m-0');
            box.type = 'checkbox';
            box.checked = !self.hiddenCols[col.name];
            box.addEventListener('change', function () {
                self.toggleColumn(col.name, box.checked);
            });

            item.appendChild(box);
            item.appendChild(el('span', null, col.title));
            menu.appendChild(item);
            self.checkboxes[col.name] = box;
        });

        if (this.opt.reorder) {
            var reset = el('button', 'dropdown-item vnl-columns-reset', this.lang.orderReset || '');
            reset.type = 'button';
            reset.addEventListener('click', function () { self.resetOrder(); });
            menu.appendChild(reset);
        }

        wrap.appendChild(menu);
        this.refreshColumnMenu();

        return wrap;
    };

    VnlTable.prototype.toggleColumn = function (name, visible) {
        if (visible) delete this.hiddenCols[name];
        else this.hiddenCols[name] = true;

        this.persistHidden();
        this.applyVisibility();
        this.refreshColumnMenu();
    };

    /** Son görünür sütunun kutusu kilitlenir: tablo tamamen boşaltılamaz. */
    VnlTable.prototype.refreshColumnMenu = function () {
        var self = this;
        if (!this.checkboxes) return;

        var lastOne = this.visibleCount() === 1;
        Object.keys(this.checkboxes).forEach(function (name) {
            var box = self.checkboxes[name];
            box.disabled = lastOne && !self.hiddenCols[name];
        });
    };

    VnlTable.prototype.applyVisibility = function () {
        var self = this;

        this.columns.forEach(function (col) {
            var hidden = !!self.hiddenCols[col.name];

            var th = self.ths[col.name];
            if (th) th.hidden = hidden;

            /* Alt toplam satırı da aynı sütunları gizler; aksi halde
               hücreler başlıklardan kayardı. */
            var tf = self.tfootCells && self.tfootCells[col.name];
            if (tf) tf.hidden = hidden;
        });

        /* Gövde eldeki veriyle yeniden basılır; sunucuya gidilmez. */
        if (this.state.groupBy.length) this.renderGroups();
        else if (this.state.rows.length) this.renderRows();
        else this.renderMessage(this.lang.empty || '');

        this.layoutPins();
    };

    /* ---------------------------------------------------------------- */
    /* Sütun filtreleri (grid)                                           */
    /* ---------------------------------------------------------------- */

    VnlTable.prototype.buildFilterButton = function (col) {
        var self = this;

        var b = el('button', 'vnl-th-filter');
        b.type = 'button';
        b.title = this.lang.filter || '';
        b.setAttribute('aria-label', (this.lang.filter || '') + ': ' + col.title);
        b.setAttribute('data-bs-toggle', 'tooltip');
        b.appendChild(icon('filter'));

        b.addEventListener('click', function (e) {
            /* Başlığın sıralama dinleyicisine ulaşmasın. */
            e.stopPropagation();
            e.preventDefault();
            hideTooltip(b);
            self.openFilter(col, b);
        });

        this.filterBtns[col.name] = b;

        return b;
    };

    /** Filtresi olan sütunun hunisi dolu ve vurgulu görünür. */
    VnlTable.prototype.paintFilters = function () {
        var self = this;
        if (this.state.pivot.on) return;

        this.columns.forEach(function (col) {
            var btn = self.filterBtns[col.name];
            if (!btn) return;

            var on = !!self.state.filters[col.name];
            var th = self.ths[col.name];
            if (th) th.classList.toggle('vnl-filter-on', on);

            /* Tooltip düğmenin kendisinde durur; yalnız glif değişir. */
            btn.innerHTML = '';
            btn.appendChild(icon(on ? 'filterOn' : 'filter'));
        });

        this.paintChips();
    };

    /** Aktif filtreler rozet olarak; × ile tek tıkta kalkar. */
    VnlTable.prototype.paintChips = function () {
        var self = this;
        if (!this.chips) return;

        var names = this.columns
            .filter(function (col) { return self.state.filters[col.name]; })
            .map(function (col) { return col; });

        this.chips.innerHTML = '';
        this.chips.hidden = !names.length;

        names.forEach(function (col) {
            var chip = el('span', 'vnl-chip');
            chip.appendChild(el('span', 'vnl-chip-text', self.chipText(col)));

            var x = el('button', 'vnl-chip-x', '×');
            x.type = 'button';
            x.setAttribute('aria-label', (self.lang.filterClear || '') + ': ' + col.title);
            x.addEventListener('click', function () {
                delete self.state.filters[col.name];
                self.state.page = 1;
                self.paintFilters();
                self.load();
            });

            chip.appendChild(x);
            self.chips.appendChild(chip);
        });
    };

    /**
     * Çipin metni: "Şehir: Ankara", "Tutar: ≥ 40000", "Tarih: 01.01 – 31.03".
     *
     * Karşılaştırma koşulları sembolle kısalır; kelimeyle yazılınca
     * ("Büyük veya eşittir") çip satırı okunmaz hâle geliyordu.
     */
    VnlTable.prototype.chipText = function (col) {
        var entry = this.state.filters[col.name];
        var lang  = this.lang;
        var parts = [];

        if (entry.set) {
            parts.push(entry.set.length <= 2
                ? entry.set.join(', ')
                : fill(lang.filterCount, { n: entry.set.length }));
        }

        if (entry.cond) {
            var cond  = entry.cond;
            var label = (lang.ops || {})[cond.op] || cond.op;

            if (OPS_NO_VALUE.indexOf(cond.op) !== -1) {
                parts.push(label.toLocaleLowerCase('tr'));
            } else if (cond.op === 'between') {
                parts.push(cond.value + ' – ' + cond.value2);
            } else if (OP_SHORT[cond.op]) {
                parts.push(OP_SHORT[cond.op] + ' ' + cond.value);
            } else {
                parts.push(label.toLocaleLowerCase('tr') + ' ' + cond.value);
            }
        }

        return col.title + ': ' + parts.join(' · ');
    };

    /** Hedef sütunun kendi filtresi hariç hepsi — değer listesi bununla daralır. */
    VnlTable.prototype.filtersExcept = function (name) {
        var filters = this.state.filters;
        var out = {};

        Object.keys(filters).forEach(function (key) {
            if (key !== name) out[key] = filters[key];
        });

        return out;
    };

    VnlTable.prototype.openFilter = function (col, anchor) {
        var reopen = this.panel && this.panel.col.name === col.name;
        this.closeFilter();

        /* Aynı huniye ikinci tıklama kapatır. */
        if (reopen) return;

        var self    = this;
        var current = this.state.filters[col.name] || {};

        var panel = el('div', 'vnl-filter-panel vnl-skin-' + (this.opt.skin || 'default'));
        panel.setAttribute('role', 'dialog');
        panel.setAttribute('aria-label', col.title);

        /* Taslak: panel kendi kopyası üzerinde çalışır, tablo ancak
           Uygula'ya basılınca değişir. checked null ise "hepsi seçili"
           demektir; ilk dokunuşta somut listeye dönüşür. */
        var draft = {
            col:       col,
            anchor:    anchor,
            node:      panel,
            values:    [],
            shown:     [],
            truncated: false,
            checked:   null,
            query:     '',
            token:     0
        };

        if (col.filter.values) this.buildValueList(draft, current);
        if (col.filter.ops && col.filter.ops.length) this.buildCondition(draft, current);

        this.buildFilterActions(draft);

        draft.outside = function (e) {
            if (panel.contains(e.target) || anchor.contains(e.target)) return;
            self.closeFilter();
        };

        draft.keys = function (e) {
            if (e.key === 'Escape') {
                self.closeFilter();
                anchor.focus();
            } else if (e.key === 'Enter' && panel.contains(e.target)) {
                e.preventDefault();
                self.applyFilter(draft);
            }
        };

        draft.reflow = function () { self.place(draft); };

        this.panel = draft;
        document.body.appendChild(panel);
        this.place(draft);

        /* Kaydırma yakalama evresinde dinlenir: paneli açan tablo kendi
           kaydırma kabında oturuyor olabilir, pencere olayı gelmez. */
        document.addEventListener('mousedown', draft.outside, true);
        document.addEventListener('keydown', draft.keys, true);
        window.addEventListener('scroll', draft.reflow, true);
        window.addEventListener('resize', draft.reflow);

        if (col.filter.values) this.loadDistinct(draft);
        else if (draft.v1 && !draft.v1.hidden) draft.v1.focus();
        else if (draft.op) draft.op.focus();
    };

    VnlTable.prototype.closeFilter = function () {
        var draft = this.panel;
        if (!draft) return;

        this.panel = null;

        document.removeEventListener('mousedown', draft.outside, true);
        document.removeEventListener('keydown', draft.keys, true);
        window.removeEventListener('scroll', draft.reflow, true);
        window.removeEventListener('resize', draft.reflow);

        if (draft.node.parentNode) draft.node.parentNode.removeChild(draft.node);
    };

    /** Panel çapaya göre yerleşir; sığmıyorsa başlığın üstüne çıkar. */
    VnlTable.prototype.place = function (draft) {
        var rect = draft.anchor.getBoundingClientRect();

        /* Çapa kaydırmayla görünürden çıktıysa panel havada kalmasın. */
        if (rect.bottom < 0 || rect.top > window.innerHeight) {
            this.closeFilter();
            return;
        }

        var panel = draft.node;
        var w = panel.offsetWidth;
        var h = panel.offsetHeight;

        var left = Math.min(rect.left, window.innerWidth - w - 8);
        var top  = rect.bottom + 4;

        if (top + h > window.innerHeight - 8 && rect.top - h - 4 > 8) {
            top = rect.top - h - 4;
        }

        panel.style.left = Math.max(8, left) + 'px';
        panel.style.top  = Math.max(8, top) + 'px';
    };

    /* ---- Değer listesi ---- */

    VnlTable.prototype.buildValueList = function (draft, current) {
        var self = this;
        var box  = el('div', 'vnl-filter-values');

        var wrap = el('div', 'vnl-filter-search');
        wrap.appendChild(icon('search'));

        var q = el('input', 'form-control form-control-sm');
        q.type = 'search';
        q.placeholder = this.lang.filterSearch || '';
        wrap.appendChild(q);
        box.appendChild(wrap);

        draft.list = el('div', 'vnl-filter-list');
        box.appendChild(draft.list);

        draft.note = el('div', 'vnl-filter-note');
        draft.note.hidden = true;
        box.appendChild(draft.note);

        q.addEventListener('input', debounce(function () {
            draft.query = q.value.trim();

            /* Liste kesilmediyse daraltma istemcide yapılır. Kesildiyse
               listeye hiç girmemiş bir değer ancak sunucuya sorarak
               bulunabilir. */
            if (draft.truncated) self.loadDistinct(draft);
            else self.renderValues(draft);
        }, 250));

        /* Açılışta uygulanmış seçim varsa taslak ondan başlar; listede
           görünmeyen (başka filtrenin sakladığı) değerler de korunur. */
        if (current.set) {
            draft.checked = {};
            current.set.forEach(function (value) { draft.checked[value] = true; });
        }

        draft.node.appendChild(box);
    };

    VnlTable.prototype.renderValues = function (draft) {
        var self = this;
        var list = draft.list;

        list.innerHTML = '';

        var needle = trLower(draft.query);
        draft.shown = draft.values.filter(function (item) {
            return !needle || trLower(item.v).indexOf(needle) !== -1;
        });

        draft.note.hidden = !draft.truncated;
        if (draft.truncated) {
            draft.note.textContent = fill(this.lang.filterMore, { max: draft.values.length });
        }

        if (!draft.shown.length) {
            draft.allBox = null;
            list.appendChild(el('div', 'vnl-filter-empty', this.lang.filterNone || ''));
            return;
        }

        /* (Tümü) üç durumludur: hiçbiri, bir kısmı (kararsız), hepsi. */
        var allItem = el('label', 'vnl-filter-item vnl-filter-all');
        var allBox  = el('input', 'form-check-input');
        allBox.type = 'checkbox';
        allItem.appendChild(allBox);
        allItem.appendChild(el('span', 'vnl-filter-item-label', this.lang.filterAll || ''));
        list.appendChild(allItem);

        draft.allBox = allBox;
        draft.boxes  = {};

        draft.shown.forEach(function (item) {
            var row = el('label', 'vnl-filter-item');

            var cb = el('input', 'form-check-input');
            cb.type = 'checkbox';
            cb.checked = self.isChecked(draft, item.v);
            cb.addEventListener('change', function () {
                self.setChecked(draft, item.v, cb.checked);
                self.paintAllBox(draft);
            });

            row.appendChild(cb);
            row.appendChild(el('span', 'vnl-filter-item-label', item.v));

            /* Sayı isteğe bağlı: geliştirici saymadıysa yalnız değer görünür. */
            if (item.n !== null && item.n !== undefined) {
                row.appendChild(el('span', 'vnl-filter-item-count', item.n));
            }

            list.appendChild(row);
            draft.boxes[item.v] = cb;
        });

        allBox.addEventListener('change', function () {
            var on = allBox.checked;

            draft.shown.forEach(function (item) {
                self.setChecked(draft, item.v, on);
                if (draft.boxes[item.v]) draft.boxes[item.v].checked = on;
            });

            self.paintAllBox(draft);
        });

        this.paintAllBox(draft);
    };

    VnlTable.prototype.isChecked = function (draft, value) {
        return draft.checked ? !!draft.checked[value] : true;
    };

    VnlTable.prototype.setChecked = function (draft, value, on) {
        /* "hepsi" durumu ancak ilk dokunuşta somut listeye dönüşür. */
        if (!draft.checked) {
            draft.checked = {};
            draft.values.forEach(function (item) { draft.checked[item.v] = true; });
        }

        if (on) draft.checked[value] = true;
        else delete draft.checked[value];
    };

    VnlTable.prototype.paintAllBox = function (draft) {
        var self = this;

        if (draft.allBox) {
            var on = 0;
            draft.shown.forEach(function (item) { if (self.isChecked(draft, item.v)) on++; });

            draft.allBox.checked = on > 0 && on === draft.shown.length;
            draft.allBox.indeterminate = on > 0 && on < draft.shown.length;
        }

        /* Hiçbir değer seçili değilken uygulanacak bir şey yok. */
        if (draft.apply) {
            draft.apply.disabled = !!draft.checked && Object.keys(draft.checked).length === 0;
        }
    };

    /** Seçim filtre midir: listenin tamamı işaretliyse değildir. */
    VnlTable.prototype.collectSet = function (draft) {
        if (!draft.checked) return null;

        var keys = Object.keys(draft.checked);
        if (!keys.length) return null;

        var coversAll = keys.length === draft.values.length && draft.values.every(function (item) {
            return draft.checked[item.v];
        });

        return coversAll ? null : keys;
    };

    VnlTable.prototype.loadDistinct = function (draft) {
        var self = this;

        draft.list.innerHTML = '';
        draft.list.appendChild(el('div', 'vnl-filter-empty', this.lang.loading || ''));

        /* Yalnız en son istek işlenir: hızlı yazan kullanıcıda geciken bir
           yanıt yeni listeyi ezmesin. */
        var token = ++draft.token;

        this.post(this.config.url, this.payload('distinct', {
            col:     draft.col.name,
            q:       draft.query,
            filters: this.filtersExcept(draft.col.name)
        }))
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (self.panel !== draft || draft.token !== token) return;

                draft.values    = data.values || [];
                draft.truncated = !!data.truncated;
                self.renderValues(draft);
                self.place(draft);
            })
            .catch(function (err) {
                if (self.panel !== draft || draft.token !== token) return;

                console.error('[VNL Table] ' + self.config.id + ' distinct:', err);
                draft.list.innerHTML = '';
                draft.list.appendChild(el('div', 'vnl-filter-empty vnl-table-error', self.lang.error || ''));
            });
    };

    /* ---- Koşul ---- */

    VnlTable.prototype.buildCondition = function (draft, current) {
        var self = this;
        var kind = draft.col.filter.kind;
        var box  = el('div', 'vnl-filter-cond');

        /* Başlık yalnız değer listesiyle birlikteyken gerekli: koşul tek
           başınaysa panelde başka bir şey yok, ne olduğu zaten belli. */
        if (draft.col.filter.values) {
            box.appendChild(el('div', 'vnl-filter-title', this.lang.filterCond || ''));
        }

        var op = el('select', 'form-select form-select-sm');
        var none = el('option', null, this.lang.filterNoCond || '');
        none.value = '';
        op.appendChild(none);

        draft.col.filter.ops.forEach(function (name) {
            var o = el('option', null, (self.lang.ops || {})[name] || name);
            o.value = name;
            op.appendChild(o);
        });

        /* Tarih ve sayıda tarayıcının kendi denetimi kullanılır: takvim ve
           artırma okları bedavaya gelir. */
        var type = kind === 'number' ? 'number' : (kind === 'date' ? 'date' : 'text');

        var v1 = el('input', 'form-control form-control-sm');
        var v2 = el('input', 'form-control form-control-sm');
        v1.type = type;
        v2.type = type;

        box.appendChild(op);
        box.appendChild(v1);
        box.appendChild(v2);

        if (current.cond) {
            op.value = current.cond.op;
            v1.value = current.cond.value || '';
            v2.value = current.cond.value2 || '';
        } else if (!draft.col.filter.values) {
            /* Değer listesi olmayan sütunda "Koşul yok" ile açmak paneli boş
               gösteriyordu: ortada yapılacak bir şey yokmuş gibi duruyordu.
               İlk koşul seçili gelir, kutu hazır bekler. */
            op.value = draft.col.filter.ops[0];
        }

        function sync() {
            var name = op.value;
            v1.hidden = name === '' || OPS_NO_VALUE.indexOf(name) !== -1;
            v2.hidden = OPS_RANGE.indexOf(name) === -1;
        }

        op.addEventListener('change', function () {
            sync();
            v1.classList.remove('is-invalid');
            v2.classList.remove('is-invalid');
            self.place(draft);
        });

        sync();

        draft.op = op;
        draft.v1 = v1;
        draft.v2 = v2;
        draft.node.appendChild(box);
    };

    VnlTable.prototype.buildFilterActions = function (draft) {
        var self = this;
        var box  = el('div', 'vnl-filter-actions');

        var reset = el('button', 'btn btn-sm btn-outline-secondary', this.lang.filterClear || '');
        reset.type = 'button';
        reset.addEventListener('click', function () {
            delete self.state.filters[draft.col.name];
            self.state.page = 1;
            self.closeFilter();
            self.paintFilters();
            self.load();
        });

        var apply = el('button', 'btn btn-sm btn-primary', this.lang.apply || '');
        apply.type = 'button';
        apply.addEventListener('click', function () { self.applyFilter(draft); });

        box.appendChild(reset);
        box.appendChild(apply);

        draft.apply = apply;
        draft.node.appendChild(box);
    };

    /** Taslağı tabloya işler. Küme ve koşul birlikte gelebilir; VE ile bağlanır. */
    VnlTable.prototype.applyFilter = function (draft) {
        var col   = draft.col;
        var entry = { kind: col.filter.kind };

        var set = this.collectSet(draft);
        if (set) entry.set = set;

        if (draft.op && draft.op.value) {
            var name = draft.op.value;
            var cond = { op: name };

            if (OPS_NO_VALUE.indexOf(name) === -1) {
                if (!filled(draft.v1)) return;
                cond.value = draft.v1.value;

                if (OPS_RANGE.indexOf(name) !== -1) {
                    if (!filled(draft.v2)) return;
                    cond.value2 = draft.v2.value;
                }
            }

            entry.cond = cond;
        }

        if (entry.set || entry.cond) this.state.filters[col.name] = entry;
        else delete this.state.filters[col.name];

        this.state.page = 1;
        this.closeFilter();
        this.paintFilters();
        this.load();

        /** Değersiz koşul filtre değildir; kutu işaretlenir ve odak oraya gider. */
        function filled(input) {
            var ok = input.value !== '';
            input.classList.toggle('is-invalid', !ok);
            if (!ok) input.focus();

            return ok;
        }
    };

    VnlTable.prototype.clear = function () {
        this.closeFilter();
        this.state.search  = '';
        this.state.sort    = (this.opt.order || []).slice();
        this.state.filters = {};
        this.state.page    = 1;
        this.clearSelection();
        if (this.searchInput) this.searchInput.value = '';
        this.paintSort();
        this.paintFilters();
        this.load();
    };

    VnlTable.prototype.payload = function (op, extra) {
        var body = {
            op:      op || 'list',
            page:    this.state.page,
            size:    this.state.size,
            search:  this.state.search,
            sort:    this.state.sort,
            filters: this.state.filters,
            context: this.config.context || {}
        };

        if (extra) {
            Object.keys(extra).forEach(function (k) { body[k] = extra[k]; });
        }

        return body;
    };

    /* ---------------------------------------------------------------- */
    /* Satir gruplama (workbench)                                        */
    /* ---------------------------------------------------------------- */

    /**
     * Gruplama birden cok seviyeli olabilir: Sehir -> Durum gibi.
     *
     * Bir dugumun YOLU, ust seviyelerdeki anahtarlarin dizisidir
     * (['Ankara', 'Onaylandi']). Yol sunucuya ayri bir alan olarak gitmez;
     * siradan filtreye cevrilir. Boylece sunucunun gruplama icin ayri bir
     * yol tutmasi gerekmez: her seviye tek sutunluk bir op = group istegi.
     */
    VnlTable.prototype.pathKey = function (path) {
        return path.join('\u0000');
    };

    /** Yolu filtreye cevirir: her seviye kendi sutununda tek degerlik kume. */
    VnlTable.prototype.pathFilters = function (path) {
        var self    = this;
        var filters = {};

        Object.keys(this.state.filters).forEach(function (k) { filters[k] = self.state.filters[k]; });

        path.forEach(function (value, i) {
            filters[self.state.groupBy[i]] = { kind: 'text', set: [value] };
        });

        return filters;
    };

    /* ---- Birakma alani ---- */

    /**
     * Basligin suruklenip birakildigi alan.
     *
     * Seviyeleri sirayla rozet olarak gosterir; yeni bir baslik birakmak
     * sona bir seviye ekler. Bosken ne yapilacagini soyleyen kesik cizgili
     * bir serittir.
     */
    VnlTable.prototype.buildDropZone = function () {
        var self = this;
        var zone = el('div', 'vnl-group-zone');

        zone.addEventListener('dragover', function (e) {
            /* preventDefault olmadan tarayici birakmaya izin vermez. */
            e.preventDefault();
            e.dataTransfer.dropEffect = 'copy';
            zone.classList.add('vnl-group-zone-over');
        });

        zone.addEventListener('dragleave', function () {
            zone.classList.remove('vnl-group-zone-over');
        });

        zone.addEventListener('drop', function (e) {
            e.preventDefault();
            zone.classList.remove('vnl-group-zone-over');

            var name = e.dataTransfer.getData('text/vnl-column');
            var col   = name && self.column(name);

            /* Gruplanamayan sutun buraya birakilabilir ama gruplanamaz;
               sessizce yutmak yerine hicbir sey yapilmaz. */
            if (col && col.groupable !== false) self.addGroup(name);
        });

        this.dropZone = zone;
        this.root.appendChild(zone);
        this.paintDropZone();
    };

    VnlTable.prototype.paintDropZone = function () {
        var self = this;
        var zone = this.dropZone;
        if (!zone) return;

        zone.innerHTML = '';
        zone.classList.toggle('vnl-group-zone-on', this.state.groupBy.length > 0);

        if (!this.state.groupBy.length) {
            zone.appendChild(el('span', 'vnl-group-zone-hint', this.lang.groupDrop || ''));

            return;
        }

        this.state.groupBy.forEach(function (name, i) {
            var col = self.column(name);
            if (!col) return;

            /* Seviye numarasi sirayi gosterir: Sehir -> Durum ile
               Durum -> Sehir bambaska iki agac. */
            var chip = el('span', 'vnl-chip vnl-group-chip');
            chip.appendChild(el('span', 'vnl-group-chip-level', i + 1));
            chip.appendChild(el('span', 'vnl-chip-text', col.title));

            var x = el('button', 'vnl-chip-x', '\u00d7');
            x.type = 'button';
            x.setAttribute('aria-label', col.title);
            x.addEventListener('click', function () { self.removeGroup(name); });

            chip.appendChild(x);
            zone.appendChild(chip);
        });
    };

    /** Baslik yazisini suruklenebilir yapar. */
    VnlTable.prototype.makeDraggable = function (label, col) {
        var self = this;

        label.setAttribute('draggable', 'true');
        label.classList.add('vnl-th-drag');

        label.addEventListener('dragstart', function (e) {
            self.dragging = true;
            self.dragCol  = col;
            self.root.classList.add('vnl-dragging');
            self.root.classList.toggle('vnl-dragging-groupable', col.groupable !== false);

            /* Firefox setData olmadan suruklemeyi hic baslatmiyor. */
            e.dataTransfer.setData('text/vnl-column', col.name);
            e.dataTransfer.setData('text/plain', col.title);
            e.dataTransfer.effectAllowed = 'copyMove';
        });

        label.addEventListener('dragend', function () {
            self.dragCol = null;
            self.root.classList.remove('vnl-dragging', 'vnl-dragging-groupable');
            self.clearDropMarks();

            /* Bayrak bir tur sonra duser: dragend'den sonra gelen click
               siralamayi tetiklemesin. */
            setTimeout(function () { self.dragging = false; }, 0);
        });
    };

    /**
     * Baslik hucresini birakma hedefi yapar.
     *
     * Imlecin hucrenin hangi yarisinda oldugu, sutunun oncesine mi
     * sonrasina mi girecegini belirler; kenarda beliren cizgi de bunu
     * gosterir. Isaret olmadan kullanici nereye birakacagini bilemiyordu.
     */
    VnlTable.prototype.makeDropTarget = function (th, col) {
        var self = this;

        th.addEventListener('dragover', function (e) {
            if (!self.dragCol || self.dragCol.name === col.name) return;

            e.preventDefault();
            e.dataTransfer.dropEffect = 'move';

            var box   = th.getBoundingClientRect();
            var after = (e.clientX - box.left) > box.width / 2;

            self.clearDropMarks();
            th.classList.add(after ? 'vnl-drop-after' : 'vnl-drop-before');
        });

        th.addEventListener('dragleave', function () {
            th.classList.remove('vnl-drop-before', 'vnl-drop-after');
        });

        th.addEventListener('drop', function (e) {
            if (!self.dragCol) return;

            e.preventDefault();
            e.stopPropagation();

            var after = th.classList.contains('vnl-drop-after');
            var from  = self.dragCol.name;

            self.clearDropMarks();
            self.moveColumn(from, col.name, after);
        });
    };

    VnlTable.prototype.clearDropMarks = function () {
        this.root.querySelectorAll('.vnl-drop-before, .vnl-drop-after').forEach(function (node) {
            node.classList.remove('vnl-drop-before', 'vnl-drop-after');
        });
    };

    /* ---- Menu ---- */

    VnlTable.prototype.buildGroupMenu = function () {
        var self = this;
        var wrap = el('div', 'dropdown vnl-table-group');

        var btn = iconButton('group', this.lang.group || '', 'btn-outline-secondary', true);
        btn.setAttribute('data-bs-toggle', 'dropdown');
        /* Art arda birkac seviye eklerken menu acik kalsin. */
        btn.setAttribute('data-bs-auto-close', 'outside');
        btn.setAttribute('aria-expanded', 'false');
        wrap.appendChild(btn);

        var menu = el('div', 'dropdown-menu dropdown-menu-end vnl-table-group-menu');
        this.groupItems = {};

        this.columns.forEach(function (col) {
            if (col.groupable === false) return;

            var item = el('label', 'dropdown-item d-flex align-items-center gap-2 mb-0');

            var box = el('input', 'form-check-input m-0');
            box.type = 'checkbox';
            box.addEventListener('change', function () {
                if (box.checked) self.addGroup(col.name);
                else self.removeGroup(col.name);
            });

            var level = el('span', 'vnl-group-menu-level');

            item.appendChild(box);
            item.appendChild(el('span', null, col.title));
            item.appendChild(level);
            menu.appendChild(item);

            self.groupItems[col.name] = { box: box, level: level };
        });

        var none = el('button', 'dropdown-item vnl-group-menu-clear', this.lang.groupNone || '');
        none.type = 'button';
        none.addEventListener('click', function () { self.setGroups([]); });
        menu.appendChild(none);

        wrap.appendChild(menu);
        this.groupBtn = btn;
        this.paintGroupMenu();

        return wrap;
    };

    VnlTable.prototype.paintGroupMenu = function () {
        var self = this;

        if (this.groupItems) {
            Object.keys(this.groupItems).forEach(function (name) {
                var at   = self.state.groupBy.indexOf(name);
                var item = self.groupItems[name];

                item.box.checked = at !== -1;
                item.level.textContent = at === -1 ? '' : (at + 1);
            });
        }

        if (this.groupBtn) this.groupBtn.classList.toggle('vnl-group-on', this.state.groupBy.length > 0);

        this.paintDropZone();
    };

    VnlTable.prototype.addGroup = function (name) {
        if (this.state.groupBy.indexOf(name) !== -1) return;

        this.setGroups(this.state.groupBy.concat([name]));
    };

    VnlTable.prototype.removeGroup = function (name) {
        this.setGroups(this.state.groupBy.filter(function (n) { return n !== name; }));
    };

    VnlTable.prototype.setGroups = function (names) {
        if (names.join(',') === this.state.groupBy.join(',')) return;

        this.state.groupBy = names;
        this.state.page    = 1;
        this.nodes         = {};
        this.buckets       = {};
        this.expanded      = {};

        this.paintGroupMenu();
        this.load();
    };

    /* ---- Yukleme ---- */

    /**
     * Satirlarin nereden gelecegi.
     *
     *   client - hepsi tek istekle iner, butun seviyeler tarayicida
     *            hesaplanir; acmak kapamak aninda olur.
     *   server - her seviye ve her yaprak ayri istekle gelir.
     *   auto   - kayit sayisi limit'in altindaysa client. Sayi henuz
     *            bilinmiyorsa client denenir; gelen yanit tavana
     *            dayandiysa server'a dusulur.
     */
    VnlTable.prototype.groupStrategy = function () {
        var group = this.opt.group || {};

        if (group.mode === 'client' || group.mode === 'server') {
            return group.mode;
        }

        return this.state.filtered <= (group.limit || 5000) ? 'client' : 'server';
    };

    VnlTable.prototype.loadGroups = function () {
        var self = this;

        this.setBusy(true);
        if (!this.tbody.querySelector('.vnl-group-row')) this.renderMessage('');

        this.nodes   = {};
        this.buckets = {};
        this.allRows = null;

        if (this.groupStrategy() === 'client') {
            this.loadAllRows().catch(function (err) { self.failed(err, 'group'); });

            return;
        }

        this.loadNode([], true);
    };

    /** client stratejisi: butun satirlar tek seferde iner. */
    VnlTable.prototype.loadAllRows = function () {
        var self  = this;
        var limit = (this.opt.group || {}).limit || 5000;

        return this.post(this.config.url, this.payload('list', { page: 1, size: limit }))
            .then(function (r) { return r.json(); })
            .then(function (data) {
                self.state.filtered = data.filtered || 0;
                if (!self.state.total) self.state.total = data.total || self.state.filtered;

                /* Yanit tavana dayandiysa elimizdeki kume eksik: butun
                   seviyeleri buradan hesaplamak yanlis sayilar uretirdi. */
                if (self.state.filtered > limit) {
                    self.allRows = null;
                    self.loadNode([], true);

                    return;
                }

                self.allRows = data.rows || [];
                self.finishGroups();
            });
    };

    /** server stratejisi: bir seviyenin gruplari. */
    VnlTable.prototype.loadNode = function (path, first) {
        var self = this;
        var key  = this.pathKey(path);
        var node = this.nodes[key] = this.nodes[key] || { groups: null, loading: false };

        if (node.loading || node.groups) return;
        node.loading = true;

        this.post(this.config.url, this.payload('group', {
            by:      this.state.groupBy[path.length],
            filters: this.pathFilters(path)
        }))
            .then(function (r) { return r.json(); })
            .then(function (data) {
                node.loading = false;
                node.groups  = data.groups || [];

                if (first) {
                    self.state.filtered = node.groups.reduce(function (sum, g) { return sum + (g.n || 0); }, 0);
                    if (!self.state.total) self.state.total = self.state.filtered;
                    self.finishGroups();

                    return;
                }

                self.renderGroups();
            })
            .catch(function (err) {
                node.loading = false;
                node.groups  = [];

                if (first) return self.failed(err, 'group');

                console.error('[VNL Table] ' + self.config.id + ' group:', err);
                self.renderGroups();
            });
    };

    VnlTable.prototype.finishGroups = function () {
        this.state.loading = false;
        this.setBusy(false);
        this.renderGroups();
        this.renderTotals(null);
        this.renderFooter();
    };

    /* ---- Yerel hesap (client stratejisi) ---- */

    /** Yolun altina dusen satirlar. */
    VnlTable.prototype.rowsAt = function (path) {
        var self = this;

        return (this.allRows || []).filter(function (row) {
            return path.every(function (value, i) {
                return asText(row[self.state.groupBy[i]]) === value;
            });
        });
    };

    /** Yolun altindaki bir seviyenin gruplari - tamamen tarayicida. */
    VnlTable.prototype.localGroups = function (path) {
        var self    = this;
        var column  = this.state.groupBy[path.length];
        var buckets = {};

        this.rowsAt(path).forEach(function (row) {
            var key = asText(row[column]);
            (buckets[key] = buckets[key] || []).push(row);
        });

        return Object.keys(buckets).sort(function (a, b) {
            return trLower(a).localeCompare(trLower(b), 'tr', { numeric: true });
        }).map(function (key) {
            return { k: key, n: buckets[key].length, totals: self.localTotals(buckets[key]) };
        });
    };

    /** Sunucudaki VnlTableRequest::totals ile ayni kurallar. */
    VnlTable.prototype.localTotals = function (rows) {
        var totals = {};

        (this.aggCols || []).forEach(function (col) {
            var values = [];

            rows.forEach(function (row) {
                var cell = row[col.name];

                if (col.agg === 'count') {
                    if (asText(cell) !== '') values.push(1);
                } else if (cell !== null && cell !== '' && isFinite(cell)) {
                    values.push(parseFloat(cell));
                }
            });

            if (!values.length) {
                totals[col.name] = null;

                return;
            }

            var sum = values.reduce(function (a, b) { return a + b; }, 0);

            switch (col.agg) {
                case 'sum':   totals[col.name] = sum; break;
                case 'count': totals[col.name] = values.length; break;
                case 'min':   totals[col.name] = Math.min.apply(null, values); break;
                case 'max':   totals[col.name] = Math.max.apply(null, values); break;
                case 'avg':   totals[col.name] = Math.round((sum / values.length) * 100) / 100; break;
            }
        });

        return totals;
    };

    /**
     * Bir yolun altindaki gruplar. Henuz gelmemisse null doner ve sunucu
     * stratejisinde yukleme baslatilir.
     */
    VnlTable.prototype.groupsAt = function (path) {
        if (this.allRows) return this.localGroups(path);

        var node = this.nodes[this.pathKey(path)];

        if (!node) {
            this.loadNode(path);

            return null;
        }

        return node.groups;
    };

    /* ---- Cizim ---- */

    VnlTable.prototype.renderGroups = function () {
        var self   = this;
        var groups = this.groupsAt([]) || [];

        if (!groups.length) {
            this.renderMessage(this.lang.empty || '');

            return;
        }

        var frag  = document.createDocumentFragment();
        var start = (this.state.page - 1) * this.state.size;

        groups.slice(start, start + this.state.size).forEach(function (group) {
            self.renderNode(frag, [group.k], group, 0);
        });

        this.tbody.innerHTML = '';
        this.tbody.appendChild(frag);
        this.layoutPins();
        this.paintSelection();
    };

    /** Bir grup satiri ve - acikssa - altindakiler. Kendini cagirir. */
    VnlTable.prototype.renderNode = function (frag, path, group, depth) {
        var self = this;

        frag.appendChild(this.buildGroupRow(group, path, depth));

        if (!this.expanded[this.pathKey(path)]) return;

        /* Son seviye degilse altinda yine gruplar var. */
        if (depth < this.state.groupBy.length - 1) {
            var children = this.groupsAt(path);

            if (children === null) {
                frag.appendChild(this.spanRow(this.lang.loading || '', 'vnl-group-note', depth + 1));

                return;
            }

            children.forEach(function (child) {
                self.renderNode(frag, path.concat([child.k]), child, depth + 1);
            });

            return;
        }

        this.renderLeaf(frag, path, depth);
    };

    /** Yaprak grubun satirlari. */
    VnlTable.prototype.renderLeaf = function (frag, path, depth) {
        var self = this;

        if (this.allRows) {
            this.rowsAt(path).forEach(function (row) {
                self.appendRow(frag, row, depth + 1);
            });

            return;
        }

        var bucket = this.buckets[this.pathKey(path)];

        if (!bucket) {
            this.loadGroupRows(path);
            frag.appendChild(this.spanRow(this.lang.loading || '', 'vnl-group-note', depth + 1));

            return;
        }

        bucket.rows.forEach(function (row) {
            self.appendRow(frag, row, depth + 1);
        });

        if (bucket.loading) {
            frag.appendChild(this.spanRow(this.lang.loading || '', 'vnl-group-note', depth + 1));

            return;
        }

        /* Sunucu stratejisinde grup parca parca iner. */
        if (!bucket.done) {
            var more = this.spanRow('', 'vnl-group-note', depth + 1);
            var btn  = el('button', 'btn btn-sm btn-link', this.lang.groupMore || '');
            btn.type = 'button';
            btn.addEventListener('click', function () { self.loadGroupRows(path); });
            more.querySelector('td').appendChild(btn);
            frag.appendChild(more);
        }
    };

    /** Satırı ve — açıksa — altındaki detay gövdesini basar. */
    VnlTable.prototype.appendRow = function (frag, row, depth) {
        var tr = this.buildRow(row);

        if (depth) {
            tr.classList.add('vnl-group-member');
            tr.style.setProperty('--vnl-depth', depth);
        }

        frag.appendChild(tr);

        var key = this.rowKey(row);

        if (key !== null && this.detailOpen[key]) {
            tr.classList.add('vnl-has-detail');
            frag.appendChild(this.detailRow(key, depth));
        }
    };

    /** Tum sutunlari kaplayan tek hucreli satir. */
    VnlTable.prototype.spanRow = function (text, cls, depth) {
        var tr = el('tr', cls);
        var td = el('td', null, text);
        td.colSpan = this.visibleCount();
        tr.appendChild(td);

        if (depth) tr.style.setProperty('--vnl-depth', depth);

        return tr;
    };

    VnlTable.prototype.buildGroupRow = function (group, path, depth) {
        var self = this;
        var open = !!this.expanded[this.pathKey(path)];

        var tr = el('tr', 'vnl-group-row' + (open ? ' vnl-group-open' : ''));
        tr.style.setProperty('--vnl-depth', depth);

        var td = el('td');
        td.colSpan = this.visibleCount();

        /* Ic kutu yatay kaydirmada yerinde kalir: genis bir tabloda saga
           kayinca grup basligi gorunmez oluyordu. */
        var inner = el('div', 'vnl-group-inner');

        inner.appendChild(el('span', 'vnl-group-caret'));

        /* Birden cok seviye varken hangi sutunun grubu oldugu yazilir;
           tek seviyede gereksiz gurultu. */
        if (this.state.groupBy.length > 1) {
            var col = this.column(this.state.groupBy[depth]);
            if (col) inner.appendChild(el('span', 'vnl-group-col', col.title));
        }

        inner.appendChild(el('span', 'vnl-group-key', group.k === '' ? (this.lang.groupEmpty || '') : group.k));

        if (group.n !== null && group.n !== undefined) {
            inner.appendChild(el('span', 'vnl-group-count', fill(this.lang.groupCount, { n: group.n })));
        }

        if (group.totals) {
            (this.aggCols || []).forEach(function (col) {
                var value = group.totals[col.name];
                if (value === null || value === undefined) return;

                var chip = el('span', 'vnl-group-total');
                chip.appendChild(el('span', 'vnl-group-total-label', col.title));
                chip.appendChild(el('span', 'vnl-group-total-value', asText(value)));
                inner.appendChild(chip);
            });
        }

        td.appendChild(inner);
        tr.appendChild(td);

        tr.setAttribute('role', 'button');
        tr.setAttribute('aria-expanded', open ? 'true' : 'false');
        tr.tabIndex = 0;
        tr.addEventListener('click', function () { self.toggleGroup(path); });
        tr.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                self.toggleGroup(path);
            }
        });

        return tr;
    };

    VnlTable.prototype.toggleGroup = function (path) {
        var key = this.pathKey(path);

        if (this.expanded[key]) delete this.expanded[key];
        else this.expanded[key] = true;

        this.renderGroups();
    };

    /** Yaprak grubun satirlarini ceker; ikinci cagrida sonrakileri ekler. */
    VnlTable.prototype.loadGroupRows = function (path) {
        var self   = this;
        var key    = this.pathKey(path);
        var bucket = this.buckets[key] = this.buckets[key] || { rows: [], done: false, loading: false };

        if (bucket.loading) return;
        bucket.loading = true;

        var page = Math.floor(bucket.rows.length / this.state.size) + 1;

        this.post(this.config.url, this.payload('list', {
            page:    page,
            filters: this.pathFilters(path)
        }))
            .then(function (r) { return r.json(); })
            .then(function (data) {
                bucket.loading = false;
                bucket.rows = bucket.rows.concat(data.rows || []);
                bucket.done = bucket.rows.length >= (data.filtered || 0);
                self.renderGroups();
            })
            .catch(function (err) {
                bucket.loading = false;
                bucket.done = true;
                console.error('[VNL Table] ' + self.config.id + ' group rows:', err);
                self.renderGroups();
            });
    };

    /* ---------------------------------------------------------------- */
    /* Satır altı detay (workbench)                                      */
    /* ---------------------------------------------------------------- */

    VnlTable.prototype.buildDetailToggle = function (row, tr) {
        var self = this;
        var key  = this.rowKey(row);

        var btn = el('button', 'vnl-detail-toggle');
        btn.type = 'button';
        btn.title = this.lang.detail || '';
        btn.setAttribute('aria-label', this.lang.detail || '');
        btn.setAttribute('aria-expanded', (key !== null && this.detailOpen[key]) ? 'true' : 'false');
        btn.appendChild(el('span', 'vnl-detail-caret'));

        if (key === null) {
            /* Kimliksiz satırın detayı istenemez: hangi kayıt olduğunu
               sunucuya söyleyecek bir şey yok. */
            btn.disabled = true;

            return btn;
        }

        btn.addEventListener('click', function (e) {
            e.stopPropagation();
            self.toggleDetail(key, tr, btn);
        });

        return btn;
    };

    VnlTable.prototype.toggleDetail = function (key, tr, btn) {
        var next = tr.nextSibling;

        if (this.detailOpen[key]) {
            delete this.detailOpen[key];
            tr.classList.remove('vnl-has-detail');
            btn.setAttribute('aria-expanded', 'false');

            if (next && next.classList && next.classList.contains('vnl-detail-row')) {
                next.parentNode.removeChild(next);
            }

            return;
        }

        this.detailOpen[key] = true;
        tr.classList.add('vnl-has-detail');
        btn.setAttribute('aria-expanded', 'true');

        var depth = parseInt(tr.style.getPropertyValue('--vnl-depth'), 10) || 0;
        tr.parentNode.insertBefore(this.detailRow(key, depth), tr.nextSibling);
    };

    /**
     * Detay satırı. Gövde bellekte varsa oradan, yoksa sunucudan.
     *
     * İçerik innerHTML ile basılır: sunucu HTML üretir ve kaçışından
     * kendisi sorumludur — render:'html' sütunlarıyla aynı sözleşme.
     */
    VnlTable.prototype.detailRow = function (key, depth) {
        var self = this;

        var tr = el('tr', 'vnl-detail-row');
        if (depth) tr.style.setProperty('--vnl-depth', depth);

        var td = el('td');
        td.colSpan = this.visibleCount();

        var body = el('div', 'vnl-detail-body');
        td.appendChild(body);
        tr.appendChild(td);

        if (this.details[key] !== undefined) {
            body.innerHTML = this.details[key];

            return tr;
        }

        body.textContent = this.lang.loading || '';

        this.post(this.config.detailUrl || this.config.url, this.payload('detail', { key: key }))
            .then(function (r) { return r.text(); })
            .then(function (html) {
                self.details[key] = html;

                /* Satır bu arada kapatılmış ya da tablo yeniden çizilmiş
                   olabilir; gövde hâlâ ekrandaysa doldurulur. */
                if (body.parentNode) body.innerHTML = html;
            })
            .catch(function (err) {
                console.error('[VNL Table] ' + self.config.id + ' detail:', err);
                body.textContent = self.lang.detailFailed || '';
                body.classList.add('vnl-table-error');
            });

        return tr;
    };

    /* ---------------------------------------------------------------- */
    /* Seçim ve toplu işlem (workbench)                                  */
    /* ---------------------------------------------------------------- */

    VnlTable.prototype.isRowSelected = function (key) {
        return this.selection.mode === 'all'
            ? !this.selection.except[key]
            : !!this.selection.ids[key];
    };

    VnlTable.prototype.setRowSelected = function (key, on) {
        if (this.selection.mode === 'all') {
            if (on) delete this.selection.except[key];
            else this.selection.except[key] = true;
        } else if (on) {
            this.selection.ids[key] = true;
        } else {
            delete this.selection.ids[key];
        }
    };

    /** Seçili kayıt sayısı. 'all' modunda sunucunun bildirdiği sayıdan düşülür. */
    VnlTable.prototype.selectionCount = function () {
        if (this.selection.mode === 'all') {
            return Math.max(0, this.state.filtered - Object.keys(this.selection.except).length);
        }

        return Object.keys(this.selection.ids).length;
    };

    VnlTable.prototype.clearSelection = function () {
        this.selection = { mode: 'some', ids: {}, except: {} };
        this.paintSelection();
    };

    /** Satır onay kutusu. */
    VnlTable.prototype.buildRowBox = function (row) {
        var self = this;
        var key  = this.rowKey(row);

        var box = el('input', 'form-check-input vnl-row-box');
        box.type = 'checkbox';
        box.setAttribute('aria-label', this.lang.select || '');

        if (key === null) {
            box.disabled = true;

            return box;
        }

        box.checked = this.isRowSelected(key);
        box.addEventListener('click', function (e) { e.stopPropagation(); });
        box.addEventListener('change', function () {
            self.setRowSelected(key, box.checked);
            self.paintSelection();
        });

        return box;
    };

    /** Başlıktaki kutu: GÖRÜNEN sayfayı seçer, tabloyu değil. */
    VnlTable.prototype.buildSelectAllBox = function () {
        var self = this;

        var box = el('input', 'form-check-input vnl-page-box');
        box.type = 'checkbox';
        box.title = this.lang.selectAll || '';
        box.setAttribute('aria-label', this.lang.selectAll || '');

        box.addEventListener('change', function () {
            self.pageKeys().forEach(function (key) { self.setRowSelected(key, box.checked); });
            self.paintSelection();
        });

        this.pageBox = box;

        return box;
    };

    /** Ekranda basılı duran satırların kimlikleri. */
    VnlTable.prototype.pageKeys = function () {
        var self = this;
        var keys = [];

        Array.prototype.forEach.call(this.tbody.querySelectorAll('tr[data-key]'), function (tr) {
            keys.push(tr.dataset.key);
        });

        return keys;
    };

    /**
     * Seçim çubuğu: kaç kayıt seçili, hangi işlemler yapılabilir.
     *
     * Sayfanın tamamı seçiliyken ve filtrede daha fazlası varken
     * "filtreye uyan hepsini seç" önerilir — kullanıcının 25 satırı
     * seçip 4200 kaydı kastettiği durum budur.
     */
    VnlTable.prototype.buildSelectionBar = function () {
        this.selectionBar = el('div', 'vnl-table-selection');
        this.selectionBar.hidden = true;
        this.root.appendChild(this.selectionBar);
    };

    VnlTable.prototype.paintSelection = function () {
        var self  = this;
        if (this.state.pivot.on) return;
        var count = this.selectionCount();

        /* Satır kutuları ve sayfa kutusu her çizimden sonra tazelenir. */
        var keys = this.pageKeys();
        var on   = 0;

        Array.prototype.forEach.call(this.tbody.querySelectorAll('tr[data-key]'), function (tr) {
            var box = tr.querySelector('.vnl-row-box');
            if (!box) return;

            box.checked = self.isRowSelected(tr.dataset.key);
            if (box.checked) on++;

            tr.classList.toggle('vnl-row-selected', box.checked);
        });

        if (this.pageBox) {
            this.pageBox.checked = keys.length > 0 && on === keys.length;
            this.pageBox.indeterminate = on > 0 && on < keys.length;
        }

        var bar = this.selectionBar;
        if (!bar) return;

        bar.innerHTML = '';
        bar.hidden = count === 0;

        if (!count) return;

        var all = this.selection.mode === 'all';

        bar.appendChild(el('span', 'vnl-selection-count',
            fill(all ? this.lang.selectedAll : this.lang.selected, { n: count })));

        /* Sayfanın tamamı seçili ve filtrede daha fazlası var. */
        if (!all && keys.length && on === keys.length && this.state.filtered > count) {
            var more = el('button', 'btn btn-sm btn-link vnl-selection-more',
                fill(this.lang.selectMatching, { n: this.state.filtered }));
            more.type = 'button';
            more.addEventListener('click', function () {
                self.selection = { mode: 'all', ids: {}, except: {} };
                self.paintSelection();
            });

            bar.appendChild(more);
        }

        var actions = el('div', 'vnl-selection-actions');

        (this.config.actions || []).forEach(function (action) {
            var btn = el('button', 'btn btn-sm btn-' + (action.variant || 'secondary'), action.title);
            btn.type = 'button';
            btn.addEventListener('click', function () { self.runAction(action, btn); });
            actions.appendChild(btn);
        });

        var clear = el('button', 'btn btn-sm btn-link', this.lang.selectClear || '');
        clear.type = 'button';
        clear.addEventListener('click', function () { self.clearSelection(); });
        actions.appendChild(clear);

        bar.appendChild(actions);
    };

    VnlTable.prototype.runAction = function (action, btn) {
        var self = this;

        if (!action.confirm) return this.sendAction(action, btn);

        /* Tema SweetAlert taşıyorsa onu, yoksa tarayıcının kendi
           onayını kullan; hiçbir işlem sessizce çalışmasın. */
        if (window.Swal && typeof window.Swal.fire === 'function') {
            window.Swal.fire({
                icon: 'warning',
                html: action.confirm,
                showCancelButton: true
            }).then(function (result) {
                if (result && result.isConfirmed) self.sendAction(action, btn);
            });

            return;
        }

        if (window.confirm(action.confirm)) this.sendAction(action, btn);
    };

    VnlTable.prototype.sendAction = function (action, btn) {
        var self  = this;
        var label = btn.textContent;

        btn.disabled = true;
        btn.textContent = this.lang.loading || label;

        this.post(this.config.url, this.payload('bulk', {
            action:    action.name,
            selection: {
                mode:   this.selection.mode,
                ids:    Object.keys(this.selection.ids),
                except: Object.keys(this.selection.except)
            }
        }))
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (!data || data.done !== true) throw new Error('reddedildi');

                if (data.message && typeof showMessage === 'function') showMessage(data.message, false);

                if (data.reload !== false) {
                    self.clearSelection();
                    self.load();
                }
            })
            .catch(function (err) {
                console.error('[VNL Table] ' + self.config.id + ' bulk:', err);

                if (typeof showModalMessage === 'function') {
                    showModalMessage(self.lang.bulkFailed || '', true);
                }
            })
            .then(function () {
                btn.disabled = false;
                btn.textContent = label;
            });
    };

    /* ---------------------------------------------------------------- */
    /* Çapraz tablo (workbench)                                          */
    /* ---------------------------------------------------------------- */

    VnlTable.prototype.togglePivot = function () {
        this.state.pivot.on = !this.state.pivot.on;

        /* İlk açılışta eksenler boşsa makul bir başlangıç seçilir:
           boş bir matris kullanıcıya hiçbir şey anlatmıyordu. */
        if (this.state.pivot.on) this.guessAxes();

        this.state.page = 1;
        this.closeFilter();
        this.rebuild();
        this.load();
    };

    /** Eksen verilmediyse ilk iki kırılabilir sütun ve ilk sayısal alan. */
    VnlTable.prototype.guessAxes = function () {
        var pivot = this.state.pivot;
        var keys  = this.axisColumns();

        if (!pivot.rows && keys[0]) pivot.rows = keys[0].name;
        if (!pivot.cols) {
            var other = keys.filter(function (c) { return c.name !== pivot.rows; })[0];
            if (other) pivot.cols = other.name;
        }

        if (!pivot.col) {
            var measure = this.measureColumns()[0];
            if (measure) pivot.col = measure.name;
            else pivot.fn = 'count';
        }
    };

    /** Kırılım ekseni olabilecek sütunlar. */
    VnlTable.prototype.axisColumns = function () {
        return this.columns.filter(function (col) {
            return !col.system && col.type !== 'number';
        });
    };

    /**
     * Ölçülebilecek sütunlar.
     *
     * agg tanımlı olanlar başa alınır: geliştirici o sütun için zaten
     * "bunun toplamı anlamlıdır" demiş. Aksi halde kendiliğinden seçim
     * ilk sayısal sütuna, çoğu tabloda da kimliğe düşüyordu — kimlikleri
     * toplamak hiçbir şey anlatmaz.
     */
    VnlTable.prototype.measureColumns = function () {
        var numeric = this.columns.filter(function (col) {
            return !col.system && col.type === 'number';
        });

        /* Array.sort kararlı: küme içinde tanım sırası korunur. */
        return numeric.sort(function (a, b) {
            return (b.agg ? 1 : 0) - (a.agg ? 1 : 0);
        });
    };

    VnlTable.prototype.buildPivotBar = function () {
        var self = this;
        var bar  = el('div', 'vnl-pivot-bar');

        function select(label, options, value, onChange) {
            var wrap = el('label', 'vnl-pivot-field');
            wrap.appendChild(el('span', 'vnl-pivot-label', label));

            var node = el('select', 'form-select form-select-sm');
            options.forEach(function (option) {
                var o = el('option', null, option.title);
                o.value = option.name;
                if (option.name === value) o.selected = true;
                node.appendChild(o);
            });

            node.addEventListener('change', function () { onChange(node.value); });
            wrap.appendChild(node);
            bar.appendChild(wrap);

            return node;
        }

        var axes = this.axisColumns().map(function (col) {
            return { name: col.name, title: col.title };
        });

        select(this.lang.pivotRows || '', axes, this.state.pivot.rows, function (value) {
            self.state.pivot.rows = value;
            self.load();
        });

        select(this.lang.pivotCols || '', axes, this.state.pivot.cols, function (value) {
            self.state.pivot.cols = value;
            self.load();
        });

        /* Ölçü boş bırakılabilir: o zaman ölçülen şey kayıt SAYISIDIR. */
        var measures = [{ name: '', title: this.lang.pivotCount || '' }].concat(
            this.measureColumns().map(function (col) {
                return { name: col.name, title: col.title };
            })
        );

        var fnNode;

        select(this.lang.pivotValue || '', measures, this.state.pivot.col, function (value) {
            self.state.pivot.col = value;

            /* Ölçü yokken tek anlamlı işlev saymaktır. */
            if (!value) {
                self.state.pivot.fn = 'count';
                if (fnNode) { fnNode.value = 'count'; fnNode.disabled = true; }
            } else if (fnNode) {
                fnNode.disabled = false;
            }

            self.load();
        });

        var fns = ['sum', 'avg', 'min', 'max', 'count'].map(function (fn) {
            return { name: fn, title: (self.lang.aggs || {})[fn] || fn };
        });

        fnNode = select(this.lang.pivotFn || '', fns, this.state.pivot.fn, function (value) {
            self.state.pivot.fn = value;
            self.load();
        });

        fnNode.disabled = !this.state.pivot.col;

        this.pivotBar = bar;
        this.root.appendChild(bar);
    };

    VnlTable.prototype.loadPivot = function () {
        var self = this;

        this.post(this.config.url, this.payload('pivot', { pivot: this.state.pivot }))
            .then(function (r) { return r.json(); })
            .then(function (data) {
                self.state.loading = false;
                self.setBusy(false);
                self.renderPivot(data);
            })
            .catch(function (err) { self.failed(err, 'pivot'); });
    };

    /**
     * Matrisi basar.
     *
     * Tablonun gövdesi tamamen değiştirilir: pivotta sütunlar tanımdan
     * değil VERİDEN gelir, sıralama/filtre/seçim makinesinin tutunacağı
     * bir sütun kalmaz. Pivot kapatıldığında rebuild() her şeyi geri kurar.
     */
    VnlTable.prototype.renderPivot = function (data) {
        var self    = this;
        var cols    = data.cols || [];
        var rows    = data.rows || [];
        var totals  = data.totals || {};
        var rowsCol = this.column(this.state.pivot.rows);

        var thead = el('thead');
        var head  = el('tr');

        var corner = el('th', 'vnl-th vnl-pivot-corner');
        corner.appendChild(el('span', 'vnl-th-label', rowsCol ? rowsCol.title : ''));
        head.appendChild(corner);

        cols.forEach(function (name) {
            head.appendChild(el('th', 'vnl-th vnl-align-right', name));
        });

        head.appendChild(el('th', 'vnl-th vnl-align-right vnl-pivot-sum', this.lang.pivotTotal || ''));
        thead.appendChild(head);

        var tbody = el('tbody');

        if (!rows.length) {
            var empty = el('tr');
            var cell  = el('td', 'vnl-table-message', this.lang.empty || '');
            cell.colSpan = cols.length + 2;
            empty.appendChild(cell);
            tbody.appendChild(empty);
        }

        rows.forEach(function (row) {
            var tr = el('tr');
            tr.appendChild(el('td', 'vnl-pivot-key', row.k === '' ? (self.lang.groupEmpty || '') : row.k));

            cols.forEach(function (name) {
                var value = row.cells ? row.cells[name] : null;

                /* Boş hücre sıfır değildir: o kırılımda hiç kayıt yok. */
                tr.appendChild(el('td', 'vnl-align-right', (value === null || value === undefined) ? '' : asText(value)));
            });

            tr.appendChild(el('td', 'vnl-align-right vnl-pivot-sum', asText(row.total)));
            tbody.appendChild(tr);
        });

        var tfoot = el('tfoot');
        var foot  = el('tr');
        foot.appendChild(el('th', 'vnl-tf vnl-tf-label', this.lang.pivotTotal || ''));

        cols.forEach(function (name) {
            var value = totals[name];
            foot.appendChild(el('th', 'vnl-tf vnl-align-right', (value === null || value === undefined) ? '' : asText(value)));
        });

        foot.appendChild(el('th', 'vnl-tf vnl-align-right vnl-pivot-sum', asText(data.grand)));
        tfoot.appendChild(foot);

        this.table.innerHTML = '';
        this.table.appendChild(thead);
        this.table.appendChild(tbody);
        this.table.appendChild(tfoot);
        this.tbody = tbody;

        /* Taban genişlik veri sütunlarının bildirdiklerinden hesaplanmıştı;
           pivotta sütunlar bambaşka olduğu için matrisi boşuna geriyor ve
           son sütunu kabın dışına itiyordu. */
        this.table.style.minWidth = '';

        this.info.textContent = fill(this.lang.pivotInfo, { rows: rows.length, cols: cols.length });
        this.pager.innerHTML = '';
    };

    /* ---------------------------------------------------------------- */
    /* URL senkronu                                                      */
    /* ---------------------------------------------------------------- */

    /**
     * Adres çubuğu anahtarları tablo kimliğiyle öneklenir: bir sayfada
     * birden çok tablo varsa durumları karışmasın.
     *
     *   ?kayitlar.q=ankara&kayitlar.s=tutar:desc&kayitlar.p=2
     *   &kayitlar.f={"sehir":{"kind":"text","set":["Ankara"]}}
     *
     * Filtreler yapısal olduğu için JSON'la taşınır — uzun ama okunabilir
     * ve belirsizliği yok. Sıralama ise "sütun:yön" listesiyle kısalır.
     */
    VnlTable.prototype.urlKey = function (suffix) {
        return this.config.id + '.' + suffix;
    };

    VnlTable.prototype.readUrl = function () {
        if (!this.opt.urlSync || typeof URLSearchParams === 'undefined') return;

        var params = new URLSearchParams(location.search);
        var self   = this;

        var search = params.get(this.urlKey('q'));
        if (search !== null) this.state.search = search;

        var page = parseInt(params.get(this.urlKey('p')), 10);
        if (page > 0) this.state.page = page;

        /* Sayfa boyu yalnız listedeki değerlerden biri olabilir: adrese
           elle yazılmış 37 açılır kutuda karşılığı olmadığı için seçimi
           boş bırakırdı. */
        var size = parseInt(params.get(this.urlKey('n')), 10);
        if ((this.opt.pageSizes || []).indexOf(size) !== -1) this.state.size = size;

        var sort = params.get(this.urlKey('s'));
        if (sort !== null) {
            this.state.sort = sort === '' ? [] : sort.split(',').map(function (part) {
                var bits = part.split(':');

                return { col: bits[0], dir: bits[1] === 'desc' ? 'desc' : 'asc' };
            }).filter(function (s) { return !!s.col; });
        }

        var groupBy = params.get(this.urlKey('g'));
        if (groupBy !== null && this.opt.group) {
            this.state.groupBy = groupBy === '' ? [] : groupBy.split(',');
        }

        var filters = params.get(this.urlKey('f'));
        if (filters) {
            try {
                var parsed = JSON.parse(filters);
                /* Adres çubuğu elle düzenlenebilir; bozuk gövde tabloyu
                   çökertmesin, sunucu da zaten kendi doğrulamasını yapar. */
                if (parsed && typeof parsed === 'object') this.state.filters = parsed;
            } catch (e) {
                console.warn('[VNL Table] ' + self.config.id + ': adresteki filtre okunamadı.');
            }
        }
    };

    VnlTable.prototype.writeUrl = function () {
        if (!this.opt.urlSync || typeof URLSearchParams === 'undefined' || !window.history) return;

        var self   = this;
        var params = new URLSearchParams(location.search);
        var prefix = this.config.id + '.';

        /* Önce bu tablonun bıraktığı her şey silinir: kalkan bir filtre
           adreste asılı kalmasın. */
        var mine = [];
        params.forEach(function (value, key) {
            if (key.indexOf(prefix) === 0) mine.push(key);
        });
        mine.forEach(function (key) { params.delete(key); });

        var s = this.state;

        if (s.search) params.set(this.urlKey('q'), s.search);
        if (s.page > 1) params.set(this.urlKey('p'), s.page);
        if (s.size !== (this.opt.pageSize || 25)) params.set(this.urlKey('n'), s.size);

        if (!sameSort(s.sort, this.opt.order || [])) {
            params.set(this.urlKey('s'), s.sort.map(function (x) {
                return x.col + ':' + x.dir;
            }).join(','));
        }

        if (Object.keys(s.filters).length) {
            params.set(this.urlKey('f'), JSON.stringify(s.filters));
        }

        if (s.groupBy.join(',') !== ((this.opt.group && this.opt.group.by) || []).join(',')) {
            params.set(this.urlKey('g'), s.groupBy.join(','));
        }

        var query = params.toString();

        /* replaceState: arama kutusuna her yazışta geçmişe bir kayıt
           eklemek geri tuşunu kullanılmaz hâle getiriyordu. */
        history.replaceState(history.state, '', location.pathname + (query ? '?' + query : '') + location.hash);

        function sameSort(a, b) {
            if (a.length !== b.length) return false;

            return a.every(function (x, i) { return x.col === b[i].col && x.dir === b[i].dir; });
        }
    };

    /**
     * Ortak POST: tablonun tüm istekleri aynı başlıklar ve gövdeyle gider.
     *
     * 3.4.0: X-CSRF-Token (vnl.js → vnlCsrfToken) eklenir; sunucu CSRF'siz
     * POST'u 403 ile reddeder. Hata yanıtları (401/403/429/500 …) vnl.js'in
     * genel kancasına verilir: fetch jQuery'nin ajaxComplete'inden geçmez,
     * aksi halde yetkisiz kullanıcı yalnız "yüklenemedi" görürdü.
     */
    VnlTable.prototype.post = function (url, body) {
        return fetch(url, {
            method:      'POST',
            headers:     {
                'Content-Type':     'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-Token':     (typeof window.vnlCsrfToken === 'function' ? window.vnlCsrfToken() : '')
            },
            body:        JSON.stringify(body),
            credentials: 'same-origin'
        }).then(function (r) {
            if (!r.ok) {
                var status = r.status;
                return r.text().then(function (text) {
                    var json = null;
                    try { json = JSON.parse(text); } catch (e) { json = null; }
                    if (typeof window.vnlHandleAjaxError === 'function') {
                        window.vnlHandleAjaxError(status, json, {});
                    }
                    throw new Error('HTTP ' + status);
                });
            }
            return r;
        });
    };

    /** Yükleme durumu: satırlar yerinde kalır, bulanıklaşır ve üstüne katman gelir. */
    VnlTable.prototype.setBusy = function (busy) {
        this.root.classList.toggle('vnl-is-loading', !!busy);
        this.overlay.setAttribute('aria-hidden', busy ? 'false' : 'true');
        this.table.setAttribute('aria-busy', busy ? 'true' : 'false');
    };

    /** $silent: adres çubuğuna dokunma — durum zaten oradan geldi. */
    VnlTable.prototype.load = function (silent) {
        var self = this;
        this.state.loading = true;
        this.setBusy(true);

        if (!silent) this.writeUrl();

        /* Filtre ya da arama değiştiyse seçim düşer: "filtreye uyan hepsi"
           artık başka bir kümeyi kastediyor olurdu. Sayfalama ve sıralama
           seçimi bozmaz. */
        var signature = JSON.stringify([this.state.filters, this.state.search]);

        if (signature !== this.selectionKey) {
            this.selectionKey = signature;

            if (this.selection.mode === 'all' || Object.keys(this.selection.ids).length) {
                this.clearSelection();
            }
        }

        /* Çapraz tablo bambaşka bir yanıt biçimi: satır listesi değil
           matris. Gruplama, seçim ve sıralama orada geçerli değil. */
        if (this.state.pivot.on) {
            this.loadPivot();

            return;
        }

        /* Gruplu görünümde satırlar doğrudan değil, grup listesi üzerinden
           gelir; akış tamamen ayrı. */
        if (this.state.groupBy.length) {
            this.loadGroups();

            return;
        }

        /* İlk yüklemede gösterilecek satır yok; katmanın kaplayacağı bir
           yükseklik olsun diye mesaj satırı basılır. Sonraki yüklemelerde
           mevcut satırlar korunur — aksi halde tek geniş hücre sütun
           genişliklerini çökertip tabloyu zıplatıyordu. */
        if (!this.state.rows.length) {
            this.renderMessage('');
        }

        /* fetch kullanılıyor: jQuery'nin global ajaxComplete akışına girmesin,
           tablo kendi hata durumunu kendi gösterebilsin. */
        this.post(this.config.url, this.payload('list'))
            .then(function (r) { return r.json(); })
            .then(function (data) {
                self.state.loading = false;
                self.setBusy(false);
                self.state.rows     = data.rows || [];
                self.state.total    = data.total || 0;
                self.state.filtered = (data.filtered !== undefined) ? data.filtered : self.state.total;
                self.renderRows();
                self.lastTotals = data.totals || null;
                self.renderTotals(self.lastTotals);
                self.renderFooter();
            })
            .catch(function (err) { self.failed(err, ''); });
    };

    VnlTable.prototype.failed = function (err, what) {
        this.state.loading = false;
        this.setBusy(false);
        this.state.rows = [];
        this.nodes = {};
        this.allRows = null;

        console.error('[VNL Table] ' + this.config.id + (what ? ' ' + what : '') + ':', err);

        this.renderMessage(this.lang.error || '', true);
        this.renderTotals(null);
        this.pager.innerHTML = '';
        this.info.textContent = '';
    };

    VnlTable.prototype.renderMessage = function (text, isError) {
        this.tbody.innerHTML = '';
        var tr = el('tr');
        var td = el('td', 'vnl-table-message' + (isError ? ' vnl-table-error' : ''), text);
        td.colSpan = this.visibleCount();
        tr.appendChild(td);
        this.tbody.appendChild(tr);
    };

    VnlTable.prototype.renderRows = function () {
        var self = this;

        if (!this.state.rows.length) {
            this.renderMessage(this.lang.empty || '');
            return;
        }

        var frag = document.createDocumentFragment();

        this.state.rows.forEach(function (row) { self.appendRow(frag, row, 0); });

        this.tbody.innerHTML = '';
        this.tbody.appendChild(frag);
        this.layoutPins();
        this.paintSelection();
    };

    /** Tek veri satırı; hem düz listede hem grup içinde kullanılır. */
    VnlTable.prototype.buildRow = function (row) {
        var self = this;
        var tr   = el('tr');

        if (this.config.key && row[this.config.key] !== undefined) {
            tr.dataset.key = row[this.config.key];
        }

        this.columns.forEach(function (col) {
            if (self.hiddenCols[col.name]) return;

            var td = el('td', 'vnl-align-' + col.align);
            pinClass(td, col);

            if (col.system) {
                td.classList.add('vnl-sys');
                td.appendChild(col.name === '__select'
                    ? self.buildRowBox(row)
                    : self.buildDetailToggle(row, tr));
                tr.appendChild(td);

                return;
            }

            paintCell(td, col, row);

            /* Düzenlenebilir hücre satır kimliğini kendi üstünde taşır:
               satır yeniden çizilse de kaydetme isteği doğru kaydı bulur. */
            if (col.editable && self.rowKey(row) !== null) {
                td.classList.add('vnl-editable');
                td.title = self.lang.editHint || '';
                td.addEventListener('dblclick', function () { self.editCell(td, col, row); });
            }

            tr.appendChild(td);
        });

        return tr;
    };

    /** Ada gore sutun tanimi. */
    VnlTable.prototype.column = function (name) {
        var hit = this.columns.filter(function (c) { return c.name === name; });

        return hit.length ? hit[0] : null;
    };

    VnlTable.prototype.rowKey = function (row) {
        var key = this.config.key;

        return (key && row[key] !== undefined && row[key] !== null) ? String(row[key]) : null;
    };

    /* ---------------------------------------------------------------- */
    /* Hücre düzenleme (workbench)                                       */
    /* ---------------------------------------------------------------- */

    /**
     * Hücreyi düzenlemeye açar.
     *
     * Tek hücre, tek istek — toplu "kirli satır" tutmuyoruz. Yarım kalmış
     * bir düzenleme sayfa yenilenince sessizce kaybolmasın diye: yazılan
     * ya kaydedilir ya da eski değere döner.
     */
    VnlTable.prototype.editCell = function (td, col, row) {
        if (td.classList.contains('vnl-editing')) return;

        var self     = this;
        var original = asText(row[col.name]);
        var input    = col.editOptions ? el('select', 'form-select form-select-sm') : el('input', 'form-control form-control-sm');

        if (col.editOptions) {
            col.editOptions.forEach(function (option) {
                var node = el('option', null, option.l);
                node.value = option.v;
                if (option.v === original) node.selected = true;
                input.appendChild(node);
            });
        } else {
            input.type = col.type === 'number' ? 'number' : (col.type === 'date' ? 'date' : 'text');
            input.value = original;
        }

        td.classList.add('vnl-editing');
        td.textContent = '';
        td.appendChild(input);
        input.focus();
        if (input.select) input.select();

        var settled = false;

        function done(value) {
            if (settled) return;
            settled = true;

            td.classList.remove('vnl-editing');
            paintValue(td, col, row, value);
        }

        function commit() {
            if (settled) return;

            var value = input.value;

            if (value === original) {
                done(original);

                return;
            }

            done(value);
            self.saveCell(td, col, row, value, original);
        }

        input.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                commit();
            } else if (e.key === 'Escape') {
                e.preventDefault();
                done(original);
            }
        });

        /* Select'te seçim yapmak blur üretmiyor; change ile de bağlanır. */
        input.addEventListener('blur', commit);
        if (col.editOptions) input.addEventListener('change', commit);
    };

    VnlTable.prototype.saveCell = function (td, col, row, value, original) {
        var self = this;

        td.classList.add('vnl-saving');

        this.post(this.config.saveUrl || this.config.url, this.payload('save', {
            key:   this.rowKey(row),
            col:   col.name,
            value: value
        }))
            .then(function (r) { return r.json(); })
            .then(function (data) {
                td.classList.remove('vnl-saving');

                if (!data || data.saved !== true) throw new Error('reddedildi');

                /* Sunucu değeri normalleştirmiş olabilir: kullanıcı yazdığını
                   değil kaydedileni görsün. */
                var saved = (data.value === null || data.value === undefined) ? value : asText(data.value);

                row[col.name] = saved;
                paintCell(td, col, row);

                td.classList.add('vnl-saved');
                setTimeout(function () { td.classList.remove('vnl-saved'); }, 900);
            })
            .catch(function (err) {
                td.classList.remove('vnl-saving');
                paintCell(td, col, row);
                td.classList.add('vnl-save-failed');
                setTimeout(function () { td.classList.remove('vnl-save-failed'); }, 1500);

                console.error('[VNL Table] ' + self.config.id + ' save:', err);

                if (typeof showModalMessage === 'function') {
                    showModalMessage(self.lang.editFailed || '', true);
                }
            });
    };

    /* ---------------------------------------------------------------- */
    /* Sütun sabitleme                                                   */
    /* ---------------------------------------------------------------- */

    /**
     * Sabit sütunların kenara uzaklığı içerikle birlikte değişir, o yüzden
     * her çizimden sonra ölçülür. Kenardaki sütun ayrıca işaretlenir:
     * gölge, kaydırılan alanla arasındaki dikişi gösterir.
     */
    VnlTable.prototype.layoutPins = function () {
        var self = this;
        if (!this.pinned || this.state.pivot.on) return;

        var visible = this.columns.filter(function (c) { return !self.hiddenCols[c.name]; });
        var offsets = {};

        var lefts  = visible.filter(function (c) { return c.pin === 'left'; });
        var rights = visible.filter(function (c) { return c.pin === 'right'; });

        var left = 0;
        lefts.forEach(function (col) {
            offsets[col.name] = ['left', left];
            left += self.ths[col.name].getBoundingClientRect().width;
        });

        var right = 0;
        rights.slice().reverse().forEach(function (col) {
            offsets[col.name] = ['right', right];
            right += self.ths[col.name].getBoundingClientRect().width;
        });

        /* Dikiş gölgesi yalnız kenardaki sütuna. Hangi sütunun kenarda
           kaldığı gizle/göster ile değiştiği için her ölçümde yenilenir. */
        var edge = {};
        if (lefts.length)  edge[lefts[lefts.length - 1].name] = true;
        if (rights.length) edge[rights[0].name] = true;

        visible.forEach(function (col, i) {
            var offset = offsets[col.name];
            if (!offset) return;

            var value = Math.round(offset[1]) + 'px';

            apply(self.ths[col.name]);
            if (self.tfootCells) apply(self.tfootCells[col.name]);

            /* Gövde hücreleri gizli sütunları hiç basmıyor: sıra görünür
               sütun sırasıyla birebir. */
            Array.prototype.forEach.call(self.tbody.rows, function (tr) {
                if (tr.cells.length === visible.length) apply(tr.cells[i]);
            });

            function apply(cell) {
                if (!cell) return;

                cell.style[offset[0]] = value;
                cell.classList.toggle('vnl-pin-edge', !!edge[col.name]);
            }
        });
    };

    /**
     * Sütunların bildirdiği genişliklerin toplamı.
     *
     * width vermeyen sütun için taban yoktur; hiçbiri vermemişse null döner
     * ve tablo eski davranışında kalır. Yalnız px kabul edilir: yüzde ya da
     * ch toplanamaz, toplanmaya çalışılırsa yanlış bir taban çıkar.
     */
    VnlTable.prototype.declaredWidth = function () {
        var self  = this;
        var total = 0;
        var any   = false;

        this.columns.forEach(function (col) {
            if (self.hiddenCols[col.name]) return;

            var px = /^(\d+(?:\.\d+)?)px$/.exec(String(col.width || ''));
            if (!px) return;

            total += parseFloat(px[1]);

            /* Sistem sütunları her zaman genişlik bildirir; tabanın
               gerekli olup olmadığına gerçek sütunlar karar verir. */
            if (!col.system) any = true;
        });

        return any ? total + 'px' : null;
    };

    /** Tanımdan gelen başlangıç eksenleri; on false ise düğme var, görünüm kapalı. */
    function pivotState(spec) {
        var axes = (spec && typeof spec === 'object') ? spec : {};

        return {
            on:   !!axes.on,
            rows: axes.rows || '',
            cols: axes.cols || '',
            col:  axes.col || '',
            fn:   axes.fn || 'sum'
        };
    }

    /**
     * Seçim ve detay sütunları.
     *
     * Sola sabitlenirler: yatay kaydırmada satırı seçme ve açma yetisi
     * ekrandan çıkmamalı.
     */
    function systemColumn(name) {
        return {
            name: name,
            title: '',
            type: 'text',
            render: 'text',
            sortable: false,
            visible: true,
            hideable: false,
            width: '2.75rem',
            align: 'center',
            filter: null,
            agg: null,
            pin: 'left',
            groupable: false,
            editable: false,
            editOptions: null,
            system: true
        };
    }

    /** Sabit sütun sınıfı. Kenar işareti layoutPins'te verilir: hangi
        sütunun kenarda kaldığı gizle/göster ile değişiyor. */
    function pinClass(cell, col) {
        if (col.pin) cell.classList.add('vnl-pin-' + col.pin);
    }

    /**
     * Alt toplamları basar.
     *
     * Sunucu totals göndermediyse hücreler boşaltılır — eski bir toplamın
     * yeni filtrenin altında durması yanlış bilgi olurdu.
     */
    VnlTable.prototype.renderTotals = function (totals) {
        var self = this;
        if (!this.totalCells) return;

        this.aggCols.forEach(function (col) {
            var cell = self.totalCells[col.name];
            if (!cell) return;

            var value = totals ? totals[col.name] : null;
            cell.textContent = (value === null || value === undefined) ? '' : asText(value);
        });
    };

    VnlTable.prototype.renderFooter = function () {
        var s = this.state;

        /* Gruplu görünümde sayfalanan birim gruptur, satır değil: bir
           grubun satırları kendi içinde açılıp yükleniyor. */
        if (s.groupBy.length) {
            /* Sayfalanan birim ust seviyedeki gruplar; alt seviyeler ve
               satirlar kendi icinde aciliyor. */
            var top = this.groupsAt([]) || [];

            this.info.textContent = top.length
                ? fill(this.lang.infoGroups, { groups: top.length, total: s.filtered })
                : (this.lang.infoEmpty || '');

            this.renderPager(Math.max(1, Math.ceil(top.length / s.size)));

            return;
        }

        var start = s.filtered ? (s.page - 1) * s.size + 1 : 0;
        var end   = Math.min(s.page * s.size, s.filtered);

        if (!s.filtered) {
            this.info.textContent = this.lang.infoEmpty || '';
        } else {
            var text = fill(this.lang.info, { total: s.filtered, start: start, end: end });
            if (s.filtered !== s.total) {
                text += ' ' + fill(this.lang.infoFiltered, { max: s.total });
            }
            this.info.textContent = text;
        }

        this.renderPager(Math.max(1, Math.ceil(s.filtered / s.size)));
    };

    VnlTable.prototype.renderPager = function (pages) {
        var self = this;

        /* Düğmeler her yüklemede yeniden kurulur; eskilerin tooltip
           örnekleri önce serbest bırakılmalı. */
        disposeTooltips(this.pager);
        this.pager.innerHTML = '';

        function button(label, page, disabled, active) {
            var b = el('button', 'btn btn-sm ' + (active ? 'btn-primary' : 'btn-outline-secondary'), label);
            b.type = 'button';
            if (disabled) b.disabled = true;
            else b.addEventListener('click', function () { self.state.page = page; self.load(); });
            return b;
        }

        /* Ok tuşları ikonla; metin tooltip ve ekran okuyucu etiketi olarak kalır. */
        function arrow(iconName, label, page, disabled) {
            var b = button('', page, disabled);
            b.classList.add('vnl-icon-btn');
            b.appendChild(icon(iconName));
            b.title = label;
            b.setAttribute('aria-label', label);
            b.setAttribute('data-bs-toggle', 'tooltip');
            return b;
        }

        var page = this.state.page;
        this.pager.appendChild(arrow('first', this.lang.first || '', 1, page <= 1));
        this.pager.appendChild(arrow('prev', this.lang.prev || '', page - 1, page <= 1));

        /* Geçerli sayfanın çevresinde en fazla 5 numara gösterilir. */
        var from = Math.max(1, page - 2);
        var to   = Math.min(pages, from + 4);
        from = Math.max(1, to - 4);

        for (var i = from; i <= to; i++) {
            this.pager.appendChild(button(i, i, false, i === page));
        }

        this.pager.appendChild(arrow('next', this.lang.next || '', page + 1, page >= pages));
        this.pager.appendChild(arrow('last', this.lang.last || '', pages, page >= pages));

        initTooltips(this.pager);
    };

    VnlTable.prototype.exportExcel = function (button) {
        var self = this;
        var label = button.textContent;
        var fileName = self.config.id + '.xlsx';
        button.disabled = true;
        button.textContent = this.lang.loading || label;

        /* exportUrl verilmişse istek oraya gider: rapor şablonu doldurmak
           gibi durumlarda geliştirici çıktıyı kendi sayfasında üretir. */
        this.post(this.config.exportUrl || this.config.url, this.payload('export'))
            .then(function (r) {
                /* Dosya adını sunucu belirler; özel export sayfaları kendi
                   adlarını verebilsin diye Content-Disposition okunur. */
                var cd = r.headers.get('Content-Disposition') || '';
                var m = /filename\*?=(?:UTF-8'')?"?([^";]+)"?/i.exec(cd);
                fileName = m ? decodeURIComponent(m[1]) : self.config.id + '.xlsx';
                return r.blob();
            })
            .then(function (blob) {
                var url = URL.createObjectURL(blob);
                var a = document.createElement('a');
                a.href = url;
                a.download = fileName;
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                URL.revokeObjectURL(url);
            })
            .catch(function (err) {
                console.error('[VNL Table] ' + self.config.id + ' export:', err);
                if (typeof showModalMessage === 'function') showModalMessage(self.lang.error || '', true);
            })
            .then(function () {
                button.disabled = false;
                button.textContent = label;
            });
    };

    /* ---------------------------------------------------------------- */
    /* Başlatma                                                          */
    /* ---------------------------------------------------------------- */

    function init(scope) {
        var roots = (scope || document).querySelectorAll('.vnl-table[data-vnl-table]');

        Array.prototype.forEach.call(roots, function (root) {
            if (root.dataset.vnlTableReady) return;

            var node = root.querySelector('script.vnl-table-config');
            if (!node) return;

            var config;
            try {
                config = JSON.parse(node.textContent);
            } catch (e) {
                console.error('[VNL Table] tanım okunamadı:', e);
                return;
            }

            root.dataset.vnlTableReady = '1';
            instances[config.id] = new VnlTable(root, config);
        });
    }

    /** Dışarıdan erişim: vnlTable('users').reload() */
    window.vnlTable = function (id) {
        return instances[id];
    };
    window.vnlTable.init = init;

    /**
     * Adlandırılmış hücre biçimlendiricisi kaydeder.
     *
     *   vnlTable.formatter('durum', function (value, row, col) {
     *       return value === 'Onaylandı' ? '✔ ' + value : value;   // metin
     *   });
     *
     * Dönüş metinse metin olarak basılır, DOM düğümüyse hücreye eklenir.
     * HTML METNİ DÖNDÜRÜLEMEZ — kasıtlı: markup üretecek biçimlendirici
     * düğümü kendisi kursun, kaçış sorusu hiç doğmasın. Gerçekten ham HTML
     * gerekiyorsa sütun zaten render:'html' diyebilir.
     *
     * Kayıt tabloyu kurmadan önce yapılmalıdır; sonradan kaydedilen bir
     * biçimlendirici vnlTable('id').reload() ile devreye girer.
     */
    window.vnlTable.formatter = function (name, fn) {
        if (typeof fn !== 'function') {
            console.error('[VNL Table] formatter("' + name + '") bir fonksiyon ister.');

            return;
        }

        formatters[String(name)] = fn;
    };

    VnlTable.prototype.reload = function () { this.load(); };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () { init(); });
    } else {
        init();
    }
})();

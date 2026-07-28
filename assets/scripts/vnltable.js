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
            filterOn: 'bi bi-funnel-fill'
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
            filterOn: 'fa-solid fa-filter'
        },
        emoji: {
            first: '«', prev: '‹', next: '›', last: '»',
            columns: '▦', clear: '✕', excel: '▤', search: '⌕',
            filter: '▽', filterOn: '▼'
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
    /* Tablo                                                             */
    /* ---------------------------------------------------------------- */

    function VnlTable(root, config) {
        this.root    = root;
        this.config  = config;
        this.columns = config.columns.filter(function (c) { return c.visible; });
        this.lang    = config.lang || {};
        this.opt     = config.options || {};

        /* Kullanıcının gizlediği sütunlar (name → true); tercih tarayıcıda
           tablo kimliğiyle saklanır. visible:false'tan farkı: bunlar tanımda
           durur ve sütun menüsünden geri açılabilir. */
        this.hiddenCols = this.restoreHidden();
        this.ths = {};
        this.filterBtns = {};

        /* Açık filtre paneli. Panel body'ye takılır — tablo overflow:hidden
           taşıdığı için içeride kalsaydı kırpılırdı. */
        this.panel = null;

        this.state = {
            page:    1,
            size:    this.opt.pageSize || 25,
            sort:    (this.opt.order || []).slice(),
            search:  '',
            filters: {},
            total:   0,
            filtered: 0,
            rows:    [],
            loading: false
        };

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

        /* ---- Aktif filtre çipleri ----
           Hangi sütunun filtreli olduğunu görmek için başlıkları tek tek
           gezmek gerekiyordu; çipler bunu tek bakışa indirir. Filtre
           yokken satır hiç çizilmez, yer kaplamasın. */
        if (this.opt.chips) {
            this.chips = el('div', 'vnl-table-chips');
            this.chips.hidden = true;
            this.root.appendChild(this.chips);
        }

        /* ---- Tablo ----
           Kaydırma alanı ayrı bir kapsayıcıya alınır: yükleme katmanı
           kaydırmayla birlikte hareket etmesin, görünen alanda sabit kalsın. */
        var body = el('div', 'vnl-table-body');

        var wrap = el('div', 'vnl-table-scroll');
        if (this.opt.height) wrap.style.maxHeight = this.opt.height;

        var table = el('table', 'vnl-table-el');
        var thead = el('thead');
        var headRow = el('tr');

        this.columns.forEach(function (col) {
            var th = el('th', 'vnl-th vnl-align-' + col.align);
            if (col.width) th.style.width = col.width;

            /* Başlık içeriği bir flex satırı: huni, hizalama ne olursa olsun
               etiketin peşinde kalsın. th'nin kendisi flex yapılamaz —
               tablo sütun genişliği hesabı bozuluyor. */
            var inner = el('span', 'vnl-th-inner');
            inner.appendChild(el('span', 'vnl-th-label', col.title));

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
                    self.toggleSort(col.name, self.opt.multiSort && e.shiftKey);
                });
            }

            if (col.filter) {
                inner.appendChild(self.buildFilterButton(col));
            }

            th.appendChild(inner);

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
                } else if (!labelled) {
                    /* İlk toplamsız sütun satırı adlandırır — hesap
                       tablolarındaki alışkanlık. */
                    cell.textContent = self.lang.totals || '';
                    cell.classList.add('vnl-tf-label');
                    labelled = true;
                }

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
        if (this.state.rows.length) this.renderRows();
        else this.renderMessage(this.lang.empty || '');
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

        var query = params.toString();

        /* replaceState: arama kutusuna her yazışta geçmişe bir kayıt
           eklemek geri tuşunu kullanılmaz hâle getiriyordu. */
        history.replaceState(history.state, '', location.pathname + (query ? '?' + query : '') + location.hash);

        function sameSort(a, b) {
            if (a.length !== b.length) return false;

            return a.every(function (x, i) { return x.col === b[i].col && x.dir === b[i].dir; });
        }
    };

    /** Ortak POST: tablonun tüm istekleri aynı başlıklar ve gövdeyle gider. */
    VnlTable.prototype.post = function (url, body) {
        return fetch(url, {
            method:      'POST',
            headers:     { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body:        JSON.stringify(body),
            credentials: 'same-origin'
        }).then(function (r) {
            if (!r.ok) throw new Error('HTTP ' + r.status);
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
                self.renderTotals(data.totals);
                self.renderFooter();
            })
            .catch(function (err) {
                self.state.loading = false;
                self.setBusy(false);
                self.state.rows = [];
                console.error('[VNL Table] ' + self.config.id + ':', err);
                self.renderMessage(self.lang.error || '', true);
                self.pager.innerHTML = '';
                self.info.textContent = '';
            });
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

        this.state.rows.forEach(function (row) {
            var tr = el('tr');
            if (self.config.key && row[self.config.key] !== undefined) {
                tr.dataset.key = row[self.config.key];
            }

            self.columns.forEach(function (col) {
                if (self.hiddenCols[col.name]) return;

                var td = el('td', 'vnl-align-' + col.align);

                if (col.render === 'html') {
                    /* Sütun bilinçli olarak html işaretlenmiş: sunucu içeriği
                       üretiyor ve kaçışından kendisi sorumlu. */
                    td.innerHTML = asText(row[col.name]);
                } else {
                    td.textContent = asText(row[col.name]);
                }

                tr.appendChild(td);
            });

            frag.appendChild(tr);
        });

        this.tbody.innerHTML = '';
        this.tbody.appendChild(frag);
    };

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

    VnlTable.prototype.reload = function () { this.load(); };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () { init(); });
    } else {
        init();
    }
})();

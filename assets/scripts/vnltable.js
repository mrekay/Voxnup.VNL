/*!
 * ============================================================
 *  Voxnup Not Laravel (VNL) 3.0.0 — VNL Table
 *  Sunucudan gelen tanımı okur, tabloyu çizer ve yönetir.
 *  Aşama 1: basic — sıralama, sayfalama, düz arama, Excel.
 *  Authors: Voxnup Authors
 * ============================================================
 */
(function () {
    'use strict';

    var instances = {};

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
            search:  'bi bi-search'
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
            search:  'fa-solid fa-magnifying-glass'
        },
        emoji: {
            first: '«', prev: '‹', next: '›', last: '»',
            columns: '▦', clear: '✕', excel: '▤', search: '⌕'
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

        this.state = {
            page:    1,
            size:    this.opt.pageSize || 25,
            sort:    (this.opt.order || []).slice(),
            search:  '',
            total:   0,
            filtered: 0,
            rows:    [],
            loading: false
        };

        this.build();
        this.load();
    }

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

            var label = el('span', 'vnl-th-label', col.title);
            th.appendChild(label);

            if (col.sortable) {
                th.classList.add('vnl-sortable');
                th.appendChild(el('span', 'vnl-sort-mark'));
                th.addEventListener('click', function () { self.toggleSort(col.name); });
            }

            if (self.hiddenCols[col.name]) th.hidden = true;
            self.ths[col.name] = th;
            headRow.appendChild(th);
        });

        thead.appendChild(headRow);
        table.appendChild(thead);

        this.tbody = el('tbody');
        table.appendChild(this.tbody);
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

        this.info = el('div', 'vnl-table-info');
        foot.appendChild(this.info);

        this.pager = el('div', 'vnl-table-pager');
        foot.appendChild(this.pager);

        this.root.appendChild(foot);
        this.table = table;

        this.paintSort();
        initTooltips(this.root);
    };

    VnlTable.prototype.toggleSort = function (name) {
        var current = this.state.sort[0];

        if (!current || current.col !== name) {
            this.state.sort = [{ col: name, dir: 'asc' }];
        } else if (current.dir === 'asc') {
            this.state.sort = [{ col: name, dir: 'desc' }];
        } else {
            this.state.sort = [];
        }

        this.state.page = 1;
        this.paintSort();
        this.load();
    };

    VnlTable.prototype.paintSort = function () {
        var current = this.state.sort[0];
        var ths = this.table.querySelectorAll('thead th');

        this.columns.forEach(function (col, i) {
            var th = ths[i];
            if (!th) return;
            th.classList.remove('vnl-sort-asc', 'vnl-sort-desc');
            if (current && current.col === col.name) {
                th.classList.add(current.dir === 'desc' ? 'vnl-sort-desc' : 'vnl-sort-asc');
            }
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
            var th = self.ths[col.name];
            if (th) th.hidden = !!self.hiddenCols[col.name];
        });

        /* Gövde eldeki veriyle yeniden basılır; sunucuya gidilmez. */
        if (this.state.rows.length) this.renderRows();
        else this.renderMessage(this.lang.empty || '');
    };

    VnlTable.prototype.clear = function () {
        this.state.search = '';
        this.state.sort   = (this.opt.order || []).slice();
        this.state.page   = 1;
        if (this.searchInput) this.searchInput.value = '';
        this.paintSort();
        this.load();
    };

    VnlTable.prototype.payload = function (op) {
        return {
            op:      op || 'list',
            page:    this.state.page,
            size:    this.state.size,
            search:  this.state.search,
            sort:    this.state.sort,
            filters: {},
            context: this.config.context || {}
        };
    };

    /** Yükleme durumu: satırlar yerinde kalır, bulanıklaşır ve üstüne katman gelir. */
    VnlTable.prototype.setBusy = function (busy) {
        this.root.classList.toggle('vnl-is-loading', !!busy);
        this.overlay.setAttribute('aria-hidden', busy ? 'false' : 'true');
        this.table.setAttribute('aria-busy', busy ? 'true' : 'false');
    };

    VnlTable.prototype.load = function () {
        var self = this;
        this.state.loading = true;
        this.setBusy(true);

        /* İlk yüklemede gösterilecek satır yok; katmanın kaplayacağı bir
           yükseklik olsun diye mesaj satırı basılır. Sonraki yüklemelerde
           mevcut satırlar korunur — aksi halde tek geniş hücre sütun
           genişliklerini çökertip tabloyu zıplatıyordu. */
        if (!this.state.rows.length) {
            this.renderMessage('');
        }

        /* fetch kullanılıyor: jQuery'nin global ajaxComplete akışına girmesin,
           tablo kendi hata durumunu kendi gösterebilsin. */
        fetch(this.config.url, {
            method:      'POST',
            headers:     { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body:        JSON.stringify(this.payload('list')),
            credentials: 'same-origin'
        })
            .then(function (r) {
                if (!r.ok) throw new Error('HTTP ' + r.status);
                return r.json();
            })
            .then(function (data) {
                self.state.loading = false;
                self.setBusy(false);
                self.state.rows     = data.rows || [];
                self.state.total    = data.total || 0;
                self.state.filtered = (data.filtered !== undefined) ? data.filtered : self.state.total;
                self.renderRows();
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
        fetch(this.config.exportUrl || this.config.url, {
            method:      'POST',
            headers:     { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body:        JSON.stringify(this.payload('export')),
            credentials: 'same-origin'
        })
            .then(function (r) {
                if (!r.ok) throw new Error('HTTP ' + r.status);
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

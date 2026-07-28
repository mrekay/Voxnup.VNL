<?php
/*!
 * ============================================================
 *  Voxnup Not Laravel (VNL) 3.0.0 — VNL Table
 *  Tablo tanımını üretir; çizimi vnltable.js yapar.
 *  Authors: Voxnup Authors
 * ============================================================
 */

/**
 * VNL Table — sunucu tarafı tanım.
 *
 * SDTable'dan farkı: filtre sütunun bir ÖZELLİĞİDİR, ayrı bir paralel dizi
 * değil. SDTable'da $columns ve $filters konum konum hizalanmak zorundaydı;
 * araya sütun eklendiğinde filtreler sessizce kayıyordu.
 *
 * render() HTML tablo değil, bir bağlama noktası ve JSON tanım basar.
 * Pivot, hücre düzenleme ve accordion sunucuda üretilmiş <thead> ile
 * yapılamayacağı için çizim tamamen istemcidedir.
 *
 *   echo VnlTable::make('users')
 *       ->url('app/users/getList')
 *       ->key('id')
 *       ->columns([
 *           ['name' => 'username', 'title' => 'Kullanıcı Adı'],
 *           ['name' => 'created_at', 'title' => 'Kayıt', 'type' => 'datetime'],
 *       ])
 *       ->render();
 */
class VnlTable
{
    /**
     * Okunan liste: sıralama, sayfalama, düz arama, Excel.
     */
    const LIST = 'list';

    /**
     * Sorgulanan tablo: LIST + sütun filtre panelleri, filtre çipleri,
     * çoklu sıralama, alt toplamlar, URL senkronu.
     */
    const GRID = 'grid';

    /**
     * Üzerinde çalışılan tezgâh: GRID + sütun sabitleme, satır gruplama,
     * detay satırı, seçim ve toplu işlem, hücre düzenleme, pivot.
     */
    const WORKBENCH = 'workbench';

    private string $id;
    private string $url       = '';
    private string $exportUrl = '';
    private string $saveUrl   = '';
    private string $detailUrl = '';
    private array  $actions   = [];
    private string $mode    = self::LIST;
    private string $key     = 'id';
    private array  $columns = [];
    private array  $context = [];
    private array  $options = [];

    private function __construct(string $id)
    {
        $this->id = $id;
    }

    /** $id tablonun HTML kimliğidir; sayfada benzersiz olmalıdır. */
    public static function make(string $id): self
    {
        if (trim($id) === '') {
            throwErrorMessage('VnlTable::make() bir tablo kimliği ister.');
        }

        return new self($id);
    }

    /**
     * Verinin çekileceği action. getList sözleşmesi için VnlTableRequest'e bakın.
     *
     * 'app/kayit/getList' gibi göreli bir yol köke sabitlenir: tablo
     * /docs/vnltable gibi alt bir yolda çizildiğinde tarayıcı aksi halde
     * /docs/app/kayit/getList adresini isterdi.
     */
    public function url(string $url): self
    {
        $url = trim($url);

        if ($url !== '' && !preg_match('~^(https?:)?//~i', $url)) {
            $url = '/' . ltrim($url, '/');
        }

        $this->url = $url;

        return $this;
    }

    public function mode(string $mode): self
    {
        $allowed = [self::LIST, self::GRID, self::WORKBENCH];

        if (!in_array($mode, $allowed, true)) {
            throwErrorMessage('VnlTable: geçersiz mod "' . $mode . '". Beklenen: ' . implode(', ', $allowed));
        }

        $this->mode = $mode;

        return $this;
    }

    /**
     * Excel çıktısını başka bir action'a yönlendirir.
     *
     * Verilmezse çıktı asıl url()'e op="export" ile gider ve
     * VnlTableRequest::exportXlsx() ile üretilir. Şablon doldurmak,
     * birden çok sayfa yazmak ya da rapor biçimlendirmek gibi durumlarda
     * kendi sayfanızı verin; istek aynı gövdeyle (filtre, sıralama, arama,
     * context) oraya düşer.
     *
     *   ->exportUrl('app/rapor/aylikSatis')
     */
    public function exportUrl(string $url): self
    {
        $url = trim($url);

        if ($url !== '' && !preg_match('~^(https?:)?//~i', $url)) {
            $url = '/' . ltrim($url, '/');
        }

        $this->exportUrl = $url;

        return $this;
    }

    /**
     * Hücre düzenlemesini başka bir action'a yönlendirir.
     *
     * Verilmezse kaydetme asıl url()'e op="save" ile gider. Yazma işini
     * okuma action'ından ayırmak istiyorsanız — yetki, kayıt tutma, doğrulama
     * genelde ayrı yerde durur — burayı kullanın.
     */
    public function saveUrl(string $url): self
    {
        $url = trim($url);

        if ($url !== '' && !preg_match('~^(https?:)?//~i', $url)) {
            $url = '/' . ltrim($url, '/');
        }

        $this->saveUrl = $url;

        return $this;
    }

    /**
     * Satır altı detayın geleceği action.
     *
     * Satır açıldığında oraya op="detail" ve satırın kimliği gider; yanıt
     * JSON değil HTML'dir (VnlTableRequest::respondDetail). Verilmezse
     * genişletme sütunu hiç çizilmez.
     *
     *   ->detailUrl('app/kayit/detay')
     */
    public function detailUrl(string $url): self
    {
        $url = trim($url);

        if ($url !== '' && !preg_match('~^(https?:)?//~i', $url)) {
            $url = '/' . ltrim($url, '/');
        }

        $this->detailUrl = $url;

        return $this;
    }

    /**
     * Toplu işlemler — seçim çubuğundaki düğmeler.
     *
     *   ->actions([
     *       ['name' => 'sil',    'title' => 'Sil', 'variant' => 'danger',
     *        'confirm' => 'Seçili kayıtlar silinecek. Emin misiniz?'],
     *       ['name' => 'onayla', 'title' => 'Onayla'],
     *   ])
     *
     * Tıklandığında url()'e op="bulk", action=<name> ve seçim gider.
     * Seçim kutuları yalnız bu liste doluyken (ya da options'ta
     * 'selection' => true iken) çizilir.
     */
    public function actions(array $actions): self
    {
        $this->actions = array_map(function ($action) {
            if (!is_array($action) || empty($action['name'])) {
                throwErrorMessage('VnlTable "' . $this->id . '": her toplu işlem bir "name" alanı içermelidir.');
            }

            return [
                'name'    => (string)$action['name'],
                'title'   => (string)($action['title'] ?? $action['name']),
                'variant' => (string)($action['variant'] ?? 'secondary'),
                'confirm' => isset($action['confirm']) ? (string)$action['confirm'] : null,
            ];
        }, array_values($actions));

        return $this;
    }

    /** Satır kimliğini taşıyan sütun; seçim ve satır işlemleri buna dayanır. */
    public function key(string $key): self
    {
        $this->key = $key;

        return $this;
    }

    public function columns(array $columns): self
    {
        $this->columns = array_map([$this, 'normalizeColumn'], array_values($columns));

        return $this;
    }

    /** Her istekle birlikte gönderilecek sabit bağlam (proje id'si gibi). */
    public function context(array $context): self
    {
        $this->context = $context;

        return $this;
    }

    /**
     * Tablo seçenekleri:
     *   pageSize (int)      varsayılan sayfa boyu
     *   pageSizes (array)   sayfa boyu seçenekleri
     *   search (bool)       düz arama kutusu
     *   excel (bool)        Excel çıktı düğmesi
     *   clear (bool)        filtre temizle düğmesi
     *   columns (bool)      sütun göster/gizle menüsü; tercih tarayıcıda kalıcıdır
     *   order (array)       başlangıç sıralaması: [['col'=>'id','dir'=>'desc']]
     *   height (string)     gövde yüksekliği, örn '60vh'
     *   skin (string)       default | modern | compact — ya da kendi skin'iniz
     *   striped (bool)      satırları dönüşümlü renklendir; skin'den bağımsızdır
     *   chips (bool)        aktif filtreleri araç çubuğunun altında rozet olarak göster
     *   multiSort (bool)    shift+tık ile ikinci, üçüncü sıralama anahtarı
     *   urlSync (bool)      durumu adres çubuğuna yaz; grid'de varsayılan açık
     *   group (array)       satır gruplaması (workbench) — bkz. resolveGroup()
     *   minWidth (string)   tablonun taban genişliği, örn '1200px'. Tablo
     *                       normalde kabına sığar; sabit sütunun iş görmesi
     *                       için taşması gerekir. Verilmezse sabit sütunlu
     *                       tabloda sütunların bildirdiği width'ler toplanır.
     */
    public function options(array $options): self
    {
        $this->options = array_merge($this->options, $options);

        return $this;
    }

    /** Tanımın tamamı — vnltable.js bunu okur. */
    public function config(): array
    {
        if ($this->url === '') {
            throwErrorMessage('VnlTable "' . $this->id . '": url() verilmedi.');
        }

        if (empty($this->columns)) {
            throwErrorMessage('VnlTable "' . $this->id . '": columns() boş.');
        }

        /* Sütun filtresi olan modlarda çip, çoklu sıralama ve URL senkronu
           kendiliğinden açılır: grid'in varlık sebebi bunlar. list'te
           kapalıdır — orada filtre de yoktur, adres çubuğunu kirletmenin
           anlamı olmaz. */
        $rich = in_array($this->mode, [self::GRID, self::WORKBENCH], true);

        $options = array_merge([
            'pageSize'  => 25,
            'pageSizes' => [10, 25, 50, 100],
            'search'    => true,
            'excel'     => true,
            'clear'     => true,
            'columns'   => true,
            'order'     => [],
            'height'    => null,
            'skin'      => 'default',
            'striped'   => false,
            'chips'     => $rich,
            'multiSort' => $rich,
            'urlSync'   => $rich,
            'group'     => null,
            'minWidth'  => null,
            /* Toplu işlem tanımlandıysa seçim kutusuna gerek var demektir;
               yalnız listelemek için de açılabilir. */
            'selection' => $this->actions !== [],
        ], $this->options);

        $options['group']     = $this->resolveGroup($options['group']);
        $options['selection'] = (bool)$options['selection'];
        $options['detail']    = $this->detailUrl !== '';

        /* Skin adı doğrudan CSS sınıfına dönüşüyor; sınıf adı olarak
           geçerli olmayan her karakter atılır. */
        $options['skin']    = preg_replace('/[^a-z0-9-]/', '', strtolower((string)$options['skin'])) ?: 'default';
        $options['striped'] = (bool)$options['striped'];

        return [
            'id'      => $this->id,
            'url'     => $this->url,
            'exportUrl' => $this->exportUrl,
            'saveUrl' => $this->saveUrl,
            'detailUrl' => $this->detailUrl,
            'actions' => $this->actions,
            'mode'    => $this->mode,
            'key'     => $this->key,
            'columns' => array_map([$this, 'resolveFilter'], $this->columns),
            'context' => (object)$this->context,
            'options' => $options,
            'lang'    => self::lang(),
        ];
    }

    /** Bağlama noktası + tanım. Çizimi vnltable.js devralır. */
    public function render(): string
    {
        /* JSON <script> içinde durduğu için < > & kaçışlanır; aksi halde
           veri içindeki "</script>" bloğu erkenden kapatabilirdi. */
        $json = json_encode(
            $this->config(),
            JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP
        );

        return '<div class="vnl-table" id="' . htmlspecialchars($this->id, ENT_QUOTES, 'UTF-8') . '" data-vnl-table>'
            . '<script type="application/json" class="vnl-table-config">' . $json . '</script>'
            . '</div>';
    }

    public function __toString(): string
    {
        return $this->render();
    }

    /* ---------------------------------------------------------------- */

    /**
     * Sütun tanımı:
     *   name     (zorunlu) veri anahtarı
     *   title    başlık; verilmezse name kullanılır
     *   type     text | number | date | datetime | bool — hizalama ve sıralama
     *   render   text | html — html yalnızca gerçekten HTML dönen sütunlar için
     *   sortable sıralanabilir mi
     *   visible  tanımdan tamamen çıkarılır mı (istemciye hiç gitmez)
     *   hideable kullanıcı sütun menüsünden gizleyebilir mi
     *   width    '120px' gibi
     *   align    left | center | right; verilmezse type'tan türetilir
     *   filter   sütun filtresi — bkz. resolveFilter()
     *   agg      alt toplam: sum | avg | min | max | count. Değeri sunucu
     *            hesaplar (VnlTableRequest::totals), burası yalnız hangi
     *            sütunda gösterileceğini söyler.
     *   pin      left | right — sütunu kenara yapıştırır, yatay kaydırmada
     *            yerinde kalır. Sunum özelliğidir, her modda geçerlidir.
     *   groupable  workbench'te gruplama menüsünde listelensin mi
     *   editable   hücre çift tıkla düzenlensin mi (workbench). Kaydetme
     *              op = save ile url()'e gider; sunucuda editColumn() ile
     *              yazılabilir sütunları BEYAZ LİSTEYE almak zorunludur.
     *   editOptions  açılır liste seçenekleri: ['Onaylandı', 'İptal'] ya da
     *              ['a' => 'Etiket']. Verilirse serbest metin yerine select.
     *
     * filter burada ham bırakılır: mod() sütunlardan sonra çağrılabildiği
     * için çözümleme config()'e ertelenir.
     */
    private function normalizeColumn($column): array
    {
        if (!is_array($column) || empty($column['name'])) {
            throwErrorMessage('VnlTable: her sütun bir "name" alanı içermelidir.');
        }

        $type = $column['type'] ?? 'text';
        $agg  = $column['agg'] ?? null;

        if ($agg !== null && !in_array($agg, VnlTableRequest::AGGS, true)) {
            throwErrorMessage(
                'VnlTable: sütun "' . $column['name'] . '" için geçersiz alt toplam "' . $agg
                . '". Beklenen: ' . implode(', ', VnlTableRequest::AGGS)
            );
        }

        return [
            'name'     => (string)$column['name'],
            'title'    => (string)($column['title'] ?? $column['name']),
            'type'     => $type,
            'render'   => ($column['render'] ?? 'text') === 'html' ? 'html' : 'text',
            'sortable' => (bool)($column['sortable'] ?? true),
            'visible'  => (bool)($column['visible'] ?? true),
            'hideable' => (bool)($column['hideable'] ?? true),
            'width'    => $column['width'] ?? null,
            'align'    => $column['align'] ?? ($type === 'number' ? 'right' : ($type === 'bool' ? 'center' : 'left')),
            'filter'   => $column['filter'] ?? null,
            'agg'      => $agg,
            'pin'      => in_array($column['pin'] ?? null, ['left', 'right'], true) ? $column['pin'] : null,
            'groupable' => (bool)($column['groupable'] ?? true),
            'editable'  => (bool)($column['editable'] ?? false),
            'editOptions' => self::editOptions($column['editOptions'] ?? null),
        ];
    }

    /**
     * Sütun filtresini moda göre çözer.
     *
     * list'te filtre yoktur: tanımda ne yazarsa yazsın kapalı döner.
     * grid ve workbench'te verilmediyse sütun tipinden türetilir —
     * sütun başına filtre yazmak zorunda kalmayın diye.
     *
     *   'filter' => false                       kapalı
     *   'filter' => 'set'                       yalnız değer listesi
     *   'filter' => 'number'                    türü elle ver
     *   'filter' => ['kind' => 'text',          değer listesi kapalı
     *                'values' => false]
     *   'filter' => ['ops' => ['equals']]       koşulları daralt
     *
     * values, sütunun benzersiz değerlerinin (op = distinct) panelde
     * listelenip listelenmeyeceğidir. Metin, bool ve set'te açıktır;
     * sayı ve tarihte kapalıdır — her satırın ayrı bir değer taşıdığı
     * sütunda 500 satırlık bir onay kutusu listesi işe yaramaz.
     */
    private function resolveFilter(array $column): array
    {
        $spec = $column['filter'];

        if (!in_array($this->mode, [self::GRID, self::WORKBENCH], true) || $spec === false || $spec === 'none') {
            $column['filter'] = null;

            return $column;
        }

        if (is_string($spec)) {
            $spec = ['kind' => $spec];
        } elseif (!is_array($spec)) {
            $spec = [];
        }

        $kind = (string)($spec['kind'] ?? self::kindFromType($column['type']));

        if (!isset(VnlTableRequest::FILTER_OPS[$kind])) {
            throwErrorMessage(
                'VnlTable "' . $this->id . '": sütun "' . $column['name'] . '" için geçersiz filtre türü "' . $kind
                . '". Beklenen: ' . implode(', ', array_keys(VnlTableRequest::FILTER_OPS))
            );
        }

        $ops = VnlTableRequest::FILTER_OPS[$kind];

        if (isset($spec['ops']) && is_array($spec['ops'])) {
            $ops = array_values(array_intersect($ops, $spec['ops']));
        }

        $column['filter'] = [
            'kind'   => $kind,
            'values' => (bool)($spec['values'] ?? in_array($kind, ['text', 'bool', 'set'], true)),
            'ops'    => $ops,
        ];

        return $column;
    }

    /**
     * Satır gruplaması — yalnız workbench'te.
     *
     *   'group' => true                        menü ve bırakma alanı açılır
     *   'group' => 'sehir'                     tek seviye
     *   'group' => ['sehir', 'durum']          iç içe iki seviye
     *   'group' => ['by' => ['sehir','durum'], tam denetim
     *               'mode' => 'auto',
     *               'limit' => 5000,
     *               'menu' => true,
     *               'zone' => true]
     *
     * mode neyin nerede yapılacağını söyler:
     *   client — bütün satırlar tek seferde çekilir, gruplama tarayıcıda.
     *            Basit ve anında; büyük tabloda kullanılamaz.
     *   server — grup listesi op=group ile gelir, satırlar grup açılınca
     *            ayrı istekle. Büyük tabloda tek doğru yol.
     *   auto   — son bilinen kayıt sayısı limit'in altındaysa client,
     *            üstündeyse server. Varsayılan.
     */
    private function resolveGroup($spec): ?array
    {
        if ($this->mode !== self::WORKBENCH || $spec === null || $spec === false) {
            return null;
        }

        /* 'sehir' ve ['sehir', 'durum'] kısa yazımları. İlişkisel dizi
           (by/mode/limit…) tam tanım sayılır; düz liste ise seviyelerdir. */
        if (is_string($spec)) {
            $spec = ['by' => [$spec]];
        } elseif (is_array($spec) && $spec !== [] && array_is_list($spec)) {
            $spec = ['by' => $spec];
        } elseif (!is_array($spec)) {
            $spec = [];
        }

        $mode = (string)($spec['mode'] ?? 'auto');

        if (!in_array($mode, ['auto', 'client', 'server'], true)) {
            throwErrorMessage(
                'VnlTable "' . $this->id . '": geçersiz gruplama modu "' . $mode . '". Beklenen: auto, client, server'
            );
        }

        $by = $spec['by'] ?? [];

        if (is_string($by)) {
            $by = ($by === '') ? [] : [$by];
        }

        $by = is_array($by) ? array_values(array_unique(array_map('strval', $by))) : [];

        foreach ($by as $name) {
            if (!$this->hasColumn($name)) {
                throwErrorMessage('VnlTable "' . $this->id . '": gruplanacak sütun "' . $name . '" tanımlı değil.');
            }
        }

        return [
            'by'    => $by,
            'mode'  => $mode,
            'limit' => max(1, (int)($spec['limit'] ?? 5000)),
            'menu'  => (bool)($spec['menu'] ?? true),
            'zone'  => (bool)($spec['zone'] ?? true),
        ];
    }

    private function hasColumn(string $name): bool
    {
        foreach ($this->columns as $column) {
            if ($column['name'] === $name) {
                return true;
            }
        }

        return false;
    }

    /**
     * Açılır liste seçenekleri tek biçime indirilir: [['v' => .., 'l' => ..]]
     *
     * Düz liste verildiğinde değer etiketin kendisidir; ilişkisel dizide
     * anahtar kaydedilen, değer gösterilendir.
     */
    private static function editOptions($options): ?array
    {
        if (!is_array($options) || !$options) {
            return null;
        }

        $out = [];

        foreach ($options as $key => $label) {
            $out[] = is_int($key)
                ? ['v' => (string)$label, 'l' => (string)$label]
                : ['v' => (string)$key, 'l' => (string)$label];
        }

        return $out;
    }

    private static function kindFromType(string $type): string
    {
        switch ($type) {
            case 'number':   return 'number';
            case 'date':
            case 'datetime': return 'date';
            case 'bool':     return 'bool';
            default:         return 'text';
        }
    }

    /**
     * Arayüz metinleri tanımın içinde gider.
     *
     * Böylece istemcide ayrı bir dil köprüsü gerekmez ve tablo, sunucudaki
     * aktif dili her zaman doğru yansıtır.
     */
    private static function lang(): array
    {
        return [
            'loading'       => lang('VNLTABLE_LOADING'),
            'empty'         => lang('VNLTABLE_EMPTY'),
            'error'         => lang('VNLTABLE_ERROR'),
            'info'          => lang('VNLTABLE_INFO'),
            'infoEmpty'     => lang('VNLTABLE_INFO_EMPTY'),
            'infoFiltered'  => lang('VNLTABLE_INFO_FILTERED'),
            'search'        => lang('VNLTABLE_SEARCH'),
            'pageSize'      => lang('VNLTABLE_PAGE_SIZE'),
            'all'           => lang('VNLTABLE_ALL'),
            'first'         => lang('VNLTABLE_FIRST'),
            'prev'          => lang('VNLTABLE_PREV'),
            'next'          => lang('VNLTABLE_NEXT'),
            'last'          => lang('VNLTABLE_LAST'),
            'clear'         => lang('VNLTABLE_CLEAR'),
            'excel'         => lang('VNLTABLE_EXCEL'),
            'columns'       => lang('VNLTABLE_COLUMNS'),
            'sortAsc'       => lang('VNLTABLE_SORT_ASC'),
            'sortDesc'      => lang('VNLTABLE_SORT_DESC'),

            /* Sütun filtre paneli */
            'filter'        => lang('VNLTABLE_FILTER'),
            'filterSearch'  => lang('VNLTABLE_FILTER_SEARCH'),
            'filterAll'     => lang('VNLTABLE_FILTER_ALL'),
            'filterNone'    => lang('VNLTABLE_FILTER_NONE'),
            'filterMore'    => lang('VNLTABLE_FILTER_MORE'),
            'filterCond'    => lang('VNLTABLE_FILTER_COND'),
            'filterNoCond'  => lang('VNLTABLE_FILTER_NO_COND'),
            'filterClear'   => lang('VNLTABLE_FILTER_CLEAR'),
            'filterCount'   => lang('VNLTABLE_FILTER_COUNT'),
            'apply'         => lang('VNLTABLE_APPLY'),
            'ops'           => self::opLabels(),

            /* Alt toplamlar */
            'totals'        => lang('VNLTABLE_TOTALS'),
            'aggs'          => self::aggLabels(),

            /* Gruplama */
            'group'         => lang('VNLTABLE_GROUP'),
            'groupNone'     => lang('VNLTABLE_GROUP_NONE'),
            'groupCount'    => lang('VNLTABLE_GROUP_COUNT'),
            'groupMore'     => lang('VNLTABLE_GROUP_MORE'),
            'groupEmpty'    => lang('VNLTABLE_GROUP_EMPTY'),
            'groupDrop'     => lang('VNLTABLE_GROUP_DROP'),
            'infoGroups'    => lang('VNLTABLE_INFO_GROUPS'),

            /* Hücre düzenleme */
            'editHint'      => lang('VNLTABLE_EDIT_HINT'),
            'editSaved'     => lang('VNLTABLE_EDIT_SAVED'),
            'editFailed'    => lang('VNLTABLE_EDIT_FAILED'),

            /* Detay ve seçim */
            'detail'        => lang('VNLTABLE_DETAIL'),
            'detailFailed'  => lang('VNLTABLE_DETAIL_FAILED'),
            'select'        => lang('VNLTABLE_SELECT'),
            'selectAll'     => lang('VNLTABLE_SELECT_ALL'),
            'selected'      => lang('VNLTABLE_SELECTED'),
            'selectedAll'   => lang('VNLTABLE_SELECTED_ALL'),
            'selectMatching' => lang('VNLTABLE_SELECT_MATCHING'),
            'selectClear'   => lang('VNLTABLE_SELECT_CLEAR'),
            'bulkFailed'    => lang('VNLTABLE_BULK_FAILED'),
        ];
    }

    /** Alt toplam adları; footer hücresinin başlık balonunda görünür. */
    private static function aggLabels(): array
    {
        $labels = [];

        foreach (VnlTableRequest::AGGS as $agg) {
            $labels[$agg] = lang('VNLTABLE_AGG_' . strtoupper($agg));
        }

        return $labels;
    }

    /**
     * Koşul adları.
     *
     * Anahtarlar VnlTableRequest::FILTER_OPS'tan gelir; buradan üretilir ki
     * yeni bir koşul eklendiğinde karşılığı olmayan anahtar sessizce
     * kaybolmasın — lang() bulunamayan anahtarın kendisini döndürür,
     * eksiklik listede görünür.
     */
    private static function opLabels(): array
    {
        $ops = [];

        foreach (VnlTableRequest::FILTER_OPS as $list) {
            foreach ($list as $op) {
                $ops[$op] = lang('VNLTABLE_OP_' . strtoupper(preg_replace('/([a-z])([A-Z])/', '$1_$2', $op)));
            }
        }

        return $ops;
    }
}

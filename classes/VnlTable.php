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
        ], $this->options);

        /* Skin adı doğrudan CSS sınıfına dönüşüyor; sınıf adı olarak
           geçerli olmayan her karakter atılır. */
        $options['skin']    = preg_replace('/[^a-z0-9-]/', '', strtolower((string)$options['skin'])) ?: 'default';
        $options['striped'] = (bool)$options['striped'];

        return [
            'id'      => $this->id,
            'url'     => $this->url,
            'exportUrl' => $this->exportUrl,
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

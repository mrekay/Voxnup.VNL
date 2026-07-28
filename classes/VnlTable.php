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
    /** Basit sütunlar, düz arama, sayfalama, Excel. */
    const BASIC = 'basic';

    /** BASIC + sütun filtre panelleri (Excel tarzı seçim ve koşullar). */
    const EXPERT = 'expert';

    /** EXPERT + pivot, hücre düzenleme, accordion. */
    const EXPERT_PLUS = 'expert_plus';

    private string $id;
    private string $url     = '';
    private string $mode    = self::BASIC;
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
        $allowed = [self::BASIC, self::EXPERT, self::EXPERT_PLUS];

        if (!in_array($mode, $allowed, true)) {
            throwErrorMessage('VnlTable: geçersiz mod "' . $mode . '". Beklenen: ' . implode(', ', $allowed));
        }

        $this->mode = $mode;

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
        ], $this->options);

        /* Skin adı doğrudan CSS sınıfına dönüşüyor; sınıf adı olarak
           geçerli olmayan her karakter atılır. */
        $options['skin']    = preg_replace('/[^a-z0-9-]/', '', strtolower((string)$options['skin'])) ?: 'default';
        $options['striped'] = (bool)$options['striped'];

        return [
            'id'      => $this->id,
            'url'     => $this->url,
            'mode'    => $this->mode,
            'key'     => $this->key,
            'columns' => $this->columns,
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
     *   filter   (aşama 2) text | set | number | date
     */
    private function normalizeColumn($column): array
    {
        if (!is_array($column) || empty($column['name'])) {
            throwErrorMessage('VnlTable: her sütun bir "name" alanı içermelidir.');
        }

        $type = $column['type'] ?? 'text';

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
        ];
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
        ];
    }
}

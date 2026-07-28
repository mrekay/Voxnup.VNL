<?php
/*!
 * ============================================================
 *  Voxnup Not Laravel (VNL) 3.0.0 — VNL Table İsteği
 *  getList action'ına gelen isteği çözer, yanıtı biçimlendirir.
 *  Authors: Voxnup Authors
 * ============================================================
 */

/**
 * VNL Table isteği.
 *
 * getList adı VNL 2.x'ten korunur ama sözleşme tamamen değişti: DataTables'ın
 * konum tabanlı columns[0][search][value] biçimi yerine adlandırılmış,
 * okunabilir bir JSON gövdesi kullanılır.
 *
 *   public function getList($req)
 *   {
 *       Auth::CheckLogin();
 *       $t = VnlTableRequest::from($req);
 *
 *       $extras = $t->extras('created_at', 'desc');
 *       $rows   = User::SelectList($t->modelFilters(), $extras)->get();
 *
 *       $t->respond(
 *           $rows->map(fn($u) => ['id' => $u->id, 'username' => $u->username])->all(),
 *           User::SelectList(null, null)->count(),
 *           User::SelectList($t->modelFilters(), null)->count()
 *       );
 *   }
 */
class VnlTableRequest
{
    /** Veri listesi. */
    const OP_LIST = 'list';

    /** Sütun filtre panelindeki değer listesi. */
    const OP_DISTINCT = 'distinct';

    /** Excel çıktısı. */
    const OP_EXPORT = 'export';

    /** Satır gruplaması: grup listesi, sayıları ve alt toplamları. */
    const OP_GROUP = 'group';

    /**
     * Filtre türü → kullanılabilir koşullar.
     *
     * Tek kaynak: VnlTable sütun tanımını buna göre doğrular, istemci
     * açılır listeyi bundan çizer, gelen istek yine buradan geçirilir.
     * set ve bool koşul almaz; onlar yalnızca değer listesiyle çalışır.
     */
    const FILTER_OPS = [
        'text'   => ['contains', 'notContains', 'equals', 'notEquals', 'startsWith', 'endsWith', 'empty', 'notEmpty'],
        'number' => ['equals', 'notEquals', 'gt', 'gte', 'lt', 'lte', 'between', 'empty', 'notEmpty'],
        'date'   => ['equals', 'before', 'after', 'between', 'empty', 'notEmpty'],
        'bool'   => ['empty', 'notEmpty'],
        'set'    => [],
    ];

    /** Değer istemeyen koşullar. */
    const OPS_NO_VALUE = ['empty', 'notEmpty'];

    /** İkinci değeri de isteyen koşullar. */
    const OPS_RANGE = ['between'];

    /** Değer listesinin tavanı; aşılırsa istemciye kesildiği bildirilir. */
    const DISTINCT_LIMIT = 500;

    /** Sütun altında gösterilebilecek toplamlar. */
    const AGGS = ['sum', 'avg', 'min', 'max', 'count'];

    public string $op       = self::OP_LIST;
    public int    $page     = 1;
    public int    $size     = 25;
    public array  $sort     = [];
    public string $search   = '';
    public array  $filters  = [];
    public array  $context  = [];

    /** OP_DISTINCT: değer listesi istenen sütun. */
    public string $column = '';

    /** OP_DISTINCT: panelin kendi arama kutusu. */
    public string $query = '';

    /** OP_GROUP: satırların hangi sütuna göre kümeleneceği. */
    public string $groupBy = '';

    public static function from($req): self
    {
        $self = new self();

        $op = (string)$req->post('op', self::OP_LIST);
        $self->op = in_array($op, [self::OP_LIST, self::OP_DISTINCT, self::OP_EXPORT, self::OP_GROUP], true)
            ? $op
            : self::OP_LIST;

        $self->page = max(1, (int)$req->post('page', 1));

        /* Sayfa boyu istemciden gelir; sunucuyu boğmamak için tavanlanır. */
        $size = (int)$req->post('size', 25);
        $self->size = ($size <= 0) ? 25 : min($size, 500);

        $self->search  = trim((string)$req->post('search', ''));
        $self->column  = (string)$req->post('col', '');
        $self->query   = trim((string)$req->post('q', ''));
        $self->groupBy = (string)$req->post('by', '');
        $self->context = self::asArray($req->post('context'));
        $self->filters = self::normalizeFilters($req->post('filters'));
        $self->sort    = self::normalizeSort($req->post('sort'));

        return $self;
    }

    /** LIMIT/OFFSET hesabı. */
    public function offset(): int
    {
        return ($this->page - 1) * $this->size;
    }

    /**
     * TableModel::SelectList()'in beklediği $extras dizisi.
     * Sıralama gelmediyse verilen varsayılan kullanılır.
     */
    public function extras(string $defaultColumn = 'id', string $defaultDir = 'desc'): array
    {
        $column = $this->sort[0]['col'] ?? $defaultColumn;
        $dir    = $this->sort[0]['dir'] ?? $defaultDir;

        return [
            'begin'    => $this->offset(),
            'limit'    => $this->size,
            'search'   => $this->search,
            'order'    => $column,
            'order_by' => $dir,
        ];
    }

    /**
     * TableModel::SelectList()'e geçirilecek $filters dizisi.
     *
     * Sütun filtrelerini TAŞIMAZ: TableModel sütun başına tek operatör
     * bilir, kümeyi (IN) ve iki değerli aralığı ifade edemez. Sütun
     * filtreleri için applyFilters() kullanın; bu yordam yalnızca
     * kendi eklediğiniz sabit filtreleri geçirir.
     */
    public function modelFilters(array $extra = []): array
    {
        return $extra;
    }

    /* ================================================================
       Filtreler
       ================================================================ */

    /**
     * Hedef sütunun kendi filtresi dışındaki filtreler.
     *
     * Değer listesi bununla hesaplanır — Excel'in davranışı. Sütunun
     * kendi seçimi de sayılsaydı liste her uygulamada küçülür, kullanıcı
     * az önce kapattığı değeri geri açmak için bulamazdı.
     */
    public function filtersExcept(?string $column = null): array
    {
        $filters = $this->filters;
        unset($filters[$column ?? $this->column]);

        return $filters;
    }

    /** Herhangi bir sütun filtresi var mı. */
    public function hasFilters(): bool
    {
        return $this->filters !== [];
    }

    /**
     * Filtreleri bir sorgu kurucusuna uygular (Eloquent ya da Query Builder).
     *
     * $columns istemciden gelen adı gerçek veritabanı sütununa bağlar;
     * listede olmayan hiçbir ad sorguya girmez. Bu köprü isteğe bağlı
     * değildir: filtre adları istemciden geldiği için doğrudan sorguya
     * verilmeleri sütun adı enjeksiyonuna açık olurdu.
     *
     *   $t->applyFilters($q, ['ad', 'sehir', 'tutar']);             // ad = sütun
     *   $t->applyFilters($q, ['sehir' => 'city', 'ad' => 'name']);  // eşleme
     *
     * Büyük/küçük harf duyarlılığı sütunun collation'ına bağlıdır;
     * MySQL'in _ci collation'larında metin karşılaştırmaları duyarsızdır.
     */
    public function applyFilters($query, array $columns, ?array $filters = null)
    {
        $map = self::columnMap($columns);

        foreach (($filters ?? $this->filters) as $name => $entry) {
            if (!isset($map[$name])) {
                continue;
            }

            $column = $map[$name];

            if (!empty($entry['set'])) {
                $query->whereIn($column, $entry['set']);
            }

            if (!empty($entry['cond'])) {
                self::applyCondition($query, $column, $entry['cond'], $entry['kind']);
            }
        }

        return $query;
    }

    /** Bellekteki satırlara aynı filtreleri uygular. */
    public function filterRows(array $rows, ?array $filters = null): array
    {
        $filters = $filters ?? $this->filters;

        if (!$filters) {
            return array_values($rows);
        }

        $out = [];
        foreach ($rows as $row) {
            if ($this->matches($row, $filters)) {
                $out[] = $row;
            }
        }

        return $out;
    }

    /**
     * Tek satır filtrelerden geçiyor mu.
     *
     * Satırda bulunmayan sütunun filtresi yok sayılır: tanım değişmiş
     * ya da tarayıcıda eski bir filtre kalmış olabilir; bu yüzden
     * tabloyu tamamen boşaltmak yanlış olurdu.
     */
    public function matches($row, ?array $filters = null): bool
    {
        $row = self::rowToArray($row);

        foreach (($filters ?? $this->filters) as $name => $entry) {
            if (!array_key_exists($name, $row)) {
                continue;
            }

            $cell = $row[$name];

            if (!empty($entry['set']) && !in_array(self::text($cell), $entry['set'], true)) {
                return false;
            }

            if (!empty($entry['cond']) && !self::cellMatches($cell, $entry['cond'], $entry['kind'])) {
                return false;
            }
        }

        return true;
    }

    /* ================================================================
       Yanıtlar
       ================================================================ */

    /**
     * Liste yanıtı.
     *
     * $totals sütun adı → değer eşlemesidir ve tablonun altındaki toplam
     * satırını doldurur. Sayfanın değil, FİLTRENİN TAMAMININ toplamı
     * beklenir: kullanıcı 25 satır görürken 4200 satırın toplamını okur.
     */
    public function respond(array $rows, int $total, ?int $filtered = null, array $totals = []): void
    {
        $data = [
            'rows'     => $rows,
            'total'    => $total,
            'filtered' => $filtered ?? $total,
            'page'     => $this->page,
            'size'     => $this->size,
        ];

        if ($totals) {
            $data['totals'] = $totals;
        }

        self::emit($data);
    }

    /**
     * Bellekteki satırlardan alt toplam.
     *
     * @param array $aggs ['tutar' => 'sum', 'id' => 'count']
     *
     * Sayısal olmayan hücreler sum/avg/min/max'ta atlanır; count her dolu
     * hücreyi sayar. Biçimlendirme yapılmaz — tabloda hücreler ham değer
     * taşıdığı için toplam da ham gider; istiyorsanız kendiniz biçimleyip
     * metin olarak verin.
     */
    public static function totals(array $rows, array $aggs): array
    {
        $out = [];

        foreach ($aggs as $column => $agg) {
            if (!in_array($agg, self::AGGS, true)) {
                continue;
            }

            $values = [];

            foreach ($rows as $row) {
                $row = self::rowToArray($row);

                if (!array_key_exists($column, $row)) {
                    continue;
                }

                $cell = $row[$column];

                if ($agg === 'count') {
                    if (self::text($cell) !== '') {
                        $values[] = 1;
                    }
                } elseif (is_numeric($cell)) {
                    $values[] = (float)$cell;
                }
            }

            if (!$values) {
                $out[$column] = null;
                continue;
            }

            switch ($agg) {
                case 'sum':   $out[$column] = array_sum($values); break;
                case 'count': $out[$column] = count($values); break;
                case 'min':   $out[$column] = min($values); break;
                case 'max':   $out[$column] = max($values); break;
                case 'avg':   $out[$column] = round(array_sum($values) / count($values), 2); break;
            }
        }

        return $out;
    }

    /**
     * Değer listesi yanıtı (op = distinct).
     *
     * İki biçim kabul edilir:
     *   ['Ankara', 'İzmir']                          — yalnız değerler
     *   [['v' => 'Ankara', 'n' => 43], ...]          — değer + kayıt sayısı
     *
     * Liste DISTINCT_LIMIT'te kesilir. Kesildiğinde istemci bunu kullanıcıya
     * söyler ve panel aramasını istemcide değil sunucuda çalıştırır — aksi
     * halde listeye girmemiş bir değer hiçbir aramayla bulunamazdı.
     * SQL tarafında LIMIT ile kestiyseniz $truncated'ı kendiniz verin.
     */
    public function respondDistinct(array $values, ?bool $truncated = null): void
    {
        $out = [];

        foreach ($values as $value) {
            if (count($out) >= self::DISTINCT_LIMIT) {
                break;
            }

            $out[] = is_array($value)
                ? ['v' => self::text($value['v'] ?? ''), 'n' => isset($value['n']) ? (int)$value['n'] : null]
                : ['v' => self::text($value), 'n' => null];
        }

        self::emit([
            'col'       => $this->column,
            'values'    => $out,
            'truncated' => $truncated ?? (count($values) > self::DISTINCT_LIMIT),
        ]);
    }

    /**
     * Tablonun JSON yanıtı.
     *
     * returnSuccessDataOnly() kullanılmaz: o, JSON_NUMERIC_CHECK ile sayı
     * gibi görünen her metni sayıya çevirir. Değer listesinde bu, "007"
     * stok kodunu 7 yapar; istemci onu geri gönderdiğinde hiçbir satırla
     * eşleşmez. Tabloda hücre ne ise o gider.
     */
    private static function emit(array $data): void
    {
        http_response_code(200);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        die;
    }

    /**
     * Bellekteki satırlardan değer listesini üretir ve yanıtı basar.
     *
     * Daraltma diğer sütunların filtrelerine göredir (filtersExcept) ve
     * panelin arama kutusu da burada uygulanır. Boş hücreler listeye
     * girmez; onlar "Boş" koşuluyla aranır.
     */
    public function distinctFrom(array $rows): void
    {
        $column  = $this->column;
        $filters = $this->filtersExcept();
        $needle  = TextGeneralModule::trToLower($this->query);

        $counts = [];

        foreach ($rows as $row) {
            $row = self::rowToArray($row);

            if (!array_key_exists($column, $row) || !$this->matches($row, $filters)) {
                continue;
            }

            $value = self::text($row[$column]);

            if ($value === '') {
                continue;
            }

            if ($needle !== '' && !str_contains(TextGeneralModule::trToLower($value), $needle)) {
                continue;
            }

            $counts[$value] = ($counts[$value] ?? 0) + 1;
        }

        /* Sayı gibi görünen değerler doğal sırayla dizilir: düz metin
           sıralamasında "10" ile "9" arasındaki sıra yanlış çıkıyordu. */
        uksort($counts, function ($a, $b) {
            return strnatcasecmp(
                TextGeneralModule::trToLower((string)$a),
                TextGeneralModule::trToLower((string)$b)
            );
        });

        $values = [];
        foreach ($counts as $value => $count) {
            $values[] = ['v' => (string)$value, 'n' => $count];
        }

        $this->respondDistinct($values);
    }

    /**
     * Grup listesi yanıtı (op = group).
     *
     * @param array $groups [['k' => 'Ankara', 'n' => 43, 'totals' => [...]], ...]
     *
     * n grup içindeki kayıt sayısı, totals ise o gruba ait alt toplamlar;
     * ikisi de isteğe bağlıdır. İstemci grubu açtığında satırları ayrı bir
     * op = list isteğiyle çeker — grup anahtarı sıradan bir filtre olarak
     * gider, yani sunucu tarafında ek bir iş gerekmez.
     */
    public function respondGroups(array $groups): void
    {
        $out = [];

        foreach ($groups as $group) {
            if (!is_array($group)) {
                continue;
            }

            $out[] = [
                'k'      => self::text($group['k'] ?? ''),
                'n'      => isset($group['n']) ? (int)$group['n'] : null,
                'totals' => isset($group['totals']) && is_array($group['totals']) ? $group['totals'] : null,
            ];
        }

        self::emit(['by' => $this->groupBy, 'groups' => $out]);
    }

    /**
     * Bellekteki satırlardan grup listesini üretir ve yanıtı basar.
     *
     * distinctFrom()'dan farkı: burada TÜM filtreler geçerlidir, çünkü
     * gruplar kullanıcının süzdüğü kümenin özetidir. Satırların filtreden
     * geçmiş hâlini verin — respond()'a verdiğinizin aynısını.
     *
     * @param array $aggs ['tutar' => 'sum'] — grup başına alt toplamlar
     */
    public function groupFrom(array $rows, array $aggs = []): void
    {
        $column  = $this->groupBy;
        $buckets = [];

        foreach ($rows as $row) {
            $flat = self::rowToArray($row);

            if (!array_key_exists($column, $flat)) {
                continue;
            }

            $key = self::text($flat[$column]);
            $buckets[$key][] = $row;
        }

        uksort($buckets, function ($a, $b) {
            return strnatcasecmp(
                TextGeneralModule::trToLower((string)$a),
                TextGeneralModule::trToLower((string)$b)
            );
        });

        $groups = [];

        foreach ($buckets as $key => $members) {
            $groups[] = [
                'k'      => (string)$key,
                'n'      => count($members),
                'totals' => $aggs ? self::totals($members, $aggs) : null,
            ];
        }

        $this->respondGroups($groups);
    }

    /**
     * Excel çıktısı — xlsx olarak akıtılır ve script biter.
     *
     * @param array $columns ['name' => 'Başlık', ...] sırası çıktıyı belirler
     */
    public function exportXlsx(array $rows, array $columns, string $fileName = 'export'): void
    {
        VnlTableExport::stream($rows, $columns, $fileName);
    }

    /* ================================================================
       İç yardımcılar
       ================================================================ */

    private static function asArray($value): array
    {
        if (is_array($value)) {
            return $value;
        }

        /* Form gönderiminde JSON string olarak gelebilir. */
        if (is_string($value) && $value !== '') {
            $decoded = json_decode($value, true);

            return is_array($decoded) ? $decoded : [];
        }

        return [];
    }

    /** [['col' => 'ad', 'dir' => 'asc']] — yön beyaz listeyle sınırlanır. */
    private static function normalizeSort($sort): array
    {
        $sort = self::asArray($sort);
        $out  = [];

        foreach ($sort as $item) {
            if (!is_array($item) || empty($item['col']) || !is_string($item['col'])) {
                continue;
            }

            $dir = strtolower((string)($item['dir'] ?? 'asc'));

            $out[] = [
                'col' => $item['col'],
                'dir' => $dir === 'desc' ? 'desc' : 'asc',
            ];
        }

        return $out;
    }

    /**
     * Sütun adı → filtre. Her giriş bir küme seçimi, bir koşul ya da
     * ikisini birden taşıyabilir; ikisi VE ile bağlanır.
     *
     *   "sehir": {"kind":"text","set":["Ankara"],"cond":{"op":"contains","value":"an"}}
     *
     * kind istemciden gelir ama yalnızca karşılaştırma biçimini seçer,
     * sorguya isim taşımaz; tanınmayan tür metin sayılır.
     */
    private static function normalizeFilters($raw): array
    {
        $out = [];

        foreach (self::asArray($raw) as $name => $spec) {
            if (!is_string($name) || $name === '' || !is_array($spec)) {
                continue;
            }

            $kind = (string)($spec['kind'] ?? 'text');
            if (!isset(self::FILTER_OPS[$kind])) {
                $kind = 'text';
            }

            $entry = ['kind' => $kind];

            /* Boş küme filtre sayılmaz: istemci hiçbir değerin seçili
               olmadığı durumu engelliyor, yine de gelirse tabloyu
               boşaltmak yerine yok sayılır. */
            $set = self::normalizeSet($spec['set'] ?? null);
            if ($set) {
                $entry['set'] = $set;
            }

            $cond = self::normalizeCondition($spec['cond'] ?? null, $kind);
            if ($cond) {
                $entry['cond'] = $cond;
            }

            if (count($entry) > 1) {
                $out[$name] = $entry;
            }
        }

        return $out;
    }

    /** Küme seçimi metne indirgenir; NULL değerler "Boş" koşuluyla aranır. */
    private static function normalizeSet($set): array
    {
        if (!is_array($set)) {
            return [];
        }

        $out = [];
        foreach ($set as $value) {
            if (is_scalar($value)) {
                $out[] = self::text($value);
            }
        }

        return array_values(array_unique($out));
    }

    /** Koşul; değeri olmayan bir koşul filtre değildir ve düşürülür. */
    private static function normalizeCondition($cond, string $kind): array
    {
        if (!is_array($cond)) {
            return [];
        }

        $op = (string)($cond['op'] ?? '');

        if (!in_array($op, self::FILTER_OPS[$kind], true)) {
            return [];
        }

        if (in_array($op, self::OPS_NO_VALUE, true)) {
            return ['op' => $op];
        }

        $value = $cond['value'] ?? null;
        if (!is_scalar($value) || trim(self::text($value)) === '') {
            return [];
        }

        $out = ['op' => $op, 'value' => self::text($value)];

        if (in_array($op, self::OPS_RANGE, true)) {
            $value2 = $cond['value2'] ?? null;
            if (!is_scalar($value2) || trim(self::text($value2)) === '') {
                return [];
            }

            $out['value2'] = self::text($value2);
        }

        return $out;
    }

    /** ['ad', 'sehir'] ve ['sehir' => 'city'] biçimlerinin ikisi de kabul edilir. */
    private static function columnMap(array $columns): array
    {
        $map = [];

        foreach ($columns as $key => $value) {
            if (is_int($key)) {
                $map[(string)$value] = (string)$value;
            } else {
                $map[$key] = (string)$value;
            }
        }

        return $map;
    }

    private static function applyCondition($query, string $column, array $cond, string $kind): void
    {
        $op     = $cond['op'];
        $value  = $cond['value'] ?? '';
        $value2 = $cond['value2'] ?? '';

        switch ($op) {
            case 'empty':
                /* NULL ile boş metin kullanıcı için aynı şey. */
                $query->where(function ($w) use ($column) {
                    $w->whereNull($column)->orWhere($column, '=', '');
                });

                return;

            case 'notEmpty':
                $query->whereNotNull($column)->where($column, '<>', '');

                return;

            case 'contains':
                $query->where($column, 'like', '%' . self::likeEscape($value) . '%');

                return;

            case 'startsWith':
                $query->where($column, 'like', self::likeEscape($value) . '%');

                return;

            case 'endsWith':
                $query->where($column, 'like', '%' . self::likeEscape($value));

                return;

            /* NOT LIKE ve <> NULL satırlara false döner; kullanıcı için
               boş bir hücre aradığı metni "içermiyor" demektir. */
            case 'notContains':
                $query->where(function ($w) use ($column, $value) {
                    $w->where($column, 'not like', '%' . self::likeEscape($value) . '%')
                        ->orWhereNull($column);
                });

                return;

            case 'notEquals':
                $query->where(function ($w) use ($column, $value) {
                    $w->where($column, '<>', $value)->orWhereNull($column);
                });

                return;

            case 'equals':
                /* Tarih sütununda saat kısmı eşitliği bozar: 05 Mart aranınca
                   o günün tamamı eşleşmeli. */
                if ($kind === 'date') {
                    $query->whereDate($column, '=', $value);
                } else {
                    $query->where($column, '=', $value);
                }

                return;

            case 'gt':
                $query->where($column, '>', $value);

                return;

            case 'gte':
                $query->where($column, '>=', $value);

                return;

            case 'lt':
                $query->where($column, '<', $value);

                return;

            case 'lte':
                $query->where($column, '<=', $value);

                return;

            case 'before':
                $query->whereDate($column, '<', $value);

                return;

            case 'after':
                $query->whereDate($column, '>', $value);

                return;

            case 'between':
                if ($kind === 'date') {
                    $query->whereDate($column, '>=', $value)->whereDate($column, '<=', $value2);
                } else {
                    $query->whereBetween($column, [$value, $value2]);
                }

                return;
        }
    }

    /**
     * Aranan metindeki % ve _ joker olmasın: "50%" yazan kullanıcı
     * yüzde işaretini arıyor, "her şey"i değil.
     */
    private static function likeEscape(string $value): string
    {
        return addcslashes($value, '%_\\');
    }

    private static function cellMatches($cell, array $cond, string $kind): bool
    {
        $op  = $cond['op'];
        $raw = self::text($cell);

        if ($op === 'empty') {
            return trim($raw) === '';
        }

        if ($op === 'notEmpty') {
            return trim($raw) !== '';
        }

        $value  = (string)($cond['value'] ?? '');
        $value2 = (string)($cond['value2'] ?? '');

        if ($kind === 'number') {
            if (!is_numeric($raw) || !is_numeric($value)) {
                return false;
            }

            $a = (float)$raw;
            $b = (float)$value;

            switch ($op) {
                case 'equals':    return $a == $b;
                case 'notEquals': return $a != $b;
                case 'gt':        return $a > $b;
                case 'gte':       return $a >= $b;
                case 'lt':        return $a < $b;
                case 'lte':       return $a <= $b;
                case 'between':   return is_numeric($value2) && $a >= $b && $a <= (float)$value2;
            }

            return true;
        }

        if ($kind === 'date') {
            $a = self::day($raw);
            $b = self::day($value);

            if ($a === null || $b === null) {
                return false;
            }

            switch ($op) {
                case 'equals': return $a === $b;
                case 'before': return $a < $b;
                case 'after':  return $a > $b;
                case 'between':
                    $c = self::day($value2);

                    return $c !== null && $a >= $b && $a <= $c;
            }

            return true;
        }

        $haystack = TextGeneralModule::trToLower($raw);
        $needle   = TextGeneralModule::trToLower($value);

        switch ($op) {
            case 'contains':    return str_contains($haystack, $needle);
            case 'notContains': return !str_contains($haystack, $needle);
            case 'equals':      return $haystack === $needle;
            case 'notEquals':   return $haystack !== $needle;
            case 'startsWith':  return str_starts_with($haystack, $needle);
            case 'endsWith':    return str_ends_with($haystack, $needle);
        }

        return true;
    }

    /** Saat kısmı atılmış gün; çözülemeyen değer null döner. */
    private static function day(string $value): ?string
    {
        $value = trim($value);

        if ($value === '') {
            return null;
        }

        $time = strtotime($value);

        return $time === false ? null : date('Y-m-d', $time);
    }

    /** Eloquent modeli, stdClass ve dizi — üçü de satır olarak kabul edilir. */
    private static function rowToArray($row): array
    {
        if (is_array($row)) {
            return $row;
        }

        if (is_object($row)) {
            return method_exists($row, 'toArray') ? $row->toArray() : get_object_vars($row);
        }

        return [];
    }

    private static function text($value): string
    {
        if ($value === null || is_bool($value)) {
            return $value ? '1' : '';
        }

        return is_scalar($value) ? (string)$value : '';
    }
}

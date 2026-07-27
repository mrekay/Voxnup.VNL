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

    /** Sütun filtre panelindeki değer listesi (aşama 2). */
    const OP_DISTINCT = 'distinct';

    /** Excel çıktısı. */
    const OP_EXPORT = 'export';

    public string $op       = self::OP_LIST;
    public int    $page     = 1;
    public int    $size     = 25;
    public array  $sort     = [];
    public string $search   = '';
    public array  $filters  = [];
    public array  $context  = [];
    public string $column   = '';   // OP_DISTINCT için

    public static function from($req): self
    {
        $self = new self();

        $op = (string)$req->post('op', self::OP_LIST);
        $self->op = in_array($op, [self::OP_LIST, self::OP_DISTINCT, self::OP_EXPORT], true)
            ? $op
            : self::OP_LIST;

        $self->page = max(1, (int)$req->post('page', 1));

        /* Sayfa boyu istemciden gelir; sunucuyu boğmamak için tavanlanır. */
        $size = (int)$req->post('size', 25);
        $self->size = ($size <= 0) ? 25 : min($size, 500);

        $self->search  = trim((string)$req->post('search', ''));
        $self->column  = (string)$req->post('col', '');
        $self->context = self::asArray($req->post('context'));
        $self->filters = self::asArray($req->post('filters'));
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
     * TableModel::SelectList()'in beklediği $filters dizisi.
     *
     * Aşama 1'de sütun filtreleri henüz yok; yalnızca düz aramayı taşır.
     * Aşama 2'de $this->filters buradan çevrilecek.
     */
    public function modelFilters(array $extra = []): array
    {
        return $extra;
    }

    /** Liste yanıtı. */
    public function respond(array $rows, int $total, ?int $filtered = null): void
    {
        returnSuccessDataOnly([
            'rows'     => $rows,
            'total'    => $total,
            'filtered' => $filtered ?? $total,
            'page'     => $this->page,
            'size'     => $this->size,
        ]);
    }

    /**
     * Excel çıktısı — CSV olarak akıtılır.
     *
     * UTF-8 BOM ve noktalı virgül ayracı ile Türkçe Excel dosyayı doğrudan
     * doğru açar. İstemci yanıtı blob olarak alıp indirmeyi tetikler.
     *
     * @param array $columns ['name' => 'Başlık', ...] sırası çıktıyı belirler
     */
    public function exportCsv(array $rows, array $columns, string $fileName = 'export'): void
    {
        while (ob_get_level()) {
            ob_end_clean();
        }

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $fileName . '-' . date('Ymd-His') . '.csv"');

        $out = fopen('php://output', 'w');

        /* BOM olmadan Excel dosyayı Windows-1254 sanıp Türkçe harfleri bozar. */
        fwrite($out, "\xEF\xBB\xBF");

        fputcsv($out, array_values($columns), ';');

        foreach ($rows as $row) {
            $line = [];
            foreach (array_keys($columns) as $name) {
                $value = $row[$name] ?? '';
                $line[] = is_scalar($value) ? (string)$value : json_encode($value, JSON_UNESCAPED_UNICODE);
            }
            fputcsv($out, $line, ';');
        }

        fclose($out);
        exit;
    }

    /* ---------------------------------------------------------------- */

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
}

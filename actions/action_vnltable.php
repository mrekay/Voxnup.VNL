<?php
/*!
 * ============================================================
 *  Voxnup Not Laravel (VNL) — VNL Table Demo Verisi
 *  docs/vnltable sayfasındaki canlı örneği besler.
 *  Authors: Voxnup Authors
 * ============================================================
 */

/**
 * Dokümantasyon demosu.
 *
 * Veritabanı gerektirmez: satırlar bellekte üretilir, filtreleme ve
 * sıralama PHP tarafında yapılır. Gerçek bir tabloda bu işleri
 * TableModel::SelectList() üstlenir; getList sözleşmesi aynıdır.
 *
 * 3.4.0: getList girişsiz çağrılabilir (PUBLIC_METHODS) — VNL_PUBLIC_DOCS=true
 * iken /docs/vnltable* sayfaları anonim ziyaretçiye açıktır ve içlerindeki
 * tablolar bu uca POST eder; 401 ziyaretçiyi /login'e atıyordu. CSRF yine
 * zorunludur. Uç veritabanına dokunmaz, düzenlemeleri yalnız çağıranın kendi
 * oturumunda tutar; oturum dosyası şişirilemesin diye satır anahtarı gerçek
 * bir satır olmalı ve değer 255 karakterle sınırlıdır.
 *
 * GERÇEK bir modülde bunu kopyalamayın: action girişi varsayılan olarak
 * zorunlu kılar ve her metodun ilk satırı Auth::CheckPermission()'dır.
 */
class action_vnltable
{
    /** Girişsiz çağrılabilen metotlar (VnlDispatcher). CSRF yine zorunlu. */
    public const PUBLIC_METHODS = ['getList'];

    /** Demo satır sayısı (rows()). */
    private const ROW_COUNT = 213;

    /** Düzenlenen değerin azami uzunluğu. */
    private const MAX_VALUE_LENGTH = 255;

    private const SEHIRLER = ['İstanbul', 'Ankara', 'İzmir', 'Bursa', 'Antalya'];
    private const DURUMLAR = ['Onaylandı', 'Beklemede', 'İptal'];

    /** Çıktıdaki sütun başlıkları — Excel dışa aktarımı da bunu kullanır. */
    private const COLUMNS = [
        'id'     => 'No',
        'ad'     => 'Ad Soyad',
        'sehir'  => 'Şehir',
        'durum'  => 'Durum',
        'tutar'  => 'Tutar',
        'tarih'  => 'Tarih',
        'aktif'  => 'Aktif',
    ];

    /** Düzenlenebilir sütunlar. Gerçek bir tabloda burası DB sütun eşlemesidir. */
    private const EDITABLE = ['ad', 'sehir', 'durum', 'tutar'];

    /** Demo düzenlemelerinin tutulduğu oturum anahtarı. */
    private const EDIT_KEY = 'vnltable_demo_edits';

    public function getList($req)
    {
        $t = VnlTableRequest::from($req);

        /* Hücre düzenleme. Sütun adı istemciden geldiği için editColumn()
           beyaz listeye bakar; listede yoksa istek hatayla biter. */
        if ($t->op === VnlTableRequest::OP_SAVE) {
            $this->save($t);
        }

        if ($t->op === VnlTableRequest::OP_DETAIL) {
            $this->detail($t);
        }

        $rows  = $this->rows();
        $total = count($rows);

        /* Düz arama önce uygulanır: sütun filtre panelindeki değer listesi
           de arama kutusunun daralttığı kümeden çıksın. */
        $rows = $this->applySearch($rows, $t->search);

        /* Değer listesi. distinctFrom() daraltmayı kendisi yapar: hedef
           sütunun KENDİ seçimi sayılmaz, diğer sütunların filtreleri
           sayılır — Excel'in davranışı. */
        if ($t->op === VnlTableRequest::OP_DISTINCT) {
            $t->distinctFrom($rows);
        }

        /* Sütun filtreleri. Gerçek bir tabloda bunun yerine
           $t->applyFilters($query, ['id','ad','sehir',...]) çağrılır. */
        $rows = $t->filterRows($rows);

        $filtered = count($rows);

        /* Toplu işlem filtrelerden SONRA çalışır: "filtreye uyan hepsi"
           kullanıcının süzdüğü kümeyi kasteder. */
        if ($t->op === VnlTableRequest::OP_BULK) {
            $this->bulk($t, $rows);
        }

        /* Grup listesi: filtrelenmiş kümenin özeti. Değer listesinin
           aksine burada TÜM filtreler geçerlidir. */
        if ($t->op === VnlTableRequest::OP_GROUP) {
            $t->groupFrom($rows, ['tutar' => 'sum', 'id' => 'count']);
        }

        if ($t->op === VnlTableRequest::OP_PIVOT) {
            $t->pivotFrom($rows);
        }

        $sort = $t->sort[0] ?? ['col' => 'id', 'dir' => 'asc'];
        if (array_key_exists($sort['col'], self::COLUMNS)) {
            usort($rows, function ($a, $b) use ($sort) {
                $x = $a[$sort['col']];
                $y = $b[$sort['col']];
                $cmp = is_numeric($x) && is_numeric($y) ? ($x <=> $y) : strcoll((string)$x, (string)$y);

                return $sort['dir'] === 'desc' ? -$cmp : $cmp;
            });
        }

        /* Excel çıktısı sayfalamadan önce alınır: kullanıcı gördüğü sayfayı
           değil, filtrenin tamamını indirir. */
        if ($t->op === VnlTableRequest::OP_EXPORT) {
            $t->exportXlsx($rows, self::COLUMNS, 'vnltable-demo');
        }

        /* Alt toplamlar sayfalamadan ÖNCE hesaplanır: kullanıcı 10 satır
           görürken filtrenin tamamının toplamını okumalı. */
        $totals = VnlTableRequest::totals($rows, [
            'id'    => 'count',
            'tutar' => 'sum',
        ]);

        $t->respond(array_slice($rows, $t->offset(), $t->size), $total, $filtered, $totals);
    }

    /**
     * Demo kaydı.
     *
     * Veritabanı yok: satırlar her istekte yeniden üretiliyor, o yüzden
     * düzenlemeler oturumda saklanır ve rows() içinde satırların üzerine
     * bindirilir. Gerçek bir tabloda burada tek bir UPDATE olurdu.
     */
    private function save(VnlTableRequest $t): void
    {
        $column = $t->editColumn(self::EDITABLE);
        $value  = trim((string)$t->value);

        /* Satır anahtarı istemciden gelir: yalnız var olan satırlar
           (1..ROW_COUNT). Aksi halde her istek oturuma yeni bir anahtar
           ekleyebilir ve oturum dosyası sınırsız büyürdü. */
        $rowKey = filter_var($t->rowKey, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => self::ROW_COUNT]]);
        if ($rowKey === false) {
            throwErrorMessage('Kayıt bulunamadı.', 404);
        }

        if ($value === '') {
            throwErrorMessage('Boş bırakılamaz.');
        }

        if (mb_strlen($value) > self::MAX_VALUE_LENGTH) {
            throwErrorMessage('En fazla ' . self::MAX_VALUE_LENGTH . ' karakter.');
        }

        if ($column === 'tutar') {
            if (!is_numeric($value)) {
                throwErrorMessage('Tutar sayı olmalı.');
            }

            $value = (string)(int)$value;
        }

        $edits = Session::get(self::EDIT_KEY) ?: [];
        $edits[$rowKey][$column] = $value;
        Session::set(self::EDIT_KEY, $edits);

        /* Normalleştirilmiş değeri geri veriyoruz: kullanıcı "  1500 "
           yazdıysa hücrede kırpılmış hâlini görsün. */
        $t->respondSaved($value);
    }

    /**
     * Satır altı detay.
     *
     * Yanıt HTML'dir; kaçış burada yapılır. Gerçek bir tabloda bu genelde
     * bir view render'ıdır: render('parts/kayit-detay', ['kayit' => $kayit]).
     */
    private function detail(VnlTableRequest $t): void
    {
        $id  = (int)$t->rowKey;
        $row = null;

        foreach ($this->rows() as $candidate) {
            if ($candidate['id'] === $id) {
                $row = $candidate;
                break;
            }
        }

        if (!$row) {
            $t->respondDetail('<em>Kayıt bulunamadı.</em>');
        }

        $e = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');

        $t->respondDetail(
            '<div class="row g-4">'
            . '<div class="col-md-4"><div class="text-body-secondary fs-8">Kayıt</div>'
            . '<div class="fw-bold fs-5">' . $e($row['ad']) . '</div></div>'
            . '<div class="col-md-4"><div class="text-body-secondary fs-8">Konum</div>'
            . '<div class="fw-semibold">' . $e($row['sehir']) . '</div></div>'
            . '<div class="col-md-4"><div class="text-body-secondary fs-8">Durum</div>'
            . '<div class="fw-semibold">' . $e($row['durum']) . '</div></div>'
            . '<div class="col-12"><div class="text-body-secondary fs-8 mb-1">Hareketler</div>'
            . '<table class="table table-sm mb-0"><tbody>'
            . '<tr><td>Açılış</td><td class="text-end">' . $e(number_format($row['tutar'] * 0.4, 0, ',', '.')) . '</td></tr>'
            . '<tr><td>Ek işlem</td><td class="text-end">' . $e(number_format($row['tutar'] * 0.6, 0, ',', '.')) . '</td></tr>'
            . '<tr class="fw-bold"><td>Toplam</td><td class="text-end">' . $e(number_format($row['tutar'], 0, ',', '.')) . '</td></tr>'
            . '</tbody></table></div>'
            . '</div>'
        );
    }

    /**
     * Toplu işlem.
     *
     * $rows filtrelerden geçmiş kümedir; "hepsi" seçimi bunu kasteder.
     * Gerçek bir tabloda: applyFilters() + applySelection() ile tek sorgu.
     */
    private function bulk(VnlTableRequest $t, array $rows): void
    {
        if (!$t->hasSelection()) {
            throwErrorMessage('Hiçbir kayıt seçilmedi.');
        }

        $secili = array_values(array_filter($rows, fn($row) => $t->isSelected($row, 'id')));
        $adet   = count($secili);

        if (!$adet) {
            throwErrorMessage('Seçim hiçbir kayda denk gelmedi.');
        }

        if ($t->action === 'onayla') {
            $edits = Session::get(self::EDIT_KEY) ?: [];

            foreach ($secili as $row) {
                $edits[$row['id']]['durum'] = 'Onaylandı';
            }

            Session::set(self::EDIT_KEY, $edits);
            $t->respondBulk($adet . ' kayıt onaylandı.');
        }

        if ($t->action === 'say') {
            /* Listeyi değiştirmeyen işlem: reload=false, seçim korunur. */
            $toplam = array_sum(array_column($secili, 'tutar'));
            $t->respondBulk($adet . ' kayıt, toplam ' . number_format($toplam, 0, ',', '.') . '.', false);
        }

        throwErrorMessage('Bilinmeyen işlem: ' . $t->action);
    }

    /** Düz arama — yalnız metin sütunlarında. */
    private function applySearch(array $rows, string $search): array
    {
        if ($search === '') {
            return $rows;
        }

        $needle = TextGeneralModule::trToLower($search);

        return array_values(array_filter($rows, function ($row) use ($needle) {
            foreach (['ad', 'sehir', 'durum'] as $field) {
                if (str_contains(TextGeneralModule::trToLower((string)$row[$field]), $needle)) {
                    return true;
                }
            }

            return false;
        }));
    }

    /** Sabit tohumla üretilir; sayfalar arasında satırlar değişmez. */
    private function rows(): array
    {
        $edits = Session::get(self::EDIT_KEY) ?: [];
        $rows  = [];

        for ($i = 1; $i <= self::ROW_COUNT; $i++) {
            $row = [
                'id'    => $i,
                'ad'    => 'Örnek Kayıt ' . str_pad((string)$i, 3, '0', STR_PAD_LEFT),
                'sehir' => self::SEHIRLER[$i % count(self::SEHIRLER)],
                'durum' => self::DURUMLAR[$i % count(self::DURUMLAR)],
                'tutar' => ($i * 3779) % 48500 + 1500,
                'tarih' => date('Y-m-d', strtotime('2026-01-01 +' . ($i * 3) . ' days')),
                /* Biçimlendirici demosu. Hücre HAM 1/0 taşır; "Evet"/"Hayır"
                   çizimi istemcinin işi — sıralama, filtre ve Excel çıktısı
                   sayının kendisini görmeye devam etsin diye. */
                'aktif' => ($i % 4 === 0) ? 0 : 1,
            ];

            /* Oturumdaki düzenlemeler üretilen satırın üzerine biner. */
            foreach ($edits[$i] ?? [] as $column => $value) {
                $row[$column] = ($column === 'tutar') ? (int)$value : $value;
            }

            $rows[] = $row;
        }

        return $rows;
    }
}

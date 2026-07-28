<?php
/*!
 * ============================================================
 *  Voxnup Not Laravel (VNL) 3.0.0 — VNL Table Demo Verisi
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
 */
class action_vnltable
{
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

        /* Grup listesi: filtrelenmiş kümenin özeti. Değer listesinin
           aksine burada TÜM filtreler geçerlidir. */
        if ($t->op === VnlTableRequest::OP_GROUP) {
            $t->groupFrom($rows, ['tutar' => 'sum', 'id' => 'count']);
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

        if ($value === '') {
            throwErrorMessage('Boş bırakılamaz.');
        }

        if ($column === 'tutar') {
            if (!is_numeric($value)) {
                throwErrorMessage('Tutar sayı olmalı.');
            }

            $value = (string)(int)$value;
        }

        $edits = Session::get(self::EDIT_KEY) ?: [];
        $edits[$t->rowKey][$column] = $value;
        Session::set(self::EDIT_KEY, $edits);

        /* Normalleştirilmiş değeri geri veriyoruz: kullanıcı "  1500 "
           yazdıysa hücrede kırpılmış hâlini görsün. */
        $t->respondSaved($value);
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

        for ($i = 1; $i <= 213; $i++) {
            $row = [
                'id'    => $i,
                'ad'    => 'Örnek Kayıt ' . str_pad((string)$i, 3, '0', STR_PAD_LEFT),
                'sehir' => self::SEHIRLER[$i % count(self::SEHIRLER)],
                'durum' => self::DURUMLAR[$i % count(self::DURUMLAR)],
                'tutar' => ($i * 3779) % 48500 + 1500,
                'tarih' => date('Y-m-d', strtotime('2026-01-01 +' . ($i * 3) . ' days')),
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

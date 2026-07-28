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

    public function getList($req)
    {
        $t = VnlTableRequest::from($req);

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
        $rows = [];

        for ($i = 1; $i <= 213; $i++) {
            $rows[] = [
                'id'    => $i,
                'ad'    => 'Örnek Kayıt ' . str_pad((string)$i, 3, '0', STR_PAD_LEFT),
                'sehir' => self::SEHIRLER[$i % count(self::SEHIRLER)],
                'durum' => self::DURUMLAR[$i % count(self::DURUMLAR)],
                'tutar' => ($i * 3779) % 48500 + 1500,
                'tarih' => date('Y-m-d', strtotime('2026-01-01 +' . ($i * 3) . ' days')),
            ];
        }

        return $rows;
    }
}

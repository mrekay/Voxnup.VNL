<?php
/*!
 * ============================================================
 *  Voxnup Not Laravel (VNL) 3.0.0 — VNL Table Excel Çıktısı
 *  Bağımlılıksız xlsx üretir; zip eklentisi zorunlu değildir.
 *  Authors: Voxnup Authors
 * ============================================================
 */

/**
 * xlsx yazıcı.
 *
 * PhpSpreadsheet ve libs/excel içindeki PHPExcel, Excel2007 çıktısı için
 * ZipArchive ister; bu sınıf ondan bağımsızdır. Eklenti varsa kullanılır,
 * yoksa sıkıştırmasız (STORE) zip kabı elle yazılır — Excel ikisini de açar.
 *
 * Metinler paylaşılan dizgi tablosu yerine hücre içine gömülür
 * (t="inlineStr"): tek geçişte yazılır, tablo tutarlılığı sorunu olmaz.
 */
class VnlTableExport
{
    /** Sütun genişliği tahmininde tek karakterin yaklaşık ağırlığı. */
    private const CHAR_WIDTH = 1.15;
    private const MIN_WIDTH  = 8;
    private const MAX_WIDTH  = 60;

    /**
     * Dosyayı üretir ve indirme olarak akıtır; çağrı sonrası script biter.
     *
     * @param array $rows    satırlar; her biri sütun adı => değer
     * @param array $columns ['sutun_adi' => 'Başlık', ...] — sıra çıktıyı belirler
     */
    public static function stream(array $rows, array $columns, string $fileName = 'export'): void
    {
        $binary = self::build($rows, $columns);
        $name   = $fileName . '-' . date('Ymd-His') . '.xlsx';

        while (ob_get_level()) {
            ob_end_clean();
        }

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $name . '"');
        header('Content-Length: ' . strlen($binary));

        echo $binary;
        exit;
    }

    /** Dosyanın ikili içeriğini döner; diske yazmak ya da maile eklemek için. */
    public static function build(array $rows, array $columns): string
    {
        $files = [
            '[Content_Types].xml'         => self::contentTypes(),
            '_rels/.rels'                 => self::rootRels(),
            'xl/workbook.xml'             => self::workbook(),
            'xl/_rels/workbook.xml.rels'  => self::workbookRels(),
            'xl/styles.xml'               => self::styles(),
            'xl/worksheets/sheet1.xml'    => self::sheet($rows, $columns),
        ];

        return self::zip($files);
    }

    /* ---------------------------------------------------------------- */
    /* Sayfa                                                             */
    /* ---------------------------------------------------------------- */

    private static function sheet(array $rows, array $columns): string
    {
        $names   = array_keys($columns);
        $titles  = array_values($columns);
        $lastCol = self::columnLetter(count($names));
        $lastRow = count($rows) + 1;

        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">';

        /* Başlık satırı ekranda sabit kalsın. */
        $xml .= '<sheetViews><sheetView workbookViewId="0" tabSelected="1">'
            . '<pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/>'
            . '</sheetView></sheetViews>';

        $xml .= self::columnWidths($rows, $names, $titles);

        $xml .= '<sheetData>';

        /* Başlıklar — kalın stil (s="1"). */
        $xml .= '<row r="1">';
        foreach ($titles as $i => $title) {
            $xml .= self::cell(self::columnLetter($i + 1) . '1', $title, true);
        }
        $xml .= '</row>';

        $r = 1;
        foreach ($rows as $row) {
            $r++;
            $xml .= '<row r="' . $r . '">';
            foreach ($names as $i => $name) {
                $xml .= self::cell(self::columnLetter($i + 1) . $r, $row[$name] ?? null, false);
            }
            $xml .= '</row>';
        }

        $xml .= '</sheetData>';

        /* Başlıklara süzme okları: kullanıcı Excel'de kaldığı yerden filtreler. */
        if ($lastRow > 1) {
            $xml .= '<autoFilter ref="A1:' . $lastCol . $lastRow . '"/>';
        }

        return $xml . '</worksheet>';
    }

    /**
     * Hücre.
     *
     * Sayı gibi görünen her şey sayı yapılmaz: "007" ya da uzun kimlikler
     * geri yazıldığında değişiyorsa metin olarak bırakılır, aksi halde
     * Excel baştaki sıfırları atar.
     */
    private static function cell(string $ref, $value, bool $header): string
    {
        $style = $header ? ' s="1"' : '';

        if ($value === null || $value === '') {
            return '<c r="' . $ref . '"' . $style . '/>';
        }

        if (!$header && self::isNumeric($value)) {
            return '<c r="' . $ref . '"' . $style . '><v>' . (0 + $value) . '</v></c>';
        }

        return '<c r="' . $ref . '"' . $style . ' t="inlineStr"><is><t xml:space="preserve">'
            . self::escape($value) . '</t></is></c>';
    }

    private static function isNumeric($value): bool
    {
        if (is_int($value) || is_float($value)) {
            return true;
        }

        if (!is_string($value) || !is_numeric($value)) {
            return false;
        }

        /* Kanonik biçime dönüşmüyorsa (baştaki sıfır, artı işareti, taşan
           tam sayı) metin olarak korunur. */
        return (string)(0 + $value) === $value;
    }

    private static function columnWidths(array $rows, array $names, array $titles): string
    {
        $xml = '<cols>';

        foreach ($names as $i => $name) {
            $longest = mb_strlen((string)$titles[$i]);

            /* İlk 200 satır genişlik için yeterli örnek; tamamını taramak
               büyük listelerde çıktıyı gereksiz yavaşlatır. */
            $seen = 0;
            foreach ($rows as $row) {
                if (++$seen > 200) break;
                $len = mb_strlen((string)($row[$name] ?? ''));
                if ($len > $longest) $longest = $len;
            }

            $width = min(self::MAX_WIDTH, max(self::MIN_WIDTH, $longest * self::CHAR_WIDTH));
            $xml .= '<col min="' . ($i + 1) . '" max="' . ($i + 1) . '" width="' . round($width, 2) . '" customWidth="1"/>';
        }

        return $xml . '</cols>';
    }

    /** 1 → A, 27 → AA */
    private static function columnLetter(int $index): string
    {
        $letters = '';

        while ($index > 0) {
            $index--;
            $letters = chr(65 + ($index % 26)) . $letters;
            $index = intdiv($index, 26);
        }

        return $letters;
    }

    /** XML'de yasak olan denetim karakterleri dosyayı bozar; ayıklanır. */
    private static function escape($value): string
    {
        $text = (string)$value;
        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/u', '', $text);

        return htmlspecialchars($text, ENT_QUOTES | ENT_XML1 | ENT_SUBSTITUTE, 'UTF-8');
    }

    /* ---------------------------------------------------------------- */
    /* Kap dosyaları                                                     */
    /* ---------------------------------------------------------------- */

    private static function contentTypes(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            . '</Types>';
    }

    private static function rootRels(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '</Relationships>';
    }

    private static function workbook(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"'
            . ' xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets><sheet name="Veri" sheetId="1" r:id="rId1"/></sheets>'
            . '</workbook>';
    }

    private static function workbookRels(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            . '</Relationships>';
    }

    /** İki stil yeter: 0 varsayılan, 1 kalın (başlık satırı). */
    private static function styles(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<fonts count="2">'
            . '<font><sz val="11"/><name val="Calibri"/></font>'
            . '<font><b/><sz val="11"/><name val="Calibri"/></font>'
            . '</fonts>'
            . '<fills count="2"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill></fills>'
            . '<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="2">'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            . '<xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0" applyFont="1"/>'
            . '</cellXfs>'
            /* Normal stil kaydı olmadan bazı okuyucular "varsayılan stil yok"
               uyarısı verip kendi varsayılanını uyduruyor. */
            . '<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'
            . '</styleSheet>';
    }

    /* ---------------------------------------------------------------- */
    /* Zip                                                               */
    /* ---------------------------------------------------------------- */

    private static function zip(array $files): string
    {
        return class_exists('ZipArchive') ? self::zipWithExtension($files) : self::zipStored($files);
    }

    private static function zipWithExtension(array $files): string
    {
        $path = tempnam(sys_get_temp_dir(), 'vnlxlsx');

        $zip = new ZipArchive();
        if ($zip->open($path, ZipArchive::OVERWRITE) !== true) {
            @unlink($path);

            return self::zipStored($files);
        }

        foreach ($files as $name => $content) {
            $zip->addFromString($name, $content);
        }

        $zip->close();

        $binary = (string)file_get_contents($path);
        @unlink($path);

        return $binary;
    }

    /**
     * Sıkıştırmasız zip kabı.
     *
     * zip eklentisi olmayan sunucularda devreye girer. Yalnızca STORE
     * yöntemi kullanılır: sıkıştırma yok, dolayısıyla deflate gerekmez.
     */
    private static function zipStored(array $files): string
    {
        $time = ((int)date('H') << 11) | ((int)date('i') << 5) | ((int)date('s') >> 1);
        $date = (((int)date('Y') - 1980) << 9) | ((int)date('n') << 5) | (int)date('j');

        $body    = '';
        $central = '';
        $offset  = 0;
        $count   = 0;

        foreach ($files as $name => $content) {
            $crc  = crc32($content);
            $size = strlen($content);

            $local = pack('V', 0x04034b50)
                . pack('v', 20) . pack('v', 0) . pack('v', 0)
                . pack('v', $time) . pack('v', $date)
                . pack('V', $crc) . pack('V', $size) . pack('V', $size)
                . pack('v', strlen($name)) . pack('v', 0)
                . $name;

            $body .= $local . $content;

            $central .= pack('V', 0x02014b50)
                . pack('v', 20) . pack('v', 20) . pack('v', 0) . pack('v', 0)
                . pack('v', $time) . pack('v', $date)
                . pack('V', $crc) . pack('V', $size) . pack('V', $size)
                . pack('v', strlen($name)) . pack('v', 0) . pack('v', 0)
                . pack('v', 0) . pack('v', 0) . pack('V', 32)
                . pack('V', $offset)
                . $name;

            $offset += strlen($local) + $size;
            $count++;
        }

        $eocd = pack('V', 0x06054b50)
            . pack('v', 0) . pack('v', 0)
            . pack('v', $count) . pack('v', $count)
            . pack('V', strlen($central)) . pack('V', $offset)
            . pack('v', 0);

        return $body . $central . $eocd;
    }
}

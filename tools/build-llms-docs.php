<?php
/**
 * VNL dokümantasyonunun LLM'ler için okunabilir halini üretir.
 *
 *   llms.txt       — llms.txt standardında dizin (proje özeti + sayfa listesi)
 *   llms-full.txt  — tüm dokümantasyonun tek dosyada markdown hali
 *
 * Kaynak: view/docs/*.php (düz HTML) + view/static/leftSidebar.php (sıra ve başlıklar).
 * Çalıştırma:
 *   php tools/build-llms-docs.php
 *   docker run --rm -v "$PWD":/app -w /app php:8.2-cli php tools/build-llms-docs.php
 */

declare(strict_types=1);

$root = dirname(__DIR__);

$pages = collectPages($root);
if (empty($pages)) {
    fwrite(STDERR, "leftSidebar.php içinde docs/ girdisi bulunamadı.\n");
    exit(1);
}

$docs = [];
foreach ($pages as $page) {
    $file = $root . '/view/' . $page['slug'] . '.php';
    if (!is_file($file)) {
        fwrite(STDERR, "Atlandı (dosya yok): {$page['slug']}\n");
        continue;
    }

    $html = file_get_contents($file);
    $html = stripPhpBlocks($html);

    $doc = parsePage($html);
    $docs[] = $page + $doc;
}

file_put_contents($root . '/llms.txt', buildIndex($docs));
file_put_contents($root . '/llms-full.txt', buildFull($docs));

printf(
    "llms.txt (%s) ve llms-full.txt (%s) üretildi — %d sayfa.\n",
    humanSize(filesize($root . '/llms.txt')),
    humanSize(filesize($root . '/llms-full.txt')),
    count($docs)
);

/* ------------------------------------------------------------------ *
 * Sayfa listesi
 * ------------------------------------------------------------------ */

/**
 * Sidebar'daki sırayı korur; "text" => ... , 'url' => 'docs/...' ikilisini yakalar.
 * Sidebar tek kaynak olduğu için yeni sayfa eklendiğinde burası kendiliğinden büyür.
 */
function collectPages(string $root): array
{
    $src = file_get_contents($root . '/view/static/leftSidebar.php');

    preg_match_all(
        '/"text"\s*=>\s*"([^"]*)"\s*,\s*\'url\'\s*=>\s*\'(docs\/[a-z0-9\-]+)\'/u',
        $src,
        $m,
        PREG_SET_ORDER
    );

    $pages = [];
    foreach ($m as $hit) {
        $pages[] = [
            'menu'  => html_entity_decode(trim($hit[1]), ENT_QUOTES, 'UTF-8'),
            'slug'  => $hit[2],
            'group' => str_starts_with($hit[2], 'docs/theme') ? 'VNL Theme El Kitabı' : 'Çekirdek',
        ];
    }

    return $pages;
}

/** Docs view'larındaki birkaç PHP demo bloğu çıktıya girmemeli. */
function stripPhpBlocks(string $html): string
{
    return preg_replace('/<\?(php|=).*?(\?>|$)/s', '', $html) ?? $html;
}

/* ------------------------------------------------------------------ *
 * HTML → Markdown
 * ------------------------------------------------------------------ */

function parsePage(string $html): array
{
    $dom = new DOMDocument();
    libxml_use_internal_errors(true);
    $dom->loadHTML(
        '<?xml encoding="UTF-8">' . $html,
        LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
    );
    libxml_clear_errors();

    $xpath = new DOMXPath($dom);

    $title = '';
    $lead  = '';

    $h1 = $xpath->query('//h1')->item(0);
    if ($h1) {
        $title = normalizeSpace($h1->textContent);
    }

    $leadNode = $xpath->query("//*[contains(concat(' ', normalize-space(@class), ' '), ' vnl-lead ')]")->item(0);
    if ($leadNode) {
        $lead = normalizeSpace($leadNode->textContent);
    }

    $body = '';
    foreach ($dom->childNodes as $child) {
        $body .= renderNode($child);
    }

    return [
        'title' => $title,
        'lead'  => $lead,
        'body'  => tidy($body),
    ];
}

function renderNode(DOMNode $node): string
{
    if ($node instanceof DOMText) {
        // Kod blokları dışında beyaz boşluk anlamsızdır.
        return preg_replace('/\s+/u', ' ', $node->nodeValue) ?? '';
    }

    if (!$node instanceof DOMElement) {
        return '';
    }

    $tag   = strtolower($node->nodeName);
    $class = ' ' . normalizeSpace($node->getAttribute('class')) . ' ';

    // İkonlar, script/style ve canlı demo alanları LLM çıktısına girmez.
    if (in_array($tag, ['script', 'style', 'svg', 'i', 'button', 'img'], true)) {
        return '';
    }

    // Hero'daki başlık ve lead sayfa girişinde ayrıca basılıyor.
    if (str_contains($class, ' vnl-page-hero ')) {
        return '';
    }

    // .vnl-code hem <pre> sarmalı hem doğrudan <code> ile kullanılıyor.
    // İçteki pre/code hedeflenir; sarmalayıcının textContent'i HTML girintisi taşır.
    if (str_contains($class, ' vnl-code ')) {
        $xp    = new DOMXPath($node->ownerDocument);
        $inner = $xp->query('.//pre|.//code', $node)->item(0);

        return renderCode(($inner ?? $node)->textContent);
    }

    // Terminal komutları <pre> yerine monospace span ile yazılmış olabiliyor.
    if (str_contains($class, ' font-monospace ')) {
        $text = normalizeSpace($node->textContent);
        return $text === '' ? '' : '`' . $text . '`';
    }

    if (str_contains($class, ' vnl-demo ')) {
        $label = '';
        $xp    = new DOMXPath($node->ownerDocument);
        $lbl   = $xp->query(".//*[contains(concat(' ', normalize-space(@class), ' '), ' vnl-demo-label ')]", $node)->item(0);
        if ($lbl) {
            $label = normalizeSpace($lbl->textContent);
        }

        return $label !== '' ? "\n\n_(canlı demo: {$label})_\n\n" : '';
    }

    switch ($tag) {
        case 'h1':
        case 'h2':
        case 'h3':
        case 'h4':
        case 'h5':
        case 'h6':
            $text = inline($node);
            if ($text === '') {
                // PHP demo blokları çıkarıldığında başlık boş kalabiliyor.
                return '';
            }
            $level = ['h1' => '#', 'h2' => '###', 'h3' => '###', 'h4' => '####'][$tag] ?? '#####';

            return "\n\n{$level} {$text}\n\n";

        case 'pre':
            return renderCode($node->textContent);

        case 'code':
            // <pre><code> zaten yukarıda yakalandı; buraya yalnız satır içi kod düşer.
            $text = normalizeSpace($node->textContent);
            return $text === '' ? '' : '`' . $text . '`';

        case 'table':
            return renderTable($node);

        case 'ul':
        case 'ol':
            return renderList($node, $tag === 'ol');

        case 'br':
            return "\n";

        case 'hr':
            return "\n\n---\n\n";

        case 'strong':
        case 'b':
            $text = trim(children($node));
            return $text === '' ? '' : '**' . $text . '**';

        case 'em':
            $text = trim(children($node));
            return $text === '' ? '' : '_' . $text . '_';

        case 'a':
            $text = trim(children($node));
            $href = $node->getAttribute('href');
            if ($text === '') {
                return '';
            }
            return ($href === '' || str_starts_with($href, '#')) ? $text : "[{$text}]({$href})";

        case 'span':
        case 'small':
            return children($node);
    }

    // .separator → yatay çizgi; docs sayfalarında bölüm ayracı olarak kullanılıyor.
    if (str_contains($class, ' separator ')) {
        return "\n\n---\n\n";
    }

    // Uyarı/bilgi kutuları alıntı bloğuna dönüşür.
    if (str_contains($class, ' alert ')) {
        $inner = tidy(children($node));
        if (trim($inner) === '') {
            return '';
        }
        // Kutu başlıkları alıntı içinde bölüm başlığı gibi durmasın.
        $inner  = preg_replace('/^#{1,6} +(.+)$/m', '**$1**', $inner);
        $quoted = preg_replace('/^/m', '> ', $inner);

        return "\n\n" . $quoted . "\n\n";
    }

    // Kalan her şey (div, p, td dışı bloklar) blok kabul edilir.
    return "\n" . children($node) . "\n";
}

function children(DOMNode $node): string
{
    $out = '';
    foreach ($node->childNodes as $child) {
        $out .= renderNode($child);
    }

    return $out;
}

/** Başlık ve hücre gibi tek satırlık bağlamlar için düzleştirilmiş içerik. */
function inline(DOMNode $node): string
{
    return normalizeSpace(str_replace(["\n", "\r"], ' ', children($node)));
}

function renderCode(string $code): string
{
    $code = str_replace("\r\n", "\n", $code);
    $code = trim($code, "\n");
    $code = rtrim($code);

    if (trim($code) === '') {
        return '';
    }

    $code = dedent($code);

    return "\n\n```" . detectLanguage($code) . "\n" . $code . "\n```\n\n";
}

/**
 * <pre> içeriği HTML girintisini taşıyabiliyor; ortak boşluk payı düşülür.
 * İlk satır açılış etiketiyle aynı satırda yazıldığı için ayrı ele alınır.
 */
function dedent(string $code): string
{
    $lines = explode("\n", $code);
    $first = ltrim(array_shift($lines) ?? '', " \t");

    $indent = null;
    foreach ($lines as $line) {
        if (trim($line) === '') {
            continue;
        }
        $width  = strlen($line) - strlen(ltrim($line, ' '));
        $indent = $indent === null ? $width : min($indent, $width);
    }

    if ($indent) {
        $lines = array_map(
            static fn(string $line): string => substr($line, $indent),
            $lines
        );
    }

    return implode("\n", array_merge([$first], $lines));
}

function detectLanguage(string $code): string
{
    // Zincirleme çağrı (->make()) ve 'anahtar' => değer, VNL örneklerinde PHP'ye özgü.
    if (
        str_contains($code, '<?php')
        || preg_match('/\$\w+\s*(=|->|\[|\))/', $code)
        || preg_match('/->\w+\(/', $code)
        || preg_match('/[\'"][\w.\-]+[\'"]\s*=>/', $code)
        || preg_match('/\b[A-Z]\w+::\w/', $code)
        || preg_match('/^\s*(require|include|echo|throw new|namespace|use )/m', $code)
    ) {
        return 'php';
    }
    if (preg_match('/[{\[]/', $code) && preg_match('/"[\w.\-]+"\s*:/', $code)) {
        return 'json';
    }
    if (preg_match('/^\s*(#|npm |git |php |docker|composer|cd |mkdir|curl |cp |mv |mysql |chmod |node |sudo )/m', $code)) {
        return 'bash';
    }
    if (preg_match('/<\/?[a-z][a-z0-9-]*[\s>]/i', $code)) {
        return 'html';
    }
    if (preg_match('/(function\s*\(|\)\s*=>|=>\s*[{(]|\bconst |\blet |document\.|window\.|\bVNL\.|\w\)\.\w+\()/', $code)) {
        return 'js';
    }
    if (preg_match('/[.#&:\[][\w-]+\s*\{|--vnl-|@media|@use|@import/', $code)) {
        return 'css';
    }

    return '';
}

function renderTable(DOMElement $table): string
{
    $xp   = new DOMXPath($table->ownerDocument);
    $rows = [];

    foreach ($xp->query('.//tr', $table) as $tr) {
        $cells = [];
        foreach ($xp->query('./th|./td', $tr) as $cell) {
            $text = inline($cell);
            // Satır içi boru işareti markdown tabloyu bozar.
            $cells[] = str_replace('|', '\\|', $text);
        }
        if ($cells !== []) {
            $rows[] = $cells;
        }
    }

    if ($rows === []) {
        return '';
    }

    $width = max(array_map('count', $rows));
    foreach ($rows as &$row) {
        $row = array_pad($row, $width, '');
    }
    unset($row);

    $head = array_shift($rows);

    $out = "\n\n| " . implode(' | ', $head) . " |\n";
    $out .= '| ' . implode(' | ', array_fill(0, $width, '---')) . " |\n";
    foreach ($rows as $row) {
        $out .= '| ' . implode(' | ', $row) . " |\n";
    }

    return $out . "\n";
}

function renderList(DOMElement $list, bool $ordered): string
{
    $out = "\n\n";
    $i   = 1;

    foreach ($list->childNodes as $li) {
        if (!$li instanceof DOMElement || strtolower($li->nodeName) !== 'li') {
            continue;
        }

        $content = tidy(children($li));
        if (trim($content) === '') {
            continue;
        }

        $marker = $ordered ? ($i++ . '.') : '-';
        $lines  = explode("\n", $content);
        $first  = array_shift($lines);

        $out .= $marker . ' ' . $first . "\n";
        foreach ($lines as $line) {
            $out .= ($line === '' ? '' : '  ' . $line) . "\n";
        }
    }

    return $out . "\n";
}

/* ------------------------------------------------------------------ *
 * Çıktı
 * ------------------------------------------------------------------ */

function buildIndex(array $docs): string
{
    $out = "# VNL\n\n";
    $out .= "> VNL (Voxnup Not Laravel), klasik MVC üzerine kurulu, Eloquent/Capsule ORM kullanan "
        . "ve Bootstrap 5.3 tabanlı kendi tema katmanı (VNL Theme) ile gelen bir PHP microframework'üdür. "
        . "Rota tanımı yoktur: URL'nin ilk segmenti doğrudan `controller/` altındaki dosyayı çalıştırır.\n\n";

    $out .= "VNL 3.0 · PHP 8.2+ · Bootstrap 5.3.8 · dokümantasyon dili Türkçe.\n\n";
    $out .= "Tüm dokümantasyonun tek dosyadaki tam hali: [llms-full.txt](/llms-full.txt)\n\n";

    $groups = [];
    foreach ($docs as $doc) {
        $groups[$doc['group']][] = $doc;
    }

    foreach ($groups as $group => $items) {
        $out .= "## {$group}\n\n";
        foreach ($items as $doc) {
            $title = $doc['title'] !== '' ? $doc['title'] : $doc['menu'];
            $desc  = firstSentence($doc['lead']);
            $out .= "- [{$title}](/{$doc['slug']})" . ($desc !== '' ? ": {$desc}" : '') . "\n";
        }
        $out .= "\n";
    }

    return $out;
}

function buildFull(array $docs): string
{
    $out = "# VNL — Tam Dokümantasyon\n\n";
    $out .= "> VNL (Voxnup Not Laravel), klasik MVC üzerine kurulu, Eloquent/Capsule ORM kullanan "
        . "ve Bootstrap 5.3 tabanlı kendi tema katmanı (VNL Theme) ile gelen bir PHP microframework'üdür.\n\n";
    $out .= "Bu dosya `view/docs/` altındaki dokümantasyon sayfalarından üretilmiştir "
        . "(`php tools/build-llms-docs.php`). Kaynak sayfalar `/docs/<slug>` adresinden de görüntülenebilir.\n\n";

    $out .= "## İçindekiler\n\n";
    foreach ($docs as $doc) {
        $title = $doc['title'] !== '' ? $doc['title'] : $doc['menu'];
        $out .= "- {$title} — `/{$doc['slug']}`\n";
    }
    $out .= "\n---\n\n";

    foreach ($docs as $doc) {
        $title = $doc['title'] !== '' ? $doc['title'] : $doc['menu'];

        $out .= "# {$title}\n\n";
        $out .= "Kaynak: `/{$doc['slug']}` · `view/{$doc['slug']}.php`\n\n";
        if ($doc['lead'] !== '') {
            $out .= "> {$doc['lead']}\n\n";
        }

        $out .= $doc['body'] . "\n\n---\n\n";
    }

    return $out;
}

/* ------------------------------------------------------------------ *
 * Yardımcılar
 * ------------------------------------------------------------------ */

function tidy(string $md): string
{
    $md = str_replace("\r\n", "\n", $md);

    // Satır sonu boşlukları ve yalnızca boşluktan oluşan satırlar.
    $md = preg_replace('/[ \t]+$/m', '', $md);
    $md = preg_replace('/^[ \t]+$/m', '', $md);

    // Kod bloklarının dışında kalan çoklu boşluk.
    $parts = preg_split('/(```.*?```)/s', $md, -1, PREG_SPLIT_DELIM_CAPTURE);
    $md    = '';
    foreach ($parts as $part) {
        if (str_starts_with($part, '```')) {
            $md .= $part;
            continue;
        }
        // Satır içi fazlalık; satır başı girintisi (liste devamı) korunur.
        $part = preg_replace('/(?<=\S)[ \t]{2,}/', ' ', $part);
        // HTML girintisinden artakalan tek boşluk — iki boşluk liste devamıdır.
        $part = preg_replace('/^ (?=\S)/m', '', $part);
        // Kaynakta boşluk yerine margin ile ayrılmış "**Başlık**— açıklama" kalıbı.
        $part = preg_replace('/\*\*(?=[—–])/', '** ', $part);
        $part = preg_replace('/\n{3,}/', "\n\n", $part);
        $md .= $part;
    }

    // Alıntı bloğu içindeki boş satır ">" kalmalı; aksi halde blok ikiye ayrılır.
    $md = preg_replace('/^>[ \t]*$/m', '>', $md);
    $md = preg_replace('/(\n---\n\s*){2,}/', "\n---\n", $md);
    $md = preg_replace('/\n{3,}/', "\n\n", $md);

    return trim($md);
}

function normalizeSpace(string $text): string
{
    return trim(preg_replace('/\s+/u', ' ', $text) ?? $text);
}

function firstSentence(string $text): string
{
    $text = normalizeSpace($text);
    if ($text === '') {
        return '';
    }

    if (preg_match('/^(.{20,200}?[.!?])(\s|$)/u', $text, $m)) {
        return trim($m[1]);
    }

    return mb_strlen($text) > 200 ? mb_substr($text, 0, 197) . '…' : $text;
}

function humanSize(int $bytes): string
{
    return $bytes >= 1024 * 1024
        ? round($bytes / 1024 / 1024, 1) . ' MB'
        : round($bytes / 1024) . ' KB';
}

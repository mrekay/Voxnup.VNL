/**
 * Keenicons (ki-duotone / ki-outline / ki-solid) → Font Awesome Free codemod.
 *
 *   node tools/migrate-icons.mjs [--dry]
 *
 * - `<i class="ki-duotone ki-X …">` → `<i class="fa-solid fa-Y …">`; diğer
 *   attribute'lar ve utility class'ları korunur.
 * - Duotone gövdesindeki `<span class="pathN"></span>` çocukları silinir.
 * - Docs sayfalarındaki HTML-escape'li örnek kodlar (`&lt;i class="ki-…`)
 *   dahil edilir — örnek kod da yeni temayı öğretmeli.
 * - Eşlemesi olmayan ikon adı bulunursa dosya adıyla raporlanır ve
 *   fa-circle-question ile işaretlenir (görsel olarak hemen yakalanır).
 *
 * Tek seferlik migrasyon aracı; kayıt olarak repo'da tutulur.
 */

import { readFileSync, writeFileSync } from 'node:fs';
import { join, dirname, relative } from 'node:path';
import { fileURLToPath } from 'node:url';
import { globSync } from 'node:fs';

const root = join(dirname(fileURLToPath(import.meta.url)), '..', '..');
const dry = process.argv.includes('--dry');

const SCOPE = [
    'view/index.php',
    'view/docs/*.php',
    'view/includes/modals/example/*.php',
    'extensions/stripe/view/*.php',
    'extensions/stripe/controller/*.php',
];

const MAP = {
    'abstract-14': 'terminal',
    'abstract-26': 'cubes',
    'arrow-right': 'arrow-right',
    'arrows-circle': 'arrows-rotate',
    'arrows-loop': 'arrows-rotate',
    'bars': 'bars',
    'burger-menu': 'bars',
    'burger-menu-2': 'bars-staggered',
    'check-circle': 'circle-check',
    'cloud': 'cloud',
    'code': 'code',
    'color-swatch': 'palette',
    'credit-cart': 'credit-card',
    'cross': 'xmark',
    'cross-circle': 'circle-xmark',
    'data': 'database',
    'directbox-default': 'inbox',
    'document': 'file-lines',
    'double-left': 'angles-left',
    'eye': 'eye',
    'file': 'file',
    'file-down': 'file-arrow-down',
    'folder': 'folder',
    'globe': 'globe',
    'home': 'house',
    'information': 'circle-info',
    'information-5': 'circle-info',
    'lightbulb': 'lightbulb',
    'lock': 'lock',
    'magnifier': 'magnifying-glass',
    'message-text-2': 'message',
    'moon': 'moon',
    'night-day': 'circle-half-stroke',
    'notification-on': 'bell',
    'pencil': 'pencil',
    'plus': 'plus',
    'price-tag': 'tag',
    'question-2': 'circle-question',
    'right': 'chevron-right',
    'screen': 'display',
    'search-list': 'list-check',
    'send': 'paper-plane',
    'setting-2': 'gear',
    'shield': 'shield',
    'shield-tick': 'shield-halved',
    'cross-square': 'square-xmark',
};

const unknown = new Set();
let totalIcons = 0;
let totalSpans = 0;

const files = SCOPE.flatMap((pattern) => globSync(pattern, { cwd: root }));

for (const file of files) {
    const path = join(root, file);
    const before = readFileSync(path, 'utf8');
    let after = before;

    // 1) Duotone path span'ları (normal + escaped biçim)
    after = after.replace(/[ \t]*<span class="path\d+"><\/span>(\r?\n)?/g, () => {
        totalSpans++;
        return '';
    });
    after = after.replace(/[ \t]*&lt;span class="path\d+"&gt;&lt;\/span&gt;(\r?\n)?/g, () => {
        totalSpans++;
        return '';
    });

    // 2) Sınıf değişimi: ki-duotone|outline|solid ki-X → fa-solid fa-Y
    after = after.replace(/ki-(?:duotone|outline|solid)\s+ki-([a-z0-9-]+)/g, (match, name) => {
        totalIcons++;
        const fa = MAP[name];
        if (!fa) {
            unknown.add(`${name} (${file})`);
            return 'fa-solid fa-circle-question';
        }
        return `fa-solid fa-${fa}`;
    });

    if (after !== before && !dry) {
        writeFileSync(path, after);
    }
    if (after !== before) {
        console.log(`${dry ? '[dry] ' : ''}${relative(root, path)}`);
    }
}

console.log(`\n${totalIcons} ikon dönüştürüldü, ${totalSpans} path span'ı silindi.`);
if (unknown.size) {
    console.error('\nEŞLEMESİ OLMAYAN İKONLAR (fa-circle-question basıldı):');
    unknown.forEach((u) => console.error('  - ' + u));
    process.exitCode = 1;
}

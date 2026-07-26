/*!
 * ============================================================
 *  Voxnup Not Laravel (VNL) — VNL Theme 1.0
 *  Migrasyon codemod'u: Metronic utility siniflari
 *  Authors: Voxnup Authors
 * ============================================================
 */
/**
 * Metronic utility sınıfı sweep'i.
 *
 *   node tools/sweep-utilities.mjs a [--dry]   → FAZ A (eski Metronic CSS'i
 *     altında da doğru render olan, Bootstrap 5.3 native dönüşümler)
 *   node tools/sweep-utilities.mjs b [--dry]   → FAZ B (hedef sınıflar yalnız
 *     VNL Theme'de var; atomik takas commit'inde koşulur)
 *
 * Dönüşümler HTML-escape'li docs örneklerinde de çalışır (sınıf adları
 * escape'ten etkilenmez). Tek seferlik migrasyon aracı; kayıt olarak repoda.
 */

import { readFileSync, writeFileSync, globSync } from 'node:fs';
import { join, dirname, relative } from 'node:path';
import { fileURLToPath } from 'node:url';

const root = join(dirname(fileURLToPath(import.meta.url)), '..', '..');
const phase = process.argv[2];
const dry = process.argv.includes('--dry');

const SCOPE = [
    'view/index.php',
    'view/docs/*.php',
    'view/includes/modals/example/*.php',
    'extensions/stripe/view/*.php',
    'extensions/stripe/controller/*.php',
    'extensions/stripe/config.php',
];

const PHASE_A = [
    [/\btext-gray-900\b/g, 'text-body-emphasis'],
    [/\btext-gray-[78]00\b/g, 'text-body'],
    [/\btext-gray-[56]00\b/g, 'text-body-secondary'],
    [/\btext-gray-[34]00\b/g, 'text-body-tertiary'],
    [/\btext-dark\b/g, 'text-body-emphasis'],
    [/\bfw-bolder\b/g, 'fw-bold'],
    [/\bbg-light-(primary|secondary|success|danger|warning|info|dark)\b/g, 'bg-$1-subtle'],
    [/\bbg-light\b(?!-)/g, 'bg-body-tertiary'],
    [/\bbg-secondary\b/g, 'bg-body-secondary'],
    // border rengi utility'si: BS'te kenarlıklar zaten adaptif --bs-border-color
    [/ ?\bborder-gray-\d+\b/g, ''],
];

const PHASE_B = [
    [/\bbadge-light-(primary|secondary|success|danger|warning|info|dark)\b/g, 'badge-soft-$1'],
    [/\bbtn-light-(primary|secondary|success|danger|warning|info|dark)\b/g, 'btn-soft-$1'],
    // Kod blokları → .vnl-code (hem div sarmalayıcı hem doğrudan pre biçimi)
    [/\bbg-dark text-light rounded p-\d+ fs-\d+\b/g, 'vnl-code'],
    [/\bbg-dark rounded p-\d+\b/g, 'vnl-code'],
    [/\btext-(?:white|light) fs-\d font-monospace mb-0\b/g, 'mb-0'],
    [/ ?\btext-(?:white|light) fs-\d font-monospace\b/g, ''],
];

const rules = phase === 'a' ? PHASE_A : phase === 'b' ? PHASE_B : null;
if (!rules) {
    console.error('Kullanım: node tools/sweep-utilities.mjs <a|b> [--dry]');
    process.exit(1);
}

let totalHits = 0;
const files = SCOPE.flatMap((pattern) => globSync(pattern, { cwd: root }));

for (const file of files) {
    const path = join(root, file);
    const before = readFileSync(path, 'utf8');
    let after = before;
    let hits = 0;

    for (const [pattern, replacement] of rules) {
        after = after.replace(pattern, (...args) => {
            hits++;
            if (typeof replacement === 'string' && replacement.includes('$1')) {
                return replacement.replace('$1', args[1]);
            }
            return replacement;
        });
    }

    if (hits > 0) {
        totalHits += hits;
        console.log(`${dry ? '[dry] ' : ''}${relative(root, path)} — ${hits}`);
        if (!dry) writeFileSync(path, after);
    }
}

console.log(`\nFAZ ${phase.toUpperCase()}: ${totalHits} dönüşüm.`);

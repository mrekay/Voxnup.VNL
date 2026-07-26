/*!
 * ============================================================
 *  Voxnup Not Laravel (VNL) — VNL Theme 1.0
 *  Migrasyon codemod'u: sayfa hero duzeni
 *  Authors: Voxnup Authors
 * ============================================================
 */
/**
 * Sayfa hero'larını VNL Theme kimliğine çevirir (tek seferlik codemod).
 *
 *   node tools/sweep-hero.mjs [--dry]
 *
 * Eski (Metronic kalıbı):
 *   <div class="text-center mb-15 mt-10">
 *       <h1 class="fs-2hx fw-bold mb-5 text-body-emphasis">Başlık</h1>
 *       <div class="fs-5 text-muted fw-semibold ...">Açıklama</div>
 *   </div>
 *
 * Yeni: sola yaslı, mono overline'lı (`// docs/routing`), ölçülü lead.
 */

import { readFileSync, writeFileSync, globSync } from 'node:fs';
import { join, dirname, relative, basename } from 'node:path';
import { fileURLToPath } from 'node:url';

const root = join(dirname(fileURLToPath(import.meta.url)), '..', '..');
const dry = process.argv.includes('--dry');

const SCOPE = ['view/index.php', 'view/docs/*.php'];

const HERO = new RegExp(
    '<div class="text-center mb-15 mt-10">\\s*' +
    '<h1 class="fs-2hx fw-bold mb-5 text-body-emphasis">([\\s\\S]*?)</h1>\\s*' +
    '<div class="fs-5 text-muted fw-semibold[^"]*">([\\s\\S]*?)</div>\\s*' +
    '</div>');

let total = 0;
const files = SCOPE.flatMap((pattern) => globSync(pattern, { cwd: root }));

for (const file of files) {
    const path = join(root, file);
    const before = readFileSync(path, 'utf8');

    const slug = file.includes('docs')
        ? 'docs/' + basename(file, '.php')
        : basename(file, '.php');

    const after = before.replace(HERO, (m, title, lead) =>
        '<div class="vnl-page-hero mb-12 mt-6">\n' +
        `                <div class="vnl-overline mb-3">// ${slug}</div>\n` +
        `                <h1 class="fs-2hx fw-bold mb-4 text-body-emphasis">${title.trim()}</h1>\n` +
        `                <div class="vnl-lead">${lead.trim()}</div>\n` +
        '            </div>');

    if (after !== before) {
        total++;
        console.log(`${dry ? '[dry] ' : ''}${relative(root, path)} → // ${slug}`);
        if (!dry) writeFileSync(path, after);
    } else {
        console.warn(`UYARI: hero deseni bulunamadı — ${file}`);
    }
}

console.log(`\n${total}/${files.length} hero dönüştürüldü.`);

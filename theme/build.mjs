/*!
 * ============================================================
 *  Voxnup Not Laravel (VNL) — VNL Theme 1.0
 *  Build gorevleri: vendor concat + esbuild + font kopyalama
 *  Authors: Voxnup Authors
 * ============================================================
 */
/**
 * VNL Theme build görevleri (CSS hariç — o sass CLI ile derlenir).
 *
 *   node build.mjs js        → vendors.min.js (dist concat) + vnl.min.js (esbuild)
 *   node build.mjs js --watch
 *   node build.mjs assets    → font dosyalarını assets/vnl/fonts/ altına kopyalar
 *
 * Vendor JS'leri bilinçli olarak bundler'dan GEÇİRİLMEZ: jquery/bootstrap/
 * sweetalert2/slim-select'in hazır IIFE/UMD dist'leri noktalı virgülle
 * birleştirilir; window globalleri ($, bootstrap, Swal, SlimSelect)
 * olduğu gibi korunur.
 */

import { build, context } from 'esbuild';
import { mkdirSync, readFileSync, writeFileSync, copyFileSync, readdirSync, existsSync } from 'node:fs';
import { dirname, join, basename } from 'node:path';
import { fileURLToPath } from 'node:url';
import * as sass from 'sass';

const root = dirname(fileURLToPath(import.meta.url));
const out = join(root, '..', 'assets', 'vnl');

const VENDORS = [
    'node_modules/jquery/dist/jquery.min.js',
    'node_modules/bootstrap/dist/js/bootstrap.bundle.min.js',
    'node_modules/sweetalert2/dist/sweetalert2.min.js',
    'node_modules/slim-select/dist/slimselect.iife.js',
];

const FONTS = [
    ['node_modules/@fontsource-variable/inter/files/inter-latin-wght-normal.woff2', 'fonts/inter/inter-latin-wght-normal.woff2'],
    ['node_modules/@fontsource-variable/inter/files/inter-latin-ext-wght-normal.woff2', 'fonts/inter/inter-latin-ext-wght-normal.woff2'],
    ['node_modules/@fontsource-variable/jetbrains-mono/files/jetbrains-mono-latin-wght-normal.woff2', 'fonts/jetbrains-mono/jetbrains-mono-latin-wght-normal.woff2'],
    ['node_modules/@fontsource-variable/jetbrains-mono/files/jetbrains-mono-latin-ext-wght-normal.woff2', 'fonts/jetbrains-mono/jetbrains-mono-latin-ext-wght-normal.woff2'],
    ['node_modules/@fortawesome/fontawesome-free/webfonts/fa-solid-900.woff2', 'fonts/fa/fa-solid-900.woff2'],
    ['node_modules/@fortawesome/fontawesome-free/webfonts/fa-regular-400.woff2', 'fonts/fa/fa-regular-400.woff2'],
    ['node_modules/@fortawesome/fontawesome-free/webfonts/fa-brands-400.woff2', 'fonts/fa/fa-brands-400.woff2'],
    ['node_modules/@fortawesome/fontawesome-free/webfonts/fa-v4compatibility.woff2', 'fonts/fa/fa-v4compatibility.woff2'],
    ['node_modules/bootstrap-icons/font/fonts/bootstrap-icons.woff2', 'fonts/bi/bootstrap-icons.woff2'],
    ['node_modules/bootstrap-icons/font/fonts/bootstrap-icons.woff', 'fonts/bi/bootstrap-icons.woff'],
];

const task = process.argv[2];
const watch = process.argv.includes('--watch');

if (task === 'js') {
    await buildVendors();
    await buildThemeJs();
} else if (task === 'assets') {
    copyAssets();
} else if (task === 'skins') {
    buildSkins();
} else {
    console.error('Kullanım: node build.mjs <js|assets|skins> [--watch]');
    process.exit(1);
}

// theme/scss/skins/ altındaki her .scss dosyası (partial'lar hariç) hafif bir
// skin katmanı olarak derlenir: dosya at → build → assets/vnl/css/skins/<ad>.min.css
function buildSkins() {
    const dir = join(root, 'scss', 'skins');
    if (!existsSync(dir)) {
        console.log('· skin yok (theme/scss/skins boş)');
        return;
    }

    const outDir = join(out, 'css', 'skins');
    mkdirSync(outDir, { recursive: true });

    let count = 0;
    for (const file of readdirSync(dir)) {
        if (!file.endsWith('.scss') || file.startsWith('_')) continue;

        const result = sass.compile(join(dir, file), {
            style: 'compressed',
            quietDeps: true,
            silenceDeprecations: ['import', 'global-builtin', 'color-functions', 'if-function'],
        });
        const name = basename(file, '.scss');
        writeFileSync(join(outDir, name + '.min.css'), result.css + '\n');
        count++;
        console.log(`✓ css/skins/${name}.min.css`);
    }
    if (count === 0) console.log('· skin yok (theme/scss/skins boş)');
}

async function buildVendors() {
    mkdirSync(join(out, 'js'), { recursive: true });

    const versions = readVersions(['jquery', 'bootstrap', 'sweetalert2', 'slim-select']);
    const banner = `/*! VNL Theme vendors — ${versions.join(', ')} */`;
    const body = VENDORS
        .map((file) => readFileSync(join(root, file), 'utf8').trim())
        .join('\n;\n');

    writeFileSync(join(out, 'js', 'vendors.min.js'), `${banner}\n${body}\n`);
    console.log('✓ js/vendors.min.js (' + VENDORS.length + ' paket)');
}

async function buildThemeJs() {
    const options = {
        entryPoints: [join(root, 'js', 'vnl-theme.js')],
        outfile: join(out, 'js', 'vnl.min.js'),
        bundle: true,
        minify: true,
        format: 'iife',
        target: ['es2018'],
        legalComments: 'inline',
    };

    if (watch) {
        const ctx = await context(options);
        await ctx.watch();
        console.log('… vnl.min.js watch modunda');
    } else {
        await build(options);
        console.log('✓ js/vnl.min.js');
    }
}

function copyAssets() {
    for (const [src, dest] of FONTS) {
        const target = join(out, dest);
        mkdirSync(dirname(target), { recursive: true });
        copyFileSync(join(root, src), target);
    }
    console.log('✓ fonts (' + FONTS.length + ' dosya)');
}

function readVersions(packages) {
    return packages.map((name) => {
        const pkg = JSON.parse(readFileSync(join(root, 'node_modules', name, 'package.json'), 'utf8'));
        return `${name} ${pkg.version}`;
    });
}

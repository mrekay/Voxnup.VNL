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
import { mkdirSync, readFileSync, writeFileSync, copyFileSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';

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
    ['node_modules/@fortawesome/fontawesome-free/webfonts/fa-solid-900.woff2', 'fonts/fa/fa-solid-900.woff2'],
    ['node_modules/@fortawesome/fontawesome-free/webfonts/fa-regular-400.woff2', 'fonts/fa/fa-regular-400.woff2'],
    ['node_modules/@fortawesome/fontawesome-free/webfonts/fa-brands-400.woff2', 'fonts/fa/fa-brands-400.woff2'],
    ['node_modules/@fortawesome/fontawesome-free/webfonts/fa-v4compatibility.woff2', 'fonts/fa/fa-v4compatibility.woff2'],
];

const task = process.argv[2];
const watch = process.argv.includes('--watch');

if (task === 'js') {
    await buildVendors();
    await buildThemeJs();
} else if (task === 'assets') {
    copyAssets();
} else {
    console.error('Kullanım: node build.mjs <js|assets> [--watch]');
    process.exit(1);
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

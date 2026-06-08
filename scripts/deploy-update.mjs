/**
 * deploy-update.mjs
 * Build + zip du bundle React pour les mises à jour OTA via @capgo/capacitor-updater
 *
 * Usage :
 *   npm run deploy:update            → build + zip + génère latest.json
 *   npm run deploy:update -- --skip-build  → zip uniquement (dist/ doit exister)
 */

import { createWriteStream, existsSync } from 'node:fs';
import { readFile, writeFile, mkdir, rm } from 'node:fs/promises';
import { execSync } from 'node:child_process';
import { fileURLToPath } from 'node:url';
import path from 'node:path';
import archiver from 'archiver';

const __dirname  = path.dirname(fileURLToPath(import.meta.url));
const ROOT       = path.resolve(__dirname, '..');
const UPDATES_DIR = path.join(ROOT, 'app', 'public', 'updates');
const BASE_URL   = 'http://saasapp.s196298.fvl-001.webo-facto.com';

const skipBuild = process.argv.includes('--skip-build');

// ── 1. Lire la version depuis package.json ──────────────────────
const pkg     = JSON.parse(await readFile(path.join(ROOT, 'package.json'), 'utf8'));
const version = pkg.version;
console.log(`\n📦 Version : ${version}`);

// ── 2. Build ─────────────────────────────────────────────────────
if (!skipBuild) {
  console.log('🔨 Build en cours…');
  execSync('npm run build', { cwd: ROOT, stdio: 'inherit' });
} else {
  console.log('⏭  Build ignoré (--skip-build)');
}

if (!existsSync(path.join(ROOT, 'dist'))) {
  console.error('❌ Le dossier dist/ est introuvable. Lancez d\'abord npm run build.');
  process.exit(1);
}

// ── 3. Créer le dossier de sortie ────────────────────────────────
await mkdir(UPDATES_DIR, { recursive: true });

// Supprimer les anciens bundles pour ne pas encombrer le repo
const bundleGlob = await readdir(UPDATES_DIR);
for (const f of bundleGlob.filter(n => n.startsWith('bundle-') && n.endsWith('.zip'))) {
  await rm(path.join(UPDATES_DIR, f));
  console.log(`🗑  Suppression ancien bundle : ${f}`);
}

// ── 4. Zipper dist/ ──────────────────────────────────────────────
const bundleFile = `bundle-${version}.zip`;
const bundlePath = path.join(UPDATES_DIR, bundleFile);
const bundleUrl  = `${BASE_URL}/public/updates/${bundleFile}`;

console.log(`🗜  Compression dist/ → ${bundleFile}…`);
await zipDirectory(path.join(ROOT, 'dist'), bundlePath);
console.log(`✅ Bundle créé : ${bundlePath}`);

// ── 5. Générer latest.json ───────────────────────────────────────
const latest = { version, url: bundleUrl };
await writeFile(
  path.join(UPDATES_DIR, 'latest.json'),
  JSON.stringify(latest, null, 2) + '\n',
  'utf8'
);
console.log('✅ latest.json généré :', latest);

// ── 6. Instructions ──────────────────────────────────────────────
console.log(`
──────────────────────────────────────────────────────
🚀 Déploiement OTA prêt !

Fichiers générés dans app/public/updates/ :
  • ${bundleFile}
  • latest.json

Pour déployer :
  git add app/public/updates/
  git commit -m "chore: OTA update v${version}"
  git push
  → GitHub Actions enverra les fichiers sur le serveur via FTP.

L'APK vérifiera les mises à jour sur :
  ${BASE_URL}/api/update
──────────────────────────────────────────────────────
`);

// ── Helpers ──────────────────────────────────────────────────────

function zipDirectory(src, dest) {
  return new Promise((resolve, reject) => {
    const output  = createWriteStream(dest);
    const archive = archiver('zip', { zlib: { level: 9 } });

    output.on('close', () => {
      const kb = (archive.pointer() / 1024).toFixed(1);
      console.log(`   → ${kb} Ko compressés`);
      resolve();
    });
    archive.on('error', reject);
    archive.pipe(output);
    // Le contenu de dist/ est placé à la racine du zip (pas dans un sous-dossier)
    archive.directory(src, false);
    archive.finalize();
  });
}

async function readdir(dir) {
  const { readdir: rd } = await import('node:fs/promises');
  try { return await rd(dir); } catch { return []; }
}

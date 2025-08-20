/*
 Patches lucide-vue-next ESM icon files to remove broken sourceMappingURL comments
 to avoid esbuild crashing on invalid/empty maps in dev prebundle.
 Safe no-op if package or files are absent.
*/
import fs from 'fs';
import path from 'path';

function stripSourceMapCommentsInDir(dir) {
  try {
    if (!fs.existsSync(dir)) return 0;
    const entries = fs.readdirSync(dir, { withFileTypes: true });
    let patched = 0;
    for (const entry of entries) {
      const full = path.join(dir, entry.name);
      if (entry.isDirectory()) {
        patched += stripSourceMapCommentsInDir(full);
      } else if (entry.isFile() && entry.name.endsWith('.js')) {
        try {
          const original = fs.readFileSync(full, 'utf8');
          const updated = original.replace(/\n?\s*\/\/#[ \t]*sourceMappingURL=.*$/gm, '');
          if (updated !== original) {
            fs.writeFileSync(full, updated, 'utf8');
            patched++;
          }
        } catch (_) {
          // ignore individual file errors
        }
      }
    }
    return patched;
  } catch (_) {
    return 0;
  }
}

function main() {
  const base = path.join(process.cwd(), 'node_modules', 'lucide-vue-next', 'dist', 'esm', 'icons');
  const count = stripSourceMapCommentsInDir(base);
  if (count > 0) {
    console.log(`Patched ${count} lucide-vue-next icon file(s) to strip sourceMappingURL comments.`);
  }
}

main();


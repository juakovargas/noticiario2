import fs from 'node:fs';
const file = 'resources/js/i18n/translations.ts';
const src = fs.readFileSync(file,'utf8');
const localeRe = /(\w+):\s*\{([\s\S]*?)\n\s*\},/g;
const localeMaps = {};
let m;
let hasDup = false;
while ((m = localeRe.exec(src))) {
  const locale = m[1];
  const body = m[2];
  const keyRe = /^\s*'([^']+)'\s*:/gm;
  const seen = new Set();
  const dup = new Set();
  let km;
  while ((km = keyRe.exec(body))) {
    if (seen.has(km[1])) dup.add(km[1]);
    seen.add(km[1]);
  }
  localeMaps[locale] = seen;
  if (dup.size) {
    hasDup = true;
    console.error(`[duplicates] ${locale}: ${[...dup].join(', ')}`);
  }
}
if (hasDup) process.exit(1);
console.log('Translation check passed (no duplicate keys).');

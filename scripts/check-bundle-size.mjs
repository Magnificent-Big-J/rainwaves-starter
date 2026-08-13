#!/usr/bin/env node
// RS-503: enforces a bundle-size budget against the gzip size actually sent over the
// wire (not raw file size, which moves around with minification/sourcemap noise).
// Run after `npm run build`. Two named chunks get their own higher, still-real budget
// rather than a blanket exemption — apexcharts (a charting library) and main (the app
// entry) are legitimately larger than everything else; a regression in either should
// still fail, just against a realistic ceiling instead of the tight default.
import { gzipSync } from 'node:zlib';
import { readFileSync, readdirSync, statSync } from 'node:fs';
import { join } from 'node:path';

const ASSETS_DIR = 'public/build/assets';
const DEFAULT_BUDGET_KB = 60;
const BUDGETS_KB = {
    main: 220,
    apexcharts: 170,
};

let failed = false;
let entries;

try {
    entries = readdirSync(ASSETS_DIR);
} catch {
    console.error(`✗ ${ASSETS_DIR} not found — run "npm run build" first.`);
    process.exit(1);
}

for (const file of entries) {
    if (!file.endsWith('.js')) {
        continue;
    }

    const path = join(ASSETS_DIR, file);

    if (!statSync(path).isFile()) {
        continue;
    }

    const gzipKb = gzipSync(readFileSync(path)).length / 1024;
    const budgetKey = Object.keys(BUDGETS_KB).find((key) => file.startsWith(key));
    const budgetKb = budgetKey ? BUDGETS_KB[budgetKey] : DEFAULT_BUDGET_KB;

    if (gzipKb > budgetKb) {
        console.error(`✗ ${file}: ${gzipKb.toFixed(1)} KB gzipped exceeds budget of ${budgetKb} KB`);
        failed = true;
    }
}

if (failed) {
    console.error('\nBundle size budget exceeded — see budgets in scripts/check-bundle-size.mjs.');
    process.exit(1);
}

console.log('Bundle size budget OK.');

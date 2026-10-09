// Regenerates resources/templates/supervisor-approval-form.pdf from its HTML
// source using a local headless Chromium (Edge or Chrome), which handles
// Arabic shaping and RTL layout correctly.
//
//   node scripts/build-supervisor-approval-form.cjs
//
// Set CHROME_PATH to use a specific browser binary.
const { execFileSync } = require('child_process');
const fs = require('fs');
const path = require('path');
const { pathToFileURL } = require('url');

const root = path.resolve(__dirname, '..');
const source = path.join(root, 'resources/templates/supervisor-approval-form.html');
const output = path.join(root, 'resources/templates/supervisor-approval-form.pdf');

const candidates = [
  process.env.CHROME_PATH,
  'C:/Program Files (x86)/Microsoft/Edge/Application/msedge.exe',
  'C:/Program Files/Microsoft/Edge/Application/msedge.exe',
  'C:/Program Files/Google/Chrome/Application/chrome.exe',
  '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome',
  '/usr/bin/google-chrome',
  '/usr/bin/chromium',
  '/usr/bin/chromium-browser',
].filter(Boolean);

const browser = candidates.find((p) => fs.existsSync(p));
if (!browser) {
  console.error('No Chromium-based browser found. Set CHROME_PATH.');
  process.exit(1);
}

execFileSync(browser, [
  '--headless',
  '--disable-gpu',
  '--no-pdf-header-footer',
  `--print-to-pdf=${output}`,
  pathToFileURL(source).href,
], { stdio: 'inherit' });

console.log(`Wrote ${path.relative(root, output)}`);

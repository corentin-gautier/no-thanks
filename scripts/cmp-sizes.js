// Weighs the best-known CMPs as their vendors deploy them: npm run cmp-sizes
// Loads each vendor's site (first visit, nothing clicked), keeps the requests to the CMP's own domains,
// then downloads each with curl to count the compressed bytes (br or gzip, as a browser gets them).
const { chromium } = require('playwright');
const { execFileSync } = require('child_process');

const cmps = [
  ['Cookiebot', 'https://www.cookiebot.com/en/', /^https:\/\/(consent|consentcdn)\.cookiebot\.(com|eu)\//],
  ['OneTrust', 'https://www.onetrust.com/', /^https:\/\/(cdn\.cookielaw\.org|geolocation\.onetrust\.com)\//],
  ['Didomi', 'https://www.didomi.io/', /^https:\/\/(sdk|api)\.privacy-center\.org\//],
  ['Axeptio', 'https://www.axeptio.eu/en', /^https:\/\/(static|client|api)\.axept\.io\/(?!datacenter)/],
  ['Usercentrics', 'https://usercentrics.com/', /^https:\/\/[a-z.-]*usercentrics\.eu\//],
  ['CookieYes', 'https://www.cookieyes.com/', /^https:\/\/(cdn-cookieyes|log\.cookieyes)\.com\//],
  ['tarteaucitron.js', 'https://tarteaucitron.io/en/', /^https:\/\/cdn(tag)?\.tarteaucitron\.io\//],
];
// The smaller of two downloads: some CDNs answer the first one uncompressed
const fetchSize = url => Number(execFileSync('curl', ['-s', '-o', '/dev/null', '-w', '%{size_download}', '-H', 'Accept-Encoding: br, gzip', url]));
const size = url => Math.min(fetchSize(url), fetchSize(url));

(async () => {
  const browser = await chromium.launch();
  for (const [name, site, own] of cmps) {
    const ctx = await browser.newContext({ locale: 'fr-FR' });
    const page = await ctx.newPage();
    const urls = new Set();
    page.on('requestfinished', req => own.test(req.url()) && urls.add(req.url()));
    try { await page.goto(site, { waitUntil: 'load', timeout: 45000 }); } catch (e) { console.log(`${name}: ${e.message.split('\n')[0]}`); }
    await page.waitForTimeout(6000);
    const total = [...urls].reduce((sum, url) => sum + size(url), 0);
    console.log(`${name}: ${Math.round(total / 1024)} KB, ${urls.size} requests`);
    await ctx.close();
  }
  await browser.close();
})();

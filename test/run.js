// End-to-end checks of the banner in headless Chromium: node test/run.js
const http = require('http');
const fs = require('fs');
const path = require('path');
const { chromium } = require('playwright');

const root = path.join(__dirname, '..');
const server = http.createServer((req, res) => {
  const url = req.url.split('?')[0];
  const file = url === '/' ? 'test/page.html' : url.slice(1);
  fs.readFile(path.join(root, file), (err, data) => {
    res.writeHead(err ? 404 : 200, { 'Content-Type': file.endsWith('.css') ? 'text/css' : file.endsWith('.js') ? 'text/javascript' : 'text/html' });
    res.end(data || '');
  });
});

let failed = 0;
const check = (name, ok) => { console.log(`${ok ? 'ok  ' : 'FAIL'} ${name}`); if (!ok) failed++; };

(async () => {
  await new Promise(r => server.listen(0, r));
  const base = `http://localhost:${server.address().port}/`;
  const browser = await chromium.launch();
  const fresh = async (gpc = false) => {
    const ctx = await browser.newContext();
    if (gpc) await ctx.addInitScript(() => Object.defineProperty(navigator, 'globalPrivacyControl', { get: () => true }));
    const page = await ctx.newPage();
    await page.goto(base);
    return page;
  };
  const runs = page => page.evaluate(() => runs.join(','));
  const shown = page => page.locator('dialog.no-thanks[open]').count();
  const toggle = page => page.locator('.no-thanks-toggle:not([hidden])');

  // First visit, then accept
  let page = await fresh();
  check('first visit shows the banner', await shown(page) === 1);
  check('nothing runs before consent', await runs(page) === '');
  check('no corner button before a choice', await toggle(page).count() === 0);
  check('first visit leaves the focus alone', await page.evaluate(() => document.activeElement === document.body));
  await page.getByRole('button', { name: 'OK' }).click();
  check('accept runs everything in order', await runs(page) === 'template,nested,plain');
  check('banner closes', await shown(page) === 0);
  check('corner button: allowed', await toggle(page).count() === 1 && await page.locator('.no-thanks-toggle--on').count() === 1);
  await page.reload();
  check('accepted: no banner on next visit', await shown(page) === 0);
  check('accepted: runs on next visit', await runs(page) === 'template,nested,plain');

  // Footer link, Escape, focus back
  await page.click('#settings');
  check('link reopens with the state', (await page.locator('.no-thanks__state').textContent()).includes('You agreed on'));
  await page.keyboard.press('Escape');
  check('Escape closes', await shown(page) === 0);
  check('focus back on the link', await page.evaluate(() => document.activeElement.id) === 'settings');
  await toggle(page).click();
  check('corner button reopens, hides', await shown(page) === 1 && await toggle(page).count() === 0);
  await page.keyboard.press('Escape');
  check('focus back on the corner button', await page.evaluate(() => document.activeElement.classList.contains('no-thanks-toggle')));

  // Revoke: reload, tracker cookie removed
  await page.click('#settings');
  await Promise.all([page.waitForNavigation(), page.getByRole('button', { name: 'No thanks' }).click()]);
  check('revoke clears tracker cookies', !(await page.evaluate(() => document.cookie)).includes('_ga'));
  check('revoke: nothing runs', await runs(page) === '');

  // New revision asks again
  await page.goto(`${base}?r=2`);
  check('new revision asks again', await shown(page) === 1);
  await page.context().close();

  // Refuse
  page = await fresh();
  await page.getByRole('button', { name: 'No thanks' }).click();
  await page.reload();
  check('refused: no banner, nothing runs', await shown(page) === 0 && await runs(page) === '');
  check('corner button: not allowed', await page.locator('.no-thanks-toggle--off:not([hidden])').count() === 1);
  check('corner button says it', (await toggle(page).textContent()) === 'Cookies not allowed');
  await page.context().close();

  // Global Privacy Control
  page = await fresh(true);
  check('GPC: notice shown, nothing runs', await shown(page) === 1 && await runs(page) === '');
  check('GPC: notice has no button', await page.locator('.no-thanks button').count() === 0);
  await page.waitForTimeout(6500);
  check('GPC: notice fades out', await shown(page) === 0);
  check('GPC: corner button, not allowed', await page.locator('.no-thanks-toggle--off:not([hidden])').count() === 1);
  await page.reload();
  check('GPC: notice only once', await shown(page) === 0 && await runs(page) === '');
  await page.click('#settings');
  check('GPC: link explains', (await page.locator('.no-thanks__text').textContent()).includes('not to be tracked'));
  await page.getByRole('button', { name: 'Allow anyway' }).click();
  check('GPC: explicit consent runs', await runs(page) === 'template,nested,plain');
  await page.reload();
  check('GPC: explicit consent kept', await runs(page) === 'template,nested,plain');

  await browser.close();
  server.close();
  process.exit(failed ? 1 : 0);
})();

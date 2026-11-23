# No Thanks

An ultra-light consent banner: about 3.3 KB gzipped, CSS included (4.1 KB with purposes), no dependency, no cookie. Do Not Track and Global Privacy Control count as a refusal; a short notice tells the visitor once, then fades out.

- A small, non-blocking banner; « No thanks » and « OK » weigh the same.
- Blocked code only runs once the visitor agrees.
- Several purposes (audience, videos, ads…): one click still decides everything, « Choose » gives one switch per purpose. Never a list of cookies.
- The choice is kept in `localStorage` for 6 months, and asked again when the trackers change.
- Once the banner is closed, a small cookie button in the corner shows the state (green check when allowed, amber dash when only some purposes are, crossed out otherwise) and reopens it; any link to `#no-thanks` does too, so visitors can change their mind. Refusing after agreeing deletes the trackers' cookies and reloads the page.

## Weight

What a visitor downloads for the banner on a first visit, compressed:

| | KB | Requests |
|---|---:|---:|
| **No Thanks** | **3.3** | **1** (CSS inlined by the plugin) |
| **No Thanks, with purposes** | **4.1** | **1** |
| CookieYes | 32 | 9 |
| tarteaucitron.js | 84 | 4 |
| Usercentrics | 137 | 5 |
| Cookiebot | 163 | 4 |
| OneTrust | 209 | 13 |
| Didomi | 210 | 3 |
| Axeptio | 290 | 9 |

No Thanks: `dist/` files, JS + CSS, gzipped. Self-hosted libraries for reference, JS + CSS gzipped: CookieConsent v3 ≈ 16 KB, Complianz (WordPress) ≈ 15 KB, Klaro ≈ 67 KB.

The others as their vendors run them on their own site, measured on 24 November 2026 from France: every request to the CMP's own domains (scripts, styles, config, translations), counted in the compressed bytes a browser gets (br or gzip). A site's own setup (number of services, languages, design) moves these figures. Run `npm run cmp-sizes` to measure again.

## WordPress

Install the plugin with composer (`cgsoft/no-thanks`, from the repository `https://corentingautier.dev/composer-repo`), activate it, then fill Settings › No Thanks: one block per purpose with the trackers' code as the service gives it, and every text of the banner (the message in a small visual editor with bold, italic and links; the buttons; the reminders; the footer link). Each text has a default in the site language. While the code is empty, nothing is loaded on the site. Elsewhere in the site, `<template data-no-thanks="<purpose id>">` holds code back for a purpose (an embedded video, say). Settings › Privacy › Policy Guide gets a suggested text listing the purposes and the services found in their code.

Footer link: `no_thanks_link()` prints the footer link set in the settings while the banner is on; in a block footer, link to `#no-thanks`.

Theming: every rule is in `:where()`; set `--no-thanks-bg`, `--no-thanks-color`, `--no-thanks-accent`, `--no-thanks-accent-color`, `--no-thanks-radius`, `--no-thanks-shadow`, `--no-thanks-offset`. Filter `no_thanks_config` to change anything passed to the script.

## Any site

```html
<!-- with several purposes: no-thanks-purposes.css and no-thanks-purposes.js -->
<link rel="stylesheet" href="dist/no-thanks.css">
<script>
  window.noThanksConfig = {
    revision: '1',
    // Several purposes: optional, one choice for everything without them
    purposes: [{ id: 'audience', label: 'Audience measurement', description: 'Google Analytics' }, { id: 'videos', label: 'Videos' }],
    texts: { message: 'This site would like to measure its audience with <b>Google Analytics</b>. Is that OK? <a href="/privacy">Learn more</a>' },
  };
</script>
<script src="dist/no-thanks.js" defer></script>

<template data-no-thanks="audience">
  <!-- the tracker's code, as given; no value: needs every purpose -->
</template>
<script type="text/plain" data-no-thanks>/* or a single inline script */</script>
<a href="#no-thanks">Cookies</a>
```

Options: `revision` (change it to ask again), `maxAge` (days, 182), `cookies` (name prefixes deleted on refusal), `privacyUrl` (adds a « Learn more » link after the message), `buttonClass`, `purposes`, `texts`: `message` and `dnt` (HTML), `refuse`, `accept`, `acceptAnyway`, `accepted`, `refused` and `chosen` (`{date}` is replaced), `choose`, `save`, `more`, `allowed`, `some` and `notAllowed` (the corner button's text), `label` (the title, next to a cookie icon). The check's colour is `--no-thanks-ok` (also the switches'), the « some » dash's `--no-thanks-some`.

API: `noThanks.open()`, `noThanks.consent` (everything allowed), `noThanks.allows(id)`, `noThanks.set(true | false | { id: bool })`; event `no-thanks:change` on `document` (`detail.consent`, `detail.purposes`).

## Development

```bash
npm install
npm run build   # dist/: the build without and with purposes, prints the sizes
npm test        # headless Chromium checks, then the plugin's PHP (needs php)
npm run demo    # every banner state side by side, on http://localhost:6480/
npm run i18n    # refreshes and compiles the French translation (needs gettext)
```

`dist/` is committed: the plugin uses it as is.

Release: `npm run release` (build, tests, `standard-version` bumps the plugin header, `NO_THANKS_VERSION`, `composer.json` and `package.json`, tags, pushes, then rebuilds the [Composer repository](https://github.com/corentin-gautier/composer-repo)). Dev files are left out of the installed plugin by `.gitattributes`.

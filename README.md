# No Thanks

An ultra-light consent banner: about 2 KB gzipped, no dependency, no cookie. Do Not Track and Global Privacy Control count as a refusal.

- A small, non-blocking banner; « No thanks » and « OK » weigh the same.
- Blocked code only runs once the visitor agrees.
- The choice is kept in `localStorage` for 6 months, and asked again when the trackers change.
- Any link to `#no-thanks` reopens the banner, so visitors can change their mind. Refusing after agreeing deletes the trackers' cookies and reloads the page.

## WordPress

Install the plugin (composer: `cgsoft/no-thanks`), activate it, then fill Settings › No Thanks: the services named in the banner, their purpose, and the trackers' code as the service gives it. While the code is empty, nothing is loaded on the site.

Footer link: `no_thanks_link()` prints a « Cookies » link while the banner is on; in a block footer, link to `#no-thanks`.

Theming: every rule is in `:where()`; set `--no-thanks-bg`, `--no-thanks-color`, `--no-thanks-accent`, `--no-thanks-accent-color`, `--no-thanks-radius`, `--no-thanks-shadow`, `--no-thanks-offset`. Filter `no_thanks_config` to change anything passed to the script.

## Any site

```html
<link rel="stylesheet" href="dist/no-thanks.css">
<script>
  window.noThanksConfig = { services: 'Google Analytics', privacyUrl: '/privacy', revision: '1' };
</script>
<script src="dist/no-thanks.js" defer></script>

<template data-no-thanks>
  <!-- the tracker's code, as given -->
</template>
<script type="text/plain" data-no-thanks>/* or a single inline script */</script>
<a href="#no-thanks">Cookies</a>
```

Options: `services`, `purpose`, `privacyUrl`, `revision` (change it to ask again), `maxAge` (days, 182), `cookies` (name prefixes deleted on refusal), `buttonClass`, `texts` (see `src/no-thanks.js`).

API: `noThanks.open()`, `noThanks.consent`, `noThanks.set(true|false)`; event `no-thanks:change` on `document` (`detail.consent`).

## Development

```bash
npm install
npm run build   # dist/, prints the sizes
npm test        # headless Chromium checks
```

`dist/` is committed: the plugin uses it as is.

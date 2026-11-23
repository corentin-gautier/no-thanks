# No Thanks

An ultra-light consent banner: about 2 KB gzipped, no dependency, no cookie. Do Not Track and Global Privacy Control count as a refusal; a short notice tells the visitor once, then fades out.

- A small, non-blocking banner; « No thanks » and « OK » weigh the same.
- Blocked code only runs once the visitor agrees.
- The choice is kept in `localStorage` for 6 months, and asked again when the trackers change.
- Any link to `#no-thanks` reopens the banner, so visitors can change their mind. Refusing after agreeing deletes the trackers' cookies and reloads the page.

## WordPress

Install the plugin with composer (`cgsoft/no-thanks`, from the repository `https://corentingautier.dev/composer-repo`), activate it, then fill Settings › No Thanks: the trackers' code as the service gives it, and every text of the banner (the message in a small visual editor with bold, italic and links; the buttons; the reminders; the footer link). Each text has a default in the site language. While the code is empty, nothing is loaded on the site.

Footer link: `no_thanks_link()` prints the footer link set in the settings while the banner is on; in a block footer, link to `#no-thanks`.

Theming: every rule is in `:where()`; set `--no-thanks-bg`, `--no-thanks-color`, `--no-thanks-accent`, `--no-thanks-accent-color`, `--no-thanks-radius`, `--no-thanks-shadow`, `--no-thanks-offset`. Filter `no_thanks_config` to change anything passed to the script.

## Any site

```html
<link rel="stylesheet" href="dist/no-thanks.css">
<script>
  window.noThanksConfig = {
    revision: '1',
    texts: { message: 'This site would like to measure its audience with <b>Google Analytics</b>. Is that OK? <a href="/privacy">Learn more</a>' },
  };
</script>
<script src="dist/no-thanks.js" defer></script>

<template data-no-thanks>
  <!-- the tracker's code, as given -->
</template>
<script type="text/plain" data-no-thanks>/* or a single inline script */</script>
<a href="#no-thanks">Cookies</a>
```

Options: `revision` (change it to ask again), `maxAge` (days, 182), `cookies` (name prefixes deleted on refusal), `privacyUrl` (adds a « Learn more » link after the message), `buttonClass`, `texts`: `message` and `dnt` (HTML), `refuse`, `accept`, `acceptAnyway`, `accepted` and `refused` (`{date}` is replaced), `more`, `label` (the dialog's accessible name).

API: `noThanks.open()`, `noThanks.consent`, `noThanks.set(true|false)`; event `no-thanks:change` on `document` (`detail.consent`).

## Development

```bash
npm install
npm run build   # dist/, prints the sizes
npm test        # headless Chromium checks
```

`dist/` is committed: the plugin uses it as is.

Release: `npm run release` (build, tests, `standard-version` bumps the plugin header, `NO_THANKS_VERSION`, `composer.json` and `package.json`, tags, pushes, then rebuilds the [Composer repository](https://github.com/corentin-gautier/composer-repo)). Dev files are left out of the installed plugin by `.gitattributes`.

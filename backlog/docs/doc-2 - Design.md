---
id: doc-2
title: Design
type: guide
---

## Principles

- Native first: `<dialog>` shown non-modal (no cookie wall), no library, about 2.5 KB gzipped (JS) + 0.8 KB (CSS); 2.9 + 1.2 KB with purposes.
- Discreet: a small vertical card in a corner (340 px wide): a title with a cookie icon (`label`), the message with « Learn more » inline, then two buttons of equal weight side by side underneath. Unasked, it never takes the focus; reopened from a link, it does, and Escape closes it. Its buttons are its own, not the theme's (theme buttons are too big here); colours come from `--no-thanks-*`.
- Corner button: once the banner is closed (a choice made, or after the Do Not Track notice), a 36 px round button in the same corner shows the state and reopens the banner (focus comes back to it on Escape). Cookie with a green check (`--no-thanks-ok`) when everything is allowed, an amber dash (`--no-thanks-some`) when some purposes are, crossed out otherwise; the text (`allowed` / `some` / `notAllowed`) slides out on hover or keyboard focus. Hidden while the banner is open, so never there while a first visit waits for an answer.
- Nothing loaded while no tracker is set (plugin).
- No cookie: the choice is in `localStorage` (`no-thanks`: `{ v, t, r }` = choice, time, revision), kept `maxAge` days. `v` is `true` / `false` for everything, or `{ id: bool }` when chosen purpose by purpose.

## Purposes

Choices are by purpose (audience, videos, ads…), never by cookie: that is what the CNIL asks, and a list of cookies only weighs the page and tires the visitor.

- One purpose (the usual case): one choice, nothing more shown or loaded. Two builds from one source (`scripts/build.js`, esbuild `define`): `PURPOSES` false drops all purpose code (`no-thanks.js` / `.css`); `no-thanks-purposes.js` / `.css` keep it. The plugin loads the second only with two purposes or more.
- Several (`purposes: [{ id, label, description }]`): « OK » and « No thanks » still decide everything in one click; « Choose » under them shows one switch per purpose (`role=switch`, all off). Reopened, the banner opens straight on the switches, set to the current choice, with its date (`chosen`).
- Blocked code names its purpose: `data-no-thanks="videos"`; no value needs every purpose. Allowing one more runs its code at once; taking one back cleans the cookies and reloads.
- Plugin: one block per purpose in Settings (name, description, head and end scripts); the empty block at the end adds one, emptying a block removes it. The id comes from the first name and stays. Settings from before purposes become one « Audience measurement » purpose.

## Do Not Track

`navigator.globalPrivacyControl` or `doNotTrack` = an implicit refusal: nothing runs, no banner. On the first visit only (`no-thanks-notice` in `localStorage`), the `dnt` message shows 6 s without buttons (`role=status`), paused while hovered or focused, then fades out. Third-party cookie blocking is not a signal: it cannot be read from the page and leaves first-party trackers working. An explicit choice (footer link, « Allow anyway ») wins over the signal.

## Blocked code

`<template data-no-thanks>` holds any pasted HTML (scripts, noscript, iframes): inert until consent, then imported in place; every script inside is rebuilt so it runs, external ones in order (`async` only when the original had it). `<script type="text/plain" data-no-thanks>` also works (`data-type` restores a type).

## Revoking

Scripts that already ran cannot be unloaded: refusing after agreeing deletes the cookies matching `cookies` (on the host and its parent domains) and reloads the page.

## Plugin

- Scripts are saved as is, only by a user with `unfiltered_html`.
- Every text is set in Settings › No Thanks, none is built: the title, the message (and the Do Not Track one) in a teeny TinyMCE (bold, italic, link; kept through `wp_kses`, paragraphs become `<br>`), the buttons, the reminders (`{date}`), the footer link, the corner button's texts, the « Choose » / « Save » ones. An empty field falls back to its translated default; the default message links to the privacy policy page.
- `revision` = hash of message + purposes (with their scripts): changing them asks every visitor again.
- Suggested privacy policy text (Settings › Privacy › Policy Guide): how consent works here, then one line per purpose and service found in its scripts (known ones: Google, Matomo, Meta, Hotjar, Clarity, LinkedIn, YouTube, Plausible), to complete.
- CSS inlined (few hundred bytes), JS deferred; texts translated (`languages/`, fr_FR).

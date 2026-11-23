---
id: doc-2
title: Design
type: guide
---

## Principles

- Native first: `<dialog>` shown non-modal (no cookie wall), no library, about 2 KB gzipped.
- Discreet: a small vertical card in a corner (340 px wide), the message with « Learn more » inline, then two buttons of equal weight side by side underneath. Unasked, it never takes the focus; reopened from a link, it does, and Escape closes it. Its buttons are its own, not the theme's (theme buttons are too big here); colours come from `--no-thanks-*`.
- Corner button: once the banner is closed (a choice made, or after the Do Not Track notice), a 36 px round button in the same corner shows the state and reopens the banner (focus comes back to it on Escape). Cookie with a green check (`--no-thanks-ok`) when allowed, crossed-out cookie otherwise; the text (`allowed` / `notAllowed`) slides out on hover or keyboard focus. Hidden while the banner is open, so never there while a first visit waits for an answer.
- Nothing loaded while no tracker is set (plugin).
- No cookie: the choice is in `localStorage` (`no-thanks`: `{ v, t, r }` = choice, time, revision), kept `maxAge` days.
- One choice for every tracker of the site: sites rarely have more than one. Per-purpose choices only if a site needs them.

## Do Not Track

`navigator.globalPrivacyControl` or `doNotTrack` = an implicit refusal: nothing runs, no banner. On the first visit only (`no-thanks-notice` in `localStorage`), the `dnt` message shows 6 s without buttons (`role=status`), paused while hovered or focused, then fades out. Third-party cookie blocking is not a signal: it cannot be read from the page and leaves first-party trackers working. An explicit choice (footer link, « Allow anyway ») wins over the signal.

## Blocked code

`<template data-no-thanks>` holds any pasted HTML (scripts, noscript, iframes): inert until consent, then imported in place; every script inside is rebuilt so it runs, external ones in order (`async` only when the original had it). `<script type="text/plain" data-no-thanks>` also works (`data-type` restores a type).

## Revoking

Scripts that already ran cannot be unloaded: refusing after agreeing deletes the cookies matching `cookies` (on the host and its parent domains) and reloads the page.

## Plugin

- Scripts are saved as is, only by a user with `unfiltered_html`.
- Every text is set in Settings › No Thanks, none is built: the message (and the Do Not Track one) in a teeny TinyMCE (bold, italic, link; kept through `wp_kses`, paragraphs become `<br>`), the buttons, the reminders (`{date}`), the footer link, the corner button's two texts. An empty field falls back to its translated default; the default message links to the privacy policy page.
- `revision` = hash of message + scripts: changing them asks every visitor again.
- CSS inlined (few hundred bytes), JS deferred; texts translated (`languages/`, fr_FR).

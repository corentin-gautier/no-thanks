---
id: doc-2
title: Design
type: guide
---

## Principles

- Native first: `<dialog>` shown non-modal (no cookie wall), no library, about 2 KB gzipped.
- Nothing loaded while no tracker is set (plugin).
- No cookie: the choice is in `localStorage` (`no-thanks`: `{ v, t, r }` = choice, time, revision), kept `maxAge` days.
- One choice for every tracker of the site: sites rarely have more than one. Per-purpose choices only if a site needs them.

## Do Not Track

`navigator.globalPrivacyControl` or `doNotTrack` = an implicit refusal: no banner, nothing runs. An explicit choice (footer link, « Allow anyway ») wins over the signal.

## Blocked code

`<template data-no-thanks>` holds any pasted HTML (scripts, noscript, iframes): inert until consent, then imported in place; every script inside is rebuilt so it runs, external ones in order (`async` only when the original had it). `<script type="text/plain" data-no-thanks>` also works (`data-type` restores a type).

## Revoking

Scripts that already ran cannot be unloaded: refusing after agreeing deletes the cookies matching `cookies` (on the host and its parent domains) and reloads the page.

## Plugin

- Scripts are saved as is, only by a user with `unfiltered_html`.
- `revision` = hash of services + scripts: changing them asks every visitor again.
- CSS inlined (few hundred bytes), JS deferred; texts translated (`languages/`, fr_FR).

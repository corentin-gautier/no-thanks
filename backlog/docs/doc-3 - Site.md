---
id: doc-3
title: Site
type: guide
created_date: '2026-11-28T16:04:13Z'
---

The project site, published by GitHub Pages at https://corentingautier.dev/no-thanks/: one hand-written page, `docs/index.html` (no build), plus `docs/sitemap.xml` and `docs/assets/social-preview.png`.

## Styling

- Built on the shared design system (portfolio repo, `design-system/`): `https://corentingautier.dev/design-system/ds.css` is linked first, then the page's own `<style>`, which only holds what is specific to this site (code blocks, hero, highlights, Ko-fi box, demo list, weight table, steps, options list, the banner's theme). Dark only.
- `lines.js` from the same place draws the hero waves, one section rule and the row signs.
- Layout as on the portfolio: 12-column grid, `.sec-head` per section (numbered: the demo banner's text points to « section 01 »), secondary content on the column 7 axis, features and install as `.rows` with `.chips`.

## Live demo

The banner is the published one (jsDelivr, `no-thanks.js@0`, purposes build), themed through `--no-thanks-*` on `.no-thanks`. Three made-up purposes run `ran(id)` from `<template data-no-thanks>`, which flips the « Blocked » labels of the demo list to « Ran ».

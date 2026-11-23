/**
 * No Thanks — an ultra-light consent banner.
 *
 * Blocked content is written as `<template data-no-thanks>` (any HTML, scripts included)
 * or `<script type="text/plain" data-no-thanks>`; it only runs once the visitor agrees.
 * The choice is kept in localStorage (no cookie). Do Not Track and Global Privacy Control
 * count as a refusal. Any link to `#no-thanks` reopens the banner.
 *
 * Config: `window.noThanksConfig = { services, purpose, privacyUrl, revision, maxAge, cookies, buttonClass, texts }`
 * set before this script runs.
 */
(() => {
  const KEY = 'no-thanks';
  const DAY = 864e5;
  const config = {
    services: '',
    purpose: 'measure its audience',
    privacyUrl: '',
    revision: '',
    maxAge: 182,
    cookies: ['_ga', '_gid', '_gat', '_gcl', '_fbp', '_hj', '_pk', '_uet'],
    buttonClass: '',
    ...window.noThanksConfig,
  };
  const texts = {
    label: 'Cookies and trackers',
    message: 'This site would like to {purpose} with {services}. Is that OK?',
    dnt: 'Your browser asks not to be tracked: {services} stays off.',
    state: 'Your choice: {choice}, on {date}.',
    accepted: 'accepted',
    refused: 'refused',
    refuse: 'No thanks',
    accept: 'OK',
    acceptAnyway: 'Allow anyway',
    more: 'Learn more',
    ...config.texts,
  };

  const doNotTrack = navigator.globalPrivacyControl === true
    || [navigator.doNotTrack, window.doNotTrack].some(v => v === '1' || v === 'yes');

  const read = () => {
    try {
      const c = JSON.parse(localStorage.getItem(KEY));
      if (c && c.r === config.revision && Date.now() - c.t < config.maxAge * DAY) return c;
    } catch (e) {}
    return null;
  };

  let choice = read();
  let dialog;
  let opener;
  let ran = false;

  /** Whether blocked content may run: an explicit choice wins over the browser signal. */
  const allowed = () => (choice ? choice.v : false);

  /** Runs every blocked script and template, in document order. */
  const run = () => {
    if (ran) return;
    ran = true;
    document.querySelectorAll('template[data-no-thanks], script[type="text/plain"][data-no-thanks]').forEach(el => {
      let frag;
      if (el.tagName === 'TEMPLATE') {
        frag = document.importNode(el.content, true);
      } else {
        frag = document.createDocumentFragment();
        const s = el.cloneNode(true);
        s.type = s.dataset.type || '';
        if (!s.type) s.removeAttribute('type');
        frag.append(s);
      }
      // Scripts built by the parser or cloned may never run: rebuild them
      frag.querySelectorAll('script').forEach(old => {
        const s = document.createElement('script');
        for (const a of old.attributes) s.setAttribute(a.name, a.value);
        s.async = old.hasAttribute('async');
        s.text = old.text;
        old.replaceWith(s);
      });
      el.replaceWith(frag);
    });
  };

  /** Deletes the trackers' cookies, on this host and its parent domains. */
  const clean = () => {
    const parts = location.hostname.split('.');
    const domains = [''];
    for (let i = 0; i < parts.length - 1; i++) domains.push(`; domain=.${parts.slice(i).join('.')}`);
    document.cookie.split(';').forEach(c => {
      const name = c.split('=')[0].trim();
      if (!config.cookies.some(p => name.startsWith(p))) return;
      domains.forEach(d => { document.cookie = `${name}=; max-age=0; path=/${d}`; });
    });
  };

  const save = v => {
    const had = allowed();
    choice = { v, t: Date.now(), r: config.revision };
    try { localStorage.setItem(KEY, JSON.stringify(choice)); } catch (e) {}
    close();
    document.dispatchEvent(new CustomEvent('no-thanks:change', { detail: { consent: v } }));
    if (v) run();
    else if (had) { clean(); location.reload(); }
  };

  /** Fills `el` with `text`, `{name}` placeholders replaced (services in bold). */
  const fill = (el, text, vars) => {
    text.split(/\{(\w+)\}/).forEach((part, i) => {
      if (!(i % 2)) return el.append(part);
      if (part !== 'services') return el.append(vars[part] ?? '');
      const b = document.createElement('b');
      b.textContent = config.services;
      el.append(b);
    });
    return el;
  };

  const node = (tag, cls, attrs = {}) => Object.assign(document.createElement(tag), { className: cls }, attrs);

  const button = (label, onclick) => node('button', `no-thanks__button ${config.buttonClass}`.trim(), {
    type: 'button', textContent: label, onclick,
  });

  const close = () => {
    if (!dialog) return;
    dialog.close();
    dialog.remove();
    dialog = null;
    if (opener) opener.focus();
    opener = null;
  };

  /** Shows the banner; `reopened` when asked for from a link (adds the current state; Escape closes it). */
  const open = reopened => {
    dialog?.remove();
    const id = 'no-thanks-text';
    dialog = node('dialog', 'no-thanks', { ariaLabel: texts.label });
    dialog.setAttribute('aria-describedby', id);
    const signal = doNotTrack && !choice;

    const text = fill(node('p', 'no-thanks__text', { id }), signal ? texts.dnt : texts.message, { purpose: config.purpose });
    if (config.privacyUrl) text.append(' ', node('a', 'no-thanks__more', { href: config.privacyUrl, textContent: texts.more }));
    if (reopened && choice) {
      const date = new Date(choice.t).toLocaleDateString(document.documentElement.lang || undefined, { dateStyle: 'long' });
      text.append(fill(node('span', 'no-thanks__state'), texts.state, { choice: choice.v ? texts.accepted : texts.refused, date }));
    }
    dialog.append(text);

    const actions = node('div', 'no-thanks__actions');
    if (signal) actions.append(button(texts.acceptAnyway, () => save(true)));
    else actions.append(button(texts.refuse, () => save(false)), button(texts.accept, () => save(true)));
    dialog.append(actions);

    if (reopened) dialog.addEventListener('keydown', e => e.key === 'Escape' && close());
    document.body.append(dialog);
    // Unasked, it leaves the focus where it is; asked for, it takes it
    if (reopened) {
      dialog.show();
      dialog.querySelector('.no-thanks__actions button').focus();
    } else dialog.setAttribute('open', '');
  };

  document.addEventListener('click', e => {
    const link = e.target.closest('a[href$="#no-thanks"]');
    if (!link) return;
    e.preventDefault();
    opener = link;
    open(true);
  });

  window.noThanks = {
    open: () => open(true),
    get consent() { return allowed(); },
    set: v => save(!!v),
  };

  const start = () => {
    if (allowed()) run();
    else if (!choice && !doNotTrack) open(false);
  };
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', start);
  else start();
})();

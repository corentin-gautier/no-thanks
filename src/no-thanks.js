/**
 * No Thanks — an ultra-light consent banner.
 *
 * Blocked content is written as `<template data-no-thanks>` (any HTML, scripts included)
 * or `<script type="text/plain" data-no-thanks>`; it only runs once the visitor agrees.
 * With several purposes, `data-no-thanks="<purpose id>"` ties it to one (no value: needs them all),
 * and visitors may also choose purpose by purpose; one choice for everything otherwise.
 * The choice is kept in localStorage (no cookie). Do Not Track and Global Privacy Control
 * count as a refusal (told once, in a short notice). Once the banner is closed, a small cookie button
 * in the corner shows the state and reopens it; so does any link to `#no-thanks`.
 *
 * Config: `window.noThanksConfig = { privacyUrl, revision, maxAge, cookies, buttonClass, purposes, texts }`,
 * set before this script runs; `purposes`: `[{ id, label, description }]`;
 * `texts.message` and `texts.dnt` are HTML (the site owner's own text).
 */
(() => {
  const KEY = 'no-thanks';
  const NOTICE = 'no-thanks-notice';
  const NOTICE_TIME = 6000;
  const DAY = 864e5;
  const config = {
    privacyUrl: '',
    revision: '',
    maxAge: 182,
    cookies: ['_ga', '_gid', '_gat', '_gcl', '_fbp', '_hj', '_pk', '_uet'],
    buttonClass: '',
    purposes: [],
    ...window.noThanksConfig,
  };
  const texts = {
    label: 'Cookies and trackers',
    message: 'This site would like to measure its audience. Is that OK?',
    dnt: 'Your browser asks not to be tracked: noted, no tracker is on.',
    accepted: 'You agreed on {date}.',
    refused: 'You refused on {date}.',
    chosen: 'Your choices of {date}.',
    refuse: 'No thanks',
    accept: 'OK',
    acceptAnyway: 'Allow anyway',
    choose: 'Choose',
    save: 'Save',
    more: 'Learn more',
    allowed: 'Cookies allowed',
    some: 'Some cookies allowed',
    notAllowed: 'Cookies not allowed',
    ...config.texts,
  };
  // Purpose by purpose only when there is a choice to make
  const purposes = config.purposes.length > 1 ? config.purposes : [];

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
  let toggle;
  let opener;

  /**
   * Whether a choice lets a purpose run (no purpose: all of them). `v` is true or false for everything,
   * or `{ id: bool }` when chosen purpose by purpose. An explicit choice wins over the browser signal.
   */
  const granted = (c, id) => !!c && (typeof c.v !== 'object' ? c.v
    : purposes.length > 0 && (id ? !!c.v[id] : purposes.every(p => c.v[p.id])));

  /** 'on' (everything allowed), 'off' (nothing) or 'some'. */
  const state = () => (granted(choice) ? 'on' : purposes.some(p => granted(choice, p.id)) ? 'some' : 'off');

  /** Runs the blocked scripts and templates whose purpose is allowed, in document order. */
  const run = () => {
    document.querySelectorAll('template[data-no-thanks], script[type="text/plain"][data-no-thanks]').forEach(el => {
      if (!granted(choice, el.dataset.noThanks)) return;
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

  /** Keeps a choice: true or false for everything, or `{ id: bool }`. Taking a purpose back reloads the page. */
  const save = v => {
    const old = choice;
    choice = { v, t: Date.now(), r: config.revision };
    try { localStorage.setItem(KEY, JSON.stringify(choice)); } catch (e) {}
    close();
    const detail = { consent: granted(choice) };
    purposes.forEach(p => { (detail.purposes ||= {})[p.id] = granted(choice, p.id); });
    document.dispatchEvent(new CustomEvent('no-thanks:change', { detail }));
    const ids = purposes.length ? purposes.map(p => p.id) : [''];
    if (ids.some(id => granted(old, id) && !granted(choice, id))) { clean(); location.reload(); }
    else run();
  };

  const node = (tag, cls, attrs = {}) => Object.assign(document.createElement(tag), { className: cls }, attrs);

  const button = (label, onclick) => node('button', `no-thanks__button ${config.buttonClass}`.trim(), {
    type: 'button', textContent: label, onclick,
  });

  const COOKIE = '<path d="M12 2.5a9.5 9.5 0 1 0 9.5 9.6 3 3 0 0 1-3.6-3.1 3 3 0 0 1-3.3-3.4A3 3 0 0 1 12 2.5Z" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><circle cx="8.5" cy="10" r="1.3"/><circle cx="9.5" cy="15.5" r="1.3"/><circle cx="14.5" cy="14" r="1.3"/>';
  const svg = paths => `<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">${paths}</svg>`;
  const badge = (state, mark) => `${COOKIE}<circle class="no-thanks-toggle__badge no-thanks-toggle__badge--${state}" cx="18.5" cy="18.5" r="5.5"/><path d="${mark}" fill="none" stroke="#fff" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>`;
  const ICONS = {
    on: badge('on', 'm16 18.6 1.7 1.7 3-3.2'),
    some: badge('some', 'M16.2 18.5h4.6'),
    off: `<g opacity=".55">${COOKIE}</g><path d="m3.5 3.5 17 17" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>`,
  };

  /** The corner button: shows the state once the banner is closed (a choice, or the browser's signal), reopens it. */
  const showToggle = () => {
    if (dialog || !(choice || doNotTrack)) return;
    if (!toggle) {
      toggle = node('button', '', { type: 'button', onclick: () => { opener = toggle; open(true); } });
      document.body.append(toggle);
    }
    const s = state();
    toggle.className = `no-thanks-toggle no-thanks-toggle--${s}`;
    toggle.innerHTML = svg(ICONS[s]);
    toggle.append(node('span', 'no-thanks-toggle__text', { textContent: { on: texts.allowed, some: texts.some, off: texts.notAllowed }[s] }));
    toggle.hidden = false;
  };

  const close = () => {
    if (!dialog) return;
    dialog.close();
    dialog.remove();
    dialog = null;
    showToggle();
    if (opener) opener.focus();
    opener = null;
  };

  /**
   * Shows the banner. `reopened`: asked for from a link or the corner button (adds the current state; Escape
   * closes it). `notice`: no button. `details`: one switch per purpose (always when reopened with purposes).
   */
  const open = (reopened, notice = false, details = reopened && purposes.length > 0) => {
    dialog?.remove();
    if (toggle) toggle.hidden = true;
    const id = 'no-thanks-text';
    dialog = node('dialog', 'no-thanks');
    dialog.setAttribute('aria-labelledby', 'no-thanks-title');
    dialog.setAttribute('aria-describedby', id);
    const title = node('p', 'no-thanks__title', { id: 'no-thanks-title', innerHTML: svg(COOKIE) });
    title.append(texts.label);
    dialog.append(title);
    const signal = doNotTrack && !choice;

    const text = node('p', 'no-thanks__text', { id });
    // The switches say it all: no message over them, unless it answers the browser's signal
    const said = !details || signal;
    const dated = reopened && choice;
    if (said) text.innerHTML = signal ? texts.dnt : texts.message;
    // « Learn more » follows a sentence, never alone (the first screen has it)
    if (config.privacyUrl && (said || dated)) text.append(' ', node('a', 'no-thanks__more', { href: config.privacyUrl, textContent: texts.more }));
    if (dated) {
      const date = new Date(choice.t).toLocaleDateString(document.documentElement.lang || undefined, { dateStyle: 'long' });
      const reminder = { on: texts.accepted, some: texts.chosen, off: texts.refused }[state()];
      text[details ? 'prepend' : 'append'](node('span', 'no-thanks__state', { textContent: reminder.replace('{date}', date) }));
    }
    dialog.append(text);

    let switches = [];
    if (details) {
      const list = node('div', 'no-thanks__purposes', { role: 'group' });
      list.setAttribute('aria-labelledby', 'no-thanks-title');
      switches = purposes.map(p => {
        const input = node('input', '', { type: 'checkbox', role: 'switch', checked: granted(choice, p.id) });
        const label = node('label', 'no-thanks__purpose');
        label.append(node('b', '', { textContent: p.label }));
        if (p.description) label.append(node('small', '', { textContent: p.description }));
        label.append(input);
        list.append(label);
        return [p.id, input];
      });
      dialog.append(list);
    }

    if (!notice) {
      const actions = node('div', 'no-thanks__actions');
      if (details) {
        actions.append(button(texts.refuse, () => save(false)), button(texts.save, () => {
          const v = Object.fromEntries(switches.map(([key, input]) => [key, input.checked]));
          save(Object.values(v).every(Boolean) || (Object.values(v).some(Boolean) ? v : false));
        }));
      } else if (signal) actions.append(button(texts.acceptAnyway, () => save(true)));
      else actions.append(button(texts.refuse, () => save(false)), button(texts.accept, () => save(true)));
      dialog.append(actions);
      if (purposes.length && !details) {
        dialog.append(node('button', 'no-thanks__choose', {
          type: 'button', textContent: texts.choose,
          onclick: () => { open(reopened, false, true); dialog.querySelector('input').focus(); },
        }));
      }
    }

    if (reopened) dialog.addEventListener('keydown', e => e.key === 'Escape' && close());
    document.body.append(dialog);
    // Unasked, it leaves the focus where it is; asked for, it takes it
    if (reopened) {
      dialog.show();
      dialog.querySelector(details ? 'input' : '.no-thanks__actions button').focus();
    } else dialog.setAttribute('open', '');
  };

  /** Tells once that the browser's signal was heard, then fades out (paused while hovered or focused). */
  const notify = () => {
    try {
      if (localStorage.getItem(NOTICE)) return;
      localStorage.setItem(NOTICE, '1');
    } catch (e) {}
    open(false, true);
    const own = dialog;
    own.classList.add('no-thanks--notice');
    own.setAttribute('role', 'status');
    let timer;
    const leave = () => {
      own.classList.add('no-thanks--leaving');
      setTimeout(() => own === dialog && close(), 300);
    };
    const wait = () => { clearTimeout(timer); timer = setTimeout(leave, NOTICE_TIME); };
    const hold = () => { clearTimeout(timer); own.classList.remove('no-thanks--leaving'); };
    own.addEventListener('pointerenter', hold);
    own.addEventListener('focusin', hold);
    own.addEventListener('pointerleave', wait);
    own.addEventListener('focusout', wait);
    wait();
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
    /** Whether everything is allowed. */
    get consent() { return granted(choice); },
    /** Whether a purpose is allowed. */
    allows: id => granted(choice, id),
    /** true or false for everything, or `{ id: bool }`. */
    set: v => save(typeof v === 'object' && v ? v : !!v),
  };

  const start = () => {
    run();
    if (!choice) doNotTrack ? notify() : open(false);
    showToggle();
  };
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', start);
  else start();
})();

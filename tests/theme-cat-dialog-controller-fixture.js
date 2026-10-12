/**
 * Cat profile dialog controller fixture tests.
 *
 * Stub-host style: NO WP boot, NO DB, NO network. A minimal in-memory DOM +
 * fetch/provider stubs loads the REAL theme script
 * (pfoa-theme/assets/js/pfoa-cat-dialog-controller.js) via vm and drives it
 * behaviorally through the captured document click handler.
 *
 * Run: node tests/theme-cat-dialog-controller-fixture.js
 */
'use strict';

const vm = require('vm');
const fs = require('fs');
const path = require('path');

const CTRL_FILE = path.join(__dirname, '..', 'pfoa-theme', 'assets', 'js', 'pfoa-cat-dialog-controller.js');
const CTRL_SRC = fs.readFileSync(CTRL_FILE, 'utf8');
const EPOCH_HEADER = 'X-PFOA-Presentation-Epoch';

let failures = 0;
let passes = 0;
function check(cond, message) {
  if (!cond) {
    failures++;
    process.stdout.write('FAIL: ' + message + '\n');
    return;
  }
  passes++;
  process.stdout.write(message + ': PASS\n');
}

function tick(n) {
  const rounds = n || 8;
  let p = Promise.resolve();
  for (let i = 0; i < rounds; i++) {
    p = p.then(() => new Promise((r) => setImmediate(r)));
  }
  return p;
}

function VALID_FRAG(slug, marker) {
  const m = marker === undefined ? 'Hello' : String(marker);
  return '<div class="pfoa-cat-profile" data-pfoa-cat-profile="' + slug + '">' +
    '<h2 class="pfoa-cat-profile-title">T</h2><p>' + m + '</p></div>';
}
function GALLERY_FRAG(slug) {
  return '<div class="pfoa-cat-profile" data-pfoa-cat-profile="' + slug + '">' +
    '<h2 class="pfoa-cat-profile-title">T</h2>' +
    '<div data-pfoa-cat-gallery=""><dl><dt>' +
    '<a href="https://example.test/cats/' + slug + '/photo.jpg">' +
    '<img src="https://example.test/cats/' + slug + '/photo.jpg" alt="C"></a>' +
    '</dt><dd>Cap</dd></dl><br style="clear: both"></div></div>';
}
function YOUTUBE_FRAG(slug) {
  return '<div class="pfoa-cat-profile" data-pfoa-cat-profile="' + slug + '">' +
    '<h2 class="pfoa-cat-profile-title">T</h2><p>Hello</p>' +
    '<iframe src="https://www.youtube.com/embed/dQw4w9WgXcQ" width="560" height="315" ' +
    'title="V" allow="accelerometer; autoplay" allowfullscreen frameborder="0" loading="lazy"></iframe></div>';
}

/* ---------------- minimal element model ---------------- */

class El {
  constructor(tag, attrs) {
    this.nodeType = 1;
    this.tagName = String(tag).toUpperCase();
    this.attrs = {};
    const a = attrs || {};
    for (const k of Object.keys(a)) {
      this.attrs[k] = String(a[k]);
    }
    this.children = [];
    this.parent = null;
    this.parentNode = null;
    this.hidden = false;
    this._html = '';
    this.focusCalls = 0;
    this._host = null;
  }
  append(child) {
    child.parent = this;
    child.parentNode = this;
    this.children.push(child);
    return this;
  }
  getAttribute(name) {
    return Object.prototype.hasOwnProperty.call(this.attrs, name) ? this.attrs[name] : null;
  }
  setAttribute(name, value) {
    this.attrs[name] = String(value);
  }
  hasAttribute(name) {
    return Object.prototype.hasOwnProperty.call(this.attrs, name);
  }
  removeAttribute(name) {
    delete this.attrs[name];
  }
  focus() {
    this.focusCalls++;
    if (this._host) {
      this._host.focusLog.push(this);
    }
  }
  querySelector(sel) {
    const m = /^\[([^\]]+)\]$/.exec(String(sel).trim());
    if (!m) {
      return null;
    }
    const all = [];
    const walk = (node) => {
      for (const k of node.children || []) {
        all.push(k);
        walk(k);
      }
    };
    walk(this);
    for (const el of all) {
      if (el.hasAttribute(m[1])) {
        return el;
      }
    }
    return null;
  }
}
Object.defineProperty(El.prototype, 'innerHTML', {
  configurable: true,
  get() {
    return this._html;
  },
  set(value) {
    this._html = String(value);
    if (this._host) {
      this._host.order.push('html');
    }
  },
});

function walkDoc(node, fn) {
  for (const k of node.children || []) {
    if (fn(k) === false) {
      return false;
    }
    if (walkDoc(k, fn) === false) {
      return false;
    }
  }
  return true;
}

/* ---------------- minimal fragment HTML parser for DOMParser stub ---------------- */

function decodeFragEntities(s) {
  return String(s)
    .replace(/&amp;/g, '&')
    .replace(/&quot;/g, '"')
    .replace(/&#39;/g, "'")
    .replace(/&lt;/g, '<')
    .replace(/&gt;/g, '>')
    .replace(/&hellip;/g, '\u2026');
}
const FRAG_VOID = {
  AREA: 1, BASE: 1, BR: 1, COL: 1, EMBED: 1, HR: 1, IMG: 1,
  INPUT: 1, LINK: 1, META: 1, SOURCE: 1, TRACK: 1, WBR: 1,
};
class FragNode {
  constructor(tag, attrs) {
    this.nodeType = 1;
    this.tagName = String(tag).toUpperCase();
    this.attrs = attrs || {};
    this.children = [];
    this.childNodes = this.children;
  }
  getAttribute(name) {
    const want = String(name).toLowerCase();
    for (const k of Object.keys(this.attrs)) {
      if (String(k).toLowerCase() === want) {
        return this.attrs[k];
      }
    }
    return null;
  }
  hasAttribute(name) {
    return this.getAttribute(name) !== null;
  }
  setAttribute(name, value) {
    this.attrs[String(name).toLowerCase()] = String(value);
  }
}
function parseFragHtml(html) {
  const roots = [];
  const stack = [{ children: roots, tagName: '' }];
  const tagRe = /<!--[\s\S]*?-->|<!DOCTYPE[^<>]*>|<(\/?)([A-Za-z0-9-]+)([^<>]*?)(\/?)>/g;
  let lastIdx = 0;
  let m;
  const pushText = (t) => {
    if (!t) {
      return;
    }
    stack[stack.length - 1].children.push({ nodeType: 3, textContent: t });
  };
  while ((m = tagRe.exec(html)) !== null) {
    pushText(html.slice(lastIdx, m.index));
    lastIdx = tagRe.lastIndex;
    const full = m[0];
    if (full.indexOf('<!--') === 0 || /^<!DOCTYPE/i.test(full)) {
      continue;
    }
    const isClose = m[1] === '/';
    const tagName = m[2];
    const attrStr = m[3] || '';
    const selfClose = m[4] === '/';
    const upper = String(tagName).toUpperCase();
    if (isClose) {
      for (let i = stack.length - 1; i > 0; i--) {
        if (stack[i].tagName && String(stack[i].tagName).toUpperCase() === upper) {
          stack.length = i;
          break;
        }
      }
      continue;
    }
    const attrs = {};
    const attrRe = /([^\s=/>]+)(?:\s*=\s*(?:"([^"]*)"|'([^']*)'|([^\s"'=<>`]+)))?/g;
    let am;
    while ((am = attrRe.exec(attrStr)) !== null) {
      const nm = String(am[1]).toLowerCase();
      const vv = am[2] !== undefined ? am[2] : am[3] !== undefined ? am[3] : am[4] !== undefined ? am[4] : '';
      attrs[nm] = decodeFragEntities(vv);
    }
    const node = new FragNode(tagName, attrs);
    stack[stack.length - 1].children.push(node);
    if (!FRAG_VOID[upper] && !selfClose) {
      stack.push(node);
    }
  }
  pushText(html.slice(lastIdx));
  return roots;
}
class StubDOMParser {
  parseFromString(text, type) {
    const kids = parseFragHtml(String(text));
    return { body: { children: kids, childNodes: kids } };
  }
}

/* ---------------- stub host ---------------- */

function makeHost(opts) {
  opts = opts || {};
  const host = {
    order: [],
    focusLog: [],
    fetchCalls: [],
    pending: [],
    providerCalls: { init: [], dispose: [] },
  };

  const doc = {
    nodeType: 9,
    children: [],
    parent: null,
    parentNode: null,
    _listeners: {},
    baseURI: 'https://example.test/',
    URL: 'https://example.test/',
    addEventListener(type, fn) {
      (this._listeners[type] = this._listeners[type] || []).push(fn);
    },
    removeEventListener(type, fn) {
      this._listeners[type] = (this._listeners[type] || []).filter((f) => f !== fn);
    },
    getElementById(id) {
      let found = null;
      walkDoc(this, (el) => {
        if (el.getAttribute && el.getAttribute('id') === id) {
          found = el;
          return false;
        }
      });
      return found;
    },
    contains(node) {
      let found = false;
      walkDoc(this, (el) => {
        if (el === node) {
          found = true;
          return false;
        }
      });
      return found;
    },
    createElement(tag) {
      return new El(tag, {});
    },
  };
  host.doc = doc;

  const h = (tag, attrs) => new El(tag, attrs);
  const html = h('html');
  const body = h('body');
  html.append(body);
  html.parent = doc;
  html.parentNode = doc;
  doc.children.push(html);
  host.body = body;

  const dialog = h('div', { id: 'pfoa-cat-dialog' });
  dialog.hidden = true;
  const dlgBody = h('div', { 'data-pfoa-cat-dialog-body': '' });
  dlgBody._host = host;
  const closeBtn = h('button', { 'data-pfoa-cat-dialog-close': '' });
  dialog.append(dlgBody);
  dialog.append(closeBtn);
  body.append(dialog);
  host.dialog = dialog;
  host.dlgBody = dlgBody;
  host.closeBtn = closeBtn;

  const linkA = h('a', { href: 'https://example.test/cats/mittens/', 'data-pfoa-cat-dialog': 'mittens' });
  const linkB = h('a', { href: 'https://example.test/cats/tiger/', 'data-pfoa-cat-dialog': 'tiger' });
  const badSlug = h('a', { href: 'https://example.test/cats/x/', 'data-pfoa-cat-dialog': 'Bad_Slug!' });
  const noHref = h('a', { 'data-pfoa-cat-dialog': 'ghost' });
  const noSlug = h('a', { href: 'https://example.test/cats/plain/' });
  body.append(linkA);
  body.append(linkB);
  body.append(badSlug);
  body.append(noHref);
  body.append(noSlug);
  host.linkA = linkA;
  host.linkB = linkB;
  host.badSlug = badSlug;
  host.noHref = noHref;
  host.noSlug = noSlug;

  const fetchStub = (url, init) => {
    host.fetchCalls.push({ url, init });
    let resolve;
    let reject;
    const promise = new Promise((res, rej) => {
      resolve = res;
      reject = rej;
    });
    host.pending.push({ resolve, reject, promise });
    return promise;
  };

  const provider = {
    initProfileGalleries(scope) {
      host.providerCalls.init.push(scope);
      host.order.push('init');
      return 1;
    },
    disposeProfileGalleries(scope) {
      host.providerCalls.dispose.push(scope);
      host.order.push('dispose');
      return 1;
    },
  };
  host.provider = provider;

  const win = {
    document: doc,
    fetch: fetchStub,
    AbortController: AbortController,
    URL: URL,
    DOMParser: StubDOMParser,
    location: { href: 'https://example.test/', origin: 'https://example.test' },
  };
  if (!opts.noProvider) {
    win.PFOAGalleryLightbox = provider;
  }
  host.win = win;

  const sandbox = {
    window: win,
    document: doc,
    AbortController: AbortController,
    Promise: Promise,
    URL: URL,
    console: console,
  };
  sandbox.globalThis = sandbox;
  vm.createContext(sandbox);
  vm.runInContext(CTRL_SRC, sandbox, { filename: 'pfoa-cat-dialog-controller.js' });
  host.api = sandbox.window.PfoaCatDialogController;
  return host;
}

function clickEl(host, target, extra) {
  const ev = {
    type: 'click',
    button: 0,
    ctrlKey: false,
    metaKey: false,
    shiftKey: false,
    altKey: false,
    target: target,
    defaultPrevented: false,
    preventDefault() {
      this.defaultPrevented = true;
    },
  };
  Object.assign(ev, extra || {});
  const handlers = (host.doc._listeners.click || []).slice();
  for (const fn of handlers) {
    fn(ev);
  }
  return ev;
}

function keydown(host, key) {
  const ev = {
    type: 'keydown',
    key: key,
    target: host.doc,
    preventDefault() {},
  };
  const handlers = (host.doc._listeners.keydown || []).slice();
  for (const fn of handlers) {
    fn(ev);
  }
  return ev;
}

function makeResponse(o) {
  return {
    ok: !!o.ok,
    headers: {
      get(name) {
        if (name === EPOCH_HEADER) {
          return o.epoch === undefined ? null : o.epoch;
        }
        return null;
      },
    },
    text() {
      return Promise.resolve(o.text || '');
    },
  };
}

function fallbackHref(html) {
  const m = /<a[^>]*href="([^"]*)"/.exec(String(html));
  return m ? m[1] : null;
}

/* ---------------- tests ---------------- */

async function testInertLoad() {
  const host = makeHost();
  check(host.api && typeof host.api.start === 'function', 'controller exposes explicit start interface');
  check((host.doc._listeners.click || []).length === 0, 'load attaches no document click listeners');
  check((host.doc._listeners.keydown || []).length === 0, 'load attaches no document keydown listeners');
  check(host.fetchCalls.length === 0, 'load performs no fetch calls');
  check(host.providerCalls.init.length === 0 && host.providerCalls.dispose.length === 0,
    'load contacts provider zero times');
  check(host.dialog.hidden === true, 'load leaves dialog hidden');
}

async function testIdempotentStart() {
  const host = makeHost();
  const c1 = host.api.start({});
  const c2 = host.api.start({});
  check(c1 && c1.started === true, 'start reports started');
  check(c2 === c1, 'repeated start returns same controller');
  check((host.doc._listeners.click || []).length === 1, 'repeated start attaches exactly one click handler');
  check(typeof host.api._resetForTests === 'function', 'test-only reset hook exposed');
  host.api._resetForTests();
  check((host.doc._listeners.click || []).length === 0, 'reset detaches click handler');
  const c3 = host.api.start({});
  check(c3 !== c1 && c3.started === true, 'start after reset returns a fresh working controller');
  check((host.doc._listeners.click || []).length === 1, 'start after reset attaches one click handler');
}

async function testOneClickOneGet() {
  const host = makeHost();
  host.api.start({});
  const ev = clickEl(host, host.linkA);
  check(ev.defaultPrevented === true, 'eligible click prevents default navigation');
  check(host.fetchCalls.length === 1, 'one click produces exactly one fetch');
  const call = host.fetchCalls[0];
  check(call.init && call.init.method === 'GET', 'fragment request uses GET');
  check(call.init && call.init.credentials === 'same-origin', 'fragment request uses same-origin credentials');
  check(call.init && 'signal' in call.init, 'fragment request carries abort signal');
  check(call.url === 'https://example.test/cats/mittens/?pfoa_cat_fragment=mittens',
    'fragment URL is canonical base plus encoded slug');
  check(host.dialog.hidden === false, 'eligible open unhides dialog');
}

async function testCloseInvalidatesOutstanding() {
  const host = makeHost();
  host.api.start({ documentEpoch: 'e1' });
  clickEl(host, host.linkA);
  check(host.pending.length === 1, 'open leaves one outstanding request');
  const closeEv = clickEl(host, host.closeBtn);
  check(closeEv.defaultPrevented === true, 'close button click handled');
  check(host.dialog.hidden === true, 'close hides dialog');
  check(host.dlgBody.innerHTML === '', 'close clears dialog body');
  check(host.linkA.focusCalls === 1, 'close restores focus to invoker');
  const signal = host.fetchCalls[0].init.signal;
  check(signal && signal.aborted === true, 'close aborts outstanding request');
  host.pending[0].resolve(makeResponse({ ok: true, epoch: 'e1', text: VALID_FRAG('mittens', 'STALE') }));
  await tick();
  check(host.dlgBody.innerHTML === '', 'late response after close never alters dialog body');
  check(host.dialog.hidden === true, 'late response after close keeps dialog hidden');
}

async function testEscapeInvalidatesOutstanding() {
  const host = makeHost();
  host.api.start({ documentEpoch: 'e1' });
  clickEl(host, host.linkA);
  keydown(host, 'Escape');
  check(host.dialog.hidden === true, 'Escape hides open dialog');
  host.pending[0].resolve(makeResponse({ ok: true, epoch: 'e1', text: VALID_FRAG('mittens', 'STALE-ESC') }));
  await tick();
  check(host.dlgBody.innerHTML.indexOf('STALE-ESC') === -1, 'late response after Escape never inserts');
}

async function testNewerInvalidatesOlder() {
  const host = makeHost();
  host.api.start({ documentEpoch: 'e1' });
  clickEl(host, host.linkA);
  clickEl(host, host.linkB);
  check(host.fetchCalls.length === 2, 'two eligible clicks produce two fetches');
  host.pending[1].resolve(makeResponse({ ok: true, epoch: 'e1', text: VALID_FRAG('tiger', 'FRAG-B') }));
  await tick();
  check(host.dlgBody.innerHTML.indexOf('FRAG-B') !== -1, 'newer response inserts');
  host.pending[0].resolve(makeResponse({ ok: true, epoch: 'e1', text: VALID_FRAG('mittens', 'FRAG-A') }));
  await tick();
  check(host.dlgBody.innerHTML.indexOf('FRAG-A') === -1, 'older superseded response ignored');
  check(host.dlgBody.innerHTML.indexOf('FRAG-B') !== -1, 'newer fragment survives older resolution');
}

async function testDisposalPrecedesRemoval() {
  const host = makeHost();
  host.api.start({ documentEpoch: 'e1' });
  clickEl(host, host.linkA);
  host.pending[0].resolve(makeResponse({ ok: true, epoch: 'e1', text: VALID_FRAG('mittens', 'FRAG-OK') }));
  await tick();
  clickEl(host, host.closeBtn);
  await tick();
  const order = host.order;
  const htmlIndexes = [];
  for (let i = 0; i < order.length; i++) {
    if (order[i] === 'html') {
      htmlIndexes.push(i);
    }
  }
  check(htmlIndexes.length >= 3, 'open, replace, and close each mutate dialog body');
  let allGuarded = true;
  for (const i of htmlIndexes) {
    if (i === 0 || order[i - 1] !== 'dispose') {
      allGuarded = false;
    }
  }
  check(allGuarded, 'disposal precedes every fragment removal');
}

async function testInitFollowsInsertion() {
  const host = makeHost();
  host.api.start({ documentEpoch: 'e1' });
  clickEl(host, host.linkA);
  host.pending[0].resolve(makeResponse({ ok: true, epoch: 'e1', text: VALID_FRAG('mittens', 'FRAG-OK') }));
  await tick();
  check(host.dlgBody.innerHTML.indexOf('FRAG-OK') !== -1, 'matching epoch inserts fragment');
  check(host.providerCalls.init.length === 2, 'init called once for initial galleries and once after insert');
  check(host.providerCalls.init[host.providerCalls.init.length - 1] === host.dlgBody,
    'provider init follows insertion with dialog body');
  const tail = host.order.slice(-3).join(',');
  check(tail === 'dispose,html,init', 'replace order is dispose, insert, init');
}

async function testInitialGalleries() {
  const host = makeHost();
  host.api.start({});
  check(host.providerCalls.init.length === 1, 'start claims initial galleries exactly once');
  check(host.providerCalls.init[0] === host.doc, 'initial galleries use provider init with document');
  check(host.providerCalls.dispose.length === 0, 'start never calls dispose');
}

async function testMissingProviderFailsClosed() {
  const host = makeHost({ noProvider: true });
  const result = host.api.start({});
  check(result && result.started === false, 'start without provider returns not-started');
  check((host.doc._listeners.click || []).length === 0, 'failed start attaches no click handler');
  const ev = clickEl(host, host.linkA);
  check(ev.defaultPrevented === false, 'missing provider click does not prevent default');
  check(host.fetchCalls.length === 0, 'missing provider click performs no fetch');
  check(host.dialog.hidden === true, 'missing provider click opens no dialog');
}

async function testFailedFragmentPreservesCanonical() {
  const host = makeHost();
  host.api.start({ documentEpoch: 'e1' });
  clickEl(host, host.linkA);
  host.pending[0].resolve(makeResponse({ ok: false, epoch: 'e1', text: '<p>NOPE</p>' }));
  await tick();
  check(host.dialog.hidden === false, 'failed fragment keeps dialog open');
  check(host.dlgBody.innerHTML.indexOf('Open full profile') !== -1, 'failed fragment shows fallback message');
  check(fallbackHref(host.dlgBody.innerHTML) === 'https://example.test/cats/mittens/',
    'fallback anchors canonical href');
  check(host.providerCalls.init.length === 1, 'failed fragment never initializes galleries');

  const host2 = makeHost();
  host2.api.start({ documentEpoch: 'e1' });
  clickEl(host2, host2.linkA);
  host2.pending[0].reject(new Error('boom'));
  await tick();
  check(host2.dialog.hidden === false, 'thrown fetch keeps dialog open');
  check(fallbackHref(host2.dlgBody.innerHTML) === 'https://example.test/cats/mittens/',
    'thrown fetch falls back with canonical link');
  check(host2.providerCalls.init.length === 1, 'thrown fetch never initializes galleries');
}

async function testEpochGuard() {
  const host = makeHost();
  host.api.start({ documentEpoch: 'epoch-X' });

  clickEl(host, host.linkA);
  host.pending[0].resolve(makeResponse({ ok: true, text: VALID_FRAG('mittens', 'NO-EPOCH') }));
  await tick();
  check(host.dlgBody.innerHTML.indexOf('NO-EPOCH') === -1, 'missing epoch prevents insertion');
  check(host.dlgBody.innerHTML.indexOf('Open full profile') !== -1, 'missing epoch shows fallback');
  check(host.providerCalls.init.length === 1, 'missing epoch never initializes galleries');

  clickEl(host, host.linkA);
  host.pending[1].resolve(makeResponse({ ok: true, epoch: 'epoch-Y', text: VALID_FRAG('mittens', 'WRONG-EPOCH') }));
  await tick();
  check(host.dlgBody.innerHTML.indexOf('WRONG-EPOCH') === -1, 'mismatched epoch prevents insertion');
  check(host.dlgBody.innerHTML.indexOf('Open full profile') !== -1, 'mismatched epoch shows fallback');
  check(host.providerCalls.init.length === 1, 'mismatched epoch never initializes galleries');

  clickEl(host, host.linkA);
  host.pending[2].resolve(makeResponse({ ok: true, epoch: 'epoch-X', text: VALID_FRAG('mittens', 'EPOCH-OK') }));
  await tick();
  check(host.dlgBody.innerHTML.indexOf('EPOCH-OK') !== -1, 'matching epoch inserts fragment');
  check(host.providerCalls.init.length === 2, 'matching epoch initializes galleries');

  clickEl(host, host.linkA);
  host.pending[3].resolve(makeResponse({ ok: true, epoch: '', text: VALID_FRAG('mittens', 'EMPTY-EPOCH') }));
  await tick();
  check(host.dlgBody.innerHTML.indexOf('EMPTY-EPOCH') === -1, 'empty epoch prevents insertion');
}

async function testIneligibleLinks() {
  const host = makeHost();
  host.api.start({ documentEpoch: 'e1' });
  const e1 = clickEl(host, host.badSlug);
  check(e1.defaultPrevented === false, 'malformed slug does not prevent default');
  const e2 = clickEl(host, host.noHref);
  check(e2.defaultPrevented === false, 'missing href does not prevent default');
  const e3 = clickEl(host, host.noSlug);
  check(e3.defaultPrevented === false, 'missing slug does not prevent default');
  const e4 = clickEl(host, host.linkA, { ctrlKey: true });
  check(e4.defaultPrevented === false, 'modifier click does not prevent default');
  const e5 = clickEl(host, host.linkA, { button: 2 });
  check(e5.defaultPrevented === false, 'non-left click does not prevent default');
  const e6 = clickEl(host, host.linkA, { defaultPrevented: true });
  check(e6.defaultPrevented === true, 'already-prevented event left alone');
  check(host.fetchCalls.length === 0, 'ineligible interactions perform no fetch');
  check(host.dialog.hidden === true, 'ineligible interactions open no dialog');
}

function testNoCompetingOwnership() {
  const lower = CTRL_SRC.toLowerCase();
  check(lower.indexOf('photobox') === -1, 'source never references competing overlay');
  check(lower.indexOf('pboverlay') === -1, 'source never references overlay singleton');
  check(lower.indexOf('magnific') === -1, 'source never references alternate lightbox');
  check(CTRL_SRC.indexOf('initProfileGalleries') !== -1, 'source drives provider init');
  check(CTRL_SRC.indexOf('disposeProfileGalleries') !== -1, 'source drives provider dispose');
}

/* ---------------- regression tests for hardened behavior ---------------- */

async function testProviderLostBeforeResponse() {
  const host = makeHost();
  host.api.start({ documentEpoch: 'e1' });
  clickEl(host, host.linkA);
  delete host.win.PFOAGalleryLightbox;
  host.pending[0].resolve(makeResponse({ ok: true, epoch: 'e1', text: VALID_FRAG('mittens', 'LOST-PROV') }));
  await tick();
  check(host.dlgBody.innerHTML.indexOf('LOST-PROV') === -1, 'provider lost before response inserts no fragment');
  check(host.dialog.hidden === false, 'provider lost before response keeps dialog open');
  check(host.providerCalls.init.length === 1, 'provider lost before response never inits fragment');
}

async function testProviderLostBeforeClose() {
  const host = makeHost();
  host.api.start({ documentEpoch: 'e1' });
  clickEl(host, host.linkA);
  host.pending[0].resolve(makeResponse({ ok: true, epoch: 'e1', text: VALID_FRAG('mittens', 'KEEP-ME') }));
  await tick();
  check(host.dlgBody.innerHTML.indexOf('KEEP-ME') !== -1, 'provider-lost-close setup inserts');
  delete host.win.PFOAGalleryLightbox;
  clickEl(host, host.closeBtn);
  await tick();
  check(host.dialog.hidden === true, 'provider lost before close still hides dialog');
  check(host.dlgBody.innerHTML.indexOf('KEEP-ME') !== -1, 'provider lost before close preserves body');
}

async function testDisposalThrowsOnReplace() {
  const host = makeHost();
  host.api.start({ documentEpoch: 'e1' });
  clickEl(host, host.linkA);
  host.pending[0].resolve(makeResponse({ ok: true, epoch: 'e1', text: VALID_FRAG('mittens', 'FIRST') }));
  await tick();
  check(host.dlgBody.innerHTML.indexOf('FIRST') !== -1, 'dispose-throw setup inserts first');
  host.provider.disposeProfileGalleries = (scope) => {
    host.providerCalls.dispose.push(scope);
    host.order.push('dispose');
    throw new Error('dispose boom');
  };
  clickEl(host, host.linkB);
  host.pending[1].resolve(makeResponse({ ok: true, epoch: 'e1', text: VALID_FRAG('tiger', 'SECOND') }));
  await tick();
  check(host.dlgBody.innerHTML.indexOf('SECOND') === -1, 'throwing dispose blocks replacement insertion');
  check(host.dlgBody.innerHTML.indexOf('FIRST') !== -1, 'throwing dispose preserves old content');
}

async function testDisposalThrowsOnClose() {
  const host = makeHost();
  host.api.start({ documentEpoch: 'e1' });
  clickEl(host, host.linkA);
  host.pending[0].resolve(makeResponse({ ok: true, epoch: 'e1', text: VALID_FRAG('mittens', 'FIRST-CLOSE') }));
  await tick();
  check(host.dlgBody.innerHTML.indexOf('FIRST-CLOSE') !== -1, 'dispose-close setup inserts');
  host.provider.disposeProfileGalleries = (scope) => {
    host.providerCalls.dispose.push(scope);
    host.order.push('dispose');
    throw new Error('dispose boom');
  };
  clickEl(host, host.closeBtn);
  await tick();
  check(host.dialog.hidden === true, 'throwing dispose on close still hides dialog');
  check(host.dlgBody.innerHTML.indexOf('FIRST-CLOSE') !== -1, 'throwing dispose on close preserves body');
}

async function testInitThrowsFallback() {
  const host = makeHost();
  host.api.start({ documentEpoch: 'e1' });
  host.provider.initProfileGalleries = (scope) => {
    host.providerCalls.init.push(scope);
    host.order.push('init');
    throw new Error('init boom');
  };
  clickEl(host, host.linkA);
  host.pending[0].resolve(makeResponse({ ok: true, epoch: 'e1', text: VALID_FRAG('mittens', 'INIT-BOOM') }));
  await tick();
  check(host.dlgBody.innerHTML.indexOf('INIT-BOOM') === -1, 'init throw leaves no successful fragment');
  check(host.dlgBody.innerHTML.indexOf('Open full profile') !== -1, 'init throw shows fallback');
  check(fallbackHref(host.dlgBody.innerHTML) === 'https://example.test/cats/mittens/',
    'init throw fallback carries canonical href');
}

async function testDestroyWhileOpen() {
  const host = makeHost();
  const h = host.api.start({ documentEpoch: 'e1' });
  clickEl(host, host.linkA);
  host.pending[0].resolve(makeResponse({ ok: true, epoch: 'e1', text: VALID_FRAG('mittens', 'DESTROY-OPEN') }));
  await tick();
  check(host.dlgBody.innerHTML.indexOf('DESTROY-OPEN') !== -1, 'destroy-open setup inserts');
  h.destroy();
  check((host.doc._listeners.click || []).length === 0, 'destroy while open removes listeners');
  check(host.dlgBody.innerHTML === '', 'destroy while open clears body when safe');
  check(host.dialog.hidden === true, 'destroy while open hides dialog');
  check(host.linkA.focusCalls >= 1, 'destroy while open restores focus');
}

async function testDestroyWithPending() {
  const host = makeHost();
  const h = host.api.start({ documentEpoch: 'e1' });
  clickEl(host, host.linkA);
  check(host.pending.length === 1, 'destroy-pending setup leaves outstanding request');
  h.destroy();
  check((host.doc._listeners.click || []).length === 0, 'destroy with pending removes listeners');
  check(host.dialog.hidden === true, 'destroy with pending hides dialog');
  host.pending[0].resolve(makeResponse({ ok: true, epoch: 'e1', text: VALID_FRAG('mittens', 'LATE-AFTER-DESTROY') }));
  await tick();
  check(host.dlgBody.innerHTML.indexOf('LATE-AFTER-DESTROY') === -1, 'late after destroy never inserts');
  check(host.dialog.hidden === true, 'late after destroy keeps dialog hidden');
}

async function testRapidCloseReopen() {
  const host = makeHost();
  host.api.start({ documentEpoch: 'e1' });
  clickEl(host, host.linkA);
  clickEl(host, host.closeBtn);
  clickEl(host, host.linkB);
  check(host.fetchCalls.length === 2, 'rapid close/reopen produces two fetches');
  host.pending[1].resolve(makeResponse({ ok: true, epoch: 'e1', text: VALID_FRAG('tiger', 'RAPID-B') }));
  await tick();
  check(host.dlgBody.innerHTML.indexOf('RAPID-B') !== -1, 'rapid newer inserts');
  host.pending[0].resolve(makeResponse({ ok: true, epoch: 'e1', text: VALID_FRAG('mittens', 'RAPID-A') }));
  await tick();
  check(host.dlgBody.innerHTML.indexOf('RAPID-A') === -1, 'rapid older ignored');
  check(host.dlgBody.innerHTML.indexOf('RAPID-B') !== -1, 'rapid newer survives');
  const tail = host.order.slice(-3).join(',');
  check(tail === 'dispose,html,init', 'rapid order is dispose,html,init');
}

async function testUnsafeCanonicalLinks() {
  const host = makeHost();
  host.api.start({ documentEpoch: 'e1' });
  const evil = new El('a', { href: 'https://evil.test/cats/mittens/', 'data-pfoa-cat-dialog': 'mittens' });
  host.body.append(evil);
  const e1 = clickEl(host, evil);
  check(e1.defaultPrevented === false, 'cross-origin slug mismatch does not prevent default');
  const js = new El('a', { href: 'javascript:alert(1)', 'data-pfoa-cat-dialog': 'mittens' });
  host.body.append(js);
  const e2 = clickEl(host, js);
  check(e2.defaultPrevented === false, 'unsafe javascript protocol does not prevent default');
  const dl = new El('a', { href: 'data:text/html,<p>x</p>', 'data-pfoa-cat-dialog': 'mittens' });
  host.body.append(dl);
  const e3 = clickEl(host, dl);
  check(e3.defaultPrevented === false, 'unsafe data protocol does not prevent default');
  const mismatch = new El('a', { href: 'https://example.test/cats/tiger/', 'data-pfoa-cat-dialog': 'mittens' });
  host.body.append(mismatch);
  const e4 = clickEl(host, mismatch);
  check(e4.defaultPrevented === false, 'slug-vs-URL mismatch does not prevent default');
  check(host.fetchCalls.length === 0, 'unsafe canonical links perform no fetch');
  check(host.dialog.hidden === true, 'unsafe canonical links open no dialog');
}

async function testTargetAndDownloadLinks() {
  const host = makeHost();
  host.api.start({ documentEpoch: 'e1' });
  const t = new El('a', {
    href: 'https://example.test/cats/mittens/',
    'data-pfoa-cat-dialog': 'mittens',
    target: '_blank',
  });
  host.body.append(t);
  const e1 = clickEl(host, t);
  check(e1.defaultPrevented === false, 'target blank does not intercept');
  const d = new El('a', {
    href: 'https://example.test/cats/mittens/',
    'data-pfoa-cat-dialog': 'mittens',
    download: '',
  });
  host.body.append(d);
  const e2 = clickEl(host, d);
  check(e2.defaultPrevented === false, 'download link does not intercept');
  check(host.fetchCalls.length === 0, 'target/download perform no fetch');
  check(host.dialog.hidden === true, 'target/download open no dialog');
  const self = new El('a', {
    href: 'https://example.test/cats/mittens/',
    'data-pfoa-cat-dialog': 'mittens',
    target: '_self',
  });
  host.body.append(self);
  const e3 = clickEl(host, self);
  check(e3.defaultPrevented === true, 'target self remains eligible');
}

async function testEpochPermissiveOverride() {
  const host = makeHost();
  host.api.start({ documentEpoch: 'e1', isEpochMatch: () => true });
  clickEl(host, host.linkA);
  host.pending[0].resolve(makeResponse({ ok: true, text: VALID_FRAG('mittens', 'PERM-MISSING') }));
  await tick();
  check(host.dlgBody.innerHTML.indexOf('PERM-MISSING') === -1, 'permissive missing epoch still prevents insertion');
  check(host.dlgBody.innerHTML.indexOf('Open full profile') !== -1, 'permissive missing epoch shows fallback');
  check(host.providerCalls.init.length === 1, 'permissive missing epoch never inits');
  clickEl(host, host.linkA);
  host.pending[1].resolve(makeResponse({ ok: true, epoch: 'WRONG', text: VALID_FRAG('mittens', 'PERM-WRONG') }));
  await tick();
  check(host.dlgBody.innerHTML.indexOf('PERM-WRONG') === -1, 'permissive mismatched epoch still prevents insertion');
  check(host.dlgBody.innerHTML.indexOf('Open full profile') !== -1, 'permissive mismatched epoch shows fallback');
}

async function testMissingDocumentEpochPermissive() {
  const host = makeHost();
  host.api.start({ isEpochMatch: () => true });
  clickEl(host, host.linkA);
  host.pending[0].resolve(makeResponse({ ok: true, epoch: 'e1', text: VALID_FRAG('mittens', 'NO-DOC-EPOCH') }));
  await tick();
  check(host.dlgBody.innerHTML.indexOf('NO-DOC-EPOCH') === -1, 'missing document epoch never inserts even when permissive');
  check(host.dlgBody.innerHTML.indexOf('Open full profile') !== -1, 'missing document epoch shows fallback');
  check(host.providerCalls.init.length === 1, 'missing document epoch never inits');
}

async function testFullDocumentRejected() {
  const host = makeHost();
  host.api.start({ documentEpoch: 'e1' });
  clickEl(host, host.linkA);
  host.pending[0].resolve(makeResponse({
    ok: true,
    epoch: 'e1',
    text: '<!DOCTYPE html><html><head><title>T</title></head><body>' +
      '<div class="pfoa-cat-profile" data-pfoa-cat-profile="mittens"><p>Hi</p></div>' +
      '</body></html>',
  }));
  await tick();
  check(host.dlgBody.innerHTML.indexOf('Open full profile') !== -1, 'full-document response shows fallback');
  check(host.providerCalls.init.length === 1, 'full-document response never inits');
}

async function testInjectionRejected() {
  const host = makeHost();
  host.api.start({ documentEpoch: 'e1' });
  clickEl(host, host.linkA);
  host.pending[0].resolve(makeResponse({
    ok: true,
    epoch: 'e1',
    text: '<div class="pfoa-cat-profile" data-pfoa-cat-profile="mittens"><p onclick="alert(1)">Hi</p></div>',
  }));
  await tick();
  check(host.dlgBody.innerHTML.indexOf('Open full profile') !== -1, 'event-handler injection shows fallback');
  check(host.providerCalls.init.length === 1, 'event-handler injection never inits');

  const host2 = makeHost();
  host2.api.start({ documentEpoch: 'e1' });
  clickEl(host2, host2.linkA);
  host2.pending[0].resolve(makeResponse({
    ok: true,
    epoch: 'e1',
    text: '<div class="pfoa-cat-profile" data-pfoa-cat-profile="mittens"><script>alert(1)</script><p>Hi</p></div>',
  }));
  await tick();
  check(host2.dlgBody.innerHTML.indexOf('Open full profile') !== -1, 'script element shows fallback');

  const host3 = makeHost();
  host3.api.start({ documentEpoch: 'e1' });
  clickEl(host3, host3.linkA);
  host3.pending[0].resolve(makeResponse({
    ok: true,
    epoch: 'e1',
    text: '<div class="pfoa-cat-profile" data-pfoa-cat-profile="mittens"><a href="javascript:alert(1)">x</a></div>',
  }));
  await tick();
  check(host3.dlgBody.innerHTML.indexOf('Open full profile') !== -1, 'javascript href shows fallback');
}

async function testValidGalleryAndYouTube() {
  const host = makeHost();
  host.api.start({ documentEpoch: 'e1' });
  clickEl(host, host.linkA);
  host.pending[0].resolve(makeResponse({ ok: true, epoch: 'e1', text: GALLERY_FRAG('mittens') }));
  await tick();
  check(host.dlgBody.innerHTML.indexOf('data-pfoa-cat-gallery') !== -1, 'gallery fragment inserts');
  check(host.providerCalls.init.length === 2, 'gallery fragment inits galleries');
  const tail = host.order.slice(-3).join(',');
  check(tail === 'dispose,html,init', 'gallery order is dispose,html,init');

  const host2 = makeHost();
  host2.api.start({ documentEpoch: 'e1' });
  clickEl(host2, host2.linkA);
  host2.pending[0].resolve(makeResponse({ ok: true, epoch: 'e1', text: YOUTUBE_FRAG('mittens') }));
  await tick();
  check(host2.dlgBody.innerHTML.indexOf('youtube.com/embed') !== -1, 'youtube embed inserts');
  check(host2.providerCalls.init.length === 2, 'youtube embed inits galleries');
}

async function testStartAfterDestroy() {
  const host = makeHost();
  const c1 = host.api.start({ documentEpoch: 'e1' });
  c1.destroy();
  check((host.doc._listeners.click || []).length === 0, 'destroy clears listeners for restart');
  const c2 = host.api.start({ documentEpoch: 'e1' });
  check(c2 !== c1 && c2.started === true, 'start after destroy returns fresh controller');
  check((host.doc._listeners.click || []).length === 1, 'start after destroy attaches one handler');
  const ev = clickEl(host, host.linkA);
  check(ev.defaultPrevented === true, 'fresh controller after destroy still works');
  check(host.fetchCalls.length === 1, 'fresh controller after destroy fetches');
}

(async () => {
  await testInertLoad();
  await testIdempotentStart();
  await testOneClickOneGet();
  await testCloseInvalidatesOutstanding();
  await testEscapeInvalidatesOutstanding();
  await testNewerInvalidatesOlder();
  await testDisposalPrecedesRemoval();
  await testInitFollowsInsertion();
  await testInitialGalleries();
  await testMissingProviderFailsClosed();
  await testFailedFragmentPreservesCanonical();
  await testEpochGuard();
  await testIneligibleLinks();
  testNoCompetingOwnership();
  await testProviderLostBeforeResponse();
  await testProviderLostBeforeClose();
  await testDisposalThrowsOnReplace();
  await testDisposalThrowsOnClose();
  await testInitThrowsFallback();
  await testDestroyWhileOpen();
  await testDestroyWithPending();
  await testRapidCloseReopen();
  await testUnsafeCanonicalLinks();
  await testTargetAndDownloadLinks();
  await testEpochPermissiveOverride();
  await testMissingDocumentEpochPermissive();
  await testFullDocumentRejected();
  await testInjectionRejected();
  await testValidGalleryAndYouTube();
  await testStartAfterDestroy();
  if (failures > 0) {
    process.stdout.write('CAT-DIALOG-CONTROLLER FIXTURE: ' + failures + ' FAILURE(S)\n');
    process.exit(1);
  }
  process.stdout.write('ALL CAT-DIALOG-CONTROLLER FIXTURE TESTS: PASS (' + passes + ' checks)\n');
})().catch((e) => {
  process.stderr.write('HARNESS ERROR: ' + (e && e.stack ? e.stack : e) + '\n');
  process.exit(1);
});

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
  host.pending[0].resolve(makeResponse({ ok: true, epoch: 'e1', text: '<p>STALE</p>' }));
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
  host.pending[0].resolve(makeResponse({ ok: true, epoch: 'e1', text: '<p>STALE-ESC</p>' }));
  await tick();
  check(host.dlgBody.innerHTML.indexOf('STALE-ESC') === -1, 'late response after Escape never inserts');
}

async function testNewerInvalidatesOlder() {
  const host = makeHost();
  host.api.start({ documentEpoch: 'e1' });
  clickEl(host, host.linkA);
  clickEl(host, host.linkB);
  check(host.fetchCalls.length === 2, 'two eligible clicks produce two fetches');
  host.pending[1].resolve(makeResponse({ ok: true, epoch: 'e1', text: '<p>FRAG-B</p>' }));
  await tick();
  check(host.dlgBody.innerHTML.indexOf('FRAG-B') !== -1, 'newer response inserts');
  host.pending[0].resolve(makeResponse({ ok: true, epoch: 'e1', text: '<p>FRAG-A</p>' }));
  await tick();
  check(host.dlgBody.innerHTML.indexOf('FRAG-A') === -1, 'older superseded response ignored');
  check(host.dlgBody.innerHTML.indexOf('FRAG-B') !== -1, 'newer fragment survives older resolution');
}

async function testDisposalPrecedesRemoval() {
  const host = makeHost();
  host.api.start({ documentEpoch: 'e1' });
  clickEl(host, host.linkA);
  host.pending[0].resolve(makeResponse({ ok: true, epoch: 'e1', text: '<p>FRAG-OK</p>' }));
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
  host.pending[0].resolve(makeResponse({ ok: true, epoch: 'e1', text: '<p>FRAG-OK</p>' }));
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
  host.pending[0].resolve(makeResponse({ ok: true, text: '<p>NO-EPOCH</p>' }));
  await tick();
  check(host.dlgBody.innerHTML.indexOf('NO-EPOCH') === -1, 'missing epoch prevents insertion');
  check(host.dlgBody.innerHTML.indexOf('Open full profile') !== -1, 'missing epoch shows fallback');
  check(host.providerCalls.init.length === 1, 'missing epoch never initializes galleries');

  clickEl(host, host.linkA);
  host.pending[1].resolve(makeResponse({ ok: true, epoch: 'epoch-Y', text: '<p>WRONG-EPOCH</p>' }));
  await tick();
  check(host.dlgBody.innerHTML.indexOf('WRONG-EPOCH') === -1, 'mismatched epoch prevents insertion');
  check(host.dlgBody.innerHTML.indexOf('Open full profile') !== -1, 'mismatched epoch shows fallback');
  check(host.providerCalls.init.length === 1, 'mismatched epoch never initializes galleries');

  clickEl(host, host.linkA);
  host.pending[2].resolve(makeResponse({ ok: true, epoch: 'epoch-X', text: '<p>EPOCH-OK</p>' }));
  await tick();
  check(host.dlgBody.innerHTML.indexOf('EPOCH-OK') !== -1, 'matching epoch inserts fragment');
  check(host.providerCalls.init.length === 2, 'matching epoch initializes galleries');

  clickEl(host, host.linkA);
  host.pending[3].resolve(makeResponse({ ok: true, epoch: '', text: '<p>EMPTY-EPOCH</p>' }));
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
  if (failures > 0) {
    process.stdout.write('CAT-DIALOG-CONTROLLER FIXTURE: ' + failures + ' FAILURE(S)\n');
    process.exit(1);
  }
  process.stdout.write('ALL CAT-DIALOG-CONTROLLER FIXTURE TESTS: PASS (' + passes + ' checks)\n');
})().catch((e) => {
  process.stderr.write('HARNESS ERROR: ' + (e && e.stack ? e.stack : e) + '\n');
  process.exit(1);
});

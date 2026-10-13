/**
 * Node smoke test for pfoa-adopted-gallery-filler.js with a hand-rolled DOM
 * shim (no dependencies). Verifies: one filler per incomplete row, no filler
 * on complete rows, no duplicates across refresh/resize, events never
 * reordered/removed, wordmark fallback when no header logo exists.
 *
 * Run: node tests/theme-adopted-gallery-filler-smoke.js
 */
'use strict';

const assert = require('assert');

function makeEl(tag, opts) {
	opts = opts || {};
	const el = {
		tagName: (tag || 'div').toUpperCase(),
		nodeType: 1,
		children: [],
		parentNode: null,
		classList: {
			_classes: new Set((opts.cls || '').split(/\s+/).filter(Boolean)),
			contains(c) { return this._classes.has(c); },
			add(c) { this._classes.add(c); },
		},
		attributes: {},
		style: {},
		textContent: '',
		offsetTop: opts.top || 0,
		clientWidth: opts.width || 0,
		nextSibling: null,
		get className() { return [...this.classList._classes].join(' '); },
		set className(v) { this.classList._classes = new Set(String(v).split(/\s+/).filter(Boolean)); },
		getAttribute(k) { return Object.prototype.hasOwnProperty.call(this.attributes, k) ? this.attributes[k] : null; },
		setAttribute(k, v) { this.attributes[k] = String(v); },
		appendChild(c) { return this.insertBefore(c, null); },
		insertBefore(c, ref) {
			if (c.parentNode) c.parentNode.removeChild(c);
			c.parentNode = this;
			const idx = ref ? this.children.indexOf(ref) : -1;
			if (idx === -1) this.children.push(c);
			else this.children.splice(idx, 0, c);
			syncSiblings(this);
			return c;
		},
		removeChild(c) {
			const idx = this.children.indexOf(c);
			if (idx !== -1) this.children.splice(idx, 1);
			c.parentNode = null;
			syncSiblings(this);
			return c;
		},
		querySelector(sel) {
			const all = this.querySelectorAll(sel);
			return all.length ? all[0] : null;
		},
		querySelectorAll(sel) {
			const out = [];
			const walk = (node) => {
				for (const c of node.children) {
					if (matches(c, sel)) out.push(c);
					walk(c);
				}
			};
			walk(this);
			return out;
		},
	};
	if (opts.span) el._span = opts.span;
	return el;
}

function syncSiblings(parent) {
	for (let i = 0; i < parent.children.length; i++) {
		parent.children[i].nextSibling = parent.children[i + 1] || null;
	}
}

function matches(el, sel) {
	if (sel === '.pfoa-adoption-event') return el.classList.contains('pfoa-adoption-event');
	if (sel === '.pfoa-adopted-filler' || sel === ':scope > .pfoa-adopted-filler') {
		return el.classList.contains('pfoa-adopted-filler');
	}
	if (sel === '.site-branding img.custom-logo, img.custom-logo') {
		return el.tagName === 'IMG' && el.classList.contains('custom-logo');
	}
	return false;
}

// Gallery: 6 cols. Row1: span-4 quad + span-1 single (5 used -> incomplete).
// Row2: 6 singles (complete). Row3: span-2 pair (incomplete).
const gallery = makeEl('div', { cls: 'gallery', width: 960 });
const events = [];
function addEvent(span, top) {
	const e = makeEl('div', { cls: 'pfoa-adoption-event', top, span });
	gallery.appendChild(e);
	events.push(e);
	return e;
}
addEvent(4, 0);
addEvent(1, 0);
for (let i = 0; i < 6; i++) addEvent(1, 200);
addEvent(2, 400);

const headerLogo = makeEl('img', {});
headerLogo.setAttribute('src', 'https://example.com/logo.png');

global.document = {
	readyState: 'complete',
	querySelectorAll(sel) {
		if (sel === 'body.page-id-22514 .entry-content .gallery') return [gallery];
		return [];
	},
	querySelector(sel) {
		if (sel === '.site-branding img.custom-logo, img.custom-logo') return headerLogo;
		return null;
	},
	createElement(tag) { return makeEl(tag, {}); },
	addEventListener() {},
	removeEventListener() {},
};

const listeners = {};
global.window = {
	getComputedStyle(el) {
		if (el === gallery) return { gridTemplateColumns: '1fr 1fr 1fr 1fr 1fr 1fr' };
		return { gridColumnStart: el._span ? 'span ' + el._span : 'auto', gridColumnEnd: 'auto' };
	},
	addEventListener(ev, fn) { listeners[ev] = fn; },
	removeEventListener(ev) { delete listeners[ev]; },
	clearTimeout(id) { clearTimeout(id); },
	setTimeout(fn, ms) { return setTimeout(fn, ms); },
};

const api = require('../pfoa-theme/assets/js/pfoa-adopted-gallery-filler.js');

assert(api && typeof api.start === 'function', 'public surface');
api.start();

const orderAfter = gallery.children.filter((c) => c.classList.contains('pfoa-adoption-event'));
assert.deepStrictEqual(orderAfter, events, 'events never reordered/removed');

let fillers = gallery.children.filter((c) => c.classList.contains('pfoa-adopted-filler'));
assert.strictEqual(fillers.length, 2, 'exactly one filler per incomplete row (rows 1 and 3), none for complete row 2');

for (const f of fillers) {
	assert.strictEqual(f.getAttribute('aria-hidden'), 'true', 'filler aria-hidden');
	assert.strictEqual(f.getAttribute('data-pfoa-filler'), '1', 'filler data marker');
	assert.strictEqual(f.style.gridColumnEnd, '-1', 'filler spans to row end');
	assert(!f.classList.contains('gallery-item'), 'filler excluded from counts');
	const img = f.querySelectorAll('img');
	assert.strictEqual(img.length, 0, 'no nested query across filler (shim check)');
	assert.strictEqual(f.children.length, 1, 'filler has one decorative child');
	assert.strictEqual(f.children[0].tagName, 'IMG', 'official logo used when header logo present');
	assert.strictEqual(f.children[0].getAttribute('alt'), '', 'decorative logo has empty alt');
}

// Idempotency: refresh + resize must not duplicate.
api.refresh();
api.refresh();
fillers = gallery.children.filter((c) => c.classList.contains('pfoa-adopted-filler'));
assert.strictEqual(fillers.length, 2, 'no duplicates after repeated refresh');
assert(listeners.resize, 'resize listener attached');
assert.deepStrictEqual(
	gallery.children.filter((c) => c.classList.contains('pfoa-adoption-event')),
	events,
	'events still untouched after refresh'
);

// Wordmark fallback when no header logo.
global.document.querySelector = () => null;
api.refresh();
fillers = gallery.children.filter((c) => c.classList.contains('pfoa-adopted-filler'));
assert.strictEqual(fillers.length, 2, 'fillers still placed without logo');
assert.strictEqual(fillers[0].children[0].tagName, 'SPAN', 'wordmark fallback');
assert.strictEqual(fillers[0].children[0].textContent, 'PFOA', 'wordmark text');

api._resetForTests();
console.log('PASS: adopted gallery filler smoke (placement, dedupe, order, fallback)');
process.exit(0);

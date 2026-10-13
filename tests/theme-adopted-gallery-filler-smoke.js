/**
 * Node smoke test for pfoa-adopted-gallery-filler.js with a hand-rolled DOM
 * shim (no dependencies). Verifies: one filler per incomplete row with
 * explicit span remainder (gridColumnStart 'span N', gridColumnEnd 'auto',
 * data-pfoa-filler-span), black-paw inline SVG (no logo img, no wordmark),
 * no filler on complete rows, no duplicates across refresh/resize, events
 * never reordered/removed, paw renders logo-independently.
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
	// Minimal innerHTML support: the filler sets an inline <svg> paw via
	// innerHTML or createElementNS. Parse a single <svg> payload into one
	// SVG child so assertions stay meaningful without a real DOM.
	Object.defineProperty(el, 'innerHTML', {
		get() { return el._html || ''; },
		set(html) {
			el._html = String(html);
			el.children = [];
			if (/<svg[\s>]/i.test(el._html)) {
				const svg = makeEl('svg', {});
				svg.setAttribute('aria-hidden', 'true');
				el.children.push(svg);
				svg.parentNode = el;
				syncSiblings(el);
			}
		},
		configurable: true,
	});
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
	if (sel === 'img') return el.tagName === 'IMG';
	if (sel === 'span') return el.tagName === 'SPAN';
	if (sel === 'svg') return el.tagName === 'SVG';
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
	createElementNS(ns, tag) { return makeEl(tag, {}); },
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
	assert(/^span\s+\d+$/i.test(f.style.gridColumnStart || ''), 'filler explicit span remainder (gridColumnStart "span N")');
	assert.strictEqual(f.style.gridColumnEnd, 'auto', 'filler gridColumnEnd auto (never -1)');
	assert(!f.classList.contains('gallery-item'), 'filler excluded from counts');
	assert.strictEqual(f.querySelectorAll('img').length, 0, 'no logo img in filler');
	assert.strictEqual(f.querySelectorAll('span').length, 0, 'no wordmark span in filler');
	assert.strictEqual(f.children.length, 1, 'filler has one decorative child');
	assert.strictEqual(String(f.children[0].tagName).toUpperCase(), 'SVG', 'black paw inline SVG');
	assert.strictEqual(f.children[0].getAttribute('aria-hidden'), 'true', 'paw aria-hidden');
}

// Row1: span-4 + span-1 = 5 of 6 used -> explicit span 1.
// Row3: span-2 = 2 of 6 used -> explicit span 4.
assert.strictEqual(fillers[0].style.gridColumnStart, 'span 1', 'row1 remainder span 1');
assert.strictEqual(fillers[1].style.gridColumnStart, 'span 4', 'row3 remainder span 4');
assert.strictEqual(fillers[0].getAttribute('data-pfoa-filler-span'), '1', 'row1 span marker 1');
assert.strictEqual(fillers[1].getAttribute('data-pfoa-filler-span'), '4', 'row3 span marker 4');

// AG-09 shared card geometry: N-slot filler == N-slot real card outer
// geometry — filler span plus row used slots equals row capacity, so the
// filler occupies exactly the remainder with no independent sizing.
function spanCount(f) {
	return parseInt(String(f.style.gridColumnStart || '').replace(/[^0-9]/g, ''), 10) || 0;
}
assert.strictEqual(spanCount(fillers[0]) + 5, 6, 'row1 N-slot outer geometry: 5 used + span 1 == 6');
assert.strictEqual(spanCount(fillers[1]) + 2, 6, 'row3 N-slot outer geometry: 2 used + span 4 == 6');
assert.strictEqual(gallery.children[2], fillers[0], 'row1 filler aligned directly after row final event card');
assert.strictEqual(gallery.children[gallery.children.length - 1], fillers[1], 'row3 filler aligned directly after row final event card');
for (const f of fillers) {
	assert.strictEqual(f.style.width, undefined, 'no independent filler width (span-only geometry)');
	assert.strictEqual(f.style.margin, undefined, 'no independent filler margin');
	assert.strictEqual(f.style.height, undefined, 'no independent filler height (stretch row alignment only)');
	assert.strictEqual(f.style.padding, undefined, 'no independent filler padding override via JS');
	assert.strictEqual(f.style.gap, undefined, 'no interior gaps on filler');
	assert.strictEqual(f.style.columnGap, undefined, 'no interior column gaps on filler');
	assert.strictEqual(f.style.rowGap, undefined, 'no interior row gaps on filler');
	assert.strictEqual(f.textContent, '', 'no interior text gaps in filler');
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

// Paw consistency: the filler paw is logo-independent — with no header
// logo, fillers still render the same SVG paw with the same explicit span
// (no IMG, no wordmark fallback).
global.document.querySelector = () => null;
api.refresh();
fillers = gallery.children.filter((c) => c.classList.contains('pfoa-adopted-filler'));
assert.strictEqual(fillers.length, 2, 'fillers still placed without logo');
for (const f of fillers) {
	assert.strictEqual(String(f.children[0].tagName).toUpperCase(), 'SVG', 'paw persists without header logo');
	assert.strictEqual(f.querySelectorAll('img').length, 0, 'still no logo img without header logo');
	assert.strictEqual(f.querySelectorAll('span').length, 0, 'still no wordmark without header logo');
}
assert.strictEqual(fillers[0].style.gridColumnStart, 'span 1', 'span persists without logo (row1)');
assert.strictEqual(fillers[1].style.gridColumnStart, 'span 4', 'span persists without logo (row3)');

api._resetForTests();
console.log('PASS: adopted gallery filler smoke (explicit span, paw, dedupe, order, logo-independence)');
process.exit(0);

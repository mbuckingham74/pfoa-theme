/**
 * Responsive regression fixture for pfoa-adopted-gallery-filler.js:
 * full-width (grid-column: 1 / -1) event cards consume their entire row.
 *
 * Hand-rolled DOM shim (no dependencies), same pattern as
 * theme-adopted-gallery-filler-smoke.js. Exercises six viewports with the
 * theme's responsive adopted-gallery tracks (6 cols desktop, 4 at <=1319px,
 * 3 at <=720px, 2 at <=480px):
 *   1440/1024/768/720/480/390 -> 6/4/4/3/2/2 tracks.
 *
 * Per viewport the gallery holds:
 *   Row A: one full-width card (start "1", end "-1") — complete, no filler.
 *   Row B: (N-1) singles — incomplete, exactly one filler with span 1.
 *   Row C: N singles — complete, no filler.
 *   Row D: one full-width card (start "1", end String(N+1)) — complete,
 *           covers the explicit-line variant, no filler.
 *
 * Asserts per viewport: exactly 1 filler with span 1 remainder, no filler
 * on full-width or complete rows, no standalone paw rows (filler sits
 * directly after row B's last card), events never reordered/removed, no
 * duplicates across refresh, paw SVG with no logo img / wordmark span.
 *
 * Run: node tests/theme-adopted-gallery-filler-responsive-fixture.js
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
	if (sel === 'img') return el.tagName === 'IMG';
	if (sel === 'span') return el.tagName === 'SPAN';
	if (sel === 'svg') return el.tagName === 'SVG';
	return false;
}

const VIEWPORTS = [
	{ viewport: 1440, tracks: 6 },
	{ viewport: 1024, tracks: 4 },
	{ viewport: 768, tracks: 4 },
	{ viewport: 720, tracks: 3 },
	{ viewport: 480, tracks: 2 },
	{ viewport: 390, tracks: 2 },
];

let api = null;

function runViewport(vp, total) {
	const gallery = makeEl('div', { cls: 'gallery', width: vp });
	const events = [];

	function addSingle(top) {
		const e = makeEl('div', { cls: 'pfoa-adoption-event', top });
		e._start = 'span 1';
		e._end = 'auto';
		gallery.appendChild(e);
		events.push(e);
		return e;
	}

	function addFullWidth(top, end) {
		const e = makeEl('div', { cls: 'pfoa-adoption-event', top });
		e._start = '1';
		e._end = end;
		gallery.appendChild(e);
		events.push(e);
		return e;
	}

	// Row A: full-width via -1 (own row).
	addFullWidth(0, '-1');
	// Row B: partial row of (total - 1) singles.
	for (let i = 0; i < total - 1; i++) addSingle(1000);
	const rowBLast = events[events.length - 1];
	// Row C: complete row of total singles.
	for (let i = 0; i < total; i++) addSingle(2000);
	// Row D: full-width via explicit line total+1 (own row).
	addFullWidth(3000, String(total + 1));

	const listeners = {};
	global.document = {
		readyState: 'complete',
		querySelectorAll(sel) {
			if (sel === 'body.page-id-22514 .entry-content .gallery') return [gallery];
			return [];
		},
		querySelector() { return null; },
		createElement(tag) { return makeEl(tag, {}); },
		createElementNS(ns, tag) { return makeEl(tag, {}); },
		addEventListener() {},
		removeEventListener() {},
	};
	global.window = {
		getComputedStyle(el) {
			if (el === gallery) {
				return { gridTemplateColumns: new Array(total).fill('1fr').join(' ') };
			}
			return { gridColumnStart: el._start || 'auto', gridColumnEnd: el._end || 'auto' };
		},
		addEventListener(ev, fn) { listeners[ev] = fn; },
		removeEventListener(ev) { delete listeners[ev]; },
		clearTimeout(id) { clearTimeout(id); },
		setTimeout(fn, ms) { return setTimeout(fn, ms); },
	};

	if (!api) {
		api = require('../pfoa-theme/assets/js/pfoa-adopted-gallery-filler.js');
	} else {
		api._resetForTests();
	}
	assert(api && typeof api.start === 'function', vp + 'px: public surface');
	api.start();

	const label = vp + 'px (' + total + '-col)';

	const orderAfter = gallery.children.filter((c) => c.classList.contains('pfoa-adoption-event'));
	assert.deepStrictEqual(orderAfter, events, label + ': events never reordered/removed');

	let fillers = gallery.children.filter((c) => c.classList.contains('pfoa-adopted-filler'));
	assert.strictEqual(fillers.length, 1, label + ': exactly one filler (partial row B only — none for full-width rows A/D or complete row C)');

	const f = fillers[0];
	assert.strictEqual(f.style.gridColumnStart, 'span 1', label + ': partial row remainder span 1 (' + (total - 1) + ' used + 1 == ' + total + ')');
	assert.strictEqual(f.style.gridColumnEnd, 'auto', label + ': filler gridColumnEnd auto (never -1)');
	assert.strictEqual(f.getAttribute('data-pfoa-filler-span'), '1', label + ': span marker 1');
	assert.strictEqual(f.getAttribute('aria-hidden'), 'true', label + ': filler aria-hidden');
	assert.strictEqual(f.querySelectorAll('img').length, 0, label + ': no logo img in filler');
	assert.strictEqual(f.querySelectorAll('span').length, 0, label + ': no wordmark span in filler');
	assert.strictEqual(f.children.length, 1, label + ': filler has one decorative child');
	assert.strictEqual(String(f.children[0].tagName).toUpperCase(), 'SVG', label + ': black paw inline SVG');

	// No standalone paw rows: filler sits directly after row B's last card,
	// i.e. between row B and row C — not on its own visual row.
	const fillerIdx = gallery.children.indexOf(f);
	const rowBLastIdx = gallery.children.indexOf(rowBLast);
	assert.strictEqual(fillerIdx, rowBLastIdx + 1, label + ': filler aligned directly after partial-row final card (no standalone paw row)');
	assert(fillerIdx < gallery.children.length - 1, label + ': filler is mid-gallery, complete row C and full-width row D follow it');

	// Idempotency: refresh + resize-path must not duplicate.
	api.refresh();
	api.refresh();
	fillers = gallery.children.filter((c) => c.classList.contains('pfoa-adopted-filler'));
	assert.strictEqual(fillers.length, 1, label + ': no duplicates after repeated refresh');
	assert.deepStrictEqual(
		gallery.children.filter((c) => c.classList.contains('pfoa-adoption-event')),
		events,
		label + ': events still untouched after refresh'
	);

	console.log('PASS ' + label + ': full-width rows consume full row, partial row filler span 1, no paw-only rows');
}

for (const v of VIEWPORTS) {
	runViewport(v.viewport, v.tracks);
}

api._resetForTests();
console.log('PASS: adopted gallery filler responsive full-width fixture (1440/1024/768/720/480/390)');
process.exit(0);

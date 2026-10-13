/*!
 * PFOA adopted-gallery filler placement (AG-09, presentation only).
 *
 * Inserts exactly one decorative, noninteractive PFOA panel per incomplete
 * gallery row on the Adopted gallery. The panel spans the row's remaining
 * grid tracks via an explicit span count (total tracks minus used tracks)
 * and shows an inline black cat paw (no external asset, no text),
 * centered and proportional.
 *
 * Guarantees: events are never reordered, moved, or edited — only
 * `.pfoa-adopted-filler` nodes are added/removed. Re-runs (resize, load)
 * remove existing fillers first, so duplicates are impossible. No-ops when
 * no adoption-event cards are present. Layout degrades gracefully without
 * this script (chronology, photos, grouping all come from CSS/PHP).
 *
 * Public surface: `window.PfoaAdoptedGalleryFiller = { start, refresh,
 * _resetForTests }`. Auto-starts once on DOMContentLoaded (or immediately
 * when the document is already ready). `start()` is idempotent.
 */
(function () {
	'use strict';

	var FILLER_CLASS = 'pfoa-adopted-filler';
	var EVENT_SELECTOR = '.pfoa-adoption-event';
	var MIN_GALLERY_WIDTH = 240;
	var ROW_TOLERANCE = 4;
	var RESIZE_DEBOUNCE_MS = 150;

	var state = {
		started: false,
		resizeTimer: null,
		onResize: null,
	};

	function galleryScope() {
		if (typeof document === 'undefined' || !document.querySelectorAll) {
			return [];
		}

		var galleries = document.querySelectorAll('body.page-id-22514 .entry-content .gallery');
		var scoped = [];

		for (var i = 0; i < galleries.length; i++) {
			if (galleries[i].querySelector(EVENT_SELECTOR)) {
				scoped.push(galleries[i]);
			}
		}

		return scoped;
	}

	function trackCount(gallery) {
		if (typeof window === 'undefined' || !window.getComputedStyle) {
			return 0;
		}

		var template = window.getComputedStyle(gallery).gridTemplateColumns;

		if (!template || template === 'none') {
			return 0;
		}

		return template.trim().split(/\s+/).length;
	}

	function itemSpan(item) {
		if (typeof window === 'undefined' || !window.getComputedStyle) {
			return 1;
		}

		var start = window.getComputedStyle(item).gridColumnStart || '';
		var match = /span\s+(\d+)/i.exec(start);

		if (match) {
			return Math.max(1, parseInt(match[1], 10) || 1);
		}

		var end = window.getComputedStyle(item).gridColumnEnd || '';
		match = /span\s+(\d+)/i.exec(end);

		if (match) {
			return Math.max(1, parseInt(match[1], 10) || 1);
		}

		return 1;
	}

	function clearFillers(gallery) {
		var fillers = gallery.querySelectorAll(':scope > .' + FILLER_CLASS);

		for (var i = 0; i < fillers.length; i++) {
			if (fillers[i].parentNode === gallery) {
				gallery.removeChild(fillers[i]);
			}
		}

		// Fallback for engines without :scope support in querySelectorAll.
		var direct = gallery.querySelectorAll('.' + FILLER_CLASS);

		for (var j = 0; j < direct.length; j++) {
			if (direct[j].parentNode === gallery) {
				gallery.removeChild(direct[j]);
			}
		}
	}

	function buildFiller(span) {
		var count = Math.max(1, parseInt(span, 10) || 1);
		var panel = document.createElement('div');
		panel.className = FILLER_CLASS;
		panel.setAttribute('aria-hidden', 'true');
		panel.setAttribute('data-pfoa-filler', '1');
		panel.setAttribute('data-pfoa-filler-span', String(count));

		var svgNS = 'http://www.w3.org/2000/svg';
		var svg = document.createElementNS
			? document.createElementNS(svgNS, 'svg')
			: document.createElement('svg');
		svg.setAttribute('viewBox', '0 0 100 100');
		svg.setAttribute('aria-hidden', 'true');
		svg.setAttribute('focusable', 'false');

		// Black cat paw: one large pad + four toe ovals, all near-black.
		var shapes = [
			{ cx: '50', cy: '65', rx: '18', ry: '14' },
			{ cx: '22', cy: '38', rx: '8', ry: '10' },
			{ cx: '40', cy: '28', rx: '8', ry: '10' },
			{ cx: '60', cy: '28', rx: '8', ry: '10' },
			{ cx: '78', cy: '38', rx: '8', ry: '10' }
		];

		for (var i = 0; i < shapes.length; i++) {
			var ellipse = document.createElementNS
				? document.createElementNS(svgNS, 'ellipse')
				: document.createElement('ellipse');
			ellipse.setAttribute('cx', shapes[i].cx);
			ellipse.setAttribute('cy', shapes[i].cy);
			ellipse.setAttribute('rx', shapes[i].rx);
			ellipse.setAttribute('ry', shapes[i].ry);
			ellipse.setAttribute('fill', '#111');
			svg.appendChild(ellipse);
		}

		panel.appendChild(svg);

		// Explicit remainder span: auto start + definite end resolves to
		// span 1 ending at -1 (last track only), so set span explicitly.
		panel.style.gridColumnStart = 'span ' + count;
		panel.style.gridColumnEnd = 'auto';

		return panel;
	}

	function placeFillers(gallery) {
		clearFillers(gallery);

		if (gallery.clientWidth < MIN_GALLERY_WIDTH) {
			return;
		}

		var total = trackCount(gallery);

		if (total < 1) {
			return;
		}

		var children = [];

		for (var i = 0; i < gallery.children.length; i++) {
			var child = gallery.children[i];

			if (child.nodeType === 1 && !child.classList.contains(FILLER_CLASS)) {
				children.push(child);
			}
		}

		if (children.length === 0) {
			return;
		}

		// Group direct children into visual rows by offsetTop. DOM order is
		// chronological and sparse grid flow never moves later items above
		// earlier ones, so rows are contiguous DOM runs.
		var rows = [];
		var current = null;

		for (var r = 0; r < children.length; r++) {
			var top = children[r].offsetTop;

			if (!current || Math.abs(top - current.top) > ROW_TOLERANCE) {
				current = { top: top, items: [] };
				rows.push(current);
			}

			current.items.push(children[r]);
		}

		for (var k = 0; k < rows.length; k++) {
			var used = 0;

			for (var s = 0; s < rows[k].items.length; s++) {
				used += itemSpan(rows[k].items[s]);
			}

			if (used >= total) {
				continue;
			}

			var remaining = total - used;

			if (remaining < 1) {
				continue;
			}

			var last = rows[k].items[rows[k].items.length - 1];
			var filler = buildFiller(remaining);

			// Insert directly after the row's last DOM node: the explicit
			// remainder span places the filler into this row's leftover
			// tracks, and later rows keep their positions (the filler only
			// consumes cells no event could occupy without breaking
			// chronology).
			if (last.nextSibling) {
				gallery.insertBefore(filler, last.nextSibling);
			} else {
				gallery.appendChild(filler);
			}
		}
	}

	function refresh() {
		var galleries = galleryScope();

		for (var i = 0; i < galleries.length; i++) {
			try {
				placeFillers(galleries[i]);
			} catch (err) {
				// Fail closed: an unplaceable gallery keeps its CSS/PHP
				// layout untouched (any partial filler is removed first).
				try {
					clearFillers(galleries[i]);
				} catch (ignored) {
					/* no-op */
				}
			}
		}
	}

	function scheduleRefresh() {
		if (state.resizeTimer) {
			window.clearTimeout(state.resizeTimer);
		}

		state.resizeTimer = window.setTimeout(function () {
			state.resizeTimer = null;
			refresh();
		}, RESIZE_DEBOUNCE_MS);
	}

	function start() {
		if (state.started) {
			refresh();
			return api;
		}

		state.started = true;

		if (typeof window !== 'undefined' && window.addEventListener) {
			state.onResize = scheduleRefresh;
			window.addEventListener('resize', state.onResize);
			window.addEventListener('load', refresh);
		}

		refresh();

		return api;
	}

	function resetForTests() {
		if (typeof window !== 'undefined' && window.clearTimeout && state.resizeTimer) {
			window.clearTimeout(state.resizeTimer);
		}

		if (typeof window !== 'undefined' && window.removeEventListener && state.onResize) {
			window.removeEventListener('resize', state.onResize);
			window.removeEventListener('load', refresh);
		}

		state.started = false;
		state.resizeTimer = null;
		state.onResize = null;
	}

	var api = {
		start: start,
		refresh: refresh,
		_resetForTests: resetForTests,
	};

	if (typeof window !== 'undefined') {
		window.PfoaAdoptedGalleryFiller = api;

		if (typeof document !== 'undefined') {
			if (document.readyState === 'loading' && document.addEventListener) {
				document.addEventListener('DOMContentLoaded', function onReady() {
					document.removeEventListener('DOMContentLoaded', onReady);
					start();
				});
			} else {
				start();
			}
		}
	}

	if (typeof module !== 'undefined' && module.exports) {
		module.exports = api;
	}
})();

/*!
 * PFOA adopted-gallery filler placement (AG-09, presentation only).
 *
 * Inserts exactly one decorative, noninteractive PFOA panel per incomplete
 * gallery row on the Adopted gallery. The panel spans the row's remaining
 * grid tracks with the official logo (header custom logo when available,
 * PFOA wordmark fallback), centered and proportional.
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

	function logoSource() {
		if (typeof document === 'undefined' || !document.querySelector) {
			return '';
		}

		var logo = document.querySelector('.site-branding img.custom-logo, img.custom-logo');

		if (logo && logo.getAttribute('src')) {
			return logo.getAttribute('src');
		}

		return '';
	}

	function buildFiller() {
		var panel = document.createElement('div');
		panel.className = FILLER_CLASS;
		panel.setAttribute('aria-hidden', 'true');
		panel.setAttribute('data-pfoa-filler', '1');

		var src = logoSource();

		if (src) {
			var img = document.createElement('img');
			img.setAttribute('src', src);
			img.setAttribute('alt', '');
			img.setAttribute('aria-hidden', 'true');
			img.setAttribute('draggable', 'false');
			panel.appendChild(img);
		} else {
			var wordmark = document.createElement('span');
			wordmark.className = 'pfoa-adopted-filler-wordmark';
			wordmark.setAttribute('aria-hidden', 'true');
			wordmark.textContent = 'PFOA';
			panel.appendChild(wordmark);
		}

		// Auto start resolves to the first free cell of the row the
		// reference node closes; spanning to -1 fills the row remainder.
		panel.style.gridColumnStart = 'auto';
		panel.style.gridColumnEnd = '-1';

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

			var last = rows[k].items[rows[k].items.length - 1];
			var filler = buildFiller();

			// Insert directly after the row's last DOM node: auto-placement
			// resolves the filler into this row's remainder, and later rows
			// keep their positions (the filler only consumes cells no event
			// could occupy without breaking chronology).
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

/*!
 * PFOA From the Home Front archive links (page 11290 only, presentation only).
 *
 * Article links in the archive listing (Week 276 and older entries) plus the
 * featured story CTA (e.g. Week 276 button in div.pfoa-hf-story__actions /
 * div.pfoa-hf-latest) open in a separate browser tab so the archive listing
 * (Home Front archive) stays available. Same-tab
 * navigation otherwise forces a full reload of the heavy article (large
 * images + YouTube embed) and loses the reader's archive position.
 * Scoped to archive-listing anchors (current ul.pfoa-hf-archive__recent /
 * ul.pfoa-hf-archive__older rows inside div.pfoa-hf-archive, plus the retired
 * p.pfoa-hf-archive__list paragraph variant) and the featured CTA container;
 * hash-only links are skipped.
 * No-ops off body.page-id-11290 or without archive links.
 *
 * PFOA 0.1.233 — presentation only: wraps the leading "Week NNN:" prefix of
 * each archive-listing link in span.pfoa-hf-week and the remainder in
 * span.pfoa-hf-title so the 0.1.233 article-row grid can style the week badge
 * without changing DB content or visible text order. Idempotent (skips links
 * already wrapped), skips hash-only links and links with rich markup,
 * preserves aria-labels and other attributes, archive-listing links only
 * (featured CTA untouched), no-ops off body.page-id-11290.
 */
(function () {
	'use strict';

	function wrapWeekPrefix(link) {
		if (link.querySelector('span.pfoa-hf-week')) {
			return;
		}

		var href = link.getAttribute('href');

		if (!href || '#' === href.charAt(0)) {
			return;
		}

		if (link.children.length > 0) {
			return;
		}

		var text = link.textContent || '';
		var match = text.match(/^\s*(Week\s+\d+\s*:)\s*([\s\S]*)$/);

		if (!match) {
			return;
		}

		link.textContent = '';

		var week = document.createElement('span');
		week.className = 'pfoa-hf-week';
		week.textContent = match[1];

		var title = document.createElement('span');
		title.className = 'pfoa-hf-title';
		title.textContent = match[2];

		link.appendChild(week);
		link.appendChild(title);
	}

	function init() {
		if (typeof document === 'undefined' || !document.querySelectorAll) {
			return;
		}

		if (!document.body || !document.body.classList || !document.body.classList.contains('page-id-11290')) {
			return;
		}

		var links = document.querySelectorAll('div.pfoa-hf-archive a, p.pfoa-hf-archive__list a, div.pfoa-hf-story__actions a, div.pfoa-hf-latest a');

		for (var i = 0; i < links.length; i++) {
			var link = links[i];
			var href = link.getAttribute('href');

			if (!href || '#' === href.charAt(0)) {
				continue;
			}

			link.setAttribute('target', '_blank');

			var rel = link.getAttribute('rel') || '';
			var tokens = rel.split(/\s+/).filter(function (token) {
				return '' !== token;
			});

			if (tokens.indexOf('noopener') === -1) {
				tokens.push('noopener');
			}

			link.setAttribute('rel', tokens.join(' '));
		}

		var archiveLinks = document.querySelectorAll('div.pfoa-hf-archive a, p.pfoa-hf-archive__list a');

		for (var j = 0; j < archiveLinks.length; j++) {
			wrapWeekPrefix(archiveLinks[j]);
		}
	}

	if (typeof document !== 'undefined' && 'loading' !== document.readyState) {
		init();
	} else if (typeof document !== 'undefined' && document.addEventListener) {
		document.addEventListener('DOMContentLoaded', init);
	}
})();

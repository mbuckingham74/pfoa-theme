/*!
 * PFOA From the Home Front archive links (page 11290 only, presentation only).
 *
 * Article links in the archive listing (Week 276 and older entries) open in a
 * separate browser tab so the archive listing stays available. Same-tab
 * navigation otherwise forces a full reload of the heavy article (large
 * images + YouTube embed) and loses the reader's archive position.
 * Scoped to archive-listing anchors only (current ul.pfoa-hf-archive__recent /
 * ul.pfoa-hf-archive__older rows inside div.pfoa-hf-archive, plus the retired
 * p.pfoa-hf-archive__list paragraph variant); hash-only links are skipped.
 * No-ops off body.page-id-11290 or without archive links.
 */
(function () {
	'use strict';

	function init() {
		if (typeof document === 'undefined' || !document.querySelectorAll) {
			return;
		}

		if (!document.body || !document.body.classList || !document.body.classList.contains('page-id-11290')) {
			return;
		}

		var links = document.querySelectorAll('div.pfoa-hf-archive a, p.pfoa-hf-archive__list a');

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
	}

	if (typeof document !== 'undefined' && 'loading' !== document.readyState) {
		init();
	} else if (typeof document !== 'undefined' && document.addEventListener) {
		document.addEventListener('DOMContentLoaded', init);
	}
})();

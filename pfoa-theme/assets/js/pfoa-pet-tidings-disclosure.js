/*!
 * PFOA Pet Tidings disclosure auto-open (page 4799 only, presentation only).
 *
 * Collapsed by default (no open attribute in markup). Sets open=true on
 * details.pfoa-pt-disclosure when the URL carries pfoa_pt_done (prefs form
 * redirect feedback) or when the disclosure already contains form feedback
 * (.pfoa-pt-success / .pfoa-pt-error-summary / .pfoa-pt-field-error), so the
 * result is visible without user action. No other behavior; no-ops off
 * body.page-id-4799 or without the disclosure node.
 */
(function () {
	'use strict';

	function shouldOpen(details) {
		try {
			if (typeof window !== 'undefined' && window.location && window.location.search) {
				var params = new URLSearchParams(window.location.search);

				if (params.has('pfoa_pt_done')) {
					return true;
				}
			}
		} catch (e) {
			/* URL parsing unavailable — fall through to DOM check. */
		}

		if (details && details.querySelector) {
			if (details.querySelector('.pfoa-pt-success, .pfoa-pt-error-summary, .pfoa-pt-field-error')) {
				return true;
			}
		}

		return false;
	}

	function init() {
		if (typeof document === 'undefined' || !document.querySelectorAll) {
			return;
		}

		if (!document.body || !document.body.classList || !document.body.classList.contains('page-id-4799')) {
			return;
		}

		var disclosures = document.querySelectorAll('details.pfoa-pt-disclosure');

		for (var i = 0; i < disclosures.length; i++) {
			if (shouldOpen(disclosures[i])) {
				disclosures[i].open = true;
			}
		}
	}

	if (typeof document !== 'undefined' && 'loading' !== document.readyState) {
		init();
	} else if (typeof document !== 'undefined' && document.addEventListener) {
		document.addEventListener('DOMContentLoaded', init);
	}
})();

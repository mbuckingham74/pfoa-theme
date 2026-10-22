/**
 * PFOA 0.1.203 — bonded-pair spotlight focus (presentation only).
 *
 * On the Adoptable Cats listing, when the URL carries a
 * ?bonded-pair=<a>-<b> query, the server already renders the spotlight
 * section at the top of the content (works with JS disabled). This script
 * only moves keyboard focus to the section and ensures the
 * #bonded-pair-spotlight anchor is present so the browser scrolls to it.
 * No click interception, no dialog, no-ops on every other page.
 *
 * Vanilla JS, no dependencies.
 */
( function () {
	'use strict';

	document.addEventListener( 'DOMContentLoaded', function () {
		if ( window.location.search.indexOf( 'bonded-pair=' ) === -1 ) {
			return;
		}

		var spotlight = document.getElementById( 'bonded-pair-spotlight' );

		if ( ! spotlight ) {
			return;
		}

		if ( window.location.hash !== '#bonded-pair-spotlight' ) {
			try {
				window.history.replaceState(
					null,
					'',
					window.location.pathname + window.location.search + '#bonded-pair-spotlight'
				);
			} catch ( err ) {
				// History API unavailable (e.g. sandboxed iframe): the anchor
				// is progressive enhancement only; the spotlight is visible
				// at the top of the listing regardless.
			}
		}

		var reduceMotion = false;

		if ( window.matchMedia ) {
			try {
				reduceMotion = window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;
			} catch ( err ) {
				reduceMotion = false;
			}
		}

		try {
			spotlight.focus( { preventScroll: true } );
		} catch ( err ) {
			spotlight.focus();
		}

		try {
			spotlight.scrollIntoView( reduceMotion ? { block: 'start' } : { behavior: 'smooth', block: 'start' } );
		} catch ( err ) {
			spotlight.scrollIntoView();
		}
	} );
} )();

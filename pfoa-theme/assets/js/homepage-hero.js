/**
 * Homepage hero carousel controls.
 *
 * Manual previous/next rotation plus timed auto-advance. The auto-advance
 * timer pauses on hover/focus and for visitors who prefer reduced motion,
 * and can be explicitly paused/resumed with the Pause/Play toggle button.
 * The crossfade transition is disabled for visitors who prefer reduced
 * motion (see style.css).
 */
( function () {
	'use strict';

	var ALLOWED_INTERVALS = [ 3500, 5000, 7000 ];
	var DEFAULT_INTERVAL = 5000;

	function getInterval( media ) {
		var raw = parseInt( media.getAttribute( 'data-carousel-interval' ), 10 );

		if ( ALLOWED_INTERVALS.indexOf( raw ) === -1 ) {
			return DEFAULT_INTERVAL;
		}

		return raw;
	}

	function prefersReducedMotion() {
		return window.matchMedia && window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;
	}

	function showSlide( slides, index ) {
		var total = slides.length;
		var current = ( ( index % total ) + total ) % total;
		var allowedScales = [ '70', '80', '90', '100', '110', '120' ];

		function applyHeadlineScale( title, scale ) {
			if ( ! title ) {
				return;
			}

			if ( allowedScales.indexOf( scale ) === -1 ) {
				scale = '100';
			}

			allowedScales.forEach( function ( allowed ) {
				title.classList.remove( 'scale-' + allowed );
			} );

			title.classList.add( 'scale-' + scale );
		}

		slides.forEach( function ( slide, position ) {
			var isActive = position === current;
			slide.classList.toggle( 'is-active', isActive );

			if ( isActive ) {
				slide.removeAttribute( 'aria-hidden' );

				var headline = slide.getAttribute( 'data-headline' );

				if ( headline !== null ) {
					var title = document.getElementById( 'homepage-hero-title' );

					if ( title ) {
						title.textContent = headline;
						applyHeadlineScale( title, slide.getAttribute( 'data-headline-scale' ) );
					}
				}
			} else {
				slide.setAttribute( 'aria-hidden', 'true' );
			}

			var mediaLink = slide.querySelector( '.homepage-hero__media-link' );

			if ( mediaLink ) {
				if ( isActive ) {
					mediaLink.removeAttribute( 'tabindex' );
				} else {
					mediaLink.setAttribute( 'tabindex', '-1' );
				}
			}
		} );

		return current;
	}

	function initCarousel( media ) {
		var slides = Array.prototype.slice.call( media.querySelectorAll( '.homepage-hero__slide' ) );

		if ( slides.length < 2 ) {
			return;
		}

		var section = media.closest( '.homepage-hero' );

		if ( ! section ) {
			return;
		}

		var prev = section.querySelector( '[data-pfoa-hero-prev]' );
		var next = section.querySelector( '[data-pfoa-hero-next]' );
		var toggle = section.querySelector( '[data-pfoa-hero-toggle]' );

		if ( ! prev || ! next ) {
			return;
		}

		var current = 0;
		var timer = null;
		var explicitlyPaused = false;
		var hoverPaused = false;
		var focusPaused = false;

		function updateToggle() {
			if ( ! toggle ) {
				return;
			}

			// The toggle reflects the explicit pause choice, except under
			// reduced motion (where nothing ever auto-runs) it always shows
			// the not-running state. Transient hover/focus pauses never flip
			// the label.
			var showPaused = explicitlyPaused || prefersReducedMotion();
			var icon = toggle.querySelector( '[data-pfoa-hero-toggle-icon]' );

			toggle.setAttribute( 'aria-pressed', showPaused ? 'true' : 'false' );
			toggle.setAttribute( 'aria-label', showPaused ? 'Play carousel' : 'Pause carousel' );

			if ( icon ) {
				icon.innerHTML = showPaused ? '&#9654;' : '&#10074;&#10074;';
			}
		}

		function canAutoplay() {
			if ( explicitlyPaused ) {
				return false;
			}

			if ( hoverPaused || focusPaused ) {
				return false;
			}

			if ( prefersReducedMotion() ) {
				return false;
			}

			return true;
		}

		function stopTimer() {
			if ( timer !== null ) {
				window.clearTimeout( timer );
				timer = null;
			}
		}

		function scheduleNext() {
			stopTimer();

			if ( ! canAutoplay() ) {
				updateToggle();
				return;
			}

			timer = window.setTimeout( function () {
				timer = null;
				current = showSlide( slides, current + 1 );
				scheduleNext();
			}, getInterval( media ) );

			updateToggle();
		}

		function goTo( index ) {
			stopTimer();
			current = showSlide( slides, index );
			scheduleNext();
		}

		prev.addEventListener( 'click', function () {
			goTo( current - 1 );
		} );

		next.addEventListener( 'click', function () {
			goTo( current + 1 );
		} );

		if ( toggle ) {
			toggle.addEventListener( 'click', function () {
				explicitlyPaused = ! explicitlyPaused;

				if ( explicitlyPaused ) {
					stopTimer();
					updateToggle();
				} else {
					current = showSlide( slides, current );
					scheduleNext();
				}
			} );
		}

		section.addEventListener( 'mouseenter', function () {
			hoverPaused = true;
			stopTimer();
			updateToggle();
		} );

		section.addEventListener( 'mouseleave', function () {
			hoverPaused = false;
			scheduleNext();
		} );

		section.addEventListener( 'focusin', function () {
			focusPaused = true;
			stopTimer();
			updateToggle();
		} );

		section.addEventListener( 'focusout', function () {
			window.setTimeout( function () {
				if ( section.contains( document.activeElement ) ) {
					return;
				}

				focusPaused = false;
				scheduleNext();
			}, 0 );
		} );

		scheduleNext();
	}

	function init() {
		var carousels = document.querySelectorAll( '[data-pfoa-hero-carousel]' );

		Array.prototype.forEach.call( carousels, initCarousel );
	}

	if ( document.readyState !== 'loading' ) {
		init();
	} else {
		document.addEventListener( 'DOMContentLoaded', init );
	}
} )();

/**
 * Homepage hero carousel controls.
 *
 * Manual previous/next rotation only: no auto-advance, so there is nothing
 * to pause on hover or focus. The crossfade transition is disabled for
 * visitors who prefer reduced motion (see style.css).
 */
( function () {
	'use strict';

	function showSlide( slides, index ) {
		var total = slides.length;
		var current = ( ( index % total ) + total ) % total;

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

		if ( ! prev || ! next ) {
			return;
		}

		var current = 0;

		prev.addEventListener( 'click', function () {
			current = showSlide( slides, current - 1 );
		} );

		next.addEventListener( 'click', function () {
			current = showSlide( slides, current + 1 );
		} );
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

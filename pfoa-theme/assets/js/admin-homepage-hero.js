/**
 * Hero Section picker and list controls.
 *
 * Native wp.media only, mirroring the Homepage Cards admin pattern:
 * single-image select/replace/remove, carousel add-from-library with
 * Move Up/Move Down/Remove buttons (no drag), and an editable ordered
 * button list with the same up/down/remove controls. All edits persist
 * with the normal page save; nothing is submitted separately.
 *
 * Carousel slides render as native <details>/<summary> cards. Collapsing
 * a card never disables or removes inputs (details-closed content still
 * submits); reordering moves the whole <li> node so field associations
 * stay intact, and submission follows DOM order without renumbering the
 * name indexes.
 */
( function ( $ ) {
	'use strict';

	var singleFrame = null;
	var singleCell = null;
	var carouselFrame = null;
	var rowFrame = null;
	var rowItem = null;
	var labels = window.pfoaHeroAdmin || {};

	function moveUpLabel() {
		return labels.moveUp || 'Move Up';
	}

	function moveDownLabel() {
		return labels.moveDown || 'Move Down';
	}

	function removeLabel() {
		return labels.remove || 'Remove';
	}

	function noHeadlineLabel() {
		return labels.noHeadline || '\u2014 No headline \u2014';
	}

	function noLinkLabel() {
		return labels.noLink || '\u2014 No link \u2014';
	}

	function activeLabel() {
		return labels.active || 'Active';
	}

	function inactiveLabel() {
		return labels.inactive || 'Inactive';
	}

	function setSingleImage( cell, attachment ) {
		var idInput = cell.find( '.pfoa-hero-single-id' );
		var preview = cell.find( '.pfoa-hero-single-preview' );
		var selectButton = cell.find( '.pfoa-hero-single-select' );
		var removeButton = cell.find( '.pfoa-hero-single-remove' );

		if ( attachment && attachment.id ) {
			idInput.val( attachment.id );
			if ( attachment.sizes && attachment.sizes.thumbnail && attachment.sizes.thumbnail.url ) {
				preview.html( '<img src="' + attachment.sizes.thumbnail.url + '" width="80" height="80" alt="" />' );
			} else if ( attachment.url ) {
				preview.html( '<img src="' + attachment.url + '" width="80" height="80" alt="" />' );
			}
			selectButton.text( 'Replace' );
			removeButton.show();
		} else {
			idInput.val( '0' );
			preview.empty();
			selectButton.text( 'Select Image' );
			removeButton.hide();
		}
	}

	$( document ).on( 'click', '.pfoa-hero-single-select', function ( event ) {
		event.preventDefault();

		singleCell = $( this ).closest( '.pfoa-hero-single-cell' );

		if ( singleFrame ) {
			singleFrame.open();
			return;
		}

		singleFrame = wp.media( {
			title: 'Select Hero Image',
			button: { text: 'Use Image' },
			library: { type: 'image' },
			multiple: false
		} );

		singleFrame.on( 'select', function () {
			var attachment = singleFrame.state().get( 'selection' ).first();
			if ( attachment && singleCell && singleCell.length ) {
				setSingleImage( singleCell, attachment.toJSON() );
			}
		} );

		singleFrame.open();
	} );

	$( document ).on( 'click', '.pfoa-hero-single-remove', function ( event ) {
		event.preventDefault();

		var cell = $( this ).closest( '.pfoa-hero-single-cell' );
		if ( cell.length ) {
			setSingleImage( cell, null );
		}
	} );

	var PFOA_HERO_MAX_SLIDES = 5;

	function getSlideCount() {
		var select = $( '#pfoa-hero-carousel-slide-count' );

		if ( ! select.length ) {
			return 3;
		}

		var count = parseInt( select.val(), 10 );

		if ( isNaN( count ) || count < 2 || count > PFOA_HERO_MAX_SLIDES ) {
			return 3;
		}

		return count;
	}

	function refreshSummaryHeadline( item ) {
		var headline = item.find( '.pfoa-hero-slide-headline' );

		if ( ! headline.length ) {
			return;
		}

		var value = item.find( '.pfoa-hero-carousel-headline-input' ).val();

		headline.text( value && String( value ).trim() !== '' ? value : noHeadlineLabel() );
	}

	function refreshSummaryLink( item ) {
		var link = item.find( '.pfoa-hero-slide-link' );

		if ( ! link.length ) {
			return;
		}

		var select = item.find( '.pfoa-hero-carousel-link select' );
		var label = noLinkLabel();

		if ( select.length ) {
			var selected = select.find( 'option:selected' ).text();

			if ( selected && String( selected ).trim() !== '' ) {
				label = selected;
			}
		}

		link.text( label );
	}

	function refreshSummary( item ) {
		if ( ! item || ! item.length ) {
			return;
		}

		refreshSummaryHeadline( item );
		refreshSummaryLink( item );
	}

	function refreshInactiveSlots() {
		var list = $( '.pfoa-hero-carousel-list' );

		if ( ! list.length ) {
			return;
		}

		var count = getSlideCount();

		list.attr( 'data-slide-count', String( count ) );

		list.children( '.pfoa-hero-carousel-item' ).each( function ( position ) {
			var item = $( this );
			var inactive = position >= count;

			item.toggleClass( 'is-inactive', inactive );
			item.find( '.pfoa-hero-slide-card' ).toggleClass( 'is-inactive', inactive );

			var slotLabel = item.find( '.pfoa-hero-carousel-slot' );

			if ( slotLabel.length ) {
				slotLabel.text( 'Slide ' + ( position + 1 ) );
			}

			var pill = item.find( '.pfoa-hero-slide-status' );

			if ( pill.length ) {
				pill.text( inactive ? inactiveLabel() : activeLabel() );
				pill.toggleClass( 'is-inactive', inactive );
				pill.toggleClass( 'is-active', ! inactive );
			}

			refreshSummary( item );

			// Inputs stay enabled so inactive slot values still submit;
			// nothing here disables or removes fields.
		} );
	}

	$( document ).on( 'change', '#pfoa-hero-carousel-slide-count', function () {
		ensureRowCount( getSlideCount() );
		refreshInactiveSlots();
	} );

	$( function () {
		ensureRowCount( getSlideCount() );
		refreshInactiveSlots();
	} );

	// Live-sync the card summary while editing; the summary is display-only
	// and never carries submitted fields.
	$( document ).on( 'input', '.pfoa-hero-carousel-headline-input', function () {
		var item = $( this ).closest( '.pfoa-hero-carousel-item' );

		if ( item.length ) {
			refreshSummaryHeadline( item );
		}
	} );

	$( document ).on( 'change', '.pfoa-hero-carousel-link select', function () {
		var item = $( this ).closest( '.pfoa-hero-carousel-item' );

		if ( item.length ) {
			refreshSummaryLink( item );
		}
	} );

	function nextCarouselIndex( list ) {
		var next = parseInt( list.data( 'next-index' ), 10 );

		if ( isNaN( next ) ) {
			next = list.children( '.pfoa-hero-carousel-item' ).length;
		}

		list.data( 'next-index', next + 1 );

		return next;
	}

	function openSlideCard( item ) {
		if ( ! item || ! item.length ) {
			return;
		}

		item.find( '.pfoa-hero-slide-card' ).each( function () {
			$( this ).attr( 'open', 'open' );
			this.open = true;
		} );
	}

	function fallbackCardHtml( index, thumb, buttonLabel ) {
		return '<li class="pfoa-hero-carousel-item">' +
			'<details class="pfoa-hero-slide-card" open>' +
			'<summary class="pfoa-hero-slide-summary">' +
			'<span class="pfoa-hero-slide-badge pfoa-hero-carousel-slot"></span> ' +
			'<span class="pfoa-hero-slide-thumb">' + ( thumb || '<span class="pfoa-hero-slide-empty" aria-hidden="true"></span>' ) + '</span> ' +
			'<span class="pfoa-hero-slide-headline">' + noHeadlineLabel() + '</span> ' +
			'<span class="pfoa-hero-slide-link">' + noLinkLabel() + '</span> ' +
			'<span class="pfoa-hero-slide-status is-active">' + activeLabel() + '</span>' +
			'</summary>' +
			'<div class="pfoa-hero-slide-body">' +
			'<section class="pfoa-hero-slide-section">' +
			'<h4 class="pfoa-hero-slide-heading">Content</h4>' +
			'<span class="pfoa-hero-carousel-preview">' + ( thumb || '' ) + '</span> ' +
			'<input type="hidden" class="pfoa-hero-carousel-id" name="pfoa_hero[carousel_items][' + index + '][id]" value="0" /> ' +
			'<button type="button" class="button pfoa-hero-carousel-select">' + buttonLabel + '</button> ' +
			'<label class="pfoa-hero-carousel-link">Image destination <select name="pfoa_hero[carousel_items][' + index + '][link_page_id]"><option value="0">' + noLinkLabel() + '</option></select></label> ' +
			'<label class="pfoa-hero-carousel-headline">Hero headline <input type="text" class="regular-text pfoa-hero-carousel-headline-input" name="pfoa_hero[carousel_items][' + index + '][headline]" value="" /></label> ' +
			'</section>' +
			'<section class="pfoa-hero-slide-section">' +
			'<h4 class="pfoa-hero-slide-heading">Appearance</h4>' +
			'<div class="pfoa-hero-slide-grid">' +
			'<label class="pfoa-hero-carousel-headline-size">Headline size <select name="pfoa_hero[carousel_items][' + index + '][headline_scale]"><option value="50">50%</option><option value="60">60%</option><option value="70">70%</option><option value="80">80%</option><option value="90">90%</option><option value="100" selected="selected">Default (100%)</option><option value="110">110%</option><option value="120">120%</option></select></label> ' +
			'<label class="pfoa-hero-carousel-headline-color">Headline color <select name="pfoa_hero[carousel_items][' + index + '][headline_color]"><option value="white" selected="selected">White</option><option value="black">Black</option></select></label> ' +
			'<label class="pfoa-hero-carousel-brightness">Image brightness <select name="pfoa_hero[carousel_items][' + index + '][brightness]"><option value="100" selected="selected">Original (100%)</option><option value="110">110%</option><option value="120">120%</option><option value="130">130%</option></select></label> ' +
			'<label class="pfoa-hero-carousel-zoom">Image zoom <select name="pfoa_hero[carousel_items][' + index + '][zoom]"><option value="50">50%</option><option value="60">60%</option><option value="70">70%</option><option value="80">80%</option><option value="90">90%</option><option value="100" selected="selected">Default (100%)</option><option value="110">110%</option><option value="120">120%</option><option value="130">130%</option><option value="140">140%</option><option value="150">150%</option></select></label> ' +
			'<label class="pfoa-hero-carousel-position">Image position <select name="pfoa_hero[carousel_items][' + index + '][position]"><option value="top left">Top left</option><option value="top center">Top center</option><option value="top right">Top right</option><option value="center left">Center left</option><option value="center center" selected="selected">Center (default)</option><option value="center right">Center right</option><option value="bottom left">Bottom left</option><option value="bottom center">Bottom center</option><option value="bottom right">Bottom right</option></select></label> ' +
			'</div>' +
			'</section>' +
			'<section class="pfoa-hero-slide-section">' +
			'<h4 class="pfoa-hero-slide-heading">Actions</h4>' +
			'<div class="pfoa-hero-slide-actions">' +
			'<button type="button" class="button pfoa-hero-carousel-up">' + moveUpLabel() + '</button> ' +
			'<button type="button" class="button pfoa-hero-carousel-down">' + moveDownLabel() + '</button> ' +
			'<button type="button" class="button pfoa-hero-carousel-remove">' + removeLabel() + '</button>' +
			'</div>' +
			'</section>' +
			'</div>' +
			'</details>' +
			'</li>';
	}

	function appendEmptyRow( list ) {
		var index = nextCarouselIndex( list );
		var template = document.getElementById( 'pfoa-hero-carousel-template' );

		if ( template && template.innerHTML ) {
			var item = $( template.innerHTML.replace( /__INDEX__/g, String( index ) ) );

			list.append( item );
			openSlideCard( item );
			return item;
		}

		var fallback = $( fallbackCardHtml( index, '', 'Select Image' ) );

		list.append( fallback );

		return fallback;
	}

	// Add empty rows (from the template) until the list holds N rows.
	// Never removes rows: lowering N only dims extras via refreshInactiveSlots.
	function ensureRowCount( count ) {
		var list = $( '.pfoa-hero-carousel-list' );

		if ( ! list.length ) {
			return;
		}

		if ( isNaN( count ) || count < 2 ) {
			count = 2;
		}

		if ( count > PFOA_HERO_MAX_SLIDES ) {
			count = PFOA_HERO_MAX_SLIDES;
		}

		while ( list.children( '.pfoa-hero-carousel-item' ).length < count ) {
			appendEmptyRow( list );
		}
	}

	function addCarouselItem( attachment ) {
		if ( ! attachment || ! attachment.id ) {
			return;
		}

		var id = parseInt( attachment.id, 10 );
		var list = $( '.pfoa-hero-carousel-list' );

		if ( ! list.length || list.find( '.pfoa-hero-carousel-id[value="' + id + '"]' ).length ) {
			return;
		}

		if ( list.children( '.pfoa-hero-carousel-item' ).length >= PFOA_HERO_MAX_SLIDES ) {
			$( '.pfoa-hero-carousel-limit-notice' ).show();
			return;
		}

		$( '.pfoa-hero-carousel-limit-notice' ).hide();

		var thumb = '';

		if ( attachment.sizes && attachment.sizes.thumbnail && attachment.sizes.thumbnail.url ) {
			thumb = '<img src="' + attachment.sizes.thumbnail.url + '" width="80" height="80" alt="" />';
		} else if ( attachment.url ) {
			thumb = '<img src="' + attachment.url + '" width="80" height="80" alt="" />';
		}

		var index = nextCarouselIndex( list );
		var template = document.getElementById( 'pfoa-hero-carousel-template' );

		if ( template && template.innerHTML ) {
			var item = $( template.innerHTML.replace( /__INDEX__/g, String( index ) ) );

			item.find( '.pfoa-hero-carousel-id' ).val( id );
			item.find( '.pfoa-hero-carousel-preview' ).html( thumb );
			item.find( '.pfoa-hero-slide-thumb' ).html( thumb || '<span class="pfoa-hero-slide-empty" aria-hidden="true"></span>' );
			item.find( '.pfoa-hero-carousel-select' ).text( 'Replace Image' );
			list.append( item );
			openSlideCard( item );
			refreshInactiveSlots();
			return;
		}

		var fallback = $( fallbackCardHtml( index, thumb, 'Replace Image' ) );

		fallback.find( '.pfoa-hero-carousel-id' ).val( id );
		list.append( fallback );
		refreshInactiveSlots();
	}

	function setCarouselRowImage( item, attachment ) {
		if ( ! item || ! item.length || ! attachment || ! attachment.id ) {
			return;
		}

		var id = parseInt( attachment.id, 10 );

		if ( isNaN( id ) || ! id ) {
			return;
		}

		var list = item.closest( '.pfoa-hero-carousel-list' );

		// Dedupe by attachment ID (ignore the row being filled).
		if ( list.length ) {
			var dupe = false;

			list.find( '.pfoa-hero-carousel-id' ).each( function () {
				if ( this !== item.find( '.pfoa-hero-carousel-id' ).get( 0 ) && $( this ).val() === String( id ) ) {
					dupe = true;
					return false;
				}
			} );

			if ( dupe ) {
				return;
			}
		}

		var thumb = '';

		if ( attachment.sizes && attachment.sizes.thumbnail && attachment.sizes.thumbnail.url ) {
			thumb = '<img src="' + attachment.sizes.thumbnail.url + '" width="80" height="80" alt="" />';
		} else if ( attachment.url ) {
			thumb = '<img src="' + attachment.url + '" width="80" height="80" alt="" />';
		}

		item.find( '.pfoa-hero-carousel-id' ).val( id );
		item.find( '.pfoa-hero-carousel-preview' ).html( thumb );
		item.find( '.pfoa-hero-slide-thumb' ).html( thumb );
		item.find( '.pfoa-hero-carousel-select' ).text( 'Replace Image' );
	}

	$( document ).on( 'click', '.pfoa-hero-carousel-select', function ( event ) {
		event.preventDefault();

		rowItem = $( this ).closest( '.pfoa-hero-carousel-item' );

		if ( rowFrame ) {
			rowFrame.open();
			return;
		}

		rowFrame = wp.media( {
			title: 'Select Carousel Slide Image',
			button: { text: 'Use Image' },
			library: { type: 'image' },
			multiple: false
		} );

		rowFrame.on( 'select', function () {
			var attachment = rowFrame.state().get( 'selection' ).first();
			if ( attachment && rowItem && rowItem.length ) {
				setCarouselRowImage( rowItem, attachment.toJSON() );
				refreshInactiveSlots();
			}
		} );

		rowFrame.open();
	} );

	$( document ).on( 'click', '.pfoa-hero-carousel-add', function ( event ) {
		event.preventDefault();

		if ( carouselFrame ) {
			carouselFrame.open();
			return;
		}

		carouselFrame = wp.media( {
			title: 'Add Hero Carousel Images',
			button: { text: 'Add Images' },
			library: { type: 'image' },
			multiple: true
		} );

		carouselFrame.on( 'select', function () {
			carouselFrame.state().get( 'selection' ).each( function ( attachment ) {
				addCarouselItem( attachment.toJSON() );
			} );
		} );

		carouselFrame.open();
	} );

	$( document ).on( 'click', '.pfoa-hero-carousel-up', function ( event ) {
		event.preventDefault();

		var item = $( this ).closest( '.pfoa-hero-carousel-item' );
		item.prev( '.pfoa-hero-carousel-item' ).before( item );
		refreshInactiveSlots();
	} );

	$( document ).on( 'click', '.pfoa-hero-carousel-down', function ( event ) {
		event.preventDefault();

		var item = $( this ).closest( '.pfoa-hero-carousel-item' );
		item.next( '.pfoa-hero-carousel-item' ).after( item );
		refreshInactiveSlots();
	} );

	$( document ).on( 'click', '.pfoa-hero-carousel-remove', function ( event ) {
		event.preventDefault();

		$( this ).closest( '.pfoa-hero-carousel-item' ).remove();
		$( '.pfoa-hero-carousel-limit-notice' ).hide();
		refreshInactiveSlots();
	} );

	$( document ).on( 'click', '.pfoa-hero-cta-add', function ( event ) {
		event.preventDefault();

		var body = $( '.pfoa-hero-ctas-body' );

		if ( ! body.length ) {
			return;
		}

		var index = parseInt( body.data( 'next-index' ), 10 ) || 0;
		body.data( 'next-index', index + 1 );

		body.append(
			'<tr class="pfoa-hero-cta-row">' +
			'<td><input type="text" class="regular-text" name="pfoa_hero[ctas][' + index + '][label]" value="" /></td>' +
			'<td><input type="url" class="regular-text" name="pfoa_hero[ctas][' + index + '][url]" value="" /></td>' +
			'<td>' +
			'<button type="button" class="button pfoa-hero-cta-up">' + moveUpLabel() + '</button> ' +
			'<button type="button" class="button pfoa-hero-cta-down">' + moveDownLabel() + '</button> ' +
			'<button type="button" class="button pfoa-hero-cta-remove">' + removeLabel() + '</button>' +
			'</td>' +
			'</tr>'
		);
	} );

	$( document ).on( 'click', '.pfoa-hero-cta-up', function ( event ) {
		event.preventDefault();

		var row = $( this ).closest( '.pfoa-hero-cta-row' );
		row.prev( '.pfoa-hero-cta-row' ).before( row );
	} );

	$( document ).on( 'click', '.pfoa-hero-cta-down', function ( event ) {
		event.preventDefault();

		var row = $( this ).closest( '.pfoa-hero-cta-row' );
		row.next( '.pfoa-hero-cta-row' ).after( row );
	} );

	$( document ).on( 'click', '.pfoa-hero-cta-remove', function ( event ) {
		event.preventDefault();

		$( this ).closest( '.pfoa-hero-cta-row' ).remove();
	} );
} )( jQuery );

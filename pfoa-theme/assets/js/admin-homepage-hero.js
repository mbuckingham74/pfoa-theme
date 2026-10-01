/**
 * Hero Section picker and list controls.
 *
 * Native wp.media only, mirroring the Homepage Cards admin pattern:
 * single-image select/replace/remove, carousel add-from-library with
 * Move Up/Move Down/Remove buttons (no drag), and an editable ordered
 * button list with the same up/down/remove controls. All edits persist
 * with the normal page save; nothing is submitted separately.
 */
( function ( $ ) {
	'use strict';

	var singleFrame = null;
	var singleCell = null;
	var carouselFrame = null;
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

	function addCarouselItem( attachment ) {
		if ( ! attachment || ! attachment.id ) {
			return;
		}

		var id = parseInt( attachment.id, 10 );
		var list = $( '.pfoa-hero-carousel-list' );

		if ( ! list.length || list.find( 'input[value="' + id + '"]' ).length ) {
			return;
		}

		var thumb = '';

		if ( attachment.sizes && attachment.sizes.thumbnail && attachment.sizes.thumbnail.url ) {
			thumb = '<img src="' + attachment.sizes.thumbnail.url + '" width="80" height="80" alt="" />';
		} else if ( attachment.url ) {
			thumb = '<img src="' + attachment.url + '" width="80" height="80" alt="" />';
		}

		list.append(
			'<li class="pfoa-hero-carousel-item">' +
			'<span class="pfoa-hero-carousel-preview">' + thumb + '</span> ' +
			'<input type="hidden" name="pfoa_hero[carousel_ids][]" value="' + id + '" /> ' +
			'<button type="button" class="button pfoa-hero-carousel-up">' + moveUpLabel() + '</button> ' +
			'<button type="button" class="button pfoa-hero-carousel-down">' + moveDownLabel() + '</button> ' +
			'<button type="button" class="button pfoa-hero-carousel-remove">' + removeLabel() + '</button>' +
			'</li>'
		);
	}

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
	} );

	$( document ).on( 'click', '.pfoa-hero-carousel-down', function ( event ) {
		event.preventDefault();

		var item = $( this ).closest( '.pfoa-hero-carousel-item' );
		item.next( '.pfoa-hero-carousel-item' ).after( item );
	} );

	$( document ).on( 'click', '.pfoa-hero-carousel-remove', function ( event ) {
		event.preventDefault();

		$( this ).closest( '.pfoa-hero-carousel-item' ).remove();
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

/**
 * Homepage Cards image picker.
 *
 * Native wp.media only. Event delegation keeps the picker working for rows
 * added via Add Card (full-page POST, new row on reload) and rows preserved
 * across Move Up/Down submissions.
 */
( function ( $ ) {
	'use strict';

	var frame = null;
	var targetCell = null;

	function setCellImage( cell, attachment ) {
		var idInput = cell.find( '.pfoa-cards-image-id' );
		var preview = cell.find( '.pfoa-cards-image-preview' );
		var selectButton = cell.find( '.pfoa-cards-image-select' );
		var removeButton = cell.find( '.pfoa-cards-image-remove' );

		if ( attachment && attachment.id ) {
			idInput.val( attachment.id );
			if ( attachment.sizes && attachment.sizes.thumbnail && attachment.sizes.thumbnail.url ) {
				preview.html( '<img src="' + attachment.sizes.thumbnail.url + '" width="80" height="80" alt="" />' );
			} else if ( attachment.url ) {
				preview.html( '<img src="' + attachment.url + '" width="80" height="80" alt="" />' );
			}
			selectButton.text( selectButton.data( 'replace-label' ) || 'Replace' );
			removeButton.show();
		} else {
			idInput.val( '0' );
			preview.empty();
			selectButton.text( selectButton.data( 'select-label' ) || 'Select Image' );
			removeButton.hide();
		}
	}

	$( document ).on( 'click', '.pfoa-cards-image-select', function ( event ) {
		event.preventDefault();

		targetCell = $( this ).closest( '.pfoa-cards-image-cell' );

		if ( frame ) {
			frame.open();
			return;
		}

		frame = wp.media( {
			title: 'Select Card Image',
			button: { text: 'Use Image' },
			library: { type: 'image' },
			multiple: false,
		} );

		frame.on( 'select', function () {
			var attachment = frame.state().get( 'selection' ).first();
			if ( attachment && targetCell && targetCell.length ) {
				setCellImage( targetCell, attachment.toJSON() );
			}
		} );

		frame.open();
	} );

	$( document ).on( 'click', '.pfoa-cards-image-remove', function ( event ) {
		event.preventDefault();

		var cell = $( this ).closest( '.pfoa-cards-image-cell' );
		if ( cell.length ) {
			setCellImage( cell, null );
		}
	} );
} )( jQuery );

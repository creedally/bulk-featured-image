jQuery( document ).ready(
	function($) {
		$( '.bfie-select2' ).select2();

		jQuery( '#bfi_posttyps' ).change(
			function () {
				var val = $( this ).val();
				$( ".enable-default-image" ).parent().hide();
				$.each(
					val,
					function( index, value ) {
						$( "#enable_default_image_" + value ).parent().show();
					}
				);

			}
		).change();

		$( document ).on(
			'click',
			'.remove-featured-image',
			function(event) {
				var data_id  = $( this ).attr( 'data-id' ),
				current_page = $( this ).attr( 'data-current_page' ),
				currentobj   = $( this );
				if ( confirm( bfie_object.delete_post_message ) ) {

					bfi_add_loader( currentobj );

					var data = {
						'action': 'remove_featured_image',
						'data_id': data_id,
						'current_page': current_page,
					};
					jQuery.post(
						bfie_object.ajax_url,
						data,
						function(response) {

							if ( response.status ) {
								$( '.bfi-row-' + data_id + ' .featured-image' ).html( '' ).html( response.html );
								$( '.post-' + data_id + ' .featured_image' ).html( '' ).html( response.html );
							}

							bfi_remove_loader( currentobj );
						}
					);
				}
			}
		);

		$( document ).on(
			'click',
			'.bfi-img-uploader',
			function(e){
				e.preventDefault();
				let dataId      = $( this ).attr( 'data-id' );
				var currentobj  = $( this );
				var button      = $( this ),
				custom_uploader = wp.media(
					{
						title: 'Insert image',
						library : {
							// uploadedTo : wp.media.view.settings.post.id, // attach to the current post?
							type : 'image'
						},
						button: {
							text: 'Use this image' // button label text
						},
						multiple: false
					}
				).on(
					'select',
					function() { // it also has "open" and "close" events
						var attachment = custom_uploader.state().get( 'selection' ).first().toJSON();
						bfi_add_loader( currentobj );
						var data = {
							'action': 'add_featured_image',
							'attach_id': attachment,
							'data_id': dataId,
						};jQuery.post(
							bfie_object.ajax_url,
							data,
							function(response) {
								if ( response.status ) {
									$( '.post-' + dataId + ' .featured_image' ).html( '' ).html( response.html );
									bfi_remove_loader( currentobj );
								}
							}
						);
					}
				).open();
			}
		);
	}
);

function bfi_add_loader( id ) {

	id.append( '<span class="loader"></span>' );
}

function bfi_remove_loader( id ) {

	id.children().remove( 'loader' );
}

function bfi_drag_drop(event, id ='' ) {

	var preview_id = 'bfi_upload_preview';
	if ( parseInt( id ) > 0 ) {
		preview_id += '_' + id;
		jQuery( '#post_thumbnail_url_' + id ).parent().remove();
		jQuery( '#no_thumbnail_url_' + id ).remove();

	}

	var fileName   = URL.createObjectURL( event.target.files[0] );
	var preview    = document.getElementById( preview_id );
	var previewImg = document.createElement( "img" );
	previewImg.setAttribute( "src", fileName );
	preview.innerHTML = "";
	preview.appendChild( previewImg );
}

function bfi_drag( event, id ='') {
	var upload_file = 'bfi_upload_file';
	if ( parseInt( id ) > 0 ) {
		upload_file += '_' + id;
	}
	//document.getElementById(upload_file).parentNode.className = 'draging dragBox';
}
function bfi_drop( event, id ='') {
	var upload_file = 'bfi_upload_file';
	if ( parseInt( id ) > 0 ) {
		upload_file += '_' + id;
	}
	//document.getElementById(upload_file).parentNode.className = 'dragBox';
}

import 'select2';
import Swal from 'sweetalert2';

(function ($) {
	'use strict';

	$(document).ready(function () {

		if ($.fn.select2) {
			$('.bfie-select2').select2();
		}

		$('#bfi_posttyps').on('change', function () {
			var val = $(this).val() || [];

			$('.enable-default-image').closest('.bfi-toggle-item').hide();

			$.each(val, function (index, value) {
				$('#enable_default_image_' + value).closest('.bfi-toggle-item').show();
			});
		}).trigger('change');

		$(document).on('click', '.remove-featured-image', function (e) {
			e.preventDefault();

			var $btn        = $(this);
			var dataId      = $btn.attr('data-id');
			var currentPage = $btn.attr('data-current_page');

			Swal.fire({
				title: bfie_object.confirm_title,
				text: bfie_object.delete_post_message,
				icon: 'warning',
				showCancelButton: true,
				confirmButtonText: bfie_object.yes_text,
				cancelButtonText: bfie_object.cancel_text,
				reverseButtons: true
			}).then(function (result) {

				if (!result.isConfirmed) {
					return;
				}

				bfi_add_loader($btn);

				$.post(bfie_object.ajax_url, {
					action: 'remove_featured_image',
					data_id: dataId,
					current_page: currentPage
				})
				.done(function (response) {
					bfi_remove_loader($btn);

					if (response && response.status) {
						$('.bfi-row-' + dataId + ' .featured-image').html(response.html);
						$('.post-' + dataId + ' .featured_image').html(response.html);

						Swal.fire({
							icon: 'success',
							title: bfie_object.success_title,
							text: response.message || bfie_object.remove_success_message,
							timer: 2000,
							showConfirmButton: false
						});
					} else {
						Swal.fire({
							icon: 'error',
							title: bfie_object.error_title,
							text: (response && response.message) || bfie_object.removeDefaultMsg
						});
					}
				})
				.fail(function () {
					bfi_remove_loader($btn);

					Swal.fire({
						icon: 'error',
						title: bfie_object.error_title,
						text: bfie_object.ajax_error_message
					});
				});
			});
		});

		$(document).on('click', '.bfi-img-uploader', function (e) {
			e.preventDefault();

			var $btn   = $(this);
			var dataId = $btn.attr('data-id');

			var customUploader = wp.media({
				title: bfie_object.media_title,
				library: {
					type: 'image'
				},
				button: {
					text: bfie_object.media_button_text
				},
				multiple: false
			});

			customUploader.on('select', function () {
				var attachment = customUploader.state().get('selection').first().toJSON();

				bfi_add_loader($btn);

				$.post(bfie_object.ajax_url, {
					action: 'add_featured_image',
					attach_id: attachment,
					data_id: dataId
				})
				.done(function (response) {
					bfi_remove_loader($btn);

					if (response && response.status) {
						$('.post-' + dataId + ' .featured_image').html(response.html);

						Swal.fire({
							icon: 'success',
							title: bfie_object.success_title,
							text: response.message || bfie_object.add_success_message,
							timer: 2000,
							showConfirmButton: false
						});
					} else {
						Swal.fire({
							icon: 'error',
							title: bfie_object.error_title,
							text: (response && response.message) || bfie_object.ajax_error_message
						});
					}
				})
				.fail(function () {
					bfi_remove_loader($btn);

					Swal.fire({
						icon: 'error',
						title: bfie_object.error_title,
						text: bfie_object.ajax_error_message
					});
				});
			});

			customUploader.open();
		});

		if (typeof bfie_object !== 'undefined' && bfie_object.page_message) {
			Swal.fire({
				icon: bfie_object.page_message_type === 'error' ? 'error' : 'success',
				title: bfie_object.page_message_type === 'error' ? bfie_object.error_title : bfie_object.success_title,
				html: bfie_object.page_message,
				timer: 2500,
				showConfirmButton: false
			});
		}
	});

	window.bfi_add_loader = function ($el) {
		if ($el.find('> .loader').length === 0) {
			$el.append('<span class="loader"></span>');
		}
	};

	window.bfi_remove_loader = function ($el) {
		$el.children('.loader').remove();
	};

	window.bfi_drag_drop = function (event, id) {
		id = id || '';

		var previewId = 'bfi_upload_preview' + (parseInt(id, 10) > 0 ? '_' + id : '');
		var $preview  = jQuery('#' + previewId);

		if (parseInt(id, 10) > 0) {
			jQuery('#post_thumbnail_url_' + id).parent().remove();
			jQuery('#no_thumbnail_url_' + id).remove();
		}

		var file = event.target.files && event.target.files[0];
		$preview.empty();

		if (!file) {
			return;
		}

		var allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];

		if (allowedTypes.indexOf(file.type) === -1) {
			Swal.fire({
				icon: 'error',
				title: bfie_object.error_title,
				text: bfie_object.invalidFileType
			});

			event.target.value = '';
			return;
		}

		var fileUrl = URL.createObjectURL(file);
		$preview.append(jQuery('<img>').attr('src', fileUrl));
	};

	window.bfi_drag = function (event) {
		if (event && event.preventDefault) {
			event.preventDefault();
		}
	};

	window.bfi_drop = function (event) {
		if (event && event.preventDefault) {
			event.preventDefault();
		}
	};

})(jQuery);
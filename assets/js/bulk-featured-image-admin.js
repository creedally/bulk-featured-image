import 'select2';
import Swal from 'sweetalert2';

(function ($) {
	'use strict';

	$(document).ready(function () {

		if ($.fn.select2) {
			$('.bfie-select2').select2();
		}

		$('#bfi_posttyps').on('change', function () {
			var selectedPostTypes = $(this).val() || [];
			var $toggleGroup = $('.bfi-toggle-group');
			var toggleStates = {};

			$toggleGroup.find('.enable-default-image').each(function () {
				toggleStates[$(this).val()] = $(this).prop('checked');
			});

			$toggleGroup.empty();

			$.each(selectedPostTypes, function (index, value) {
				var id = 'enable_default_image_' + value;
				var isChecked = toggleStates[value] || false;

				var $item = $('<div>', {
					class: 'bfi-toggle-item'
				});

				var $meta = $('<div>', {
					class: 'bfi-toggle-item__meta'
				});

				$meta.append(
					$('<span>', {
						class: 'bfi-toggle-item__title',
						text: value.charAt(0).toUpperCase() + value.slice(1)
					})
				);

				$meta.append(
					$('<span>', {
						class: 'bfi-toggle-item__sub',
						text: 'Applies to ' + value
					})
				);

				var $label = $('<label>', {
					class: 'bfi-switch',
					for: id
				});

				var $input = $('<input>', {
					type: 'checkbox',
					id: id,
					class: 'bfi-switch__input enable-default-image',
					name: 'enable_default_image[]',
					value: value,
					checked: isChecked
				});

				var $slider = $('<span>', {
					class: 'bfi-switch__slider'
				});

				$label.append($input, $slider);
				$item.append($meta, $label);

				$toggleGroup.append($item);
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
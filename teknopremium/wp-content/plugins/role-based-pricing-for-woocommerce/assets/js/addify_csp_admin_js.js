jQuery(function ($) {

	jQuery('.sel2').select2();
	jQuery('.csp_applied_on_categories').select2();
	jQuery('.cps_hide_categories').select2();
	var ajaxurl = csp_php_vars.admin_url;
	var nonce = csp_php_vars.nonce;

	if ('Role Based Pricing Rules' == $('h1.wp-heading-inline').text().trim()) {
		$('a.page-title-action').after('<a class="page-title-action" href="' + csp_php_vars.import_href + '">' + csp_php_vars.import_title + '</a>' + '<a class="page-title-action" href="' + csp_php_vars.export_href + '">' + csp_php_vars.export_title + '</a>');
	}

	jQuery('.sel_pros').select2({

		ajax: {
			url: ajaxurl, // AJAX URL is predefined in WordPress admin
			dataType: 'json',
			type: 'POST',
			delay: 250, // delay in ms while typing when to perform a AJAX search
			data: function (params) {
				return {
					q: params.term, // search query
					action: 'cspsearchProducts', // AJAX action for admin-ajax.php
					nonce: nonce // AJAX nonce for admin-ajax.php
				};
			},
			processResults: function (data) {
				var options = [];
				if (data) {

					// data is the array of arrays, and each of them contains ID and the Label of the option
					$.each(data, function (index, text) { // do not forget that "index" is just auto incremented value
						options.push({ id: text[0], text: text[1] });
					});
				}
				return {
					results: options
				};
			},
			cache: true
		},
		multiple: true,
		placeholder: 'Choose Products',
		minimumInputLength: 3 // the minimum of symbols to input before perform a search

	});


	jQuery('.sel22').select2({

		ajax: {
			url: ajaxurl, // AJAX URL is predefined in WordPress admin
			dataType: 'json',
			type: 'POST',
			delay: 250, // delay in ms while typing when to perform a AJAX search
			data: function (params) {
				return {
					q: params.term, // search query
					action: 'cspsearchUsers', // AJAX action for admin-ajax.php
					nonce: nonce // AJAX nonce for admin-ajax.php
				};
			},
			processResults: function (data) {
				var options = [];
				if (data) {

					// data is the array of arrays, and each of them contains ID and the Label of the option
					$.each(data, function (index, text) { // do not forget that "index" is just auto incremented value
						options.push({ id: text[0], text: text[1] });
					});

				}
				return {
					results: options
				};
			},
			cache: true
		},
		multiple: false,
		placeholder: 'Choose Users',
		minimumInputLength: 3 // the minimum of symbols to input before perform a search

	});


	$('.save-variation-changes').prop('disabled', false);

	$('#csp_enable_hide_pirce').change(function () {
		if (this.checked) {
			//  ^
			$('#hide_div').fadeIn('fast');
		} else {
			$('#hide_div').fadeOut('fast');
		}
	});

	$('#csp_enable_hide_pirce_registered').change(function () {
		if (this.checked) {
			//  ^
			$('#userroles').fadeIn('fast');
		} else {
			$('#userroles').fadeOut('fast');
		}
	});

	$('#csp_hide_price').change(function () {
		if (this.checked) {
			//  ^
			$('#hp_price').fadeIn('fast');
		} else {
			$('#hp_price').fadeOut('fast');
		}
	});

	$('#csp_hide_cart_button').change(function () {
		if (this.checked) {
			//  ^
			$('.hp_cart').fadeIn('fast');
		} else {
			$('.hp_cart').fadeOut('fast');
		}
	});

	$('#csp_apply_on_all_products').change(function () {
		if (this.checked) {
			//  ^
			$('.hide_all_pro').fadeOut('fast');
		} else {
			$('.hide_all_pro').fadeIn('fast');
		}
	});


	//On Load

	if ($("#csp_enable_hide_pirce").is(':checked')) {
		$("#hide_div").show();  // checked
	} else {
		$("#hide_div").hide();
	}

	if ($("#csp_enable_hide_pirce_registered").is(':checked')) {
		$("#userroles").show();  // checked
	} else {
		$("#userroles").hide();
	}

	if ($("#csp_hide_price").is(':checked')) {
		$("#hp_price").show();  // checked
	} else {
		$("#hp_price").hide();
	}

	if ($("#csp_hide_cart_button").is(':checked')) {
		$(".hp_cart").show();  // checked
	} else {
		$(".hp_cart").hide();
	}


	if ($("#csp_apply_on_all_products").is(':checked')) {
		$(".hide_all_pro").hide();  // checked
	} else {
		$(".hide_all_pro").show();
	}

});

jQuery(function ($) {

	var ajaxurl = csp_php_vars.admin_url;
	var nonce = csp_php_vars.nonce;

	var progress_bar_percent = 0;

	$(".child").on("click", function () {
		$parent = $(this).prevAll(".parent");
		if ($(this).is(":checked")) {
			$parent.prop("checked", true);
		} else {
			var len = $(this).parent().find(".child:checked").length;
			$parent.prop("checked", len > 0);
		}
	});
	$(".parent").on("click", function () {
		$(this).parent().find(".child").prop("checked", this.checked);
	});

	setInterval(function () {

		var gradients = ['linear-gradient(90deg, #1D8348, #239B56, #7DCEA0, #1D8348, #239B56, #7DCEA0 )', 'linear-gradient(270deg, #1D8348, #239B56, #7DCEA0, #1D8348, #239B56, #7DCEA0 )', 'linear-gradient(90deg, #1D8348, #7DCEA0, #239B56, #1D8348, #7DCEA0, #239B56 )']
		var randomIndex = Math.floor(Math.random() * 3);

		$('.afcsp-progress-bar .ui-progressbar-value').css('background-image', gradients[randomIndex]);

	}, 500);


	$('form.addify-importer').submit(function (e) {
		e.preventDefault();

		var formData = new FormData(this);
		formData.append('afcsp_nonce', nonce);
		formData.append('action', 'afcsp_upload_csv');

		$.ajax({
			url: ajaxurl,
			type: 'POST',
			data: formData,
			processData: false,  // tell jQuery not to process the data
			contentType: false,  // tell jQuery not to set contentType
			success: function (data) {
				if (data.success) {

					jQuery(document.body).trigger('afcsp_file_uploaded', data);
					return;

				} else {

					if ($('div.afcsp_file_upload_error')) {
						$('div.afcsp_file_upload_error').remove();
					}

					$('form.addify-importer').after(data.message);
				}
			}
		});
	});

	$(document.body).on('afcsp_file_uploaded', function (event, data) {

		$('form.addify-importer').hide();
		$('.afcsp-progress-bar').progressbar({
			value: 10
		});

		$('.afcsp-progress-bar').show();
		$('#step-2-flag').addClass('filled');

		if ($(window).width() < 500) {
			$('#step-1-flag').hide();
			$('#step-2-flag').css('left', '-10px');
			$('#step-2-flag').show();
		}
		$('.afcsp-progress-bar .ui-progressbar-value').append('<div class"progress">Importing</div>');

		var total_lines = data.total_lines;
		var file_path = data.file_path;

		$('.afcsp-progress-bar').show();

		if (total_lines > 1) {
			var i = 1;
			let start = i;
			let end = i;
			total_requests = total_lines / 50;

			while (i < total_lines) {

				start = i;

				if (i + 50 > total_lines) {
					end = total_lines - 1;
				} else {
					end = i + 50;
				}

				i = end + 1;

				$.ajax({

					url: ajaxurl,
					type: 'POST',
					async: true,
					data: {
						action: 'afcsp_import_members',
						afcsp_nonce: nonce,
						start: start,
						end: end,
						file_path: file_path
					},
					success: function (data) {

						if (data.success) {

							if (data.completed) {

								if ($(window).width() < 500) {
									$('#step-1-flag').hide();
									$('#step-2-flag').hide();
									$('#step-3-flag').css('left', '-10px');
								}

								$('#step-3-flag').addClass('filled');

								$('section.afcsp-progress-section').hide();
								$('.afcsp-completed-bar').before(data.message);
								$('.afcsp-completed-bar').show();

							} else {

								let percent = data.lines / total_lines * 100;

								$('.afcsp-progress-bar').progressbar({
									value: percent
								});

								$('.afcsp-progress-bar').show();

								$('section.afcsp-progress-section').find('div.progress').html($('.afcsp-progress-bar').attr('aria-valuenow') + "%");
							}

						} else {

							if ($('div.afcsp_file_upload_error')) {
								$('div.afcsp_file_upload_error').remove();
							}

							$('#step-3-flag').addClass('filled');

							if ($(window).width() < 500) {
								$('#step-1-flag').hide();
								$('#step-2-flag').hide();
								$('#step-3-flag').css('left', '-10px');
								$('.addify_imp_exp_container .line').hide();
							}

							$('.afcsp-progress-bar').before(data.message);
						}
					}
				});
			}
		}
	});

	$(document).ajaxComplete(function (event, xhr, settings) {

		let data = settings.data;
		if ('string' == typeof data && data.indexOf('afcsp_import_members') > 0) {
			progress_bar_percent = progress_bar_percent + 1 / total_requests * 100;
			$('.afcsp-progress-bar').progressbar({
				value: progress_bar_percent
			});
			$('section.afcsp-progress-section').find('div.progress').html(progress_bar_percent + "%");
		}
	});

	if ($(window).width() < 500) {
		$('.addify_imp_exp_container .line').hide();
	}

	// Check to show or hide the enforce min max quantity msgs.
	$("#csp_enforce_min_max_qt").on('change', function () {
		if ($(this).prop('checked')) {
			jQuery('.af_csp_qty_msg').show();

		} else {
			jQuery('.af_csp_qty_msg').hide();
		}
	});
	if (jQuery('#csp_enforce_min_max_qt').is(":checked")) {
		jQuery('.af_csp_qty_msg').show();

	} else {
		jQuery('.af_csp_qty_msg').hide();
	}
});





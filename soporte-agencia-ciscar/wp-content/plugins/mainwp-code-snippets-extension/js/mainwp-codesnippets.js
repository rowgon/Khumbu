/*
 * MainWp Code Snippets Extension
 */
jQuery(document).ready(function ($) {
	var mainwp_snp_get_code = function () {
		return jQuery('#mainwp-code-snippets-code-editor').val();
	};

	var mainwp_snp_get_clean_code = function () {
		return mainwp_snp_get_code()
			.replace(/^\s*<\?(?:php|=)?/i, '')
			.replace(/\?>\s*$/, '')
			.trim();
	};

	var mainwp_snippet_get_selected_targets = function () {
		var targets = {
			sites: [],
			groups: [],
			clients: [],
			selectBy: jQuery('#select_by').val()
		};

		if (targets.selectBy == 'site') {
			jQuery("input[name='selected_sites[]']:checked").each(function () {
				targets.sites.push(jQuery(this).val());
			});
		} else if (targets.selectBy == 'group') {
			jQuery("input[name='selected_groups[]']:checked").each(function () {
				targets.groups.push(jQuery(this).val());
			});
		} else if (targets.selectBy == 'client') {
			jQuery("input[name='selected_clients[]']:checked").each(function () {
				targets.clients.push(jQuery(this).val());
			});
		}

		return targets;
	};

	var mainwp_snippet_show_message = function (message, type) {
		jQuery('#mainwp-message-zone')
			.removeClass('yellow red green')
			.addClass(type || 'yellow')
			.text(message)
			.show();
	};

	var mainwp_snippet_update_type_selector = function ($button) {
		var snippetType = $button.data('snippet-type');
		var helpText = $button.data('snippet-type-help');
		var $selector = $button.closest('.mainwp-snippet-type-selector');

		$selector.find('.mainwp-snippet-type-button').removeClass('active green').attr('aria-pressed', 'false');
		$button.addClass('active green').attr('aria-pressed', 'true');
		$selector.closest('.mainwp-snippet-type-field').find('input[name="snp_snippet_type"][value="' + snippetType + '"]').prop('checked', true).trigger('change');
		$selector.closest('.mainwp-snippet-type-field').find('.mainwp-snippet-type-help').text(helpText);
	};

	jQuery('.mainwp-snippet-type-button').on('click', function () {
		mainwp_snippet_update_type_selector(jQuery(this));
	});

	jQuery('.mainwp-snippet-type-selector').each(function () {
		var $selected = jQuery(this).find('.mainwp-snippet-type-button.active:first');

		if ($selected.length == 0) {
			$selected = jQuery(this).find('.mainwp-snippet-type-button:first');
		}

		mainwp_snippet_update_type_selector($selected);
	});

	window.mainwpSnippetModal = {
		processLabels: {
			prepare: __('Preparing child site queue...'),
			save: __('Saving snippet...'),
			clear: __('Step 1 of 3: Checking existing snippets on child sites...'),
			update: __('Step 2 of 3: Saving snippet on child sites...'),
			run: __('Step 3 of 3: Executing snippet on child sites...'),
			complete: __('Process completed!'),
			failed: __('Process failed!'),
			stopped: __('Process stopped.')
		},
		typeLabels: {
			S: {
				color: 'orange',
				icon: 'bolt',
				label: __('Execute')
			},
			R: {
				color: 'blue',
				icon: 'eye',
				label: __('Return')
			},
			C: {
				color: 'purple',
				icon: 'file alternate outline',
				label: __('wp-config.php')
			}
		},
		getTitle: function () {
			var title = jQuery.trim(jQuery('#snp_snippet_title').val());

			return title !== '' ? title : __('Console');
		},
		getType: function () {
			return jQuery('input[name="snp_snippet_type"]:checked').val() || jQuery('#mainwp_snippet_type_value').val() || 'R';
		},
		getTypeLabelData: function () {
			var type = this.getType();

			return this.typeLabels[type] || {
				color: 'grey',
				icon: 'code',
				label: __('Snippet')
			};
		},
		getTypeLabel: function () {
			var typeLabel = this.getTypeLabelData();

			return jQuery('<span/>', {
				class: 'ui small ' + typeLabel.color + ' basic label'
			}).append(
				jQuery('<i/>', {
					class: typeLabel.icon + ' icon'
				}),
				document.createTextNode(' ' + typeLabel.label)
			);
		},
		getProcessLabel: function (processKey, processLabel) {
			return processLabel || this.processLabels[processKey] || processKey;
		},
		setHeader: function (processKey, total, processLabel) {
			var $status = jQuery('<span/>', {
				class: 'mainwp-code-snippet-header-status',
				text: this.getProcessLabel(processKey, processLabel)
			});

			jQuery('#mainwp-code-snippet-output-title').text(this.getTitle());
			jQuery('#mainwp-code-snippet-output-log').empty().append(this.getTypeLabel(), $status);
			this.setStopEnabled(jQuery.inArray(processKey, ['complete', 'failed', 'stopped']) === -1);
		},
		reset: function () {
			jQuery('#mainwp-code-snippet-output').html('');
			this.setHeader('prepare', 0);
			this.hideProgress();
		},
		hideProgress: function () {
			var $progress = jQuery('#mainwp-code-snippet-output-progress');

			$progress.hide().removeData('snippet-total');
			$progress.find('.label').text('');
		},
		initProgress: function (total) {
			var count = parseInt(total, 10) || 0;
			var $progress = jQuery('#mainwp-code-snippet-output-progress');

			if (count <= 0) {
				this.hideProgress();
				return;
			}

			$progress.show().data('snippet-total', count).progress({
				value: 0,
				total: count
			});
			this.updateProgress(0, count);
		},
		updateProgress: function (done, total) {
			var count = parseInt(total, 10) || 0;
			var completed = parseInt(done, 10) || 0;
			var $progress = jQuery('#mainwp-code-snippet-output-progress');

			if (count <= 0) {
				this.hideProgress();
				return;
			}

			if ($progress.data('snippet-total') !== count) {
				this.initProgress(count);
			}

			$progress.progress('set progress', completed);
			$progress.find('.label').text(completed + ' / ' + count + ' ' + __('Processed'));
		},
		renderOutput: function (response) {
			var $output = jQuery('#mainwp-code-snippet-output');

			$output.html(response);
			$output.find('.ui.accordion').accordion({
				exclusive: false,
				collapsible: true
			});
		},
		showMessage: function (message, type) {
			var messageType = type || 'yellow';

			jQuery('#mainwp-code-snippet-output').html('<div class="ui ' + messageType + ' message">' + message + '</div>');
		},
		setStatusError: function ($status, message) {
			$status.empty().append(
				jQuery('<span/>', {
					'data-tooltip': message,
					'data-inverted': '',
					'data-position': 'left center'
				}).append(
					jQuery('<i/>', {
						class: 'red times icon'
					})
				)
			);
		},
		setStatusStopped: function ($status, message) {
			$status.empty().append(
				jQuery('<span/>', {
					'data-tooltip': message,
					'data-inverted': '',
					'data-position': 'left center'
				}).append(
					jQuery('<i/>', {
						class: 'orange times icon'
					})
				)
			);
		},
		setStopEnabled: function (enabled) {
			jQuery('#mainwp-code-snippets-console-modal .mainwp-code-snippet-stop-button')
				.prop('disabled', !enabled)
				.toggleClass('disabled', !enabled);
		}
	};
	var mainwp_snippet_allow_modal_close = false;
	var mainwp_snippet_skip_modal_callback = false;

	// Close Modal and Reload page
	$('#mainwp-code-snippets-console-modal .ui.reload.cancel.button').on('click', function () {
		window.location.reload();
	});

	$(document).on('click', '#mainwp-code-snippets-console-modal .mainwp-code-snippet-edit-button', function (event) {
		event.preventDefault();

		mainwp_snippet_allow_modal_close = true;
		mainwp_snippet_skip_modal_callback = true;
		$('#mainwp-code-snippets-console-modal').modal('hide');
	});

	$(document).on('click', '#mainwp-code-snippets-console-modal .mainwp-code-snippet-stop-button', function (event) {
		event.preventDefault();

		mainwp_snippet_cancel_process();
	});

	// Trigger the code execution
	$('#mainwp-code-snippetes-execute-snippet-button').on('click', function (event) {

		var confirmation = confirm(__('Are you sure you want to execute this code snippet?'));

		// confirm that you want to execute
		if (confirmation == false) {
			return;
		}

		mainwp_snippet_reset_process_control();
		window.mainwpSnippetModal.reset();

		var errors = [];

		if ($.trim($('#snp_snippet_title').val()) == '') {
			errors.push(__('Snippet title is required. Please, enter the title and try again.'));
		}

		var code = mainwp_snp_get_clean_code();

		if (code == '') {
			errors.push(__('Snippet cannot be empty. Please, enter the snippet and try again.'));
		}

		if (errors.length > 0) {
			jQuery('#mainwp-message-zone').html(errors.join('<br />')).show();
			jQuery('#mainwp-message-zone').addClass('yellow');
			return false;
		} else {
			jQuery('#mainwp-message-zone').removeClass('yellow');
			jQuery('#mainwp-message-zone').html('').hide();
		}

		var snippet_id = jQuery('#mainwp_snippet_id_value').val();
		var current_type = jQuery('#mainwp_snippet_type_value').val();
		var selected_type = jQuery('input[name="snp_snippet_type"]:checked').val();

		jQuery('#mainwp-code-snippets-console-modal').modal({
			onHide: function () {
				if (mainwp_snippet_allow_modal_close) {
					mainwp_snippet_allow_modal_close = false;
					mainwp_snippet_skip_modal_callback = false;
					return true;
				}
				// to fix issue Save & Execute do not open the current snippet.
				if (!mainwp_snippet_skip_modal_callback && typeof mainwp_snippet_closed_modal_callback === 'function') {
					mainwp_snippet_closed_modal_callback();
				}
				mainwp_snippet_skip_modal_callback = false;
				return false;
			}
		}).modal('show');

		if (snippet_id > 0 && (current_type === 'S' || selected_type === 'S' || current_type === 'C' || selected_type === 'C')) {
			var data = mainwp_secure_data({
				action: 'mainwp_snippet_clear_on_site_loading',
				snippetId: snippet_id
			});

			jQuery(this).attr('disabled', 'disabled');
			window.mainwpSnippetModal.setHeader('prepare', 0, __('Preparing existing snippet check'));

			mainwp_snippet_post(data, function (response) {
				jQuery('#mainwp-code-snippetes-execute-snippet-button').removeAttr('disabled');
				if (current_type === 'S' || current_type === 'C') {
					if (response !== 'NOSITES') {
						window.mainwpSnippetModal.renderOutput(response);
						mainwp_snippet_clear_start();
					} else {
						window.mainwpSnippetModal.showMessage(__('No selected sites. Please select wanted child sites first.'));
						mainwp_snippet_save(true, false); // avoid clear on sites
					}
				} else { // selected_type = S
					mainwp_snippet_save(true, false);
				}
			});
		} else {
			if (selected_type === 'S' || selected_type === 'C') {
				mainwp_snippet_save(true, false); // update on sites
			} else {
				mainwp_snippet_save(false, false); // do not update on sites
			}
		}
	});

	// Trigger save snippet process
	$('#mainwp-code-snippetes-save-snippet-button').on('click', function (event) {
		var errors = [];
		if ($.trim($('#snp_snippet_title').val()) == '') {
			errors.push(__('Snippet title is required. Please, enter the title and try again.'));
		}
		var code = mainwp_snp_get_clean_code();
		if (code == '') {
			errors.push(__('Snippet cannot be empty. Please, enter the snippet and try again.'));
		}
		if (errors.length > 0) {
			jQuery('#mainwp-message-zone').html(errors.join('<br />')).show();
			jQuery('#mainwp-message-zone').addClass('yellow');
			return false;
		} else {
			jQuery('#mainwp-message-zone').removeClass('yellow');
			jQuery('#mainwp-message-zone').html('').hide();
			jQuery('#mainwp-message-zone').html('<i class="notched circle loading icon"></i> ' + __('Saving snippet. Please wait...')).show();
		}
		mainwp_snippet_reset_process_control();
		mainwp_snippet_save(false, true); // do not update on sites
	});

	// onclick delete snippet button
	$('#mainwp-code-snippetes-delete-snippet-button').on('click', function (event) {
		var type = jQuery('#mainwp_snippet_type_value').val();
		var sid = jQuery('#mainwp_snippet_id_value').val();

		if (type === "S" || type === "C") {
			jQuery('#mainwp-code-snippet-delete-snippet-modal').modal({
				onHide: function () {
					window.location.href = 'admin.php?page=Extensions-Mainwp-Code-Snippets-Extension&tab=snippets';
					return false;
				}
			}).modal('show');
			jQuery('input[name="delete_snippetid"]').val(sid);
		} else if (type === 'R') {
			var confirmation = confirm(__('Are you sure you want to delete this code snippet?'));
			if (confirmation == false) {
				return;
			}
			var data = mainwp_secure_data({
				action: 'mainwp_snippet_delete_snippet',
				snippet_id: sid
			});
			jQuery('#mainwp-message-zone').html('<i class="notched circle loading icon"></i> ' + __(' Deleting. Please wait...')).show();

			jQuery.post(ajaxurl, data, function (response) {
				if (response && response === 'SUCCESS') {
					jQuery('#mainwp-message-zone').fadeOut();
					location.href = 'admin.php?page=Extensions-Mainwp-Code-Snippets-Extension&tab=snippets';
				} else {
					jQuery('#mainwp-message-zone').html('<i class="red times icon"></i> ' + __('Snippet could not be deleted. Please, reload the page and try again.'));
				}
			});
		}
		return false;
	});


	// Trigger the delete snippet process
	$(document).on('click', '.snippet_list_delete_item', function () {
		var type = $(this).attr('type');
		if (type === "S" || type === "C") {
			$('input[name="delete_snippetid"]').val($(this).attr('id'));
			$('#mainwp-code-snippet-delete-snippet-modal').modal({
				onHide: function () {
					window.location.href = 'admin.php?page=Extensions-Mainwp-Code-Snippets-Extension&tab=snippets';
					return false;
				}
			}).modal('show');
		} else if (type === 'R') {
			var confirmation = confirm(__('Are you sure you want to delete this code snippet?'));

			if (confirmation == false) {
				return;
			}
			mainwp_snippet_delete($(this));
		}
		return false;
	});

	// Delete the R type snippets
	mainwp_snippet_delete = function (pItem) {
		var parent = pItem.closest('tr');
		var data = mainwp_secure_data({
			action: 'mainwp_snippet_delete_snippet',
			snippet_id: pItem.attr('id')
		});

		parent.html('<td colspan="5"><i class="notched circle loading icon"></i> ' + __(' Deleting. Please wait...') + '</td>').show();
		$.post(ajaxurl, data, function (response) {
			if (response && response === 'SUCCESS') {
				parent.fadeOut();
			} else {
				parent.html('<i class="red times icon"></i> ' + __('Snippet could not be deleted. Please, reload the page and try again.'));
			}
		});
		return false;
	};

	// Delete the S and C type snippets.
	$('#mainwp-code-snippets-delete-snippet-button').on('click', function () {

		$(this).attr('disabled', 'disabled');

		var snippetid = $('input[name="delete_snippetid"]').val();

		var delete_on_site = $('input[name="delete_snippet_child_site"]:radio:checked').val();
		if (delete_on_site == 1) {
			location.href = 'admin.php?page=Extensions-Mainwp-Code-Snippets-Extension&deleteonsites=1&id=' + snippetid;
			return;
		}

		var data = mainwp_secure_data({
			action: 'mainwp_snippet_delete_snippet',
			snippet_id: snippetid
		});

		$('#mainwp-code-snippet-delete-snippet-modal').find('.content').html('<div class="ui message"><i class="notched circle loading icon"></i> ' + __('Deleting. Please wait...') + '</div>');

		$.post(ajaxurl, data, function (response) {
			$('#mainwp-code-snippet-delete-snippet-modal').find('.content').find('.ui.message').html('').hide();
			if (response && response === 'SUCCESS') {
				$('#mainwp-code-snippet-delete-snippet-modal').find('.content').find('.ui.message').html(__('Snippet deleted successfully.')).show();
				$('#mainwp-code-snippet-delete-snippet-modal').find('.content').find('.ui.message').addClass('green');
				setTimeout(function () {
					location.href = 'admin.php?page=Extensions-Mainwp-Code-Snippets-Extension&tab=snippets';
				}, 3000);
			}
		});
		return false;
	});

	// Reload page aftre closing modal
	//	jQuery( '#mainwp-code-snippet-delete-snippet-modal .cancel' ).on( 'click', function() {
	//		window.location = 'admin.php?page=Extensions-Mainwp-Code-Snippets-Extension&tab=snippets';
	//	} );

	// Run snippet
	mainwp_snippet_run = function () {

		var errors = [];
		var selectedTargets = mainwp_snippet_get_selected_targets();

		if (selectedTargets.groups.length == 0 && selectedTargets.sites.length == 0 && selectedTargets.clients.length == 0) {
			window.mainwpSnippetModal.showMessage(__('Please select at least one website, tag, or client.'));
			return;
		}

		var code = mainwp_snp_get_clean_code();

		if (code == '') {
			errors.push(__('Snippet cannot be empty. Please, enter the snippet and try again.'));
		}

		if (errors.length > 0) {
			window.mainwpSnippetModal.showMessage(errors.join('<br />'));
			return false;
		} else {
			jQuery('#mainwp-code-snippet-output').find('.ui.yellow.message').html('').hide();
		}

		window.mainwpSnippetModal.setHeader('prepare', 0);
		window.mainwpSnippetModal.hideProgress();

		var data = mainwp_secure_data({
			action: 'mainwp_snippet_run_snippet_loading',
			'groups[]': selectedTargets.groups,
			'sites[]': selectedTargets.sites,
			'clients[]': selectedTargets.clients,
			type: jQuery('input[name="snp_snippet_type"]:checked').val()
		});

		mainwp_snippet_post(data, function (response) {
			window.mainwpSnippetModal.renderOutput(response);
			mainwp_snippet_run_start();
		});
	}

	mainwp_snippet_run_start = function () {
		mainwp_snippet_init_start('run');
		mainwp_snippet_run_start_next();
	}

	mainwp_snippet_clear_start = function () {
		mainwp_snippet_init_start('clear');
		mainwp_snippet_clear_sites_start_next();
	}

	mainwp_snippet_update_start = function () {
		mainwp_snippet_init_start('update');
		mainwp_snippet_update_sites_start_next();
	}

	var mainwp_snippet_closed_modal_callback;

	// Process the snippet
	mainwp_snippet_save = function (doUpdate, saveOnly) {
		var selectedTargets = mainwp_snippet_get_selected_targets();
		var selected_type = jQuery('input[name="snp_snippet_type"]:checked').val();
		var snippet_id = jQuery('#mainwp_snippet_id_value').val();
		var data = mainwp_secure_data({
			action: 'mainwp_snippet_save_snippet',
			snippet_title: jQuery('#snp_snippet_title').val(),
			code: mainwp_snp_get_code(),
			desc: jQuery('#snp_snippet_desc').val(),
			type: selected_type,
			snippet_id: snippet_id,
			sites: selectedTargets.sites,
			groups: selectedTargets.groups,
			clients: selectedTargets.clients,
			select_by: selectedTargets.selectBy
		});

		if (doUpdate) {
			jQuery('#mainwp-code-snippets-console-modal').modal('show');
		}

		window.mainwpSnippetModal.setHeader('save', 0);
		window.mainwpSnippetModal.hideProgress();

		mainwp_snippet_post(data, function (response) {

			if (!response || response['status'] !== 'SUCCESS') {
				var errorMessage = response && response['message'] ? response['message'] : __('Saving process failed.');

				if (saveOnly) {
					mainwp_snippet_show_message(errorMessage, 'red');
				} else {
					window.mainwpSnippetModal.setHeader('failed', 0, errorMessage);
					window.mainwpSnippetModal.showMessage(errorMessage, 'red');
					mainwp_snippet_process_done();
				}
				return;
			}

			if (response['id']) {
				mainwp_snippet_closed_modal_callback = () => {
					location.href = 'admin.php?page=Extensions-Mainwp-Code-Snippets-Extension&tab=editor&id=' + response['id'];
				};
			}

			if (saveOnly) {
				var id_param = '';
				if (response['id']) {
					id_param = '&id=' + response['id'];

				} else if (snippet_id) {
					id_param = '&id=' + snippet_id;
				}
				location.href = 'admin.php?page=Extensions-Mainwp-Code-Snippets-Extension&tab=editor&message=1' + id_param;
				return;
			} else if (selected_type == 'R') {
				mainwp_snippet_run();
				return;
			}

			jQuery('#mainwp_snippet_id_value').val(response['id']);
			jQuery('#mainwp_snippet_slug_value').val(response['slug']);
			jQuery('#mainwp_snippet_type_value').val(response['type']);
			jQuery('#mainwp_snippet_save_status').fadeOut(3000);
			window.mainwpSnippetModal.setHeader('save', 0, __('Snippet saved. Preparing child sites'));
			if (doUpdate && (selected_type === 'S' || selected_type === 'C')) {
				var data = mainwp_secure_data({
					action: 'mainwp_snippet_update_site_loading',
					snippetId: response['id']
				});
				mainwp_snippet_post(data, function (response) {
					if (response !== 'NOSITES') {
						window.mainwpSnippetModal.renderOutput(response);
						mainwp_snippet_update_start();
					} else {
						window.mainwpSnippetModal.setHeader('complete', 0, __('No selected sites to proceed. Process completed.'));
					}
				});

			} else {
				window.mainwpSnippetModal.setHeader('complete', 0, __('Snippet saved successfully.'));
				mainwp_snippet_process_done();
			}
		}, 'json');
	}

	// Loop through sites
	mainwp_snippet_run_start_next = function () {
		if (snippet_ProcessCancelled) {
			return;
		}

		if (snippet_TotalThreads == 0) {
			snippet_TotalThreads = jQuery('.mainwp-snippet-item[status="queue"]').length;
			if (snippet_TotalThreads == 0) {
				window.mainwpSnippetModal.setHeader('complete', 0, __('No child sites found.'));
				window.mainwpSnippetModal.hideProgress();
				return;
			}
			window.mainwpSnippetModal.setHeader('run', snippet_TotalThreads);
			window.mainwpSnippetModal.initProgress(snippet_TotalThreads);
		}

		while ((siteToRun = jQuery('.mainwp-snippet-item[status="queue"]:first')) && (siteToRun.length > 0) && (snippet_CurrentThreads < snippet_MaxThreads)) {
			mainwp_snippet_run_start_specific(siteToRun);
		}

	}

	// Loop
	mainwp_snippet_update_sites_start_next = function () {
		if (snippet_ProcessCancelled) {
			return;
		}

		if (snippet_TotalThreads == 0) {
			snippet_TotalThreads = jQuery('.mainwp-update-snippet-item[status="queue"]').length;
			if (snippet_TotalThreads == 0) {
				window.mainwpSnippetModal.setHeader('complete', 0, __('No child sites found.'));
				window.mainwpSnippetModal.hideProgress();
				return;
			}
			window.mainwpSnippetModal.setHeader('update', snippet_TotalThreads);
			window.mainwpSnippetModal.initProgress(snippet_TotalThreads);
		}
		while ((siteToRun = jQuery('.mainwp-update-snippet-item[status="queue"]:first')) && (siteToRun.length > 0) && (snippet_CurrentThreads < snippet_MaxThreads)) {
			mainwp_snippet_update_sites_start_specific(siteToRun);
		}
	}

	// Execute on specific
	mainwp_snippet_run_start_specific = function (pSiteToRun) {
		if (snippet_ProcessCancelled) {
			return;
		}

		snippet_CurrentThreads++;
		pSiteToRun.attr('status', 'progress');
		var statusEl = pSiteToRun.find('.status').html('<i class="notched circle loading icon"></i>');
		var resultEl = pSiteToRun.find('.mainwp-snippet-output');
		var data = mainwp_secure_data({
			action: 'mainwp_snippet_run_snippet',
			siteId: pSiteToRun.attr('siteid'),
			code: mainwp_snp_get_code()
		});

		mainwp_snippet_post(data, function (response) {
			pSiteToRun.attr('status', 'done');
			if (!response || response === 'FAIL') {
				window.mainwpSnippetModal.setStatusError(statusEl, __('Undefined error occurred. Please, try again.'));
			} else if (response === 'CODEEMPTY') {
				window.mainwpSnippetModal.setStatusError(statusEl, __('Snippet cannot be empty.'));
			} else {
				if (response['error']) {
					window.mainwpSnippetModal.setStatusError(statusEl, response['error']);
				} else if (response['status'] === 'SUCCESS') {
					statusEl.html('<i class="green check icon"></i>');
				} else if (response['status'] === 'FAIL') {
					window.mainwpSnippetModal.setStatusError(statusEl, __('Process failed. Please, try again.'));
				}
				if (response['result'] !== '') {
					resultEl.html(response['result']);
				}
			}

			snippet_CurrentThreads--;
			snippet_FinishedThreads++;
			window.mainwpSnippetModal.updateProgress(snippet_FinishedThreads, snippet_TotalThreads);

			if (snippet_FinishedThreads == snippet_TotalThreads && snippet_FinishedThreads != 0) {
				window.mainwpSnippetModal.setHeader('complete', snippet_TotalThreads, __('Process completed successfully.'));
			}

			mainwp_snippet_run_start_next();
		}, 'json');
	};

	// Start specific
	mainwp_snippet_update_sites_start_specific = function (pSiteToRun) {
		if (snippet_ProcessCancelled) {
			return;
		}

		snippet_CurrentThreads++;
		pSiteToRun.attr('status', 'progress');
		var statusEl = pSiteToRun.find('.status').html('<i class="notched circle loading icon"></i>');
		var type = jQuery('#mainwp_snippet_type_value').val();
		var data = mainwp_secure_data({
			action: 'mainwp_snippet_update_site',
			siteId: pSiteToRun.attr('siteid'),
			code: mainwp_snp_get_code(),
			snippetSlug: jQuery('#mainwp_snippet_slug_value').val(),
			type: type
		});

		mainwp_snippet_post(data, function (response) {
			pSiteToRun.attr('status', 'done');
			if (!response || response === 'FAIL') {
				window.mainwpSnippetModal.setStatusError(statusEl, __('Undefined error occurred. Please, try again.'));
			} else if (response === 'CODEEMPTY') {
				window.mainwpSnippetModal.setStatusError(statusEl, __('Snippet cannot be empty.'));
			} else {
				if (response['error']) {
					window.mainwpSnippetModal.setStatusError(statusEl, response['error']);
				} else if (response['status'] === 'SUCCESS') {
					statusEl.html('<i class="green check icon"></i>');
				} else if (response['status'] === 'FAIL') {
					window.mainwpSnippetModal.setStatusError(statusEl, __('Process failed. Please, try again.'));
				}
			}
			snippet_CurrentThreads--;
			snippet_FinishedThreads++;
			window.mainwpSnippetModal.updateProgress(snippet_FinishedThreads, snippet_TotalThreads);

			if (snippet_FinishedThreads == snippet_TotalThreads && snippet_FinishedThreads != 0) {
				window.mainwpSnippetModal.setHeader('complete', snippet_TotalThreads, __('Snippet saved successfully.'));
				if (type !== 'C') { // do not run if snippet code go to wp-config file
					mainwp_snippet_run();
				}
			}
			mainwp_snippet_update_sites_start_next();
		}, 'json');
	}
});

var snippet_MaxThreads = 3;
var snippet_CurrentThreads = 0;
var snippet_TotalThreads = 0;
var snippet_FinishedThreads = 0;
var snippet_CurrentProcess = 'prepare';
var snippet_ProcessCancelled = false;
var snippet_ActiveRequests = [];

mainwp_snippet_reset_process_control = function () {
	snippet_ProcessCancelled = false;
	snippet_ActiveRequests = [];

	if (window.mainwpSnippetModal) {
		window.mainwpSnippetModal.setStopEnabled(true);
	}
}

mainwp_snippet_post = function (data, callback, dataType) {
	if (snippet_ProcessCancelled) {
		return null;
	}

	var request = jQuery.post(ajaxurl, data, function (response) {
		if (snippet_ProcessCancelled) {
			return;
		}

		callback(response);
	}, dataType);

	snippet_ActiveRequests.push(request);

	request.always(function () {
		var index = jQuery.inArray(request, snippet_ActiveRequests);

		if (index > -1) {
			snippet_ActiveRequests.splice(index, 1);
		}
	});

	return request;
}

mainwp_snippet_cancel_process = function () {
	if (snippet_ProcessCancelled) {
		return;
	}

	snippet_ProcessCancelled = true;

	jQuery.each(snippet_ActiveRequests.slice(), function (index, request) {
		if (request && request.readyState !== 4) {
			request.abort();
		}
	});

	snippet_ActiveRequests = [];
	snippet_CurrentThreads = 0;

	jQuery('.mainwp-snippet-item[status="queue"], .mainwp-update-snippet-item[status="queue"], .mainwp-clear-snippet-item[status="queue"]')
		.attr('status', 'stopped')
		.each(function () {
			window.mainwpSnippetModal.setStatusStopped(jQuery(this).find('.status'), __('Process stopped before this site was processed.'));
		});

	jQuery('.mainwp-snippet-item[status="progress"], .mainwp-update-snippet-item[status="progress"], .mainwp-clear-snippet-item[status="progress"]')
		.attr('status', 'stopped')
		.each(function () {
			window.mainwpSnippetModal.setStatusStopped(jQuery(this).find('.status'), __('Process stopped. This request may already have reached the child site.'));
		});

	if (window.mainwpSnippetModal) {
		window.mainwpSnippetModal.setHeader('stopped', snippet_TotalThreads);
		window.mainwpSnippetModal.setStopEnabled(false);
	}
}

mainwp_snippet_init_delete_sites_progress = function (total) {
	var $progress = jQuery('#mainwp-code-snippets-cleaning-sites-progress');
	var count = parseInt(total, 10) || 0;

	if (!$progress.length || count <= 0) {
		return;
	}

	$progress.show().data('snippet-total', count).progress({
		value: 0,
		total: count
	});
	mainwp_snippet_update_delete_sites_progress();
}

mainwp_snippet_update_delete_sites_progress = function () {
	var $progress = jQuery('#mainwp-code-snippets-cleaning-sites-progress');
	var count = parseInt(snippet_TotalThreads, 10) || 0;
	var completed = parseInt(snippet_FinishedThreads, 10) || 0;

	if (!$progress.length || count <= 0) {
		return;
	}

	if ($progress.data('snippet-total') !== count) {
		$progress.data('snippet-total', count).progress({
			value: completed,
			total: count
		});
	}

	$progress.show().progress('set progress', completed);
	$progress.find('.label').text(completed + ' / ' + count + ' ' + __('Processed'));
}

mainwp_snippet_init_start = function (processKey) {
	snippet_MaxThreads = 3;
	snippet_CurrentThreads = 0;
	snippet_TotalThreads = 0;
	snippet_FinishedThreads = 0;
	snippet_CurrentProcess = processKey || 'prepare';

	if (window.mainwpSnippetModal) {
		window.mainwpSnippetModal.setHeader(snippet_CurrentProcess, 0);
		window.mainwpSnippetModal.hideProgress();
	}
}

// Clean site
mainwp_snippet_process_done = function () {
	setTimeout(function () {
		if (snippet_ProcessCancelled) {
			return;
		}

		location.href = 'admin.php?page=Extensions-Mainwp-Code-Snippets-Extension&message=1';
	}, 3000);
}

// Loop
mainwp_snippet_clear_sites_start_next = function () {
	if (snippet_ProcessCancelled) {
		return;
	}

	if (snippet_TotalThreads == 0) {
		snippet_TotalThreads = jQuery('.mainwp-clear-snippet-item[status="queue"]').length;
		if (snippet_TotalThreads == 0) {
			if (window.mainwpSnippetModal) {
				window.mainwpSnippetModal.setHeader('complete', 0, __('No child sites found.'));
				window.mainwpSnippetModal.hideProgress();
			}
			return;
		}
		if (window.mainwpSnippetModal) {
			window.mainwpSnippetModal.setHeader('clear', snippet_TotalThreads);
			window.mainwpSnippetModal.initProgress(snippet_TotalThreads);
		}
	}
	while ((siteToRun = jQuery('.mainwp-clear-snippet-item[status="queue"]:first')) && (siteToRun.length > 0) && (snippet_CurrentThreads < snippet_MaxThreads)) {
		mainwp_snippet_clear_sites_start_specific(siteToRun);
	}
}

// Clear Sites
mainwp_snippet_clear_sites_start_specific = function (pSiteToRun) {
	if (snippet_ProcessCancelled) {
		return;
	}

	snippet_CurrentThreads++;
	pSiteToRun.attr('status', 'progress');
	var statusEl = pSiteToRun.find('.status').html('<i class="notched circle loading icon"></i>');
	var data = mainwp_secure_data({
		action: 'mainwp_snippet_clear_on_site',
		siteId: pSiteToRun.attr('siteid'),
		snippetSlug: jQuery('#mainwp_snippet_slug_value').val(),
		type: jQuery('#mainwp_snippet_type_value').val()
	});

	mainwp_snippet_post(data, function (response) {
		pSiteToRun.attr('status', 'done');
		if (!response || response === 'FAIL') {
			window.mainwpSnippetModal.setStatusError(statusEl, __('Undefined error occurred. Please, try again.'));
		} else {
			if (response['error']) {
				window.mainwpSnippetModal.setStatusError(statusEl, response['error']);
			} else if (response['status'] === 'SUCCESS') {
				statusEl.html('<i class="green check icon"></i>');
			} else if (response['status'] === 'FAIL') {
				statusEl.html(__('Saved without changes.'));
			}
		}
		snippet_CurrentThreads--;
		snippet_FinishedThreads++;
		if (window.mainwpSnippetModal) {
			window.mainwpSnippetModal.updateProgress(snippet_FinishedThreads, snippet_TotalThreads);
		}
		if (snippet_FinishedThreads == snippet_TotalThreads && snippet_FinishedThreads != 0) {
			mainwp_snippet_save(true, false);
		}
		mainwp_snippet_clear_sites_start_next();
	}, 'json');
}

// Loop through sites and to delete snippet
mainwp_snippet_delete_sites_start_next = function () {
	if (snippet_TotalThreads == 0) {
		snippet_TotalThreads = jQuery('.mainwp-code-snippets-snippet-to-delete[status="queue"]').length;
		mainwp_snippet_init_delete_sites_progress(snippet_TotalThreads);
	}
	while ((siteToRun = jQuery('.mainwp-code-snippets-snippet-to-delete[status="queue"]:first')) && (siteToRun.length > 0) && (snippet_CurrentThreads < snippet_MaxThreads)) {
		mainwp_snippet_delete_sites_start_specific(siteToRun);
	}
}

// Remove snippet from child sites
mainwp_snippet_delete_sites_start_specific = function (pSiteToRun) {
	snippet_CurrentThreads++;
	pSiteToRun.attr('status', 'progress');
	var statusEl = pSiteToRun.find('.status').html('<i class="notched circle loading icon"></i>');
	var data = mainwp_secure_data({
		action: 'mainwp_snippet_delete_on_site',
		siteId: pSiteToRun.attr('siteid'),
		snippetSlug: jQuery('#mainwp_snippet_slug_value').val(),
		type: jQuery('#mainwp_snippet_type_value').val()
	});

	jQuery.post(ajaxurl, data, function (response) {
		pSiteToRun.attr('status', 'done');
		if (!response || response === 'FAIL') {
			window.mainwpSnippetModal.setStatusError(statusEl, __('Undefined error occurred. Please, try again.'));
		} else {
			if (response['error']) {
				window.mainwpSnippetModal.setStatusError(statusEl, response['error']);
			} else if (response['status'] === 'SUCCESS') {
				statusEl.html('<i class="green check icon"></i>');
			} else if (response['status'] === 'FAIL') {
				statusEl.html(__('Saved without changes.'));
			}
		}

		snippet_CurrentThreads--;
		snippet_FinishedThreads++;
		mainwp_snippet_update_delete_sites_progress();
		if (snippet_FinishedThreads == snippet_TotalThreads && snippet_FinishedThreads != 0) {
			var data = mainwp_secure_data({
				action: 'mainwp_snippet_delete_snippet',
				snippet_id: jQuery('#mainwp_snippet_delete_id').val()
			});
			jQuery.post(ajaxurl, data, function (response) {
				var mess;
				var mess_class;
				if (response && response === 'SUCCESS') {
					mess = __('Process finished successfully.');
					mess_class = 'green';
				} else {
					mess = __('Process finished with errors');
					mess_class = 'red';
				}

				jQuery('#mainwp-modal-message-zone').html(mess).show();
				jQuery('#mainwp-modal-message-zone').addClass(mess_class);

				setTimeout(function () {
					location.href = 'admin.php?page=Extensions-Mainwp-Code-Snippets-Extension&tab=snippets';
				}, 2000);
			});
		}
		mainwp_snippet_delete_sites_start_next();
	}, 'json');
}


mainwp_pro_reports_date_selection_init = function () {

    var typeVal = jQuery('#pro-report-type').val();

    if (typeVal == 1) {
        jQuery('#scheduled_date_range_wrap').hide();
        jQuery('#scheduled_additional_options_wrap').show();
        jQuery('#scheduled_schedule_selection_wrap').show();

        jQuery('#mainwp-pro-reports-send-report-button').hide();
        jQuery('#mainwp-pro-reports-schedule-report-button').show();


        var scheduleVal = jQuery('#pro-report-schedule-select').val();

        if (scheduleVal == 'weekly') {
            jQuery('#scheduled_send_on_day_of_week_wrap').show();
            jQuery('#scheduled_send_on_day_of_month_wrap').hide();
        } else if (scheduleVal == 'monthly') {
            jQuery('#scheduled_send_on_day_of_week_wrap').hide();
            jQuery('#scheduled_send_on_day_of_month_wrap').show();
        } else {  // daily                 
            jQuery('#scheduled_send_on_day_of_week_wrap').hide();
            jQuery('#scheduled_send_on_day_of_month_wrap').hide();
        }

    } else {
        jQuery('#scheduled_date_range_wrap').show();
        jQuery('#scheduled_additional_options_wrap').hide();
        jQuery('#scheduled_schedule_selection_wrap').hide();

        jQuery('#mainwp-pro-reports-send-report-button').show();
        jQuery('#mainwp-pro-reports-schedule-report-button').hide();

        jQuery('#scheduled_send_on_day_of_week_wrap').hide();
        jQuery('#scheduled_send_on_day_of_month_wrap').hide();
    }
};



jQuery(document).ready(function ($) {
    // Manage report schedule
    // Check if scheduled and adjust options
    $('#pro-report-type').change(function () {
        mainwp_pro_reports_date_selection_init();
    });

    // Manage options based on schedule
    $('#pro-report-schedule-select').change(function () {
        mainwp_pro_reports_date_selection_init();
    });

    jQuery('#mainwp-pro-reports-sites-filter-button').on('click', function (e) {
        var groups = jQuery('#mainwp-pro-reports-groups-selection').dropdown("get value");
        console.log(groups);
        if (groups !== null && groups != '') {
            groups = '&group=' + groups.join('-');
        } else {
            groups = '';
        }
        location.href = 'admin.php?page=Extensions-Mainwp-Pro-Reports-Extension&tab=dashboard' + groups;
    });

    // Manage Cutom Tokens

    // Trigger new token modal
    jQuery('#mainwp-pro-reports-new-custom-token-button').on('click', function (e) {
        jQuery('#mainwp-pro-reports-new-custom-token-modal').modal({
            closable: false,
            onHide: function () {
                jQuery('#mainwp-pro-reports-new-custom-token-modal input[name="token-name"]').val('');
                jQuery('#mainwp-pro-reports-new-custom-token-modal input[name="token-description"]').val('');
                jQuery('#mainwp-pro-reports-new-custom-token-modal input[name="token-id"]').val(0);
            }
        }).modal('show');
    });

    // Edit custom tokens
    jQuery(document).on('click', '#mainwp-pro-reports-edit-custom-token', function () {
        var parent = jQuery(this).closest('.mainwp-token');
        var token_name = parent.find('td.token-name').html();
        var token_description = parent.find('td.token-description').html();
        var token_id = parent.attr('token-id');

        token_name = token_name.replace(/\[|\]/gi, "");

        jQuery('#mainwp-pro-reports-update-token-modal').modal({
            closable: false,
            onHide: function () {
                jQuery('#mainwp-pro-reports-update-token-modal input[name="token-name"]').val('');
                jQuery('#mainwp-pro-reports-update-token-modal input[name="token-description"]').val('');
                jQuery('#mainwp-pro-reports-update-token-modal input[name="token-id"]').val(0);
            }
        }).modal('show');

        jQuery('#mainwp-pro-reports-update-token-modal input[name="token-name"]').val(token_name);
        jQuery('#mainwp-pro-reports-update-token-modal input[name="token-description"]').val(token_description);
        jQuery('#mainwp-pro-reports-update-token-modal input[name="token-id"]').val(token_id);

        return false;
    });


    // Save custom tokens
    $('#mainwp-pro-reports-create-new-custom-token').on('click', function () {
        var parent = jQuery(this).closest('#mainwp-pro-reports-new-custom-token-modal');
        var errors = [];

        var tok_name = parent.find('input[name="token-name"]').val();
        var tok_desc = parent.find('input[name="token-description"]').val();

        tok_name = tok_name.trim();
        tok_desc = tok_desc.trim();

        if (tok_name == '') {
            errors.push('Please provide a name for the token. This field is mandatory.');
        }

        if (tok_desc == '') {
            errors.push('Please provide a description for the token. This field is mandatory.');
        }

        if (errors.length > 0) {
            parent.find('.ui.message').html(errors.join('<br />')).show();
            return false;
        }

        var fields = {
            token_name: tok_name,
            token_description: tok_desc,
            action: 'mainwp_pro_reports_save_token',
            nonce: mainwp_pro_reports_loc.nonce
        };

        parent.find('.ui.message').removeClass('yellow').removeClass('red');
        parent.find('.ui.message').html('<i class="notched circle loading icon"></i> Saving token. Please wait...').show();

        $.post(ajaxurl, fields, function (response) {
            if (response) {
                if (response['success']) {
                    location.href = 'admin.php?page=Extensions-Mainwp-Pro-Reports-Extension&tab=tokens';
                } else {
                    if (response['error']) {
                        parent.find('.ui.message').html(response['error']).show().addClass('red');
                    } else {
                        parent.find('.ui.message').html('An unspecified error has occurred. Please retry the action. If the issue persists, contact support for further assistance.').show().addClass('red');
                    }
                }
            } else {
                parent.find('.ui.message').html('An unspecified error has occurred. Please retry the action. If the issue persists, contact support for further assistance.').show().addClass('red');
            }
        }, 'json');
        return false;
    });

    // Update custom tokens
    jQuery(document).on('click', '#mainwp-save-pro-reports-custom-token', function (e) {
        var parent = jQuery(this).closest('#mainwp-pro-reports-update-token-modal');
        var errors = [];

        var tok_name = parent.find('input[name="token-name"]').val();
        var tok_desc = parent.find('input[name="token-description"]').val();

        tok_name = tok_name.trim();
        tok_desc = tok_desc.trim();

        if (tok_name == '') {
            errors.push('Please provide a name for the token. This field is mandatory.');
        }

        if (tok_desc == '') {
            errors.push('Please provide a description for the token. This field is mandatory.');
        }

        if (errors.length > 0) {
            parent.find('.ui.message').html(errors.join('<br />')).show();
            return false;
        }

        var fields = {
            token_name: tok_name,
            token_description: tok_desc,
            token_id: parent.find('input[name="token-id"]').val(),
            action: 'mainwp_pro_reports_save_token',
            nonce: mainwp_pro_reports_loc.nonce
        };

        parent.find('.ui.message').removeClass('yellow').removeClass('red');
        parent.find('.ui.message').html('<i class="notched circle loading icon"></i> Saving token. Please wait...').show();

        $.post(ajaxurl, fields, function (response) {
            if (response) {
                if (response['success']) {
                    location.href = 'admin.php?page=Extensions-Mainwp-Pro-Reports-Extension&tab=tokens';
                } else {
                    if (response['error']) {
                        parent.find('.ui.message').html(response['error']).show().addClass('red');
                    } else {
                        parent.find('.ui.message').html('An unspecified error has occurred. Please retry the action. If the issue persists, contact support for further assistance.').show().addClass('red');
                    }
                }
            } else {
                parent.find('.ui.message').html('An unspecified error has occurred. Please retry the action. If the issue persists, contact support for further assistance.').show().addClass('red');
            }
        }, 'json');
        return false;
    });

    // Delete Custom token
    jQuery(document).on('click', '#mainwp-pro-reports-delete-custom-token', function () {
        if (confirm(__('Are you sure you want to delete this token?'))) {

            var parent = $(this).closest('.mainwp-token');

            jQuery.post(ajaxurl, {
                action: 'mainwp_pro_reports_delete_token',
                token_id: parent.attr('token-id'),
                nonce: mainwp_pro_reports_loc.nonce
            }, function (data) {
                if (data && data.success) {
                    parent.html('<td colspan="3">' + __('The token has been successfully removed.') + '</td>').fadeOut(3000);
                } else {
                    jQuery('#mainwp-message-zone').html(__('The token cannot be removed at this time. Please reload the page and try again. If the issue persists, contact support for further assistance.')).addClass('red').show();
                }
            }, 'json');
            return false;
        }
        return false;
    });

    // Email Preview
    jQuery('#mainwp-email-preview-button').on('click', function () {
        var subject = jQuery('#pro-report-email-subject').val();
        var message = tinyMCE.editors['pro-report-email-message'].getContent();
        jQuery('#mainwp-pro-reports-preview-email-modal').modal('show');

        var select_siteId = 0;
        if (jQuery('#select_by').val() == 'site') {
            var selected_sites = [];
            jQuery("input[name='selected_sites[]']:checked").each(function (i) {
                selected_sites.push(jQuery(this).val());
            });
            if (selected_sites.length > 0) {
                select_siteId = selected_sites[Math.floor(Math.random() * selected_sites.length)];
            }
        }
        console.log(select_siteId);

        var data = {
            action: 'mainwp_pro_reports_email_message_preview',
            site_id: select_siteId,
            subject: subject,
            message: message,
            nonce: mainwp_pro_reports_loc.nonce
        };
        jQuery('#mainwp-pro-email-subject-show').html('<i class="notched circle loading icon"></i>');
        jQuery('#mainwp-pro-email-message-show').html('<i class="notched circle loading icon"></i>');
        jQuery.post(ajaxurl, data, function (response) {
            if (response && response.subject) {
                jQuery('#mainwp-pro-email-subject-show').html(response.subject);
                jQuery('#mainwp-pro-email-message-show').html(response.message);
            } else {
                var er = '<i class="red times icon"></i>';
                jQuery('#mainwp-pro-email-subject-show').html(er);
                jQuery('#mainwp-pro-email-message-show').html(er);
            }
        });
        return false;
    });

    // Dashbaord Actions

    // Activate plugin - trigger
    $('.mainwp-pro-reports-activate-plugin').on('click', function () {
        mainwp_pro_reports_activate_specific($(this), false);
        return false;
    });

    // Update plugin - trigger
    $('.mainwp-pro-reports-update-plugin').on('click', function () {
        mainwp_pro_reports_update_specific($(this), false);
        return false;
    });

    // Show/Hide plugin - trigger
    $('.mainwp-pro-reports-showhide-plugin').on('click', function () {
        mainwp_pro_reports_showhide_specific($(this), false);
        return false;
    });

    // Bulk actions menu trigger
    $('#mainwp-pro-reports-actions-button').on('click', function () {
        var action = $('#mainwp-pro-reports-actions').val();
        console.log(action);
        mainwp_pro_repots_bulk_action(action);
    });

});

// Define bulk actions counters
var pro_reports_bulkMaxThreads = 3;
var pro_reports_bulkTotalThreads = 0;
var pro_reports_bulkCurrentThreads = 0;
var pro_reports_bulkFinishedThreads = 0;
var pro_reports_bulkSuccessCount = 0;

// Bulk actions selector
mainwp_pro_repots_bulk_action = function (act) {
    var selector = '';
    switch (act) {
        case 'activate-selected':
            selector = 'tbody tr.negative .mainwp-pro-reports-activate-plugin';
            jQuery(selector).addClass('queue');
            mainwp_pro_reports_activate_specific_next(selector);
            break;
        case 'update-selected':
            selector = 'tbody tr.warning .mainwp-pro-reports-update-plugin';
            jQuery(selector).addClass('queue');
            mainwp_pro_reports_update_specific_next(selector);
            break;
        case 'hide-selected':
            selector = 'tbody tr .mainwp-pro-reports-showhide-plugin[showhide="hide"]';
            jQuery(selector).addClass('queue');
            mainwp_pro_reports_showhide_specific_next(selector);
            break;
        case 'show-selected':
            selector = 'tbody tr .mainwp-pro-reports-showhide-plugin[showhide="show"]';
            jQuery(selector).addClass('queue');
            mainwp_pro_reports_showhide_specific_next(selector);
            break;
    }
}

// Show/Hide plugin - show/hide loop
mainwp_pro_reports_showhide_specific_next = function (selector) {
    while ((objProcess = jQuery(selector + '.queue:first')) && (objProcess.length > 0) && (pro_reports_bulkCurrentThreads < pro_reports_bulkMaxThreads)) {
        objProcess.removeClass('queue');
        if (objProcess.closest('tr').find('td input[type="checkbox"]:checked').length == 0) {
            continue;
        }
        mainwp_pro_reports_showhide_specific(objProcess, true, selector);
    }
}

// Show/Hide plugin - show/hide instance
mainwp_pro_reports_showhide_specific = function (pObj, bulk, selector) {
    var parent = pObj.closest('tr');
    var showhide = pObj.attr('showhide');
    var statusEl = parent.find('.visibility');

    if (bulk) {
        pro_reports_bulkCurrentThreads++;
    }

    var data = {
        action: 'mainwp_pro_reports_showhide_plugin',
        websiteId: parent.attr('website-id'),
        showhide: showhide,
        nonce: mainwp_pro_reports_loc.nonce
    };

    statusEl.html('<i class="notched circle loading icon"></i>').show();

    jQuery.post(ajaxurl, data, function (response) {
        pObj.removeClass('queue');
        if (response && response['error']) {
            statusEl.html('<i class="red times icon"></i>');
        } else if (response && response['result'] == 'SUCCESS') {
            if (showhide == 'show') {
                pObj.text("Hide Plugin");
                pObj.attr('showhide', 'hide');
                parent.find('.wp-reports-visibility').html(__('No'));
            } else {
                pObj.text("Unhide Plugin");
                pObj.attr('showhide', 'show');
                parent.find('.wp-reports-visibility').html(__('Yes'));
            }
            statusEl.hide();
        } else {
            statusEl.html('<i class="red times icon"></i>');
        }

        if (bulk) {
            pro_reports_bulkCurrentThreads--;
            pro_reports_bulkFinishedThreads++;
            mainwp_pro_reports_showhide_specific_next(selector);
        }

    }, 'json');
    return false;
}

// Activate plugin - activate loop
mainwp_pro_reports_activate_specific_next = function (selector) {
    while ((objProcess = jQuery(selector + '.queue:first')) && (objProcess.length > 0) && (objProcess.length > 0) && (pro_reports_bulkCurrentThreads < pro_reports_bulkMaxThreads)) {
        objProcess.removeClass('queue');
        if (objProcess.closest('tr').find('td input[type="checkbox"]:checked').length == 0) {
            continue;
        }
        mainwp_pro_reports_activate_specific(objProcess, true, selector);
    }
}

// Activate plugin - activate instance
mainwp_pro_reports_activate_specific = function (pObj, bulk, selector) {
    var parent = pObj.closest('tr');
    var statusEl = parent.find('.updating');
    var slug = parent.attr('plugin-slug');

    var data = {
        action: 'mainwp_pro_reports_active_plugin',
        websiteId: parent.attr('website-id'),
        'plugins[]': [slug],
        nonce: mainwp_pro_reports_loc.nonce
    }

    if (bulk) {
        pro_reports_bulkCurrentThreads++;
    }

    statusEl.html('<i class="notched circle loading icon"></i>');

    jQuery.post(ajaxurl, data, function (response) {
        statusEl.html('');
        pObj.removeClass('queue');

        if (response && response['error']) {
            statusEl.html('<i class="red times icon"></i>');
        } else if (response && response['result']) {
            parent.removeClass('negative');
            pObj.remove();
        }

        if (bulk) {
            pro_reports_bulkCurrentThreads--;
            pro_reports_bulkFinishedThreads++;
            mainwp_pro_reports_activate_specific_next(selector);
        }

    }, 'json');
    return false;
}

// Update plugin - update loop
mainwp_pro_reports_update_specific_next = function (selector) {
    while ((objProcess = jQuery(selector + '.queue:first')) && (objProcess.length > 0) && (objProcess.length > 0) && (pro_reports__bulkCurrentThreads < pro_reports__bulkMaxThreads)) {
        objProcess.removeClass('queue');
        if (objProcess.closest('tr').find('td input[type="checkbox"]:checked').length == 0) {
            continue;
        }
        mainwp_pro_reports_update_specific(objProcess, true, selector);
    }
}

// Update plugin - update instance
mainwp_pro_reports_update_specific = function (pObj, bulk, selector) {
    var parent = pObj.closest('tr');
    var statusEl = parent.find('.updating');
    var slug = parent.attr('plugin-slug');

    var data = {
        action: 'mainwp_pro_reports_upgrade_plugin',
        websiteId: parent.attr('website-id'),
        type: 'plugin',
        'slugs[]': [slug],
        nonce: mainwp_pro_reports_loc.nonce
    }

    if (bulk) {
        pro_reports_bulkCurrentThreads++;
    }

    statusEl.html('<i class="notched circle loading icon"></i>');

    jQuery.post(ajaxurl, data, function (response) {
        statusEl.html('');
        pObj.removeClass('queue');

        if (response && response['error']) {
            statusEl.html('<i class="red times icon"></i>');
        } else if (response && response['upgrades'][slug]) {
            pObj.remove();
            parent.removeClass('warning');
        } else {
            statusEl.html('<i class="red times icon"></i>');
        }

        if (bulk) {
            pro_reports_bulkCurrentThreads--;
            pro_reports_bulkFinishedThreads++;
            mainwp_pro_reports_update_specific_next(selector);
        }

    }, 'json');
    return false;
}

jQuery(document).ready(function ($) {

    $('#pro-report-schedule-send-email-auto').change(function () {
        if ($(this).is(':checked')) {
            $('#pro-report-schedule-send-email-bcc-me').removeAttr("disabled");
        } else {
            $('#pro-report-schedule-send-email-bcc-me').attr("disabled", "disabled");
            $('#pro-report-schedule-send-email-bcc-me').removeAttr("checked");
        }
    });

    $('#pro-report-schedule-send-email-me-review').change(function () {
        if ($(this).is(':checked')) {
            $('#pro-report-schedule-send-email-bcc-me').attr("disabled", "disabled");
            $('#pro-report-schedule-send-email-bcc-me').removeAttr("checked");
        } else {
            $('#pro-report-schedule-send-email-bcc-me').removeAttr("disabled");
        }
    });

    $('.reports_action_row_lnk').on('click', function () {

        var what = jQuery(this).attr('action');
        if (what == 'delete') {
            if (!confirm(__('Are you sure?'))) {
                return false;
            }
        }
        mainwp_reports_do_action_start_specific(this, what);

    });

    mainwp_reports_valid_input_data = function (action) {

        var errors = [];
        var selected_sites = [];
        var selected_groups = [];
        var selected_clients = [];

        if ($.trim($('#pro-report-title').val()) == '') {
            errors.push(__('Report Title missing! This field is mandatory.'));
        }

        if (action !== 'save') {

            if ($('#pro-report-type').val() == 0) {
                if ($.trim($('#pro-report-from-date').val()) == '') {
                    errors.push(__('Report Send from value missing! This field is mandatory.'));
                }

                if ($.trim($('#pro-report-to-date').val()) == '') {
                    errors.push(__('Report Send to value missing! This field is mandatory.'));
                }
            }

            if (jQuery('#select_by').val() == 'site') {
                jQuery("input[name='selected_sites[]']:checked").each(function (i) {
                    selected_sites.push(jQuery(this).val());
                });
            } else if (jQuery('#select_by').val() == 'group') {
                jQuery("input[name='selected_groups[]']:checked").each(function (i) {
                    selected_groups.push(jQuery(this).val());
                });
            } else {
                jQuery("input[name='selected_clients[]']:checked").each(function (i) {
                    selected_clients.push(jQuery(this).val());
                });
            }
            if (selected_sites.length == 0 && selected_groups.length == 0 && selected_clients.length == 0) {
                errors.push(__('Please select at least one website, tag, or client.'));
            }
        }


        if (action == 'schedule') {
            if ($.trim($('#pro-report-schedule-select :selected').val()) == '') {
                errors.push(__('Report schedule value missing! This field is mandatory.'));
            }
        }

        if (action == 'send' || action == 'save') {
            if ($.trim($('#pro-report-from-email').val()) == '') {
                errors.push(__('Report Send from email address missing! This field is mandatory.'));
            }

            if ($.trim($('#pro-report-to-email').val()) == '') {
                errors.push(__('Report Send to email address missing! This field is mandatory.'));
            }
        }

        if (errors.length > 0) {
            jQuery('#edit-reports-message-zone').html(errors.join('<br />')).show();
            jQuery(window).scrollTop(0);
            return false;
        } else {
            jQuery('#edit-reports-message-zone').html("");
            jQuery('#edit-reports-message-zone').hide();
        }
        return true;
    };


    $('#mainwp-pro-reports-send-report-button').on('click', function () {
        if (mainwp_reports_valid_input_data('send') === false) {
            return false;
        }
        if (!confirm("Are you sure?")) {
            return false;
        }
        $('#pro-report-action').val('send');
    });

    jQuery(document).on('click', '#mainwp-pro-reports-schedule-report-button', function () {
        if (mainwp_reports_valid_input_data('schedule') === false) {
            return false;
        }
        $('#pro-report-action').val('save');
    });

    $('#mainwp-pro-reports-preview-report-button').on('click', function () {
        if (mainwp_reports_valid_input_data() === false) {
            return false;
        }
        $('#pro-report-action').val('preview');
    });

    $('#mainwp-pro-reports-test-email-button').on('click', function () {
        if (mainwp_reports_valid_input_data() === false) {
            return false;
        }
        $('#pro-report-action').val('send_test');
    });

    jQuery(document).on('click', '#mainwp-pro-reports-save-report-button', function () {
        if (mainwp_reports_valid_input_data('save') === false) {
            return false;
        }
        jQuery('#pro-report-action').val('save');
        jQuery("form#mainwp-pro-report-form").submit();
    });

    jQuery(document).on('click', '#mainwp-pro-reports-pdf-button', function () {
        if (mainwp_reports_valid_input_data() === false) {
            return false;
        }
        $('#pro-report-action').val('save_pdf');
    });

    $('#mainwp-pro-reports-preview-send-button').on('click', function () {
        jQuery('#mainwp-pro-reports-preview-modal').modal('hide');
        jQuery('#mainwp-pro-reports-send-report-button').click();
    });

    $('.reports_active_plugin').on('click', function () {
        mainwp_reports_plugin_active_start_specific($(this), false);
        return false;
    });

    $('.reports_upgrade_plugin').on('click', function () {
        mainwp_reports_plugin_upgrade_start_specific($(this), false);
        return false;
    });

    $('.reports_showhide_plugin').on('click', function () {
        mainwp_reports_plugin_showhide_start_specific($(this), false);
        return false;
    });
});

mainwp_pro_reports_remove_sites_without_reports_plugin = function (str_ids) {
    var ids = str_ids.split(",");
    jQuery('#mainwp-pro-reports-select-sites-box #mainwp-select-sites .mainwp_selected_sites_item').each(function () {
        var site_id = jQuery(this).find('input[type="checkbox"]').attr('siteid');
        if (jQuery.inArray(site_id, ids) == -1) {
            jQuery(this).remove();
        }
    });
};


mainwp_reports_to_load_sites = function (pWhat, pReportId) {
    var data = {
        action: 'mainwp_pro_reports_load_sites',
        what: pWhat,
        report_id: pReportId,
        nonce: mainwp_pro_reports_loc.nonce
    };
    jQuery('#mainwp-pro-reports-generating-report-modal').modal({ closable: false }).modal('show');
    jQuery('#mainwp-pro-reports-generating-report-content').html('<div class="ui active inverted dimmer"><div class="ui small text loader">Loading...</div></div><p></p>');
    jQuery.post(ajaxurl, data, function (response) {
        if (response) {
            jQuery('#mainwp-pro-reports-generating-report-content').html(response);
            pro_reports_bulkTotalThreads = jQuery('.siteItemProcess[status=queue]').length;
            if (pro_reports_bulkTotalThreads > 0) {
                if (pWhat == 'send_test' || pWhat == 'send') {
                    pro_reports_bulkMaxThreads = 1; // send one email per time
                }
                mainwp_reports_action_start_next(pWhat, pReportId);
            }
        } else {
            jQuery('#mainwp-pro-reports-generating-report-content').html('<div class="ui red message">' + __("An unspecified error has occurred. Please retry the action. If the issue persists, contact support for further assistance.") + '</div>');

        }
    })
}

mainwp_reports_action_start_next = function (pWhat, pReportId) {
    while ((objProcess = jQuery('.siteItemProcess[status=queue]:first')) && (objProcess.length > 0) && (pro_reports_bulkCurrentThreads < pro_reports_bulkMaxThreads)) {
        objProcess.attr('status', 'processed');
        mainwp_reports_generate_start_specific(objProcess, pWhat, pReportId);
    }
    if (pro_reports_bulkFinishedThreads > 0 && pro_reports_bulkFinishedThreads == pro_reports_bulkTotalThreads) {
        mainwp_reports_generate_report_done(pWhat, pReportId);
    }
}

mainwp_reports_generate_start_specific = function (objProcess, pWhat, pReportId) {
    var loadingEl = objProcess.find('i');
    var statusEl = objProcess.find('.status');

    pro_reports_bulkCurrentThreads++;
    var data = {
        action: 'mainwp_pro_reports_generate_report',
        site_id: objProcess.attr('site-id'),
        what: pWhat,
        report_id: pReportId,
        nonce: mainwp_pro_reports_loc.nonce
    };

    statusEl.html('');
    loadingEl.show();
    jQuery.post(ajaxurl, data, function (response) {
        loadingEl.hide();
        if (response) {
            if (response.error) {
                statusEl.html('<span data-tooltip="' + response.error + '" data-inverted="" data-position="left center"><i class="red times icon" ></i></span>');
            } else if (response.result == 'success') {
                statusEl.html('<i class="green check icon"></i>');
                pro_reports_bulkSuccessCount++;
            } else {
                statusEl.html('<span data-tooltip="An unspecified error has occurred. Please retry the action. If the issue persists, contact support for further assistance." data-inverted="" data-position="left center"><i class="red times icon"></i></span>');
            }
        } else {
            statusEl.html('<span data-tooltip="An unspecified error has occurred. Please retry the action. If the issue persists, contact support for further assistance." data-inverted="" data-position="left center"><i class="red times icon"></i></span>');
        }
        pro_reports_bulkCurrentThreads--;
        pro_reports_bulkFinishedThreads++;
        mainwp_reports_action_start_next(pWhat, pReportId);
    }, 'json');
}

mainwp_reports_generate_report_done = function (pWhat, pReportId) {

    if (pWhat == 'preview') {
        location.href = 'admin.php?page=Extensions-Mainwp-Pro-Reports-Extension&tab=report&action=preview_generated&id=' + pReportId;
    } else if (pWhat == 'save_pdf' || pWhat == 'get_save_pdf') {
        location.href = 'admin.php?page=Extensions-Mainwp-Pro-Reports-Extension&tab=report&action=download_pdf&id=' + pReportId;
    } else if (pWhat == 'send' || pWhat == 'send_test') {
        if (pro_reports_bulkSuccessCount == pro_reports_bulkTotalThreads) {
            location.href = 'admin.php?page=Extensions-Mainwp-Pro-Reports-Extension&tab=report&id=' + pReportId + '&message=1'; // reports send message
        }
    }
}

mainwp_reports_do_action_start_specific = function (pObj, pWhat) {

    var row = jQuery(pObj).closest('tr');
    var rowtd = jQuery(pObj).closest('td');
    var reportId = row.attr('report-id');

    row.html('<td colspan="7"><i class="notched circle loading icon"></i></td>');
    var data = {
        action: 'mainwp_pro_reports_do_action_report',
        what: pWhat,
        reportId: reportId,
        nonce: mainwp_pro_reports_loc.nonce
    };

    jQuery.post( ajaxurl, data, function ( response ) {
        if ( response && response['status'] == 'success' ) {
            if ( pWhat == 'delete' ) {
                row.html( '<td colspan="7">' + __( 'The report has been successfully deleted.' ) + '</td>' );
            }
        } else if ( response && response['error'] ) {
            row.html( '<td colspan="7">' + response['error'] + '</td>' );
        } else {
            row.html( '<td colspan="7">' + __( 'An unspecified error has occurred. Please retry the action. If the issue persists, contact support for further assistance.' ) + '</td>' );
        }
    }, 'json');
    return false;
}

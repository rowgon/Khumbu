/**
 *	Copyright (C) 2015-26 CERBER TECH INC., https://wpcerber.com
 */
jQuery( function( $ ) {

    let crb_admin = $('#crb-admin');

    /* Select2 */

    let crb_se2 = crb_admin.find('select.crb-select2-ajax');
    if (crb_se2.length) {
        crb_se2.select2({
            allowClear: true,
            placeholder: crb_se2.data('placeholder'),
            minimumInputLength: crb_se2.data('min_symbols') ? crb_se2.data('min_symbols') : '1',
            ajax: {
                url: ajaxurl,
                dataType: 'json',
                delay: 1000,
                data: function (params) {
                    return {
                        user_search: params.term,
                        action: 'cerber_ajax',
                        ajax_nonce: crb_ajax_nonce,
                    };
                },
                processResults: function (data) {
                    return {
                        results: data
                    };
                },
                // cache: true // doesn't work due to "no-cache" header, see also: https://github.com/select2/select2/issues/3862
            }
        });
    }

    crb_se2 = crb_admin.find('select.crb-select2');
    if (crb_se2.length) {
        crb_se2.select2({
            /*width: 'resolve',*/
            /*selectOnClose: true*/
        });
    }

    crb_se2 = crb_admin.find('select.crb-select2-tags');
    if (crb_se2.length) {
        crb_se2.select2({
            tags: true,
            allowClear: true
        });
    }

    /* UI utils */

    crb_admin.on('click', '.crb-opener', function (event) {
        let target = $(this).data('target');
        if (target) {
            $('#' + target).slideToggle(200);
        }
    });

    // Plain confirmation dialog for click actions in the admin UI

    $(document.body).on('click', '.crb-confirm-action', function (event) {

        const message = $(this).data('user_message') || crb_admin_messages.are_you_sure;

        if (!confirm(message)) {
            event.preventDefault();
        }
    });

    /* WP Comments page */

    let comtable = 'table.wp-list-table.comments';

    if (typeof crb_lab_available !== 'undefined' && crb_lab_available && $(comtable).length) {
        $(comtable + " td.column-author").each(function (index) {
            let ip = $(this).find('a').last().text();
            let ip_id = cerber_get_id_ip(ip);
            $(this).append('<p><img class="crb-ajax-load" data-ajax_group="country" data-item_id="' + ip_id + '" src="' + crb_ajax_loader + '" /></p>');
        });
    }

    /* Load IP address data with AJAX */

    // New

    window.ajax_items = $(".crb-ajax-load");

    if (ajax_items.length) {
        cerber_ajax_data_process(ajax_items);
    }

    function cerber_ajax_data_process(ajax_items) {
        let ajax_groups = [];
        let group_items = [];

        ajax_items.each(function (index) {

            // Skip hidden elements. This class is used by WordPress to hide columns in the WordPress tables
            if ($(this).parent('.hidden').length) {
                $(this).replaceWith('');
                return;
            }

            let group = $(this).data('ajax_group');
            if (crb_is_empty(group_items[group])) {
                group_items[group] = [];
            }
            group_items[group].push(this);
            ajax_groups.push(group);
        });

        let ajax_groups_unique = ajax_groups.filter((element, index) => {
            return ajax_groups.indexOf(element) === index;
        });

        ajax_groups_unique.forEach(function (group) {
            let ajax_list = [];
            group_items[group].forEach(function (item) {
                let item_id = $(item).data('item_id');
                if (!crb_is_empty(group_items[group])) {
                    ajax_list.push(item_id);
                }
            });

            if (ajax_list.length !== 0) {
                $.post(ajaxurl, {
                    action: 'cerber_ajax',
                    crb_ajax_slug: group,
                    crb_ajax_list: ajax_list,
                    ajax_nonce: crb_ajax_nonce
                }, cerber_ajax_data_set, 'json');
            }
        });
    }

    function cerber_ajax_data_set(server_response) {
        if (crb_is_empty(server_response['data'])) {
            console.log('Error: No data provided by the server.');
            return;
        }
        let data = server_response['data'];
        let group = server_response['slug'];

        ajax_items.filter('[data-ajax_group="' + group + '"]').each(function () {
            $(this).replaceWith(data[$(this).data('item_id')]);
        });
    }

    // ACL management

    $(".acl-table .delete_entry").on('click', function () {
        $.post(ajaxurl, {
                action: 'cerber_ajax',
                acl_delete: $(this).data('ip'),
                slice: $(this).closest('[data-acl-slice]').data('acl-slice'),
                ajax_nonce: crb_ajax_nonce
            },
            onDeleteResponse,
            'json'
        );
    });

    function onDeleteResponse(server_response) {
        if (!crb_is_empty(server_response.error)) {
            alert(server_response.error);
        }
        else {
            $('.delete_entry[data-ip="' + server_response.deleted_ip + '"]').parent().parent().fadeOut(300);
        }
    }

    // ----------------------

    $('.crb-dismiss-trigger').on('click', function (e) {
        e.preventDefault();

        // Dismiss an element

        let $trigger = $(this);
        let $scope = $trigger.closest('.crb-dismiss-scope');

        if ($scope.length) {
            $scope.fadeOut(400, function () {
                $(this).remove();
            });
        }

        // Send optional ID and context to the server

        let dismiss_id = $trigger.data('crb-dismiss-id') || '';
        let dismiss_context = $trigger.data('crb-context') || '';

        if (!dismiss_context) {
            return;
        }

        // The element is already removed above. The server outcome is not acted upon, only reported,
        // so that a dismissal the server did not perform is diagnosable instead of silent.

        $.ajax({
            url: ajaxurl,
            method: 'POST',
            dataType: 'json',
            data: {
                action: 'cerber_ajax',
                ajax_nonce: crb_ajax_nonce,
                crb_dismiss_info: 1,
                crb_context: dismiss_context,
                crb_target_id: dismiss_id
            }
        }).done(function (server_response) {
            let dismiss_result = (server_response && server_response.diss_result_msg) || '';

            if (dismiss_result !== 'OK') {
                let dismiss_error = (server_response && server_response.error) || dismiss_result || 'no result reported';
                console.error('WP Cerber: the server did not confirm dismissing "' + dismiss_context + '": ' + dismiss_error);
            }
        }).fail(function (jqXHR, textStatus, errorThrown) {
            console.error('WP Cerber: the dismiss request for "' + dismiss_context + '" failed: ' + textStatus + ' ' + (errorThrown || jqXHR.status));
        });
    });


    function cerber_get_id_ip(ip) {
        let id = ip.replace(/\./g, '-');
        id = id.replace(/:/g, '_');

        return id;
    }

    /* Traffic */

    let crb_traffic = $('#crb-traffic');

    crb_traffic.find('tr.crb-toggle td.crb-request').on('click', function (event) {
        event.preventDefault();

        if ($(event.target).data('no-js') === 1) {
            return;
        }

        let request_details = $(this).closest('tr').next('.crb-request-details');

        if (request_details.length) {
            request_details.toggle();
        }
    });

    $('#traffic-search-btn').on('click', function (event) {
        $('#crb-traffic-search').slideToggle(500);
    });

    /* Enabling conditional input setting fields */

    let setting_form = $('.crb-settings-form');

    setting_form.find('input,select').on('change', function () {
        let enabler_id = $(this).attr('id');
        let enabler_val;

        if ('checkbox' === $(this).attr('type')) {
            enabler_val = !!$(this).is(':checked');
        }
        else {
            enabler_val = $(this).val();
        }

        setting_form.find('[data-input_enabler="' + enabler_id + '"]').each(function () {
            let input_data = $(this).data();
            let method = 'hide';

            if (typeof input_data['input_enabler_value'] !== "undefined") {
                let target = input_data['input_enabler_value'];
                if (Array.isArray(target)) {
                    for (let i = 0; i < target.length; i++) {
                        if (String(enabler_val) === String(target[i])) {
                            method = 'show';
                            break;
                        }
                    }
                }
                else {
                    if (String(enabler_val) === String(input_data['input_enabler_value'])) {
                        method = 'show';
                    }
                }
            }
            else {
                if (enabler_val) {
                    method = 'show';
                }
            }

            let input_wrapper = $(this).closest('tr');

            if (method === 'show') {
                input_wrapper.fadeIn(500);
                input_wrapper.find('input[data-input_required]').prop('required', true);
            }
            else if (method === 'hide') {
                input_wrapper.fadeOut();
                input_wrapper.find('input[data-input_required]').prop('required', false);
            }

        });
    });

    // Add UTM

    $('div#crb-admin').on('click', 'a', function (event) {
        let link = $(this).attr('href');
        if (link.startsWith('https://wpcerber.com') && !link.includes('wp-admin')) {
            let url_char = '?';
            if (link.includes('?')) {
                url_char = '&';
            }
            $(this).attr('href', link + url_char + 'utm_source=wp_plugin&culoc=' + crb_user_locale);
        }
    });

    /* Nexus Master's code */

    $('#crb-nexus-sites .crb-nexus-managed .column-updates a').on('click', function (event) {
        let managed_site_id = $(this).closest('tr').data('managed-site-id');

        $.magnificPopup.open({
            items: {
                src: ajaxurl + '?managed_site_id=' + managed_site_id + '&action=cerber_master_ajax&crb_ajax_do=nexus_view_updates&ajax_nonce=' + crb_ajax_nonce,
            },
            type: 'ajax',
            callbacks: {
                parseAjax: function (server_response) {
                    let the_response = JSON.parse(server_response.data);
                    // The server returns complete, pre-escaped markup wrapped in "crb-popup-wrap".
                    server_response.data = the_response['html'];
                },
                ajaxContentAdded: function () {
                    let popup_width = window.innerWidth * ((window.innerWidth < 800) ? 0.7 : 0.6);
                    $('.crb-admin-mpopup .mfp-content').css('width', popup_width + 'px');
                    let popup_height = window.innerHeight * ((window.innerHeight < 800) ? 0.7 : 0.6);
                    $('.crb-admin-mpopup #crb-inner').css('max-height', popup_height + 'px');
                }
            },
            overflowY: 'scroll', // main browser scrollbar
            mainClass: 'crb-admin-mpopup',
            closeOnContentClick: false,
            //preloader: true,
        });

        event.preventDefault();
    });

    $(document.body).on('click', '.crb-mpopup-close', function (event) {
        $.magnificPopup.close();
        event.preventDefault();
    });

    // GEO

    $("form#crb-geo-rules .crb-geo-switcher").on('change', function () {
        let to_show = '#crb-geo-wrap_' + $(this).data('rule-id');
        if ($(this).val() !== '---first') {
            to_show += '_' + $(this).val()
        }
        $(to_show).parent().children('.crb-geo-wrapper').hide();
        $(to_show).show();
    });

    // Simple Highlighter

    // Search and highlighting pieces of text, case-sensitive
    function cerber_highlight_text(id, text, limit) {
        let inputText = document.getElementById(id);
        if (inputText === null) {
            return;
        }

        let innerHTML = inputText.innerHTML;
        let i = 0;
        let list = [];
        let index = innerHTML.indexOf(text);
        while (index >= 0 && i < limit) {
            list.push(index);
            index = innerHTML.indexOf(text, index + 1);
            i++;
        }
        list.reverse();
        list.forEach(function (index) {
            innerHTML = innerHTML.substring(0, index) + "<span class='cerber-error'>" + innerHTML.substring(index, index + text.length) + "</span>" + innerHTML.substring(index + text.length);
        });

        inputText.innerHTML = innerHTML;
    }

    cerber_highlight_text('crb-log-viewer', 'ERROR:', 200);


    /* VTabs */

    // Select and initialize visible tab

    let crb_reveal = crb_parse_fragment();

    let vtabs = $('#crb-vtabs');
    let form_id = vtabs.closest('form').attr('id');
    let active_tab_id = crb_reveal.tab || crb_reveal.legacy;
    let active_tab = false;

    if (active_tab_id) {
        let find_tab = vtabs.find('[data-tab-id=' + active_tab_id + ']');
        active_tab = find_tab.length > 0 ? find_tab : false;
    }

    if (!active_tab) {
        active_tab_id = crb_get_local('vtab_active' + form_id);
    }

    if (active_tab_id) {
        active_tab = vtabs.find('[data-tab-id=' + active_tab_id + ']');
    }
    else {
        active_tab = vtabs.find('.tablinks').first();
    }

    active_tab.addClass('active_tab');

    crb_init_active_tab();
    crb_update_local('vtab_active' + form_id, active_tab_id);

    function crb_init_active_tab() {
        let active = $('#crb-vtabs .active_tab');
        let callback = active.data('callback');
        let tab_id = active.data('tab-id');
        $('#tab-' + tab_id).show();
        if (callback && (typeof window[callback] === "function")) {
            window[callback](tab_id);
        }
    }

    $('.tablinks').on('click', function () {
        let tab_id = $(this).data('tab-id');
        $('.vtabcontent').hide();
        //$('#tab-' + tab_id).show();

        $(".tablinks").removeClass('active_tab');
        $(this).addClass("active_tab");

        crb_init_active_tab();
        crb_update_local('vtab_active' + form_id, tab_id);
    });

    /* Misc UI routines */

    // Reveal the setting the link fragment points to

    crb_reveal_flash_element();

    // -----------------------------

    /**
     * Centers the setting named by the flash declaration in the viewport and flashes it.
     *
     * The declaration holds the ID of the wrapper of a setting field. Its setting row is revealed,
     * because the row carries both the setting title and the field. Positioning is done here rather
     * than left to the browser. A declaration is not an element ID, so a browser scrolls nowhere on
     * its own, while a legacy bare ID fragment makes it align the wrapper with the top edge of the
     * viewport, which leaves the setting at the very edge of the screen without the surrounding
     * settings. The scroll is not animated for a visitor who asked for reduced motion.
     *
     * Centering runs twice. Content that gets its final height late moves the setting after the
     * first centering, and a legacy fragment additionally has the browser repeating its own
     * alignment while the document is loading, so the position is applied again once the document
     * is loaded.
     *
     * The declaration promises a setting field, so a value that does not name one is ignored rather
     * than flashed. Does nothing when the fragment carries no flash declaration, when the named
     * element is absent, or when it is not part of a setting row.
     *
     * @returns {void}
     */
    function crb_reveal_flash_element() {
        let element_id = crb_reveal.element || crb_reveal.legacy;

        // The prefix mirrors CRB_WRAPPER_ID_PREFIX, the ID prefix of every setting field wrapper

        if (!element_id || element_id.indexOf('crb-setting-field-') !== 0) {
            return;
        }

        let setting = document.getElementById(element_id);

        if (!setting) {
            return;
        }

        let reveal_this = $(setting).closest('tr.crb-setting-row');

        if (!reveal_this.length) {
            return;
        }

        reveal_this.addClass('crb-flash-effect');

        // A browser that does not support the scrolling options keeps its own top alignment

        let center_in_viewport = function () {
            let scroll_behavior = window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth';

            reveal_this.get(0).scrollIntoView({block: 'center', behavior: scroll_behavior});
        };

        center_in_viewport();

        $(window).on('load', center_in_viewport);
    }

    /**
     * Parses the URL fragment into the reveal instructions this page acts on.
     *
     * A link generated by crb_get_setting_link() carries a list of declarations separated by a
     * semicolon, each one a key and a value bound by a colon, for example
     * crb-tab-reveal:administrator;crb-element-flash:crb-setting-field-role-administrator-2famode.
     * The two declarations are independent: crb-tab-reveal names the vertical tab to open,
     * crb-element-flash names the element to center and flash, and either may appear without the
     * other. A value never contains a separator, so no unescaping is involved.
     *
     * Declaration order does not matter, a key outside the grammar is ignored, a repeated key keeps
     * its first occurrence, and a declaration is discarded when it carries no colon or when its
     * value is not one the grammar allows.
     *
     * A fragment carrying no colon at all is the format used before the declarations existed, a
     * bare tab ID or element ID. It is reported on its own member so that both readers can fall
     * back to it and old bookmarks keep working.
     *
     * @returns {{tab: string, element: string, legacy: string}} The reveal instructions. A member
     *          holds an empty string when the fragment does not carry it.
     */
    function crb_parse_fragment() {
        let reveal = {tab: '', element: '', legacy: ''};
        let fragment = window.location.hash.replace(/^#/, '');

        if (!fragment) {
            return reveal;
        }

        if (fragment.indexOf(':') === -1) {
            reveal.legacy = crb_is_fragment_value(fragment) ? fragment : '';

            return reveal;
        }

        // Each key of the grammar is matched on its own, so a fragment reaches no member beyond the
        // two it is allowed to name

        fragment.split(';').forEach(function (declaration) {
            let separator = declaration.indexOf(':');

            if (separator < 1) {
                return;
            }

            let declaration_key = declaration.substring(0, separator);
            let declaration_value = declaration.substring(separator + 1);

            if (!crb_is_fragment_value(declaration_value)) {
                return;
            }

            if (declaration_key === 'crb-tab-reveal' && !reveal.tab) {
                reveal.tab = declaration_value;
            }
            else if (declaration_key === 'crb-element-flash' && !reveal.element) {
                reveal.element = declaration_value;
            }
        });

        return reveal;
    }

    /**
     * Tells whether a fragment carries a value the declaration grammar allows.
     *
     * The fragment is external input, while a declared value addresses an element or a tab of this
     * page. A value outside the alphabet a WP Cerber ID is built from is rejected as a whole rather
     * than repaired, because dropping the offending characters would produce a different ID, and a
     * setting the link never named would be revealed.
     *
     * @param {string} value Raw value taken from one fragment declaration.
     * @returns {boolean} True when the value consists of ASCII letters, digits, hyphens, and
     *          underscores only.
     */
    function crb_is_fragment_value(value) {
        return /^[a-z\d_-]+$/i.test(value);
    }

});

function crb_create_link(href, text, options = {}, dataAttributes = {}) {
    let link = document.createElement('a');

    link.href = crb_escape_string(href);
    link.textContent = crb_escape_string(text);

    const { css_class = '', id = '', target = '' } = options;

    if (css_class) {
        link.className = crb_escape_string(css_class);
    }

    if (id) {
        link.id = crb_escape_string(id);
    }

    if (target) {
        link.target = crb_escape_string(target);
    }

    for (let key in dataAttributes) {
        if (dataAttributes.hasOwnProperty(key)) {
            link.setAttribute('data-' + crb_escape_string(key), crb_escape_string(dataAttributes[key]));
        }
    }

    return link;
}

/**
 * Escapes special characters in the given text to prevent XSS attacks when generating output for a web page.
 *
 * @param {string} text - The text to escape.
 * @returns {string} - The escaped text.
 */
function crb_escape_string(text) {
    return String(text).replace(/[<>"']/g, function (m) {
        return {
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#39;'
        }[m];
    });
}

/**
 * Recursively escapes special characters within the given array to prevent XSS attacks when generating output for a web page.
 *
 * @param {Array} arr - The array to escape elements of.
 * @returns {Array} - The array with escaped elements.
 */
function crb_escape_array_elements(arr) {
    return arr.map(element => {
        if (Array.isArray(element)) {
            return crb_escape_array_elements(element);
        }
        else if (typeof element === 'string') {
            return crb_escape_string(element);
        }
        else {
            return element;
        }
    });
}

/**
 * Recursively traverses all properties of an object, escaping strings that contain special HTML characters to prevent XSS attacks when generating output for a web page.
 *
 * @param {Object} obj - The object to escape the string values for.
 * @returns {Object} - The object with escaped string values.
 */
function crb_escape_object_properties(obj) {
    if (typeof obj !== 'object' || obj == null) {
        return obj;
    }

    let escaped_obj = Array.isArray(obj) ? [] : {};

    for (let key in obj) {
        if (obj.hasOwnProperty(key)) {
            let value = obj[key];
            if (typeof value === 'object' && value !== null) {
                escaped_obj[key] = crb_escape_object_properties(value);
            }
            else if (typeof value === 'string') {
                escaped_obj[key] = crb_escape_string(value);
            }
            else {
                escaped_obj[key] = value;
            }
        }
    }

    return escaped_obj;
}


/* Storage API */

const crb_sprefix = 'wp_cerber_';

function crb_update_local(key, value, json = false) {
    if (json) {
        value = JSON.stringify(value)
    }

    localStorage.setItem(crb_sprefix + key, value);
}

function crb_get_local(key, json = false) {
    let value = localStorage.getItem(crb_sprefix + key);

    if (!json) {
        if (value == null) {
            value = '';
        }
        return value;
    }

    if (value == null || value == '') {
        return {};
    }

    return JSON.parse(value);
}

function crb_delete_local(key) {
    localStorage.removeItem(crb_sprefix + key);
}

/* Misc */

function crb_is_empty(thing) {
    if (typeof thing === 'undefined') {
        return true;
    } else if (thing.length === 0) {
        return true;
    }

    return false;
}

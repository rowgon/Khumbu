<?php
/*
	Copyright (C) 2015-26 CERBER TECH INC., https://wpcerber.com

    Licensed under the GNU GPL.

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

/*

*========================================================================*
|                                                                        |
|	       ATTENTION!  Do not change or edit this file!                  |
|                                                                        |
*========================================================================*

*/

if ( ! defined( 'WPINC' ) || ! defined( 'CERBER_VER' ) ) {
	exit;
}

/**
 * settings_screen_id: logical identifier of a settings screen, used to select
 * sections, fields, defaults, validation, and saving behavior. The constant
 * value is also the name of the hidden form field that carries the ID in
 * submitted settings forms.
 *
 * @since 8.5.9.1
 */
const CRB_SETTINGS_SCREEN_ID_FIELD = 'cerber_settings_screen_id';

// id prefix for a setting's actual input element
const CRB_INPUT_ID_PREFIX = 'crb-input-';

// id prefix for a setting field's wrapping <div>
const CRB_WRAPPER_ID_PREFIX = 'crb-setting-field-';

// Fragment declaration keys of a link that reveals a setting, see crb_get_setting_link().
// The declaration key names the vertical tab to open and the element to flash, respectively.
const CRB_FRAGMENT_TAB_KEY = 'crb-tab-reveal';
const CRB_FRAGMENT_FLASH_KEY = 'crb-element-flash';

// Namespace prefix for a settings screen's field names: cerber-{settings_screen_id}[{field}].
// Also doubles as the key into the defaults array returned by cerber_get_defaults().
const CRB_INPUT_NAME_PREFIX = 'cerber-';

/**
 * Top-level processor of plugin settings form submissions posted to plugin
 * admin pages.
 *
 * Local interactive submissions follow the POST-redirect-GET pattern: after
 * a settings form has been processed, the browser is redirected back to the
 * settings page the form was posted to. Queued admin messages survive the
 * redirect since they are stored in the database.
 *
 * The redirect target states an empty fragment, so the single-use reveal
 * declarations of the link that opened the page, see crb_get_setting_link(),
 * do not flash the setting again on every save.
 *
 * Settings forms submitted on the main Cerber.Hub website for a managed
 * website are forwarded by nexus_show_remote_page() and processed on the
 * managed website by nexus_process_settings_form(), not by this function.
 *
 * @return void
 */
function crb_settings_processor() {

    if ( ! cerber_is_admin_page()
         || ! cerber_is_http_post() ) {
        return;
    }

    // POST-redirect-GET for settings forms posted to a plugin admin page

    if ( cerber_process_settings_form() ) {
        crb_safe_redirect( crb_url_discard_fragment( (string) $_SERVER['REQUEST_URI'] ) );
        exit;
    }
}

function cerber_role_select( $name = 'cerber-roles', $selected = array(), $class = '', $multiple = '', $placeholder = '', $width = '75%' ) {

	if ( ! is_array( $selected ) ) {
		$selected = array( $selected );
	}
	if ( ! $placeholder ) {
		$placeholder = __( 'Select one or more roles', 'wp-cerber' );
	}
	$roles = wp_roles();
	$options = array();
	foreach ( $roles->get_names() as $key => $title ) {
		$s         = ( in_array( $key, $selected ) ) ? 'selected' : '';
		$options[] = '<option value="' . $key . '" ' . $s . '>' . $title . '</option>';
	}

	$m = ( $multiple ) ? 'multiple="multiple"' : '';

	// Setting width via class is not working
	$style = '';
	if ( $width ) {
		$style = 'width: ' . $width.';';
	}

	return ' <select style="' . $style . '" name="' . $name . '" class="crb-select2 ' . $class . '" ' . $m . ' data-placeholder="' . $placeholder . '" data-allow-clear="true">' . implode( "\n", $options ) . '</select>';
}

function cerber_time_select( $args, $settings ) {

	// Week
	$php_week = array(
		__( 'Sunday' ),
		__( 'Monday' ),
		__( 'Tuesday' ),
		__( 'Wednesday' ),
		__( 'Thursday' ),
		__( 'Friday' ),
		__( 'Saturday' ),
	);
	$field = $args['setting_id'].'-day';
	$selected = $settings[ $field ] ?? '';
	$ret = cerber_select( CRB_INPUT_NAME_PREFIX . $args['settings_screen_id'] . '[' . $field . ']', $php_week, $selected );
	/* translators: 'at' is a preposition of time, used in contexts like "at 11:00" or "at midnight". */
	$ret .= ' &nbsp; ' . _x( 'at', 'preposition of time like: at 11:00', 'wp-cerber' ) . ' &nbsp; ';

	// Hours
	$hours = array();
	for ( $i = 0; $i <= 23; $i ++ ) {
		$hours[] = str_pad( $i, 2, '0', STR_PAD_LEFT ) . ':00';
	}
	$field = $args['setting_id'] . '-time';
	$selected = $settings[ $field ] ?? '';
	$ret .= cerber_select( CRB_INPUT_NAME_PREFIX . $args['settings_screen_id'] . '[' . $field . ']', $hours, $selected );

	return $ret . crb_test_notify_link( array( 'type' => 'report', 'title' => __( 'Click to send now', 'wp-cerber' ) ) );
}

/**
 * Generates day and time picker.
 * Replacement for cerber_time_select()
 *
 * @param array $conf Setting field config
 * @param mixed $val Value of the saved setting
 *
 * @return string HTML code of the picker
 * @since 9.3.5
 */
function cerber_time_picker( $conf, $val ) {

	// Week
	if ( $conf['period'] == 'one_week' ) {
		$period = array(
			__( 'Sunday' ),
			__( 'Monday' ),
			__( 'Tuesday' ),
			__( 'Wednesday' ),
			__( 'Thursday' ),
			__( 'Friday' ),
			__( 'Saturday' ),
		);
	}
	elseif ( $conf['period'] == 'one_month' ) {
		$period = range( 0, 31 );
		unset( $period[0] );
    }
    else {
        return 'Not supported period specified';
    }

	$selected = $val['day'] ?? '';
	$picker = cerber_select( CRB_INPUT_NAME_PREFIX . $conf['settings_screen_id'] . '[' . $conf['setting_id'] . '][day]', $period, $selected );
	/* translators: 'at' is a preposition of time, used in contexts like "at 11:00" or "at midnight". */
	$picker .= ' &nbsp; ' . _x( 'at', 'preposition of time like: at 11:00', 'wp-cerber' ) . ' &nbsp; ';

	// Hours
	$hours = array();
	for ( $i = 0; $i <= 23; $i ++ ) {
		$hours[] = str_pad( $i, 2, '0', STR_PAD_LEFT ) . ':00';
	}
	$selected = $val['hours'] ?? '';
	$picker .= cerber_select( CRB_INPUT_NAME_PREFIX . $conf['settings_screen_id'] . '[' . $conf['setting_id'] . '][hours]', $hours, $selected );

	return $picker . crb_test_notify_link( array( 'type' => 'report', 'test_period' => $conf['period'], 'title' => __( 'Click to send now', 'wp-cerber' ) ) );
}


function crb_render_checkbox( $name, $value, $label = '', $id = '', $atts = '' ) {
	if ( ! $id ) {
		$id = CRB_INPUT_ID_PREFIX . $name;
	}

	return '<div style="display: table-cell;"><label class="crb-switch"><input class="screen-reader-text" type="checkbox" id="' . $id . '" name="' . $name . '" value="1" ' . checked( 1, $value, false ) . $atts . ' /><span class="crb-slider round"></span></label></div>
	<div style="display: table-cell;"><label for="' . $id . '">' . $label . '</label></div>';
}

function cerber_digi_field( $name, $value = '', $class = '', $args = array() ) {
	return cerber_txt_field( $name, $value, '', $args['size'] ?? '3', $args['maxln'] ?? '3', '\d+', $class . ' crb-input-digits' );
}

function cerber_txt_field( $name, $value = '', $id = '', $size = '', $maxlength = '', $pattern = '', $class = '' ) {
	$atts = '';

	$atts .= $id ? ' id="' . $id . '" ' : '';
	$atts .= $class ? ' class="' . $class . '" ' : '';
	$atts .= $size ? ' size="' . $size . '" ' : '';
	$atts .= $maxlength ? ' maxlength="' . $maxlength . '" ' : '';
	$atts .= $pattern ? ' pattern="' . $pattern . '" ' : '';

	return '<input type="text" name="' . $name . '" value="' . $value . '" ' . $atts . ' />';
}

function cerber_nonce_field( $action = 'control', $echo = false ) {
	$sf = '';
	if ( nexus_is_valid_request() ) {
		$sf = '<input type="hidden" name="cerber_nexus_seal" value="' . nexus_request_data()->seal . '">';
	}
	$nf = wp_nonce_field( $action, 'cerber_nonce', false, false );
	if ( ! $echo ) {
		return $nf . $sf;
	}

	echo $nf . $sf;
}

/**
 * Generates a WordPress admin submit button, optionally disabled.
 *
 * @param string $text  Optional. Button text. Defaults to "Save Changes".
 * @param bool   $echo  Optional. Whether to echo the HTML. Defaults to false.
 *
 * @return string The button HTML. Safe in any context.
 */
function crb_admin_submit_button( string $text = '', bool $echo = false ) {

	$text = $text ?: __( 'Save Changes', 'wp-cerber' );

	$disabled    = '';
	$hint_text = '';

	if ( nexus_is_valid_request()
         && ! nexus_is_granted( 'submit' ) ) {
		$disabled    = 'disabled="disabled"';
		$hint_text = ' not available in the read-only mode';
	}

	$html = '<p class="submit"><input ' . $disabled . ' type="submit" name="submit" id="submit" class="button button-primary" value="' . esc_attr( $text ) . '" /> ' . esc_html( $hint_text ) . '</p>';

	if ( $echo ) {
		echo $html;
	}

	return $html;
}

/**
 * Legacy per-screen sanitizer for the Main Settings screen. Pending migration
 * to per-field callbacks in the settings config.
 *
 * @param array<string,mixed> $new New setting values to sanitize
 * @param array<string,mixed> $old Currently stored setting values
 *
 * @return array<string,mixed> Sanitized setting values
 */
function crb_pre_update_main( $new, $old ) {
	if ( isset( $new['boot-mode'] ) ) {
		$ret = cerber_set_boot_mode( $new['boot-mode'] );

		if ( crb_is_wp_error( $ret ) ) {
			crb_admin_error_notice( $ret );
			cerber_admin_notice( __( 'Plugin initialization mode has not been changed', 'wp-cerber' ) );
			$new['boot-mode'] = $old['boot-mode'];
		}
        elseif ( $ret == 2 ) {
			cerber_admin_message( __( 'A must-use WP Cerber boot file has been copied to the WordPress must-use directory', 'wp-cerber' ) );
		}
	}

	$new['attempts'] = absint( $new['attempts'] );
	$new['period']   = absint( $new['period'] );
	$new['lockout']  = absint( $new['lockout'] );

	$new['agperiod'] = absint( $new['agperiod'] );
	$new['aglocks']  = absint( $new['aglocks'] );
	$new['aglast']   = absint( $new['aglast'] );

	if ( cerber_is_permalink_enabled() ) {
		$new['loginpath'] = urlencode( str_replace( '/', '', $new['loginpath'] ) );
		$new['loginpath'] = sanitize_text_field( $new['loginpath'] );

		if ( $new['loginpath'] ) {
			if ( $new['loginpath'] == 'wp-admin'
			     || preg_match( '/[#|.!?:\s]/', $new['loginpath'] ) ) {
                crb_add_admin_error( __( 'The entered value is not allowed for the Custom login URL field. Please specify a different one.', 'wp-cerber' ) );
				$new['loginpath'] = $old['loginpath'];
			}
			elseif ( $new['loginpath'] != $old['loginpath'] ) {
				$href    = cerber_get_site_url() . '/' . $new['loginpath'] . '/';
				$url     = urldecode( $href );
				$msg     = array();
				$msg_e   = array();
				$msg[]   = __( 'Attention! You have changed the login URL! The new login URL is', 'wp-cerber' ) . ': <a href="' . $href . '">' . $url . '</a>';
				$msg_e[] = __( 'Attention! You have changed the login URL! The new login URL is', 'wp-cerber' ) . ': ' . $url;
				$msg[]   = __( 'If you use a caching plugin, you have to add your new login URL to the list of pages not to cache.', 'wp-cerber' );
				$msg_e[] = __( 'If you use a caching plugin, you have to add your new login URL to the list of pages not to cache.', 'wp-cerber' );
				cerber_admin_notice( $msg );
                CRB_Messaging::send( 'newlurl', array( 'text' => $msg_e ) );
			}
		}
	}
	else {
		$new['loginpath'] = '';
		$new['loginnowp'] = 0;
	}

	if ( $new['loginnowp'] && empty( $new['loginpath'] ) && ! class_exists( 'WooCommerce' ) ) {
		cerber_admin_notice( array(
			'<b>' . __( 'Heads up!' ) . '</b>',
			__( 'You have disabled the default login page. Ensure that you have configured an alternative login page. Otherwise, you will not be able to log in.', 'wp-cerber' )
		) );
	}

	$new['ciduration'] = absint( $new['ciduration'] );
	$new['cilimit']    = absint( $new['cilimit'] );
	$new['cilimit']    = $new['cilimit'] == 0 ? '' : $new['cilimit'];
	$new['ciperiod']   = absint( $new['ciperiod'] );
	$new['ciperiod']   = $new['ciperiod'] == 0 ? '' : $new['ciperiod'];
	if ( ! $new['cilimit'] ) {
		$new['ciperiod'] = '';
	}
	if ( ! $new['ciperiod'] ) {
		$new['cilimit'] = '';
	}

	return $new;
}

/**
 * Legacy per-screen sanitizer for the Anti-spam settings screen. Pending
 * migration to per-field callbacks in the settings config.
 *
 * @param array<string,mixed> $new New setting values to sanitize
 * @param array<string,mixed> $old Currently stored setting values
 *
 * @return array<string,mixed> Sanitized setting values
 */
function crb_pre_update_antispam( $new, $old ) {

	if ( empty( $new['botsany'] ) && empty( $new['botscomm'] ) && empty( $new['botsreg'] ) ) {
		update_site_option( 'cerber-antibot', '' );
	}

	$warn = false;

	if ( ! empty( $new['botsany'] )
         && crb_array_get( $new, 'botsany' ) != crb_array_get( $old, 'botsany' ) ) {
		$warn = true;
	}

	if ( ! empty( $new['botscomm'] )
         && crb_array_get( $new, 'botscomm' ) != crb_array_get( $old, 'botscomm' ) ) {
		$warn = true;
	}

	if ( ! empty( $new['customcomm'] ) ) {
		if ( ! crb_get_compiled( 'custom_comm_slug' ) ) {
			crb_update_compiled( 'custom_comm_slug', crb_random_string( 20, 30 ) );
			crb_update_compiled( 'custom_comm_mark', crb_random_string( 20, 30 ) );
			$warn = true;
		}
	}
	else {
		if ( crb_get_compiled( 'custom_comm_slug' ) ) {
			crb_update_compiled( 'custom_comm_slug', '' );
			$warn = true;
		}
	}

	if ( $warn ) {
		cerber_admin_notice( array(
			'<b>' . __( 'Important note if you have a caching plugin in place', 'wp-cerber' ) . '</b>',
			__( 'To avoid false positives and get better anti-spam performance, please clear the plugin cache.', 'wp-cerber' )
		) );
	}

	return $new;
}
/**
 * Legacy per-screen sanitizer for the reCAPTCHA settings screen. Pending
 * migration to per-field callbacks in the settings config.
 *
 * @param array<string,mixed> $new New setting values to sanitize
 * @param array<string,mixed> $old Currently stored setting values
 *
 * @return array<string,mixed> Sanitized setting values
 */
function crb_pre_update_recaptcha( $new, $old ) {

	// Check ability to make external HTTP requests
	if ( ! empty( $new['sitekey'] ) && ! empty( $new['secretkey'] ) ) {
		if ( ( ! $goo = get_wp_cerber()->reCaptchaRequest( '1' ) )
		     || empty( $goo['success'] ) ) {
            crb_add_admin_error( cerber_get_labels( 'status', 534 ) );
		}
	}

	$new['recaptcha-period'] = absint( $new['recaptcha-period'] );
	$new['recaptcha-number'] = absint( $new['recaptcha-number'] );
	$new['recaptcha-within'] = absint( $new['recaptcha-within'] );

	return $new;
}
/**
 * Legacy per-screen sanitizer for the Notifications settings screen. Pending
 * migration to per-field callbacks in the settings config.
 *
 * @param array<string,mixed> $new New setting values to sanitize
 * @param array<string,mixed> $old Currently stored setting values
 *
 * @return array<string,mixed> Sanitized setting values
 */
function crb_pre_update_notifications( $new, $old ) {

	$new['email'] = crb_email_purify( $new['email'] );
	$new['email-report'] = crb_email_purify( $new['email-report'] );
	$new['email_report_one_month'] = crb_email_purify( $new['email_report_one_month'] );

	$new['emailrate'] = absint( $new['emailrate'] );

	// When we install a new token, we set proper default value for the device setting

	if ( $new['pbtoken'] != $old['pbtoken'] ) {

		if ( ! $new['pbtoken'] ) {
			$new['pbdevice'] = '';
		}
		else {

			$list = cerber_pb_get_devices( $new['pbtoken'] );

			if ( is_array( $list ) && ! empty( $list ) ) {
				$new['pbdevice'] = 'all';
				cerber_admin_message( __( 'The Pushbullet token is valid. You can select devices to receive notifications.', 'wp-cerber' ) );
			}
			else {
				$new['pbdevice'] = '';
				if ( crb_is_wp_error( $list ) ) {
                    crb_add_admin_error( crb_escape_html( $list->get_error_message() ) );
				}
			}
		}
	}

	return $new;
}

/**
 * Legacy per-screen sanitizer for the Scanner Schedule settings screen.
 * Pending migration to per-field callbacks in the settings config.
 *
 * @param array<string,mixed> $new New setting values to sanitize
 * @param array<string,mixed> $old Currently stored setting values
 *
 * @return array<string,mixed> Sanitized setting values
 */
function crb_pre_update_schedule( $new, $old ) {
	$new['scan_aquick'] = absint( $new['scan_aquick'] );
	$new['scan_afull-enabled'] = ( empty( $new['scan_afull-enabled'] ) ) ? 0 : 1;

	$sec = cerber_sec_from_time( $new['scan_afull'] );
	if ( ! $sec || ! ( $sec >= 0 && $sec <= 86400 ) ) {
		$new['scan_afull'] = '01:00';
	}

	$new['email-scan'] = crb_email_purify( $new['email-scan'] );

	if ( lab_lab() ) {
		if ( cerber_cloud_sync( $new ) ) {
			cerber_admin_message( __( 'The scanner schedule has been updated', 'wp-cerber' ) );
		}
		else {
			cerber_admin_message( __( 'Unable to update the scanner schedule', 'wp-cerber' ) );
		}
	}

	return $new;
}

function cerber_normal_dirs( $list = array() ) {
	if ( ! is_array( $list ) ) {
		$list = cerber_text2array( $list, "\n" );
	}
	$ready = array();

	foreach ( $list as $item ) {
		$item = rtrim( cerber_normal_path( $item ), '/\\' ) . DIRECTORY_SEPARATOR;
		if ( ! @is_dir( $item ) ) {
			$dir = cerber_get_abspath() . ltrim( $item, DIRECTORY_SEPARATOR );
			if ( ! @is_dir( $dir ) ) {
				cerber_admin_notice( 'Directory does not exist: ' . crb_escape_html( $item ) );
				continue;
			}
			$item = $dir;
		}
		$ready[] = $item;
	}

	return $ready;
}

/**
 * @param string $emails
 *
 * @return array
 * @since 9.3.5
 */
function crb_email_purify( $emails ) {

	$list = array();

	if ( $emails = cerber_text2array( $emails, ',' ) ) {

		foreach ( $emails as $item ) {
			if ( is_email( $item ) ) {
				$list[] = $item;
			}
			else {
				cerber_admin_notice( __( '<strong>ERROR</strong>: please enter a valid email address.' ) );
			}
		}
	}

    return $list;
}

/**
 * Process WP Cerber's settings forms
 *
 * @return bool True if a settings form has been processed (with or without
 * validation errors on particular values), false if the request is not a
 * processable settings form submission or the security checks failed.
 *
 * @since 8.6
 *
 */
function cerber_process_settings_form(): bool {

	if ( ! cerber_is_http_post()
         || nexus_is_remote_context()
	     || ! $settings_screen_id = crb_get_post_fields( CRB_SETTINGS_SCREEN_ID_FIELD ) ) {
		return false;
	}

	if ( ! nexus_is_valid_request() ) {

        // Standard local context: we check permissions and the form nonce

		if ( ! cerber_user_can_manage() ) {
			return false;
		}

        // Abort processing if the plugin form nonce is not valid or missing.
		// See cerber_nonce_field() in cerber_show_settings_form()
		if ( ! wp_verify_nonce( (string) crb_get_post_fields( 'cerber_nonce', '' ), 'control' ) ) {
			cerber_admin_notice( __( 'The settings were not saved because the form has expired. Please try again.', 'wp-cerber' ) );

			return false;
		}
	}

    // All checks are passed, now we are processing the submitted form fields

    $post_fields = crb_get_post_fields( CRB_INPUT_NAME_PREFIX . $settings_screen_id, array() );
	crb_trim_deep( $post_fields );
	$post_fields = stripslashes_deep( $post_fields );

	// The Cerber.Hub managed-site edit form carries client website data
	// stored on the main website, not plugin settings

	if ( defined( 'CRB_NX_MANAGED' ) && $settings_screen_id == CRB_NX_MANAGED ) {
		return nexus_save_client_data_form( $post_fields );
	}

    if ( ! defined( '_CRB_PROCESS_SETTING_FORM' ) ) {
        define( '_CRB_PROCESS_SETTING_FORM', true );
    }

    $msg = '';

	if ( 'addon-settings' == crb_get_post_fields( 'page_type' ) ) {
		if ( CRB_Addons::update_settings( $post_fields, crb_get_post_fields( 'addon_id' ) ) ) {
			$msg = __( 'Add-on settings updated', 'wp-cerber' );
		}
	}
	else {
		// Fields extracted from the setting fields registry
		$fields_one = array_fill_keys( array_keys( CRB_Settings_Registry::get_setting_fields( $settings_screen_id ) ), '' );

        // Fields extracted from the default WP Cerber settings
		$defs = cerber_get_defaults();
		$fields_two = array_fill_keys( array_keys( $defs[ CRB_INPUT_NAME_PREFIX . $settings_screen_id ] ), '' );

        // For the best coverage, we combine them, thereby ensuring that we save the complete configuration.
		$fields = array_merge( $fields_one, $fields_two );
		$update_settings = array_merge( $fields, $post_fields );

		if ( cerber_settings_update( $update_settings, $settings_screen_id ) ) {
			$msg = __( 'Plugin settings updated', 'wp-cerber' );
		}
	}

	if ( $msg ) {
		cerber_admin_message( $msg, true );

		// A non-empty message means the settings were successfully written to the DB

		CRB_Settings_Backup::sync( CRB_Settings_Backup::CONTEXT_SETTINGS_UPDATE );
	}

	return true;
}

/**
 * Fully deletes specified settings stored in the DB.
 *
 * @param string[] $delete_list Setting IDs to delete
 *
 * @return string[] Deleted setting IDs
 *
 * @since 9.6.7.5
 */
function cerber_settings_delete( array $delete_list ) {

	if ( ( ! $all_settings = get_site_option( CERBER_CONFIG ) )
	     || ! is_array( $all_settings ) ) {
		return array();
	}

	$deleted = array();
	$protected = CRB_Settings_Registry::get_setting_fields();
	$protected [ CRB_ADDON_STS ] = 1;
	$protected [ CRB_ROLE_STS ] = 1;

	foreach ( $delete_list as $id ) {
		if ( ! isset( $all_settings[ $id ] )
		     || isset( $protected[ $id ] ) ) {
			continue;
		}

		unset( $all_settings[ $id ] );
		$deleted[] = $id;
	}

	if ( $deleted
	     && update_site_option( CERBER_CONFIG, $all_settings ) ) {
		return $deleted;
	}

	return array();
}

/**
 * Updates WP Cerber's settings in the database in the new format
 *
 * @param array $new_settings Array of settings (id => value) to update
 * @param string $settings_screen_id ID of the settings screen the settings belong to
 *
 * @return bool
 *
 * @since 9.3.4
 */
function cerber_settings_update( array $new_settings, string $settings_screen_id = '' ) {

	if ( ( ! $stored_settings = get_site_option( CERBER_CONFIG ) )
	     || ! is_array( $stored_settings ) ) {
		$stored_settings = array();
	}

    // Ensure that all settings keys are in place in $stored_settings

	$all_settings = array_fill_keys( array_keys( crb_get_default_values() ), '' );
	$stored_settings = array_merge( $all_settings, $stored_settings );

    // Preserve PRO settings if the license is expired

	if ( ! lab_lab()
	     && $preserve = array_intersect_key( $new_settings, CRB_PRO_SETTINGS ) ) {
		$new_settings = array_merge( $new_settings, array_intersect_key( $stored_settings, $preserve ) );
	}

	// Pre-process in the old way @before 9.3.4

	$new_settings = cerber_settings_pre_update( $stored_settings, $new_settings, $settings_screen_id );

    // Merge existing settings and new ones

	$save = array_merge( $stored_settings, $new_settings );

    // Pre-process in the new way @since 9.3.4

	$defs = CRB_Settings_Registry::get_setting_fields();
	$defs = array_intersect_key( $defs, $new_settings );

	foreach ( $defs as $id => $config ) {

        // Validate the setting value if a validation callback is defined

        if ( ( $validate_cb = crb_array_get( $config, 'validate_value' ) )
             && is_callable( $validate_cb ) ) {

            $validation_check = call_user_func_array( $validate_cb, array( $save[ $id ], $save ) );

            if ( crb_is_wp_error( $validation_check )
                 && defined( '_CRB_PROCESS_SETTING_FORM' ) ) {

                $error_messages = $validation_check->get_error_messages();

                /* translators: %s is a setting title. */
                array_unshift( $error_messages, sprintf( __( 'Please check the values in %s. See the errors below.', 'wp-cerber' ), '<strong>' . esc_html( $config['title'] ) . '</strong>' ) );

                cerber_admin_notice( $error_messages );
            }
        }

        // Alter the setting value if a pre-update callback is defined

        if ( ( $pre_update = crb_array_get( $config, 'pre_update' ) )
		     && is_callable( $pre_update ) ) {

	        $save[ $id ] = call_user_func_array( $pre_update, array( $save[ $id ], $stored_settings[ $id ], &$save, $stored_settings, $new_settings ) );
		}
	}

    // Check if any changes in the settings

	$changed = array();

	foreach ( $save as $key => $val ) {

		if ( ! isset( $stored_settings[ $key ] ) ) {
			continue;
		}

		$old = $stored_settings[ $key ];

		// We compare only non-empty values, for WP Cerber settings '', 0, false, and empty array are equal

		if ( empty( $old ) && empty( $val ) ) {

			continue;
		}

		if ( ! is_array( $val ) && ! is_array( $old ) ) {
			if ( (string) $old != (string) $val ) {
				$changed[] = $key;
			}
		}
        elseif ( is_array( $val ) && is_array( $old ) ) {
			if ( json_encode( $old ) !== json_encode( $val ) ) {
				$changed[] = $key;
			}
		}
		else {
			$changed[] = $key;
		}

	}

    $is_equal = empty( $changed );
    $result = false;

	if ( ! $is_equal ) {

		// Save settings to the DB

		if ( ! $result = update_site_option( CERBER_CONFIG, $save ) ) {
			cerber_admin_notice( 'Critical I/O error #77 occurred while updating WP Cerber settings.' );
		}
	}

	$diag_log = array();

	if ( $result ) {

		crb_purge_settings_cache(); // Delete outdated values from the static cache

		// The stored configuration has been rewritten with a valid value: the corrupted
		// settings issue registered by crb_get_settings() is resolved now

		CRB_Issues::delete_item( 'corrupted_settings', 'settings' );

		// The administrator has reviewed and saved the settings: the notice about
		// the automatic restoration from a backup copy is resolved as well

		CRB_Issues::delete_item( 'settings_restored', 'settings' );

		$back = array();

		foreach ( $defs as $id => $config ) {

            if ( ! in_array( $id, $changed ) ) {
				continue;
			}

			if ( $msg = crb_array_get( $config, 'diag_log' ) ) {
				$diag_log[ $id ] = $msg;
			}

            // Post-processing if any needed

			if ( ( $on_change_cb = crb_array_get( $config, 'on_change' ) )
			     && is_callable( $on_change_cb ) ) {

				call_user_func( $on_change_cb, $save[ $id ], $save, $stored_settings );
			}

			// Rolling back value if a rollback returns true

			if ( ( $rollback_cb = crb_array_get( $config, 'rollback' ) )
			     && is_callable( $rollback_cb ) ) {

				if ( call_user_func( $rollback_cb, $save[ $id ], $save, $stored_settings ) ) {
					$back[ $id ] = crb_array_get( $stored_settings, $id, '' );
				}
			}
		}

		if ( $back ) {

			// Rolling back values

			$save = array_merge( $save, $back );

			if ( ! update_site_option( CERBER_CONFIG, $save ) ) {
				cerber_admin_notice( 'Critical I/O error #78 occurred while updating WP Cerber settings.' );
			}
			else {
				$changed = array_flip( array_diff_key( array_flip( $changed ), $back ) );
			}
		}

	}

	$update_details = array(
		'settings_screen_id' => $settings_screen_id,
		'group'              => $settings_screen_id, // Legacy alias for add-on handlers.
		'equal'              => $is_equal,
		'result'             => $result,
		'changed'            => $changed,
		'new_values'         => $new_settings,
		'old_values'         => $stored_settings,
	);

	if ( $result && $changed && $diag_log ) {
		crb_journaling( $update_details, $diag_log );
	}

	crb_event_handler( 'update_settings', $update_details );

    return $result;
}

/**
 * Sanitizing and processing setting values before saving to the DB.
 * Runs the legacy per-screen sanitizers pending their migration to
 * per-field callbacks in the settings config.
 *
 * @param array<string,mixed> $stored_settings Currently stored setting values
 * @param array<string,mixed> $new_settings New setting values to sanitize
 * @param string $settings_screen_id ID of the settings screen being saved, 'all' to run all per-screen sanitizers
 *
 * @return array<string,mixed> Sanitized setting values
 *
 * @since 9.3.4
 */
function cerber_settings_pre_update( $stored_settings, $new_settings, $settings_screen_id = 'all' ) {

	if ( $settings_screen_id === 'all' ) {
		$new_settings = crb_pre_update_main( $new_settings, $stored_settings );
		$new_settings = crb_pre_update_antispam( $new_settings, $stored_settings );
		$new_settings = crb_pre_update_recaptcha( $new_settings, $stored_settings );
		$new_settings = crb_pre_update_notifications( $new_settings, $stored_settings );
		$new_settings = crb_pre_update_schedule( $new_settings, $stored_settings );
	}
    elseif ( $settings_screen_id ) {

		// Screen specific sanitizing and processing

		$defaults_group_key = CRB_INPUT_NAME_PREFIX . $settings_screen_id;

		switch ( $defaults_group_key ) {
			case CERBER_OPT:
				$new_settings = crb_pre_update_main( $new_settings, $stored_settings );
				break;
			case CERBER_OPT_A:
				$new_settings = crb_pre_update_antispam( $new_settings, $stored_settings );
				break;
			case CERBER_OPT_C:
				$new_settings = crb_pre_update_recaptcha( $new_settings, $stored_settings );
				break;
			case CERBER_OPT_N:
				$new_settings = crb_pre_update_notifications( $new_settings, $stored_settings );
				break;
			case CERBER_OPT_E:
				$new_settings = crb_pre_update_schedule( $new_settings, $stored_settings );
				break;
		}
	}

    // Global sanitizing and processing

	return cerber_grand_sanitizing( $new_settings );
}

/**
 *  Global sanitizing, processing, formatting
 *
 * @param array $settings
 *
 * @return array
 */
function cerber_grand_sanitizing( $settings ) {

	$pre_sanitize = $settings;
	$set_minimum = array();
	$defs = CRB_Settings_Registry::get_setting_fields();

	// Parsing settings, applying formatting, etc.

	foreach ( $settings as $setting_id => &$setting_val ) {

		if ( ! $conf = crb_array_get( $defs, $setting_id ) ) {
			continue;
		}

		if ( $enabler = $conf['enabler'] ?? '' ) {
			if ( crb_check_enabler( $conf, $settings[ $enabler[0] ] ) ) {
				continue;
			}
		}

		$callback = crb_array_get( $conf, 'apply' );
		$regex = crb_array_get( $conf, 'regex_filter' ); // Filtering out not allowed chars
		$validate = crb_array_get( $conf, 'validate' );

		if ( isset( $conf['list'] ) ) {
			// Process the values
			$setting_val = cerber_text2array( $setting_val, $conf['delimiter'], $callback, $regex );

			// Remove not allowed values
			global $_deny;
			if ( $_deny = crb_array_get( $conf, 'deny_filter' ) ) {
				$setting_val = array_filter( $setting_val, function ( $e ) {
					global $_deny;

					return ! in_array( $e, $_deny );
				} );
			}
		}
		else {
			// Process the value
			if ( $callback && is_callable( $callback ) ) {
				$setting_val = call_user_func( $callback, $setting_val );
			}
			if ( $regex ) {
				$setting_val = mb_ereg_replace( $regex, '', $setting_val );
			}

            // Validating the value
			if ( $validate ) {

				$field_name = $conf['title'] ?? $conf['label'] ?? 'Unknown field';
				$error_msg = '';

				if ( ! empty( $validate['required'] )
				     && ! $setting_val ) {
					/* translators: %s is the field name. */
					$error_msg = sprintf( __( 'Field %s may not be empty', 'wp-cerber' ), '<b>' . $field_name . '</b>' );
				}
                elseif ( $setting_val
				         && ( $sat = $validate['satisfy'] ?? false )
				         && is_callable( $sat )
				         && ! call_user_func( $sat, $setting_val ) ) {
					/* translators: %s is the field name. */
					$error_msg = sprintf( __( 'Field %s contains an invalid value', 'wp-cerber' ), '<b>' . $field_name . '</b>' );
				}

				if ( $error_msg ) {
                    crb_add_admin_error( $error_msg );
				}
			}

			// Limits for numeric values

			if ( isset( $conf['min_val'] )
			     && ! ( ( $conf['empty_val'] ?? false ) && $setting_val === '' ) // Is empty values allowed
			     && $setting_val < $conf['min_val'] ) {
				$setting_val = $conf['min_val'];
				$set_minimum[] = $setting_id;
			}
		}
	}

	crb_sanitize_deep( $settings );

	// Warn the user if we have altered some values the user has entered

	if ( $set_minimum ) {
		$list = array_intersect_key( $defs, array_flip( $set_minimum ) );
		$msg = '<div style="font-weight: 600;"><p>' . implode( '</p><p>', array_column( $list, 'title' ) ) . '</p></div>';
		cerber_admin_notice( __( 'The following settings have been set to their minimum acceptable values.', 'wp-cerber' ) . $msg );
	}

	$changed = array();

	foreach ( $pre_sanitize as $setting_id => $pre_val ) {
		if ( empty( $pre_val ) ) {
			continue;
		}

		if ( is_array( $pre_val ) ) { // Usually checkboxes
			if ( json_encode( $pre_val ) !== json_encode( $settings[ $setting_id ] ) ) {
				$changed[] = $setting_id;
			}
		}
        elseif ( is_array( $settings[ $setting_id ] ) ) {
			$pre_val = preg_replace( '/\s+/', '', $pre_val );
			$san_val = preg_replace( '/\s+/', '', crb_format_field_value( $settings[ $setting_id ], crb_array_get( $defs, $setting_id ) ) );
			if ( $pre_val != $san_val ) {
				$changed[] = $setting_id;
			}
		}
        elseif ( $pre_val != $settings[ $setting_id ] ) {
			$changed[] = $setting_id;
		}
	}

	if ( $changed ) {
		$list = array_intersect_key( $defs, array_flip( $changed ) );
		$msg = '<div style="font-weight: 600;"><p>' . implode( '</p><p>', array_column( $list, 'title' ) ) . '</p></div>';
		cerber_admin_notice( __( 'For safety reasons, prohibited symbols and invalid values have been removed from the following settings. Please check their values.', 'wp-cerber' ) . $msg );
	}

	return $settings;
}

/**
 * Logs changes of specific WP Cerber settings to the diagnostic log.
 * To be logged, a setting has to have a 'diag_log' field in the setting config.
 *
 * @param array $update_details Information about updated settings.
 * @param array $diag_log_list The list of settings that have to be logged to the diagnostic log.
 *
 * @return void
 *
 * @since 9.0.1
 */
function crb_journaling( $update_details, $diag_log_list ) {
	if ( $update_details['equal'] ) {
		return;
	}

	if ( ! $changed = array_intersect( array_keys( $diag_log_list ), $update_details['changed'] ) ) {
		return;
	}

	foreach ( $changed as $key ) {

		$what = $diag_log_list[ $key ];

		if ( empty( $update_details['new_values'][ $key ] ) ) {
			$msg = 'Disabled: ' . $what;
		}
		else {
			$msg = 'Enabled: ' . $what;
		}

		cerber_diag_log( $msg, '*' );
	}

}

/**
 * Check setting field enabler and returns conditional inputs CSS class
 *
 * @param array $config The config of a setting field
 * @param mixed $enab_val The value of the enabler field
 *
 * @return string CSS class to be used
 */
function crb_check_enabler( $config, $enab_val ) {
	if ( ! isset( $config['enabler'] ) ) {
		return '';
	}

	$enabled = true;

	if ( isset( $config['enabler'][1] ) ) {
		$target_val = $config['enabler'][1];
		if ( 0 === strpos( $target_val, '[' ) ) {
			$list = json_decode( $target_val );
			if ( ! in_array( $enab_val, $list ) ) {
				$enabled = false;
			}
		}
		else {
			if ( $enab_val != $target_val ) {
				$enabled = false;
			}
		}
	}
	else {
		if ( empty( $enab_val ) ) {
			$enabled = false;
		}
	}

	return ( ! $enabled ) ? ' crb-disable-this' : '';
}

/**
 * @param mixed $value
 * @param array $config
 *
 * @return string
 *
 * @since 9.1.5
 */
function crb_format_field_value( $value, $config ) {
	if ( isset( $config['list'] ) ) {
		$dlt = $config['delimiter_show'] ?? $config['delimiter'];
		$value = cerber_array2text( $value, $dlt );
	}

	return $value;
}

/**
 * @param string $file
 *
 * @return bool False if .htaccess was not updated
 */
function crb_htaccess_admin( $file ): bool {

	$result = cerber_htaccess_sync( $file );

	if ( crb_is_wp_error( $result ) ) {
		CRB_Globals::$htaccess_failure[ $file ] = true;
		crb_admin_error_notice( $result );

		return false;
	}
    elseif ( $result ) {
		cerber_admin_message( $result );
	}

	return true;
}

/**
 * Retrieves and caches the list of languages available for the WP Cerber admin interface.
 * Function fetches the list of translations from the WP Cerber repo.
 *
 * @param array[] &$set Languages in an array as pairs [ locale => language name ]
 *
 * @return void|WP_Error Returns WP_Error if fetching available languages fails
 *
 * @since 9.6.6.14
 */
function crb_get_admin_languages( &$set = array() ) {

	if ( ( $set = get_site_transient( 'wp_cerber_lang_set' ) )
	     && is_array( $set ) ) {

		return;
	}

	$set = array();

	if ( ! include_once( ABSPATH . 'wp-admin/includes/translation-install.php' ) ) {

		return new WP_Error( 'include_failed', 'Unable to load translation-install.php' );
	}

	$list = array();

	// Get list if language names

	if ( $wp_langs = wp_get_available_translations() ) {
		$list = crb_extract_column_preserve_keys( $wp_langs, 'native_name' );
	}

	// Get available translations

	$data = crb_get_remote_json( 'https://downloads.wpcerber.com/versions/wp-cerber.json' );

	if ( crb_is_wp_error( $data ) ) {
		crb_admin_error_notice( $data );

		return $data;
	}

	if ( ! $crb_locales = $data[ CERBER_PLUGIN_ID ]['trans_bucket'] ?? false ) {

		return new WP_Error( 'no_trans_bucket', 'Unable to load available languages' );
	}

	// Prepare list of languages

	$crb_locales = array_filter( $crb_locales );
	$list = array_intersect_key( $list, $crb_locales );

	$list = array_filter( $list, function ( $key ) {
		return strpos( $key, 'en_' ) !== 0;
	}, ARRAY_FILTER_USE_KEY );

	$list['en_US'] = 'English';
	ksort( $list );

	set_site_transient( 'wp_cerber_lang_set', $list, DAY_IN_SECONDS );

    $set = $list;
}

/**
 * Determines the locale (translation language) for rendering WP Cerber admin pages for the current user.
 *
 * The locale is determined based on the WP Cerber settings and user preferences,
 * prioritizing the configured admin language, with a fallback to the user's locale.
 *
 * @param int $user_id User's ID or a WP_User object. Defaults to current user.
 *
 * @return string WordPress locale used for rendering admin pages.
 *
 * @since 9.6.6.14
 */
function crb_get_admin_locale( int $user_id = 0 ): string {
    static $locale;

	if ( $locale ) {
		return $locale;
	}

	$locale = '';

	if ( nexus_is_valid_request()
	     && $locale = nexus_request_data()->locale ) {

		return $locale;
	}

	if ( crb_get_settings( 'cerber_sw_repo' ) ) {
		$locale = crb_get_settings( 'admin_locale' );
	}
    elseif ( crb_get_settings( 'admin_lang' ) ) {
		$locale = 'en_US';
	}

	if ( ! $locale ) {
		$locale = get_user_locale( $user_id );
	}

	return $locale;
}

/**
 * Validates values of a settings field (user input) for proper REGEX patterns.
 *
 * Only values wrapped in curly braces {} are treated as REGEX patterns
 * and validated. All other values are considered plain strings and skipped.
 *
 * @param string|string[] $val The value to validate. Can be a string or an array of strings.
 *
 * @return true|WP_Error Returns true if all patterns are valid or the value is empty.
 *                       Returns a WP_Error object if invalid patterns are detected.
 *
 * @since 9.7.1
 */
function crb_validate_regex_field( $val ) {

    if ( ! $val ) {
        return true;
    }

    if ( ! is_array( $val ) ) {
        $val = array( $val );
    }

    $ret = new WP_Error();

    foreach ( $val as $item ) {

        if ( ! $item ) {
            continue;
        }

        $item = (string) $item;

        if ( $item[0] === '{' && substr( $item, - 1 ) === '}' ) {
            if ( ! $regex_body = substr( $item, 1, - 1 ) ) {
                continue;
            }

            $pattern = '~' . str_replace( '~', '\~', $regex_body ) . '~i';

            $result = crb_validate_regex( $pattern );

            if ( ! $result['valid'] ) {

                /* translators: %s is an invalid REGEX pattern. */
                $ret->add( 'invalid_regex', sprintf( __( 'The REGEX pattern %s is not valid.', 'wp-cerber' ), '<span class="crb-monospace-bold">' . crb_escape_html( $regex_body ) . '</span>' ) . ' ' . $result['error'] );
            }
        }
    }

    return $ret->has_errors() ? $ret : true;
}

/**
 * Validates a regular expression pattern using PHP PCRE engine.
 *
 * This function performs syntax-level validation by attempting to compile
 * the given pattern via preg_match(). It is intended for validating user-defined
 * REGEX patterns in settings before they are used in runtime logic.
 *
 * @param string $pattern The REGEX pattern including delimiters and optional modifiers.
 *
 * @return array{
 *     valid: bool,
 *     error: string
 * }
 *
 * @since 9.7.1
 */
function crb_validate_regex( string $pattern ): array {

    if ( $pattern === '' ) {
        return [
                'valid' => false,
                'error' => 'Empty pattern',
        ];
    }

    // Capture PCRE warning text produced during pattern compilation.
    $error_message = '';

    set_error_handler(
            static function ( $errno, $errstr ) use ( &$error_message ) {
                $error_message = $errstr;

                return true;
            },
            E_WARNING
    );

    try {
        $result = preg_match( $pattern, '' );
    }
    finally {
        restore_error_handler();
    }

    // Return the captured compilation error if the pattern is invalid.
    if ( $result === false ) {
        return [
                'valid' => false,
                'error' => $error_message ?: __( 'Invalid regular expression', 'wp-cerber' ),
        ];
    }

    // The pattern is syntactically valid.
    return [
            'valid' => true,
            'error' => '',
    ];
}

/**
 * Generates an HTML link tag to the admin setting page where the specified setting is located
 *
 * The URL fragment of the generated link carries the declarations that tell the admin UI
 * what to reveal on the target page. A declaration is a key and a value bound by a colon,
 * and declarations are separated by a semicolon. The CRB_FRAGMENT_TAB_KEY declaration names
 * the vertical tab to open, the CRB_FRAGMENT_FLASH_KEY declaration names the setting field
 * wrapper to center in the viewport and flash. The two are independent: a global setting
 * link carries the flash declaration alone, a role policy link carries both, and a role
 * policy link for an unknown user carries neither. Declaration values are sanitized IDs and
 * therefore never contain a separator, so the fragment needs no escaping of its own.
 *
 * @param string $setting_id WP Cerber setting ID
 * @param int $user_id User ID, if the setting is role-based and a user is involved.
 * @param bool $show_title Whether to prepend the setting title before the link.
 *
 * @return string Safe HTML markup for rendering in the admin UI, or an empty string
 *                 if the setting location cannot be determined.
 *
 * @since 9.7
 */
function crb_get_setting_link( string $setting_id, int $user_id = 0, bool $show_title = false ): string {

	$title = '';
	$tab_id = '';
	$declarations = array();

	if ( $setting = CRB_Settings_Registry::get_setting_config( (string) $setting_id ) ) {
		if ( $title = $setting['title'] ?? '' ) {
			$tab_id = $setting['page_tab_id'];
			$declarations[ CRB_FRAGMENT_FLASH_KEY ] = CRB_WRAPPER_ID_PREFIX . 'global-' . $setting_id;
		}
	}
	elseif ( $setting = crb_get_role_settings_config( $setting_id ) ) {
		if ( $title = $setting['title'] ?? '' ) {
			$tab_id = 'role_policies';

			if ( $user_id
			     && ( $user = crb_get_userdata( $user_id ) )
			     && $role = crb_sanitize_id( array_shift( $user->roles ) ) ) { // Currently, we do not support multiple roles

				$declarations[ CRB_FRAGMENT_TAB_KEY ] = crb_admin_get_vtab_id( $role );
				$declarations[ CRB_FRAGMENT_FLASH_KEY ] = CRB_WRAPPER_ID_PREFIX . 'role-' . $role . '-' . $setting_id;
			}
		}
	}

	if ( ! $tab_id ) {
		return '';
	}

	return ( $show_title ? $title : '' ) . ' [&nbsp;<a href="' . crb_admin_link_for_html( $tab_id ) . crb_attr_escape( crb_build_reveal_fragment( $declarations ) ) . '" target="_blank">' . __( 'Manage', 'wp-cerber' ) . '</a>&nbsp;]';
}

/**
 * Builds the URL fragment that tells the admin UI what to reveal on the target page.
 *
 * The declarations are rendered in the order given, each one as a key and a value bound by a
 * colon, with a semicolon between declarations. Callers must supply keys defined for this
 * format, CRB_FRAGMENT_TAB_KEY and CRB_FRAGMENT_FLASH_KEY, and values that are sanitized IDs,
 * because the format reserves the colon and the semicolon as separators and provides no
 * escaping for them. A declaration with an empty value is skipped, since it names nothing.
 *
 * @param array<string,string> $declarations Declaration values keyed by the declaration key.
 *
 * @return string The fragment including its leading hash character, or an empty string when
 *                no declaration carries a value.
 *
 * @since 9.7
 */
function crb_build_reveal_fragment( array $declarations ): string {

	$rendered = array();

	foreach ( $declarations as $declaration_key => $declaration_value ) {
		if ( $declaration_value !== '' ) {
			$rendered[] = $declaration_key . ':' . $declaration_value;
		}
	}

	if ( ! $rendered ) {
		return '';
	}

	return '#' . implode( ';', $rendered );
}

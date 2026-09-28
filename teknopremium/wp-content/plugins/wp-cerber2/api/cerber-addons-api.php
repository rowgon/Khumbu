<?php

// WP Cerber add-ons API -----------------------------------------------------------------

/**
 * @param string $file Add-on PHP file to be loaded after WP Cerber has loaded itself
 * @param string $addon_id Add-on slug (unique add-on ID)
 * @param string $name Name of the add-on show in the admin UI
 * @param string $requires Optional version of WP Cerber required by the add-on
 * @param callable $settings Optional configuration of the add-on setting fields: a callback function returning the settings fields since 9.6.2.3
 * @param callable $cb Optional callback function invoked when a website admin saves add-on settings.
 *
 * @return bool
 */
function cerber_register_addon( $file, $addon_id, $name, $requires = '', $settings = null, $cb = null ) {

	return CRB_Addons::register_addon( $file, $addon_id, $name, $requires, $settings, $cb );
}

/**
 * @param string $event
 * @param callable $callback
 * @param string $addon_id
 *
 * @return bool
 */
function cerber_add_handler( $event, $callback, $addon_id = null ) {

	return CRB_Events::add_handler( $event, $callback, $addon_id );
}

/**
 * Returns add-on settings
 *
 * @param string $addon_id
 * @param string $setting
 * @param bool $purge_cache
 *
 * @return array|bool|mixed
 *
 * @since 9.3.4
 */
function cerber_get_addon_settings( $addon_id = '', $setting = '', $purge_cache = false ) {
	$all = crb_get_settings( CRB_ADDON_STS, $purge_cache );

	if ( ! $addon_id ) {
		return $all;
	}

	$ret = crb_array_get( $all, $addon_id, false );

	if ( ! $ret || ! $setting ) {
		return $ret;
	}

	return crb_array_get( $ret, $setting, false );
}

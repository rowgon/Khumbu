<?php

/**
 * Class CRB_Settings_Renderer
 *
 * Owns presentation of WP Cerber settings screens in the admin UI.
 *
 * This class is the boundary between declarative settings definitions and
 * emitted settings-form markup. It consumes configuration supplied by
 * CRB_Settings_Registry or by runtime callers such as add-ons and Cerber.Hub
 * management screens, then renders that configuration as the form structure
 * expected by the settings processing layer.
 *
 * The renderer intentionally does not own persistence, validation of submitted
 * settings, capability checks, or business rules beyond presentation-time
 * visibility decisions. Those responsibilities remain with the registry,
 * settings processor, and feature-specific callbacks. Its application-level
 * side effect is direct HTML output, including form controls, hidden routing
 * fields, nonces, and informational links for local and managed-site settings
 * pages.
 *
 * @since 9.8.4
 */
final class CRB_Settings_Renderer {

	/**
	 * Renders a complete settings form for a plugin settings screen.
	 *
	 * The form posts to the current admin page, includes the screen identifier
	 * and a control nonce, then renders the configured sections and submit
	 * button. Local settings pages pass a screen ID and rely on the registry
	 * configuration. Add-on and managed-site forms may pass extra hidden fields
	 * and an explicit section configuration that is rendered instead of loading
	 * the registry configuration.
	 *
	 * The caller must provide configuration that follows the settings registry
	 * section and field schema when using the $sections override. Hidden field
	 * names and values are escaped before output. The reserved settings screen
	 * hidden field is always set from $settings_screen_id, overriding any value
	 * supplied in $hidden_fields.
	 *
	 * @param string $settings_screen_id Settings screen ID used in form element IDs,
	 *                                   input names, and submission dispatch.
	 * @param array<string,string|int|float|bool> $hidden_fields Additional hidden
	 *                                                          fields submitted
	 *                                                          with the form.
	 * @param array<string,array<string,mixed>> $sections Optional section
	 *                                                    configuration. When
	 *                                                    empty, the renderer
	 *                                                    loads the screen
	 *                                                    configuration from
	 *                                                    CRB_Settings_Registry.
	 *
	 * @return void
	 */
	public static function render_form( $settings_screen_id = '', $hidden_fields = array(), $sections = array() ) {
		?>
		<div class="crb-admin-form">
			<form id="crb-form-<?php echo crb_boring_escape( $settings_screen_id ); ?>" class="crb-settings-form" method="post" action="">

				<?php

				$hidden_fields[ CRB_SETTINGS_SCREEN_ID_FIELD ] = $settings_screen_id;

				if ( $hidden_fields ) {
					foreach ( $hidden_fields as $name => $value ) {
						echo '<input type="hidden" name="' . crb_boring_escape( $name ) . '" value="' . crb_boring_escape( $value ) . '">';
					}
				}

				cerber_nonce_field( 'control', true );

				self::render_sections( $settings_screen_id, $sections );

				echo '<div style="padding-left: 220px">';

				echo crb_admin_submit_button();

				echo '</div>';

				?>

			</form>
		</div>
		<?php
	}

	/**
	 * Renders settings sections and visible field rows for a settings screen.
	 *
	 * If no section configuration is provided, the configuration is loaded from
	 * CRB_Settings_Registry for the given screen ID. Each section may render a
	 * heading, section deck, and one table row per field that passes its display
	 * constraints. Sections with no visible fields keep their heading and deck
	 * but do not render an empty table.
	 *
	 * Must be called inside an HTML form element. Field callbacks from the
	 * settings configuration may execute while the form is rendered and can
	 * modify derived field attributes by reference.
	 *
	 * @param string $settings_screen_id Settings screen ID used to load registry
	 *                                   configuration and namespace generated
	 *                                   input names.
	 * @param array<string,array<string,mixed>> $sections Optional section
	 *                                                    configuration. When
	 *                                                    empty, the registry
	 *                                                    configuration is used.
	 *
	 * @return void
	 */
	private static function render_sections( string $settings_screen_id, array $sections = array() ) {
		if ( ! $sections
		     && ! $sections = CRB_Settings_Registry::get_config( array( 'settings_screen_id' => $settings_screen_id ) ) ) {
			return;
		}

		foreach ( $sections as $section_id => $section_config ) {

			// Section heading and description block

			if ( $section_title = crb_array_get( $section_config, 'name', '' ) ) {
				echo '<h2>' . $section_title . '</h2>' . "\n";
			}

			echo self::build_section_deck_html( $section_config );

			// Prepare visible fields

			$prepared_fields = array();

			foreach ( $section_config['fields'] as $field_id => $field_config ) {
				if ( $prepared = self::prepare_field_config( $field_id, $field_config, $settings_screen_id ) ) {
					$prepared_fields[] = $prepared;
				}
			}

			// No visible fields gets no table

			if ( ! $prepared_fields ) {
				continue;
			}

			echo '<table class="form-table" role="presentation">';

			foreach ( $prepared_fields as $prepared ) {
				echo '<tr class="' . crb_attr_escape( $prepared['class'] ) . '"><th scope="row">' . crb_array_get( $prepared, 'title', '' ) . '</th><td>';
				self::render_field( $prepared );
				echo '</td></tr>';
			}

			echo '</table>';
		}
	}

	/**
	 * Builds the optional informational block shown below a section heading.
	 *
	 * Accepts a section configuration that may define advisory text,
	 * descriptive text, security links, and a documentation link. Text and URLs
	 * are escaped for direct HTML output. Link labels are expected to be
	 * human-readable strings supplied by trusted settings configuration.
	 *
	 * @param array<string,mixed> $section_config Section configuration from the
	 *                                            settings registry or an
	 *                                            equivalent runtime form
	 *                                            definition.
	 *
	 * @return string HTML for the section deck, or an empty string when the
	 *                section defines no deck content.
	 */
	private static function build_section_deck_html( array $section_config ): string {

		$deck_items = [];

		if ( $text = crb_array_get( $section_config, 'section_advisory' ) ) {
			$deck_items[] = '<div style="color:#DF0000;">' . crb_escape_html( $text ) . '</div>';
		}

		if ( $text = crb_array_get( $section_config, 'section_desc' ) ) {
			$deck_items[] = '<div>' . crb_escape_html( $text ) . '</div>';
		}

		$all_links = array_merge(
			crb_array_get( $section_config, 'seclinks', [] ),
			crb_array_get( $section_config, 'doclink' ) ? [ [ __( 'Documentation', 'wp-cerber' ), $section_config['doclink'] ] ] : []
		);

		foreach ( $all_links as $link ) {
			$deck_items[] = sprintf(
				'<div class="crb-insetting-link">[ <a target="_blank" href="%s">%s</a> ]</div>',
				crb_escape_url( $link[1] ),
				esc_html( $link[0] )
			);
		}

		if ( ! $deck_items ) {
			return '';
		}

		return '<div class="crb-setting-section-deck">' . implode( "\n", $deck_items ) . '</div>';
	}

	/**
	 * Normalizes and filters a field configuration before row rendering.
	 *
	 * A field is omitted when its WordPress version requirement is not met, its
	 * requires_true callback returns false, or it is a PRO-only setting without
	 * an active PRO context. Visible fields receive the identifiers and default
	 * type needed by render_field(), plus row CSS classes derived from row
	 * callbacks, enabler state, and hidden field type.
	 *
	 * The field configuration is expected to come from trusted plugin code.
	 * Optional row_attr callbacks receive an attributes array by reference and
	 * may add a classes list used for the table row.
	 *
	 * @param string $setting_id Field ID as defined in the settings
	 *                           configuration.
	 * @param array<string,mixed> $field_config Field configuration from the
	 *                                          settings registry or equivalent
	 *                                          runtime form definition.
	 * @param string $settings_screen_id Settings screen ID that owns the field.
	 *
	 * @return array<string,mixed> Prepared field configuration, or an empty
	 *                             array when the field must not be rendered.
	 */
	private static function prepare_field_config( string $setting_id, array $field_config, string $settings_screen_id ): array {

		// Field skip conditions

		if ( ( $req_wp = $field_config['requires_wp'] ?? false )
		     && ! crb_wp_version_compare( $req_wp ) ) {
			return array();
		}

		if ( ( $cb = $field_config['requires_true'] ?? false )
		     && is_callable( $cb )
		     && ! $cb() ) {
			return array();
		}

		if ( array_key_exists( $setting_id, CRB_PRO_SETTINGS ) && ! lab_lab() ) {
			return array();
		}

		// Derived config entries used by render_field()

		$field_config['setting_id'] = $setting_id;
		$field_config['settings_screen_id'] = $settings_screen_id;

		$field_config['type'] = $field_config['type'] ?? 'text';

		// Setting row (tr) classes, to specify the input class use 'input_class'

		$row_class = array( 'crb-setting-row' );
		$attrs = array();

		if ( ( $row_attr = ( $field_config['row_attr'] ?? false ) )
		     && is_callable( $row_attr ) ) {

			call_user_func_array( $row_attr, array( &$attrs ) );
		}

		if ( $class_list = $attrs['classes'] ?? false ) {
			$row_class = array_merge( $row_class, $class_list );
		}

		if ( isset( $field_config['enabler'] ) ) {
			$row_class[] = 'crb-font-normal';
		}

		if ( $field_config['type'] == 'hidden' ) {
			$row_class[] = 'crb-display-none';
		}

		if ( isset( $field_config['enabler'] ) ) {
			$row_class[] = crb_check_enabler( $field_config, crb_get_settings( $field_config['enabler'][0] ) );
		}

		$field_config['class'] = implode( ' ', $row_class );

		return $field_config;
	}

	/**
	 * Renders one prepared settings field input and its auxiliary markup.
	 *
	 * The configuration must already include the screen ID and field ID entries
	 * produced by prepare_field_config(). The current stored settings are used
	 * as default values unless the configuration provides an explicit non-empty
	 * value. Custom input_renderer, pre_render, and callback_under callbacks are
	 * supported for fields whose markup or displayed value cannot be represented
	 * by the built-in field types.
	 *
	 * The method echoes HTML directly and returns no value. It does not persist
	 * settings. Dynamic values are escaped before being inserted into generated
	 * attributes or field values, while custom renderers are responsible for
	 * returning safe HTML.
	 *
	 * @param array<string,mixed> $config Prepared field configuration.
	 *
	 * @return void
	 */
	private static function render_field( array $config ): void {

		$settings = crb_get_settings();

		$attrs = array();

		$label = $config['label'] ?? '';

		if ( ! empty( $config['doclink'] ) ) {
			$label .= '<span class="crb-insetting-link">[ <a class="crb-no-wrap" target="_blank" href="' . $config['doclink'] . '">' . __( 'Know more', 'wp-cerber' ) . '</a> ]</span>';
		}

		// Unconditionally required
		$attrs['required'] = $config['required'] ?? false ? 1 : 0;

		// Conditionally (if enabled) required
		if ( $config['validate']['required'] ?? false ) {
			$attrs['data-input_required'] = '1';
		}

		if ( $placeholder = $config['placeholder'] ?? '' ) {
			$attrs['placeholder'] = $placeholder;
		}

		$attrs['disabled'] = $config['disabled'] ?? false ? 1 : 0;

		$value = $config['value'] ?? '';

		$setting_id = $config['setting_id'] ?? '';

		if ( $setting_id ) {
			if ( ! $value && isset( $settings[ $setting_id ] ) ) {
				$value = $settings[ $setting_id ];
			}
			if ( ( $setting_id == 'loginnowp' || $setting_id == 'loginpath' )
			     && ! cerber_is_permalink_enabled() ) {
				$attrs['disabled'] = 1;
			}
			if ( $setting_id == 'loginpath' ) {
				$value = urldecode( $value );
			}
		}

		$value = crb_attr_escape( $value );
		$value = crb_format_field_value( $value, $config );

		$settings_screen_id = $config['settings_screen_id'];

		$name_prefix = CRB_INPUT_NAME_PREFIX . $settings_screen_id;

		$input_name = $name_prefix . '[' . $setting_id . ']';
		$input_id = CRB_INPUT_ID_PREFIX . $setting_id;

		$data_atts = '';
		$ena_atts = array();

		if ( isset( $config['enabler'] ) ) {
			$ena_atts['input_enabler'] = CRB_INPUT_ID_PREFIX . $config['enabler'][0];
			if ( isset( $config['enabler'][1] ) ) {
				$ena_atts['input_enabler_value'] = $config['enabler'][1];
			}
			foreach ( $ena_atts as $att => $val ) {
				$data_atts .= ' data-' . $att . '="' . $val . '"';
			}
		}

		$type = $config['type'] ?? 'text';

		$class = 'crb-input-' . $type;
		$class .= ' ' . ( $config['input_class'] ?? '' );

		if ( ( $pre_render = $config['pre_render'] ?? false )
		     && is_callable( $pre_render ) ) {

			call_user_func_array( $pre_render, array( &$value, &$attrs, &$config ) );
		}

		// Remove empty attributes including binary ones
		$attrs = array_filter( $attrs );

		$input_atts = '';

		if ( $attrs ) {
			foreach ( $attrs as $at => $val ) {
				$input_atts .= $at . '="' . crb_attr_escape( $val ) . '"';
			}
		}

		$html = '';
		$html_secondary = '';

		if ( ( $renderer = $config['input_renderer'] ?? false )
		     && is_callable( $renderer ) ) {

			$html = call_user_func_array( $renderer, array( $label, $config['setting_ids'] ?? $setting_id, $value, $settings, $attrs, $name_prefix, $data_atts ) );
		}
		else {
			switch ( $type ) {

				case 'checkbox':
					$html = '<label class="crb-switch"><input class="screen-reader-text" type="checkbox" id="' . $input_id . '" name="' . $input_name . '" value="1" ' . checked( 1, $value, false ) . $input_atts . ' /><span class="crb-slider round"></span></label>';

					if ( $label ) {
						$html_secondary .= '<label for="' . $input_id . '">' . $label . '</label>';
					}

					if ( $data_atts ) {
						$html_secondary .= '<i ' . $data_atts . '></i>';
					}

					break;

				case 'textarea':
					$html = '<textarea class="large-text crb-monospace" id="' . $input_id . '" name="' . $input_name . '" ' . $input_atts . $data_atts . '>' . $value . '</textarea>';
					if ( $label ) {
						$html .= '<br/><label class="crb-below" for="' . $setting_id . '">' . $label . '</label>';
					}
					break;

				case 'select':
					$html = cerber_select( $input_name, $config['set'], $value, $class, $input_id, '', $placeholder, $ena_atts );
					if ( $label ) {
						$html .= '<br/><label class="crb-below">' . $label . '</label>';
					}
					break;

				case 'role_select':
					$label = $label ? '<p class="crb-label-above"><label for="' . $input_name . '">' . $label . '</label></p>' : '';
					$html = $label . '<div class="crb-select2-multi">' . cerber_role_select( $input_name . '[]', $value, '', true, '' ) . '<i ' . $data_atts . '></i></div>';
					break;

				case 'checkbox_set':
					$label = $label ? '<p class="crb-label-above">' . $label . '</p>' : '';
					$html = '<div id="' . $input_id . '"class="crb-checkbox-set" style="line-height: 2em;" ' . $data_atts . '>' . $label;
					foreach ( $config['set'] as $key => $item ) {
						$v = ( ! empty( $value[ $key ] ) ) ? $value[ $key ] : 0;
						$box_name = $input_name . '[' . $key . ']';
						$html .= '<input type="checkbox" id="' . $box_name . '" value="1" name="' . $box_name . '" ' . checked( 1, $v, false ) . $input_atts . '/><label for="' . $box_name . '">' . $item . '</label><br />';
					}
					$html .= '</div>';
					break;

				case 'reptime':
					$html = cerber_time_select( $config, $settings ) . '<i ' . $data_atts . '></i>';
					break;

				case 'day_time_picker':
					$html = cerber_time_picker( $config, $value ) . '<i ' . $data_atts . '></i>';
					break;

				case 'timepicker':
					$html = '<input class="crb-tpicker" type="text" size="7" id="' . $setting_id . '" name="' . $input_name . '" value="' . $value . '"' . $input_atts . '/>';
					$html .= ' <label for="' . $setting_id . '">' . $label . '</label>';
					break;

				case 'hidden':
					$html = '<input type="hidden" id="' . $setting_id . '" class="crb-hidden-field" name="' . $input_name . '" value="' . $value . '" />';
					break;

				case 'text':
				case 'digits':
				default:

					if ( in_array( $type, array( 'url', 'number', 'email' ) ) ) {
						$input_type = $type;
					}
					else {
						$input_type = 'text';
					}

					if ( $prefix = $config['prefix'] ?? '' ) {
						$before = '<div class="crb-prefixed-input"><span class="crb-input-prefix">' . $prefix . '</span>';
						$after = '</div>';
					}
					else {
						$before = '';
						$after = '';
					}

					$size = $config['size'] ?? '';

					if ( ! $size && $type == 'digits' ) {
						$size = '3';
					}

					$maxlength = $config['maxlength'] ?? $size;

					if ( $maxlength ) {
						$maxlength = ' maxlength="' . $maxlength . '" ';
					}

					if ( $size ) {
						$size = ' size="' . $size . '"';
					}
					else {
						$class .= ' crb-wide';
					}

					$pattern = $config['pattern'] ?? '';

					if ( ! $pattern && $type == 'digits' ) {
						$pattern = '\d+';
					}

					if ( $pattern ) {
						$input_atts .= ' pattern="' . $pattern . '"';
					}

					if ( isset( $config['attr'] ) ) {
						foreach ( $config['attr'] as $at_name => $at_value ) {
							$input_atts .= ' ' . $at_name . ' ="' . $at_value . '" ';
						}
					}
					else {
						if ( isset( $config['title'] ) ) {
							$input_atts .= ' title="' . $config['title'] . '"';
						}
					}

					$html = $before . '<input type="' . $input_type . '" id="' . $setting_id . '" name="' . $input_name . '" value="' . $value . '"' . ' class="' . $class . ' crb-first-field" ' . $size . $maxlength . $input_atts . $data_atts . ' />' . $after;

					if ( $label ) {
						if ( ! $size || crb_array_get( $config, 'label_pos' ) == 'below' ) {
							$html .= '<label class="crb-below" for="' . $setting_id . '">' . $label . '</label>';
						}
						else {
							$html_secondary = '<label for="' . $setting_id . '">' . $label . '</label>';
						}
					}

					break;
			}
		}


		if ( $loh = $config['act_relation'] ?? false ) {
			foreach ( $loh as $item ) {
				if ( $item[0]
				     && ! in_array( $value, $item[0] ) ) {
					continue;
				}

				$html .= '<span class="crb-insetting-link">[ <a href="' . crb_admin_link_for_html( 'activity', $item[1] ) . '" target="_blank">' . $item[2] . '</a> ]</span>';
			}
		}

		if ( ! empty( $config['field_switcher'] ) ) {
			$input_name = $name_prefix . '[' . $setting_id . '-enabled]';
			$value = $settings[ $setting_id . '-enabled' ] ?? 0;
			$checkbox = '<label class="crb-switch"><input class="screen-reader-text" type="checkbox" id="' . $input_id . '" name="' . $input_name . '" value="1" ' . checked( 1, $value, false ) . ' /><span class="crb-slider round"></span></label>';
			$html_secondary = '<label for="' . $input_id . '">' . $config['field_switcher'] . '</label>' . $html . $html_secondary;
			$html = $checkbox;
		}

		$setting_class = $html_secondary ? 'crb_setting_twin' : 'crb_setting_single';
		$html_secondary = $html_secondary ? '<div>' . $html_secondary . '</div>' : '';

		echo '<div id="' . CRB_WRAPPER_ID_PREFIX . 'global-' . $setting_id . '" class="crb-setting-input crb_setting_' . $type . ' ' . $setting_class . '">';
		echo '<div>' . $html . '</div>';
		echo $html_secondary . "</div>\n";

		if ( ( $under = $config['callback_under'] ?? false )
		     && is_callable( $under )
		     && $content = call_user_func( $under ) ) {

			echo '<div class="crb-settings-under">';
			echo $content;
			echo '</div>';
		}
	}
}

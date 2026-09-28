<?php

/**
 * Defines the authoritative, versioned UI Spec schema for the portable subset of UI Factory.
 *
 * The schema declares which CRB_UI_Element types may cross the serialization
 * boundary and carries the optional lower-level policy each element type opts
 * into. It is shared by CRB_UI_Spec_Serializer and CRB_UI_Spec_Tree_Builder so
 * serialization and hydration enforce the same contract.
 *
 * UI Factory remains the canonical runtime model. This schema defines only its
 * portable subset and does not describe all UI Factory or renderer capabilities.
 *
 * @version 1.0
 */
final class CRB_UI_Spec_Schema {
	public const SCHEMA_ID = 'ui-factory-dsl';
	public const VERSION = 1;

	/**
	 * Registers the supported UI Spec element types and their opt-in policy.
	 *
	 * Presence of an element type in this registry is what makes the element
	 * supported by UI Spec. Every lower-level member is explicit policy, and an
	 * absent member never means an empty allowlist:
	 *
	 * - missing props: prop names are unrestricted by the element registry
	 * - props set to an empty array: props are forbidden
	 * - missing required_props: the registry requires no prop
	 * - missing attributes: attribute names are unrestricted by the element registry
	 * - attributes set to an empty array: attributes are forbidden
	 * - missing children: children are allowed
	 * - children set to false: children are rejected
	 * - children set to true: children are allowed
	 *
	 * Registry lookups must use array_key_exists() so that an absent member and an
	 * explicit empty array keep their opposite meanings. A null-coalescing default
	 * collapses those two states and must not be used for props, required_props or
	 * attributes.
	 *
	 * The v1 entries keep an explicit attribute-name allowlist for every element
	 * type. The JSON UI Spec schema still owns baseline attribute-name policy until
	 * that responsibility moves to the renderer.
	 *
	 * @var array<string,array<string,mixed>>
	 */
	private const ELEMENTS = array(
		// Structural UI Factory element. Props and attributes are not part of the fragment concept.
		'fragment' => array(
			'props'      => array(),
			'attributes' => array(),
		),
		'text'     => array(
			'required_props' => array( 'content' ),
			'attributes'     => array(),
			'children'       => false,
		),
		// Generic block container. The id attribute is the hook the plugin stylesheet uses for the activation announcement.
		'div'      => array(
			'attributes' => array( 'id' ),
		),
		'h1'       => array(
			'attributes' => array(),
		),
		'h2'       => array(
			'attributes' => array( 'style' ),
		),
		'p'        => array(
			'attributes' => array( 'style' ),
		),
		'ul'       => array(
			'attributes' => array(),
		),
		'li'       => array(
			'attributes' => array(),
		),
		'span'     => array(
			'attributes' => array( 'class', 'style' ),
		),
		'table'    => array(
			'attributes' => array( 'style' ),
		),
		'tr'       => array(
			'attributes' => array(),
		),
		'td'       => array(
			'attributes' => array( 'style' ),
		),
		'link'     => array(
			'required_props' => array( 'href', 'label' ),
			'attributes'     => array( 'target' ),
			'children'       => false,
		),
	);

	/**
	 * Validates an element against the UI Spec schema.
	 *
	 * The element type must be registered. Prop names, required props and attribute
	 * names are validated only when the element registry declares the corresponding
	 * policy member.
	 *
	 * @param string $element_type Element type.
	 * @param array $props Element props.
	 * @param array $attributes Element attributes.
	 * @param string $path Diagnostic path.
	 *
	 * @return Revalt<bool>
	 */
	public static function validate_element(
		string $element_type,
		array $props,
		array $attributes,
		string $path
	): Revalt {

		// The supported element-type set is the mandatory compatibility boundary.

		if ( ! array_key_exists( $element_type, self::ELEMENTS ) ) {
			return self::error( 'ui_spec_unsupported_type', 'Unsupported UI Spec element type "' . $element_type . '" at ' . $path . '.', $path );
		}

		$element_definition = self::ELEMENTS[ $element_type ];

		// Prop names are restricted only by an explicitly declared allowlist.

		if ( array_key_exists( 'props', $element_definition ) ) {
			$unknown_props = array_diff( array_keys( $props ), $element_definition['props'] );

			if ( $unknown_props ) {
				return self::error( 'ui_spec_invalid_value', 'Unsupported prop "' . reset( $unknown_props ) . '" at ' . $path . '.props.', $path . '.props' );
			}
		}

		if ( array_key_exists( 'required_props', $element_definition ) ) {
			foreach ( $element_definition['required_props'] as $required_prop ) {
				if ( ! array_key_exists( $required_prop, $props ) ) {
					return self::error( 'ui_spec_invalid_value', 'Missing required prop "' . $required_prop . '" at ' . $path . '.props.', $path . '.props' );
				}
			}
		}

		// Attribute names keep an explicit v1 allowlist on every registered element type.

		if ( array_key_exists( 'attributes', $element_definition ) ) {
			$unknown_attributes = array_diff( array_keys( $attributes ), $element_definition['attributes'] );

			if ( $unknown_attributes ) {
				return self::error( 'ui_spec_invalid_value', 'Unsupported attribute "' . reset( $unknown_attributes ) . '" at ' . $path . '.attributes.', $path . '.attributes' );
			}
		}

		return self::validate_values( $element_type, $props, $path );
	}

	/**
	 * Checks whether the schema permits child elements for an element type.
	 *
	 * An absent children member means children are allowed. The element type is
	 * expected to be validated by validate_element() beforehand, so an unregistered
	 * type is reported as permitting children rather than as a separate failure.
	 *
	 * @param string $element_type Element type.
	 *
	 * @return bool True when child elements are permitted.
	 */
	public static function allows_children( string $element_type ): bool {
		return (bool) ( self::ELEMENTS[ $element_type ]['children'] ?? true );
	}

	/**
	 * Validates the element-semantic prop values the schema constrains.
	 *
	 * The schema keeps only the small portable contracts that describe element
	 * semantics. Presentation values such as style, class and link target are not
	 * constrained here, and URL safety belongs to the target renderer.
	 *
	 * @param string $element_type Element type.
	 * @param array $props Element props.
	 * @param string $path Diagnostic path.
	 *
	 * @return Revalt<bool>
	 */
	private static function validate_values( string $element_type, array $props, string $path ): Revalt {
		if ( $element_type === 'text' ) {
			return self::validate_string_prop( $props, 'content', $path );
		}

		if ( $element_type === 'link' ) {
			$href_result = self::validate_string_prop( $props, 'href', $path );

			if ( $href_result->has_errors() ) {
				return $href_result;
			}

			return self::validate_string_prop( $props, 'label', $path );
		}

		return new Revalt( true );
	}

	/**
	 * Checks that a prop holds a string when the prop is present.
	 *
	 * The presence check is local so the value contract does not depend on a
	 * required_props declaration having run first.
	 *
	 * @param array $props Element props.
	 * @param string $prop_name Prop name to check.
	 * @param string $path Diagnostic path of the element.
	 *
	 * @return Revalt<bool>
	 */
	private static function validate_string_prop( array $props, string $prop_name, string $path ): Revalt {
		if ( array_key_exists( $prop_name, $props ) && ! is_string( $props[ $prop_name ] ) ) {
			$prop_path = $path . '.props.' . $prop_name;

			return self::error( 'ui_spec_invalid_value', 'Prop "' . $prop_name . '" must be a string at ' . $prop_path . '.', $prop_path );
		}

		return new Revalt( true );
	}

	/**
	 * Creates a schema validation error with its structural path.
	 *
	 * @param string $error_code Error code.
	 * @param string $error_message Error message.
	 * @param string $path Structural path.
	 *
	 * @return Revalt<bool> Schema validation error result.
	 */
	private static function error(
		string $error_code,
		string $error_message,
		string $path
	): Revalt {
		return new Revalt( null, $error_code, $error_message, array( 'path' => $path ) );
	}
}

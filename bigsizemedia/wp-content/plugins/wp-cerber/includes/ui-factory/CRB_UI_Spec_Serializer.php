<?php

/**
 * Serializes the supported UI Factory subset into a versioned UI Spec array.
 *
 * The serializer is lossless for the v1 contract and returns an error when the
 * tree contains an element, prop, attribute, or child shape outside that contract.
 *
 * It also enforces the declarative data domain of the codec: a prop or attribute
 * value may hold only scalars, null, and arrays of those. This invariant belongs
 * to the codec rather than to the element registry, so it applies to every
 * element type regardless of how much prop policy that type declares.
 *
 * @version 1.0
 */
final class CRB_UI_Spec_Serializer {

	/**
	 * Maximum nesting depth accepted inside a prop or attribute value.
	 *
	 * PHP arrays may hold a reference to themselves, so the data domain walk needs
	 * a termination bound: without it a self-referential value exhausts the call
	 * stack instead of returning an error. The bound stays far below the default
	 * depth of the JSON encoder, so a value that passes the walk is never rejected
	 * by the encoder for its nesting alone.
	 */
	private const MAX_VALUE_DEPTH = 64;

	/**
	 * Serializes a supported UI Factory tree into a versioned UI Spec document.
	 *
	 * @param CRB_UI_Element $root Root UI Factory element.
	 *
	 * @return Revalt<array<string,mixed>> Versioned UI Spec document or a validation error.
	 */
	public static function serialize( CRB_UI_Element $root ): Revalt {
		$root_result = self::serialize_element( $root, 'root' );

		if ( $root_result->has_errors() ) {
			return $root_result;
		}

		return new Revalt(
			array(
				'schema'  => CRB_UI_Spec_Schema::SCHEMA_ID,
				'version' => CRB_UI_Spec_Schema::VERSION,
				'root'    => $root_result->get_results(),
			)
		);
	}

	/**
	 * @return Revalt<array<string,mixed>>
	 */
	private static function serialize_element(
		CRB_UI_Element $element,
		string $path
	): Revalt {
		$validation = CRB_UI_Spec_Schema::validate_element( $element->type, $element->props, $element->attributes, $path );

		if ( $validation->has_errors() ) {
			return $validation;
		}

		// Values crossing the boundary must round-trip as declarative UI Spec data.

		$props_domain = self::validate_data_domain( $element->props, $path . '.props' );

		if ( $props_domain->has_errors() ) {
			return $props_domain;
		}

		$attributes_domain = self::validate_data_domain( $element->attributes, $path . '.attributes' );

		if ( $attributes_domain->has_errors() ) {
			return $attributes_domain;
		}

		if ( ! CRB_UI_Spec_Schema::allows_children( $element->type ) && $element->children ) {
			return new Revalt( null, 'ui_spec_invalid_value', 'Element type "' . $element->type . '" cannot have children at ' . $path . '.', array( 'path' => $path . '.children' ) );
		}

		$serialized_children = array();
		foreach ( $element->children as $child_index => $child ) {
			$child_path = $path . '.children[' . $child_index . ']';

			if ( ! ( $child instanceof CRB_UI_Element ) ) {
				return new Revalt( null, 'ui_spec_invalid_value', 'UI Spec children must be CRB_UI_Element instances at ' . $child_path . '.', array( 'path' => $child_path ) );
			}

			$child_result = self::serialize_element( $child, $child_path );
			if ( $child_result->has_errors() ) {
				return $child_result;
			}

			$serialized_children[] = $child_result->get_results();
		}

		$node = array( 'type' => $element->type );

		if ( $element->props ) {
			$node['props'] = $element->props;
		}

		if ( $element->attributes ) {
			$node['attributes'] = $element->attributes;
		}

		if ( $serialized_children ) {
			$node['children'] = $serialized_children;
		}

		return new Revalt( $node );
	}

	/**
	 * Verifies that a value belongs to the declarative UI Spec data domain.
	 *
	 * Scalars and null are accepted. An array is accepted when every value it holds
	 * is accepted, at any depth up to MAX_VALUE_DEPTH. Objects, resources and
	 * closures are rejected because they cannot round-trip through the UI Spec
	 * representation: encoding would silently change them into a different
	 * structure. A value nested beyond the bound is rejected as well, which is how
	 * a self-referential array is reported instead of exhausting the call stack.
	 *
	 * @param mixed $member_value Prop or attribute value, or a nested value of one.
	 * @param string $path Diagnostic path of the value.
	 * @param int $depth Current nesting depth of the value, counted from the member itself.
	 *
	 * @return Revalt<bool>
	 */
	private static function validate_data_domain( $member_value, string $path, int $depth = 0 ): Revalt {
		if ( is_array( $member_value ) ) {
			if ( $depth >= self::MAX_VALUE_DEPTH ) {
				return new Revalt(
					null,
					'ui_spec_invalid_value',
					'UI Spec values must not nest deeper than ' . self::MAX_VALUE_DEPTH . ' levels at ' . $path . '. A self-referential array reaches this bound.',
					array( 'path' => $path )
				);
			}

			foreach ( $member_value as $nested_key => $nested_value ) {
				$nested_result = self::validate_data_domain( $nested_value, $path . '.' . $nested_key, $depth + 1 );

				if ( $nested_result->has_errors() ) {
					return $nested_result;
				}
			}

			return new Revalt( true );
		}

		if ( null === $member_value || is_scalar( $member_value ) ) {
			return new Revalt( true );
		}

		return new Revalt(
			null,
			'ui_spec_invalid_value',
			'UI Spec values must be scalars, null, or arrays of those, got ' . gettype( $member_value ) . ' at ' . $path . '.',
			array( 'path' => $path )
		);
	}
}

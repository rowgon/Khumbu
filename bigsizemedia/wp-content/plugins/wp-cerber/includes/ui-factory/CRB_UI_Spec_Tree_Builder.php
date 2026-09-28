<?php

/**
 * Validates a versioned UI Spec array and hydrates it into a UI Factory tree.
 *
 * The builder accepts only the explicit v1 schema subset and never returns a
 * partially hydrated tree when validation fails.
 *
 * @version 1.0
 */
final class CRB_UI_Spec_Tree_Builder {

	/**
	 * Hydrates a validated v1 UI Spec document into a UI Factory element tree.
	 *
	 * @param array<string,mixed> $document Decoded UI Spec document.
	 *
	 * @return Revalt<CRB_UI_Element> Root element or a validation error.
	 */
	public static function build( array $document ): Revalt {
		$allowed_document_keys = array( 'schema', 'version', 'root' );
		$unknown_keys = array_diff( array_keys( $document ), $allowed_document_keys );

		if ( $unknown_keys ) {
			return self::error( 'ui_spec_invalid_document', 'Unsupported UI Spec document key "' . reset( $unknown_keys ) . '".', 'document' );
		}

		if ( ( $document['schema'] ?? null ) !== CRB_UI_Spec_Schema::SCHEMA_ID ) {
			return self::error( 'ui_spec_invalid_document', 'Unsupported UI Spec schema.', 'schema' );
		}

		if ( ! array_key_exists( 'version', $document ) || ! is_int( $document['version'] ) ) {
			return self::error( 'ui_spec_invalid_document', 'UI Spec document requires an integer version.', 'version' );
		}

		if ( $document['version'] !== CRB_UI_Spec_Schema::VERSION ) {
			return self::error( 'invalid_spec_version', 'Unsupported UI Spec version ' . $document['version'] . '.', 'version' );
		}

		if ( ! isset( $document['root'] ) || ! is_array( $document['root'] ) ) {
			return self::error( 'ui_spec_invalid_document', 'UI Spec document requires a root node.', 'root' );
		}

		return self::build_element( $document['root'], 'root' );
	}

	/**
	 * @param array<mixed> $node
	 *
	 * @return Revalt<CRB_UI_Element>
	 */
	private static function build_element(
		array $node,
		string $path
	): Revalt {
		$allowed_node_keys = array( 'type', 'props', 'attributes', 'children' );
		$unknown_keys = array_diff( array_keys( $node ), $allowed_node_keys );

		if ( $unknown_keys ) {
			return self::error( 'ui_spec_invalid_value', 'Unsupported node key "' . reset( $unknown_keys ) . '" at ' . $path . '.', $path );
		}

		$element_type = $node['type'] ?? null;
		if ( ! is_string( $element_type ) || $element_type === '' ) {
			return self::error( 'ui_spec_invalid_value', 'UI Spec node requires a non-empty string type at ' . $path . '.', $path . '.type' );
		}

		$props = $node['props'] ?? array();
		$attributes = $node['attributes'] ?? array();
		$children = $node['children'] ?? array();

		if ( ! is_array( $props ) || ! is_array( $attributes ) || ! is_array( $children ) || ! self::is_list( $children ) ) {
			return self::error( 'ui_spec_invalid_value', 'Invalid UI Spec node shape at ' . $path . '.', $path );
		}

		$validation = CRB_UI_Spec_Schema::validate_element( $element_type, $props, $attributes, $path );
		if ( $validation->has_errors() ) {
			return $validation;
		}

		if ( ! CRB_UI_Spec_Schema::allows_children( $element_type ) && $children ) {
			return self::error( 'ui_spec_invalid_value', 'Element type "' . $element_type . '" cannot have children at ' . $path . '.', $path . '.children' );
		}

		$child_elements = array();
		foreach ( $children as $child_index => $child_node ) {
			$child_path = $path . '.children[' . $child_index . ']';

			if ( ! is_array( $child_node ) ) {
				return self::error( 'ui_spec_invalid_value', 'UI Spec child must be an object-like node at ' . $child_path . '.', $child_path );
			}

			$child_result = self::build_element( $child_node, $child_path );
			if ( $child_result->has_errors() ) {
				return $child_result;
			}

			$child_elements[] = $child_result->get_results();
		}

		return new Revalt( new CRB_UI_Element( $element_type, $props, $attributes, $child_elements ) );
	}

	/**
	 * @param array $nodes
	 *
	 * @return bool
	 */
	private static function is_list( array $nodes ): bool {
		if ( $nodes === array() ) {
			return true;
		}

		return array_keys( $nodes ) === range( 0, count( $nodes ) - 1 );
	}

	/**
	 * @param string $error_code
	 * @param string $error_message
	 * @param string $path
	 *
	 * @return Revalt
	 */
	private static function error(
		string $error_code,
		string $error_message,
		string $path
	): Revalt {
		return new Revalt( null, $error_code, $error_message, array( 'path' => $path ) );
	}
}

<?php
/*
	Copyright (C) 2025-26 CERBER TECH INC., https://wpcerber.com

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

== UI Factory: Developer Quick Start ==

UI Factory provides a declarative way to build administrative interfaces.

Instead of concatenating or echoing HTML, PHP code creates a tree of
CRB_UI_Element objects. Each element describes what should be displayed. The
active renderer converts the completed tree into safe HTML.

The normal workflow is:

1. Create UI elements.
2. Combine them into an element tree.
3. Render the root element.

Basic example:

$card = crb_ui_element( 'div', [ 'class' => 'card' ], [
	crb_ui_element( 'h3', [], 'Title' ),
	crb_ui_element( 'p', [], 'Some description text.' ),
	crb_ui_link( $details_url, 'Learn more' ),
] );

echo crb_ui_renderer()->render_element( $card );


1. Element Structure

Every CRB_UI_Element has an element type and may contain properties, HTML
attributes, and child elements:

- type: Identifies the element or component, such as 'div', 'link', 'button',
  or 'rich_table'. The type is required.
- props: Defines behavior or data specific to the element type, such as a
  button label or a table specification.
- attributes: Defines HTML attributes rendered on the resulting tag, such as
  class, id, and data-* attributes.
- children: Contains nested elements that form the UI tree.

UI elements remain structured objects until the tree is rendered.


2. Creating Elements

Use the helper functions defined in this file instead of instantiating
CRB_UI_Element directly:

- crb_ui_element( $type, $attributes, $content, $props )
  Creates a general-purpose element. Strings and numbers passed as content
  become escaped text elements.

- crb_ui_link( $href, $label, ... )
  Creates a semantic link element.

- crb_ui_formatted_text( $template, $arguments )
  Creates formatted text with numbered placeholders. Placeholder values may
  be scalars or UI elements.

- crb_ui_fragment( $children )
  Creates a tagless group of mixed sibling elements.

- crb_ui_element_set( $child_type, $children )
  Creates a tagless collection whose children share one declared element type.

Domain-specific helpers for message boxes, diagnostic sections, and plain
tables are defined in includes/cerber-ui-blocks.php.


3. Rendering

Render the completed root element through the active renderer:

echo crb_ui_renderer()->render_element( $element );

crb_ui_renderer() currently returns CRB_UI_Html_Renderer. The HTML renderer:

- escapes text content and HTML attributes
- sanitizes URL attributes
- renders nested element trees recursively
- throws InvalidArgumentException for unknown element types

Call sites must not concatenate dynamic values into HTML. Escaping and final
output generation belong to the renderer.


4. Tables

UI Factory provides three declarative table element types:

- simple_table
  Accepts 'headers' and a two-dimensional 'data' array. Use it for basic
  tabular output.

- standard_table
  Accepts the same input as simple_table and converts it to the standard rich
  table representation.

- rich_table
  Accepts 'columns' and 'rows' specifications. It supports per-cell content,
  per-cell attributes, UI elements inside cells, and optional header and footer
  rendering.

Example:

$table = crb_ui_element(
	'rich_table',
	[ 'class' => 'table' ],
	null,
	[
		'columns' => [
			'name' => [
				'label' => 'User',
			],
			'action' => [
				'label' => 'Action',
			],
		],
		'rows' => [
			[
				'cells' => [
					'name'   => 'Anna',
					'action' => crb_ui_element(
						'button',
						[],
						null,
						[ 'label' => 'Edit' ]
					),
				],
			],
		],
		'render_header' => true,
		'render_footer' => false,
	]
);

echo crb_ui_renderer()->render_element( $table );


5. Fluent Builders

Builders provide convenient APIs for assembling larger element trees:

- CRB_UI_Content_Builder
  Collects text, trusted HTML, individual elements, and element collections.
  Use to_element() to wrap the collected content in a container element. The
  former CRB_UI_Fragment_Builder name remains available as a backward
  compatibility alias.

- CRB_UI_Form_Builder
  Builds form element trees with fields arranged in columns, hidden inputs,
  and a submit button.


6. Trusted Raw HTML

crb_ui_raw_html( $html ) bypasses normal escaping.

Use it only for legacy markup that is already trusted and correctly escaped.
Do not pass user-controlled or unescaped dynamically assembled HTML to this
helper. New UI code should use structured UI elements instead.


Core Rules

- Represent UI output as a CRB_UI_Element tree.
- Prefer factory helpers over direct CRB_UI_Element instantiation.
- Keep dynamic content as text, attributes, properties, or child elements.
- Render the completed tree through crb_ui_renderer().
- Do not concatenate dynamic values into HTML.
- Treat crb_ui_raw_html() as a legacy escape hatch, not a normal rendering
  mechanism.

The element tree is the canonical backend representation of the UI. Keeping
call sites independent from HTML generation preserves consistent escaping and
allows other renderers or transformation stages to process the same tree in
the future.

 */

spl_autoload_register( function ( $class_name ) {
	static $classes = [
		'CRB_UI_Html_Renderer'     => '/ui-factory-html-renderer.php',
		'CRB_UI_Element'           => '/ui-factory-html-renderer.php',
		'CRB_UI_Content_Builder'   => '/ui-factory-builder.php',
		'CRB_UI_Fragment_Builder'  => '/ui-factory-builder.php',
		'CRB_UI_Form_Builder'      => '/ui-factory-form-builder.php',
		'CRB_UI_Spec_Schema'       => '/CRB_UI_Spec_Schema.php',
		'CRB_UI_Spec_Serializer'   => '/CRB_UI_Spec_Serializer.php',
		'CRB_UI_Spec_Tree_Builder' => '/CRB_UI_Spec_Tree_Builder.php',
	];

	if ( $file = $classes[ $class_name ] ?? '' ) {
		require_once( __DIR__ . $file );
	}
} );

/**
 * UI Factory - Main Accessor
 *
 * Returns the active UI renderer instance for the application.
 *
 * This function acts as a simple Service Locator/Factory, providing a single
 * point of control for switching the entire UI rendering engine.
 *
 * @return CRB_UI_Renderer The active renderer instance.
 *
 * @since 9.6.9.5
 */
function crb_ui_renderer(): CRB_UI_Renderer {
	static $instance = null;

	if ( $instance === null ) {
		$instance = new CRB_UI_Html_Renderer();
	}

	return $instance;
}

/**
 * The core low-level factory for creating UI Element objects.
 *
 * This function serves as the primary, declarative way to build UI trees.
 * Its main responsibility is to transform a simple, developer-friendly format into a valid CRB_UI_Element DTO.
 *
 * Strings or numeric content are automatically converted into 'text' elements,
 * allowing for clean and readable nested structures.
 *
 * --- USAGE EXAMPLES ---
 *
 * // 1. Simple paragraph with text
 * $p = crb_ui_element('p', ['class' => 'lead'], 'Hello, World!');
 *
 * // 2. A div with nested elements
 * $container = crb_ui_element('div', ['class' => 'container'], [
 * crb_ui_element('h1', [], 'Title'),
 * crb_ui_element('p', [], 'Some description text.'),
 * ]);
 *
 * // 3. An empty element
 * $divider = crb_ui_element('hr');
 *
 *
 * @param string $type The element type (e.g., 'div', 'p', 'text').
 * @param array $attributes Optional. An associative array of HTML attributes (e.g., ['class' => '...', 'href' => '...']).
 * @param mixed $content Optional. The content or children of the element. Can be:
 * - a string (will be converted to a 'text' element).
 * - a CRB_UI_Element object.
 * - an array of strings or CRB_UI_Element objects.
 * - null for an empty element.
 *
 * @param array $props Optional. Component-specific properties (e.g. label, icon name, raw HTML).
 *
 * @return CRB_UI_Element A new instance of the UI element.
 *
 * @since 9.6.9.8
 */
function crb_ui_element( string $type, array $attributes = [], $content = null, array $props = [] ): CRB_UI_Element {
	$children = [];

	$content_items = is_array( $content ) ? $content : [ $content ];

	foreach ( $content_items as $item ) {
		if ( $item instanceof CRB_UI_Element ) {
			$children[] = $item;
		}
		elseif ( is_string( $item ) || is_numeric( $item ) ) {
			if ( (string) $item !== '' ) {
				$children[] = new CRB_UI_Element( 'text', [ 'content' => (string) $item ] );
			}
		}
	}

	return new CRB_UI_Element( $type, $props, $attributes, $children );
}

/**
 * Creates a semantic UI link element.
 *
 * This helper constructs a CRB_UI_Element of type 'link' with the specified
 * href and label. Optional HTML attributes and additional props can be provided.
 *
 * The href and label are stored as component props, not as generic HTML
 * attributes. This keeps the descriptor stable for both the current HTML
 * renderer and alternative future UI renderer.
 *
 * @param string $href Unescaped absolute URL or '#', e.g. generated by `cerber_admin_link()` or `_crb_admin_link_add()`
 * @param string $label Human-readable link label.
 * @param array $attributes Optional HTML attributes, such as class, id, title, target, rel, or data-*.
 * @param array $props Optional additional link props. Explicit href and label parameters always take precedence.
 *
 * @return CRB_UI_Element A new instance of the UI element.
 *
 * @since 9.7.3.1
 */
function crb_ui_link( string $href, string $label, array $attributes = [], array $props = [] ): CRB_UI_Element {
	$href = trim( $href );

	// The link URL belongs to component props, not to generic attributes.
	if ( array_key_exists( 'href', $attributes ) ) {
		unset( $attributes['href'] );
	}

	$link_props = array_merge(
		$props,
		[
			'href'  => $href,
			'label' => $label,
		]
	);

	return new CRB_UI_Element( 'link', $link_props, $attributes );
}

/**
 * Creates a formatted inline text element with typed placeholders.
 *
 * The template must be plain text, not HTML. Numbered placeholders in the form
 * %N$s may be replaced with scalar values or CRB_UI_Element instances. Scalar
 * values are rendered as escaped text by the renderer. UI elements are rendered
 * through the active renderer, so each dynamic value is escaped in its proper
 * output context. This keeps a translated sentence intact as a single string
 * while allowing inline elements such as links.
 *
 * Arguments are a zero-based positional list. Placeholder %N$s is replaced with
 * the Nth argument (index N - 1); array keys are ignored and values are consumed
 * in order.
 *
 * @param string $template Plain-text template with numbered placeholders.
 * @param array<int, mixed> $arguments Positional placeholder arguments, zero-based.
 *
 * @return CRB_UI_Element A new formatted text UI element.
 *
 * @since 9.8.2
 */
function crb_ui_formatted_text( string $template, array $arguments = [] ): CRB_UI_Element {
	return new CRB_UI_Element(
		'formatted_text',
		[
			'template' => $template,
			'args'     => $arguments,
		]
	);
}

/**
 * Temporary helper for legacy raw HTML content.
 *
 * WARNING: Do not use for untrusted sources, raw content from the database,
 * user input, or any content that has not been properly sanitized and escaped.
 *
 * @param string $html Raw HTML string.
 *
 * @return CRB_UI_Element A new instance of the UI element.
 *
 * @since 9.7.3.1
 */
function crb_ui_raw_html( string $html ): CRB_UI_Element {
	return new CRB_UI_Element( 'raw_html', [ 'content' => $html ] );
}

/**
 * Creates a 'fragment' element, a tagless group of sibling UI elements.
 *
 * A fragment has no HTML tag, no attributes, and no props of its own. When
 * rendered, it outputs only its children, in the given order, with no wrapper
 * markup, separators, or whitespace. Use it to return several sibling nodes
 * as a single CRB_UI_Element without introducing an extra wrapping tag.
 *
 * Children may have different element types, so a fragment fits mixed inline
 * content such as text, emphasis, and links. String and numeric children are
 * converted into 'text' elements and escaped by the renderer, exactly as
 * crb_ui_element() does; empty strings and unsupported values are dropped.
 * The caller remains responsible for the semantic and HTML validity of the
 * child sequence in its final location.
 *
 * When every child must share the same element type, use crb_ui_element_set().
 *
 * --- USAGE EXAMPLE ---
 *
 * // Renders as: <b>Note:</b> <a href="...">Documentation</a>
 * crb_ui_fragment( [
 *     crb_ui_element( 'b', [], 'Note:' ),
 *     ' ',
 *     crb_ui_link( $documentation_url, 'Documentation' ),
 * ] );
 *
 * @param array<int, CRB_UI_Element|string|int|float> $children Child elements in output order.
 *
 * @return CRB_UI_Element A new fragment element.
 *
 * @since 9.6.9.8
 */
function crb_ui_fragment( array $children ): CRB_UI_Element {
	return crb_ui_element( 'fragment', [], $children );
}

/**
 * Creates an 'element_set' element, a tagless collection of same-type UI elements.
 *
 * An element set holds an ordered collection of sibling elements that all
 * share one declared CRB_UI_Element type, such as a set of 'p', 'li', or 'tr'
 * nodes. When rendered, it outputs only its children, in the given order,
 * with no wrapper markup, separators, or whitespace.
 *
 * The declared child type is stored in the 'child_type' prop. It marks the
 * set as a homogeneous collection for future renderers and compilers and has
 * no effect on the current HTML output.
 *
 * An empty children array is valid: the collection type stays declared even
 * when there is nothing to render yet. An element set does not check whether
 * its children are allowed inside a particular HTML parent; it can guarantee
 * a set of 'li' elements, but not that the set is placed inside 'ul' or 'ol'.
 * Placement remains the caller's responsibility.
 *
 * To group children of different element types, use crb_ui_fragment().
 *
 * --- USAGE EXAMPLE ---
 *
 * // Renders as: <p>First message.</p><p>Second message.</p>
 * crb_ui_element_set( 'p', [
 *     crb_ui_element( 'p', [], 'First message.' ),
 *     crb_ui_element( 'p', [], 'Second message.' ),
 * ] );
 *
 * @param string $child_type Element type required for every child, e.g. 'p', 'li', 'tr'.
 * Must be lowercase letters, digits, and underscores, starting with a letter.
 * @param array<int, CRB_UI_Element> $children Child elements in output order. Every child
 * type must equal $child_type. May be empty.
 *
 * @return CRB_UI_Element A new element-set element.
 *
 * @throws InvalidArgumentException When $child_type is empty or malformed, when a child is
 * not a CRB_UI_Element instance, or when a child's type differs from $child_type.
 *
 * @since 9.8.4.1
 */
function crb_ui_element_set( string $child_type, array $children ): CRB_UI_Element {

	// The declared child type is a contract value: it is validated, never transformed.

	if ( trim( $child_type ) === '' ) {
		throw new InvalidArgumentException( 'UI element set requires a non-empty child type.' );
	}

	// Enforce the canonical element type format: lowercase snake_case starting with a letter.

	if ( ! preg_match( '/^[a-z][a-z0-9_]*$/', $child_type ) ) {
		throw new InvalidArgumentException( 'UI element set requires a child type of lowercase letters, digits, and underscores, got "' . $child_type . '".' );
	}

	// A heterogeneous element set is a programmer error and must fail immediately.

	foreach ( $children as $child_element ) {
		if ( ! ( $child_element instanceof CRB_UI_Element ) ) {
			throw new InvalidArgumentException( 'UI element set accepts only CRB_UI_Element children.' );
		}

		if ( $child_element->type !== $child_type ) {
			throw new InvalidArgumentException( 'UI element set expects children of type "' . $child_type . '", got "' . $child_element->type . '".' );
		}
	}

	return new CRB_UI_Element(
		'element_set',
		[ 'child_type' => $child_type ],
		[],
		$children
	);
}

/**
 * Serializes a UI Factory tree into a JSON encoded UI Spec document.
 *
 * The JSON boundary belongs to the caller of the codec rather than to the codec
 * itself, so this helper keeps the two boundary steps, serialization and
 * encoding, behind one contract shared by every producer that stores a UI tree.
 *
 * Native encoding reports a failure instead of sanitizing the payload and
 * retrying, so a document that cannot be encoded, in practice because a string
 * holds invalid UTF-8, fails here rather than being stored in an altered form.
 *
 * @param CRB_UI_Element $ui_tree Root element of the tree to store.
 *
 * @return Revalt<string> JSON encoded UI Spec document, or a serialization or encoding error.
 *
 * @since 9.9.4
 */
function crb_ui_spec_encode( CRB_UI_Element $ui_tree ): Revalt {

	$serialization_result = CRB_UI_Spec_Serializer::serialize( $ui_tree );

	if ( $serialization_result->has_errors() ) {
		return $serialization_result;
	}

	$ui_spec_json = json_encode( $serialization_result->get_results(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );

	if ( false === $ui_spec_json ) {
		return new Revalt( null, 'ui_spec_encoding_failed', 'Unable to encode the UI Spec document, ' . json_last_error_msg() );
	}

	return new Revalt( $ui_spec_json );
}

/**
 * Decodes a stored UI Spec document and hydrates it into a UI Factory tree.
 *
 * The counterpart of crb_ui_spec_encode(). Structural validation of the decoded
 * document, including its schema identifier and version, belongs to
 * CRB_UI_Spec_Tree_Builder and is not repeated here. A hydration error is
 * returned unchanged, so the caller reads the failing structural path the builder
 * reports.
 *
 * @param string $ui_spec_json Stored JSON encoded UI Spec document.
 *
 * @return Revalt<CRB_UI_Element> Root element of the hydrated tree, or a decoding or validation error.
 *
 * @since 9.9.4
 */
function crb_ui_spec_decode( string $ui_spec_json ): Revalt {

	$ui_spec = json_decode( $ui_spec_json, true );

	if ( ! is_array( $ui_spec ) ) {

		// A decoding failure and a decoded value of the wrong shape share this recovery path, so they differ only in the diagnostic.

		$decoding_status = ( json_last_error() === JSON_ERROR_NONE )
			? 'the stored value is not a UI Spec document'
			: 'JSON decoding failed, ' . json_last_error_msg();

		return new Revalt( null, 'ui_spec_decoding_failed', 'Unable to read the stored UI Spec document, ' . $decoding_status );
	}

	return CRB_UI_Spec_Tree_Builder::build( $ui_spec );
}

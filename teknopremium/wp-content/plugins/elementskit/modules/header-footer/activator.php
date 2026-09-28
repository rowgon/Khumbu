<?php
namespace ElementsKit\Modules\Header_Footer;

defined( 'ABSPATH' ) || exit;

class Activator {
	public static $instance = null;

	protected $templates;
	public $header_template;
	public $footer_template;

	protected $current_theme;
	protected $current_template;
	protected $post_type = 'elementskit_template';

	public function __construct() {
		add_action( 'wp', array( $this, 'hooks' ) );
	}

	public function hooks() {
		$this->current_template = basename(get_page_template_slug());

		if ($this->current_template === 'elementor_canvas') {
			return;
		}

		$this->current_theme = get_template();

		switch ($this->current_theme) {
			case 'astra':
				new Theme_Hooks\Astra(self::template_ids());
				break;

			case 'neve':
				new Theme_Hooks\Neve(self::template_ids());
				break;

			case 'generatepress':
			case 'generatepress-child':
				new Theme_Hooks\Generatepress(self::template_ids());
				break;

			case 'oceanwp':
			case 'oceanwp-child':
				new Theme_Hooks\Oceanwp(self::template_ids());
				break;

			case 'bb-theme':
			case 'bb-theme-child':
				new Theme_Hooks\Bbtheme(self::template_ids());
				break;

			case 'genesis':
			case 'genesis-child':
				new Theme_Hooks\Genesis(self::template_ids());
				break;

			case 'twentynineteen':
				new Theme_Hooks\TwentyNineteen(self::template_ids());
				break;

			case 'my-listing':
			case 'my-listing-child':
				new Theme_Hooks\MyListing(self::template_ids());
				break;

			case 'houzez':
				new Theme_Hooks\Houzez(self::template_ids());
				break;

			default:
				new Theme_Hooks\Theme_Support(self::template_ids());
		}
	}

	public static function template_ids() {
		$cached = wp_cache_get('elementskit_template_ids');
		if ($cached !== false) {
			return $cached;
		}

		$instance = self::instance();
		$instance->run_conditions();

		$ids = [
			$instance->header_template,
			$instance->footer_template,
		];

		if ($instance->header_template) {
			\ElementsKit\Utils::render_elementor_content_css($instance->header_template);
		}
		if ($instance->footer_template) {
			\ElementsKit\Utils::render_elementor_content_css($instance->footer_template);
		}

		wp_cache_set('elementskit_template_ids', $ids);
		return $ids;
	}

	/**
	 * Main condition handler
	 */
	protected function run_conditions() {
		$this->templates = get_posts([
			'posts_per_page' => -1,
			'orderby'        => 'id',
			'order'          => 'DESC',
			'post_status'    => 'publish',
			'post_type'      => $this->post_type,
			'meta_query'     => [
				[
					'key'     => 'elementskit_template_activation',
					'value'   => 'yes',
					'compare' => '=',
				],
			],
		]);

		if (!is_admin()) {
			$this->match([
				['key' => 'condition_a', 'value' => 'entire_site']
			]);
		}

		/** -----------------------
		 * ENTIRE SHOP CONDITION (WooCommerce)
		 * ---------------------- */
		if (class_exists('WooCommerce') && !is_admin()) {
			if (is_shop() || is_product_category() || is_product_tag() || is_product() ||
				is_cart() || is_checkout() || is_account_page() || is_woocommerce() ||
				(is_search() && isset($_GET['post_type']) && $_GET['post_type'] === 'product')) {
				$this->match([
					['key' => 'condition_a', 'value' => 'entire_shop']
				]);
			}
		}

		/** -----------------------
		 * ARCHIVE CONDITIONS
		 * ---------------------- */

		if (is_archive() || is_search() || is_post_type_archive() || is_home()) {
			// All Archives Legacy
			$this->match([
				['key' => 'condition_a', 'value' => 'archive'],
				['key' => 'condition_archive', 'value' => '']
			]);

			// All Archives
			$this->match([
				['key' => 'condition_a', 'value' => 'archive'],
				['key' => 'condition_archive', 'value' => 'all_archive']
			]);

			// Date Archives
			if (is_date()) {
				$this->match([
					['key' => 'condition_a', 'value' => 'archive'],
					['key' => 'condition_archive', 'value' => 'date_archive']
				]);
			}

			// Author Archives
			if (is_author()) {
				$this->match([
					['key' => 'condition_a', 'value' => 'archive'],
					['key' => 'condition_archive', 'value' => 'author_archive']
				]);
			}

			// Blog Post Archives (fixed)
			if ((is_home() && !is_front_page()) || is_category() || is_tag()) {
				$this->match([
					['key' => 'condition_a', 'value' => 'archive'],
					['key' => 'condition_archive', 'value' => 'post_archive'],
				]);
			}

			// Category
			if (is_category()) {
				$this->match([
					['key' => 'condition_a', 'value' => 'archive'],
					['key' => 'condition_archive', 'value' => 'category'],
					['key' => 'condition_archive_id', 'value' => get_queried_object_id()],
				]);
			}

			// Tag
			if (is_tag()) {
				$this->match([
					['key' => 'condition_a', 'value' => 'archive'],
					['key' => 'condition_archive', 'value' => 'post_tag'],
					['key' => 'condition_archive_id', 'value' => get_queried_object_id()],
				]);
			}

			/** WooCommerce Conditions */
			if (class_exists('WooCommerce')) {

				// Product Archive (fixed & expanded)
				if (is_shop() || is_post_type_archive('product') || is_product_category() || is_product_tag()) {
					$this->match([
						['key' => 'condition_a', 'value' => 'archive'],
						['key' => 'condition_archive', 'value' => 'product_archive'],
					]);
				}

				// Shop page
				if (is_shop()) {
					$this->match([
						['key' => 'condition_a', 'value' => 'archive'],
						['key' => 'condition_archive', 'value' => 'shop_page']
					]);
				}

				// Product category
				if (is_product_category()) {
					$this->match([
						['key' => 'condition_a', 'value' => 'archive'],
						['key' => 'condition_archive', 'value' => 'product_cat'],
						['key' => 'condition_archive_id', 'value' => get_queried_object_id()],
					]);
				}

				// Product tag
				if (is_product_tag()) {
					$this->match([
						['key' => 'condition_a', 'value' => 'archive'],
						['key' => 'condition_archive', 'value' => 'product_tag'],
						['key' => 'condition_archive_id', 'value' => get_queried_object_id()],
					]);
				}

				// Product brand
				if (is_tax('product_brand')) {
					$this->match([
						['key' => 'condition_a', 'value' => 'archive'],
						['key' => 'condition_archive', 'value' => 'product_brand'],
						['key' => 'condition_archive_id', 'value' => get_queried_object_id()],
					]);
				}
			}
		}

		/** SEARCH CONDITIONS */
		if (is_search()) {
			$this->match([
				['key' => 'condition_a', 'value' => 'archive'],
				['key' => 'condition_archive', 'value' => 'search_result']
			]);

			if (class_exists('WooCommerce') && isset($_GET['post_type']) && $_GET['post_type'] === 'product') {
				$this->match([
					['key' => 'condition_a', 'value' => 'archive'],
					['key' => 'condition_archive', 'value' => 'product_search']
				]);
			}
		}

		/** -----------------------
		 *  SINGULAR CONDITIONS
		 * ---------------------- */

		if (is_page() || is_single() || is_404()) {
			$this->match([
				['key' => 'condition_a', 'value' => 'singular'],
				['key' => 'condition_singular', 'value' => 'all']
			]);
		}

		if (is_page()) {
			$this->match([
				['key' => 'condition_a', 'value' => 'singular'],
				['key' => 'condition_singular', 'value' => 'all_pages']
			]);
		}

		if (is_single()) {
			$this->match([
				['key' => 'condition_a', 'value' => 'singular'],
				['key' => 'condition_singular', 'value' => 'all_posts']
			]);
		}

		if (is_404()) {
			$this->match([
				['key' => 'condition_a', 'value' => 'singular'],
				['key' => 'condition_singular', 'value' => '404page']
			]);
		}

		// Selective Pages
		if (is_page() || is_single()) {
			$this->match([
				['key' => 'condition_a', 'value' => 'singular'],
				['key' => 'condition_singular', 'value' => 'selective'],
				['key' => 'condition_singular_id', 'value' => get_the_ID()]
			]);
		}

		// Homepage
		if (is_home() || is_front_page()) {
			$this->match([
				['key' => 'condition_a', 'value' => 'singular'],
				['key' => 'condition_singular', 'value' => 'front_page']
			]);
		}
	}

	/**
	 * Core matching function
	 */
	protected function match($filters) {
		foreach ($this->templates as $template) {

			$template = $this->get_full_data($template);
			$match = true;

			foreach ($filters as $filter) {

				$key = $filter['key'];
				$value = $filter['value'];

				if ($key === 'condition_singular_id') {
					$ids = array_map('trim', explode(',', $template[$key]));
					if (!in_array($value, $ids)) {
						$match = false;
					}
				}
				elseif ($key === 'condition_archive_id') {

					if (empty($template[$key])) {
						continue; // matches all
					}

					$ids = array_map('trim', explode(',', $template[$key]));

					if (!in_array($value, $ids)) {
						$match = false;
					}
				}
				else {
					if ($template[$key] != $value) {
						$match = false;
					}
				}
			}

			/**
			 * Filter the match result for template conditions.
			 *
			 * Allows extending condition matching logic from external code.
			 *
			 * @param bool   $match    Whether the template matches the current conditions.
			 * @param array  $template The template data being checked.
			 * @param array  $filters  The condition filters being applied.
			 */
			$match = apply_filters('elementskit/template/condition_match', $match, $template, $filters);

			if ($match) {
				if ($template['type'] === 'header') {
					$this->header_template = $template['ID'];
				}
				if ($template['type'] === 'footer') {
					$this->footer_template = $template['ID'];
				}
			}
		}
	}

	protected function get_full_data($post) {
		return [
			'ID'                    => $post->ID,
			'type'                  => get_post_meta($post->ID, 'elementskit_template_type', true),
			'condition_a'           => get_post_meta($post->ID, 'elementskit_template_condition_a', true),
			'condition_singular'    => get_post_meta($post->ID, 'elementskit_template_condition_singular', true),
			'condition_singular_id' => get_post_meta($post->ID, 'elementskit_template_condition_singular_id', true),
			'condition_archive'     => get_post_meta($post->ID, 'elementskit_template_condition_archive', true),
			'condition_archive_id'  => get_post_meta($post->ID, 'elementskit_template_condition_archive_id', true),
		];
	}

	public static function instance() {
		if (self::$instance === null) {
			self::$instance = new self();
		}
		return self::$instance;
	}
}

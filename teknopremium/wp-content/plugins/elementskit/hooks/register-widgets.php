<?php
namespace ElementsKit\Hooks;

defined( 'ABSPATH' ) || exit;

class Register_Widgets {
	use \ElementsKit\Traits\Singleton;

	public function __construct() {
		add_filter( 'elementskit/widgets/list', [$this, 'get_list'] );
	}

	public function get_list($list) {
		return array_merge($list, [
			'blog-posts' => [
				'slug'    => 'blog-posts',
				'title'   => 'Blog Posts',
				'package' => 'pro',
				'path'    => \ElementsKit::widget_dir() . 'blog-posts/',
				'widget-category' => 'post', // post
				'hasJS'   => true,
			],
			'advanced-accordion' => [
				'slug'    => 'advanced-accordion',
				'title'   => 'Advanced Accordion',
				'package' => 'pro',
				'path'    => \ElementsKit::widget_dir() . 'advanced-accordion/',
				'widget-category' => 'advanced', // advanced
				'hasJS'   => true,
			],
			'advanced-tab'       => [
				'slug'    => 'advanced-tab',
				'title'   => 'Advanced Tab',
				'package' => 'pro',
				'path'    => \ElementsKit::widget_dir() . 'advanced-tab/',
				'widget-category' => 'advanced', // advanced
				'hasJS'   => true,
			],
			'hotspot'            => [
				'slug'    => 'hotspot',
				'title'   => 'Hotspot',
				'package' => 'pro',
				'path'    => \ElementsKit::widget_dir() . 'hotspot/',
				'widget-category' => 'creative', // creative
				'hasJS'   => true,
			],
			'motion-text'        => [
				'slug'    => 'motion-text',
				'title'   => 'Motion Text',
				'package' => 'pro',
				'path'    => \ElementsKit::widget_dir() . 'motion-text/',
				'widget-category' => 'creative', // creative
				'hasJS'   => true,
			],
			'twitter-feed'       => [
				'slug'    => 'twitter-feed',
				'title'   => 'Twitter Feed',
				'package' => 'pro',
				'path'    => \ElementsKit::widget_dir() . 'twitter-feed/',
				'widget-category' => 'social-media-feeds', // social media feeds
			],
			'instagram-feed'       => [
				'slug'    => 'instagram-feed',
				'title'   => 'Instagram Feed',
				'package' => 'pro',
				'path'    => \ElementsKit::widget_dir() . 'instagram-feed/',
				'widget-category' => 'social-media-feeds', // social media feeds
			],
			'gallery'              => [
				'slug'    => 'gallery',
				'title'   => 'Gallery',
				'package' => 'pro',
				'path'    => \ElementsKit::widget_dir() . 'gallery/',
				'widget-category' => 'general', // general
				'hasJS'   => true,
			],
			'whatsapp'     => [
				'slug'    => 'whatsapp',
				'title'   => 'WhatsApp',
				'package' => 'pro',
				'path'    => \ElementsKit::widget_dir() . 'whatsapp/',
				'widget-category' => 'general', // general
				'hasJS'   => true,
			],
			'chart'                => [
				'slug'    => 'chart',
				'title'   => 'Chart',
				'package' => 'pro',
				'path'    => \ElementsKit::widget_dir() . 'chart/',
				'widget-category' => 'general', // general
			],
			'woo-category-list'    => [
				'slug'    => 'woo-category-list',
				'title'   => 'Woo Category List',
				'package' => 'pro',
				'path'    => \ElementsKit::widget_dir() . 'woo-category-list/',
				'widget-category' => 'woocommerce', // woocommerce
			],
			'woo-mini-cart'        => [
				'slug'    => 'woo-mini-cart',
				'title'   => 'Woo Mini Cart',
				'package' => 'pro',
				'path'    => \ElementsKit::widget_dir() . 'woo-mini-cart/',
				'widget-category' => 'woocommerce', // woocommerce
				'hasJS'   => true,
			],
			'woo-product-carousel' => [
				'slug'    => 'woo-product-carousel',
				'title'   => 'Woo Product Carousel',
				'package' => 'pro',
				'path'    => \ElementsKit::widget_dir() . 'woo-product-carousel/',
				'widget-category' => 'woocommerce', // woocommerce
				'hasJS'   => true,
			],
			'woo-product-list'     => [
				'slug'    => 'woo-product-list',
				'title'   => 'Woo Product List',
				'package' => 'pro',
				'path'    => \ElementsKit::widget_dir() . 'woo-product-list/',
				'widget-category' => 'woocommerce', // woocommerce
			],
			'table'                => [
				'slug'    => 'table',
				'title'   => 'Data Table',
				'package' => 'pro',
				'path'    => \ElementsKit::widget_dir() . 'table/',
				'widget-category' => 'general', // general
				'hasJS'   => true,
			],
			'timeline'             => [
				'slug'    => 'timeline',
				'title'   => 'Timeline',
				'package' => 'pro',
				'path'    => \ElementsKit::widget_dir() . 'timeline/',
				'widget-category' => 'creative', // creative
				'hasJS'   => true,
			],
			'creative-button'      => [
				'slug'    => 'creative-button',
				'title'   => 'Creative Button',
				'package' => 'pro',
				'path'    => \ElementsKit::widget_dir() . 'creative-button/',
				'widget-category' => 'creative', // creative
				'hasJS'   => true,
			],
			'vertical-menu'        => [
				'slug'    => 'vertical-menu',
				'title'   => 'Vertical Menu',
				'package' => 'pro',
				'path'    => \ElementsKit::widget_dir() . 'vertical-menu/',
				'widget-category' => 'header-footer', // header footer
				'hasJS'   => true,
			],
			'advanced-toggle'      => [
				'slug'    => 'advanced-toggle',
				'title'   => 'Advanced Toggle',
				'package' => 'pro',
				'path'    => \ElementsKit::widget_dir() . 'advanced-toggle/',
				'widget-category' => 'advanced', // advanced
				'hasJS'   => true,
			],
			'image-swap'           => [
				'slug'    => 'image-swap',
				'title'   => 'Image Swap',
				'package' => 'pro',
				'path'    => \ElementsKit::widget_dir() . 'image-swap/',
				'widget-category' => 'creative', // creative
				'hasJS'   => true,
			],
			'video-gallery'        => [
				'slug'    => 'video-gallery',
				'title'   => 'Video Gallery',
				'package' => 'pro',
				'path'    => \ElementsKit::widget_dir() . 'video-gallery/',
				'widget-category' => 'advanced', // advanced
				'hasJS'   => true,
			],
			'zoom'                 => [
				'slug'    => 'zoom',
				'title'   => 'Zoom',
				'package' => 'pro',
				'path'    => \ElementsKit::widget_dir() . 'zoom/',
				'widget-category' => 'general', // general
				'hasJS'   => true,
			],
			'behance-feed'         => [
				'slug'    => 'behance-feed',
				'title'   => 'Behance Feed',
				'package' => 'pro',
				'path'    => \ElementsKit::widget_dir() . 'behance-feed/',
				'widget-category' => 'social-media-feeds', // social media feeds
			],
			'breadcrumb' => [
				'slug'    => 'breadcrumb',
				'title'   => 'Breadcrumb',
				'package' => 'pro',
				'path'    => \ElementsKit::widget_dir() . 'breadcrumb/',
				'widget-category' => 'header-footer', // header footer
			],
			'dribble-feed' => [
				'slug'    => 'dribble-feed',
				'title'   => 'Dribbble Feed',
				'package' => 'pro',
				'path'    => \ElementsKit::widget_dir() . 'dribble-feed/',
				'widget-category' => 'social-media-feeds', // social media feeds
			],
			'facebook-feed' => [
				'slug'    => 'facebook-feed',
				'title'   => 'Facebook feed',
				'package' => 'pro',
				'path'    => \ElementsKit::widget_dir() . 'facebook-feed/',
				'widget-category' => 'social-media-feeds', // social media feeds
				'hasJS'   => true,
			],
			'facebook-review' => [
				'slug'    => 'facebook-review',
				'title'   => 'Facebook review',
				'package' => 'pro',
				'path'    => \ElementsKit::widget_dir() . 'facebook-review/',
				'widget-category' => 'review-testimonials', // review testimonials
				'hasJS'   => true,
			],
			'yelp' => [
				'slug'    => 'yelp',
				'title'   => 'Yelp',
				'package' => 'pro',
				'path'    => \ElementsKit::widget_dir() . 'yelp/',
				'widget-category' => 'review-testimonials', // review testimonials
				'hasJS'   => true,
			],
			'popup-modal' => [
				'slug'    => 'popup-modal',
				'title'   => 'Popup Modal',
				'package' => 'pro',
				'path'    => \ElementsKit::widget_dir() . 'popup-modal/',
				'widget-category' => 'marketing', // marketing
				'hasJS'   => true,
			],
			'google-map' => [
				'slug'    => 'google-map',
				'title'   => 'Google Map',
				'package' => 'pro',
				'path'    => \ElementsKit::widget_dir() . 'google-map/',
				'widget-category' => 'general', // general
			],
			'unfold' => [
				'slug'    => 'unfold',
				'title'   => 'Unfold',
				'package' => 'pro',
				'path'    => \ElementsKit::widget_dir() . 'unfold/',
				'widget-category' => 'creative', // creative
				'hasJS'   => true,
			],
			'pinterest-feed' => [
				'slug'    => 'pinterest-feed',
				'title'   => 'Pinterest Feed',
				'package' => 'pro',
				'path'    => \ElementsKit::widget_dir() . 'pinterest-feed/',
				'widget-category' => 'social-media-feeds', // social media feeds
			],
			'advanced-slider' => [
				'slug'    => 'advanced-slider',
				'title'   => 'Advanced Slider',
				'package' => 'pro',
				'path'    => \ElementsKit::widget_dir() . 'advanced-slider/',
				'widget-category' => 'advanced', // advanced
				'hasJS'   => true,
			],
			'image-hover-effect' => [
				'slug'    => 'image-hover-effect',
				'title'   => 'Image Hover Effect',
				'package' => 'pro',
				'path'    => \ElementsKit::widget_dir() . 'image-hover-effect/',
				'widget-category' => 'creative' // creative
			],
			'fancy-animated-text' => [
				'slug'    => 'fancy-animated-text',
				'title'   => 'Fancy Animated Text',
				'package' => 'pro',
				'path'    => \ElementsKit::widget_dir() . 'fancy-animated-text/',
				'widget-category' => 'creative', // creative
				'hasJS'   => true,
			],
			'price-menu' => [
				'slug'    => 'price-menu',
				'title'   => 'Price Menu',
				'package' => 'pro',
				'path'    => \ElementsKit::widget_dir() . 'price-menu/',
				'widget-category' => 'marketing', // marketing
				'hasJS'   => true,
			],
			'team-slider' => [
				'slug'    => 'team-slider',
				'title'   => 'Team Slider',
				'package' => 'pro',
				'path'    => \ElementsKit::widget_dir() . 'team-slider/',
				'widget-category' => 'advanced', // advanced
				'hasJS'   => true,
			],
			'audio-player' => [
				'slug'    => 'audio-player',
				'title'   => 'Audio Player',
				'package' => 'pro',
				'path'    => \ElementsKit::widget_dir() . 'audio-player/',
				'widget-category' => 'general', // general
				'hasJS'   => true,
			],
			'stylish-list' => [
				'slug'    => 'stylish-list',
				'title'   => 'Stylish List',
				'package' => 'pro',
				'path'    => \ElementsKit::widget_dir() . 'stylish-list/',
				'widget-category' => 'general', // general
				'hasJS'   => true,
			],
			'flip-box' => [
				'slug'    => 'flip-box',
				'title'   => 'Flip Box',
				'package' => 'pro',
				'path'    => \ElementsKit::widget_dir() . 'flip-box/',
				'widget-category' => 'creative', // creative
				'hasJS'   => true,
			],
			'image-morphing' => [
				'slug'    => 'image-morphing',
				'title'   => 'Image Morphing',
				'package' => 'pro',
				'path'    => \ElementsKit::widget_dir() . 'image-morphing/',
				'widget-category' => 'creative', // creative
				'hasJS'   => true,
			],
			'content-ticker' => [
				'slug'    => 'content-ticker',
				'title'   => 'Content Ticker',
				'package' => 'pro',
				'path'    => \ElementsKit::widget_dir() . 'content-ticker/',
				'widget-category' => 'marketing', // marketing
				'hasJS'   => true,
			],
			'coupon-code' => [
				'slug'    => 'coupon-code',
				'title'   => 'Coupon Code',
				'package' => 'pro',
				'path'    => \ElementsKit::widget_dir() . 'coupon-code/',
				'widget-category' => 'marketing', // marketing
				'hasJS'   => true,
			],
			'comparison-table' => [
				'slug'    => 'comparison-table',
				'title'   => 'Comparison Table',
				'package' => 'pro',
				'path'    => \ElementsKit::widget_dir() . 'comparison-table/',
				'widget-category' => 'marketing', // marketing
				'hasJS'   => true,
			],
			'protected-content' => [
				'slug'    => 'protected-content',
				'title'   => 'Protected Content',
				'package' => 'pro',
				'path'    => \ElementsKit::widget_dir() . 'protected-content/',
				'widget-category' => 'advanced', // advanced
			],
			'interactive-links' => [
				'slug'    => 'interactive-links',
				'title'   => 'Interactive Links',
				'package' => 'pro',
				'path'    => \ElementsKit::widget_dir() . 'interactive-links/',
				'widget-category' => 'creative', // creative
				'hasJS'   => true,
			],
			'circle-menu' => [
				'slug'    => 'circle-menu',
				'title'   => 'Circle Menu',
				'package' => 'pro',
				'path'    => \ElementsKit::widget_dir() . 'circle-menu/',
				'widget-category' => 'general', // general
				'hasJS'   => true,
			],
			'advanced-search' => [
				'slug'    => 'advanced-search',
				'title'   => 'Advanced Search',
				'package' => 'pro',
				'path'    => \ElementsKit::widget_dir() . 'advanced-search/',
				'widget-category' => 'header-footer', // header footer
				'hasJS'   => true,
			],
			'stacked-cards' => [
				'slug'    => 'stacked-cards',
				'title'   => 'Stacked Cards',
				'package' => 'pro',
				'path'    => \ElementsKit::widget_dir() . 'stacked-cards/',
				'widget-category' => 'creative', // creative
				'badge'   => 'new',
				'hasJS'   => true,
			],
			'login-form' => [
				'slug'    => 'login-form',
				'title'   => 'Login Form',
				'package' => 'pro',
				'path'    => \ElementsKit::widget_dir() . 'login-form/',
				'widget-category' => 'general', // general
				'badge'   => 'new',
				'hasJS'   => true,
			],
			'register-form' => [
				'slug'    => 'register-form',
				'title'   => 'Register Form',
				'package' => 'pro',
				'path'    => \ElementsKit::widget_dir() . 'register-form/',
				'widget-category' => 'general', // general
				'badge'   => 'new',
				'hasJS'   => true,
			],
			'copyright' => [
				'slug'    => 'copyright',
				'title'   => 'Copyright',
				'package' => 'pro',
				'path'    => \ElementsKit::widget_dir() . 'copyright/',
				'widget-category' => 'header-footer', // header footer
				'badge'   => 'new',
			],
			'event-calendar' => [
				'slug'    => 'event-calendar',
				'title'   => 'Event Calendar',
				'package' => 'pro',
				'path'    => \ElementsKit::widget_dir() . 'event-calendar/',
				'widget-category' => 'general', // General
				'badge'   => 'new',
				'hasJS'   => true,
			]
		]);
	}
}

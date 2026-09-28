<?php

namespace FluentSupport\App\Hooks\Handlers;

use FluentSupport\App\App;
use FluentSupport\Framework\Support\Arr;
use FluentSupport\App\Services\Blocks\BlockAttributes;

class BlockEditorHandler
{
    public function init()
    {
        $app = App::getInstance();

        $assets = $app['url.assets'];

        wp_register_script(
            'fluent-support/customer-portal',
            $assets . 'block-editor/js/fs_block.js',
            array('wp-blocks', 'wp-components', 'wp-block-editor', 'wp-element'),
            FLUENT_SUPPORT_VERSION,
            true
        );

        wp_register_style(
            'fluent-support/customer-portal-block-style',
            $assets . 'block-editor/css/fs_block.css',
            array(),
            FLUENT_SUPPORT_VERSION
        );

        wp_localize_script('fluent-support/customer-portal', 'fluent_support_vars', [
            'rest' => $this->getRestInfo(),
        ]);

        register_block_type('fluent-support/customer-portal', array(
            'api_version'     => 3,
            'editor_script'   => 'fluent-support/customer-portal',
            'editor_style'    => 'fluent-support/customer-portal-block-style',
            'render_callback' => array($this, 'renderBlock'),
            'attributes'      => BlockAttributes::CustomerPortalAttributes(),
        ));
    }

    protected function getRestInfo()
    {
        $app = App::getInstance();

        $ns = $app->config->get('app.rest_namespace');
        $v = $app->config->get('app.rest_version');

        return [
            'base_url'  => esc_url_raw(rest_url()),
            'url'       => rest_url($ns . '/' . $v),
            'nonce'     => wp_create_nonce('wp_rest'),
            'namespace' => $ns,
            'version'   => $v
        ];
    }

    public function renderBlock($attributes)
    {
        if (defined('REST_REQUEST') && REST_REQUEST) {
            return '';
        }

        $param = '';

        if (Arr::get($attributes, 'showLogoutButton')) {
            $param = "show_logout=yes";
        }

        if ($selectedMailbox = Arr::get($attributes, 'selectedMailbox')) {
            $param .= " business_box_id='" . esc_attr($selectedMailbox) . "'";
        }

        $param .= " attributes='" . esc_attr( base64_encode( wp_json_encode( $attributes, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) ) ) . "'";

        return do_shortcode("[fluent_support_portal $param]");
    }
}

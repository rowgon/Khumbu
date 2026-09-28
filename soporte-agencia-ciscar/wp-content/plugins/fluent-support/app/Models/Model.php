<?php

namespace FluentSupport\App\Models;

use FluentSupport\Framework\Database\Orm\Model as BaseModel;

class Model extends BaseModel
{
    protected $guarded = ['id', 'ID'];

    public function __construct($attributes = [])
    {
        parent::__construct($attributes);
    }

    public function getPerPage()
    {
        if (!isset($_REQUEST['per_page'])) {
            return 15;
        }

        if (isset($_REQUEST['nonce'])) {
            $nonceAction = (defined('REST_REQUEST') && REST_REQUEST) ? 'wp_rest' : 'fluent-support';
            
            if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_REQUEST['nonce'])), $nonceAction)) {
                return 15;
            }
        } elseif (is_admin() || wp_doing_ajax() || (defined('REST_REQUEST') && REST_REQUEST)) {
            return intval(sanitize_text_field(wp_unslash($_REQUEST['per_page']))) ?? 15;
        }

        return intval(sanitize_text_field(wp_unslash($_REQUEST['per_page'])));
    }
}

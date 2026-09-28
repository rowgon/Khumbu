<?php

namespace FluentSupport\App\Http\Controllers;

use FluentSupport\Framework\Http\Request\Request;

class AuthorizeController extends Controller
{
    public function handleHelpScoutAuthorization(Request $request)
    {
        wp_redirect(admin_url('admin.php?page=fluent-support#/help_scout?code=' . $request->getSafe('code', 'sanitize_text_field')));
    }
}

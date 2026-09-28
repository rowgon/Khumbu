<?php

namespace FluentSupport\App\Http\Requests;

use FluentSupport\Framework\Foundation\RequestGuard;

class AgentCreateRequest extends RequestGuard
{
    public function rules()
    {
        return [
        	'email' => 'required|email',
            'first_name' => 'required'
        ];
    }

    public function messages()
    {
        return [
            'email.required' => __('Email is required', 'fluent-support'),
            'first_name.required' => __('First name is required', 'fluent-support')
        ];
    }
}

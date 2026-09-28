<?php

namespace FluentSupport\App\Services;

use FluentSupport\App\Models\Customer;

class ProfileInfoService
{
    public static function getProfileExtraWidgets( $customer )
    {
        $widgets = [];
        /*
         * Filter customer profile widgets
         *
         * @since v1.0.0
         * @param array $widgets
         * @param object|array  $customer
         *
         * @return void
         */
        $widgets = apply_filters('fluent_support/customer_extra_widgets', $widgets, $customer);
        return $widgets;
    }

    // This method is linked with 'profile_update' action & it will trigger when user update profile
    public function onWPProfileUpdate( $userId, $userOldData, $userUpdatedData )
    {
        if ( !$userId ) {
            return false;
        }

        $customer = Customer::where( 'user_id', $userId );

        if ( $customer->count() == 0 ) {
            return false;
        }

        $keys = ['first_name', 'last_name', 'user_email'];
        $data = [];

        if( !array_diff_key( array_flip($keys), $userUpdatedData ) ) {
            $data = [
                'first_name' => $userUpdatedData['first_name'],
                'last_name' => $userUpdatedData['last_name'],
                'email' => $userUpdatedData['user_email'],
            ];
        };

        if ( count($data) > 0 ) {
            $customer->update($data);
        }

    }
}

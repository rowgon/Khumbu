<?php

namespace FluentSupport\App\Hooks\Handlers;

use FluentSupport\App\Models\Meta;
use FluentSupport\App\Services\Helper;
use FluentSupport\Framework\Support\Arr;


class EmailVerificationHandler
{
    /**
     * How long an issued signup verification code stays valid, in seconds.
     * Matches the "valid for 10 minutes" copy in the verification email below.
     */
    const CODE_TTL_SECONDS = 10 * MINUTE_IN_SECONDS;

    public static function sendSignupEmailVerificationHtml($formData)
    {
        $email = strtolower(trim($formData['email']));

        // IP bucket is a generous volumetric backstop (shared office/NAT IPs can have
        // many unrelated signups); the email bucket is the primary throttle, since this
        // endpoint is otherwise unauthenticated and can be used to mail-bomb any address.
        $ipKey = 'fs_signup_verify_ip_' . wp_hash(Helper::getIp());
        $emailKey = 'fs_signup_verify_email_' . wp_hash($email);

        /*
         * Site-wide ceiling on signup verification mail, as a circuit breaker against a
         * distributed attacker who rotates both source IP and target address and so never
         * trips either bucket above. Deliberately generous: this is the one bucket a
         * legitimate signup rush shares, and tripping it turns signup off for everyone,
         * so it is sized to be unreachable by organic traffic and filterable for sites
         * that genuinely run hotter.
         *
         * @since v2.2.2
         * @param int $limit Signup verification emails allowed site-wide per hour.
         */
        $globalLimit = (int) apply_filters('fluent_support/signup_verification_hourly_limit', 100);

        // Order matters: PHP short-circuits, so the global counter is only incremented by
        // requests that already cleared the IP and email gates. Were it checked first, a
        // flood from a single IP would burn the site-wide budget with requests that are
        // about to be rejected anyway, handing an attacker a cheap way to trip the breaker
        // and lock out every legitimate signup.
        if (Helper::hitRateLimit($ipKey, 20)
            || Helper::hitRateLimit($emailKey, 5)
            || Helper::hitRateLimit('fs_signup_verify_global', $globalLimit, HOUR_IN_SECONDS)
        ) {
            wp_send_json([
                'message' => __('Too many verification code requests. Please try again later.', 'fluent-support')
            ], 429);
        }

        try {
            $verifcationCode = str_pad(random_int(100123, 900987), 6, 0, STR_PAD_LEFT);
        } catch (\Exception $e) {
            $verifcationCode = str_pad(wp_rand(100123, 900987), 6, 0, STR_PAD_LEFT);
        }

        $string = $formData['email'] . '-' . wp_generate_uuid4() . wp_rand(1, 99999999);
        $hash = wp_hash_password($string);
        $hash = sanitize_title($hash, '', 'display');
        $hash .= $formData['email'] . '-' . time();

        $data = array(
            'login_hash'       => $hash,
            'status'           => 'issued',
            'email'            => strtolower(trim($formData['email'])),
            'ip_address'       => Helper::getIp(),
            'use_type'         => 'signup_verification',
            'used_count'       => 0,
            'two_fa_code_hash' => wp_hash_password($verifcationCode),
            'valid_till'       => gmdate('Y-m-d H:i:s', current_time('timestamp') + self::CODE_TTL_SECONDS),
            'created_at'       => current_time('mysql'),
            'updated_at'       => current_time('mysql')
        );

        $savedRecord = Meta::create([
            'object_type' => 'fs_login_hashes',
            'key'         => $hash,
            'value'       => maybe_serialize($data)
        ]);

        if (!$savedRecord || !$savedRecord->exists) {
            wp_send_json([
                'message' => __('Unable to send verification code. Please try again later.', 'fluent-support')
            ], 500);
        }

        // translators: %s is the site name
        $mailSubject = apply_filters("fluent_support/signup_verification_mail_subject", sprintf(__('Your registration verification code for %s', 'fluent-support'), get_bloginfo('name')));

        $pStart = '<p style="font-family: Arial, sans-serif; font-size: 16px; font-weight: normal; margin: 0; margin-bottom: 16px;">';

        // translators: %s is the user's first name
        $message = $pStart . sprintf(__('Hello %s,', 'fluent-support'), Arr::get($formData, 'first_name')) . '</p>' .
            $pStart . __('Thank you for registering with us! To complete the setup of your account, please enter the verification code below on the registration page.', 'fluent-support') . '</p>' .
            // translators: %s is the verification code
            $pStart . '<b>' . sprintf(__('Verification Code: %s', 'fluent-support'), $verifcationCode) . '</b></p>' .
            '<br />' .
            $pStart . __('This code is valid for 10 minutes and is meant to ensure the security of your account. If you did not initiate this request, please ignore this email.', 'fluent-support') . '</p>';

        $message = apply_filters('fluent_support/signup_verification_email_body', $message, $verifcationCode, $formData);

        $data = [
            'body'        => $message,
            'pre_header'  => __('Activate your account', 'fluent-support'),
            'show_footer' => false
        ];

        $message = Helper::loadView('notification', $data);
        $headers = array('Content-Type: text/html; charset=UTF-8');

        \wp_mail($formData['email'], $mailSubject, $message, $headers);

        ob_start();
        ?>
            <div class="fs_signup_verification">
                <div class="fs_field_group fs_field_verification">
                    <?php // translators: %s is the email address ?>
                    <p><?php echo esc_html(sprintf(__('A verification code has been sent to %s. Please provide the code below:', 'fluent-support'), $formData['email'])); ?></p>
                    <input type="hidden" name="_email_verification_hash" value="<?php echo esc_attr($hash); ?>"/>
                    <div class="fs_field_label is-required">
                        <label for="fs_field_verification"><?php esc_html_e('Verification Code', 'fluent-support'); ?></label>
                    </div>
                    <div class="fs_input_wrap">
                        <input type="text" id="fs_field_verification" placeholder="" name="_email_verification_token" required>
                    </div>
                </div>
                <button
                    style="display: inline-block; cursor: pointer; border: 0; background: #2271b1; color: #fff; text-decoration: none; text-shadow: none; min-height: 32px; padding: 8px 24px; font-size: 14px; border-radius: 3px; margin-top: 10px;"
                    id="fs_verification_submit" type="submit">
                    <?php esc_html_e('Complete Signup', 'fluent-support'); ?>
                </button>
            </div>
        <?php
        return ob_get_clean();
    }

}

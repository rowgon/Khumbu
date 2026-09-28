<?php

namespace FluentSupport\App\Http\Controllers;

use FluentSupport\App\Models\Meta;
use FluentSupport\App\Services\Helper;
use FluentSupport\Framework\Support\Arr;
use FluentSupport\Framework\Http\Request\Request;
use FluentSupport\App\Hooks\Handlers\AuthHandler;
use FluentSupport\App\Hooks\Handlers\ReCaptchaHandler;
use FluentSupport\App\Hooks\Handlers\TwoFaHandler;
use FluentSupport\App\Hooks\Handlers\EmailVerificationHandler;


class AuthController extends Controller
{
    /**
     * signUp method will create new user submitted data from sign up form
     * @param Request $request
     * @return \WP_REST_Response
     * @throws \FluentSupport\Framework\Validator\ValidationException
     */
    public function signup(Request $request)
    {

        if(Helper::getAuthProvider() !== 'fluent_support') {
            return $this->sendError([
                'message' => __('You are not allowed to signup using this form', 'fluent-support')
            ]);
        }

        if (!wp_verify_nonce($request->getSafe('_fsupport_signup_nonce', 'sanitize_text_field'), 'fluent_support_signup_nonce')) {
            return $this->sendError([
                'message' => __('Security verification failed. Please try again', 'fluent-support')
            ]);
        }

        $fields = AuthHandler::getSignupFields();

        $rules = $this->getRules($fields);

        $messages = $this->getMessages($rules);

        /*
         * Filter user signup form data
         *
         * @since v1.0.0
         * @param array $formData
         */
        $formData = apply_filters('fluent_support/signup_form_data', $request->all());

        /*
         * Action before validate user signup
         *
         * @since v1.0.0
         * @param array $formData
         */
        do_action('fluent_support/before_signup_validation', $formData);

        $checkRecaptchaAvailability = $this->isRecaptchaApplicable('signup_form');
        if ($checkRecaptchaAvailability && !$formData['_email_verification_hash']) {
            $validateCaptcha = ReCaptchaHandler::validateRecaptcha($formData['g-recaptcha-response']);
            if (!$validateCaptcha) {
                return $this->response([
                    'message' => __('Your recaptcha is not verified', 'fluent-support')
                ], 422);
            }
        }

        $this->validate($formData, $rules, $messages);

        if (empty($formData['_email_verification_token'])) {
            $tokenHtml = EmailVerificationHandler::sendSignupEmailVerificationHtml($formData);

            return $this->response([
                'verification_html' => $tokenHtml
            ]);
        } else {
            $token = $formData['_email_verification_token'];
            $verificationHash = $formData['_email_verification_hash'];

            $logHashMeta = Meta::where('object_type', 'fs_login_hashes',)
                ->where('key', $verificationHash)
                ->first();

            if (!$logHashMeta) {
                wp_send_json([
                    'message' => __('Please provide a valid verification code that was sent to your email address', 'fluent-support')
                ], 422);
            }

            $logHash = Helper::safeUnserialize($logHashMeta->value);

            if (!$logHash) {
                wp_send_json([
                    'message' => __('Please provide a valid verification code that was sent to your email address', 'fluent-support')
                ], 422);
            }

            // the code must still be unused and must not have been consumed by a prior request
            if (($logHash['status'] ?? '') !== 'issued') {
                wp_send_json([
                    'message' => __('Your verification code has already been used. Please try again', 'fluent-support')
                ], 422);
            }

            // records created before the email-binding fix (or any other legacy/malformed record)
            // have no bound email; treat them as invalid rather than proceeding with a null email
            if (empty($logHash['email'])) {
                wp_send_json([
                    'message' => __('Your verification code has expired. Please request a new one', 'fluent-support')
                ], 422);
            }

            // check if it got expired or not
            $validTill = $logHash['valid_till'] ?? '';
            if (($logHash['used_count'] ?? 0) > 5 || ($validTill && strtotime($validTill) < current_time('timestamp'))) {
                wp_send_json([
                    'message' => __('Your verification code has been expired. Please try again', 'fluent-support')
                ], 422);
            }

            if (!wp_check_password($token, $logHash['two_fa_code_hash'])) {

                $logHash['used_count'] +=  1;
                Meta::where('key', $logHash['login_hash'])->update([
                    'value' => maybe_serialize($logHash)
                ]);

                wp_send_json([
                    'message' => __('Please provide a valid verification code that was sent to your email address', 'fluent-support')
                ], 422);
            }

            // atomically consume the code: only succeeds if the record is still in the exact
            // state we just read, closing the race where two requests both pass the checks above
            $consumed = Meta::where('key', $logHash['login_hash'])
                ->where('object_type', 'fs_login_hashes')
                ->where('value', $logHashMeta->value)
                ->update([
                    'value' => maybe_serialize(array_merge($logHash, [
                        'used_count' => ($logHash['used_count'] ?? 0) + 1,
                        'status'     => 'used',
                    ]))
                ]);

            if (!$consumed) {
                wp_send_json([
                    'message' => __('Your verification code has already been used. Please try again', 'fluent-support')
                ], 422);
            }

            // the email is now server-verified for this code; ignore whatever the client
            // submitted and use the address the code was actually issued to, so the signup
            // can never be completed against a different (e.g. victim's) email address
            $formData['email'] = $logHash['email'];
        }

        /*
         * Action After validate user signup validation success
         *
         * @since v1.0.0
         * @param array $formData
         */
        do_action('fluent_support/after_signup_validation', $formData);

        $userId = $this->createUser($formData);

        if (is_wp_error($userId)) {
            return $this->response(
                apply_filters(
                    'fluent_support/signup_create_user_error',
                    ['error' => $userId->get_error_message()]
                ), 423);
        }

        /*
         * Action After creating WP user from ticket sign up form
         *
         * @since v1.0.0
         * @param array $formData
         */
        do_action('fluent_support/after_creating_user');

        $this->maybeUpdateUser($userId, $formData);
        $this->addUserMetaData($userId, $formData);
        $this->assignRole($userId);
        $this->login($userId);

        /*
         * Filter for user signup complete message and redirect
         *
         * @since v1.0.0
         * @param array $response
         */
        $response = apply_filters('fluent_support/signup_complete_response', [
            'message' => __('Successfully registered to the site.', 'fluent-support'),
            'redirect' => Arr::get($formData, '__redirect_to', Helper::getPortalBaseUrl())
        ]);

        return $this->response($response);
    }

    /**
     * handleLogin method will perform login functionality and redirect
     * @param Request $request
     * @return \WP_REST_Response
     */
    public function handleLogin(Request $request)
    {
        if(Helper::getAuthProvider() !== 'fluent_support') {
            return $this->sendError([
                'message' => __('You are not allowed to login using this form', 'fluent-support')
            ]);
        }

        if (!wp_verify_nonce($request->getSafe('_support_login_nonce', 'sanitize_text_field'), 'fsupport_login_nonce')) {
            return $this->response([
                'message' => __('Security verification failed', 'fluent-support')
            ], 403);
        }

        $data = $request->all();

        $checkRecaptchaAvailability = $this->isRecaptchaApplicable('login_form');
        if ($checkRecaptchaAvailability) {
            $validateCaptcha = ReCaptchaHandler::validateRecaptcha($data['g-recaptcha-response']);

            if (!$validateCaptcha) {
                return $this->response([
                    'message' => __('Your recaptcha is not verified', 'fluent-support')
                ], 422);
            }
        }

        if (empty($data['pwd']) || empty($data['log'])) {
            return $this->response([
                'message' => __('Email and Password is required', 'fluent-support')
            ], 403);
        }
        $redirectUrl = Helper::getPortalBaseUrl();
        if ($redirect = $request->getSafe('redirect_to', 'sanitize_text_field')) {
            $redirectUrl = wp_validate_redirect($redirect, $redirectUrl);
        }

        if (get_current_user_id()) { // user already registered
            return $this->sendSuccess([
                'redirect' => $redirectUrl
            ]);
        }

        $email = sanitize_user($data['log']);
        $password = trim($data['pwd'] ?? '');

        if (is_email($email)) {
            $user = get_user_by('email', $email);
        } else {
            $user = get_user_by('login', $email);
        }

        // Rate limiting: per-IP bucket (5 attempts) + per-account bucket (20 attempts)
        $ip = Helper::getIp();
        $ipKey = $user
            ? 'fs_login_ip_' . wp_hash($user->ID . '|' . $ip)
            : 'fs_login_ip_' . wp_hash(strtolower($email) . '|' . $ip);
        $accountKey = $user
            ? 'fs_login_act_' . wp_hash($user->ID)
            : 'fs_login_act_' . wp_hash(strtolower($email));

        $ipAttempts = get_transient($ipKey);
        $accountAttempts = get_transient($accountKey);

        if (($ipAttempts !== false && $ipAttempts >= 5) || ($accountAttempts !== false && $accountAttempts >= 20)) {
            return $this->sendError([
                'message' => __('Too many login attempts. Please try again after 15 minutes.', 'fluent-support')
            ], 429);
        }

        if (!$user) {
            $user = new \WP_Error('authentication_failed', __('Invalid username, email address or incorrect password.', 'fluent-support'));

            do_action('wp_login_failed', $email, $user);
            $this->incrementLoginAttempts($ipKey);
            $this->incrementLoginAttempts($accountKey);

            return $this->response([
                'message' => __('Email or Password is not valid. Please try again', 'fluent-support')
            ], 403);

        }

        $twoFactorEnabled = Helper::getBusinessSettings('enable_two_fa');
        if ('yes' === $twoFactorEnabled) {
            if (!wp_check_password($password, $user->user_pass, $user->ID)) {
                $this->incrementLoginAttempts($ipKey);
                $this->incrementLoginAttempts($accountKey);

                return $this->response([
                    'message' => __('Invalid username, email address or incorrect password.', 'fluent-support')
                ], 403);
            }

            (new TwoFaHandler)->maybe2FaRedirect($user);
        }

        if (apply_filters('fluent_support_use_native_login', true)) {
            $user = wp_signon();
            if (is_wp_error($user)) {
                $this->incrementLoginAttempts($ipKey);
                $this->incrementLoginAttempts($accountKey);
                return $this->response([
                    'message' => $user->get_error_message()
                ], 403);
            }

            // Clear rate limits for the authenticated user
            $authIpKey = 'fs_login_ip_' . wp_hash($user->ID . '|' . $ip);
            $authAccountKey = 'fs_login_act_' . wp_hash($user->ID);
            delete_transient($authIpKey);
            delete_transient($authAccountKey);
            return $this->sendSuccess([
                'redirect' => $redirectUrl
            ]);
        }

        if (wp_check_password($password, $user->user_pass, $user->ID)) {
            delete_transient($ipKey);
            delete_transient($accountKey);
            $this->login($user->ID);
            return $this->sendSuccess([
                'redirect' => $redirectUrl
            ]);
        }

        $this->incrementLoginAttempts($ipKey);
        $this->incrementLoginAttempts($accountKey);

        return $this->response([
            'message' => __('Invalid username, email address or incorrect password.', 'fluent-support')
        ], 403);
    }

    private function incrementLoginAttempts($rateLimitKey)
    {
        $attempts = get_transient($rateLimitKey);
        if ($attempts === false) {
            set_transient($rateLimitKey, 1, 15 * MINUTE_IN_SECONDS);
        } else {
            set_transient($rateLimitKey, $attempts + 1, 15 * MINUTE_IN_SECONDS);
        }
    }

    public function isRecaptchaApplicable($formName)
    {
        $reCaptchaSettingsData = Meta::where('object_type', '_fs_recaptcha_settings')->first();
        if(!isset($reCaptchaSettingsData->value)){
            return false;
        }
        $reCaptchaData = Helper::safeUnserialize($reCaptchaSettingsData->value, []);
        if(!isset($reCaptchaData['is_enabled']) || !isset($reCaptchaData['formContainingReCaptcha'])){
            return false;
        }
        $isEnabled = filter_var($reCaptchaData['is_enabled'], FILTER_VALIDATE_BOOLEAN);
        if (!$isEnabled) {
            return false;
        }
        $formContainingReCaptcha = $reCaptchaData['formContainingReCaptcha'];
        return 'yes' === $formContainingReCaptcha[$formName];
    }

    private function nativeLoginHandler($user, $info, $redirectUrl = '')
    {
        if (!$redirectUrl) {
            $redirectUrl = Helper::getPortalBaseUrl();
        }

        $secure_cookie = is_ssl();
        if (!$secure_cookie && !force_ssl_admin()) {
            if (get_user_option('use_ssl', $user->ID)) {
                $secure_cookie = true;
                force_ssl_admin(true);
            }
        }

        if (class_exists('\Limit_Login_Attempts')) {
            global $limit_login_attempts_obj;
            $limit_login_attempts_try = $limit_login_attempts_obj->wp_authenticate_user($user, false);
            if (is_wp_error($limit_login_attempts_try)) {
                return $this->response([
                    'message' => implode('<br/>', $limit_login_attempts_try->get_error_messages())
                ], 403);
            }
        }

        $user_signon = wp_signon($info, $secure_cookie);

        // Note: No sanitization needed here as we're only checking emptiness, not using the cookie value
        if (!is_wp_error($user_signon) && empty($_COOKIE[LOGGED_IN_COOKIE])) {
            if (headers_sent()) {
                return $this->response([
                    // translators: %1$s is the URL to WordPress cookies documentation, %2$s is the URL to WordPress support forums
                    'message' => sprintf(__('<strong>ERROR</strong>: Cookies are blocked due to unexpected output. For help, please see <a href="%1$s">this documentation</a> or try the <a href="%2$s">support forums</a>.', 'fluent-support'),
                        'https://codex.wordpress.org/Cookies', 'https://wordpress.org/support/')
                ], 403);
            }
        }

        if (is_wp_error($user_signon)) {
            $errorMessage = __('Email or Password is not valid. Please try again', 'fluent-support');

            if (class_exists('Limit_Login_Attempts')) {
                global $limit_login_attempts_obj;
                if ($limit_login_attempts_obj) {
                    $limit_login_attempts_obj->limit_login_failed($user->user_login);
                    $msg = $limit_login_attempts_obj->get_message();
                    if ($msg) {
                        $errorMessage = $msg;
                    }
                }
            }

            return $this->response([
                'message' => $errorMessage
            ], 403);
        }

        // WP Last Login plugin compatibility
        if (class_exists('\Obenland_Wp_Last_Login')) {
            update_user_meta($user_signon->ID, 'wp-last-login', time());
        }

        return $this->sendSuccess([
            'redirect' => $redirectUrl
        ]);
    }

    /**
     * getRules method will prepare the rules for the input field
     * @param array $fields
     * @return mixed
     */
    protected function getRules($fields = [])
    {
        $rules = [];

        foreach ($fields as $fieldName => $field) {
            if (array_key_exists('required', $field)) {
                $rules[$fieldName] = 'required';
            }

            $pipe = array_key_exists($fieldName, $rules) ? '|' : '';

            if ($field['type'] === 'email') {
                $rules[$fieldName] = $rules[$fieldName] . $pipe . 'email';
            } elseif ($field['type'] === 'password') {
                $rules[$fieldName] = $rules[$fieldName] . $pipe . 'min:8';
            }
        }
        /*
         * Filter user signup validation rules
         *
         * @since v1.0.0
         * @param array $rules
         */
        return apply_filters('fluent_support/signup_validation_rules', $rules);
    }


    public function resetPassword(Request $request)
    {

        if(Helper::getAuthProvider() !== 'fluent_support') {
            return $this->sendError([
                'message' => __('You are not allowed to reset password using this form', 'fluent-support')
            ]);
        }

        $errors = new \WP_Error();

        if (!wp_verify_nonce($request->getSafe('_fsupport_reset_pass_nonce', 'sanitize_text_field'), 'fluent_support_reset_pass_nonce')) {
            return $this->sendError([
                'message' => __('Security verification failed. Please try again', 'fluent-support')
            ]);
        }

        $usernameOrEmail = trim(wp_unslash($request->getSafe('user_login', 'sanitize_text_field')));

        if (!$usernameOrEmail) {
            return $this->sendError([
                'message' => 'Username or email is required'
            ]);
        }

        // IP bucket is a generous volumetric backstop (shared office/NAT IPs can have many
        // unrelated users). It runs before the account lookup so that probes for accounts
        // that don't exist are throttled too. Keyed on the IP only, so a 429 here reveals
        // nothing about whether any given account exists.
        if (Helper::hitRateLimit('fs_reset_pass_ip_' . wp_hash(Helper::getIp()), 20)) {
            return $this->sendError([
                'message' => __('Too many password reset requests. Please try again after 15 minutes.', 'fluent-support')
            ], 429);
        }

        $user_data = get_user_by('email', $usernameOrEmail);

        if (!$user_data) {
            $user_data = get_user_by('login', $usernameOrEmail);
        }

        if (!$user_data) {
            return $this->sendError([
                'message' => __('Invalid username or email', 'fluent-support')
            ]);
        }

        $user_data = apply_filters('lostpassword_user_data', $user_data, $errors);

        do_action('lostpassword_post', $errors, $user_data);

        $errors = apply_filters('lostpassword_errors', $errors, $user_data);

        if ($errors->has_errors()) {
            return $this->sendError([
                'message' => $errors->get_error_message()
            ]);
        }

        if (!$user_data) {
            return $this->sendError([
                'message' => __('There is no account with that username or email address.', 'fluent-support')
            ]);
        }

        if (is_multisite() && !is_user_member_of_blog($user_data->ID, get_current_blog_id())) {

            return $this->sendError([
                'message' => __('Invalid username or email', 'fluent-support')
            ]);
        }

        // Redefining user_login ensures we return the right case in the email.
        $user_login = $user_data->user_login;

        do_action('retrieve_password', $user_login);

        $allow = apply_filters('allow_password_reset', true, $user_data->ID);

        if (!$allow) {
            return $this->sendError([
                'message' => __('Password reset is not allowed for this user', 'fluent-support')
            ]);
        }

        if (is_wp_error($allow)) {
            return $this->sendError([
                'message' => $allow->get_error_message()
            ]);
        }


        /*
         * Filter reset password link text
         *
         * @since v1.5.7
         * @param string $linkText
         */
        // translators: %s is the site name
        $linkText = apply_filters("fluent_support/reset_password_link", sprintf(__('Reset your password for %s', 'fluent-support'), get_bloginfo('name')));

        // Issuance cooldown. get_password_reset_key() rotates the stored key, invalidating
        // any link already sitting in the account owner's inbox, so an unthrottled caller
        // could deny password recovery indefinitely. Suppressing the duplicate issuance is
        // safe: reset mail only ever goes to the account owner, so whoever triggered the
        // first send has already put a working link in that inbox.
        $cooldownKey = 'fs_reset_pass_sent_' . wp_hash($user_data->ID);

        if (get_transient($cooldownKey)) {
            return $this->sendError([
                'message' => __('A password reset link was already sent to this account recently. Please check your email, including the spam folder, or try again in a few minutes.', 'fluent-support')
            ], 429);
        }

        set_transient($cooldownKey, 1, 5 * MINUTE_IN_SECONDS);

        $resetUrl = add_query_arg([
            'action' => 'rp',
            'key' => get_password_reset_key($user_data),
            'login' => rawurlencode($user_data->user_login)
        ], wp_login_url());

        $resetLink = '<a href="' . $resetUrl . '">' . $linkText . '</a>';

        /*
         * Filter reset password email subject
         *
         * @since v1.5.7
         * @param string $mailSubject
         */
        // translators: %s is the site name
        $mailSubject = apply_filters("fluent_support/reset_password_mail_subject", sprintf(__('Reset your password for %s support portal', 'fluent-support'), get_bloginfo('name')));

        // translators: %s is the user's first name
        $message = '<p>' . sprintf(__('Hi %s,', 'fluent-support'), $user_data->first_name) . '</p>' .
            '<p>' . __('Someone has requested a new password for the following account on WordPress:', 'fluent-support') . '</p>' .
            // translators: %s is the username
            '<p>' . sprintf(__('Username: %s', 'fluent-support'), $user_login) . '</p>' .
            '<p>' . $resetLink . '</p>' .
            '<p>' . __('If you did not request to reset your password, please ignore this email.', 'fluent-support') . '</p>';

        /*
         * Filter reset password email body text
         *
         * @since v1.5.7
         * @param string $message
         * @param object $user
         * @param string $resetLink
         */
        $message = apply_filters('fluent_support/reset_password_message', $message, $user_data, $resetLink);

        $headers = array('Content-Type: text/html; charset=UTF-8');

        wp_mail($user_data->user_email, $mailSubject, $message, $headers);

        return $this->sendSuccess([
            'message' => __('Please check your email for the reset link', 'fluent-support')
        ]);
    }

    /**
     * getMessages message will return the validation message regarding sign up or sign in
     * @param array $rules
     * @return mixed
     */
    protected function getMessages($rules = [])
    {
        /*
         * Filter user signup validation message
         *
         * @since v1.0.0
         * @param array $arg
         * @param array $rules
         */
        return apply_filters('fluent_support/signup_validation_messages', [], $rules);
    }

    /**
     * createUser method will create new user
     * @param array $formData
     * @return mixed
     */
    public function createUser($formData = [])
    {
        /*
         * Filter user signup email
         *
         * @since v1.0.0
         * @param string $email
         */
        $email = apply_filters('fluent_support/signup_email', Arr::get($formData, 'email'));

        /*
         * Filter user signup username
         *
         * @since v1.0.0
         * @param string $username
         */
        $userName = apply_filters('fluent_support/signup_username', Arr::get($formData, 'username'));

        if (empty($formData['password'])) {
            $password = wp_generate_password(16, true, true);
        } else {
            $password = $formData['password'];
        }

        /*
         * Filter user signup password
         *
         * @since v1.0.0
         * @param string $password
         */
        $password = apply_filters('fluent_support/signup_password', $password);

        /*
         * Action before creating WP user using Fluent Support signup form
         *
         * @since v1.0.0
         * @param string $userName
         * @param string $password
         * @param string $email
         */
        do_action('fluent_support/before_creating_user', $userName, $password, $email);

        $userId = wp_create_user($userName, $password, $email);

        if (is_wp_error($userId)) {
            return false;
        }

        return $userId;

    }

    /**
     * maybeUpdateUser method will update user information if exists
     * @param $userId
     * @param $formData
     */
    public function maybeUpdateUser($userId, $formData)
    {
        $firstName = Arr::get($formData, 'first_name', '');
        $lastName = Arr::get($formData, 'last_name', '');
        $name = trim($firstName . ' ' . $lastName);

        $data = array_filter([
            'ID' => $userId,
            'user_nicename' => $name,
            'display_name' => $name,
            'first_name' => $firstName,
            'last_name' => $lastName,
        ]);

        if ($name) {
            /*
             * Action before updating a customer/user
             *
             * @since v1.0.0
             * @param array $data
             */
            do_action('fluent_support/before_updating_user', $data);

            /*
             * Filter user updatable data
             *
             * @since v1.0.0
             * @param $data
             */
            $updateUserData = apply_filters('fluent_support/update_user_data', $data);
            wp_update_user($updateUserData);

            /*
             * Action after updating a customer/user
             *
             * @since v1.0.0
             * @param array $data
             */
            do_action('fluent_support/after_updating_user', $data);
        }
    }

    public function addUserMetaData($userId, $formData) {
        $customFieldsKey = apply_filters('fluent_support/custom_registration_form_fields_key', Helper::getBusinessSettings('custom_registration_form_field'));

        if (empty($customFieldsKey)) {
            return;
        }

        foreach ($customFieldsKey as $key) {
            if (isset($formData[$key])) {
                $fieldValue = $formData[$key];
                update_user_meta($userId, $key, $fieldValue);
            }
        }
    }

    /**
     * assignRole method will assign role to a given user id
     * @param $userId
     */
    protected function assignRole($userId)
    {
        $user = new \WP_User($userId);

        /*
         * Action before assigning role to registered user
         *
         * @since v1.0.0
         * @param array $data
         */
        do_action('fluent_support/before_assigning_role', $user);
        /*
         * Filter user assignable role after signup
         *
         * @since v1.0.0
         * @param string $setRole WordPress user role key
         */
        $setRole = apply_filters('fluent_support/user_role', 'subscriber');
        $user->set_role($setRole);

        /*
         * Action after assigning role to registered user
         *
         * @since v1.0.0
         * @param array $data
         */
        do_action('fluent_support/after_assigning_role', $user);
    }


    /**
     * login method will clear existing cookies and set new cookie for a given user id
     * @param $userId
     */
    protected function login($userId)
    {
        /*
         * Action before login
         *
         * @since v1.0.0
         * @param integer $userId
         */
        do_action('fluent_support/before_logging_in_user', $userId);

        wp_clear_auth_cookie();
        wp_set_current_user($userId);
        wp_set_auth_cookie($userId);

        /*
         * Action after login
         *
         * @since v1.0.0
         * @param integer $userId
         */
        do_action('fluent_support/after_logging_in_user', $userId);
    }

}

<?php

namespace ElementsKit\Widgets\LoginForm;

class Login_Ajax_Handler {
    public function __construct() {
        // AJAX handler for login form submission
        add_action( 'wp_ajax_ekit_login_form', [ $this, 'handle_login_ajax' ] );
        add_action( 'wp_ajax_nopriv_ekit_login_form', [ $this, 'handle_login_ajax' ] );
        // AJAX handler for lost password requests
        add_action( 'wp_ajax_ekit_lost_password', [ $this, 'handle_lost_password_ajax' ] );
        add_action( 'wp_ajax_nopriv_ekit_lost_password', [ $this, 'handle_lost_password_ajax' ] );
        // Localize lost password settings
        add_filter( 'elementskit/common/localize_settings', [ $this, 'lost_password_localize_settings' ] );
        add_filter( 'auth_cookie_expiration', [$this, 'remember_me_duration'], 10, 3);
    }

    /**
     * Handles AJAX login requests for the ElementsKit login form.
     *
     * This method processes login form submissions via AJAX, validates user credentials,
     * and manages user authentication. It includes security checks via nonce verification
     * and sanitizes all user inputs.
     *
     * @return void Sends JSON response with success or error message via wp_send_json_success()
     *              or wp_send_json_error(). On success, includes redirect URL.
     *
     * Expected POST Parameters:
     * @param string $_POST['log']           Username (required)
     * @param string $_POST['pwd']           Password (required)
     * @param bool   $_POST['rememberme']    Optional. Whether to remember login
     * @param string $_POST['redirect_to']   Optional. URL to redirect after successful login
     * @param string $_POST['security']      Nonce token for security verification
     *
     */
    public function handle_login_ajax() {
        check_ajax_referer('ekit_pro', 'security');

        $username = isset( $_POST['log'] ) ? sanitize_text_field( $_POST['log'] ) : '';
        $password = isset( $_POST['pwd'] ) ? $_POST['pwd'] : '';

        if ( empty( $username ) || empty( $password ) ) {
            wp_send_json_error( [ 'message' => __( 'Username and password are required.', 'elementskit' ) ] );
        }

        $user = wp_signon( [ 
            'user_login' => $username, 
            'user_password' => $password,
            'remember' => isset( $_POST['rememberme'] ) ? true : false
        ], is_ssl() );

        if ( is_wp_error( $user ) ) {
            $error_message = $user->get_error_message();
            $error_message = str_replace( '<a ', '<a class="ekit-login-lost-password" ', $error_message );
            wp_send_json_error( [ 
                'message' => $error_message, 
                'severity' => 'warning'
            ] );
        } else {
            wp_set_current_user( $user->ID );
            do_action( 'wp_login', $user->user_login, $user );
        
            $redirect_url = isset( $_POST['redirect_to'] ) ? esc_url( $_POST['redirect_to'] ) : admin_url();
            
            wp_send_json_success( [ 
                'message' => __( 'Login successful. Redirecting...', 'elementskit' ),
                'redirect_url' => $redirect_url,
            ] );
        }
    }
    /**
     * Please provide the code selection you want documented.
     *
     * When you paste the selection, include:
     *  - The exact code (function, method, class, or snippet) to document.
     *  - The intended visibility/usage context if not clear.
     *  - Any explicit parameter or return types and descriptions you want included (if they cannot be inferred).
     *  - Preferred documentation style/length (brief, detailed, phpdoc tags).
     *
     * After you provide the selection, I will return only a documentation comment in PHP docblock format.
     */
    public function handle_lost_password_ajax() {
        check_ajax_referer('ekit_pro', 'security');

        $user_login = isset( $_POST['user_login'] ) ? sanitize_text_field( $_POST['user_login'] ) : '';

        if ( empty( $user_login ) ) {
            wp_send_json_error( [ 
                'message' => __( 'Please enter a username or email address.', 'elementskit' ),
                'severity' => 'warning'
            ] );
        }

        // Check if user exists
        $user = strpos( $user_login, '@' ) ? get_user_by( 'email', $user_login ) : get_user_by( 'login', $user_login );
        if ( ! $user ) {
            wp_send_json_error( [ 
                'message' => __( 'Invalid username or email address.', 'elementskit' ),
                'severity' => 'warning'
            ] );
        }

        // Trigger WordPress lost password functionality
        $result = retrieve_password( $user->user_login );

        if ( is_wp_error( $result ) ) {
            wp_send_json_error( [ 
                'message' => $result->get_error_message(),
                'severity' => 'warning'
            ] );
        } else {
            wp_send_json_success( [ 
                'message' => __( 'Check your email for the password reset link.', 'elementskit' )
            ] );
        }
    }

    public function lost_password_localize_settings( $settings ) {
        $settings['errorMessage'] = esc_html__('Something went wrong. Please try again.', 'elementskit');
        return $settings;
    }

    /**
     * Filters the remember me cookie duration based on user preference.
     *
     * @param int  $expiration The expiration time in seconds.
     * @param int  $user_id    The user ID.
     * @param bool $remember   Whether the user checked the "remember me" checkbox.
     *
     * @return int The filtered expiration time in seconds. Returns the custom duration
     *             if remember me is checked and a valid custom duration is provided via
     *             POST data, otherwise returns the original expiration time.
     *
     * @since 4.1.2
     */
    public function remember_me_duration($expiration, $user_id, $remember) {
       // Only apply custom duration if remember me is checked
        if (!$remember) {
            return $expiration;
        }
        
        // Get custom duration from POST data
        if (isset($_POST['ekit_remember_duration']) && !empty($_POST['ekit_remember_duration'])) {
            $custom_duration = absint($_POST['ekit_remember_duration']);
            // Validate duration (max 7 days = 604800 seconds)
            if ($custom_duration > 0 && $custom_duration <= 604800) {
                return $custom_duration;
            }
        }

        return $expiration;
    }
}

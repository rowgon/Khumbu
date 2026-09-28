<?php
namespace ElementsKit\Widgets\RegisterForm;
use \Elementor\ElementsKit_Widget_Register_Form_Handler as Handler;
defined('ABSPATH') || exit;

/**
 * AJAX handler for the ElementsKit Register Form widget.
 *
 * Handles:
 * - AJAX user registration
 * - Input validation
 * - Error responses
 * - Localized nonce for frontend scripts
 *
 * @package ElementsKit
 * @subpackage Widgets\RegisterForm
 */
class Register_Ajax_Handler {

    /**
     * Register_Ajax_Handler constructor.
     *
     * Registers AJAX actions for logged-in and guest users.
     * Adds filter to localize security nonces for frontend usage.
     *
     * @return void
     */
    public function __construct() {
        add_action('wp_ajax_nopriv_ekit_register_user', [$this, 'register_user']);
        add_action('wp_ajax_ekit_register_user',        [$this, 'register_user']);
        add_filter('user_contactmethods', [$this, 'add_phone_contact_method'], 30, 3);
        add_filter('elementskit/common/localize_settings', [$this, 'localize_settings']);
    }

    /**
     * Send error message in uniform JSON format.
     *
     * @param string $message Error message shown to the user.
     * @return void
     */
    private function error( $message ) {
        wp_send_json_error([
            'message' => esc_html( $message ),
        ]);
    }

    /**
     * Handle user registration via AJAX.
     *
     * Steps:
     * 1. Verify AJAX nonce for security.
     * 2. Sanitize all input values.
     * 3. Validate required fields.
     * 4. Validate email, password match, and existing accounts.
     * 5. Create the new WordPress user.
     * 6. Add optional user meta fields.
     * 7. Return success JSON response.
     *
     * @return void Sends JSON success or error response.
     */
    public function register_user() {
        // Verify security nonce
        check_ajax_referer('ekit_pro', 'security');
        $is_disabled = Handler::is_disabled_register();

        if ($is_disabled['name'] === 'ekit_register' && !$is_disabled['value']) {
            $this->error( __('Registration is currently disabled.', 'elementskit') );
            return;
        }

        // Sanitize input fields
        $username         = sanitize_user( $_POST['user_login'] ?? '' );
        $email            = sanitize_email( $_POST['user_email'] ?? '' );
        $password         = sanitize_text_field( $_POST['user_pass'] ?? '' );
        $confirm_password = sanitize_text_field( $_POST['user_confirm_password'] ?? '' );

        // Optional fields
        $first_name = sanitize_text_field($_POST['first_name'] ?? '');
        $last_name  = sanitize_text_field($_POST['last_name'] ?? '');
        $phone      = sanitize_text_field($_POST['user_phone'] ?? '');
        $user_url   = esc_url_raw($_POST['user_url'] ?? '');

    
        /**
         * Validates user registration input fields.
         *
         * This code performs the following checks:
         * 1. Ensures all required fields (username, email, password, confirm_password) are filled.
         * 2. Validates that the provided email address is in a proper format.
         * 3. Checks if the password and confirm_password fields match.
         * 4. Verifies that the username is not already taken.
         * 5. Ensures the email address is not already registered.
         * 6. Creates a new WordPress user with the provided username, password, and email.
         *     -@param string $username The desired username for the new user.
         *     -@param string $password The desired password for the new user.
         *     -@param string $email    The email address for the new user.
         * If any of these validations fail, an error message is returned.
         *
         * @throws Exception If any validation fails, an error message is triggered.
         */

        if ( ! is_email( $email ) ) {
            $this->error( __('Please enter a valid email address.', 'elementskit') );
        }

        // Check only required fields (username, email, password)
        if ( ! $username || ! $email || ! $password ) {
            $this->error( __('All required fields must be filled.', 'elementskit') );
        }
        
        // Only validate password match if confirm_password is provided
        if ( ! empty( $confirm_password ) && $password !== $confirm_password ) {
            $this->error( __('Passwords do not match.', 'elementskit') );
        }

        if ( username_exists( $username ) ) {
            $this->error( __('This username is already taken. Please choose another one.', 'elementskit') );
        }

        if ( email_exists( $email ) ) {
            $this->error( __('This email is already registered. Please use a different email address.', 'elementskit') );
        }
        
        $user_id = wp_create_user( $username, $password, $email );

        if ( is_wp_error( $user_id ) ) {
            $error_message = $user_id->get_error_message();
            $error_message = str_replace( '<a ', '<a class="ekit-register-error-message" ', $error_message );
            $this->error( $error_message );
        } else{
            /**
             * Updates the user meta information for a given user ID.
             *
             * @param int    $user_id   The ID of the user whose meta data is being updated.
             * @param string $first_name The first name of the user.
             * @param string $last_name  The last name of the user.
             * @param string $phone      The phone number of the user.
             * @param string $user_url   The website URL of the user.
             *
             * @return void
             */
            update_user_meta( $user_id, 'first_name', $first_name );
            update_user_meta( $user_id, 'last_name',  $last_name );
            update_user_meta( $user_id, 'phone',      $phone );
            update_user_meta( $user_id, 'user_url',   $user_url );
            // User created successfully
            $redirect_url = isset( $_POST['redirect_to'] ) ? esc_url( $_POST['redirect_to'] ) : wp_login_url();
            
            wp_send_json_success([
                'message' => __('Registration successful.', 'elementskit'),
                'redirect_url' => $redirect_url,
            ]);
            exit;
        }
    }

    /**
     * Add AJAX nonce to localized settings so JS can read it.
     *
     * @param array $settings Existing localized settings.
     * @return array Modified settings.
     */
    public function localize_settings( $settings ) {
        $settings['errorMessage'] = esc_html__('Something went wrong. Please try again.', 'elementskit');
        return $settings;
    }

    /**
     * Add phone as a contact method for users.
     *
     * @param array $contactmethods Existing contact methods.
     * @return array Modified contact methods.
     */
    public function add_phone_contact_method( $contactmethods ) {
        $contactmethods['phone'] = esc_html__( 'Phone Number', 'elementskit' );
        return $contactmethods; 
    }
}

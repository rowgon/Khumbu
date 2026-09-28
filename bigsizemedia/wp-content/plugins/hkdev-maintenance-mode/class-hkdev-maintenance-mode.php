<?php

/**
 * Core maintenance mode implementation for the plugin.
 *
 * This class is responsible for:
 * - creating the database tables used for access keys and allowed IPs,
 * - loading and storing the plugin settings,
 * - deciding whether a request should be blocked by maintenance mode,
 * - rendering the maintenance page or redirecting visitors,
 * - handling the AJAX actions used by the admin settings screen.
 *
 * @package HkDevMaintenanceMode
 * @access  public
 */


if (!defined('ABSPATH')) die();

if (class_exists("HkDevMaintenanceMode") === false) {

    class HkDevMaintenanceMode
    {
        private $admin_options_name;
        private $maintenance_html_head;
        private $maintenance_html_foot;
        private $maintenance_html_body;

        /**
         * Constructor.
         *
         * Sets the default option names, the maintenance page markup, and
         * immediately initializes the plugin state.
         */
        public function __construct()
        {
            $this->admin_options_name = "hkdev_mm";
            $this->maintenance_html_head = '<!DOCTYPE html><html><head><link href="[[WP_STYLE]]" rel="stylesheet" type="text/css" /><title>[[WP_TITLE]]</title></head><body><div style="margin: auto;max-width: 800px;">';
            $this->maintenance_html_body = _x('<h1>Website Under Maintenance</h1>Our Website is currently undergoing scheduled maintenance. Please check back soon.', 'Maintenance message', 'hkdev-maintenance-mode');
            $this->maintenance_html_foot = '</div></body></html>';

            $this->init(); // initialize
        }

        /**
         * Initialize the plugin database and defaults.
         *
         * This method ensures the custom tables for access keys and unrestricted IPs
         * exist, and it creates the default plugin options if they are missing.
         */
        public function init()
        {
            global $wpdb;

            // Create the access keys table if it does not exist yet.
            $def_time = '0000-00-00 00:00:00';
            $tbl = esc_sql($wpdb->prefix . $this->admin_options_name . "_access_keys");
            if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $tbl)) !== $tbl) {
                $wpdb->query($wpdb->prepare("CREATE TABLE %i (id int auto_increment primary key, `name` varchar(100), access_key varchar(20), email varchar(100), created_at datetime NOT NULL DEFAULT %s, active int(1) NOT NULL DEFAULT 1)", $tbl, $def_time));
            }

            // Create IPs table if needed
            $tbl = esc_sql($wpdb->prefix . $this->admin_options_name . "_unrestricted_ips");
            if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $tbl)) !== $tbl) {
                $wpdb->query($wpdb->prepare("CREATE TABLE %i (id int auto_increment primary key, `name` varchar(100), ip_address varchar(20), created_at datetime NOT NULL DEFAULT %s, active int(1) NOT NULL DEFAULT 1)", $tbl, $def_time));
            }

            // Setup options
            add_option("hkdev_maintenance_mode_version", "1.7");
            $tmp_opt = $this->get_admin_options();
        }

        /**
         * Find the user's IP address.
         *
         * This method checks the REMOTE_ADDR server variable and validates it.
         */
        private function get_user_ip()
        {
            $client_ip = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : null;

            if ($client_ip && filter_var($client_ip, FILTER_VALIDATE_IP)) {
                return $client_ip;
            }
            return null;
        }

        /**
         * Determine the user's class C IP address.
         *
         * This method returns the first three octets of the user's IP address,
         * followed by a wildcard, e.g., 192.168.1.*.
         */
        private function get_user_class_c()
        {
            $ip = $this->get_user_ip();

            if (!$ip) {
                return '0.0.0.*';
            }

            $ip_parts = explode('.', $ip);

            if (count($ip_parts) >= 3) {
                $class_c = $ip_parts[0] . '.' . $ip_parts[1] . '.' . $ip_parts[2] . '.*';
                return $class_c;
            }

            return '0.0.0.*';
        }

        /**
         * Handle the AJAX request to search for posts.
         *
         * This method is called via AJAX and returns a JSON array of post IDs and titles
         * matching the search query.
         */
        public function get_posts_ajax_callback()
        {
            if (!current_user_can('manage_options')) {
                wp_die(-1);
            }

            check_ajax_referer('hkdev_nonce', 'security');

            $return = array();
            $search_query = isset($_GET['q']) ? sanitize_text_field(wp_unslash($_GET['q'])) : '';

            if ($search_query === '') {
                wp_send_json($return);
            }

            $search_results = new WP_Query(array(
                's' => $search_query,
                'post_type' => array('page', 'post'),
                'post_status' => 'publish',
                'ignore_sticky_posts' => 1,
                'posts_per_page' => 10,
                'orderby' => 'title',
                'order' => 'ASC'
            ));
        
            if ($search_results->have_posts()) {
                while ($search_results->have_posts()) {
                    $search_results->the_post();
                    $title = get_the_title();
                    $title = (mb_strlen($title) > 50) ? mb_substr($title, 0, 49) . '...' : $title;
                    $return[] = array(get_the_ID(), $title);
                }
            }
        
            wp_reset_postdata();
            wp_send_json($return);
        }

        /**
         * Return the plugin options with safe defaults.
         *
         * If no saved options exist yet, the method initializes the default values
         * so the maintenance mode logic can run immediately.
         */
        public function get_admin_options()
        {
            $hkdev_mm_options = array(
                'enable_mm'             => 'no',
                'header_type'           => '200',
                'method'                => 'die_message',
                'maintenance_title'     => '',
                'maintenance_message'   => $this->maintenance_html_head . $this->maintenance_html_body . $this->maintenance_html_foot,
                'maintenance_html'      => $this->maintenance_html_body,
                'static_page'           => '',
                'exclude_pages'         => array()
            );

            $hkdev_mm_saved_options = get_option($this->admin_options_name);
            
            if (!empty($hkdev_mm_saved_options) && is_array($hkdev_mm_saved_options)) {
                foreach ($hkdev_mm_options as $key => $value) {
                    if (array_key_exists($key, $hkdev_mm_saved_options)) {
                        $saved_value = $hkdev_mm_saved_options[$key];

                        if (in_array($key, array('maintenance_message', 'maintenance_html'), true)) {
                            $saved_value = wp_unslash($saved_value);
                            $saved_value = html_entity_decode((string) $saved_value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                        }

                        $hkdev_mm_options[$key] = $saved_value;
                    }
                }
            } else {
                update_option($this->admin_options_name, $hkdev_mm_options);
            }

            return $hkdev_mm_options;
        }

        /**
         * Render the maintenance page or terminate the request with a message.
         *
         * This method is used when a visitor should not be allowed to continue, and
         * it decides whether to show a plain message, an HTML page, or a redirect.
         */
        private function generate_maintenance_page($content_override = '', $method = 'die_message')
        {
            $hkdev_mm_options = $this->get_admin_options();

            if ($hkdev_mm_options['header_type'] == "200") {
                header('HTTP/1.1 200 OK');
                header('Status: 200 OK');
            } else {
                header('HTTP/1.1 503 Service Temporarily Unavailable');
                header('Status: 503 Service Temporarily Unavailable');
            }

            header('Retry-After: 600');
            $site_title = $hkdev_mm_options['maintenance_title'];
            $site_title = ($site_title!='') ? $site_title: get_bloginfo('name');

            if ($method == 'html') {
                $content = ($content_override !== '') ? wp_unslash($content_override) : $this->maintenance_html_body;
                echo wp_kses_post($content);
                exit();
            } elseif ($method == 'site_message') {
                $allowed_html = array(
                    'html' => array('lang' => array(), 'style' => array()),
                    'head' => array(),
                    'body' => array('style' => array()),
                    'title' => array(),
                    'link' => array('href' => array(), 'rel' => array(), 'type' => array()),
                    'div' => array('style' => array(), 'class' => array(), 'id' => array()),
                    'style' => array('type' => array()),
                    'p' => array('class' => array(), 'id' => array(), 'style' => array()),
                    'h1' => array('class' => array(), 'id' => array(), 'style' => array()),
                    'strong' => array(),
                    'br' => array(),
                );
                $out  = $this->maintenance_html_head;
                $out  = str_replace('[[WP_TITLE]]', esc_html($site_title), $out);
                $out  = str_replace('[[WP_STYLE]]', esc_url(get_bloginfo('stylesheet_url')), $out);
                $out .= ($content_override !== '') ? wp_kses_post(wp_unslash($content_override)) : wp_kses_post($this->maintenance_html_body);
                $out .= $this->maintenance_html_foot;
                echo wp_kses($out, $allowed_html);
                exit();
            } else {
                $out  = ($content_override !== '') ? wp_unslash($content_override) : $this->maintenance_html_body;
                $response_code = in_array($hkdev_mm_options['header_type'], array('200', '503', '507'), true) ? absint($hkdev_mm_options['header_type']) : 503;
                wp_die(wp_kses_post($out), esc_html($site_title), ['response' => absint($response_code)]);
            }
        }

        private function hkdev_get_page_id()
        {
            $url = get_permalink();
            if (empty($url)) {
                $protocol = isset($_SERVER['HTTPS']) && sanitize_text_field(wp_unslash($_SERVER['HTTPS'])) === 'on' ? 'https' : 'http';
                $http_host = isset($_SERVER['HTTP_HOST']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_HOST'])) : '';
                $request_uri = isset($_SERVER['REQUEST_URI']) ? sanitize_text_field(wp_unslash($_SERVER['REQUEST_URI'])) : '';
                $url = $protocol . "://" . $http_host . $request_uri;
            }
            //$permalink = strtok($url, '?');
            return url_to_postid($url);
        }

        private function prevent_page_caching_for_maintenance()
        {
            if (headers_sent()) {
                return;
            }

            if (function_exists('nocache_headers')) {
                nocache_headers();
                return;
            }

            header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
            header('Pragma: no-cache');
            header('Expires: Wed, 11 Jan 1984 05:00:00 GMT');
        }

        /**
         * Determine whether the current request should bypass maintenance mode.
         *
         * Requests are allowed through when the user is an administrator, the page is
         * excluded, the request uses a valid access key, or the visitor IP is on the
         * allow list.
         */
        public function is_request_exempt_from_maintenance($request_uri = null, $page_id = null)
        {
            $request_uri = $request_uri ?? (isset($_SERVER['REQUEST_URI']) ? sanitize_text_field(wp_unslash($_SERVER['REQUEST_URI'])) : '/');
            $url_parts = explode('/', $request_uri);

            if (in_array('wp-admin', $url_parts, true)) {
                return true;
            }

            if (current_user_can(apply_filters('hkdev_user_can', 'manage_options'))) {
                return true;
            }

            $hkdev_mm_options = $this->get_admin_options();

            if ($hkdev_mm_options['enable_mm'] !== 'YES') {
                return true;
            }

            if (isset($hkdev_mm_options['exclude_pages']) && is_array($hkdev_mm_options['exclude_pages'])) {
                $page_id = $page_id ?? $this->hkdev_get_page_id();
                if (in_array($page_id, $hkdev_mm_options['exclude_pages'], true)) {
                    return true;
                }
            }

            global $wpdb;
            $valid_ips = array();
            $valid_class_cs = array();
            $valid_aks = array();

            $tbl = esc_sql($wpdb->prefix . $this->admin_options_name . '_unrestricted_ips');
            $ips = $wpdb->get_results($wpdb->prepare("SELECT ip_address FROM %i WHERE active = %d", $tbl, 1), OBJECT);
            if ($ips && is_array($ips)) {
                foreach ($ips as $ip) {
                    $candidate_ip = isset($ip->ip_address) ? sanitize_text_field(wp_unslash($ip->ip_address)) : '';
                    if (!$candidate_ip || !filter_var($candidate_ip, FILTER_VALIDATE_IP)) {
                        continue;
                    }

                    $ip_parts = explode('.', $candidate_ip);

                    if (count($ip_parts) === 4 && $ip_parts[3] === '*') {
                        $valid_class_cs[] = $ip_parts[0] . '.' . $ip_parts[1] . '.' . $ip_parts[2];
                    } else {
                        $valid_ips[] = $candidate_ip;
                    }
                }
            }

            $tbl = esc_sql($wpdb->prefix . $this->admin_options_name . '_access_keys');
            $aks = $wpdb->get_results($wpdb->prepare("SELECT access_key FROM %i WHERE active = %d", $tbl, 1), OBJECT);
            if ($aks && is_array($aks)) {
                foreach ($aks as $ak) {
                    $valid_aks[] = $ak->access_key;
                }
            }

            if (isset($_GET['hkdev_temp_access_key']) && trim((string) wp_unslash($_GET['hkdev_temp_access_key'])) !== '') {
                $temp_access_key = sanitize_text_field(wp_unslash($_GET['hkdev_temp_access_key']));
                if (in_array($temp_access_key, $valid_aks, true)) {
                    return true;
                }
            }

            if (isset($_COOKIE['hkdev_mm_access_key']) && trim((string) wp_unslash($_COOKIE['hkdev_mm_access_key'])) !== '') {
                if (in_array(wp_unslash($_COOKIE['hkdev_mm_access_key']), $valid_aks, true)) {
                    return true;
                }
            }

            $user_ip = $this->get_user_ip();
            if ($user_ip && in_array($user_ip, $valid_ips, true)) {
                return true;
            }

            if ($user_ip) {
                $ip_parts = explode('.', $user_ip);
                $user_class_c = (count($ip_parts) >= 3) ? $ip_parts[0] . '.' . $ip_parts[1] . '.' . $ip_parts[2] : '';
                if (in_array($user_class_c, $valid_class_cs, true)) {
                    return true;
                }
            }

            return false;
        }

        /**
         * Main request flow for maintenance mode.
         *
         * This method checks the current request, applies cache-protection headers,
         * validates access keys and IPs, and stops the request by rendering the
         * maintenance page when the visitor should be blocked.
         */
        public function process_redirect()
        {
            global $wpdb;
            $valid_ips      = array();
            $valid_class_cs = array();
            $valid_aks      = array();
            $hkdev_matches  = apply_filters('hkdev_matches', array());
            // set cookie if needed
            if (isset($_GET['hkdev_temp_access_key']) && trim((string) wp_unslash($_GET['hkdev_temp_access_key'])) !== '') {
                // get valid access keys
                $tbl = esc_sql($wpdb->prefix . $this->admin_options_name . '_access_keys');
                $aks = $wpdb->get_results($wpdb->prepare("SELECT access_key FROM %i WHERE active = %d", $tbl, 1), OBJECT);

                if ($aks) {
                    $valid_aks = array_map(function($ak) {
                        return $ak->access_key;
                    }, $aks);

                    $temp_access_key = sanitize_text_field(wp_unslash($_GET['hkdev_temp_access_key']));

                    if (in_array($temp_access_key, $valid_aks)) {
                        $hkdev_mm_cookie_time = time() + (60 * 60 * 24 * 365);
                        setcookie('hkdev_mm_access_key', $temp_access_key, $hkdev_mm_cookie_time, '/', '', true, true);
                        $_COOKIE['hkdev_mm_access_key'] = $temp_access_key;
                    }
                }
            }
 
            // Get plugin options
            $hkdev_mm_options = $this->get_admin_options();

            // Skip admin pages by default
            $request_uri = isset($_SERVER['REQUEST_URI']) ? sanitize_text_field(wp_unslash($_SERVER['REQUEST_URI'])) : '/';
            $url_parts = explode('/', $request_uri);

            if (in_array('wp-admin', $url_parts, true)) {
                $hkdev_matches[] = "<!-- WPHKDEV_MM: SKIPPING ADMIN -->";
            } else {

                /*
                 * Check if the current request should be exempt from maintenance mode.
                 * This includes checks for admin users, excluded pages, valid access keys,
                 * and allowed IP addresses.
                 */
                if (current_user_can(apply_filters('hkdev_user_can', 'manage_options'))) {
                    $hkdev_matches[] = "<!-- WPHKDEV_MM: USER IS ADMIN -->";
                } else {

                    // If page excluded
                    if (isset($hkdev_mm_options['exclude_pages']) && in_array($this->hkdev_get_page_id(), $hkdev_mm_options['exclude_pages'])) {
                        $hkdev_matches[] = "<!-- WPHKDEV_MM: PAGE EXCLUDED -->";
                    } else {

                        if ($hkdev_mm_options['enable_mm'] === 'YES') {

                            $this->prevent_page_caching_for_maintenance();

                            // Get valid unrestricted IPs
                            global $wpdb;
                            $tbl = esc_sql($wpdb->prefix . $this->admin_options_name . '_unrestricted_ips');
                            $ips = $wpdb->get_results($wpdb->prepare("SELECT ip_address FROM %i WHERE active = %d", $tbl, 1), OBJECT);

                            if ($ips && is_array($ips)) {
                                foreach ($ips as $ip) {
                                    $ip_parts = explode('.', $ip->ip_address);

                                    if ($ip_parts[3] == '*') {
                                        $valid_class_cs[] = $ip_parts[0] . '.' . $ip_parts[1] . '.' . $ip_parts[2];
                                    } else {
                                        $valid_ips[] = $ip->ip_address;
                                    }
                                }
                            }
                            
                            // Get valid access keys
                            $valid_aks = array();
                            $tbl = esc_sql($wpdb->prefix . $this->admin_options_name . '_access_keys');
                            $aks = $wpdb->get_results($wpdb->prepare("SELECT access_key FROM %i WHERE active = %d", $tbl, 1), OBJECT);
                            if ($aks && is_array($aks)) {
                                foreach ($aks as $ak) {
                                    $valid_aks[] = $ak->access_key;
                                }
                            }
                            
                            // Manage cookie filtering
                            if (isset($_COOKIE['hkdev_mm_access_key']) && trim((string) wp_unslash($_COOKIE['hkdev_mm_access_key'])) !== '') {
                                // check versus active codes
                                if (in_array(wp_unslash($_COOKIE['hkdev_mm_access_key']), $valid_aks)) {
                                    $hkdev_matches[] = "<!-- WPHKDEV_MM: COOKIE MATCH -->";
                                }
                            }

                            // Manage IP filtering
                            if (in_array($this->get_user_ip(), $valid_ips)) {
                                $hkdev_matches[] = "<!-- WPHKDEV_MM: IP MATCH -->";
                            } else {
                                // check for partial (class c) match
                                $ip_parts  = explode('.', $this->get_user_ip());
                                $user_class_c = (count($ip_parts) >= 3) ? $ip_parts[0] . '.' . $ip_parts[1] . '.' . $ip_parts[2] : '';
                                if (in_array($user_class_c, $valid_class_cs)) {
                                    $hkdev_matches[] = "<!-- WPHKDEV_MM: CLASS C MATCH -->";
                                }
                            }

                            // Check for access key in URL
                            if (count($hkdev_matches) == 0) {

                                // no match found. show maintenance page / message
                                if ($hkdev_mm_options['method'] == 'redirect') {
                                    // redirect
                                    header('HTTP/1.1 307 Temporary Redirect');
                                    header('Status: 307 Temporary Redirect');
                                    header('Retry-After: 600');
                                    header('Location:'.$hkdev_mm_options['static_page']);
                                    exit();
                                } elseif ($hkdev_mm_options['method'] == 'html') {
                                    $this->generate_maintenance_page($hkdev_mm_options['maintenance_html'], 'html');
                                } else {
                                    $this->generate_maintenance_page($hkdev_mm_options['maintenance_message'], $hkdev_mm_options['method']);
                                }

                            }

                        } else {
                            $hkdev_matches[] = "<!-- WPHKDEV_MM: REDIR DISABLED -->";
                        }
                    } // end if page excluded
                } // end if user is admin
            } // end if admin pages
        }
        //...

        /**
         * Toggle the global maintenance mode setting from the admin UI.
         */
        public function toggle_maintenance_mode()
        {
            $hkdev_mm_options = $this->get_admin_options();
            if (!current_user_can('manage_options')) wp_die("Oh no you don't!");
            check_ajax_referer('hkdev_nonce', 'security');
            $hkdev_mm_options['enable_mm'] = ($hkdev_mm_options['enable_mm'] != "YES") ? "YES" : "NO" ;
            if (update_option($this->admin_options_name, $hkdev_mm_options)) {
                // $this->print_unrestricted_ips();
                wp_send_json_success(array('status' => $hkdev_mm_options['enable_mm']));
            } else {
                wp_send_json_error(array('status' => 'error', 'message' => _x('There was an unknown error. Please reload the page.', 'Error message when toggle Maintenance Mode ', 'hkdev-maintenance-mode')));
            }
            die();
        }

        // Toggle IP status
        public function toggle_ip_status()
        {
            if (!current_user_can('manage_options')) wp_die("Oh no you don't!");
            check_ajax_referer('hkdev_nonce', 'security');
            global $wpdb;
            $tbl       = esc_sql($wpdb->prefix . $this->admin_options_name . '_unrestricted_ips');
            $ip_id     = absint(isset($_POST['hkdev_mm_ip_id']) ? wp_unslash($_POST['hkdev_mm_ip_id']) : 0);
            $ip_active = (isset($_POST['hkdev_mm_ip_active']) && (int) wp_unslash($_POST['hkdev_mm_ip_active']) === 1) ? 1 : 0;
            $rs        = $wpdb->query($wpdb->prepare("UPDATE %i SET active = %d WHERE id = %d", $tbl, $ip_active, $ip_id));
            if ($rs) {
                // $this->print_unrestricted_ips();
                wp_send_json_success(array('ip_id' => $ip_id, 'ip_active' => $ip_active));
            } else {
                wp_send_json_error(array('status' => 'error', 'message' => _x('There was an unknown database error. Please reload the page.', 'Error message when toggle IP status', 'hkdev-maintenance-mode')));
            }
            die();
        }

        // Add new IP
        public function add_new_ip()
        {
            if (!current_user_can('manage_options')) wp_die("Oh no you don't!");
            check_ajax_referer('hkdev_nonce', 'security');
            global $wpdb;
            $tbl        = esc_sql($wpdb->prefix . $this->admin_options_name . '_unrestricted_ips');
            $name       = sanitize_text_field(isset($_POST['hkdev_mm_ip_name']) ? wp_unslash($_POST['hkdev_mm_ip_name']) : '');
            $ip_address = sanitize_text_field(trim(isset($_POST['hkdev_mm_ip_ip']) ? wp_unslash($_POST['hkdev_mm_ip_ip']) : ''));
            $rs         = $wpdb->query($wpdb->prepare("INSERT INTO %i (`name`, ip_address, created_at) VALUES (%s, %s, NOW())", $tbl, $name, $ip_address));
            if ($rs) {
                // send table data
                $this->print_unrestricted_ips();
            } else {
                echo '<div class="notice notice-error" id="hkdev_mm_error_notice"><p><strong>' . esc_html(_x('Unable to add IP because of a database error. Please reload the page.', 'Error message adding new IP', 'hkdev-maintenance-mode')) . '</strong></p></div>'; 
            }
            die();
        }

        // Delete IP
        public function delete_ip()
        {
            if (!current_user_can('manage_options')) wp_die("Oh no you don't!");
            check_ajax_referer('hkdev_nonce', 'security');
            global $wpdb;
            $tbl       = esc_sql($wpdb->prefix . $this->admin_options_name . '_unrestricted_ips');
            $ip_id     = absint(isset($_POST['hkdev_mm_ip_id']) ? wp_unslash($_POST['hkdev_mm_ip_id']) : 0);
            $rs        = $wpdb->query($wpdb->prepare("DELETE FROM %i WHERE id = %d", $tbl, $ip_id));
            if ($rs) {
                $this->print_unrestricted_ips();
            } else {
                echo '<div class="notice notice-error" id="hkdev_mm_error_notice"><p><strong>' . esc_html(_x('Unable to delete IP because of a database error. Please reload the page.', 'Error message deleting IP', 'hkdev-maintenance-mode')) . '</strong></p></div>'; 
            }
            die();
        }

        // Toggle Access Key status
        public function toggle_ak_status()
        {
            if (!current_user_can('manage_options')) wp_die("Oh no you don't!");
            check_ajax_referer('hkdev_nonce', 'security');
            global $wpdb;
            $tbl       = esc_sql($wpdb->prefix . $this->admin_options_name . '_access_keys');
            $ak_id     = absint(isset($_POST['hkdev_mm_ak_id']) ? wp_unslash($_POST['hkdev_mm_ak_id']) : 0);
            $ak_active = (isset($_POST['hkdev_mm_ak_active']) && (int) wp_unslash($_POST['hkdev_mm_ak_active']) === 1) ? 1 : 0;
            $rs        = $wpdb->query($wpdb->prepare("UPDATE %i SET active = %d WHERE id = %d", $tbl, $ak_active, $ak_id));
            if ($rs) {
                // $this->print_access_keys();
                wp_send_json_success(array('ak_id' => $ak_id, 'ak_active' => $ak_active));
            } else {
                wp_send_json_error(array('status' => 'error', 'message' => _x('There was an unknown database error. Please reload the page.', 'Message error when toggle Access Key status', 'hkdev-maintenance-mode')));
            }
            die();
        }

        // Add new Access Key
        public function add_new_ak()
        {
            if (!current_user_can('manage_options')) wp_die("Oh no you don't!");
            check_ajax_referer('hkdev_nonce', 'security');
            global $wpdb;
            $tbl        = esc_sql($wpdb->prefix . $this->admin_options_name . '_access_keys');
            $name       = sanitize_text_field(isset($_POST['hkdev_mm_ak_name']) ? wp_unslash($_POST['hkdev_mm_ak_name']) : '');
            $email      = sanitize_email(isset($_POST['hkdev_mm_ak_email']) ? wp_unslash($_POST['hkdev_mm_ak_email']) : '');
            $access_key = wp_generate_password(20, false);
            $rs         = $wpdb->query($wpdb->prepare("INSERT INTO %i (`name`, email, access_key, created_at) VALUES (%s, %s, %s, NOW())", $tbl, $name, $email, $access_key));
            if ($rs) {
                // email user
                $subject = sprintf(
                    /* translators: %s: site name. */
                    esc_html_x('Access Key Link for %s', 'Access Key Email Subject, %s = name of the website/blog', 'hkdev-maintenance-mode'),
                    get_bloginfo()
                );
                $full_msg = sprintf(
                    /* translators: %s: site name. */
                    esc_html_x('The following link will provide you temporary access to %s:', 'Access Key Email Message, %s = name of the website/blog', 'hkdev-maintenance-mode'),
                    get_bloginfo()
                ) . "\n\n";
                $full_msg  .= esc_html_x('Please note that you must have cookies enabled for this to work.', 'Access Key Email Message (secong line)', 'hkdev-maintenance-mode') . "\n\n";
                $full_msg  .= get_bloginfo('url') . '?hkdev_temp_access_key=' . $access_key;
                $mail_sent  = $email ? wp_mail($email, $subject, $full_msg) : false;
                echo ($mail_sent) ? '<!-- SEND_SUCCESS -->'.PHP_EOL : '<!-- SEND_FAILURE -->'.PHP_EOL;
                // send table data
                $this->print_access_keys();
            } else {
                echo '<div class="notice notice-error" id="hkdev_mm_error_notice"><p><strong>' . esc_html(_x("Unable to add Access Key because of a database error. Please reload the page.", 'Message error when unable to add Access Key', 'hkdev-maintenance-mode')) . '</strong></p></div>'; 
            }
            die();
        }

        // Delete Access Key
        public function delete_ak()
        {
            if (!current_user_can('manage_options')) wp_die("Oh no you don't!");
            check_ajax_referer('hkdev_nonce', 'security');
            global $wpdb;
            $tbl       = esc_sql($wpdb->prefix . $this->admin_options_name . '_access_keys');
            $ak_id     = absint(isset($_POST['hkdev_mm_ak_id']) ? wp_unslash($_POST['hkdev_mm_ak_id']) : 0);
            $rs        = $wpdb->query($wpdb->prepare("DELETE FROM %i WHERE id = %d", $tbl, $ak_id));
            if ($rs) {
                $this->print_access_keys();
            } else {
                echo '<div class="notice notice-error" id="hkdev_mm_error_notice"><p><strong>' . esc_html(_x('Unable to delete Access Key because of a database error. Please reload the page.', 'Message error when unable to delete Access Key', 'hkdev-maintenance-mode')) . '</strong></p></div>'; 
            }
            die();
        }
 
        // Resend Access Key email
        public function resend_ak()
        {
            if (!current_user_can('manage_options')) wp_die("Oh no you don't!");
            check_ajax_referer('hkdev_nonce', 'security');
            global $wpdb;
            $tbl       = esc_sql($wpdb->prefix . $this->admin_options_name . '_access_keys');
            $ak_id     = absint(isset($_POST['hkdev_mm_ak_id']) ? wp_unslash($_POST['hkdev_mm_ak_id']) : 0);
            $ak        = $wpdb->get_row($wpdb->prepare("SELECT * FROM %i WHERE id = %d", $tbl, $ak_id));
            if ($ak) {
                $subject = sprintf(
                    /* translators: %s: site name. */
                    esc_html_x('Access Key Link for %s', 'Resend Access Key Email Subject, %s = name of the website/blog', 'hkdev-maintenance-mode'),
                    get_bloginfo()
                );
                $full_msg = sprintf(
                    /* translators: %s: site name. */
                    esc_html_x('The following link will provide you temporary access to %s:', 'Resend Access Key Email Message, %s = name of the website/blog', 'hkdev-maintenance-mode'),
                    get_bloginfo()
                ) . "\n\n";
                $full_msg  .= esc_html_x('Please note that you must have cookies enabled for this to work.', 'Resend Access Key Email Message (secong line)', 'hkdev-maintenance-mode') . "\n\n";
                $full_msg  .= get_bloginfo('url') . '?hkdev_temp_access_key=' . $ak->access_key;
                $mail_sent  = wp_mail($ak->email, $subject, $full_msg);
                if  ($mail_sent) {
                    wp_send_json_success(array('status' => 'success'));
                } else {
                    wp_send_json_error(array('status' => 'error', 'message' => _x('There was an unknown error sending the email. Please reload the page.', 'Error message sending Access Key email', 'hkdev-maintenance-mode')));
                }
            } else {
                wp_send_json_error(array('status' => 'error', 'message' =>  _x("Unable to add Access Key because of a database error. Please reload the page.", 'Message error when unable to add Access Key', 'hkdev-maintenance-mode')));
            }
            die();
        }

        // Generate IP table data
        private function print_unrestricted_ips()
        {
            global $wpdb;
            ?>
            <table class="widefat fixed" cellspacing="0">
                <thead>
                    <tr>
                        <th class="column-hkdev-ip-name"><?php esc_html_e("Name", 'hkdev-maintenance-mode'); ?></th>
                        <th class="column-hkdev-ip-ip"><?php esc_html_e("IP", 'hkdev-maintenance-mode'); ?></th>
                        <th class="column-hkdev-ip-active"><?php esc_html_e("Active", 'hkdev-maintenance-mode'); ?></th>
                        <th class="column-hkdev-actions"><?php esc_html_e("Actions", 'hkdev-maintenance-mode'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $tbl = esc_sql($wpdb->prefix . $this->admin_options_name . '_unrestricted_ips');
                    $ips = $wpdb->get_results($wpdb->prepare("SELECT * FROM %i ORDER BY `name`", $tbl), OBJECT);
                    $ip_row_class = 'alternate';
                    if ($ips) {
                        foreach ($ips as $ip) {
                            ?>
                            <tr id="wphkdev-ip-<?php echo esc_attr($ip->id); ?>" valign="middle" class="<?php echo esc_attr($ip_row_class); ?>">
                                <td class="column-hkdev-ip-name"><?php echo esc_html($ip->name); ?></td>
                                <td class="column-hkdev-ip-ip"><?php echo esc_html($ip->ip_address); ?></td>
                                <td class="column-hkdev-ip-active" id="hkdev_mm_ip_status_<?php echo esc_attr($ip->id); ?>"><?php echo ($ip->active == 1) ? '<span class="actived">' . esc_html(__('Yes', 'hkdev-maintenance-mode')) . '</span>' : '<span class="deactived">' . esc_html(__( 'No', 'hkdev-maintenance-mode')) . '</span>'; ?></td>
                                <td class="column-hkdev-actions">
                                    <span class='edit' id="hkdev_mm_ip_status_<?php echo esc_attr($ip->id); ?>_action">
                                        <?php if ($ip->active == 1) { ?>
                                            <a href="javascript:hkdev_mm_toggle_ip(0,<?php echo esc_attr($ip->id); ?>);"><?php esc_html_e('Disable', 'hkdev-maintenance-mode'); ?></a> |
                                        <?php } else { ?>
                                            <a href="javascript:hkdev_mm_toggle_ip(1,<?php echo esc_attr($ip->id); ?>);"><?php esc_html_e('Enable', 'hkdev-maintenance-mode'); ?></a> |
                                        <?php } ?>
                                    </span>
                                    <span class='delete'>
                                        <a class='submitdelete' href="javascript:hkdev_mm_delete_ip(<?php echo esc_attr($ip->id); ?>,'<?php echo esc_attr(wp_slash($ip->ip_address)); ?>');" ><?php esc_html_e("Delete", 'hkdev-maintenance-mode'); ?></a>
                                    </span>
                                </td>
                            </tr>
                            <?php
                            $ip_row_class = ($ip_row_class == '') ? 'alternate' : '';
                        }
                    } else {
                        ?>
                        <tr valign="middle" class="alternate">
                            <td class="no-results" colspan="4"><?php esc_html_e("No IPs found!", 'hkdev-maintenance-mode'); ?></td>
                        </tr>
                    <?php } ?>

                </tbody>
                <tfoot>
                    <tr id="wphkdev-ip-NEW" valign="middle">
                        <td class="column-hkdev-ip-name">
                            <input class="hkdev_mm_disabled_field" type="text" id="hkdev_mm_new_ip_name" name="hkdev_mm_new_ip_name" placeholder="<?php esc_html_e("Enter Name:", 'hkdev-maintenance-mode'); ?>">
                        </td>
                        <td class="column-hkdev-ip-ip">
                            <input class="hkdev_mm_disabled_field" type="text" id="hkdev_mm_new_ip_ip" name="hkdev_mm_new_ip_ip" placeholder="<?php esc_html_e("Enter IP:", 'hkdev-maintenance-mode'); ?>">
                        </td>
                        <td class="column-hkdev-ip-active">
                            <button type="button" class="button button-secondary hkdev-mm-inline-button" id="hkdev_mm_add_ip_link" onclick="hkdev_mm_add_new_ip();">
                                <?php esc_html_e("Add New IP", 'hkdev-maintenance-mode'); ?>
                            </button>
                        </td>
                        <td class="column-hkdev-actions">&nbsp;</td>
                    </tr>
                </tfoot>
            </table>
            <?php
        }

        // Generate Access Key table data
        private function print_access_keys()
        {
            global $wpdb;
            ?>
            <table class="widefat fixed" cellspacing="0">
                <thead>
                    <tr>
                        <th class="column-hkdev-ak-name"><?php esc_html_e("Name", 'hkdev-maintenance-mode'); ?></th>
                        <th class="column-hkdev-ak-email"><?php esc_html_e("Email", 'hkdev-maintenance-mode'); ?></th>
                        <th class="column-hkdev-ak-key"><?php esc_html_e("Access Key", 'hkdev-maintenance-mode'); ?></th>
                        <th class="column-hkdev-ak-active" style="width: 10%;white-space: nowrap;"><?php esc_html_e("Active", 'hkdev-maintenance-mode'); ?></th>
                        <th class="column-hkdev-actions"><?php esc_html_e("Actions", 'hkdev-maintenance-mode'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $tbl   = esc_sql($wpdb->prefix . $this->admin_options_name . '_access_keys');
                    $codes = $wpdb->get_results($wpdb->prepare("SELECT * FROM %i ORDER BY name", $tbl), OBJECT);
                    $ak_row_class = 'alternate';
                    if ($codes) {
                        foreach ($codes as $code) { 
                            ?>
                            <tr id="wphkdev-ak-<?php echo esc_attr($code->id); ?>" valign="middle" class="<?php echo esc_attr($ak_row_class); ?>">
                                <td class="column-hkdev-ak-name"><?php echo esc_html($code->name); ?></td>
                                <td class="column-hkdev-ak-email"><a href="mailto:<?php echo esc_attr($code->email); ?>" title="email <?php echo esc_attr($code->email); ?>"><?php echo esc_html($code->email); ?></a></td>
                                <td class="column-hkdev-ak-key"><?php echo esc_html($code->access_key); ?></td>
                                <td class="column-hkdev-ak-active" id="hkdev_mm_ak_status_<?php echo esc_attr($code->id); ?>"><?php echo ($code->active == 1) ? '<span class="actived">' . esc_html(__('Yes', 'hkdev-maintenance-mode')) . '</span>' : '<span class="deactived">' . esc_html(__('No', 'hkdev-maintenance-mode')) . '</span>'; ?></td>
                                <td class="column-hkdev-actions">
                                    <span class='edit' id="hkdev_mm_ak_status_<?php echo esc_attr($code->id); ?>_action">
                                        <?php if ($code->active == 1) { ?>
                                            <a href="javascript:hkdev_mm_toggle_ak(0,<?php echo esc_attr($code->id); ?>);"><?php esc_html_e('Disable', 'hkdev-maintenance-mode'); ?></a> |
                                        <?php } else { ?>
                                            <a href="javascript:hkdev_mm_toggle_ak(1,<?php echo esc_attr($code->id); ?>);"><?php esc_html_e('Enable', 'hkdev-maintenance-mode'); ?></a> |
                                        <?php } ?>
                                    </span>
                                    <span class='resend'>
                                    <?php if(!empty($code->email)) { ?><a class='submitdelete' href="javascript:hkdev_mm_resend_ak(<?php echo esc_attr($code->id); ?>,'<?php echo esc_attr(wp_slash($code->name)); ?>','<?php echo esc_attr(wp_slash($code->email)); ?>');"><?php esc_html_e('Resend Code', 'hkdev-maintenance-mode'); ?></a><?php }else{ echo '<span style="opacity:0.8;pointer-events: none;">' . esc_html__('Resend Code', 'hkdev-maintenance-mode') . '</span>'; } ?> | 
                                    </span>
                                    <?php $https = isset($_SERVER['HTTPS']) ? sanitize_text_field(wp_unslash($_SERVER['HTTPS'])) : ''; $server_port = isset($_SERVER['SERVER_PORT']) ? absint(wp_unslash($_SERVER['SERVER_PORT'])) : 0; if (($https !== '' && $https !== 'off') || $server_port === 443) { ?>
                                        <span class='copy' id="hkdev_mm_submit_copy_<?php echo esc_attr($code->id); ?>">
                                            <a class='submitdelete' href="javascript:hkdev_mm_copy_ak(<?php echo esc_attr($code->id); ?>, '<?php echo esc_attr($code->access_key); ?>');" ><?php esc_html_e('Copy', 'hkdev-maintenance-mode'); ?></a> | 
                                        </span>
                                    <?php } ?>
                                    <span class='delete'>
                                        <a class='submitdelete' href="javascript:hkdev_mm_delete_ak(<?php echo esc_attr($code->id); ?>,'<?php echo esc_attr(wp_slash($code->name)); ?>');" ><?php esc_html_e('Delete', 'hkdev-maintenance-mode'); ?></a>
                                    </span>
                                </td>
                            </tr>
                            <?php
                            $ak_row_class = ($ak_row_class == '') ? 'alternate' : '';
                        }
                    } else {
                        ?>
                        <tr valign="middle" class="alternate">
                            <td class="no-results" colspan="5"><?php esc_html_e("No Access Codes found!", 'hkdev-maintenance-mode'); ?></td>
                        </tr>
                        <?php
                    }
                    ?>
                </tbody>
                <tfoot>
                    <tr id="wphkdev-ak-NEW" valign="middle">
                        <td class="column-hkdev-ak-name">
                            <input class="hkdev_mm_disabled_field" type="text" id="hkdev_mm_new_ak_name" name="hkdev_mm_new_ak_name" placeholder="<?php esc_html_e("Enter Name:", 'hkdev-maintenance-mode'); ?>">
                        </td>
                        <td class="column-hkdev-ak-email">
                            <input class="hkdev_mm_disabled_field" type="email" id="hkdev_mm_new_ak_email" name="hkdev_mm_new_ak_email" placeholder="<?php esc_html_e("Enter Email:", 'hkdev-maintenance-mode'); ?>">
                        </td>
                        <td class="column-hkdev-ak-key">
                            <button type="button" class="button button-secondary hkdev-mm-inline-button" id="hkdev_mm_add_ak_link" onclick="hkdev_mm_add_new_ak();">
                                <?php esc_html_e("Add New Access Key", 'hkdev-maintenance-mode'); ?>
                            </button>
                        </td>
                        <td class="column-hkdev-ak-active">&nbsp;</td>
                        <td class="column-hkdev-actions">&nbsp;</td>
                    </tr>
                </tfoot>
            </table>
            <?php
        }

        // Display redirect status if active
        public function display_status_if_active()
        {
            $hkdev_mm_options = $this->get_admin_options();
            $show_notice = false;
            
            if ($hkdev_mm_options['enable_mm'] == 'YES') {
                $show_notice = true;
            }
            
            $status_title = $show_notice ? _x('Maintenance ON', 'Admin bar indicator', 'hkdev-maintenance-mode') : _x('Maintenance OFF', 'Admin bar indicator', 'hkdev-maintenance-mode');
            $class_name = $show_notice ? 'hkdev-indicator--enabled' : '';

            echo '<script>
                jQuery(function($) {
                    var $indicator = $(".hkdev-indicator");
                    if (' . ($show_notice ? 'true' : 'false') . ') {
                        $indicator.addClass("hkdev-indicator--enabled");
                    } else {
                        $indicator.removeClass("hkdev-indicator--enabled");
                    }
                    $indicator.find(".hkdev-indicator-title").attr("title", ' . wp_json_encode($status_title) . ');
                });
            </script>';
        }

        //Admin bar indicator
        public function ab_indicator(&$wp_admin_bar)
        {
            if (!is_admin_bar_showing() || !is_blog_admin()) { return; }
            //$enabled = apply_filters('hkdev_admin_bar_indicator_enabled', $enabled = true);
            if (!current_user_can('delete_plugins')) { return false; }
            //if (!$enabled) { return false; }
            $hkdev_mm_options = $this->get_admin_options();
            $is_enabled = $hkdev_mm_options['enable_mm'];
            if ($is_enabled=='YES') {
                $status = _x('Maintenance ON', 'Admin bar indicator', 'hkdev-maintenance-mode');
            } else {
                $status = _x('Maintenance OFF', 'Admin bar indicator', 'hkdev-maintenance-mode');
            }
            $indicatorClasses = ($is_enabled=='YES') ? 'hkdev-indicator hkdev-indicator--enabled' : 'hkdev-indicator';
            $wp_admin_bar->add_menu(
                array(
                    'id' => 'hkdev-indicator',
                    'title' => '<span class="icon_mm"><svg xmlns="http://www.w3.org/2000/svg" xmlns:bx="https://boxy-svg.com" version="1.0" viewBox="0 0 235 215"><defs/><g bx:origin="0 0"><path d="M33 132l30 32c4 4 6 8 5 14-3 10-15 14-23 7L3 140c-4-5-4-12 1-17l40-44c6-6 14-6 20-1s6 14 0 20l-31 34zM202 131l-30-32-5-9c-1-6 2-11 7-14 5-2 12-2 16 2l42 44c4 5 4 13-1 18l-40 44c-6 6-14 6-20 1-5-5-6-14-1-20l32-34zM82 212l7 2c8 3 14-1 17-12l34-116c16-2 31-13 36-30 5-18-1-38-16-48l-12 39-27-8 12-39c-18 1-34 13-40 31-5 17 0 35 13 45L73 191c-2 10-2 18 9 21z"/></g></svg></span>',
                    'parent' => false,
                    'href' => get_admin_url(null, 'options-general.php?page=hkdev_Maintenance_Mode'),
                    'meta' => array(
                        'title' => $status,
                        'class' => $indicatorClasses
                    )
                )
            );
            //$wp_admin_bar->add_node($indicator);
        }

        // Indicator styles
        public function ab_indicator_style()
        {
            echo '<style type="text/css">
            #wp-admin-bar-hkdev-indicator.hkdev-indicator--enabled { background: rgba(159, 0, 0, 1) }
            .icon_mm{
                float:left;
                width:22px !important;
                height:22px !important;
                margin: 4px 0 !important;
            }
            .icon_mm svg{
                fill: rgba(255,255,255,.6) !important;
            }
            #wp-admin-bar-hkdev-indicator:hover .icon_mm svg {
                fill: #00b9eb !important;
            }</style>';
        }

        // Plugin action links
        public function action_links($links)
        {
           $links[] = '<a href="' . get_admin_url(null, 'options-general.php?page=hkdev_Maintenance_Mode') . '">' . esc_html(_x('Settings', 'Plugin Settings link','hkdev-maintenance-mode')) . '</a>';
           return $links;
        }

        // Create the admin page
        public function print_admin_page()
        {
            global $ajax_nonce;
            $ajax_nonce = wp_create_nonce('hkdev_nonce');
            $hkdev_mm_options = $this->get_admin_options();
            $maintenance_message_value = html_entity_decode(wp_unslash($hkdev_mm_options['maintenance_message']), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $maintenance_html_value = html_entity_decode(wp_unslash($hkdev_mm_options['maintenance_html']), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

            // Process update
            if (isset($_POST['update_wp_maintenance_mode_settings'])) {

                check_admin_referer('hkdev_nonce');  

                function shapeSpace_allowed_html() {
                    $allowed_tags = array(
                        'a' => array(
                            'class' => array(),
                            'href'  => array(),
                            'rel'   => array(),
                            'title' => array(),
                            'id' => array(),
                            'target' => array(),
                            'style' => array(),
                        ),
                        'abbr' => array(
                            'title' => array(),
                        ),
                        'b' => array(),
                        'br' => array(),
                        'body' => array(
                            'class' => array(),
                            'style' => array(),
                        ),
                        'blockquote' => array(
                            'cite'  => array(),
                        ),
                        'cite' => array(
                            'title' => array(),
                        ),
                        'code' => array(),
                        'del' => array(
                            'datetime' => array(),
                            'title' => array(),
                        ),
                        'dd' => array(),
                        'div' => array(
                            'id' => array(),
                            'class' => array(),
                            'title' => array(),
                            'style' => array(),
                            'data-*' => array(),
                        ),
                        'dl' => array(),
                        'dt' => array(),
                        'em' => array(),
                        'h1' => array(),
                        'h2' => array(),
                        'h3' => array(),
                        'h4' => array(),
                        'h5' => array(),
                        'h6' => array(),
                        'head' => array(),
                        'link' => array(
                            'href' => array(),
                            'rel' => array(),
                            'type' => array(),
                        ),
                        '!DOCTYPE' => array(
                            'html' => array(),
                        ),
                        'html' => array(
                            'class' => array(),
                            'lang' => array(),
                            'style' => array(),
                        ),
                        'nav' => array(
                            'id' => array(),
                            'class' => array(),
                        ),
                        'main' => array(
                            'id' => array(),
                            'class' => array(),
                        ),
                        'meta' => array(
                            'charset' => array(),
                            'name' => array(),
                            'content' => array(),
                            'http-equiv' => array(),
                        ),
                        'i' => array(
                            'class'  => array(),
                        ),
                        'img' => array(
                            'id' => array(),
                            'alt'    => array(),
                            'class'  => array(),
                            'height' => array(),
                            'src'    => array(),
                            'width'  => array(),
                            'data-*' => array(),
                            'style' => array(),
                        ),
                        'li' => array(
                            'class' => array(),
                            'style' => array(),
                        ),
                        'ol' => array(
                            'id' => array(),
                            'class' => array(),
                            'style' => array(),
                        ),
                        'ul' => array(
                            'id' => array(),
                            'class' => array(),
                            'style' => array(),
                        ),
                        'p' => array(
                            'id' => array(),
                            'class' => array(),
                            'style' => array(),
                        ),
                        'title' => array(),
                        'q' => array(
                            'cite' => array(),
                            'title' => array(),
                        ),
                        'script' => array(),
                        'span' => array(
                            'id' => array(),
                            'class' => array(),
                            'title' => array(),
                            'style' => array(),
                            'data-*' => array(),
                        ),
                        'style' => array(
                            'type' => array(),
                        ),
                        'section' => array(
                            'id' => array(),
                            'class' => array(),
                            'style' => array(),
                        ),
                        'strike' => array(),
                        'strong' => array(),
                        'ul' => array(
                            'id' => array(),
                            'class' => array(),
                        ),
                        'video' => array(
                            'id' => array(),
                            'class' => array(),
                            'autoplay' => array(),
                            'controls' => array(),
                            'height' => array(),
                            'loop' => array(),
                            'muted' => array(),
                            'poster' => array(),
                            'preload' => array(),
                            'src' => array(),
                            'width' => array(),
                        ),
                    );
                    return $allowed_tags;
                }
                $allowed_html = shapeSpace_allowed_html();

                // Prepare options
                $hkdev_mm_options['header_type']         = isset($_POST['hkdev_mm_header_type']) ? sanitize_text_field(trim($_POST['hkdev_mm_header_type'])) : '';
                $hkdev_mm_options['static_page']         = isset($_POST['hkdev_mm_static_page']) ? esc_url_raw(trim($_POST['hkdev_mm_static_page'])) : '';
                $hkdev_mm_options['method']              = isset($_POST['hkdev_mm_method']) ? sanitize_text_field(trim($_POST['hkdev_mm_method'])) : '';
                $hkdev_mm_options['maintenance_title']   = isset($_POST['hkdev_mm_maintenance_title']) ? sanitize_text_field(trim($_POST['hkdev_mm_maintenance_title'])) : '';
                $hkdev_mm_options['maintenance_message'] = isset($_POST['hkdev_mm_maintenance_message']) ? wp_kses_post($_POST['hkdev_mm_maintenance_message']) : '';
                $hkdev_mm_options['maintenance_html']    = isset($_POST['hkdev_mm_maintenance_html']) ? wp_kses($_POST['hkdev_mm_maintenance_html'], $allowed_html) : '';
                $hkdev_mm_options['exclude_pages']       = isset($_POST['hkdev_mm_exclude_pages']) ? array_map('esc_attr', (array) $_POST['hkdev_mm_exclude_pages']) : array();
                
                
                // Update options
                update_option($this->admin_options_name, $hkdev_mm_options);
                echo '<div class="notice notice-success is-dismissible"><p><strong>' . esc_html(_x("Settings Updated", 'Notice when settings updated', 'hkdev-maintenance-mode')) . '</strong></p></div>';

            } ?>

            <script type="text/javascript" charset="utf-8">
                var hkdev_mm_codeEditor_is_ini = false;

                // Bind actions
                jQuery(document).ready(function($) {

                    // Sleep time expects milliseconds (js)
                    window.hkdev_sleep = function(time) {
                        return new Promise((resolve) => setTimeout(resolve, time));
                    }

                    // Active tab
                    <?php if (isset($_POST['activeTab'])) {
                        $activeTab = esc_attr($_POST['activeTab']);
                        echo '$(".nav-tab-wrapper a").removeClass("nav-tab-active");
                        $(".hkdev_mm_admin_section").addClass("hidden");
                        $("a[href=\'' . esc_js($activeTab) . '\']").addClass("nav-tab-active");
                        $("' . esc_js($activeTab) . '").removeClass("hidden");';
                        
                        if ($activeTab == '#unrestricted-ip' || $activeTab == '#access-keys') { 
                            echo "$('#hkdev_submit_button').hide();";
                        } else {
                            echo "$('#hkdev_submit_button').show();";
                        }

                    } ?>

                    // Method mode toggle
                    $('#hkdev_mm_method').on("change", function() { 
                        hkdev_mm_toggle_method_options(); 
                    });

                    // Nav tabs settings
                    $(".nav-tab-wrapper a").on("click",function() {
                        event.preventDefault();
                        $(".nav-tab-wrapper a").removeClass("nav-tab-active");
                        $(this).addClass("nav-tab-active");
                        $(".hkdev_mm_admin_section").addClass("hidden");
                        var activeTab = $(this).attr("href");
                        $(activeTab).removeClass("hidden");
                        $('#activeTab').val(activeTab);
                        if ($('#hkdev_method_html').is(":visible") && activeTab == '#maintenance-message' && !hkdev_mm_codeEditor_is_ini) {
                            hkdev_mm_ini_codeEditor ();
                        }
                        if (activeTab == '#unrestricted-ip'||activeTab == '#access-keys') { 
                            $('#hkdev_submit_button').hide();
                        } else {
                            $('#hkdev_submit_button').show();
                        }
                    });

                    if ($('#hkdev_method_html').is(":visible") && !hkdev_mm_codeEditor_is_ini) {
                        hkdev_mm_ini_codeEditor ();
                    }

                    // Multiple select with AJAX search
                    $('#hkdev_mm_exclude_pages').select2({
                        ajax: {
                            url: ajaxurl,
                            dataType: 'json',
                            delay: 250,
                            data: function (params) {
                                return {
                                    q: params.term,
                                    action: 'hkdev_mm_getposts',
                                    security: '<?php echo esc_js($ajax_nonce); ?>'
                                };
                            },
                            processResults: function(data) {
                                var options = [];
                                if (data) {
                                    $.each(data, function(index, text) {
                                        options.push({ id: text[0], text: text[1] });
                                    });
                                }
                                return {
                                    results: options
                                };
                            },
                            //cache: true
                        },
                        multiple: true,
                        minimumInputLength: 3, // the minimum of symbols to input before perform a search
                        language: {
                            errorLoading: function () {
                                return '<?php esc_html_e("The results could not be loaded.", "hkdev-maintenance-mode") ?>';
                            },
                            inputTooLong: function (args) {
                                var overChars = args.input.length - args.maximum;
                                var message = '<?php /* translators: %s: number of characters. */ echo esc_html(sprintf(_x("Please delete %s characters", "Plural", "hkdev-maintenance-mode"), "{overChars}")) ?>';
                                if (overChars == 1) {
                                    message = '<?php /* translators: %s: number of characters. */ echo esc_html(_x("Please delete 1 character", "Singular", "hkdev-maintenance-mode")) ?>';
                                }
                                return message.replace('{overChars}',overChars);
                            },
                            inputTooShort: function (args) {
                                var remainingChars = args.minimum - args.input.length;
                                var message = '<?php /* translators: %s: number of characters. */ echo esc_html(sprintf(__("Please enter %s or more characters", "hkdev-maintenance-mode"), "{remainingChars}")) ?>';
                                return message.replace('{remainingChars}',remainingChars);
                            },
                            loadingMore: function () {
                            return '<?php esc_html_e("Loading more results…", "hkdev-maintenance-mode") ?>';
                            },
                            maximumSelected: function (args) {
                                var message = '<?php /* translators: %s: number of items. */ echo esc_html(sprintf(_x("You can only select %s items", "Plural", "hkdev-maintenance-mode"), "{args.maximum}")) ?>';
                                if (args.maximum == 1) {
                                    message = '<?php /* translators: %s: number of items. */ echo esc_html(_x("You can only select 1 item", "Singular", "hkdev-maintenance-mode")) ?>';
                                }
                                return message.replace('{args.maximum}',args.maximum);
                            },
                            noResults: function () {
                                return '<?php esc_html_e("No results found", "hkdev-maintenance-mode") ?>';
                            },
                            searching: function () {
                            return '<?php esc_html_e("Searching…", "hkdev-maintenance-mode") ?>';
                            },
                            removeAllItems: function () {
                                return '<?php esc_html_e("Remove all items", "hkdev-maintenance-mode") ?>';
                            },
                            removeItem: function () {
                                return '<?php esc_html_e("Remove item", "hkdev-maintenance-mode") ?>';
                            },
                            search: function() {
                                return '<?php esc_html_e("Search", "hkdev-maintenance-mode") ?>';
                            }
                        }
                    });
                });

                // ini wp code editor
                function hkdev_mm_ini_codeEditor () {
                    wp.codeEditor.initialize(jQuery('#hkdev_mm_maintenance_html'), cm_settings);
                    hkdev_mm_codeEditor_is_ini = true;
                }

                // Update form layout based on method option (js) 
                function hkdev_mm_toggle_method_options () {
                    if (jQuery('#hkdev_mm_method').val() == 'redirect') {
                        jQuery('#hkdev_method_message').hide();
                        jQuery('#hkdev_method_html').hide();
                        jQuery('#hkdev_method_redirect').show();
                    }else 
                    if (jQuery('#hkdev_mm_method').val() == 'html') {
                        jQuery('#hkdev_method_message').hide();
                        jQuery('#hkdev_method_html').show();
                        jQuery('#hkdev_method_redirect').hide();
                        if (!hkdev_mm_codeEditor_is_ini) {
                            hkdev_mm_ini_codeEditor ();
                        }
                    } else {
                        jQuery('#hkdev_method_message').show();
                        jQuery('#hkdev_method_html').hide();
                        jQuery('#hkdev_method_redirect').hide();
                    }
                }

                // Undim field (js)
                function hkdev_mm_undim_field(field_id, default_text) {
                    if (jQuery('#'+field_id).val() == default_text) jQuery('#'+field_id).val('');
                    jQuery('#'+field_id).css('color','#000');
                }

                // Dim field (js)
                function hkdev_mm_dim_field(field_id, default_text) {
                    if (jQuery('#'+field_id).val() == '') {
                        jQuery('#'+field_id).val(default_text);
                        jQuery('#'+field_id).css('color','#888');
                    }
                }

                // Validate IP4 address (js)
                function ValidateIPaddress(ipaddress) {
                    if (/^(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.((25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.((25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)|\*))$/.test(ipaddress)) {  
                        return (true)
                    }
                }

                // Toggle maintenance mode (js)
                function hkdev_mm_toggle_maintenance_mode () {
                    // prepare ajax data
                    var data = {
                        action: 'hkdev_mm_toggle_maintenance_mode',
                        security: '<?php echo esc_js($ajax_nonce); ?>'
                    };

                    // Set status to loading (js)
                    //jQuery('#hkdev_mm_toggle-wrap').after('<span style="visibility:visible; float:none;" class="spinner switch_spinner"></span>');

                    // Send ajax request
                    jQuery.post(ajaxurl, data, function(response) {
                        if (response.success) {
                            if (response.data.status != 'YES') {
                                // disabled
                                jQuery(".hkdev-switch").removeClass('on');
                                jQuery(".hkdev-switch span.on").removeClass('checked');
                                jQuery(".hkdev-switch span.off").addClass('checked');
                                jQuery(".hkdev-indicator").removeClass("hkdev-indicator--enabled");
                                jQuery(".hkdev-indicator .hkdev-indicator-title").attr("title", "<?php echo esc_attr_x('Maintenance OFF', 'Admin bar indicator', 'hkdev-maintenance-mode'); ?>");
                            } else {
                                // active
                                jQuery(".hkdev-switch").addClass('on');
                                jQuery(".hkdev-switch span.on").addClass('checked');
                                jQuery(".hkdev-switch span.off").removeClass('checked');
                                jQuery(".hkdev-indicator").addClass("hkdev-indicator--enabled");
                                jQuery(".hkdev-indicator .hkdev-indicator-title").attr("title", "<?php echo esc_attr_x('Maintenance ON', 'Admin bar indicator', 'hkdev-maintenance-mode'); ?>");
                            }
                        } else {
                            jQuery('.ajax_notices').html(
                                '<div class="notice notice-error is-dismissible"><p>' +
                                (response.data.message ? response.data.message : '<?php echo esc_js(__("There was a database error. Please try reload this page", 'hkdev-maintenance-mode')); ?>') +
                                '</p><button type="button" class="notice-dismiss"><span class="screen-reader-text"><?php echo esc_js(__("Dismiss this notice", 'hkdev-maintenance-mode')); ?></span></button></div>'
                            ).autoDismiss();
                        }
                        //jQuery('.switch_spinner').remove();
                    });
                }

                // Add new IP (js)
                function hkdev_mm_add_new_ip () {
                    // Validate entries before posting ajax call
                    var error_msg = '';
                    if (jQuery('#hkdev_mm_new_ip_name').val() == '') error_msg += '<?php echo esc_js(__("You must enter a Name", 'hkdev-maintenance-mode')); ?>.<br>';
                    if (jQuery('#hkdev_mm_new_ip_ip').val() == '') error_msg += '<?php echo esc_js(__("You must enter an IP", 'hkdev-maintenance-mode')); ?>.<br>';
                    if (ValidateIPaddress(jQuery('#hkdev_mm_new_ip_ip').val()) != true) error_msg += '<?php echo esc_js(__("IP address not valid", 'hkdev-maintenance-mode')); ?>.<br>';
                    if (error_msg != '') {
                        jQuery('.ajax_notices').html(
                            '<div class="notice notice-warning is-dismissible"><p>' +
                            '<?php echo esc_js(__("There is a problem with the information you have entered:", 'hkdev-maintenance-mode')); ?><br>' + error_msg +
                            '</p><button type="button" class="notice-dismiss"><span class="screen-reader-text"><?php echo esc_js(__("Dismiss this notice", 'hkdev-maintenance-mode')); ?></span></button></div>'
                        ).autoDismiss(8000);
                    } else {
                        // Prepare ajax data
                        var data = {
                            action: 'hkdev_mm_add_ip',
                            security: '<?php echo esc_js($ajax_nonce); ?>',
                            hkdev_mm_ip_name: jQuery('#hkdev_mm_new_ip_name').val(),
                            hkdev_mm_ip_ip: jQuery('#hkdev_mm_new_ip_ip').val() 
                        };

                        // Set section to loading 
                        jQuery('#hkdev_mm_ip_tbl_container').html('<span style="visibility:visible; float:none;" class="spinner"></span>');

                        // Send ajax request
                        jQuery.post(ajaxurl, data, function(response) {
                            jQuery('#hkdev_mm_ip_tbl_container').html(response);
                        });
                    }
                }

                // Toggle IP status (js)
                function hkdev_mm_toggle_ip (status, ip_id) {
                    // prepare ajax data
                    var data = {
                        action: 'hkdev_mm_toggle_ip',
                        security: '<?php echo esc_js($ajax_nonce); ?>',
                        hkdev_mm_ip_active: status,
                        hkdev_mm_ip_id: ip_id 
                    };

                    // Set status to loading (js)
                    jQuery('#hkdev_mm_ip_status_' + ip_id).html('<span style="visibility:visible; float:none;" class="spinner"></span>');

                    // Send ajax request
                    jQuery.post(ajaxurl, data, function(response) {
                        if (response.success) {
                            if (response.data.ip_active == '1') {
                                // active
                                jQuery('#hkdev_mm_ip_status_' + response.data.ip_id).html('<span class="actived"><?php echo esc_js(__("Yes", 'hkdev-maintenance-mode')); ?></span>');
                                jQuery('#hkdev_mm_ip_status_' + response.data.ip_id + '_action').html(`<a href="javascript:hkdev_mm_toggle_ip(0,${response.data.ip_id});"><?php echo esc_js(__("Disable", 'hkdev-maintenance-mode')); ?></a> | `);
                            } else {
                                // disabled
                                jQuery('#hkdev_mm_ip_status_' + response.data.ip_id).html('<span class="deactived"><?php echo esc_js(__("No", 'hkdev-maintenance-mode')); ?></span>');
                                jQuery('#hkdev_mm_ip_status_' + response.data.ip_id + '_action').html(`<a href="javascript:hkdev_mm_toggle_ip(1,${response.data.ip_id});"><?php echo esc_js(__("Enable", 'hkdev-maintenance-mode')); ?></a> | `);
                            }
                        } else {
                            jQuery('.ajax_notices').html(
                                '<div class="notice notice-error is-dismissible"><p>' +
                                (response.data.message ? response.data.message : '<?php echo esc_js(__("There was a database error. Please try reload this page", 'hkdev-maintenance-mode')); ?>') +
                                '</p><button type="button" class="notice-dismiss"><span class="screen-reader-text"><?php echo esc_js(__("Dismiss this notice", 'hkdev-maintenance-mode')); ?></span></button></div>'
                            ).autoDismiss();
                        }
                    });
                }
 
                // Delete IP (js)
                function hkdev_mm_delete_ip (ip_id, ip_addr) {
                    if (confirm('<?php echo esc_js(__("You are about to delete the IP address:", 'hkdev-maintenance-mode')); ?>\n\n' + ip_addr)) {

                        // prepare ajax data
                        var data = {
                            action: 'hkdev_mm_delete_ip',
                            security: '<?php echo esc_js($ajax_nonce); ?>',
                            hkdev_mm_ip_id: ip_id
                        };
                        
                        // Set section to loading
                        jQuery('#hkdev_mm_ip_tbl_container').html('<span style="visibility:visible; float:none;" class="spinner"></span>');
                        
                        // Send ajax request
                        jQuery.post(ajaxurl, data, function(response) {
                            jQuery('#hkdev_mm_ip_tbl_container').html(response);
                        });
                    }
                }

                // Add new Access Key (js)
                function hkdev_mm_add_new_ak () {
                    // validate entries before posting ajax call
                    var error_msg = '';
                    if (jQuery('#hkdev_mm_new_ak_name').val() == '') error_msg += '<?php echo esc_js(__("You must enter a Name", 'hkdev-maintenance-mode')); ?>.<br>';
                    //if (jQuery('#hkdev_mm_new_ak_email').val() == '') error_msg += '<?php echo esc_js(__("You must enter an Email", 'hkdev-maintenance-mode')); ?>.<br>';

                    // Validate email 
                    var email = jQuery('#hkdev_mm_new_ak_email').val();
                    var emailReg = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
                    if (email!=='' && !emailReg.test(email)) {
                        error_msg += '<?php echo esc_js(__("Email address not valid", "hkdev-maintenance-mode")); ?>.<br>';
                    }

                    if (error_msg != '') {
                        jQuery('.ajax_notices').html(
                            '<div class="notice notice-warning is-dismissible"><p>' +
                            '<?php echo esc_js(__("There is a problem with the information you have entered:", 'hkdev-maintenance-mode')); ?><br>' + error_msg +
                            '</p><button type="button" class="notice-dismiss"><span class="screen-reader-text"><?php echo esc_js(__("Dismiss this notice", 'hkdev-maintenance-mode')); ?></span></button></div>'
                        ).autoDismiss(8000);
                    } else {
                        // Prepare ajax data
                        var data = {
                            action: 'hkdev_mm_add_ak',
                            security: '<?php echo esc_js($ajax_nonce); ?>',
                            hkdev_mm_ak_name: jQuery('#hkdev_mm_new_ak_name').val(),
                            hkdev_mm_ak_email: jQuery('#hkdev_mm_new_ak_email').val() 
                        };

                        // Set section to loading
                        jQuery('#hkdev_mm_ak_tbl_container').html('<span style="visibility:visible; float:none;" class="spinner"></span>');

                        // Send ajax request
                        jQuery.post(ajaxurl, data, function(response) {
                            
                            jQuery('.ajax_notices').html(
                                '<div class="notice notice-success is-dismissible"><p>' +
                                ((response.indexOf('SEND_SUCCESS') !== -1) ?
                                    '<?php echo esc_js(__("Access Key added successfully and sent via email.", 'hkdev-maintenance-mode')); ?>'
                                :
                                    '<?php echo esc_js(__("Access Key added successfully.", 'hkdev-maintenance-mode')); ?>'
                                ) +
                                '</p><button type="button" class="notice-dismiss"><span class="screen-reader-text"><?php echo esc_js(__("Dismiss this notice", 'hkdev-maintenance-mode')); ?></span></button></div>'
                            ).autoDismiss(5000);
                        
                            jQuery('#hkdev_mm_ak_tbl_container').html(response);
                        });
                    }
                }

                // Toggle Access Key status (js)
                function hkdev_mm_toggle_ak (status, ak_id) {
                    // prepare ajax data
                    var data = {
                        action: 'hkdev_mm_toggle_ak',
                        security: '<?php echo esc_js($ajax_nonce); ?>',
                        hkdev_mm_ak_active: status,
                        hkdev_mm_ak_id: ak_id 
                    };

                    // Set status to loading
                    jQuery('#hkdev_mm_ak_status_' + ak_id).html('<span style="visibility:visible; float:none;" class="spinner"></span>');

                    // Send ajax request
                    jQuery.post(ajaxurl, data, function(response) {
                        if (response.success) {
                            if (response.data.ak_active == '1') {
                                // active
                                jQuery('#hkdev_mm_ak_status_' + response.data.ak_id).html('<span class="actived"><?php echo esc_js(__("Yes", 'hkdev-maintenance-mode')); ?></span>');
                                jQuery('#hkdev_mm_ak_status_' + response.data.ak_id + '_action').html(`<a href="javascript:hkdev_mm_toggle_ak(0,${response.data.ak_id});"><?php echo esc_js(__("Disable", 'hkdev-maintenance-mode')); ?></a> | `);
                            } else {
                                // disabled
                                jQuery('#hkdev_mm_ak_status_' + response.data.ak_id).html('<span class="deactived"><?php echo esc_js(__("No", 'hkdev-maintenance-mode')); ?></span>');
                                jQuery('#hkdev_mm_ak_status_' + response.data.ak_id + '_action').html(`<a href="javascript:hkdev_mm_toggle_ak(1,${response.data.ak_id});"><?php echo esc_js(__("Enable", 'hkdev-maintenance-mode')); ?></a> | `);
                            }
                        } else {
                            jQuery('.ajax_notices').html(
                                '<div class="notice notice-error is-dismissible"><p>' +
                                (response.data.message ? response.data.message : '<?php echo esc_js(__("There was a database error. Please try reload this page", 'hkdev-maintenance-mode')); ?>') +
                                '</p><button type="button" class="notice-dismiss"><span class="screen-reader-text"><?php echo esc_js(__("Dismiss this notice", 'hkdev-maintenance-mode')); ?></span></button></div>'
                            ).autoDismiss();
                        }
                    });
                }

                // Delete Access Key (js)
                function hkdev_mm_delete_ak (ak_id, ak_name) {
                    if (confirm('<?php echo esc_js(__("You are about to delete this Access Key:", 'hkdev-maintenance-mode')); ?>\n\n' + ak_name)) {
                        // prepare ajax data
                        var data = {
                            action: 'hkdev_mm_delete_ak',
                            security: '<?php echo esc_js($ajax_nonce); ?>',
                            hkdev_mm_ak_id:	ak_id
                        };

                        // Set section to loading
                        jQuery('#hkdev_mm_ak_tbl_container').html('<span style="visibility:visible; float:none;" class="spinner"></span>');

                        // Send ajax request
                        jQuery.post(ajaxurl, data, function(response) {
                            jQuery('#hkdev_mm_ak_tbl_container').html(response);
                        });
                    }
                }

                // Copy Access Key (js)
                function hkdev_mm_copy_ak (ak_id, ak_code) {
                    var savedTxt = jQuery('#hkdev_mm_submit_copy_' + ak_id + ' a').html();
                    navigator.clipboard.writeText('<?php echo esc_js(get_bloginfo('url')); ?>?hkdev_temp_access_key=' + ak_code).then(() => {
                        jQuery('#hkdev_mm_submit_copy_' + ak_id +' a').html('<span style="color:green"><?php esc_html_e("Copied", 'hkdev-maintenance-mode'); ?></span>');
                        window.hkdev_sleep(5000).then(() => {
                            jQuery('#hkdev_mm_submit_copy_' + ak_id +' a').html(savedTxt);
                        });
                    });
                }

                // Re-send Access Key (js)
                function hkdev_mm_resend_ak (ak_id, ak_name, ak_email) {
                    if (confirm('<?php echo esc_js(__("You are about to email an Access Key link to ", 'hkdev-maintenance-mode')); ?>' + ak_email)) {
                        // prepare ajax data
                        var data = {
                            action: 'hkdev_mm_resend_ak',
                            security: '<?php echo esc_js($ajax_nonce); ?>',
                            hkdev_mm_ak_id: ak_id
                        };

                        // Send ajax request
                        jQuery.post(ajaxurl, data, function(response) {
                            if (response.success) {
                                jQuery('.ajax_notices').html(
                                    '<div class="notice notice-success is-dismissible"><p>' +
                                    (response.data.message ? response.data.message : '<?php echo esc_js(__("Code sent via email.", 'hkdev-maintenance-mode')); ?>') +
                                    '</p><button type="button" class="notice-dismiss"><span class="screen-reader-text"><?php echo esc_js(__("Dismiss this notice", 'hkdev-maintenance-mode')); ?></span></button></div>'
                                ).autoDismiss(5000);
                            } else {
                                jQuery('.ajax_notices').html(
                                    '<div class="notice notice-error is-dismissible"><p>' +
                                    (response.data.message ? response.data.message : '<?php echo esc_js(__("Email sending failed. Please check your server settings", 'hkdev-maintenance-mode')); ?>') +
                                    '</p><button type="button" class="notice-dismiss"><span class="screen-reader-text"><?php echo esc_js(__("Dismiss this notice", 'hkdev-maintenance-mode')); ?></span></button></div>'
                                ).autoDismiss();
                            }
                        });
                    }
                }

                
                jQuery(document).ready(function($) {

                    // Dismiss ajax notices (js)
                    $(document).on('click', '.notice.is-dismissible', function() {
                            $(this).slideUp(250, function() {
                            $(this).remove();
                        });
                    });

                    // Auto dismiss ajax notices after 10 seconds (js/jQ Plugin)
                    $.fn.autoDismiss = function(time = 10000) {
                        setTimeout(function() {
                            $('.notice.is-dismissible').slideUp(250, function() {
                                $(this).remove();
                            });
                        }, time);
                    };

                });

            </script>

            <style type="text/css" media="screen">
                .hkdev_mm_admin_section { margin: 20px 0; }
                .hkdev_mm_disabled_field { color: #888; }
                .hkdev_mm_small_dim { font-size: 11px; font-weight: normal; color: #444; }
                .hkdev_mm_admin_section dt { font-weight: bold; }
                .hkdev_mm_admin_section dd { margin-left: 0; }
                .CodeMirror { border: 1px solid #ddd; }
                .submit { padding: 0; }
                .actived, .deactived { color: #ffffff; padding: 2px 10px;width: 20%; min-width: 40px; display: block; text-align: center; border-radius: 3px;}
                .actived { background-color: #4caf50; }
                .deactived { background-color: #f44336; }
                .hkdev-switch-a{
                    display: inline-flex;
                    border-radius: 2px;
                }
                .hkdev-switch {
                    position: relative;
                    display: inline-block;
                    padding: 2px;
                    overflow: hidden;
                    border-radius: 2px;
                    background-color: #d4d3d3;
                    cursor:pointer
                }
                .hkdev-switch.on { background-color: #6be67c }
                .hkdev-switch span {
                    visibility: hidden;
                    box-sizing: border-box;
                    float: left;
                    height: 100%;
                    font-size: 12px;
                    line-height: 20px;
                    border-radius: 2px;
                    -webkit-border-radius: 2px;
                    font-weight: 700;
                    padding: 4px 8px;
                    background: #fff;
                    color: #8d8d8d;
                    z-index: 1
                }
                .hkdev-switch span.on {color:#6be67c}
                .hkdev-switch span.checked {visibility:visible}
                .hkdev-switch.disabled {
                    cursor: default;
                    background:#e6e6e6
                }
                .hkdev-switch.disabled span {
                    background: #f1f1f1;
                    color: #d6d6d6
                }
                .hkdev-switch input[type="checkbox"] {
                    position: absolute !important;
                    top: 0;
                    left: 0;
                    opacity: 0;
                    z-index: -1
                }
                .hkdev_mm_admin_section table input[type="text"],
                .hkdev_mm_admin_section table input[type="email"] { width: 100%; padding: 0 8px; }
                .hkdev_mm_admin_section a  { cursor: pointer; }
                .hkdev_mm_admin_section .no-results { color: #888; }
                .hkdev-mm-inline-button { margin: 0; padding: 0 8px; line-height: 24px; min-height: 24px; }
                .hkdev-mm-inline-button:focus { outline: none; box-shadow: none; }
                .hkdev_mm_admin_section td { line-height: 1em !important; }
                .select2-container--default .select2-selection--multiple { border-radius: 2px; }
            </style>

            <div class=wrap>
                <form method="post" action="<?php echo esc_attr(esc_url(wp_unslash($_SERVER['REQUEST_URI']))); ?>" >
                    <h2><?php esc_html_e('Maintenance Mode', 'hkdev-maintenance-mode'); ?></h2>

                    <div class="ajax_notices"></div>

                    <div class="notice notice-info">
                        <p>
                            <?php echo sprintf(
                                /* translators: %s: line break. */
                                esc_html__('This plugin is intended primarily for developers that need to allow clients to preview sites before being available to the general public.%sAny logged in user with WordPress administrator privileges will be allowed to view the site regardless of the settings below.', 'hkdev-maintenance-mode'),
                                '<br>'
                            ); ?><br>
                            <?php echo sprintf(
                                /* translators: %s: link to donate. */
                                esc_html__('If you enjoyed this plugin or found it useful, I\'d really appreciate it if you\'d %s.', 'hkdev-maintenance-mode'),
                                '<a href="https://paypal.me/helderk" target="_blank" rel="noopener noreferrer">' . esc_html__('buy me a coffee', 'hkdev-maintenance-mode') . '</a>'
                            ); ?>
                        </p>
                    </div>

                    <h3><?php esc_html_e("Enable Maintenance Mode", 'hkdev-maintenance-mode'); ?></h3>

                    <a href="javascript:hkdev_mm_toggle_maintenance_mode();" class="hkdev-switch-a">
                        <div id="hkdev_mm_toggle-wrap"  class="hkdev-switch<?php if ($hkdev_mm_options['enable_mm'] == "YES") echo " on"; ?>">
                            <span class="off<?php if ($hkdev_mm_options['enable_mm'] != "YES") echo " checked"; ?>">OFF</span>
                            <span class="on<?php if ($hkdev_mm_options['enable_mm'] == "YES") echo " checked"; ?>">ON</span>
                        </div>
                    </a>

                    <p></p>

                    <div id="hkdev_main_options">

                        <h3 class="nav-tab-wrapper">
                        <a href="#maintenance-message" class="nav-tab nav-tab-active"><?php esc_html_e('Maintenance Message', 'hkdev-maintenance-mode'); ?></a>
                            <a href="#exclude-pages" class="nav-tab"><?php esc_html_e('Exclude Pages', 'hkdev-maintenance-mode'); ?></a>
                            <a href="#header-type" class="nav-tab"><?php esc_html_e('Header Type', 'hkdev-maintenance-mode'); ?></a>
                            <a href="#unrestricted-ip" class="nav-tab"><?php esc_html_e('Unrestricted IP\'s', 'hkdev-maintenance-mode'); ?></a>
                            <a href="#access-keys" class="nav-tab"><?php esc_html_e('Access Keys', 'hkdev-maintenance-mode'); ?></a>
                        </h3>

                        <div id="maintenance-message" class="hkdev_mm_admin_section">
                            <h3><?php esc_html_e("Maintenance Message", 'hkdev-maintenance-mode'); ?></h3>
                            <p class="description"><?php esc_html_e("You have four options for how to specify what you want to show users when your site is in maintenance mode.", 'hkdev-maintenance-mode'); ?></p>
                            <p><select name="hkdev_mm_method" id="hkdev_mm_method" style="width:30%" >
                                <option value="die_message" <?php selected($hkdev_mm_options['method'], "die_message"); ?>><?php esc_html_e("Message - wp_die() Method", 'hkdev-maintenance-mode'); ?></option>
                                <option value="site_message" <?php selected($hkdev_mm_options['method'], "site_message"); ?>><?php esc_html_e("Message - Site style Method", 'hkdev-maintenance-mode'); ?></option>
                                <option value="html" <?php selected($hkdev_mm_options['method'], "html"); ?>><?php esc_html_e("HTML", 'hkdev-maintenance-mode'); ?></option>
                                <option value="redirect" <?php selected($hkdev_mm_options['method'], "redirect"); ?>><?php esc_html_e("Redirect", 'hkdev-maintenance-mode'); ?></option>
                            </select></p>

                            <br>

                            <div id="hkdev_method_message" style="<?php if ($hkdev_mm_options['method'] == "redirect" || $hkdev_mm_options['method'] == "html") echo "display:none;"; ?>" >
                                <strong><?php esc_html_e("Site Title", 'hkdev-maintenance-mode'); ?></strong>
                                <p class="description"><?php esc_html_e('Overrides default site meta title.', 'hkdev-maintenance-mode'); ?></p>
                                <p style="margin-top: 0;"><input name="hkdev_mm_maintenance_title" type="text" placeholder="<?php echo esc_attr(get_bloginfo('name')); ?>" value="<?php echo esc_attr($hkdev_mm_options['maintenance_title']); ?>" class="regular-text"></p>

                                <strong><?php esc_html_e("Message", 'hkdev-maintenance-mode'); ?></strong>
                                <p class="description"><?php esc_html_e("This is the message that will be displayed while your site is in maintenance mode.", 'hkdev-maintenance-mode'); ?></p>
                                <p style="margin-top: 0;">
                                     <?php wp_editor($maintenance_message_value, 'hkdev_mm_maintenance_message'); ?>
                                </p>
                            </div>

                            <div id="hkdev_method_html" style="<?php if ($hkdev_mm_options['method'] != "html") echo "display:none;"; ?>" >
                                <strong><?php esc_html_e("HTML", 'hkdev-maintenance-mode'); ?></strong>
                                <p class="description"><?php esc_html_e("This is the HTML that will be displayed while your site is in maintenance mode.", 'hkdev-maintenance-mode'); ?></p>
                                <p style="margin-top: 0;">
                                    <textarea id="hkdev_mm_maintenance_html" name="hkdev_mm_maintenance_html" rows="10" style="width:100%"><?php echo esc_textarea($maintenance_html_value); ?></textarea>
                                </p>
                            </div>

                            <div id="hkdev_method_redirect" style="<?php if ($hkdev_mm_options['method'] != "redirect") echo "display:none;"; ?>" >
                                <strong><?php esc_html_e("Static Maintenance Page", 'hkdev-maintenance-mode'); ?></strong>
                                <p class="description"><?php esc_html_e("Insert a URL to redirect to an external page or to a static HTML page loaded on your site.", 'hkdev-maintenance-mode'); ?></p>
                                <p style="margin-top: 0;"><input type="text" name="hkdev_mm_static_page" value="<?php echo esc_attr($hkdev_mm_options['static_page']); ?>" id="hkdev_mm_static_page" style="width:100%"></p>
                            </div>
                        </div>

                        <div id="exclude-pages" class="hkdev_mm_admin_section hidden">
                            <h3><?php esc_html_e("Exclude pages", 'hkdev-maintenance-mode'); ?></h3>
                            <p><?php esc_html_e('Select the page(s) to be displayed normally, excluded by maintenance mode.', 'hkdev-maintenance-mode'); ?></p>
                            <select class="select" name="hkdev_mm_exclude_pages[]"  id="hkdev_mm_exclude_pages" multiple="multiple" style="width:30%" >
                            <?php
                            if (isset($hkdev_mm_options['exclude_pages'])) {
                                foreach ($hkdev_mm_options['exclude_pages'] as $post_id) {
                                    $title = get_the_title($post_id);
                                    // If the post title is too long, truncate it and add "..." at the end
                                    $title = (mb_strlen($title) > 50) ? mb_substr($title, 0, 49) . '...' : $title;
                                    echo '<option value="' . esc_attr($post_id) . '" selected="selected">' . esc_html($title) . '</option>';
                                }
                            }
                            ?>
                            </select>
                        </div>

                        <div id="header-type" class="hkdev_mm_admin_section hidden" >
                            <h3><?php esc_html_e("Header Type", 'hkdev-maintenance-mode'); ?></h3>
                            <p><?php echo wp_kses(_x('We can send 2 different header types.<br>When "Redirect" is selected, this setting will be ignored and <strong>307 Temporary Redirect</strong> used instead.','DO NOT TRANSLATE <strong>307 Temporary Redirect</strong> - leave exactly as is', 'hkdev-maintenance-mode'), array('br' => array(), 'strong' => array())); ?></p>
                            <dl>
                                <dt>200 OK</dt>
                                <dd><?php esc_html_e("Best used for when the site is under development.", 'hkdev-maintenance-mode'); ?></dd>
                                <dt>503 Service Temporarily Unavailable</dt> 
                                <dd><?php esc_html_e("Best for when the site is temporarily taken offline for small amendments.", 'hkdev-maintenance-mode'); ?><br>
                                <em><?php esc_html_e("If used for a long period of time, 503 can damage your Google ranking.", 'hkdev-maintenance-mode'); ?></em></dd>
                            </dl>

                            <select name="hkdev_mm_header_type" id="hkdev_mm_header_type" style="width:30%" >
                                <option value="200" <?php if ($hkdev_mm_options['method'] != "redirect" && $hkdev_mm_options['header_type'] == "200") echo "selected"; ?> <?php if ($hkdev_mm_options['method'] == "redirect") echo "disabled"; ?>>200 OK </option>
                                <option value="503" <?php if ($hkdev_mm_options['method'] != "redirect" && $hkdev_mm_options['header_type'] == "503") echo "selected"; ?> <?php if ($hkdev_mm_options['method'] == "redirect") echo "disabled"; ?>>503 Service Temporarily Unavailable</option>
                                <option value="507" <?php echo ($hkdev_mm_options['method'] == "redirect") ? "selected":"disabled"; ?>>507 Temporary Redirect </option>
                            </select>
                        </div>

                        <div id="unrestricted-ip" class="hkdev_mm_admin_section hidden">
                            <h3><?php esc_html_e("Unrestricted IP addresses", 'hkdev-maintenance-mode'); ?><br>
                            <span class="hkdev_mm_small_dim"><?php esc_html_e("Your IP address is:", 'hkdev-maintenance-mode'); ?>&nbsp;<a id="hkdev_set_ip"><?php echo esc_html($this->get_user_ip()); ?></a> - <?php esc_html_e("Your Class C is:", 'hkdev-maintenance-mode'); ?>&nbsp;<a id="hkdev_set_ip_c"><?php echo esc_html($this->get_user_class_c()); ?></a></span></h3>
                            <p><?php esc_html_e("Users with unrestricted IP addresses will bypass maintenance mode entirely. Using this option is useful to an entire office of clients to view the site without needing to jump through any extra hoops.", 'hkdev-maintenance-mode'); ?></p> 
                            <script>
                                jQuery("#hkdev_set_ip").on("click", function() { jQuery("#hkdev_mm_new_ip_ip").val("<?php echo esc_js($this->get_user_ip()); ?>")});
                                jQuery("#hkdev_set_ip_c").on("click", function() { jQuery("#hkdev_mm_new_ip_ip").val("<?php echo esc_js($this->get_user_class_c()); ?>")});
                            </script>
                            <div id="hkdev_mm_ip_tbl_container">
                                <?php $this->print_unrestricted_ips(); ?>
                            </div>
                        </div>

                        <div id="access-keys" class="hkdev_mm_admin_section hidden">
                            <h3><?php esc_html_e("Access Keys", 'hkdev-maintenance-mode'); ?></h3>
                            <p><?php esc_html_e("You can allow users temporary access by sending them the access key. When a new key is created, a link to create the access key cookie will be emailed to the email address provided. Access can then be revoked either by disabling or deleting the key.", 'hkdev-maintenance-mode'); ?></p>
                            
                            <div id="hkdev_mm_ak_tbl_container">
                                <?php $this->print_access_keys(); ?>
                            </div>
                        </div>

                    </div>

                    <div class="submit">
                        <?php wp_nonce_field('hkdev_nonce'); ?>
                        <input id="hkdev_submit_button" type="submit" class="button button-primary" name="update_wp_maintenance_mode_settings" value="<?php esc_attr_e('Update Settings', 'hkdev-maintenance-mode'); ?>" />
                    </div>
                    <input type="hidden" id="activeTab" name="activeTab" value="#maintenance-message">
                </form>
            </div>

            <?php
            //var_dump(get_option('hkdev_mm'));
        } // end function print_admin_page()

    } // end class HkDevMaintenanceMode
}

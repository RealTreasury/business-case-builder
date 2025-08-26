<?php
/**
 * Bootstrap file for WordPress testing environment
 * 
 * @package RealTreasuryBusinessCaseBuilder
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    define( 'ABSPATH', dirname( dirname( __DIR__ ) ) . '/' );
}

// Define test environment
define( 'RTBCB_TESTING', true );
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', false );
define( 'WP_DEBUG_DISPLAY', false );

// Mock WordPress functions for standalone testing
if ( ! function_exists( '__' ) ) {
    function __( $text, $domain = 'default' ) {
        return $text;
    }
}

if ( ! function_exists( '_e' ) ) {
    function _e( $text, $domain = 'default' ) {
        echo $text;
    }
}

if ( ! function_exists( 'esc_html' ) ) {
    function esc_html( $text ) {
        return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
    }
}

if ( ! function_exists( 'esc_attr' ) ) {
    function esc_attr( $text ) {
        return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
    }
}

if ( ! function_exists( 'esc_url' ) ) {
    function esc_url( $url ) {
        return filter_var( $url, FILTER_SANITIZE_URL );
    }
}

if ( ! function_exists( 'sanitize_text_field' ) ) {
    function sanitize_text_field( $text ) {
        return is_scalar( $text ) ? trim( strip_tags( (string) $text ) ) : '';
    }
}

if ( ! function_exists( 'sanitize_textarea_field' ) ) {
    function sanitize_textarea_field( $text ) {
        return is_scalar( $text ) ? trim( (string) $text ) : '';
    }
}

if ( ! function_exists( 'sanitize_key' ) ) {
    function sanitize_key( $key ) {
        $key = strtolower( $key );
        return preg_replace( '/[^a-z0-9_\-]/', '', $key );
    }
}

if ( ! function_exists( 'get_option' ) ) {
    function get_option( $option, $default = false ) {
        global $rtbcb_test_options;
        return isset( $rtbcb_test_options[ $option ] ) ? $rtbcb_test_options[ $option ] : $default;
    }
}

if ( ! function_exists( 'update_option' ) ) {
    function update_option( $option, $value ) {
        global $rtbcb_test_options;
        $rtbcb_test_options[ $option ] = $value;
        return true;
    }
}

if ( ! function_exists( 'delete_option' ) ) {
    function delete_option( $option ) {
        global $rtbcb_test_options;
        unset( $rtbcb_test_options[ $option ] );
        return true;
    }
}

if ( ! function_exists( 'add_action' ) ) {
    function add_action( $tag, $function_to_add, $priority = 10, $accepted_args = 1 ) {
        // Mock implementation for testing
        return true;
    }
}

if ( ! function_exists( 'add_filter' ) ) {
    function add_filter( $tag, $function_to_add, $priority = 10, $accepted_args = 1 ) {
        // Mock implementation for testing
        return true;
    }
}

if ( ! function_exists( 'apply_filters' ) ) {
    function apply_filters( $tag, $value ) {
        return $value;
    }
}

if ( ! function_exists( 'wp_json_encode' ) ) {
    function wp_json_encode( $data ) {
        return json_encode( $data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
    }
}

if ( ! function_exists( 'is_wp_error' ) ) {
    function is_wp_error( $thing ) {
        return ( $thing instanceof WP_Error );
    }
}

// WordPress plugin functions
if ( ! function_exists( 'plugin_dir_url' ) ) {
    function plugin_dir_url( $file ) {
        return 'http://example.com/wp-content/plugins/' . basename( dirname( $file ) ) . '/';
    }
}

if ( ! function_exists( 'plugin_dir_path' ) ) {
    function plugin_dir_path( $file ) {
        return dirname( $file ) . '/';
    }
}

if ( ! function_exists( 'plugin_basename' ) ) {
    function plugin_basename( $file ) {
        return basename( dirname( $file ) ) . '/' . basename( $file );
    }
}

if ( ! function_exists( 'register_activation_hook' ) ) {
    function register_activation_hook( $file, $function ) {
        return true;
    }
}

if ( ! function_exists( 'register_deactivation_hook' ) ) {
    function register_deactivation_hook( $file, $function ) {
        return true;
    }
}

if ( ! function_exists( 'do_action' ) ) {
    function do_action( $tag ) {
        return true;
    }
}

if ( ! function_exists( 'load_plugin_textdomain' ) ) {
    function load_plugin_textdomain( $domain, $deprecated, $plugin_rel_path ) {
        return true;
    }
}

if ( ! function_exists( 'wp_create_nonce' ) ) {
    function wp_create_nonce( $action ) {
        return 'test_nonce_' . $action;
    }
}

if ( ! function_exists( 'wp_verify_nonce' ) ) {
    function wp_verify_nonce( $nonce, $action ) {
        return $nonce === 'test_nonce_' . $action;
    }
}

if ( ! function_exists( 'current_user_can' ) ) {
    function current_user_can( $capability ) {
        global $test_user_capabilities;
        if ( isset( $test_user_capabilities ) ) {
            return in_array( $capability, $test_user_capabilities );
        }
        return true; // Allow all capabilities in tests by default
    }
}

if ( ! function_exists( 'admin_url' ) ) {
    function admin_url( $path = '', $scheme = 'admin' ) {
        return 'http://example.com/wp-admin/' . $path;
    }
}

if ( ! function_exists( 'wp_upload_dir' ) ) {
    function wp_upload_dir() {
        return array(
            'path' => '/tmp/wp-uploads',
            'url' => 'http://example.com/wp-content/uploads',
            'subdir' => '',
            'basedir' => '/tmp/wp-uploads',
            'baseurl' => 'http://example.com/wp-content/uploads',
            'error' => false
        );
    }
}

if ( ! function_exists( 'get_file_data' ) ) {
    function get_file_data( $file, $headers, $context = '' ) {
        return array(
            'name' => 'Real Treasury - Business Case Builder (Enhanced Pro)',
            'version' => '2.1.0',
            'description' => 'Professional-grade ROI calculator',
            'author' => 'Real Treasury',
            'requires_wp' => '6.0',
            'requires_php' => '7.4',
            'text_domain' => 'rtbcb'
        );
    }
}

if ( ! function_exists( 'is_admin' ) ) {
    function is_admin() {
        return defined( 'RTBCB_TESTING_ADMIN' ) ? RTBCB_TESTING_ADMIN : false;
    }
}

if ( ! function_exists( 'wp_schedule_event' ) ) {
    function wp_schedule_event( $timestamp, $recurrence, $hook, $args = array() ) {
        return true;
    }
}

if ( ! function_exists( 'wp_next_scheduled' ) ) {
    function wp_next_scheduled( $hook, $args = array() ) {
        return false;
    }
}

if ( ! function_exists( 'wp_clear_scheduled_hook' ) ) {
    function wp_clear_scheduled_hook( $hook, $args = array() ) {
        return true;
    }
}

if ( ! function_exists( 'wp_cache_flush' ) ) {
    function wp_cache_flush() {
        return true;
    }
}

if ( ! function_exists( 'wp_send_json_success' ) ) {
    function wp_send_json_success( $data = null ) {
        echo wp_json_encode( array( 'success' => true, 'data' => $data ) );
        exit;
    }
}

if ( ! function_exists( 'wp_send_json_error' ) ) {
    function wp_send_json_error( $data = null ) {
        if ( isset( $data['message'] ) ) {
            throw new Exception( $data['message'] );
        }
        echo wp_json_encode( array( 'success' => false, 'data' => $data ) );
        exit;
    }
}

if ( ! function_exists( 'shortcode_atts' ) ) {
    function shortcode_atts( $pairs, $atts, $shortcode = '' ) {
        return array_merge( $pairs, $atts );
    }
}

if ( ! function_exists( 'add_shortcode' ) ) {
    function add_shortcode( $tag, $func ) {
        return true;
    }
}

if ( ! function_exists( 'wp_enqueue_style' ) ) {
    function wp_enqueue_style( $handle, $src = '', $deps = array(), $ver = false, $media = 'all' ) {
        return true;
    }
}

if ( ! function_exists( 'wp_enqueue_script' ) ) {
    function wp_enqueue_script( $handle, $src = '', $deps = array(), $ver = false, $in_footer = false ) {
        return true;
    }
}

if ( ! function_exists( 'wp_localize_script' ) ) {
    function wp_localize_script( $handle, $object_name, $l10n ) {
        return true;
    }
}

if ( ! function_exists( 'wp_die' ) ) {
    function wp_die( $message, $title = '', $args = array() ) {
        throw new Exception( $message );
    }
}

if ( ! function_exists( 'add_menu_page' ) ) {
    function add_menu_page( $page_title, $menu_title, $capability, $menu_slug, $function = '', $icon_url = '', $position = null ) {
        global $admin_page_hooks;
        if ( ! isset( $admin_page_hooks ) ) {
            $admin_page_hooks = array();
        }
        $admin_page_hooks[ $menu_slug ] = array(
            'page_title' => $page_title,
            'menu_title' => $menu_title,
            'capability' => $capability
        );
        return $menu_slug;
    }
}

if ( ! function_exists( 'add_submenu_page' ) ) {
    function add_submenu_page( $parent_slug, $page_title, $menu_title, $capability, $menu_slug, $function = '' ) {
        return true;
    }
}

if ( ! function_exists( 'register_setting' ) ) {
    function register_setting( $option_group, $option_name, $args = array() ) {
        return true;
    }
}

if ( ! function_exists( 'flush_rewrite_rules' ) ) {
    function flush_rewrite_rules( $hard = true ) {
        return true;
    }
}

if ( ! function_exists( 'deactivate_plugins' ) ) {
    function deactivate_plugins( $plugins, $silent = false, $network_wide = null ) {
        return true;
    }
}

if ( ! function_exists( 'set_transient' ) ) {
    function set_transient( $transient, $value, $expiration = 0 ) {
        return true;
    }
}

if ( ! function_exists( 'get_transient' ) ) {
    function get_transient( $transient ) {
        return false;
    }
}

// Mock WP_Error class
if ( ! class_exists( 'WP_Error' ) ) {
    class WP_Error {
        private $errors = array();
        private $error_data = array();

        public function __construct( $code = '', $message = '', $data = '' ) {
            if ( empty( $code ) ) {
                return;
            }

            $this->errors[ $code ][] = $message;

            if ( ! empty( $data ) ) {
                $this->error_data[ $code ] = $data;
            }
        }

        public function get_error_code() {
            $codes = array_keys( $this->errors );
            return empty( $codes ) ? '' : $codes[0];
        }

        public function get_error_message( $code = '' ) {
            if ( empty( $code ) ) {
                $code = $this->get_error_code();
            }
            return isset( $this->errors[ $code ] ) ? $this->errors[ $code ][0] : '';
        }

        public function get_error_messages( $code = '' ) {
            if ( empty( $code ) ) {
                $all_messages = array();
                foreach ( (array) $this->errors as $code => $messages ) {
                    $all_messages = array_merge( $all_messages, $messages );
                }
                return $all_messages;
            }
            return isset( $this->errors[ $code ] ) ? $this->errors[ $code ] : array();
        }

        public function add( $code, $message, $data = '' ) {
            $this->errors[ $code ][] = $message;
            if ( ! empty( $data ) ) {
                $this->error_data[ $code ] = $data;
            }
        }
    }
}

// Global variables needed
global $wp_version;
$wp_version = '6.3.0';

// Initialize test options
global $rtbcb_test_options;
$rtbcb_test_options = array();

// Mock WordPress HTTP functions
if ( ! function_exists( 'wp_remote_post' ) ) {
    function wp_remote_post( $url, $args ) {
        global $rtbcb_mock_http_response;
        return isset( $rtbcb_mock_http_response ) ? $rtbcb_mock_http_response : new WP_Error( 'http_request_failed', 'No mock response set' );
    }
}

if ( ! function_exists( 'wp_remote_get' ) ) {
    function wp_remote_get( $url, $args = array() ) {
        global $rtbcb_mock_http_response;
        return isset( $rtbcb_mock_http_response ) ? $rtbcb_mock_http_response : new WP_Error( 'http_request_failed', 'No mock response set' );
    }
}

if ( ! function_exists( 'wp_remote_retrieve_response_code' ) ) {
    function wp_remote_retrieve_response_code( $response ) {
        if ( is_wp_error( $response ) ) {
            return 0;
        }
        return isset( $response['response']['code'] ) ? $response['response']['code'] : 200;
    }
}

if ( ! function_exists( 'wp_remote_retrieve_body' ) ) {
    function wp_remote_retrieve_body( $response ) {
        if ( is_wp_error( $response ) ) {
            return '';
        }
        return isset( $response['body'] ) ? $response['body'] : '';
    }
}

if ( ! function_exists( 'wp_remote_retrieve_headers' ) ) {
    function wp_remote_retrieve_headers( $response ) {
        if ( is_wp_error( $response ) ) {
            return array();
        }
        return isset( $response['headers'] ) ? $response['headers'] : array();
    }
}

// Include plugin files
require_once ABSPATH . 'inc/utils/helpers.php';
require_once ABSPATH . 'inc/class-rtbcb-category-recommender.php';
require_once ABSPATH . 'inc/class-rtbcb-calculator.php';
require_once ABSPATH . 'inc/class-rtbcb-llm.php';
require_once ABSPATH . 'inc/class-rtbcb-validator.php';
require_once ABSPATH . 'inc/class-rtbcb-router.php';

// Load main plugin file for tests that need the rtbcb() function
require_once ABSPATH . 'real-treasury-business-case-builder.php';

/**
 * Test assertion helper
 */
function rtbcb_assert( $condition, $message = 'Assertion failed' ) {
    if ( ! $condition ) {
        throw new Exception( $message );
    }
}

/**
 * Test helper for mocking HTTP responses
 */
function rtbcb_mock_http_response( $response ) {
    global $rtbcb_mock_http_response;
    $rtbcb_mock_http_response = $response;
}

echo "Bootstrap loaded successfully\n";
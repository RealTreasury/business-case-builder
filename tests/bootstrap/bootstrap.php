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

// Initialize test options
global $rtbcb_test_options;
$rtbcb_test_options = array();

// Include plugin files
require_once ABSPATH . 'inc/utils/helpers.php';
require_once ABSPATH . 'inc/class-rtbcb-category-recommender.php';
require_once ABSPATH . 'inc/class-rtbcb-calculator.php';
require_once ABSPATH . 'inc/class-rtbcb-llm.php';
require_once ABSPATH . 'inc/class-rtbcb-validator.php';
require_once ABSPATH . 'inc/class-rtbcb-router.php';

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

echo "Bootstrap loaded successfully\n";
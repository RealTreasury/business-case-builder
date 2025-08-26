<?php
if ( ! defined( 'ABSPATH' ) ) {
    define( 'ABSPATH', __DIR__ . '/../' );
}

$api_key = getenv( 'OPENAI_API_KEY' );
if ( empty( $api_key ) ) {
    echo "openai-error-handling.test.php skipped: missing OPENAI_API_KEY\n";
    exit( 0 );
}

if ( ! class_exists( 'WP_Error' ) ) {
    class WP_Error {
        private $code;
        private $message;
        public function __construct( $code = '', $message = '' ) {
            $this->code    = $code;
            $this->message = $message;
        }
        public function get_error_code() {
            return $this->code;
        }
        public function get_error_message() {
            return $this->message;
        }
    }
}

if ( ! function_exists( 'is_wp_error' ) ) {
    function is_wp_error( $thing ) {
        return $thing instanceof WP_Error;
    }
}

if ( ! function_exists( '__' ) ) {
    function __( $text, $domain = null ) {
        return $text;
    }
}

if ( ! function_exists( 'get_option' ) ) {
    function get_option( $name, $default = '' ) {
        global $api_key;
        if ( 'rtbcb_openai_api_key' === $name ) {
            return $api_key;
        }
        return $default;
    }
}

if ( ! function_exists( 'sanitize_text_field' ) ) {
    function sanitize_text_field( $text ) {
        return is_scalar( $text ) ? (string) $text : '';
    }
}

if ( ! function_exists( 'sanitize_textarea_field' ) ) {
    function sanitize_textarea_field( $text ) {
        return is_scalar( $text ) ? (string) $text : '';
    }
}

if ( ! function_exists( 'sanitize_key' ) ) {
    function sanitize_key( $key ) {
        $key = strtolower( $key );
        return preg_replace( '/[^a-z0-9_]/', '', $key );
    }
}

if ( ! function_exists( 'add_filter' ) ) {
    function add_filter( $tag, $function_to_add, $priority = 10, $accepted_args = 1 ) {}
}

if ( ! function_exists( 'wp_json_encode' ) ) {
    function wp_json_encode( $data ) {
        return json_encode( $data );
    }
}

require_once __DIR__ . '/../inc/class-rtbcb-llm.php';

$mock_response = null;
function wp_remote_post( $url, $args ) {
    global $mock_response;
    return $mock_response;
}

function wp_remote_retrieve_response_code( $response ) {
    return $response['response']['code'] ?? 0;
}

function wp_remote_retrieve_body( $response ) {
    return $response['body'] ?? '';
}

$llm    = new RTBCB_LLM();
$method = new ReflectionMethod( RTBCB_LLM::class, 'call_openai' );
$method->setAccessible( true );

// Network error should return WP_Error with api_error code
$mock_response = new WP_Error( 'http_request_failed', 'Network down' );
$result = $method->invoke( $llm, 'gpt-5-mini', 'prompt' );
if ( ! is_wp_error( $result ) || 'api_error' !== $result->get_error_code() ) {
    echo "Network error did not return WP_Error with api_error code\n";
    exit( 1 );
}

// Invalid credentials should return WP_Error with api_error code
$mock_response = [
    'response' => [ 'code' => 401 ],
    'body'     => 'Unauthorized',
];
$result = $method->invoke( $llm, 'gpt-5-mini', 'prompt' );
if ( ! is_wp_error( $result ) || 'api_error' !== $result->get_error_code() || false === strpos( $result->get_error_message(), 'Unauthorized' ) ) {
    echo "Invalid credentials did not return expected WP_Error\n";
    exit( 1 );
}

echo "openai-error-handling.test.php passed\n";

<?php
if ( ! defined( 'ABSPATH' ) ) {
    define( 'ABSPATH', __DIR__ . '/../' );
}
if ( ! function_exists( 'add_filter' ) ) {
    function add_filter( $tag, $function_to_add, $priority = 10, $accepted_args = 1 ) {
        // no-op
    }
}
require_once __DIR__ . '/../inc/config.php';
require_once __DIR__ . '/../inc/helpers.php';
require_once __DIR__ . '/../inc/class-rtbcb-llm.php';

if ( ! class_exists( 'WP_Error' ) ) {
    class WP_Error {
        public $errors = [];
        public function __construct( $code = '', $message = '' ) {
            if ( $code ) {
                $this->errors[ $code ] = [ $message ];
            }
        }
    }
}

if ( ! function_exists( '__' ) ) {
    function __( $text, $domain = null ) {
        return $text;
    }
}

if ( ! function_exists( 'is_wp_error' ) ) {
    function is_wp_error( $thing ) {
        return $thing instanceof WP_Error;
    }
}

if ( ! function_exists( 'sanitize_text_field' ) ) {
    function sanitize_text_field( $text ) {
        $text = is_scalar( $text ) ? (string) $text : '';
        $text = preg_replace( '/[\r\n\t\0\x0B]/', '', $text );
        return trim( $text );
    }
}

if ( ! function_exists( 'sanitize_textarea_field' ) ) {
    function sanitize_textarea_field( $text ) {
        $text = is_scalar( $text ) ? (string) $text : '';
        $text = preg_replace( '/[\r\0\x0B]/', '', $text );
        return trim( $text );
    }
}

if ( ! function_exists( 'sanitize_key' ) ) {
    function sanitize_key( $key ) {
        $key = strtolower( $key );
        return preg_replace( '/[^a-z0-9_]/', '', $key );
    }
}

if ( ! function_exists( 'wp_json_encode' ) ) {
    function wp_json_encode( $data ) {
        return json_encode( $data );
    }
}

if ( ! function_exists( 'wp_remote_post' ) ) {
    function wp_remote_post( $url, $args ) {
        $ch = curl_init( $url );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
        curl_setopt( $ch, CURLOPT_POST, true );
        curl_setopt( $ch, CURLOPT_POSTFIELDS, $args['body'] ?? '' );
        $headers = [];
        foreach ( $args['headers'] ?? [] as $key => $value ) {
            $headers[] = $key . ': ' . $value;
        }
        curl_setopt( $ch, CURLOPT_HTTPHEADER, $headers );
        if ( isset( $args['timeout'] ) ) {
            curl_setopt( $ch, CURLOPT_TIMEOUT, intval( $args['timeout'] ) );
        }
        $body = curl_exec( $ch );
        $code = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
        curl_close( $ch );
        return [
            'body'     => $body,
            'response' => [ 'code' => $code ],
        ];
    }
}

if ( ! function_exists( 'wp_remote_retrieve_response_code' ) ) {
    function wp_remote_retrieve_response_code( $response ) {
        return intval( $response['response']['code'] ?? 0 );
    }
}

if ( ! function_exists( 'wp_remote_retrieve_body' ) ) {
    function wp_remote_retrieve_body( $response ) {
        return $response['body'] ?? '';
    }
}

if ( ! function_exists( 'get_option' ) ) {
    function get_option( $name, $default = '' ) {
        return $default;
    }
}

$api_key = getenv( 'OPENAI_API_KEY' );
if ( ! $api_key ) {
    echo "Skipping live GPT-5 response test: OPENAI_API_KEY not set.\n";
    exit( 0 );
}

$llm = new RTBCB_LLM();
$ref = new ReflectionClass( $llm );
$prop = $ref->getProperty( 'api_key' );
$prop->setAccessible( true );
$prop->setValue( $llm, $api_key );

$method = $ref->getMethod( 'call_openai' );
$method->setAccessible( true );

$prompt = [
    'instructions' => 'Reply with a short confirmation.',
    'input'        => 'ping',
];

$response = $method->invoke( $llm, 'gpt-5-mini', $prompt );

if ( is_wp_error( $response ) ) {
    echo "call_openai returned WP_Error\n";
    exit( 1 );
}

$code = wp_remote_retrieve_response_code( $response );
if ( 200 !== $code ) {
    echo "Unexpected HTTP status: {$code}\n";
    exit( 1 );
}

$body = json_decode( wp_remote_retrieve_body( $response ), true );
if ( ! is_array( $body ) ) {
    echo "Response body not JSON\n";
    exit( 1 );
}

if ( empty( $body['output_text'] ) && empty( $body['output'] ) ) {
    echo "Missing output fields\n";
    exit( 1 );
}

echo "gpt5-responses-live.test.php passed\n";

<?php
if ( ! defined( 'ABSPATH' ) ) {
    define( 'ABSPATH', __DIR__ . '/../' );
}

if ( ! function_exists( 'add_filter' ) ) {
    function add_filter( $tag, $function_to_add, $priority = 10, $accepted_args = 1 ) {}
}

if ( ! defined( 'RTBCB_DIR' ) ) {
    define( 'RTBCB_DIR', __DIR__ . '/../' );
}

require_once __DIR__ . '/../inc/config.php';
require_once __DIR__ . '/../inc/class-rtbcb-api-tester.php';
require_once __DIR__ . '/../inc/class-rtbcb-llm.php';

if ( ! function_exists( 'get_option' ) ) {
    function get_option( $name, $default = '' ) {
        if ( 'rtbcb_mini_model' === $name ) {
            return 'gpt-5-mini';
        }
        if ( 'rtbcb_advanced_model' === $name ) {
            return 'gpt-5-mini';
        }
        if ( 'rtbcb_gpt5_config' === $name ) {
            return [];
        }
        if ( 'rtbcb_gpt5_max_output_tokens' === $name ) {
            return 25000;
        }
        if ( 'rtbcb_gpt5_min_output_tokens' === $name ) {
            return 5000;
        }
        return $default;
    }
}

if ( ! function_exists( '__' ) ) {
    function __( $text, $domain = null ) {
        return $text;
    }
}

if ( ! function_exists( 'wp_json_encode' ) ) {
    function wp_json_encode( $data ) {
        return json_encode( $data );
    }
}

if ( ! function_exists( 'sanitize_text_field' ) ) {
    function sanitize_text_field( $text ) {
        $text = is_scalar( $text ) ? (string) $text : '';
        $text = preg_replace( '/[\r\n\t\0\x0B]/', '', $text );
        return trim( $text );
    }
}

if ( ! function_exists( 'sanitize_key' ) ) {
    function sanitize_key( $key ) {
        $key = strtolower( $key );
        return preg_replace( '/[^a-z0-9_]/', '', $key );
    }
}

if ( ! class_exists( 'WP_Error' ) ) {
    class WP_Error {}
}

if ( ! function_exists( 'is_wp_error' ) ) {
    function is_wp_error( $thing ) {
        return $thing instanceof WP_Error;
    }
}

$mock_response = [
    'body' => json_encode( [
        'status' => 'completed',
        'output' => [
            [
                'id'      => 'reasoning',
                'type'    => 'reasoning',
                'content' => [
                    [
                        'type' => 'reasoning',
                        'text' => 'thinking',
                    ],
                ],
            ],
            [
                'id'      => 'message',
                'type'    => 'message',
                'content' => [
                    [
                        'type' => 'output_text',
                        'text' => 'This is a meaningful response message.',
                    ],
                ],
            ],
        ],
    ] ),
];

$captured_args = [];

if ( ! function_exists( 'wp_remote_post' ) ) {
    function wp_remote_post( $url, $args ) {
        global $mock_response, $captured_args;
        $captured_args = $args;
        return $mock_response;
    }
}

if ( ! function_exists( 'wp_remote_retrieve_response_code' ) ) {
    function wp_remote_retrieve_response_code( $response ) {
        return 200;
    }
}

if ( ! function_exists( 'wp_remote_retrieve_body' ) ) {
    function wp_remote_retrieve_body( $response ) {
        return $response['body'] ?? '';
    }
}

if ( ! function_exists( 'rtbcb_model_supports_temperature' ) ) {
    function rtbcb_model_supports_temperature( $model ) {
        return true;
    }
}

$method = new ReflectionMethod( RTBCB_API_Tester::class, 'test_completion' );
$method->setAccessible( true );
$result = $method->invoke( null, 'test-key' );

$sent = json_decode( $captured_args['body'] ?? '{}', true );
if ( 25000 !== ( $sent['max_output_tokens'] ?? 0 ) ) {
    echo "API tester did not respect configured max tokens\n";
    exit( 1 );
}

if ( 5000 !== ( $sent['min_output_tokens'] ?? 0 ) ) {
    echo "API tester did not respect configured min tokens\n";
    exit( 1 );
}

if ( ! $result['success'] ) {
    echo "API tester did not report success\n";
    exit( 1 );
}

if ( 'This is a meaningful response message.' !== ( $result['response'] ?? '' ) ) {
    echo "API tester failed to extract text\n";
    exit( 1 );
}

$combined = ( $result['message'] ?? '' ) . ' ' . ( $result['details'] ?? '' );
if ( false !== strpos( $combined, 'max_output_tokens' ) ) {
    echo "API tester flagged max_output_tokens\n";
    exit( 1 );
}

$parsed = rtbcb_parse_gpt5_response( $mock_response );
if ( 'This is a meaningful response message.' !== ( $parsed['output_text'] ?? '' ) ) {
    echo "Failed to extract message text\n";
    exit( 1 );
}

echo "api-tester-gpt5-mini.test.php passed\n";

<?php
if ( ! defined( 'ABSPATH' ) ) {
    define( 'ABSPATH', __DIR__ . '/' );
}

if ( ! function_exists( 'add_filter' ) ) {
    function add_filter( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
        // No-op for tests.
    }
}

require_once __DIR__ . '/../inc/helpers.php';

if ( ! function_exists( 'sanitize_text_field' ) ) {
    function sanitize_text_field( $text ) {
        $text = is_scalar( $text ) ? (string) $text : '';
        $text = preg_replace( '/[\r\n\t\0\x0B]/', '', $text );
        return trim( $text );
    }
}

$valid_keys = [
    'sk-' . str_repeat( 'a', 31 ) . '_',
    'sk-proj-' . str_repeat( 'b', 15 ) . '_' . str_repeat( 'b', 16 ),
];

foreach ( $valid_keys as $key ) {
    if ( ! rtbcb_is_valid_openai_api_key( $key ) ) {
        echo "Valid key rejected: {$key}\n";
        exit( 1 );
    }
}

$invalid_keys = [
    'sk-' . str_repeat( 'a', 31 ) . '-',
];

foreach ( $invalid_keys as $key ) {
    if ( rtbcb_is_valid_openai_api_key( $key ) ) {
        echo "Invalid key accepted: {$key}\n";
        exit( 1 );
    }
}

echo "openai-api-key-validation.test.php passed\n";

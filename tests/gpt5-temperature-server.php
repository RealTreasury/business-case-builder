<?php
require_once __DIR__ . '/../inc/class-rtbcb-llm.php';

if ( ! class_exists( 'WP_Error' ) ) {
    class WP_Error {
        private $message;
        public function __construct( $code = '', $message = '' ) { $this->message = $message; }
        public function get_error_message() { return $this->message; }
    }
}

function get_option( $name, $default = '' ) {
    if ( 'rtbcb_openai_api_key' === $name ) {
        return 'test-key';
    }
    return $default;
}
function wp_json_encode( $data ) { return json_encode( $data ); }
function wp_remote_post( $url, $args ) {
    $GLOBALS['captured_body'] = $args['body'];
    return [ 'body' => '{}', 'response' => [ 'code' => 200 ] ];
}
function is_wp_error( $thing ) { return false; }
function wp_remote_retrieve_response_code( $response ) { return $response['response']['code']; }
function wp_remote_retrieve_body( $response ) { return $response['body']; }

$llm = new RTBCB_LLM();
$method = new ReflectionMethod( RTBCB_LLM::class, 'call_openai' );
$method->setAccessible( true );
$method->invoke( $llm, 'gpt-5', 'test prompt' );

$body = json_decode( $GLOBALS['captured_body'], true );
if ( isset( $body['temperature'] ) ) {
    echo "Server-side temperature test failed\n";
    exit( 1 );
}

echo "Server-side temperature test passed\n";

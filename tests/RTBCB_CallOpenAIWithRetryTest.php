<?php
if ( ! defined( 'ABSPATH' ) ) {
define( 'ABSPATH', __DIR__ . '/../' );
}
defined( 'ABSPATH' ) || exit;

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/wp-stubs.php';
require_once __DIR__ . '/../inc/class-rtbcb-llm.php';

if ( ! defined( 'HOUR_IN_SECONDS' ) ) {
define( 'HOUR_IN_SECONDS', 3600 );
}

if ( ! function_exists( 'wp_cache_get' ) ) {
function wp_cache_get( $key, $group = '' ) {
return false;
}
}

if ( ! function_exists( 'wp_cache_set' ) ) {
function wp_cache_set( $key, $value, $group = '', $ttl = 0 ) {
return true;
}
}

if ( ! function_exists( 'wp_cache_delete' ) ) {
function wp_cache_delete( $key, $group = '' ) {
return true;
}
}

if ( ! function_exists( 'get_transient' ) ) {
function get_transient( $key ) {
return false;
}
}

if ( ! function_exists( 'set_transient' ) ) {
function set_transient( $key, $value, $ttl ) {
return true;
}
}

if ( ! function_exists( 'delete_transient' ) ) {
function delete_transient( $key ) {
return true;
}
}

if ( ! class_exists( 'WP_Error' ) ) {
class WP_Error {
private $code;
private $message;
private $data;

public function __construct( $code = '', $message = '', $data = [] ) {
$this->code    = $code;
$this->message = $message;
$this->data    = $data;
}

public function get_error_code() {
return $this->code;
}

public function get_error_message() {
return $this->message;
}

public function get_error_data() {
return $this->data;
}
}
}

if ( ! function_exists( 'is_wp_error' ) ) {
function is_wp_error( $thing ) {
return $thing instanceof WP_Error;
}
}

if ( ! function_exists( 'sanitize_key' ) ) {
function sanitize_key( $key ) {
return preg_replace( '/[^a-z0-9_]/', '', strtolower( $key ) );
}
}

class RTBCB_Test_LLM extends RTBCB_LLM {
public function __construct() {
$property = new ReflectionProperty( RTBCB_LLM::class, 'gpt5_config' );
$property->setAccessible( true );
$property->setValue( $this, [
'max_retries'     => 3,
'timeout'         => 300,
'max_retry_time'  => 300,
'min_output_tokens' => 1,
] );
}

public function call_openai_with_retry_wrapper( $model, $prompt, $max_output_tokens = null, $max_retries = null, $chunk_handler = null ) {
return $this->call_openai_with_retry( $model, $prompt, $max_output_tokens, $max_retries, $chunk_handler );
}

protected function call_openai( $model, $prompt, $max_output_tokens = null, $chunk_handler = null ) {
return [
'body'     => 'ok',
'response' => [
'code'    => 200,
'message' => '',
],
'headers'  => [],
];
}
}

final class RTBCB_CallOpenAIWithRetryTest extends TestCase {
public function test_call_openai_with_retry_returns_response() {
$expected = [
'body'     => 'ok',
'response' => [
'code'    => 200,
'message' => '',
],
'headers'  => [],
];

$llm = new RTBCB_Test_LLM();

$result = $llm->call_openai_with_retry_wrapper( 'model', 'prompt' );

$this->assertSame( $expected, $result );
}
}

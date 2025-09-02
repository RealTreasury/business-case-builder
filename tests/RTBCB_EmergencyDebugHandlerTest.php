<?php
if ( ! defined( 'ABSPATH' ) ) {
define( 'ABSPATH', __DIR__ . '/../' );
}
defined( 'ABSPATH' ) || exit;

use PHPUnit\Framework\TestCase;

if ( ! class_exists( 'WP_Error' ) ) {
class WP_Error {}
}

if ( ! function_exists( '__' ) ) {
function __( $text, $domain = null ) {
return $text;
}
}
if ( ! function_exists( 'sanitize_text_field' ) ) {
function sanitize_text_field( $text ) {
return is_string( $text ) ? trim( $text ) : '';
}
}
if ( ! function_exists( 'sanitize_key' ) ) {
function sanitize_key( $key ) {
return $key;
}
}
if ( ! function_exists( 'wp_unslash' ) ) {
function wp_unslash( $value ) {
return $value;
}
}
if ( ! function_exists( 'is_admin' ) ) {
function is_admin() {
return false;
}
}
if ( ! function_exists( 'wp_verify_nonce' ) ) {
function wp_verify_nonce( $nonce, $action ) {
return 'valid' === $nonce;
}
}
if ( ! function_exists( 'size_format' ) ) {
function size_format( $bytes ) {
return '1 MB';
}
}
if ( ! function_exists( 'rtbcb_has_openai_api_key' ) ) {
function rtbcb_has_openai_api_key() {
return true;
}
}
if ( ! function_exists( 'plugin_dir_url' ) ) {
function plugin_dir_url( $file ) {
return '';
}
}
if ( ! function_exists( 'plugin_dir_path' ) ) {
function plugin_dir_path( $file ) {
return __DIR__ . '/../';
}
}
if ( ! function_exists( 'register_activation_hook' ) ) {
function register_activation_hook() {}
}
if ( ! function_exists( 'register_deactivation_hook' ) ) {
function register_deactivation_hook() {}
}
if ( ! function_exists( 'register_uninstall_hook' ) ) {
function register_uninstall_hook() {}
}
if ( ! function_exists( 'add_action' ) ) {
function add_action() {}
}
if ( ! function_exists( 'add_shortcode' ) ) {
function add_shortcode() {}
}
if ( ! function_exists( 'add_filter' ) ) {
function add_filter() {}
}
if ( ! function_exists( 'plugin_basename' ) ) {
function plugin_basename() {
return '';
}
}
if ( ! function_exists( 'get_file_data' ) ) {
function get_file_data() {
return [];
}
}

if ( ! class_exists( 'RTBCB_JSON_Response' ) ) {
class RTBCB_JSON_Response extends Exception {
public $data;
public function __construct( $data ) {
parent::__construct();
$this->data = $data;
}
}
}
if ( ! function_exists( 'wp_send_json_success' ) ) {
function wp_send_json_success( $data = null ) {
throw new RTBCB_JSON_Response(
[
'success' => true,
'data'    => $data,
]
);
}
}

if ( ! class_exists( 'RTBCB_Calculator' ) ) {
class RTBCB_Calculator {}
}
if ( ! class_exists( 'RTBCB_DB' ) ) {
class RTBCB_DB {}
}

if ( ! defined( 'RTBCB_NO_BOOTSTRAP' ) ) {
define( 'RTBCB_NO_BOOTSTRAP', true );
}
require_once __DIR__ . '/../real-treasury-business-case-builder.php';

final class RTBCB_EmergencyDebugHandlerTest extends TestCase {
protected function setUp(): void {
global $wpdb;
$wpdb       = new class() {
public $prefix = 'wp_';
public function prepare( $query, $table ) {
return sprintf( $query, $table );
}
public function get_var( $query ) {
return 'wp_rtbcb_leads';
}
};
$_POST = [];
}

public function test_debug_handler_valid_nonce() {
$_POST['rtbcb_nonce'] = 'valid';
try {
$ref    = new ReflectionClass( 'RTBCB_Main' );
$plugin = $ref->newInstanceWithoutConstructor();
$plugin->debug_ajax_handler();
$this->fail( 'Expected RTBCB_JSON_Response' );
} catch ( RTBCB_JSON_Response $e ) {
$this->assertTrue( $e->data['success'] );
$this->assertTrue( $e->data['data']['nonce_valid'] );
$this->assertTrue( $e->data['data']['db_table_exists'] );
}
}

public function test_debug_handler_invalid_nonce() {
$_POST['rtbcb_nonce'] = 'bad';
try {
$ref    = new ReflectionClass( 'RTBCB_Main' );
$plugin = $ref->newInstanceWithoutConstructor();
$plugin->debug_ajax_handler();
$this->fail( 'Expected RTBCB_JSON_Response' );
} catch ( RTBCB_JSON_Response $e ) {
$this->assertFalse( $e->data['data']['nonce_valid'] );
}
}
}

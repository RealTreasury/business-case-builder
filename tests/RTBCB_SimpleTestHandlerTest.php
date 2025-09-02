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
$GLOBALS['rtbcb_test_nonce_valid'] = true;
if ( ! function_exists( 'check_ajax_referer' ) ) {
function check_ajax_referer( $action, $query_arg = false, $die = true ) {
return $GLOBALS['rtbcb_test_nonce_valid'];
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
if ( ! function_exists( 'wp_send_json_error' ) ) {
function wp_send_json_error( $data = null, $status = 400 ) {
throw new RTBCB_JSON_Response(
[
'success' => false,
'data'    => $data,
'status'  => $status,
]
);
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

final class RTBCB_SimpleTestHandlerTest extends TestCase {
protected function setUp(): void {
$_POST = [];
$GLOBALS['rtbcb_test_nonce_valid'] = true;
}

public function test_simple_handler_success() {
$_POST['investment'] = '100';
$_POST['returns']    = '150';
$_POST['rtbcb_nonce'] = 'valid';
try {
$ref    = new ReflectionClass( 'RTBCB_Main' );
$plugin = $ref->newInstanceWithoutConstructor();
$plugin->ajax_generate_case_simple();
$this->fail( 'Expected RTBCB_JSON_Response' );
} catch ( RTBCB_JSON_Response $e ) {
$this->assertTrue( $e->data['success'] );
$this->assertEquals( 50, $e->data['data']['roi'] );
}
}

public function test_simple_handler_nonce_failure() {
$_POST['investment'] = '100';
$_POST['returns']    = '150';
$GLOBALS['rtbcb_test_nonce_valid'] = false;
try {
$ref    = new ReflectionClass( 'RTBCB_Main' );
$plugin = $ref->newInstanceWithoutConstructor();
$plugin->ajax_generate_case_simple();
$this->fail( 'Expected RTBCB_JSON_Response' );
} catch ( RTBCB_JSON_Response $e ) {
$this->assertFalse( $e->data['success'] );
$this->assertEquals( 'Security check failed.', $e->data['data'] );
$this->assertEquals( 403, $e->data['status'] );
}
}
}

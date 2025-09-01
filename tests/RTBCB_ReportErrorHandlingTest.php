<?php
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/../' );
}
defined( 'ABSPATH' ) || exit;

use PHPUnit\Framework\TestCase;

if ( ! class_exists( 'WP_Error' ) ) {
class WP_Error {
private $code;
private $message;
private $data;
public function __construct( $code = '', $message = '', $data = [] ) {
$this->code   = $code;
$this->message = $message;
$this->data    = $data;
}
public function get_error_message() {
return $this->message;
}
public function get_error_code() {
return $this->code;
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

if ( ! function_exists( 'sanitize_email' ) ) {
function sanitize_email( $email ) {
return filter_var( $email, FILTER_SANITIZE_EMAIL );
}
}

if ( ! function_exists( 'wp_unslash' ) ) {
function wp_unslash( $value ) {
return $value;
}
}

if ( ! function_exists( 'check_ajax_referer' ) ) {
function check_ajax_referer( $action, $query_arg = false, $die = true ) {
return true;
}
}

if ( ! function_exists( 'wp_verify_nonce' ) ) {
function wp_verify_nonce( $nonce, $action ) {
return true;
}
}

class RTBCB_JSON_Error extends Exception {
public $data;
public $status;
public function __construct( $data, $status ) {
parent::__construct();
$this->data   = $data;
$this->status = $status;
}
}

if ( ! function_exists( 'wp_send_json_error' ) ) {
function wp_send_json_error( $data = null, $status_code = null ) {
throw new RTBCB_JSON_Error(
[
'success' => false,
'data'    => $data,
],
$status_code
);
}
}

if ( ! function_exists( 'wp_send_json_success' ) ) {
function wp_send_json_success( $data = null ) {
// No-op for tests.
}
}

class RTBCB_Background_Job {
public static function enqueue( $user_inputs ) {
return 123;
}
}

require_once __DIR__ . '/../inc/helpers.php';
require_once __DIR__ . '/../inc/class-rtbcb-validator.php';
require_once __DIR__ . '/../inc/class-rtbcb-ajax.php';
require_once __DIR__ . '/../inc/class-rtbcb-router.php';

final class RTBCB_ReportErrorHandlingTest extends TestCase {
protected function setUp(): void {
$_POST = [];
}

public function test_ajax_missing_required_fields() {
$_POST = [
'email'        => 'user@corp.com',
'company_size' => '100-500',
];

try {
RTBCB_Ajax::generate_comprehensive_case();
$this->fail( 'Expected RTBCB_JSON_Error not thrown.' );
} catch ( RTBCB_JSON_Error $e ) {
$this->assertSame( 400, $e->status );
$this->assertSame(
[
'success' => false,
'data'    => 'Company name is required.',
],
$e->data
);
}
}

public function test_ajax_invalid_email() {
$_POST = [
'company_name' => 'Acme',
'company_size' => '100-500',
'email'        => 'user@gmail.com',
];

try {
RTBCB_Ajax::generate_comprehensive_case();
$this->fail( 'Expected RTBCB_JSON_Error not thrown.' );
} catch ( RTBCB_JSON_Error $e ) {
$this->assertSame( 400, $e->status );
$this->assertSame(
[
'success' => false,
'data'    => 'Please use your business email address.',
],
$e->data
);
}
}

public function test_ajax_malformed_numeric() {
$_POST = [
'company_name'         => 'Acme',
'company_size'         => '100-500',
'email'                => 'user@corp.com',
'hours_reconciliation' => 'notanumber',
];

try {
RTBCB_Ajax::generate_comprehensive_case();
$this->fail( 'Expected RTBCB_JSON_Error not thrown.' );
} catch ( RTBCB_JSON_Error $e ) {
$this->assertSame( 400, $e->status );
$this->assertSame(
[
'success' => false,
'data'    => 'Hours Reconciliation must be a numeric value.',
],
$e->data
);
}
}

public function test_router_missing_required_fields() {
$_POST = [
'rtbcb_nonce' => 'nonce',
'email'       => 'user@corp.com',
];

$router = new RTBCB_Router();

try {
$router->handle_form_submission();
$this->fail( 'Expected RTBCB_JSON_Error not thrown.' );
} catch ( RTBCB_JSON_Error $e ) {
$this->assertSame( 400, $e->status );
$this->assertSame(
[
'success' => false,
'data'    => [ 'message' => 'Company name is required.' ],
],
$e->data
);
}
}

public function test_router_invalid_email() {
$_POST = [
'rtbcb_nonce'  => 'nonce',
'company_name' => 'Acme',
'company_size' => '100-500',
'email'        => 'user@yahoo.com',
];

$router = new RTBCB_Router();

try {
$router->handle_form_submission();
$this->fail( 'Expected RTBCB_JSON_Error not thrown.' );
} catch ( RTBCB_JSON_Error $e ) {
$this->assertSame( 400, $e->status );
$this->assertSame(
[
'success' => false,
'data'    => [ 'message' => 'Please use your business email address.' ],
],
$e->data
);
}
}

public function test_router_malformed_numeric() {
$_POST = [
'rtbcb_nonce'            => 'nonce',
'company_name'          => 'Acme',
'company_size'          => '100-500',
'email'                 => 'user@corp.com',
'hours_cash_positioning' => 'abc',
];

$router = new RTBCB_Router();

try {
$router->handle_form_submission();
$this->fail( 'Expected RTBCB_JSON_Error not thrown.' );
} catch ( RTBCB_JSON_Error $e ) {
$this->assertSame( 400, $e->status );
$this->assertSame(
[
'success' => false,
'data'    => [ 'message' => 'Hours Cash Positioning must be a numeric value.' ],
],
$e->data
);
}
}
}


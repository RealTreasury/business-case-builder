<?php
require_once __DIR__ . '/wp-stubs.php';

defined( 'ABSPATH' ) || exit;

use PHPUnit\Framework\TestCase;

// Stub WordPress functions.
if ( ! function_exists( 'wp_verify_nonce' ) ) {
function wp_verify_nonce( $nonce, $action ) {
return true;
}
}

if ( ! function_exists( 'sanitize_text_field' ) ) {
function sanitize_text_field( $text ) {
$text = is_scalar( $text ) ? (string) $text : '';
$text = preg_replace( '/[\r\n\t\0\x0B]/', '', $text );
return trim( $text );
}
}

if ( ! function_exists( 'wp_unslash' ) ) {
function wp_unslash( $value ) {
return $value;
}
}

if ( ! function_exists( '__' ) ) {
function __( $text, $domain = null ) {
return $text;
}
}

if ( ! function_exists( 'sanitize_email' ) ) {
function sanitize_email( $email ) {
return filter_var( $email, FILTER_SANITIZE_EMAIL );
}
}

if ( ! function_exists( 'wp_send_json_success' ) ) {
function wp_send_json_success( $data, $status = 200 ) {
global $last_response;
$last_response = [
'success' => true,
'data'    => $data,
'status'  => $status,
];
return $last_response;
}
}

if ( ! function_exists( 'wp_send_json_error' ) ) {
function wp_send_json_error( $data, $status = 400 ) {
global $last_response;
$last_response = [
'success' => false,
'data'    => $data,
'status'  => $status,
];
return $last_response;
}
}

if ( ! function_exists( 'wp_upload_dir' ) ) {
function wp_upload_dir() {
return [ 'basedir' => sys_get_temp_dir() ];
}
}

if ( ! function_exists( 'wp_mkdir_p' ) ) {
function wp_mkdir_p( $dir ) {
return is_dir( $dir ) ? true : mkdir( $dir, 0777, true );
}
}

if ( ! function_exists( 'get_bloginfo' ) ) {
function get_bloginfo( $field ) {
return 'Test Site';
}
}

if ( ! function_exists( 'wp_mail' ) ) {
function wp_mail( $to, $subject, $message, $headers = [], $attachments = [] ) {
return true;
}
}

if ( ! function_exists( 'trailingslashit' ) ) {
function trailingslashit( $string ) {
return rtrim( $string, '/\\' ) . '/';
}
}

// Stub plugin classes.
if ( ! class_exists( 'RTBCB_Calculator' ) ) {
class RTBCB_Calculator {
public static function calculate_roi( $data, $category = [] ) {
return [ 'roi_base' => 1000 ];
}
public static function calculate_category_refined_roi( $data, $category ) {
return self::calculate_roi( $data, $category );
}
}
}

if ( ! class_exists( 'RTBCB_LLM' ) ) {
class RTBCB_LLM {
public function generate_business_case( $form_data, $calculations, $rag_context, $model ) {
return [ 'roi_base' => 1000 ];
}

public function generate_comprehensive_business_case( $form_data, $calculations, $rag_context, $chunk_callback = null ) {
return [ 'roi_base' => 1000 ];
}
}
}

if ( ! class_exists( 'RTBCB_RAG' ) ) {
class RTBCB_RAG {
public function get_context( $description ) {
return [];
}
}
}

if ( ! class_exists( 'RTBCB_Leads' ) ) {
class RTBCB_Leads {
public function save_lead( $form_data, $business_case_data ) {
return 1;
}
}
}

if ( ! class_exists( 'RTBCB_Validator' ) ) {
class RTBCB_Validator {
public function validate( $data ) {
$required = [ 'company_name', 'company_size', 'email' ];
foreach ( $required as $field ) {
if ( empty( $data[ $field ] ) ) {
return [ 'error' => $field . ' required' ];
}
}

$numerics = [ 'hours_reconciliation', 'hours_cash_positioning', 'num_banks', 'ftes' ];
foreach ( $numerics as $field ) {
if ( isset( $data[ $field ] ) && $data[ $field ] < 0 ) {
return [ 'error' => $field . ' must be non-negative' ];
}
}

$data['company_description'] = $data['company_name'] . ' ' . ( $data['industry'] ?? '' );
return $data;
}
}
}

require_once __DIR__ . '/../inc/class-rtbcb-router.php';

final class RTBCB_EdgeCasesTest extends TestCase {
/**
	* @dataProvider edge_case_provider
	*/
public function test_handle_form_submission( $post_data, $expected_success ) {
global $last_response;
$last_response = null;
$_POST         = $post_data;

$router = new RTBCB_Router();
$router->handle_form_submission();

$this->assertNotNull( $last_response, 'No response captured.' );
$this->assertSame( $expected_success, $last_response['success'] );
}

public function edge_case_provider() {
return [
'extreme_numeric_values' => [
[
'company_name'           => 'MegaCorp',
'company_size'           => '999999',
'industry'               => 'finance',
'hours_reconciliation'   => 10000,
'hours_cash_positioning' => 20000,
'num_banks'              => 1000,
'ftes'                   => 1000,
'current_tech'           => 'legacy',
'business_objective'     => 'growth',
'implementation_timeline'=> 'immediate',
'decision_makers'        => [ 'CFO', 'CEO' ],
'budget_range'           => 'over-9000',
'email'                  => 'edge@example.com',
'consent'                => '1',
'rtbcb_nonce'            => 'nonce',
],
true,
],
'missing_optional_fields' => [
[
'company_name' => 'Minimal LLC',
'company_size' => 'small',
'email'        => 'minimal@example.com',
'consent'      => '1',
'rtbcb_nonce'  => 'nonce',
],
true,
],
'unusual_character_sets' => [
[
'company_name'       => '公司🚀',
'company_size'       => '中',
'industry'           => '金融',
'business_objective' => 'Expand to new markets 🌐',
'email'              => 'unicode@example.com',
'consent'            => '1',
'rtbcb_nonce'        => 'nonce',
],
true,
],
'negative_numeric_values' => [
[
'company_name'         => 'Negative Co',
'company_size'         => 'small',
'email'                => 'neg@example.com',
'consent'              => '1',
'hours_reconciliation' => -5,
'rtbcb_nonce'          => 'nonce',
],
false,
],
'missing_required_field' => [
[
'company_size' => 'small',
'email'        => 'no-name@example.com',
'consent'      => '1',
'rtbcb_nonce'  => 'nonce',
],
false,
],
];
}
}

<?php
if ( ! defined( 'ABSPATH' ) ) {
define( 'ABSPATH', __DIR__ . '/../' );
}
if ( ! defined( 'RTBCB_DIR' ) ) {
define( 'RTBCB_DIR', __DIR__ . '/../' );
}
if ( ! defined( 'HOUR_IN_SECONDS' ) ) {
define( 'HOUR_IN_SECONDS', 3600 );
}
defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/wp-stubs.php';

use PHPUnit\Framework\TestCase;

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
if ( ! function_exists( 'esc_html__' ) ) {
function esc_html__( $text, $domain = null ) {
return $text;
}
}
if ( ! function_exists( 'esc_html' ) ) {
function esc_html( $text ) {
return $text;
}
}
if ( ! function_exists( 'esc_html_e' ) ) {
function esc_html_e( $text, $domain = null ) {
echo $text;
}
}
if ( ! function_exists( 'esc_attr' ) ) {
function esc_attr( $text ) {
return $text;
}
}
if ( ! function_exists( 'esc_js' ) ) {
function esc_js( $text ) {
return $text;
}
}
if ( ! function_exists( 'esc_url' ) ) {
function esc_url( $url ) {
return $url;
}
}
if ( ! function_exists( 'wp_verify_nonce' ) ) {
function wp_verify_nonce( $nonce, $action ) {
return true;
}
}
if ( ! function_exists( 'wp_send_json_success' ) ) {
function wp_send_json_success( $data = null, $status = null ) {
global $last_response;
$last_response = [ 'success' => true, 'data' => $data, 'status' => $status ];
return $last_response;
}
}
if ( ! function_exists( 'wp_send_json_error' ) ) {
function wp_send_json_error( $data = null, $status = null ) {
global $last_response;
$last_response = [ 'success' => false, 'data' => $data, 'status' => $status ];
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
if ( ! function_exists( 'sanitize_key' ) ) {
function sanitize_key( $key ) {
return preg_replace( '/[^a-z0-9_]/', '', strtolower( $key ) );
}
}
if ( ! function_exists( 'current_time' ) ) {
function current_time( $type ) {
return '2024-01-01';
}
}
if ( ! function_exists( 'number_format_i18n' ) ) {
function number_format_i18n( $number, $decimals = 0 ) {
return number_format( $number, $decimals );
}
}
if ( ! function_exists( 'rtbcb_heavy_features_disabled' ) ) {
function rtbcb_heavy_features_disabled() {
return false;
}
}
if ( ! function_exists( 'rtbcb_sanitize_report_html' ) ) {
function rtbcb_sanitize_report_html( $html ) {
return $html;
}
}
if ( ! function_exists( 'rtbcb_get_report_cache_version' ) ) {
function rtbcb_get_report_cache_version() {
return '1';
}
}
if ( ! function_exists( 'rtbcb_log_error' ) ) {
function rtbcb_log_error( $msg, $context = [] ) {}
}
if ( ! function_exists( 'rtbcb_log_api_debug' ) ) {
function rtbcb_log_api_debug( $msg, $context = [] ) {}
}
if ( ! function_exists( 'wp_cache_get' ) ) {
function wp_cache_get( $key, $group = '' ) {
return false;
}
}
if ( ! function_exists( 'wp_cache_set' ) ) {
function wp_cache_set( $key, $data, $group = '', $expire = 0 ) {}
}

if ( ! class_exists( 'RTBCB_Settings' ) ) {
class RTBCB_Settings {
public static function get_setting( $key, $default = null ) {
return $default;
}
}
}
if ( ! class_exists( 'RTBCB_Logger' ) ) {
class RTBCB_Logger {
public static function log( $tag, $data = [] ) {}
}
}
if ( ! class_exists( 'RTBCB_Calculator' ) ) {
class RTBCB_Calculator {
public static function calculate_roi( $data ) {
return [ 'base' => [ 'roi_percentage' => 0 ], 'conservative' => [ 'roi_percentage' => 0 ], 'optimistic' => [ 'roi_percentage' => 0 ] ];
}
public static function calculate_category_refined_roi( $data, $category ) {
return self::calculate_roi( $data );
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
public static function save_lead( $data ) {
return 1;
}
}
}
if ( ! class_exists( 'RTBCB_LLM_Optimized' ) ) {
class RTBCB_LLM_Optimized {
public static $mode = 'success';
public function generate_comprehensive_business_case( $form_data, $calculations, $rag_context, $chunk_callback = null ) {
if ( 'error' === self::$mode ) {
return new WP_Error( 'missing_sections', 'Required sections missing.', [ 'status' => 500 ] );
}
return [
    'report_data' => [
    'action_plan'         => [ 'immediate_steps' => [ 'Step 1' ] ],
    'operational_insights' => [
        'current_state_assessment' => [ 'Manual process' ],
        'process_improvements'     => [
            [
                'process_area'   => 'Reconciliation',
                'current_state'  => 'Manual spreadsheets',
                'improved_state' => 'Automated workflow',
                'impact_level'   => 'High',
            ],
        ],
        'automation_opportunities' => [
            [
                'opportunity'   => 'Cash Forecasting',
                'complexity'    => 'Medium',
                'time_savings'  => '10 hours',
            ],
        ],
    ],
    'risk_analysis'       => [ 'Risk' ],
    'company_intelligence'=> [],
    'executive_summary'   => [],
    'financial_analysis'  => [],
    'technology_strategy' => [],
    'financial_benchmarks'=> [],
    'metadata'            => [],
    ],
];
}
public function generate_business_case( $form_data, $calculations, $rag_context, $model ) {
return [
'report_data' => [
'executive_summary' => [ 'summary' => 'basic' ],
],
];
}
}
}

require_once __DIR__ . '/../inc/class-rtbcb-validator.php';
require_once __DIR__ . '/../inc/class-rtbcb-router.php';

final class RTBCB_ReportTypeTest extends TestCase {
protected function setUp(): void {
global $last_response;
$last_response = null;
$_POST         = [
'rtbcb_nonce'  => 'nonce',
'company_name' => 'Acme',
'company_size' => '100-500',
'email'        => 'user@corp.com',
];
RTBCB_LLM_Optimized::$mode = 'success';
}

    public function test_comprehensive_includes_sections() {
        $router = new RTBCB_Router();
        $router->handle_form_submission( 'comprehensive' );
        global $last_response;
        $this->assertTrue( $last_response['success'] );
        $html = $last_response['data']['report_html'] ?? '';
        $this->assertStringContainsString( 'Implementation Action Plan', $html );
        $this->assertStringContainsString( 'Operational Insights', $html );
        $this->assertStringContainsString( 'Risk Assessment', $html );
    }

    public function test_operational_insights_section_populates() {
        $router = new RTBCB_Router();
        $router->handle_form_submission( 'comprehensive' );
        global $last_response;
        $this->assertTrue( $last_response['success'] );
        $html = $last_response['data']['report_html'] ?? '';
        $this->assertStringContainsString( 'Reconciliation', $html );
        $this->assertStringContainsString( 'Cash Forecasting', $html );
        $this->assertStringNotContainsString( 'No data provided', $html );
    }

public function test_basic_omits_sections() {
$router = new RTBCB_Router();
$router->handle_form_submission( 'basic' );
global $last_response;
$this->assertTrue( $last_response['success'] );
$html = $last_response['data']['report_html'] ?? '';
$this->assertStringNotContainsString( 'Implementation Action Plan', $html );
$this->assertStringNotContainsString( 'Operational Insights', $html );
$this->assertStringNotContainsString( 'Risk Assessment', $html );
}

public function test_missing_sections_returns_error() {
RTBCB_LLM_Optimized::$mode = 'error';
$router = new RTBCB_Router();
$router->handle_form_submission( 'comprehensive' );
global $last_response;
$this->assertFalse( $last_response['success'] );
$this->assertSame( 'Required sections missing.', $last_response['data']['message'] );
$this->assertSame( 'missing_sections', $last_response['data']['error_code'] );
}
}

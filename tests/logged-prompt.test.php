<?php
if ( ! defined( 'ABSPATH' ) ) {
define( 'ABSPATH', __DIR__ . '/../' );
}

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/wp-stubs.php';

if ( ! function_exists( 'sanitize_key' ) ) {
function sanitize_key( $key ) {
$key = strtolower( $key );
return preg_replace( '/[^a-z0-9_\-]/', '', $key );
}
}
if ( ! function_exists( 'sanitize_title' ) ) {
function sanitize_title( $title ) {
$title = strtolower( $title );
$title = preg_replace( '/[^a-z0-9]+/', '-', $title );
return trim( $title, '-' );
}
}
if ( ! function_exists( 'sanitize_textarea_field' ) ) {
function sanitize_textarea_field( $text ) {
return is_scalar( $text ) ? trim( (string) $text ) : '';
}
}
if ( ! function_exists( 'get_transient' ) ) {
function get_transient( $key ) {
global $transients;
return $transients[ $key ] ?? false;
}
}
if ( ! function_exists( 'set_transient' ) ) {
function set_transient( $key, $value, $expiration ) {
global $transients;
$transients[ $key ] = $value;
return true;
}
}
if ( ! function_exists( 'delete_transient' ) ) {
function delete_transient( $key ) {
global $transients;
unset( $transients[ $key ] );
return true;
}
}
if ( ! function_exists( '__' ) ) {
function __( $text, $domain = null ) {
return $text;
}
}
if ( ! function_exists( 'wp_remote_retrieve_body' ) ) {
function wp_remote_retrieve_body( $response ) {
return $response['body'] ?? '';
}
}
if ( ! class_exists( 'RTBCB_Logger' ) ) {
class RTBCB_Logger {
public static function log( $event, $context = [] ) {}
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

if ( ! defined( 'DAY_IN_SECONDS' ) ) {
define( 'DAY_IN_SECONDS', 86400 );
}

require_once __DIR__ . '/../inc/class-rtbcb-llm.php';

class Fake_Transport {
public $received_prompt;
public function call_openai_with_retry( $model, $prompt, $max_output_tokens = null, $max_retries = null, $chunk_handler = null ) {
$this->received_prompt = $prompt;
return [ 'body' => wp_json_encode( [ 'output_text' => wp_json_encode( [
'executive_summary'    => [],
'company_intelligence' => [],
'operational_insights' => [],
'risk_analysis'        => [],
'action_plan'          => [],
'financial_benchmarks' => [],
'technology_strategy'  => [ 'implementation_roadmap' => [] ],
'financial_analysis'   => [],
] ) ] ) ];
}
}

class Fake_Response_Parser {
public function parse_business_case( $response ) {
return [
'executive_summary'    => [],
'company_intelligence' => [],
'operational_insights' => [],
'risk_analysis'        => [],
'action_plan'          => [],
'financial_benchmarks' => [],
'technology_strategy'  => [],
'financial_analysis'   => [],
];
}
}

use PHPUnit\Framework\TestCase;

class LoggedPromptTest extends TestCase {
protected function tearDown(): void {
global $transients;
$transients = [];
}

public function test_prompt_is_logged() {
global $transients;
$company  = 'Test Co';
$industry = 'finance';
$company_key   = rtbcb_get_research_cache_key( $company, $industry, 'company' );
$industry_key  = rtbcb_get_research_cache_key( $company, $industry, 'industry' );
$treasury_key  = rtbcb_get_research_cache_key( $company, $industry, 'treasury' );
$risk_key      = rtbcb_get_research_cache_key( $company, $industry, 'risk' );
$financial_key = rtbcb_get_research_cache_key( $company, $industry, 'financial' );
$transients[ $company_key ] = [
'company_profile'  => [
'business_stage'      => '',
'key_characteristics' => '',
'treasury_priorities' => '',
'common_challenges'   => '',
],
'treasury_maturity' => [ 'level' => '', 'rationale' => '' ],
];
$transients[ $industry_key ]  = [ 'analysis' => '', 'recommendations' => [], 'references' => [], 'errors' => [] ];
$transients[ $treasury_key ]  = 'landscape';
$transients[ $risk_key ]      = [ 'risk_matrix' => [], 'mitigations' => [] ];
$transients[ $financial_key ] = [ 'industry_benchmarks' => [], 'valuation_multiples' => [] ];

$transport = new Fake_Transport();
$parser    = new Fake_Response_Parser();
$config    = new class extends RTBCB_LLM_Config {
public function __construct() {}
public function get_api_key() { return 'test'; }
public function estimate_tokens( $words ) { return $words; }
};

$llm = new RTBCB_LLM();
$prop = new ReflectionProperty( RTBCB_LLM::class, 'config' );
$prop->setAccessible( true );
$prop->setValue( $llm, $config );
$prop = new ReflectionProperty( RTBCB_LLM::class, 'transport' );
$prop->setAccessible( true );
$prop->setValue( $llm, $transport );
$prop = new ReflectionProperty( RTBCB_LLM::class, 'response_parser' );
$prop->setAccessible( true );
$prop->setValue( $llm, $parser );

$user_inputs = [
'company_name'           => $company,
'industry'               => $industry,
'company_size'           => 'SMB',
'hours_reconciliation'   => 1,
'hours_cash_positioning' => 1,
'num_banks'              => 1,
'ftes'                   => 1,
'pain_points'            => [],
];

$llm->generate_comprehensive_business_case( $user_inputs, [], [] );
$this->assertSame( $transport->received_prompt, $llm->get_last_prompt() );
}
}

class_alias( LoggedPromptTest::class, 'logged-prompt' );


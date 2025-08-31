<?php
use PHPUnit\Framework\TestCase;

if ( ! class_exists( 'WP_Error' ) ) {
class WP_Error {
private $code;
private $message;
private $data;

public function __construct( $code = '', $message = '', $data = [] ) {
$this->code	   = $code;
$this->message = $message;
$this->data	   = $data;
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

if ( ! function_exists( '__' ) ) {
function __( $text, $domain = null ) {
return $text;
}
}

if ( ! function_exists( 'rtbcb_has_openai_api_key' ) ) {
function rtbcb_has_openai_api_key() {
return true;
}
}

if ( ! function_exists( 'rtbcb_get_api_timeout' ) ) {
function rtbcb_get_api_timeout() {
return 100;
}
}

if ( ! function_exists( 'rtbcb_log_error' ) ) {
function rtbcb_log_error( $message, $details = '' ) {
}
}

class RTBCB_LLM {
public function generate_comprehensive_business_case( $user_inputs, $scenarios, $rag_context, $chunk_callback = null ) {
return $this->call_openai_with_retry( '', '', 0 );
}

public function call_openai_with_retry( $model, $prompt, $max_output_tokens = null, $max_retries = null, $chunk_handler = null ) {
return new WP_Error( 'llm_timeout', 'Request timed out' );
}
}

class Real_Treasury_BCB {
private function generate_business_analysis( $user_inputs, $scenarios, $recommendation, $chunk_callback = null ) {
$start_time		 = microtime( true );
$timeout		 = rtbcb_get_api_timeout();
$time_remaining	 = static function() use ( $start_time, $timeout ) {
return $timeout - ( microtime( true ) - $start_time );
};
$rag_context	 = [];
$rag_loader		 = function() use ( &$rag_context ) {
return $rag_context;
};

if ( ! class_exists( 'RTBCB_LLM' ) ) {
return [
'analysis'	  => new WP_Error( 'llm_unavailable', __( 'AI analysis service unavailable.', 'rtbcb' ) ),
'rag_context' => [],
];
}

if ( ! rtbcb_has_openai_api_key() ) {
return [
'analysis'	  => $this->generate_fallback_analysis( $user_inputs, $scenarios ),
'rag_context' => [],
];
}

if ( $time_remaining() < 5 ) {
return [
'analysis'	  => $this->generate_fallback_analysis( $user_inputs, $scenarios ),
'rag_context' => [],
];
}

try {
$llm	= new RTBCB_LLM();
$result = $llm->generate_comprehensive_business_case( $user_inputs, $scenarios, $rag_loader, $chunk_callback );

if ( is_wp_error( $result ) ) {
return [
'analysis'	  => $this->generate_fallback_analysis( $user_inputs, $scenarios ),
'rag_context' => [],
];
}

return [
'analysis'	  => $result,
'rag_context' => $rag_context,
];
} catch ( Exception $e ) {
rtbcb_log_error( 'LLM analysis failed', $e->getMessage() );
return [
'analysis'	  => $this->generate_fallback_analysis( $user_inputs, $scenarios ),
'rag_context' => [],
];
}
}

private function generate_fallback_analysis( $user_inputs, $scenarios ) {
$company_name = $user_inputs['company_name'];
$base_roi	  = $scenarios['base']['total_annual_benefit'] ?? 0;

return [
'executive_summary' => sprintf(
__( '%s has significant opportunities to improve treasury operations through technology automation. Based on current processes, implementing a modern treasury management system could deliver substantial ROI while reducing operational risk.', 'rtbcb' ),
$company_name
),
'narrative'			=> sprintf(
__( 'Our analysis of %s treasury operations reveals opportunities for process automation and efficiency gains. Key areas for improvement include cash management, bank reconciliation, and reporting processes.', 'rtbcb' ),
$company_name
),
'key_benefits'		=> [
__( 'Automated cash positioning and forecasting', 'rtbcb' ),
__( 'Streamlined bank reconciliation processes', 'rtbcb' ),
__( 'Enhanced regulatory compliance and reporting', 'rtbcb' ),
__( 'Improved operational risk management', 'rtbcb' ),
],
'risks'				=> [
__( 'Implementation complexity and timeline risk', 'rtbcb' ),
__( 'User adoption and change management challenges', 'rtbcb' ),
__( 'Integration complexity with existing systems', 'rtbcb' ),
],
'next_actions'		=> [
__( 'Secure executive sponsorship and project funding', 'rtbcb' ),
__( 'Conduct detailed requirements analysis', 'rtbcb' ),
__( 'Evaluate treasury technology vendors', 'rtbcb' ),
__( 'Develop implementation roadmap and timeline', 'rtbcb' ),
],
'confidence'		=> 0.75,
'enhanced_fallback' => true,
];
}
}

final class RTBCB_GenerateBusinessAnalysisTimeoutTest extends TestCase {
public function test_timeout_returns_fallback_analysis() {
$plugin	 = new Real_Treasury_BCB();
$method	 = new ReflectionMethod( Real_Treasury_BCB::class, 'generate_business_analysis' );
$method->setAccessible( true );

$user_inputs = [ 'company_name' => 'Test Co' ];
$scenarios	 = [ 'base' => [ 'total_annual_benefit' => 1000 ] ];

$result = $method->invoke( $plugin, $user_inputs, $scenarios, [] );

$this->assertIsArray( $result );
$this->assertArrayHasKey( 'analysis', $result );
$this->assertArrayHasKey( 'enhanced_fallback', $result['analysis'] );
$this->assertTrue( $result['analysis']['enhanced_fallback'] );
$this->assertArrayHasKey( 'executive_summary', $result['analysis'] );
}
}


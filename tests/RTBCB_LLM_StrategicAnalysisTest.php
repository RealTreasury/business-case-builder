<?php
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/wp-stubs.php';

if ( ! function_exists( '__' ) ) {
function __( $text, $domain = 'rtbcb' ) {
return $text;
}
}
if ( ! function_exists( 'sanitize_key' ) ) {
function sanitize_key( $key ) {
return preg_replace( '/[^a-z0-9_]/', '', strtolower( $key ) );
}
}
if ( ! function_exists( 'rtbcb_heavy_features_disabled' ) ) {
function rtbcb_heavy_features_disabled() {
return false;
}
}
if ( ! function_exists( 'wp_remote_retrieve_body' ) ) {
function wp_remote_retrieve_body( $response ) {
return $response['body'] ?? '';
}
}
if ( ! class_exists( 'WP_Error' ) ) {
class WP_Error {
public function __construct( $code = '', $message = '', $data = [] ) {
$this->code    = $code;
$this->message = $message;
}
public function get_error_message() {
return $this->message;
}
}
}

require_once __DIR__ . '/../inc/class-rtbcb-llm-config.php';
require_once __DIR__ . '/../inc/class-rtbcb-llm-transport.php';
require_once __DIR__ . '/../inc/class-rtbcb-llm-response-parser.php';
require_once __DIR__ . '/../inc/class-rtbcb-llm-prompt.php';
require_once __DIR__ . '/../inc/class-rtbcb-llm.php';

final class RTBCB_LLM_StrategicAnalysisTest extends TestCase {
public function test_generate_strategic_analysis_uses_transport() {
$llm = new class extends RTBCB_LLM {
public function __construct() {
$this->config = new class extends RTBCB_LLM_Config {
public function get_api_key() {
return 'test';
}
};
$this->prompt_builder = new RTBCB_LLM_Prompt();
$this->response_parser = new RTBCB_LLM_Response_Parser();
$this->transport = new class( $this->config ) extends RTBCB_LLM_Transport {
public $called = false;
public function call_openai_with_retry( $model, $prompt, $max_output_tokens = null, $max_retries = null, $chunk_handler = null ) {
$this->called = true;
$data = [
'executive_summary'      => [],
'operational_insights'   => [],
'financial_analysis'     => [],
'implementation_roadmap' => [],
'risk_analysis'          => [],
'action_plan'            => [],
'vendor_considerations'  => [],
];
return [ 'body' => wp_json_encode( [ 'choices' => [ [ 'message' => [ 'content' => wp_json_encode( $data ) ] ] ] ] ) ];
}
};
}
};

$result = $llm->generate_strategic_analysis( [], [], [], [] );
$this->assertTrue( $llm->transport->called );
$this->assertIsArray( $result );
$this->assertArrayHasKey( 'executive_summary', $result );
}
}


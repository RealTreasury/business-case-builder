<?php
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

if ( ! function_exists( 'rtbcb_log_error' ) ) {
	function rtbcb_log_error( $context, $message ) {}
}

$GLOBALS['rtbcb_has_key'] = true;
$GLOBALS['rtbcb_timeout'] = 100;
if ( ! function_exists( 'rtbcb_has_openai_api_key' ) ) {
	function rtbcb_has_openai_api_key() {
		return $GLOBALS['rtbcb_has_key'];
	}
}

if ( ! function_exists( 'rtbcb_get_api_timeout' ) ) {
	function rtbcb_get_api_timeout() {
		return $GLOBALS['rtbcb_timeout'];
	}
}

if ( ! class_exists( 'RTBCB_LLM' ) ) {
class RTBCB_LLM {
public static $called = false;
public static $sleep  = 0;
public function generate_comprehensive_business_case( $user_inputs, $scenarios, $rag_context, $chunk_callback = null ) {
self::$called = true;
if ( is_callable( $rag_context ) ) {
$rag_context = $rag_context();
}
if ( self::$sleep > 0 ) {
usleep( self::$sleep );
}
return [ 'executive_summary' => 'summary', 'context_used' => $rag_context ];
}
}
}

class RTBCB_Main {
public $fallback_called = false;

private function generate_business_analysis( $user_inputs, $scenarios, $recommendation, $chunk_callback = null ) {
$start_time      = microtime( true );
$timeout         = rtbcb_get_api_timeout();
$time_remaining  = static function() use ( $start_time, $timeout ) {
return $timeout - ( microtime( true ) - $start_time );
};
$rag_context     = [];
$rag_used        = false;
$rag_loader      = function() use ( &$rag_context, &$rag_used ) {
$rag_used    = true;
$rag_context = [ 'ctx' ];
return $rag_context;
};

if ( ! class_exists( 'RTBCB_LLM' ) ) {
return [
'analysis'    => new WP_Error( 'llm_unavailable', __( 'AI analysis service unavailable.', 'rtbcb' ) ),
'rag_context' => [],
];
}

if ( ! rtbcb_has_openai_api_key() ) {
return [
'analysis'    => $this->generate_fallback_analysis( $user_inputs, $scenarios ),
'rag_context' => [],
];
}

if ( $time_remaining() < 5 ) {
return [
'analysis'    => $this->generate_fallback_analysis( $user_inputs, $scenarios ),
'rag_context' => [],
];
}

try {
$llm    = new RTBCB_LLM();
$result = $llm->generate_comprehensive_business_case( $user_inputs, $scenarios, $rag_loader, $chunk_callback );

if ( is_wp_error( $result ) ) {
return [
'analysis'    => $this->generate_fallback_analysis( $user_inputs, $scenarios ),
'rag_context' => [],
];
}

if ( $time_remaining() < 2 ) {
return [
'analysis'    => [
'executive_summary' => $result['executive_summary'] ?? '',
'partial'          => true,
],
'rag_context' => $rag_used ? $rag_context : [],
];
}

return [
'analysis'    => $result,
'rag_context' => $rag_used ? $rag_context : [],
];
} catch ( Exception $e ) {
rtbcb_log_error( 'LLM analysis failed', $e->getMessage() );
return [
'analysis'    => $this->generate_fallback_analysis( $user_inputs, $scenarios ),
'rag_context' => [],
];
}
}

private function generate_fallback_analysis( $user_inputs, $scenarios ) {
$this->fallback_called = true;
return [ 'fallback' => true ];
}
}

final class Generate_Business_Analysis_Test extends TestCase {
	private $plugin;

	protected function setUp(): void {
		$this->plugin = new RTBCB_Main();
	}

	private function invoke_generate_business_analysis() {
		$reflection = new ReflectionClass( $this->plugin );
		$method     = $reflection->getMethod( 'generate_business_analysis' );
		$method->setAccessible( true );
		return $method->invoke( $this->plugin, [], [], [] );
	}

	public function test_llm_called_when_api_key_exists() {
		$GLOBALS['rtbcb_has_key'] = true;
		$GLOBALS['rtbcb_timeout'] = 100;
		RTBCB_LLM::$called        = false;
		$this->plugin->fallback_called = false;

		$this->invoke_generate_business_analysis();

		$this->assertTrue( RTBCB_LLM::$called, 'LLM should be called when API key exists.' );
		$this->assertFalse( $this->plugin->fallback_called, 'Fallback should not be called when API key exists.' );
	}

	public function test_fallback_called_when_no_api_key() {
		$GLOBALS['rtbcb_has_key'] = false;
		$GLOBALS['rtbcb_timeout'] = 100;
		RTBCB_LLM::$called        = false;
		$this->plugin->fallback_called = false;

		$this->invoke_generate_business_analysis();

		$this->assertFalse( RTBCB_LLM::$called, 'LLM should not be called without API key.' );
		$this->assertTrue( $this->plugin->fallback_called, 'Fallback should be called when no API key.' );
	}

	public function test_llm_skipped_when_timeout_low() {
		$GLOBALS['rtbcb_has_key'] = true;
		$GLOBALS['rtbcb_timeout'] = 3;
		RTBCB_LLM::$called        = false;
		$this->plugin->fallback_called = false;

		$this->invoke_generate_business_analysis();

		$this->assertFalse( RTBCB_LLM::$called, 'LLM should be skipped when timeout low.' );
		$this->assertTrue( $this->plugin->fallback_called, 'Fallback should run when timeout low.' );
	}

	public function test_partial_return_when_time_runs_out_after_llm() {
		$GLOBALS['rtbcb_has_key'] = true;
		$GLOBALS['rtbcb_timeout'] = 6;
		RTBCB_LLM::$sleep         = 5000000; // 5 seconds
		RTBCB_LLM::$called        = false;
		$this->plugin->fallback_called = false;

		$result = $this->invoke_generate_business_analysis();

		$this->assertTrue( RTBCB_LLM::$called, 'LLM should be called when time permits.' );
		$this->assertArrayHasKey( 'analysis', $result );
		$this->assertArrayHasKey( 'partial', $result['analysis'], 'Partial result expected when time runs out.' );
	}
}


<?php
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/../' );
}
defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/wp-stubs.php';

use PHPUnit\Framework\TestCase;

/**
 * Tests for RTBCB_LLM_Transport.
 */
final class RTBCB_LLM_TransportTest extends TestCase {
	public function test_requires_api_key() {
		require_once __DIR__ . '/../inc/class-rtbcb-llm-config.php';
		require_once __DIR__ . '/../inc/class-rtbcb-llm-transport.php';
		$config    = new RTBCB_LLM_Config();
		$transport = new RTBCB_LLM_Transport( $config );

		$result = $transport->call_openai_with_retry( 'gpt-test', 'prompt' );
		$this->assertTrue( is_wp_error( $result ) );
		$this->assertSame( 'no_api_key', $result->get_error_code() );
	}

	public function test_retries_and_chunk_handler() {
		require_once __DIR__ . '/../inc/class-rtbcb-llm-config.php';
		require_once __DIR__ . '/../inc/class-rtbcb-llm-transport.php';
		$config = new class extends RTBCB_LLM_Config {
			public function __construct() {}
			public function get_api_key() { return 'test'; }
			public function get_gpt5_config() { return [ 'timeout' => 1, 'max_retries' => 2, 'min_output_tokens' => 1 ]; }
		};
		$transport = new class( $config ) extends RTBCB_LLM_Transport {
			public $calls = 0;
			protected function call_openai( $model, $prompt, $max_tokens = null, $chunk_handler = null ) {
				$this->calls++;
				if ( $this->calls < 2 ) {
					return new WP_Error( 'llm_http_status', 'rate limited', [ 'status' => 429 ] );
				}
				if ( is_callable( $chunk_handler ) ) {
					call_user_func( $chunk_handler, 'partial' );
				}
				return [ 'body' => wp_json_encode( [ 'choices' => [ [ 'message' => [ 'content' => 'ok' ] ] ] ] ), 'response' => [ 'code' => 200, 'message' => '' ], 'headers' => [] ];
			}
		};
		$chunks   = '';
		$response = $transport->call_openai_with_retry( 'gpt-test', 'prompt', null, 2, function ( $data ) use ( &$chunks ) { $chunks .= $data; } );
		$this->assertSame( 2, $transport->calls );
		$this->assertNotEmpty( $chunks );
		$this->assertFalse( is_wp_error( $response ) );
	}
}

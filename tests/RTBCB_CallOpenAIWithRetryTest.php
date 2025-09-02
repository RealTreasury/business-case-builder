<?php
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/../' );
}
defined( 'ABSPATH' ) || exit;

if ( ! defined( 'HOUR_IN_SECONDS' ) ) {
	define( 'HOUR_IN_SECONDS', 3600 );
}

require_once __DIR__ . '/wp-stubs.php';
require_once __DIR__ . '/../inc/class-rtbcb-llm.php';

use PHPUnit\Framework\TestCase;

class RTBCB_Test_LLM extends RTBCB_LLM {
	public function call_openai_with_retry_public( $model, $prompt, $max_output_tokens = null, $max_retries = null, $chunk_handler = null ) {
		return $this->call_openai_with_retry( $model, $prompt, $max_output_tokens, $max_retries, $chunk_handler );
	}
}

class RTBCB_CallOpenAIWithRetryTest extends TestCase {
	public function test_call_openai_with_retry_returns_response() {
		$expected = [
			'body'     => 'ok',
			'response' => [
				'code'    => 200,
				'message' => '',
			],
			'headers'  => [],
		];

		$llm = $this->getMockBuilder( RTBCB_Test_LLM::class )
			->onlyMethods( [ 'call_openai' ] )
			->getMock();

		$llm->expects( $this->once() )
			->method( 'call_openai' )
			->willReturn( $expected );

		$result = $llm->call_openai_with_retry_public( 'model', 'prompt' );

		$this->assertSame( $expected, $result );
	}
}


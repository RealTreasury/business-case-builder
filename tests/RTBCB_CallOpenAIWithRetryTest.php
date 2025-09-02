<?php
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/../' );
}
defined( 'ABSPATH' ) || exit;

use PHPUnit\Framework\TestCase;

if ( ! class_exists( 'RTBCB_LLM' ) ) {
	class RTBCB_LLM {
		public function call_openai_with_retry( $model, $prompt, $max_output_tokens = null, $max_retries = null, $chunk_handler = null ) {
			return $this->call_openai( $model, $prompt, $max_output_tokens, $chunk_handler );
		}

		protected function call_openai( $model, $prompt, $max_output_tokens = null, $chunk_handler = null ) {
			return [];
		}
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

		$llm = $this->getMockBuilder( RTBCB_LLM::class )
			->onlyMethods( [ 'call_openai' ] )
			->getMock();

		$llm->expects( $this->once() )
			->method( 'call_openai' )
			->willReturn( $expected );

		$result = $llm->call_openai_with_retry( 'model', 'prompt' );

		$this->assertSame( $expected, $result );
	}
}

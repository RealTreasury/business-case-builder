<?php
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/../' );
}
defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/wp-stubs.php';
require_once __DIR__ . '/../inc/helpers.php';

use PHPUnit\Framework\TestCase;

/**
 * Tests rtbcb_extract_json_from_openai_response().
 */
final class RTBCB_ExtractOpenAIJsonTest extends TestCase {
	public function test_extracts_nested_json() {
		$raw_body = json_encode(
			[
				'output' => [
					[
						'id'      => 'reasoning',
						'type'    => 'reasoning',
						'content' => [ [ 'type' => 'reasoning', 'text' => 'thinking' ] ],
					],
					[
						'id'      => 'message',
						'type'    => 'message',
						'content' => [ [ 'type' => 'output_text', 'text' => '{"foo":"bar"}' ] ],
					],
				],
			]
		);

		$decoded = rtbcb_extract_json_from_openai_response( $raw_body );
		$this->assertIsArray( $decoded );
		$this->assertSame( 'bar', $decoded['foo'] );
	}
}

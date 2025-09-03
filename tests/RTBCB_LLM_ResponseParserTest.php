<?php
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/../' );
}
defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/wp-stubs.php';

use PHPUnit\Framework\TestCase;

/**
 * Tests for RTBCB_LLM_Response_Parser::process_openai_response().
 */
/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
final class RTBCB_LLM_ResponseParserTest extends TestCase {
       /**
       * Parser instance.
       *
       * @var RTBCB_LLM_Response_Parser
       */
       private $parser;
	
	protected function setUp(): void {
               require_once __DIR__ . '/../inc/class-rtbcb-llm-response-parser.php';
               $this->parser = new RTBCB_LLM_Response_Parser();
	}

	public function test_handles_valid_utf8() {
               $json    = '{"message":"Hello"}';
               $decoded = $this->parser->process_openai_response( $json );
		$this->assertSame( 'Hello', $decoded['message'] );
	}

	public function test_converts_iso_8859_1_to_utf8() {
               $json    = '{"text":"' . mb_convert_encoding( 'café', 'ISO-8859-1', 'UTF-8' ) . '"}';
               $decoded = $this->parser->process_openai_response( $json );
		$this->assertSame( 'café', $decoded['text'] );
	}

	public function test_returns_false_on_invalid_json() {
		$json = '{"text":"missing end"';
		$orig = ini_get( 'error_log' );
		ini_set( 'error_log', '/tmp/phpunit.log' );
               $this->assertFalse( $this->parser->process_openai_response( $json ) );
		ini_set( 'error_log', $orig );
	}

	public function test_handles_large_response() {
               $large   = str_repeat( 'a', 10000 );
               $json    = '{"text":"' . $large . '"}';
               $decoded = $this->parser->process_openai_response( $json );
               $this->assertSame( $large, $decoded['text'] );
       }

	public function test_preserves_usage_data() {
               $payload = [
                       'output_text' => json_encode( [ 'result' => 'ok' ] ),
                       'usage'       => [
                               'input_tokens'  => 5,
                               'output_tokens' => 7,
                               'total_tokens'  => 12,
                       ],
               ];
               $json    = json_encode( $payload );
               $decoded = $this->parser->process_openai_response( $json );
               $this->assertSame( 5, $decoded['usage']['input_tokens'] );
               $this->assertSame( 'ok', $decoded['result'] );
       }

	public function test_parses_streaming_response() {
		$stream  = "data: {\"choices\":[{\"delta\":{\"content\":\"Hello\"}}]}\n\n";
		$stream .= "data: {\"choices\":[{\"delta\":{\"content\":\" world\"}}]}\n\n";
		$stream .= "data: {\"choices\":[{\"message\":{\"role\":\"assistant\",\"content\":\"Hello world\"}}]}\n\n";
		$stream .= "data: [DONE]\n\n";
		$decoded = $this->parser->process_openai_response( $stream );
		$this->assertSame( 'Hello world', $decoded );
       }
}


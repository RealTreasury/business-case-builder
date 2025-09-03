<?php
if ( ! defined( 'ABSPATH' ) ) {
		define( 'ABSPATH', __DIR__ . '/../' );
}
defined( 'ABSPATH' ) || exit;

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/wp-stubs.php';

if ( ! function_exists( 'get_option' ) ) {
		function get_option( $name, $default = '' ) {
				return $default;
		}
}

if ( ! class_exists( 'WP_Error' ) ) {
	   class WP_Error {
			   public $errors = [];
			   public $data   = [];
			   public function __construct( $code = '', $message = '', $data = null ) {
					   $this->errors[ $code ] = [ $message ];
					   $this->data           = $data;
			   }
	   }
}

if ( ! function_exists( '__' ) ) {
		function __( $text, $domain = null ) {
				return $text;
		}
}

if ( ! function_exists( 'is_wp_error' ) ) {
		function is_wp_error( $thing ) {
				return $thing instanceof WP_Error;
		}
}

if ( ! function_exists( 'wp_remote_retrieve_body' ) ) {
		function wp_remote_retrieve_body( $response ) {
				return $response['body'] ?? '';
		}
}

if ( ! function_exists( 'sanitize_text_field' ) ) {
	   function sanitize_text_field( $text ) {
			   $text = is_scalar( $text ) ? (string) $text : '';
			   $text = preg_replace( '/[\r\n\t\0\x0B]/', '', $text );
			   return trim( $text );
	   }
}

if ( ! function_exists( 'sanitize_key' ) ) {
	   function sanitize_key( $key ) {
			   $key = strtolower( $key );
			   return preg_replace( '/[^a-z0-9_\-]/', '', $key );
	   }
}

if ( ! function_exists( 'wp_kses_post' ) ) {
	   function wp_kses_post( $text ) {
			   return $text;
	   }
}

require_once __DIR__ . '/../inc/class-rtbcb-response-parser.php';
require_once __DIR__ . '/../inc/class-rtbcb-llm.php';

final class RTBCB_ResponseParserTest extends TestCase {
		public function test_parse_business_case_success() {
				$valid = [
						'executive_summary'    => [],
						'company_intelligence' => [],
						'operational_insights' => [],
						'risk_analysis'        => [],
						'action_plan'          => [],
						'industry_insights'    => [],
						'technology_strategy'  => [],
						'financial_analysis'   => [],
				];
				$response = [ 'body' => json_encode( [ 'output_text' => json_encode( $valid ) ] ) ];
				$parser  = new RTBCB_Response_Parser();
				$result  = $parser->parse_business_case( $response );
				$this->assertIsArray( $result );
				$this->assertArrayHasKey( 'executive_summary', $result );
		}

		public function test_parse_business_case_malformed_json() {
				$response = [ 'body' => 'not json' ];
				$parser   = new RTBCB_Response_Parser();
				$result   = $parser->parse_business_case( $response );
				$this->assertTrue( is_wp_error( $result ) );
		}

		public function test_parse_business_case_missing_section() {
				$invalid  = [ 'executive_summary' => [] ];
				$response = [ 'body' => json_encode( [ 'output_text' => json_encode( $invalid ) ] ) ];
				$parser   = new RTBCB_Response_Parser();
				$result   = $parser->parse_business_case( $response );
				$this->assertTrue( is_wp_error( $result ) );
		}

		public function test_parse_marks_truncated_output() {
				$payload = [
						'status'      => 'incomplete',
						'output_text' => str_repeat( 'a', 25 ),
						'usage'       => [ 'output_tokens' => 15 ],
				];
				$response = [ 'body' => json_encode( $payload ) ];
				$parser   = new RTBCB_Response_Parser();
				$result   = $parser->parse( $response );
				$this->assertTrue( $result['truncated'] );
		}

		public function test_extracts_function_calls() {
				$payload = [
						'output' => [
								[
										'type' => 'function_call',
										'name' => 'test',
										'arguments' => '{}',
								],
						],
				];
				$response = [ 'body' => json_encode( $payload ) ];
				$parser   = new RTBCB_Response_Parser();
				$result   = $parser->parse( $response );
				$this->assertCount( 1, $result['function_calls'] );
		}

		public function test_validate_strategic_insights_normalizes_obstacle_count() {
				$ref    = new ReflectionClass( RTBCB_LLM::class );
				$llm    = $ref->newInstanceWithoutConstructor();
				$method = $ref->getMethod( 'validate_strategic_insights' );
				$method->setAccessible( true );
				
				$input  = [ 'potential_obstacles' => [ 'a', 'b', 'c' ] ];
				$result = $method->invoke( $llm, $input );
				$this->assertIsArray( $result );
				$this->assertCount( 4, $result['potential_obstacles'] );
				
				$input['potential_obstacles'][] = 'd';
				$input['potential_obstacles'][] = 'e';
				$input['potential_obstacles'][] = 'f';
				$result = $method->invoke( $llm, $input );
				$this->assertCount( 5, $result['potential_obstacles'] );
		}
}

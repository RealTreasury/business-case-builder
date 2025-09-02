<?php
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/../' );
}
defined( 'ABSPATH' ) || exit;

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/wp-stubs.php';
require_once __DIR__ . '/../inc/class-rtbcb-llm.php';

if ( ! class_exists( 'WP_Error' ) ) {
	class WP_Error {}
}

if ( ! function_exists( '__' ) ) {
	function __( $text, $domain = null ) {
		return $text;
	}
}

final class RTBCB_ParseGpt5ResponseTest extends TestCase {
	public function test_returns_empty_array_when_response_not_array() {
		$result = rtbcb_parse_gpt5_response( null );
		$this->assertIsArray( $result );
		$this->assertSame( '', $result['output_text'] );
	}

	public function test_propagates_wp_error_response() {
		$error  = new WP_Error( 'test', 'test' );
		$result = rtbcb_parse_gpt5_response( $error );
		$this->assertSame( $error, $result );
	}
}

<?php
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/../' );
}
defined( 'ABSPATH' ) || exit;

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
		public function get_error_code() {
			return $this->code;
		}
	}
}

if ( ! function_exists( '__' ) ) {
	function __( $text, $domain = null ) {
		return $text;
	}
}

require_once __DIR__ . '/../inc/class-rtbcb-llm.php';

final class RTBCB_ParseGpt5ResponseInvalidTest extends TestCase {
	public function test_null_response_returns_wp_error() {
		$result = rtbcb_parse_gpt5_response( null );
		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'invalid_response', $result->get_error_code() );
	}
}


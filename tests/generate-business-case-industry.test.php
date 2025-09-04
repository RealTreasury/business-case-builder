<?php
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/../' );
}
defined( 'ABSPATH' ) || exit;

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
		public function get_error_message() { return $this->message; }
		public function get_error_code() { return $this->code; }
		public function get_error_data() { return $this->data; }
	}
}
if ( ! function_exists( 'is_wp_error' ) ) {
	function is_wp_error( $thing ) {
		return $thing instanceof WP_Error;
	}
}

$GLOBALS['wp_options'] = [];
if ( ! function_exists( 'get_option' ) ) {
	function get_option( $option, $default = false ) {
		return $GLOBALS['wp_options'][ $option ] ?? $default;
	}
}
if ( ! function_exists( 'update_option' ) ) {
	function update_option( $option, $value ) {
		$GLOBALS['wp_options'][ $option ] = $value;
		return true;
	}
}

$GLOBALS['wp_transients'] = [];
if ( ! function_exists( 'get_transient' ) ) {
	function get_transient( $key ) {
		return $GLOBALS['wp_transients'][ $key ] ?? false;
	}
}
if ( ! function_exists( 'set_transient' ) ) {
	function set_transient( $key, $value, $ttl = 0 ) {
		$GLOBALS['wp_transients'][ $key ] = $value;
		return true;
	}
}
if ( ! function_exists( 'delete_transient' ) ) {
	function delete_transient( $key ) {
		unset( $GLOBALS['wp_transients'][ $key ] );
		return true;
	}
}

if ( ! function_exists( 'sanitize_textarea_field' ) ) {
	function sanitize_textarea_field( $text ) {
		return is_string( $text ) ? trim( $text ) : '';
	}
}
if ( ! function_exists( 'sanitize_key' ) ) {
	function sanitize_key( $key ) {
		return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( $key ) );
	}
}
if ( ! function_exists( 'sanitize_title' ) ) {
	function sanitize_title( $title ) {
		return preg_replace( '/[^a-z0-9]+/', '-', strtolower( $title ) );
	}
}

require_once __DIR__ . '/wp-stubs.php';

putenv( 'RTBCB_OPENAI_API_KEY=dummy' );

require_once __DIR__ . '/../inc/class-rtbcb-llm-unified.php';

use PHPUnit\Framework\TestCase;

final class GenerateBusinessCaseIndustryTest extends TestCase {
	public function test_prompt_uses_stored_industry() {
		update_option( 'rtbcb_current_company', [
			'company_name' => 'Acme Corp',
			'industry'     => 'Finance',
			'company_size' => 'SMB',
		] );

		$company_name    = 'Acme Corp';
		$stored_industry = 'Finance';

		$key_company   = rtbcb_get_research_cache_key( $company_name, $stored_industry, 'company' );
		$key_industry  = rtbcb_get_research_cache_key( $company_name, $stored_industry, 'industry' );
		$key_treasury  = rtbcb_get_research_cache_key( $company_name, $stored_industry, 'treasury' );
		$key_risk      = rtbcb_get_research_cache_key( $company_name, $stored_industry, 'risk' );
		$key_financial = rtbcb_get_research_cache_key( $company_name, $stored_industry, 'financial' );

		set_transient( $key_company, [
			'company_profile' => [
				'business_stage'      => '',
				'key_characteristics' => '',
				'treasury_priorities' => '',
				'common_challenges'   => '',
			],
		] );
		set_transient( $key_industry, [] );
		set_transient( $key_treasury, '' );
		set_transient( $key_risk, [] );
		set_transient( $key_financial, [] );

		$transport = new class extends RTBCB_LLM_Transport {
			public $captured;
			public function __construct() {}
			public function call_openai_with_retry( $model, $prompt, $max_output_tokens = null, $max_retries = null, $chunk_handler = null ) {
				$this->captured = $prompt;
				return new WP_Error( 'mock', 'mock' );
			}
		};

		$llm = new RTBCB_LLM();
		$ref = new ReflectionClass( $llm );
		$prop = $ref->getProperty( 'transport' );
		$prop->setAccessible( true );
		$prop->setValue( $llm, $transport );

		$inputs = [
			'company_name' => $company_name,
			'industry'     => 'Manufacturing',
			'company_size' => 'SMB',
		];

		$llm->generate_comprehensive_business_case( $inputs, [], [] );

		$this->assertStringContainsString( 'Finance', $transport->captured['input'] );
	}
}

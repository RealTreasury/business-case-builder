<?php
use PHPUnit\Framework\TestCase;

define( 'ABSPATH', __DIR__ . '/../' );

if ( ! function_exists( 'add_filter' ) ) {
	function add_filter( $tag, $function_to_add, $priority = 10, $accepted_args = 1 ) {
		// No-op for testing.
	}
}

if ( ! function_exists( '__' ) ) {
	function __( $text, $domain = null ) {
		return $text;
	}
}

if ( ! function_exists( 'sanitize_text_field' ) ) {
	function sanitize_text_field( $text ) {
		return is_string( $text ) ? trim( $text ) : '';
	}
}

if ( ! function_exists( 'sanitize_email' ) ) {
	function sanitize_email( $email ) {
		return filter_var( $email, FILTER_SANITIZE_EMAIL );
	}
}

if ( ! function_exists( 'wp_unslash' ) ) {
	function wp_unslash( $value ) {
		return $value;
	}
}

require_once __DIR__ . '/../inc/helpers.php';
require_once __DIR__ . '/../inc/class-rtbcb-validator.php';

final class RTBCB_ValidatorTest extends TestCase {
	public function test_missing_required_fields() {
		$validator = new RTBCB_Validator();

		$result = $validator->validate( [ 'email' => 'user@corp.com', 'company_size' => '100-500' ] );
		$this->assertSame( 'Company name is required.', $result['error'] );

		$result = $validator->validate( [ 'company_name' => 'Acme', 'company_size' => '100-500' ] );
		$this->assertSame( 'Email is required.', $result['error'] );

		$result = $validator->validate( [ 'company_name' => 'Acme', 'email' => 'user@corp.com' ] );
		$this->assertSame( 'Company size is required.', $result['error'] );
	}

	public function test_email_domain_validation() {
		$validator = new RTBCB_Validator();
		$result = $validator->validate( [
			'company_name' => 'Acme',
			'email'		   => 'user@gmail.com',
			'company_size' => '100-500',
		] );
		$this->assertSame( 'Please use your business email address.', $result['error'] );

		$this->assertTrue( rtbcb_is_business_email( 'user@company.com' ) );
		$this->assertFalse( rtbcb_is_business_email( 'user@yahoo.com' ) );
	}

	public function test_field_length_constraints() {
		$validator = new RTBCB_Validator();
		$long_name = str_repeat( 'a', 256 );
		$result = $validator->validate( [
			'company_name' => $long_name,
			'email'		   => 'user@corp.com',
			'company_size' => '100-500',
		] );
		$this->assertSame( 'Company Name cannot exceed 255 characters.', $result['error'] );

		$long_email = str_repeat( 'a', 250 ) . '@example.com';
		$result = $validator->validate( [
			'company_name' => 'Acme',
			'email'		   => $long_email,
			'company_size' => '100-500',
		] );
		$this->assertSame( 'Email cannot exceed 254 characters.', $result['error'] );
	}
}

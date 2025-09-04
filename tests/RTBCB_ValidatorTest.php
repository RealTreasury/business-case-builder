<?php
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/../' );
}
defined( 'ABSPATH' ) || exit;

use PHPUnit\Framework\TestCase;
require_once __DIR__ . '/wp-stubs.php';
require_once __DIR__ . '/../inc/helpers.php';

if ( ! function_exists( '__' ) ) {
function __( $text, $domain = null ) {
return $text;
}
}

final class RTBCB_ValidatorTest extends TestCase {
        protected function setUp(): void {
                require_once __DIR__ . '/../inc/class-rtbcb-validator.php';
        }

public function test_basic_required_fields() {
$validator = new RTBCB_Validator();

$result = $validator->validate( [ 'email' => 'user@corp.com', 'report_type' => 'basic' ] );
$this->assertSame( 'Company name is required.', $result['error'] );

$result = $validator->validate( [ 'company_name' => 'Acme', 'report_type' => 'basic' ] );
$this->assertSame( 'Email is required.', $result['error'] );

$result = $validator->validate( [ 'company_name' => 'Acme', 'email' => 'user@corp.com', 'report_type' => 'basic' ] );
$this->assertArrayNotHasKey( 'error', $result );
}

public function test_enhanced_required_fields() {
$validator = new RTBCB_Validator();

$result = $validator->validate( [
'company_name' => 'Acme',
'email' => 'user@corp.com',
'report_type' => 'enhanced',
] );
$this->assertSame( 'Company size is required.', $result['error'] );

$result = $validator->validate( [
'company_name' => 'Acme',
'email' => 'user@corp.com',
'company_size' => '100-500',
'industry' => 'Finance',
'job_title' => 'CFO',
'num_entities' => '2',
'num_currencies' => '1',
'hours_reconciliation' => '5',
'hours_cash_positioning' => '2',
'ftes' => '1',
'report_type' => 'enhanced',
] );
$this->assertSame( 'Number of banks is required.', $result['error'] );
}

public function test_email_domain_validation() {
$validator = new RTBCB_Validator();
$result = $validator->validate( [
'company_name' => 'Acme',
'email'        => 'user@gmail.com',
'report_type'  => 'basic',
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
'email'        => 'user@corp.com',
'report_type'  => 'basic',
] );
$this->assertSame( 'Company Name cannot exceed 255 characters.', $result['error'] );

$long_email = str_repeat( 'a', 250 ) . '@example.com';
$result = $validator->validate( [
'company_name' => 'Acme',
'email'        => $long_email,
'report_type'  => 'basic',
] );
$this->assertSame( 'Email cannot exceed 254 characters.', $result['error'] );
}
}

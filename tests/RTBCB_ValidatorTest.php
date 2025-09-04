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

public function test_basic_report_requires_minimum_fields() {
$validator = new RTBCB_Validator();

$result = $validator->validate( [ 'email' => 'user@corp.com' ], 'basic' );
$this->assertSame( 'Company name is required.', $result['error'] );

$result = $validator->validate( [ 'company_name' => 'Acme' ], 'basic' );
$this->assertSame( 'Email is required.', $result['error'] );

$result = $validator->validate(
        [
                'company_name' => 'Acme',
                'email'        => 'user@corp.com',
        ],
        'basic'
);
$this->assertArrayNotHasKey( 'error', $result );
}

public function test_enhanced_report_requires_all_fields() {
$validator = new RTBCB_Validator();

$base = [
        'company_name' => 'Acme',
        'email'        => 'user@corp.com',
];

$result = $validator->validate( $base, 'enhanced' );
$this->assertSame( 'Company size is required.', $result['error'] );

$base['company_size'] = '100-500';
$result               = $validator->validate( $base, 'enhanced' );
$this->assertSame( 'Industry is required.', $result['error'] );

       $base['industry'] = 'finance';
       $result           = $validator->validate( $base, 'enhanced' );
       $this->assertSame( 'Number of legal entities is required.', $result['error'] );

$base['num_entities'] = 1;
$result               = $validator->validate( $base, 'enhanced' );
$this->assertSame( 'Number of active currencies is required.', $result['error'] );

$base['num_currencies'] = 1;
$result                 = $validator->validate( $base, 'enhanced' );
$this->assertSame( 'Number of banking relationships is required.', $result['error'] );

$base['num_banks'] = 1;
$result            = $validator->validate( $base, 'enhanced' );
$this->assertSame( 'Bank reconciliation hours are required.', $result['error'] );

$base['hours_reconciliation'] = 1;
$result                       = $validator->validate( $base, 'enhanced' );
$this->assertSame( 'Cash positioning hours are required.', $result['error'] );

$base['hours_cash_positioning'] = 1;
$result                         = $validator->validate( $base, 'enhanced' );
$this->assertSame( 'Treasury team size is required.', $result['error'] );

$base['ftes'] = 1;
$result       = $validator->validate( $base, 'enhanced' );
$this->assertSame( 'Treasury workflow automation level is required.', $result['error'] );

$base['treasury_automation'] = 'manual';
$result                      = $validator->validate( $base, 'enhanced' );
$this->assertArrayNotHasKey( 'error', $result );
}

public function test_email_domain_validation() {
$validator = new RTBCB_Validator();
$result = $validator->validate( [
'company_name' => 'Acme',
'email'        => 'user@gmail.com',
], 'basic' );
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
], 'basic' );
$this->assertSame( 'Company Name cannot exceed 255 characters.', $result['error'] );

$long_email = str_repeat( 'a', 250 ) . '@example.com';
$result = $validator->validate( [
'company_name' => 'Acme',
'email'        => $long_email,
], 'basic' );
$this->assertSame( 'Email cannot exceed 254 characters.', $result['error'] );
}
}

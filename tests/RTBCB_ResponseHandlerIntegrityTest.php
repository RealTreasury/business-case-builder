<?php
if ( ! defined( 'ABSPATH' ) ) {

define( 'ABSPATH', __DIR__ . '/../' );
}
defined( 'ABSPATH' ) || exit;

use PHPUnit\Framework\TestCase;
require_once __DIR__ . '/wp-stubs.php';

if ( ! class_exists( 'RTBCB_Logger' ) ) {
class RTBCB_Logger {
public static function log( $event, $context = [] ) {}
}
}

if ( ! class_exists( 'RTBCB_API_Log' ) ) {
class RTBCB_API_Log {
public static function get_logs( $limit = 50 ) {
return [
[ 'id' => 1, 'response_json' => '{"foo":"bar"}' ],
[ 'id' => 2, 'response_json' => '{"foo":}' ],
];
}
}
}

require_once __DIR__ . '/../inc/class-rtbcb-response-handler.php';

final class RTBCB_ResponseHandlerIntegrityTest extends TestCase {
public function test_validate_response() {
$this->assertTrue( RTBCB_Response_Handler::validate_response( '{"a":1}' ) );
$this->assertFalse( RTBCB_Response_Handler::validate_response( '{"a":}' ) );
}

public function test_repair_response() {
$corrupted = '{"a":1,}';
$repaired  = RTBCB_Response_Handler::repair_response( $corrupted );
$this->assertTrue( RTBCB_Response_Handler::validate_response( $repaired ) );
}

public function test_detect_corruption_and_reprocess() {
$result = RTBCB_Response_Handler::detect_corruption( '{"a":1}', '{"a":2}' );
$this->assertTrue( $result['corrupted'] );
$this->assertContains( 'mismatch', $result['issues'] );

$processed = RTBCB_Response_Handler::reprocess_historical_data( function( $log, $repaired ) use ( & $ids ) {
$ids[] = $log['id'];
} );
$this->assertSame( 1, $processed );
$this->assertSame( [2], $ids );
}
}

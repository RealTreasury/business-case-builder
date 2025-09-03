<?php
if ( ! defined( 'ABSPATH' ) ) {
define( 'ABSPATH', __DIR__ . '/../' );
}
defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/wp-stubs.php';

use PHPUnit\Framework\TestCase;

/**
 * Tests for RTBCB_LLM_Transport.
 */
final class RTBCB_LLM_TransportTest extends TestCase {
public function test_requires_api_key() {
require_once __DIR__ . '/../inc/class-rtbcb-llm-config.php';
require_once __DIR__ . '/../inc/class-rtbcb-llm-transport.php';
$config    = new RTBCB_LLM_Config();
$transport = new RTBCB_LLM_Transport( $config );

$result = $transport->call_openai_with_retry( 'gpt-test', 'prompt' );
$this->assertTrue( is_wp_error( $result ) );
$this->assertSame( 'no_api_key', $result->get_error_code() );
}
}

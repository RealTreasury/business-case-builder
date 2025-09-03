<?php
if ( ! defined( 'ABSPATH' ) ) {
define( 'ABSPATH', __DIR__ . '/../' );
}
defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/wp-stubs.php';

use PHPUnit\Framework\TestCase;

/**
 * Tests for RTBCB_LLM_Config.
 */
final class RTBCB_LLM_ConfigTest extends TestCase {
/**
 * Config instance.
 *
 * @var RTBCB_LLM_Config
 */
private $config;

protected function setUp(): void {
require_once __DIR__ . '/../inc/class-rtbcb-llm-config.php';
$this->config = new RTBCB_LLM_Config();
}

public function test_get_model_defaults() {
$model = $this->config->get_model( 'mini' );
$this->assertSame( rtbcb_get_default_model( 'mini' ), $model );
}
}

<?php
if ( ! defined( 'ABSPATH' ) ) {
define( 'ABSPATH', __DIR__ . '/../' );
}
defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/wp-stubs.php';

use PHPUnit\Framework\TestCase;

/**
 * Tests for RTBCB_LLM_Prompt.
 */
final class RTBCB_LLM_PromptTest extends TestCase {
/**
 * Prompt builder.
 *
 * @var RTBCB_LLM_Prompt
 */
private $prompt;

protected function setUp(): void {
require_once __DIR__ . '/../inc/class-rtbcb-llm-prompt.php';
$this->prompt = new RTBCB_LLM_Prompt();
}

public function test_build_context_for_responses() {
$history = [
[ 'role' => 'user', 'content' => 'Hello' ],
];
$context = $this->prompt->build_context_for_responses( $history, 'System' );
$this->assertSame( 'System', $context['instructions'] );
$this->assertSame( 'Hello', $context['input'] );
}
}

<?php
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/../' );
}

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/wp-stubs.php';

use PHPUnit\Framework\TestCase;

/**
 * Ensure strategic system prompt includes required field names.
 */
final class RTBCB_StrategicPromptFieldsTest extends TestCase {
	public function test_system_prompt_mentions_required_fields() {
		require_once __DIR__ . '/../inc/class-rtbcb-llm-unified.php';
		$llm    = new RTBCB_LLM();
		$method = new ReflectionMethod( RTBCB_LLM::class, 'get_strategic_system_prompt' );
		$method->setAccessible( true );
		$prompt = $method->invoke( $llm );
		$fields = [
						'immediate_steps',
						'long_term_objectives',
						'short_term_milestones',
						'competitive_position',
						'key_challenges',
						'strategic_priorities',
						'maturity_assessment',
						'key_value_drivers',
						'investment_breakdown',
						'payback_analysis',
						'industry_insights',
						'automation_opportunities',
						'current_state_assessment',
						'process_improvements',
						'implementation_risks',
						'mitigation_strategies',
						'risk_matrix',
						'success_factors',
						'implementation_roadmap',
						'vendor_considerations',
				];
				foreach ( $fields as $field ) {
						$this->assertStringContainsString( $field, $prompt );
				}
		}
}


<?php
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/../' );
	}

defined( 'ABSPATH' ) || exit;

	require_once __DIR__ . '/wp-stubs.php';

use PHPUnit\Framework\TestCase;

/**
 * Ensure action plan keys are present in comprehensive prompts.
 */
final class RTBCB_ActionPlanKeysTest extends TestCase {
	public function test_build_comprehensive_prompt_includes_action_plan_keys() {
        require_once __DIR__ . '/../inc/class-rtbcb-llm-unified.php';
	$llm    = new RTBCB_LLM();
	$method = new ReflectionMethod( RTBCB_LLM::class, 'build_comprehensive_prompt' );
	$method->setAccessible( true );

	$user_inputs = [ 'company_name' => 'Acme', 'pain_points' => [] ];
	$roi_data = [ 'conservative' => [ 'total_annual_benefit' => 0 ], 'base' => [ 'total_annual_benefit' => 0 ], 'optimistic' => [ 'total_annual_benefit' => 0 ] ];
	$company_research = [ 'company_profile' => [ 'business_stage' => '', 'key_characteristics' => '', 'treasury_priorities' => '', 'common_challenges' => '' ] ];
	$prompt = $method->invoke( $llm, $user_inputs, $roi_data, $company_research, [], '', [], [] );

	$this->assertStringContainsString( '"action_plan"', $prompt );
	$this->assertStringContainsString( '"immediate_steps"', $prompt );
	$this->assertStringContainsString( '"short_term_milestones"', $prompt );
	$this->assertStringContainsString( '"long_term_objectives"', $prompt );
	}

	public function test_optimized_prompt_includes_action_plan_keys() {
        require_once __DIR__ . '/../inc/class-rtbcb-llm-unified.php';
	$llm    = new RTBCB_LLM_Optimized();
	$method = new ReflectionMethod( RTBCB_LLM_Optimized::class, 'build_comprehensive_prompt' );
	$method->setAccessible( true );

	$user_inputs = [ 'company_name' => 'Acme', 'pain_points' => [], 'business_objective' => '', 'implementation_timeline' => '', 'budget_range' => '' ];
	$roi_data = [ 'conservative' => [ 'total_annual_benefit' => 0 ], 'base' => [ 'total_annual_benefit' => 0 ], 'optimistic' => [ 'total_annual_benefit' => 0 ] ];
	$company_research = [ 'company_profile' => [ 'business_stage' => '', 'key_characteristics' => '', 'treasury_priorities' => '', 'common_challenges' => '' ] ];
	$prompt = $method->invoke( $llm, $user_inputs, $roi_data, $company_research, [], '', [], [] );

	$this->assertStringContainsString( '"action_plan"', $prompt );
	$this->assertStringContainsString( '"immediate_steps"', $prompt );
	$this->assertStringContainsString( '"short_term_milestones"', $prompt );
	$this->assertStringContainsString( '"long_term_objectives"', $prompt );
	}
	}


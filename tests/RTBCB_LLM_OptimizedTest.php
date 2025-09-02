<?php
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/../' );
}
defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/wp-stubs.php';
require_once __DIR__ . '/../inc/class-rtbcb-llm.php';
require_once __DIR__ . '/../inc/class-rtbcb-llm-optimized.php';

use PHPUnit\Framework\TestCase;

final class RTBCB_LLM_OptimizedTest extends TestCase {
	public function test_build_enrichment_user_prompt_formats_pain_points() {
		$llm	= new RTBCB_LLM_Optimized();
		$ref	= new ReflectionClass( $llm );
		$method = $ref->getMethod( 'build_enrichment_user_prompt' );
		$method->setAccessible( true );

		$prompt = $method->invoke( $llm, [
			'company_name'			  => 'TestCo',
			'industry'				  => 'Finance',
			'company_size'			  => 'SMB',
			'business_objective'	  => 'Growth',
			'implementation_timeline' => 'Q1',
			'budget_range'			  => '$100k-$200k',
			'ftes'					  => 5,
			'hours_reconciliation'	  => 10,
			'hours_cash_positioning'  => 8,
			'num_banks'				  => 2,
			'pain_points'			  => [ 'manual_processes', 'lack_of_visibility' ],
		] );

		$this->assertStringContainsString( 'Manual Processes, Lack Of Visibility', $prompt );
	}

	public function test_build_enrichment_user_prompt_handles_empty_pain_points() {
		$llm	= new RTBCB_LLM_Optimized();
		$ref	= new ReflectionClass( $llm );
		$method = $ref->getMethod( 'build_enrichment_user_prompt' );
		$method->setAccessible( true );

		$prompt = $method->invoke( $llm, [
			'company_name'			  => 'TestCo',
			'industry'				  => 'Finance',
			'company_size'			  => 'SMB',
			'business_objective'	  => 'Growth',
			'implementation_timeline' => 'Q1',
			'budget_range'			  => '$100k-$200k',
			'ftes'					  => 5,
			'hours_reconciliation'	  => 10,
			'hours_cash_positioning'  => 8,
			'num_banks'				  => 2,
			'pain_points'			  => [],
		] );

		$this->assertStringContainsString( 'None specified', $prompt );
	}
}

<?php
defined( 'ABSPATH' ) || exit;

/**
 * AJAX handlers for Business Case Builder.
 *
 * @package RealTreasuryBusinessCaseBuilder
 */
class RTBCB_Ajax {
	/**
	 * Generate comprehensive case via AJAX.
	 *
	 * @return void
	 */
	public static function generate_comprehensive_case() {
		if ( ! check_ajax_referer( 'rtbcb_generate', 'rtbcb_nonce', false ) ) {
			wp_send_json_error( __( 'Security check failed.', 'rtbcb' ), 403 );
			return;
		}

		$user_inputs = self::collect_and_validate_user_inputs();
		if ( is_wp_error( $user_inputs ) ) {
			wp_send_json_error( $user_inputs->get_error_message(), 400 );
			return;
		}

		$job_id = RTBCB_Background_Job::enqueue( $user_inputs );
		wp_send_json_success( [ 'job_id' => $job_id ] );
	}

	/**
	 * Process comprehensive case generation.
	 *
	 * @param array $user_inputs User inputs.
	 * @return array|WP_Error Result data or error.
	 */
	public static function process_comprehensive_case( $user_inputs ) {
		$request_start    = microtime( true );
		$workflow_tracker = new RTBCB_Workflow_Tracker();

		add_action(
			'rtbcb_llm_prompt_sent',
			function( $prompt ) use ( $workflow_tracker ) {
				$workflow_tracker->add_prompt( $prompt );
			}
		);

		try {
			$workflow_tracker->start_step( 'ai_enrichment' );
			$enriched_profile = new WP_Error( 'llm_missing', 'LLM service unavailable.' );
			if ( class_exists( 'RTBCB_LLM' ) ) {
				$llm = new RTBCB_LLM();
				if ( method_exists( $llm, 'enrich_company_profile' ) ) {
					$enriched_profile = $llm->enrich_company_profile( $user_inputs );
				}
			}
			if ( is_wp_error( $enriched_profile ) ) {
				$enriched_profile = self::create_fallback_profile( $user_inputs );
				$workflow_tracker->add_warning( 'ai_enrichment_failed', $enriched_profile->get_error_message() );
			}
			$workflow_tracker->complete_step( 'ai_enrichment', $enriched_profile );

			$workflow_tracker->start_step( 'enhanced_roi_calculation' );
			$enhanced_calculator = new RTBCB_Enhanced_Calculator();
			$roi_scenarios       = $enhanced_calculator->calculate_enhanced_roi( $user_inputs, $enriched_profile );
			$workflow_tracker->complete_step( 'enhanced_roi_calculation', $roi_scenarios );

			$workflow_tracker->start_step( 'intelligent_recommendations' );
			$intelligent_recommender = new RTBCB_Intelligent_Recommender();
			$recommendation          = $intelligent_recommender->recommend_with_ai_insights( $user_inputs, $enriched_profile );
			$workflow_tracker->complete_step( 'intelligent_recommendations', $recommendation );

			$workflow_tracker->start_step( 'hybrid_rag_analysis' );
			$rag_baseline = [];
			if ( class_exists( 'RTBCB_RAG' ) ) {
				$rag          = new RTBCB_RAG();
				$search_query = self::build_rag_search_query( $user_inputs, $enriched_profile );
				$rag_baseline = $rag->search_similar( $search_query, 5 );
			}
			$final_analysis = new WP_Error( 'analysis_unavailable', 'Final analysis unavailable.' );
			if ( isset( $llm ) && method_exists( $llm, 'generate_strategic_analysis' ) ) {
				$final_analysis = $llm->generate_strategic_analysis( $enriched_profile, $roi_scenarios, $recommendation, $rag_baseline );
			}
			if ( is_wp_error( $final_analysis ) ) {
				$final_analysis = self::create_fallback_analysis( $enriched_profile, $roi_scenarios );
				$workflow_tracker->add_warning( 'final_analysis_failed', $final_analysis->get_error_message() );
			}
			$workflow_tracker->complete_step( 'hybrid_rag_analysis', $final_analysis );

			$workflow_tracker->start_step( 'data_structuring' );
			$structured_report_data = self::structure_report_data( $user_inputs, $enriched_profile, $roi_scenarios, $recommendation, $final_analysis, $request_start );
			$workflow_tracker->complete_step( 'data_structuring', $structured_report_data );

$lead_id = self::save_lead_data_async( $user_inputs, $structured_report_data );

$debug_info          = $workflow_tracker->get_debug_info();
$debug_info['lead'] = [
'id'    => $lead_id,
'email' => $user_inputs['email'] ?? '',
];
self::store_workflow_history( $debug_info );

			return [
				'report_data'   => $structured_report_data,
				'workflow_info' => $debug_info,
				'lead_id'       => $lead_id,
				'analysis_type' => 'enhanced_comprehensive',
			];
		} catch ( Exception $e ) {
			$workflow_tracker->add_error( 'exception', $e->getMessage() );
			rtbcb_log_error( 'Ajax exception in new workflow', $e->getMessage() );
			$debug_info = $workflow_tracker->get_debug_info();
			self::store_workflow_history( $debug_info );
			return new WP_Error( 'generation_failed', __( 'An error occurred while generating your business case. Please try again.', 'rtbcb' ) );
		}
	}

	/**
	 * Get background job status.
	 *
	 * @return void
	 */
	public static function get_job_status() {
		if ( ! check_ajax_referer( 'rtbcb_generate', 'rtbcb_nonce', false ) ) {
			wp_send_json_error( __( 'Security check failed.', 'rtbcb' ), 403 );
			return;
		}

		$job_id = sanitize_text_field( wp_unslash( $_GET['job_id'] ?? '' ) );
		if ( empty( $job_id ) ) {
			wp_send_json_error( __( 'Missing job ID.', 'rtbcb' ), 400 );
			return;
		}

		$status = RTBCB_Background_Job::get_status( $job_id );
		if ( is_wp_error( $status ) ) {
			wp_send_json_error( $status->get_error_message(), 404 );
			return;
		}

		if (
			'completed' === ( $status['status'] ?? '' ) &&
			! empty( $status['result']['report_data'] )
		) {
			$result                = $status['result'];
			$status['report_data'] = $result['report_data'];
			if ( is_array( $result ) ) {
				foreach ( $result as $key => $value ) {
					if ( 'report_data' === $key ) {
						continue;
					}
					$status[ $key ] = $value;
				}
			}
			unset( $status['result'] );
		}

		wp_send_json_success( $status );
	}

	private static function collect_and_validate_user_inputs() {
		$user_inputs = [
			'email'                  => sanitize_email( wp_unslash( $_POST['email'] ?? '' ) ),
			'company_name'           => sanitize_text_field( wp_unslash( $_POST['company_name'] ?? '' ) ),
			'company_size'           => sanitize_text_field( wp_unslash( $_POST['company_size'] ?? '' ) ),
			'industry'               => sanitize_text_field( wp_unslash( $_POST['industry'] ?? '' ) ),
			'hours_reconciliation'   => floatval( wp_unslash( $_POST['hours_reconciliation'] ?? 0 ) ),
			'hours_cash_positioning' => floatval( wp_unslash( $_POST['hours_cash_positioning'] ?? 0 ) ),
			'num_banks'              => intval( wp_unslash( $_POST['num_banks'] ?? 0 ) ),
			'ftes'                   => floatval( wp_unslash( $_POST['ftes'] ?? 0 ) ),
			'pain_points'            => array_map( 'sanitize_text_field', (array) wp_unslash( $_POST['pain_points'] ?? [] ) ),
			'business_objective'     => sanitize_text_field( wp_unslash( $_POST['business_objective'] ?? '' ) ),
			'implementation_timeline'=> sanitize_text_field( wp_unslash( $_POST['implementation_timeline'] ?? '' ) ),
			'budget_range'           => sanitize_text_field( wp_unslash( $_POST['budget_range'] ?? '' ) ),
		];

		return $user_inputs;
	}

	private static function create_fallback_profile( $user_inputs ) {
		return [
			'company_profile' => [
				'name'               => $user_inputs['company_name'],
				'size'               => $user_inputs['company_size'],
				'industry'           => $user_inputs['industry'],
				'maturity_level'     => 'basic',
				'key_challenges'     => $user_inputs['pain_points'],
				'strategic_priorities'=> [ $user_inputs['business_objective'] ],
			],
			'industry_context' => [
				'sector_trends'        => 'General industry modernization trends',
				'competitive_pressure' => 'moderate',
				'regulatory_environment'=> 'standard compliance requirements',
			],
			'enrichment_status'    => 'fallback_used',
			'enrichment_confidence'=> 0.6,
		];
	}

	private static function structure_report_data( $user_inputs, $enriched_profile, $roi_scenarios, $recommendation, $final_analysis, $request_start ) {
		return [
			'metadata' => [
				'company_name'   => $user_inputs['company_name'],
				'analysis_date'  => current_time( 'Y-m-d' ),
				'analysis_type'  => 'comprehensive_enhanced',
				'confidence_level' => $final_analysis['confidence_level'] ?? 0.85,
				'processing_time' => microtime( true ) - $request_start,
			],
			'executive_summary' => [
				'strategic_positioning'   => $final_analysis['executive_summary']['strategic_positioning'] ?? '',
				'business_case_strength'  => self::calculate_business_case_strength( $roi_scenarios, $recommendation ),
				'key_value_drivers'       => $final_analysis['executive_summary']['key_value_drivers'] ?? [],
				'executive_recommendation' => $final_analysis['executive_summary']['executive_recommendation'] ?? '',
				'confidence_level'        => $final_analysis['executive_summary']['confidence_level'] ?? 0.85,
			],
			'company_intelligence' => [
				'enriched_profile'    => $enriched_profile['company_profile'],
				'industry_context'    => $enriched_profile['industry_context'],
				'maturity_assessment' => $enriched_profile['maturity_assessment'] ?? [],
				'competitive_position'=> $enriched_profile['competitive_position'] ?? [],
			],
			'financial_analysis' => [
				'roi_scenarios'        => self::format_roi_scenarios( $roi_scenarios ),
				'investment_breakdown' => $final_analysis['financial_analysis']['investment_breakdown'] ?? [],
				'payback_analysis'     => $final_analysis['financial_analysis']['payback_analysis'] ?? [],
				'sensitivity_analysis' => $roi_scenarios['sensitivity_analysis'] ?? [],
			],
			'technology_strategy' => [
				'recommended_category' => $recommendation['recommended'],
				'category_details'     => $recommendation['category_info'],
				'implementation_roadmap' => $final_analysis['implementation_roadmap'] ?? [],
				'vendor_considerations'=> $final_analysis['vendor_considerations'] ?? [],
			],
			'operational_insights' => [
				'current_state_assessment' => $final_analysis['operational_analysis']['current_state_assessment'] ?? [],
				'process_improvements'     => $final_analysis['operational_analysis']['process_improvements'] ?? [],
				'automation_opportunities' => $final_analysis['operational_analysis']['automation_opportunities'] ?? [],
			],
			'risk_analysis' => [
				'implementation_risks' => $final_analysis['risk_mitigation']['implementation_risks'] ?? [],
				'mitigation_strategies' => $final_analysis['risk_mitigation']['mitigation_strategies'] ?? [],
				'success_factors'      => $final_analysis['risk_mitigation']['success_factors'] ?? [],
			],
			'action_plan' => [
				'immediate_steps'    => $final_analysis['next_steps']['immediate'] ?? [],
				'short_term_milestones' => $final_analysis['next_steps']['short_term'] ?? [],
				'long_term_objectives'  => $final_analysis['next_steps']['long_term'] ?? [],
			],
		];
	}

	private static function build_rag_search_query( $user_inputs, $enriched_profile ) {
		$query_parts = [
			$user_inputs['company_name'],
			$user_inputs['industry'],
			$enriched_profile['company_profile']['maturity_level'] ?? '',
			implode( ' ', $user_inputs['pain_points'] ),
			$user_inputs['business_objective'],
		];

		return implode( ' ', array_filter( $query_parts ) );
	}

	private static function create_fallback_analysis( $enriched_profile, $roi_scenarios ) {
		return [
			'executive_summary' => [
				'strategic_positioning'   => '',
				'key_value_drivers'       => [],
				'executive_recommendation' => '',
				'confidence_level'        => 0.5,
			],
			'financial_analysis' => [],
		];
	}

	private static function save_lead_data_async( $user_inputs, $structured_report_data ) {
		if ( class_exists( 'RTBCB_Leads' ) ) {
			$lead_data = array_merge( $user_inputs, [ 'report_data' => $structured_report_data ] );
			return RTBCB_Leads::save_lead( $lead_data );
		}
		return null;
	}

	private static function calculate_business_case_strength( $roi_scenarios, $recommendation ) {
		$base = $roi_scenarios['base']['total_annual_benefit'] ?? 0;
		return $base > 0 ? 'strong' : 'weak';
	}

	private static function format_roi_scenarios( $roi_scenarios ) {
		return $roi_scenarios;
	}

	private static function store_workflow_history( $debug_info ) {
		$history = get_option( 'rtbcb_workflow_history', [] );
		if ( ! is_array( $history ) ) {
			$history = [];
		}
		$history[] = $debug_info;
		if ( count( $history ) > 20 ) {
			$history = array_slice( $history, -20 );
		}
		update_option( 'rtbcb_workflow_history', $history, false );
	}
}


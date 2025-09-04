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
		   if ( ! function_exists( 'check_ajax_referer' ) ) {
			   wp_die( 'WordPress not ready' );
								}

$params = self::get_sanitized_params();

rtbcb_increase_memory_limit();
		   $timeout = absint( rtbcb_get_api_timeout() );
		   if ( ! ini_get( 'safe_mode' ) && $timeout > 0 ) {
			   set_time_limit( $timeout );
		   }
		   rtbcb_log_memory_usage( 'start' );

		   if ( ! check_ajax_referer( 'rtbcb_generate', 'rtbcb_nonce', false ) ) {
			self::log_request( __FUNCTION__, $params, 'error', [ 'code' => 'invalid_nonce' ] );
			   wp_send_json_error( [ 'code' => 'invalid_nonce', 'message' => __( 'Security check failed.', 'rtbcb' ) ], 403 );
			   return;
		   }

		   $user_inputs = self::collect_and_validate_user_inputs();
		   if ( is_wp_error( $user_inputs ) ) {
			self::log_request( __FUNCTION__, $params, 'error', [ 'code' => 'validation_error', 'message' => $user_inputs->get_error_message() ] );
			   wp_send_json_error( [ 'code' => 'validation_error', 'message' => $user_inputs->get_error_message() ], 400 );
			   return;
		   }

		   try {
			   $job_id = RTBCB_Background_Job::enqueue( $user_inputs );
		   } catch ( Exception $e ) {
			self::log_request( __FUNCTION__, $params, 'error', [ 'code' => 'background_job_exception', 'message' => $e->getMessage() ] );
			   wp_send_json_error( [ 'code' => 'background_job_exception', 'message' => __( 'An unexpected error occurred.', 'rtbcb' ) ], 500 );
			   return;
		   }

		   if ( is_wp_error( $job_id ) ) {
			self::log_request( __FUNCTION__, $params, 'error', [ 'code' => 'background_job_error', 'message' => $job_id->get_error_message() ] );
			   wp_send_json_error( [ 'code' => 'background_job_error', 'message' => $job_id->get_error_message() ], 500 );
			   return;
		   }

			self::log_request( __FUNCTION__, $params, 'success', [ 'job_id' => $job_id ] );
		   wp_send_json_success( [ 'job_id' => $job_id ] );
	   }

		/**
		* Stream business analysis chunks via AJAX.
		*
		* @return void
		*/

	   public static function stream_analysis() {
			   $action = isset( $_REQUEST['action'] ) ? sanitize_key( wp_unslash( $_REQUEST['action'] ) ) : '';
			   if ( 'rtbcb_stream_analysis' !== $action ) {
					   // Jetpack also routes requests through admin-ajax.php; avoid sending
					   // streaming headers for unrelated actions.
					   return;
			   }

			   if ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) {
					   wp_die( 'Invalid request' );
			   }

		if ( ! function_exists( 'check_ajax_referer' ) ) {
			wp_die( 'WordPress not ready' );
		}

		$params = self::get_sanitized_params();

		if ( function_exists( 'rtbcb_is_wpcom' ) && rtbcb_is_wpcom() ) {
			self::log_request( __FUNCTION__, $params, 'error', [ 'code' => 'streaming_unsupported' ] );
			wp_send_json_error( [ 'code' => 'streaming_unsupported', 'message' => __( 'Streaming is not supported on this hosting environment.', 'rtbcb' ) ], 400 );
			return;
		}

		rtbcb_increase_memory_limit();
		$timeout = absint( rtbcb_get_api_timeout() );
		if ( ! ini_get( 'safe_mode' ) && $timeout > 0 ) {
			set_time_limit( $timeout );
		}
		rtbcb_log_memory_usage( 'start' );

		if ( ! check_ajax_referer( 'rtbcb_generate', 'rtbcb_nonce', false ) ) {
			self::log_request( __FUNCTION__, $params, 'error', [ 'code' => 'invalid_nonce' ] );
			wp_send_json_error( [ 'code' => 'invalid_nonce', 'message' => __( 'Security check failed.', 'rtbcb' ) ], 403 );
			return;
		}

		$user_inputs = self::collect_and_validate_user_inputs();
		if ( is_wp_error( $user_inputs ) ) {
			self::log_request( __FUNCTION__, $params, 'error', [ 'code' => 'validation_error', 'message' => $user_inputs->get_error_message() ] );
			wp_send_json_error( [ 'code' => 'validation_error', 'message' => $user_inputs->get_error_message() ], 400 );
			return;
		}

		nocache_headers();
		header( 'Content-Type: text/event-stream' );
		header( 'Cache-Control: no-cache' );
		header( 'Connection: keep-alive' );

		$scenarios      = RTBCB_Calculator::calculate_roi( $user_inputs );
		$recommendation = RTBCB_Category_Recommender::recommend_category( $user_inputs );

		$plugin = RTBCB_Main::instance();

		$chunk_callback = function( $chunk ) {
			echo 'data: ' . wp_json_encode( $chunk ) . "\n\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		};

		$complete_callback = function() {
			echo "data: [DONE]\n\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		};

		$error_callback = function( $error ) {
			echo 'data: ' . wp_json_encode( [ 'error' => $error->get_error_message() ] ) . "\n\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		};

		RTBCB_LLM::stream_business_analysis( $plugin, $scenarios, $recommendation, $chunk_callback, $complete_callback, $error_callback );

		wp_die();
	}
	/**
	* Process the basic ROI calculation step.
	*
	* @param array $user_inputs User inputs.
	* @return array ROI block.
	*/
	public static function process_basic_roi_step( $user_inputs ) {
		$roi_scenarios = RTBCB_Calculator::calculate_roi( $user_inputs );

		return [
			'financial_analysis' => [
				'roi_scenarios' => self::format_roi_scenarios( $roi_scenarios ),
			],
		];
	}


	/**
	* Process comprehensive case generation.
	*
	* @param array $user_inputs User inputs.
	* @return array|WP_Error Result data or error.
	*/
		public static function process_comprehensive_case( $user_inputs, $job_id = '' ) {
		$request_start    = microtime( true );
		$workflow_tracker = new RTBCB_Workflow_Tracker();
		$bypass_heavy     = rtbcb_heavy_features_disabled();
		$enable_ai        = ! $bypass_heavy && ( class_exists( 'RTBCB_Settings' ) ? RTBCB_Settings::get_setting( 'enable_ai_analysis', true ) : true );
		$report_template  = 'comprehensive-report-template.php';

		add_action(
			'rtbcb_llm_prompt_sent',
			function( $prompt ) use ( $workflow_tracker ) {
				$workflow_tracker->add_prompt( $prompt );
			}
		);

		try {
						if ( $bypass_heavy ) {
								$workflow_tracker->add_warning( 'heavy_features_bypassed', __( 'AI features temporarily disabled.', 'rtbcb' ) );
								$workflow_tracker->start_step( 'ai_enrichment' );
								$enriched_profile = self::create_fallback_profile( $user_inputs );
								$workflow_tracker->complete_step( 'ai_enrichment', $enriched_profile );
								if ( $job_id ) {
			self::safe_update_status( $job_id, 'processing', [ 'enriched_profile' => $enriched_profile ] );
								}

								$workflow_tracker->start_step( 'enhanced_roi_calculation' );
								$roi_scenarios = RTBCB_Calculator::calculate_roi( $user_inputs );
								$workflow_tracker->complete_step( 'enhanced_roi_calculation', $roi_scenarios );
								if ( $job_id ) {
			self::safe_update_status( $job_id, 'processing', [ 'enhanced_roi' => $roi_scenarios ] );
								}

								$workflow_tracker->start_step( 'intelligent_recommendations' );
								$recommendation = RTBCB_Category_Recommender::recommend_category( $user_inputs );
								$workflow_tracker->complete_step( 'intelligent_recommendations', $recommendation );
								if ( $job_id ) {
			self::safe_update_status( $job_id, 'processing', [ 'category' => $recommendation['recommended'] ] );
								}

				                                                  $workflow_tracker->start_step( 'hybrid_rag_analysis' );
				                                                  $final_analysis = self::create_fallback_analysis( $enriched_profile, $roi_scenarios );
				                                                  $workflow_tracker->complete_step( 'hybrid_rag_analysis', $final_analysis );
				                                                  $rag_context   = [];
								if ( $job_id ) {
			self::safe_update_status( $job_id, 'processing', [ 'analysis' => $final_analysis ] );
								}

								$chart_data = self::prepare_chart_data( $roi_scenarios );

								$workflow_tracker->start_step( 'data_structuring' );
$structured_report_data = self::structure_report_data(
$user_inputs,
$enriched_profile,
$roi_scenarios,
$recommendation,
$final_analysis,
$rag_context,
$chart_data,
$request_start,
$final_analysis['financial_benchmarks'] ?? ( $final_analysis['research']['financial'] ?? [] )
(								));
								$workflow_tracker->complete_step( 'data_structuring', $structured_report_data );
								if ( $job_id ) {
			self::safe_update_status( $job_id, 'processing', [ 'report_data' => $structured_report_data ] );
								}

								$lead_id    = self::save_lead_data_async( $user_inputs, $structured_report_data );
								$lead_email = ! empty( $user_inputs['email'] ) ? sanitize_email( $user_inputs['email'] ) : '';

			$debug_info           = $workflow_tracker->get_debug_info();
			$debug_info['lead_id'] = $lead_id;
								if ( $lead_email ) {
			$debug_info['lead_email'] = $lead_email;
								}
			$debug_info['report_template'] = $report_template;
			self::store_workflow_history( $debug_info, $lead_id, $lead_email, $user_inputs['company_name'] ?? '' );

								return [
										'report_data'   => $structured_report_data,
										'workflow_info' => $debug_info,
										'lead_id'       => $lead_id,
										'analysis_type' => rtbcb_get_analysis_type(),
								];
						}
						$workflow_tracker->start_step( 'ai_enrichment' );
					       if ( $enable_ai ) {
								$enriched_profile = new WP_Error( 'llm_missing', 'LLM service unavailable.' );
								if ( class_exists( 'RTBCB_LLM' ) ) {
										try {
												$llm = new RTBCB_LLM_Optimized();
												if ( method_exists( $llm, 'enrich_company_profile' ) ) {
														$enriched_profile = $llm->enrich_company_profile( $user_inputs );
												}
										} catch ( Exception $e ) {
												return new WP_Error( 'llm_exception', __( 'AI enrichment failed.', 'rtbcb' ) );
										}
								}
								if ( is_wp_error( $enriched_profile ) ) {
										$error_message    = $enriched_profile->get_error_message();
										$enriched_profile = self::create_fallback_profile( $user_inputs );
										$workflow_tracker->add_warning( 'ai_enrichment_failed', sanitize_text_field( $error_message ) );
								}
					       } else {
								$enriched_profile = self::create_fallback_profile( $user_inputs );
								$workflow_tracker->add_warning( 'ai_enrichment_disabled', __( 'AI analysis disabled.', 'rtbcb' ) );
						}
						$workflow_tracker->complete_step( 'ai_enrichment', $enriched_profile );
						if ( $job_id ) {
			self::safe_update_status( $job_id, 'processing', [ 'enriched_profile' => $enriched_profile ] );
						}

			$workflow_tracker->start_step( 'enhanced_roi_calculation' );
			$enhanced_calculator = new RTBCB_Enhanced_Calculator();
			$roi_scenarios       = $enhanced_calculator->calculate_enhanced_roi( $user_inputs, $enriched_profile );
						$workflow_tracker->complete_step( 'enhanced_roi_calculation', $roi_scenarios );
						if ( $job_id ) {
			self::safe_update_status( $job_id, 'processing', [ 'enhanced_roi' => $roi_scenarios ] );
						}

						$workflow_tracker->start_step( 'intelligent_recommendations' );
						if ( $enable_ai ) {
								$intelligent_recommender = new RTBCB_Intelligent_Recommender();
								$recommendation          = $intelligent_recommender->recommend_with_ai_insights( $user_inputs, $enriched_profile );
						} else {
								$recommendation = RTBCB_Category_Recommender::recommend_category( $user_inputs );
								$workflow_tracker->add_warning( 'intelligent_recommendations_disabled', __( 'AI analysis disabled.', 'rtbcb' ) );
						}
						$workflow_tracker->complete_step( 'intelligent_recommendations', $recommendation );
						if ( $job_id ) {
			self::safe_update_status( $job_id, 'processing', [ 'category' => $recommendation['recommended'] ] );
						}

						$workflow_tracker->start_step( 'hybrid_rag_analysis' );
						if ( $enable_ai ) {
								$rag_context = [];
								if ( class_exists( 'RTBCB_RAG' ) ) {
										$rag          = new RTBCB_RAG();
										$search_query = self::build_rag_search_query( $user_inputs, $enriched_profile );
										$rag_context = $rag->search_similar( $search_query, 5 );
								}
								$final_analysis = new WP_Error( 'analysis_unavailable', 'Final analysis unavailable.' );
								if ( isset( $llm ) && method_exists( $llm, 'generate_strategic_analysis' ) ) {
										try {
												$final_analysis = $llm->generate_strategic_analysis( $enriched_profile, $roi_scenarios, $recommendation, $rag_context );
										} catch ( Exception $e ) {
												return new WP_Error( 'llm_exception', __( 'AI analysis failed.', 'rtbcb' ) );
										}
								}
								if ( is_wp_error( $final_analysis ) ) {
								$error_message  = $final_analysis->get_error_message();
								$error_data     = $final_analysis->get_error_data();
								$analysis_data  = [];

								if ( isset( $error_data['raw'] ) && isset( $llm ) && is_callable( [ $llm, 'validate_and_structure_analysis' ] ) ) {
								$decoded = json_decode( $error_data['raw'], true );
								if ( is_array( $decoded ) ) {
								$analysis_data = $llm->validate_and_structure_analysis( $decoded );
								}
								}

								if ( empty( $analysis_data['executive_summary'] ) ) {
								$analysis_data = self::create_fallback_analysis( $enriched_profile, $roi_scenarios );
								}

								$final_analysis = $analysis_data;
								$workflow_tracker->add_warning( 'final_analysis_failed', sanitize_text_field( $error_message ) );
}
						} else {
								$rag_context  = [];
								$final_analysis = self::create_fallback_analysis( $enriched_profile, $roi_scenarios );
								$workflow_tracker->add_warning( 'hybrid_rag_disabled', __( 'AI analysis disabled.', 'rtbcb' ) );
						}
					$workflow_tracker->complete_step( 'hybrid_rag_analysis', $final_analysis );
					if ( $job_id ) {
			self::safe_update_status( $job_id, 'processing', [ 'analysis' => $final_analysis ] );
					}

					$chart_data = self::prepare_chart_data( $roi_scenarios );

					$workflow_tracker->start_step( 'data_structuring' );
$structured_report_data = self::structure_report_data(
$user_inputs,
$enriched_profile,
$roi_scenarios,
$recommendation,
$final_analysis,
$rag_context,
$chart_data,
$request_start,
$final_analysis['financial_benchmarks'] ?? ( $final_analysis['research']['financial'] ?? [] )
);
						$workflow_tracker->complete_step( 'data_structuring', $structured_report_data );
						if ( $job_id ) {
			self::safe_update_status( $job_id, 'processing', [ 'report_data' => $structured_report_data ] );
						}

			$lead_id    = self::save_lead_data_async( $user_inputs, $structured_report_data );
			$lead_email = ! empty( $user_inputs['email'] ) ? sanitize_email( $user_inputs['email'] ) : '';

			$debug_info           = $workflow_tracker->get_debug_info();
			$debug_info['lead_id'] = $lead_id;
			if ( $lead_email ) {
			$debug_info['lead_email'] = $lead_email;
			}
			$debug_info['report_template'] = $report_template;
			self::store_workflow_history( $debug_info, $lead_id, $lead_email, $user_inputs['company_name'] ?? '' );

			return [
				'report_data'   => $structured_report_data,
				'workflow_info' => $debug_info,
				'lead_id'       => $lead_id,
							'analysis_type' => rtbcb_get_analysis_type(),
			];
		} catch ( Exception $e ) {
			$workflow_tracker->add_error( 'exception', $e->getMessage() );
			rtbcb_log_error( 'Ajax exception in new workflow', $e->getMessage() );
			$debug_info           = $workflow_tracker->get_debug_info();
			$lead_email           = ! empty( $user_inputs['email'] ) ? sanitize_email( $user_inputs['email'] ) : '';
			$debug_info['lead_id'] = isset( $lead_id ) ? $lead_id : null;
			if ( $lead_email ) {
			$debug_info['lead_email'] = $lead_email;
			}
			$debug_info['report_template'] = $report_template;
			self::store_workflow_history( $debug_info, isset( $lead_id ) ? $lead_id : null, $lead_email, $user_inputs['company_name'] ?? '' );
			return new WP_Error( 'generation_failed', __( 'An error occurred while generating your business case. Please try again.', 'rtbcb' ) );
		}
	}

	/**
	* Get background job status.
	*
	* @return void
	*/
	   public static function get_job_status() {
		   if ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) {
			   wp_die( 'Invalid request' );
		   }

		   if ( ! function_exists( 'check_ajax_referer' ) ) {
			   wp_die( 'WordPress not ready' );
		   }

		   $params = self::get_sanitized_params();

		   if ( ! check_ajax_referer( 'rtbcb_generate', 'rtbcb_nonce', false ) ) {
			self::log_request( __FUNCTION__, $params, 'error', [ 'code' => 'invalid_nonce' ] );
			   wp_send_json_error( [ 'code' => 'invalid_nonce', 'message' => __( 'Security check failed.', 'rtbcb' ) ], 403 );
			   return;
		   }

		   $job_id = sanitize_text_field( wp_unslash( $_GET['job_id'] ?? '' ) );
		   if ( empty( $job_id ) ) {
			self::log_request( __FUNCTION__, $params, 'error', [ 'code' => 'missing_job_id' ] );
			   wp_send_json_error( [ 'code' => 'missing_job_id', 'message' => __( 'Missing job ID.', 'rtbcb' ) ], 400 );
			   return;
		   }

		   try {
			   $status = RTBCB_Background_Job::get_status( $job_id );
		   } catch ( Exception $e ) {
			self::log_request( __FUNCTION__, $params, 'error', [ 'code' => 'status_exception', 'message' => $e->getMessage() ] );
			   wp_send_json_error( [ 'code' => 'status_exception', 'message' => __( 'Unable to retrieve job status.', 'rtbcb' ) ], 500 );
			   return;
		   }

		   if ( is_wp_error( $status ) ) {
			self::log_request( __FUNCTION__, $params, 'error', [ 'code' => 'status_error', 'message' => $status->get_error_message() ] );
			   $status = [
				   'state'   => 'error',
				   'message' => sanitize_text_field( $status->get_error_message() ),
			   ];
		   }

		   $response = [
			   'status' => $status['state'] ?? '',
		   ];

				foreach ( $status as $field => $value ) {
						if ( in_array( $field, [ 'state', 'created', 'updated', 'result' ], true ) ) {
								continue;
						}
			if ( 'percent' === $field ) {
				$response[ $field ] = floatval( $value );
				        } elseif ( 'report_url' === $field && is_string( $value ) ) {
				                $response[ $field ] = function_exists( 'esc_url_raw' ) ? esc_url_raw( $value ) : $value;
			} elseif ( in_array( $field, [ 'step', 'message' ], true ) && is_string( $value ) ) {
				$response[ $field ] = sanitize_text_field( $value );
			} else {
				$response[ $field ] = $value;
			}
				}

		   if ( isset( $status['result'] ) ) {
			   if (
				   'completed' === ( $response['status'] ?? '' ) &&
				   ! empty( $status['result']['report_data'] )
			   ) {
				   $result                  = $status['result'];
				   $response['report_data'] = $result['report_data'];
				   if ( is_array( $result ) ) {
				       foreach ( $result as $key => $value ) {
					       if ( 'report_data' === $key ) {
						       continue;
					       }
					       $response[ $key ] = $value;
				       }
				   }
			   } else {
				   $response['result'] = $status['result'];
			   }
		   }

			self::log_request( __FUNCTION__, $params, 'success', [ 'job_id' => $job_id, 'status' => $response['status'] ] );
		   wp_send_json_success( $response );
	   }
	/**
	 * Collect and validate user input from the POST request.
 *
 * @return array|WP_Error Sanitized input array or validation error.
 */
	public static function collect_and_validate_user_inputs() {
		$validator = new RTBCB_Validator();
		$validated = $validator->validate( $_POST );
		
		if ( isset( $validated['error'] ) ) {
		return new WP_Error( 'validation_error', $validated['error'] );
		}
		
		$company = function_exists( 'rtbcb_get_current_company' ) ? rtbcb_get_current_company() : [];
		if ( ! empty( $company['industry'] ) ) {
		$validated['industry'] = sanitize_text_field( $company['industry'] );
		}
		
		return $validated;
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

/**
 * Structure report data for client consumption.
 *
 * @param array $user_inputs          User-provided inputs.
 * @param array $enriched_profile     Enriched company profile.
 * @param array $roi_scenarios        ROI scenarios.
 * @param array $recommendation       Category recommendation.
 * @param array $final_analysis       Final AI analysis.
 * @param array $rag_context          Retrieval augmented generation context.
 * @param array $chart_data           Prepared chart data.
 * @param float $request_start        Request start time.
 * @param array $financial_benchmarks Financial benchmark data.
 * @return array Structured report data.
 */
private static function structure_report_data( $user_inputs, $enriched_profile, $roi_scenarios, $recommendation, $final_analysis, $rag_context, $chart_data, $request_start, $financial_benchmarks = array() ) {
	$operational_insights     = (array) ( $final_analysis['operational_insights'] ?? $final_analysis['operational_analysis'] ?? [] );
	$current_state_assessment = (array) ( $operational_insights['current_state_assessment'] ?? [] );
	if ( empty( $current_state_assessment ) ) {
		$current_state_assessment = [ __( 'No data provided', 'rtbcb' ) ];
	}

	$process_improvements_raw = (array) ( $operational_insights['process_improvements'] ?? [] );
	$process_improvements     = array();
	foreach ( $process_improvements_raw as $item ) {
		if ( is_array( $item ) ) {
			$process_area   = sanitize_text_field( $item['process_area'] ?? '' );
			$current_state  = sanitize_text_field( $item['current_state'] ?? '' );
			$improved_state = sanitize_text_field( $item['improved_state'] ?? '' );
			$impact_level   = sanitize_text_field( $item['impact_level'] ?? '' );

			if ( '' === $process_area && '' === $current_state && '' === $improved_state && '' === $impact_level ) {
				continue;
			}

			$summary_parts = array();
			if ( $current_state || $improved_state ) {
				$summary_parts[] = trim( sprintf( '%s → %s', $current_state, $improved_state ) );
			}
			if ( $impact_level ) {
				$summary_parts[] = sprintf( '(%s %s)', $impact_level, __( 'impact', 'rtbcb' ) );
			}

			$summary = $process_area;
			if ( ! empty( $summary_parts ) ) {
				$summary .= ': ' . implode( ' ', $summary_parts );
			}

			$process_improvements[] = $summary;
		} else {
			$summary = sanitize_text_field( $item );
			if ( '' !== $summary ) {
				$process_improvements[] = $summary;
			}
		}
	}
	if ( empty( $process_improvements ) ) {
		$process_improvements = [ __( 'No data provided', 'rtbcb' ) ];
	}

	$automation_opportunities_raw = (array) ( $operational_insights['automation_opportunities'] ?? [] );
	$automation_opportunities     = array();
	foreach ( $automation_opportunities_raw as $item ) {
		if ( is_array( $item ) ) {
			$opportunity           = sanitize_text_field( $item['opportunity'] ?? '' );
			$complexity            = sanitize_text_field( $item['complexity'] ?? '' );
			$time_savings          = floatval( $item['time_savings'] ?? 0 );
			$implementation_effort = sanitize_text_field( $item['implementation_effort'] ?? '' );

			if ( '' === $opportunity && '' === $complexity && '' === $implementation_effort && 0 === $time_savings ) {
				continue;
			}

			$summary = $opportunity;
			$details = array();
			if ( $complexity ) {
				$details[] = sprintf( __( '%s complexity', 'rtbcb' ), $complexity );
			}
			if ( $implementation_effort ) {
				$details[] = sprintf( __( '%s effort', 'rtbcb' ), $implementation_effort );
			}
			if ( ! empty( $details ) ) {
				$summary .= ': ' . implode( ', ', $details );
			}
			if ( $time_savings > 0 ) {
				$formatted = function_exists( 'number_format_i18n' ) ? number_format_i18n( $time_savings ) : $time_savings;
				$summary  .= ' → ' . $formatted . ' ' . __( 'hours saved', 'rtbcb' );
			}

			$automation_opportunities[] = $summary;
		} else {
			$summary = sanitize_text_field( $item );
			if ( '' !== $summary ) {
				$automation_opportunities[] = $summary;
			}
		}
	}
	if ( empty( $automation_opportunities ) ) {
		$automation_opportunities = [ __( 'No data provided', 'rtbcb' ) ];
	}

	$risk_analysis        = (array) ( $final_analysis['risk_analysis'] ?? $final_analysis['risk_mitigation'] ?? [] );
	$implementation_risks = (array) ( $risk_analysis['implementation_risks'] ?? [] );
	if ( empty( $implementation_risks ) ) {
		$implementation_risks = [ __( 'No data provided', 'rtbcb' ) ];
	}
       $action_plan             = (array) ( $final_analysis['action_plan'] ?? [] );
       $immediate_steps_raw     = $action_plan['immediate_steps'] ?? ( $final_analysis['next_steps']['immediate'] ?? [] );
       $immediate_steps         = self::normalize_action_items( $immediate_steps_raw );
       if ( empty( $immediate_steps ) ) {
               $immediate_steps = [ __( 'No data provided', 'rtbcb' ) ];
       }
       $short_term_raw          = $action_plan['short_term_milestones'] ?? ( $final_analysis['next_steps']['short_term'] ?? [] );
       $short_term_milestones   = self::normalize_action_items( $short_term_raw );
       if ( empty( $short_term_milestones ) ) {
               $short_term_milestones = [ __( 'No data provided', 'rtbcb' ) ];
       }
       $long_term_raw           = $action_plan['long_term_objectives'] ?? ( $final_analysis['next_steps']['long_term'] ?? [] );
       $long_term_objectives    = self::normalize_action_items( $long_term_raw );
       if ( empty( $long_term_objectives ) ) {
               $long_term_objectives = [ __( 'No data provided', 'rtbcb' ) ];
       }

	$company_profile      = $enriched_profile['company_profile'] ?? [];
	$industry_context_raw = $enriched_profile['industry_context'] ?? [];
	$sector_analysis_raw      = (array) ( $industry_context_raw['sector_analysis'] ?? [] );
	$benchmarking_raw         = (array) ( $industry_context_raw['benchmarking'] ?? [] );
	$regulatory_landscape_raw = (array) ( $industry_context_raw['regulatory_landscape'] ?? [] );
	$enriched_profile_struct = [
		'name'                => $company_profile['name'] ?? '',
		'industry'           => $company_profile['industry'] ?? sanitize_text_field( $user_inputs['industry'] ?? '' ),
		'enhanced_description'=> $company_profile['enhanced_description'] ?? ( $company_profile['description'] ?? '' ),
		'maturity_level'      => $company_profile['maturity_level'] ?? '',
		'treasury_maturity'   => (array) ( is_array( $company_profile['treasury_maturity'] ?? null ) ? $company_profile['treasury_maturity'] : [] ),
		'key_challenges'      => (array) ( is_array( $company_profile['key_challenges'] ?? null ) ? $company_profile['key_challenges'] : [] ),
		'strategic_priorities'=> (array) ( is_array( $company_profile['strategic_priorities'] ?? null ) ? $company_profile['strategic_priorities'] : [] ),
	];
	$industry_context_struct = [
		'sector_analysis' => [
			'market_dynamics'    => $sector_analysis_raw['market_dynamics'] ?? ( $industry_context_raw['sector_trends'] ?? ( $industry_context_raw['market_dynamics'] ?? '' ) ),
			'growth_trends'      => $sector_analysis_raw['growth_trends'] ?? '',
			'disruption_factors' => (array) ( $sector_analysis_raw['disruption_factors'] ?? [] ),
			'technology_adoption'=> $sector_analysis_raw['technology_adoption'] ?? ( $industry_context_raw['technology_adoption'] ?? '' ),
		],
		'benchmarking'    => [
			'typical_treasury_setup' => $benchmarking_raw['typical_treasury_setup'] ?? '',
			'common_pain_points'     => (array) ( $benchmarking_raw['common_pain_points'] ?? [] ),
			'technology_penetration' => $benchmarking_raw['technology_penetration'] ?? ( $industry_context_raw['technology_penetration'] ?? '' ),
			'investment_patterns'    => $benchmarking_raw['investment_patterns'] ?? '',
		],
		'regulatory_landscape' => [
			'key_regulations'      => (array) ( $regulatory_landscape_raw['key_regulations'] ?? [] ),
			'compliance_complexity'=> $regulatory_landscape_raw['compliance_complexity'] ?? '',
			'upcoming_changes'     => (array) ( $regulatory_landscape_raw['upcoming_changes'] ?? [] ),
		],
	];

	$company_name_meta = sanitize_text_field( $enriched_profile_struct['name'] ?: $user_inputs['company_name'] );

	return [
		'metadata' => [
			'company_name'   => $company_name_meta,
			'analysis_date'  => current_time( 'Y-m-d' ),
			'analysis_type'  => rtbcb_get_analysis_type(),
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
				'enriched_profile'    => $enriched_profile_struct,
				'industry_context'    => $industry_context_struct,
				               'maturity_assessment' => (array) ( is_array( $enriched_profile['maturity_assessment'] ?? null ) ? $enriched_profile['maturity_assessment'] : [] ),
				               'competitive_position'=> (array) ( is_array( $enriched_profile['competitive_position'] ?? null ) ? $enriched_profile['competitive_position'] : [] ),
			],
'industry_insights'    => $final_analysis['industry_insights'] ?? [],
'financial_benchmarks' => (array) ( is_array( $financial_benchmarks ) ? $financial_benchmarks : [] ),
'rag_context'          => (array) ( is_array( $rag_context ) ? $rag_context : [] ),
'financial_analysis'   => [
				                                       'roi_scenarios'        => self::format_roi_scenarios( $roi_scenarios ),
				                                       'investment_breakdown' => (array) ( is_array( $final_analysis['financial_analysis']['investment_breakdown'] ?? null ) ? $final_analysis['financial_analysis']['investment_breakdown'] : [] ),
				                                       'payback_analysis'     => (array) ( is_array( $final_analysis['financial_analysis']['payback_analysis'] ?? null ) ? $final_analysis['financial_analysis']['payback_analysis'] : [] ),
				                                       'sensitivity_analysis' => (array) ( is_array( $roi_scenarios['sensitivity_analysis'] ?? null ) ? $roi_scenarios['sensitivity_analysis'] : [] ),
				                                       'confidence_metrics'   => (array) ( is_array( $roi_scenarios['confidence_metrics'] ?? null ) ? $roi_scenarios['confidence_metrics'] : [] ),
				                                       'chart_data'           => $chart_data,
				                       ],
			'technology_strategy' => [
				               'recommended_category' => $recommendation['recommended'],
				               'category_details'     => (array) ( is_array( $recommendation['category_info'] ?? null ) ? $recommendation['category_info'] : [] ),
				               'implementation_roadmap' => (array) ( is_array( $final_analysis['implementation_roadmap'] ?? null ) ? $final_analysis['implementation_roadmap'] : [] ),
				               'vendor_considerations'=> (array) ( is_array( $final_analysis['vendor_considerations'] ?? null ) ? $final_analysis['vendor_considerations'] : [] ),
			],
				        'operational_insights' => [
				                                        'current_state_assessment' => $current_state_assessment,
				                                        'process_improvements'     => $process_improvements,
				                                        'automation_opportunities' => $automation_opportunities,
				        ],
				        'risk_analysis' => [
				                                        'implementation_risks' => $implementation_risks,
				                                       'mitigation_strategies' => (array) ( is_array( $risk_analysis['mitigation_strategies'] ?? null ) ? $risk_analysis['mitigation_strategies'] : [] ),
				                                       'success_factors'      => (array) ( is_array( $risk_analysis['success_factors'] ?? null ) ? $risk_analysis['success_factors'] : [] ),
				        ],
				        'action_plan' => [
				                'immediate_steps'       => $immediate_steps,
				                'short_term_milestones' => $short_term_milestones,
				                'long_term_objectives'  => $long_term_objectives,
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

	/**
		* Prepare chart data for ROI visualization.
		*
		* @param array $roi_scenarios ROI scenarios.
		* @return array Chart.js compatible data structure.
		*/
	private static function prepare_chart_data( $roi_scenarios ) {
			return [
					'labels'   => [
							__( 'Labor Savings', 'rtbcb' ),
							__( 'Fee Savings', 'rtbcb' ),
							__( 'Error Reduction', 'rtbcb' ),
							__( 'Total Benefit', 'rtbcb' ),
					],
					'datasets' => [
							[
									'label'           => __( 'Conservative', 'rtbcb' ),
									'data'            => [
											$roi_scenarios['conservative']['labor_savings'] ?? 0,
											$roi_scenarios['conservative']['fee_savings'] ?? 0,
											$roi_scenarios['conservative']['error_reduction'] ?? 0,
											$roi_scenarios['conservative']['total_annual_benefit'] ?? 0,
									],
									'backgroundColor' => 'rgba(239, 68, 68, 0.8)',
									'borderColor'     => 'rgba(239, 68, 68, 1)',
									'borderWidth'     => 1,
							],
							[
									'label'           => __( 'Base Case', 'rtbcb' ),
									'data'            => [
											$roi_scenarios['base']['labor_savings'] ?? 0,
											$roi_scenarios['base']['fee_savings'] ?? 0,
											$roi_scenarios['base']['error_reduction'] ?? 0,
											$roi_scenarios['base']['total_annual_benefit'] ?? 0,
									],
									'backgroundColor' => 'rgba(59, 130, 246, 0.8)',
									'borderColor'     => 'rgba(59, 130, 246, 1)',
									'borderWidth'     => 1,
							],
							[
									'label'           => __( 'Optimistic', 'rtbcb' ),
									'data'            => [
											$roi_scenarios['optimistic']['labor_savings'] ?? 0,
											$roi_scenarios['optimistic']['fee_savings'] ?? 0,
											$roi_scenarios['optimistic']['error_reduction'] ?? 0,
											$roi_scenarios['optimistic']['total_annual_benefit'] ?? 0,
									],
									'backgroundColor' => 'rgba(16, 185, 129, 0.8)',
									'borderColor'     => 'rgba(16, 185, 129, 1)',
									'borderWidth'     => 1,
							],
					],
			];
	}

       private static function calculate_business_case_strength( $roi_scenarios, $recommendation ) {
               $base = $roi_scenarios['base']['total_annual_benefit'] ?? 0;
               return $base > 0 ? 'strong' : 'weak';
       }

       /**
        * Normalize action plan items into summary strings.
        *
        * @param array $items Raw action plan items.
        * @return array Sanitized summary strings.
        */
       private static function normalize_action_items( $items ) {
               $normalized = array();
               foreach ( (array) $items as $item ) {
                       if ( is_array( $item ) ) {
                               $action   = sanitize_text_field( $item['action'] ?? ( $item['step'] ?? ( $item['milestone'] ?? ( $item['objective'] ?? '' ) ) ) );
                               $owner    = sanitize_text_field( $item['owner'] ?? '' );
                               $timeline = sanitize_text_field( $item['timeline'] ?? ( $item['timeframe'] ?? '' ) );
                               $parts    = array_filter( array( $action, $owner, $timeline ) );
                               $summary  = implode( ' - ', $parts );
                       } else {
                               $summary = sanitize_text_field( $item );
                       }

                       if ( '' !== $summary ) {
                               $normalized[] = $summary;
                       }
               }

               return $normalized;
       }

       private static function format_roi_scenarios( $roi_scenarios ) {
               $allowed   = array( 'conservative', 'base', 'optimistic' );
               $formatted = array();

               foreach ( $allowed as $key ) {
                       if ( isset( $roi_scenarios[ $key ] ) && is_array( $roi_scenarios[ $key ] ) ) {
                               $formatted[ $key ] = $roi_scenarios[ $key ];
                       }
               }

               return $formatted;
       }

       /**
        * Sanitize request parameters for logging.
        *
        * @return array
        */
       private static function get_sanitized_params() {
               $params = [];
               foreach ( $_REQUEST as $key => $value ) {
                       if ( is_scalar( $value ) ) {
                               $params[ sanitize_key( $key ) ] = sanitize_text_field( wp_unslash( $value ) );
                       }
               }
               return $params;
        }

	   /**
	* Log AJAX request details.
	*
	* @param string $action Action name.
	* @param array  $params Request parameters.
	* @param string $status Response status.
	* @param array  $extra  Additional context.
	* @return void
	*/
	private static function log_request( $action, $params, $status, $extra = [] ) {
		   if ( class_exists( 'RTBCB_Logger' ) ) {
			   $context = array_merge(
				   [
				       'action'  => $action,
				       'params'  => $params,
				       'user_id' => function_exists( 'get_current_user_id' ) ? get_current_user_id() : 0,
				       'status'  => $status,
				   ],
				   $extra
			   );
			   RTBCB_Logger::log( 'ajax_request', $context );
		   }
	   }

	   /**
	* Safely update background job status.
	*
	* @param string $job_id Job ID.
	* @param string $state  Job state.
	* @param array  $data   Data to store.
	* @return void
	*/
	private static function safe_update_status( $job_id, $state, $data ) {
		   if ( ! $job_id ) {
			   return;
		   }
		   try {
			   RTBCB_Background_Job::update_status( $job_id, $state, $data );
		   } catch ( Exception $e ) {
			   if ( class_exists( 'RTBCB_Logger' ) ) {
				   RTBCB_Logger::log( 'background_job_error', [ 'job_id' => $job_id, 'message' => $e->getMessage() ] );
			   }
		   }
	   }

	   /**
	* Store workflow history and associated lead metadata.
	*
	* @param array     $debug_info  Workflow debug information.
	* @param int|null  $lead_id     Lead ID.
	* @param string    $lead_email  Lead email address.
	* @return void
	*/
	private static function store_workflow_history( $debug_info, $lead_id = null, $lead_email = '', $company_name = '' ) {
		   $history = function_exists( 'get_option' ) ? get_option( 'rtbcb_workflow_history', [] ) : [];
		   if ( ! is_array( $history ) ) {
			   $history = [];
		   }
			$debug_info['lead_id'] = $lead_id ? intval( $lead_id ) : null;
		   if ( ! empty( $lead_email ) ) {
			$debug_info['lead_email'] = sanitize_email( $lead_email );
		   }
		   if ( ! empty( $company_name ) ) {
			$debug_info['company_name'] = sanitize_text_field( $company_name );
		   }
                   if ( class_exists( 'RTBCB_API_Log' ) ) {
                           $log_ids = RTBCB_API_Log::get_log_ids_for_contact( $debug_info['lead_id'] ?? 0, $debug_info['lead_email'] ?? '' );
                           if ( ! empty( $log_ids ) ) {
                                   $debug_info['log_ids'] = array_map( 'intval', $log_ids );
                           }
                   }

                   $history[] = $debug_info;
                   if ( count( $history ) > 20 ) {
                           $history = array_slice( $history, -20 );
                   }
                   if ( function_exists( 'update_option' ) ) {
                           update_option( 'rtbcb_workflow_history', $history, false );
                   }
           }
}


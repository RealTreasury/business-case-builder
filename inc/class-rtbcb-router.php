<?php
defined( 'ABSPATH' ) || exit;

/**
	* Routes model selection based on request complexity.
	*
	* @package RealTreasuryBusinessCaseBuilder
	*/

require_once __DIR__ . '/helpers.php';

/**
	* Class RTBCB_Router.
	*/
class RTBCB_Router {
	/**
       * Handle form submission and generate the business case.
       *
       * @param string $report_type Optional report type (fast, basic or comprehensive).
       *
       * @return void|WP_Error
       */
       public function handle_form_submission( $report_type = 'basic' ) {
               $allowed_types = [ 'fast', 'basic', 'comprehensive', 'enhanced' ];

               if ( isset( $_POST['report_type'] ) ) {
                       $requested_type = sanitize_text_field( wp_unslash( $_POST['report_type'] ) );
                       if ( ! in_array( $requested_type, $allowed_types, true ) ) {
                               RTBCB_Logger::log(
                                       'invalid_report_type',
                                       [ 'requested_type' => $requested_type ]
                               );
                               wp_send_json_error(
                                       [ 'message' => __( 'Invalid report type.', 'rtbcb' ) ],
                                       400
                               );
                               return;
                       }
                       $report_type = $requested_type;
               } elseif ( ! in_array( $report_type, $allowed_types, true ) ) {
                       $report_type = 'basic';
               }

               // Nonce verification.
               if (
                       ! isset( $_POST['rtbcb_nonce'] )
                       || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['rtbcb_nonce'] ) ), 'rtbcb_generate' )
               ) {
                       wp_send_json_error( [ 'message' => __( 'Nonce verification failed.', 'rtbcb' ) ], 403 );
                       return;
               }

               try {
                       // Sanitize and validate input.
                       $validator      = new RTBCB_Validator();
                       $validated_data = $validator->validate( $_POST, $report_type );

                       if ( isset( $validated_data['error'] ) ) {
                               wp_send_json_error( [ 'message' => $validated_data['error'] ], 400 );
                               return;
                       }

                       $form_data = $validated_data;
                       // Perform ROI calculations.
                       $calculations   = RTBCB_Calculator::calculate_roi( $form_data );
                       $recommendation = RTBCB_Category_Recommender::recommend_category( $form_data );
                       $calculations   = RTBCB_Calculator::calculate_category_refined_roi(
                               $form_data,
                               $recommendation['category_info']
                       );

                       $fast_mode   = 'fast' === $report_type || ! empty( $_POST['fast_mode'] ) || rtbcb_heavy_features_disabled();
                       $llm         = null;
                       $rag         = null;
                       $rag_context = [];

                       if ( $fast_mode ) {
                               $model = 'fast';
                       } else {
                               $llm         = new RTBCB_LLM_Optimized();
                               $rag         = new RTBCB_RAG();
                               $rag_context = $rag->get_context( $form_data['company_description'] );

                               if ( 'comprehensive' === $report_type ) {
                                       $model = 'comprehensive';
                               } else {
                                       $model = $this->route_model( $form_data, $rag_context );
                                       if ( is_wp_error( $model ) ) {
                                               throw new Exception( $model->get_error_message() );
                                       }
                               }
                       }

                       RTBCB_Logger::log(
                               'report_config',
                               [
                                       'report_type' => $report_type,
                                       'fast_mode'   => $fast_mode,
                                       'model'       => $model,
                               ]
                       );

                       if ( $fast_mode ) {
                               $report_html = $this->get_fast_report_html( $form_data, $calculations );

                               $lead_data = array_merge(
                                       $form_data,
                                       [
                                               'roi_low'    => $calculations['conservative']['roi_percentage'] ?? 0,
                                               'roi_base'   => $calculations['base']['roi_percentage'] ?? 0,
                                               'roi_high'   => $calculations['optimistic']['roi_percentage'] ?? 0,
                                               'report_html' => $report_html,
                                       ]
                               );

                               $lead_id = RTBCB_Leads::save_lead( $lead_data );

                               $upload_dir  = wp_upload_dir();
                               $reports_dir = trailingslashit( $upload_dir['basedir'] ) . 'rtbcb-reports';
                               if ( ! file_exists( $reports_dir ) ) {
                                       wp_mkdir_p( $reports_dir );
                               }

                               $filepath = trailingslashit( $reports_dir ) . 'report-' . $lead_id . '.html';
                               file_put_contents( $filepath, $report_html );

                               $to      = sanitize_email( $form_data['email'] );
                               $subject = sprintf( __( 'Your Business Case from %s', 'rtbcb' ), get_bloginfo( 'name' ) );
                               $message = __( 'Thank you for using the Business Case Builder. Your report is attached.', 'rtbcb' );
                               wp_mail( $to, $subject, $message, [], [ $filepath ] );

                               if ( file_exists( $filepath ) ) {
                                       unlink( $filepath );
                               }

                               wp_send_json_success(
                                       [
                                               'message'     => __( 'Business case generated successfully.', 'rtbcb' ),
                                               'report_id'   => $lead_id,
                                               'report_html' => $report_html,
                                       ]
                               );
                               return;
                       }

                       if ( 'comprehensive' === $report_type ) {
                               // Generate comprehensive business case with LLM.
                               $business_case_data = $llm->generate_comprehensive_business_case( $form_data, $calculations, $rag_context );
                       } else {
                               // Generate basic business case with LLM.
                               $business_case_data = $llm->generate_business_case( $form_data, $calculations, $rag_context, $model );
                       }

			// Check for LLM generation errors before proceeding.
			if ( is_wp_error( $business_case_data ) ) {
				$error_message = $business_case_data->get_error_message();
				$error_data    = $business_case_data->get_error_data();
				$status        = is_array( $error_data ) && isset( $error_data['status'] ) ? (int) $error_data['status'] : 500;

				wp_send_json_error(
					[
						'message'    => $error_message,
						'error_code' => $business_case_data->get_error_code(),
					],
					$status
				);
				return;
			}

			// Generate report HTML based on type.
			$report_html = 'comprehensive' === $report_type ?
				$this->get_comprehensive_report_html( $business_case_data ) :
				$this->get_report_html( $business_case_data );

			// Save the lead.
			$lead_data = array_merge(
				$form_data,
				[
					'roi_low'    => $calculations['conservative']['roi_percentage'] ?? 0,
					'roi_base'   => $calculations['base']['roi_percentage'] ?? 0,
					'roi_high'   => $calculations['optimistic']['roi_percentage'] ?? 0,
					'report_html' => $report_html,
				]
			);
			$lead_id = RTBCB_Leads::save_lead( $lead_data );

			// Write report HTML to temporary file in uploads directory.
			$upload_dir  = wp_upload_dir();
			$reports_dir = trailingslashit( $upload_dir['basedir'] ) . 'rtbcb-reports';
			if ( ! file_exists( $reports_dir ) ) {
				wp_mkdir_p( $reports_dir );
			}

			$filepath = trailingslashit( $reports_dir ) . 'report-' . $lead_id . '.html';
			file_put_contents( $filepath, $report_html );

			// Prepare and send the report email.
			$to      = sanitize_email( $form_data['email'] );
			$subject = sprintf(
				__( 'Your Business Case from %s', 'rtbcb' ),
				get_bloginfo( 'name' )
			);
			$message = __( 'Thank you for using the Business Case Builder. Your report is attached.', 'rtbcb' );
			wp_mail( $to, $subject, $message, [], [ $filepath ] );

			// Clean up temporary file.
			if ( file_exists( $filepath ) ) {
				unlink( $filepath );
			}

			// Send success response.
			wp_send_json_success(
				[
					'message'     => __( 'Business case generated successfully.', 'rtbcb' ),
					'report_id'   => $lead_id,
					'report_html' => $report_html,
				]
			);
               } catch ( RTBCB_JSON_Error $e ) {
                       throw $e;
               } catch ( Exception $e ) {
                       // Log the detailed error to debug.log.
                       rtbcb_log_error(
                               'Form submission error',
                               [
                                       'operation' => 'generate_case',
                                       'error'     => $e->getMessage(),
                               ]
                       );

                       // Send a generic error response to the client.
                       wp_send_json_error(
                               [
                                       'message' => sprintf(
                                               __( 'An unexpected error occurred while generating your report. Please check the server logs for more details. Error: %s', 'rtbcb' ),
                                               $e->getMessage()
                                               ),
                               ],
                               500
                       );
               }
	}
	/**
	* Route to the appropriate LLM model.
	*
	* @param array $inputs User inputs.
	* @param array $chunks Context chunks.
	*
	* @return string|WP_Error Model name or error if no model configured.
	*/
	public function route_model( $inputs, $chunks ) {
		$complexity = $this->calculate_complexity( $inputs, $chunks );
		$category   = RTBCB_Category_Recommender::recommend_category( $inputs )['recommended'];

// Get available models
$mini_model    = function_exists( 'get_option' ) ? get_option( 'rtbcb_mini_model', rtbcb_get_default_model( 'mini' ) ) : rtbcb_get_default_model( 'mini' );
$premium_model = function_exists( 'get_option' ) ? get_option( 'rtbcb_premium_model', rtbcb_get_default_model( 'premium' ) ) : rtbcb_get_default_model( 'premium' );

		// Start with mini model as default
		$model     = $mini_model;
		$reasoning = 'Default mini model for basic requests';

		if ( $complexity > 0.6 || 'trms' === $category ) {
			$model     = $premium_model;
			$reasoning = 'Premium model for high complexity or TRMS category';
		} elseif ( 'tms_lite' === $category && $complexity > 0.4 ) {
			$model     = $premium_model;
			$reasoning = 'Premium model for TMS-Lite with moderate complexity';
		}

		// Validate selected model
		if ( empty( $model ) ) {
                       $error = new WP_Error(
                               'rtbcb_missing_model',
                               __( 'No language model configured. Please review the plugin settings.', 'rtbcb' )
                       );
                       rtbcb_log_error(
                               $error->get_error_message(),
                               [ 'operation' => 'route_model' ]
                       );
                       return $error;
		}

               RTBCB_Logger::log(
                       'model_selected',
                       [
                               'model'      => $model,
                               'complexity' => $complexity,
                               'category'   => $category,
                               'reason'     => $reasoning,
                       ]
               );

		return $model;
	}

	/**
	* Calculate complexity score for model routing.
	*
	* @param array $inputs User inputs.
	* @param array $chunks Context chunks.
	*
	* @return float Complexity score between 0 and 1.
	*/
	private function calculate_complexity( $inputs, $chunks ) {
		$score = 0;

		$pain_points = isset( $inputs['pain_points'] ) ? (array) $inputs['pain_points'] : [];
		$score      += count( $pain_points ) * 0.1;
		$score      += count( $chunks ) * 0.2;

		if ( isset( $inputs['company_size'] ) && '>$2B' === $inputs['company_size'] ) {
			$score += 0.3;
		}

		return min( 1.0, $score );
	}

	/**
	* Generate report HTML.
	*
	* @param array $business_case_data Business case data.
	*
	* @return string Report HTML.
	*/
	public function get_report_html( $business_case_data ) {
		$template_path = RTBCB_DIR . 'templates/report-template.php';

		if ( ! file_exists( $template_path ) ) {
			return '';
		}

		$business_case_data = is_array( $business_case_data ) ? $business_case_data : [];
	$report_data        = $business_case_data['report_data'] ?? null;
	$hash_source        = $report_data ?: $business_case_data;
	$data_hash          = md5( wp_json_encode( $hash_source ) );
	$version            = rtbcb_get_report_cache_version();
	$cache_key          = md5( $template_path . ':' . $data_hash . ':' . $version );

	$cached_html = wp_cache_get( $cache_key, 'rtbcb_reports' );
		if ( false !== $cached_html ) {
			return $cached_html;
		}

		ob_start();
		include $template_path;
		$html = ob_get_clean();
$html = rtbcb_sanitize_report_html( $html );

		wp_cache_set( $cache_key, $html, 'rtbcb_reports', HOUR_IN_SECONDS );

		return $html;
	}

	/**
	* Generate fast mode report HTML.
	*
	* @param array $form_data   Form data.
	* @param array $calculations ROI calculations.
	*
	* @return string Report HTML.
	*/
	public function get_fast_report_html( $form_data, $calculations ) {
		$template_path = RTBCB_DIR . 'templates/fast-report-template.php';

		if ( ! file_exists( $template_path ) ) {
			return '';
		}

		$form_data    = is_array( $form_data ) ? $form_data : [];
		$calculations = is_array( $calculations ) ? $calculations : [];

		ob_start();
		include $template_path;
		$html = ob_get_clean();

return rtbcb_sanitize_report_html( $html );
	}

	/**
	* Generate comprehensive report HTML from template with proper data transformation.
	*
	* @param array $business_case_data Business case data.
	*
	* @return string
	*/
	private function get_comprehensive_report_html( $business_case_data ) {
		$template_path = RTBCB_DIR . 'templates/comprehensive-report-template.php';

		if ( file_exists( $template_path ) ) {
			rtbcb_log_api_debug( 'Router: using comprehensive template', [ 'template_path' => $template_path ] );
		} else {
			rtbcb_log_api_debug( 'Router: comprehensive template missing, using basic template', [ 'template_path' => $template_path ] );
			return $this->get_report_html( $business_case_data );
		}

	$business_case_data = is_array( $business_case_data ) ? $business_case_data : [];

	$report_data = $business_case_data['report_data'] ?? null;
	$hash_source = $report_data ?: $business_case_data;
	$data_hash  = md5( wp_json_encode( $hash_source ) );
	$version    = rtbcb_get_report_cache_version();
	$cache_key  = md5( $template_path . ':' . $data_hash . ':' . $version );

	$cached_html = wp_cache_get( $cache_key, 'rtbcb_reports' );
	if ( false !== $cached_html ) {
		return $cached_html;
	}

	if ( null === $report_data ) {
		// Transform data structure for comprehensive template.
		$report_data = rtbcb_transform_data_for_template( $business_case_data );
	}

	ob_start();
	include $template_path;
	$html = ob_get_clean();
$html = rtbcb_sanitize_report_html( $html );

	wp_cache_set( $cache_key, $html, 'rtbcb_reports', HOUR_IN_SECONDS );

	return $html;
	}
}

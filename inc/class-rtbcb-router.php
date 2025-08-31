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
     * @param string $report_type Optional report type (basic or comprehensive).
     *
     * @return void
     */
    public function handle_form_submission( $report_type = 'basic' ) {
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
            $validated_data = $validator->validate( $_POST );

            if ( isset( $validated_data['error'] ) ) {
                wp_send_json_error( [ 'message' => $validated_data['error'] ], 400 );
                return;
            }

            $form_data = $validated_data;

            // Determine report type from request if provided.
            if ( isset( $_POST['report_type'] ) ) {
                $report_type = sanitize_text_field( wp_unslash( $_POST['report_type'] ) );
            }

            // Instantiate necessary classes.
            $llm = new RTBCB_LLM();
            $rag = new RTBCB_RAG();

            // Perform calculations.
            $calculations = RTBCB_Calculator::calculate_roi( $form_data );

            // Generate context from RAG.
            $rag_context = $rag->get_context( $form_data['company_description'] );

            if ( 'comprehensive' === $report_type ) {
                // Generate comprehensive business case with LLM.
                $business_case_data = $llm->generate_comprehensive_business_case( $form_data, $calculations, $rag_context );
            } else {
                // Route to the appropriate model.
                $model = $this->route_model( $form_data, $rag_context );
                if ( is_wp_error( $model ) ) {
                    throw new Exception( $model->get_error_message() );
                }

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
$lead_id = RTBCB_Leads::save_lead( $form_data, $business_case_data );

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
        } catch ( Exception $e ) {
            // Log the detailed error to debug.log.
            error_log( 'RTBCB Form Submission Error: ' . $e->getMessage() );

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
        $mini_model    = get_option( 'rtbcb_mini_model', rtbcb_get_default_model( 'mini' ) );
        $premium_model = get_option( 'rtbcb_premium_model', rtbcb_get_default_model( 'premium' ) );

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
            error_log( 'RTBCB: ' . $error->get_error_message() );
            return $error;
        }

        error_log( "RTBCB: Model selected: {$model} (Complexity: {$complexity}, Category: {$category}, Reason: {$reasoning})" );

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

       $data_hash = md5( wp_json_encode( $business_case_data ) );
       $cache_key = md5( $template_path . ':' . $data_hash );

       $cached_html = function_exists( 'wp_cache_get' ) ? wp_cache_get( $cache_key, 'rtbcb_reports' ) : false;
       if ( false !== $cached_html ) {
           return $cached_html;
       }

       ob_start();
       include $template_path;
       $html = ob_get_clean();
       $html = wp_kses( $html, rtbcb_get_report_allowed_html() );

       if ( function_exists( 'wp_cache_set' ) ) {
           wp_cache_set( $cache_key, $html, 'rtbcb_reports', HOUR_IN_SECONDS );
       }

       return $html;
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
       $cache_key  = md5( $template_path . ':' . $data_hash );

       $cached_html = function_exists( 'wp_cache_get' ) ? wp_cache_get( $cache_key, 'rtbcb_reports' ) : false;
       if ( false !== $cached_html ) {
           return $cached_html;
       }

       if ( null === $report_data ) {
           // Transform data structure for comprehensive template.
           $report_data = $this->transform_data_for_template( $business_case_data );
       }

       ob_start();
       include $template_path;
       $html = ob_get_clean();
       $html = wp_kses( $html, rtbcb_get_report_allowed_html() );

       if ( function_exists( 'wp_cache_set' ) ) {
           wp_cache_set( $cache_key, $html, 'rtbcb_reports', HOUR_IN_SECONDS );
       }

       return $html;
   }

   /**
    * Transform LLM response data into the structure expected by comprehensive template.
    *
    * @param array $business_case_data Business case data.
    *
    * @return array
    */
   private function transform_data_for_template( $business_case_data ) {
       $defaults = [
           'company_name'           => '',
           'base_roi'               => 0,
           'roi_base'               => 0,
           'recommended_category'   => '',
           'category_info'          => [],
           'executive_summary'      => '',
           'narrative'              => '',
           'executive_recommendation' => '',
           'recommendation'         => '',
           'payback_months'         => 'N/A',
           'sensitivity_analysis'   => [],
           'company_analysis'       => '',
           'maturity_level'         => 'intermediate',
           'current_state_analysis' => '',
           'market_analysis'        => '',
           'tech_adoption_level'    => 'medium',
           'operational_analysis'   => [],
           'risks'                  => [],
           'confidence'             => 0.85,
           'processing_time'        => 0,
       ];
       $business_case_data = wp_parse_args( (array) $business_case_data, $defaults );

       // Get current company data.
       $company      = rtbcb_get_current_company();
       $company_name = sanitize_text_field( $business_case_data['company_name'] ?: ( $company['name'] ?? __( 'Your Company', 'rtbcb' ) ) );

       // Derive recommended category and details from recommendation if not provided.
       $recommended_category = sanitize_text_field( $business_case_data['recommended_category'] ?: ( $business_case_data['recommendation']['recommended'] ?? 'treasury_management_system' ) );
       $category_details     = $business_case_data['category_info'] ?: ( $business_case_data['recommendation']['category_info'] ?? [] );

       // Prepare operational and risk data with fallbacks.
       $operational_analysis = array_map( 'sanitize_text_field', (array) $business_case_data['operational_analysis'] );
       if ( empty( $operational_analysis ) ) {
           $operational_analysis = [ __( 'No data provided', 'rtbcb' ) ];
       }

       $implementation_risks = array_map( 'sanitize_text_field', (array) $business_case_data['risks'] );
       if ( empty( $implementation_risks ) ) {
           $implementation_risks = [ __( 'No data provided', 'rtbcb' ) ];
       }

       // Create structured data format expected by template.
       $report_data = [
           'metadata'           => [
               'company_name'     => $company_name,
               'analysis_date'    => current_time( 'Y-m-d' ),
               'confidence_level' => floatval( $business_case_data['confidence'] ),
               'processing_time'  => intval( $business_case_data['processing_time'] ),
           ],
           'executive_summary'  => [
               'strategic_positioning'    => wp_kses_post( $business_case_data['executive_summary'] ?: $business_case_data['narrative'] ),
               'key_value_drivers'       => $this->extract_value_drivers( $business_case_data ),
               'executive_recommendation' => wp_kses_post( $business_case_data['executive_recommendation'] ?: $business_case_data['recommendation'] ),
               'business_case_strength'  => $this->determine_business_case_strength( $business_case_data ),
           ],
           'financial_analysis' => [
               'roi_scenarios'      => $this->format_roi_scenarios( $business_case_data ),
               'payback_analysis'   => [
                   'payback_months' => sanitize_text_field( $business_case_data['payback_months'] ),
               ],
               'sensitivity_analysis' => $business_case_data['sensitivity_analysis'],
           ],
           'company_intelligence' => [
               'enriched_profile' => [
                   'enhanced_description' => wp_kses_post( $business_case_data['company_analysis'] ),
                   'maturity_level'       => sanitize_text_field( $business_case_data['maturity_level'] ),
                   'treasury_maturity'    => [
                       'current_state' => wp_kses_post( $business_case_data['current_state_analysis'] ),
                   ],
               ],
               'industry_context' => [
                   'sector_analysis' => [
                       'market_dynamics' => wp_kses_post( $business_case_data['market_analysis'] ),
                   ],
                   'benchmarking'   => [
                       'technology_penetration' => sanitize_text_field( $business_case_data['tech_adoption_level'] ),
                   ],
               ],
           ],
           'technology_strategy' => [
               'recommended_category' => $recommended_category,
               'category_details'     => $category_details,
           ],
           'operational_insights' => $operational_analysis,
           'risk_analysis'        => [
               'implementation_risks' => $implementation_risks,
           ],
           'action_plan'          => [
               'immediate_steps'      => $this->extract_immediate_steps( $business_case_data ),
               'short_term_milestones'=> $this->extract_short_term_steps( $business_case_data ),
               'long_term_objectives' => $this->extract_long_term_steps( $business_case_data ),
           ],
       ];

       return $report_data;
   }

   /**
    * Extract value drivers from business case data.
    *
    * @param array $data Business case data.
    *
    * @return array
    */
   private function extract_value_drivers( $data ) {
       $drivers = [];

       // Extract from various possible sources.
       if ( ! empty( $data['value_drivers'] ) ) {
           $drivers = (array) $data['value_drivers'];
       } elseif ( ! empty( $data['key_benefits'] ) ) {
           $drivers = (array) $data['key_benefits'];
       } else {
           // Default value drivers.
           $drivers = [
               __( 'Automated cash management processes', 'rtbcb' ),
               __( 'Enhanced financial visibility and reporting', 'rtbcb' ),
               __( 'Reduced operational risk and errors', 'rtbcb' ),
               __( 'Improved regulatory compliance', 'rtbcb' ),
           ];
       }

       return array_slice( $drivers, 0, 4 );
   }

   /**
    * Format ROI scenarios for template.
    *
    * @param array $data Business case data.
    *
    * @return array
    */
   private function format_roi_scenarios( $data ) {
       // Try to get ROI data from various possible locations.
       if ( ! empty( $data['scenarios'] ) ) {
           return $data['scenarios'];
       }

       if ( ! empty( $data['roi_scenarios'] ) ) {
           return $data['roi_scenarios'];
       }

       // Fallback to default structure.
       return [
           'conservative' => [
               'total_annual_benefit' => $data['roi_low'] ?? 0,
               'labor_savings'        => ( $data['roi_low'] ?? 0 ) * 0.6,
               'fee_savings'          => ( $data['roi_low'] ?? 0 ) * 0.3,
               'error_reduction'      => ( $data['roi_low'] ?? 0 ) * 0.1,
           ],
           'base' => [
               'total_annual_benefit' => $data['roi_base'] ?? 0,
               'labor_savings'        => ( $data['roi_base'] ?? 0 ) * 0.6,
               'fee_savings'          => ( $data['roi_base'] ?? 0 ) * 0.3,
               'error_reduction'      => ( $data['roi_base'] ?? 0 ) * 0.1,
           ],
           'optimistic' => [
               'total_annual_benefit' => $data['roi_high'] ?? 0,
               'labor_savings'        => ( $data['roi_high'] ?? 0 ) * 0.6,
               'fee_savings'          => ( $data['roi_high'] ?? 0 ) * 0.3,
               'error_reduction'      => ( $data['roi_high'] ?? 0 ) * 0.1,
           ],
       ];
   }

   /**
    * Determine business case strength based on ROI.
    *
    * @param array $data Business case data.
    *
    * @return string
    */
   private function determine_business_case_strength( $data ) {
       $base_roi = $data['roi_base'] ?? $data['scenarios']['base']['total_annual_benefit'] ?? 0;

       if ( $base_roi > 500000 ) {
           return 'Compelling';
       } elseif ( $base_roi > 200000 ) {
           return 'Strong';
       } elseif ( $base_roi > 50000 ) {
           return 'Moderate';
       } else {
           return 'Developing';
       }
   }

   /**
    * Extract action steps from business case data.
    *
    * @param array $data Business case data.
    *
    * @return array
    */
   private function extract_immediate_steps( $data ) {
       if ( ! empty( $data['next_actions'] ) ) {
           $all_actions = (array) $data['next_actions'];
           return array_slice( $all_actions, 0, 3 );
       }

       return [
           __( 'Secure executive sponsorship and budget approval', 'rtbcb' ),
           __( 'Form project steering committee', 'rtbcb' ),
           __( 'Conduct detailed requirements gathering', 'rtbcb' ),
       ];
   }

   /**
    * Extract short term action steps.
    *
    * @param array $data Business case data.
    *
    * @return array
    */
   private function extract_short_term_steps( $data ) {
       if ( ! empty( $data['implementation_steps'] ) ) {
           $steps = (array) $data['implementation_steps'];
           return array_slice( $steps, 0, 4 );
       }

       return [
           __( 'Issue RFP to qualified vendors', 'rtbcb' ),
           __( 'Conduct vendor demonstrations and evaluations', 'rtbcb' ),
           __( 'Negotiate contracts and terms', 'rtbcb' ),
           __( 'Begin system implementation planning', 'rtbcb' ),
       ];
   }

   /**
    * Extract long term action steps.
    *
    * @param array $data Business case data.
    *
    * @return array
    */
   private function extract_long_term_steps( $data ) {
       return [
           __( 'Complete system implementation and testing', 'rtbcb' ),
           __( 'Conduct user training and change management', 'rtbcb' ),
           __( 'Measure and optimize system performance', 'rtbcb' ),
           __( 'Expand functionality and integration capabilities', 'rtbcb' ),
       ];
   }
}


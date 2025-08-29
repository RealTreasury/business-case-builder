<?php
/**
 * Routes model selection based on request complexity.
 *
 * @package RealTreasuryBusinessCaseBuilder
 */

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
            || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['rtbcb_nonce'] ) ), 'rtbcb_form_action' )
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
                throw new Exception( $business_case_data->get_error_message() );
            }

            // Generate report HTML based on type.
            $report_html = 'comprehensive' === $report_type ?
                $this->get_comprehensive_report_html( $business_case_data ) :
                $this->get_report_html( $business_case_data );

            // Save the lead.
            $leads   = new RTBCB_Leads();
            $lead_id = $leads->save_lead( $form_data, $business_case_data );

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

        ob_start();
        include $template_path;
        $html = ob_get_clean();

        return wp_kses_post( $html );
    }

    /**
     * Generate comprehensive report HTML.
     *
     * @param array $business_case_data Business case data.
     *
     * @return string Report HTML.
     */
    private function get_comprehensive_report_html( $business_case_data ) {
        $template_path = RTBCB_DIR . 'templates/comprehensive-report-template.php';

        if ( ! file_exists( $template_path ) ) {
            return $this->get_report_html( $business_case_data );
        }

        $business_case_data = is_array( $business_case_data ) ? $business_case_data : [];

        ob_start();
        include $template_path;
        $html = ob_get_clean();

        return wp_kses_post( $html );
    }
}


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
     * @return void
     */
    public function handle_form_submission() {
        // Nonce verification.
        if ( ! isset( $_POST['rtbcb_nonce'] ) || ! wp_verify_nonce( $_POST['rtbcb_nonce'], 'rtbcb_form_action' ) ) {
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

            // Instantiate necessary classes.
            $llm = new RTBCB_LLM();
            $rag = new RTBCB_RAG();

            // Perform calculations.
            $calculations = RTBCB_Calculator::calculate_roi( $form_data );

            // Generate context from RAG.
            $rag_context = $rag->get_context( $form_data['company_description'] );

            // Generate business case with LLM.
            $business_case_data = $llm->generate_business_case( $form_data, $calculations, $rag_context );

            // Save the lead.
            $leads   = new RTBCB_Leads();
            $lead_id = $leads->save_lead( $form_data, $business_case_data );

            // Check for LLM generation errors.
            if ( is_wp_error( $business_case_data ) ) {
                throw new Exception( $business_case_data->get_error_message() );
            }

            // Send success response.
            wp_send_json_success(
                [
                    'message'     => __( 'Business case generated successfully.', 'rtbcb' ),
                    'report_id'   => $lead_id,
                    'report_html' => $this->get_report_html( $business_case_data ),
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
     * @return string Model name.
     */
    public function route_model( $inputs, $chunks ) {
        $complexity = $this->calculate_complexity( $inputs, $chunks );
        $category   = RTBCB_Category_Recommender::recommend_category( $inputs )['recommended'];

        $model = get_option( 'rtbcb_mini_model', 'gpt-4o-mini' );

        if ( $complexity > 0.6 || 'trms' === $category ) {
            $model = get_option( 'rtbcb_premium_model', 'gpt-4o' );
        } elseif ( 'tms_lite' === $category && $complexity > 0.4 ) {
            $model = get_option( 'rtbcb_premium_model', 'gpt-4o' );
        }

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
    private function get_report_html( $business_case_data ) {
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
}


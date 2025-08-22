<?php
/**
 * Handles LLM interactions for the plugin.
 *
 * @package RealTreasuryBusinessCaseBuilder
 */

/**
 * Class RTBCB_LLM.
 */
class RTBCB_LLM {
    /**
     * OpenAI API key.
     *
     * @var string
     */
    private $api_key;

    /**
     * Available models.
     *
     * @var array
     */
    private $models;

    /**
     * Constructor.
     */
    public function __construct() {
        $this->api_key = get_option( 'rtbcb_openai_api_key' );
        $this->models  = [
            'mini'      => get_option( 'rtbcb_mini_model', 'gpt-4o-mini' ),
            'premium'   => get_option( 'rtbcb_premium_model', 'gpt-4o' ),
            'embedding' => get_option( 'rtbcb_embedding_model', 'text-embedding-3-small' ),
        ];
    }

    /**
     * Generate a business case narrative using the LLM.
     *
     * @param array $user_inputs   User provided inputs.
     * @param array $roi_data      ROI calculation results.
     * @param array $context_chunks Context chunks for RAG.
     *
     * @return array Parsed LLM response.
     */
    public function generate_business_case( $user_inputs, $roi_data, $context_chunks = [] ) {
        $category_data = [];
        if ( class_exists( 'RTBCB_Category_Recommender' ) ) {
            try {
                $category_data = RTBCB_Category_Recommender::recommend_category( $user_inputs );
            } catch ( Exception $e ) {
                error_log( 'RTBCB: Category recommendation failed - ' . $e->getMessage() );
            }
        }

        $model    = $this->select_model( $user_inputs, $context_chunks );
        $prompt   = $this->build_prompt( $user_inputs, $roi_data, $context_chunks, $category_data );
        $response = $this->call_openai( $model, $prompt );

        $parsed = $this->parse_response( $response, $category_data );

        if ( isset( $parsed['error'] ) ) {
            return $this->get_fallback_response( $category_data, $parsed['error'] );
        }

        return $parsed;
    }

    /**
     * Select the appropriate model for the request.
     *
     * @param array $inputs User inputs.
     * @param array $chunks Context chunks.
     *
     * @return string Model name.
     */
    private function select_model( $inputs, $chunks ) {
        // TODO: Implement model routing logic via RTBCB_Router class
        $router = new RTBCB_Router();
        return $router->route_model( $inputs, $chunks );
    }

    /**
     * Build the prompt for the LLM with stricter JSON requirements.
     *
     * @param array $inputs User inputs.
     * @param array $roi_data ROI data.
     * @param array $chunks Context chunks.
     * @param array $category_data Category data.
     *
     * @return string Prompt text.
     */
    private function build_prompt( $inputs, $roi_data, $chunks, $category_data = [] ) {
        $context = $this->format_context_chunks( $chunks );

        $recommended = '';
        if ( ! empty( $category_data['recommended'] ) ) {
            $recommended  = 'Recommended solution category: ' . $category_data['recommended'] . "\n";
            $recommended .= 'Reasoning: ' . ( $category_data['reasoning'] ?? '' ) . "\n\n";
        }

        $prompt  = "You are a CFO advisor creating a business case for treasury technology.\n\n";
        $prompt .= "CONTEXT from vendor research:\n{$context}\n\n";
        $prompt .= $recommended;
        $prompt .= "USER SITUATION:\n";
        $prompt .= "- Company size: {$inputs['company_size']}\n";
        if ( ! empty( $inputs['current_tech'] ) ) {
            $prompt .= "- Current technology: {$inputs['current_tech']}\n";
        }
        if ( ! empty( $inputs['business_objective'] ) ) {
            $prompt .= "- Primary objective: {$inputs['business_objective']}\n";
        }
        if ( ! empty( $inputs['implementation_timeline'] ) ) {
            $prompt .= "- Implementation timeline: {$inputs['implementation_timeline']}\n";
        }
        if ( ! empty( $inputs['budget_range'] ) ) {
            $prompt .= "- Budget range: {$inputs['budget_range']}\n";
        }
        $prompt .= "- Current pain points: " . implode( ', ', $inputs['pain_points'] ) . "\n\n";
        $prompt .= "ROI ANALYSIS (assumption-driven, not vendor pricing):\n";
        $prompt .= "- Base case annual benefit: $" . number_format( $roi_data['base']['total_annual_benefit'] ) . "\n\n";
        $prompt .= "Create a concise business case narrative (≤180 words) with CFO tone.\n\n";

        // More explicit JSON instructions
        $prompt .= "CRITICAL: Respond with ONLY valid JSON. No explanations, no markdown, no additional text.\n";
        $prompt .= "Return exactly this JSON structure:\n\n";

        $prompt .= json_encode(
            [
                'narrative'            => 'Your business case narrative here',
                'risks'                => [ 'Implementation risk', 'Adoption risk' ],
                'assumptions_explained'=> [ 'Labor cost assumption', 'Efficiency assumption' ],
                'citations'            => [ [ 'ref' => 'source', 'loc' => 'location' ] ],
                'next_actions'         => [ 'Present to stakeholders', 'Evaluate vendors' ],
                'confidence'           => 0.85,
                'recommended_category' => $category_data['recommended'] ?? '',
            ],
            JSON_PRETTY_PRINT
        );

        return $prompt;
    }

    /**
     * Format context chunks into a single string.
     *
     * @param array $chunks Context chunks.
     *
     * @return string
     */
    private function format_context_chunks( $chunks ) {
        $formatted = '';
        foreach ( $chunks as $chunk ) {
            if ( empty( $chunk['text'] ) ) {
                continue;
            }
            $ref      = isset( $chunk['ref'] ) ? '[' . $chunk['ref'] . '] ' : '';
            $formatted .= $ref . $chunk['text'] . "\n";
        }
        return trim( $formatted );
    }

    /**
     * Call the OpenAI API with the provided prompt.
     *
     * @param string $model  Model name.
     * @param string $prompt Prompt to send.
     *
     * @return array|WP_Error Response object.
     */
    private function call_openai( $model, $prompt ) {
        if ( empty( $this->api_key ) ) {
            return new WP_Error( 'rtbcb_no_api_key', __( 'Missing OpenAI API key.', 'rtbcb' ) );
        }

        $allowed_models = [
            'gpt-4o',
            'gpt-4o-mini',
        ];

        if ( ! is_string( $model ) || '' === trim( $model ) || ! in_array( $model, $allowed_models, true ) ) {
            error_log( 'RTBCB: Invalid model specified. Falling back to gpt-4o.' );
            $model = 'gpt-4o';
        }

        $endpoint = 'https://api.openai.com/v1/chat/completions';
        $args     = [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->api_key,
                'Content-Type'  => 'application/json',
            ],
            'body'    => wp_json_encode(
                [
                    'model'       => $model,
                    'messages'    => [
                        [
                            'role'    => 'user',
                            'content' => $prompt,
                        ],
                    ],
                    'temperature' => 0.2,
                ]
            ),
            'timeout' => 60,
        ];

        $response = wp_remote_post( $endpoint, $args );
        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );

        if ( isset( $data['error'] ) ) {
            $message = sanitize_text_field( wp_strip_all_tags( $data['error']['message'] ?? '' ) );
            if ( empty( $message ) ) {
                $message = __( 'Unknown API error.', 'rtbcb' );
            }
            return new WP_Error( 'rtbcb_api_error', $message );
        }

        $code = wp_remote_retrieve_response_code( $response );
        if ( 200 !== $code ) {
            $message = sanitize_text_field( wp_strip_all_tags( $data['error']['message'] ?? '' ) );
            if ( empty( $message ) ) {
                $message = sprintf( __( 'API request failed (%d).', 'rtbcb' ), $code );
            }
            return new WP_Error( 'rtbcb_api_error', $message );
        }

        return $response;
    }

    /**
     * Parse the OpenAI API response with enhanced debugging.
     *
     * @param array|WP_Error $response Response from OpenAI.
     * @param array          $category_data Recommended category data.
     *
     * @return array Parsed response or error details.
     */
    private function parse_response( $response, $category_data ) {
        if ( is_wp_error( $response ) ) {
            return [ 'error' => $response->get_error_message() ];
        }

        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );

        // Log the raw API response for debugging
        error_log( 'RTBCB OpenAI Raw Response: ' . $body );

        if ( empty( $data['choices'][0]['message']['content'] ) ) {
            error_log( 'RTBCB: No content in OpenAI response' );
            return [ 'error' => __( 'Invalid response from OpenAI API.', 'rtbcb' ) ];
        }

        $content = $data['choices'][0]['message']['content'];

        // Log the content we're trying to parse
        error_log( 'RTBCB OpenAI Content: ' . $content );

        // Clean up common formatting issues from LLM responses
        $cleaned_content = $this->clean_llm_response( $content );

        // Log the cleaned content
        error_log( 'RTBCB Cleaned Content: ' . $cleaned_content );

        $decoded = json_decode( $cleaned_content, true );

        if ( null === $decoded ) {
            $json_error = json_last_error_msg();
            error_log( 'RTBCB JSON Decode Error: ' . $json_error );

            // Return fallback instead of error
            return $this->get_fallback_response( $category_data, 'JSON parsing failed: ' . $json_error );
        }

        return $this->ensure_output_structure( $decoded, $category_data );
    }

    /**
     * Clean common formatting issues from LLM responses.
     *
     * @param string $content Raw LLM response content.
     * @return string Cleaned content.
     */
    private function clean_llm_response( $content ) {
        // Remove markdown code blocks
        $content = preg_replace( '/```json\s*/', '', $content );
        $content = preg_replace( '/```\s*$/', '', $content );
        $content = preg_replace( '/```/', '', $content );

        // Remove any text before the first {
        $first_brace = strpos( $content, '{' );
        if ( false !== $first_brace ) {
            $content = substr( $content, $first_brace );
        }

        // Remove any text after the last }
        $last_brace = strrpos( $content, '}' );
        if ( false !== $last_brace ) {
            $content = substr( $content, 0, $last_brace + 1 );
        }

        return trim( $content );
    }

    /**
     * Ensure the LLM output contains all required fields.
     *
     * @param array $data          Decoded response.
     * @param array $category_data Recommended category data.
     *
     * @return array
     */
    private function ensure_output_structure( $data, $category_data ) {
        $defaults = [
            'narrative'            => '',
            'risks'                => [],
            'assumptions_explained'=> [],
            'citations'            => [],
            'next_actions'         => [],
            'confidence'           => 0,
            'recommended_category' => $category_data['recommended'] ?? '',
        ];

        return wp_parse_args( $data, $defaults );
    }

    /**
     * Generate a comprehensive fallback response when the API call fails.
     *
     * @param array  $category_data Category data for context.
     * @param string $error_message Error message.
     *
     * @return array Complete fallback response.
     */
    private function get_fallback_response( $category_data, $error_message ) {
        $category      = $category_data['recommended'] ?? 'Treasury Management System';
        $category_info = $category_data['category_info'] ?? [];
        $reasoning     = $category_data['reasoning'] ?? '';

        // Generate a meaningful narrative
        $narrative = sprintf(
            /* translators: %s: recommended category */
            __( 'Based on your treasury operations profile, implementing %s presents a compelling investment opportunity. ', 'rtbcb' ),
            $category
        );

        if ( ! empty( $reasoning ) ) {
            $narrative .= $reasoning . ' ';
        }

        $narrative .= __( 'The projected ROI demonstrates strong potential returns through operational efficiency gains, reduced manual processes, and improved cash management. ', 'rtbcb' );
        $narrative .= __( 'This technology investment aligns with industry best practices and provides a foundation for scalable treasury operations.', 'rtbcb' );

        return [
            'narrative'            => $narrative,
            'risks'                => [
                __( 'Implementation complexity may impact timeline', 'rtbcb' ),
                __( 'User adoption requires proper change management', 'rtbcb' ),
                __( 'Integration challenges with existing systems', 'rtbcb' ),
            ],
            'assumptions_explained'=> [
                __( 'Labor cost savings based on 30% efficiency improvement', 'rtbcb' ),
                __( 'Bank fee reduction through optimized cash positioning', 'rtbcb' ),
                __( 'Error reduction value from automated reconciliation', 'rtbcb' ),
            ],
            'citations'            => [],
            'next_actions'         => [
                __( 'Present business case to key stakeholders', 'rtbcb' ),
                sprintf( __( 'Evaluate %s solution providers', 'rtbcb' ), $category ),
                __( 'Develop detailed implementation timeline', 'rtbcb' ),
                __( 'Plan user training and change management program', 'rtbcb' ),
            ],
            'confidence'           => 0.75,
            'recommended_category' => $category_data['recommended'] ?? '',
            'fallback_used'        => true,
        ];
    }
}


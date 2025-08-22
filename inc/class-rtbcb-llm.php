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
     * Current inputs for fallback use.
     *
     * @var array
     */
    private $current_inputs = [];

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
        // Store inputs for fallback use
        $this->current_inputs = $user_inputs;

        // Rest of the method remains the same...
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
     * Build the prompt for the LLM with company name personalization.
     *
     * @param array $inputs        User inputs.
     * @param array $roi_data      ROI data.
     * @param array $chunks        Context chunks.
     * @param array $category_data Category data.
     *
     * @return string Prompt text.
     */
    private function build_prompt( $inputs, $roi_data, $chunks, $category_data = [] ) {
        $context = $this->format_context_chunks( $chunks );

        // Extract company information
        $company_name = $inputs['company_name'] ?? 'the company';
        $company_size = $inputs['company_size'] ?? '';
        $industry     = $inputs['industry'] ?? '';
        $job_title    = $inputs['job_title'] ?? '';

        // Build company context
        $company_context = $company_name;
        if ( $company_size && $industry ) {
            $company_context .= ", a {$company_size} {$industry} company";
        } elseif ( $company_size ) {
            $company_context .= ", a {$company_size} company";
        } elseif ( $industry ) {
            $company_context .= ", a {$industry} company";
        }

        $recommended = '';
        if ( ! empty( $category_data['recommended'] ) ) {
            $recommended  = 'Recommended solution category: ' . $category_data['recommended'] . "\n";
            $recommended .= 'Reasoning: ' . ( $category_data['reasoning'] ?? '' ) . "\n\n";
        }

        $prompt  = "You are a CFO advisor creating a business case for treasury technology specifically for {$company_context}.\n\n";

        if ( $context ) {
            $prompt .= "CONTEXT from vendor research:\n{$context}\n\n";
        }

        $prompt .= $recommended;
        $prompt .= "COMPANY PROFILE:\n";
        $prompt .= "- Company: {$company_name}\n";
        $prompt .= "- Size: {$company_size}\n";

        if ( $industry ) {
            $prompt .= "- Industry: {$industry}\n";
        }

        if ( $job_title ) {
            $prompt .= "- Requestor role: {$job_title}\n";
        }

        $prompt .= "\nCURRENT OPERATIONS:\n";
        $prompt .= "- Weekly reconciliation hours: " . ( $inputs['hours_reconciliation'] ?? 0 ) . "\n";
        $prompt .= "- Weekly cash positioning hours: " . ( $inputs['hours_cash_positioning'] ?? 0 ) . "\n";
        $prompt .= "- Number of banks: " . ( $inputs['num_banks'] ?? 0 ) . "\n";
        $prompt .= "- Treasury team size: " . ( $inputs['ftes'] ?? 0 ) . " FTEs\n";

        if ( ! empty( $inputs['business_objective'] ) ) {
            $prompt .= "- Primary objective: {$inputs['business_objective']}\n";
        }

        if ( ! empty( $inputs['implementation_timeline'] ) ) {
            $prompt .= "- Implementation timeline: {$inputs['implementation_timeline']}\n";
        }

        if ( ! empty( $inputs['budget_range'] ) ) {
            $prompt .= "- Budget range: {$inputs['budget_range']}\n";
        }

        $prompt .= "- Key challenges: " . implode( ', ', $inputs['pain_points'] ?? [] ) . "\n\n";

        $prompt .= "ROI ANALYSIS:\n";
        $prompt .= "- Conservative annual benefit: $" . number_format( $roi_data['conservative']['total_annual_benefit'] ?? 0 ) . "\n";
        $prompt .= "- Base case annual benefit: $" . number_format( $roi_data['base']['total_annual_benefit'] ?? 0 ) . "\n";
        $prompt .= "- Optimistic annual benefit: $" . number_format( $roi_data['optimistic']['total_annual_benefit'] ?? 0 ) . "\n\n";

        $prompt .= "Create a personalized business case narrative for {$company_name} with a CFO/executive tone.\n";
        $prompt .= "Address how this technology investment specifically benefits {$company_name}'s operations.\n\n";

        // More explicit JSON instructions with company personalization
        $prompt .= 'CRITICAL: Respond with ONLY valid JSON. No explanations, no markdown, no additional text.' . "\n";
        $prompt .= "Return exactly this JSON structure with {$company_name}-specific content:\n\n";

        $prompt .= json_encode(
            [
                'narrative'             => "Your personalized business case narrative for {$company_name} here (150-180 words)",
                'risks'                 => [
                    "Implementation risk specific to {$company_name}",
                    "Adoption risk for {$company_name} team",
                    "Integration challenges with {$company_name}'s systems",
                ],
                'assumptions_explained' => [
                    'Labor cost assumption explanation',
                    'Efficiency assumption explanation',
                    'Fee reduction assumption explanation',
                ],
                'citations'             => [ [ 'ref' => 'source', 'loc' => 'location' ] ],
                'next_actions'          => [
                    "Present to {$company_name} stakeholders",
                    'Evaluate vendors',
                    "Plan {$company_name} implementation",
                    "Design {$company_name} change management",
                ],
                'confidence'            => 0.85,
                'recommended_category'  => $category_data['recommended'] ?? '',
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
     * Parse structured API responses and validate HTTP status code.
     *
     * @param array|WP_Error $response Response from wp_remote_* call.
     *
     * @return array|WP_Error Parsed data or error object.
     */
    private function parse_structured_response( $response ) {
        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $code = wp_remote_retrieve_response_code( $response );
        error_log( 'RTBCB structured response status: ' . $code );

        if ( 200 !== $code ) {
            error_log( 'RTBCB structured response error: HTTP ' . $code );
            return new WP_Error(
                'rtbcb_api_error',
                sprintf( __( 'API request failed (%d).', 'rtbcb' ), $code )
            );
        }

        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );

        if ( null === $data ) {
            error_log( 'RTBCB structured response JSON decode error.' );
            return new WP_Error( 'rtbcb_api_error', __( 'Invalid JSON in API response.', 'rtbcb' ) );
        }

        return $data;
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
     * Enhanced fallback response generator with company name.
     *
     * @param array  $category_data Category data for context.
     * @param string $error_message Error message.
     *
     * @return array Complete fallback response.
     */
    private function get_fallback_response( $category_data, $error_message ) {
        $company_name = $this->current_inputs['company_name'] ?? 'your company';
        $category     = $category_data['recommended'] ?? 'Treasury Management System';
        $category_info = $category_data['category_info'] ?? [];
        $reasoning    = $category_data['reasoning'] ?? '';

        // Generate a meaningful narrative with company name
        $narrative = sprintf(
            __( '%s is well-positioned to realize significant value from implementing %s. ', 'rtbcb' ),
            $company_name,
            $category
        );

        if ( ! empty( $reasoning ) ) {
            $narrative .= $reasoning . ' ';
        }

        $narrative .= sprintf(
            __( 'The projected ROI demonstrates strong potential returns for %s through operational efficiency gains, reduced manual processes, and improved cash management. ', 'rtbcb' ),
            $company_name
        );

        $narrative .= sprintf(
            __( 'This technology investment aligns with %s\'s operational needs and provides a foundation for scalable treasury operations.', 'rtbcb' ),
            $company_name
        );

        return [
            'narrative'            => $narrative,
            'risks'                => [
                sprintf( __( 'Implementation complexity may impact %s\'s timeline', 'rtbcb' ), $company_name ),
                sprintf( __( 'User adoption at %s requires proper change management', 'rtbcb' ), $company_name ),
                sprintf( __( 'Integration challenges with %s\'s existing systems', 'rtbcb' ), $company_name ),
            ],
            'assumptions_explained'=> [
                __( 'Labor cost savings based on 30% efficiency improvement', 'rtbcb' ),
                __( 'Bank fee reduction through optimized cash positioning', 'rtbcb' ),
                __( 'Error reduction value from automated reconciliation', 'rtbcb' ),
            ],
            'citations'            => [],
            'next_actions'         => [
                sprintf( __( 'Present business case to %s leadership team', 'rtbcb' ), $company_name ),
                sprintf( __( 'Evaluate %s solution providers', 'rtbcb' ), $category ),
                sprintf( __( 'Develop %s-specific implementation timeline', 'rtbcb' ), $company_name ),
                sprintf( __( 'Plan %s user training and change management program', 'rtbcb' ), $company_name ),
            ],
            'confidence'           => 0.75,
            'recommended_category' => $category_data['recommended'] ?? '',
            'fallback_used'        => true,
        ];
    }
}


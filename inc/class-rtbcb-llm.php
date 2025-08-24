<?php
/**
 * Enhanced LLM integration with comprehensive business analysis
 *
 * @package RealTreasuryBusinessCaseBuilder
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers.php';

class RTBCB_LLM {
    private $api_key;
    private $current_inputs = [];
    private $gpt5_config;

    public function __construct() {
        $this->api_key = get_option( 'rtbcb_openai_api_key' );

        $config     = rtbcb_get_gpt5_config( get_option( 'rtbcb_gpt5_config', [] ) );
        $this->gpt5_config = $config;

        if ( empty( $this->api_key ) ) {
            error_log( 'RTBCB: OpenAI API key not configured' );
        }
    }

    /**
     * Get the configured model for a given tier.
     *
     * Retrieves the model name from the WordPress options table and falls
     * back to the plugin's default if the option is not set.
     *
     * @param string $tier Model tier identifier.
     * @return string Sanitized model name.
     */
    private function get_model( $tier ) {
        $tier    = sanitize_key( $tier );
        $default = rtbcb_get_default_model( $tier );
        $model   = get_option( "rtbcb_{$tier}_model", $default );

        return sanitize_text_field( $model );
    }

    /**
     * Generate a simplified business case analysis.
     *
     * Attempts to call the LLM for a brief analysis. If no API key is
     * configured or the LLM call fails, a {@see WP_Error} is returned for the
     * caller to handle.
     *
     * @param array       $user_inputs    Sanitized user inputs.
     * @param array       $roi_data       ROI calculation data.
     * @param array       $context_chunks Optional context strings for the prompt.
     * @param string|null $model          LLM model to use.
     *
     * @return array|WP_Error Simplified analysis array or error object.
     */
    public function generate_business_case( $user_inputs, $roi_data, $context_chunks = [], $model = null ) {
        $inputs = [
            'company_name'           => sanitize_text_field( $user_inputs['company_name'] ?? '' ),
            'company_size'           => sanitize_text_field( $user_inputs['company_size'] ?? '' ),
            'industry'               => sanitize_text_field( $user_inputs['industry'] ?? '' ),
            'hours_reconciliation'   => floatval( $user_inputs['hours_reconciliation'] ?? 0 ),
            'hours_cash_positioning' => floatval( $user_inputs['hours_cash_positioning'] ?? 0 ),
            'num_banks'              => intval( $user_inputs['num_banks'] ?? 0 ),
            'ftes'                   => floatval( $user_inputs['ftes'] ?? 0 ),
            'pain_points'            => array_map( 'sanitize_text_field', (array) ( $user_inputs['pain_points'] ?? [] ) ),
        ];

        $this->current_inputs = $inputs;

        if ( empty( $this->api_key ) ) {
            return new WP_Error( 'no_api_key', __( 'OpenAI API key not configured.', 'rtbcb' ) );
        }

        $selected_model = $model ? sanitize_text_field( $model ) : $this->get_model( 'mini' );
        $prompt = 'Create a concise treasury technology business case in JSON with keys '
            . 'executive_summary (strategic_positioning, business_case_strength, key_value_drivers[], '
            . 'executive_recommendation), operational_analysis (current_state_assessment), '
            . 'industry_insights (sector_trends, competitive_benchmarks, regulatory_considerations).'
            . '\nCompany: ' . $inputs['company_name']
            . '\nIndustry: ' . $inputs['industry']
            . '\nSize: ' . $inputs['company_size']
            . '\nPain Points: ' . implode( ', ', $inputs['pain_points'] );

        if ( ! empty( $context_chunks ) ) {
            $prompt .= '\nContext: ' . implode( '\n', array_map( 'sanitize_text_field', $context_chunks ) );
        }

        $history   = [
            [
                'role'    => 'user',
                'content' => $prompt,
            ],
        ];
        $context   = $this->build_context_for_responses( $history );
        $response  = $this->call_openai_with_retry( $selected_model, $context );

        if ( is_wp_error( $response ) ) {
            return new WP_Error( 'llm_failure', __( 'Unable to generate analysis at this time.', 'rtbcb' ) );
        }

        $parsed = rtbcb_parse_gpt5_response( $response );
        $json   = json_decode( $parsed['output_text'], true );

        if ( ! is_array( $json ) ) {
            return new WP_Error( 'llm_parse_error', __( 'Invalid response from language model.', 'rtbcb' ) );
        }

        $analysis = [
            'company_name'       => $inputs['company_name'],
            'analysis_date'      => current_time( 'Y-m-d' ),
            'executive_summary'  => [
                'strategic_positioning'   => sanitize_text_field( $json['executive_summary']['strategic_positioning'] ?? '' ),
                'business_case_strength'  => sanitize_text_field( $json['executive_summary']['business_case_strength'] ?? '' ),
                'key_value_drivers'       => array_map( 'sanitize_text_field', $json['executive_summary']['key_value_drivers'] ?? [] ),
                'executive_recommendation'=> sanitize_text_field( $json['executive_summary']['executive_recommendation'] ?? '' ),
            ],
            'operational_analysis' => [
                'current_state_assessment' => sanitize_text_field( $json['operational_analysis']['current_state_assessment'] ?? '' ),
            ],
            'industry_insights'   => [
                'sector_trends'          => sanitize_text_field( $json['industry_insights']['sector_trends'] ?? '' ),
                'competitive_benchmarks' => sanitize_text_field( $json['industry_insights']['competitive_benchmarks'] ?? '' ),
                'regulatory_considerations' => sanitize_text_field( $json['industry_insights']['regulatory_considerations'] ?? '' ),
            ],
            'financial_analysis' => $this->build_financial_analysis( $roi_data, $inputs ),
        ];

        return $analysis;
    }

    /**
     * Generate short commentary for a given industry.
     *
     * @param string $industry Industry slug.
     * @return string|WP_Error Commentary text or error object.
     */
    public function generate_industry_commentary( $industry ) {
        $industry = sanitize_text_field( $industry );

        if ( empty( $this->api_key ) ) {
            return new WP_Error( 'no_api_key', __( 'OpenAI API key not configured.', 'rtbcb' ) );
        }

        $model  = $this->get_model( 'mini' );
        $prompt = 'Provide a brief treasury industry commentary for the ' . $industry . ' industry in two sentences.';

        $history  = [
            [
                'role'    => 'user',
                'content' => $prompt,
            ],
        ];
        $context  = $this->build_context_for_responses( $history );
        $response = $this->call_openai_with_retry( $model, $context );

        if ( is_wp_error( $response ) ) {
            return new WP_Error( 'llm_failure', __( 'Unable to generate commentary at this time.', 'rtbcb' ) );
        }

        $parsed     = rtbcb_parse_gpt5_response( $response );
        $commentary = sanitize_textarea_field( $parsed['output_text'] );

        if ( empty( $commentary ) ) {
            return new WP_Error( 'llm_empty_response', __( 'No commentary returned.', 'rtbcb' ) );
        }

        return $commentary;
    }


    /**
     * Generate a comprehensive company overview with structured analysis.
     *
     * @param string $company_name  Company name.
     * @param bool   $include_prompt Whether to include prompt in response for debugging.
     * @return array|WP_Error Structured overview array or error object.
     */
    public function generate_company_overview( $company_name, $include_prompt = false ) {
        $company_name = sanitize_text_field( $company_name );

        if ( empty( $this->api_key ) ) {
            return new WP_Error( 'no_api_key', __( 'OpenAI API key not configured.', 'rtbcb' ) );
        }

        $model = $this->get_model( 'mini' );

        // Store prompts for debugging
        $system_prompt = 'You are a treasury technology consultant. Analyze companies and respond with valid JSON only using this exact structure:

{
  "analysis": "Comprehensive analysis covering: company background, size/revenue, recent developments, financial position, treasury challenges, market position, and technology readiness",
  "recommendations": ["Specific treasury tech recommendation 1", "Specific recommendation 2", "Strategic recommendation 3"],
  "references": ["https://credible-source-1.com", "https://credible-source-2.com"]
}

Focus on actionable insights. Keep analysis detailed but concise. Ensure recommendations are specific to the company size and industry.';

        $user_prompt = "Analyze $company_name for treasury technology opportunities. Include:

- Company overview (industry, size, business model)
- Recent financial/operational developments  
- Treasury challenges and pain points
- Market position and competitive context
- Technology adoption readiness
- Specific treasury tech recommendations
- Implementation considerations

Respond with the JSON structure only. No additional text.";

        $history = [
            [
                'role'    => 'user',
                'content' => $user_prompt,
            ],
        ];

        // Log prompt being sent
        error_log( "RTBCB: Sending company overview request for: {$company_name}" );
        error_log( 'RTBCB: System prompt length: ' . strlen( $system_prompt ) );
        error_log( 'RTBCB: User prompt length: ' . strlen( $user_prompt ) );

        $context  = $this->build_context_for_responses( $history, $system_prompt );
        $response = $this->call_openai_with_retry( $model, $context, 2 ); // Reduce retries for faster response

        if ( is_wp_error( $response ) ) {
            error_log( 'RTBCB: Company overview generation failed: ' . $response->get_error_message() );
            return new WP_Error( 'llm_failure', __( 'Unable to generate company overview. Please try again.', 'rtbcb' ) );
        }

        $parsed  = rtbcb_parse_gpt5_response( $response );
        $content = trim( $parsed['output_text'] );

        if ( empty( $content ) ) {
            return new WP_Error( 'llm_empty_response', __( 'No analysis returned. Please try again.', 'rtbcb' ) );
        }

        // Clean and parse JSON response
        $json_content = $this->clean_json_response( $content );
        $json         = json_decode( $json_content, true );

        if ( ! is_array( $json ) || json_last_error() !== JSON_ERROR_NONE ) {
            error_log( 'RTBCB: JSON parse error for company overview: ' . json_last_error_msg() );
            error_log( 'RTBCB: Response content: ' . substr( $content, 0, 500 ) );
            return new WP_Error( 'llm_parse_error', __( 'Invalid response format. Please try again.', 'rtbcb' ) );
        }

        // Validate required fields
        if ( empty( $json['analysis'] ) || empty( $json['recommendations'] ) ) {
            return new WP_Error( 'llm_incomplete_response', __( 'Incomplete analysis received. Please try again.', 'rtbcb' ) );
        }

        // Build response
        $result = [
            'company_name'   => $company_name,
            'analysis'       => sanitize_textarea_field( $json['analysis'] ),
            'recommendations'=> array_map( 'sanitize_text_field', array_filter( (array) $json['recommendations'] ) ),
            'references'     => array_map( 'esc_url_raw', array_filter( (array) ( $json['references'] ?? [] ) ) ),
            'generated_at'   => current_time( 'Y-m-d H:i:s' ),
            'analysis_type'  => 'company_overview',
        ];

        // Include prompt information for debugging if requested
        if ( $include_prompt ) {
            $result['prompt_sent'] = [
                'system' => $system_prompt,
                'user'   => $user_prompt,
            ];

            $result['debug_info'] = [
                'model_used'       => $model,
                'response_length'  => strlen( $content ),
                'json_parse_error' => json_last_error_msg(),
                'processing_time'  => $parsed['raw']['usage'] ?? null,
            ];
        }

        return $result;
    }

    /**
     * Clean JSON response by removing common formatting issues.
     *
     * @param string $content Raw response content.
     * @return string Cleaned JSON string.
     */
    private function clean_json_response( $content ) {
        // Remove markdown code blocks
        $content = preg_replace( '/```json\s*/', '', $content );
        $content = preg_replace( '/```\s*$/', '', $content );

        // Remove any text before the first {
        if ( preg_match( '/^.*?(\{.*\}).*$/s', $content, $matches ) ) {
            $content = $matches[1];
        }

        return trim( $content );
    }

    /**
     * Generate an industry overview.
     *
     * @param string $industry     Industry name.
     * @param string $company_size Company size description.
     * @return string|WP_Error Overview text or error object.
     */
    public function generate_industry_overview( $industry, $company_size ) {
        $industry     = sanitize_text_field( $industry );
        $company_size = sanitize_text_field( $company_size );

        if ( empty( $industry ) || empty( $company_size ) ) {
            return new WP_Error( 'invalid_params', __( 'Industry and company size required.', 'rtbcb' ) );
        }

        if ( empty( $this->api_key ) ) {
            return new WP_Error( 'no_api_key', __( 'OpenAI API key not configured.', 'rtbcb' ) );
        }

        $model  = $this->get_model( 'mini' );
        $prompt = 'Provide an industry overview for ' . $industry . ' companies of size ' . $company_size .
            '. Cover treasury challenges, key regulations, seasonal patterns, industry benchmarks, common pain points, and opportunities.';

        $history  = [
            [
                'role'    => 'user',
                'content' => $prompt,
            ],
        ];
        $context  = $this->build_context_for_responses( $history );
        $response = $this->call_openai_with_retry( $model, $context );

        if ( is_wp_error( $response ) ) {
            return new WP_Error( 'llm_failure', __( 'Unable to generate overview at this time.', 'rtbcb' ) );
        }

        $parsed   = rtbcb_parse_gpt5_response( $response );
        $overview = sanitize_textarea_field( $parsed['output_text'] );

        if ( empty( $overview ) ) {
            return new WP_Error( 'llm_empty_response', __( 'No overview returned.', 'rtbcb' ) );
        }

        return $overview;
    }

    /**
     * Generate a treasury technology overview.
     *
     * @param array $company_data Company data including focus areas and complexity.
     * @return string|WP_Error Overview text or error object.
     */
    public function generate_treasury_tech_overview( $company_data ) {
        $company_data = rtbcb_sanitize_form_data( (array) $company_data );
        $focus_areas  = array_map( 'sanitize_text_field', (array) ( $company_data['focus_areas'] ?? [] ) );
        $focus_areas  = array_filter( $focus_areas );
        $complexity   = sanitize_text_field( $company_data['complexity'] ?? '' );
        $name         = sanitize_text_field( $company_data['name'] ?? '' );
        $size         = sanitize_text_field( $company_data['size'] ?? '' );

        if ( empty( $focus_areas ) ) {
            return new WP_Error( 'no_focus_areas', __( 'No focus areas provided.', 'rtbcb' ) );
        }

        if ( empty( $this->api_key ) ) {
            return new WP_Error( 'no_api_key', __( 'OpenAI API key not configured.', 'rtbcb' ) );
        }

        $areas_list = implode( ', ', $focus_areas );
        $model      = $this->get_model( 'mini' );
        $prompt     = 'Provide a treasury technology overview for ' . $name . '. Company size: ' . $size . '. Complexity: ' . $complexity . '. Focus on: ' . $areas_list . '. Include current landscape, emerging trends, technology gaps, key vendor or solution comparisons, implementation considerations, and adoption trends.';

        $history  = [
            [
                'role'    => 'user',
                'content' => $prompt,
            ],
        ];
        $context  = $this->build_context_for_responses( $history );
        $response = $this->call_openai_with_retry( $model, $context );

        if ( is_wp_error( $response ) ) {
            return new WP_Error( 'llm_failure', __( 'Unable to generate overview at this time.', 'rtbcb' ) );
        }

        $parsed   = rtbcb_parse_gpt5_response( $response );
        $overview = sanitize_textarea_field( $parsed['output_text'] );

        if ( empty( $overview ) ) {
            return new WP_Error( 'llm_empty_response', __( 'No overview returned.', 'rtbcb' ) );
        }

        return $overview;
    }

    /**
     * Generate a Real Treasury platform overview.
     *
     * @param array $company_data {
     *     Company context data.
     *
     *     @type bool   $include_portal Include portal integration details.
     *     @type string $company_size   Company size description.
     *     @type string $industry       Company industry.
     *     @type array  $challenges     List of identified challenges.
     *     @type array  $categories     Vendor categories to highlight.
     * }
     * @return string|WP_Error Overview text or error object.
     */
    public function generate_real_treasury_overview( $company_data ) {
        $include_portal = ! empty( $company_data['include_portal'] );
        $company_size   = sanitize_text_field( $company_data['company_size'] ?? '' );
        $industry       = sanitize_text_field( $company_data['industry'] ?? '' );
        $challenges     = array_filter( array_map( 'sanitize_text_field', (array) ( $company_data['challenges'] ?? [] ) ) );
        $categories     = array_filter( array_map( 'sanitize_text_field', (array) ( $company_data['categories'] ?? [] ) ) );

        if ( empty( $this->api_key ) ) {
            return new WP_Error( 'no_api_key', __( 'OpenAI API key not configured.', 'rtbcb' ) );
        }

        $model  = $this->get_model( 'mini' );
        $prompt = 'Provide a Real Treasury platform overview for a ' . ( $company_size ?: __( 'company', 'rtbcb' ) ) . ' in the ' . ( $industry ?: __( 'unspecified', 'rtbcb' ) ) . ' industry.';

        if ( ! empty( $challenges ) ) {
            $prompt .= ' Address these challenges: ' . implode( ', ', $challenges ) . '.';
        }

        if ( ! empty( $categories ) ) {
            $prompt .= ' Highlight vendor categories: ' . implode( ', ', $categories ) . '.';
        }

        if ( $include_portal ) {
            $prompt .= ' Include how the Real Treasury portal complements the platform.';
        }

        $prompt .= ' Provide sections for platform capabilities, portal integration benefits, vendor ecosystem overview, Real Treasury differentiators, implementation approach, and support/community aspects.';

        $history  = [
            [
                'role'    => 'user',
                'content' => $prompt,
            ],
        ];
        $context  = $this->build_context_for_responses( $history );
        $response = $this->call_openai_with_retry( $model, $context );

        if ( is_wp_error( $response ) ) {
            return new WP_Error( 'llm_failure', __( 'Unable to generate overview at this time.', 'rtbcb' ) );
        }

        $parsed   = rtbcb_parse_gpt5_response( $response );
        $overview = sanitize_textarea_field( $parsed['output_text'] );

        if ( empty( $overview ) ) {
            return new WP_Error( 'llm_empty_response', __( 'No overview returned.', 'rtbcb' ) );
        }

        return $overview;
    }

    /**
     * Generate implementation roadmap and success factors for a category.
     *
     * @param string $category Category key.
     * @return array|WP_Error Roadmap and success factor text or error object.
     */
    public function generate_category_recommendation( $category ) {
        $category = sanitize_text_field( $category );

        if ( empty( $this->api_key ) ) {
            return new WP_Error( 'no_api_key', __( 'OpenAI API key not configured.', 'rtbcb' ) );
        }

        $info = RTBCB_Category_Recommender::get_category_info( $category );
        if ( empty( $info ) ) {
            return new WP_Error( 'invalid_category', __( 'Invalid category.', 'rtbcb' ) );
        }

        $model  = $this->get_model( 'mini' );
        $prompt = 'Return a JSON object with keys "roadmap" and "success_factors" describing the implementation roadmap and key success factors for adopting a ' . $info['name'] . ' solution.';

        $history  = [
            [
                'role'    => 'user',
                'content' => $prompt,
            ],
        ];
        $context  = $this->build_context_for_responses( $history );
        $response = $this->call_openai_with_retry( $model, $context );

        if ( is_wp_error( $response ) ) {
            return new WP_Error( 'llm_failure', __( 'Unable to generate recommendation details at this time.', 'rtbcb' ) );
        }

        $parsed_response = rtbcb_parse_gpt5_response( $response );
        $parsed          = json_decode( $parsed_response['output_text'], true );

        if ( empty( $parsed ) || ! is_array( $parsed ) ) {
            return new WP_Error( 'llm_empty_response', __( 'No recommendation details returned.', 'rtbcb' ) );
        }

        return [
            'roadmap'        => sanitize_textarea_field( $parsed['roadmap'] ?? '' ),
            'success_factors' => sanitize_textarea_field( $parsed['success_factors'] ?? '' ),
        ];
    }

    /**
     * Generate benefits estimate based on company metrics and category.
     *
     * @param float  $revenue     Annual revenue.
     * @param int    $staff_count Number of staff.
     * @param float  $efficiency  Current efficiency percentage.
     * @param string $category    Solution category.
     * @return array|WP_Error Structured benefits estimate or error object.
     */
    public function generate_benefits_estimate( $revenue, $staff_count, $efficiency, $category ) {
        $revenue     = floatval( $revenue );
        $staff_count = intval( $staff_count );
        $efficiency  = floatval( $efficiency );
        $category    = sanitize_text_field( $category );

        if ( empty( $this->api_key ) ) {
            return new WP_Error( 'no_api_key', __( 'OpenAI API key not configured.', 'rtbcb' ) );
        }

        $model  = $this->get_model( 'mini' );
        $prompt = 'Return a JSON object with keys "time_savings_hours", "cost_reduction_usd", '
            . '"efficiency_gain_percent", "roi_percent", "roi_timeline_months", '
            . '"risk_mitigation", "productivity_gain_percent" describing expected benefits for a '
            . $category . ' solution. Revenue: ' . $revenue . ', Staff: ' . $staff_count . ', '
            . 'Efficiency: ' . $efficiency . '.';

        $history  = [
            [
                'role'    => 'user',
                'content' => $prompt,
            ],
        ];
        $context  = $this->build_context_for_responses( $history );
        $response = $this->call_openai_with_retry( $model, $context );

        if ( is_wp_error( $response ) ) {
            return new WP_Error( 'llm_failure', __( 'Unable to generate benefits estimate at this time.', 'rtbcb' ) );
        }

        $parsed_response = rtbcb_parse_gpt5_response( $response );
        $parsed          = json_decode( $parsed_response['output_text'], true );

        if ( empty( $parsed ) || ! is_array( $parsed ) ) {
            return new WP_Error( 'llm_empty_response', __( 'No estimate returned.', 'rtbcb' ) );
        }

        $estimate = [
            'time_savings_hours'       => floatval( $parsed['time_savings_hours'] ?? 0 ),
            'cost_reduction_usd'       => floatval( $parsed['cost_reduction_usd'] ?? 0 ),
            'efficiency_gain_percent'  => floatval( $parsed['efficiency_gain_percent'] ?? 0 ),
            'roi_percent'              => floatval( $parsed['roi_percent'] ?? 0 ),
            'roi_timeline_months'      => floatval( $parsed['roi_timeline_months'] ?? 0 ),
            'risk_mitigation'          => sanitize_textarea_field( $parsed['risk_mitigation'] ?? '' ),
            'productivity_gain_percent'=> floatval( $parsed['productivity_gain_percent'] ?? 0 ),
        ];

        return $estimate;
    }

    /**
     * Generate comprehensive business case with deep analysis.
     *
     * Returns a {@see WP_Error} when the API key is missing or when the LLM
     * call or response parsing fails.
     *
     * @param array $user_inputs    Sanitized user inputs.
     * @param array $roi_data       ROI calculation data.
     * @param array $context_chunks Optional context strings for the prompt.
     *
     * @return array|WP_Error Comprehensive analysis array or error object.
     */
    public function generate_comprehensive_business_case( $user_inputs, $roi_data, $context_chunks = [] ) {
        $this->current_inputs = $user_inputs;

        if ( empty( $this->api_key ) ) {
            return new WP_Error( 'no_api_key', __( 'OpenAI API key not configured.', 'rtbcb' ) );
        }

        // Enhanced company research
        $company_research = $this->conduct_company_research( $user_inputs );
        
        // Industry analysis
        $industry_analysis = $this->analyze_industry_context( $user_inputs );
        if ( is_wp_error( $industry_analysis ) ) {
            return $industry_analysis;
        }

        // Technology landscape research
        $tech_landscape = $this->research_treasury_solutions( $user_inputs, $context_chunks );
        if ( is_wp_error( $tech_landscape ) ) {
            return $tech_landscape;
        }
        
        // Generate comprehensive report
        $model = $this->select_optimal_model( $user_inputs, $context_chunks );
        $prompt = $this->build_comprehensive_prompt( 
            $user_inputs, 
            $roi_data, 
            $company_research,
            $industry_analysis,
            $tech_landscape
        );
        
        $history  = [
            [
                'role'    => 'user',
                'content' => $prompt,
            ],
        ];
        $context  = $this->build_context_for_responses( $history );
        $response = $this->call_openai_with_retry( $model, $context );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $parsed = $this->parse_comprehensive_response( $response );

        if ( is_wp_error( $parsed ) ) {
            return $parsed;
        }

        if ( isset( $parsed['error'] ) ) {
            return new WP_Error( 'llm_parse_error', $parsed['error'] );
        }

        return $this->enhance_with_research( $parsed, $company_research, $industry_analysis );
    }

    /**
     * Parse and validate comprehensive OpenAI response.
     *
     * @param array $response Raw response from wp_remote_post.
     *
     * @return array|WP_Error Structured analysis array or error object.
     */
    private function parse_comprehensive_response( $response ) {
        $parsed_response = rtbcb_parse_gpt5_response( $response );
        $content         = $parsed_response['output_text'];
        $decoded         = $parsed_response['raw'];

        if ( empty( $decoded ) ) {
            return new WP_Error( 'llm_response_decode_error', __( 'Failed to decode response body.', 'rtbcb' ) );
        }

        if ( is_string( $content ) ) {
            $json_content = rtbcb_clean_json_content( $content );
            $json         = json_decode( $json_content, true );

            if ( json_last_error() !== JSON_ERROR_NONE ) {
                $error_msg = 'Invalid JSON response format: ' . json_last_error_msg();
                error_log( 'RTBCB: JSON decode failed: ' . $error_msg );
                error_log( 'RTBCB: Raw content length: ' . strlen( $json_content ) );
                error_log( 'RTBCB: Content preview: ' . substr( $json_content, 0, 500 ) );

                if ( empty( $json_content ) ) {
                    return new WP_Error( 'llm_parse_error', __( 'Empty response content', 'rtbcb' ) );
                } elseif ( strlen( $json_content ) < 50 ) {
                    return new WP_Error( 'llm_parse_error', __( 'Response too short: ', 'rtbcb' ) . $json_content );
                } else {
                    return new WP_Error( 'llm_parse_error', $error_msg );
                }
            }
        } else {
            $json = is_array( $content ) ? $content : [];
        }

        if ( ! is_array( $json ) ) {
            return new WP_Error( 'llm_response_parse_error', __( 'Invalid JSON from language model.', 'rtbcb' ) );
        }

        $required = [
            'executive_summary',
            'operational_analysis',
            'industry_insights',
            'technology_recommendations',
            'financial_analysis',
            'risk_mitigation',
            'next_steps',
        ];

        $missing = array_diff( $required, array_keys( $json ) );

        if ( ! empty( $missing ) ) {
            return new WP_Error(
                'llm_missing_fields',
                __( 'Missing required fields: ', 'rtbcb' ) . implode( ', ', $missing )
            );
        }

        return [
            'executive_summary'      => [
                'strategic_positioning'   => sanitize_text_field( $json['executive_summary']['strategic_positioning'] ?? '' ),
                'business_case_strength'  => sanitize_text_field( $json['executive_summary']['business_case_strength'] ?? '' ),
                'key_value_drivers'       => array_map( 'sanitize_text_field', $json['executive_summary']['key_value_drivers'] ?? [] ),
                'executive_recommendation'=> sanitize_text_field( $json['executive_summary']['executive_recommendation'] ?? '' ),
                'confidence_level'        => floatval( $json['executive_summary']['confidence_level'] ?? 0 ),
            ],
            'operational_analysis'   => [
                'current_state_assessment' => [
                    'efficiency_rating'   => sanitize_text_field( $json['operational_analysis']['current_state_assessment']['efficiency_rating'] ?? '' ),
                    'benchmark_comparison'=> sanitize_text_field( $json['operational_analysis']['current_state_assessment']['benchmark_comparison'] ?? '' ),
                    'capacity_utilization'=> sanitize_text_field( $json['operational_analysis']['current_state_assessment']['capacity_utilization'] ?? '' ),
                ],
                'process_inefficiencies'  => array_map(
                    function ( $item ) {
                        return [
                            'process'     => sanitize_text_field( $item['process'] ?? '' ),
                            'impact'      => sanitize_text_field( $item['impact'] ?? '' ),
                            'description' => sanitize_text_field( $item['description'] ?? '' ),
                        ];
                    },
                    $json['operational_analysis']['process_inefficiencies'] ?? []
                ),
                'automation_opportunities' => array_map(
                    function ( $item ) {
                        return [
                            'area'                 => sanitize_text_field( $item['area'] ?? '' ),
                            'complexity'           => sanitize_text_field( $item['complexity'] ?? '' ),
                            'potential_hours_saved'=> floatval( $item['potential_hours_saved'] ?? 0 ),
                        ];
                    },
                    $json['operational_analysis']['automation_opportunities'] ?? []
                ),
            ],
            'industry_insights'      => [
                'sector_trends'          => sanitize_text_field( $json['industry_insights']['sector_trends'] ?? '' ),
                'competitive_benchmarks' => sanitize_text_field( $json['industry_insights']['competitive_benchmarks'] ?? '' ),
                'regulatory_considerations' => sanitize_text_field( $json['industry_insights']['regulatory_considerations'] ?? '' ),
            ],
            'technology_recommendations' => [
                'primary_solution' => [
                    'category'     => sanitize_text_field( $json['technology_recommendations']['primary_solution']['category'] ?? '' ),
                    'rationale'    => sanitize_text_field( $json['technology_recommendations']['primary_solution']['rationale'] ?? '' ),
                    'key_features' => array_map( 'sanitize_text_field', $json['technology_recommendations']['primary_solution']['key_features'] ?? [] ),
                ],
                'implementation_approach' => [
                    'phase_1'        => sanitize_text_field( $json['technology_recommendations']['implementation_approach']['phase_1'] ?? '' ),
                    'phase_2'        => sanitize_text_field( $json['technology_recommendations']['implementation_approach']['phase_2'] ?? '' ),
                    'success_metrics'=> array_map( 'sanitize_text_field', $json['technology_recommendations']['implementation_approach']['success_metrics'] ?? [] ),
                ],
            ],
            'financial_analysis'     => [
                'investment_breakdown' => [
                    'software_licensing'      => sanitize_text_field( $json['financial_analysis']['investment_breakdown']['software_licensing'] ?? '' ),
                    'implementation_services' => sanitize_text_field( $json['financial_analysis']['investment_breakdown']['implementation_services'] ?? '' ),
                    'training_change_management' => sanitize_text_field( $json['financial_analysis']['investment_breakdown']['training_change_management'] ?? '' ),
                ],
                'payback_analysis'    => [
                    'payback_months' => floatval( $json['financial_analysis']['payback_analysis']['payback_months'] ?? 0 ),
                    'roi_3_year'     => floatval( $json['financial_analysis']['payback_analysis']['roi_3_year'] ?? 0 ),
                    'npv_analysis'   => sanitize_text_field( $json['financial_analysis']['payback_analysis']['npv_analysis'] ?? '' ),
                ],
            ],
            'risk_mitigation'        => [
                'implementation_risks' => array_map( 'sanitize_text_field', $json['risk_mitigation']['implementation_risks'] ?? [] ),
                'mitigation_strategies' => [
                    'risk_1_mitigation' => sanitize_text_field( $json['risk_mitigation']['mitigation_strategies']['risk_1_mitigation'] ?? '' ),
                    'risk_2_mitigation' => sanitize_text_field( $json['risk_mitigation']['mitigation_strategies']['risk_2_mitigation'] ?? '' ),
                ],
            ],
            'next_steps'             => array_map( 'sanitize_text_field', $json['next_steps'] ?? [] ),
        ];
    }

    /**
     * Conduct company-specific research
     */
    private function conduct_company_research( $user_inputs ) {
        $company_name = $user_inputs['company_name'] ?? '';
        $industry = $user_inputs['industry'] ?? '';
        $company_size = $user_inputs['company_size'] ?? '';
        
        // Simulate company research (in real implementation, this could query APIs, databases, etc.)
        $research = [
            'company_profile' => $this->build_company_profile( $company_name, $industry, $company_size ),
            'industry_positioning' => $this->analyze_market_position( $industry, $company_size ),
            'treasury_maturity' => $this->assess_treasury_maturity( $user_inputs ),
            'competitive_landscape' => $this->analyze_competitive_context( $industry ),
            'growth_trajectory' => $this->project_growth_path( $company_size, $industry ),
        ];
        
        return $research;
    }

    /**
     * Build detailed company profile
     */
    private function build_company_profile( $company_name, $industry, $company_size ) {
        $size_profiles = [
            '<$50M' => [
                'stage' => 'emerging growth',
                'characteristics' => 'agile decision-making, resource constraints, high growth potential',
                'treasury_focus' => 'cash flow optimization, banking relationship efficiency',
                'typical_challenges' => 'manual processes, limited treasury expertise, cash flow volatility'
            ],
            '$50M-$500M' => [
                'stage' => 'scaling business',
                'characteristics' => 'expanding operations, increasing complexity, professionalization',
                'treasury_focus' => 'process automation, risk management, strategic cash management',
                'typical_challenges' => 'growing complexity, system integration, resource allocation'
            ],
            '$500M-$2B' => [
                'stage' => 'established enterprise',
                'characteristics' => 'mature operations, multiple business units, geographic diversity',
                'treasury_focus' => 'enterprise integration, advanced analytics, risk optimization',
                'typical_challenges' => 'legacy systems, coordination complexity, regulatory compliance'
            ],
            '>$2B' => [
                'stage' => 'large enterprise',
                'characteristics' => 'global operations, sophisticated structure, regulatory oversight',
                'treasury_focus' => 'enterprise-wide optimization, regulatory compliance, strategic finance',
                'typical_challenges' => 'system complexity, governance requirements, scale management'
            ]
        ];
        
        $profile = $size_profiles[$company_size] ?? $size_profiles['$50M-$500M'];
        
        return [
            'company_name' => $company_name,
            'revenue_segment' => $company_size,
            'business_stage' => $profile['stage'],
            'key_characteristics' => $profile['characteristics'],
            'treasury_priorities' => $profile['treasury_focus'],
            'common_challenges' => $profile['typical_challenges'],
            'industry_context' => $this->get_industry_context( $industry ),
        ];
    }

    /**
     * Get industry-specific context
     */
    private function get_industry_context( $industry ) {
        $contexts = [
            'manufacturing' => [
                'cash_flow_pattern' => 'cyclical with seasonal variations',
                'working_capital_intensity' => 'high inventory and receivables',
                'regulatory_environment' => 'environmental and safety regulations',
                'treasury_priorities' => 'supply chain financing, FX risk management'
            ],
            'technology' => [
                'cash_flow_pattern' => 'rapid growth with high volatility',
                'working_capital_intensity' => 'low physical assets, high cash burn',
                'regulatory_environment' => 'data privacy and cybersecurity',
                'treasury_priorities' => 'liquidity management, investment optimization'
            ],
            'retail' => [
                'cash_flow_pattern' => 'highly seasonal and promotional',
                'working_capital_intensity' => 'inventory-heavy with payment timing',
                'regulatory_environment' => 'consumer protection and payment regulations',
                'treasury_priorities' => 'cash forecasting, payment processing optimization'
            ],
            // Add more industries as needed
        ];
        
        return $contexts[$industry] ?? [
            'cash_flow_pattern' => 'varies by business model',
            'working_capital_intensity' => 'moderate',
            'regulatory_environment' => 'standard compliance requirements',
            'treasury_priorities' => 'operational efficiency and risk management'
        ];
    }

    /**
     * Analyze industry context using the LLM.
     *
     * @param array $user_inputs Sanitized user inputs.
     * @return array|WP_Error Industry analysis or error object.
     */
    private function analyze_industry_context( $user_inputs ) {
        if ( empty( $this->api_key ) ) {
            return new WP_Error( 'no_api_key', __( 'OpenAI API key not configured.', 'rtbcb' ) );
        }

        $industry = sanitize_text_field( $user_inputs['industry'] ?? '' );
        if ( empty( $industry ) ) {
            return [
                'sector_trends'          => '',
                'competitive_benchmarks' => '',
                'regulatory_considerations' => '',
            ];
        }

        $model  = $this->get_model( 'mini' );
        $prompt = 'Provide sector_trends, competitive_benchmarks, and regulatory_considerations for the ' . $industry . ' industry in JSON.';

        $history  = [
            [
                'role'    => 'user',
                'content' => $prompt,
            ],
        ];
        $context  = $this->build_context_for_responses( $history );
        $response = $this->call_openai_with_retry( $model, $context );
        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $parsed_response = rtbcb_parse_gpt5_response( $response );
        $json            = json_decode( $parsed_response['output_text'], true );

        if ( ! is_array( $json ) ) {
            return new WP_Error( 'llm_parse_error', __( 'Invalid response from language model.', 'rtbcb' ) );
        }

        return [
            'sector_trends'          => sanitize_text_field( $json['sector_trends'] ?? '' ),
            'competitive_benchmarks' => sanitize_text_field( $json['competitive_benchmarks'] ?? '' ),
            'regulatory_considerations' => sanitize_text_field( $json['regulatory_considerations'] ?? '' ),
        ];
    }

    /**
     * Research treasury technology solutions using the LLM.
     *
     * @param array $user_inputs    Sanitized user inputs.
     * @param array $context_chunks Optional context strings.
     * @return string|WP_Error Research summary or error object.
     */
    private function research_treasury_solutions( $user_inputs, $context_chunks ) {
        if ( empty( $this->api_key ) ) {
            return new WP_Error( 'no_api_key', __( 'OpenAI API key not configured.', 'rtbcb' ) );
        }

        $industry     = sanitize_text_field( $user_inputs['industry'] ?? '' );
        $company_size = sanitize_text_field( $user_inputs['company_size'] ?? '' );

        $model  = $this->get_model( 'mini' );
        $prompt = 'Briefly summarize treasury technology solutions relevant to a ' . $company_size . ' company in the ' . $industry . ' industry.';

        if ( ! empty( $context_chunks ) ) {
            $prompt .= '\nContext: ' . implode( '\n', array_map( 'sanitize_text_field', $context_chunks ) );
        }

        $history  = [
            [
                'role'    => 'user',
                'content' => $prompt,
            ],
        ];
        $context  = $this->build_context_for_responses( $history );
        $response = $this->call_openai_with_retry( $model, $context );
        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $parsed  = rtbcb_parse_gpt5_response( $response );
        $summary = sanitize_textarea_field( $parsed['output_text'] );

        if ( empty( $summary ) ) {
            return new WP_Error( 'llm_empty_response', __( 'No technology research returned.', 'rtbcb' ) );
        }

        return $summary;
    }

    /**
     * Select the optimal model for comprehensive analysis.
     *
     * @param array $user_inputs    Sanitized user inputs.
     * @param array $context_chunks Optional context strings.
     * @return string Model identifier.
     */
    private function select_optimal_model( $user_inputs, $context_chunks ) {
        $model = $this->get_model( 'advanced' );

        if ( count( $context_chunks ) < 3 ) {
            $model = $this->get_model( 'premium' );
        }

        return sanitize_text_field( $model );
    }

    /**
     * Build comprehensive prompt with research context
     */
    private function build_comprehensive_prompt( $user_inputs, $roi_data, $company_research, $industry_analysis, $tech_landscape ) {
        $company_name    = $user_inputs['company_name'] ?? 'the company';
        $company_profile = $company_research['company_profile'];

        $prompt  = "As a senior treasury technology consultant with 15+ years of experience, create a comprehensive business case for {$company_name}.\n\n";

        // Add detailed context sections...
        $prompt .= "EXECUTIVE BRIEF:\n";
        $prompt .= "Create a strategic business case that justifies treasury technology investment with:\n";
        $prompt .= "- Clear ROI projections with risk-adjusted scenarios\n";
        $prompt .= "- Industry-specific operational improvements\n";
        $prompt .= "- Implementation roadmap with success metrics\n";
        $prompt .= "- Risk mitigation strategies\n\n";

        // Company Context
        $prompt .= "COMPANY PROFILE:\n";
        $prompt .= "Company: {$company_name}\n";
        $prompt .= "Industry: " . ($user_inputs['industry'] ?? 'Not specified') . "\n";
        $prompt .= "Revenue Size: " . ($user_inputs['company_size'] ?? 'Not specified') . "\n";
        $prompt .= "Business Stage: {$company_profile['business_stage']}\n";
        $prompt .= "Key Characteristics: {$company_profile['key_characteristics']}\n";
        $prompt .= "Treasury Priorities: {$company_profile['treasury_priorities']}\n";
        $prompt .= "Common Challenges: {$company_profile['common_challenges']}\n\n";
        
        // Current State Analysis
        $prompt .= "CURRENT TREASURY OPERATIONS:\n";
        $prompt .= "Weekly Reconciliation Hours: " . ($user_inputs['hours_reconciliation'] ?? 0) . "\n";
        $prompt .= "Weekly Cash Positioning Hours: " . ($user_inputs['hours_cash_positioning'] ?? 0) . "\n";
        $prompt .= "Banking Relationships: " . ($user_inputs['num_banks'] ?? 0) . "\n";
        $prompt .= "Treasury Team Size: " . ($user_inputs['ftes'] ?? 0) . " FTEs\n";
        $prompt .= "Key Pain Points: " . implode(', ', $user_inputs['pain_points'] ?? []) . "\n\n";

        // Industry Context
        if ( ! empty( $industry_analysis ) ) {
            $prompt .= "INDUSTRY CONTEXT:\n";
            $prompt .= 'Sector Trends: ' . ( $industry_analysis['sector_trends'] ?? '' ) . "\n";
            $prompt .= 'Competitive Benchmarks: ' . ( $industry_analysis['competitive_benchmarks'] ?? '' ) . "\n";
            $prompt .= 'Regulatory Considerations: ' . ( $industry_analysis['regulatory_considerations'] ?? '' ) . "\n\n";
        }

        // Treasury technology landscape
        if ( ! empty( $tech_landscape ) ) {
            $prompt .= "TECHNOLOGY LANDSCAPE:\n{$tech_landscape}\n\n";
        }

        // ROI Analysis
        $prompt .= "PROJECTED ROI ANALYSIS:\n";
        $prompt .= "Conservative Scenario: $" . number_format($roi_data['conservative']['total_annual_benefit'] ?? 0) . "\n";
        $prompt .= "Base Case Scenario: $" . number_format($roi_data['base']['total_annual_benefit'] ?? 0) . "\n";
        $prompt .= "Optimistic Scenario: $" . number_format($roi_data['optimistic']['total_annual_benefit'] ?? 0) . "\n\n";
        
        // Strategic Context
        if ( ! empty( $user_inputs['business_objective'] ) ) {
            $prompt .= "Primary Business Objective: " . $user_inputs['business_objective'] . "\n";
        }
        if ( ! empty( $user_inputs['implementation_timeline'] ) ) {
            $prompt .= "Implementation Timeline: " . $user_inputs['implementation_timeline'] . "\n";
        }
        if ( ! empty( $user_inputs['budget_range'] ) ) {
            $prompt .= "Budget Range: " . $user_inputs['budget_range'] . "\n";
        }

        $prompt .= "\nDELIVER A PROFESSIONAL BUSINESS CASE:\n";
        $prompt .= "Respond with a comprehensive JSON structure containing all required sections.\n";
        $prompt .= "Ensure each section provides actionable insights specific to {$company_name}'s situation.\n";
        $prompt .= "Use professional consulting language appropriate for C-level executives.\n\n";

        $prompt .= "REQUIRED JSON STRUCTURE (respond with ONLY this JSON, no other text):\n";
        $prompt .= json_encode([
            'executive_summary' => [
                'strategic_positioning' => "2-3 sentences about {$company_name}'s strategic position and readiness for treasury technology",
                'business_case_strength' => 'Strong|Moderate|Compelling',
                'key_value_drivers' => [
                    "Primary value driver specific to {$company_name}",
                    "Secondary value driver for their industry/size",
                    "Third strategic benefit for their situation"
                ],
                'executive_recommendation' => "Clear recommendation with specific next steps for {$company_name}",
                'confidence_level' => 'decimal between 0.7-0.95'
            ],
            'operational_analysis' => [
                'current_state_assessment' => [
                    'efficiency_rating' => 'Excellent|Good|Fair|Poor',
                    'benchmark_comparison' => "How {$company_name} compares to industry peers",
                    'capacity_utilization' => "Analysis of current team capacity and bottlenecks"
                ],
                'process_inefficiencies' => [
                    [
                        'process' => 'specific process name',
                        'impact' => 'High|Medium|Low',
                        'description' => 'detailed description of inefficiency'
                    ]
                ],
                'automation_opportunities' => [
                    [
                        'area' => 'process area',
                        'complexity' => 'High|Medium|Low',
                        'potential_hours_saved' => 'number'
                    ]
                ]
            ],
            'industry_insights' => [
                'sector_trends' => "Key trends affecting {$company_name}'s industry",
                'competitive_benchmarks' => "How competitors are leveraging treasury technology",
                'regulatory_considerations' => "Relevant compliance and regulatory factors"
            ],
            'technology_recommendations' => [
                'primary_solution' => [
                    'category' => 'recommended category',
                    'rationale' => "Why this fits {$company_name} specifically",
                    'key_features' => ['feature1', 'feature2', 'feature3']
                ],
                'implementation_approach' => [
                    'phase_1' => 'initial implementation focus',
                    'phase_2' => 'expansion phase',
                    'success_metrics' => ['metric1', 'metric2', 'metric3']
                ]
            ],
            'financial_analysis' => [
                'investment_breakdown' => [
                    'software_licensing' => 'estimated cost range',
                    'implementation_services' => 'estimated cost range',
                    'training_change_management' => 'estimated cost range'
                ],
                'payback_analysis' => [
                    'payback_months' => 'number',
                    'roi_3_year' => 'percentage',
                    'npv_analysis' => 'positive value justification'
                ]
            ],
            'risk_mitigation' => [
                'implementation_risks' => [
                    "Risk specific to {$company_name}'s situation",
                    "Industry-specific risk consideration",
                    "Technology adoption risk"
                ],
                'mitigation_strategies' => [
                    'risk_1_mitigation' => 'specific mitigation approach',
                    'risk_2_mitigation' => 'specific mitigation approach'
                ]
            ],
            'next_steps' => [
                "Immediate action for {$company_name} leadership",
                "Vendor evaluation and selection process",
                "Implementation planning and timeline",
                "Change management and training program"
            ]
        ], JSON_PRETTY_PRINT);
        
        return $prompt;
    }

    /**
     * Enhance parsed analysis with research context.
     *
     * @param array $analysis          Parsed analysis from LLM.
     * @param array $company_research  Company research data.
     * @param array $industry_analysis Industry analysis data.
     * @return array Enhanced analysis.
     */
    private function enhance_with_research( $analysis, $company_research, $industry_analysis ) {
        $analysis['research'] = [
            'company'  => $company_research,
            'industry' => $industry_analysis,
        ];

        return $analysis;
    }

    /**
     * Call OpenAI with retry logic.
     */

    private function call_openai_with_retry( $model, $prompt, $max_retries = null ) {
        $max_retries = $max_retries ?? intval( $this->gpt5_config['max_retries'] );

        for ( $attempt = 1; $attempt <= $max_retries; $attempt++ ) {
            $response = $this->call_openai( $model, $prompt );

            if ( ! is_wp_error( $response ) ) {
                return $response;
            }

            error_log( "RTBCB: OpenAI attempt {$attempt} failed: " . $response->get_error_message() );

            if ( $attempt < $max_retries ) {
                sleep( $attempt ); // Progressive backoff
            }
        }

        return $response; // Return last error
    }

    /**
     * Build instructions and input for the Responses API.
     *
     * @param array       $history       Array of conversation items.
     * @param string|null $system_prompt Optional system prompt.
     *
     * @return array {
     *     @type string $instructions System prompt for the model.
     *     @type string $input        User prompt for the model.
     * }
     */
    private function build_context_for_responses( $history, $system_prompt = null ) {
        $default_system = 'You are a senior treasury technology consultant. Provide detailed, research-driven analysis in the exact JSON format requested. Do not include any text outside the JSON structure.';

        $system_prompt = $system_prompt ? $system_prompt : $default_system;

        $input_parts = [];

        foreach ( (array) $history as $item ) {
            if ( ! is_array( $item ) || 'user' !== ( $item['role'] ?? '' ) || ! isset( $item['content'] ) ) {
                continue;
            }

            $input_parts[] = function_exists( 'sanitize_textarea_field' ) ? sanitize_textarea_field( $item['content'] ) : $item['content'];
        }

        $instructions = function_exists( 'sanitize_textarea_field' ) ? sanitize_textarea_field( $system_prompt ) : $system_prompt;

        return [
            'instructions' => $instructions,
            'input'        => implode( "\n", $input_parts ),
        ];
    }

    /**
     * Call the OpenAI Responses API.
     *
     * @param string       $model             Model name.
     * @param array|string $prompt            Prompt array or string.
     * @param int|null     $max_output_tokens Maximum output tokens.
     * @return array|WP_Error HTTP response array or WP_Error on failure.
     */
    /**
     * Call the OpenAI API with optimized settings for company overview.
     *
     * @param string       $model             Model name.
     * @param array|string $prompt            Prompt array or string.
     * @param int|null     $max_output_tokens Maximum output tokens.
     * @return array|WP_Error HTTP response array or WP_Error on failure.
     */
    private function call_openai( $model, $prompt, $max_output_tokens = null ) {
        if ( empty( $this->api_key ) ) {
            return new WP_Error( 'no_api_key', __( 'OpenAI API key not configured.', 'rtbcb' ) );
        }

        $endpoint          = 'https://api.openai.com/v1/responses';
        $model_name        = sanitize_text_field( $model ?: 'gpt-5-mini' );
        $model_name        = rtbcb_normalize_model_name( $model_name );
        $max_output_tokens = max( 256, intval( $max_output_tokens ?? 4000 ) ); // Reduced for faster response

        // Build request body
        if ( is_array( $prompt ) && isset( $prompt['input'] ) ) {
            $instructions = sanitize_textarea_field( $prompt['instructions'] ?? '' );
            $input        = sanitize_textarea_field( $prompt['input'] );
        } else {
            $instructions = '';
            $input        = sanitize_textarea_field( (string) $prompt );
        }

        if ( '' === trim( $input ) ) {
            return new WP_Error( 'empty_prompt', __( 'Prompt cannot be empty.', 'rtbcb' ) );
        }

        $body = [
            'model'            => $model_name,
            'input'            => $input,
            'max_output_tokens'=> $max_output_tokens,
            'temperature'      => 0.3, // Lower temperature for more focused responses
        ];

        if ( ! empty( $instructions ) ) {
            $body['instructions'] = $instructions;
        }

        // Optimized settings for company overview
        if ( strpos( $model_name, 'gpt-5' ) === 0 ) {
            $body['reasoning'] = [
                'effort' => 'medium', // Reduced from high for faster response
            ];
            $body['text'] = [
                'verbosity' => 'medium',
            ];
        }

        $args = [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->api_key,
                'Content-Type'  => 'application/json',
            ],
            'body'    => wp_json_encode( $body ),
            'timeout' => 120, // Reduced from 300 to 2 minutes
        ];

        $response = wp_remote_post( $endpoint, $args );

        // Log timing information
        if ( ! is_wp_error( $response ) ) {
            $response_code = wp_remote_retrieve_response_code( $response );
            error_log( "RTBCB: OpenAI API response code: {$response_code}" );
        }

        return $response;
    }

    /**
     * Determine appropriate reasoning effort based on task complexity.
     *
     * @param array|string $prompt Prompt data or text.
     * @return string Reasoning effort level.
     */
    private function get_reasoning_effort_for_task( $prompt ) {
        $prompt_text = is_array( $prompt )
            ? ( $prompt['input'] ?? '' ) . ' ' . ( $prompt['instructions'] ?? '' )
            : (string) $prompt;

        $prompt_lower = strtolower( $prompt_text );

        // High effort for complex business analysis
        if ( strpos( $prompt_lower, 'comprehensive' ) !== false ||
            strpos( $prompt_lower, 'business case' ) !== false ||
            strpos( $prompt_lower, 'analysis' ) !== false ||
            strpos( $prompt_lower, 'financial' ) !== false ) {
            return 'high';
        }

        // Medium effort for standard tasks
        if ( strpos( $prompt_lower, 'generate' ) !== false ||
            strpos( $prompt_lower, 'create' ) !== false ) {
            return 'medium';
        }

        // Low effort for simple requests
        return 'low';
    }

    /**
     * Determine appropriate verbosity based on output requirements.
     *
     * @param array|string $prompt Prompt data or text.
     * @return string Verbosity level.
     */
    private function get_verbosity_for_task( $prompt ) {
        $prompt_text = is_array( $prompt )
            ? ( $prompt['input'] ?? '' ) . ' ' . ( $prompt['instructions'] ?? '' )
            : (string) $prompt;

        $prompt_lower = strtolower( $prompt_text );

        // High verbosity for detailed business cases
        if ( strpos( $prompt_lower, 'comprehensive' ) !== false ||
            strpos( $prompt_lower, 'detailed' ) !== false ||
            strpos( $prompt_lower, 'analysis' ) !== false ) {
            return 'high';
        }

        // Medium verbosity for standard generation
        return 'medium';
    }

    /**
     * Log details about a GPT-5 API call.
     *
     * @param array|string $context  Context or instructions sent to the model.
     * @param array        $response Decoded response array or HTTP response array.
     * @param string|null  $error    Optional error message.
     */
    private function log_gpt5_call( $context, $response, $error = null ) {
        $context_serialized = is_array( $context ) ? wp_json_encode( $context ) : (string) $context;
        $context_size       = strlen( $context_serialized );

        $response_for_parser = ( is_array( $response ) && isset( $response['body'] ) )
            ? $response
            : [ 'body' => wp_json_encode( is_array( $response ) ? $response : [] ) ];

        $parsed = rtbcb_parse_gpt5_response( $response_for_parser );
        $content = $parsed['output_text'];
        $usage   = $parsed['raw']['usage'] ?? [];

        $completion_tokens = intval( $usage['completion_tokens'] ?? 0 );
        $reasoning_tokens  = intval( $usage['reasoning_tokens'] ?? 0 );
        $total_tokens      = intval( $usage['total_tokens'] ?? 0 );
        $response_length   = strlen( $content );

        $log_message = sprintf(
            'RTBCB GPT5 Call: context_size=%d, completion_tokens=%d, reasoning_tokens=%d, total_tokens=%d, response_length=%d',
            $context_size,
            $completion_tokens,
            $reasoning_tokens,
            $total_tokens,
            $response_length
        );

        if ( ! empty( $error ) ) {
            $log_message .= ', error=' . $error;
        }

        error_log( $log_message );
    }

    /**
     * Extract content and decoded data from an OpenAI response.
     *
     * @param array $response HTTP response array.
     * @return array {string, array} Content string and decoded array.
     */
    private function extract_openai_output( $response ) {
        $body    = wp_remote_retrieve_body( $response );
        $decoded = json_decode( $body, true );

        if ( ! is_array( $decoded ) ) {
            return [ '', [] ];
        }

        $content = '';

        if ( isset( $decoded['output'] ) && is_array( $decoded['output'] ) ) {
            foreach ( $decoded['output'] as $chunk ) {
                if ( ! is_array( $chunk ) ) {
                    continue;
                }

                $text = '';
                if ( isset( $chunk['content'] ) && is_array( $chunk['content'] ) ) {
                    foreach ( $chunk['content'] as $piece ) {
                        if ( isset( $piece['text'] ) && '' !== $piece['text'] ) {
                            $text = $piece['text'];
                            break;
                        }
                    }
                }

                if ( 'message' === ( $chunk['type'] ?? '' ) || '' !== $text ) {
                    $content = $text;
                    break;
                }
            }
        }

        if ( '' === $content && isset( $decoded['output_text'] ) ) {
            $content = is_array( $decoded['output_text'] ) ? implode( ' ', (array) $decoded['output_text'] ) : $decoded['output_text'];
        }

        return [ $content, $decoded ];
    }

    // Helper methods for enhanced analysis
    private function calculate_efficiency_rating( $user_inputs ) {
        $total_hours = ($user_inputs['hours_reconciliation'] ?? 0) + ($user_inputs['hours_cash_positioning'] ?? 0);
        $team_size = $user_inputs['ftes'] ?? 1;
        $hours_per_fte = $team_size > 0 ? $total_hours / $team_size : $total_hours;
        
        if ( $hours_per_fte < 5 ) return 'Good';
        if ( $hours_per_fte < 15 ) return 'Fair'; 
        return 'Poor';
    }

    private function get_automation_level( $user_inputs ) {
        $pain_points = $user_inputs['pain_points'] ?? [];
        if ( in_array( 'manual_processes', $pain_points ) ) return 'low';
        if ( in_array( 'integration_issues', $pain_points ) ) return 'moderate';
        return 'moderate';
    }

    private function analyze_process_inefficiencies( $user_inputs ) {
        $inefficiencies = [];
        $pain_points = $user_inputs['pain_points'] ?? [];
        
        foreach ( $pain_points as $pain_point ) {
            switch ( $pain_point ) {
                case 'manual_processes':
                    $inefficiencies[] = [
                        'process' => 'Bank Reconciliation',
                        'impact' => 'High',
                        'description' => 'Manual data entry and reconciliation processes consume significant time and introduce error risk'
                    ];
                    break;
                case 'poor_visibility':
                    $inefficiencies[] = [
                        'process' => 'Cash Position Reporting',
                        'impact' => 'High', 
                        'description' => 'Lack of real-time visibility delays decision-making and impacts working capital optimization'
                    ];
                    break;
                case 'forecast_accuracy':
                    $inefficiencies[] = [
                        'process' => 'Cash Forecasting',
                        'impact' => 'Medium',
                        'description' => 'Inaccurate forecasting leads to suboptimal cash positioning and increased financing costs'
                    ];
                    break;
            }
        }
        
        return $inefficiencies;
    }

    private function identify_automation_opportunities( $user_inputs ) {
        $opportunities = [];
        
        if ( ($user_inputs['hours_reconciliation'] ?? 0) > 0 ) {
            $opportunities[] = [
                'area' => 'Bank Reconciliation',
                'complexity' => 'Medium',
                'potential_hours_saved' => round( ($user_inputs['hours_reconciliation'] ?? 0) * 0.7, 1 )
            ];
        }
        
        if ( ($user_inputs['hours_cash_positioning'] ?? 0) > 0 ) {
            $opportunities[] = [
                'area' => 'Cash Position Management',
                'complexity' => 'Low',
                'potential_hours_saved' => round( ($user_inputs['hours_cash_positioning'] ?? 0) * 0.5, 1 )
            ];
        }
        
        return $opportunities;
    }

    private function get_industry_trends( $industry ) {
        $trends = [
            'manufacturing' => 'Digital transformation initiatives are driving treasury automation adoption, with focus on supply chain finance integration and sustainability reporting',
            'technology' => 'Rapid growth companies are prioritizing real-time cash management and automated forecasting to support scaling operations',
            'retail' => 'Omnichannel payment processing and seasonal cash flow management are key drivers for treasury technology investment'
        ];
        
        return $trends[$industry] ?? 'Companies across industries are modernizing treasury operations to improve efficiency and risk management';
    }

    private function build_financial_analysis( $roi_data, $user_inputs ) {
        $base_benefit = $roi_data['base']['total_annual_benefit'] ?? 0;
        $estimated_cost = $base_benefit * 0.4; // Rough estimate
        
        return [
            'investment_breakdown' => [
                'software_licensing'       => '$' . number_format( $estimated_cost * 0.6 ) . ' - $' . number_format( $estimated_cost * 0.8 ),
                'implementation_services' => '$' . number_format( $estimated_cost * 0.15 ) . ' - $' . number_format( $estimated_cost * 0.25 ),
                'training_change_management' => '$' . number_format( $estimated_cost * 0.05 ) . ' - $' . number_format( $estimated_cost * 0.15 ),
            ],
            'payback_analysis'       => [
                'payback_months' => $base_benefit > 0 ? round( 12 * $estimated_cost / $base_benefit ) : 24,
                'roi_3_year'     => round( ( $base_benefit * 3 - $estimated_cost ) / $estimated_cost * 100 ),
                'npv_analysis'   => 'Positive NPV of $' . number_format( $base_benefit * 2.5 - $estimated_cost ) . ' over 3 years at 10% discount rate',
            ],
        ];
    }
}

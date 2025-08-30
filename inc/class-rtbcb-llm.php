<?php
defined( 'ABSPATH' ) || exit;

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

    /**
     * GPT-5 configuration settings.
     *
     * @var array
     */
    private $gpt5_config;

    /**
     * Last JSON-decoded request body sent to OpenAI.
     *
     * @var array|null
     */
    protected $last_request;

    /**
     * Last response or WP_Error returned from the OpenAI API.
     *
     * @var array|WP_Error|null
     */
    protected $last_response;

    /**
     * Serialized company research from the last request.
     *
     * @var string|null
     */
    protected $last_company_research;

    public function __construct() {
        $this->api_key = rtbcb_get_openai_api_key();

        $timeout           = rtbcb_get_api_timeout();
        $max_output_tokens = intval( get_option( 'rtbcb_gpt5_max_output_tokens', 20000 ) );
        $config            = rtbcb_get_gpt5_config(
            array_merge(
                get_option( 'rtbcb_gpt5_config', [] ),
                [
                    'timeout'           => $timeout,
                    'max_output_tokens' => $max_output_tokens,
                ]
            )
        );
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
     * Retrieve the last request body sent to the OpenAI API.
     *
     * @return array|null Last request body.
     */
    public function get_last_request() {
        return $this->last_request;
    }

    /**
     * Retrieve the last response returned from the OpenAI API.
     *
     * @return array|WP_Error|null Last response or WP_Error.
     */
    public function get_last_response() {
        return $this->last_response;
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
     * @param string $company_name Company name.
     * @return array|WP_Error Structured overview array or error object.
     */
    public function generate_company_overview( $company_name ) {
        $company_name = sanitize_text_field( $company_name );

        if ( empty( $this->api_key ) ) {
            return new WP_Error( 'no_api_key', __( 'OpenAI API key not configured.', 'rtbcb' ) );
        }

        $model = $this->get_model( 'mini' );

        // System prompt optimized for comprehensive company analysis
        $system_prompt = <<<'SYSTEM'
You are a senior treasury technology consultant.
Return only valid JSON matching this schema:
{
  "type": "object",
  "properties": {
    "analysis": {"type": "string", "minLength": 200},
    "recommendations": {"type": "array", "items": {"type": "string"}, "minItems": 1},
    "references": {"type": "array", "items": {"type": "string", "format": "uri"}},
    "metrics": {
      "type": "object",
      "properties": {
        "revenue": {"type": "number"},
        "staff_count": {"type": "number"},
        "baseline_efficiency": {"type": "number"}
      },
      "required": ["revenue", "staff_count", "baseline_efficiency"]
    }
  },
  "required": ["analysis", "recommendations", "references", "metrics"]
}
No explanatory text outside the JSON. If any field is unknown, use null.
SYSTEM;

        // Enhanced user prompt with comprehensive company analysis request
        $user_prompt = sprintf(
            <<<'USER'
Provide a comprehensive company overview and treasury technology analysis for %s.

# Required Analysis Coverage:
- Company background and business model overview
- Recent news, developments, and significant market activity
- Company size, scale, revenue range, and organizational structure
- Financial highlights and performance indicators (revenue, profitability, cash position)
- Treasury challenges and opportunities specific to their industry and size
- Market position and competitive landscape context
- Treasury technology maturity and digital transformation potential

# Context Enhancement:
If available from your knowledge, include relevant details about:
- Industry-specific treasury requirements and regulations
- Regulatory considerations affecting treasury operations
- Seasonal or cyclical business patterns impacting cash flow
- Recent financial performance trends and outlook
- Technology adoption patterns and digital maturity in their sector
- Geographic footprint and multi-currency exposure

# Analysis Depth:
- Provide specific, actionable insights rather than generic statements
- Include quantitative details where available (revenue figures, employee count, etc.)
- Address both current state and future trajectory
- Consider industry benchmarks and peer comparisons

# Recommendation Requirements:
- Treasury technology recommendations tailored to company-specific factors
- Consider implementation complexity relative to company size and resources
- Address the most pressing treasury challenges identified in the analysis
- Include both immediate quick-wins and strategic long-term initiatives

# Output Requirements:
- The "analysis" field must contain at least 200 characters.
- "recommendations" must be a non-empty array of strings.
- "references" must be an array of URLs.
- "metrics.revenue", "metrics.staff_count", and "metrics.baseline_efficiency" must be numeric.
- Do not include any text outside the JSON object.
- If any field is unknown, use null.

Respond with valid JSON only, following the specified schema exactly.
USER,
            $company_name
        );

        $history = [
            [
                'role'    => 'user',
                'content' => $user_prompt,
            ],
        ];

        $context  = $this->build_context_for_responses( $history, $system_prompt );
        $response = $this->call_openai_with_retry( $model, $context, 3 ); // Reduce retries

        if ( is_wp_error( $response ) ) {
            return new WP_Error( 'llm_failure', $response->get_error_message() );
        }

        $parsed  = rtbcb_parse_gpt5_response( $response );
        $content = $parsed['output_text'];

        if ( empty( $content ) ) {
            return new WP_Error( 'llm_empty_response', __( 'No overview returned.', 'rtbcb' ) );
        }

        // Parse JSON response
        $json = json_decode( $content, true );

        if ( ! is_array( $json ) ) {
            return new WP_Error( 'llm_parse_error', __( 'Invalid JSON response from language model.', 'rtbcb' ) );
        }

        // Validate required fields
        $required_fields = [ 'analysis', 'recommendations', 'references', 'metrics' ];
        $missing_fields  = array_diff( $required_fields, array_keys( $json ) );

        if ( ! empty( $missing_fields ) ) {
            return new WP_Error(
                'llm_missing_fields',
                __( 'Missing required fields in response: ', 'rtbcb' ) . implode( ', ', $missing_fields )
            );
        }

        // Additional validation for content quality
        $analysis = trim( $json['analysis'] ?? '' );
        if ( strlen( $analysis ) < 200 ) {
            return new WP_Error( 'llm_insufficient_analysis', __( 'Analysis content is too brief.', 'rtbcb' ) );
        }

        if ( empty( $json['recommendations'] ) || ! is_array( $json['recommendations'] ) ) {
            return new WP_Error( 'llm_missing_recommendations', __( 'No recommendations provided.', 'rtbcb' ) );
        }

        if ( empty( $json['metrics'] ) || ! is_array( $json['metrics'] ) ) {
            return new WP_Error( 'llm_missing_metrics', __( 'No metrics provided.', 'rtbcb' ) );
        }

        // Sanitize and structure the response
        $metrics = [
            'revenue'            => floatval( $json['metrics']['revenue'] ?? 0 ),
            'staff_count'        => intval( $json['metrics']['staff_count'] ?? 0 ),
            'baseline_efficiency' => floatval( $json['metrics']['baseline_efficiency'] ?? 0 ),
        ];

        return [
            'company_name'   => $company_name,
            'analysis'       => sanitize_textarea_field( $json['analysis'] ),
            'recommendations' => array_map( 'sanitize_text_field', array_filter( (array) $json['recommendations'] ) ),
            'references'     => array_map( 'esc_url_raw', array_filter( (array) $json['references'] ) ),
            'metrics'        => $metrics,
            'generated_at'   => current_time( 'Y-m-d H:i:s' ),
            'analysis_type'  => 'comprehensive_company_overview',
        ];
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
        $prompt = 'Provide an overview of Real Treasury, a treasury consulting company that helps organizations optimize treasury operations, benchmark market practices, and evaluate technology options for a ' . ( $company_size ?: __( 'company', 'rtbcb' ) ) . ' in the ' . ( $industry ?: __( 'unspecified', 'rtbcb' ) ) . ' industry.';

        if ( ! empty( $challenges ) ) {
            $prompt .= ' Address these challenges: ' . implode( ', ', $challenges ) . '.';
        }

        if ( ! empty( $categories ) ) {
            $prompt .= ' Highlight how Real Treasury advises on vendor categories: ' . implode( ', ', $categories ) . '.';
        }

        if ( $include_portal ) {
            $prompt .= ' Include how clients access Real Treasury research and tools through a client portal.';
        }

        $prompt .= ' Provide sections for consulting services, vendor ecosystem advisory, Real Treasury differentiators, implementation approach, and support/community aspects.';

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

        $analysis = $this->enhance_with_research( $parsed, $company_research, $industry_analysis );

        return [
            'executive_summary'      => $analysis['executive_summary'] ?? [],
            'company_overview'       => $analysis['research']['company']['company_profile'] ?? [],
            'industry_analysis'      => $analysis['industry_insights'] ?? [],
            'treasury_maturity'      => $analysis['research']['company']['treasury_maturity'] ?? '',
            'financial_analysis'     => $analysis['financial_analysis'] ?? [],
            'implementation_roadmap' => $analysis['technology_recommendations'] ?? [],
            'risk_mitigation'        => $analysis['risk_mitigation'] ?? [],
            'next_steps'             => $analysis['next_steps'] ?? [],
            'raw'                    => $analysis,
        ];
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
            $json = json_decode( $content, true );
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
     * Conduct company-specific research.
     *
     * @param array $user_inputs User-provided company details.
     * @return array Structured research data.
     */
    private function conduct_company_research( $user_inputs ) {
        $company_name = sanitize_text_field( $user_inputs['company_name'] ?? '' );
        $industry     = sanitize_text_field( $user_inputs['industry'] ?? '' );
        $company_size = sanitize_text_field( $user_inputs['company_size'] ?? '' );

        // Simulate company research (in real implementation, this could query APIs, databases, etc.)
        $research = [
            'company_profile'       => $this->build_company_profile( $company_name, $industry, $company_size ),
            'industry_positioning'  => $this->analyze_market_position( $industry, $company_size ),
            'treasury_maturity'     => $this->assess_treasury_maturity( $user_inputs ),
            'competitive_landscape' => $this->analyze_competitive_context( $industry ),
            'growth_trajectory'     => $this->project_growth_path( $company_size, $industry ),
        ];

        $this->last_company_research = wp_json_encode( $research );

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
     * Analyze the competitive landscape for a given industry.
     *
     * Provides a list of competitors and brief notes on their strengths. If an
     * OpenAI API key is configured, the method will query the language model for
     * up-to-date insights; otherwise a basic set of placeholder competitors is
     * returned.
     *
     * @param string $industry Industry name or slug.
     * @return array {
     *     @type array $competitors List of competitors, each having `name` and
     *                              `strength` keys.
     * }
     */
    private function analyze_competitive_context( $industry ) {
        $industry = sanitize_text_field( $industry );

        $default = [
            'competitors' => [
                [
                    'name'     => 'General Competitor A',
                    'strength' => __( 'broad market reach', 'rtbcb' ),
                ],
                [
                    'name'     => 'General Competitor B',
                    'strength' => __( 'cost leadership', 'rtbcb' ),
                ],
            ],
        ];

        if ( empty( $this->api_key ) ) {
            return $default;
        }

        $model        = $this->get_model( 'mini' );
        $system_prompt = 'You are a market analyst. Return only JSON with an array "competitors" of objects each having "name" and "strength".';
        $user_prompt   = sprintf( 'Identify three major competitors in the %s industry and note one key strength for each.', $industry );

        $history = [
            [
                'role'    => 'system',
                'content' => $system_prompt,
            ],
            [
                'role'    => 'user',
                'content' => $user_prompt,
            ],
        ];

        $context  = $this->build_context_for_responses( $history );
        $response = $this->call_openai_with_retry( $model, $context );

        if ( is_wp_error( $response ) ) {
            return $default;
        }

        $parsed = rtbcb_parse_gpt5_response( $response );
        $json   = json_decode( $parsed['output_text'], true );

        if ( ! is_array( $json ) || empty( $json['competitors'] ) || ! is_array( $json['competitors'] ) ) {
            return $default;
        }

        $competitors = [];

        foreach ( $json['competitors'] as $comp ) {
            if ( ! is_array( $comp ) ) {
                continue;
            }

            $name     = sanitize_text_field( $comp['name'] ?? '' );
            $strength = sanitize_text_field( $comp['strength'] ?? '' );

            if ( ! empty( $name ) ) {
                $competitors[] = [
                    'name'     => $name,
                    'strength' => $strength,
                ];
            }
        }

        if ( empty( $competitors ) ) {
            return $default;
        }

        return [
            'competitors' => $competitors,
        ];
    }

    /**
     * Analyze a company's market position within its industry.
     *
     * @param string $industry     Industry name.
     * @param string $company_size Company size descriptor.
     * @return array Structured data including market share, peers, and growth outlook.
     */
    private function analyze_market_position( $industry, $company_size ) {
        $industry     = sanitize_text_field( $industry );
        $company_size = sanitize_text_field( $company_size );

        $market_shares = [
            '<$50M'      => 'emerging niche player',
            '$50M-$500M' => 'growing regional player',
            '$500M-$2B'  => 'established contender',
            '>$2B'       => 'market leader',
        ];

        $industry_peers = [
            'manufacturing' => [
                'peers'  => [ 'Acme Manufacturing', 'Global Fabricators' ],
                'growth' => 'stable',
            ],
            'technology'    => [
                'peers'  => [ 'Innovatech', 'NextGen Software' ],
                'growth' => 'rapid',
            ],
            'retail'        => [
                'peers'  => [ 'RetailCo', 'ShopSmart' ],
                'growth' => 'moderate',
            ],
        ];

        $selected = $industry_peers[ $industry ] ?? [
            'peers'  => [ 'General Competitor A', 'General Competitor B' ],
            'growth' => 'moderate',
        ];

        return [
            'market_share'   => sanitize_text_field( $market_shares[ $company_size ] ?? 'unknown' ),
            'peers'          => array_map( 'sanitize_text_field', $selected['peers'] ),
            'growth_outlook' => sanitize_text_field( $selected['growth'] ),
        ];
    }

    /**
     * Assess treasury maturity based on user inputs.
     *
     * @param array $user_inputs Sanitized user inputs.
     * @return array {
     *     @type string $level     Assessed maturity level.
     *     @type string $rationale Rationale for the assessment.
     * }
     */
    private function assess_treasury_maturity( $user_inputs ) {
        $company_data = [
            'ftes' => isset( $user_inputs['ftes'] ) ? floatval( $user_inputs['ftes'] ) : 0,
        ];

        if ( class_exists( 'RTBCB_Maturity_Model' ) ) {
            $model      = new RTBCB_Maturity_Model();
            $assessment = $model->assess( $company_data );

            return [
                'level'     => sanitize_text_field( $assessment['level'] ?? '' ),
                'rationale' => sanitize_text_field( $assessment['assessment'] ?? '' ),
            ];
        }

        $ftes  = $company_data['ftes'];
        $level = __( 'Basic', 'rtbcb' );

        if ( $ftes > 5 ) {
            $level = __( 'Advanced', 'rtbcb' );
        } elseif ( $ftes > 2 ) {
            $level = __( 'Intermediate', 'rtbcb' );
        }

        $rationale = sprintf(
            /* translators: %d: number of treasury FTEs */
            __( 'Assessment based on %d treasury FTEs.', 'rtbcb' ),
            $ftes
        );

        return [
            'level'     => sanitize_text_field( $level ),
            'rationale' => sanitize_text_field( $rationale ),
        ];
    }

    /**
     * Project growth trajectory based on company size and industry.
     *
     * @param string $company_size Company size descriptor.
     * @param string $industry     Industry name or slug.
     * @return array {
     *     @type string $size_tier        Tier label derived from company size.
     *     @type string $size_outlook     Growth outlook based on company size.
     *     @type string $industry_tier    Tier label derived from industry.
     *     @type string $industry_outlook Growth outlook based on industry.
     *     @type string $summary          Combined sanitized summary.
     * }
     */
    private function project_growth_path( $company_size, $industry ) {
        $company_size = sanitize_text_field( $company_size );
        $industry     = sanitize_key( $industry );

        $size_outlooks = [
            '<$50M'      => [ 'tier' => 'startup',    'description' => __( 'high growth potential', 'rtbcb' ) ],
            '$50M-$500M' => [ 'tier' => 'scaleup',    'description' => __( 'scaling trajectory', 'rtbcb' ) ],
            '$500M-$2B'  => [ 'tier' => 'growth',     'description' => __( 'steady expansion', 'rtbcb' ) ],
            '>$2B'       => [ 'tier' => 'enterprise', 'description' => __( 'mature growth', 'rtbcb' ) ],
        ];

        $industry_outlooks = [
            'technology'    => [ 'tier' => 'hyper-growth', 'description' => __( 'rapid expansion', 'rtbcb' ) ],
            'manufacturing' => [ 'tier' => 'stable',       'description' => __( 'stable outlook', 'rtbcb' ) ],
            'retail'        => [ 'tier' => 'competitive',  'description' => __( 'moderate growth', 'rtbcb' ) ],
            'finance'       => [ 'tier' => 'regulated',    'description' => __( 'regulated stability', 'rtbcb' ) ],
        ];

        $size_data     = $size_outlooks[ $company_size ] ?? [ 'tier' => 'baseline', 'description' => __( 'stable', 'rtbcb' ) ];
        $industry_data = $industry_outlooks[ $industry ] ?? [ 'tier' => 'neutral',  'description' => __( 'neutral', 'rtbcb' ) ];

        return [
            'size_tier'        => sanitize_text_field( $size_data['tier'] ),
            'size_outlook'     => sanitize_text_field( $size_data['description'] ),
            'industry_tier'    => sanitize_text_field( $industry_data['tier'] ),
            'industry_outlook' => sanitize_text_field( $industry_data['description'] ),
            'summary'          => sanitize_text_field( $size_data['description'] . '; ' . $industry_data['description'] ),
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
                'analysis'        => '',
                'recommendations' => [],
                'references'      => [],
                'errors'          => [],
            ];
        }

        $model = $this->get_model( 'mini' );

        $system_prompt = <<<'SYSTEM'
You are a senior treasury technology consultant tasked with delivering comprehensive, research-based analysis. Begin by reviewing the request and identifying the main aspects to address. Your responses must be formatted strictly as a single JSON object according to the specified schema, and no text should appear outside this JSON.

# Output Format
Return a single JSON object with these fields, in the exact order shown:
{
  "analysis": string,
  "recommendations": [string],
  "references": [string],
  "errors": [string]
}

## Formatting Rules
- Every field is required.
- If a field has no applicable content: use an empty string for "analysis" and empty arrays for the rest.
- Maintain the order of fields as listed.
- For multiple recommendations or references, use arrays.
- Ensure strict adherence to the given JSON schema.

Before generating your response, create a concise checklist (3-7 bullets) of what you will do to ensure your approach is methodical and covers all aspects of the request. After constructing the JSON, validate that your output strictly follows the schema and formatting requirements before returning it.
SYSTEM;

        $user_prompt = 'Provide sector_trends, competitive_benchmarks, and regulatory_considerations for the ' . $industry . ' industry in JSON.';

        $history = [
            [
                'role'    => 'user',
                'content' => $user_prompt,
            ],
        ];
        $context = $this->build_context_for_responses( $history, $system_prompt );
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
            'analysis'        => sanitize_text_field( $json['analysis'] ?? '' ),
            'recommendations' => array_map( 'sanitize_text_field', $json['recommendations'] ?? [] ),
            'references'      => array_map( 'sanitize_text_field', $json['references'] ?? [] ),
            'errors'          => array_map( 'sanitize_text_field', $json['errors'] ?? [] ),
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
            if ( ! empty( $industry_analysis['analysis'] ) ) {
                $prompt .= "INDUSTRY CONTEXT:\n" . $industry_analysis['analysis'] . "\n\n";
            }
            if ( ! empty( $industry_analysis['recommendations'] ) ) {
                $prompt .= "INDUSTRY RECOMMENDATIONS:\n- " . implode( "\n- ", $industry_analysis['recommendations'] ) . "\n\n";
            }
            if ( ! empty( $industry_analysis['references'] ) ) {
                $prompt .= "INDUSTRY REFERENCES:\n- " . implode( "\n- ", $industry_analysis['references'] ) . "\n\n";
            }
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
    private function call_openai( $model, $prompt, $max_output_tokens = null ) {
        if ( empty( $this->api_key ) ) {
            return new WP_Error( 'no_api_key', __( 'OpenAI API key not configured.', 'rtbcb' ) );
        }

        $endpoint         = 'https://api.openai.com/v1/responses'; // Correct endpoint.
        $model_name       = sanitize_text_field( $model ?: 'gpt-5-mini' );
        $model_name       = rtbcb_normalize_model_name( $model_name );
        $default_tokens    = intval( $this->gpt5_config['max_output_tokens'] ?? 20000 );
        $max_output_tokens = intval( $max_output_tokens ?? $default_tokens );
        $max_output_tokens = min( 50000, max( 256, $max_output_tokens ) );

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
            'model' => $model_name,
            'input' => $input,
            'max_output_tokens' => $max_output_tokens,
        ];

        if ( ! empty( $instructions ) ) {
            $body['instructions'] = $instructions;
        }

        if ( strpos( $model_name, 'gpt-5' ) === 0 ) {
            $body['reasoning'] = [
                'effort' => $this->get_reasoning_effort_for_task( $prompt ),
            ];
            $body['text'] = [
                'verbosity' => $this->get_verbosity_for_task( $prompt ),
            ];
        }

        if ( rtbcb_model_supports_temperature( $model_name ) ) {
            $body['temperature'] = floatval( $this->gpt5_config['temperature'] ?? 0.7 );
        }

        if ( isset( $this->gpt5_config['store'] ) ) {
            $body['store'] = (bool) $this->gpt5_config['store'];
        }

        $timeout = intval( $this->gpt5_config['timeout'] ?? 180 );

        $args = [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->api_key,
                'Content-Type' => 'application/json',
            ],
            'body'    => wp_json_encode( $body ),
            'timeout' => $timeout,
        ];

        $this->last_request = $body;
        $response           = wp_remote_post( $endpoint, $args );
        $this->last_response = $response;

        if ( class_exists( 'RTBCB_API_Log' ) ) {
            $decoded = [];
            if ( is_wp_error( $response ) ) {
                $decoded = [ 'error' => $response->get_error_message() ];
            } else {
                $decoded = json_decode( wp_remote_retrieve_body( $response ), true );
                if ( ! is_array( $decoded ) ) {
                    $decoded = [];
                }
            }

            RTBCB_API_Log::save_log( $body, $decoded, get_current_user_id() );
        }

        if ( is_wp_error( $response ) ) {
            if ( 'timeout' === $response->get_error_code() || false !== strpos( $response->get_error_message(), 'timed out' ) ) {
                return new WP_Error( 'llm_timeout', __( 'The language model request timed out. Please try again.', 'rtbcb' ) );
            }

            return new WP_Error(
                'llm_http_error',
                sprintf( __( 'Language model request failed: %s', 'rtbcb' ), $response->get_error_message() )
            );
        }

        $code = intval( wp_remote_retrieve_response_code( $response ) );
        if ( $code >= 400 ) {
            $body_response = wp_remote_retrieve_body( $response );
            $decoded       = json_decode( $body_response, true );

            if ( is_array( $decoded ) ) {
                if ( isset( $decoded['error']['message'] ) ) {
                    $message = $decoded['error']['message'];
                } elseif ( isset( $decoded['message'] ) ) {
                    $message = $decoded['message'];
                } else {
                    $message = wp_json_encode( $decoded );
                }
            } else {
                $message = $body_response;
            }

            $message = sanitize_text_field( $message );

            return new WP_Error( 'llm_http_status', $message, [ 'status' => $code ] );
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
                'software_licensing' => '$' . number_format( $estimated_cost * 0.6 ) . ' - $' . number_format( $estimated_cost * 0.8 ),
                'implementation_services' => '$' . number_format( $estimated_cost * 0.15 ) . ' - $' . number_format( $estimated_cost * 0.25 ),
                'training_change_management' => '$' . number_format( $estimated_cost * 0.05 ) . ' - $' . number_format( $estimated_cost * 0.15 )
            ],
            'payback_analysis' => [
                'payback_months' => $base_benefit > 0 ? round( 12 * $estimated_cost / $base_benefit ) : 24,
                'roi_3_year' => round( ( $base_benefit * 3 - $estimated_cost ) / $estimated_cost * 100 ),
                'npv_analysis' => 'Positive NPV of $' . number_format( $base_benefit * 2.5 - $estimated_cost ) . ' over 3 years at 10% discount rate'
            ]
        ];
    }
}

/**
 * Parse a GPT-5 response into output text, reasoning, and function calls.
 *
 * The parser first looks for a convenience `output_text` field. If a valid
 * string is not found, it manually walks the `output` chunks, prioritizing
 * message content before reasoning.
 *
 * @param array $response HTTP response array from wp_remote_post().
 * @return array {
 *     @type string $output_text    Combined output text from the response.
 *     @type array  $reasoning      Reasoning segments provided by the model.
 *     @type array  $function_calls Function call items returned by the model.
 *     @type array  $raw            Raw decoded response body.
 *     @type bool   $truncated      Whether the response hit the token limit.
 * }
 */
function rtbcb_parse_gpt5_response( $response ) {
    $body    = wp_remote_retrieve_body( $response );
    $decoded = json_decode( $body, true );

    if ( ! is_array( $decoded ) ) {
        return [
            'output_text'    => '',
            'reasoning'      => [],
            'function_calls' => [],
            'raw'            => [],
        ];
    }

    $output_text    = '';
    $reasoning      = [];
    $function_calls = [];
    $truncated      = false;

    // PRIORITY 1: Convenience field.
    if ( isset( $decoded['output_text'] ) && ! empty( trim( $decoded['output_text'] ) ) ) {
        $output_text = trim( $decoded['output_text'] );

        // Reject trivial responses.
        if ( strlen( $output_text ) < 20 ||
            false !== stripos( $output_text, 'pong' ) ||
            false !== stripos( $output_text, 'how can I help' ) ) {
            error_log( 'RTBCB: Detected trivial response: ' . $output_text );
            $output_text = '';
        }
    }

    // PRIORITY 2: Manual parsing when no good output text was found.
    if ( empty( $output_text ) && isset( $decoded['output'] ) && is_array( $decoded['output'] ) ) {

        // First pass: Look for message content only.
        foreach ( $decoded['output'] as $chunk ) {
            if ( ! is_array( $chunk ) || 'message' !== ( $chunk['type'] ?? '' ) ) {
                continue;
            }

            if ( isset( $chunk['content'] ) && is_array( $chunk['content'] ) ) {
                foreach ( $chunk['content'] as $content_piece ) {
                    if ( isset( $content_piece['text'] ) && ! empty( trim( $content_piece['text'] ) ) ) {
                        $candidate = trim( $content_piece['text'] );

                        if ( strlen( $candidate ) >= 20 &&
                            false === stripos( $candidate, 'pong' ) ) {
                            $output_text = $candidate;
                            break 2;
                        }
                    }
                }
            }
        }

        // Second pass: Collect reasoning and function calls.
        foreach ( $decoded['output'] as $chunk ) {
            $chunk_type = $chunk['type'] ?? '';

            if ( 'reasoning' === $chunk_type ) {
                if ( isset( $chunk['content'] ) && is_array( $chunk['content'] ) ) {
                    foreach ( $chunk['content'] as $piece ) {
                        if ( isset( $piece['text'] ) && ! empty( $piece['text'] ) ) {
                            $reasoning[] = $piece['text'];
                        }
                    }
                }
            }

            if ( 'function_call' === $chunk_type ) {
                $function_calls[] = $chunk;
            }
        }
    }

    // Log quality metrics.
    $text_length   = strlen( $output_text );
    $usage         = $decoded['usage'] ?? [];
    $output_tokens = $usage['output_tokens'] ?? 0;
    $config        = rtbcb_get_gpt5_config();
    if ( 'incomplete' === ( $decoded['status'] ?? '' ) ) {
        $truncated = true;
    }
    if ( ! $truncated && $output_tokens >= $config['max_output_tokens'] ) {
        $truncated = true;
    }
    if ( $truncated ) {
        error_log( 'RTBCB: OpenAI response truncated at ' . $output_tokens . ' tokens' );
    }

    error_log(
        sprintf(
            'RTBCB: Parsed response - text_length=%d, output_tokens=%d, reasoning_chunks=%d',
            $text_length,
            $output_tokens,
            count( $reasoning )
        )
    );

    if ( $output_tokens > 50 && $text_length < 100 ) {
        error_log( 'RTBCB: WARNING - High token count but short text output' );
    }

    return [
        'output_text'    => $output_text,
        'reasoning'      => $reasoning,
        'function_calls' => $function_calls,
        'raw'            => $decoded,
        'truncated'      => $truncated,
    ];
}


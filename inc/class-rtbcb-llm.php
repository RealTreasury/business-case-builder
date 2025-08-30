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
        $max_output_tokens = intval( get_option( 'rtbcb_gpt5_max_output_tokens', 8000 ) );
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
     * Estimate token usage from a desired word count.
     *
     * @param int $words Desired word count.
     * @return int Estimated token count capped by configuration.
     */
    private function estimate_tokens( $words ) {
        $words       = max( 0, intval( $words ) );
        $tokens      = (int) ceil( $words * 1.5 );
        $limit       = intval( $this->gpt5_config['max_output_tokens'] ?? 8000 );
        $min_tokens  = intval( $this->gpt5_config['min_output_tokens'] ?? 1 );
        $limit       = min( 128000, max( $min_tokens, $limit ) );

        return max( $min_tokens, min( $tokens, $limit ) );
    }

    /**
     * Determine token limit for a report type.
     *
     * @param string $type Report type identifier.
     * @return int Estimated token count for the report.
     */
    private function tokens_for_report( $type ) {
        $targets = [
            'business_case'             => 600,
            'industry_commentary'       => 60,
            'company_overview'          => 400,
            'industry_overview'         => 400,
            'treasury_tech_overview'    => 400,
            'real_treasury_overview'    => 400,
            'category_recommendation'   => 200,
            'benefits_estimate'         => 200,
            'comprehensive_business_case' => 2000,
            'competitive_context'       => 200,
            'industry_analysis'         => 400,
            'tech_research'             => 400,
        ];

        $words = $targets[ $type ] ?? 800;

        return $this->estimate_tokens( $words );
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
        $context = $this->build_context_for_responses( $history );
        $tokens  = $this->tokens_for_report( 'business_case' );
        $response = $this->call_openai_with_retry( $selected_model, $context, $tokens );

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
        $context = $this->build_context_for_responses( $history );
        $tokens  = $this->tokens_for_report( 'industry_commentary' );
        $response = $this->call_openai_with_retry( $model, $context, $tokens );

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

        $context = $this->build_context_for_responses( $history, $system_prompt );
        $tokens  = $this->tokens_for_report( 'company_overview' );
        $response = $this->call_openai_with_retry( $model, $context, $tokens, 3 ); // Reduce retries

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
        $context = $this->build_context_for_responses( $history );
        $tokens  = $this->tokens_for_report( 'industry_overview' );
        $response = $this->call_openai_with_retry( $model, $context, $tokens );

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
        $context = $this->build_context_for_responses( $history );
        $tokens  = $this->tokens_for_report( 'treasury_tech_overview' );
        $response = $this->call_openai_with_retry( $model, $context, $tokens );

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
        $context = $this->build_context_for_responses( $history );
        $tokens  = $this->tokens_for_report( 'real_treasury_overview' );
        $response = $this->call_openai_with_retry( $model, $context, $tokens );

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
        $context = $this->build_context_for_responses( $history );
        $tokens  = $this->tokens_for_report( 'category_recommendation' );
        $response = $this->call_openai_with_retry( $model, $context, $tokens );

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
        $context = $this->build_context_for_responses( $history );
        $tokens  = $this->tokens_for_report( 'benefits_estimate' );
        $response = $this->call_openai_with_retry( $model, $context, $tokens );

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

        $company_name = sanitize_text_field( $user_inputs['company_name'] ?? '' );
        $industry     = sanitize_text_field( $user_inputs['industry'] ?? '' );

        $company_research = rtbcb_get_research_cache( $company_name, $industry, 'company' );
        if ( false === $company_research ) {
            $company_research = $this->conduct_company_research( $user_inputs );
            if ( ! is_wp_error( $company_research ) ) {
                rtbcb_set_research_cache( $company_name, $industry, 'company', $company_research );
            }
        }

        if ( is_wp_error( $company_research ) ) {
            return $company_research;
        }

        $industry_analysis = rtbcb_get_research_cache( $company_name, $industry, 'industry' );
        if ( false === $industry_analysis ) {
            $industry_analysis = $this->analyze_industry_context( $user_inputs );
            if ( ! is_wp_error( $industry_analysis ) ) {
                rtbcb_set_research_cache( $company_name, $industry, 'industry', $industry_analysis );
            }
        }

        if ( is_wp_error( $industry_analysis ) ) {
            return $industry_analysis;
        }

        $tech_landscape = rtbcb_get_research_cache( $company_name, $industry, 'treasury' );
        if ( false === $tech_landscape ) {
            $tech_landscape = $this->research_treasury_solutions( $user_inputs, $context_chunks );
            if ( ! is_wp_error( $tech_landscape ) ) {
                rtbcb_set_research_cache( $company_name, $industry, 'treasury', $tech_landscape );
            }
        }

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
        $context = $this->build_context_for_responses( $history );
        $tokens  = $this->tokens_for_report( 'comprehensive_business_case' );
        $response = $this->call_openai_with_retry( $model, $context, $tokens );

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

        $analysis = $this->enhance_with_research( $parsed, $company_research, $industry_analysis, $tech_landscape );

        return [
            'executive_summary'      => $analysis['executive_summary'] ?? [],
            'company_overview'       => $analysis['research']['company']['company_profile'] ?? [],
            'industry_analysis'      => $analysis['industry_insights'] ?? [],
            'treasury_maturity'      => $analysis['research']['company']['treasury_maturity'] ?? '',
            'technology_landscape'   => $analysis['research']['technology'] ?? '',
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

        $context = $this->build_context_for_responses( $history );
        $tokens  = $this->tokens_for_report( 'competitive_context' );
        $response = $this->call_openai_with_retry( $model, $context, $tokens );

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
     * Execute company, industry, and technology research in a single LLM call.
     *
     * @param array $user_inputs    Sanitized user inputs.
     * @param array $context_chunks Optional context strings.
     * @return array|WP_Error {
     *     @type array  $company_research  Company research data.
     *     @type array  $industry_analysis Industry analysis data.
     *     @type string $tech_landscape    Technology landscape summary.
     * }
     */
    private function run_batched_research( $user_inputs, $context_chunks ) {
        if ( empty( $this->api_key ) ) {
            return new WP_Error( 'no_api_key', __( 'OpenAI API key not configured.', 'rtbcb' ) );
        }

        $company_name = sanitize_text_field( $user_inputs['company_name'] ?? '' );
        $industry     = sanitize_text_field( $user_inputs['industry'] ?? '' );
        $company_size = sanitize_text_field( $user_inputs['company_size'] ?? '' );

        $model = $this->get_model( 'mini' );

        $system_prompt = <<<'SYSTEM'
You are a senior treasury technology consultant. Return a single JSON object with the following structure and no additional text:
{
  "company_research": {
    "company_profile": {
      "business_stage": string,
      "key_characteristics": string,
      "treasury_priorities": string,
      "common_challenges": string
    },
    "treasury_maturity": {
      "level": string,
      "rationale": string
    }
  },
  "industry_analysis": {
    "analysis": string,
    "recommendations": [string],
    "references": [string],
    "errors": [string]
  },
  "technology_landscape": string
}
SYSTEM;

        $user_prompt = 'Company: ' . $company_name . "\n";
        $user_prompt .= 'Industry: ' . $industry . "\n";
        $user_prompt .= 'Size: ' . $company_size . "\n";
        if ( ! empty( $context_chunks ) ) {
            $user_prompt .= 'Context: ' . implode( '\n', array_map( 'sanitize_text_field', $context_chunks ) );
        }

        $history = [
            [
                'role'    => 'user',
                'content' => $user_prompt,
            ],
        ];
        $context  = $this->build_context_for_responses( $history, $system_prompt );
        $response = $this->call_openai_with_retry( $model, $context );
        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $parsed = rtbcb_parse_gpt5_response( $response );
        $json   = json_decode( $parsed['output_text'], true );

        if ( ! is_array( $json ) ) {
            return new WP_Error( 'llm_parse_error', __( 'Invalid response from language model.', 'rtbcb' ) );
        }

        $company_profile = [
            'business_stage'      => sanitize_text_field( $json['company_research']['company_profile']['business_stage'] ?? '' ),
            'key_characteristics' => sanitize_text_field( $json['company_research']['company_profile']['key_characteristics'] ?? '' ),
            'treasury_priorities' => sanitize_text_field( $json['company_research']['company_profile']['treasury_priorities'] ?? '' ),
            'common_challenges'   => sanitize_text_field( $json['company_research']['company_profile']['common_challenges'] ?? '' ),
        ];

        $treasury_maturity = [
            'level'     => sanitize_text_field( $json['company_research']['treasury_maturity']['level'] ?? '' ),
            'rationale' => sanitize_text_field( $json['company_research']['treasury_maturity']['rationale'] ?? '' ),
        ];

        $company_research = [
            'company_profile'  => $company_profile,
            'treasury_maturity'=> $treasury_maturity,
        ];

        $industry_analysis = [
            'analysis'        => sanitize_text_field( $json['industry_analysis']['analysis'] ?? '' ),
            'recommendations' => array_map( 'sanitize_text_field', $json['industry_analysis']['recommendations'] ?? [] ),
            'references'      => array_map( 'sanitize_text_field', $json['industry_analysis']['references'] ?? [] ),
            'errors'          => array_map( 'sanitize_text_field', $json['industry_analysis']['errors'] ?? [] ),
        ];

        $tech_landscape = sanitize_textarea_field( $json['technology_landscape'] ?? '' );

        $this->last_company_research = wp_json_encode( $company_research );

        return [
            'company_research' => $company_research,
            'industry_analysis' => $industry_analysis,
            'tech_landscape'    => $tech_landscape,
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
        $tokens  = $this->tokens_for_report( 'industry_analysis' );
        $response = $this->call_openai_with_retry( $model, $context, $tokens );
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
        $context = $this->build_context_for_responses( $history );
        $tokens  = $this->tokens_for_report( 'tech_research' );
        $response = $this->call_openai_with_retry( $model, $context, $tokens );
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
     * @param array  $company_research  Company research data.
     * @param array  $industry_analysis Industry analysis data.
     * @param string $tech_landscape    Technology landscape summary.
     * @return array Enhanced analysis.
     */
    private function enhance_with_research( $analysis, $company_research, $industry_analysis, $tech_landscape ) {
        $analysis['research'] = [
            'company'   => $company_research,
            'industry'  => $industry_analysis,
            'technology' => $tech_landscape,
        ];

return $analysis;
}

	/**
	 * PHASE 1: Consolidated Company & Industry Enrichment.
	 *
	 * @param array $user_inputs Validated user inputs.
	 * @return array|WP_Error Enriched company profile or error.
	 */
	public function enrich_company_profile( $user_inputs ) {
	if ( empty( $this->api_key ) ) {
	return new WP_Error( 'no_api_key', __( 'OpenAI API key not configured.', 'rtbcb' ) );
	}
	
	$system_prompt = $this->build_enrichment_system_prompt();
	$user_prompt   = $this->build_enrichment_user_prompt( $user_inputs );
	
	$response = $this->call_openai_with_retry(
	$this->get_model( 'advanced' ),
	[ 'instructions' => $system_prompt, 'input' => $user_prompt ],
	$this->estimate_tokens( 1500 )
	);
	
	if ( is_wp_error( $response ) ) {
	return $response;
	}
	
	$parsed        = rtbcb_parse_gpt5_response( $response );
	$enriched_data = json_decode( $parsed['output_text'], true );
	
	if ( ! is_array( $enriched_data ) ) {
	return new WP_Error( 'parse_error', __( 'Failed to parse AI enrichment response.', 'rtbcb' ) );
	}
	
	return $this->validate_and_structure_enrichment( $enriched_data, $user_inputs );
	}
	
	/**
	 * Build system prompt for consolidated enrichment.
	 *
	 * @return string System prompt.
	 */
	private function build_enrichment_system_prompt() {
	return <<<'SYSTEM'
	You are a senior treasury technology consultant conducting comprehensive company and industry research.
	
	Your task is to enrich the provided company information with strategic insights, industry context, and actionable intelligence that will inform treasury technology recommendations.
	
	## Required Output Format
	
	Return a single JSON object with this exact structure:
	
	```json
	{
	  "company_profile": {
	    "enhanced_description": "string - comprehensive company description",
	    "business_model": "string - primary business model and revenue streams",
	    "market_position": "string - competitive position and market standing",
	    "maturity_level": "basic|developing|strategic|optimized",
	    "financial_indicators": {
	      "estimated_revenue": "number - best estimate in USD",
	      "growth_stage": "startup|growth|mature|decline",
	      "financial_health": "strong|stable|concerning|unknown"
	    },
	    "treasury_maturity": {
	      "current_state": "string - assessment of current treasury operations",
	      "sophistication_level": "manual|semi_automated|automated|strategic",
	      "key_gaps": ["array of identified gaps"],
	      "automation_readiness": "low|medium|high"
	    },
	    "strategic_context": {
	      "primary_challenges": ["array of business challenges"],
	      "growth_objectives": ["array of growth objectives"],
	      "competitive_pressures": ["array of competitive factors"],
	      "regulatory_environment": "string - regulatory considerations"
	    }
	  },
	  "industry_context": {
	    "sector_analysis": {
	      "market_dynamics": "string - current market conditions",
	      "growth_trends": "string - industry growth patterns",
	      "disruption_factors": ["array of disruptive forces"],
	      "technology_adoption": "laggard|follower|mainstream|leader"
	    },
	    "benchmarking": {
	      "typical_treasury_setup": "string - industry norm for treasury operations",
	      "common_pain_points": ["array of industry-wide challenges"],
	      "technology_penetration": "low|medium|high",
	      "investment_patterns": "string - typical technology investment patterns"
	    },
	    "regulatory_landscape": {
	      "key_regulations": ["array of relevant regulations"],
	      "compliance_complexity": "low|medium|high|very_high",
	      "upcoming_changes": ["array of anticipated regulatory changes"]
	    }
	  },
	  "strategic_insights": {
	    "technology_readiness": "not_ready|ready|urgent_need",
	    "investment_justification": "weak|moderate|strong|compelling",
	    "implementation_complexity": "low|medium|high|very_high",
	    "expected_benefits": {
	      "efficiency_gains": "string - expected efficiency improvements",
	      "risk_reduction": "string - risk mitigation benefits",
	      "strategic_value": "string - strategic business value",
	      "competitive_advantage": "string - competitive positioning benefits"
	    },
	    "critical_success_factors": ["array of key success factors"],
	    "potential_obstacles": ["array of implementation challenges"]
	  },
	  "enrichment_metadata": {
	    "confidence_level": "number - 0.0 to 1.0",
	    "data_sources": ["array of information sources considered"],
	    "analysis_depth": "surface|moderate|comprehensive",
	    "recommendations_priority": "low|medium|high|urgent"
	  }
	}
	```
	
	## Analysis Guidelines
	
	1. **Be Specific**: Provide company-specific insights, not generic advice
	2. **Use Context**: Leverage all provided company data for comprehensive analysis
	3. **Industry Focus**: Consider industry-specific factors that impact treasury operations
	4. **Practical Insights**: Focus on actionable intelligence for decision-making
	5. **Risk Assessment**: Identify both opportunities and potential challenges
	6. **Confidence Scoring**: Honestly assess confidence based on available information
	
	## Important Notes
	
	- Return ONLY the JSON object, no additional text
	- Use realistic estimates based on company size and industry
	- Consider both current state and future trajectory
	- Focus on treasury-relevant insights and recommendations
	- Maintain professional consulting tone in all descriptions
	SYSTEM;
	}
	
	/**
	 * Build user prompt with company data.
	 *
	 * @param array $user_inputs User inputs.
	 * @return string User prompt.
	 */
	private function build_enrichment_user_prompt( $user_inputs ) {
	$pain_points_formatted = implode( ', ', $user_inputs['pain_points'] );
	
	return <<<PROMPT
	Please conduct comprehensive company and industry enrichment analysis for the following organization:
	
	## Company Information
	- **Company Name**: {$user_inputs['company_name']}
	- **Industry**: {$user_inputs['industry']}
	- **Company Size**: {$user_inputs['company_size']}
	- **Business Objective**: {$user_inputs['business_objective']}
	- **Implementation Timeline**: {$user_inputs['implementation_timeline']}
	- **Budget Range**: {$user_inputs['budget_range']}
	
	## Current Treasury Operations
	- **Weekly Reconciliation Hours**: {$user_inputs['hours_reconciliation']}
	- **Weekly Cash Positioning Hours**: {$user_inputs['hours_cash_positioning']}
	- **Banking Relationships**: {$user_inputs['num_banks']}
	- **Treasury Team Size**: {$user_inputs['ftes']} FTEs
	- **Key Pain Points**: {$pain_points_formatted}
	
	## Analysis Requirements
	
	Provide deep, actionable insights that will inform:
	1. ROI calculations and financial modeling
	2. Technology category recommendations
	3. Implementation planning and risk assessment
	4. Strategic positioning and competitive analysis
	
	Focus on treasury-specific challenges and opportunities within the {$user_inputs['industry']} industry for a {$user_inputs['company_size']} organization.
	
	Consider how the stated business objective of "{$user_inputs['business_objective']}" and timeline of "{$user_inputs['implementation_timeline']}" impact the technology strategy.
	PROMPT;
	}
	
	/**
	 * PHASE 2: Strategic Analysis Generation.
	 *
	 * @param array $enriched_profile Enriched company profile.
	 * @param array $roi_scenarios    ROI calculations.
	 * @param array $recommendation   Category recommendation.
	 * @param array $rag_baseline     RAG search results.
	 * @return array|WP_Error Strategic analysis or error.
	 */
	public function generate_strategic_analysis( $enriched_profile, $roi_scenarios, $recommendation, $rag_baseline ) {
	if ( empty( $this->api_key ) ) {
	return new WP_Error( 'no_api_key', __( 'OpenAI API key not configured.', 'rtbcb' ) );
	}
	
	$system_prompt = $this->build_strategic_analysis_system_prompt();
	$user_prompt   = $this->build_strategic_analysis_user_prompt(
	$enriched_profile,
	$roi_scenarios,
	$recommendation,
	$rag_baseline
	);
	
	$response = $this->call_openai_with_retry(
	$this->get_model( 'premium' ),
	[ 'instructions' => $system_prompt, 'input' => $user_prompt ],
	$this->estimate_tokens( 2000 )
	);
	
	if ( is_wp_error( $response ) ) {
	return $response;
	}
	
	$parsed        = rtbcb_parse_gpt5_response( $response );
	$analysis_data = json_decode( $parsed['output_text'], true );
	
	if ( ! is_array( $analysis_data ) ) {
	return new WP_Error( 'parse_error', __( 'Failed to parse strategic analysis response.', 'rtbcb' ) );
	}
	
	return $this->validate_and_structure_analysis( $analysis_data );
	}
	
	/**
	 * Build system prompt for strategic analysis.
	 *
	 * @return string System prompt.
	 */
	private function build_strategic_analysis_system_prompt() {
	return <<<'SYSTEM'
	You are a senior treasury technology consultant creating executive-level strategic recommendations.
	
	You have been provided with:
	1. Enriched company intelligence and industry context
	2. Detailed ROI calculations and financial modeling
	3. Technology category recommendations
	4. Relevant market research and best practices
	
	Your task is to synthesize this information into a comprehensive strategic analysis that executives can use to make informed treasury technology investment decisions.
	
	## Required Output Format
	
	Return a single JSON object with this exact structure:
	
	```json
	{
	  "executive_summary": {
	    "strategic_positioning": "string - 2-3 sentences on strategic position",
	    "business_case_strength": "weak|moderate|strong|compelling",
	    "key_value_drivers": ["array of 3-4 primary value drivers"],
	    "executive_recommendation": "string - clear recommendation with next steps",
	    "confidence_level": "number - 0.7 to 0.95"
	  },
	  "operational_analysis": {
	    "current_state_assessment": {
	      "efficiency_rating": "poor|fair|good|excellent",
	      "benchmark_comparison": "string - vs industry peers",
	      "capacity_utilization": "string - team capacity analysis"
	    },
	    "process_improvements": [
	      {
	        "process_area": "string - specific process",
	        "current_state": "string - current approach",
	        "improved_state": "string - post-implementation state",
	        "impact_level": "low|medium|high|transformational"
	      }
	    ],
	    "automation_opportunities": [
	      {
	        "opportunity": "string - automation opportunity",
	        "complexity": "low|medium|high",
	        "time_savings": "number - hours per week",
	        "implementation_effort": "low|medium|high"
	      }
	    ]
	  },
	  "financial_analysis": {
	    "investment_breakdown": {
	      "software_licensing": "string - cost range and considerations",
	      "implementation_services": "string - cost range and scope",
	      "training_change_management": "string - cost range and requirements",
	      "ongoing_support": "string - annual costs"
	    },
	    "payback_analysis": {
	      "payback_months": "number - expected payback period",
	      "roi_3_year": "number - 3 year ROI percentage",
	      "npv_analysis": "string - net present value assessment",
	      "sensitivity_factors": ["array of factors affecting ROI"]
	    }
	  },
	  "implementation_roadmap": [
	    {
	      "phase": "string - phase name",
	      "duration": "string - time estimate",
	      "key_activities": ["array of activities"],
	      "success_criteria": ["array of success metrics"],
	      "risks": ["array of phase-specific risks"]
	    }
	  ],
	  "risk_mitigation": {
	    "implementation_risks": ["array of key risks"],
	    "mitigation_strategies": {
	      "change_management": "string - change management approach",
	      "technical_integration": "string - integration risk mitigation",
	      "vendor_selection": "string - vendor risk mitigation",
	      "timeline_management": "string - timeline risk mitigation"
	    },
	    "success_factors": ["array of critical success factors"]
	  },
	  "next_steps": {
	    "immediate": ["array of immediate actions (next 30 days)"],
	    "short_term": ["array of short-term milestones (3-6 months)"],
	    "long_term": ["array of long-term objectives (6+ months)"]
	  },
	  "vendor_considerations": {
	    "evaluation_criteria": ["array of key selection criteria"],
	    "due_diligence_areas": ["array of due diligence focus areas"],
	    "negotiation_priorities": ["array of contract negotiation priorities"]
	  }
	}
	```
	
	## Analysis Standards
	
	- **Executive Focus**: Write for C-level decision makers
	- **Actionable Insights**: Provide specific, implementable recommendations
	- **Risk Balance**: Address both opportunities and challenges honestly
	- **Financial Rigor**: Support recommendations with solid financial analysis
	- **Implementation Reality**: Consider practical constraints and requirements
	- **Competitive Context**: Position recommendations within competitive landscape
	
	Return ONLY the JSON object with no additional text.
	SYSTEM;
	}
	
	/**
	 * Build user prompt for strategic analysis.
	 *
	 * @param array $enriched_profile Enriched company profile.
	 * @param array $roi_scenarios    ROI scenarios.
	 * @param array $recommendation   Technology recommendation.
	 * @param array $rag_baseline     Market research context.
	 * @return string User prompt.
	 */
	private function build_strategic_analysis_user_prompt( $enriched_profile, $roi_scenarios, $recommendation, $rag_baseline ) {
	$prompt = <<<PROMPT
	Create a comprehensive strategic analysis and executive recommendations based on the following research and analysis:
	
	## Enriched Company Intelligence
	```json
	{$this->json_encode_safe( $enriched_profile )}
	```
	
	## Financial Analysis & ROI Scenarios
	```json
	{$this->json_encode_safe( $roi_scenarios )}
	```
	
	## Technology Recommendations
	```json
	{$this->json_encode_safe( $recommendation )}
	```
	
	## Market Research Context
	```json
	{$this->json_encode_safe( $rag_baseline )}
	```
	
	## Analysis Requirements
	
	Synthesize all provided information to create executive-level strategic recommendations that:
	
	1. **Justify the Investment**: Clear business case with financial backing
	2. **Address Specific Needs**: Solutions tailored to identified challenges
	3. **Mitigate Risks**: Comprehensive risk assessment and mitigation strategies
	4. **Enable Success**: Practical implementation roadmap with success metrics
	5. **Competitive Advantage**: Position technology investment within competitive context
	
	Focus on creating actionable insights that executives can use to make informed decisions about treasury technology investments.
	
	Consider the company's maturity level, industry dynamics, and specific operational challenges when formulating recommendations.
	PROMPT;
	
	return $prompt;
	}
	
	/**
	 * Validate and structure enrichment data.
	 *
	 * @param array $enriched_data Enriched data from LLM.
	 * @param array $user_inputs   User inputs.
	 * @return array Structured enrichment data.
	 */
	private function validate_and_structure_enrichment( $enriched_data, $user_inputs ) {
	$structured = [
	'company_profile'    => $this->validate_company_profile( $enriched_data['company_profile'] ?? [], $user_inputs ),
	'industry_context'   => $this->validate_industry_context( $enriched_data['industry_context'] ?? [] ),
	'strategic_insights' => $this->validate_strategic_insights( $enriched_data['strategic_insights'] ?? [] ),
	'enrichment_metadata' => $this->validate_enrichment_metadata( $enriched_data['enrichment_metadata'] ?? [] ),
	];
	
	return $structured;
	}
	
	/**
	 * Validate company profile data.
	 *
	 * @param array $profile     Profile data.
	 * @param array $user_inputs User inputs.
	 * @return array Validated profile data.
	 */
	private function validate_company_profile( $profile, $user_inputs ) {
	return [
	'name'                => $user_inputs['company_name'],
	'enhanced_description' => sanitize_textarea_field( $profile['enhanced_description'] ?? '' ),
	'business_model'      => sanitize_textarea_field( $profile['business_model'] ?? '' ),
	'market_position'     => sanitize_textarea_field( $profile['market_position'] ?? '' ),
	'maturity_level'      => in_array( $profile['maturity_level'] ?? '', [ 'basic', 'developing', 'strategic', 'optimized' ], true )
	? $profile['maturity_level']
	: 'basic',
	'financial_indicators' => [
	'estimated_revenue' => floatval( $profile['financial_indicators']['estimated_revenue'] ?? 0 ),
	'growth_stage'      => sanitize_text_field( $profile['financial_indicators']['growth_stage'] ?? 'unknown' ),
	'financial_health'  => sanitize_text_field( $profile['financial_indicators']['financial_health'] ?? 'unknown' ),
	],
	'treasury_maturity'   => [
	'current_state'        => sanitize_textarea_field( $profile['treasury_maturity']['current_state'] ?? '' ),
	'sophistication_level' => sanitize_text_field( $profile['treasury_maturity']['sophistication_level'] ?? 'manual' ),
	'key_gaps'            => array_map( 'sanitize_text_field', $profile['treasury_maturity']['key_gaps'] ?? [] ),
	'automation_readiness' => sanitize_text_field( $profile['treasury_maturity']['automation_readiness'] ?? 'medium' ),
	],
	'strategic_context'   => [
	'primary_challenges'   => array_map( 'sanitize_text_field', $profile['strategic_context']['primary_challenges'] ?? [] ),
	'growth_objectives'    => array_map( 'sanitize_text_field', $profile['strategic_context']['growth_objectives'] ?? [] ),
	'competitive_pressures' => array_map( 'sanitize_text_field', $profile['strategic_context']['competitive_pressures'] ?? [] ),
	'regulatory_environment' => sanitize_textarea_field( $profile['strategic_context']['regulatory_environment'] ?? '' ),
	],
	];
	}
	
	/**
	 * Safe JSON encoding with error handling.
	 *
	 * @param mixed $data   Data to encode.
	 * @param bool  $pretty Pretty print JSON.
	 * @return string JSON representation.
	 */
	private function json_encode_safe( $data, $pretty = true ) {
	$flags = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;
	if ( $pretty ) {
	$flags |= JSON_PRETTY_PRINT;
	}
	
	$json = json_encode( $data, $flags );
	return false !== $json ? $json : '{}';
	}
	
	/**
	 * Validate industry context data.
	 *
	 * @param array $context Context data.
	 * @return array Validated context.
	 */
	private function validate_industry_context( $context ) {
	return [
	'sector_analysis'    => [
	'market_dynamics'   => sanitize_textarea_field( $context['sector_analysis']['market_dynamics'] ?? '' ),
	'growth_trends'     => sanitize_textarea_field( $context['sector_analysis']['growth_trends'] ?? '' ),
	'disruption_factors' => array_map( 'sanitize_text_field', $context['sector_analysis']['disruption_factors'] ?? [] ),
	'technology_adoption' => sanitize_text_field( $context['sector_analysis']['technology_adoption'] ?? 'follower' ),
	],
	'benchmarking'       => [
	'typical_treasury_setup' => sanitize_textarea_field( $context['benchmarking']['typical_treasury_setup'] ?? '' ),
	'common_pain_points'     => array_map( 'sanitize_text_field', $context['benchmarking']['common_pain_points'] ?? [] ),
	'technology_penetration' => sanitize_text_field( $context['benchmarking']['technology_penetration'] ?? 'medium' ),
	'investment_patterns'    => sanitize_textarea_field( $context['benchmarking']['investment_patterns'] ?? '' ),
	],
	'regulatory_landscape' => [
	'key_regulations'      => array_map( 'sanitize_text_field', $context['regulatory_landscape']['key_regulations'] ?? [] ),
	'compliance_complexity' => sanitize_text_field( $context['regulatory_landscape']['compliance_complexity'] ?? 'medium' ),
	'upcoming_changes'     => array_map( 'sanitize_text_field', $context['regulatory_landscape']['upcoming_changes'] ?? [] ),
	],
	];
	}
	
	/**
	 * Validate strategic insights data.
	 *
	 * @param array $insights Insights data.
	 * @return array Validated insights.
	 */
	private function validate_strategic_insights( $insights ) {
	return [
	'technology_readiness'     => sanitize_text_field( $insights['technology_readiness'] ?? 'not_ready' ),
	'investment_justification' => sanitize_text_field( $insights['investment_justification'] ?? 'weak' ),
	'implementation_complexity' => sanitize_text_field( $insights['implementation_complexity'] ?? 'medium' ),
	'expected_benefits'        => [
	'efficiency_gains'     => sanitize_textarea_field( $insights['expected_benefits']['efficiency_gains'] ?? '' ),
	'risk_reduction'       => sanitize_textarea_field( $insights['expected_benefits']['risk_reduction'] ?? '' ),
	'strategic_value'      => sanitize_textarea_field( $insights['expected_benefits']['strategic_value'] ?? '' ),
	'competitive_advantage' => sanitize_textarea_field( $insights['expected_benefits']['competitive_advantage'] ?? '' ),
	],
	'critical_success_factors' => array_map( 'sanitize_text_field', $insights['critical_success_factors'] ?? [] ),
	'potential_obstacles'      => array_map( 'sanitize_text_field', $insights['potential_obstacles'] ?? [] ),
	];
	}
	
	/**
	 * Validate enrichment metadata.
	 *
	 * @param array $metadata Metadata.
	 * @return array Validated metadata.
	 */
	private function validate_enrichment_metadata( $metadata ) {
	$confidence = isset( $metadata['confidence_level'] ) ? floatval( $metadata['confidence_level'] ) : 0;
	$confidence = max( 0, min( 1, $confidence ) );
	
	return [
	'confidence_level'         => $confidence,
	'data_sources'             => array_map( 'sanitize_text_field', $metadata['data_sources'] ?? [] ),
	'analysis_depth'           => sanitize_text_field( $metadata['analysis_depth'] ?? 'surface' ),
	'recommendations_priority' => sanitize_text_field( $metadata['recommendations_priority'] ?? 'medium' ),
	];
	}
	
	/**
	 * Validate and structure strategic analysis data.
	 *
	 * @param array $analysis_data Raw analysis data.
	 * @return array Structured analysis.
	 */
	private function validate_and_structure_analysis( $analysis_data ) {
	$analysis = [
	'executive_summary' => [
	'strategic_positioning'   => sanitize_textarea_field( $analysis_data['executive_summary']['strategic_positioning'] ?? '' ),
	'business_case_strength'  => sanitize_text_field( $analysis_data['executive_summary']['business_case_strength'] ?? 'weak' ),
	'key_value_drivers'       => array_map( 'sanitize_text_field', $analysis_data['executive_summary']['key_value_drivers'] ?? [] ),
	'executive_recommendation' => sanitize_textarea_field( $analysis_data['executive_summary']['executive_recommendation'] ?? '' ),
	'confidence_level'        => floatval( $analysis_data['executive_summary']['confidence_level'] ?? 0 ),
	],
	'operational_analysis' => [
	'current_state_assessment' => [
	'efficiency_rating'   => sanitize_text_field( $analysis_data['operational_analysis']['current_state_assessment']['efficiency_rating'] ?? '' ),
	'benchmark_comparison' => sanitize_textarea_field( $analysis_data['operational_analysis']['current_state_assessment']['benchmark_comparison'] ?? '' ),
	'capacity_utilization' => sanitize_textarea_field( $analysis_data['operational_analysis']['current_state_assessment']['capacity_utilization'] ?? '' ),
	],
	'process_improvements'     => [],
	'automation_opportunities' => [],
	],
	'financial_analysis' => [
	'investment_breakdown' => [
	'software_licensing'        => sanitize_textarea_field( $analysis_data['financial_analysis']['investment_breakdown']['software_licensing'] ?? '' ),
	'implementation_services'   => sanitize_textarea_field( $analysis_data['financial_analysis']['investment_breakdown']['implementation_services'] ?? '' ),
	'training_change_management' => sanitize_textarea_field( $analysis_data['financial_analysis']['investment_breakdown']['training_change_management'] ?? '' ),
	'ongoing_support'           => sanitize_textarea_field( $analysis_data['financial_analysis']['investment_breakdown']['ongoing_support'] ?? '' ),
	],
	'payback_analysis' => [
	'payback_months'     => floatval( $analysis_data['financial_analysis']['payback_analysis']['payback_months'] ?? 0 ),
	'roi_3_year'         => floatval( $analysis_data['financial_analysis']['payback_analysis']['roi_3_year'] ?? 0 ),
	'npv_analysis'       => sanitize_textarea_field( $analysis_data['financial_analysis']['payback_analysis']['npv_analysis'] ?? '' ),
	'sensitivity_factors' => array_map( 'sanitize_text_field', $analysis_data['financial_analysis']['payback_analysis']['sensitivity_factors'] ?? [] ),
	],
	],
	'implementation_roadmap' => [],
	'risk_mitigation' => [
	'implementation_risks'  => array_map( 'sanitize_text_field', $analysis_data['risk_mitigation']['implementation_risks'] ?? [] ),
	'mitigation_strategies' => [
	'change_management'    => sanitize_textarea_field( $analysis_data['risk_mitigation']['mitigation_strategies']['change_management'] ?? '' ),
	'technical_integration' => sanitize_textarea_field( $analysis_data['risk_mitigation']['mitigation_strategies']['technical_integration'] ?? '' ),
	'vendor_selection'     => sanitize_textarea_field( $analysis_data['risk_mitigation']['mitigation_strategies']['vendor_selection'] ?? '' ),
	'timeline_management'  => sanitize_textarea_field( $analysis_data['risk_mitigation']['mitigation_strategies']['timeline_management'] ?? '' ),
	],
	'success_factors'      => array_map( 'sanitize_text_field', $analysis_data['risk_mitigation']['success_factors'] ?? [] ),
	],
	'next_steps' => [
	'immediate'  => array_map( 'sanitize_text_field', $analysis_data['next_steps']['immediate'] ?? [] ),
	'short_term' => array_map( 'sanitize_text_field', $analysis_data['next_steps']['short_term'] ?? [] ),
	'long_term'  => array_map( 'sanitize_text_field', $analysis_data['next_steps']['long_term'] ?? [] ),
	],
	'vendor_considerations' => [
	'evaluation_criteria'   => array_map( 'sanitize_text_field', $analysis_data['vendor_considerations']['evaluation_criteria'] ?? [] ),
	'due_diligence_areas'   => array_map( 'sanitize_text_field', $analysis_data['vendor_considerations']['due_diligence_areas'] ?? [] ),
	'negotiation_priorities' => array_map( 'sanitize_text_field', $analysis_data['vendor_considerations']['negotiation_priorities'] ?? [] ),
	],
	];
	
	foreach ( (array) ( $analysis_data['operational_analysis']['process_improvements'] ?? [] ) as $item ) {
	$analysis['operational_analysis']['process_improvements'][] = [
	'process_area'   => sanitize_text_field( $item['process_area'] ?? '' ),
	'current_state'  => sanitize_textarea_field( $item['current_state'] ?? '' ),
	'improved_state' => sanitize_textarea_field( $item['improved_state'] ?? '' ),
	'impact_level'   => sanitize_text_field( $item['impact_level'] ?? '' ),
	];
	}
	
	foreach ( (array) ( $analysis_data['operational_analysis']['automation_opportunities'] ?? [] ) as $item ) {
	$analysis['operational_analysis']['automation_opportunities'][] = [
	'opportunity'          => sanitize_text_field( $item['opportunity'] ?? '' ),
	'complexity'           => sanitize_text_field( $item['complexity'] ?? '' ),
	'time_savings'         => floatval( $item['time_savings'] ?? 0 ),
	'implementation_effort' => sanitize_text_field( $item['implementation_effort'] ?? '' ),
	];
	}
	
	foreach ( (array) ( $analysis_data['implementation_roadmap'] ?? [] ) as $phase ) {
	$analysis['implementation_roadmap'][] = [
	'phase'          => sanitize_text_field( $phase['phase'] ?? '' ),
	'duration'       => sanitize_text_field( $phase['duration'] ?? '' ),
	'key_activities' => array_map( 'sanitize_text_field', $phase['key_activities'] ?? [] ),
	'success_criteria' => array_map( 'sanitize_text_field', $phase['success_criteria'] ?? [] ),
	'risks'          => array_map( 'sanitize_text_field', $phase['risks'] ?? [] ),
	];
	}
	
	return $analysis;
	}
	
	/**
	 * Call OpenAI with retry logic.
	 *
	 * Uses exponential backoff with jitter between retries. Each subsequent
	 * attempt slightly reduces the maximum output tokens and increases the
	 * timeout to improve the chances of success.
	 *
	 * @param string       $model             Model name.
	 * @param array|string $prompt            Prompt for the model.
	 * @param int|null     $max_output_tokens Maximum output tokens.
	 * @param int|null     $max_retries       Number of retries.
	 * @param callable|null $chunk_handler     Optional streaming handler.
	 * @return array|WP_Error Response array or error.
	 */
	private function call_openai_with_retry( $model, $prompt, $max_output_tokens = null, $max_retries = null, $chunk_handler = null ) {
	        $max_retries     = $max_retries ?? intval( $this->gpt5_config['max_retries'] );
	        $base_timeout    = intval( $this->gpt5_config['timeout'] ?? 180 );
	        $current_timeout = $base_timeout;
	        $current_tokens  = $max_output_tokens;
	        $max_retry_time  = max( $base_timeout, intval( $this->gpt5_config['max_retry_time'] ?? $base_timeout ) );
	        $start_time      = microtime( true );

        for ( $attempt = 1; $attempt <= $max_retries; $attempt++ ) {
            $elapsed = microtime( true ) - $start_time;
            if ( $elapsed >= $max_retry_time ) {
                break;
            }

            $remaining                    = $max_retry_time - $elapsed;
            $this->gpt5_config['timeout'] = min( $current_timeout, $remaining );

            $response = $this->call_openai( $model, $prompt, $current_tokens, $chunk_handler );

            if ( ! is_wp_error( $response ) ) {
                $this->gpt5_config['timeout'] = $base_timeout;
                return $response;
            }

            $error_code = $response->get_error_code();
            if ( in_array( $error_code, [ 'no_api_key', 'empty_prompt' ], true ) ) {
                $this->gpt5_config['timeout'] = $base_timeout;
                return $response;
            }

            if ( 'llm_http_status' === $error_code ) {
                $data   = $response->get_error_data();
                $status = isset( $data['status'] ) ? intval( $data['status'] ) : 0;
                if ( $status >= 400 && $status < 500 && 429 !== $status ) {
                    $this->gpt5_config['timeout'] = $base_timeout;
                    return $response;
                }
            }

            error_log( "RTBCB: OpenAI attempt {$attempt} failed: " . $response->get_error_message() );

            if ( $attempt < $max_retries ) {
                if ( null !== $current_tokens ) {
                    $min_tokens    = intval( $this->gpt5_config['min_output_tokens'] ?? 1 );
                    $current_tokens = max( $min_tokens, (int) ( $current_tokens * 0.9 ) );
                }

                $current_timeout = min( $current_timeout + 5, $max_retry_time );

                $elapsed = microtime( true ) - $start_time;
                $delay   = min( pow( 2, $attempt - 1 ), $max_retry_time - $elapsed );
                if ( $delay > 0 ) {
                    $jitter = wp_rand( 0, 1000 ) / 1000;
                    usleep( (int) ( ( $delay + $jitter ) * 1000000 ) );
                }
            }
        }

        $this->gpt5_config['timeout'] = $base_timeout;

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
     * @param callable|null $chunk_handler    Optional streaming handler.
     * @return array|WP_Error HTTP response array or WP_Error on failure.
     */
    private function call_openai( $model, $prompt, $max_output_tokens = null, $chunk_handler = null ) {
        if ( empty( $this->api_key ) ) {
            return new WP_Error( 'no_api_key', __( 'OpenAI API key not configured.', 'rtbcb' ) );
        }

        $endpoint         = 'https://api.openai.com/v1/responses'; // Correct endpoint.
        $model_name       = sanitize_text_field( $model ?: 'gpt-5-mini' );
        $model_name       = rtbcb_normalize_model_name( $model_name );
        $default_tokens    = intval( $this->gpt5_config['max_output_tokens'] ?? 8000 );
        $max_output_tokens = intval( $max_output_tokens ?? $default_tokens );
        $min_tokens        = intval( $this->gpt5_config['min_output_tokens'] ?? 1 );
        $max_output_tokens = min( 128000, max( $min_tokens, $max_output_tokens ) );

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

        // Enable streaming so partial chunks are returned progressively.
        $body['stream'] = true;

        do_action( 'rtbcb_llm_prompt_sent', $body );

        $timeout   = intval( $this->gpt5_config['timeout'] ?? 180 );
        $payload   = wp_json_encode( $body );
        $streamed  = '';
        $ch        = curl_init( $endpoint );
        curl_setopt( $ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->api_key,
            'Content-Type: application/json',
        ] );
        curl_setopt( $ch, CURLOPT_POST, true );
        curl_setopt( $ch, CURLOPT_POSTFIELDS, $payload );
        curl_setopt( $ch, CURLOPT_TIMEOUT, $timeout );
        curl_setopt( $ch, CURLOPT_WRITEFUNCTION, function ( $curl, $data ) use ( &$streamed, $chunk_handler ) {
            $streamed .= $data;
            if ( is_callable( $chunk_handler ) ) {
                call_user_func( $chunk_handler, $data );
            }
            return strlen( $data );
        } );

        $this->last_request = $body;
        $ok                 = curl_exec( $ch );
        $error              = curl_error( $ch );
        $http_code          = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
        curl_close( $ch );

        if ( false === $ok ) {
            if ( false !== strpos( strtolower( $error ), 'timed out' ) ) {
                return new WP_Error( 'llm_timeout', __( 'The language model request timed out. Please try again.', 'rtbcb' ) );
            }

            return new WP_Error(
                'llm_http_error',
                sprintf( __( 'Language model request failed: %s', 'rtbcb' ), $error )
            );
        }

        $last_event = '';
        $lines      = explode( "\n", $streamed );
        foreach ( $lines as $line ) {
            $line = trim( $line );
            if ( '' === $line ) {
                continue;
            }
            if ( 0 === strpos( $line, 'data:' ) ) {
                $payload_line = trim( substr( $line, strpos( $line, ':' ) + 1 ) );
                if ( '[DONE]' === $payload_line ) {
                    continue;
                }
                $last_event = $payload_line;
            }
        }

        $response_body = $last_event ? $last_event : $streamed;

        $response = [
            'body'     => $response_body,
            'response' => [ 'code' => $http_code, 'message' => '' ],
            'headers'  => [],
        ];

        $this->last_response = $response;

        if ( class_exists( 'RTBCB_API_Log' ) ) {
            $decoded = json_decode( $response_body, true );
            if ( ! is_array( $decoded ) ) {
                $decoded = [];
            }
            $company      = rtbcb_get_current_company();
            $user_email   = $this->current_inputs['email'] ?? ( $company['email'] ?? '' );
            $company_name = $this->current_inputs['company_name'] ?? ( $company['name'] ?? '' );
            RTBCB_API_Log::save_log( $body, $decoded, get_current_user_id(), $user_email, $company_name );
        }

        if ( $http_code >= 400 ) {
            $decoded = json_decode( $response_body, true );
            if ( is_array( $decoded ) ) {
                if ( isset( $decoded['error']['message'] ) ) {
                    $message = $decoded['error']['message'];
                } elseif ( isset( $decoded['message'] ) ) {
                    $message = $decoded['message'];
                } else {
                    $message = wp_json_encode( $decoded );
                }
            } else {
                $message = $response_body;
            }

            $message = sanitize_text_field( $message );

            return new WP_Error( 'llm_http_status', $message, [ 'status' => $http_code ] );
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


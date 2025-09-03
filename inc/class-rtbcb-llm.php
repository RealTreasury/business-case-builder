<?php
defined( 'ABSPATH' ) || exit;

/**
	* Enhanced LLM integration with comprehensive business analysis
	*
	* @package RealTreasuryBusinessCaseBuilder
	*/

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers.php';

require_once __DIR__ . '/class-rtbcb-response-parser.php';
require_once __DIR__ . '/class-rtbcb-llm-config.php';
require_once __DIR__ . '/class-rtbcb-llm-prompt.php';
require_once __DIR__ . '/class-rtbcb-llm-transport.php';
class RTBCB_LLM {
	private $current_inputs = [];

	/**
	* Configuration instance.
	*
	* @var RTBCB_LLM_Config
	*/
	private $config;

	/**
	* Prompt builder instance.
	*
	* @var RTBCB_LLM_Prompt
	*/
	private $prompt_builder;

	/**
	* Transport instance.
	*
	* @var RTBCB_LLM_Transport
	*/
	private $transport;

	/**
	* Response parser instance.
*
* @var RTBCB_Response_Parser
*/
private $response_parser;

	/**
	* Serialized company research from the last request.
	*
	* @var string|null
	*/
	protected $last_company_research;

	/**
	 * Last prompt sent to the OpenAI API.
	 *
	 * @var array|string|null
	 */
	protected $last_prompt;

	public function __construct() {
$this->config          = new RTBCB_LLM_Config();
$this->prompt_builder  = new RTBCB_LLM_Prompt();
$this->transport       = new RTBCB_LLM_Transport( $this->config );
$this->response_parser = new RTBCB_Response_Parser();

	       if ( empty( $this->config->get_api_key() ) ) {
	               rtbcb_log_error(
	                       'OpenAI API key not configured',
	                       [ 'operation' => '__construct' ]
	               );
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
	protected function get_model( $tier ) {
		$tier    = sanitize_key( $tier );
		$default = rtbcb_get_default_model( $tier );
		$model_option = function_exists( 'get_option' ) ? get_option( "rtbcb_{$tier}_model", $default ) : $default;
	return function_exists( 'sanitize_text_field' ) ? sanitize_text_field( $model_option ) : $model_option;
	}

	/**
	* Retrieve the last request body sent to the OpenAI API.
	*
	* @return array|null Last request body.
	*/
	 public function get_last_request() {
	         return $this->transport->get_last_request();
	 }

	/**
	* Retrieve the last response returned from the OpenAI API.
	*
	* @return array|WP_Error|null Last response or WP_Error.
	*/
	public function get_last_response() {
	       return $this->transport->get_last_response();
	}

	/**
	 * Retrieve the last prompt sent to the OpenAI API.
	 *
	 * @return array|string|null Last prompt.
	 */
	public function get_last_prompt() {
		return $this->last_prompt;
	}

	/**
	* Process an OpenAI response body.
	*
	* @param string $response_body Raw response body.
	* @return array|string|false Parsed content or false on failure.
	*/
	public function process_openai_response( $response_body ) {
	       return $this->response_parser->process_openai_response( $response_body );
	}

	/**
	* Estimate token usage from a desired word count.
	*
	* @param int $words Desired word count.
	* @return int Estimated token count capped by configuration.
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

		return $this->config->estimate_tokens( $words );
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
	               'email'                  => sanitize_email( $user_inputs['email'] ?? '' ),
	       ];

	       $this->current_inputs            = $inputs;

		if ( empty( $this->config->get_api_key() ) ) {
			return new WP_Error( 'no_api_key', __( 'OpenAI API key not configured.', 'rtbcb' ) );
		}

		$selected_model = $model ? sanitize_text_field( $model ) : $this->get_model( 'mini' );
	       $prompt = 'Create a concise treasury technology business case in JSON with keys '
	               . 'executive_summary (strategic_positioning, business_case_strength, key_value_drivers[], '
	               . 'executive_recommendation), operational_insights (current_state_assessment), '
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
		$context = $this->prompt_builder->build_context_for_responses( $history );
		$tokens  = $this->tokens_for_report( 'business_case' );
		$response = $this->transport->call_openai_with_retry( $selected_model, $context, $tokens );

		if ( is_wp_error( $response ) ) {
			return new WP_Error( 'llm_failure', __( 'Unable to generate analysis at this time.', 'rtbcb' ) );
		}

$parsed = $this->response_parser->parse( $response );
		$json   = $this->response_parser->process_openai_response( $parsed['output_text'] );

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
	               'operational_insights' => [
	                       'current_state_assessment' => sanitize_text_field( $json['operational_insights']['current_state_assessment'] ?? ( $json['operational_analysis']['current_state_assessment'] ?? '' ) ),
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

		if ( empty( $this->config->get_api_key() ) ) {
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
		$context = $this->prompt_builder->build_context_for_responses( $history );
		$tokens  = $this->tokens_for_report( 'industry_commentary' );
		$response = $this->transport->call_openai_with_retry( $model, $context, $tokens, null, $chunk_callback );

		if ( is_wp_error( $response ) ) {
			return new WP_Error( 'llm_failure', __( 'Unable to generate commentary at this time.', 'rtbcb' ) );
		}

$parsed     = $this->response_parser->parse( $response );
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

		if ( empty( $this->config->get_api_key() ) ) {
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

		$context = $this->prompt_builder->build_context_for_responses( $history, $system_prompt );
		$tokens  = $this->tokens_for_report( 'company_overview' );
		$response = $this->transport->call_openai_with_retry( $model, $context, $tokens, 3 ); // Reduce retries

		if ( is_wp_error( $response ) ) {
			return new WP_Error( 'llm_failure', $response->get_error_message() );
		}

$parsed  = $this->response_parser->parse( $response );
		$content = $parsed['output_text'];

		if ( empty( $content ) ) {
			return new WP_Error( 'llm_empty_response', __( 'No overview returned.', 'rtbcb' ) );
		}

// Parse JSON response
$json = $this->response_parser->process_openai_response( $content );

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
			'analysis_type'  => rtbcb_get_analysis_type() . '_company_overview',
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

		if ( empty( $this->config->get_api_key() ) ) {
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
		$context = $this->prompt_builder->build_context_for_responses( $history );
		$tokens  = $this->tokens_for_report( 'industry_overview' );
		$response = $this->transport->call_openai_with_retry( $model, $context, $tokens );

		if ( is_wp_error( $response ) ) {
			return new WP_Error( 'llm_failure', __( 'Unable to generate overview at this time.', 'rtbcb' ) );
		}

$parsed   = $this->response_parser->parse( $response );
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

		if ( empty( $this->config->get_api_key() ) ) {
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
		$context = $this->prompt_builder->build_context_for_responses( $history );
		$tokens  = $this->tokens_for_report( 'treasury_tech_overview' );
		$response = $this->transport->call_openai_with_retry( $model, $context, $tokens );

		if ( is_wp_error( $response ) ) {
			return new WP_Error( 'llm_failure', __( 'Unable to generate overview at this time.', 'rtbcb' ) );
		}

$parsed   = $this->response_parser->parse( $response );
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

		if ( empty( $this->config->get_api_key() ) ) {
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
		$context = $this->prompt_builder->build_context_for_responses( $history );
		$tokens  = $this->tokens_for_report( 'real_treasury_overview' );
		$response = $this->transport->call_openai_with_retry( $model, $context, $tokens );

		if ( is_wp_error( $response ) ) {
			return new WP_Error( 'llm_failure', __( 'Unable to generate overview at this time.', 'rtbcb' ) );
		}

$parsed   = $this->response_parser->parse( $response );
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

		if ( empty( $this->config->get_api_key() ) ) {
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
		$context = $this->prompt_builder->build_context_for_responses( $history );
		$tokens  = $this->tokens_for_report( 'category_recommendation' );
		$response = $this->transport->call_openai_with_retry( $model, $context, $tokens );

		if ( is_wp_error( $response ) ) {
			return new WP_Error( 'llm_failure', __( 'Unable to generate recommendation details at this time.', 'rtbcb' ) );
		}

$parsed_response = $this->response_parser->parse( $response );
$parsed          = $this->response_parser->process_openai_response( $parsed_response['output_text'] );

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

		if ( empty( $this->config->get_api_key() ) ) {
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
		$context = $this->prompt_builder->build_context_for_responses( $history );
		$tokens  = $this->tokens_for_report( 'benefits_estimate' );
		$response = $this->transport->call_openai_with_retry( $model, $context, $tokens );

		if ( is_wp_error( $response ) ) {
			return new WP_Error( 'llm_failure', __( 'Unable to generate benefits estimate at this time.', 'rtbcb' ) );
		}

$parsed_response = $this->response_parser->parse( $response );
$parsed          = $this->response_parser->process_openai_response( $parsed_response['output_text'] );

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
	* @param array                     $user_inputs    Sanitized user inputs.
	* @param array                     $roi_data       ROI calculation data.
	* @param callable|array|Traversable $context_chunks Optional context provider or strings for the prompt.
	* @param callable|null             $chunk_callback Optional streaming callback.
	*
	* @return array|WP_Error Structured analysis array (executive_summary, financial_analysis, implementation_roadmap) or error object.
	*/
	public function generate_comprehensive_business_case( $user_inputs, $roi_data, $context_chunks = [], $chunk_callback = null ) {

		if ( rtbcb_heavy_features_disabled() ) {
			return new WP_Error( 'heavy_features_disabled', __( 'AI features are disabled.', 'rtbcb' ) );
		}
		$this->current_inputs = $user_inputs;

		if ( empty( $this->config->get_api_key() ) ) {
			return new WP_Error( 'no_api_key', __( 'OpenAI API key not configured.', 'rtbcb' ) );
		}

		$company_name = sanitize_text_field( $user_inputs['company_name'] ?? '' );
		$industry     = sanitize_text_field( $user_inputs['industry'] ?? '' );
		$company_size = sanitize_text_field( $user_inputs['company_size'] ?? '' );

	       $company_research    = rtbcb_get_research_cache( $company_name, $industry, 'company' );
	       $industry_analysis   = rtbcb_get_research_cache( $company_name, $industry, 'industry' );
	       $tech_landscape      = rtbcb_get_research_cache( $company_name, $industry, 'treasury' );
	       $risk_profile        = rtbcb_get_research_cache( $company_name, $industry, 'risk' );
	       $financial_benchmarks = rtbcb_get_research_cache( $company_name, $industry, 'financial' );

		$batch_prompts = [];

		if ( false === $company_research ) {
			$batch_prompts['company'] = [
				'instructions' => 'Return JSON with company_profile {business_stage,key_characteristics,treasury_priorities,common_challenges} and treasury_maturity {level,rationale}.',
				'input'        => 'Company: ' . $company_name . "\nIndustry: " . $industry . "\nSize: " . $company_size,
			];
		}

	       if ( false === $industry_analysis ) {
	                $batch_prompts['industry'] = [
	                        'instructions' => 'Return JSON with analysis, recommendations, references, errors.',
	                        'input'        => 'Provide sector trends, competitive benchmarks, and regulatory considerations for the ' . $industry . ' industry.',
	                ];
	       }

	       if ( false === $tech_landscape ) {
$tech_prompt = 'Briefly summarize treasury technology solutions relevant to a ' . $company_size . ' company in the ' . $industry . ' industry.';

	if ( is_callable( $context_chunks ) ) {
$context_chunks = $context_chunks();
	}

	if ( $context_chunks instanceof Traversable ) {
$context_chunks = iterator_to_array( $context_chunks );
	}

	if ( ! empty( $context_chunks ) ) {
$tech_prompt .= '\nContext: ' . implode( '\n', array_map( 'sanitize_text_field', (array) $context_chunks ) );
	}

	       $batch_prompts['tech'] = [
	               'instructions' => 'Return plain text summary.',
	               'input'        => $tech_prompt,
	       ];
	       }

	       if ( false === $risk_profile ) {
	               $batch_prompts['risk'] = [
	                       'instructions' => 'Return JSON with risk_matrix {risk,likelihood,impact}[] and mitigations {risk,strategy}[]',
	                       'input'        => 'Assess treasury technology implementation risks for a ' . $company_size . ' company in the ' . $industry . ' industry.',
	               ];
	       }

	       if ( false === $financial_benchmarks ) {
	               $batch_prompts['financial'] = [
	                       'instructions' => 'Return JSON with industry_benchmarks [{metric,value,source}] and valuation_multiples [{metric,range}].',
	                       'input'        => 'Provide financial benchmarks for treasury operations in the ' . $industry . ' industry.',
	               ];
	       }

		$batch_results = [];
		if ( ! empty( $batch_prompts ) ) {
			$batch_results = $this->generate_research_batch( $batch_prompts );
			if ( is_wp_error( $batch_results ) ) {
				$batch_results = [];
			}
		}

	        if ( false === $company_research ) {
	                if ( isset( $batch_results['company'] ) && ! is_wp_error( $batch_results['company'] ) ) {
	                        $json = $this->response_parser->process_openai_response( $batch_results['company'] );
	                        if ( is_array( $json ) ) {
					$company_research = [
						'company_profile'  => [
							'business_stage'      => sanitize_text_field( $json['company_profile']['business_stage'] ?? '' ),
							'key_characteristics' => sanitize_text_field( $json['company_profile']['key_characteristics'] ?? '' ),
							'treasury_priorities' => sanitize_text_field( $json['company_profile']['treasury_priorities'] ?? '' ),
							'common_challenges'   => sanitize_text_field( $json['company_profile']['common_challenges'] ?? '' ),
						],
						'treasury_maturity' => [
							'level'     => sanitize_text_field( $json['treasury_maturity']['level'] ?? '' ),
							'rationale' => sanitize_text_field( $json['treasury_maturity']['rationale'] ?? '' ),
						],
					];
				}
			}

			if ( false === $company_research ) {
				$company_research = $this->conduct_company_research( $user_inputs );
			}
					if ( ! is_wp_error( $company_research ) ) {
				rtbcb_set_research_cache( $company_name, $industry, 'company', $company_research );
			} else {
				return $company_research;
			}
		}

	        if ( false === $industry_analysis ) {
	                if ( isset( $batch_results['industry'] ) && ! is_wp_error( $batch_results['industry'] ) ) {
	                        $json = $this->response_parser->process_openai_response( $batch_results['industry'] );
	                        if ( is_array( $json ) ) {
					$industry_analysis = [
						'analysis'        => sanitize_text_field( $json['analysis'] ?? '' ),
						'recommendations' => array_map( 'sanitize_text_field', $json['recommendations'] ?? [] ),
						'references'      => array_map( 'sanitize_text_field', $json['references'] ?? [] ),
						'errors'          => array_map( 'sanitize_text_field', $json['errors'] ?? [] ),
					];
				}
			}

			if ( false === $industry_analysis ) {
				$industry_analysis = $this->analyze_industry_context( $user_inputs );
			}

			if ( ! is_wp_error( $industry_analysis ) ) {
				rtbcb_set_research_cache( $company_name, $industry, 'industry', $industry_analysis );
			} else {
				return $industry_analysis;
			}
		}

	       if ( false === $tech_landscape ) {
	               if ( isset( $batch_results['tech'] ) && ! is_wp_error( $batch_results['tech'] ) ) {
	                       $tech_landscape = sanitize_textarea_field( $batch_results['tech'] );
	               }

			if ( false === $tech_landscape ) {
				$tech_landscape = $this->research_treasury_solutions( $user_inputs, $context_chunks );
			}

	               if ( ! is_wp_error( $tech_landscape ) ) {
	                       rtbcb_set_research_cache( $company_name, $industry, 'treasury', $tech_landscape );
	               } else {
	                       return $tech_landscape;
	               }
	       }

	       if ( false === $risk_profile ) {
	               if ( isset( $batch_results['risk'] ) && ! is_wp_error( $batch_results['risk'] ) ) {
	                       $json = $this->response_parser->process_openai_response( $batch_results['risk'] );
	                       if ( is_array( $json ) ) {
	                               $risk_profile = [
	                                       'risk_matrix' => array_map(
	                                               function( $risk ) {
	                                                       return [
	                                                               'risk'       => sanitize_text_field( $risk['risk'] ?? '' ),
	                                                               'likelihood' => sanitize_text_field( $risk['likelihood'] ?? '' ),
	                                                               'impact'     => sanitize_text_field( $risk['impact'] ?? '' ),
	                                                       ];
	                                               },
	                                               $json['risk_matrix'] ?? []
	                                       ),
	                                       'mitigations' => array_map(
	                                               function( $mit ) {
	                                                       return [
	                                                               'risk'     => sanitize_text_field( $mit['risk'] ?? '' ),
	                                                               'strategy' => sanitize_text_field( $mit['strategy'] ?? '' ),
	                                                       ];
	                                               },
	                                               $json['mitigations'] ?? []
	                                       ),
	                               ];
	                       }
	               }

	               if ( ! empty( $risk_profile ) ) {
	                       rtbcb_set_research_cache( $company_name, $industry, 'risk', $risk_profile );
	               } else {
	                       $risk_profile = [];
	               }
	       }

	       if ( false === $financial_benchmarks ) {
	               if ( isset( $batch_results['financial'] ) && ! is_wp_error( $batch_results['financial'] ) ) {
	                       $json = $this->response_parser->process_openai_response( $batch_results['financial'] );
	                       if ( is_array( $json ) ) {
	                               $financial_benchmarks = [
	                                       'industry_benchmarks' => array_map(
	                                               function( $bench ) {
	                                                       return [
	                                                               'metric' => sanitize_text_field( $bench['metric'] ?? '' ),
	                                                               'value'  => sanitize_text_field( $bench['value'] ?? '' ),
	                                                               'source' => sanitize_text_field( $bench['source'] ?? '' ),
	                                                       ];
	                                               },
	                                               $json['industry_benchmarks'] ?? []
	                                       ),
	                                       'valuation_multiples' => array_map(
	                                               function( $mult ) {
	                                                       return [
	                                                               'metric' => sanitize_text_field( $mult['metric'] ?? '' ),
	                                                               'range'  => sanitize_text_field( $mult['range'] ?? '' ),
	                                                       ];
	                                               },
	                                               $json['valuation_multiples'] ?? []
	                                       ),
	                               ];
	                       }
	               }

	               if ( ! empty( $financial_benchmarks ) ) {
	                       rtbcb_set_research_cache( $company_name, $industry, 'financial', $financial_benchmarks );
	               } else {
	                       $financial_benchmarks = [];
	               }
	       }

		// Generate comprehensive report
	       $model = $this->select_optimal_model( $user_inputs, $context_chunks );
	       $prompt = $this->build_comprehensive_prompt(
	               $user_inputs,
	               $roi_data,
	               $company_research,
	               $industry_analysis,
	               $tech_landscape,
	               $risk_profile,
	               $financial_benchmarks
	       );

		$history  = [
			[
				'role'    => 'user',
				'content' => $prompt,
			],
		];
		$context = $this->prompt_builder->build_context_for_responses( $history );
		$tokens  = $this->tokens_for_report( 'comprehensive_business_case' );
		$response = $this->transport->call_openai_with_retry( $model, $context, $tokens, null, $chunk_callback );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

$parsed = $this->response_parser->parse_business_case( $response );

		if ( is_wp_error( $parsed ) ) {
			return $parsed;
		}


	       $analysis = $this->enhance_with_research( $parsed, $company_research, $industry_analysis, $tech_landscape, $risk_profile, $financial_benchmarks );
		$analysis['implementation_roadmap'] = $analysis['technology_strategy']['implementation_roadmap'] ?? [];

	return [
'executive_summary'      => $analysis['executive_summary'] ?? [],
'company_intelligence'   => $analysis['company_intelligence'] ?? [],
'operational_insights'   => $analysis['operational_insights'] ?? [],
'risk_analysis'          => $analysis['risk_analysis'] ?? [],
'action_plan'            => $analysis['action_plan'] ?? [],
	'industry_insights'      => $analysis['industry_insights'] ?? [],
'technology_strategy'    => $analysis['technology_strategy'] ?? [],
'financial_analysis'     => $analysis['financial_analysis'] ?? [],
'implementation_roadmap' => $analysis['implementation_roadmap'] ?? [],
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
return $this->response_parser->parse_business_case( $response );
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

	$company_profile = rtbcb_get_research_cache( $company_name, $industry, 'company_profile' );
	if ( false === $company_profile ) {
		$company_profile = $this->build_company_profile( $company_name, $industry, $company_size );
		rtbcb_set_research_cache( $company_name, $industry, 'company_profile', $company_profile );
	}

	$research = [
		'company_profile'       => $company_profile,
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

		if ( empty( $this->config->get_api_key() ) ) {
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

	        $context  = $this->prompt_builder->build_context_for_responses( $history );
	        $tokens   = $this->tokens_for_report( 'competitive_context' );
	        $response = $this->transport->call_openai_with_retry( $model, $context, $tokens );

	        if ( is_wp_error( $response ) ) {
	                return $default;
	        }

$parsed = $this->response_parser->parse( $response );
	        $json   = $this->response_parser->process_openai_response( $parsed['output_text'] );

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
	* @param array                     $user_inputs    Sanitized user inputs.
	* @param callable|array|Traversable $context_chunks Optional context strings.
	* @return array|WP_Error {
	*     @type array  $company_research  Company research data.
	*     @type array  $industry_analysis Industry analysis data.
	*     @type string $tech_landscape    Technology landscape summary.
	* }
	*/
	private function run_batched_research( $user_inputs, $context_chunks ) {
		if ( empty( $this->config->get_api_key() ) ) {
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
		$context  = $this->prompt_builder->build_context_for_responses( $history, $system_prompt );
		$response = $this->transport->call_openai_with_retry( $model, $context );
	       if ( is_wp_error( $response ) ) {
	               return $response;
	       }

$parsed = $this->response_parser->parse( $response );
	       $json   = $this->response_parser->process_openai_response( $parsed['output_text'] );

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
	* Generate multiple research responses in a single OpenAI request.
	*
	* Each prompt should include `instructions` and `input` keys. The returned
	* array preserves the original prompt keys.
	*
	* @param array $prompts Associative array of prompt data.
	* @return array|WP_Error Array of responses or error object.
	*/
	private function generate_research_batch( $prompts ) {
		if ( empty( $this->config->get_api_key() ) ) {
			return new WP_Error( 'no_api_key', __( 'OpenAI API key not configured.', 'rtbcb' ) );
		}

		if ( empty( $prompts ) || ! is_array( $prompts ) ) {
			return new WP_Error( 'empty_prompt', __( 'Prompts array cannot be empty.', 'rtbcb' ) );
		}

		$model  = $this->get_model( 'mini' );
		$inputs = [];
		foreach ( $prompts as $prompt ) {
			$instructions = sanitize_textarea_field( $prompt['instructions'] ?? '' );
			$input        = sanitize_textarea_field( $prompt['input'] ?? '' );
			$inputs[]     = $instructions ? $instructions . "\n" . $input : $input;
		}

	       $config = $this->config->get_gpt5_config();
	       $body   = [
	               'model'            => $model,
	               'input'            => $inputs,
	               'max_output_tokens'=> intval( $config['max_output_tokens'] ?? 8000 ),
	               'stream'           => false,
	       ];

	       if ( rtbcb_model_supports_temperature( $model ) ) {
	               $body['temperature'] = floatval( $config['temperature'] ?? 0.7 );
	       }

		$response = wp_remote_post(
			'https://api.openai.com/v1/responses',
			[
				'headers' => [
					'Authorization' => 'Bearer ' . $this->config->get_api_key(),
					'Content-Type'  => 'application/json',
				],
				'body'    => wp_json_encode( $body ),
				'timeout' => 600,
			]
		);

		if ( is_wp_error( $response ) ) {
			if ( class_exists( 'RTBCB_API_Log' ) ) {
				$user_email   = $this->current_inputs['email'] ?? '';
				$company_name = $this->current_inputs['company_name'] ?? '';
				RTBCB_API_Log::save_log( $body, [ 'error' => $response->get_error_message() ], get_current_user_id(), $user_email, $company_name );
			}
			return $response;
		}

		$status = wp_remote_retrieve_response_code( $response );
		if ( $status < 200 || $status >= 300 ) {
			return new WP_Error(
				'llm_http_status',
				__( 'Language model request failed.', 'rtbcb' ),
				[ 'status' => $status ]
			);
		}

	        $decoded = $this->response_parser->process_openai_response( wp_remote_retrieve_body( $response ) );
	        if ( ! is_array( $decoded ) ) {
	                return new WP_Error( 'llm_response_decode_error', __( 'Failed to decode response body.', 'rtbcb' ) );
	        }

		$outputs = $decoded['output'] ?? [];
		$keys    = array_keys( $prompts );
		$results = [];

		foreach ( $keys as $index => $key ) {
			$text = '';
			if ( isset( $outputs[ $index ]['content'] ) && is_array( $outputs[ $index ]['content'] ) ) {
				foreach ( $outputs[ $index ]['content'] as $piece ) {
					if ( isset( $piece['text'] ) ) {
						$text .= $piece['text'];
					}
				}
			} elseif ( isset( $outputs[ $index ]['output_text'] ) ) {
				$text = $outputs[ $index ]['output_text'];
			}

			$text = trim( $text );
			if ( '' === $text ) {
				$results[ $key ] = new WP_Error( 'llm_empty_response', __( 'Empty response from language model.', 'rtbcb' ) );
			} else {
				$results[ $key ] = $text;
			}
		}

		return $results;
	}

	/**
	* Analyze industry context using the LLM.
	*
	* @param array $user_inputs Sanitized user inputs.
	* @return array|WP_Error Industry analysis or error object.
	*/
	private function analyze_industry_context( $user_inputs ) {
		if ( empty( $this->config->get_api_key() ) ) {
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
		$context = $this->prompt_builder->build_context_for_responses( $history, $system_prompt );
		$tokens  = $this->tokens_for_report( 'industry_analysis' );
		$response = $this->transport->call_openai_with_retry( $model, $context, $tokens );
		if ( is_wp_error( $response ) ) {
			return $response;
		}

$parsed_response = $this->response_parser->parse( $response );
$json            = $this->response_parser->process_openai_response( $parsed_response['output_text'] );

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
		if ( empty( $this->config->get_api_key() ) ) {
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
		$context = $this->prompt_builder->build_context_for_responses( $history );
		$tokens  = $this->tokens_for_report( 'tech_research' );
		$response = $this->transport->call_openai_with_retry( $model, $context, $tokens );
		if ( is_wp_error( $response ) ) {
			return $response;
		}

$parsed  = $this->response_parser->parse( $response );
		$summary = sanitize_textarea_field( $parsed['output_text'] );

		if ( empty( $summary ) ) {
			return new WP_Error( 'llm_empty_response', __( 'No technology research returned.', 'rtbcb' ) );
		}

		return $summary;
	}

	/**
	* Select the optimal model for comprehensive analysis.
	*
	* @param array                     $user_inputs    Sanitized user inputs.
	* @param callable|array|Traversable $context_chunks Optional context strings.
	* @return string Model identifier.
	*/
	protected function select_optimal_model( $user_inputs, $context_chunks ) {
		$model         = $this->get_model( 'advanced' );
		$context_count = 0;

		if ( is_callable( $context_chunks ) || $context_chunks instanceof Traversable ) {
			// Lazily provided context; treat as empty without invoking.
		} elseif ( is_array( $context_chunks ) || $context_chunks instanceof Countable ) {
			$context_count = count( $context_chunks );
		}

		if ( $context_count < 3 ) {
			$model = $this->get_model( 'premium' );
		}

		return sanitize_text_field( $model );
	}

/**
	 * Build a comprehensive analysis prompt with full context and JSON schema.
	 *
	 * @param array  $user_inputs      Sanitized user inputs.
	 * @param array  $roi_data         ROI calculation data.
	* @param array  $company_research   Research data about the company.
	* @param array  $industry_analysis  Industry analysis strings.
	* @param string $tech_landscape     Treasury technology landscape description.
	* @param array  $risk_analysis      Risk research data.
	* @param array  $financial_benchmarks Financial benchmark data.
	*
	* @return string Prompt instructing the LLM to return a populated JSON structure.
	*/
	protected function build_comprehensive_prompt( $user_inputs, $roi_data, $company_research, $industry_analysis, $tech_landscape, $risk_analysis, $financial_benchmarks ) {
	$company_name    = $user_inputs['company_name'] ?? 'the company';
	$company_profile = $company_research['company_profile'];
	
	$prompt  = "As a senior treasury technology consultant with 15+ years of experience, create a comprehensive business case for {$company_name}.\n\n";
	
	// Executive summary guidance.
	$prompt .= "EXECUTIVE BRIEF:\n";
	$prompt .= "Create a strategic business case that justifies treasury technology investment with:\n";
	$prompt .= "- Clear ROI projections with risk-adjusted scenarios\n";
	$prompt .= "- Key strategic value drivers\n";
	$prompt .= "- Implementation roadmap with success metrics\n\n";
	
	// Company context.
	$prompt .= "COMPANY PROFILE:\n";
	$prompt .= "Company: {$company_name}\n";
	$prompt .= "Industry: " . ( $user_inputs['industry'] ?? 'Not specified' ) . "\n";
	$prompt .= "Revenue Size: " . ( $user_inputs['company_size'] ?? 'Not specified' ) . "\n";
	$prompt .= "Business Stage: {$company_profile['business_stage']}\n";
	$prompt .= "Key Characteristics: {$company_profile['key_characteristics']}\n";
	$prompt .= "Treasury Priorities: {$company_profile['treasury_priorities']}\n";
	$prompt .= "Common Challenges: {$company_profile['common_challenges']}\n\n";
	
	// Current state analysis.
	$prompt .= "CURRENT TREASURY OPERATIONS:\n";
	$prompt .= "Weekly Reconciliation Hours: " . ( $user_inputs['hours_reconciliation'] ?? 0 ) . "\n";
	$prompt .= "Weekly Cash Positioning Hours: " . ( $user_inputs['hours_cash_positioning'] ?? 0 ) . "\n";
	$prompt .= "Banking Relationships: " . ( $user_inputs['num_banks'] ?? 0 ) . "\n";
	$prompt .= "Treasury Team Size: " . ( $user_inputs['ftes'] ?? 0 ) . " FTEs\n";
	$prompt .= "Key Pain Points: " . implode( ', ', $user_inputs['pain_points'] ?? [] ) . "\n\n";
	
	// Industry context.
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
	
	// Treasury technology landscape.
	if ( ! empty( $tech_landscape ) ) {
	$prompt .= "TECHNOLOGY LANDSCAPE:\n{$tech_landscape}\n\n";
	}

	// Risk context.
	if ( ! empty( $risk_analysis ) ) {
	if ( ! empty( $risk_analysis['risk_matrix'] ) ) {
	$prompt .= "RISK MATRIX:\n";
	foreach ( $risk_analysis['risk_matrix'] as $risk ) {
	$prompt .= '- ' . $risk['risk'] . ' (Likelihood: ' . $risk['likelihood'] . ', Impact: ' . $risk['impact'] . ")\n";
	}
	$prompt .= "\n";
	}
	if ( ! empty( $risk_analysis['mitigations'] ) ) {
	$prompt .= "MITIGATION STRATEGIES:\n";
	foreach ( $risk_analysis['mitigations'] as $mit ) {
	$prompt .= '- ' . $mit['risk'] . ': ' . $mit['strategy'] . "\n";
	}
	$prompt .= "\n";
	}
	}

	// Financial benchmarks.
	if ( ! empty( $financial_benchmarks ) ) {
	if ( ! empty( $financial_benchmarks['industry_benchmarks'] ) ) {
	$prompt .= "FINANCIAL BENCHMARKS:\n";
	foreach ( $financial_benchmarks['industry_benchmarks'] as $bench ) {
	$prompt .= '- ' . $bench['metric'] . ': ' . $bench['value'];
	if ( ! empty( $bench['source'] ) ) {
	$prompt .= ' (' . $bench['source'] . ')';
	}
	$prompt .= "\n";
	}
	$prompt .= "\n";
	}
	if ( ! empty( $financial_benchmarks['valuation_multiples'] ) ) {
	$prompt .= "VALUATION MULTIPLES:\n";
	foreach ( $financial_benchmarks['valuation_multiples'] as $mult ) {
	$prompt .= '- ' . $mult['metric'] . ': ' . $mult['range'] . "\n";
	}
	$prompt .= "\n";
	}
	}

	// ROI analysis.
	$prompt .= "PROJECTED ROI ANALYSIS:\n";
	$prompt .= "Conservative Scenario: $" . number_format( $roi_data['conservative']['total_annual_benefit'] ?? 0 ) . "\n";
	$prompt .= "Base Case Scenario: $" . number_format( $roi_data['base']['total_annual_benefit'] ?? 0 ) . "\n";
	$prompt .= "Optimistic Scenario: $" . number_format( $roi_data['optimistic']['total_annual_benefit'] ?? 0 ) . "\n\n";
	
	// Strategic context.
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
	$prompt .= "Return only valid JSON using the structure below. Replace all example values with specific, meaningful content for {$company_name}. Every field must be filled; no empty arrays or generic placeholders.\n";
	$prompt .= "Do not include any explanatory text outside the JSON.\n\n";
	
	$structure = [
	'executive_summary' => [
	'strategic_positioning' => '2-3 sentence strategic assessment',
	'key_value_drivers'     => [ 'driver1', 'driver2', 'driver3', 'driver4' ],
	'business_case_strength' => 'strong|moderate|compelling|weak',
	'executive_recommendation' => 'clear next steps recommendation',
	'confidence_level'       => 0.85,
	],
	'company_intelligence' => [
	'enriched_profile' => [
	'name'               => 'company name',
	'industry'           => 'industry',
	'size'               => 'company size',
	'maturity_level'     => 'basic|developing|strategic|optimized',
	'key_challenges'     => [ 'challenge1', 'challenge2', 'challenge3' ],
	'strategic_priorities'=> [ 'priority1', 'priority2' ],
	],
	'industry_context' => [
	'competitive_pressure'   => 'low|moderate|high',
	'regulatory_environment' => 'description of regulatory environment',
	'sector_trends'          => 'key industry trends affecting treasury',
	],
	'maturity_assessment' => [
	[
	'dimension'     => 'process automation',
	'current_level' => 'manual|semi-automated|automated',
	'target_level'  => 'target state',
	'gap_analysis'  => 'description of gaps',
	],
	],
	'competitive_position' => [
	[
	'competitor'        => 'competitor name',
	'relative_position' => 'ahead|behind|similar',
	'key_differentiator' => 'main difference',
	],
	],
	],
	'operational_insights' => [
	'current_state_assessment' => [ 'assessment point 1', 'assessment point 2', 'assessment point 3' ],
	'process_improvements' => [
	[
	'process'        => 'process name',
	'current_state'  => 'current approach',
	'improved_state' => 'future approach',
	'impact'         => 'expected impact',
	],
	],
	'automation_opportunities' => [
	[
'opportunity'           => 'automation area',
'complexity'            => 'low|medium|high',
'potential_savings'     => 'time/cost savings',
'implementation_effort' => 'effort required',
	],
],
],
'financial_analysis' => [
'roi_scenarios' => [
'conservative' => [
'total_annual_benefit' => 150000,
'labor_savings'        => 90000,
'fee_savings'          => 45000,
'error_reduction'      => 15000,
],
'base' => [
'total_annual_benefit' => 250000,
'labor_savings'        => 150000,
'fee_savings'          => 75000,
'error_reduction'      => 25000,
],
'optimistic' => [
'total_annual_benefit' => 350000,
'labor_savings'        => 210000,
'fee_savings'          => 105000,
'error_reduction'      => 35000,
],
],
'investment_breakdown' => [
[
'category'    => 'software licensing',
'amount'      => 50000,
'description' => 'annual licensing costs',
],
],
'payback_analysis' => [
[
'scenario'       => 'base case',
'payback_months' => 18,
'roi_3_year'     => 245,
'npv'            => 425000,
],
],
'sensitivity_analysis' => [
[
'factor'            => 'labor cost assumptions',
'impact_percentage' => 15,
'probability'       => 0.7,
],
],
],
'technology_strategy' => [
'recommended_category' => 'tms_lite',
'category_details' => [
'name'      => 'Treasury Management System (Lite)',
'features'  => [ 'feature1', 'feature2' ],
'ideal_for' => 'company profile match',
],
'implementation_roadmap' => [
[
'phase'           => 'Phase 1',
'timeline'        => '0-3 months',
'activities'      => [ 'activity1', 'activity2' ],
'success_criteria' => [ 'criteria1', 'criteria2' ],
],
],
'vendor_considerations' => [ 'vendor selection criteria', 'implementation considerations' ],
],
'industry_insights' => [
		'sector_trends' => [ 'trend 1 affecting treasury operations', 'trend 2 driving technology adoption' ],
		'competitive_benchmarks' => [ 'benchmark 1 for treasury efficiency', 'benchmark 2 for technology adoption' ],
		'regulatory_considerations' => [ 'regulatory requirement 1', 'regulatory requirement 2' ],
],
'risk_analysis' => [
'implementation_risks' => [ 'risk 1: description and likelihood', 'risk 2: description and impact' ],
'mitigation_strategies' => [ 'mitigation approach 1', 'mitigation approach 2' ],
'success_factors'       => [ 'critical success factor 1', 'critical success factor 2' ],
],
'action_plan' => [
'immediate_steps'      => [ 'immediate action 1 (next 30 days)', 'immediate action 2 (next 30 days)' ],
'short_term_milestones' => [ 'milestone 1 (3-6 months)', 'milestone 2 (3-6 months)' ],
'long_term_objectives'  => [ 'objective 1 (6+ months)', 'objective 2 (6+ months)' ],
],
];

	$prompt .= json_encode( $structure, JSON_PRETTY_PRINT );

return $prompt;
	}

	/**
	* Enhance parsed analysis with research context.
	*
	* @param array $analysis             Parsed analysis from LLM.
	* @param array $company_research     Company research data.
	* @param array $industry_analysis    Industry analysis data.
	* @param string $tech_landscape      Technology landscape summary.
	* @param array $risk_profile         Risk research data.
	* @param array $financial_benchmarks Financial benchmark data.
	* @return array Enhanced analysis.
	*/
	private function enhance_with_research( $analysis, $company_research, $industry_analysis, $tech_landscape, $risk_profile, $financial_benchmarks ) {
	       $analysis['research'] = [
	               'company'    => $company_research,
	               'industry'   => $industry_analysis,
	               'technology' => $tech_landscape,
	               'risk'       => $risk_profile,
	               'financial'  => $financial_benchmarks,
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

		if ( rtbcb_heavy_features_disabled() ) {
			return new WP_Error( 'heavy_features_disabled', __( 'AI features are disabled.', 'rtbcb' ) );
		}
	if ( empty( $this->config->get_api_key() ) ) {
	return new WP_Error( 'no_api_key', __( 'OpenAI API key not configured.', 'rtbcb' ) );
	}

	$system_prompt = $this->build_enrichment_system_prompt();
	$user_prompt   = $this->build_enrichment_user_prompt( $user_inputs );

	$response = $this->transport->call_openai_with_retry(
	$this->get_model( 'advanced' ),
	[ 'instructions' => $system_prompt, 'input' => $user_prompt ],
	$this->config->estimate_tokens( 1500 )
	);

	if ( is_wp_error( $response ) ) {
	        return $response;
	}

$parsed        = $this->response_parser->parse( $response );
	$enriched_data = $this->validate_enrichment_response( $parsed['output_text'] );

	if ( is_wp_error( $enriched_data ) ) {
	        return $enriched_data;
	}

	return $this->validate_and_structure_enrichment( $enriched_data, $user_inputs );
	}

	/**
	* Build system prompt for consolidated enrichment.
	*
	* @return string System prompt.
	*/
	protected function build_enrichment_system_prompt() {
		return <<<'SYSTEM'
You are a senior treasury technology consultant with 15+ years of experience conducting company and industry research for Fortune 500 clients.

Your expertise includes:
- Treasury operations optimization and digital transformation
- Industry benchmarking and competitive analysis  
- Technology vendor evaluation and selection
- Financial modeling and ROI analysis

CRITICAL: Respond ONLY with valid JSON matching the exact schema provided.
SYSTEM;
	}

/**
 * Build user prompt with company data.
 *
 * @param array $user_inputs User inputs.
 * @return string User prompt.
 */
	protected function build_enrichment_user_prompt( $user_inputs ) {
		$pain_points_formatted = implode( ', ', array_map( function( $point ) {
			return str_replace( '_', ' ', ucwords( $point, '_' ) );
		}, $user_inputs['pain_points'] ?? [] ) );

		return <<<PROMPT
## Treasury Technology Analysis Request

### Company Profile  
- **Company Name**: {$user_inputs['company_name']}
- **Industry**: {$user_inputs['industry']}
- **Revenue Size**: {$user_inputs['company_size']}
- **Business Objective**: {$user_inputs['business_objective']}

### Current Treasury Operations
- **Team Size**: {$user_inputs['ftes']} FTEs
- **Weekly Reconciliation Hours**: {$user_inputs['hours_reconciliation']}
- **Weekly Cash Positioning Hours**: {$user_inputs['hours_cash_positioning']}
- **Banking Relationships**: {$user_inputs['num_banks']}
- **Key Pain Points**: {$pain_points_formatted}

### Analysis Requirements
Provide actionable insights for:
1. Technology readiness assessment
2. ROI projection foundations
3. Implementation complexity evaluation
4. Strategic positioning analysis

Focus on treasury-specific challenges and opportunities within the {$user_inputs['industry']} industry for a {$user_inputs['company_size']} organization.

### Required JSON Schema
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
	"potential_obstacles": ["array of 4-5 likely implementation challenges"]
  },
  "enrichment_metadata": {
	"confidence_level": "number - 0.0 to 1.0",
	"data_sources": ["array of information sources considered"],
	"analysis_depth": "surface|moderate|comprehensive",
	"recommendations_priority": "low|medium|high|urgent"
  }
	}
```
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

	       if ( rtbcb_heavy_features_disabled() ) {
	               return new WP_Error( 'heavy_features_disabled', __( 'AI features are disabled.', 'rtbcb' ) );
	       }
               if ( empty( $this->config->get_api_key() ) ) {
                       return new WP_Error( 'no_api_key', __( 'OpenAI API key not configured.', 'rtbcb' ) );
               }

               $payload = [
                       'company_intelligence'       => $enriched_profile,
                       'financial_analysis'         => $roi_scenarios,
                       'technology_recommendations' => $recommendation,
                       'market_research_context'    => $rag_baseline,
                       'analysis_requirements'      => [
                               'Justify the Investment: Clear business case with financial backing',
                               'Address Specific Needs: Solutions tailored to identified challenges',
                               'Mitigate Risks: Comprehensive risk assessment and mitigation strategies',
                               'Enable Success: Practical implementation roadmap with success metrics',
                               'Competitive Advantage: Position technology investment within competitive context',
                       ],
               ];

               $messages = [
                       [
                               'role'    => 'system',
                               'content' => $this->get_strategic_system_prompt(),
                       ],
                       [
                               'role'    => 'user',
                               'content' => wp_json_encode( $payload ),
                       ],
               ];

               $body     = [ 'messages' => $messages ];
               $model    = $this->get_model( 'premium' );
               $response = $this->transport->call_openai_with_retry( $model, $body );

               if ( is_wp_error( $response ) ) {
                       return $response;
               }

               $decoded = json_decode( wp_remote_retrieve_body( $response ), true );
               $content = $decoded['choices'][0]['message']['content'] ?? '';
               $data    = json_decode( $content, true );

               if ( $this->is_valid_strategic_response( $data ) ) {
                       if ( class_exists( 'RTBCB_API_Log' ) ) {
                               $user_email   = $this->current_inputs['email'] ?? '';
                               $company_name = $this->current_inputs['company_name'] ?? '';
                               RTBCB_API_Log::save_log( $payload, $decoded, get_current_user_id(), $user_email, $company_name );
                       }

                       return $this->validate_and_structure_analysis( $data );
               }

               return new WP_Error( 'invalid_json', __( 'Model returned invalid JSON.', 'rtbcb' ), [ 'raw' => $content ] );
       }

       /**
        * Get system prompt for strategic analysis.
        *
        * @return string System prompt string.
        */
       private function get_strategic_system_prompt() {
               return 'You are a senior treasury technology consultant tasked with creating executive-level strategic recommendations.';
       }

       /**
        * Validate strategic analysis response structure.
        *
        * @param mixed $data Decoded JSON data.
        * @return bool Whether the response contains required sections.
        */
       private function is_valid_strategic_response( $data ) {
               if ( ! is_array( $data ) ) {
                       return false;
               }

               $required = [
                       'executive_summary',
                       'operational_insights',
                       'financial_analysis',
                       'implementation_roadmap',
                       'risk_analysis',
                       'action_plan',
                       'vendor_considerations',
               ];

               foreach ( $required as $key ) {
                       if ( ! array_key_exists( $key, $data ) ) {
                               return false;
                       }
               }

               return true;
       }

	/**
	* Validate enrichment response JSON structure.
	*
	* @param string $response JSON response from LLM.
	* @return array|WP_Error Decoded array or error.
	*/
	private function validate_enrichment_response( $response ) {
	        $decoded = $this->response_parser->process_openai_response( $response );

	        if ( ! $decoded ) {
	                return new WP_Error( 'invalid_json', __( 'Response is not valid JSON', 'rtbcb' ) );
	        }

	        $required_fields = [
	                'company_profile',
	                'industry_context',
	                'strategic_insights',
	                'enrichment_metadata',
	        ];

	        foreach ( $required_fields as $field ) {
	                if ( ! isset( $decoded[ $field ] ) ) {
	                        return new WP_Error(
	                                'missing_field',
	                                sprintf( __( 'Missing required field: %s', 'rtbcb' ), $field )
	                        );
	                }
	        }

	        return $decoded;
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
		'industry'            => sanitize_text_field( $user_inputs['industry'] ?? '' ),
		'enhanced_description' => wp_kses_post( $profile['enhanced_description'] ?? '' ),
	'business_model'      => wp_kses_post( $profile['business_model'] ?? '' ),
	'market_position'     => wp_kses_post( $profile['market_position'] ?? '' ),
	'maturity_level'      => in_array( $profile['maturity_level'] ?? '', [ 'basic', 'developing', 'strategic', 'optimized' ], true )
	? $profile['maturity_level']
	: 'basic',
	'financial_indicators' => [
	'estimated_revenue' => floatval( $profile['financial_indicators']['estimated_revenue'] ?? 0 ),
	'growth_stage'      => sanitize_text_field( $profile['financial_indicators']['growth_stage'] ?? 'unknown' ),
	'financial_health'  => sanitize_text_field( $profile['financial_indicators']['financial_health'] ?? 'unknown' ),
	],
	'treasury_maturity'   => [
	'current_state'        => wp_kses_post( $profile['treasury_maturity']['current_state'] ?? '' ),
	'sophistication_level' => sanitize_text_field( $profile['treasury_maturity']['sophistication_level'] ?? 'manual' ),
	'key_gaps'            => array_map( 'sanitize_text_field', $profile['treasury_maturity']['key_gaps'] ?? [] ),
	'automation_readiness' => sanitize_text_field( $profile['treasury_maturity']['automation_readiness'] ?? 'medium' ),
	],
	'strategic_context'   => [
	'primary_challenges'   => array_map( 'sanitize_text_field', $profile['strategic_context']['primary_challenges'] ?? [] ),
	'growth_objectives'    => array_map( 'sanitize_text_field', $profile['strategic_context']['growth_objectives'] ?? [] ),
	'competitive_pressures' => array_map( 'sanitize_text_field', $profile['strategic_context']['competitive_pressures'] ?? [] ),
	'regulatory_environment' => wp_kses_post( $profile['strategic_context']['regulatory_environment'] ?? '' ),
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
	'market_dynamics'   => wp_kses_post( $context['sector_analysis']['market_dynamics'] ?? '' ),
	'growth_trends'     => wp_kses_post( $context['sector_analysis']['growth_trends'] ?? '' ),
	'disruption_factors' => array_map( 'sanitize_text_field', $context['sector_analysis']['disruption_factors'] ?? [] ),
	'technology_adoption' => sanitize_text_field( $context['sector_analysis']['technology_adoption'] ?? 'follower' ),
	],
	'benchmarking'       => [
	'typical_treasury_setup' => wp_kses_post( $context['benchmarking']['typical_treasury_setup'] ?? '' ),
	'common_pain_points'     => array_map( 'sanitize_text_field', $context['benchmarking']['common_pain_points'] ?? [] ),
	'technology_penetration' => sanitize_text_field( $context['benchmarking']['technology_penetration'] ?? 'medium' ),
	'investment_patterns'    => wp_kses_post( $context['benchmarking']['investment_patterns'] ?? '' ),
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
		$obstacles = array_map( 'sanitize_text_field', $insights['potential_obstacles'] ?? [] );
		$count     = count( $obstacles );

		if ( $count < 4 ) {
			while ( count( $obstacles ) < 4 ) {
				$obstacles[] = __( 'unspecified challenge', 'rtbcb' );
			}
			RTBCB_Logger::log(
				'insufficient_potential_obstacles',
				[ 'count' => $count ]
			);
		} elseif ( $count > 5 ) {
			$obstacles = array_slice( $obstacles, 0, 5 );
			RTBCB_Logger::log(
				'too_many_potential_obstacles',
				[ 'count' => $count ]
			);
		}

		return [
			'technology_readiness'     => sanitize_text_field( $insights['technology_readiness'] ?? 'not_ready' ),
			'investment_justification' => sanitize_text_field( $insights['investment_justification'] ?? 'weak' ),
			'implementation_complexity' => sanitize_text_field( $insights['implementation_complexity'] ?? 'medium' ),
			'expected_benefits'        => [
				'efficiency_gains'     => wp_kses_post( $insights['expected_benefits']['efficiency_gains'] ?? '' ),
				'risk_reduction'       => wp_kses_post( $insights['expected_benefits']['risk_reduction'] ?? '' ),
				'strategic_value'      => wp_kses_post( $insights['expected_benefits']['strategic_value'] ?? '' ),
				'competitive_advantage' => wp_kses_post( $insights['expected_benefits']['competitive_advantage'] ?? '' ),
			],
			'critical_success_factors' => array_map( 'sanitize_text_field', $insights['critical_success_factors'] ?? [] ),
			'potential_obstacles'      => $obstacles,
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
'operational_insights' => [
'current_state_assessment' => array_map(
'sanitize_textarea_field',
(array) ( $analysis_data['operational_insights']['current_state_assessment'] ??
( $analysis_data['operational_analysis']['current_state_assessment'] ?? [] ) )
),
'process_improvements'     => [],
'automation_opportunities' => [],
],
	'industry_insights'   => [
		'sector_trends'          => array_map( 'sanitize_textarea_field', (array) ( $analysis_data['industry_insights']['sector_trends'] ?? [] ) ),
		'competitive_benchmarks' => array_map( 'sanitize_textarea_field', (array) ( $analysis_data['industry_insights']['competitive_benchmarks'] ?? [] ) ),
		'regulatory_considerations' => array_map( 'sanitize_textarea_field', (array) ( $analysis_data['industry_insights']['regulatory_considerations'] ?? [] ) ),
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
	'risk_analysis' => [
	'implementation_risks'  => array_map( 'sanitize_text_field', ( $analysis_data['risk_analysis']['implementation_risks'] ?? $analysis_data['risk_mitigation']['implementation_risks'] ?? [] ) ),
	'mitigation_strategies' => array_map( 'sanitize_text_field', ( $analysis_data['risk_analysis']['mitigation_strategies'] ?? $analysis_data['risk_mitigation']['mitigation_strategies'] ?? [] ) ),
	'success_factors'      => array_map( 'sanitize_text_field', ( $analysis_data['risk_analysis']['success_factors'] ?? $analysis_data['risk_mitigation']['success_factors'] ?? [] ) ),
	],
	'action_plan' => [
	'immediate_steps'       => array_map( 'sanitize_text_field', ( $analysis_data['action_plan']['immediate_steps'] ?? $analysis_data['next_steps']['immediate'] ?? [] ) ),
	'short_term_milestones' => array_map( 'sanitize_text_field', ( $analysis_data['action_plan']['short_term_milestones'] ?? $analysis_data['next_steps']['short_term'] ?? [] ) ),
	'long_term_objectives'  => array_map( 'sanitize_text_field', ( $analysis_data['action_plan']['long_term_objectives'] ?? $analysis_data['next_steps']['long_term'] ?? [] ) ),
	],
	'vendor_considerations' => [
	'evaluation_criteria'   => array_map( 'sanitize_text_field', $analysis_data['vendor_considerations']['evaluation_criteria'] ?? [] ),
	'due_diligence_areas'   => array_map( 'sanitize_text_field', $analysis_data['vendor_considerations']['due_diligence_areas'] ?? [] ),
'negotiation_priorities' => array_map( 'sanitize_text_field', $analysis_data['vendor_considerations']['negotiation_priorities'] ?? [] ),
],
];

$financial_benchmarks = $analysis_data['financial_benchmarks'] ?? ( $analysis_data['research']['financial'] ?? array() );
if ( ! empty( $financial_benchmarks ) ) {
$analysis['financial_benchmarks'] = [
'industry_benchmarks' => array_map(
function( $bench ) {
return [
'metric' => sanitize_text_field( $bench['metric'] ?? '' ),
'value'  => sanitize_text_field( $bench['value'] ?? '' ),
'source' => sanitize_text_field( $bench['source'] ?? '' ),
];
},
$financial_benchmarks['industry_benchmarks'] ?? []
),
'valuation_multiples' => array_map(
function( $mult ) {
return [
'metric' => sanitize_text_field( $mult['metric'] ?? '' ),
'range'  => sanitize_text_field( $mult['range'] ?? '' ),
];
},
$financial_benchmarks['valuation_multiples'] ?? []
),
];
}

foreach ( (array) ( $analysis_data['operational_insights']['process_improvements'] ?? $analysis_data['operational_analysis']['process_improvements'] ?? [] ) as $item ) {
$analysis['operational_insights']['process_improvements'][] = [
'process_area'   => sanitize_text_field( $item['process'] ?? ( $item['process_area'] ?? '' ) ),
'current_state'  => sanitize_textarea_field( $item['current_state'] ?? '' ),
'improved_state' => sanitize_textarea_field( $item['improved_state'] ?? '' ),
'impact_level'   => sanitize_text_field( $item['impact'] ?? ( $item['impact_level'] ?? '' ) ),
];
}

foreach ( (array) ( $analysis_data['operational_insights']['automation_opportunities'] ?? $analysis_data['operational_analysis']['automation_opportunities'] ?? [] ) as $item ) {
$analysis['operational_insights']['automation_opportunities'][] = [
'opportunity'          => sanitize_text_field( $item['opportunity'] ?? '' ),
'complexity'           => sanitize_text_field( $item['complexity'] ?? '' ),
'time_savings'         => floatval( $item['potential_savings'] ?? ( $item['time_savings'] ?? 0 ) ),
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
	* Directly call the OpenAI API and return decoded data.
	*
	* @param array|string $prompt Prompt for the model.
	* @param string|null  $model  Optional model name.
	* @return array|WP_Error Decoded response array or error.
	*/
	public function call_openai_api( $prompt, $model = null ) {
	       $model    = $model ? sanitize_text_field( $model ) : $this->get_model( 'mini' );
	       $response = $this->transport->call_openai_with_retry( $model, $prompt );

	       if ( is_wp_error( $response ) ) {
	               return $response;
	       }

	       $response_body = $response['body'] ?? '';
	       $response_data = $this->response_parser->process_openai_response( $response_body );

	       if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
	               RTBCB_Logger::log(
	                       'openai_response_clean',
	                       [ 'response' => $response_data ]
	               );
	       }

	       return $response_data;
	}

	/**
	* Call OpenAI with retry logic.
	*
	* @param string       $model             Model name.
	* @param array|string $prompt            Prompt for the model.
	* @param int|null     $max_output_tokens Maximum output tokens.
	* @param int|null     $max_retries       Number of retries.
	* @param callable|null $chunk_handler    Optional streaming handler.
	* @return array|WP_Error Response array or error.
	*/
	private function call_openai_with_retry( $model, $prompt, $max_output_tokens = null, $max_retries = null, $chunk_handler = null ) {
		$prompt             = apply_filters( 'rtbcb_llm_final_prompt', $prompt, $model );
		$this->last_prompt = $prompt;
		return $this->transport->call_openai_with_retry( $model, $prompt, $max_output_tokens, $max_retries, $chunk_handler );
        }
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
	* @param array $response  HTTP response array from wp_remote_post().
	* @param bool  $store_raw Optional. Include full raw payload. Default false.
	* @return array|WP_Error {
	* @type string $output_text    Combined output text from the response.
	* @type array  $reasoning      Reasoning segments provided by the model.
	* @type array  $function_calls Function call items returned by the model.
	* @type array  $raw            Raw decoded response body.
	* @type bool   $truncated      Whether the response hit the token limit.
	* }
 */


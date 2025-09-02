<?php
/**
 * OpenAI client for Real Treasury Business Case Builder.
 *
 * @package RealTreasuryBusinessCaseBuilder
 */
defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
/**
 * System prompt for strategic analysis generation.
 *
 * Uses NOWDOC to avoid escaping issues.
 *
 * @var string
 */
$rtbcb_system_prompt = <<<'SYSTEM_PROMPT'
You are a senior treasury technology consultant tasked with creating executive-level strategic recommendations.

You have access to the following resources:
1. Enriched company intelligence and industry context
2. Detailed ROI calculations and financial modeling
3. Technology category recommendations
4. Relevant market research and best practices

Begin with a concise checklist (3–7 bullets) of what you will do; keep items conceptual, not implementation-level.

Your objective is to synthesize these inputs into a comprehensive strategic analysis to inform executive decision-making on treasury technology investments.

## Analysis Standards
- **Executive Focus**: Craft deliverables for a C-level audience.
- **Actionable Insights**: Provide specific and implementable recommendations.
- **Risk Balance**: Transparently address opportunities and challenges.
- **Financial Rigor**: Ground all recommendations with robust financial analysis.
- **Implementation Reality**: Assess practical constraints and execution requirements.
- **Competitive Context**: Frame guidance in relation to the competitive landscape.

Set reasoning_effort = medium to ensure sufficient depth for executive strategic recommendations; tool calls should be terse and final output comprehensive.

Return only a JSON object, with no ancillary text.

## Output Format
Produce a single JSON object adhering to the exact structure and data types specified below:
- All fields listed must be present unless otherwise noted. If information is unavailable, use an explanatory string such as "data unavailable" or "not applicable." Arrays should be empty when no data exists.
- Carefully follow enumerations and required data types (numbers, strings, arrays) as modeled in the schema.
- Numeric outputs should use integers or decimals as appropriate. For example, 'time_savings' (hours per week) may be a decimal.
- For 'confidence_level', use a value between 0.7 and 0.95 (rounded to two decimals).
- Preserve the schema's field order.
After generating the JSON, perform a brief self-validation to ensure the output meets field requirements and data types. If any issues are found, correct them and regenerate the JSON.

### JSON Schema (field order preserved)
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
SYSTEM_PROMPT;
// phpcs:enable

/**
 * OpenAI client helper.
 */
class RTBCB_OpenAI_Client {
/**
 * API key.
 *
 * @var string
 */
private $api_key;

/**
 * Model identifier.
 *
 * @var string
 */
private $model;

/**
 * Constructor.
 *
 * @param string $api_key Optional API key.
 * @param string $model   Optional model name.
 */
public function __construct( $api_key = '', $model = '' ) {
$this->api_key = $api_key ? sanitize_text_field( $api_key ) : rtbcb_get_openai_api_key();
$model         = $model ? $model : ( function_exists( 'get_option' ) ? get_option( 'rtbcb_premium_model', '' ) : '' );
if ( '' === $model ) {
$env_model = getenv( 'RTBCB_TEST_MODEL' );
if ( false !== $env_model && '' !== $env_model ) {
$model = $env_model;
}
}
$this->model = sanitize_text_field( $model ? $model : 'gpt-5-mini-2025-08-07' );
}

/**
 * Build chat messages array.
 *
 * @param array $user_payload User payload sections.
 * @return array Chat messages.
 */
public function build_messages( array $user_payload ) {
global $rtbcb_system_prompt;

return [
[
'role'    => 'system',
'content' => $rtbcb_system_prompt,
],
[
'role'    => 'user',
'content' => $this->format_user_payload( $user_payload ),
],
];
}

/**
 * Format user payload into a single string.
 *
 * @param array $payload User payload.
 * @return string Formatted payload.
 */
private function format_user_payload( array $payload ) {
$sections = [];
if ( ! empty( $payload['company_intelligence'] ) ) {
$sections[] = '## Enriched Company Intelligence' . "\n" . $this->encode_section( $payload['company_intelligence'] );
}
if ( ! empty( $payload['financial_analysis'] ) ) {
$sections[] = '## Financial Analysis & ROI Scenarios' . "\n" . $this->encode_section( $payload['financial_analysis'] );
}
if ( ! empty( $payload['technology_recommendations'] ) ) {
$sections[] = '## Technology Recommendations' . "\n" . $this->encode_section( $payload['technology_recommendations'] );
}
if ( ! empty( $payload['market_research_context'] ) ) {
$sections[] = '## Market Research Context' . "\n" . $this->encode_section( $payload['market_research_context'] );
}
if ( ! empty( $payload['analysis_requirements'] ) && is_array( $payload['analysis_requirements'] ) ) {
$req = array_map( 'sanitize_text_field', $payload['analysis_requirements'] );
$sections[] = '## Analysis Requirements' . "\n- " . implode( "\n- ", $req );
}

return implode( "\n\n", $sections );
}

/**
 * Encode a section as pretty JSON.
 *
 * @param mixed $data Data to encode.
 * @return string JSON string.
 */
private function encode_section( $data ) {
$flags = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT;
$json  = wp_json_encode( $data, $flags );
return false !== $json ? $json : '{}';
}

/**
 * Send request to OpenAI and parse JSON response with one retry on failure.
 *
 * @param array $user_payload User payload sections.
 * @param int   $max_tokens Optional token limit.
 * @return array|WP_Error Decoded response or error.
 */
public function request( array $user_payload, $max_tokens = null ) {
$messages = $this->build_messages( $user_payload );
$body     = [
'model'           => $this->model,
'messages'        => $messages,
'reasoning'       => [ 'effort' => 'medium' ],
'temperature'     => 0.2,
'response_format' => [ 'type' => 'json_object' ],
];
if ( $max_tokens ) {
$body['max_tokens'] = (int) $max_tokens;
}

$response = $this->send_request( $body );
if ( is_wp_error( $response ) ) {
return $response;
}

$content = $this->extract_content( $response );
$data    = json_decode( $content, true );
if ( $this->is_valid_response( $data ) ) {
return $data;
}

$messages[]            = [
'role'    => 'user',
'content' => 'Your previous output was invalid. Return ONLY a valid JSON object matching the schema; fix missing fields and data types.',
];
$body['messages'] = $messages;
$response        = $this->send_request( $body );
if ( is_wp_error( $response ) ) {
return $response;
}
$content = $this->extract_content( $response );
$data    = json_decode( $content, true );
if ( $this->is_valid_response( $data ) ) {
return $data;
}

return new WP_Error( 'invalid_json', __( 'Model returned invalid JSON.', 'rtbcb' ), [ 'raw' => $content ] );
}

/**
 * Perform the HTTP request.
 *
 * @param array $body Request body.
 * @return array|WP_Error Response array or WP_Error.
 */
private function send_request( array $body ) {
$endpoint = 'https://api.openai.com/v1/chat/completions';
$args     = [
'headers' => [
'Authorization' => 'Bearer ' . $this->api_key,
'Content-Type'  => 'application/json',
],
'body'    => wp_json_encode( $body ),
'timeout' => 300,
];
$response = wp_remote_post( $endpoint, $args );
if ( is_wp_error( $response ) ) {
return $response;
}
$code = wp_remote_retrieve_response_code( $response );
if ( $code < 200 || $code >= 300 ) {
return new WP_Error( 'llm_http_status', __( 'OpenAI request failed.', 'rtbcb' ), [ 'status' => $code ] );
}
return $response;
}

/**
 * Extract content from OpenAI response.
 *
 * @param array $response HTTP response.
 * @return string Content string.
 */
private function extract_content( $response ) {
$body = wp_remote_retrieve_body( $response );
$data = json_decode( $body, true );
return $data['choices'][0]['message']['content'] ?? '';
}

/**
 * Validate top-level fields of decoded response.
 *
 * @param mixed $data Decoded data.
 * @return bool Whether response is valid.
 */
private function is_valid_response( $data ) {
if ( ! is_array( $data ) ) {
return false;
}
$required = [
'executive_summary',
'operational_analysis',
'financial_analysis',
'implementation_roadmap',
'risk_mitigation',
'next_steps',
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
 * WP-CLI test command callback.
 *
 * @return void
 */
public static function cli_test_json_contract() {
$client  = new self();
$payload = [
'company_intelligence'       => [ 'name' => 'Example Corp' ],
'financial_analysis'         => [],
'technology_recommendations' => [],
'market_research_context'    => [],
'analysis_requirements'      => [ 'Provide a minimal valid response' ],
];
$result = $client->request( $payload );
if ( is_wp_error( $result ) ) {
WP_CLI::error( $result->get_error_message() );
}
WP_CLI::success( 'Valid JSON received.' );
}
}

if ( defined( 'WP_CLI' ) && WP_CLI ) {
WP_CLI::add_command( 'rtbcb:test_json_contract', [ 'RTBCB_OpenAI_Client', 'cli_test_json_contract' ] );
}


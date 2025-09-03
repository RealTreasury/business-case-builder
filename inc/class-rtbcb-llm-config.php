<?php
defined( 'ABSPATH' ) || exit;

/**
 * Configuration helper for LLM integration.
 *
 * @package RealTreasuryBusinessCaseBuilder
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers.php';

class RTBCB_LLM_Config {
/**
 * OpenAI API key.
 *
 * @var string
 */
private $api_key;

/**
 * GPT-5 configuration settings.
 *
 * @var array
 */
private $gpt5_config;

/**
 * Constructor.
 */
public function __construct() {
$this->api_key = rtbcb_get_openai_api_key();

$timeout           = rtbcb_get_api_timeout();
$max_output_tokens = intval( function_exists( 'get_option' ) ? get_option( 'rtbcb_gpt5_max_output_tokens', 8000 ) : 8000 );
$this->gpt5_config = rtbcb_get_gpt5_config(
array_merge(
function_exists( 'get_option' ) ? get_option( 'rtbcb_gpt5_config', [] ) : [],
[
'timeout'           => $timeout,
'max_output_tokens' => $max_output_tokens,
]
)
);
}

/**
 * Get the configured API key.
 *
 * @return string API key.
 */
public function get_api_key() {
return $this->api_key;
}

/**
 * Get GPT-5 configuration settings.
 *
 * @return array Configuration array.
 */
public function get_gpt5_config() {
return $this->gpt5_config;
}

/**
 * Get the configured model for a tier.
 *
 * @param string $tier Model tier.
 * @return string Model name.
 */
public function get_model( $tier ) {
$tier    = sanitize_key( $tier );
$default = rtbcb_get_default_model( $tier );
$model_option = function_exists( 'get_option' ) ? get_option( "rtbcb_{$tier}_model", $default ) : $default;
return function_exists( 'sanitize_text_field' ) ? sanitize_text_field( $model_option ) : $model_option;
}

/**
 * Estimate token usage from a desired word count.
 *
 * @param int $words Desired word count.
 * @return int Estimated token count.
 */
public function estimate_tokens( $words ) {
$words      = max( 0, intval( $words ) );
$tokens     = (int) ceil( $words * 1.5 );
$limit      = intval( $this->gpt5_config['max_output_tokens'] ?? 8000 );
$min_tokens = intval( $this->gpt5_config['min_output_tokens'] ?? 1 );
$limit      = min( 128000, max( $min_tokens, $limit ) );

return max( $min_tokens, min( $tokens, $limit ) );
}

/**
 * Determine token limit for a report type.
 *
 * @param string $type Report type identifier.
 * @return int Estimated token count for the report.
 */
public function tokens_for_report( $type ) {
$targets = [
'business_case'               => 600,
'industry_commentary'         => 60,
'company_overview'            => 400,
'industry_overview'           => 400,
'treasury_tech_overview'      => 400,
'real_treasury_overview'      => 400,
'category_recommendation'     => 200,
'benefits_estimate'           => 200,
'comprehensive_business_case' => 2000,
'competitive_context'         => 200,
'industry_analysis'           => 400,
'tech_research'               => 400,
];

$words = $targets[ $type ] ?? 800;

return $this->estimate_tokens( $words );
}
}

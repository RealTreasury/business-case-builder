<?php
defined( 'ABSPATH' ) || exit;

/**
 * API transport helper for LLM.
 *
 * @package RealTreasuryBusinessCaseBuilder
 */
require_once __DIR__ . '/helpers.php';

class RTBCB_LLM_Transport {
/**
 * API key.
 *
 * @var string
 */
private $api_key;

/**
 * GPT-5 configuration.
 *
 * @var array
 */
private $gpt5_config;

/**
 * Last request body.
 *
 * @var array|null
 */
private $last_request;

/**
 * Last response from API.
 *
 * @var array|WP_Error|null
 */
private $last_response;

/**
 * Constructor.
 *
 * @param RTBCB_LLM_Config $config Configuration instance.
 */
public function __construct( RTBCB_LLM_Config $config ) {
$this->api_key     = $config->get_api_key();
$this->gpt5_config = $config->get_gpt5_config();
}

/**
 * Get last request body.
 *
 * @return array|null
 */
public function get_last_request() {
return $this->last_request;
}

/**
 * Get last response.
 *
 * @return array|WP_Error|null
 */
public function get_last_response() {
return $this->last_response;
}

/**
 * Call OpenAI Responses API.
 *
 * @param string       $model             Model name.
 * @param array|string $prompt            Prompt data.
 * @param int|null     $max_output_tokens Optional max output tokens.
 * @param int|null     $max_retries       Optional retries.
 * @param callable|null $chunk_handler    Optional streaming handler.
 * @return array|WP_Error Response array or WP_Error.
 */
public function call_openai_with_retry( $model, $prompt, $max_output_tokens = null, $max_retries = null, $chunk_handler = null ) {
if ( rtbcb_heavy_features_disabled() ) {
return new WP_Error( 'heavy_features_disabled', __( 'AI features temporarily disabled.', 'rtbcb' ) );
}

if ( empty( $this->api_key ) ) {
return new WP_Error( 'no_api_key', __( 'OpenAI API key not configured.', 'rtbcb' ) );
}

$input = is_array( $prompt ) ? ( $prompt['input'] ?? '' ) : $prompt;
if ( '' === trim( (string) $input ) ) {
return new WP_Error( 'empty_prompt', __( 'Prompt cannot be empty.', 'rtbcb' ) );
}

$endpoint = 'https://api.openai.com/v1/responses';
$body     = is_array( $prompt ) ? $prompt : [ 'input' => $prompt ];
$body['model'] = sanitize_text_field( $model ?: 'gpt-5-mini' );
if ( $max_output_tokens ) {
$body['max_output_tokens'] = intval( $max_output_tokens );
}

$args = [
'headers' => [
'Authorization' => 'Bearer ' . $this->api_key,
'Content-Type'  => 'application/json',
],
'body'    => wp_json_encode( $body ),
'timeout' => intval( $this->gpt5_config['timeout'] ?? 300 ),
];

$this->last_request  = $body;
$response            = function_exists( 'wp_remote_post' ) ? wp_remote_post( $endpoint, $args ) : new WP_Error( 'http_unavailable', __( 'HTTP transport unavailable.', 'rtbcb' ) );
$this->last_response = $response;

return $response;
}
}

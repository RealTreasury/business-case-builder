<?php
defined( 'ABSPATH' ) || exit;

/**
 * Parse OpenAI Responses and business case JSON.
 *
 * @package RealTreasuryBusinessCaseBuilder
 */
require_once __DIR__ . '/config.php';

class RTBCB_Response_Parser {
/**
 * Parse raw OpenAI response.
 *
 * @param array|WP_Error $response Raw response from wp_remote_post.
 * @param bool $store_raw Optional. Whether to include decoded body.
 * @return array|WP_Error Parsed data or WP_Error.
 */
public function parse( $response, $store_raw = false ) {
if ( is_wp_error( $response ) ) {
return $response;
}

if ( ! is_array( $response ) ) {
return [
'output_text'    => '',
'reasoning'      => [],
'function_calls' => [],
'raw'            => [],
'truncated'      => false,
];
}

$body    = wp_remote_retrieve_body( $response );
$decoded = json_decode( $body, true );

if ( ! is_array( $decoded ) ) {
       rtbcb_log_error(
               'Malformed JSON response',
               [
                       'component' => 'response_parser',
                       'operation' => 'parse',
               ]
       );
       return [
               'output_text'    => '',
               'reasoning'      => [],
               'function_calls' => [],
               'raw'            => $store_raw ? $body : [],
               'truncated'      => false,
       ];
}

$output_text    = '';
$reasoning      = [];
$function_calls = [];
$truncated      = false;

if ( isset( $decoded['output_text'] ) && ! empty( trim( $decoded['output_text'] ) ) ) {
$output_text = trim( $decoded['output_text'] );

if ( strlen( $output_text ) < 20 ||
false !== stripos( $output_text, 'pong' ) ||
false !== stripos( $output_text, 'how can I help' ) ) {
       rtbcb_log_error(
               'Detected trivial response',
               [
                       'component' => 'response_parser',
                       'response'  => $output_text,
               ]
       );
       $output_text = '';
}
}

if ( empty( $output_text ) && isset( $decoded['output'] ) && is_array( $decoded['output'] ) ) {
foreach ( $decoded['output'] as $chunk ) {
if ( ! is_array( $chunk ) || 'message' !== ( $chunk['type'] ?? '' ) ) {
continue;
}

if ( isset( $chunk['content'] ) && is_array( $chunk['content'] ) ) {
foreach ( $chunk['content'] as $piece ) {
if ( isset( $piece['text'] ) && ! empty( trim( $piece['text'] ) ) ) {
$candidate = trim( $piece['text'] );

if ( strlen( $candidate ) >= 20 &&
false === stripos( $candidate, 'pong' ) ) {
$output_text = $candidate;
break 2;
}
}
}
}
}

foreach ( $decoded['output'] as $chunk ) {
$type = $chunk['type'] ?? '';

if ( 'reasoning' === $type && isset( $chunk['content'] ) && is_array( $chunk['content'] ) ) {
foreach ( $chunk['content'] as $piece ) {
if ( isset( $piece['text'] ) && ! empty( $piece['text'] ) ) {
$reasoning[] = $piece['text'];
}
}
}

if ( 'function_call' === $type ) {
$function_calls[] = $chunk;
}
}
}

$usage         = $decoded['usage'] ?? [];
$output_tokens = $usage['output_tokens'] ?? 0;
$config        = rtbcb_get_gpt5_config();
if ( 'incomplete' === ( $decoded['status'] ?? '' ) || ( ! empty( $output_tokens ) && $output_tokens >= $config['max_output_tokens'] ) ) {
       $truncated = true;
       rtbcb_log_error(
               'OpenAI response truncated',
               [
                       'component'     => 'response_parser',
                       'output_tokens' => $output_tokens,
               ]
       );
}

RTBCB_Logger::log(
       'parsed_response',
       [
               'text_length'     => strlen( $output_text ),
               'output_tokens'   => $output_tokens,
               'reasoning_chunks' => count( $reasoning ),
       ]
);

return [
'output_text'    => $output_text,
'reasoning'      => $reasoning,
'function_calls' => $function_calls,
'raw'            => $store_raw ? $decoded : [],
'truncated'      => $truncated,
];
}

/**
 * Parse and sanitize business case JSON.
 *
 * @param array|WP_Error $response Raw response from OpenAI.
 * @return array|WP_Error Sanitized business case or WP_Error on failure.
 */
public function parse_business_case( $response ) {
$parsed = $this->parse( $response, true );
if ( is_wp_error( $parsed ) ) {
return $parsed;
}

$content = $parsed['output_text'];
$json    = is_string( $content ) ? json_decode( $content, true ) : ( is_array( $content ) ? $content : [] );

if ( ! is_array( $json ) ) {
return new WP_Error( 'llm_response_parse_error', __( 'Invalid JSON from language model.', 'rtbcb' ) );
}

$required = [
'executive_summary',
'company_intelligence',
'operational_insights',
'risk_analysis',
'action_plan',
'industry_insights',
'technology_strategy',
'financial_analysis',
];

foreach ( $required as $section ) {
if ( ! isset( $json[ $section ] ) || ! is_array( $json[ $section ] ) ) {
return new WP_Error(
'llm_missing_section',
sprintf( __( 'Missing required section: %s', 'rtbcb' ), $section )
);
}
}

return array_map( [ $this, 'sanitize_value' ], $json );
}

/**
 * Recursively sanitize values and cast numerics.
 *
 * @param mixed $value Value to sanitize.
 * @return mixed
 */
private function sanitize_value( $value ) {
if ( is_array( $value ) ) {
return array_map( [ $this, 'sanitize_value' ], $value );
}

if ( is_numeric( $value ) ) {
return 0 + $value;
}

return sanitize_text_field( (string) $value );
}
}

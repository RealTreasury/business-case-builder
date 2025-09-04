<?php
defined( 'ABSPATH' ) || exit;

/**
 * Handle OpenAI responses including parsing and integrity checks.
 *
 * @package RealTreasuryBusinessCaseBuilder
 */
require_once __DIR__ . '/config.php';

class RTBCB_Response_Handler {
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
 * Process raw OpenAI response body.
 *
 * @param string $response_body Raw response.
 * @return array|string|false Parsed content or false on failure.
 */
public function process_openai_response( $response_body ) {
// Log the raw response for debugging.
if ( class_exists( 'RTBCB_Logger' ) ) {
RTBCB_Logger::log(
'raw_api_response',
[ 'snippet' => substr( $response_body, 0, 500 ) ]
);
}

if ( empty( $response_body ) ) {
if ( function_exists( 'rtbcb_log_error' ) ) {
rtbcb_log_error(
'Empty response body received',
[ 'operation' => 'process_openai_response' ]
);
} else {
error_log( 'Empty response body received' );
}
return false;
}

$response_body = trim( $response_body );
$response_body = preg_replace( '/\x{FEFF}/u', '', $response_body );

if ( function_exists( 'wp_unslash' ) ) {
$response_body = wp_unslash( $response_body );
}

if ( function_exists( 'mb_detect_encoding' ) ) {
$encoding = mb_detect_encoding( $response_body, [ 'UTF-8', 'ISO-8859-1', 'ASCII' ], true );
if ( $encoding && 'UTF-8' !== $encoding ) {
if ( function_exists( 'mb_convert_encoding' ) ) {
$converted = mb_convert_encoding( $response_body, 'UTF-8', $encoding );
if ( false !== $converted ) {
$response_body = $converted;
}
}
}
}

$decoded    = json_decode( $response_body, true );
$json_error = json_last_error();

if ( JSON_ERROR_NONE === $json_error && is_array( $decoded ) ) {
if ( class_exists( 'RTBCB_Logger' ) ) {
RTBCB_Logger::log( 'json_decode_first_attempt_success' );
}
return $this->extract_content_from_decoded_response( $decoded );
}

if ( function_exists( 'rtbcb_log_error' ) ) {
rtbcb_log_error(
'JSON decode failed',
[ 'error' => json_last_error_msg() ]
);
} else {
error_log( 'JSON decode failed: ' . json_last_error_msg() );
}

if ( preg_match( '/```(?:json)?\s*(\{.*\})\s*```/s', $response_body, $matches ) ) {
$json_content = trim( $matches[1] );
$decoded      = json_decode( $json_content, true );
if ( JSON_ERROR_NONE === json_last_error() && is_array( $decoded ) ) {
if ( class_exists( 'RTBCB_Logger' ) ) {
RTBCB_Logger::log( 'json_extracted_from_markdown' );
}
return $this->extract_content_from_decoded_response( $decoded );
}
}

if ( preg_match( '/\{.*\}/s', $response_body, $matches ) ) {
$json_content = $matches[0];
$decoded      = json_decode( $json_content, true );
if ( JSON_ERROR_NONE === json_last_error() && is_array( $decoded ) ) {
if ( class_exists( 'RTBCB_Logger' ) ) {
RTBCB_Logger::log( 'json_extracted_from_mixed_content' );
}
return $this->extract_content_from_decoded_response( $decoded );
}
}

if ( $this->is_streaming_response( $response_body ) ) {
return $this->parse_streaming_response( $response_body );
}

if ( function_exists( 'rtbcb_log_error' ) ) {
rtbcb_log_error(
'All JSON parsing attempts failed',
[ 'snippet' => substr( $response_body, 0, 200 ) ]
);
} else {
error_log( 'All JSON parsing attempts failed: ' . substr( $response_body, 0, 200 ) );
}
return false;
}

/**
 * Extract content from decoded response.
 *
 * @param array $decoded Decoded JSON.
 * @return array|string Parsed content.
 */
private function extract_content_from_decoded_response( $decoded ) {
if ( isset( $decoded['choices'][0]['message']['content'] ) ) {
$content = $decoded['choices'][0]['message']['content'];
if ( is_string( $content ) ) {
if ( $this->looks_like_json( $content ) ) {
$inner = json_decode( $content, true );
if ( JSON_ERROR_NONE === json_last_error() ) {
return $inner;
}
}
return $content;
}
}

if ( isset( $decoded['output'] ) && is_array( $decoded['output'] ) ) {
foreach ( $decoded['output'] as $chunk ) {
if ( 'message' === ( $chunk['type'] ?? '' ) && isset( $chunk['content'] ) ) {
if ( is_array( $chunk['content'] ) ) {
foreach ( $chunk['content'] as $piece ) {
if ( isset( $piece['text'] ) ) {
$text = $piece['text'];
if ( $this->looks_like_json( $text ) ) {
$inner = json_decode( $text, true );
if ( JSON_ERROR_NONE === json_last_error() ) {
return $inner;
}
}
return $text;
}
}
}
}
}
}

if ( isset( $decoded['output_text'] ) ) {
$content = $decoded['output_text'];
if ( $this->looks_like_json( $content ) ) {
$inner = json_decode( $content, true );
if ( JSON_ERROR_NONE === json_last_error() ) {
return $inner;
}
}
return $content;
}

return $decoded;
}

/**
 * Check if content looks like JSON.
 *
 * @param string $content Content string.
 * @return bool Whether content appears JSON.
 */
private function looks_like_json( $content ) {
$content = trim( $content );
return ( substr( $content, 0, 1 ) === '{' && substr( $content, -1 ) === '}' ) ||
( substr( $content, 0, 1 ) === '[' && substr( $content, -1 ) === ']' );
}

/**
 * Check if response is streaming format.
 *
 * @param string $response_body Body string.
 * @return bool Streaming detected.
 */
private function is_streaming_response( $response_body ) {
return preg_match( '/^\s*(?:for\s*\(\s*;;\s*\);\s*)?(?:data|event):\s/m', $response_body );
}

/**
 * Parse streaming response format.
 *
 * @param string $response_body Body string.
 * @return array|string|false Parsed content or false.
 */
private function parse_streaming_response( $response_body ) {
$lines          = preg_split( "/\r?\n/", $response_body );
$output_text    = '';
$reasoning      = [];
$function_calls = [];
$final_response = null;

foreach ( $lines as $line ) {
$line = trim( $line );
if ( '' === $line ) {
continue;
}

if ( 0 === strpos( $line, 'for' ) ) {
$line = preg_replace( '/^for\s*\(\s*;;\s*\);\s*/', '', $line );
$line = ltrim( $line );
}

if ( '' === $line || 0 !== strpos( $line, 'data:' ) ) {
continue;
}

$payload = trim( substr( $line, 5 ) );
if ( '' === $payload ) {
continue;
}
if ( '[DONE]' === $payload ) {
break;
}

$decoded = json_decode( $payload, true );
if ( JSON_ERROR_NONE !== json_last_error() ) {
continue;
}

if ( isset( $decoded['type'] ) ) {
switch ( $decoded['type'] ) {
case 'response.done':
case 'response.content_part.done':
case 'response.output_text.done':
if ( isset( $decoded['response'] ) ) {
$final_response = $decoded['response'];
}
break;
case 'response.content_part.delta':
case 'response.output_text.delta':
if ( isset( $decoded['delta']['text'] ) ) {
$output_text .= $decoded['delta']['text'];
}
break;
case 'response.reasoning.delta':
if ( isset( $decoded['delta']['text'] ) ) {
$reasoning[] = $decoded['delta']['text'];
}
break;
}
} else {
if ( isset( $decoded['choices'][0]['delta']['content'] ) ) {
$output_text .= $decoded['choices'][0]['delta']['content'];
} elseif ( isset( $decoded['choices'][0]['message'] ) ) {
$final_response = $decoded;
}
}
}

if ( $final_response ) {
if ( isset( $final_response['output_text'] ) && '' === $output_text ) {
$output_text = trim( (string) $final_response['output_text'] );
}

if ( isset( $final_response['output'] ) && is_array( $final_response['output'] ) ) {
foreach ( $final_response['output'] as $chunk ) {
$type = $chunk['type'] ?? '';

if ( 'message' === $type && isset( $chunk['content'] ) && is_array( $chunk['content'] ) ) {
foreach ( $chunk['content'] as $piece ) {
if ( isset( $piece['text'] ) && ! empty( trim( $piece['text'] ) ) ) {
$output_text = trim( $piece['text'] );
break 2;
}
}
}

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

return [
'output_text'    => $output_text,
'reasoning'      => $reasoning,
'function_calls' => $function_calls,
'raw'            => $final_response,
'truncated'      => false,
];
}

if ( $output_text || $reasoning ) {
return [
'output_text'    => $output_text,
'reasoning'      => $reasoning,
'function_calls' => [],
'raw'            => [],
'truncated'      => false,
];
}

return false;
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

	if ( is_array( $json ) ) {
		foreach ( [ 'analysis', 'report_data' ] as $wrapper ) {
			if ( isset( $json[ $wrapper ] ) && is_array( $json[ $wrapper ] ) ) {
				$json = $json[ $wrapper ];
				break;
			}
		}
	}

	if ( ! is_array( $json ) ) {
		return new WP_Error( 'llm_response_parse_error', __( 'Invalid JSON from language model.', 'rtbcb' ) );
	}

$required = [
'executive_summary',
'company_intelligence',
'operational_insights',
'risk_analysis',
'action_plan',
'financial_benchmarks',
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

/**
 * Validate JSON structure integrity.
 *
 * @param string $response JSON response string.
 * @return bool True when valid JSON, false otherwise.
 */
public static function validate_response( $response ) {
$response = function_exists( 'wp_unslash' ) ? wp_unslash( $response ) : $response;
json_decode( $response );
$valid = JSON_ERROR_NONE === json_last_error();

if ( class_exists( 'RTBCB_Logger' ) ) {
RTBCB_Logger::log(
'response_validate',
[
'valid'  => $valid,
'length' => strlen( $response ),
]
);
}

return $valid;
}

/**
 * Detect corruption patterns between stored and original responses.
 *
 * @param string $stored_response   Stored response string.
 * @param string $original_response Original response string.
 * @return array{corrupted:bool,issues:array}
 */
public static function detect_corruption( $stored_response, $original_response ) {
$issues = [];

json_decode( $stored_response );
if ( JSON_ERROR_NONE !== json_last_error() ) {
$issues[] = 'invalid_json';
}

if ( $stored_response !== $original_response ) {
$issues[] = 'mismatch';
}

$result = [
'corrupted' => ! empty( $issues ),
'issues'    => $issues,
];

if ( class_exists( 'RTBCB_Logger' ) ) {
RTBCB_Logger::log( 'response_detect_corruption', $result );
}

return $result;
}

/**
 * Attempt automatic repair of minor JSON issues.
 *
 * @param string $corrupted_response Possibly corrupted JSON.
 * @return string Repaired JSON string or original when unrepaired.
 */
public static function repair_response( $corrupted_response ) {
$attempts = [
$corrupted_response,
str_replace( ',}', '}', $corrupted_response ),
str_replace( ',]', ']', $corrupted_response ),
];

foreach ( $attempts as $attempt ) {
json_decode( $attempt );
if ( JSON_ERROR_NONE === json_last_error() ) {
if ( class_exists( 'RTBCB_Logger' ) ) {
RTBCB_Logger::log(
'response_repair_success',
[
'original_length' => strlen( $corrupted_response ),
'repaired_length' => strlen( $attempt ),
]
);
}
return wp_json_encode( json_decode( $attempt, true ) );
}
}

if ( class_exists( 'RTBCB_Logger' ) ) {
RTBCB_Logger::log( 'response_repair_failed', [ 'length' => strlen( $corrupted_response ) ] );
}

return $corrupted_response;
}

/**
 * Generate integrity report for a log entry.
 *
 * @param int $log_id Log identifier.
 * @return array Report details.
 */
public static function generate_integrity_report( $log_id ) {
$log_id = intval( $log_id );
if ( $log_id <= 0 || ! class_exists( 'RTBCB_API_Log' ) ) {
return [];
}

$logs  = RTBCB_API_Log::get_logs( 200 );
$entry = null;
foreach ( $logs as $log ) {
if ( intval( $log['id'] ) === $log_id ) {
$entry = $log;
break;
}
}

if ( ! $entry ) {
return [];
}

$result               = self::detect_corruption( $entry['response_json'], $entry['response_json'] );
$result['log_id']     = $log_id;
$result['valid_json'] = self::validate_response( $entry['response_json'] );

if ( class_exists( 'RTBCB_Logger' ) ) {
RTBCB_Logger::log( 'response_integrity_report', $result );
}

return $result;
}

/**
 * Re-process corrupted historical data.
 *
 * @param callable $callback Callback invoked with (log, repaired_json).
 * @param int      $limit    Number of logs to inspect.
 * @return int Number of logs reprocessed.
 */
public static function reprocess_historical_data( callable $callback, $limit = 100 ) {
if ( ! class_exists( 'RTBCB_API_Log' ) ) {
return 0;
}

$logs  = RTBCB_API_Log::get_logs( $limit );
$count = 0;

foreach ( $logs as $log ) {
$check = self::detect_corruption( $log['response_json'], $log['response_json'] );
if ( $check['corrupted'] ) {
$repaired = self::repair_response( $log['response_json'] );
call_user_func( $callback, $log, $repaired );
$count++;
}
}

if ( class_exists( 'RTBCB_Logger' ) ) {
RTBCB_Logger::log(
'response_reprocess',
[
'processed' => $count,
'limit'     => $limit,
]
);
}

return $count;
}
}

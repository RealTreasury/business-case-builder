<?php
defined( 'ABSPATH' ) || exit;

/**
 * Response parsing helper for LLM.
 *
 * @package RealTreasuryBusinessCaseBuilder
 */
class RTBCB_LLM_Response_Parser {
/**
 * Process raw OpenAI response body.
 *
 * @param string $response_body Raw response.
 * @return array|string|false Parsed content or false on failure.
 */
public function process_openai_response( $response_body ) {
// Log the raw response for debugging.
error_log( 'RTBCB: Raw API response: ' . substr( $response_body, 0, 500 ) . '...' );

if ( empty( $response_body ) ) {
error_log( 'RTBCB: Empty response body received' );
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
error_log( 'RTBCB: JSON decoded successfully on first attempt' );
return $this->extract_content_from_decoded_response( $decoded );
}

error_log( 'RTBCB: JSON decode failed: ' . json_last_error_msg() );

if ( preg_match( '/```(?:json)?\s*(\{.*\})\s*```/s', $response_body, $matches ) ) {
$json_content = trim( $matches[1] );
$decoded      = json_decode( $json_content, true );
if ( JSON_ERROR_NONE === json_last_error() && is_array( $decoded ) ) {
error_log( 'RTBCB: JSON extracted from markdown successfully' );
return $this->extract_content_from_decoded_response( $decoded );
}
}

if ( preg_match( '/\{.*\}/s', $response_body, $matches ) ) {
$json_content = $matches[0];
$decoded      = json_decode( $json_content, true );
if ( JSON_ERROR_NONE === json_last_error() && is_array( $decoded ) ) {
error_log( 'RTBCB: JSON extracted from mixed content successfully' );
return $this->extract_content_from_decoded_response( $decoded );
}
}

if ( $this->is_streaming_response( $response_body ) ) {
return $this->parse_streaming_response( $response_body );
}

error_log( 'RTBCB: All JSON parsing attempts failed for response: ' . substr( $response_body, 0, 200 ) );
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
return strpos( $response_body, 'data: {' ) !== false || strpos( $response_body, 'event: ' ) !== false;
}

/**
 * Parse streaming response format.
 *
 * @param string $response_body Body string.
 * @return array|string|false Parsed content or false.
 */
private function parse_streaming_response( $response_body ) {
$lines     = explode( "\n", $response_body );
$json_data = '';

foreach ( $lines as $line ) {
$line = trim( $line );
if ( strpos( $line, 'data: {' ) === 0 ) {
$json_data = substr( $line, 6 );
break;
}
}

if ( $json_data ) {
$decoded = json_decode( $json_data, true );
if ( JSON_ERROR_NONE === json_last_error() ) {
return $this->extract_content_from_decoded_response( $decoded );
}
}

return false;
}
}

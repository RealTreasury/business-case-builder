<?php
defined( 'ABSPATH' ) || exit;

/**
 * Prompt assembly helper for LLM.
 *
 * @package RealTreasuryBusinessCaseBuilder
 */
class RTBCB_LLM_Prompt {
/**
 * Build context array for Responses API.
 *
 * @param array       $history       Message history.
 * @param string|null $system_prompt Optional system prompt.
 * @return array Context array.
 */
public function build_context_for_responses( $history, $system_prompt = null ) {
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
}

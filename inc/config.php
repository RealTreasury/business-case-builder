<?php
/**
 * GPT-5 configuration defaults.
 *
 * @package RealTreasuryBusinessCaseBuilder
 */

/**
 * Retrieve GPT-5 configuration defaults.
 *
 * @return array GPT-5 configuration.
 */
function rtbcb_get_gpt5_config() {
    $defaults = [
        'model'       => 'gpt-5-mini',
        'max_tokens'  => 4000,
        'text'        => [ 'verbosity' => 'medium' ],
        'temperature' => 0.7,
        'store'       => true,
        'timeout'     => 120,
        'max_retries' => 2,
    ];

    $defaults['model'] = sanitize_text_field( get_option( 'rtbcb_advanced_model', 'gpt-5-mini' ) );

    return $defaults;
}

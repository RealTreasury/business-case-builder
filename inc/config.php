<?php
/**
 * GPT-5 configuration defaults.
 *
 * @package RealTreasuryBusinessCaseBuilder
 */

/**
 * Retrieve GPT-5 configuration defaults.
 *
 * @param array $overrides Optional overrides.
 * @return array GPT-5 configuration.
 */
function rtbcb_get_gpt5_config( $overrides = [] ) {
    $defaults = [
        'model'            => 'gpt-5-mini',
        'max_output_tokens' => 4000,
        'text'             => [ 'verbosity' => 'medium' ],
        'temperature'      => 0.7,
        'store'            => true,
        'timeout'          => 120,
        'max_retries'      => 2,
    ];

    if ( isset( $overrides['max_completion_tokens'] ) && ! isset( $overrides['max_output_tokens'] ) ) {
        $overrides['max_output_tokens'] = $overrides['max_completion_tokens'];
        unset( $overrides['max_completion_tokens'] );
    }

    if ( is_array( $overrides ) ) {
        $defaults = array_merge( $defaults, array_intersect_key( $overrides, $defaults ) );
    }

    $defaults['model'] = sanitize_text_field( get_option( 'rtbcb_advanced_model', 'gpt-5-mini' ) );

    return $defaults;
}


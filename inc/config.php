<?php
/**
 * Configuration defaults for Real Treasury Business Case Builder.
 *
 * @package RealTreasuryBusinessCaseBuilder
 */

/**
 * Retrieve the default model for a given tier.
 *
 * Centralizes model defaults so they can be overridden in one place.
 *
 * @param string $tier Model tier identifier.
 * @return string Default model name.
 */
function rtbcb_get_default_model( $tier ) {
    $defaults = [
        'mini'      => 'gpt-4o-mini',
        'premium'   => 'gpt-4o',
        'advanced'  => 'gpt-5-mini',
        'gpt5_mini' => 'gpt-5-mini',
        'embedding' => 'text-embedding-3-small',
    ];

    $tier = sanitize_key( $tier );

    return $defaults[ $tier ] ?? '';
}

/**
 * Retrieve GPT-5 configuration defaults.
 *
 * @param array $overrides Optional overrides.
 * @return array GPT-5 configuration.
 */
function rtbcb_get_gpt5_config( $overrides = [] ) {
    $defaults = [
        'model'            => rtbcb_get_default_model( 'gpt5_mini' ),
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

    $defaults['model'] = sanitize_text_field( get_option( 'rtbcb_advanced_model', rtbcb_get_default_model( 'advanced' ) ) );

    return $defaults;
}


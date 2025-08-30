<?php
defined( 'ABSPATH' ) || exit;

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
        'model'             => 'gpt-5-mini',
        'max_output_tokens' => 8000,
        'temperature'       => 0.7,
        'store'             => true,
        'timeout'           => 180,
        'max_retries'       => 2,
        'max_retry_time'    => 60,
        'reasoning_effort'  => 'medium',
        'text_verbosity'    => 'medium',
    ];

    $file_overrides = [];
    $config_path    = dirname( __DIR__ ) . '/rtbcb-config.json';
    if ( file_exists( $config_path ) ) {
        $decoded = json_decode( file_get_contents( $config_path ), true );
        if ( is_array( $decoded ) && isset( $decoded['max_output_tokens'] ) ) {
            $file_overrides['max_output_tokens'] = $decoded['max_output_tokens'];
        }
    }

    $env_tokens = getenv( 'RTBCB_MAX_OUTPUT_TOKENS' );
    if ( false !== $env_tokens ) {
        $file_overrides['max_output_tokens'] = $env_tokens;
    }

    $option_tokens = get_option( 'rtbcb_gpt5_max_output_tokens', false );
    if ( false !== $option_tokens && '' !== $option_tokens ) {
        $file_overrides['max_output_tokens'] = $option_tokens;
    }

    $overrides = array_merge( $file_overrides, $overrides );

    $config = array_merge( $defaults, array_intersect_key( $overrides, $defaults ) );
    $config['max_output_tokens'] = min( 128000, max( 256, intval( $config['max_output_tokens'] ) ) );
    $config['max_retry_time']    = max( 1, intval( $config['max_retry_time'] ) );

    return $config;
}


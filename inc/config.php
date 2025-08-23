<?php
/**
 * GPT-5 configuration defaults.
 *
 * @package RealTreasuryBusinessCaseBuilder
 */

if ( ! defined( 'GPT5_CONFIG' ) ) {
    define(
        'GPT5_CONFIG',
        [
            'model'       => 'gpt-5-mini',
            'max_tokens'  => 4000,
            'reasoning'   => [ 'effort' => 'medium' ],
            'text'        => [ 'verbosity' => 'medium' ],
            'temperature' => 0.7,
            'store'       => true,
            'timeout'     => 120,
            'max_retries' => 2,
        ]
    );
}


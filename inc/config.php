<?php
/**
 * Configuration defaults for GPT-5 integration.
 *
 * @package RealTreasuryBusinessCaseBuilder
 */

if ( ! defined( 'ABSPATH' ) ) {
    // Allow direct access for testing environments.
    define( 'ABSPATH', __DIR__ . '/' );
}

if ( ! defined( 'GPT5_CONFIG' ) ) {
    define(
        'GPT5_CONFIG',
        [
            'model'       => 'gpt-5-chat-latest',
            'max_tokens'  => 4000,
            'reasoning'   => 4000,
            'text'        => 4000,
            'temperature' => null,
            'store'       => true,
            'timeout'     => 120,
            'max_retries' => 2,
        ]
    );
}


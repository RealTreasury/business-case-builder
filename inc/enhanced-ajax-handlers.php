<?php
/**
 * Enhanced AJAX helper functions.
 *
 * @package RealTreasuryBusinessCaseBuilder
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Prepare enhanced result payload for unified dashboard responses.
 *
 * @param array $overview Overview data.
 * @param array $debug    Optional debug information.
 * @return array
 */
function rtbcb_prepare_enhanced_result( $overview, $debug = [] ) {
    $overview = is_array( $overview ) ? $overview : [];

    return [
        'overview'        => $overview['analysis'] ?? '',
        'recommendations' => $overview['recommendations'] ?? [],
        'references'      => $overview['references'] ?? [],
        'debug'           => $debug,
    ];
}

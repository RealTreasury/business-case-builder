<?php
defined( 'ABSPATH' ) || exit;

/**
 * Minimal WordPress function polyfills for standalone execution.
 */

if ( ! function_exists( 'wp_json_encode' ) ) {
	/**
	 * Encode data as JSON.
	 *
	 * @param mixed $data Data to encode.
	 * @return string JSON encoded string.
	 */
	function wp_json_encode( $data ) {
		return json_encode( $data );
	}
}

if ( ! function_exists( 'absint' ) ) {
	/**
	 * Convert value to non-negative integer.
	 *
	 * @param mixed $maybeint Value to convert.
	 * @return int Non-negative integer.
	 */
	function absint( $maybeint ) {
		return abs( (int) $maybeint );
	}
}

if ( ! function_exists( 'esc_js' ) ) {
	/**
	 * Escape text for JavaScript output.
	 *
	 * @param string $text Text to escape.
	 * @return string Escaped text.
	 */
	function esc_js( $text ) {
		return addslashes( $text );
	}
}

if ( ! function_exists( 'size_format' ) ) {
	/**
	 * Format bytes into a human readable size.
	 *
	 * @param int $bytes	Size in bytes.
	 * @param int $decimals Optional precision.
	 * @return string Human readable size.
	 */
	function size_format( $bytes, $decimals = 0 ) {
		$units = [ 'B', 'KB', 'MB', 'GB', 'TB', 'PB' ];
		if ( $bytes <= 0 ) {
			return '0 B';
		}
		$factor = floor( ( strlen( (string) $bytes ) - 1 ) / 3 );
		return sprintf( '%.' . $decimals . 'f', $bytes / pow( 1024, $factor ) ) . ' ' . $units[ $factor ];
	}
}

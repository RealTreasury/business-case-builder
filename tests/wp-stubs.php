<?php
if ( ! defined( 'ABSPATH' ) ) {
        define( 'ABSPATH', __DIR__ . '/../' );
}

if ( ! isset( $GLOBALS['rtbcb_filters'] ) ) {
	$GLOBALS['rtbcb_filters'] = [];
}
if ( ! isset( $GLOBALS['rtbcb_actions'] ) ) {
	$GLOBALS['rtbcb_actions'] = [];
}
if ( ! isset( $GLOBALS['rtbcb_options'] ) ) {
	$GLOBALS['rtbcb_options'] = [];
}

if ( ! function_exists( 'add_filter' ) ) {
	function add_filter( $tag, $function_to_add, $priority = 10, $accepted_args = 1 ) {
		$GLOBALS['rtbcb_filters'][ $tag ][] = $function_to_add;
	}
}

if ( ! function_exists( 'apply_filters' ) ) {
	function apply_filters( $tag, $value ) {
		$args  = func_get_args();
		$tag   = array_shift( $args );
		$value = array_shift( $args );
		if ( ! empty( $GLOBALS['rtbcb_filters'][ $tag ] ) ) {
			foreach ( $GLOBALS['rtbcb_filters'][ $tag ] as $callback ) {
				$value = call_user_func_array( $callback, array_merge( [ $value ], $args ) );
			}
		}
		return $value;
	}
}

if ( ! function_exists( 'add_action' ) ) {
	function add_action( $tag, $function_to_add, $priority = 10, $accepted_args = 1 ) {
		$GLOBALS['rtbcb_actions'][ $tag ][] = $function_to_add;
	}
}

if ( ! function_exists( 'do_action' ) ) {
	function do_action( $tag ) {
		$args = func_get_args();
		$tag  = array_shift( $args );
		if ( ! empty( $GLOBALS['rtbcb_actions'][ $tag ] ) ) {
			foreach ( $GLOBALS['rtbcb_actions'][ $tag ] as $callback ) {
				call_user_func_array( $callback, $args );
			}
		}
	}
}

if ( ! function_exists( 'get_option' ) ) {
	function get_option( $option, $default = false ) {
		return $GLOBALS['rtbcb_options'][ $option ] ?? $default;
	}
}

if ( ! function_exists( 'update_option' ) ) {
	function update_option( $option, $value ) {
		$GLOBALS['rtbcb_options'][ $option ] = $value;
		return true;
	}
}

if ( ! function_exists( 'sanitize_text_field' ) ) {
	function sanitize_text_field( $text ) {
		$text = is_scalar( $text ) ? $text : '';
		$text = wp_unslash( $text );
		$text = strip_tags( $text );
		return trim( $text );
	}
}

if ( ! function_exists( 'sanitize_email' ) ) {
	function sanitize_email( $email ) {
		return filter_var( $email, FILTER_SANITIZE_EMAIL );
	}
}

if ( ! function_exists( 'wp_unslash' ) ) {
	function wp_unslash( $value ) {
		if ( is_array( $value ) ) {
			return array_map( 'wp_unslash', $value );
		}
		return stripslashes( (string) $value );
	}
}

<?php
defined( 'ABSPATH' ) || exit;
if ( ! function_exists( 'add_filter' ) ) {
    function add_filter( $tag, $function_to_add, $priority = 10, $accepted_args = 1 ) {}
}
if ( ! function_exists( 'apply_filters' ) ) {
    function apply_filters( $tag, $value ) {
        return $value;
    }
}
if ( ! function_exists( 'add_action' ) ) {
    function add_action( $tag, $function_to_add, $priority = 10, $accepted_args = 1 ) {}
}
if ( ! function_exists( 'do_action' ) ) {
    function do_action( $tag, ...$args ) {}
}
if ( ! function_exists( 'get_option' ) ) {
    function get_option( $option, $default = false ) {
        return $default;
    }
}
if ( ! function_exists( 'update_option' ) ) {
    function update_option( $option, $value ) {
        return true;
    }
}
if ( ! function_exists( 'sanitize_text_field' ) ) {
    function sanitize_text_field( $text ) {
        return is_string( $text ) ? trim( $text ) : '';
    }
}
if ( ! function_exists( 'sanitize_email' ) ) {
    function sanitize_email( $email ) {
        return filter_var( $email, FILTER_SANITIZE_EMAIL );
    }
}
if ( ! function_exists( 'wp_unslash' ) ) {
    function wp_unslash( $value ) {
        return $value;
    }
}

if ( ! class_exists( 'RTBCB_Category_Recommender' ) ) {
    class RTBCB_Category_Recommender {
        /**
         * Recommend a category based on inputs.
         *
         * @param array $inputs Input values.
         * @return array Recommended category.
         */
        public static function recommend_category( $inputs ) {
            return self::suggest_category( $inputs );
        }

        /**
         * Suggest a category for backward compatibility.
         *
         * @param array $inputs Input values.
         * @return array Recommended category.
         */
        public static function suggest_category( $inputs ) {
            return [ 'recommended' => 'general' ];
        }
    }
}

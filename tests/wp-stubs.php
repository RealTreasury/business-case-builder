<?php
if ( ! function_exists( 'add_filter' ) ) {
    $GLOBALS['rtbcb_filters'] = [];
    function add_filter( $tag, $function_to_add, $priority = 10, $accepted_args = 1 ) {
        $GLOBALS['rtbcb_filters'][ $tag ] = $function_to_add;
    }
}
if ( ! function_exists( 'apply_filters' ) ) {
    function apply_filters( $tag, $value ) {
        $args  = func_get_args();
        $tag   = array_shift( $args );
        $value = array_shift( $args );
        if ( isset( $GLOBALS['rtbcb_filters'][ $tag ] ) ) {
            $value = call_user_func_array( $GLOBALS['rtbcb_filters'][ $tag ], array_merge( [ $value ], $args ) );
        }
        return $value;
    }
}
if ( ! function_exists( 'add_action' ) ) {
    $GLOBALS['rtbcb_actions'] = [];
    function add_action( $tag, $function_to_add, $priority = 10, $accepted_args = 1 ) {
        $GLOBALS['rtbcb_actions'][ $tag ] = $function_to_add;
    }
}
if ( ! function_exists( 'do_action' ) ) {
    function do_action( $tag, ...$args ) {
        if ( isset( $GLOBALS['rtbcb_actions'][ $tag ] ) ) {
            call_user_func_array( $GLOBALS['rtbcb_actions'][ $tag ], $args );
        }
    }
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
        public static function suggest_category( $inputs ) {
            return 'general';
        }
    }
}

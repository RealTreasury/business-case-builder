<?php
defined( 'ABSPATH' ) || exit;
if ( ! defined( 'DOING_AJAX' ) ) {
        define( 'DOING_AJAX', true );
}

if ( ! function_exists( 'wp_die' ) ) {
        function wp_die( $message = '' ) {
                throw new Exception( $message );
        }
}

if ( ! function_exists( 'is_admin' ) ) {
        function is_admin() {
                return false;
        }
}

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
if ( ! function_exists( 'wp_die' ) ) {
        function wp_die( $message = '' ) {}
}
if ( ! function_exists( 'did_action' ) ) {
	function did_action( $tag ) {
		return 1;
	}
}
if ( ! function_exists( 'is_admin' ) ) {
	function is_admin() {
		return false;
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
if ( ! function_exists( 'wp_trim_words' ) ) {
	function wp_trim_words( $text, $num_words = 55, $more = null ) {
		$words = preg_split( '/[\s]+/', strip_tags( $text ) );
		if ( count( $words ) > $num_words ) {
			$words = array_slice( $words, 0, $num_words );
			$text  = implode( ' ', $words ) . ( $more ? $more : '...' );
		}
		return $text;
	}
}
if ( ! function_exists( 'wp_json_encode' ) ) {
        function wp_json_encode( $data ) {
                return json_encode( $data );
        }
}

if ( ! class_exists( 'RTBCB_LLM_Optimized' ) ) {
	class RTBCB_LLM_Optimized {
		public function generate_comprehensive_business_case( $user_inputs, $scenarios, $rag_context, $chunk_callback = null ) {
			$mode = class_exists( 'RTBCB_LLM' ) && property_exists( 'RTBCB_LLM', 'mode' ) ? RTBCB_LLM::$mode : 'generic';
			
			if ( 'no_api_key' === $mode ) {
				return new WP_Error( 'no_api_key', 'OpenAI API key not configured.' );
			}
			
			if ( 'http_status' === $mode ) {
				return new WP_Error( 'llm_http_status', 'Teapot', [ 'status' => 418 ] );
			}
			
			return new WP_Error( 'llm_error', 'LLM failed' );
		}
	}
}

if ( ! function_exists( 'is_wp_error' ) ) {
       function is_wp_error( $thing ) {
               return $thing instanceof WP_Error;
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


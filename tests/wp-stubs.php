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

if ( ! class_exists( 'WP_Error' ) ) {
	class WP_Error {
	/**
	 * Error code.
	 *
	 * @var string
	 */
	protected $code;
	
	/**
	 * Error message.
	 *
	 * @var string
	 */
	protected $message;
	
	/**
	 * Error data.
	 *
	 * @var mixed
	 */
	protected $data;
	
	/**
	 * Constructor.
	 *
	 * @param string $code    Error code.
	 * @param string $message Error message.
	 * @param mixed  $data    Error data.
	 */
	public function __construct( $code = '', $message = '', $data = null ) {
		$this->code    = $code;
		$this->message = $message;
		$this->data    = $data;
	}
	
	/**
	 * Retrieve the error code.
	 *
	 * @return string Error code.
	 */
	public function get_error_code() {
		return $this->code;
	}
	
	/**
	 * Retrieve the error message.
	 *
	 * @param string $code Optional. Error code.
	 * @return string Error message.
	 */
	public function get_error_message( $code = '' ) {
		return $this->message;
	}
	
	/**
	 * Retrieve the error data.
	 *
	 * @param string $code Optional. Error code.
	 * @return mixed Error data.
	 */
	public function get_error_data( $code = '' ) {
		return $this->data;
}
}
}

if ( ! function_exists( 'is_wp_error' ) ) {
	function is_wp_error( $thing ) {
		return $thing instanceof WP_Error;
	}
}

global $wp_cache_store, $wp_transients;
$wp_cache_store = [];
$wp_transients  = [];

if ( ! function_exists( 'wp_cache_get' ) ) {
	function wp_cache_get( $key, $group = '' ) {
		global $wp_cache_store;
		return $wp_cache_store[ $group ][ $key ] ?? false;
	}
}
if ( ! function_exists( 'wp_cache_set' ) ) {
	function wp_cache_set( $key, $value, $group = '', $ttl = 0 ) {
		global $wp_cache_store;
		if ( ! isset( $wp_cache_store[ $group ] ) ) {
		        $wp_cache_store[ $group ] = [];
		}
		$wp_cache_store[ $group ][ $key ] = $value;
		return true;
	}
}
if ( ! function_exists( 'wp_cache_delete' ) ) {
	function wp_cache_delete( $key, $group = '' ) {
		global $wp_cache_store;
		unset( $wp_cache_store[ $group ][ $key ] );
		return true;
	}
}
if ( ! function_exists( 'get_transient' ) ) {
	function get_transient( $key ) {
		global $wp_transients;
		return $wp_transients[ $key ] ?? false;
	}
}
if ( ! function_exists( 'set_transient' ) ) {
	function set_transient( $key, $value, $expiration ) {
		global $wp_transients;
		$wp_transients[ $key ] = $value;
		return true;
	}
}
if ( ! function_exists( 'delete_transient' ) ) {
	function delete_transient( $key ) {
		global $wp_transients;
		unset( $wp_transients[ $key ] );
		return true;
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


<?php
if ( ! defined( 'ABSPATH' ) ) {
        define( 'ABSPATH', __DIR__ . '/../' );
}

if ( ! function_exists( 'sanitize_text_field' ) ) {
        function sanitize_text_field( $text ) {
                $text = is_scalar( $text ) ? (string) $text : '';
                $text = preg_replace( '/[\r\n\t\0\x0B]/', '', $text );
                return trim( $text );
        }
}

if ( ! function_exists( 'sanitize_title' ) ) {
        function sanitize_title( $title ) {
                $title = strtolower( $title );
                $title = preg_replace( '/[^a-z0-9]+/', '-', $title );
                return trim( $title, '-' );
        }
}

if ( ! function_exists( 'sanitize_key' ) ) {
        function sanitize_key( $key ) {
                $key = strtolower( $key );
                return preg_replace( '/[^a-z0-9_\-]/', '', $key );
        }
}

if ( ! function_exists( 'set_transient' ) ) {
        function set_transient( $name, $value, $expiration ) {
                global $transients;
                $transients[ $name ] = $value;
                return true;
        }
}

if ( ! function_exists( 'get_transient' ) ) {
        function get_transient( $name ) {
                global $transients;
                return $transients[ $name ] ?? false;
        }
}

if ( ! function_exists( 'get_option' ) ) {
        function get_option( $name, $default = '' ) {
                return $default;
        }
}

if ( ! function_exists( 'apply_filters' ) ) {
        function apply_filters( $tag, $value ) {
                return $value;
        }
}

if ( ! function_exists( 'add_filter' ) ) {
        function add_filter( $tag, $callback, $priority = 10, $accepted_args = 1 ) {}
}

if ( ! function_exists( '__' ) ) {
        function __( $text, $domain = null ) {
                return $text;
        }
}

if ( ! defined( 'DAY_IN_SECONDS' ) ) {
        define( 'DAY_IN_SECONDS', 86400 );
}

require_once __DIR__ . '/../inc/helpers.php';

$company = 'Cache Co';
$industry = 'finance';
$small = '$50M-$500M';
$large = '>$2B';

$small_data = [ 'stage' => 'small' ];
rtbcb_set_research_cache( $company, $industry, 'company', $small_data, 0, $small );

$cached_small = rtbcb_get_research_cache( $company, $industry, 'company', $small );
if ( 'small' !== ( $cached_small['stage'] ?? '' ) ) {
        echo "Small size not cached\n";
        exit( 1 );
}

$cached_large = rtbcb_get_research_cache( $company, $industry, 'company', $large );
if ( false !== $cached_large ) {
        echo "Large size incorrectly reused small cache\n";
        exit( 1 );
}

$large_data = [ 'stage' => 'large' ];
rtbcb_set_research_cache( $company, $industry, 'company', $large_data, 0, $large );

$cached_large = rtbcb_get_research_cache( $company, $industry, 'company', $large );
if ( 'large' !== ( $cached_large['stage'] ?? '' ) ) {
        echo "Large size not cached\n";
        exit( 1 );
}

echo "company-research-cache.test.php passed\n";

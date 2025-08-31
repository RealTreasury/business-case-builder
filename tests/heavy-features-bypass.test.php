<?php
define( 'ABSPATH', __DIR__ . '/' );
if ( ! function_exists( '__' ) ) {
    function __( $text, $domain = null ) {
        return $text;
    }
}
if ( ! function_exists( 'add_filter' ) ) {
    function add_filter( $tag, $func, $priority = 10, $args = 1 ) {}
}
if ( ! function_exists( 'apply_filters' ) ) {
    function apply_filters( $tag, $value ) {
        return $value;
    }
}
if ( ! function_exists( 'get_option' ) ) {
    $GLOBALS['rtbcb_options'] = [];
    function get_option( $name, $default = false ) {
        return $GLOBALS['rtbcb_options'][ $name ] ?? $default;
    }
    function update_option( $name, $value ) {
        $GLOBALS['rtbcb_options'][ $name ] = $value;
    }
}
require_once __DIR__ . '/../inc/helpers.php';
require_once __DIR__ . '/../inc/class-rtbcb-category-recommender.php';
require_once __DIR__ . '/../inc/class-rtbcb-intelligent-recommender.php';

update_option( 'rtbcb_disable_heavy_features', true );

if ( ! rtbcb_heavy_features_disabled() ) {
    echo "heavy-features-bypass.test.php failed (flag)\n";
    exit( 1 );
}

$recommender = new RTBCB_Intelligent_Recommender();
$result = $recommender->recommend_with_ai_insights( [ 'company_size' => 'small' ], [] );
if ( isset( $result['ai_insights'] ) ) {
    echo "heavy-features-bypass.test.php failed (ai_insights)\n";
    exit( 1 );
}

echo "heavy-features-bypass.test.php passed\n";

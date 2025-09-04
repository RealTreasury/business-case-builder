<?php
if ( ! defined( 'ABSPATH' ) ) {
        define( 'ABSPATH', __DIR__ . '/../' );
}
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WP_Error' ) ) {
        class WP_Error {
                public $errors = [];
                public function __construct( $code = '', $message = '' ) {
                        $this->errors[ $code ] = [ $message ];
                }
        }
}

if ( ! function_exists( '__' ) ) {
        function __( $text, $domain = null ) {
                return $text;
        }
}

if ( ! function_exists( 'is_wp_error' ) ) {
        function is_wp_error( $thing ) {
                return $thing instanceof WP_Error;
        }
}

if ( ! function_exists( 'get_option' ) ) {
        function get_option( $name, $default = '' ) {
                return $default;
        }
}

if ( ! function_exists( 'sanitize_text_field' ) ) {
        function sanitize_text_field( $text ) {
                $text = is_scalar( $text ) ? (string) $text : '';
                $text = preg_replace( '/[\r\n\t\0\x0B]/', '', $text );
                return trim( $text );
        }
}

if ( ! function_exists( 'sanitize_key' ) ) {
        function sanitize_key( $key ) {
                $key = strtolower( $key );
                return preg_replace( '/[^a-z0-9_]/', '', $key );
        }
}

if ( ! function_exists( 'wp_remote_retrieve_body' ) ) {
        function wp_remote_retrieve_body( $response ) {
                return $response['body'] ?? '';
        }
}

require_once __DIR__ . '/../inc/class-rtbcb-response-parser.php';

$parser = new RTBCB_Response_Parser();

$valid_json = [
        'executive_summary' => [ 'strategic_positioning' => 'pos' ],
        'company_intelligence' => [],
'operational_insights' => [],
'risk_analysis' => [],
'action_plan' => [],
'financial_benchmarks' => [],
'technology_strategy' => [],
'financial_analysis' => [],
];

$response = [
        'body' => json_encode( [
                'output_text' => json_encode( $valid_json ),
        ] ),
];

$result = $parser->parse_business_case( $response );

if ( is_wp_error( $result ) ) {
        echo "Valid response produced WP_Error\n";
        exit( 1 );
}

$required = [
        'executive_summary',
        'company_intelligence',
        'operational_insights',
        'risk_analysis',
        'action_plan',
'financial_benchmarks',
        'technology_strategy',
        'financial_analysis',
];

foreach ( $required as $key ) {
        if ( ! isset( $result[ $key ] ) ) {
                echo "Missing expected key: {$key}\n";
                exit( 1 );
        }
}

$bad_response = [ 'body' => 'not json' ];
$bad_result   = $parser->parse_business_case( $bad_response );

if ( ! is_wp_error( $bad_result ) ) {
        echo "Invalid response did not return WP_Error\n";
        exit( 1 );
}

echo "parse-comprehensive-response.test.php passed\n";

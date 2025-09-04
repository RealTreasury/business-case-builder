<?php
if ( ! defined( 'ABSPATH' ) ) {
        define( 'ABSPATH', __DIR__ . '/../' );
}
defined( 'ABSPATH' ) || exit;

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/wp-stubs.php';

if ( ! function_exists( '__' ) ) {
        function __( $text, $domain = null ) {
                return $text;
        }
}
if ( ! function_exists( 'current_time' ) ) {
        function current_time( $format ) {
                return '2024-01-01';
        }
}
if ( ! function_exists( 'wp_parse_args' ) ) {
        function wp_parse_args( $args, $defaults = [] ) {
                return array_merge( $defaults, $args );
        }
}
if ( ! function_exists( 'wp_kses_post' ) ) {
        function wp_kses_post( $data ) {
                return $data;
        }
}

require_once __DIR__ . '/../inc/helpers.php';

/**
 * @runTestsInSeparateProcesses
 */
final class RTBCB_ComprehensiveTransformTest extends TestCase {
        public function test_additional_sections_preserved() {
                $input = [
                        'financial_benchmarks' => [
                                'industry_benchmarks' => [ [ 'metric' => 'EBITDA Margin', 'value' => '20%' ] ],
                        ],
                        'rag_context' => [ 'Context A' ],
                        'technology_strategy' => [
                                'implementation_roadmap' => [ 'Step 1', 'Step 2' ],
                                'vendor_considerations' => [ 'Vendor A', 'Vendor B' ],
                        ],
                ];

                $result = rtbcb_transform_data_for_template( $input );

                $this->assertSame( 'EBITDA Margin', $result['financial_benchmarks']['industry_benchmarks'][0]['metric'] );
                $this->assertSame( [ 'Context A' ], $result['rag_context'] );
                $this->assertSame( [ 'Step 1', 'Step 2' ], $result['technology_strategy']['implementation_roadmap'] );
                $this->assertSame( [ 'Vendor A', 'Vendor B' ], $result['technology_strategy']['vendor_considerations'] );
        }
}


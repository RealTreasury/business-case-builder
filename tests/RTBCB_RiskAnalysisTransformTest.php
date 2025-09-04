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
final class RTBCB_RiskAnalysisTransformTest extends TestCase {
	public function test_risk_analysis_arrays_present() {
               $input  = [
                       'risk_analysis' => [
                               'risk_matrix' => [
                                       [ 'risk' => ' Risk X ', 'likelihood' => ' High ', 'impact' => ' Low ' ],
                                       [ 'risk' => 'Risk Y', 'likelihood' => 'Low', 'impact' => 'High' ],
                               ],
                               'implementation_risks' => [ ' Risk A ', 'Risk B' ],
                               'mitigation_strategies' => [ ' Strategy A ', 'Strategy B' ],
                               'success_factors'      => [ ' Factor A ', 'Factor B' ],
                       ],
               ];

               $result = rtbcb_transform_data_for_template( $input );
               $risk   = $result['risk_analysis'];

               $this->assertSame(
                       [
                               [ 'risk' => 'Risk X', 'likelihood' => 'High', 'impact' => 'Low' ],
                               [ 'risk' => 'Risk Y', 'likelihood' => 'Low', 'impact' => 'High' ],
                       ],
                       $risk['risk_matrix']
               );
               $this->assertSame( [ 'Risk A', 'Risk B' ], $risk['implementation_risks'] );
               $this->assertSame( [ 'Strategy A', 'Strategy B' ], $risk['mitigation_strategies'] );
               $this->assertSame( [ 'Factor A', 'Factor B' ], $risk['success_factors'] );
       }
}

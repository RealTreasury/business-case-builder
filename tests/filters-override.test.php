<?php
require_once __DIR__ . '/wp-stubs.php';
require_once __DIR__ . '/../inc/class-rtbcb-calculator.php';

if ( ! function_exists( 'add_filter' ) ) {
	$GLOBALS['rtbcb_filters'] = [];

	function add_filter( $tag, $function_to_add, $priority = 10, $accepted_args = 1 ) {
		$GLOBALS['rtbcb_filters'][ $tag ] = $function_to_add;
	}

	function apply_filters( $tag, $value ) {
		$args = func_get_args();
		$tag  = array_shift( $args );
		$value = array_shift( $args );

		if ( isset( $GLOBALS['rtbcb_filters'][ $tag ] ) ) {
			$value = call_user_func_array( $GLOBALS['rtbcb_filters'][ $tag ], array_merge( [ $value ], $args ) );
		}

		return $value;
	}
}

$inputs = [
	'hours_reconciliation' => 10,
	'hours_cash_positioning' => 0,
	'num_banks' => 1,
	'company_size' => '$50M-$500M',
];

$settings = [
	'labor_cost_per_hour' => 100,
	'bank_fee_baseline' => 15000,
];

$category = [ 'roi_range' => [ 0, 1000000 ] ];
$scenario = 'base';
$industry_mult = 1.0;

add_filter( 'rtbcb_roi_multipliers', function ( $multipliers, $inputs, $settings, $category, $scenario_type, $industry ) {
	$multipliers['base'] = 2.0;
	return $multipliers;
}, 10, 6 );

add_filter( 'rtbcb_error_cost_map', function ( $cost_map ) {
	$cost_map['$50M-$500M'] = 1000000;
	return $cost_map;
}, 10, 4 );

$method = new ReflectionMethod( RTBCB_Calculator::class, 'calculate_scenario' );
$method->setAccessible( true );
$result = $method->invoke( null, $inputs, $settings, $category, $scenario, $industry_mult );

if ( abs( $result['assumptions']['efficiency_improvement'] - 0.60 ) > 0.0001 ) {
	echo "Multiplier filter did not apply\n";
	exit( 1 );
}

if ( 500000 !== (int) $result['error_reduction'] ) {
	echo "Error cost map filter did not apply\n";
	exit( 1 );
}

echo "filters-override.test.php passed\n";

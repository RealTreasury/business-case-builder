<?php
define( 'ABSPATH', __DIR__ );
require_once __DIR__ . '/../inc/helpers.php';

if ( ! function_exists( 'add_filter' ) ) {
    $GLOBALS['rtbcb_filters'] = [];
    function add_filter( $tag, $function_to_add, $priority = 10, $accepted_args = 1 ) {
        $GLOBALS['rtbcb_filters'][ $tag ] = $function_to_add;
    }
    function apply_filters( $tag, $value ) {
        $args   = func_get_args();
        $tag    = array_shift( $args );
        $value  = array_shift( $args );
        if ( isset( $GLOBALS['rtbcb_filters'][ $tag ] ) ) {
            $value = call_user_func_array( $GLOBALS['rtbcb_filters'][ $tag ], array_merge( [ $value ], $args ) );
        }
        return $value;
    }
}

$received = '';
add_filter( 'rtbcb_sample_report_inputs', function ( $inputs, $scenario_key ) use ( &$received ) {
    $received = $scenario_key;
    if ( 'alt' === $scenario_key ) {
        $inputs['company_name'] = 'Alternate Inc';
    }
    return $inputs;
}, 10, 2 );

$inputs = apply_filters( 'rtbcb_sample_report_inputs', rtbcb_get_sample_inputs(), 'alt' );
if ( 'alt' !== $received ) {
    echo "Scenario key not passed to filter\n";
    exit( 1 );
}
if ( 'Alternate Inc' !== $inputs['company_name'] ) {
    echo "Scenario modification not applied\n";
    exit( 1 );
}

echo "scenario-selection.test.php passed\n";

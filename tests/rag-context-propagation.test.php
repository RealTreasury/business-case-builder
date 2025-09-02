<?php
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ );
}
defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/wp-stubs.php';

if ( ! function_exists( '__' ) ) {
	function __( $text, $domain = null ) {
		return $text;
	}
}

require_once __DIR__ . '/../real-treasury-business-case-builder.php';

$input  = [ 'rag_context' => [ 'First context', 'Second context' ] ];
$result = rtbcb_transform_data_for_template( $input );

$expected = array_map( 'sanitize_text_field', $input['rag_context'] );
if ( $result['rag_context'] !== $expected ) {
	echo "rag context not preserved\n";
	exit( 1 );
}

echo "rag-context-propagation.test.php passed\n";


<?php
if ( ! defined( 'ABSPATH' ) ) {
    define( 'ABSPATH', __DIR__ . '/../' );
}
defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/../inc/class-rtbcb-response-parser.php';

$parser = new RTBCB_Response_Parser();

$stream  = "for (;;);\n";
$stream .= "for (;;); event: ping\n";
$stream .= "for (;;); data: {invalid json}\n\n";
$stream .= "   for (;;); data: {\"choices\":[{\"delta\":{\"content\":\"Hello\"}}]}\n\n";
$stream .= "for (;;); data: {\"choices\":[{\"delta\":{\"content\":\" world\"}}]}\n\n";
$stream .= "for (;;); data: {\"choices\":[{\"message\":{\"content\":\"Hello world\"}}]}\n\n";
$stream .= "for (;;); data: [DONE]\n\n";

$result = $parser->process_openai_response( $stream );
if ( 'Hello world' !== $result ) {
    echo "wpcom-streaming-response.test.php failed\n";
    exit( 1 );
}

echo "wpcom-streaming-response.test.php passed\n";

<?php
if ( ! defined( 'ABSPATH' ) ) {
    define( 'ABSPATH', __DIR__ . '/../' );
}
defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/../inc/class-rtbcb-response-parser.php';

$parser = new RTBCB_Response_Parser();

$stream  = "for (;;); data: {\"type\":\"response.reasoning.delta\",\"delta\":{\"text\":\"thinking\"}}\n\n";
$stream .= "for (;;); data: {\"type\":\"response.output_text.delta\",\"delta\":{\"text\":\"Hello\"}}\n\n";
$stream .= "for (;;); data: {\"type\":\"response.output_text.delta\",\"delta\":{\"text\":\" world\"}}\n\n";
$stream .= "for (;;); data: {\"type\":\"response.output_text.done\",\"response\":{\"output_text\":\"Hello world\"}}\n\n";
$stream .= "for (;;); data: [DONE]\n\n";

$result = $parser->process_openai_response( $stream );
if ( 'Hello world' !== $result['output_text'] ) {
    echo "wpcom-streaming-response.test.php failed: output_text\n";
    exit( 1 );
}
if ( [ 'thinking' ] !== $result['reasoning'] ) {
    echo "wpcom-streaming-response.test.php failed: reasoning\n";
    exit( 1 );
}

echo "wpcom-streaming-response.test.php passed\n";

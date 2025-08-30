<?php

if ( ! defined( 'ABSPATH' ) ) {
    define( 'ABSPATH', __DIR__ . '/..' );
}

if ( ! function_exists( 'sanitize_email' ) ) {
    function sanitize_email( $email ) {
        return $email;
    }
}

if ( ! function_exists( '__' ) ) {
    function __( $text, $domain = null ) {
        return $text;
    }
}

if ( ! function_exists( 'add_filter' ) ) {
    $GLOBALS['rtbcb_filters'] = [];
    function add_filter( $tag, $function_to_add ) {
        $GLOBALS['rtbcb_filters'][ $tag ] = $function_to_add;
    }
    function apply_filters( $tag, $value ) {
        $args = func_get_args();
        $tag = array_shift( $args );
        $value = array_shift( $args );
        if ( isset( $GLOBALS['rtbcb_filters'][ $tag ] ) ) {
            $value = call_user_func_array( $GLOBALS['rtbcb_filters'][ $tag ], array_merge( [ $value ], $args ) );
        }
        return $value;
    }
}

require_once __DIR__ . '/../inc/helpers.php';

$captured = [];
add_filter( 'rtbcb_mailer', function () use ( &$captured ) {
    return function ( $to, $subject, $message, $headers, $attachments ) use ( &$captured ) {
        $captured = [
            'to' => $to,
            'subject' => $subject,
            'attachments' => $attachments,
        ];
        return true;
    };
} );

$report_path = tempnam( sys_get_temp_dir(), 'rtbcb_report_' );
file_put_contents( $report_path, 'dummy pdf' );

rtbcb_send_report_email( [ 'email' => 'user@example.com' ], $report_path );

unlink( $report_path );

if ( 'user@example.com' !== $captured['to'] ) {
    echo "Email recipient mismatch\n";
    exit( 1 );
}

if ( 'Your Business Case Report' !== $captured['subject'] ) {
    echo "Email subject mismatch\n";
    exit( 1 );
}

if ( empty( $captured['attachments'] ) || $captured['attachments'][0] !== $report_path ) {
    echo "Email attachment mismatch\n";
    exit( 1 );
}

echo "email-and-pdf.test.php passed\n";

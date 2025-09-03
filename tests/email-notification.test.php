<?php

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'sanitize_email' ) ) {
	function sanitize_email( $email ) {
		return filter_var( $email, FILTER_SANITIZE_EMAIL );
	}
}

if ( ! function_exists( '__' ) ) {
	function __( $text, $domain = null ) {
		return $text;
	}
}

if ( ! function_exists( 'add_filter' ) ) {
	function add_filter( $tag, $function_to_add, $priority = 10, $accepted_args = 1 ) {}
}

$sent_mail = [];

function rtbcb_mock_mail( $to, $subject, $message, $headers = [], $attachments = [] ) {
        global $sent_mail;
        $sent_mail = [
                'to'          => $to,
                'subject'     => $subject,
                'message'     => $message,
                'attachments' => $attachments,
        ];
        return true;
}

require_once __DIR__ . '/../inc/helpers.php';

$form_data  = [ 'email' => 'user@example.com' ];
$report_url = 'http://example.com/report.html';

rtbcb_send_report_email( $form_data, $report_url, 'rtbcb_mock_mail' );

if ( $sent_mail['to'] !== 'user@example.com' ) {
        echo "Recipient mismatch\n";
        exit( 1 );
}

if ( $sent_mail['subject'] !== 'Your Business Case Report' ) {
        echo "Subject mismatch\n";
        exit( 1 );
}

if ( strpos( $sent_mail['message'], $report_url ) === false ) {
        echo "URL missing from message\n";
        exit( 1 );
}

if ( ! empty( $sent_mail['attachments'] ) ) {
        echo "Unexpected attachments\n";
        exit( 1 );
}

echo "email-and-pdf.test.php passed\n";


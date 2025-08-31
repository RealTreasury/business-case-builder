<?php

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

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
		'to'		  => $to,
		'subject'	  => $subject,
		'attachments' => $attachments,
	];
	return true;
}

require_once __DIR__ . '/../inc/helpers.php';

$report_path = tempnam( sys_get_temp_dir(), 'rtbcb' ) . '.pdf';
file_put_contents( $report_path, 'PDF' );

$form_data = [ 'email' => 'user@example.com' ];

rtbcb_send_report_email( $form_data, $report_path, 'rtbcb_mock_mail' );

if ( $sent_mail['to'] !== 'user@example.com' ) {
	echo "Recipient mismatch\n";
	exit( 1 );
}

if ( $sent_mail['subject'] !== 'Your Business Case Report' ) {
	echo "Subject mismatch\n";
	exit( 1 );
}

if ( $sent_mail['attachments'][0] !== $report_path ) {
	echo "Attachment mismatch\n";
	exit( 1 );
}

unlink( $report_path );

echo "email-and-pdf.test.php passed\n";


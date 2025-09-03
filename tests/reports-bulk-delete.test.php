<?php
if ( ! defined( 'ABSPATH' ) ) {
define( 'ABSPATH', __DIR__ . '/../' );
}

define( 'RTBCB_TESTS', true );

if ( ! function_exists( 'admin_url' ) ) {
function admin_url( $path = '' ) {
return 'http://example.com/wp-admin/' . ltrim( $path, '/' );
}
}

if ( ! function_exists( 'wp_safe_redirect' ) ) {
function wp_safe_redirect( $location ) {
global $redirect_location;
$redirect_location = $location;
}
}

if ( ! class_exists( 'WP_List_Table' ) ) {
class WP_List_Table {
public function __construct( $args = [] ) {}
public function current_action() {
if ( isset( $_REQUEST['action'] ) && '-1' !== $_REQUEST['action'] && '' !== $_REQUEST['action'] ) {
return $_REQUEST['action'];
}
if ( isset( $_REQUEST['action2'] ) && '-1' !== $_REQUEST['action2'] && '' !== $_REQUEST['action2'] ) {
return $_REQUEST['action2'];
}
return false;
}
protected function get_items_per_page( $option, $default = 20 ) {
return $default;
}
protected function get_pagenum() {
return 1;
}
protected function set_pagination_args( $args ) {}
}
}

if ( ! function_exists( 'check_admin_referer' ) ) {
function check_admin_referer( $action ) {}
}
if ( ! function_exists( 'wp_unslash' ) ) {
function wp_unslash( $value ) {
return $value;
}
}
if ( ! function_exists( 'sanitize_file_name' ) ) {
function sanitize_file_name( $name ) {
return preg_replace( '/[^A-Za-z0-9._-]/', '', $name );
}
}
if ( ! function_exists( 'trailingslashit' ) ) {
function trailingslashit( $string ) {
return rtrim( $string, '/\\' ) . '/';
}
}
if ( ! function_exists( 'size_format' ) ) {
function size_format( $bytes, $decimals = 2 ) {
return $bytes;
}
}
if ( ! function_exists( 'date_i18n' ) ) {
function date_i18n( $format, $timestamp ) {
return date( $format, $timestamp );
}
}
if ( ! function_exists( 'get_option' ) ) {
function get_option( $name ) {
return 'Y-m-d H:i:s';
}
}
if ( ! function_exists( '__' ) ) {
function __( $text, $domain = null ) {
return $text;
}
}
if ( ! function_exists( 'current_user_can' ) ) {
function current_user_can( $cap ) {
return true;
}
}

$redirect_location = '';

$clear_called = 0;
function rtbcb_clear_report_cache() {
global $clear_called;
$clear_called++;
}

$tmp_base    = sys_get_temp_dir() . '/rtbcb-reports-test-' . uniqid();
$reports_dir = $tmp_base . '/rtbcb-reports';
mkdir( $reports_dir, 0777, true );
file_put_contents( $reports_dir . '/report1.html', 'html' );
file_put_contents( $reports_dir . '/report2.pdf', 'pdf' );

function wp_upload_dir() {
global $tmp_base;
return [
'basedir' => $tmp_base,
'baseurl' => 'http://example.com',
];
}

require_once __DIR__ . '/../admin/class-rtbcb-reports-table.php';

$table = new RTBCB_Reports_Table();

$_REQUEST['action']  = 'delete';
$_POST['files']      = [ 'report1.html' ];
$_POST['_wpnonce']   = 'nonce';
$_REQUEST['_wpnonce'] = 'nonce';

$table->prepare_items();

if ( file_exists( $reports_dir . '/report1.html' ) ) {
echo "Selected delete failed\n";
exit( 1 );
}
if ( ! file_exists( $reports_dir . '/report2.pdf' ) ) {
echo "Unexpected delete\n";
exit( 1 );
}
if ( 1 !== $clear_called ) {
echo "Cache not cleared after delete\n";
exit( 1 );
}

$_REQUEST['action'] = 'delete_all';
$_POST              = [ '_wpnonce' => 'nonce' ];

$table->prepare_items();

if ( admin_url( 'admin.php?page=rtbcb-reports' ) !== $redirect_location ) {
echo "No redirect after delete all\n";
exit( 1 );
}

if ( glob( $reports_dir . '/*' ) ) {
echo "Delete all failed\n";
exit( 1 );
}
if ( 2 !== $clear_called ) {
echo "Cache not cleared after delete all\n";
exit( 1 );
}

echo "reports-bulk-delete.test.php passed\n";

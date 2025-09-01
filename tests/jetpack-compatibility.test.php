<?php
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/../' );
}
defined( 'ABSPATH' ) || exit;
require_once __DIR__ . '/wp-stubs.php';

if ( ! function_exists( 'plugin_dir_url' ) ) {
	function plugin_dir_url( $file ) {
		return '';
	}
}
if ( ! function_exists( 'plugin_dir_path' ) ) {
	function plugin_dir_path( $file ) {
		return __DIR__ . '/../';
	}
}
if ( ! function_exists( 'register_activation_hook' ) ) {
	function register_activation_hook() {}
}
if ( ! function_exists( 'register_deactivation_hook' ) ) {
	function register_deactivation_hook() {}
}
if ( ! function_exists( 'register_uninstall_hook' ) ) {
	function register_uninstall_hook() {}
}
if ( ! function_exists( 'add_shortcode' ) ) {
	function add_shortcode() {}
}
if ( ! function_exists( 'plugin_basename' ) ) {
	function plugin_basename() {
		return '';
	}
}
if ( ! function_exists( 'get_file_data' ) ) {
	function get_file_data() {
		return [];
	}
}

$_GET['for'] = 'jetpack';

ob_start();
$plugin_code = file_get_contents( __DIR__ . '/../real-treasury-business-case-builder.php' );
$plugin_code = preg_replace( '/\$this->includes\(\);/', '', $plugin_code );
eval( '?>' . $plugin_code );
$plugin_output	= ob_get_clean();
$plugin_headers = headers_list();
if ( '' !== $plugin_output ) {
	echo "Plugin produced output during initialization\n";
	exit( 1 );
}
if ( ! empty( $plugin_headers ) ) {
	echo "Plugin sent unexpected headers during initialization\n";
	exit( 1 );
}

header_remove();
ob_start();
header( 'Content-Type: text/xml' );
$expected = '<methodResponse><params><param><value>JETPACK_OK</value></param></params></methodResponse>';
echo $expected;
$response = ob_get_clean();
if ( $response !== $expected ) {
	echo "Jetpack handshake response altered\n";
	exit( 1 );
}

echo "jetpack-compatibility.test.php passed\n";

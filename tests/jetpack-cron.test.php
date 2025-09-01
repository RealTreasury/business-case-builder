<?php
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/../' );
}
defined( 'ABSPATH' ) || exit;

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

if ( ! function_exists( 'wp_unslash' ) ) {
	function wp_unslash( $value ) {
		return $value;
	}
}

if ( ! function_exists( 'wp_doing_cron' ) ) {
	function wp_doing_cron() {
		return true;
	}
}

if ( ! function_exists( 'add_action' ) ) {
	function add_action( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
	}
}
if ( ! function_exists( 'is_admin' ) ) {
	function is_admin() {
		return false;
	}
}

$_SERVER['HTTP_X_JETPACK_SIGNATURE'] = 'sig';

define( 'RTBCB_NO_BOOTSTRAP', true );
require_once __DIR__ . '/../real-treasury-business-case-builder.php';

$reflection = new ReflectionClass( 'RTBCB_Main' );
$method      = $reflection->getMethod( 'is_jetpack_request' );
$method->setAccessible( true );
$instance = $reflection->newInstanceWithoutConstructor();
$result   = $method->invoke( $instance );

if ( $result ) {
	echo "Jetpack cron incorrectly detected\n";
	exit( 1 );
}

echo "jetpack-cron.test.php passed\n";

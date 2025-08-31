<?php
if ( ! function_exists( '__' ) ) {
function __( $text, $domain = null ) {
return $text;
}
}
if ( ! class_exists( 'WP_Error' ) ) {
class WP_Error {
public $errors;
public $error_data;
public function __construct( $code = '', $message = '', $data = '' ) {
$this->errors	  = [ $code => [ $message ] ];
$this->error_data = [ $code => $data ];
}
public function get_error_message( $code = '' ) {
if ( empty( $code ) ) {
$code = key( $this->errors );
}
return $this->errors[ $code ][0];
}
public function get_error_data( $code = '' ) {
if ( empty( $code ) ) {
$code = key( $this->error_data );
}
return $this->error_data[ $code ];
}
}
}
if ( ! function_exists( 'is_wp_error' ) ) {
function is_wp_error( $thing ) {
return $thing instanceof WP_Error;
}
}
if ( ! function_exists( 'current_time' ) ) {
function current_time( $type ) {
return '2024-01-01';
}
}
if ( ! function_exists( 'rtbcb_get_current_company' ) ) {
function rtbcb_get_current_company() {
return [];
}
}
if ( ! function_exists( 'plugin_dir_url' ) ) {
function plugin_dir_url( $file ) {
return '';
}
}
if ( ! function_exists( 'plugin_dir_path' ) ) {
function plugin_dir_path( $file ) {
return __DIR__ . '/';
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
if ( ! function_exists( 'add_action' ) ) {
function add_action() {}
}
if ( ! function_exists( 'add_shortcode' ) ) {
function add_shortcode() {}
}
if ( ! function_exists( 'add_filter' ) ) {
function add_filter() {}
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

define( 'ABSPATH', __DIR__ );
$plugin_code = file_get_contents( __DIR__ . '/../real-treasury-business-case-builder.php' );
$plugin_code = preg_replace( '/
?\/\/ Initialize the plugin\s*Real_Treasury_BCB::instance\(\);/', '', $plugin_code );
eval( '?>' . $plugin_code );

$ref  = new ReflectionClass( 'Real_Treasury_BCB' );
$plugin = $ref->newInstanceWithoutConstructor();
$method = $ref->getMethod( 'transform_data_for_template' );
$method->setAccessible( true );

$result = $method->invoke( $plugin, [] );
if ( ! is_wp_error( $result ) ) {
echo "Validation did not produce WP_Error\n";
exit( 1 );
}
$data = $result->get_error_data();
if ( $data['operational_insights'][0] !== 'No data provided' ) {
echo "Operational fallback failed\n";
exit( 1 );
}
if ( $data['risk_analysis']['implementation_risks'][0] !== 'No data provided' ) {
echo "Risk fallback failed\n";
exit( 1 );
}

echo "operational-risks-fallback.test.php passed\n";

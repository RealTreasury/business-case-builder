<?php
if ( ! defined( 'ABSPATH' ) ) {
$temp_root = sys_get_temp_dir() . '/wp-' . uniqid();
mkdir( $temp_root . '/wp-admin/includes', 0777, true );
file_put_contents( $temp_root . '/wp-admin/includes/upgrade.php', "<?php\nfunction dbDelta( \$sql ) {\n\tglobal \$wpdb;\n\t\$wpdb->query( \$sql );\n\treturn true;\n}\n" );
define( 'ABSPATH', $temp_root . '/' );
}

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'DB_NAME' ) ) {
define( 'DB_NAME', 'testdb' );
}

if ( ! defined( 'ARRAY_A' ) ) {
define( 'ARRAY_A', 'ARRAY_A' );
}

if ( ! function_exists( 'add_action' ) ) {
function add_action( $hook, $callback ) {}
}

if ( ! function_exists( 'is_email' ) ) {
function is_email( $email ) {
return (bool) filter_var( $email, FILTER_VALIDATE_EMAIL );
}
}

if ( ! function_exists( 'sanitize_email' ) ) {
function sanitize_email( $email ) {
return filter_var( $email, FILTER_SANITIZE_EMAIL );
}
}

if ( ! function_exists( 'sanitize_text_field' ) ) {
function sanitize_text_field( $text ) {
$text = is_scalar( $text ) ? (string) $text : '';
$text = preg_replace( '/[\r\n\t\0\x0B]/', '', $text );
return trim( $text );
}
}

if ( ! function_exists( 'maybe_serialize' ) ) {
function maybe_serialize( $data ) {
return ( is_array( $data ) || is_object( $data ) ) ? serialize( $data ) : $data;
}
}

if ( ! function_exists( 'maybe_unserialize' ) ) {
function maybe_unserialize( $data ) {
$unser = @unserialize( $data );
return false === $unser && 'b:0;' !== $data ? $data : $unser;
}
}

if ( ! function_exists( 'wp_parse_args' ) ) {
function wp_parse_args( $args, $defaults = [] ) {
return array_merge( $defaults, $args );
}
}

if ( ! function_exists( 'sanitize_sql_orderby' ) ) {
function sanitize_sql_orderby( $orderby ) {
return $orderby;
}
}

if ( ! function_exists( 'wp_kses_post' ) ) {
function wp_kses_post( $data ) {
return $data;
}
}

if ( ! function_exists( '__' ) ) {
function __( $text, $domain = null ) {
return $text;
}
}

if ( ! function_exists( 'update_option' ) ) {
function update_option( $option, $value ) {}
}

if ( ! function_exists( 'get_option' ) ) {
function get_option( $option, $default = false ) {
return $default;
}
}

class WPDB_Memory {
public $prefix = '';
public $insert_id = 0;
public $last_error = '';
private $rows = [];

public function get_charset_collate() { return ''; }
public function query( $sql ) { return true; }
public function prepare( $query, ...$args ) { return $query; }
public function get_var( $sql ) { return 1; }
public function get_row( $sql, $output = ARRAY_A ) { return $this->rows[0] ?? null; }
public function get_results( $sql, $output = ARRAY_A ) { return $this->rows; }
public function insert( $table, $data, $format ) { $this->rows[] = $data; $this->insert_id = count( $this->rows ); return 1; }
public function update( $table, $data, $where, $format, $where_format ) { foreach ( $this->rows as &$row ) { if ( $row[ key( $where ) ] === current( $where ) ) { $row = array_merge( $row, $data ); return true; } } return false; }
public function last_error() { return $this->last_error; }
}

global $wpdb;
$wpdb = new WPDB_Memory();

require_once __DIR__ . '/../inc/class-rtbcb-leads.php';

$reflect = new ReflectionClass( 'RTBCB_Leads' );
$prop    = $reflect->getProperty( 'table_name' );
$prop->setAccessible( true );
$prop->setValue( null, $wpdb->prefix . 'rtbcb_leads' );

$wpdb->query( 'CREATE TABLE rtbcb_leads (
id INTEGER PRIMARY KEY AUTOINCREMENT,
email TEXT UNIQUE,
company_name TEXT,
company_size TEXT,
industry TEXT,
hours_reconciliation REAL,
hours_cash_positioning REAL,
num_banks INTEGER,
ftes REAL,
pain_points TEXT,
recommended_category TEXT,
roi_low REAL,
roi_base REAL,
roi_high REAL,
report_html TEXT,
ip_address TEXT,
user_agent TEXT,
utm_source TEXT,
utm_medium TEXT,
utm_campaign TEXT,
created_at TEXT,
updated_at TEXT
)' );

$_SERVER['REMOTE_ADDR']     = '127.0.0.1';
$_SERVER['HTTP_USER_AGENT'] = 'test-agent';

$lead_data = [
'email'         => 'test@example.com',
'company_size'  => '100-500',
'pain_points'   => [ 'delays', 'errors' ],
'roi_low'       => 1000,
'roi_base'      => 2000,
'roi_high'      => 3000,
'report_html'   => '<p>Report</p>',
];

$lead_id = RTBCB_Leads::save_lead( $lead_data );
if ( ! $lead_id ) {
echo "Failed to save lead\n";
exit( 1 );
}

$retrieved = RTBCB_Leads::get_lead_by_email( 'test@example.com' );
if ( '100-500' !== ( $retrieved['company_size'] ?? '' ) ) {
echo "Company size mismatch\n";
exit( 1 );
}

if ( [ 'delays', 'errors' ] !== ( $retrieved['pain_points'] ?? [] ) ) {
echo "Pain points mismatch\n";
exit( 1 );
}

if ( 1000.0 !== (float) ( $retrieved['roi_low'] ?? 0 ) || 2000.0 !== (float) ( $retrieved['roi_base'] ?? 0 ) || 3000.0 !== (float) ( $retrieved['roi_high'] ?? 0 ) ) {
echo "ROI mismatch\n";
exit( 1 );
}

if ( '<p>Report</p>' !== ( $retrieved['report_html'] ?? '' ) ) {
echo "Report HTML mismatch\n";
exit( 1 );
}

$all = RTBCB_Leads::get_all_leads( [ 'per_page' => 1 ] );
if ( '<p>Report</p>' !== ( $all['leads'][0]['report_html'] ?? '' ) ) {
echo "All leads report HTML mismatch\n";
exit( 1 );
}

echo "lead-storage.test.php passed\n";

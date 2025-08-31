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
	private $dbh;

public function __construct() {
$this->dbh = new SQLite3( ':memory:' );
}

public function get_charset_collate() {
return '';
}

public function query( $sql ) {
return $this->dbh->exec( $sql );
}

public function prepare( $query, ...$args ) {
$escaped = array_map( function ( $arg ) {
if ( is_string( $arg ) ) {
return "'" . str_replace( "'", "''", $arg ) . "'";
}
return $arg;
}, $args );
return vsprintf( $query, $escaped );
}

public function get_var( $sql ) {
if ( false !== strpos( $sql, 'information_schema.tables' ) ) {
return 1;
}
$result = $this->dbh->query( $sql );
if ( $result instanceof SQLite3Result ) {
$row = $result->fetchArray( SQLITE3_NUM );
return $row[0] ?? null;
}
return null;
}

public function get_row( $sql, $output = ARRAY_A ) {
$result = $this->dbh->query( $sql );
if ( $result instanceof SQLite3Result ) {
$row = $result->fetchArray( SQLITE3_ASSOC );
return $row ?: null;
}
return null;
}

public function insert( $table, $data, $format ) {
$cols  = array_keys( $data );
$vals  = [];
foreach ( $cols as $i => $col ) {
$fmt  = $format[ $i ];
$val  = $data[ $col ];
$vals[] = '%s' === $fmt ? "'" . str_replace( "'", "''", $val ) . "'" : $val;
}
		$sql = "INSERT INTO $table (" . implode( ',', $cols ) . ") VALUES (" . implode( ',', $vals ) . ")";
		$ok	 = $this->dbh->exec( $sql );
		if ( $ok ) {
			$this->insert_id = $this->dbh->lastInsertRowID();
			return 1;
		}
		$this->last_error = $this->dbh->lastErrorMsg();
		return false;
}

public function update( $table, $data, $where, $format, $where_format ) {
$sets = [];
foreach ( $data as $col => $val ) {
$fmt	= array_shift( $format );
$sets[] = '%s' === $fmt ? "$col = '" . str_replace( "'", "''", $val ) . "'" : "$col = $val";
}
$where_col = key( $where );
$where_val = current( $where );
$where_fmt = $where_format[0];
$where_sql = '%s' === $where_fmt ? "'" . str_replace( "'", "''", $where_val ) . "'" : $where_val;
$sql	   = "UPDATE $table SET " . implode( ', ', $sets ) . " WHERE $where_col = $where_sql";
		$ok = $this->dbh->exec( $sql );
		if ( ! $ok ) {
			$this->last_error = $this->dbh->lastErrorMsg();
		}
		return $ok;
}

public function last_error() {
return $this->dbh->lastErrorMsg();
}
}

global $wpdb;
$wpdb = new WPDB_Memory();

require_once __DIR__ . '/../inc/class-rtbcb-leads.php';

$reflect = new ReflectionClass( 'RTBCB_Leads' );
$prop	 = $reflect->getProperty( 'table_name' );
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

$_SERVER['REMOTE_ADDR']		= '127.0.0.1';
$_SERVER['HTTP_USER_AGENT'] = 'test-agent';

$lead_data = [
'email'			=> 'test@example.com',
'company_size'	=> '100-500',
'pain_points'	=> [ 'delays', 'errors' ],
'roi_low'		=> 1000,
'roi_base'		=> 2000,
'roi_high'		=> 3000,
'report_html'	=> '<p>Report</p>',
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

<?php
defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/../' );

defined( 'ARRAY_A' ) || define( 'ARRAY_A', 'ARRAY_A' );

if ( ! function_exists( 'add_action' ) ) {
	function add_action( $tag, $function_to_add, $priority = 10, $accepted_args = 1 ) {}
}

if ( ! function_exists( '__' ) ) {
	function __( $text, $domain = null ) {
		return $text;
	}
}

if ( ! function_exists( 'sanitize_email' ) ) {
	function sanitize_email( $email ) {
		return filter_var( $email, FILTER_SANITIZE_EMAIL );
	}
}

if ( ! function_exists( 'sanitize_text_field' ) ) {
	function sanitize_text_field( $str ) {
		$str = is_scalar( $str ) ? (string) $str : '';
		$str = preg_replace( '/[\r\n\t\0\x0B]/', '', $str );
		return trim( $str );
	}
}

if ( ! function_exists( 'is_email' ) ) {
	function is_email( $email ) {
		return (bool) filter_var( $email, FILTER_VALIDATE_EMAIL );
	}
}

if ( ! function_exists( 'maybe_serialize' ) ) {
	function maybe_serialize( $data ) {
		if ( is_array( $data ) || is_object( $data ) ) {
			return serialize( $data );
		}
		if ( is_serialized( $data ) ) {
			return serialize( $data );
		}
		return $data;
	}
}

if ( ! function_exists( 'is_serialized' ) ) {
	function is_serialized( $data ) {
		if ( ! is_string( $data ) ) {
			return false;
		}
		$data = trim( $data );
		if ( 'N;' === $data ) {
			return true;
		}
		if ( strlen( $data ) < 4 ) {
			return false;
		}
		if ( ':' !== $data[1] ) {
			return false;
		}
		$lastc = substr( $data, -1 );
		if ( ';' !== $lastc && '}' !== $lastc ) {
			return false;
		}
		return @unserialize( $data ) !== false || 'b:0;' === $data;
	}
}

if ( ! function_exists( 'maybe_unserialize' ) ) {
	function maybe_unserialize( $original ) {
		if ( is_serialized( $original ) ) {
			return @unserialize( $original );
		}
		return $original;
	}
}

if ( ! function_exists( 'wp_kses_post' ) ) {
	function wp_kses_post( $data ) {
		return $data;
	}
}

if ( ! class_exists( 'wpdb' ) ) {
	class wpdb {
		public $prefix = 'wp_';
		public $insert_id = 0;
		public $last_error = '';
		private $dbh;

		public function __construct() {
			$this->dbh = new PDO( 'sqlite::memory:' );
		}

		public function get_charset_collate() {
			return '';
		}

		public function prepare( $query, ...$args ) {
			foreach ( $args as &$arg ) {
				if ( is_string( $arg ) ) {
					$arg = str_replace( "'", "''", $arg );
					$arg = "'$arg'";
				}
			}
			return vsprintf( $query, $args );
		}

		public function query( $sql ) {
			$result = $this->dbh->exec( $sql );
			if ( false === $result ) {
				$this->last_error = implode( ' ', $this->dbh->errorInfo() );
			}
			return $result;
		}

		public function insert( $table, $data, $formats ) {
			$columns = implode( ',', array_keys( $data ) );
			$values	 = [];
			foreach ( $data as $value ) {
				if ( is_string( $value ) ) {
					$values[] = "'" . str_replace( "'", "''", $value ) . "'";
				} else {
					$values[] = $value;
				}
			}
			$sql	= "INSERT INTO {$table} ({$columns}) VALUES (" . implode( ',', $values ) . ')';
			$result = $this->dbh->exec( $sql );
			if ( false === $result ) {
				$this->last_error = implode( ' ', $this->dbh->errorInfo() );
				return false;
			}
			$this->insert_id = (int) $this->dbh->lastInsertId();
			return $result;
		}

		public function update( $table, $data, $where, $formats, $where_formats ) {
			$set_parts	 = [];
			$where_parts = [];
			foreach ( $data as $col => $value ) {
				if ( is_string( $value ) ) {
					$value = "'" . str_replace( "'", "''", $value ) . "'";
				}
				$set_parts[] = "$col = $value";
			}
			foreach ( $where as $col => $value ) {
				if ( is_string( $value ) ) {
					$value = "'" . str_replace( "'", "''", $value ) . "'";
				}
				$where_parts[] = "$col = $value";
			}
			$sql	= "UPDATE {$table} SET " . implode( ',', $set_parts ) . ' WHERE ' . implode( ' AND ', $where_parts );
			$result = $this->dbh->exec( $sql );
			if ( false === $result ) {
				$this->last_error = implode( ' ', $this->dbh->errorInfo() );
				return false;
			}
			return $result;
		}

		public function get_row( $query, $output = 'ARRAY_A' ) {
			$stmt = $this->dbh->query( $query );
			if ( ! $stmt ) {
				return null;
			}
			$row = $stmt->fetch( PDO::FETCH_ASSOC );
			return $row ? $row : null;
		}

		public function get_var( $query ) {
			$stmt = $this->dbh->query( $query );
			if ( ! $stmt ) {
				return null;
			}
			return $stmt->fetchColumn();
		}
	}
}

require_once __DIR__ . '/../inc/class-rtbcb-leads.php';

global $wpdb;
$wpdb = new wpdb();

$wpdb->query( 'CREATE TABLE ' . $wpdb->prefix . "rtbcb_leads (
	id INTEGER PRIMARY KEY AUTOINCREMENT,
	email TEXT,
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
)" );

$ref  = new ReflectionClass( 'RTBCB_Leads' );
$prop = $ref->getProperty( 'table_name' );
$prop->setAccessible( true );
$prop->setValue( null, $wpdb->prefix . 'rtbcb_leads' );

$_SERVER['HTTP_USER_AGENT'] = 'tests';
$_SERVER['REMOTE_ADDR']		= '127.0.0.1';

$lead_data = [
	'email'		   => 'john@example.com',
	'company_name' => 'Acme Corp',
	'company_size' => '$10M-$50M',
	'industry'	   => 'tech',
	'pain_points'  => [ 'manual processes', 'limited visibility' ],
	'roi_low'	   => 1000,
	'roi_base'	   => 2000,
	'roi_high'	   => 3000,
];

$lead_id = RTBCB_Leads::save_lead( $lead_data );

if ( ! $lead_id ) {
	echo "Failed to save lead\n";
	exit( 1 );
}

$saved = RTBCB_Leads::get_lead_by_email( 'john@example.com' );

if ( '$10M-$50M' !== $saved['company_size'] ) {
	echo "Company size did not persist\n";
	exit( 1 );
}

if ( ! isset( $saved['pain_points'][1] ) || 'limited visibility' !== $saved['pain_points'][1] ) {
	echo "Pain points did not persist\n";
	exit( 1 );
}

if ( 1000.0 !== (float) $saved['roi_low'] || 2000.0 !== (float) $saved['roi_base'] || 3000.0 !== (float) $saved['roi_high'] ) {
	echo "ROI values did not persist\n";
	exit( 1 );
}

echo "lead-storage.test.php passed\n";

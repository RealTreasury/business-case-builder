<?php
defined( 'ABSPATH' ) || exit;

/**
	* Database management and migration handling.
	*
	* @package RealTreasuryBusinessCaseBuilder
	*/

/**
	* Handles database versioning and upgrades.
	*/
class RTBCB_DB {
	/**
	* Current database version.
	*/
        const DB_VERSION = '2.0.4';

	/**
	/**
	 * Initialize database and handle upgrades.
	 *
	 * @return bool True on success, false on failure.
	 */
	public static function init() {
		global $wpdb;

		if ( ! $wpdb ) {
			error_log( 'RTBCB: WordPress database not available' );
			return false;
		}

		try {
			$current = function_exists( 'get_option' ) ? get_option( 'rtbcb_db_version', '1.0.0' ) : '1.0.0';

			if ( version_compare( $current, self::DB_VERSION, '<' ) ) {
				self::upgrade( $current );
				if ( function_exists( 'update_option' ) ) {
					update_option( 'rtbcb_db_version', self::DB_VERSION );
				}
			}

			// Ensure required tables exist.
			RTBCB_Leads::init();
			if ( class_exists( 'RTBCB_API_Log' ) ) {
				RTBCB_API_Log::init();
			}
			self::create_rag_table();
			self::seed_rag_sample_data();

			return true;
		} catch ( Throwable $e ) {
			error_log( 'RTBCB: Database initialization failed: ' . $e->getMessage() );
			return false;
		}
	}


	/**
	* Perform database upgrades.
	*
	* @param string $from_version Previous database version.
	* @return void
	*/
	private static function upgrade( $from_version ) {
		// Run table creation/migration for leads.
		RTBCB_Leads::init();
		if ( class_exists( 'RTBCB_API_Log' ) ) {
			RTBCB_API_Log::init();
		}

	// Ensure RAG index table is present during upgrades.
	self::create_rag_table();

		if ( version_compare( $from_version, '2.0.1', '<' ) ) {
				self::add_embedding_norm_index();
		}

				if ( version_compare( $from_version, '2.0.2', '<' ) ) {
						RTBCB_Leads::add_missing_indexes();
				}

		if ( version_compare( $from_version, '2.0.3', '<' ) ) {
			RTBCB_Leads::compress_existing_report_html();
		}

		if ( version_compare( $from_version, '2.0.4', '<' ) ) {
			RTBCB_Leads::add_api_response_column();
		}

	// Future migrations can be handled here.

		// Log the upgrade.
		error_log( 'RTBCB: Database upgraded from version ' . $from_version . ' to ' . self::DB_VERSION );
	}

	/**
	* Create the RAG index table if it does not exist.
	*
	* @return void
	*/
	private static function create_rag_table() {
		global $wpdb;

		$table_name      = $wpdb->prefix . 'rtbcb_rag_index';
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table_name} (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			type varchar(20) NOT NULL,
			ref_id varchar(100) NOT NULL,
			text_hash varchar(64) NOT NULL,
			embedding longtext NOT NULL,
			metadata longtext NOT NULL,
			embedding_norm float NOT NULL,
			updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			UNIQUE KEY hash_key (text_hash),
			KEY type_ref (type, ref_id),
			KEY embedding_norm_idx (embedding_norm)
		) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	* Add embedding_norm index to the RAG table if missing.
	*
	* @return void
	*/
	private static function add_embedding_norm_index() {
		global $wpdb;

		$table_name = $wpdb->prefix . 'rtbcb_rag_index';
		$exists     = $wpdb->get_var( $wpdb->prepare( 'SHOW INDEX FROM ' . $table_name . ' WHERE Key_name = %s', 'embedding_norm_idx' ) );
		if ( empty( $exists ) ) {
			$wpdb->query( 'ALTER TABLE ' . $table_name . ' ADD KEY embedding_norm_idx (embedding_norm)' );
		}
	}

	/**
	* Seed sample data into the RAG index when empty.
	*
	* @return void
	*/
	private static function seed_rag_sample_data() {
		global $wpdb;

		$table_name = $wpdb->prefix . 'rtbcb_rag_index';

		// Bail if table does not exist.
		$table_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) );
		if ( $table_exists !== $table_name ) {
			return;
		}

		$count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table_name}" );
		if ( $count > 0 ) {
			return;
		}

		$text = 'Sample RAG content';
		$wpdb->insert(
			$table_name,
			[
				'type'          => 'sample',
				'ref_id'        => 'sample',
				'text_hash'     => hash( 'sha256', $text ),
				'embedding'     => maybe_serialize( [ 0.0 ] ),
				'metadata'      => maybe_serialize( [ 'content' => $text ] ),
				'embedding_norm' => 0.0,
			],
			[ '%s', '%s', '%s', '%s', '%s', '%f' ]
		);
	}
	/**
	* Store API response data for a lead.
	*
	* @param int   $lead_id      Lead identifier.
	* @param mixed $api_response API response data.
	*
	* @return bool True on success, false on failure.
	*/
	public static function store_api_response( $lead_id, $api_response ) {
		global $wpdb;

		$response_data = is_string( $api_response ) ? $api_response : wp_json_encode( $api_response, JSON_UNESCAPED_SLASHES );

		$result = $wpdb->update(
			$wpdb->prefix . 'rtbcb_leads',
			[
				'api_response' => $response_data,
				'updated_at'   => current_time( 'mysql' ),
			],
			[ 'id' => $lead_id ],
			[ '%s', '%s' ],
			[ '%d' ]
		);

		return false !== $result;
	}

	/**
	* Retrieve stored API response for a lead.
	*
	* @param int $lead_id Lead identifier.
	*
	* @return array|null Decoded API response or null if not found.
	*/
	public static function get_api_response( $lead_id ) {
		global $wpdb;

		$response = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT api_response FROM {$wpdb->prefix}rtbcb_leads WHERE id = %d",
				$lead_id
			)
		);

		if ( $response ) {
			$decoded = json_decode( $response, true );
			if ( null !== $decoded ) {
				return $decoded;
			}

			$decoded = json_decode( stripslashes( $response ), true );
			if ( null !== $decoded ) {
				return $decoded;
			}
		}

		return null;
	}
}

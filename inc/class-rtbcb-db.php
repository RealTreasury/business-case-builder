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
    const DB_VERSION = '2.1.0';

    /**
     * Initialize database and handle upgrades.
     *
     * @return void
     */
    public static function init() {
        $current = get_option( 'rtbcb_db_version', '1.0.0' );

        if ( version_compare( $current, self::DB_VERSION, '<' ) ) {
            self::upgrade( $current );
            update_option( 'rtbcb_db_version', self::DB_VERSION );
        }

        // Ensure required tables exist after upgrades.
        RTBCB_Leads::init();
        if ( class_exists( 'RTBCB_API_Log' ) ) {
            RTBCB_API_Log::init();
        }
        self::create_rag_table();
        self::seed_rag_sample_data();
    }

    /**
     * Perform database upgrades.
     *
     * @param string $from_version Previous database version.
     * @return void
     */
    private static function upgrade( $from_version ) {
        if ( version_compare( $from_version, '2.1.0', '<' ) ) {
            RTBCB_Leads::upgrade_schema();
        } else {
            RTBCB_Leads::init();
        }

        if ( class_exists( 'RTBCB_API_Log' ) ) {
            RTBCB_API_Log::init();
        }

        // Ensure RAG index table is present during upgrades.
        self::create_rag_table();

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
            KEY type_ref (type, ref_id)
        ) {$charset_collate};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );
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
}

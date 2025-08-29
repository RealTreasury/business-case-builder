<?php
/**
 * API log management class.
 *
 * @package RealTreasuryBusinessCaseBuilder
 */

/**
 * Handles storage and retrieval of API interaction logs.
 */
class RTBCB_API_Log {
    /**
     * Database table name.
     *
     * @var string
     */
    private static $table_name;

    /**
     * Initialize the table name and create the table.
     *
     * @return void
     */
    public static function init() {
        global $wpdb;

        self::$table_name = $wpdb->prefix . 'rtbcb_api_logs';
        self::create_table();
    }

    /**
     * Create the API logs table.
     *
     * @return bool True on success, false on failure.
     */
    private static function create_table() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();
        $sql             = 'CREATE TABLE ' . self::$table_name . " (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            user_id bigint(20) unsigned DEFAULT 0,
            request_json longtext DEFAULT '',
            response_json longtext DEFAULT '',
            prompt_tokens int(11) DEFAULT 0,
            completion_tokens int(11) DEFAULT 0,
            total_tokens int(11) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY user_id (user_id),
            KEY created_at (created_at)
        ) $charset_collate;";

        try {
            require_once ABSPATH . 'wp-admin/includes/upgrade.php';
            dbDelta( $sql );

            $table_exists = $wpdb->get_var(
                $wpdb->prepare(
                    'SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = %s AND table_name = %s',
                    DB_NAME,
                    self::$table_name
                )
            );

            if ( ! $table_exists ) {
                error_log( 'RTBCB: Failed to create table ' . self::$table_name );

                $simple_sql = 'CREATE TABLE IF NOT EXISTS ' . self::$table_name . " (
                    id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                    user_id bigint(20) unsigned DEFAULT 0,
                    request_json longtext DEFAULT '',
                    response_json longtext DEFAULT '',
                    prompt_tokens int(11) DEFAULT 0,
                    completion_tokens int(11) DEFAULT 0,
                    total_tokens int(11) DEFAULT 0,
                    created_at datetime DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (id)
                ) $charset_collate;";

                $wpdb->query( $simple_sql );

                $table_exists = $wpdb->get_var(
                    $wpdb->prepare(
                        'SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = %s AND table_name = %s',
                        DB_NAME,
                        self::$table_name
                    )
                );

                if ( ! $table_exists ) {
                    error_log( 'RTBCB: Failed to create API log table even with simple structure' );
                    return false;
                }
            }

            return true;
        } catch ( Exception $e ) {
            error_log( 'RTBCB: Exception creating API log table: ' . $e->getMessage() );
            return false;
        } catch ( Error $e ) {
            error_log( 'RTBCB: Fatal error creating API log table: ' . $e->getMessage() );
            return false;
        }
    }

    /**
     * Save a log entry.
     *
     * @param array $request  Request data.
     * @param array $response Response data.
     * @param int   $user_id  User ID.
     * @return void
     */
    public static function save_log( $request, $response, $user_id ) {
        global $wpdb;

        if ( empty( self::$table_name ) ) {
            self::init();
        }

        $request_json  = wp_json_encode( $request );
        $response_json = wp_json_encode( $response );

        $request_json  = substr( $request_json, 0, 10000 );
        $response_json = substr( $response_json, 0, 10000 );

        $prompt_tokens     = intval( $response['usage']['prompt_tokens'] ?? 0 );
        $completion_tokens = intval( $response['usage']['completion_tokens'] ?? 0 );
        $total_tokens      = intval( $response['usage']['total_tokens'] ?? 0 );

        $wpdb->insert(
            self::$table_name,
            [
                'user_id'           => intval( $user_id ),
                'request_json'      => $request_json,
                'response_json'     => $response_json,
                'prompt_tokens'     => $prompt_tokens,
                'completion_tokens' => $completion_tokens,
                'total_tokens'      => $total_tokens,
                'created_at'        => current_time( 'mysql' ),
            ],
            [ '%d', '%s', '%s', '%d', '%d', '%d', '%s' ]
        );
    }

    /**
     * Retrieve recent logs.
     *
     * @param int $limit Number of logs to retrieve.
     * @return array Log rows.
     */
    public static function get_logs( $limit = 50 ) {
        global $wpdb;

        if ( empty( self::$table_name ) ) {
            self::init();
        }

        $limit = max( 1, intval( $limit ) );

        $query = $wpdb->prepare(
            'SELECT * FROM ' . self::$table_name . ' ORDER BY created_at DESC LIMIT %d',
            $limit
        );

        return $wpdb->get_results( $query, ARRAY_A );
    }

    /**
     * Purge old logs.
     *
     * @param int $days Number of days to retain.
     * @return int Rows deleted.
     */
    public static function purge_logs( $days = 30 ) {
        global $wpdb;

        if ( empty( self::$table_name ) ) {
            self::init();
        }

        $days = intval( $days );
        if ( $days <= 0 ) {
            return 0;
        }

        $threshold = gmdate( 'Y-m-d H:i:s', strtotime( "-{$days} days" ) );

        return $wpdb->query(
            $wpdb->prepare(
                'DELETE FROM ' . self::$table_name . ' WHERE created_at < %s',
                $threshold
            )
        );
    }
}

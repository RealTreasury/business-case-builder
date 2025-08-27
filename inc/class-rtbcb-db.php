<?php
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
    const DB_VERSION = '2.0.0';

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

        // Ensure required tables exist.
        RTBCB_Leads::init();
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

        // Future migrations can be handled here.

        // Log the upgrade.
        error_log( 'RTBCB: Database upgraded from version ' . $from_version . ' to ' . self::DB_VERSION );
    }
}

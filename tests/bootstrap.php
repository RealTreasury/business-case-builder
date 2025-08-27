<?php
/**
 * PHPUnit bootstrap file for Real Treasury Business Case Builder
 *
 * @package RealTreasuryBusinessCaseBuilder
 */

// Composer autoloader must be loaded before any tests run.
require_once dirname( __DIR__ ) . '/vendor/autoload.php';

// Only attempt to load the WordPress test suite if the path is provided.
$wp_phpunit_dir = getenv( 'WP_PHPUNIT_DIR' );

if ( $wp_phpunit_dir && file_exists( $wp_phpunit_dir . '/includes/functions.php' ) ) {
    // Give access to tests_add_filter() function.
    require_once $wp_phpunit_dir . '/includes/functions.php';

    /**
     * Manually load the plugin being tested.
     */
    function rtbcb_manually_load_plugin() {
        require dirname( __DIR__ ) . '/real-treasury-business-case-builder.php';
    }

    tests_add_filter( 'muplugins_loaded', 'rtbcb_manually_load_plugin' );

    // Start up the WP testing environment.
    require_once $wp_phpunit_dir . '/includes/bootstrap.php';
}


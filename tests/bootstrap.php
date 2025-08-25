<?php
/**
 * PHPUnit bootstrap file for Real Treasury Business Case Builder
 *
 * @package RealTreasuryBusinessCaseBuilder
 */

// Composer autoloader must be loaded before WP_PHPUNIT__DIR will be available
require_once dirname( __DIR__ ) . '/vendor/autoload.php';

// Give access to tests_add_filter() function.
require_once getenv( 'WP_PHPUNIT_DIR' ) . '/includes/functions.php';

/**
 * Manually load the plugin being tested.
 */
function _manually_load_plugin() {
    require dirname( __DIR__ ) . '/real-treasury-business-case-builder.php';
}

tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

// Start up the WP testing environment.
require getenv( 'WP_PHPUNIT_DIR' ) . '/includes/bootstrap.php';
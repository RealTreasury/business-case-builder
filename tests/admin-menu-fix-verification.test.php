<?php
/**
 * Simple test to verify admin menu registration timing fix
 * 
 * @package RealTreasuryBusinessCaseBuilder
 */

// Set testing flag for admin
define( 'RTBCB_TESTING_ADMIN', true );

// Include test bootstrap
require_once __DIR__ . '/bootstrap/bootstrap.php';

// Include main plugin file
require_once __DIR__ . '/../real-treasury-business-case-builder.php';

// Define required constants for testing
if ( !defined( 'RTBCB_VERSION' ) ) {
    define( 'RTBCB_VERSION', '2.1.0' );
}
if ( !defined( 'RTBCB_PLUGIN_URL' ) ) {
    define( 'RTBCB_PLUGIN_URL', '/fake-url/' );
}
if ( !defined( 'RTBCB_PLUGIN_DIR' ) ) {
    define( 'RTBCB_PLUGIN_DIR', __DIR__ . '/../' );
}

echo "Running Admin Menu Fix Verification Test...\n";

global $admin_page_hooks;
$admin_page_hooks = array();

echo "1. Getting plugin instance... ";
$plugin = RTBCB_Business_Case_Builder::get_instance();
echo "PASS\n";

echo "2. Calling plugins_loaded (should instantiate admin)... ";
$plugin->plugins_loaded();
echo "PASS\n";

echo "3. Checking if admin service was instantiated... ";
$admin_service = $plugin->get_service( 'admin' );
if ( $admin_service instanceof RTBCB_Admin ) {
    echo "PASS\n";
} else {
    echo "FAIL - Admin service not instantiated (" . gettype($admin_service) . ")\n";
    exit(1);
}

echo "4. Simulating admin_menu hook... ";
do_action( 'admin_menu' );
echo "PASS\n";

echo "5. Checking if admin menu was registered... ";
if ( array_key_exists( 'rtbcb-dashboard', $admin_page_hooks ) ) {
    echo "PASS\n";
    echo "\n[SUCCESS] Admin menu registration fix is working!\n";
    echo "Real Treasury menu should now appear in WordPress admin.\n";
    echo "Menu details: " . print_r($admin_page_hooks['rtbcb-dashboard'], true);
} else {
    echo "FAIL - Admin menu not registered\n";
    echo "Available menu hooks: " . implode(', ', array_keys($admin_page_hooks)) . "\n";
    echo "All menu data: " . print_r($admin_page_hooks, true) . "\n";
    exit(1);
}
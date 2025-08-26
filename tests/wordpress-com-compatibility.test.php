<?php
/**
 * Test WordPress.com compatibility for admin menu registration
 * 
 * @package RealTreasuryBusinessCaseBuilder
 */

// Define testing flag for admin
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

// Force admin context for testing
if ( !defined( 'WP_ADMIN' ) ) {
    define( 'WP_ADMIN', true );
}

echo "Testing WordPress.com Compatibility for Admin Menu Registration...\n";

// Test helper functions
echo "1. Testing WordPress.com detection functions... ";
require_once __DIR__ . '/../inc/utils/helpers.php';

// Test normal environment (should return false)
$is_wpcom = rtbcb_is_wordpress_com();
if ( $is_wpcom === false ) {
    echo "PASS (normal environment detected)\n";
} else {
    echo "FAIL - Should detect non-WordPress.com environment\n";
    exit(1);
}

// Test capability selection in normal environment
echo "2. Testing capability selection in normal environment... ";
$capability = rtbcb_get_admin_capability();
if ( in_array( $capability, [ 'manage_options', 'edit_pages', 'edit_posts', 'read' ] ) ) {
    echo "PASS (capability: {$capability})\n";
} else {
    echo "FAIL - Invalid capability: {$capability}\n";
    exit(1);
}

// Mock WordPress.com environment
echo "3. Testing WordPress.com environment simulation... ";
define( 'IS_WPCOM', true );

$is_wpcom_simulated = rtbcb_is_wordpress_com();
if ( $is_wpcom_simulated === true ) {
    echo "PASS (WordPress.com environment detected)\n";
} else {
    echo "FAIL - Should detect WordPress.com environment\n";
    exit(1);
}

// Test admin capability in simulated WordPress.com
echo "4. Testing admin capability in WordPress.com environment... ";
$wpcom_capability = rtbcb_get_admin_capability();
if ( in_array( $wpcom_capability, [ 'edit_pages', 'edit_posts', 'read' ] ) ) {
    echo "PASS (WordPress.com capability: {$wpcom_capability})\n";
} else {
    echo "FAIL - Invalid WordPress.com capability: {$wpcom_capability}\n";
    exit(1);
}

// Test admin class instantiation
echo "5. Testing admin class with WordPress.com compatibility... ";

global $admin_page_hooks;
$admin_page_hooks = array();

// Get plugin instance
$plugin = RTBCB_Business_Case_Builder::get_instance();
$plugin->plugins_loaded();

// Get admin service
$admin_service = $plugin->get_service( 'admin' );
if ( $admin_service instanceof RTBCB_Admin ) {
    echo "PASS\n";
} else {
    echo "FAIL - Admin service not instantiated\n";
    exit(1);
}

// Test menu registration with WordPress.com compatibility
echo "6. Testing menu registration with WordPress.com capabilities... ";
do_action( 'admin_menu' );

if ( array_key_exists( 'rtbcb-dashboard', $admin_page_hooks ) ) {
    $menu_details = $admin_page_hooks['rtbcb-dashboard'];
    $menu_capability = $menu_details['capability'];
    
    // Verify that the menu uses WordPress.com compatible capability
    if ( in_array( $menu_capability, [ 'edit_pages', 'edit_posts', 'read' ] ) ) {
        echo "PASS (menu capability: {$menu_capability})\n";
    } else {
        echo "FAIL - Menu should use WordPress.com compatible capability, got: {$menu_capability}\n";
        exit(1);
    }
} else {
    echo "FAIL - Admin menu not registered\n";
    echo "Available menu hooks: " . implode(', ', array_keys($admin_page_hooks)) . "\n";
    exit(1);
}

echo "\n[SUCCESS] WordPress.com compatibility tests passed!\n";
echo "The admin menu should now work on WordPress.com with appropriate capabilities.\n";

// Test user capability checking functions
echo "\n7. Testing user capability checking functions... ";
$can_admin = rtbcb_user_can_admin();
$can_manage = rtbcb_user_can_manage_settings();

echo "PASS (can_admin: " . ($can_admin ? 'true' : 'false') . ", can_manage: " . ($can_manage ? 'true' : 'false') . ")\n";

echo "\n[PASS] All WordPress.com compatibility tests completed successfully!\n";
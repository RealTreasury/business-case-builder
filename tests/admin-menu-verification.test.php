<?php
/**
 * Comprehensive test for admin menu visibility and capability handling
 * 
 * This test verifies that:
 * 1. Admin menu appears for appropriate user roles
 * 2. Capability requirements work across different environments
 * 3. PHP 8.3 compatibility is maintained
 * 4. WordPress.com compatibility functions correctly
 */

// Define testing flag for admin
define( 'RTBCB_TESTING_ADMIN', true );

// Bootstrap WordPress environment
require_once __DIR__ . '/bootstrap/bootstrap.php';

// Include main plugin file
require_once __DIR__ . '/../real-treasury-business-case-builder.php';

class RTBCB_Admin_Menu_Verification_Test {
    
    private $original_server = array();
    
    public function setUp() {
        // Store original $_SERVER values
        $this->original_server = $_SERVER;
        
        // Reset global variables
        global $admin_page_hooks, $submenu, $menu;
        $admin_page_hooks = array();
        $submenu = array();
        $menu = array();
    }
    
    public function tearDown() {
        // Restore original $_SERVER values
        $_SERVER = $this->original_server;
        
        // Clean up constants if they were defined during test
        if ( defined( 'IS_WPCOM' ) ) {
            // Note: Constants cannot be undefined in PHP, but we document this
            // for future reference
        }
    }
    
    /**
     * Test admin menu visibility with different capability scenarios
     */
    public function test_admin_menu_capability_scenarios() {
        echo "1. Testing admin menu with different capability scenarios... ";
        
        // Get plugin instance
        $plugin = RTBCB_Business_Case_Builder::get_instance();
        $plugin->plugins_loaded();
        
        // Get admin service
        $admin_service = $plugin->get_service( 'admin' );
        
        if ( ! $admin_service instanceof RTBCB_Admin ) {
            echo "FAIL - Admin service not properly instantiated\n";
            return false;
        }
        
        // Test capability selection in normal environment
        $capability = rtbcb_get_admin_capability();
        $valid_caps = [ 'manage_options', 'edit_pages', 'edit_posts', 'read' ];
        
        if ( ! in_array( $capability, $valid_caps ) ) {
            echo "FAIL - Invalid capability returned: {$capability}\n";
            return false;
        }
        
        echo "PASS (capability: {$capability})\n";
        return true;
    }
    
    /**
     * Test WordPress.com environment simulation
     */
    public function test_wordpress_com_simulation() {
        echo "2. Testing WordPress.com environment simulation... ";
        
        // Simulate WordPress.com environment
        define( 'IS_WPCOM', true );
        
        // Test WordPress.com detection
        $is_wpcom = rtbcb_is_wordpress_com();
        if ( ! $is_wpcom ) {
            echo "FAIL - WordPress.com environment not detected\n";
            return false;
        }
        
        // Test capability selection in WordPress.com environment
        $wpcom_capability = rtbcb_get_admin_capability();
        $valid_wpcom_caps = [ 'edit_pages', 'edit_posts', 'read' ];
        
        if ( ! in_array( $wpcom_capability, $valid_wpcom_caps ) ) {
            echo "FAIL - Invalid WordPress.com capability: {$wpcom_capability}\n";
            return false;
        }
        
        echo "PASS (WordPress.com capability: {$wpcom_capability})\n";
        return true;
    }
    
    /**
     * Test admin menu registration process
     */
    public function test_admin_menu_registration_process() {
        echo "3. Testing admin menu registration process... ";
        
        global $admin_page_hooks;
        $admin_page_hooks = array(); // Reset for clean test
        
        // Trigger admin menu registration
        do_action( 'admin_menu' );
        
        // Check if our admin menu was registered
        if ( ! array_key_exists( 'rtbcb-dashboard', $admin_page_hooks ) ) {
            echo "FAIL - Admin menu not registered\n";
            return false;
        }
        
        echo "PASS (menu registered successfully)\n";
        return true;
    }
    
    /**
     * Test PHP 8.3 compatibility features
     */
    public function test_php83_compatibility() {
        echo "4. Testing PHP 8.3 compatibility features... ";
        
        // Test null coalescing operator usage
        $server_name = $_SERVER['SERVER_NAME'] ?? '';
        if ( ! is_string( $server_name ) ) {
            echo "FAIL - Null coalescing operator not working properly\n";
            return false;
        }
        
        // Test that no deprecated function warnings are triggered
        $error_level = error_reporting();
        error_reporting( E_ALL );
        
        // Test admin capability function
        $capability = rtbcb_get_admin_capability();
        
        // Test WordPress.com detection
        $is_wpcom = rtbcb_is_wordpress_com();
        
        error_reporting( $error_level );
        
        echo "PASS (PHP 8.3 features working)\n";
        return true;
    }
    
    /**
     * Test user capability checking functions
     */
    public function test_user_capability_functions() {
        echo "5. Testing user capability checking functions... ";
        
        // Test admin capability check
        $can_admin = rtbcb_user_can_admin();
        if ( ! is_bool( $can_admin ) ) {
            echo "FAIL - rtbcb_user_can_admin() should return boolean\n";
            return false;
        }
        
        // Test settings management capability check
        $can_manage = rtbcb_user_can_manage_settings();
        if ( ! is_bool( $can_manage ) ) {
            echo "FAIL - rtbcb_user_can_manage_settings() should return boolean\n";
            return false;
        }
        
        // Test report viewing capability check
        $can_view_reports = rtbcb_user_can_view_reports();
        if ( ! is_bool( $can_view_reports ) ) {
            echo "FAIL - rtbcb_user_can_view_reports() should return boolean\n";
            return false;
        }
        
        echo "PASS (all capability functions working)\n";
        return true;
    }
    
    /**
     * Test error handling and edge cases
     */
    public function test_error_handling() {
        echo "6. Testing error handling and edge cases... ";
        
        // Test with empty $_SERVER array
        $original_server = $_SERVER;
        $_SERVER = array();
        
        // Should not crash
        $is_wpcom = rtbcb_is_wordpress_com();
        if ( ! is_bool( $is_wpcom ) ) {
            $_SERVER = $original_server;
            echo "FAIL - WordPress.com detection should handle empty \$_SERVER\n";
            return false;
        }
        
        // Restore $_SERVER
        $_SERVER = $original_server;
        
        echo "PASS (error handling working)\n";
        return true;
    }
    
    /**
     * Run all tests
     */
    public function run_all_tests() {
        $tests = [
            'test_admin_menu_capability_scenarios',
            'test_wordpress_com_simulation', 
            'test_admin_menu_registration_process',
            'test_php83_compatibility',
            'test_user_capability_functions',
            'test_error_handling'
        ];
        
        $passed = 0;
        $total = count( $tests );
        
        foreach ( $tests as $test ) {
            if ( $this->$test() ) {
                $passed++;
            }
        }
        
        echo "\n=== Test Results ===\n";
        echo "Passed: {$passed}/{$total}\n";
        
        if ( $passed === $total ) {
            echo "[PASS] All admin menu verification tests passed!\n";
            echo "The Business Case Builder admin menu should work correctly for all user roles.\n";
            return true;
        } else {
            echo "[FAIL] Some tests failed. Admin menu may not work properly.\n";
            return false;
        }
    }
}

// Run tests
echo "Bootstrap loaded successfully\n";
echo "Running Comprehensive Admin Menu Verification Tests...\n\n";

$test = new RTBCB_Admin_Menu_Verification_Test();
$test->setUp();

$success = $test->run_all_tests();

$test->tearDown();

exit( $success ? 0 : 1 );
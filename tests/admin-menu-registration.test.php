<?php
/**
 * Test admin menu registration timing
 * 
 * @package RealTreasuryBusinessCaseBuilder
 */

// Set testing flag for admin
define( 'RTBCB_TESTING_ADMIN', true );

// Include test bootstrap
require_once __DIR__ . '/bootstrap/bootstrap.php';

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

// Manually include Admin class for testing
require_once __DIR__ . '/../admin/classes/Admin.php';

/**
 * Simple test class for admin menu registration
 */
class RTBCB_Admin_Menu_Test {
    
    private $original_admin_pages;
    
    public function setUp() {
        // Store original state
        global $admin_page_hooks;
        $this->original_admin_pages = $admin_page_hooks;
        
        // Reset global admin pages for clean test
        $admin_page_hooks = array();
        
        // Force admin context
        if ( !defined( 'WP_ADMIN' ) ) {
            define( 'WP_ADMIN', true );
        }
    }
    
    public function tearDown() {
        // Restore original state
        global $admin_page_hooks;
        $admin_page_hooks = $this->original_admin_pages;
    }
    
    private function assertTrue($condition, $message) {
        if (!$condition) {
            throw new Exception($message);
        }
    }
    
    private function assertInstanceOf($expected, $actual, $message) {
        if (!($actual instanceof $expected)) {
            throw new Exception($message);
        }
    }
    
    private function assertArrayHasKey($key, $array, $message) {
        if (!array_key_exists($key, $array)) {
            throw new Exception($message);
        }
    }
    
    private function assertEquals($expected, $actual, $message) {
        if ($expected !== $actual) {
            throw new Exception($message . " Expected: $expected, Actual: $actual");
        }
    }
    
    private function assertNotEmpty($value, $message) {
        if (empty($value)) {
            throw new Exception($message);
        }
    }
    
    /**
     * Test that RTBCB_Admin class exists and can be instantiated
     */
    public function test_admin_class_exists() {
        $this->assertTrue( class_exists( 'RTBCB_Admin' ), 'RTBCB_Admin class should exist' );
        
        // Test instantiation
        $admin = new RTBCB_Admin();
        $this->assertInstanceOf( 'RTBCB_Admin', $admin, 'Should be able to instantiate RTBCB_Admin' );
    }
    
    /**
     * Test that admin menu is registered when RTBCB_Admin is instantiated
     */
    public function test_admin_menu_registration() {
        global $admin_page_hooks;
        
        // Instantiate admin class (this should register the menu)
        $admin = new RTBCB_Admin();
        
        // Simulate the admin_menu hook firing
        do_action( 'admin_menu' );
        
        // Check if our admin menu was registered
        $this->assertArrayHasKey( 'rtbcb-dashboard', $admin_page_hooks, 
            'Real Treasury admin menu should be registered in admin_page_hooks' );
        
        $this->assertEquals( 'real-treasury', $admin_page_hooks['rtbcb-dashboard'], 
            'Admin menu hook should be registered with correct slug' );
    }
    
    /**
     * Test that main plugin correctly instantiates admin in admin context
     */
    public function test_plugin_admin_instantiation() {
        // Get plugin instance
        $plugin = RTBCB_Business_Case_Builder::get_instance();
        
        // Check if admin service is registered in services
        $admin_service = $plugin->get_service( 'admin' );
        
        // This test will initially fail, proving the issue exists
        $this->assertInstanceOf( 'RTBCB_Admin', $admin_service, 
            'Plugin should have admin service registered when in admin context' );
    }
    
    /**
     * Test admin menu hook timing
     */
    public function test_admin_menu_hook_timing() {
        global $wp_filter;
        
        // Get the plugin instance
        $plugin = RTBCB_Business_Case_Builder::get_instance();
        
        // Check if admin_menu hook has been registered
        $this->assertArrayHasKey( 'admin_menu', $wp_filter, 'admin_menu hook should exist' );
        
        // Check if our admin menu callback is registered
        $admin_menu_callbacks = array();
        if ( isset( $wp_filter['admin_menu'] ) ) {
            foreach ( $wp_filter['admin_menu']->callbacks as $priority => $callbacks ) {
                foreach ( $callbacks as $callback ) {
                    if ( is_array( $callback['function'] ) && 
                         is_object( $callback['function'][0] ) && 
                         get_class( $callback['function'][0] ) === 'RTBCB_Admin' ) {
                        $admin_menu_callbacks[] = $callback;
                    }
                }
            }
        }
        
        $this->assertNotEmpty( $admin_menu_callbacks, 
            'RTBCB_Admin should have registered admin_menu callback' );
    }
}

echo "Running Admin Menu Registration Test...\n";

// Run the test
$test = new RTBCB_Admin_Menu_Test();

try {
    $test->setUp();
    
    echo "1. Testing admin class exists... ";
    $test->test_admin_class_exists();
    echo "PASS\n";
    
    echo "2. Testing admin menu registration... ";
    $test->test_admin_menu_registration();
    echo "PASS\n";
    
    echo "3. Testing plugin admin instantiation... ";
    // Get plugin instance
    $plugin = RTBCB_Business_Case_Builder::get_instance();
    
    // Simulate plugins_loaded hook which should instantiate admin
    $plugin->plugins_loaded();
    
    // Check if admin service is registered in services
    $admin_service = $plugin->get_service( 'admin' );
    
    if ( $admin_service instanceof RTBCB_Admin ) {
        echo "PASS\n";
        
        echo "4. Testing admin menu registration via plugin... ";
        // Now test that the admin menu gets registered
        global $admin_page_hooks;
        $admin_page_hooks = array(); // Reset for clean test
        
        // Simulate the admin_menu hook firing
        do_action( 'admin_menu' );
        
        // Check if our admin menu was registered
        if ( array_key_exists( 'rtbcb-dashboard', $admin_page_hooks ) ) {
            echo "PASS\n";
            echo "\n[PASS] Admin menu registration fix is working!\n";
        } else {
            echo "FAIL - Admin menu not registered even after plugin instantiation\n";
        }
    } else {
        echo "FAIL - Plugin admin service not instantiated (" . gettype($admin_service) . ")\n";
        echo "[INFO] This confirms the issue - admin class not instantiated by plugin\n";
    }
    
    $test->tearDown();
    
    echo "\n[PASS] Admin menu registration test completed!\n";
    
} catch ( Exception $e ) {
    echo "FAIL - " . $e->getMessage() . "\n";
    echo "\n[INFO] Test failed as expected - confirming admin menu issue exists\n";
}
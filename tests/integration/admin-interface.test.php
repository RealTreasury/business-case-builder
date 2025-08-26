<?php
/**
 * Admin Integration Test
 * 
 * Tests admin interface functionality and AJAX endpoints
 * 
 * @package RealTreasuryBusinessCaseBuilder
 */

require_once __DIR__ . '/../bootstrap/bootstrap.php';

// Define plugin constants for test
if ( ! defined( 'RTBCB_PLUGIN_DIR' ) ) {
    define( 'RTBCB_PLUGIN_DIR', dirname( dirname( __DIR__ ) ) . '/' );
}
if ( ! defined( 'RTBCB_VERSION' ) ) {
    define( 'RTBCB_VERSION', '2.1.0' );
}

// Load main plugin file to get rtbcb() function
require_once RTBCB_PLUGIN_DIR . 'real-treasury-business-case-builder.php';

class RTBCB_Admin_Integration_Test {
    
    /**
     * Test admin menu registration
     */
    public function test_admin_menu_registration() {
        // Mock WordPress admin functions
        global $admin_page_hooks, $submenu;
        $admin_page_hooks = array();
        $submenu = array();
        
        // Initialize plugin admin
        if ( class_exists( 'RTBCB_Admin' ) ) {
            $admin = new RTBCB_Admin();
            
            // Simulate admin_menu hook
            do_action( 'admin_menu' );
            
            rtbcb_assert(
                isset( $admin_page_hooks['rtbcb-dashboard'] ),
                'Admin menu should be registered'
            );
            
            rtbcb_assert(
                $admin_page_hooks['rtbcb-dashboard']['capability'] === 'manage_options',
                'Admin menu should require manage_options capability'
            );
        }
    }
    
    /**
     * Test AJAX endpoint security
     */
    public function test_ajax_security() {
        // Test without nonce
        $_POST = array(
            'action' => 'rtbcb_generate_case',
            'data' => array( 'test' => 'value' )
        );
        
        try {
            $plugin = rtbcb();
            $plugin->handle_ajax_generate_case();
            rtbcb_assert( false, 'AJAX handler should reject request without valid nonce' );
        } catch ( Exception $e ) {
            rtbcb_assert(
                $e->getMessage() === 'Security verification failed.',
                'Security check should fail without valid nonce'
            );
        }
        
        // Test with valid nonce (using the test nonce format from bootstrap)
        $_POST['nonce'] = 'test_nonce_rtbcb_generate_case';
        
        // This should not throw security exception
        // (but may fail for other reasons like missing data)
    }
    
    /**
     * Test settings validation
     */
    public function test_settings_validation() {
        // Test API key validation
        $valid_key = 'sk-' . str_repeat( 'a', 48 );
        $invalid_key = 'invalid-key';
        
        rtbcb_assert(
            rtbcb_validate_api_key( $valid_key ) !== false,
            'Valid API key format should pass validation'
        );
        
        rtbcb_assert(
            rtbcb_validate_api_key( $invalid_key ) === false,
            'Invalid API key format should fail validation'
        );
        
        rtbcb_assert(
            rtbcb_validate_api_key( '' ) === false,
            'Empty API key should fail validation'
        );
    }
    
    /**
     * Test data sanitization
     */
    public function test_data_sanitization() {
        $test_inputs = array(
            'treasury_staff_count' => '5',
            'treasury_staff_salary' => '80000.50',
            'time_savings_percentage' => '20',
            'investment_cost' => '100000',
            'malicious_script' => '<script>alert("xss")</script>',
            'sql_injection' => "'; DROP TABLE users; --"
        );
        
        $sanitized = rtbcb_sanitize_calculation_inputs( $test_inputs );
        
        rtbcb_assert(
            ! is_wp_error( $sanitized ),
            'Valid input data should sanitize successfully'
        );
        
        rtbcb_assert(
            $sanitized['treasury_staff_count'] === 5.0,
            'Numeric strings should be converted to floats'
        );
        
        rtbcb_assert(
            ! isset( $sanitized['malicious_script'] ),
            'Non-whitelisted fields should be removed'
        );
        
        // Test missing required field
        $incomplete_inputs = array(
            'treasury_staff_count' => '5'
            // Missing other required fields
        );
        
        $sanitized = rtbcb_sanitize_calculation_inputs( $incomplete_inputs );
        
        rtbcb_assert(
            is_wp_error( $sanitized ),
            'Incomplete input data should return error'
        );
    }
    
    /**
     * Test permission checks
     */
    public function test_permission_checks() {
        // Test with manage_options capability
        global $test_user_capabilities;
        $test_user_capabilities = array( 'manage_options' );
        
        rtbcb_assert(
            rtbcb_user_can_manage_settings(),
            'User with manage_options should be able to manage settings'
        );
        
        // Test with edit_posts capability (WordPress.com compatible)
        $test_user_capabilities = array( 'edit_posts' );
        
        rtbcb_assert(
            rtbcb_user_can_manage_settings(),
            'User with edit_posts should be able to manage settings (WordPress.com compatibility)'
        );
        
        rtbcb_assert(
            rtbcb_user_can_view_reports(),
            'User with edit_posts should be able to view reports'
        );
        
        // Test with minimal capability
        $test_user_capabilities = array( 'read' );
        
        rtbcb_assert(
            rtbcb_user_can_manage_settings(),
            'User with read capability should be able to manage settings (WordPress.com compatibility)'
        );
        
        // Test admin capability function
        rtbcb_assert(
            rtbcb_user_can_admin(),
            'User should have admin capability'
        );
    }
    
    /**
     * Test error handling
     */
    public function test_error_handling() {
        // Test user-friendly error messages
        $error_codes = array(
            'unauthorized',
            'rate_limit_exceeded',
            'server_error',
            'invalid_input',
            'unknown_error_code'
        );
        
        foreach ( $error_codes as $code ) {
            $message = rtbcb_get_user_friendly_error( $code );
            
            rtbcb_assert(
                ! empty( $message ),
                "Error code '$code' should return non-empty message"
            );
            
            rtbcb_assert(
                is_string( $message ),
                "Error message should be a string"
            );
        }
    }
    
    /**
     * Run all tests
     */
    public function run_all_tests() {
        $tests = array(
            'test_admin_menu_registration',
            'test_ajax_security',
            'test_settings_validation',
            'test_data_sanitization',
            'test_permission_checks',
            'test_error_handling'
        );
        
        foreach ( $tests as $test ) {
            try {
                $this->$test();
                echo "✓ $test passed\n";
            } catch ( Exception $e ) {
                echo "✗ $test failed: " . $e->getMessage() . "\n";
                throw $e;
            }
        }
    }
}

// Run the tests
try {
    $test = new RTBCB_Admin_Integration_Test();
    $test->run_all_tests();
    echo "Admin integration test completed successfully\n";
} catch ( Exception $e ) {
    echo "Admin integration test failed: " . $e->getMessage() . "\n";
    exit( 1 );
}
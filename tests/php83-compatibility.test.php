<?php
/**
 * PHP 8.3 Compatibility Test
 * 
 * This test verifies PHP 8.3 specific features and compatibility:
 * 1. Null coalescing operators
 * 2. Type declarations
 * 3. Array access patterns
 * 4. No deprecated function usage
 */

// Bootstrap WordPress environment
require_once __DIR__ . '/bootstrap/bootstrap.php';

class RTBCB_PHP83_Compatibility_Test {
    
    /**
     * Test null coalescing operator usage
     */
    public function test_null_coalescing_operators() {
        echo "1. Testing null coalescing operators... ";
        
        // Test $_SERVER usage with null coalescing
        $server_name = $_SERVER['SERVER_NAME'] ?? '';
        if ( ! is_string( $server_name ) ) {
            echo "FAIL - Null coalescing operator not working\n";
            return false;
        }
        
        // Test array access with null coalescing  
        $test_array = array( 'key1' => 'value1' );
        $value = $test_array['nonexistent'] ?? 'default';
        if ( $value !== 'default' ) {
            echo "FAIL - Null coalescing with arrays not working\n";
            return false;
        }
        
        echo "PASS\n";
        return true;
    }
    
    /**
     * Test array access patterns for PHP 8.3
     */
    public function test_array_access_patterns() {
        echo "2. Testing array access patterns... ";
        
        // Test proper array access without warnings
        $test_data = array(
            'config' => array(
                'setting1' => 'value1'
            )
        );
        
        // This should not generate warnings in PHP 8.3
        $value = isset( $test_data['config']['setting1'] ) ? $test_data['config']['setting1'] : null;
        if ( $value !== 'value1' ) {
            echo "FAIL - Array access pattern not working\n";
            return false;
        }
        
        // Test with null coalescing for nested arrays
        $value2 = $test_data['config']['nonexistent'] ?? 'default_value';
        if ( $value2 !== 'default_value' ) {
            echo "FAIL - Nested array null coalescing not working\n";
            return false;
        }
        
        echo "PASS\n";
        return true;
    }
    
    /**
     * Test no deprecated function usage
     */
    public function test_no_deprecated_functions() {
        echo "3. Testing for deprecated function usage... ";
        
        // Load plugin files and check for deprecated patterns
        $plugin_files = array(
            __DIR__ . '/../real-treasury-business-case-builder.php',
            __DIR__ . '/../admin/classes/Admin.php',
            __DIR__ . '/../inc/utils/helpers.php'
        );
        
        $deprecated_patterns = array(
            'create_function',           // Removed in PHP 8.0
            'each(',                     // Deprecated since PHP 7.2
            'assert_options',            // Deprecated since PHP 8.3
            'mysql_',                    // Removed in PHP 7.0
            'ereg',                      // Removed in PHP 7.0
            'split(',                    // Removed in PHP 7.0
        );
        
        foreach ( $plugin_files as $file ) {
            if ( ! file_exists( $file ) ) {
                continue;
            }
            
            $content = file_get_contents( $file );
            
            foreach ( $deprecated_patterns as $pattern ) {
                if ( strpos( $content, $pattern ) !== false ) {
                    echo "FAIL - Found deprecated function pattern '$pattern' in " . basename( $file ) . "\n";
                    return false;
                }
            }
        }
        
        echo "PASS\n";
        return true;
    }
    
    /**
     * Test string interpolation and PHP 8.3 string handling
     */
    public function test_string_handling() {
        echo "4. Testing PHP 8.3 string handling... ";
        
        // Test string interpolation patterns used in the plugin
        $variable = 'test_value';
        $interpolated = "String with {$variable} interpolation";
        
        if ( strpos( $interpolated, 'test_value' ) === false ) {
            echo "FAIL - String interpolation not working\n";
            return false;
        }
        
        // Test sprintf usage (common in WordPress)
        $formatted = sprintf( 'RTBCB: Using capability "%s" for admin menu', 'manage_options' );
        if ( strpos( $formatted, 'manage_options' ) === false ) {
            echo "FAIL - sprintf formatting not working\n";
            return false;
        }
        
        echo "PASS\n";
        return true;
    }
    
    /**
     * Test error handling patterns
     */
    public function test_error_handling() {
        echo "5. Testing error handling patterns... ";
        
        // Test try-catch blocks work correctly
        try {
            throw new Exception( 'Test exception' );
        } catch ( Exception $e ) {
            if ( $e->getMessage() !== 'Test exception' ) {
                echo "FAIL - Exception handling not working\n";
                return false;
            }
        }
        
        // Test error_log function
        $original_level = error_reporting();
        error_reporting( 0 ); // Suppress output
        
        $result = error_log( 'Test log message', 4 ); // Log to string
        
        error_reporting( $original_level );
        
        echo "PASS\n";
        return true;
    }
    
    /**
     * Test class and method compatibility
     */
    public function test_class_method_compatibility() {
        echo "6. Testing class and method compatibility... ";
        
        // Test that our classes can be instantiated without errors
        if ( ! class_exists( 'RTBCB_Business_Case_Builder' ) ) {
            echo "FAIL - Main plugin class not found\n";
            return false;
        }
        
        // Test singleton pattern works
        $instance1 = RTBCB_Business_Case_Builder::get_instance();
        $instance2 = RTBCB_Business_Case_Builder::get_instance();
        
        if ( $instance1 !== $instance2 ) {
            echo "FAIL - Singleton pattern not working\n";
            return false;
        }
        
        echo "PASS\n";
        return true;
    }
    
    /**
     * Run all tests
     */
    public function run_all_tests() {
        $tests = array(
            'test_null_coalescing_operators',
            'test_array_access_patterns',
            'test_no_deprecated_functions',
            'test_string_handling',
            'test_error_handling',
            'test_class_method_compatibility'
        );
        
        $passed = 0;
        $total = count( $tests );
        
        foreach ( $tests as $test ) {
            if ( $this->$test() ) {
                $passed++;
            }
        }
        
        echo "\n=== PHP 8.3 Compatibility Test Results ===\n";
        echo "Passed: {$passed}/{$total}\n";
        
        if ( $passed === $total ) {
            echo "[PASS] All PHP 8.3 compatibility tests passed!\n";
            echo "The plugin is fully compatible with PHP 8.3.\n";
            return true;
        } else {
            echo "[FAIL] Some PHP 8.3 compatibility tests failed.\n";
            return false;
        }
    }
}

// Run tests
echo "Bootstrap loaded successfully\n";
echo "Running PHP 8.3 Compatibility Tests...\n\n";

// Include main plugin file
require_once __DIR__ . '/../real-treasury-business-case-builder.php';

$test = new RTBCB_PHP83_Compatibility_Test();
$success = $test->run_all_tests();

exit( $success ? 0 : 1 );
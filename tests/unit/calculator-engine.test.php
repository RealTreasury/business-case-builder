<?php
/**
 * Calculator Engine Unit Test
 * 
 * Tests core ROI calculation logic and validation
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

// Load required dependencies for new calculator
require_once RTBCB_PLUGIN_DIR . 'inc/class-rtbcb-error-handler.php';
require_once RTBCB_PLUGIN_DIR . 'inc/class-rtbcb-performance-monitor.php';
require_once RTBCB_PLUGIN_DIR . 'inc/class-rtbcb-calculator.php';

class RTBCB_Calculator_Test {
    
    /**
     * Test basic ROI calculation with new calculator
     */
    public function test_basic_roi_calculation() {
        // Initialize calculator static dependencies
        RTBCB_Calculator::initialize();
        
        // Use new field structure that matches rebuilt calculator
        $inputs = array(
            'company_name' => 'Test Company',
            'industry' => 'banking',
            'company_size' => 'medium',
            'hours_reconciliation' => 10,
            'hours_cash_positioning' => 5,
            'num_banks' => 3,
            'ftes' => 2
        );
        
        $result = RTBCB_Calculator::calculate_roi( $inputs );
        
        rtbcb_assert(
            is_array( $result ),
            'ROI calculation should return an array'
        );
        
        rtbcb_assert(
            isset( $result['conservative'] ),
            'Result should include conservative scenario'
        );
        
        rtbcb_assert(
            isset( $result['base'] ),
            'Result should include base scenario'
        );
        
        rtbcb_assert(
            isset( $result['optimistic'] ),
            'Result should include optimistic scenario'
        );
        
        // Check that each scenario has the new expected structure
        foreach ( array( 'conservative', 'base', 'optimistic' ) as $scenario ) {
            rtbcb_assert(
                isset( $result[ $scenario ]['annual_benefits']['total'] ),
                "Scenario '$scenario' should include total annual benefits"
            );
            
            rtbcb_assert(
                is_numeric( $result[ $scenario ]['annual_benefits']['total'] ),
                "Total annual benefit should be numeric in '$scenario' scenario"
            );
            
            rtbcb_assert(
                isset( $result[ $scenario ]['financial_metrics']['roi_percentage'] ),
                "Scenario '$scenario' should include ROI percentage"
            );
            
            rtbcb_assert(
                isset( $result[ $scenario ]['financial_metrics']['npv'] ),
                "Scenario '$scenario' should include NPV"
            );
        }
    }
    
    /**
     * Test input validation with new requirements
     */
    public function test_input_validation() {
        // Initialize calculator
        RTBCB_Calculator::initialize();
        
        // Test with missing required fields - should return WP_Error
        $result = RTBCB_Calculator::calculate_roi( array() );
        
        rtbcb_assert(
            is_wp_error( $result ),
            'Calculator should return error for missing required fields'
        );
        
        // Test with minimal valid inputs - should work
        $minimal_inputs = array(
            'company_name' => 'Test Company',
            'industry' => 'default', 
            'company_size' => 'medium'
        );
        
        $result = RTBCB_Calculator::calculate_roi( $minimal_inputs );
        
        rtbcb_assert(
            is_array( $result ),
            'Calculator should handle minimal valid inputs'
        );
        
        rtbcb_assert(
            isset( $result['base'] ),
            'Calculator should return base scenario for minimal inputs'
        );
        
        // Test with minimal valid inputs - should work
        $minimal_inputs = array(
            'company_name' => 'Test Company',
            'industry' => 'default', 
            'company_size' => 'medium'
        );
        
        $result = RTBCB_Calculator::calculate_roi( $minimal_inputs );
        
        rtbcb_assert(
            is_array( $result ),
            'Calculator should handle minimal valid inputs'
        );
        
        rtbcb_assert(
            isset( $result['base'] ),
            'Calculator should return base scenario for minimal inputs'
        );
    }
    
    /**
     * Test edge cases
     */
    public function test_edge_cases() {
        // Test with zero investment cost
        $zero_investment = array(
            'treasury_staff_count' => 5,
            'treasury_staff_salary' => 80000,
            'investment_cost' => 0
        );
        
        $result = RTBCB_Calculator::calculate_roi( $zero_investment );
        
        rtbcb_assert(
            is_array( $result ),
            'Zero investment should be handled gracefully'
        );
        
        // Test with very high values
        $high_values = array(
            'treasury_staff_count' => 1000,
            'treasury_staff_salary' => 200000,
            'investment_cost' => 10000000
        );
        
        $result = RTBCB_Calculator::calculate_roi( $high_values );
        
        rtbcb_assert(
            is_array( $result ),
            'High values should be handled appropriately'
        );
    }
    
    /**
     * Test scenario calculations
     */
    public function test_scenario_calculations() {
        $base_inputs = array(
            'treasury_staff_count' => 5,
            'treasury_staff_salary' => 80000,
            'investment_cost' => 100000,
            'industry' => 'financial_services'
        );
        
        $result = RTBCB_Calculator::calculate_roi( $base_inputs );
        
        rtbcb_assert(
            isset( $result['conservative'] ),
            'Conservative scenario should be calculated'
        );
        
        rtbcb_assert(
            isset( $result['optimistic'] ),
            'Optimistic scenario should be calculated'
        );
        
        rtbcb_assert(
            isset( $result['base'] ),
            'Base scenario should be calculated'
        );
        
        // Verify scenario ordering if ROI values are present
        if ( isset( $result['conservative']['total_annual_benefit'] ) && 
             isset( $result['base']['total_annual_benefit'] ) && 
             isset( $result['optimistic']['total_annual_benefit'] ) ) {
            
            rtbcb_assert(
                $result['conservative']['total_annual_benefit'] <= $result['optimistic']['total_annual_benefit'],
                'Conservative benefits should be less than or equal to optimistic'
            );
        }
    }
    
    /**
     * Test calculation accuracy
     */
    public function test_calculation_accuracy() {
        $inputs = array(
            'treasury_staff_count' => 10,
            'treasury_staff_salary' => 100000,
            'investment_cost' => 200000,
            'industry' => 'financial_services'
        );
        
        $result = RTBCB_Calculator::calculate_roi( $inputs );
        
        rtbcb_assert(
            is_array( $result ),
            'Calculator should return array result'
        );
        
        // Check that numeric values are reasonable
        foreach ( array( 'conservative', 'base', 'optimistic' ) as $scenario ) {
            if ( isset( $result[ $scenario ]['total_annual_benefit'] ) ) {
                rtbcb_assert(
                    is_numeric( $result[ $scenario ]['total_annual_benefit'] ),
                    "Total annual benefit should be numeric in $scenario scenario"
                );
                
                rtbcb_assert(
                    $result[ $scenario ]['total_annual_benefit'] >= 0,
                    "Total annual benefit should be non-negative in $scenario scenario"
                );
            }
        }
    }
    
    /**
     * Test data formatting
     */
    public function test_data_formatting() {
        $inputs = array(
            'treasury_staff_count' => 5,
            'treasury_staff_salary' => 80000,
            'investment_cost' => 100000,
            'industry' => 'financial_services'
        );
        
        $result = RTBCB_Calculator::calculate_roi( $inputs );
        
        rtbcb_assert(
            is_array( $result ),
            'Result should be an array'
        );
        
        // Check that we have the expected scenario structure
        $required_scenarios = array( 'conservative', 'base', 'optimistic' );
        foreach ( $required_scenarios as $scenario ) {
            rtbcb_assert(
                isset( $result[ $scenario ] ),
                "Result should include $scenario scenario"
            );
            
            rtbcb_assert(
                is_array( $result[ $scenario ] ),
                "Scenario $scenario should be an array"
            );
        }
    }
    
    /**
     * Run all tests
     */
    public function run_all_tests() {
        $tests = array(
            'test_basic_roi_calculation',
            'test_input_validation',
            'test_edge_cases',
            'test_scenario_calculations',
            'test_calculation_accuracy',
            'test_data_formatting'
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
    $test = new RTBCB_Calculator_Test();
    $test->run_all_tests();
    echo "Calculator engine test completed successfully\n";
} catch ( Exception $e ) {
    echo "Calculator engine test failed: " . $e->getMessage() . "\n";
    exit( 1 );
}
<?php
/**
 * Calculator Engine Unit Test
 * 
 * Tests core ROI calculation logic and validation
 * 
 * @package RealTreasuryBusinessCaseBuilder
 */

require_once __DIR__ . '/../bootstrap/bootstrap.php';

class RTBCB_Calculator_Test {
    
    /**
     * Test basic ROI calculation
     */
    public function test_basic_roi_calculation() {
        $inputs = array(
            'treasury_staff_count' => 5,
            'treasury_staff_salary' => 80000,
            'time_savings_percentage' => 20,
            'error_reduction_percentage' => 15,
            'compliance_cost_savings' => 50000,
            'investment_cost' => 100000,
            'industry' => 'financial_services'
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
        
        // Check that each scenario has the expected structure
        foreach ( array( 'conservative', 'base', 'optimistic' ) as $scenario ) {
            rtbcb_assert(
                isset( $result[ $scenario ]['total_annual_benefit'] ),
                "Scenario '$scenario' should include total annual benefit"
            );
            
            rtbcb_assert(
                is_numeric( $result[ $scenario ]['total_annual_benefit'] ),
                "Total annual benefit should be numeric in '$scenario' scenario"
            );
            
            rtbcb_assert(
                isset( $result[ $scenario ]['roi_percentage'] ),
                "Scenario '$scenario' should include ROI percentage"
            );
        }
    }
    
    /**
     * Test input validation
     */
    public function test_input_validation() {
        // Test with empty inputs
        $result = RTBCB_Calculator::calculate_roi( array() );
        
        rtbcb_assert(
            is_array( $result ),
            'Calculator should handle empty inputs gracefully'
        );
        
        // Test with minimal valid inputs
        $minimal_inputs = array(
            'treasury_staff_count' => 1,
            'treasury_staff_salary' => 50000,
            'investment_cost' => 10000
        );
        
        $result = RTBCB_Calculator::calculate_roi( $minimal_inputs );
        
        rtbcb_assert(
            is_array( $result ),
            'Calculator should handle minimal inputs'
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
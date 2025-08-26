<?php
/**
 * OpenAI API Connection Test
 * 
 * Tests basic connectivity to OpenAI API with authentication validation
 * 
 * @package RealTreasuryBusinessCaseBuilder
 */

require_once __DIR__ . '/../bootstrap/bootstrap.php';

class RTBCB_OpenAI_Connection_Test {
    
    private $api_key;
    
    public function __construct() {
        $this->api_key = getenv( 'OPENAI_API_KEY' );
        
        if ( empty( $this->api_key ) ) {
            echo "Test skipped: OPENAI_API_KEY environment variable not set\n";
            exit( 0 );
        }
    }
    
    /**
     * Test API key validation
     */
    public function test_api_key_validation() {
        // Test with valid API key format
        $valid_key = $this->api_key;
        rtbcb_assert( 
            preg_match( '/^sk-[a-zA-Z0-9]{48,}$/', $valid_key ),
            'API key should match OpenAI format'
        );
        
        // Test with invalid API key format
        $invalid_key = 'invalid-key';
        rtbcb_assert(
            ! preg_match( '/^sk-[a-zA-Z0-9]{48,}$/', $invalid_key ),
            'Invalid API key should not match OpenAI format'
        );
    }
    
    /**
     * Test API connectivity
     */
    public function test_api_connectivity() {
        $llm = new RTBCB_LLM();
        
        // Set the API key
        update_option( 'rtbcb_openai_api_key', $this->api_key );
        
        // Test a simple API call
        $response = $llm->generate_narrative( 
            'Test prompt for connectivity check',
            array( 'model' => 'gpt-4o-mini' )
        );
        
        rtbcb_assert(
            ! is_wp_error( $response ),
            'API call should succeed with valid key: ' . ( is_wp_error( $response ) ? $response->get_error_message() : 'OK' )
        );
        
        rtbcb_assert(
            ! empty( $response ),
            'API response should not be empty'
        );
    }
    
    /**
     * Test API error handling
     */
    public function test_api_error_handling() {
        $llm = new RTBCB_LLM();
        
        // Test with invalid API key
        update_option( 'rtbcb_openai_api_key', 'sk-invalid-key-for-testing' );
        
        $response = $llm->generate_narrative( 
            'Test prompt for error handling',
            array( 'model' => 'gpt-4o-mini' )
        );
        
        rtbcb_assert(
            is_wp_error( $response ),
            'API call with invalid key should return WP_Error'
        );
        
        rtbcb_assert(
            $response->get_error_code() === 'api_error',
            'Error code should be api_error'
        );
    }
    
    /**
     * Test model availability
     */
    public function test_model_availability() {
        $llm = new RTBCB_LLM();
        update_option( 'rtbcb_openai_api_key', $this->api_key );
        
        $models_to_test = array(
            'gpt-4o-mini',
            'gpt-4o',
            'gpt-4',
            'gpt-3.5-turbo'
        );
        
        foreach ( $models_to_test as $model ) {
            $response = $llm->generate_narrative(
                'Test prompt for model: ' . $model,
                array( 'model' => $model )
            );
            
            // Note: We expect some models might not be available, so we just check format
            if ( ! is_wp_error( $response ) ) {
                rtbcb_assert(
                    is_string( $response ) && ! empty( $response ),
                    "Model $model should return valid response"
                );
            }
        }
    }
    
    /**
     * Test rate limiting handling
     */
    public function test_rate_limiting() {
        $llm = new RTBCB_LLM();
        update_option( 'rtbcb_openai_api_key', $this->api_key );
        
        // Make multiple rapid requests to test rate limiting
        $requests = 3;
        $responses = array();
        
        for ( $i = 0; $i < $requests; $i++ ) {
            $response = $llm->generate_narrative(
                "Rate limit test request #$i",
                array( 'model' => 'gpt-4o-mini' )
            );
            $responses[] = $response;
            
            // Small delay between requests
            usleep( 100000 ); // 0.1 seconds
        }
        
        // Check that at least some requests succeeded
        $success_count = 0;
        foreach ( $responses as $response ) {
            if ( ! is_wp_error( $response ) ) {
                $success_count++;
            }
        }
        
        rtbcb_assert(
            $success_count > 0,
            'At least one request should succeed in rate limit test'
        );
    }
    
    /**
     * Run all tests
     */
    public function run_all_tests() {
        $tests = array(
            'test_api_key_validation',
            'test_api_connectivity', 
            'test_api_error_handling',
            'test_model_availability',
            'test_rate_limiting'
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
    $test = new RTBCB_OpenAI_Connection_Test();
    $test->run_all_tests();
    echo "OpenAI API connection test completed successfully\n";
} catch ( Exception $e ) {
    echo "OpenAI API connection test failed: " . $e->getMessage() . "\n";
    exit( 1 );
}
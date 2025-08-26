<?php
/**
 * OpenAI API Error Handling Test
 * 
 * Tests comprehensive error scenarios for OpenAI integration
 * 
 * @package RealTreasuryBusinessCaseBuilder
 */

require_once __DIR__ . '/../bootstrap/bootstrap.php';

class RTBCB_OpenAI_Error_Test {
    
    /**
     * Test network errors
     */
    public function test_network_errors() {
        $llm = new RTBCB_LLM();
        
        // Mock network failure
        rtbcb_mock_http_response( new WP_Error( 'http_request_failed', 'cURL error 6: Could not resolve host' ) );
        
        $response = $llm->generate_narrative( 'Test prompt', array( 'model' => 'gpt-4o-mini' ) );
        
        rtbcb_assert(
            is_wp_error( $response ),
            'Network error should return WP_Error'
        );
        
        rtbcb_assert(
            $response->get_error_code() === 'api_error',
            'Network error should have api_error code'
        );
        
        rtbcb_assert(
            strpos( $response->get_error_message(), 'API request failed' ) !== false,
            'Error message should mention API request failure'
        );
    }
    
    /**
     * Test authentication errors
     */
    public function test_authentication_errors() {
        $llm = new RTBCB_LLM();
        
        // Mock 401 Unauthorized response
        rtbcb_mock_http_response( array(
            'response' => array( 'code' => 401 ),
            'body' => json_encode( array(
                'error' => array(
                    'message' => 'Invalid API key provided',
                    'type' => 'invalid_request_error'
                )
            ) )
        ) );
        
        $response = $llm->generate_narrative( 'Test prompt', array( 'model' => 'gpt-4o-mini' ) );
        
        rtbcb_assert(
            is_wp_error( $response ),
            'Authentication error should return WP_Error'
        );
        
        rtbcb_assert(
            $response->get_error_code() === 'api_error',
            'Authentication error should have api_error code'
        );
        
        rtbcb_assert(
            strpos( $response->get_error_message(), 'Invalid API key' ) !== false,
            'Error message should mention invalid API key'
        );
    }
    
    /**
     * Test rate limit errors
     */
    public function test_rate_limit_errors() {
        $llm = new RTBCB_LLM();
        
        // Mock 429 Rate Limit response
        rtbcb_mock_http_response( array(
            'response' => array( 'code' => 429 ),
            'headers' => array(
                'retry-after' => '60'
            ),
            'body' => json_encode( array(
                'error' => array(
                    'message' => 'Rate limit exceeded',
                    'type' => 'rate_limit_error'
                )
            ) )
        ) );
        
        $response = $llm->generate_narrative( 'Test prompt', array( 'model' => 'gpt-4o-mini' ) );
        
        rtbcb_assert(
            is_wp_error( $response ),
            'Rate limit error should return WP_Error'
        );
        
        rtbcb_assert(
            $response->get_error_code() === 'api_error',
            'Rate limit error should have api_error code'
        );
        
        rtbcb_assert(
            strpos( $response->get_error_message(), 'Rate limit' ) !== false,
            'Error message should mention rate limit'
        );
    }
    
    /**
     * Test quota exceeded errors
     */
    public function test_quota_exceeded_errors() {
        $llm = new RTBCB_LLM();
        
        // Mock 429 Quota Exceeded response
        rtbcb_mock_http_response( array(
            'response' => array( 'code' => 429 ),
            'body' => json_encode( array(
                'error' => array(
                    'message' => 'You exceeded your current quota',
                    'type' => 'quota_exceeded'
                )
            ) )
        ) );
        
        $response = $llm->generate_narrative( 'Test prompt', array( 'model' => 'gpt-4o-mini' ) );
        
        rtbcb_assert(
            is_wp_error( $response ),
            'Quota exceeded error should return WP_Error'
        );
        
        rtbcb_assert(
            strpos( $response->get_error_message(), 'quota' ) !== false,
            'Error message should mention quota'
        );
    }
    
    /**
     * Test server errors
     */
    public function test_server_errors() {
        $llm = new RTBCB_LLM();
        
        // Mock 500 Internal Server Error response
        rtbcb_mock_http_response( array(
            'response' => array( 'code' => 500 ),
            'body' => 'Internal Server Error'
        ) );
        
        $response = $llm->generate_narrative( 'Test prompt', array( 'model' => 'gpt-4o-mini' ) );
        
        rtbcb_assert(
            is_wp_error( $response ),
            'Server error should return WP_Error'
        );
        
        rtbcb_assert(
            $response->get_error_code() === 'api_error',
            'Server error should have api_error code'
        );
    }
    
    /**
     * Test malformed JSON response
     */
    public function test_malformed_json_response() {
        $llm = new RTBCB_LLM();
        
        // Mock response with malformed JSON
        rtbcb_mock_http_response( array(
            'response' => array( 'code' => 200 ),
            'body' => 'Invalid JSON response {'
        ) );
        
        $response = $llm->generate_narrative( 'Test prompt', array( 'model' => 'gpt-4o-mini' ) );
        
        rtbcb_assert(
            is_wp_error( $response ),
            'Malformed JSON should return WP_Error'
        );
        
        rtbcb_assert(
            strpos( $response->get_error_message(), 'Invalid response' ) !== false,
            'Error message should mention invalid response'
        );
    }
    
    /**
     * Test empty response
     */
    public function test_empty_response() {
        $llm = new RTBCB_LLM();
        
        // Mock empty response
        rtbcb_mock_http_response( array(
            'response' => array( 'code' => 200 ),
            'body' => ''
        ) );
        
        $response = $llm->generate_narrative( 'Test prompt', array( 'model' => 'gpt-4o-mini' ) );
        
        rtbcb_assert(
            is_wp_error( $response ),
            'Empty response should return WP_Error'
        );
    }
    
    /**
     * Test missing choices in response
     */
    public function test_missing_choices_response() {
        $llm = new RTBCB_LLM();
        
        // Mock response without choices
        rtbcb_mock_http_response( array(
            'response' => array( 'code' => 200 ),
            'body' => json_encode( array(
                'id' => 'test-response',
                'object' => 'chat.completion'
            ) )
        ) );
        
        $response = $llm->generate_narrative( 'Test prompt', array( 'model' => 'gpt-4o-mini' ) );
        
        rtbcb_assert(
            is_wp_error( $response ),
            'Response without choices should return WP_Error'
        );
    }
    
    /**
     * Run all tests
     */
    public function run_all_tests() {
        $tests = array(
            'test_network_errors',
            'test_authentication_errors',
            'test_rate_limit_errors',
            'test_quota_exceeded_errors',
            'test_server_errors',
            'test_malformed_json_response',
            'test_empty_response',
            'test_missing_choices_response'
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
    $test = new RTBCB_OpenAI_Error_Test();
    $test->run_all_tests();
    echo "OpenAI API error handling test completed successfully\n";
} catch ( Exception $e ) {
    echo "OpenAI API error handling test failed: " . $e->getMessage() . "\n";
    exit( 1 );
}
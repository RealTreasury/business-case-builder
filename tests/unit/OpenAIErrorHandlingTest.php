<?php
/**
 * Tests for OpenAI error handling in the LLM class
 *
 * @package RealTreasuryBusinessCaseBuilder
 */

use PHPUnit\Framework\TestCase;
use Yoast\PHPUnitPolyfills\TestCases\TestCase as PolyfillTestCase;

/**
 * Test OpenAI error handling functionality.
 */
class OpenAIErrorHandlingTest extends PolyfillTestCase {

    /**
     * Mock LLM instance for testing.
     *
     * @var RTBCB_LLM
     */
    private $llm;

    /**
     * Reflection method for accessing private call_openai method.
     *
     * @var ReflectionMethod
     */
    private $call_openai_method;

    /**
     * Set up test environment.
     */
    public function set_up() {
        parent::set_up();
        
        // Skip if no API key is available
        if ( empty( getenv( 'OPENAI_API_KEY' ) ) ) {
            $this->markTestSkipped( 'OPENAI_API_KEY environment variable not set' );
        }

        // Load the LLM class if not already loaded
        if ( ! class_exists( 'RTBCB_LLM' ) ) {
            require_once dirname( __DIR__, 2 ) . '/inc/class-rtbcb-llm.php';
        }

        $this->llm = new RTBCB_LLM();
        $this->call_openai_method = new ReflectionMethod( RTBCB_LLM::class, 'call_openai' );
        $this->call_openai_method->setAccessible( true );
    }

    /**
     * Test that network errors return WP_Error with api_error code.
     */
    public function test_network_error_returns_wp_error() {
        // Mock wp_remote_post to return a WP_Error
        $mock_response = new WP_Error( 'http_request_failed', 'Network down' );
        
        // Use filter to mock the HTTP response
        add_filter( 'pre_http_request', function() use ( $mock_response ) {
            return $mock_response;
        }, 10, 3 );

        $result = $this->call_openai_method->invoke( $this->llm, 'gpt-4', 'test prompt' );

        // Clean up filter
        remove_all_filters( 'pre_http_request' );

        $this->assertTrue( is_wp_error( $result ), 'Expected WP_Error for network failure' );
        $this->assertEquals( 'api_error', $result->get_error_code(), 'Expected api_error code' );
    }

    /**
     * Test that invalid credentials return WP_Error with api_error code.
     */
    public function test_invalid_credentials_returns_wp_error() {
        // Mock wp_remote_post to return unauthorized response
        $mock_response = [
            'response' => [ 'code' => 401 ],
            'body'     => json_encode( [ 'error' => [ 'message' => 'Unauthorized' ] ] ),
        ];

        add_filter( 'pre_http_request', function() use ( $mock_response ) {
            return $mock_response;
        }, 10, 3 );

        $result = $this->call_openai_method->invoke( $this->llm, 'gpt-4', 'test prompt' );

        // Clean up filter
        remove_all_filters( 'pre_http_request' );

        $this->assertTrue( is_wp_error( $result ), 'Expected WP_Error for unauthorized request' );
        $this->assertEquals( 'api_error', $result->get_error_code(), 'Expected api_error code' );
        $this->assertStringContainsString( 'Unauthorized', $result->get_error_message(), 'Expected unauthorized message' );
    }

    /**
     * Test rate limiting error handling.
     */
    public function test_rate_limiting_error_handling() {
        // Mock wp_remote_post to return rate limit response
        $mock_response = [
            'response' => [ 'code' => 429 ],
            'body'     => json_encode( [ 'error' => [ 'message' => 'Rate limit exceeded' ] ] ),
        ];

        add_filter( 'pre_http_request', function() use ( $mock_response ) {
            return $mock_response;
        }, 10, 3 );

        $result = $this->call_openai_method->invoke( $this->llm, 'gpt-4', 'test prompt' );

        // Clean up filter
        remove_all_filters( 'pre_http_request' );

        $this->assertTrue( is_wp_error( $result ), 'Expected WP_Error for rate limit' );
        $this->assertEquals( 'api_error', $result->get_error_code(), 'Expected api_error code' );
        $this->assertStringContainsString( 'Rate limit exceeded', $result->get_error_message(), 'Expected rate limit message' );
    }

    /**
     * Test malformed JSON response handling.
     */
    public function test_malformed_json_response_handling() {
        // Mock wp_remote_post to return malformed JSON
        $mock_response = [
            'response' => [ 'code' => 200 ],
            'body'     => 'invalid json response',
        ];

        add_filter( 'pre_http_request', function() use ( $mock_response ) {
            return $mock_response;
        }, 10, 3 );

        $result = $this->call_openai_method->invoke( $this->llm, 'gpt-4', 'test prompt' );

        // Clean up filter
        remove_all_filters( 'pre_http_request' );

        $this->assertTrue( is_wp_error( $result ), 'Expected WP_Error for malformed JSON' );
        $this->assertEquals( 'api_error', $result->get_error_code(), 'Expected api_error code' );
    }
}
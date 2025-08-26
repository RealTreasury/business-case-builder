<?php
/**
 * Integration tests for error handling functionality
 *
 * @package RealTreasuryBusinessCaseBuilder
 */

require_once __DIR__ . '/../bootstrap.php';

class RTBCB_Error_Handler_Integration_Test extends WP_UnitTestCase {

    public function setUp(): void {
        parent::setUp();
        
        // Clear error log before each test
        RTBCB_Error_Handler::clear_error_log();
    }

    public function tearDown(): void {
        // Clean up after each test
        RTBCB_Error_Handler::clear_error_log();
        
        parent::tearDown();
    }

    /**
     * Test basic error logging functionality.
     */
    public function test_basic_error_logging() {
        $message = 'Test error message';
        $level = RTBCB_Error_Handler::ERROR_LEVEL_ERROR;
        $source = 'TEST';
        
        $result = RTBCB_Error_Handler::log_error( $message, $level, [], $source );
        
        $this->assertTrue( $result, 'Error logging should return true' );
        
        $recent_errors = RTBCB_Error_Handler::get_recent_errors( 10 );
        $this->assertCount( 1, $recent_errors, 'Should have one logged error' );
        
        $logged_error = $recent_errors[0];
        $this->assertEquals( $message, $logged_error['message'] );
        $this->assertEquals( $level, $logged_error['level'] );
        $this->assertEquals( $source, $logged_error['source'] );
        $this->assertIsInt( $logged_error['timestamp'] );
        $this->assertIsInt( $logged_error['user_id'] );
    }

    /**
     * Test error logging with context data.
     */
    public function test_error_logging_with_context() {
        $message = 'Test error with context';
        $context = [
            'user_action' => 'generate_business_case',
            'model' => 'gpt-5-mini',
            'error_code' => 429,
        ];
        
        RTBCB_Error_Handler::log_error( $message, RTBCB_Error_Handler::ERROR_LEVEL_WARNING, $context, 'LLM_API' );
        
        $recent_errors = RTBCB_Error_Handler::get_recent_errors( 1 );
        $logged_error = $recent_errors[0];
        
        $this->assertEquals( $context, $logged_error['context'] );
    }

    /**
     * Test LLM error handling.
     */
    public function test_llm_error_handling() {
        $error_message = 'Rate limit exceeded';
        $model = 'gpt-5-mini';
        $prompt = 'Test prompt';
        $response_code = 429;
        $wp_error = new WP_Error( 'rate_limit', 'Rate limit exceeded' );
        
        RTBCB_Error_Handler::handle_llm_error( $error_message, $model, $prompt, $response_code, $wp_error );
        
        $recent_errors = RTBCB_Error_Handler::get_recent_errors( 1 );
        $logged_error = $recent_errors[0];
        
        $this->assertEquals( $error_message, $logged_error['message'] );
        $this->assertEquals( 'LLM_API', $logged_error['source'] );
        $this->assertEquals( RTBCB_Error_Handler::ERROR_LEVEL_WARNING, $logged_error['level'] );
        $this->assertEquals( $model, $logged_error['context']['model'] );
        $this->assertEquals( $response_code, $logged_error['context']['response_code'] );
        $this->assertEquals( 'rate_limit', $logged_error['context']['wp_error_code'] );
    }

    /**
     * Test validation error handling.
     */
    public function test_validation_error_handling() {
        $field_name = 'company_name';
        $error_message = 'Field cannot be empty';
        $input_value = '';
        
        RTBCB_Error_Handler::handle_validation_error( $field_name, $error_message, $input_value );
        
        $recent_errors = RTBCB_Error_Handler::get_recent_errors( 1 );
        $logged_error = $recent_errors[0];
        
        $this->assertEquals( "Validation failed for {$field_name}: {$error_message}", $logged_error['message'] );
        $this->assertEquals( 'VALIDATION', $logged_error['source'] );
        $this->assertEquals( RTBCB_Error_Handler::ERROR_LEVEL_WARNING, $logged_error['level'] );
        $this->assertEquals( $field_name, $logged_error['context']['field'] );
        $this->assertEquals( 'string', $logged_error['context']['input_type'] );
    }

    /**
     * Test database error handling.
     */
    public function test_database_error_handling() {
        $operation = 'INSERT';
        $error_message = 'Duplicate key error';
        $query = 'INSERT INTO test_table VALUES (1, "test")';
        
        RTBCB_Error_Handler::handle_database_error( $operation, $error_message, $query );
        
        $recent_errors = RTBCB_Error_Handler::get_recent_errors( 1 );
        $logged_error = $recent_errors[0];
        
        $this->assertEquals( "Database error during {$operation}: {$error_message}", $logged_error['message'] );
        $this->assertEquals( 'DATABASE', $logged_error['source'] );
        $this->assertEquals( RTBCB_Error_Handler::ERROR_LEVEL_ERROR, $logged_error['level'] );
        $this->assertEquals( $operation, $logged_error['context']['operation'] );
        $this->assertEquals( strlen( $query ), $logged_error['context']['query_length'] );
    }

    /**
     * Test error level filtering.
     */
    public function test_error_level_filtering() {
        // Log errors of different levels
        RTBCB_Error_Handler::log_error( 'Debug message', RTBCB_Error_Handler::ERROR_LEVEL_DEBUG, [], 'TEST' );
        RTBCB_Error_Handler::log_error( 'Info message', RTBCB_Error_Handler::ERROR_LEVEL_INFO, [], 'TEST' );
        RTBCB_Error_Handler::log_error( 'Warning message', RTBCB_Error_Handler::ERROR_LEVEL_WARNING, [], 'TEST' );
        RTBCB_Error_Handler::log_error( 'Error message', RTBCB_Error_Handler::ERROR_LEVEL_ERROR, [], 'TEST' );
        RTBCB_Error_Handler::log_error( 'Critical message', RTBCB_Error_Handler::ERROR_LEVEL_CRITICAL, [], 'TEST' );
        
        // Test filtering by level
        $warning_errors = RTBCB_Error_Handler::get_recent_errors( 10, RTBCB_Error_Handler::ERROR_LEVEL_WARNING );
        $this->assertCount( 1, $warning_errors );
        $this->assertEquals( 'Warning message', $warning_errors[0]['message'] );
        
        $error_errors = RTBCB_Error_Handler::get_recent_errors( 10, RTBCB_Error_Handler::ERROR_LEVEL_ERROR );
        $this->assertCount( 1, $error_errors );
        $this->assertEquals( 'Error message', $error_errors[0]['message'] );
    }

    /**
     * Test error statistics generation.
     */
    public function test_error_statistics() {
        // Log multiple errors
        RTBCB_Error_Handler::log_error( 'API error 1', RTBCB_Error_Handler::ERROR_LEVEL_ERROR, [], 'LLM_API' );
        RTBCB_Error_Handler::log_error( 'API error 2', RTBCB_Error_Handler::ERROR_LEVEL_ERROR, [], 'LLM_API' );
        RTBCB_Error_Handler::log_error( 'Validation error', RTBCB_Error_Handler::ERROR_LEVEL_WARNING, [], 'VALIDATION' );
        RTBCB_Error_Handler::log_error( 'Critical system error', RTBCB_Error_Handler::ERROR_LEVEL_CRITICAL, [], 'SYSTEM' );
        
        $stats = RTBCB_Error_Handler::get_error_stats( 24 );
        
        $this->assertIsArray( $stats );
        $this->assertEquals( 4, $stats['total_errors'] );
        
        // Check level statistics
        $this->assertEquals( 2, $stats['by_level'][RTBCB_Error_Handler::ERROR_LEVEL_ERROR] );
        $this->assertEquals( 1, $stats['by_level'][RTBCB_Error_Handler::ERROR_LEVEL_WARNING] );
        $this->assertEquals( 1, $stats['by_level'][RTBCB_Error_Handler::ERROR_LEVEL_CRITICAL] );
        
        // Check source statistics
        $this->assertEquals( 2, $stats['by_source']['LLM_API'] );
        $this->assertEquals( 1, $stats['by_source']['VALIDATION'] );
        $this->assertEquals( 1, $stats['by_source']['SYSTEM'] );
    }

    /**
     * Test critical error detection.
     */
    public function test_critical_error_detection() {
        $this->assertFalse( RTBCB_Error_Handler::has_critical_errors( 1 ), 'Should not have critical errors initially' );
        
        RTBCB_Error_Handler::log_error( 'Regular error', RTBCB_Error_Handler::ERROR_LEVEL_ERROR, [], 'TEST' );
        $this->assertFalse( RTBCB_Error_Handler::has_critical_errors( 1 ), 'Should not detect non-critical errors' );
        
        RTBCB_Error_Handler::log_error( 'Critical error', RTBCB_Error_Handler::ERROR_LEVEL_CRITICAL, [], 'TEST' );
        $this->assertTrue( RTBCB_Error_Handler::has_critical_errors( 1 ), 'Should detect critical errors' );
    }

    /**
     * Test error level determination for different scenarios.
     */
    public function test_error_level_determination() {
        // Test critical errors (500+ status codes)
        RTBCB_Error_Handler::handle_llm_error( 'Internal server error', 'gpt-5', 'prompt', 500 );
        $errors = RTBCB_Error_Handler::get_recent_errors( 1 );
        $this->assertEquals( RTBCB_Error_Handler::ERROR_LEVEL_CRITICAL, $errors[0]['level'] );
        
        // Clear and test authentication errors
        RTBCB_Error_Handler::clear_error_log();
        RTBCB_Error_Handler::handle_llm_error( 'Authentication failed', 'gpt-5', 'prompt', 401 );
        $errors = RTBCB_Error_Handler::get_recent_errors( 1 );
        $this->assertEquals( RTBCB_Error_Handler::ERROR_LEVEL_CRITICAL, $errors[0]['level'] );
        
        // Clear and test rate limit errors
        RTBCB_Error_Handler::clear_error_log();
        RTBCB_Error_Handler::handle_llm_error( 'Rate limit exceeded', 'gpt-5', 'prompt', 429 );
        $errors = RTBCB_Error_Handler::get_recent_errors( 1 );
        $this->assertEquals( RTBCB_Error_Handler::ERROR_LEVEL_WARNING, $errors[0]['level'] );
        
        // Clear and test client errors
        RTBCB_Error_Handler::clear_error_log();
        RTBCB_Error_Handler::handle_llm_error( 'Bad request', 'gpt-5', 'prompt', 400 );
        $errors = RTBCB_Error_Handler::get_recent_errors( 1 );
        $this->assertEquals( RTBCB_Error_Handler::ERROR_LEVEL_ERROR, $errors[0]['level'] );
    }

    /**
     * Test error log size management.
     */
    public function test_error_log_size_management() {
        // Log more than the maximum number of errors
        for ( $i = 0; $i < 510; $i++ ) {
            RTBCB_Error_Handler::log_error( "Error message {$i}", RTBCB_Error_Handler::ERROR_LEVEL_ERROR, [], 'TEST' );
        }
        
        $error_log = get_option( 'rtbcb_error_log', [] );
        
        // Should not exceed the maximum (500) and should have been trimmed to half (250)
        $this->assertLessThanOrEqual( 500, count( $error_log ), 'Error log should not exceed maximum size' );
        
        // The most recent errors should be preserved
        $recent_errors = RTBCB_Error_Handler::get_recent_errors( 10 );
        $last_error = $recent_errors[0];
        $this->assertEquals( 'Error message 509', $last_error['message'], 'Most recent error should be preserved' );
    }
}
<?php
/**
 * Integration tests for performance monitoring functionality
 *
 * @package RealTreasuryBusinessCaseBuilder
 */

require_once __DIR__ . '/../bootstrap.php';

class RTBCB_Performance_Monitor_Integration_Test extends WP_UnitTestCase {

    public function setUp(): void {
        parent::setUp();
        
        // Clear performance metrics before each test
        RTBCB_Performance_Monitor::clear_metrics();
    }

    public function tearDown(): void {
        // Clean up after each test
        RTBCB_Performance_Monitor::clear_metrics();
        
        parent::tearDown();
    }

    /**
     * Test basic timer functionality.
     */
    public function test_basic_timer_functionality() {
        $operation_name = 'test_operation';
        
        RTBCB_Performance_Monitor::start_timer( $operation_name );
        
        // Simulate some work
        usleep( 10000 ); // 10ms
        
        $duration = RTBCB_Performance_Monitor::end_timer( $operation_name );
        
        $this->assertIsFloat( $duration, 'Duration should be a float' );
        $this->assertGreaterThan( 0.005, $duration, 'Duration should be greater than 5ms' );
        $this->assertLessThan( 1.0, $duration, 'Duration should be less than 1 second' );
    }

    /**
     * Test timer with context data.
     */
    public function test_timer_with_context() {
        $operation_name = 'test_operation_with_context';
        $context = [
            'user_id' => 123,
            'action' => 'generate_business_case',
            'model' => 'gpt-5-mini',
        ];
        
        RTBCB_Performance_Monitor::start_timer( $operation_name );
        usleep( 5000 ); // 5ms
        RTBCB_Performance_Monitor::end_timer( $operation_name, $context );
        
        $metrics = RTBCB_Performance_Monitor::get_current_metrics();
        
        $this->assertCount( 1, $metrics, 'Should have one metric recorded' );
        
        $metric = $metrics[0];
        $this->assertEquals( $operation_name, $metric['operation'] );
        $this->assertEquals( $context, $metric['context'] );
        $this->assertArrayHasKey( 'timestamp', $metric );
        $this->assertArrayHasKey( 'memory_usage', $metric );
        $this->assertArrayHasKey( 'peak_memory', $metric );
    }

    /**
     * Test manual metric recording.
     */
    public function test_manual_metric_recording() {
        $operation = 'manual_test';
        $duration = 0.123;
        $context = [ 'manual' => true ];
        
        RTBCB_Performance_Monitor::record_metric( $operation, $duration, $context );
        
        $metrics = RTBCB_Performance_Monitor::get_current_metrics();
        
        $this->assertCount( 1, $metrics );
        
        $metric = $metrics[0];
        $this->assertEquals( $operation, $metric['operation'] );
        $this->assertEquals( $duration, $metric['duration'] );
        $this->assertEquals( $context, $metric['context'] );
    }

    /**
     * Test LLM performance logging.
     */
    public function test_llm_performance_logging() {
        $model = 'gpt-5-mini';
        $prompt = 'Test prompt for performance monitoring';
        $duration = 2.5;
        $cached = false;
        
        RTBCB_Performance_Monitor::log_llm_performance( $model, $prompt, $duration, $cached );
        
        $metrics = RTBCB_Performance_Monitor::get_current_metrics();
        
        $this->assertCount( 1, $metrics );
        
        $metric = $metrics[0];
        $this->assertEquals( 'llm_api_call', $metric['operation'] );
        $this->assertEquals( $duration, $metric['duration'] );
        $this->assertEquals( $model, $metric['context']['model'] );
        $this->assertEquals( $cached, $metric['context']['cached'] );
        $this->assertEquals( strlen( $prompt ), $metric['context']['prompt_length'] );
    }

    /**
     * Test LLM performance logging with cached response.
     */
    public function test_llm_performance_logging_cached() {
        $model = 'gpt-5-mini';
        $prompt = [ 'input' => 'Test prompt', 'instructions' => 'Test instructions' ];
        $duration = 0.001;
        $cached = true;
        
        RTBCB_Performance_Monitor::log_llm_performance( $model, $prompt, $duration, $cached );
        
        $metrics = RTBCB_Performance_Monitor::get_current_metrics();
        $metric = $metrics[0];
        
        $this->assertTrue( $metric['context']['cached'], 'Should indicate cached response' );
        $this->assertLessThan( 0.01, $metric['duration'], 'Cached response should have very low duration' );
        $this->assertEquals( strlen( wp_json_encode( $prompt ) ), $metric['context']['prompt_length'] );
    }

    /**
     * Test historical metrics storage and retrieval.
     */
    public function test_historical_metrics() {
        // Record some metrics
        RTBCB_Performance_Monitor::record_metric( 'operation1', 1.0, [ 'test' => 'data1' ] );
        RTBCB_Performance_Monitor::record_metric( 'operation2', 2.0, [ 'test' => 'data2' ] );
        RTBCB_Performance_Monitor::record_metric( 'operation3', 0.5, [ 'test' => 'data3' ] );
        
        $historical = RTBCB_Performance_Monitor::get_historical_metrics( 10 );
        
        $this->assertIsArray( $historical, 'Historical metrics should be an array' );
        $this->assertCount( 3, $historical, 'Should have 3 historical metrics' );
        
        // Check that metrics are stored with proper structure
        foreach ( $historical as $metric ) {
            $this->assertArrayHasKey( 'operation', $metric );
            $this->assertArrayHasKey( 'duration', $metric );
            $this->assertArrayHasKey( 'timestamp', $metric );
            $this->assertArrayHasKey( 'context', $metric );
        }
    }

    /**
     * Test operation statistics calculation.
     */
    public function test_operation_statistics() {
        $operation_name = 'test_operation_stats';
        
        // Record multiple metrics for the same operation
        RTBCB_Performance_Monitor::record_metric( $operation_name, 1.0 );
        RTBCB_Performance_Monitor::record_metric( $operation_name, 2.0 );
        RTBCB_Performance_Monitor::record_metric( $operation_name, 3.0 );
        RTBCB_Performance_Monitor::record_metric( 'other_operation', 5.0 );
        
        $stats = RTBCB_Performance_Monitor::get_operation_stats( $operation_name, 24 );
        
        $this->assertIsArray( $stats, 'Stats should be an array' );
        $this->assertEquals( $operation_name, $stats['operation'] );
        $this->assertEquals( 3, $stats['count'] );
        $this->assertEquals( 2.0, $stats['avg_duration'] ); // (1+2+3)/3 = 2
        $this->assertEquals( 1.0, $stats['min_duration'] );
        $this->assertEquals( 3.0, $stats['max_duration'] );
        $this->assertEquals( 6.0, $stats['total_duration'] ); // 1+2+3 = 6
    }

    /**
     * Test operation statistics for non-existent operation.
     */
    public function test_operation_statistics_empty() {
        $stats = RTBCB_Performance_Monitor::get_operation_stats( 'non_existent_operation', 24 );
        
        $this->assertEquals( 'non_existent_operation', $stats['operation'] );
        $this->assertEquals( 0, $stats['count'] );
        $this->assertEquals( 0, $stats['avg_duration'] );
        $this->assertEquals( 0, $stats['min_duration'] );
        $this->assertEquals( 0, $stats['max_duration'] );
    }

    /**
     * Test performance summary generation.
     */
    public function test_performance_summary() {
        // Record some LLM performance metrics
        RTBCB_Performance_Monitor::log_llm_performance( 'gpt-5-mini', 'prompt1', 1.5, false );
        RTBCB_Performance_Monitor::log_llm_performance( 'gpt-5-mini', 'prompt2', 0.001, true );
        RTBCB_Performance_Monitor::log_llm_performance( 'gpt-4', 'prompt3', 2.0, false );
        
        $summary = RTBCB_Performance_Monitor::get_performance_summary();
        
        $this->assertIsArray( $summary, 'Summary should be an array' );
        $this->assertArrayHasKey( 'llm_performance', $summary );
        $this->assertArrayHasKey( 'memory_usage', $summary );
        $this->assertArrayHasKey( 'last_updated', $summary );
        
        $llm_stats = $summary['llm_performance'];
        $this->assertEquals( 'llm_api_call', $llm_stats['operation'] );
        $this->assertEquals( 3, $llm_stats['count'] );
        
        $memory_usage = $summary['memory_usage'];
        $this->assertArrayHasKey( 'current', $memory_usage );
        $this->assertArrayHasKey( 'peak', $memory_usage );
        $this->assertIsInt( $memory_usage['current'] );
        $this->assertIsInt( $memory_usage['peak'] );
    }

    /**
     * Test timer with non-existent operation.
     */
    public function test_end_timer_non_existent() {
        $duration = RTBCB_Performance_Monitor::end_timer( 'non_existent_operation' );
        
        $this->assertEquals( 0.0, $duration, 'Non-existent timer should return 0.0' );
        $this->assertEmpty( RTBCB_Performance_Monitor::get_current_metrics(), 'No metrics should be recorded' );
    }

    /**
     * Test metrics limit and cleanup.
     */
    public function test_metrics_limit() {
        // Record more than 100 metrics to test the limit
        for ( $i = 0; $i < 110; $i++ ) {
            RTBCB_Performance_Monitor::record_metric( "operation_{$i}", 1.0, [ 'index' => $i ] );
        }
        
        $performance_log = get_option( 'rtbcb_performance_log', [] );
        
        // Should not exceed 100 entries (or 50 after cleanup)
        $this->assertLessThanOrEqual( 100, count( $performance_log ), 'Performance log should respect size limits' );
        
        // Most recent entries should be preserved
        $historical = RTBCB_Performance_Monitor::get_historical_metrics( 10 );
        $last_metric = $historical[ count( $historical ) - 1 ];
        $this->assertEquals( 109, $last_metric['context']['index'], 'Most recent metric should be preserved' );
    }

    /**
     * Test multiple simultaneous timers.
     */
    public function test_multiple_simultaneous_timers() {
        RTBCB_Performance_Monitor::start_timer( 'operation_1' );
        usleep( 5000 ); // 5ms
        
        RTBCB_Performance_Monitor::start_timer( 'operation_2' );
        usleep( 5000 ); // 5ms
        
        $duration_2 = RTBCB_Performance_Monitor::end_timer( 'operation_2' );
        usleep( 5000 ); // 5ms
        $duration_1 = RTBCB_Performance_Monitor::end_timer( 'operation_1' );
        
        $this->assertGreaterThan( 0.004, $duration_2, 'Operation 2 should have ~5ms duration' );
        $this->assertLessThan( 0.02, $duration_2, 'Operation 2 should be less than 20ms' );
        
        $this->assertGreaterThan( 0.014, $duration_1, 'Operation 1 should have ~15ms duration' );
        $this->assertLessThan( 0.03, $duration_1, 'Operation 1 should be less than 30ms' );
        
        $this->assertGreaterThan( $duration_2, $duration_1, 'Operation 1 should take longer than operation 2' );
        
        $metrics = RTBCB_Performance_Monitor::get_current_metrics();
        $this->assertCount( 2, $metrics, 'Should have 2 metrics recorded' );
    }
}
<?php
/**
 * Integration tests for LLM caching functionality
 *
 * @package RealTreasuryBusinessCaseBuilder
 */

require_once __DIR__ . '/../bootstrap.php';

class RTBCB_LLM_Caching_Integration_Test extends WP_UnitTestCase {
    private $llm;

    public function setUp(): void {
        parent::setUp();
        
        // Set up mock API key for testing
        update_option( 'rtbcb_openai_api_key', 'test-api-key-12345' );
        update_option( 'rtbcb_llm_caching_enabled', true );
        update_option( 'rtbcb_llm_cache_duration', 300 ); // 5 minutes for testing
        
        $this->llm = new RTBCB_LLM();
    }

    public function tearDown(): void {
        // Clean up cache and options
        $this->llm->clear_llm_cache();
        delete_option( 'rtbcb_openai_api_key' );
        delete_option( 'rtbcb_llm_caching_enabled' );
        delete_option( 'rtbcb_llm_cache_duration' );
        
        parent::tearDown();
    }

    /**
     * Test cache key generation is consistent.
     */
    public function test_cache_key_generation_consistency() {
        $model = 'gpt-5-mini';
        $prompt = [ 'input' => 'Test prompt', 'instructions' => 'Test instructions' ];
        
        // Use reflection to access private method
        $reflection = new ReflectionClass( $this->llm );
        $method = $reflection->getMethod( 'generate_cache_key' );
        $method->setAccessible( true );
        
        $key1 = $method->invoke( $this->llm, $model, $prompt );
        $key2 = $method->invoke( $this->llm, $model, $prompt );
        
        $this->assertEquals( $key1, $key2, 'Cache keys should be consistent for same input' );
        $this->assertStringStartsWith( 'rtbcb_llm_', $key1, 'Cache key should have correct prefix' );
    }

    /**
     * Test cache key uniqueness for different inputs.
     */
    public function test_cache_key_uniqueness() {
        $reflection = new ReflectionClass( $this->llm );
        $method = $reflection->getMethod( 'generate_cache_key' );
        $method->setAccessible( true );
        
        $key1 = $method->invoke( $this->llm, 'gpt-5-mini', 'prompt1' );
        $key2 = $method->invoke( $this->llm, 'gpt-5-mini', 'prompt2' );
        $key3 = $method->invoke( $this->llm, 'gpt-4', 'prompt1' );
        
        $this->assertNotEquals( $key1, $key2, 'Different prompts should generate different keys' );
        $this->assertNotEquals( $key1, $key3, 'Different models should generate different keys' );
    }

    /**
     * Test cache statistics functionality.
     */
    public function test_cache_statistics() {
        $stats = $this->llm->get_cache_stats();
        
        $this->assertIsArray( $stats, 'Cache stats should return an array' );
        $this->assertArrayHasKey( 'cache_enabled', $stats );
        $this->assertArrayHasKey( 'cached_responses', $stats );
        $this->assertArrayHasKey( 'cache_duration', $stats );
        
        $this->assertTrue( $stats['cache_enabled'], 'Cache should be enabled in test' );
        $this->assertIsInt( $stats['cached_responses'], 'Cached responses should be an integer' );
        $this->assertEquals( 300, $stats['cache_duration'], 'Cache duration should match option' );
    }

    /**
     * Test caching can be disabled.
     */
    public function test_caching_can_be_disabled() {
        update_option( 'rtbcb_llm_caching_enabled', false );
        
        $reflection = new ReflectionClass( $this->llm );
        $method = $reflection->getMethod( 'is_caching_enabled' );
        $method->setAccessible( true );
        
        $this->assertFalse( $method->invoke( $this->llm ), 'Caching should be disabled when option is false' );
    }

    /**
     * Test cache clearing functionality.
     */
    public function test_cache_clearing() {
        // Create some fake cache entries
        set_transient( 'rtbcb_llm_test1', 'test_data1', 300 );
        set_transient( 'rtbcb_llm_test2', 'test_data2', 300 );
        set_transient( 'other_transient', 'other_data', 300 );
        
        // Clear LLM cache
        $cleared = $this->llm->clear_llm_cache();
        
        $this->assertIsInt( $cleared, 'Clear cache should return number of items cleared' );
        
        // Check that LLM cache entries are gone but others remain
        $this->assertFalse( get_transient( 'rtbcb_llm_test1' ), 'LLM cache entry should be cleared' );
        $this->assertFalse( get_transient( 'rtbcb_llm_test2' ), 'LLM cache entry should be cleared' );
        $this->assertEquals( 'other_data', get_transient( 'other_transient' ), 'Non-LLM transients should remain' );
        
        // Clean up
        delete_transient( 'other_transient' );
    }

    /**
     * Test cache stores and retrieves data correctly.
     */
    public function test_cache_storage_and_retrieval() {
        $reflection = new ReflectionClass( $this->llm );
        
        $store_method = $reflection->getMethod( 'store_cached_response' );
        $store_method->setAccessible( true );
        
        $get_method = $reflection->getMethod( 'get_cached_response' );
        $get_method->setAccessible( true );
        
        $cache_key = 'rtbcb_llm_test_key';
        $test_response = [
            'body' => json_encode( [ 'output_text' => 'Test response content' ] ),
            'status' => 200,
        ];
        
        // Store response
        $stored = $store_method->invoke( $this->llm, $cache_key, $test_response, 300 );
        $this->assertTrue( $stored, 'Cache storage should succeed' );
        
        // Retrieve response
        $retrieved = $get_method->invoke( $this->llm, $cache_key );
        $this->assertEquals( $test_response, $retrieved, 'Retrieved response should match stored response' );
        
        // Test non-existent key
        $non_existent = $get_method->invoke( $this->llm, 'rtbcb_llm_non_existent' );
        $this->assertFalse( $non_existent, 'Non-existent cache key should return false' );
    }

    /**
     * Test that WP_Error responses are not cached.
     */
    public function test_wp_error_not_cached() {
        $reflection = new ReflectionClass( $this->llm );
        $store_method = $reflection->getMethod( 'store_cached_response' );
        $store_method->setAccessible( true );
        
        $cache_key = 'rtbcb_llm_error_test';
        $error_response = new WP_Error( 'test_error', 'Test error message' );
        
        $stored = $store_method->invoke( $this->llm, $cache_key, $error_response, 300 );
        $this->assertFalse( $stored, 'WP_Error responses should not be cached' );
        
        $this->assertFalse( get_transient( $cache_key ), 'Error response should not be stored in cache' );
    }

    /**
     * Test cache duration option is respected.
     */
    public function test_cache_duration_option() {
        update_option( 'rtbcb_llm_cache_duration', 120 );
        
        $stats = $this->llm->get_cache_stats();
        $this->assertEquals( 120, $stats['cache_duration'], 'Cache duration should reflect option value' );
    }
}
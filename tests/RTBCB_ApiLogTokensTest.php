<?php
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/../' );
}
defined( 'ABSPATH' ) || exit;

use PHPUnit\Framework\TestCase;
require_once __DIR__ . '/wp-stubs.php';

if ( ! function_exists( 'current_time' ) ) {
	function current_time( $type ) {
	    return '2024-01-01 00:00:00';
	}
}

class WPDB_LogStub {
	public $rows = [];

	public function insert( $table, $data, $format ) {
	    $this->rows[] = $data;
	    return 1;
	}
}

final class RTBCB_ApiLogTokensTest extends TestCase {
	/**
	 * @runInSeparateProcess
	 */
	public function test_save_log_stores_token_usage() {
	    require_once __DIR__ . '/../inc/class-rtbcb-api-log.php';
	    global $wpdb;
	    $wpdb = new WPDB_LogStub();

	    $reflect  = new ReflectionClass( RTBCB_API_Log::class );
	    $property = $reflect->getProperty( 'table_name' );
	    $property->setAccessible( true );
	    $property->setValue( null, 'rtbcb_api_logs' );

	    $response = [
	        'usage' => [
	            'prompt_tokens'     => 5,
	            'completion_tokens' => 7,
	            'total_tokens'      => 12,
	        ],
	    ];

            RTBCB_API_Log::save_log( [], $response, 1, '', '', 0, 'gpt-5' );

	    $this->assertNotEmpty( $wpdb->rows );
	    $row = $wpdb->rows[0];
	    $this->assertSame( 5, $row['prompt_tokens'] );
	    $this->assertSame( 7, $row['completion_tokens'] );
	    $this->assertSame( 12, $row['total_tokens'] );
	}
}

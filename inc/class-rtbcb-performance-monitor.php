<?php
/**
 * Performance monitoring for Real Treasury Business Case Builder
 *
 * @package RealTreasuryBusinessCaseBuilder
 */

/**
 * Performance monitoring and metrics collection class.
 */
class RTBCB_Performance_Monitor {
    /**
     * Performance metrics storage.
     *
     * @var array
     */
    private static $metrics = [];

    /**
     * Active timers.
     *
     * @var array
     */
    private static $timers = [];

    /**
     * Start timing an operation.
     *
     * @param string $operation_name Name of the operation to time.
     * @return void
     */
    public static function start_timer( $operation_name ) {
        self::$timers[ $operation_name ] = microtime( true );
        error_log( 'RTBCB Performance: Started timing ' . $operation_name );
    }

    /**
     * End timing an operation and record the duration.
     *
     * @param string $operation_name Name of the operation.
     * @param array  $context       Additional context data.
     * @return float Operation duration in seconds.
     */
    public static function end_timer( $operation_name, $context = [] ) {
        if ( ! isset( self::$timers[ $operation_name ] ) ) {
            error_log( 'RTBCB Performance: Timer not found for ' . $operation_name );
            return 0.0;
        }

        $duration = microtime( true ) - self::$timers[ $operation_name ];
        unset( self::$timers[ $operation_name ] );

        self::record_metric( $operation_name, $duration, $context );

        error_log( sprintf( 'RTBCB Performance: %s completed in %.3f seconds', $operation_name, $duration ) );
        return $duration;
    }

    /**
     * Record a performance metric.
     *
     * @param string $operation Name of the operation.
     * @param float  $duration  Duration in seconds.
     * @param array  $context   Additional context data.
     * @return void
     */
    public static function record_metric( $operation, $duration, $context = [] ) {
        $metric = [
            'operation' => sanitize_text_field( $operation ),
            'duration' => (float) $duration,
            'timestamp' => time(),
            'memory_usage' => memory_get_usage( true ),
            'peak_memory' => memory_get_peak_usage( true ),
            'context' => $context,
        ];

        // Store in array for current request
        self::$metrics[] = $metric;

        // Store persistent metrics for admin dashboard
        self::store_persistent_metric( $metric );
    }

    /**
     * Store metric in database for historical analysis.
     *
     * @param array $metric Metric data.
     * @return void
     */
    private static function store_persistent_metric( $metric ) {
        $performance_log = get_option( 'rtbcb_performance_log', [] );
        
        // Keep only last 100 entries to prevent database bloat
        if ( count( $performance_log ) >= 100 ) {
            $performance_log = array_slice( $performance_log, -50 );
        }

        $performance_log[] = $metric;
        update_option( 'rtbcb_performance_log', $performance_log );
    }

    /**
     * Get current request metrics.
     *
     * @return array Current request performance metrics.
     */
    public static function get_current_metrics() {
        return self::$metrics;
    }

    /**
     * Get historical performance metrics.
     *
     * @param int $limit Number of recent metrics to retrieve.
     * @return array Historical performance metrics.
     */
    public static function get_historical_metrics( $limit = 50 ) {
        $performance_log = get_option( 'rtbcb_performance_log', [] );
        return array_slice( $performance_log, -$limit );
    }

    /**
     * Get performance statistics for an operation.
     *
     * @param string $operation_name Name of the operation.
     * @param int    $hours         Hours of data to analyze.
     * @return array Performance statistics.
     */
    public static function get_operation_stats( $operation_name, $hours = 24 ) {
        $metrics = self::get_historical_metrics( 200 );
        $since = time() - ( $hours * 3600 );
        
        $filtered_metrics = array_filter( $metrics, function( $metric ) use ( $operation_name, $since ) {
            return $metric['operation'] === $operation_name && $metric['timestamp'] >= $since;
        });

        if ( empty( $filtered_metrics ) ) {
            return [
                'operation' => $operation_name,
                'count' => 0,
                'avg_duration' => 0,
                'min_duration' => 0,
                'max_duration' => 0,
            ];
        }

        $durations = array_column( $filtered_metrics, 'duration' );
        
        return [
            'operation' => $operation_name,
            'count' => count( $filtered_metrics ),
            'avg_duration' => array_sum( $durations ) / count( $durations ),
            'min_duration' => min( $durations ),
            'max_duration' => max( $durations ),
            'total_duration' => array_sum( $durations ),
        ];
    }

    /**
     * Clear performance metrics.
     *
     * @return bool Success status.
     */
    public static function clear_metrics() {
        self::$metrics = [];
        delete_option( 'rtbcb_performance_log' );
        error_log( 'RTBCB Performance: Metrics cleared' );
        return true;
    }

    /**
     * Monitor LLM API call performance.
     *
     * @param string $model  Model name.
     * @param array  $prompt Prompt data.
     * @param float  $duration Call duration.
     * @param bool   $cached Whether response was cached.
     * @return void
     */
    public static function log_llm_performance( $model, $prompt, $duration, $cached = false ) {
        $context = [
            'model' => $model,
            'cached' => $cached,
            'prompt_length' => is_string( $prompt ) ? strlen( $prompt ) : strlen( wp_json_encode( $prompt ) ),
        ];

        self::record_metric( 'llm_api_call', $duration, $context );
    }

    /**
     * Get performance summary for admin dashboard.
     *
     * @return array Performance summary.
     */
    public static function get_performance_summary() {
        $llm_stats = self::get_operation_stats( 'llm_api_call', 24 );
        $cache_stats = [];
        
        // Get cache hit rate from LLM class if available
        if ( class_exists( 'RTBCB_LLM' ) ) {
            $llm = new RTBCB_LLM();
            if ( method_exists( $llm, 'get_cache_stats' ) ) {
                $cache_stats = $llm->get_cache_stats();
            }
        }

        return [
            'llm_performance' => $llm_stats,
            'cache_stats' => $cache_stats,
            'memory_usage' => [
                'current' => memory_get_usage( true ),
                'peak' => memory_get_peak_usage( true ),
            ],
            'last_updated' => time(),
        ];
    }
}
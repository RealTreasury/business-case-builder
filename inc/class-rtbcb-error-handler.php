<?php
/**
 * Centralized error handling and logging for Real Treasury Business Case Builder
 *
 * @package RealTreasuryBusinessCaseBuilder
 */

/**
 * Centralized error handling and logging class.
 */
class RTBCB_Error_Handler {
    /**
     * Error levels.
     */
    const ERROR_LEVEL_DEBUG = 'debug';
    const ERROR_LEVEL_INFO = 'info';
    const ERROR_LEVEL_WARNING = 'warning';
    const ERROR_LEVEL_ERROR = 'error';
    const ERROR_LEVEL_CRITICAL = 'critical';

    /**
     * Maximum number of log entries to keep.
     */
    const MAX_LOG_ENTRIES = 500;

    /**
     * Log an error with context.
     *
     * @param string $message Error message.
     * @param string $level   Error level.
     * @param array  $context Additional context data.
     * @param string $source  Source of the error.
     * @return bool Success status.
     */
    public static function log_error( $message, $level = self::ERROR_LEVEL_ERROR, $context = [], $source = '' ) {
        $log_entry = [
            'timestamp' => time(),
            'level' => sanitize_text_field( $level ),
            'message' => sanitize_text_field( $message ),
            'source' => sanitize_text_field( $source ),
            'context' => $context,
            'user_id' => get_current_user_id(),
            'request_uri' => isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '',
            'user_agent' => isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '',
        ];

        // Store in WordPress error log
        $log_message = sprintf(
            '[RTBCB-%s] %s: %s (Source: %s)',
            strtoupper( $level ),
            current_time( 'Y-m-d H:i:s' ),
            $message,
            $source
        );
        
        if ( ! empty( $context ) ) {
            $log_message .= ' Context: ' . wp_json_encode( $context );
        }

        error_log( $log_message );

        // Store in database for admin dashboard
        if ( self::should_store_in_database( $level ) ) {
            self::store_error_in_database( $log_entry );
        }

        // Trigger WordPress action for other plugins to hook into
        do_action( 'rtbcb_error_logged', $log_entry );

        return true;
    }

    /**
     * Determine if error should be stored in database.
     *
     * @param string $level Error level.
     * @return bool True if should store.
     */
    private static function should_store_in_database( $level ) {
        $store_levels = get_option( 'rtbcb_error_log_levels', [
            self::ERROR_LEVEL_WARNING,
            self::ERROR_LEVEL_ERROR,
            self::ERROR_LEVEL_CRITICAL,
        ]);

        return in_array( $level, $store_levels, true );
    }

    /**
     * Store error in database.
     *
     * @param array $log_entry Log entry data.
     * @return void
     */
    private static function store_error_in_database( $log_entry ) {
        $error_log = get_option( 'rtbcb_error_log', [] );
        
        // Keep only recent entries to prevent database bloat
        if ( count( $error_log ) >= self::MAX_LOG_ENTRIES ) {
            $error_log = array_slice( $error_log, -( self::MAX_LOG_ENTRIES / 2 ) );
        }

        $error_log[] = $log_entry;
        update_option( 'rtbcb_error_log', $error_log );
    }

    /**
     * Handle LLM API errors specifically.
     *
     * @param string     $error_message Error message.
     * @param string     $model         Model name.
     * @param array      $prompt        Prompt data.
     * @param int        $response_code HTTP response code.
     * @param WP_Error   $error         WP_Error object.
     * @return void
     */
    public static function handle_llm_error( $error_message, $model = '', $prompt = [], $response_code = 0, $error = null ) {
        $context = [
            'model' => $model,
            'prompt_length' => is_string( $prompt ) ? strlen( $prompt ) : strlen( wp_json_encode( $prompt ) ),
            'response_code' => $response_code,
        ];

        if ( $error && is_wp_error( $error ) ) {
            $context['wp_error_code'] = $error->get_error_code();
            $context['wp_error_data'] = $error->get_error_data();
        }

        $level = self::determine_error_level( $response_code, $error_message );
        
        self::log_error( 
            $error_message, 
            $level, 
            $context, 
            'LLM_API'
        );

        // Store specific LLM error for admin notices
        if ( $level === self::ERROR_LEVEL_ERROR || $level === self::ERROR_LEVEL_CRITICAL ) {
            set_transient( 'rtbcb_llm_error_notice', $error_message, 300 );
        }
    }

    /**
     * Determine error level based on context.
     *
     * @param int    $response_code HTTP response code.
     * @param string $message       Error message.
     * @return string Error level.
     */
    private static function determine_error_level( $response_code, $message ) {
        // Critical errors
        if ( $response_code >= 500 || stripos( $message, 'authentication' ) !== false ) {
            return self::ERROR_LEVEL_CRITICAL;
        }
        
        // Regular errors
        if ( $response_code >= 400 ) {
            return self::ERROR_LEVEL_ERROR;
        }
        
        // Warnings for rate limiting, etc.
        if ( $response_code === 429 || stripos( $message, 'rate limit' ) !== false ) {
            return self::ERROR_LEVEL_WARNING;
        }

        return self::ERROR_LEVEL_ERROR;
    }

    /**
     * Handle validation errors.
     *
     * @param string $field_name Field that failed validation.
     * @param string $error_message Error message.
     * @param mixed  $input_value Input value that failed.
     * @return void
     */
    public static function handle_validation_error( $field_name, $error_message, $input_value = null ) {
        $context = [
            'field' => $field_name,
            'input_type' => gettype( $input_value ),
        ];

        // Don't log actual input value for security
        if ( is_string( $input_value ) ) {
            $context['input_length'] = strlen( $input_value );
        }

        self::log_error( 
            "Validation failed for {$field_name}: {$error_message}", 
            self::ERROR_LEVEL_WARNING, 
            $context, 
            'VALIDATION'
        );
    }

    /**
     * Handle database errors.
     *
     * @param string $operation Database operation (SELECT, INSERT, etc.).
     * @param string $error_message Error message.
     * @param string $query Optional SQL query.
     * @return void
     */
    public static function handle_database_error( $operation, $error_message, $query = '' ) {
        $context = [
            'operation' => $operation,
            'query_length' => strlen( $query ),
        ];

        self::log_error( 
            "Database error during {$operation}: {$error_message}", 
            self::ERROR_LEVEL_ERROR, 
            $context, 
            'DATABASE'
        );
    }

    /**
     * Get recent error logs.
     *
     * @param int    $limit Maximum number of entries.
     * @param string $level Optional level filter.
     * @return array Error log entries.
     */
    public static function get_recent_errors( $limit = 50, $level = '' ) {
        $error_log = get_option( 'rtbcb_error_log', [] );
        
        if ( ! empty( $level ) ) {
            $error_log = array_filter( $error_log, function( $entry ) use ( $level ) {
                return $entry['level'] === $level;
            });
        }

        return array_slice( array_reverse( $error_log ), 0, $limit );
    }

    /**
     * Clear error logs.
     *
     * @return bool Success status.
     */
    public static function clear_error_log() {
        delete_option( 'rtbcb_error_log' );
        self::log_error( 'Error log cleared by admin', self::ERROR_LEVEL_INFO, [], 'ADMIN' );
        return true;
    }

    /**
     * Get error statistics.
     *
     * @param int $hours Hours to analyze (default 24).
     * @return array Error statistics.
     */
    public static function get_error_stats( $hours = 24 ) {
        $error_log = get_option( 'rtbcb_error_log', [] );
        $since = time() - ( $hours * 3600 );
        
        $recent_errors = array_filter( $error_log, function( $entry ) use ( $since ) {
            return $entry['timestamp'] >= $since;
        });

        $stats = [
            'total_errors' => count( $recent_errors ),
            'by_level' => [],
            'by_source' => [],
            'most_common' => [],
        ];

        foreach ( $recent_errors as $error ) {
            // Count by level
            $level = $error['level'];
            $stats['by_level'][ $level ] = ( $stats['by_level'][ $level ] ?? 0 ) + 1;
            
            // Count by source
            $source = $error['source'];
            $stats['by_source'][ $source ] = ( $stats['by_source'][ $source ] ?? 0 ) + 1;
            
            // Count by message
            $message = $error['message'];
            $stats['most_common'][ $message ] = ( $stats['most_common'][ $message ] ?? 0 ) + 1;
        }

        // Sort most common errors
        arsort( $stats['most_common'] );
        $stats['most_common'] = array_slice( $stats['most_common'], 0, 10, true );

        return $stats;
    }

    /**
     * Check if there are critical errors.
     *
     * @param int $hours Hours to check (default 1).
     * @return bool True if critical errors exist.
     */
    public static function has_critical_errors( $hours = 1 ) {
        $error_log = get_option( 'rtbcb_error_log', [] );
        $since = time() - ( $hours * 3600 );
        
        foreach ( $error_log as $error ) {
            if ( $error['timestamp'] >= $since && $error['level'] === self::ERROR_LEVEL_CRITICAL ) {
                return true;
            }
        }

        return false;
    }
}
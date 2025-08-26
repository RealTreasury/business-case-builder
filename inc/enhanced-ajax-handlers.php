<?php

add_action( 'wp_ajax_rtbcb_run_llm_test', 'rtbcb_ajax_run_llm_test' );
add_action( 'wp_ajax_rtbcb_run_rag_test', 'rtbcb_ajax_run_rag_test' );
add_action( 'wp_ajax_rtbcb_api_health_ping', 'rtbcb_ajax_api_health_ping' );
add_action( 'wp_ajax_rtbcb_export_results', 'rtbcb_ajax_export_results' );
add_action( 'wp_ajax_rtbcb_debug_api_key', 'rtbcb_debug_api_key' );

// Remove duplicate handlers and add debug logging.
add_action(
    'init',
    function () {
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            error_log( '[RTBCB] Registering AJAX handlers' );
        }

        // API Health handlers.
        add_action( 'wp_ajax_rtbcb_run_api_health_tests', 'rtbcb_run_api_health_tests' );
        add_action( 'wp_ajax_rtbcb_run_single_api_test', 'rtbcb_run_single_api_test' );

        // Verify handlers are registered.
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            error_log( '[RTBCB] API health handlers registered' );
        }
    }
);

/**
 * Enhanced AJAX helper functions.
 *
 * @package RealTreasuryBusinessCaseBuilder
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Send standardized JSON error response.
 *
 * @param string $code   Error code.
 * @param string $message Error message.
 * @param int    $status HTTP status code.
 * @param string $debug Optional debug information.
 * @param array  $extra Additional data to include in the response.
 *
 * @return void
 */
function rtbcb_send_json_error( $code, $message, $status = 400, $debug = '', $extra = [] ) {
    $data = array_merge(
        [
            'code'    => $code,
            'message' => $message,
        ],
        $extra
    );

    if ( WP_DEBUG && ! empty( $debug ) ) {
        $data['debug'] = $debug;
    }

    wp_send_json_error( $data, $status );
}

/**
 * Debug OpenAI API key configuration.
 *
 * Provides basic information about the stored API key for troubleshooting
 * purposes. Requires manage_options capability.
 *
 * @return void
 */
function rtbcb_debug_api_key() {
    if ( ! check_ajax_referer( 'rtbcb_debug_api_key', 'nonce', false ) ) {
        rtbcb_send_json_error( 'security_check_failed', rtbcb_get_user_friendly_error( 'security_check_failed' ), 403 );
    }

    if ( ! current_user_can( 'manage_options' ) ) {
        rtbcb_send_json_error( 'insufficient_permissions', rtbcb_get_user_friendly_error( 'insufficient_permissions' ), 403 );
    }

    $api_key    = sanitize_text_field( get_option( 'rtbcb_openai_api_key' ) );
    $key_length = strlen( $api_key );
    $key_preview = $key_length > 10 ? substr( $api_key, 0, 8 ) . '...' . substr( $api_key, -4 ) : 'too_short';

    wp_send_json_success(
        [
            'configured'   => ! empty( $api_key ),
            'length'       => $key_length,
            'preview'      => $key_preview,
            'valid_format' => rtbcb_is_valid_openai_api_key( $api_key ),
        ]
    );
}

/**
 * OpenAI API connection tester.
 */
class RTBCB_API_Tester {

    /**
     * Test OpenAI API connection using the models endpoint.
     *
     * @param string $api_key Optional API key.
     * @return array
     */
    public static function test_connection( $api_key = null ) {
        $api_key = $api_key ?: get_option( 'rtbcb_openai_api_key' );

        if ( empty( $api_key ) ) {
            return [
                'success' => false,
                'message' => __( 'API key not configured', 'rtbcb' ),
                'details' => [ 'error' => 'missing_api_key' ],
            ];
        }

        $response = wp_remote_get(
            'https://api.openai.com/v1/models',
            [
                'headers' => [
                    'Authorization' => 'Bearer ' . $api_key,
                    'Content-Type'  => 'application/json',
                ],
                'timeout' => 60,
            ]
        );

        if ( is_wp_error( $response ) ) {
            return [
                'success' => false,
                'message' => $response->get_error_message(),
                'details' => [
                    'error'      => $response->get_error_code(),
                    'connection' => 'failed',
                ],
            ];
        }

        $status_code = wp_remote_retrieve_response_code( $response );
        $headers     = wp_remote_retrieve_headers( $response );

        $rate_limit_info = [
            'requests_remaining' => $headers['x-ratelimit-remaining-requests'] ?? null,
            'tokens_remaining'   => $headers['x-ratelimit-remaining-tokens'] ?? null,
            'reset_requests'     => $headers['x-ratelimit-reset-requests'] ?? null,
            'reset_tokens'       => $headers['x-ratelimit-reset-tokens'] ?? null,
        ];

        if ( function_exists( 'rtbcb_track_api_rate_limits' ) ) {
            rtbcb_track_api_rate_limits( $headers );
        }

        switch ( $status_code ) {
            case 200:
                $body        = json_decode( wp_remote_retrieve_body( $response ), true );
                $model_count = isset( $body['data'] ) ? count( $body['data'] ) : 0;

                update_option( 'rtbcb_openai_last_ok', time() );
                delete_transient( 'rtbcb_openai_error' );

                return [
                    'success' => true,
                    'message' => sprintf( __( 'API connection healthy (%d models available)', 'rtbcb' ), $model_count ),
                    'details' => [
                        'status_code' => $status_code,
                        'model_count' => $model_count,
                        'rate_limits' => $rate_limit_info,
                    ],
                ];

            case 401:
                self::store_error_info( 'unauthorized', $status_code, 'Invalid API key' );
                return [
                    'success' => false,
                    'message' => __( 'Invalid API key', 'rtbcb' ),
                    'details' => [ 'error' => 'unauthorized', 'status_code' => $status_code ],
                ];

            case 429:
                self::store_error_info( 'rate_limited', $status_code, 'Rate limit exceeded' );
                return [
                    'success' => false,
                    'message' => __( 'Rate limit exceeded', 'rtbcb' ),
                    'details' => [
                        'error'       => 'rate_limited',
                        'status_code' => $status_code,
                        'rate_limits' => $rate_limit_info,
                    ],
                ];

            default:
                self::store_error_info( 'api_error', $status_code, wp_remote_retrieve_body( $response ) );
                return [
                    'success' => false,
                    'message' => sprintf( __( 'API error (HTTP %d)', 'rtbcb' ), $status_code ),
                    'details' => [
                        'error'       => 'api_error',
                        'status_code' => $status_code,
                        'body'        => substr( wp_remote_retrieve_body( $response ), 0, 200 ),
                    ],
                ];
        }
    }

    /**
     * Test embedding API functionality.
     *
     * @return array
     */
    public static function test_embedding() {
        $api_key = get_option( 'rtbcb_openai_api_key' );

        if ( empty( $api_key ) ) {
            return [
                'success' => false,
                'message' => __( 'API key not configured', 'rtbcb' ),
                'details' => [ 'error' => 'missing_api_key' ],
            ];
        }

        $model = get_option( 'rtbcb_embedding_model', 'text-embedding-3-small' );

        $response = wp_remote_post(
            'https://api.openai.com/v1/embeddings',
            [
                'headers' => [
                    'Authorization' => 'Bearer ' . $api_key,
                    'Content-Type'  => 'application/json',
                ],
                'body'    => wp_json_encode(
                    [
                        'model' => $model,
                        'input' => 'test embedding',
                    ]
                ),
                'timeout' => 15,
            ]
        );

        if ( is_wp_error( $response ) ) {
            return [
                'success' => false,
                'message' => $response->get_error_message(),
                'details' => [ 'error' => $response->get_error_code() ],
            ];
        }

        $status_code = wp_remote_retrieve_response_code( $response );
        $headers     = wp_remote_retrieve_headers( $response );
        if ( function_exists( 'rtbcb_track_api_rate_limits' ) ) {
            rtbcb_track_api_rate_limits( $headers );
        }

        if ( 200 === $status_code ) {
            $body             = json_decode( wp_remote_retrieve_body( $response ), true );
            $embedding_length = isset( $body['data'][0]['embedding'] ) ? count( $body['data'][0]['embedding'] ) : 0;

            return [
                'success' => true,
                'message' => sprintf( __( 'Embedding API working (vector dim: %d)', 'rtbcb' ), $embedding_length ),
                'details' => [
                    'model_used'           => $model,
                    'embedding_dimensions' => $embedding_length,
                    'tokens_used'          => $body['usage']['total_tokens'] ?? 0,
                ],
            ];
        }

        return [
            'success' => false,
            'message' => sprintf( __( 'Embedding API error (HTTP %d)', 'rtbcb' ), $status_code ),
            'details' => [
                'error'       => 'api_error',
                'status_code' => $status_code,
                'body'        => substr( wp_remote_retrieve_body( $response ), 0, 200 ),
            ],
        ];
    }

    /**
     * Test Real Treasury Portal integration.
     *
     * @return array
     */
    public static function test_portal() {
        if ( ! has_filter( 'rt_portal_get_vendors' ) ) {
            return [
                'success' => false,
                'message' => __( 'Portal integration not active', 'rtbcb' ),
                'details' => [ 'error' => 'integration_missing' ],
            ];
        }

        try {
            $vendors = apply_filters( 'rt_portal_get_vendors', [], [ 'limit' => 1 ] );

            return [
                'success' => true,
                'message' => sprintf( __( 'Portal integration active (%d vendors available)', 'rtbcb' ), count( $vendors ) ),
                'details' => [
                    'vendors_count'     => count( $vendors ),
                    'integration_active' => true,
                ],
            ];
        } catch ( Exception $e ) {
            return [
                'success' => false,
                'message' => __( 'Portal integration error: ', 'rtbcb' ) . $e->getMessage(),
                'details' => [ 'error' => 'integration_error', 'exception' => $e->getMessage() ],
            ];
        }
    }

    /**
     * Test ROI calculator functionality.
     *
     * @return array
     */
    public static function test_roi_calculator() {
        $test_data = [
            'company_size'        => 'medium',
            'annual_revenue'      => 100000000,
            'treasury_staff'      => 3,
            'hours_reconciliation'=> 20,
            'num_banks'           => 5,
        ];

        try {
            if ( ! class_exists( 'RTBCB_Calculator' ) ) {
                return [
                    'success' => false,
                    'message' => __( 'ROI Calculator class not found', 'rtbcb' ),
                    'details' => [ 'error' => 'class_missing' ],
                ];
            }

            $calculator = new RTBCB_Calculator();
            $result     = $calculator->calculate_roi( $test_data );

            if ( is_wp_error( $result ) ) {
                return [
                    'success' => false,
                    'message' => __( 'ROI calculation failed: ', 'rtbcb' ) . $result->get_error_message(),
                    'details' => [ 'error' => 'calculation_failed' ],
                ];
            }

            return [
                'success' => true,
                'message' => __( 'ROI Calculator working', 'rtbcb' ),
                'details' => [
                    'test_roi'       => $result['base']['roi_percentage'] ?? 0,
                    'calculation_time' => microtime( true ),
                ],
            ];
        } catch ( Exception $e ) {
            return [
                'success' => false,
                'message' => __( 'ROI Calculator error: ', 'rtbcb' ) . $e->getMessage(),
                'details' => [ 'error' => 'exception', 'exception' => $e->getMessage() ],
            ];
        }
    }

    /**
     * Test RAG index health.
     *
     * @return array
     */
    public static function test_rag_index() {
        global $wpdb;

        try {
            $table_name  = $wpdb->prefix . 'rtbcb_rag_index';
            $table_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) );

            if ( ! $table_exists ) {
                return [
                    'success' => false,
                    'message' => __( 'RAG index table not found', 'rtbcb' ),
                    'details' => [ 'error' => 'table_missing' ],
                ];
            }

            $index_size = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table_name}" );

            if ( class_exists( 'RTBCB_RAG' ) ) {
                $rag          = new RTBCB_RAG();
                $test_results = $rag->search_similar( 'test query', 1 );

                return [
                    'success' => true,
                    'message' => sprintf( __( 'RAG index healthy (%d entries)', 'rtbcb' ), $index_size ),
                    'details' => [
                        'index_size'        => $index_size,
                        'test_results_count' => count( $test_results ),
                        'last_indexed'      => get_option( 'rtbcb_last_indexed', '' ),
                    ],
                ];
            }

            return [
                'success' => $index_size > 0,
                'message' => sprintf( __( 'RAG table exists (%d entries) but RAG class unavailable', 'rtbcb' ), $index_size ),
                'details' => [
                    'index_size'      => $index_size,
                    'class_available' => false,
                ],
            ];
        } catch ( Exception $e ) {
            return [
                'success' => false,
                'message' => __( 'RAG index error: ', 'rtbcb' ) . $e->getMessage(),
                'details' => [ 'error' => 'exception', 'exception' => $e->getMessage() ],
            ];
        }
    }

    /**
     * Store error details for admin notice display.
     *
     * @param string $code        Error code.
     * @param int    $http_status HTTP status code.
     * @param string $body        Response body.
     * @return void
     */
    private static function store_error_info( $code, $http_status, $body ) {
        $timestamp = time();
        update_option( 'rtbcb_openai_last_error_at', $timestamp );
        set_transient(
            'rtbcb_openai_error',
            [
                'code'       => $code,
                'httpStatus' => $http_status,
                'body'       => substr( $body, 0, 200 ),
                'timestamp'  => $timestamp,
            ],
            600
        );

        $history   = get_option( 'rtbcb_api_error_history', [] );
        $history[] = [
            'code'        => $code,
            'http_status' => $http_status,
            'body'        => substr( $body, 0, 200 ),
            'timestamp'   => $timestamp,
        ];
        $history = array_slice( $history, -100 );
        update_option( 'rtbcb_api_error_history', $history );
    }
}

/**
 * Prepare enhanced result payload for unified dashboard responses.
 *
 * @param array $overview Overview data.
 * @param array $debug    Optional debug information.
 * @return array
 */
function rtbcb_prepare_enhanced_result( $overview, $debug = [] ) {
    $overview = is_array( $overview ) ? $overview : [];

    return [
        'overview'        => $overview['analysis'] ?? '',
        'recommendations' => $overview['recommendations'] ?? [],
        'references'      => $overview['references'] ?? [],
        'debug'           => $debug,
    ];
}

/**
 * Calculate LLM response quality score.
 *
 * @param string $content Model response content.
 * @param string $prompt  Prompt used for the request.
 * @return int
 */
function rtbcb_calculate_llm_response_quality( $content, $prompt ) {
    $score = 50; // Base score

    // Length scoring
    $word_count = str_word_count( wp_strip_all_tags( $content ) );
    if ( $word_count >= 50 && $word_count <= 500 ) {
        $score += 15;
    } elseif ( $word_count > 25 ) {
        $score += 10;
    }

    // Relevance scoring - check for business/treasury terms
    $business_terms = [ 'treasury', 'cash', 'financial', 'business', 'ROI', 'investment', 'cost', 'benefit' ];
    $found_terms    = 0;
    foreach ( $business_terms as $term ) {
        if ( false !== stripos( $content, $term ) ) {
            $found_terms++;
        }
    }
    $score += min( 20, $found_terms * 3 );

    // Structure scoring
    $sentences = substr_count( $content, '.' ) + substr_count( $content, '!' ) + substr_count( $content, '?' );
    if ( $sentences >= 2 && $sentences <= 10 ) {
        $score += 10;
    }

    // Coherence indicators
    $coherence_words = [ 'however', 'therefore', 'additionally', 'furthermore', 'moreover', 'consequently' ];
    foreach ( $coherence_words as $word ) {
        if ( false !== stripos( $content, $word ) ) {
            $score += 5;
            break; // Only award once
        }
    }

    return min( 100, max( 0, $score ) );
}

/**
 * LLM Integration Testing AJAX Handlers - Add to enhanced-ajax-handlers.php
 */

// Add AJAX handlers for LLM Integration Testing
add_action( 'wp_ajax_rtbcb_test_llm_model', 'rtbcb_ajax_test_llm_model' );
add_action( 'wp_ajax_rtbcb_test_company_overview_enhanced', 'rtbcb_ajax_test_company_overview_enhanced' );
add_action( 'wp_ajax_rtbcb_calculate_roi_test', 'rtbcb_ajax_calculate_roi_test' );
add_action( 'wp_ajax_rtbcb_evaluate_response_quality', 'rtbcb_ajax_evaluate_response_quality' );
add_action( 'wp_ajax_rtbcb_optimize_prompt_tokens', 'rtbcb_ajax_optimize_prompt_tokens' );
add_action( 'wp_ajax_rtbcb_run_data_health_checks', 'rtbcb_run_data_health_checks' );
add_action( 'wp_ajax_rtbcb_test_rag_query', 'rtbcb_test_rag_query' );
add_action( 'wp_ajax_rtbcb_rag_rebuild_index', 'rtbcb_rag_rebuild_index' );
add_action( 'wp_ajax_rtbcb_generate_preview_report', 'rtbcb_generate_preview_report' );
add_action( 'wp_ajax_rtbcb_save_dashboard_settings', 'rtbcb_save_dashboard_settings' );
add_action( 'wp_ajax_rtbcb_export_dashboard_results', 'rtbcb_export_dashboard_results' );
add_action( 'wp_ajax_rtbcb_generate_report', 'rtbcb_generate_report' );
add_action( 'wp_ajax_nopriv_rtbcb_generate_report', 'rtbcb_generate_report' );

/**
 * Test individual LLM model with given prompt.
 *
 * @return void
 */
function rtbcb_ajax_test_llm_model() {
    // Verify nonce and permissions
    if ( ! check_ajax_referer( 'rtbcb_llm_testing', 'nonce', false ) ) {
        rtbcb_send_json_error( 'security_check_failed', rtbcb_get_user_friendly_error( 'security_check_failed' ), 403 );
    }

    if ( ! current_user_can( 'manage_options' ) ) {
        rtbcb_send_json_error( 'insufficient_permissions', rtbcb_get_user_friendly_error( 'insufficient_permissions' ), 403 );
    }

    // Collect and validate input parameters
    $model_key      = isset( $_POST['model_key'] ) ? sanitize_text_field( wp_unslash( $_POST['model_key'] ) ) : '';
    $system_prompt  = isset( $_POST['system_prompt'] ) ? sanitize_textarea_field( wp_unslash( $_POST['system_prompt'] ) ) : '';
    $user_prompt    = isset( $_POST['user_prompt'] ) ? sanitize_textarea_field( wp_unslash( $_POST['user_prompt'] ) ) : '';
    $max_tokens     = isset( $_POST['max_tokens'] ) ? intval( wp_unslash( $_POST['max_tokens'] ) ) : 1000;
    $temperature    = isset( $_POST['temperature'] ) ? floatval( wp_unslash( $_POST['temperature'] ) ) : 0.3;
    $include_context = isset( $_POST['include_context'] ) ? rest_sanitize_boolean( wp_unslash( $_POST['include_context'] ) ) : false;

    // Validate required fields
    if ( empty( $model_key ) ) {
        rtbcb_send_json_error( 'model_key_required', __( 'Model key is required.', 'rtbcb' ) );
    }

    if ( empty( $user_prompt ) ) {
        rtbcb_send_json_error( 'user_prompt_required', __( 'User prompt is required.', 'rtbcb' ) );
    }

    // Validate model key
    $available_models = [
        'mini'     => get_option( 'rtbcb_mini_model', rtbcb_get_default_model( 'mini' ) ),
        'premium'  => get_option( 'rtbcb_premium_model', rtbcb_get_default_model( 'premium' ) ),
        'advanced' => get_option( 'rtbcb_advanced_model', rtbcb_get_default_model( 'advanced' ) ),
    ];

    if ( ! isset( $available_models[ $model_key ] ) ) {
        rtbcb_send_json_error( 'invalid_model_key', __( 'Invalid model key.', 'rtbcb' ) );
    }

    $model_name = $available_models[ $model_key ];

    // Validate parameters
    if ( $max_tokens < 1 || $max_tokens > 4000 ) {
        rtbcb_send_json_error( 'invalid_max_tokens', __( 'Max tokens must be between 1 and 4000.', 'rtbcb' ) );
    }

    if ( $temperature < 0 || $temperature > 2 ) {
        rtbcb_send_json_error( 'invalid_temperature', __( 'Temperature must be between 0 and 2.', 'rtbcb' ) );
    }

    // Get API key
    $api_key = get_option( 'rtbcb_openai_api_key', '' );
    if ( empty( $api_key ) ) {
        rtbcb_send_json_error( 'api_key_not_configured', __( 'OpenAI API key not configured.', 'rtbcb' ), 500 );
    }

    // Prepare context if requested
    $context_data = '';
    if ( $include_context ) {
        $context_data = rtbcb_get_sample_context_for_testing();
        $user_prompt  = $context_data . "\n\n" . $user_prompt;
    }

    // Record start time for performance tracking
    $start_time = microtime( true );

    try {
        // Call LLM API
        $result = rtbcb_call_llm_api( $model_name, $system_prompt, $user_prompt, $max_tokens, $temperature );

        if ( is_wp_error( $result ) ) {
            rtbcb_send_json_error( $result->get_error_code(), $result->get_error_message(), 500, $result->get_error_data() );
        }

        // Calculate performance metrics
        $end_time     = microtime( true );
        $response_time = round( ( $end_time - $start_time ) * 1000 ); // milliseconds

        // Process response
        $content     = $result['content'] ?? '';
        $tokens_used = $result['tokens_used'] ?? 0;
        $model_used  = $result['model_used'] ?? $model_name;

        // Calculate additional metrics
        $word_count       = str_word_count( wp_strip_all_tags( $content ) );
        $character_count  = strlen( $content );
        $estimated_cost   = rtbcb_calculate_model_cost( $tokens_used, $model_key );

        // Quality assessment
        $quality_metrics = rtbcb_assess_response_quality( $content, $user_prompt );

        // Prepare response data
        $response_data = [
            'model_key'       => $model_key,
            'model_name'      => $model_name,
            'model_used'      => $model_used,
            'content'         => wp_kses_post( $content ),
            'tokens_used'     => intval( $tokens_used ),
            'response_time'   => intval( $response_time ),
            'word_count'      => intval( $word_count ),
            'character_count' => intval( $character_count ),
            'estimated_cost'  => floatval( $estimated_cost ),
            'quality_metrics' => $quality_metrics,
            'request_params'  => [
                'system_prompt'   => $system_prompt,
                'user_prompt'     => $user_prompt,
                'max_tokens'      => $max_tokens,
                'temperature'     => $temperature,
                'include_context' => $include_context,
            ],
            'timestamp'        => current_time( 'mysql' ),
            'context_included' => $include_context,
        ];

        // Log successful test
        error_log(
            sprintf(
                'RTBCB: LLM Model Test - Model: %s, Tokens: %d, Time: %dms, Quality: %d/100',
                $model_key,
                $tokens_used,
                $response_time,
                $quality_metrics['overall_score']
            )
        );

        wp_send_json_success( $response_data );
    } catch ( Exception $e ) {
        error_log( 'RTBCB LLM Model Test Error: ' . $e->getMessage() );
        rtbcb_send_json_error( 'model_test_failed', __( 'An error occurred while testing the model. Please try again.', 'rtbcb' ), 500, $e->getMessage() );
    }
}

/**
 * Enhanced company overview testing with debug information.
 *
 * @return void
 */
function rtbcb_ajax_test_company_overview_enhanced() {
    if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
        $nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : 'missing';
        error_log( '[RTBCB] AJAX Entry: company_overview, User: ' . get_current_user_id() . ', Nonce: ' . $nonce );
    }

    // Verify nonce and permissions
    if ( ! check_ajax_referer( 'rtbcb_unified_test_dashboard', 'nonce', false ) ) {
        rtbcb_send_json_error( 'security_check_failed', __( 'Security check failed.', 'rtbcb' ), 403, 'Invalid or missing nonce.' );
    }

    if ( ! current_user_can( 'manage_options' ) ) {
        rtbcb_send_json_error( 'insufficient_permissions', __( 'Insufficient permissions.', 'rtbcb' ), 403, 'User lacks manage_options capability.' );
    }

    // Get input parameters
    $company_name = isset( $_POST['company_name'] ) ? sanitize_text_field( wp_unslash( $_POST['company_name'] ) ) : '';
    $model_key    = isset( $_POST['model'] ) ? sanitize_text_field( wp_unslash( $_POST['model'] ) ) : 'mini';
    $show_debug   = isset( $_POST['show_debug'] ) ? rest_sanitize_boolean( wp_unslash( $_POST['show_debug'] ) ) : false;
    $request_id   = isset( $_POST['request_id'] ) ? sanitize_text_field( wp_unslash( $_POST['request_id'] ) ) : '';

    // Validate required fields
    if ( empty( $company_name ) ) {
        rtbcb_send_json_error( 'company_name_required', __( 'Company name is required.', 'rtbcb' ), 400, 'Missing company_name parameter.' );
    }

    // Set timeout and memory limits for comprehensive analysis
    if ( ! ini_get( 'safe_mode' ) ) {
        set_time_limit( 180 ); // 3 minutes
    }
    wp_raise_memory_limit( '512M' );

    $start_time = microtime( true );

    try {
        // Generate company overview
        $overview_result = rtbcb_test_generate_company_overview( $company_name, $model_key );

        if ( is_wp_error( $overview_result ) ) {
            $detail = $overview_result->get_error_data();
            if ( empty( $detail ) ) {
                $detail = $overview_result->get_error_message();
            }
            rtbcb_send_json_error( $overview_result->get_error_code(), $overview_result->get_error_message(), 500, $detail );
        }

        // Calculate metrics
        $end_time    = microtime( true );
        $elapsed_time = round( $end_time - $start_time, 2 );
        $content     = $overview_result['analysis'] ?? '';
        $word_count  = str_word_count( wp_strip_all_tags( $content ) );

        // Prepare debug information if requested
        $debug_info = [];
        if ( $show_debug ) {
            $debug_info = [
                'system_prompt' => rtbcb_get_company_analysis_system_prompt(),
                'user_prompt'   => rtbcb_get_company_analysis_user_prompt( $company_name ),
                'api_request'   => [
                    'model'       => $model_key,
                    'max_tokens'  => 2000,
                    'temperature' => 0.3,
                ],
                'response_time' => $elapsed_time,
                'tokens_used'   => $overview_result['tokens_used'] ?? 0,
                'model_used'    => $overview_result['model_used'] ?? $model_key,
            ];
        }

        // Store result for navigation
        $company_data = [
            'name'         => $company_name,
            'summary'      => wp_strip_all_tags( $content ),
            'analysis'     => $content,
            'generated_at' => current_time( 'mysql' ),
            'word_count'   => $word_count,
            'elapsed_time' => $elapsed_time,
        ];
        update_option( 'rtbcb_current_company', $company_data );

        // Prepare response
        $response_data = [
            'overview'     => wp_kses_post( $content ),
            'company_name' => $company_name,
            'word_count'   => $word_count,
            'elapsed'      => $elapsed_time,
            'generated'    => current_time( 'Y-m-d H:i:s' ),
            'model_used'   => $model_key,
            'debug'        => $debug_info,
        ];

        error_log( sprintf( 'RTBCB: Company Overview Test - %s (%d words, %ss)', $company_name, $word_count, $elapsed_time ) );

        wp_send_json_success( $response_data );
    } catch ( Exception $e ) {
        error_log( 'RTBCB Enhanced Company Overview Error: ' . $e->getMessage() );
        rtbcb_send_json_error( 'company_overview_failed', __( 'An error occurred while generating the company overview.', 'rtbcb' ), 500, $e->getMessage() );
    }
}

/**
 * Calculate ROI for testing purposes.
 *
 * @return void
 */
function rtbcb_ajax_calculate_roi_test() {
    error_log( 'AJAX handler called: rtbcb_ajax_calculate_roi_test' );
    error_log( 'Request data: ' . print_r( $_POST, true ) );
    // Verify nonce and permissions
    if ( ! check_ajax_referer( 'rtbcb_roi_calculator_test', 'nonce', false ) ) {
        rtbcb_send_json_error(
            'security_check_failed',
            __( 'Security check failed.', 'rtbcb' ),
            403,
            [ 'request' => rtbcb_sanitize_form_data( wp_unslash( $_POST ) ) ]
        );
    }

    if ( ! current_user_can( 'manage_options' ) ) {
        rtbcb_send_json_error(
            'insufficient_permissions',
            __( 'Insufficient permissions.', 'rtbcb' ),
            403,
            [ 'request' => rtbcb_sanitize_form_data( wp_unslash( $_POST ) ) ]
        );
    }

    // Get ROI input data
    $roi_data = isset( $_POST['roi_data'] ) ? wp_unslash( $_POST['roi_data'] ) : [];

    if ( empty( $roi_data ) || ! is_array( $roi_data ) ) {
        rtbcb_send_json_error(
            'roi_data_required',
            __( 'ROI data is required.', 'rtbcb' ),
            400,
            [ 'request' => rtbcb_sanitize_form_data( wp_unslash( $_POST ) ) ]
        );
    }

    // Sanitize ROI data
    $roi_data = rtbcb_sanitize_form_data( $roi_data );

    try {
        // Calculate ROI scenarios
        $scenarios = RTBCB_Calculator::calculate_roi( $roi_data );

        if ( is_wp_error( $scenarios ) ) {
            rtbcb_send_json_error( $scenarios->get_error_code(), $scenarios->get_error_message(), 500, $scenarios->get_error_data() );
        }

        // Add additional analysis
        $analysis = rtbcb_analyze_roi_scenarios( $scenarios );

        $response_data = array_merge(
            $scenarios,
            [
                'analysis'         => $analysis,
                'input_summary'    => rtbcb_summarize_roi_inputs( $roi_data ),
                'calculated_at'    => current_time( 'mysql' ),
                'calculation_time' => microtime( true ),
            ]
        );

        wp_send_json_success( $response_data );
    } catch ( Exception $e ) {
        error_log( 'RTBCB ROI Test Calculation Error: ' . $e->getMessage() . ' Data: ' . print_r( $roi_data, true ) );
        rtbcb_send_json_error(
            'roi_calculation_failed',
            __( 'An error occurred while calculating ROI.', 'rtbcb' ),
            500,
            [
                'error'   => $e->getMessage(),
                'request' => $roi_data,
            ]
        );
    }
}

/**
 * Evaluate response quality using various metrics.
 *
 * @return void
 */
function rtbcb_ajax_evaluate_response_quality() {
    // Verify nonce and permissions
    if ( ! check_ajax_referer( 'rtbcb_llm_testing', 'nonce', false ) ) {
        rtbcb_send_json_error( 'security_check_failed', __( 'Security check failed.', 'rtbcb' ), 403 );
    }

    if ( ! current_user_can( 'manage_options' ) ) {
        rtbcb_send_json_error( 'insufficient_permissions', __( 'Insufficient permissions.', 'rtbcb' ), 403 );
    }

    $response_text  = isset( $_POST['response_text'] ) ? sanitize_textarea_field( wp_unslash( $_POST['response_text'] ) ) : '';
    $reference_text = isset( $_POST['reference_text'] ) ? sanitize_textarea_field( wp_unslash( $_POST['reference_text'] ) ) : '';

    if ( empty( $response_text ) ) {
        rtbcb_send_json_error( 'response_text_required', __( 'Response text is required.', 'rtbcb' ) );
    }

    try {
        // Perform comprehensive quality assessment
        $quality_metrics = rtbcb_assess_response_quality( $response_text, '', $reference_text );

        // Add detailed analysis
        $detailed_analysis = [
            'readability'        => rtbcb_calculate_readability_score( $response_text ),
            'sentiment'          => rtbcb_analyze_sentiment( $response_text ),
            'key_topics'         => rtbcb_extract_key_topics( $response_text ),
            'structure_score'    => rtbcb_analyze_structure( $response_text ),
            'business_relevance' => rtbcb_assess_business_relevance( $response_text ),
        ];

        $response_data = [
            'quality_metrics'   => $quality_metrics,
            'detailed_analysis' => $detailed_analysis,
            'evaluation_time'   => current_time( 'mysql' ),
            'recommendations'   => rtbcb_generate_improvement_recommendations( $quality_metrics ),
        ];

        error_log( sprintf( 'RTBCB: Response Evaluation - Score: %d', intval( $quality_metrics['overall_score'] ) ) );

        wp_send_json_success( $response_data );
    } catch ( Exception $e ) {
        error_log( 'RTBCB Response Quality Evaluation Error: ' . $e->getMessage() );
        rtbcb_send_json_error( 'response_evaluation_failed', __( 'An error occurred while evaluating response quality.', 'rtbcb' ), 500, $e->getMessage() );
    }
}

/**
 * Optimize prompt for token efficiency.
 *
 * @return void
 */
function rtbcb_ajax_optimize_prompt_tokens() {
    // Verify nonce and permissions
    if ( ! check_ajax_referer( 'rtbcb_llm_testing', 'nonce', false ) ) {
        rtbcb_send_json_error( 'security_check_failed', __( 'Security check failed.', 'rtbcb' ), 403 );
    }

    if ( ! current_user_can( 'manage_options' ) ) {
        rtbcb_send_json_error( 'insufficient_permissions', __( 'Insufficient permissions.', 'rtbcb' ), 403 );
    }

    $prompt = isset( $_POST['prompt'] ) ? sanitize_textarea_field( wp_unslash( $_POST['prompt'] ) ) : '';

    if ( empty( $prompt ) ) {
        rtbcb_send_json_error( 'prompt_required', __( 'Prompt text is required.', 'rtbcb' ) );
    }

    try {
        // Analyze current prompt
        $analysis = rtbcb_analyze_prompt_tokens( $prompt );

        // Generate optimization suggestions
        $optimizations = rtbcb_generate_prompt_optimizations( $prompt );

        // Create optimized version
        $optimized_prompt = rtbcb_apply_prompt_optimizations( $prompt, $optimizations );

        // Compare before and after
        $optimized_analysis = rtbcb_analyze_prompt_tokens( $optimized_prompt );

        $response_data = [
            'original_analysis'     => $analysis,
            'optimized_analysis'    => $optimized_analysis,
            'optimized_prompt'      => wp_kses_post( $optimized_prompt ),
            'optimizations_applied' => $optimizations,
            'token_savings'         => $analysis['estimated_tokens'] - $optimized_analysis['estimated_tokens'],
            'cost_savings'          => rtbcb_calculate_token_cost_savings( $analysis, $optimized_analysis ),
            'efficiency_improvement' => round( ( ( $analysis['estimated_tokens'] - $optimized_analysis['estimated_tokens'] ) / $analysis['estimated_tokens'] ) * 100, 2 ),
        ];

        error_log(
            sprintf(
                'RTBCB: Prompt Optimization - %d -> %d tokens',
                intval( $analysis['estimated_tokens'] ),
                intval( $optimized_analysis['estimated_tokens'] )
            )
        );

        wp_send_json_success( $response_data );
    } catch ( Exception $e ) {
        error_log( 'RTBCB Prompt Token Optimization Error: ' . $e->getMessage() );
        rtbcb_send_json_error( 'prompt_optimization_failed', __( 'An error occurred while optimizing the prompt.', 'rtbcb' ), 500, $e->getMessage() );
    }
}

/**
 * Helper Functions for LLM Integration Testing
 */

/**
 * Call LLM API with given parameters.
 *
 * @param string $model_name   Model to use.
 * @param string $system_prompt System prompt.
 * @param string $user_prompt  User prompt.
 * @param int    $max_tokens   Maximum tokens.
 * @param float  $temperature  Temperature setting.
 * @return array|WP_Error Response data or error.
 */
function rtbcb_call_llm_api( $model_name, $system_prompt, $user_prompt, $max_tokens, $temperature ) {
    $api_key = get_option( 'rtbcb_openai_api_key', '' );

    if ( empty( $api_key ) ) {
        return new WP_Error( 'no_api_key', __( 'OpenAI API key not configured.', 'rtbcb' ) );
    }

    // Prepare messages
    $messages = [];
    if ( ! empty( $system_prompt ) ) {
        $messages[] = [
            'role'    => 'system',
            'content' => $system_prompt,
        ];
    }
    $messages[] = [
        'role'    => 'user',
        'content' => $user_prompt,
    ];

    // Prepare request data
    $request_data = [
        'model'      => $model_name,
        'messages'   => $messages,
        'max_tokens' => $max_tokens,
    ];

    // Add temperature if model supports it
    if ( rtbcb_model_supports_temperature( $model_name ) ) {
        $request_data['temperature'] = $temperature;
    }

    // Make API request
    $response = wp_remote_post(
        'https://api.openai.com/v1/chat/completions',
        [
            'headers' => [
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type'  => 'application/json',
            ],
            'body'    => wp_json_encode( $request_data ),
            'timeout' => 60,
        ]
    );

    if ( is_wp_error( $response ) ) {
        return $response;
    }

    $response_code = wp_remote_retrieve_response_code( $response );
    $response_body = wp_remote_retrieve_body( $response );

    if ( 200 !== $response_code ) {
        return new WP_Error( 'api_error', sprintf( __( 'API request failed with code %d: %s', 'rtbcb' ), $response_code, $response_body ) );
    }

    $decoded = json_decode( $response_body, true );

    if ( json_last_error() !== JSON_ERROR_NONE ) {
        return new WP_Error( 'json_decode_error', __( 'Invalid JSON response from API.', 'rtbcb' ) );
    }

    if ( ! isset( $decoded['choices'][0]['message']['content'] ) ) {
        return new WP_Error( 'invalid_response', __( 'Unexpected API response structure.', 'rtbcb' ) );
    }

    // Extract response data
    $content     = $decoded['choices'][0]['message']['content'];
    $tokens_used = $decoded['usage']['total_tokens'] ?? 0;
    $model_used  = $decoded['model'] ?? $model_name;

    return [
        'content'      => $content,
        'tokens_used'  => $tokens_used,
        'model_used'   => $model_used,
        'usage'        => $decoded['usage'] ?? [],
        'raw_response' => $decoded,
    ];
}

/**
 * Calculate model cost based on tokens used.
 *
 * @param int    $tokens_used Number of tokens used.
 * @param string $model_key   Model key.
 * @return float Estimated cost in USD.
 */
function rtbcb_calculate_model_cost( $tokens_used, $model_key ) {
    $cost_per_1k = [
        'mini'     => 0.00015,  // GPT-4O Mini input
        'premium'  => 0.005,    // GPT-4O input
        'advanced' => 0.015,    // O1-Preview input (approximation)
    ];

    $rate = $cost_per_1k[ $model_key ] ?? 0.005;
    return ( $tokens_used / 1000 ) * $rate;
}

/**
 * Get human-readable model name from key.
 *
 * @param string $model_key Model key.
 * @return string
 */
function rtbcb_get_model_display_name( $model_key ) {
    $names = [
        'mini'     => __( 'GPT-4O Mini', 'rtbcb' ),
        'premium'  => __( 'GPT-4O', 'rtbcb' ),
        'advanced' => __( 'O1-Preview', 'rtbcb' ),
    ];

    return $names[ $model_key ] ?? ucfirst( $model_key );
}

/**
 * Find best performing result by quality score.
 *
 * @param array $results Test results.
 * @return array
 */
function rtbcb_find_best_performing_result( $results ) {
    if ( empty( $results ) ) {
        return [];
    }

    usort(
        $results,
        function ( $a, $b ) {
            return ( $b['quality_score'] ?? 0 ) <=> ( $a['quality_score'] ?? 0 );
        }
    );

    $best = $results[0];

    return [
        'model_key' => $best['model_key'],
        'score'     => $best['quality_score'],
        'reason'    => 'highest_quality',
    ];
}

/*
Per-run result schema
[
    'model_key' => 'mini',
    'model_name' => 'GPT-4O Mini',
    'prompt' => 'A',
    'response' => 'Generated response text...',
    'latency' => 1250.5,
    'tokens_used' => 150,
    'cost_estimate' => 0.000225,
    'quality_score' => 85,
    'pass_fail_rules' => [
        'contains_roi' => true,
        'json_parseable' => false,
        'length_adequate' => true,
    ],
]

Aggregation summary schema
[
    'total_tests' => 4,
    'total_time' => 5.2,
    'avg_latency' => 1312.75,
    'total_tokens' => 600,
    'total_cost' => 0.0009,
    'best_performer' => [
        'model_key' => 'premium',
        'score' => 92,
        'reason' => 'highest_quality',
    ],
]
*/

/**
 * Assess response quality using various metrics.
 *
 * @param string $content        Response content.
 * @param string $prompt         Original prompt.
 * @param string $reference_text Optional reference text.
 * @return array Quality metrics.
 */
function rtbcb_assess_response_quality( $content, $prompt = '', $reference_text = '' ) {
    // Basic metrics
    $word_count     = str_word_count( wp_strip_all_tags( $content ) );
    $char_count     = strlen( $content );
    $sentence_count = preg_match_all( '/[.!?]+/', $content );

    // Initialize score
    $score = 50; // Base score

    // Length scoring
    if ( $word_count >= 50 && $word_count <= 500 ) {
        $score += 15;
    } elseif ( $word_count > 500 ) {
        $score += 10;
    }

    // Structure scoring
    $avg_sentence_length = $word_count / max( $sentence_count, 1 );
    if ( $avg_sentence_length >= 10 && $avg_sentence_length <= 25 ) {
        $score += 10;
    }

    // Content relevance (basic keyword matching)
    $business_terms = [ 'ROI', 'business', 'treasury', 'financial', 'investment', 'cost', 'benefit', 'analysis', 'strategy' ];
    $found_terms    = 0;
    foreach ( $business_terms as $term ) {
        if ( stripos( $content, $term ) !== false ) {
            $found_terms++;
        }
    }
    $score += min( 15, $found_terms * 2 );

    // Clarity indicators
    if ( strpos( $content, 'however' ) || strpos( $content, 'therefore' ) || strpos( $content, 'additionally' ) ) {
        $score += 5; // Transition words indicate good flow
    }

    // Professional tone indicators
    if ( ! preg_match( '/\b(gonna|wanna|gotta)\b/i', $content ) ) {
        $score += 5; // No casual contractions
    }

    // Completeness check
    if ( $word_count >= 100 && $sentence_count >= 3 ) {
        $score += 10;
    }

    // Cap the score
    $score = min( 100, max( 0, $score ) );

    return [
        'overall_score'        => intval( $score ),
        'word_count'           => $word_count,
        'character_count'      => $char_count,
        'sentence_count'       => $sentence_count,
        'avg_sentence_length'  => round( $avg_sentence_length, 1 ),
        'business_terms_found' => $found_terms,
        'readability'          => rtbcb_calculate_simple_readability( $content ),
        'completeness'         => rtbcb_assess_completeness( $content, $prompt ),
        'coherence'            => rtbcb_assess_coherence( $content ),
    ];
}

/**
 * Calculate simple readability score.
 *
 * @param string $text Text to analyze.
 * @return string Readability level.
 */
function rtbcb_calculate_simple_readability( $text ) {
    $words                  = str_word_count( $text );
    $sentences              = preg_match_all( '/[.!?]+/', $text );
    $avg_words_per_sentence = $words / max( $sentences, 1 );

    if ( $avg_words_per_sentence <= 15 ) {
        return 'Easy';
    } elseif ( $avg_words_per_sentence <= 20 ) {
        return 'Moderate';
    } else {
        return 'Complex';
    }
}

/**
 * Assess response completeness.
 *
 * @param string $content Response content.
 * @param string $prompt  Original prompt.
 * @return int Completeness score (0-100).
 */
function rtbcb_assess_completeness( $content, $prompt ) {
    $score = 50; // Base score

    // Check if response addresses key components
    if ( stripos( $prompt, 'analyze' ) !== false && stripos( $content, 'analysis' ) !== false ) {
        $score += 15;
    }

    if ( stripos( $prompt, 'business case' ) !== false && ( stripos( $content, 'business' ) !== false || stripos( $content, 'case' ) !== false ) ) {
        $score += 15;
    }

    if ( stripos( $prompt, 'ROI' ) !== false && ( stripos( $content, 'ROI' ) !== false || stripos( $content, 'return' ) !== false ) ) {
        $score += 10;
    }

    // Length completeness
    $word_count = str_word_count( $content );
    if ( $word_count >= 100 ) {
        $score += 10;
    }

    return min( 100, $score );
}

/**
 * Assess response coherence.
 *
 * @param string $content Response content.
 * @return int Coherence score (0-100).
 */
function rtbcb_assess_coherence( $content ) {
    $score = 60; // Base score

    // Check for logical flow indicators
    $flow_words       = [ 'first', 'second', 'next', 'then', 'finally', 'however', 'therefore', 'additionally', 'furthermore', 'moreover' ];
    $found_flow_words = 0;
    foreach ( $flow_words as $word ) {
        if ( stripos( $content, $word ) !== false ) {
            $found_flow_words++;
        }
    }
    $score += min( 20, $found_flow_words * 3 );

    // Check for structure (paragraphs or sections)
    $paragraph_count = substr_count( $content, "\n\n" ) + substr_count( $content, '<p>' );
    if ( $paragraph_count >= 2 ) {
        $score += 10;
    }

    // Check for repetitive patterns (negative indicator)
    $sentences       = preg_split( '/[.!?]+/', $content );
    $similar_starts  = 0;
    for ( $i = 1; $i < count( $sentences ); $i++ ) {
        $current_start  = substr( trim( $sentences[ $i ] ), 0, 10 );
        $previous_start = substr( trim( $sentences[ $i - 1 ] ), 0, 10 );
        if ( $current_start === $previous_start && strlen( $current_start ) > 5 ) {
            $similar_starts++;
        }
    }
    $score -= min( 20, $similar_starts * 5 );

    return min( 100, max( 0, $score ) );
}

/**
 * Get sample context data for testing.
 *
 * @return string Sample context.
 */
function rtbcb_get_sample_context_for_testing() {
    return 'Company Context: Mid-market manufacturing company with $150M annual revenue, 500 employees, operating in North America. Current treasury challenges include manual bank reconciliation processes, limited cash visibility across 8 banking relationships, and time-consuming month-end reporting that takes 2 weeks to complete. The treasury team consists of 3 FTEs who currently spend 60% of their time on manual, repetitive tasks.';
}

/**
 * Analyze prompt tokens and efficiency.
 *
 * @param string $prompt Prompt text.
 * @return array Token analysis.
 */
function rtbcb_analyze_prompt_tokens( $prompt ) {
    $word_count       = str_word_count( $prompt );
    $char_count       = strlen( $prompt );
    $estimated_tokens = intval( ceil( $char_count / 4 ) ); // Rough GPT tokenization estimate

    return [
        'word_count'       => $word_count,
        'character_count'  => $char_count,
        'estimated_tokens' => $estimated_tokens,
        'efficiency_score' => rtbcb_calculate_prompt_efficiency( $prompt ),
    ];
}

/**
 * Calculate prompt efficiency score.
 *
 * @param string $prompt Prompt text.
 * @return int Efficiency score (0-100).
 */
function rtbcb_calculate_prompt_efficiency( $prompt ) {
    $words       = explode( ' ', $prompt );
    $word_count  = count( $words );
    $unique_words = count( array_unique( array_map( 'strtolower', $words ) ) );

    $redundancy = 1 - ( $unique_words / $word_count );

    $efficiency = 80; // Base efficiency

    // Penalize redundancy
    $efficiency -= $redundancy * 30;

    // Penalize excessive length
    if ( $word_count > 200 ) {
        $efficiency -= 15;
    }

    // Bonus for conciseness
    if ( $word_count < 50 && $word_count > 10 ) {
        $efficiency += 10;
    }

    return intval( min( 100, max( 0, $efficiency ) ) );
}

/**
 * Generate prompt optimization suggestions.
 *
 * @param string $prompt Original prompt.
 * @return array Optimization suggestions.
 */
function rtbcb_generate_prompt_optimizations( $prompt ) {
    $optimizations = [];

    // Check for common inefficiencies
    if ( stripos( $prompt, 'please' ) !== false || stripos( $prompt, 'could you' ) !== false ) {
        $optimizations[] = [
            'type'        => 'remove_politeness',
            'description' => 'Remove politeness words to save tokens',
            'example'     => 'Remove "please", "could you", etc.',
        ];
    }

    if ( preg_match_all( '/\b(\w+)\b.*\b\1\b/', $prompt, $matches ) ) {
        $optimizations[] = [
            'type'        => 'reduce_repetition',
            'description' => 'Reduce word repetition',
            'example'     => 'Combine or rephrase repeated concepts',
        ];
    }

    if ( str_word_count( $prompt ) > 150 ) {
        $optimizations[] = [
            'type'        => 'reduce_length',
            'description' => 'Shorten overall length',
            'example'     => 'Focus on essential information only',
        ];
    }

    return $optimizations;
}

/**
 * Apply prompt optimizations.
 *
 * @param string $prompt        Original prompt.
 * @param array  $optimizations List of optimizations.
 * @return string Optimized prompt.
 */
function rtbcb_apply_prompt_optimizations( $prompt, $optimizations ) {
    $optimized = $prompt;

    foreach ( $optimizations as $optimization ) {
        switch ( $optimization['type'] ) {
            case 'remove_politeness':
                $optimized = preg_replace( '/\b(please|could you|would you|if you could)\b\s*/i', '', $optimized );
                break;
            case 'reduce_repetition':
                // Basic repetition removal (this would need more sophisticated logic in production)
                $optimized = preg_replace( '/\b(\w+)\s+\1\b/i', '$1', $optimized );
                break;
            case 'reduce_length':
                // Simple length reduction by removing filler words
                $filler_words = [ 'very', 'really', 'quite', 'rather', 'somewhat', 'just', 'actually' ];
                foreach ( $filler_words as $filler ) {
                    $optimized = preg_replace( '/\b' . preg_quote( $filler, '/' ) . '\b\s*/i', '', $optimized );
                }
                break;
        }
    }

    // Clean up extra spaces
    $optimized = preg_replace( '/\s+/', ' ', $optimized );
    $optimized = trim( $optimized );

    return $optimized;
}

/**
 * Calculate token cost savings.
 *
 * @param array $original_analysis  Original prompt analysis.
 * @param array $optimized_analysis Optimized prompt analysis.
 * @return array Cost savings by model.
 */
function rtbcb_calculate_token_cost_savings( $original_analysis, $optimized_analysis ) {
    $token_savings = $original_analysis['estimated_tokens'] - $optimized_analysis['estimated_tokens'];

    $models = [
        'mini'     => 0.00015,
        'premium'  => 0.005,
        'advanced' => 0.015,
    ];

    $cost_savings = [];
    foreach ( $models as $model_key => $cost_per_1k ) {
        $savings               = ( $token_savings / 1000 ) * $cost_per_1k;
        $cost_savings[ $model_key ] = round( $savings, 6 );
    }

    return $cost_savings;
}

/**
 * Get company analysis system prompt.
 *
 * @return string System prompt.
 */
function rtbcb_get_company_analysis_system_prompt() {
    return 'You are a business analyst specializing in treasury operations and financial technology. Provide comprehensive, professional analysis focusing on treasury challenges, opportunities, and technology recommendations.';
}

/**
 * Get company analysis user prompt.
 *
 * @param string $company_name Company name.
 * @return string User prompt.
 */
function rtbcb_get_company_analysis_user_prompt( $company_name ) {
    return "Analyze {$company_name} from a treasury operations perspective. Focus on potential treasury challenges, cash management needs, banking relationships, and opportunities for treasury technology improvements. Provide specific, actionable insights.";
}

/**
 * Analyze ROI scenarios for additional insights.
 *
 * @param array $scenarios ROI scenarios.
 * @return array Analysis insights.
 */
function rtbcb_analyze_roi_scenarios( $scenarios ) {
    $analysis = [
        'recommendation' => 'proceed',
        'confidence'     => 'high',
        'key_drivers'    => [],
        'risks'          => [],
        'opportunities'  => [],
    ];

    // Analyze base case ROI
    $base_roi = $scenarios['base']['roi_percentage'] ?? 0;

    if ( $base_roi >= 200 ) {
        $analysis['recommendation'] = 'strongly_recommend';
        $analysis['confidence']     = 'very_high';
    } elseif ( $base_roi >= 100 ) {
        $analysis['recommendation'] = 'recommend';
        $analysis['confidence']     = 'high';
    } elseif ( $base_roi >= 50 ) {
        $analysis['recommendation'] = 'consider';
        $analysis['confidence']     = 'moderate';
    } else {
        $analysis['recommendation'] = 'reconsider';
        $analysis['confidence']     = 'low';
    }

    return $analysis;
}

/**
 * Summarize ROI inputs.
 *
 * @param array $roi_data ROI input data.
 * @return array Input summary.
 */
function rtbcb_summarize_roi_inputs( $roi_data ) {
    return [
        'company_profile'  => [
            'size'     => $roi_data['roi-company-size'] ?? 'unknown',
            'industry' => $roi_data['roi-industry'] ?? 'unknown',
            'revenue'  => $roi_data['roi-annual-revenue'] ?? 0,
        ],
        'treasury_metrics' => [
            'staff_count'     => $roi_data['roi-treasury-staff'] ?? 0,
            'avg_salary'      => $roi_data['roi-avg-salary'] ?? 0,
            'hours_recon'     => $roi_data['roi-hours-reconciliation'] ?? 0,
            'hours_reporting' => $roi_data['roi-hours-reporting'] ?? 0,
            'num_banks'       => $roi_data['roi-num-banks'] ?? 0,
        ],
        'cost_factors'     => [
            'monthly_fees'    => $roi_data['roi-monthly-bank-fees'] ?? 0,
            'error_frequency' => $roi_data['roi-error-frequency'] ?? 0,
            'error_cost'      => $roi_data['roi-avg-error-cost'] ?? 0,
        ],
    ];
}

/**
 * Generate improvement recommendations based on quality metrics.
 *
 * @param array $quality_metrics Quality assessment results.
 * @return array Recommendations.
 */
function rtbcb_generate_improvement_recommendations( $quality_metrics ) {
    $recommendations = [];

    if ( $quality_metrics['overall_score'] < 70 ) {
        if ( $quality_metrics['word_count'] < 50 ) {
            $recommendations[] = 'Increase response length for more comprehensive coverage';
        }

        if ( $quality_metrics['business_terms_found'] < 3 ) {
            $recommendations[] = 'Include more relevant business and treasury terminology';
        }

        if ( $quality_metrics['coherence'] < 60 ) {
            $recommendations[] = 'Improve logical flow and structure with better transitions';
        }

        if ( $quality_metrics['completeness'] < 60 ) {
            $recommendations[] = 'Ensure response fully addresses the original prompt';
        }
    } else {
        $recommendations[] = 'Response quality is good - minor refinements could enhance clarity';
    }

    return $recommendations;
}

/**
 * Calculate a readability score for text.
 *
 * Provides a rough 0-100 score based on average sentence length.
 *
 * @param string $text Text to analyze.
 * @return int Readability score.
 */
function rtbcb_calculate_readability_score( $text ) {
    $words     = max( 1, str_word_count( wp_strip_all_tags( $text ) ) );
    $sentences = max( 1, preg_match_all( '/[.!?]+/', $text ) );
    $avg       = $words / $sentences;
    $score     = 100 - ( $avg - 20 ) * 5;

    return intval( max( 0, min( 100, $score ) ) );
}

/**
 * Perform a naive sentiment analysis on text.
 *
 * @param string $text Text to analyze.
 * @return array Sentiment data with score and label.
 */
function rtbcb_analyze_sentiment( $text ) {
    $positive = [ 'good', 'great', 'benefit', 'positive', 'success', 'improve', 'growth' ];
    $negative = [ 'bad', 'poor', 'negative', 'risk', 'issue', 'problem', 'loss' ];

    $text_lower = strtolower( wp_strip_all_tags( $text ) );

    $pos = 0;
    foreach ( $positive as $word ) {
        if ( substr_count( $text_lower, $word ) ) {
            $pos++;
        }
    }

    $neg = 0;
    foreach ( $negative as $word ) {
        if ( substr_count( $text_lower, $word ) ) {
            $neg++;
        }
    }

    $score = $pos - $neg;
    $label = 'neutral';
    if ( $score > 1 ) {
        $label = 'positive';
    } elseif ( $score < -1 ) {
        $label = 'negative';
    }

    return [
        'score' => $score,
        'label' => $label,
    ];
}

/**
 * Extract key topics from text using keyword matching.
 *
 * @param string $text Text to analyze.
 * @return array List of detected topics.
 */
function rtbcb_extract_key_topics( $text ) {
    $keywords = [ 'cash', 'treasury', 'bank', 'investment', 'risk', 'strategy', 'technology', 'reporting' ];
    $topics   = [];
    foreach ( $keywords as $keyword ) {
        if ( stripos( $text, $keyword ) !== false ) {
            $topics[] = $keyword;
        }
    }
    return array_values( array_unique( $topics ) );
}

/**
 * Analyze structural quality of text.
 *
 * @param string $text Text to analyze.
 * @return int Structure score (0-100).
 */
function rtbcb_analyze_structure( $text ) {
    $paragraphs = max( 1, substr_count( trim( $text ), "\n" ) + 1 );
    $sentences  = max( 1, preg_match_all( '/[.!?]+/', $text ) );

    $score = 50;

    if ( $paragraphs >= 2 ) {
        $score += 25;
    }

    $avg = str_word_count( $text ) / $sentences;
    if ( $avg >= 10 && $avg <= 25 ) {
        $score += 25;
    }

    return intval( max( 0, min( 100, $score ) ) );
}

/**
 * Assess business relevance of text based on keyword usage.
 *
 * @param string $text Text to analyze.
 * @return int Business relevance score (0-100).
 */
function rtbcb_assess_business_relevance( $text ) {
    $keywords = [ 'treasury', 'cash', 'bank', 'finance', 'ROI', 'investment', 'cost', 'benefit' ];
    $found    = 0;
    foreach ( $keywords as $keyword ) {
        if ( stripos( $text, $keyword ) !== false ) {
            $found++;
        }
    }

    $score = ( $found / count( $keywords ) ) * 100;

    return intval( max( 0, min( 100, $score ) ) );
}

/**
 * Execute a RAG search query for testing purposes.
 *
 * @return void
 */
function rtbcb_test_rag_query() {
    if ( ! check_ajax_referer( 'rtbcb_unified_test_dashboard', 'nonce', false ) ) {
        rtbcb_send_json_error( 'security_check_failed', __( 'Security check failed.', 'rtbcb' ), 403 );
    }

    if ( ! current_user_can( 'manage_options' ) ) {
        rtbcb_send_json_error( 'insufficient_permissions', __( 'Insufficient permissions.', 'rtbcb' ), 403 );
    }

    $query = isset( $_POST['query'] ) ? sanitize_text_field( wp_unslash( $_POST['query'] ) ) : '';
    $top_k = isset( $_POST['top_k'] ) ? intval( wp_unslash( $_POST['top_k'] ) ) : 3;
    $type  = isset( $_POST['type'] ) ? sanitize_key( wp_unslash( $_POST['type'] ) ) : 'all';

    if ( '' === $query ) {
        rtbcb_send_json_error( 'query_required', __( 'Query is required.', 'rtbcb' ) );
    }

    if ( ! class_exists( 'RTBCB_RAG' ) ) {
        rtbcb_send_json_error( 'rag_class_missing', __( 'RAG class missing.', 'rtbcb' ), 500 );
    }

    $api_key = get_option( 'rtbcb_openai_api_key' );
    if ( empty( $api_key ) ) {
        rtbcb_send_json_error( 'api_key_missing', __( 'No API key configured.', 'rtbcb' ), 500 );
    }

    try {
        $rag = new RTBCB_RAG();

        $embed_method = new ReflectionMethod( RTBCB_RAG::class, 'get_embedding' );
        $embed_method->setAccessible( true );
        $embedding         = $embed_method->invoke( $rag, $query );
        $embedding_length  = count( $embedding );
        $embedding_preview = array_slice( $embedding, 0, 5 );

        $start   = microtime( true );
        $results = $rag->search_similar( $query, $top_k );
        $elapsed = intval( ( microtime( true ) - $start ) * 1000 );

        if ( 'all' !== $type ) {
            $results = array_values( array_filter( $results, function( $r ) use ( $type ) {
                return isset( $r['type'] ) && $r['type'] === $type;
            } ) );
        }

        $scores = wp_list_pluck( $results, 'score' );
        $avg    = $scores ? array_sum( $scores ) / count( $scores ) : 0;

        $sanitized = [];
        foreach ( $results as $row ) {
            $metadata = [];
            if ( isset( $row['metadata'] ) && is_array( $row['metadata'] ) ) {
                foreach ( $row['metadata'] as $k => $v ) {
                    if ( is_scalar( $v ) ) {
                        $metadata[ sanitize_key( $k ) ] = sanitize_text_field( (string) $v );
                    }
                }
            }

            $sanitized[] = [
                'type'     => sanitize_key( $row['type'] ?? '' ),
                'ref_id'   => sanitize_text_field( $row['ref_id'] ?? '' ),
                'metadata' => $metadata,
                'score'    => isset( $row['score'] ) ? floatval( $row['score'] ) : 0,
            ];
        }

        global $wpdb;
        $index_info = [
            'last_indexed' => get_option( 'rtbcb_last_indexed', '' ),
            'index_size'   => (int) $wpdb->get_var( 'SELECT COUNT(*) FROM ' . $wpdb->prefix . 'rtbcb_rag_index' ),
        ];

        $response = [
            'query'   => $query,
            'top_k'   => $top_k,
            'results' => $sanitized,
            'metrics' => [
                'retrieval_time' => $elapsed,
                'result_count'   => count( $sanitized ),
                'average_score'  => $avg,
            ],
            'index_info' => $index_info,
        ];

        if ( WP_DEBUG ) {
            $response['debug'] = [
                'embedding_length' => $embedding_length,
                'embedding_preview' => $embedding_preview,
                'scores'            => $scores,
            ];
        }

        wp_send_json_success( $response );
    } catch ( Exception $e ) {
        error_log( 'RTBCB RAG Query Error: ' . $e->getMessage() );
        rtbcb_send_json_error( 'rag_search_failed', __( 'RAG search failed.', 'rtbcb' ), 500, $e->getMessage() );
    }
}

/**
 * Rebuild the RAG index.
 *
 * @return void
 */
function rtbcb_rag_rebuild_index() {
    if ( ! check_ajax_referer( 'rtbcb_unified_test_dashboard', 'nonce', false ) ) {
        rtbcb_send_json_error( 'security_check_failed', __( 'Security check failed.', 'rtbcb' ), 403 );
    }

    if ( ! current_user_can( 'manage_options' ) ) {
        rtbcb_send_json_error( 'insufficient_permissions', __( 'Insufficient permissions.', 'rtbcb' ), 403 );
    }

    if ( ! class_exists( 'RTBCB_RAG' ) ) {
        rtbcb_send_json_error( 'rag_class_missing', __( 'RAG class missing.', 'rtbcb' ), 500 );
    }

    try {
        $rag = new RTBCB_RAG();
        $rag->rebuild_index();

        global $wpdb;
        $index_size = (int) $wpdb->get_var( 'SELECT COUNT(*) FROM ' . $wpdb->prefix . 'rtbcb_rag_index' );

        wp_send_json_success(
            [
                'last_indexed' => get_option( 'rtbcb_last_indexed', '' ),
                'index_size'   => $index_size,
            ]
        );
    } catch ( Exception $e ) {
        error_log( 'RTBCB RAG Rebuild Error: ' . $e->getMessage() );
        rtbcb_send_json_error( 'rag_rebuild_failed', __( 'Failed to rebuild index.', 'rtbcb' ), 500, $e->getMessage() );
    }
}

/**
 * Run all API health tests and return aggregated results.
 *
 * @return void
 */
function rtbcb_run_api_health_tests() {
    $is_debug = defined( 'WP_DEBUG' ) && WP_DEBUG;

    if ( $is_debug ) {
        error_log( '[RTBCB] API Health Test - Entry Point' );
    }

    // Enhanced nonce checking with debug info.
    $nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
    if ( $is_debug ) {
        error_log( '[RTBCB] Nonce received: ' . $nonce );
    }

    if ( ! wp_verify_nonce( $nonce, 'rtbcb_api_health_tests' ) ) {
        if ( $is_debug ) {
            error_log( '[RTBCB] Nonce verification failed' );
        }
        rtbcb_send_json_error( 'security_check_failed', __( 'Security check failed.', 'rtbcb' ), 403 );
        return;
    }

    if ( ! current_user_can( 'manage_options' ) ) {
        if ( $is_debug ) {
            error_log( '[RTBCB] Permission check failed for user: ' . get_current_user_id() );
        }
        rtbcb_send_json_error( 'insufficient_permissions', __( 'Insufficient permissions.', 'rtbcb' ), 403 );
        return;
    }

    if ( $is_debug ) {
        error_log( '[RTBCB] Security checks passed, running tests...' );
    }

    $components = rtbcb_get_api_health_components();

    $results   = [];
    $timestamp = current_time( 'mysql' );

    try {
        foreach ( $components as $key => $component ) {
            if ( $is_debug ) {
                error_log( "[RTBCB] Testing component: {$key}" );
            }
            $start = microtime( true );

            if ( ! is_callable( $component['test'] ) ) {
                if ( $is_debug ) {
                    error_log( "[RTBCB] Test method not callable for {$key}" );
                }
                $results[ $key ] = [
                    'name'          => $component['label'],
                    'passed'        => false,
                    'response_time' => 0,
                    'message'       => 'Test method not available',
                    'details'       => [ 'error' => 'method_not_callable' ],
                    'last_tested'   => $timestamp,
                ];
                continue;
            }

            $test = call_user_func( $component['test'] );
            $end  = microtime( true );

            $results[ $key ] = [
                'name'          => $component['label'],
                'passed'        => (bool) ( $test['success'] ?? false ),
                'response_time' => intval( ( $end - $start ) * 1000 ),
                'message'       => sanitize_text_field( $test['message'] ?? '' ),
                'details'       => $test,
                'last_tested'   => $timestamp,
            ];

            if ( $is_debug ) {
                error_log( '[RTBCB] Component ' . $key . ' result: ' . ( $results[ $key ]['passed'] ? 'PASS' : 'FAIL' ) );
            }
        }
    } catch ( Throwable $e ) {
        if ( $is_debug ) {
            error_log( '[RTBCB] Exception in API health tests: ' . $e->getMessage() );
        }
        rtbcb_send_json_error( 'api_health_tests_failed', __( 'API health tests failed to execute.', 'rtbcb' ), 500, $e->getMessage() );
        return;
    }

    $all_passed = ! array_filter( $results, function( $r ) {
        return empty( $r['passed'] );
    } );

    update_option(
        'rtbcb_last_api_test',
        [
            'timestamp' => $timestamp,
            'results'   => $results,
        ]
    );

    if ( $is_debug ) {
        error_log( '[RTBCB] API Health Tests completed. Overall: ' . ( $all_passed ? 'PASS' : 'FAIL' ) );
    }

    wp_send_json_success(
        [
            'timestamp'      => $timestamp,
            'overall_status' => $all_passed ? 'all_passed' : 'some_failed',
            'results'        => $results,
        ]
    );
}

/**
 * Run data health checks.
 *
 * @return void
 */
function rtbcb_run_data_health_checks() {
    if ( ! check_ajax_referer( 'rtbcb_data_health_checks', 'nonce', false ) ) {
        rtbcb_send_json_error( 'security_check_failed', __( 'Security check failed.', 'rtbcb' ), 403 );
    }

    if ( ! current_user_can( 'manage_options' ) ) {
        rtbcb_send_json_error( 'insufficient_permissions', __( 'Insufficient permissions.', 'rtbcb' ), 403 );
    }

    global $wpdb;

    $results = [];

    // Database check.
    $db_ok = $wpdb->get_var( 'SELECT 1' );
    $results['database'] = [
        'label'   => __( 'Database', 'rtbcb' ),
        'passed'  => ( null !== $db_ok ),
        'message' => ( null !== $db_ok ) ? __( 'Database connection is healthy.', 'rtbcb' ) : __( 'Database query failed.', 'rtbcb' ),
    ];

    // API connectivity check.
    $api_key = get_option( 'rtbcb_openai_api_key', '' );
    if ( empty( $api_key ) ) {
        $results['api'] = [
            'label'   => __( 'API Connectivity', 'rtbcb' ),
            'passed'  => false,
            'message' => __( 'OpenAI API key not configured.', 'rtbcb' ),
        ];
    } else {
        $response = wp_remote_get(
            'https://api.openai.com/v1/models',
            [
                'headers' => [ 'Authorization' => 'Bearer ' . $api_key ],
                'timeout' => 60,
            ]
        );

        if ( is_wp_error( $response ) ) {
            $results['api'] = [
                'label'   => __( 'API Connectivity', 'rtbcb' ),
                'passed'  => false,
                'message' => sanitize_text_field( $response->get_error_message() ),
            ];
        } else {
            $code            = wp_remote_retrieve_response_code( $response );
            $results['api'] = [
                'label'   => __( 'API Connectivity', 'rtbcb' ),
                'passed'  => ( 200 === $code ),
                'message' => ( 200 === $code ) ? __( 'API reachable.', 'rtbcb' ) : sprintf( __( 'Unexpected response code: %d', 'rtbcb' ), $code ),
            ];
        }
    }

    // File permission check.
    $upload_dir = wp_upload_dir();
    $writable   = is_writable( $upload_dir['basedir'] );
    $results['files'] = [
        'label'   => __( 'File Permissions', 'rtbcb' ),
        'passed'  => $writable,
        'message' => $writable ? __( 'Upload directory is writable.', 'rtbcb' ) : __( 'Upload directory is not writable.', 'rtbcb' ),
    ];

    wp_send_json_success( $results );
}

/**
 * Run a single API health test.
 *
 * @return void
 */
function rtbcb_run_single_api_test() {
    if ( ! check_ajax_referer( 'rtbcb_api_health_tests', 'nonce', false ) ) {
        rtbcb_send_json_error( 'security_check_failed', __( 'Security check failed.', 'rtbcb' ), 403 );
    }

    if ( ! current_user_can( 'manage_options' ) ) {
        rtbcb_send_json_error( 'insufficient_permissions', __( 'Insufficient permissions.', 'rtbcb' ), 403 );
    }

    $component = isset( $_POST['component'] ) ? sanitize_key( wp_unslash( $_POST['component'] ) ) : '';

    $components = rtbcb_get_api_health_components();

    if ( empty( $component ) || ! isset( $components[ $component ] ) ) {
        rtbcb_send_json_error( 'invalid_component', __( 'Invalid component.', 'rtbcb' ) );
    }

    $label    = sanitize_text_field( $components[ $component ]['label'] );
    $callback = $components[ $component ]['test'];

    $start_time = microtime( true );
    $test       = call_user_func( $callback );
    $end_time   = microtime( true );

    $result = [
        'component'     => $component,
        'name'          => $label,
        'passed'        => (bool) ( $test['success'] ?? false ),
        'response_time' => (int) ( ( $end_time - $start_time ) * 1000 ),
        'message'       => sanitize_text_field( $test['message'] ?? '' ),
        'details'       => $test,
    ];

    $timestamp = current_time( 'mysql' );
    $stored    = get_option( 'rtbcb_last_api_test', [ 'timestamp' => '', 'results' => [] ] );
    $stored['timestamp']           = $timestamp;
    $stored['results'][ $component ] = $result;
    update_option( 'rtbcb_last_api_test', $stored );

    wp_send_json_success(
        [
            'timestamp' => sanitize_text_field( $timestamp ),
            'result'    => $result,
        ]
    );
}

/**
 * Generate full HTML preview report.
 *
 * @return void
 */
function rtbcb_generate_preview_report() {
    if ( ! check_ajax_referer( 'rtbcb_generate_preview_report', 'nonce', false ) ) {
        rtbcb_send_json_error( 'security_check_failed', __( 'Security check failed.', 'rtbcb' ), 403 );
    }

    if ( ! current_user_can( 'manage_options' ) ) {
        rtbcb_send_json_error( 'insufficient_permissions', __( 'Insufficient permissions.', 'rtbcb' ), 403 );
    }

    $company = rtbcb_get_current_company();
    if ( empty( $company ) ) {
        rtbcb_send_json_error( 'no_company_data', __( 'No company data found. Please run the company overview first.', 'rtbcb' ) );
    }

    $context = [
        'company'    => $company,
        'api_health' => get_option( 'rtbcb_last_api_test', [] ),
    ];

    $template_path = RTBCB_DIR . 'templates/comprehensive-report-template.php';
    if ( ! file_exists( $template_path ) ) {
        $template_path = RTBCB_DIR . 'templates/report-template.php';
    }

    if ( ! file_exists( $template_path ) ) {
        rtbcb_send_json_error( 'report_template_missing', __( 'Report template not found.', 'rtbcb' ), 500 );
    }

    $business_case_data = $context;

    ob_start();
    include $template_path;
    $html = ob_get_clean();

    $allowed_tags          = wp_kses_allowed_html( 'post' );
    $allowed_tags['style'] = [];

    $html = wp_kses( $html, $allowed_tags );

    wp_send_json_success( [ 'html' => $html ] );
}

/**
 * Save dashboard settings.
 *
 * @return void
 */
function rtbcb_save_dashboard_settings() {
    error_log( 'rtbcb_save_dashboard_settings request: ' . wp_json_encode( array_map( 'sanitize_text_field', wp_unslash( $_POST ) ) ) );

    if ( ! check_ajax_referer( 'rtbcb_save_dashboard_settings', 'nonce', false ) ) {
        error_log( 'rtbcb_save_dashboard_settings: nonce verification failed' );
        rtbcb_send_json_error( 'invalid_nonce', __( 'Security check failed.', 'rtbcb' ), 403 );
    }

    if ( ! current_user_can( 'manage_options' ) ) {
        error_log( 'rtbcb_save_dashboard_settings: insufficient permissions for user ' . get_current_user_id() );
        rtbcb_send_json_error( 'insufficient_permissions', __( 'Insufficient permissions.', 'rtbcb' ), 403 );
    }

    $openai_key = isset( $_POST['rtbcb_openai_api_key'] ) ? sanitize_text_field( wp_unslash( $_POST['rtbcb_openai_api_key'] ) ) : '';

    if ( $openai_key && ! rtbcb_is_valid_openai_api_key( $openai_key ) ) {
        error_log( 'rtbcb_save_dashboard_settings: invalid API key format' );
        rtbcb_send_json_error( 'invalid_api_key', __( 'Invalid OpenAI API key format.', 'rtbcb' ) );
    }

    update_option( 'rtbcb_openai_api_key', $openai_key );

    $fields = [
        'rtbcb_mini_model'      => 'sanitize_text_field',
        'rtbcb_premium_model'   => 'sanitize_text_field',
        'rtbcb_advanced_model'  => 'sanitize_text_field',
        'rtbcb_embedding_model' => 'sanitize_text_field',
        'rtbcb_cb_threshold'    => 'absint',
        'rtbcb_cb_reset_time'   => 'absint',
    ];

    foreach ( $fields as $option => $sanitize ) {
        $value = isset( $_POST[ $option ] ) ? call_user_func( $sanitize, wp_unslash( $_POST[ $option ] ) ) : '';
        update_option( $option, $value );
    }

    $api_valid = false;
    if ( $openai_key ) {
        $test      = RTBCB_API_Tester::test_connection( $openai_key );
        $api_valid = ! empty( $test['success'] );
    }

    error_log( 'rtbcb_save_dashboard_settings: settings saved' );

    wp_send_json_success(
        [
            'message'   => __( 'Settings saved.', 'rtbcb' ),
            'api_valid' => $api_valid,
        ]
    );
}

/**
 * Run LLM integration test.
 *
 * @return void
 */
function rtbcb_ajax_run_llm_test() {
    error_log( 'AJAX handler called: rtbcb_ajax_run_llm_test' );
    error_log( 'Request data: ' . print_r( $_POST, true ) );
    if ( ! check_ajax_referer( 'rtbcb_llm_testing', 'nonce', false ) ) {
        rtbcb_send_json_error(
            'security_check_failed',
            __( 'Security check failed.', 'rtbcb' ),
            403,
            [ 'request' => rtbcb_sanitize_form_data( wp_unslash( $_POST ) ) ]
        );
    }

    if ( ! current_user_can( 'manage_options' ) ) {
        rtbcb_send_json_error(
            'insufficient_permissions',
            __( 'Insufficient permissions.', 'rtbcb' ),
            403,
            [ 'request' => rtbcb_sanitize_form_data( wp_unslash( $_POST ) ) ]
        );
    }

    $input_data = [
        'modelIds'   => array_map( 'sanitize_text_field', wp_unslash( $_POST['modelIds'] ?? [] ) ),
        'promptA'    => sanitize_textarea_field( wp_unslash( $_POST['promptA'] ?? '' ) ),
        'promptB'    => sanitize_textarea_field( wp_unslash( $_POST['promptB'] ?? '' ) ),
        'runMode'    => sanitize_text_field( wp_unslash( $_POST['runMode'] ?? 'single' ) ),
        'maxTokens'  => intval( wp_unslash( $_POST['maxTokens'] ?? 1000 ) ),
        'temperature'=> floatval( wp_unslash( $_POST['temperature'] ?? 0.3 ) ),
    ];

    if ( empty( $input_data['modelIds'] ) ) {
        rtbcb_send_json_error(
            'no_models_selected',
            __( 'No models selected.', 'rtbcb' ),
            400,
            [ 'request' => $input_data ]
        );
    }

    if ( empty( $input_data['promptA'] ) ) {
        rtbcb_send_json_error(
            'prompt_a_required',
            __( 'Prompt A is required.', 'rtbcb' ),
            400,
            [ 'request' => $input_data ]
        );
    }

    try {
        $results    = [];
        $start_time = microtime( true );

        foreach ( $input_data['modelIds'] as $model_key ) {
            $model_start = microtime( true );

            $result_a = rtbcb_test_single_model( $model_key, $input_data['promptA'], $input_data );
            $model_end = microtime( true );

            $results[] = [
                'model_key'     => $model_key,
                'model_name'    => rtbcb_get_model_display_name( $model_key ),
                'prompt'        => 'A',
                'response'      => $result_a['content'],
                'latency'       => round( ( $model_end - $model_start ) * 1000, 2 ),
                'tokens_used'   => $result_a['tokens_used'],
                'cost_estimate' => rtbcb_calculate_model_cost( $result_a['tokens_used'], $model_key ),
                'quality_score' => rtbcb_assess_response_quality( $result_a['content'] ),
            ];

            if ( ! empty( $input_data['promptB'] ) ) {
                $model_start = microtime( true );
                $result_b    = rtbcb_test_single_model( $model_key, $input_data['promptB'], $input_data );
                $model_end   = microtime( true );

                $results[] = [
                    'model_key'     => $model_key,
                    'model_name'    => rtbcb_get_model_display_name( $model_key ),
                    'prompt'        => 'B',
                    'response'      => $result_b['content'],
                    'latency'       => round( ( $model_end - $model_start ) * 1000, 2 ),
                    'tokens_used'   => $result_b['tokens_used'],
                    'cost_estimate' => rtbcb_calculate_model_cost( $result_b['tokens_used'], $model_key ),
                    'quality_score' => rtbcb_assess_response_quality( $result_b['content'] ),
                ];
            }
        }

        $total_time = round( ( microtime( true ) - $start_time ), 2 );

        $summary = [
            'total_tests' => count( $results ),
            'total_time'  => $total_time,
            'avg_latency' => array_sum( array_column( $results, 'latency' ) ) / max( count( $results ), 1 ),
            'total_tokens'=> array_sum( array_column( $results, 'tokens_used' ) ),
            'total_cost'  => array_sum( array_column( $results, 'cost_estimate' ) ),
            'best_performer' => rtbcb_find_best_performing_result( $results ),
        ];

        wp_send_json_success( [
            'results'   => $results,
            'summary'   => $summary,
            'timestamp' => current_time( 'mysql' ),
        ] );

    } catch ( Exception $e ) {
        error_log( 'RTBCB LLM Test Error: ' . $e->getMessage() . ' Data: ' . print_r( $input_data, true ) );
        rtbcb_send_json_error(
            'llm_test_failed',
            __( 'LLM test execution failed.', 'rtbcb' ),
            500,
            [
                'error'   => $e->getMessage(),
                'request' => $input_data,
            ]
        );
    }
}

/**
 * Test a single language model with a given prompt.
 *
 * @param string $model_key Model identifier.
 * @param string $prompt    Prompt content to send.
 * @param array  $config    Configuration options.
 *
 * @return array {
 *     @type string $content     Generated response text.
 *     @type int    $tokens_used Number of tokens consumed.
 *     @type string $model_used  Name of the model that handled the request.
 * }
 *
 * @throws Exception If the API request fails or returns an invalid response.
 */
function rtbcb_test_single_model( $model_key, $prompt, $config ) {
    $llm = new RTBCB_LLM();

    $model_map = [
        'mini'     => get_option( 'rtbcb_mini_model', 'gpt-4o-mini' ),
        'premium'  => get_option( 'rtbcb_premium_model', 'gpt-4o' ),
        'advanced' => get_option( 'rtbcb_advanced_model', 'o1-preview' ),
    ];

    $model_name = $model_map[ $model_key ] ?? 'gpt-4o-mini';

    $response = wp_remote_post( 'https://api.openai.com/v1/chat/completions', [
        'headers' => [
            'Authorization' => 'Bearer ' . get_option( 'rtbcb_openai_api_key' ),
            'Content-Type'  => 'application/json',
        ],
        'body'    => wp_json_encode( [
            'model'      => $model_name,
            'messages'   => [ [ 'role' => 'user', 'content' => $prompt ] ],
            'max_tokens' => $config['maxTokens'],
            'temperature'=> rtbcb_model_supports_temperature( $model_name ) ? $config['temperature'] : null,
        ] ),
        'timeout' => 60,
    ] );

    if ( is_wp_error( $response ) ) {
        throw new Exception( $response->get_error_message() );
    }

    $body = json_decode( wp_remote_retrieve_body( $response ), true );

    if ( ! isset( $body['choices'][0]['message']['content'] ) ) {
        throw new Exception( 'Invalid API response structure' );
    }

    return [
        'content'     => $body['choices'][0]['message']['content'],
        'tokens_used' => $body['usage']['total_tokens'] ?? 0,
        'model_used'  => $body['model'] ?? $model_name,
    ];
}

/**
 * Interface for vector database implementations.
 */
interface RTBCB_Vector_DB {
    /**
     * Search the vector database.
     *
     * @param array $embedding Embedding vector.
     * @param int   $top_k     Number of results to return.
     *
     * @return array
     */
    public function search( $embedding, $top_k = 5 );

    /**
     * Get status information about the index.
     *
     * @return array
     */
    public function get_index_status();

    /**
     * Rebuild the index with the provided documents.
     *
     * @param array $documents Documents to index.
     *
     * @return array
     */
    public function rebuild_index( $documents );
}

/**
 * Mock vector database used when no real vector DB is available.
 */
class RTBCB_Mock_Vector_DB implements RTBCB_Vector_DB {
    /**
     * {@inheritDoc}
     */
    public function search( $embedding, $top_k = 5 ) {
        return array_fill(
            0,
            min( $top_k, 3 ),
            [
                'id'       => 'mock_' . uniqid(),
                'score'    => 0.85 + ( mt_rand() / mt_getrandmax() ) * 0.1,
                'metadata' => [
                    'type'    => 'mock_document',
                    'title'   => 'Sample Document',
                    'content' => 'This is mock content for testing purposes.',
                ],
            ]
        );
    }

    /**
     * {@inheritDoc}
     */
    public function get_index_status() {
        return [
            'indexed_count' => 25,
            'last_updated'  => current_time( 'mysql' ),
            'status'        => 'healthy',
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function rebuild_index( $documents ) {
        sleep( 2 );

        return [
            'indexed' => count( $documents ),
            'time'    => '2.1s',
        ];
    }
}

/**
 * Run RAG system test.
 *
 * @return void
 */
function rtbcb_ajax_run_rag_test() {
    error_log( 'AJAX handler called: rtbcb_ajax_run_rag_test' );
    error_log( 'Request data: ' . print_r( $_POST, true ) );
    // Security and permission checks.
    if ( ! check_ajax_referer( 'rtbcb_rag_testing', 'nonce', false ) ) {
        rtbcb_send_json_error(
            'security_check_failed',
            __( 'Security check failed.', 'rtbcb' ),
            403,
            [ 'request' => rtbcb_sanitize_form_data( wp_unslash( $_POST ) ) ]
        );
    }

    if ( ! current_user_can( 'manage_options' ) ) {
        rtbcb_send_json_error(
            'insufficient_permissions',
            __( 'Insufficient permissions.', 'rtbcb' ),
            403,
            [ 'request' => rtbcb_sanitize_form_data( wp_unslash( $_POST ) ) ]
        );
    }

    // Input handling.
    $queries         = array_map( 'sanitize_text_field', wp_unslash( $_POST['queries'] ?? [] ) );
    $top_k           = intval( wp_unslash( $_POST['topK'] ?? 5 ) );
    $evaluation_mode = sanitize_text_field( wp_unslash( $_POST['evaluationMode'] ?? 'similarity' ) );

    if ( empty( $queries ) ) {
        $debug_request = [
            'queries'         => $queries,
            'top_k'           => $top_k,
            'evaluation_mode' => $evaluation_mode,
        ];
        rtbcb_send_json_error(
            'test_queries_required',
            __( 'Test queries required.', 'rtbcb' ),
            400,
            [ 'request' => $debug_request ]
        );
    }

    try {
        // Initialize RAG system.
        $rag       = class_exists( 'RTBCB_RAG' ) ? new RTBCB_RAG() : null;
        $vector_db = ( $rag && method_exists( $rag, 'get_vector_db' ) ) ? $rag->get_vector_db() : new RTBCB_Mock_Vector_DB();

        $results = [];

        foreach ( $queries as $query ) {
            $start_time = microtime( true );

            // Execute retrieval.
            $embedding      = ( $rag && method_exists( $rag, 'get_embedding' ) ) ? $rag->get_embedding( $query ) : [ 0.1, 0.2, 0.3 ];
            $retrieved_docs = $vector_db->search( $embedding, $top_k );

            $retrieval_time = ( microtime( true ) - $start_time ) * 1000;

            // Calculate metrics.
            $metrics = [
                'ndcg_at_k'      => rtbcb_calculate_ndcg( $retrieved_docs, $top_k ),
                'recall_at_k'    => rtbcb_calculate_recall( $retrieved_docs, $query, $top_k ),
                'precision_at_k' => rtbcb_calculate_precision( $retrieved_docs, $query, $top_k ),
                'avg_score'      => array_sum( array_column( $retrieved_docs, 'score' ) ) / count( $retrieved_docs ),
            ];

            $results[] = [
                'query'          => $query,
                'retrieved_docs' => $retrieved_docs,
                'metrics'        => array_merge(
                    $metrics,
                    [
                        'retrieval_time_ms' => round( $retrieval_time, 2 ),
                        'docs_retrieved'    => count( $retrieved_docs ),
                    ]
                ),
            ];
        }

        // Generate overall performance summary.
        $summary = [
            'avg_ndcg'        => array_sum( array_column( array_column( $results, 'metrics' ), 'ndcg_at_k' ) ) / count( $results ),
            'avg_recall'      => array_sum( array_column( array_column( $results, 'metrics' ), 'recall_at_k' ) ) / count( $results ),
            'avg_precision'   => array_sum( array_column( array_column( $results, 'metrics' ), 'precision_at_k' ) ) / count( $results ),
            'avg_retrieval_time' => array_sum( array_column( array_column( $results, 'metrics' ), 'retrieval_time_ms' ) ) / count( $results ),
            'index_status'    => $vector_db->get_index_status(),
        ];

        wp_send_json_success(
            [
                'results'        => $results,
                'summary'        => $summary,
                'evaluation_mode' => $evaluation_mode,
                'timestamp'      => current_time( 'mysql' ),
            ]
        );
    } catch ( Exception $e ) {
        $request_data = [
            'queries'         => $queries,
            'top_k'           => $top_k,
            'evaluation_mode' => $evaluation_mode,
        ];
        error_log( 'RTBCB RAG Test Error: ' . $e->getMessage() . ' Data: ' . print_r( $request_data, true ) );
        rtbcb_send_json_error(
            'rag_test_failed',
            __( 'RAG test execution failed.', 'rtbcb' ),
            500,
            [
                'error'   => $e->getMessage(),
                'request' => $request_data,
            ]
        );
    }
}

/**
 * Calculate nDCG@k metric.
 *
 * @param array $retrieved_docs Retrieved documents.
 * @param int   $k              Cutoff rank.
 *
 * @return float
 */
function rtbcb_calculate_ndcg( $retrieved_docs, $k ) {
    $dcg       = 0;
    $ideal_dcg = 0;

    for ( $i = 0; $i < min( $k, count( $retrieved_docs ) ); $i++ ) {
        $relevance = $retrieved_docs[ $i ]['score'] ?? 0;
        $position  = $i + 1;

        // DCG formula: relevance / log2(position + 1).
        $dcg += $relevance / log( $position + 1, 2 );

        // For ideal DCG, assume perfect ranking.
        $ideal_relevance = 1.0;
        $ideal_dcg      += $ideal_relevance / log( $position + 1, 2 );
    }

    return $ideal_dcg > 0 ? $dcg / $ideal_dcg : 0;
}

/**
 * Mock recall calculation.
 *
 * @param array  $retrieved_docs Retrieved documents.
 * @param string $query          Query string.
 * @param int    $k              Cutoff rank.
 *
 * @return float
 */
function rtbcb_calculate_recall( $retrieved_docs, $query, $k ) {
    $relevant_retrieved = 0;
    $total_relevant     = 5;

    foreach ( $retrieved_docs as $doc ) {
        if ( isset( $doc['score'] ) && $doc['score'] > 0.7 ) {
            $relevant_retrieved++;
        }
    }

    return $total_relevant > 0 ? $relevant_retrieved / $total_relevant : 0;
}

/**
 * Mock precision calculation.
 *
 * @param array  $retrieved_docs Retrieved documents.
 * @param string $query          Query string.
 * @param int    $k              Cutoff rank.
 *
 * @return float
 */
function rtbcb_calculate_precision( $retrieved_docs, $query, $k ) {
    $relevant_retrieved = 0;

    foreach ( $retrieved_docs as $doc ) {
        if ( isset( $doc['score'] ) && $doc['score'] > 0.7 ) {
            $relevant_retrieved++;
        }
    }

    return count( $retrieved_docs ) > 0 ? $relevant_retrieved / count( $retrieved_docs ) : 0;
}

/**
 * Audit context window usage and apply truncation if needed.
 *
 * @param array $context_chunks Context chunks to audit.
 *
 * @return array
 */
function rtbcb_audit_context_window( $context_chunks ) {
    $total_tokens   = 0;
    $chunk_analysis = [];

    foreach ( $context_chunks as $i => $chunk ) {
        $estimated_tokens = ceil( strlen( $chunk ) / 4 );
        $total_tokens    += $estimated_tokens;

        $chunk_analysis[] = [
            'chunk_id'        => $i,
            'char_count'      => strlen( $chunk ),
            'estimated_tokens'=> $estimated_tokens,
            'truncated'       => false,
        ];
    }

    $max_context_tokens = 8000;
    if ( $total_tokens > $max_context_tokens ) {
        $tokens_to_remove = $total_tokens - $max_context_tokens;

        for ( $i = count( $chunk_analysis ) - 1; $i >= 0 && $tokens_to_remove > 0; $i-- ) {
            $chunk_tokens = $chunk_analysis[ $i ]['estimated_tokens'];
            if ( $tokens_to_remove >= $chunk_tokens ) {
                $chunk_analysis[ $i ]['truncated'] = true;
                $tokens_to_remove -= $chunk_tokens;
                $total_tokens     -= $chunk_tokens;
            }
        }
    }

    return [
        'total_estimated_tokens' => $total_tokens,
        'chunks_analyzed'        => count( $chunk_analysis ),
        'chunks_truncated'       => count( array_filter( $chunk_analysis, fn( $c ) => $c['truncated'] ) ),
        'chunk_details'          => $chunk_analysis,
        'within_limit'           => $total_tokens <= $max_context_tokens,
    ];
}

/**
 * Run API health ping.
 *
 * @return void
 */
function rtbcb_ajax_api_health_ping() {
    error_log( 'AJAX handler called: rtbcb_ajax_api_health_ping' );
    error_log( 'Request data: ' . print_r( $_POST, true ) );

    if ( ! check_ajax_referer( 'rtbcb_api_health_tests', 'nonce', false ) ) {
        rtbcb_send_json_error( 'security_failed', __( 'Security check failed.', 'rtbcb' ), 403, '', [ 'requestId' => uniqid( 'req_' ) ] );
    }

    if ( ! current_user_can( 'manage_options' ) ) {
        rtbcb_send_json_error( 'insufficient_permissions', __( 'Insufficient permissions.', 'rtbcb' ), 403, '', [ 'requestId' => uniqid( 'req_' ) ] );
    }

    $input_data = [];

    try {
        $ping_result = rtbcb_execute_openai_health_ping();

        if ( $ping_result['success'] ) {
            update_option( 'rtbcb_openai_last_ok', time() );
            delete_transient( 'rtbcb_openai_error' );
        } else {
            update_option( 'rtbcb_openai_last_error_at', time() );
            set_transient(
                'rtbcb_openai_error',
                [
                    'code'       => $ping_result['code'] ?? 'unknown',
                    'httpStatus' => $ping_result['httpStatus'] ?? 0,
                    'body'       => substr( $ping_result['body'] ?? '', 0, 200 ),
                    'timestamp'  => time(),
                ],
                600
            );
        }

        error_log( 'rtbcb_ajax_api_health_ping result: ' . print_r( $ping_result, true ) );

        wp_send_json_success(
            [
                'data'      => $ping_result,
                'timestamp' => current_time( 'mysql' ),
                'requestId' => uniqid( 'req_' ),
            ]
        );
    } catch ( Exception $e ) {
        error_log( 'RTBCB API Health Ping Error: ' . $e->getMessage() );
        rtbcb_send_json_error( 'execution_failed', __( 'Health ping failed.', 'rtbcb' ), 500, $e->getMessage(), [ 'requestId' => uniqid( 'req_' ), 'retryAfter' => 30 ] );
    }
}

/**
 * Execute OpenAI health ping with minimal token usage
 */
function rtbcb_execute_openai_health_ping() {
    $api_key = get_option( 'rtbcb_openai_api_key' );

    if ( empty( $api_key ) ) {
        return [
            'success' => false,
            'code'    => 'no_api_key',
            'message' => __( 'API key not configured', 'rtbcb' ),
        ];
    }

    // Use models endpoint for fast, free check
    $response = wp_remote_get(
        'https://api.openai.com/v1/models',
        [
            'headers' => [
                'Authorization' => 'Bearer ' . $api_key,
            ],
            'timeout' => 60,
        ]
    );

    if ( is_wp_error( $response ) ) {
        return [
            'success' => false,
            'code'    => 'connection_failed',
            'message' => $response->get_error_message(),
        ];
    }

    $status_code   = wp_remote_retrieve_response_code( $response );
    $headers       = wp_remote_retrieve_headers( $response );
    if ( function_exists( 'rtbcb_track_api_rate_limits' ) ) {
        rtbcb_track_api_rate_limits( $headers );
    }
    $response_body = wp_remote_retrieve_body( $response );

    switch ( $status_code ) {
        case 200:
            return [
                'success'    => true,
                'message'    => __( 'API connection healthy', 'rtbcb' ),
                'httpStatus' => $status_code,
            ];

        case 401:
        case 403:
            return [
                'success'    => false,
                'code'       => 'auth_failed',
                'message'    => __( 'Invalid API key or insufficient permissions', 'rtbcb' ),
                'httpStatus' => $status_code,
            ];

        case 429:
            return [
                'success'    => false,
                'code'       => 'rate_limited',
                'message'    => __( 'Rate limit exceeded', 'rtbcb' ),
                'httpStatus' => $status_code,
                'retryAfter' => 60,
            ];

        default:
            return [
                'success'    => false,
                'code'       => 'api_error',
                'message'    => sprintf( __( 'API returned status %d', 'rtbcb' ), $status_code ),
                'httpStatus' => $status_code,
                'body'       => substr( $response_body, 0, 200 ),
            ];
    }
}

/**
 * Export test results.
 *
 * @return void
 */
function rtbcb_ajax_export_results() {
    if ( ! check_ajax_referer( 'rtbcb_unified_test_dashboard', 'nonce', false ) ) {
        rtbcb_send_json_error( 'security_failed', __( 'Security check failed.', 'rtbcb' ), 403, '', [ 'requestId' => uniqid( 'req_' ) ] );
    }

    if ( ! current_user_can( 'manage_options' ) ) {
        rtbcb_send_json_error( 'insufficient_permissions', __( 'Insufficient permissions.', 'rtbcb' ), 403, '', [ 'requestId' => uniqid( 'req_' ) ] );
    }

    $input_data = [
        'exportType' => sanitize_text_field( wp_unslash( $_POST['exportType'] ?? 'json' ) ),
        'testData'   => isset( $_POST['testData'] ) ? wp_unslash( $_POST['testData'] ) : [],
    ];

    if ( empty( $input_data['testData'] ) ) {
        rtbcb_send_json_error( 'no_data', __( 'No test data to export.', 'rtbcb' ), 400, '', [ 'requestId' => uniqid( 'req_' ) ] );
    }

    try {
        $export_data = [
            'metadata' => [
                'exportTime'    => current_time( 'c' ),
                'plugin'        => 'Real Treasury Business Case Builder',
                'version'       => defined( 'RTBCB_VERSION' ) ? RTBCB_VERSION : '2.0.0',
                'testDashboard' => 'unified-test-dashboard',
            ],
            'results'  => $input_data['testData'],
        ];

        if ( 'csv' === $input_data['exportType'] ) {
            $csv_content = rtbcb_convert_results_to_csv( $export_data );
            $result      = [
                'content'     => $csv_content,
                'filename'    => 'rtbcb-test-results-' . date( 'Y-m-d-H-i-s' ) . '.csv',
                'contentType' => 'text/csv',
            ];
        } else {
            $result = [
                'content'     => wp_json_encode( $export_data, JSON_PRETTY_PRINT ),
                'filename'    => 'rtbcb-test-results-' . date( 'Y-m-d-H-i-s' ) . '.json',
                'contentType' => 'application/json',
            ];
        }

        wp_send_json_success(
            [
                'data'      => $result,
                'timestamp' => current_time( 'mysql' ),
                'requestId' => uniqid( 'req_' ),
            ]
        );
    } catch ( Exception $e ) {
        error_log( 'RTBCB Export Results Error: ' . $e->getMessage() );
        rtbcb_send_json_error( 'execution_failed', __( 'Export generation failed.', 'rtbcb' ), 500, $e->getMessage(), [ 'requestId' => uniqid( 'req_' ) ] );
    }
}

/**
 * Convert export results to CSV.
 *
 * @param array $export_data Export data.
 * @return string CSV content.
 */
function rtbcb_convert_results_to_csv( $export_data ) {
    $output = fopen( 'php://temp', 'r+' );

    if ( isset( $export_data['results'] ) && is_array( $export_data['results'] ) ) {
        $results = $export_data['results'];
        $first   = reset( $results );
        if ( is_array( $first ) ) {
            fputcsv( $output, array_keys( $first ) );
            foreach ( $results as $row ) {
                fputcsv( $output, is_array( $row ) ? $row : [ $row ] );
            }
        }
    }

    rewind( $output );
    $csv = stream_get_contents( $output );
    fclose( $output );

    return $csv;
}


/**
 * Export dashboard test results.
 *
 * @return void
 */
function rtbcb_export_dashboard_results() {
    if ( ! check_ajax_referer( 'rtbcb_unified_test_dashboard', 'nonce', false ) ) {
        rtbcb_send_json_error( 'security_check_failed', __( 'Security check failed.', 'rtbcb' ), 403 );
    }

    if ( ! current_user_can( 'manage_options' ) ) {
        rtbcb_send_json_error( 'insufficient_permissions', __( 'Insufficient permissions.', 'rtbcb' ), 403 );
    }

    $export_format = sanitize_text_field( wp_unslash( $_POST['format'] ?? 'json' ) );
    $date_range    = sanitize_text_field( wp_unslash( $_POST['date_range'] ?? '7_days' ) );
    $modules       = array_map( 'sanitize_text_field', wp_unslash( $_POST['modules'] ?? [] ) );

    try {
        global $wpdb;

        // Build date filter
        $date_filter = '';
        switch ( $date_range ) {
            case '24_hours':
                $date_filter = 'AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)';
                break;
            case '7_days':
                $date_filter = 'AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)';
                break;
            case '30_days':
                $date_filter = 'AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)';
                break;
        }

        $results = [
            'company_overview' => get_option( 'rtbcb_recent_company_tests', [] ),
            'llm_tests'        => get_option( 'rtbcb_recent_llm_tests', [] ),
            'rag_tests'        => get_option( 'rtbcb_recent_rag_tests', [] ),
            'api_health'       => get_option( 'rtbcb_last_api_test', [] ),
        ];

        if ( ! empty( $modules ) ) {
            $results = array_intersect_key( $results, array_flip( $modules ) );
        }

        $export_data = [
            'metadata' => [
                'export_time'     => current_time( 'c' ),
                'plugin_version'  => defined( 'RTBCB_VERSION' ) ? RTBCB_VERSION : '2.0.0',
                'export_format'   => $export_format,
                'date_range'      => $date_range,
                'modules_included' => array_keys( $results ),
            ],
            'results'  => $results,
            'summary'  => rtbcb_generate_export_summary( $results ),
        ];

        if ( 'csv' === $export_format ) {
            $csv_content = rtbcb_convert_results_to_csv( $export_data );

            header( 'Content-Type: text/csv' );
            header( 'Content-Disposition: attachment; filename="rtbcb-dashboard-export-' . date( 'Y-m-d-H-i-s' ) . '.csv"' );

            wp_send_json_success(
                [
                    'content'      => $csv_content,
                    'filename'     => 'rtbcb-dashboard-export-' . date( 'Y-m-d-H-i-s' ) . '.csv',
                    'content_type' => 'text/csv',
                ]
            );
        } else {
            wp_send_json_success(
                [
                    'content'      => wp_json_encode( $export_data, JSON_PRETTY_PRINT ),
                    'filename'     => 'rtbcb-dashboard-export-' . date( 'Y-m-d-H-i-s' ) . '.json',
                    'content_type' => 'application/json',
                ]
            );
        }
    } catch ( Exception $e ) {
        error_log( 'RTBCB Export Error: ' . $e->getMessage() );
        rtbcb_send_json_error( 'export_failed', __( 'Export generation failed.', 'rtbcb' ), 500, $e->getMessage() );
    }
}

/**
 * Generate professional report via OpenAI using stored API key.
 *
 * @return void
 */
function rtbcb_generate_report() {
    if ( ! check_ajax_referer( 'rtbcb_generate_report', 'nonce', false ) ) {
        rtbcb_send_json_error( 'security_check_failed', __( 'Security check failed.', 'rtbcb' ), 403 );
    }

    if ( ! current_user_can( 'manage_options' ) ) {
        rtbcb_send_json_error( 'insufficient_permissions', __( 'Insufficient permissions.', 'rtbcb' ), 403 );
    }

    $request_raw = wp_unslash( $_POST['request'] ?? '' );
    if ( empty( $request_raw ) ) {
        rtbcb_send_json_error( 'invalid_request', __( 'Invalid request.', 'rtbcb' ), 400 );
    }

    $request = json_decode( $request_raw, true );
    if ( ! is_array( $request ) ) {
        rtbcb_send_json_error( 'invalid_request', __( 'Invalid request.', 'rtbcb' ), 400 );
    }

    $api_key = sanitize_text_field( get_option( 'rtbcb_openai_api_key', '' ) );
    if ( empty( $api_key ) ) {
        rtbcb_send_json_error( 'api_key_not_configured', __( 'OpenAI API key not configured.', 'rtbcb' ), 500 );
    }

    $args = [
        'timeout' => 60,
        'headers' => [
            'Content-Type'  => 'application/json',
            'Authorization' => 'Bearer ' . $api_key,
        ],
        'body'    => wp_json_encode( $request ),
    ];

    $response = wp_remote_post( 'https://api.openai.com/v1/responses', $args );

    if ( is_wp_error( $response ) ) {
        rtbcb_send_json_error( 'http_request_failed', __( 'HTTP request failed.', 'rtbcb' ), 500, $response->get_error_message() );
    }

    $code    = wp_remote_retrieve_response_code( $response );
    $body    = wp_remote_retrieve_body( $response );
    $decoded = json_decode( $body, true );

    if ( 200 !== $code || ! is_array( $decoded ) ) {
        rtbcb_send_json_error( 'api_error', __( 'API request failed.', 'rtbcb' ), $code, $body );
    }

    if ( isset( $decoded['error'] ) ) {
        $message = is_array( $decoded['error'] ) ? sanitize_text_field( $decoded['error']['message'] ?? '' ) : '';
        rtbcb_send_json_error( 'api_error', $message ? $message : __( 'API request failed.', 'rtbcb' ), $code, $body );
    }

    $html = wp_kses_post( $decoded['output_text'] ?? '' );
    wp_send_json_success( [ 'html' => $html ] );
}

/**
 * Generate export summary statistics.
 *
 * @param array $results Results data.
 * @return array Summary data.
 */
function rtbcb_generate_export_summary( $results ) {
    $summary = [
        'total_modules'   => count( $results ),
        'total_tests'     => 0,
        'generation_time' => current_time( 'mysql' ),
        'module_summaries' => [],
    ];

    foreach ( $results as $module => $data ) {
        if ( is_array( $data ) ) {
            $test_count = isset( $data['results'] ) ? count( $data['results'] ) : count( $data );
            $summary['total_tests'] += $test_count;
            $summary['module_summaries'][ $module ] = [
                'test_count'   => $test_count,
                'last_updated' => $data['timestamp'] ?? 'unknown',
            ];
        }
    }

    return $summary;
}


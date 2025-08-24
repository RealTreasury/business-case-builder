<?php
// Temporary AJAX flow tracing
add_action( 'wp_ajax_rtbcb_test_company_overview_enhanced', function() {
    error_log( '[DIAG] AJAX Handler Entry: rtbcb_test_company_overview_enhanced' );
    error_log( '[DIAG] POST data keys: ' . implode( ', ', array_keys( $_POST ) ) );
    error_log( '[DIAG] Current user can manage_options: ' . ( current_user_can( 'manage_options' ) ? 'YES' : 'NO' ) );
    // Continue to actual handler...
}, 5 );

add_action( 'wp_ajax_rtbcb_run_llm_test', function() {
    error_log( '[DIAG] AJAX Handler Entry: rtbcb_run_llm_test - Handler ' . ( has_action( 'wp_ajax_rtbcb_run_llm_test' ) ? 'EXISTS' : 'MISSING' ) );
}, 5 );

/**
 * Enhanced AJAX helper functions.
 *
 * @package RealTreasuryBusinessCaseBuilder
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
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
 * Execute LLM test matrix across multiple models.
 *
 * @param array $input_data Sanitized input data.
 * @return array
 * @throws Exception When API key not configured.
 */
function rtbcb_execute_llm_test_matrix( $input_data ) {
    $results = [];
    $api_key = get_option( 'rtbcb_openai_api_key' );

    if ( empty( $api_key ) ) {
        throw new Exception( __( 'OpenAI API key not configured.', 'rtbcb' ) );
    }

    // Model pricing per 1K tokens (input)
    $pricing = [
        'mini'     => 0.00015, // GPT-4O Mini
        'premium'  => 0.005,   // GPT-4O
        'advanced' => 0.015,   // O1-Preview
    ];

    foreach ( $input_data['modelIds'] as $model_key ) {
        $model_name = get_option( "rtbcb_{$model_key}_model", rtbcb_get_default_model( $model_key ) );

        $start_time = microtime( true );

        try {
            $messages = [
                [ 'role' => 'user', 'content' => $input_data['promptA'] ],
            ];

            $request_data = [
                'model'      => $model_name,
                'messages'   => $messages,
                'max_tokens' => $input_data['maxTokens'],
            ];

            // Add temperature if model supports it
            if ( rtbcb_model_supports_temperature( $model_name ) ) {
                $request_data['temperature'] = $input_data['temperature'];
            }

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

            $end_time      = microtime( true );
            $response_time = round( ( $end_time - $start_time ) * 1000 ); // ms

            if ( is_wp_error( $response ) ) {
                $results[ $model_key ] = [
                    'success'       => false,
                    'error'         => $response->get_error_message(),
                    'response_time' => $response_time,
                    'model_key'     => $model_key,
                    'model_name'    => $model_name,
                ];
                continue;
            }

            $response_code = wp_remote_retrieve_response_code( $response );
            $response_body = wp_remote_retrieve_body( $response );

            if ( 200 !== $response_code ) {
                $results[ $model_key ] = [
                    'success'       => false,
                    'error'         => sprintf( __( 'API error %d: %s', 'rtbcb' ), $response_code, $response_body ),
                    'response_time' => $response_time,
                    'model_key'     => $model_key,
                    'model_name'    => $model_name,
                ];
                continue;
            }

            $decoded = json_decode( $response_body, true );

            if ( ! isset( $decoded['choices'][0]['message']['content'] ) ) {
                $results[ $model_key ] = [
                    'success'       => false,
                    'error'         => __( 'Unexpected API response format', 'rtbcb' ),
                    'response_time' => $response_time,
                    'model_key'     => $model_key,
                    'model_name'    => $model_name,
                ];
                continue;
            }

            $content       = $decoded['choices'][0]['message']['content'];
            $tokens_used   = $decoded['usage']['total_tokens'] ?? 0;
            $cost_estimate = ( $tokens_used / 1000 ) * ( $pricing[ $model_key ] ?? 0.005 );

            // Calculate quality metrics
            $quality_score = rtbcb_calculate_llm_response_quality( $content, $input_data['promptA'] );

            $results[ $model_key ] = [
                'success'         => true,
                'content'         => $content,
                'response_time'   => $response_time,
                'tokens_used'     => $tokens_used,
                'cost_estimate'   => round( $cost_estimate, 6 ),
                'quality_score'   => $quality_score,
                'model_key'       => $model_key,
                'model_name'      => $model_name,
                'word_count'      => str_word_count( wp_strip_all_tags( $content ) ),
                'character_count' => strlen( $content ),
            ];

        } catch ( Exception $e ) {
            $end_time      = microtime( true );
            $response_time = round( ( $end_time - $start_time ) * 1000 );

            $results[ $model_key ] = [
                'success'       => false,
                'error'         => $e->getMessage(),
                'response_time' => $response_time,
                'model_key'     => $model_key,
                'model_name'    => $model_name,
            ];
        }
    }

    return $results;
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
add_action( 'wp_ajax_rtbcb_run_api_health_tests', 'rtbcb_run_api_health_tests' );
add_action( 'wp_ajax_rtbcb_run_single_api_test', 'rtbcb_run_single_api_test' );
add_action( 'wp_ajax_rtbcb_run_data_health_checks', 'rtbcb_run_data_health_checks' );
add_action( 'wp_ajax_rtbcb_test_rag_query', 'rtbcb_test_rag_query' );
add_action( 'wp_ajax_rtbcb_rag_rebuild_index', 'rtbcb_rag_rebuild_index' );
add_action( 'wp_ajax_rtbcb_generate_preview_report', 'rtbcb_generate_preview_report' );
add_action( 'wp_ajax_rtbcb_save_dashboard_settings', 'rtbcb_save_dashboard_settings' );
// Missing AJAX action hooks
add_action( 'wp_ajax_rtbcb_run_llm_test', 'rtbcb_ajax_run_llm_test' );
add_action( 'wp_ajax_rtbcb_run_rag_test', 'rtbcb_ajax_run_rag_test' );
add_action( 'wp_ajax_rtbcb_api_health_ping', 'rtbcb_ajax_api_health_ping' );
add_action( 'wp_ajax_rtbcb_export_results', 'rtbcb_ajax_export_results' );

/**
 * Test individual LLM model with given prompt.
 *
 * @return void
 */
function rtbcb_ajax_test_llm_model() {
    // Verify nonce and permissions
    if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'rtbcb_llm_testing' ) ) {
        wp_send_json_error( [ 'message' => __( 'Security check failed.', 'rtbcb' ) ], 403 );
    }

    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( [ 'message' => __( 'Insufficient permissions.', 'rtbcb' ) ], 403 );
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
        wp_send_json_error( [ 'message' => __( 'Model key is required.', 'rtbcb' ) ], 400 );
    }

    if ( empty( $user_prompt ) ) {
        wp_send_json_error( [ 'message' => __( 'User prompt is required.', 'rtbcb' ) ], 400 );
    }

    // Validate model key
    $available_models = [
        'mini'     => get_option( 'rtbcb_mini_model', rtbcb_get_default_model( 'mini' ) ),
        'premium'  => get_option( 'rtbcb_premium_model', rtbcb_get_default_model( 'premium' ) ),
        'advanced' => get_option( 'rtbcb_advanced_model', rtbcb_get_default_model( 'advanced' ) ),
    ];

    if ( ! isset( $available_models[ $model_key ] ) ) {
        wp_send_json_error( [ 'message' => __( 'Invalid model key.', 'rtbcb' ) ], 400 );
    }

    $model_name = $available_models[ $model_key ];

    // Validate parameters
    if ( $max_tokens < 1 || $max_tokens > 4000 ) {
        wp_send_json_error( [ 'message' => __( 'Max tokens must be between 1 and 4000.', 'rtbcb' ) ], 400 );
    }

    if ( $temperature < 0 || $temperature > 2 ) {
        wp_send_json_error( [ 'message' => __( 'Temperature must be between 0 and 2.', 'rtbcb' ) ], 400 );
    }

    // Get API key
    $api_key = get_option( 'rtbcb_openai_api_key', '' );
    if ( empty( $api_key ) ) {
        wp_send_json_error( [ 'message' => __( 'OpenAI API key not configured.', 'rtbcb' ) ], 500 );
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
            wp_send_json_error(
                [
                    'message' => $result->get_error_message(),
                    'code'    => $result->get_error_code(),
                ],
                500
            );
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
        wp_send_json_error(
            [
                'message' => __( 'An error occurred while testing the model. Please try again.', 'rtbcb' ),
                'debug'   => WP_DEBUG ? $e->getMessage() : null,
            ],
            500
        );
    }
}

/**
 * Enhanced company overview testing with debug information.
 *
 * @return void
 */
function rtbcb_ajax_test_company_overview_enhanced() {
    // Verify nonce and permissions
    if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'rtbcb_unified_test_dashboard' ) ) {
        wp_send_json_error( [ 'message' => __( 'Security check failed.', 'rtbcb' ) ], 403 );
    }

    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( [ 'message' => __( 'Insufficient permissions.', 'rtbcb' ) ], 403 );
    }

    // Get input parameters
    $company_name = isset( $_POST['company_name'] ) ? sanitize_text_field( wp_unslash( $_POST['company_name'] ) ) : '';
    $model_key    = isset( $_POST['model'] ) ? sanitize_text_field( wp_unslash( $_POST['model'] ) ) : 'mini';
    $show_debug   = isset( $_POST['show_debug'] ) ? rest_sanitize_boolean( wp_unslash( $_POST['show_debug'] ) ) : false;
    $request_id   = isset( $_POST['request_id'] ) ? sanitize_text_field( wp_unslash( $_POST['request_id'] ) ) : '';

    // Validate required fields
    if ( empty( $company_name ) ) {
        wp_send_json_error( [ 'message' => __( 'Company name is required.', 'rtbcb' ) ], 400 );
    }

    // Set timeout and memory limits for comprehensive analysis
    if ( ! ini_get( 'safe_mode' ) ) {
        set_time_limit( 180 ); // 3 minutes
    }
    wp_raise_memory_limit( '512M' );

    $start_time = microtime( true );

    try {
        // Generate company overview
        $overview_result = rtbcb_test_generate_company_overview( $company_name );

        if ( is_wp_error( $overview_result ) ) {
            wp_send_json_error(
                [
                    'message' => $overview_result->get_error_message(),
                    'code'    => $overview_result->get_error_code(),
                ],
                500
            );
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
        wp_send_json_error(
            [
                'message' => __( 'An error occurred while generating the company overview.', 'rtbcb' ),
                'debug'   => WP_DEBUG ? $e->getMessage() : null,
            ],
            500
        );
    }
}

/**
 * Calculate ROI for testing purposes.
 *
 * @return void
 */
function rtbcb_ajax_calculate_roi_test() {
    // Verify nonce and permissions
    if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'rtbcb_roi_calculator_test' ) ) {
        wp_send_json_error( [ 'message' => __( 'Security check failed.', 'rtbcb' ) ], 403 );
    }

    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( [ 'message' => __( 'Insufficient permissions.', 'rtbcb' ) ], 403 );
    }

    // Get ROI input data
    $roi_data = isset( $_POST['roi_data'] ) ? wp_unslash( $_POST['roi_data'] ) : [];

    if ( empty( $roi_data ) || ! is_array( $roi_data ) ) {
        wp_send_json_error( [ 'message' => __( 'ROI data is required.', 'rtbcb' ) ], 400 );
    }

    // Sanitize ROI data
    $roi_data = rtbcb_sanitize_form_data( $roi_data );

    try {
        // Calculate ROI scenarios
        $scenarios = RTBCB_Calculator::calculate_roi( $roi_data );

        if ( is_wp_error( $scenarios ) ) {
            wp_send_json_error(
                [
                    'message' => $scenarios->get_error_message(),
                ],
                500
            );
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
        error_log( 'RTBCB ROI Test Calculation Error: ' . $e->getMessage() );
        wp_send_json_error(
            [
                'message' => __( 'An error occurred while calculating ROI.', 'rtbcb' ),
                'debug'   => WP_DEBUG ? $e->getMessage() : null,
            ],
            500
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
    if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'rtbcb_llm_testing' ) ) {
        wp_send_json_error( [ 'message' => __( 'Security check failed.', 'rtbcb' ) ], 403 );
    }

    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( [ 'message' => __( 'Insufficient permissions.', 'rtbcb' ) ], 403 );
    }

    $response_text  = isset( $_POST['response_text'] ) ? sanitize_textarea_field( wp_unslash( $_POST['response_text'] ) ) : '';
    $reference_text = isset( $_POST['reference_text'] ) ? sanitize_textarea_field( wp_unslash( $_POST['reference_text'] ) ) : '';

    if ( empty( $response_text ) ) {
        wp_send_json_error( [ 'message' => __( 'Response text is required.', 'rtbcb' ) ], 400 );
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
        wp_send_json_error(
            [
                'message' => __( 'An error occurred while evaluating response quality.', 'rtbcb' ),
                'debug'   => WP_DEBUG ? $e->getMessage() : null,
            ],
            500
        );
    }
}

/**
 * Optimize prompt for token efficiency.
 *
 * @return void
 */
function rtbcb_ajax_optimize_prompt_tokens() {
    // Verify nonce and permissions
    if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'rtbcb_llm_testing' ) ) {
        wp_send_json_error( [ 'message' => __( 'Security check failed.', 'rtbcb' ) ], 403 );
    }

    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( [ 'message' => __( 'Insufficient permissions.', 'rtbcb' ) ], 403 );
    }

    $prompt = isset( $_POST['prompt'] ) ? sanitize_textarea_field( wp_unslash( $_POST['prompt'] ) ) : '';

    if ( empty( $prompt ) ) {
        wp_send_json_error( [ 'message' => __( 'Prompt text is required.', 'rtbcb' ) ], 400 );
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
        wp_send_json_error(
            [
                'message' => __( 'An error occurred while optimizing the prompt.', 'rtbcb' ),
                'debug'   => WP_DEBUG ? $e->getMessage() : null,
            ],
            500
        );
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
        wp_send_json_error( [ 'message' => __( 'Security check failed.', 'rtbcb' ) ], 403 );
    }

    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( [ 'message' => __( 'Insufficient permissions.', 'rtbcb' ) ], 403 );
    }

    $query = isset( $_POST['query'] ) ? sanitize_text_field( wp_unslash( $_POST['query'] ) ) : '';
    $top_k = isset( $_POST['top_k'] ) ? intval( wp_unslash( $_POST['top_k'] ) ) : 3;
    $type  = isset( $_POST['type'] ) ? sanitize_key( wp_unslash( $_POST['type'] ) ) : 'all';

    if ( '' === $query ) {
        wp_send_json_error( [ 'message' => __( 'Query is required.', 'rtbcb' ) ], 400 );
    }

    if ( ! class_exists( 'RTBCB_RAG' ) ) {
        wp_send_json_error( [ 'message' => __( 'RAG class missing.', 'rtbcb' ) ], 500 );
    }

    $api_key = get_option( 'rtbcb_openai_api_key' );
    if ( empty( $api_key ) ) {
        wp_send_json_error( [ 'message' => __( 'No API key configured.', 'rtbcb' ) ], 500 );
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
        wp_send_json_error( [ 'message' => __( 'RAG search failed.', 'rtbcb' ) ], 500 );
    }
}

/**
 * Rebuild the RAG index.
 *
 * @return void
 */
function rtbcb_rag_rebuild_index() {
    if ( ! check_ajax_referer( 'rtbcb_unified_test_dashboard', 'nonce', false ) ) {
        wp_send_json_error( [ 'message' => __( 'Security check failed.', 'rtbcb' ) ], 403 );
    }

    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( [ 'message' => __( 'Insufficient permissions.', 'rtbcb' ) ], 403 );
    }

    if ( ! class_exists( 'RTBCB_RAG' ) ) {
        wp_send_json_error( [ 'message' => __( 'RAG class missing.', 'rtbcb' ) ], 500 );
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
        wp_send_json_error( [ 'message' => __( 'Failed to rebuild index.', 'rtbcb' ) ], 500 );
    }
}

/**
 * Run all API health tests and return aggregated results.
 *
 * @return void
 */
function rtbcb_run_api_health_tests() {
    if ( ! check_ajax_referer( 'rtbcb_api_health_tests', 'nonce', false ) ) {
        wp_send_json_error( [ 'message' => __( 'Security check failed.', 'rtbcb' ) ], 403 );
    }

    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( [ 'message' => __( 'Insufficient permissions.', 'rtbcb' ) ], 403 );
    }

    $components = [
        'chat'      => __( 'OpenAI Chat API', 'rtbcb' ),
        'embedding' => __( 'OpenAI Embedding API', 'rtbcb' ),
        'portal'    => __( 'Real Treasury Portal', 'rtbcb' ),
        'roi'       => __( 'ROI Calculator', 'rtbcb' ),
        'rag'       => __( 'RAG Index', 'rtbcb' ),
    ];

    $results = [];

    $timestamp = current_time( 'mysql' );

    foreach ( $components as $key => $label ) {
        $start = microtime( true );

        switch ( $key ) {
            case 'chat':
                $test = RTBCB_API_Tester::test_connection();
                break;
            case 'embedding':
                $test = RTBCB_API_Tester::test_embedding();
                break;
            case 'portal':
                $test = RTBCB_API_Tester::test_portal();
                break;
            case 'roi':
                $test = RTBCB_API_Tester::test_roi_calculator();
                break;
            case 'rag':
                $test = RTBCB_API_Tester::test_rag_index();
                break;
            default:
                $test = [ 'success' => false, 'message' => __( 'Unknown test.', 'rtbcb' ) ];
        }

        $end = microtime( true );

        $results[ $key ] = [
            'name'          => $label,
            'passed'        => (bool) ( $test['success'] ?? false ),
            'response_time' => intval( ( $end - $start ) * 1000 ),
            'message'       => sanitize_text_field( $test['message'] ?? '' ),
            'details'       => $test,
            'last_tested'   => $timestamp,
        ];
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
        wp_send_json_error( [ 'message' => __( 'Security check failed.', 'rtbcb' ) ], 403 );
    }

    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( [ 'message' => __( 'Insufficient permissions.', 'rtbcb' ) ], 403 );
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
                'timeout' => 10,
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
        wp_send_json_error( [ 'message' => __( 'Security check failed.', 'rtbcb' ) ], 403 );
    }

    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( [ 'message' => __( 'Insufficient permissions.', 'rtbcb' ) ], 403 );
    }

    $component = isset( $_POST['component'] ) ? sanitize_key( wp_unslash( $_POST['component'] ) ) : '';
    $start     = microtime( true );

    switch ( $component ) {
        case 'chat':
            $test = RTBCB_API_Tester::test_connection();
            $name = __( 'OpenAI Chat API', 'rtbcb' );
            break;
        case 'embedding':
            $test = RTBCB_API_Tester::test_embedding();
            $name = __( 'OpenAI Embedding API', 'rtbcb' );
            break;
        case 'portal':
            $test = RTBCB_API_Tester::test_portal();
            $name = __( 'Real Treasury Portal', 'rtbcb' );
            break;
        case 'roi':
            $test = RTBCB_API_Tester::test_roi_calculator();
            $name = __( 'ROI Calculator', 'rtbcb' );
            break;
        case 'rag':
            $test = RTBCB_API_Tester::test_rag_index();
            $name = __( 'RAG Index', 'rtbcb' );
            break;
        default:
            wp_send_json_error( [ 'message' => __( 'Invalid component.', 'rtbcb' ) ], 400 );
    }

    $end = microtime( true );

    $result = [
        'name'          => $name,
        'passed'        => (bool) ( $test['success'] ?? false ),
        'response_time' => intval( ( $end - $start ) * 1000 ),
        'message'       => sanitize_text_field( $test['message'] ?? '' ),
        'details'       => $test,
    ];

    $option                              = get_option( 'rtbcb_last_api_test', [ 'timestamp' => '', 'results' => [] ] );
    $option['timestamp']                 = current_time( 'mysql' );
    $result['last_tested']               = $option['timestamp'];
    $option['results'][ $component ]     = $result;
    update_option( 'rtbcb_last_api_test', $option );

    wp_send_json_success(
        [
            'timestamp' => $option['timestamp'],
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
        wp_send_json_error( [ 'message' => __( 'Security check failed.', 'rtbcb' ) ], 403 );
    }

    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( [ 'message' => __( 'Insufficient permissions.', 'rtbcb' ) ], 403 );
    }

    $company = rtbcb_get_current_company();
    if ( empty( $company ) ) {
        wp_send_json_error( [ 'message' => __( 'No company data found. Please run the company overview first.', 'rtbcb' ) ], 400 );
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
        wp_send_json_error( [ 'message' => __( 'Report template not found.', 'rtbcb' ) ], 500 );
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
    if ( ! check_ajax_referer( 'rtbcb_save_dashboard_settings', 'nonce', false ) ) {
        wp_send_json_error( [ 'message' => __( 'Security check failed.', 'rtbcb' ) ], 403 );
    }

    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( [ 'message' => __( 'Insufficient permissions.', 'rtbcb' ) ], 403 );
    }

    $fields = [
        'rtbcb_openai_api_key'      => 'sanitize_text_field',
        'rtbcb_mini_model'          => 'sanitize_text_field',
        'rtbcb_premium_model'       => 'sanitize_text_field',
        'rtbcb_advanced_model'      => 'sanitize_text_field',
        'rtbcb_embedding_model'     => 'sanitize_text_field',
    ];

    foreach ( $fields as $option => $sanitize ) {
        $value = isset( $_POST[ $option ] ) ? call_user_func( $sanitize, wp_unslash( $_POST[ $option ] ) ) : '';
        update_option( $option, $value );
    }

    wp_send_json_success( [ 'message' => __( 'Settings saved.', 'rtbcb' ) ] );
}

/**
 * Run LLM integration test
 */
function rtbcb_ajax_run_llm_test() {
    // Security checks
    if ( ! check_ajax_referer( 'rtbcb_llm_testing', 'nonce', false ) ) {
        wp_send_json_error(
            [
                'code'      => 'security_failed',
                'message'   => __( 'Security check failed.', 'rtbcb' ),
                'requestId' => uniqid( 'req_' ),
            ],
            403
        );
    }

    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error(
            [
                'code'      => 'insufficient_permissions',
                'message'   => __( 'Insufficient permissions.', 'rtbcb' ),
                'requestId' => uniqid( 'req_' ),
            ],
            403
        );
    }

    // Input sanitization
    $input_data = [
        'modelIds'   => array_map( 'sanitize_text_field', wp_unslash( $_POST['modelIds'] ?? [] ) ),
        'promptA'    => sanitize_textarea_field( wp_unslash( $_POST['promptA'] ?? '' ) ),
        'promptB'    => sanitize_textarea_field( wp_unslash( $_POST['promptB'] ?? '' ) ),
        'inputs'     => array_map( 'sanitize_text_field', wp_unslash( $_POST['inputs'] ?? [] ) ),
        'runMode'    => sanitize_text_field( wp_unslash( $_POST['runMode'] ?? 'single' ) ),
        'maxTokens'  => intval( wp_unslash( $_POST['maxTokens'] ?? 1000 ) ),
        'temperature'=> floatval( wp_unslash( $_POST['temperature'] ?? 0.3 ) ),
    ];

    // Validation
    if ( empty( $input_data['modelIds'] ) ) {
        wp_send_json_error(
            [
                'code'    => 'missing_models',
                'message' => __( 'No models specified.', 'rtbcb' ),
            ],
            400
        );
    }

    if ( empty( $input_data['promptA'] ) ) {
        wp_send_json_error(
            [
                'code'    => 'missing_prompt',
                'message' => __( 'Prompt is required.', 'rtbcb' ),
            ],
            400
        );
    }

    try {
        $start_time = microtime( true );

        // Run LLM tests
        $results = rtbcb_execute_llm_test_matrix( $input_data );

        $end_time = microtime( true );

        wp_send_json_success(
            [
                'results'  => $results,
                'metadata' => [
                    'totalTime'  => round( ( $end_time - $start_time ), 2 ),
                    'modelsCount'=> count( $input_data['modelIds'] ),
                    'timestamp'  => current_time( 'mysql' ),
                ],
                'requestId' => uniqid( 'req_' ),
            ]
        );
    } catch ( Exception $e ) {
        error_log( 'RTBCB LLM Test Error: ' . $e->getMessage() );
        wp_send_json_error(
            [
                'code'      => 'execution_failed',
                'message'   => __( 'LLM test execution failed.', 'rtbcb' ),
                'detail'    => WP_DEBUG ? $e->getMessage() : null,
                'requestId' => uniqid( 'req_' ),
            ],
            500
        );
    }
}

/**
 * Run RAG system test
 */
function rtbcb_ajax_run_rag_test() {
    // Security checks
    if ( ! check_ajax_referer( 'rtbcb_rag_testing', 'nonce', false ) ) {
        wp_send_json_error(
            [
                'code'    => 'security_failed',
                'message' => __( 'Security check failed.', 'rtbcb' ),
            ],
            403
        );
    }

    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error(
            [
                'code'    => 'insufficient_permissions',
                'message' => __( 'Insufficient permissions.', 'rtbcb' ),
            ],
            403
        );
    }

    // Input sanitization
    $input_data = [
        'queries' => array_map( 'sanitize_text_field', wp_unslash( $_POST['queries'] ?? [] ) ),
        'topK'    => intval( wp_unslash( $_POST['topK'] ?? 5 ) ),
        'mode'    => sanitize_text_field( wp_unslash( $_POST['mode'] ?? 'similarity' ) ),
    ];

    if ( empty( $input_data['queries'] ) ) {
        wp_send_json_error(
            [
                'code'    => 'missing_queries',
                'message' => __( 'Test queries are required.', 'rtbcb' ),
            ],
            400
        );
    }

    try {
        // Initialize RAG system
        if ( ! class_exists( 'RTBCB_RAG' ) ) {
            wp_send_json_error(
                [
                    'code'    => 'rag_unavailable',
                    'message' => __( 'RAG system not available.', 'rtbcb' ),
                ],
                500
            );
        }

        $rag     = new RTBCB_RAG();
        $results = [];

        foreach ( $input_data['queries'] as $query ) {
            $start_time     = microtime( true );
            $search_results = $rag->search_similar( $query, $input_data['topK'] );
            $end_time       = microtime( true );

            $results[] = [
                'query'   => $query,
                'results' => $search_results,
                'metrics' => [
                    'retrievalTime' => round( ( $end_time - $start_time ) * 1000, 2 ),
                    'resultCount'   => count( $search_results ),
                    'avgScore'      => count( $search_results ) > 0 ?
                        array_sum( array_column( $search_results, 'score' ) ) / count( $search_results ) : 0,
                ],
            ];
        }

        wp_send_json_success(
            [
                'results' => $results,
                'summary' => [
                    'totalQueries'     => count( $input_data['queries'] ),
                    'avgRetrievalTime' => array_sum( array_column( array_column( $results, 'metrics' ), 'retrievalTime' ) ) / count( $results ),
                    'timestamp'        => current_time( 'mysql' ),
                ],
            ]
        );
    } catch ( Exception $e ) {
        error_log( 'RTBCB RAG Test Error: ' . $e->getMessage() );
        wp_send_json_error(
            [
                'code'    => 'rag_test_failed',
                'message' => __( 'RAG test execution failed.', 'rtbcb' ),
                'detail'  => WP_DEBUG ? $e->getMessage() : null,
            ],
            500
        );
    }
}

/**
 * Run API health ping
 */
function rtbcb_ajax_api_health_ping() {
    if ( ! check_ajax_referer( 'rtbcb_api_health_tests', 'nonce', false ) ) {
        wp_send_json_error(
            [
                'code'    => 'security_failed',
                'message' => __( 'Security check failed.', 'rtbcb' ),
            ],
            403
        );
    }

    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error(
            [
                'code'    => 'insufficient_permissions',
                'message' => __( 'Insufficient permissions.', 'rtbcb' ),
            ],
            403
        );
    }

    try {
        $ping_result = rtbcb_execute_openai_health_ping();

        if ( $ping_result['success'] ) {
            // Update last successful ping
            update_option( 'rtbcb_openai_last_ok', time() );
            delete_transient( 'rtbcb_openai_error' );
        } else {
            // Store error info
            update_option( 'rtbcb_openai_last_error_at', time() );
            set_transient(
                'rtbcb_openai_error',
                [
                    'code'      => $ping_result['code'] ?? 'unknown',
                    'httpStatus'=> $ping_result['httpStatus'] ?? 0,
                    'body'      => substr( $ping_result['body'] ?? '', 0, 200 ),
                    'timestamp' => time(),
                ],
                600
            ); // 10 minutes
        }

        wp_send_json_success( $ping_result );
    } catch ( Exception $e ) {
        error_log( 'RTBCB API Health Ping Error: ' . $e->getMessage() );
        wp_send_json_error(
            [
                'code'       => 'ping_failed',
                'message'    => __( 'Health ping failed.', 'rtbcb' ),
                'retryAfter' => 30,
            ],
            500
        );
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
            'timeout' => 10,
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
 * Export test results
 */
function rtbcb_ajax_export_results() {
    if ( ! check_ajax_referer( 'rtbcb_unified_test_dashboard', 'nonce', false ) ) {
        wp_send_json_error(
            [
                'code'    => 'security_failed',
                'message' => __( 'Security check failed.', 'rtbcb' ),
            ],
            403
        );
    }

    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error(
            [
                'code'    => 'insufficient_permissions',
                'message' => __( 'Insufficient permissions.', 'rtbcb' ),
            ],
            403
        );
    }

    $export_type = sanitize_text_field( wp_unslash( $_POST['exportType'] ?? 'json' ) );
    $test_data   = isset( $_POST['testData'] ) ? wp_unslash( $_POST['testData'] ) : [];

    if ( empty( $test_data ) ) {
        wp_send_json_error(
            [
                'code'    => 'no_data',
                'message' => __( 'No test data to export.', 'rtbcb' ),
            ],
            400
        );
    }

    try {
        $export_data = [
            'metadata' => [
                'exportTime'   => current_time( 'c' ),
                'plugin'       => 'Real Treasury Business Case Builder',
                'version'      => defined( 'RTBCB_VERSION' ) ? RTBCB_VERSION : '2.0.0',
                'testDashboard'=> 'unified-test-dashboard',
            ],
            'results'  => $test_data,
        ];

        if ( 'csv' === $export_type ) {
            $csv_content = rtbcb_convert_results_to_csv( $export_data );
            wp_send_json_success(
                [
                    'content'     => $csv_content,
                    'filename'    => 'rtbcb-test-results-' . date( 'Y-m-d-H-i-s' ) . '.csv',
                    'contentType' => 'text/csv',
                ]
            );
        } else {
            wp_send_json_success(
                [
                    'content'     => wp_json_encode( $export_data, JSON_PRETTY_PRINT ),
                    'filename'    => 'rtbcb-test-results-' . date( 'Y-m-d-H-i-s' ) . '.json',
                    'contentType' => 'application/json',
                ]
            );
        }
    } catch ( Exception $e ) {
        error_log( 'RTBCB Export Results Error: ' . $e->getMessage() );
        wp_send_json_error(
            [
                'code'    => 'export_failed',
                'message' => __( 'Export generation failed.', 'rtbcb' ),
            ],
            500
        );
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


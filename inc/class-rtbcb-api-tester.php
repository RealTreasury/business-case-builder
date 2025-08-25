<?php
/**
 * API connection testing utilities.
 *
 * @package RealTreasuryBusinessCaseBuilder
 */

/**
 * Class RTBCB_API_Tester.
 */
class RTBCB_API_Tester {
    /**
     * Test OpenAI API connection.
     *
     * @param string $api_key Optional API key to test.
     * @return array|WP_Error Test result or error.
     */
    public static function test_connection( $api_key = null ) {
        $api_key = $api_key ?: get_option( 'rtbcb_openai_api_key' );

        if ( empty( $api_key ) ) {
            return [
                'success' => false,
                'message' => __( 'No API key configured.', 'rtbcb' ),
                'details' => __( 'Please add your OpenAI API key in settings.', 'rtbcb' ),
            ];
        }

        if ( ! rtbcb_is_valid_openai_api_key( $api_key ) ) {
            return [
                'success' => false,
                'message' => __( 'Invalid API key format.', 'rtbcb' ),
                'details' => __( 'OpenAI API keys should start with "sk-".', 'rtbcb' ),
            ];
        }

        $result = self::test_completion( $api_key );

        if ( is_wp_error( $result ) ) {
            return $result;
        }

        if ( isset( $result['parsed_length'], $result['raw_length'] ) ) {
            $result['details'] = sprintf(
                __( 'Parsed %1$d characters from %2$d bytes.', 'rtbcb' ),
                $result['parsed_length'],
                $result['raw_length']
            );
        }

        $models = self::fetch_available_models( $api_key );
        if ( ! empty( $models ) ) {
            $result['models_available'] = $models;
        }

        return $result;
    }

    /**
     * Test OpenAI embedding API connectivity.
     *
     * @return array Test result data.
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

        if ( 200 === $status_code ) {
            $body            = json_decode( wp_remote_retrieve_body( $response ), true );
            $embedding       = $body['data'][0]['embedding'] ?? [];
            $embedding_count = is_array( $embedding ) ? count( $embedding ) : 0;

            return [
                'success' => true,
                'message' => sprintf( __( 'Embedding API working (vector dim: %d)', 'rtbcb' ), $embedding_count ),
                'details' => [
                    'model_used'           => $model,
                    'embedding_dimensions' => $embedding_count,
                    'tokens_used'          => $body['usage']['total_tokens'] ?? 0,
                ],
            ];
        } else {
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
    }

    /**
     * Test Real Treasury Portal integration.
     *
     * @return array Test result data.
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
                    'vendors_count'      => count( $vendors ),
                    'integration_active' => true,
                ],
            ];
        } catch ( Exception $e ) {
            return [
                'success' => false,
                'message' => __( 'Portal integration error: ', 'rtbcb' ) . $e->getMessage(),
                'details' => [
                    'error'     => 'integration_error',
                    'exception' => $e->getMessage(),
                ],
            ];
        }
    }

    /**
     * Test ROI calculator functionality.
     *
     * @return array Test result data.
     */
    public static function test_roi_calculator() {
        $test_data = [
            'company_size'        => 'medium',
            'annual_revenue'      => 100000000,
            'treasury_staff'      => 3,
            'hours_reconciliation' => 20,
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
                    'test_roi'        => $result['base']['roi_percentage'] ?? 0,
                    'calculation_time' => microtime( true ),
                ],
            ];
        } catch ( Exception $e ) {
            return [
                'success' => false,
                'message' => __( 'ROI Calculator error: ', 'rtbcb' ) . $e->getMessage(),
                'details' => [
                    'error'     => 'exception',
                    'exception' => $e->getMessage(),
                ],
            ];
        }
    }

    /**
     * Test RAG index health and search capability.
     *
     * @return array Test result data.
     */
    public static function test_rag_index() {
        global $wpdb;

        try {
            $table_name   = $wpdb->prefix . 'rtbcb_rag_index';
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
                        'index_size'         => $index_size,
                        'test_results_count' => is_array( $test_results ) ? count( $test_results ) : 0,
                        'last_indexed'       => get_option( 'rtbcb_last_indexed', '' ),
                    ],
                ];
            } else {
                return [
                    'success' => $index_size > 0,
                    'message' => sprintf( __( 'RAG table exists (%d entries) but RAG class unavailable', 'rtbcb' ), $index_size ),
                    'details' => [
                        'index_size'      => $index_size,
                        'class_available' => false,
                    ],
                ];
            }
        } catch ( Exception $e ) {
            return [
                'success' => false,
                'message' => __( 'RAG index error: ', 'rtbcb' ) . $e->getMessage(),
                'details' => [
                    'error'     => 'exception',
                    'exception' => $e->getMessage(),
                ],
            ];
        }
    }

    /**
     * Test API with a simple completion request.
     *
     * @param string $api_key API key.
     * @return array|WP_Error Test result or error.
     */
    private static function test_completion( $api_key ) {
        $model = get_option( 'rtbcb_mini_model', rtbcb_get_default_model( 'mini' ) );

        $body = [
            'model' => rtbcb_normalize_model_name( $model ),
            'input' => 'Test connection - respond with exactly: "API connection successful"',
            'max_output_tokens' => 256,
        ];

        if ( rtbcb_model_supports_temperature( $model ) ) {
            $body['temperature'] = 0.1;
        }

        $args = [
            'headers' => [
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json',
            ],
            'body' => wp_json_encode( $body ),
            'timeout' => 30,
        ];

        $response = wp_remote_post( 'https://api.openai.com/v1/responses', $args );

        if ( is_wp_error( $response ) ) {
            return [
                'success' => false,
                'message' => __( 'Connection failed.', 'rtbcb' ),
                'details' => $response->get_error_message(),
            ];
        }

        $code = wp_remote_retrieve_response_code( $response );
        if ( 200 !== $code ) {
            $body    = wp_remote_retrieve_body( $response );
            $decoded = json_decode( $body, true );
            $error_message = $decoded['error']['message'] ?? 'Unknown error';

            return [
                'success' => false,
                'message' => sprintf( __( 'API error (%d)', 'rtbcb' ), $code ),
                'details' => $error_message,
            ];
        }

        // Test the parsing specifically.
        $test_result = rtbcb_parse_gpt5_response( $response );
        if ( empty( $test_result['output_text'] ) ) {
            return new WP_Error( 'api_test_failed', __( 'API responded but parsing failed. Check error logs.', 'rtbcb' ) );
        }

        // Log the test result for debugging.
        error_log( 'RTBCB API Test: Parsed output length: ' . strlen( $test_result['output_text'] ) );
        error_log( 'RTBCB API Test: Output preview: ' . substr( $test_result['output_text'], 0, 200 ) );

        return [
            'success'       => true,
            'message'       => __( 'API connection successful', 'rtbcb' ),
            'response'      => $test_result['output_text'],
            'raw_length'    => strlen( $response['body'] ?? '' ),
            'parsed_length' => strlen( $test_result['output_text'] ),
        ];
    }

    /**
     * Retrieve available model identifiers from the OpenAI API.
     *
     * @param string $api_key API key.
     * @return string[] List of model IDs.
     */
    private static function fetch_available_models( $api_key ) {
        $response = wp_remote_get(
            'https://api.openai.com/v1/models',
            [
                'headers' => [
                    'Authorization' => 'Bearer ' . $api_key,
                    'Content-Type'  => 'application/json',
                ],
                'timeout' => 20,
            ]
        );

        if ( is_wp_error( $response ) ) {
            return [];
        }

        if ( 200 !== wp_remote_retrieve_response_code( $response ) ) {
            return [];
        }

        $body   = json_decode( wp_remote_retrieve_body( $response ), true );
        $models = [];

        foreach ( $body['data'] ?? [] as $model ) {
            if ( isset( $model['id'] ) ) {
                $models[] = sanitize_text_field( $model['id'] );
            }
        }

        sort( $models );
        return $models;
    }
}


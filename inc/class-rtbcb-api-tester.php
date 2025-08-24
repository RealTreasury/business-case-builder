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
     * Test the OpenAI Embedding API by generating a sample embedding.
     *
     * @return array Test result data.
     */
    public static function test_embedding() {
        $rag       = new RTBCB_RAG();
        $embedding = $rag->get_embedding( 'test' );

        if ( empty( $embedding ) || ! is_array( $embedding ) ) {
            return [
                'success' => false,
                'message' => __( 'Embedding request failed.', 'rtbcb' ),
                'details' => __( 'No embedding returned.', 'rtbcb' ),
            ];
        }

        return [
            'success'    => true,
            'message'    => __( 'Embedding API reachable.', 'rtbcb' ),
            'dimensions' => count( $embedding ),
        ];
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


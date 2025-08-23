<?php
/**
 * OpenAI API test utilities.
 *
 * @package RealTreasuryBusinessCaseBuilder
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers.php';

/**
 * Provides OpenAI API diagnostics.
 */
class RTBCB_API_Tester {
    /**
     * Run full connection test.
     *
     * @return array Test results.
     */
    public static function test_connection() {
        $api_key = get_option( 'rtbcb_openai_api_key' );

        if ( empty( $api_key ) ) {
            return [
                'success' => false,
                'message' => __( 'No API key configured', 'rtbcb' ),
                'details' => __( 'Please configure your OpenAI API key in the settings.', 'rtbcb' ),
            ];
        }

        if ( ! rtbcb_is_valid_openai_api_key( $api_key ) ) {
            return [
                'success' => false,
                'message' => __( 'Invalid API key format', 'rtbcb' ),
                'details' => __( 'API key must start with "sk-" and may contain letters, numbers, hyphens, colons, and underscores.', 'rtbcb' ),
            ];
        }

        $models_test = self::test_models_endpoint( $api_key );
        if ( ! $models_test['success'] ) {
            return $models_test;
        }

        $completion_test = self::test_completion( $api_key );
        if ( ! $completion_test['success'] ) {
            return $completion_test;
        }

        return [
            'success'         => true,
            'message'         => __( 'OpenAI API connection successful', 'rtbcb' ),
            'details'         => __( 'All tests passed. API is ready for business case generation.', 'rtbcb' ),
            'models_available' => $models_test['models'] ?? [],
            'test_response'    => $completion_test['response'] ?? '',
        ];
    }

    /**
     * Test models endpoint.
     *
     * @param string $api_key API key.
     * @return array Test result.
     */
    private static function test_models_endpoint( $api_key ) {
        $endpoint = 'https://api.openai.com/v1/models';
        $args     = [
            'headers' => [
                'Authorization' => 'Bearer ' . $api_key,
            ],
            'timeout' => 15,
        ];

        $response = wp_remote_get( $endpoint, $args );

        if ( is_wp_error( $response ) ) {
            return [
                'success' => false,
                'message' => __( 'HTTP request failed', 'rtbcb' ),
                'details' => sanitize_text_field( $response->get_error_message() ),
            ];
        }

        $code = wp_remote_retrieve_response_code( $response );
        $body = wp_remote_retrieve_body( $response );

        if ( 200 !== $code ) {
            $error_data = json_decode( $body, true );
            return [
                'success' => false,
                'message' => __( 'API authentication failed', 'rtbcb' ),
                'details' => sanitize_text_field( $error_data['error']['message'] ?? 'HTTP ' . $code ),
                'http_code' => $code,
            ];
        }

        $data   = json_decode( $body, true );
        $models = [];

        if ( isset( $data['data'] ) ) {
            foreach ( $data['data'] as $model ) {
                if ( strpos( $model['id'], 'gpt' ) !== false ) {
                    $models[] = sanitize_text_field( $model['id'] );
                }
            }
        }

        return [
            'success' => true,
            'message' => __( 'Models endpoint accessible', 'rtbcb' ),
            'models'  => $models,
        ];
    }

    /**
     * Test Responses API endpoint.
     *
     * @param string $api_key API key.
     * @return array Test result.
     */
    private static function test_completion( $api_key ) {
        $endpoint = 'https://api.openai.com/v1/responses';

        $model             = 'gpt-5-mini';
        $config            = rtbcb_get_gpt5_config( get_option( 'rtbcb_gpt5_config', [] ) );
        $max_output_tokens = intval( $config['max_output_tokens'] ); // Sanitize token limit.
        $body              = [
            'model'             => $model,
            'input'             => __( "Briefly confirm the API is wired correctly. Reply with: 'API connection successful and ready for business case generation.'", 'rtbcb' ),
            // Use the configured token limit for the API test.
            'max_output_tokens' => $max_output_tokens,
            'reasoning'         => [ 'effort' => 'minimal' ],
            'text'              => [ 'verbosity' => 'low' ],
        ];

        $args = [
            'headers' => [
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type'  => 'application/json',
            ],
            'body'    => wp_json_encode( $body ),
            'timeout' => 30,
        ];

        $response = wp_remote_post( $endpoint, $args );

        if ( is_wp_error( $response ) ) {
            return [
                'success' => false,
                'message' => __( 'Responses API request failed', 'rtbcb' ),
                'details' => sanitize_text_field( $response->get_error_message() ),
            ];
        }

        $code          = wp_remote_retrieve_response_code( $response );
        $response_body = wp_remote_retrieve_body( $response );

        if ( 200 !== $code ) {
            $error_data = json_decode( $response_body, true );
            return [
                'success'   => false,
                'message'   => __( 'Responses API error', 'rtbcb' ),
                'details'   => sanitize_text_field( $error_data['error']['message'] ?? 'HTTP ' . $code ),
                'http_code' => $code,
            ];
        }

        $data = json_decode( $response_body, true );

        if ( JSON_ERROR_NONE !== json_last_error() || ! is_array( $data ) ) {
            return [
                'success' => false,
                'message' => __( 'Invalid JSON response', 'rtbcb' ),
                'details' => 'Response: ' . sanitize_text_field( $response_body ),
            ];
        }

        $text = '';

        if ( isset( $data['output_text'] ) && '' !== $data['output_text'] ) {
            $text = is_array( $data['output_text'] ) ? implode( ' ', (array) $data['output_text'] ) : (string) $data['output_text'];
        }

        if ( '' === $text && ! empty( $data['output'] ) && is_array( $data['output'] ) ) {
            foreach ( $data['output'] as $item ) {
                if ( 'message' === ( $item['type'] ?? '' ) && ! empty( $item['content'] ) ) {
                    foreach ( $item['content'] as $content_piece ) {
                        if ( 'output_text' === ( $content_piece['type'] ?? '' ) && isset( $content_piece['text'] ) ) {
                            $text = (string) $content_piece['text'];
                            break 2;
                        }
                    }
                }
            }
        }

        $text = trim( $text );

        if ( '' === $text ) {
            return [
                'success' => false,
                'message' => __( 'Could not extract assistant text from Responses payload', 'rtbcb' ),
                'raw'     => sanitize_text_field( $response_body ),
            ];
        }

        return [
            'success'  => true,
            'message'  => __( 'API connection test successful', 'rtbcb' ),
            'response' => sanitize_text_field( $text ),
        ];
    }
}


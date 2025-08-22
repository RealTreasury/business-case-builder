<?php
/**
 * OpenAI API test utilities.
 *
 * @package RealTreasuryBusinessCaseBuilder
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

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
                'details' => __( 'API key must start with "sk-" and may contain letters, numbers, hyphens, and colons.', 'rtbcb' ),
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
     * Test completion endpoint.
     *
     * @param string $api_key API key.
     * @return array Test result.
     */
    private static function test_completion( $api_key ) {
        $endpoint = 'https://api.openai.com/v1/chat/completions';

        $test_prompt = 'Respond with exactly this JSON: {"test": "success", "message": "API is working correctly"}';

        $body = [
            'model' => get_option( 'rtbcb_mini_model', 'gpt-4o-mini' ),
            'messages' => [
                [
                    'role'    => 'system',
                    'content' => 'You are a test assistant. Respond only with the exact JSON requested.',
                ],
                [
                    'role'    => 'user',
                    'content' => $test_prompt,
                ],
            ],
            'temperature'     => 0,
            'max_tokens'      => 100,
            'response_format' => [ 'type' => 'json_object' ],
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
                'message' => __( 'Completion request failed', 'rtbcb' ),
                'details' => sanitize_text_field( $response->get_error_message() ),
            ];
        }

        $code          = wp_remote_retrieve_response_code( $response );
        $response_body = wp_remote_retrieve_body( $response );

        if ( 200 !== $code ) {
            $error_data    = json_decode( $response_body, true );
            $error_message = $error_data['error']['message'] ?? '';

            if ( false !== strpos( strtolower( $error_message ), 'response_format' ) ) {
                unset( $body['response_format'] );
                $args['body'] = wp_json_encode( $body );
                $response     = wp_remote_post( $endpoint, $args );

                if ( is_wp_error( $response ) ) {
                    return [
                        'success' => false,
                        'message' => __( 'Completion request failed', 'rtbcb' ),
                        'details' => sanitize_text_field( $response->get_error_message() ),
                    ];
                }

                $code          = wp_remote_retrieve_response_code( $response );
                $response_body = wp_remote_retrieve_body( $response );

                if ( 200 !== $code ) {
                    $error_data = json_decode( $response_body, true );
                    return [
                        'success' => false,
                        'message' => __( 'Completion API error', 'rtbcb' ),
                        'details' => sanitize_text_field( $error_data['error']['message'] ?? 'HTTP ' . $code ),
                        'http_code' => $code,
                    ];
                }
            } else {
                return [
                    'success' => false,
                    'message' => __( 'Completion API error', 'rtbcb' ),
                    'details' => sanitize_text_field( $error_message ?: 'HTTP ' . $code ),
                    'http_code' => $code,
                ];
            }
        }

        $data = json_decode( $response_body, true );

        if ( JSON_ERROR_NONE !== json_last_error() ) {
            return [
                'success' => false,
                'message' => __( 'Invalid JSON response', 'rtbcb' ),
                'details' => 'Response: ' . sanitize_text_field( $response_body ),
            ];
        }

        if ( isset( $data['choices'][0]['message']['content'] ) ) {
            $content = $data['choices'][0]['message']['content'];
            $parsed  = json_decode( $content, true );
            if ( JSON_ERROR_NONE !== json_last_error() ) {
                return [
                    'success' => false,
                    'message' => __( 'Invalid JSON response', 'rtbcb' ),
                    'details' => 'Response: ' . sanitize_text_field( $content ),
                ];
            }
        } else {
            $content = $response_body;
            $parsed  = $data;
        }

        if ( 'success' !== ( $parsed['test'] ?? '' ) ) {
            return [
                'success' => false,
                'message' => __( 'Unexpected response content', 'rtbcb' ),
                'details' => 'Response: ' . sanitize_text_field( $content ),
            ];
        }

        return [
            'success'  => true,
            'message'  => __( 'Completion test successful', 'rtbcb' ),
            'response' => $content,
        ];
    }
}


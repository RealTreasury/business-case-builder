<?php
defined( 'ABSPATH' ) || exit;

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
     * @return array Test result.
     */
    public static function test_connection( $api_key = null ) {
        $api_key = $api_key ?: rtbcb_get_openai_api_key();

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

        return self::test_completion( $api_key );
    }

    /**
     * Test API with a simple completion request.
     *
     * @param string $api_key API key.
     * @return array Test result.
     */
    private static function test_completion( $api_key ) {
        $model = get_option( 'rtbcb_mini_model', rtbcb_get_default_model( 'mini' ) );

        $min_tokens = intval( get_option( 'rtbcb_gpt5_min_output_tokens', 256 ) );

        $body = [
            'model'             => rtbcb_normalize_model_name( $model ),
            'input'             => 'Test connection - respond with exactly: "API connection successful"',
            'max_output_tokens' => max( 256, $min_tokens ),
        ];

        if ( rtbcb_model_supports_temperature( $model ) ) {
            $body['temperature'] = 0.1;
        }

        $timeout = rtbcb_get_api_timeout();

        $args = [
            'headers' => [
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json',
            ],
            'body'    => wp_json_encode( $body ),
            'timeout' => $timeout,
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

        $parsed      = rtbcb_parse_gpt5_response( $response );
        $output_text = $parsed['output_text'];

        if ( empty( $output_text ) ) {
            return [
                'success' => false,
                'message' => __( 'Empty response from API.', 'rtbcb' ),
                'details' => __( 'The API returned an empty response.', 'rtbcb' ),
            ];
        }

        return [
            'success' => true,
            'message' => __( 'Connection successful.', 'rtbcb' ),
            'response' => $output_text,
            'details' => sprintf( __( 'Model: %s, Response length: %d characters', 'rtbcb' ),
                $model, strlen( $output_text ) ),
        ];
    }
}


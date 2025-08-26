<?php
/**
 * Modern OpenAI API Client
 * 
 * Clean, secure, and efficient OpenAI API integration with comprehensive error handling
 * 
 * @package RealTreasuryBusinessCaseBuilder
 * @since 2.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * OpenAI API Client Class
 * 
 * Handles all communication with OpenAI API with proper error handling,
 * rate limiting, and security measures.
 */
class RTBCB_OpenAI_Client {
    
    /**
     * API endpoint base URL
     */
    const API_BASE_URL = 'https://api.openai.com/v1';
    
    /**
     * Default timeout for API requests
     */
    const DEFAULT_TIMEOUT = 60;
    
    /**
     * Maximum retries for failed requests
     */
    const MAX_RETRIES = 3;
    
    /**
     * API key for authentication
     * 
     * @var string
     */
    private $api_key;
    
    /**
     * Request timeout in seconds
     * 
     * @var int
     */
    private $timeout;
    
    /**
     * Constructor
     * 
     * @param string $api_key OpenAI API key
     * @param int    $timeout Request timeout in seconds
     */
    public function __construct( $api_key = null, $timeout = self::DEFAULT_TIMEOUT ) {
        $this->api_key = $api_key ?: get_option( 'rtbcb_openai_api_key', '' );
        $this->timeout = absint( $timeout );
        
        if ( $this->timeout < 10 ) {
            $this->timeout = self::DEFAULT_TIMEOUT;
        }
    }
    
    /**
     * Test API connection
     * 
     * @return array Connection test results
     */
    public function test_connection() {
        if ( empty( $this->api_key ) ) {
            return array(
                'success' => false,
                'error' => 'missing_api_key',
                'message' => __( 'OpenAI API key not configured', 'rtbcb' )
            );
        }
        
        if ( ! $this->validate_api_key_format( $this->api_key ) ) {
            return array(
                'success' => false,
                'error' => 'invalid_api_key_format',
                'message' => __( 'Invalid API key format', 'rtbcb' )
            );
        }
        
        $response = $this->make_request( 'GET', '/models' );
        
        if ( is_wp_error( $response ) ) {
            return array(
                'success' => false,
                'error' => $response->get_error_code(),
                'message' => $response->get_error_message()
            );
        }
        
        $models = isset( $response['data'] ) ? $response['data'] : array();
        
        return array(
            'success' => true,
            'message' => sprintf( __( 'Connection successful. %d models available.', 'rtbcb' ), count( $models ) ),
            'models' => $models
        );
    }
    
    /**
     * Generate text completion
     * 
     * @param string $prompt The input prompt
     * @param array  $options Additional options for the request
     * @return string|WP_Error Generated text or error
     */
    public function generate_completion( $prompt, $options = array() ) {
        if ( empty( $prompt ) ) {
            return new WP_Error( 'empty_prompt', __( 'Prompt cannot be empty', 'rtbcb' ) );
        }
        
        $defaults = array(
            'model' => 'gpt-4o-mini',
            'max_tokens' => 2000,
            'temperature' => 0.7,
            'top_p' => 1.0,
            'frequency_penalty' => 0,
            'presence_penalty' => 0
        );
        
        $options = wp_parse_args( $options, $defaults );
        
        $data = array(
            'model' => sanitize_text_field( $options['model'] ),
            'messages' => array(
                array(
                    'role' => 'user',
                    'content' => sanitize_textarea_field( $prompt )
                )
            ),
            'max_tokens' => absint( $options['max_tokens'] ),
            'temperature' => floatval( $options['temperature'] ),
            'top_p' => floatval( $options['top_p'] ),
            'frequency_penalty' => floatval( $options['frequency_penalty'] ),
            'presence_penalty' => floatval( $options['presence_penalty'] )
        );
        
        $response = $this->make_request( 'POST', '/chat/completions', $data );
        
        if ( is_wp_error( $response ) ) {
            return $response;
        }
        
        if ( ! isset( $response['choices'][0]['message']['content'] ) ) {
            return new WP_Error( 'invalid_response', __( 'Invalid API response format', 'rtbcb' ) );
        }
        
        return $response['choices'][0]['message']['content'];
    }
    
    /**
     * Make HTTP request to OpenAI API
     * 
     * @param string $method HTTP method
     * @param string $endpoint API endpoint
     * @param array  $data Request data
     * @return array|WP_Error Response data or error
     */
    private function make_request( $method, $endpoint, $data = null ) {
        if ( empty( $this->api_key ) ) {
            return new WP_Error( 'missing_api_key', __( 'OpenAI API key not configured', 'rtbcb' ) );
        }
        
        $url = self::API_BASE_URL . $endpoint;
        $headers = array(
            'Authorization' => 'Bearer ' . $this->api_key,
            'Content-Type' => 'application/json',
            'User-Agent' => 'RealTreasury-BusinessCaseBuilder/' . RTBCB_VERSION
        );
        
        $args = array(
            'method' => strtoupper( $method ),
            'headers' => $headers,
            'timeout' => $this->timeout,
            'sslverify' => true
        );
        
        if ( $data !== null ) {
            $args['body'] = wp_json_encode( $data );
        }
        
        $response = wp_remote_request( $url, $args );
        
        if ( is_wp_error( $response ) ) {
            return new WP_Error(
                'api_request_failed',
                sprintf( __( 'API request failed: %s', 'rtbcb' ), $response->get_error_message() )
            );
        }
        
        $status_code = wp_remote_retrieve_response_code( $response );
        $body = wp_remote_retrieve_body( $response );
        
        // Handle HTTP errors
        if ( $status_code >= 400 ) {
            return $this->handle_api_error( $status_code, $body );
        }
        
        $decoded = json_decode( $body, true );
        
        if ( json_last_error() !== JSON_ERROR_NONE ) {
            return new WP_Error(
                'json_decode_error',
                sprintf( __( 'Failed to decode API response: %s', 'rtbcb' ), json_last_error_msg() )
            );
        }
        
        return $decoded;
    }
    
    /**
     * Handle API error responses
     * 
     * @param int    $status_code HTTP status code
     * @param string $body Response body
     * @return WP_Error Error object
     */
    private function handle_api_error( $status_code, $body ) {
        $decoded = json_decode( $body, true );
        
        $error_message = '';
        $error_code = 'api_error';
        
        if ( isset( $decoded['error']['message'] ) ) {
            $error_message = sanitize_text_field( $decoded['error']['message'] );
        }
        
        switch ( $status_code ) {
            case 401:
                $error_code = 'unauthorized';
                $error_message = $error_message ?: __( 'Invalid API key', 'rtbcb' );
                break;
                
            case 429:
                $error_code = 'rate_limit_exceeded';
                $error_message = $error_message ?: __( 'Rate limit exceeded', 'rtbcb' );
                break;
                
            case 500:
            case 502:
            case 503:
            case 504:
                $error_code = 'server_error';
                $error_message = $error_message ?: __( 'OpenAI service temporarily unavailable', 'rtbcb' );
                break;
                
            default:
                $error_message = $error_message ?: sprintf( __( 'API error (HTTP %d)', 'rtbcb' ), $status_code );
        }
        
        return new WP_Error( $error_code, $error_message );
    }
    
    /**
     * Validate API key format
     * 
     * @param string $api_key API key to validate
     * @return bool True if format is valid
     */
    private function validate_api_key_format( $api_key ) {
        return preg_match( '/^sk-[a-zA-Z0-9]{48,}$/', $api_key );
    }
}
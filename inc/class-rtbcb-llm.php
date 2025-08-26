<?php
/**
 * Modern LLM Service - Rebuilt from Scratch with Best-in-Class Practices
 *
 * This file has been completely rebuilt from scratch to implement:
 * - Modern PHP patterns and dependency injection
 * - Comprehensive error handling and logging
 * - Rate limiting and performance optimization
 * - Security-first design with input validation
 * - Clean separation of concerns
 * - Enterprise-grade architecture
 *
 * @package RealTreasuryBusinessCaseBuilder
 * @since 2.1.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit( 'Direct access not permitted.' );
}

/**
 * Modern LLM Service Implementation
 * 
 * Provides AI-powered business case generation with enterprise-grade reliability.
 * Implements dependency injection, comprehensive error handling, and performance optimization.
 */
final class RTBCB_LLM {
    
    /**
     * API client instance
     * 
     * @var RTBCB_OpenAI_Client
     */
    private $api_client;
    
    /**
     * Error handler instance
     * 
     * @var RTBCB_Error_Handler
     */
    private $error_handler;
    
    /**
     * Performance monitor
     * 
     * @var RTBCB_Performance_Monitor
     */
    private $performance_monitor;
    
    /**
     * Configuration settings
     * 
     * @var array
     */
    private $config;
    
    /**
     * Request context cache
     * 
     * @var array
     */
    private $context_cache = array();
    
    /**
     * Rate limiting data
     * 
     * @var array
     */
    private $rate_limits = array();
    
    /**
     * Default model configurations
     */
    private const DEFAULT_MODELS = array(
        'mini'         => 'gpt-4o-mini',
        'standard'     => 'gpt-4o',
        'advanced'     => 'gpt-4o',
        'enterprise'   => 'gpt-4o'
    );
    
    /**
     * Request timeout settings (in seconds)
     */
    private const TIMEOUTS = array(
        'mini'         => 30,
        'standard'     => 60,
        'advanced'     => 90,
        'enterprise'   => 120
    );
    
    /**
     * Rate limit settings (requests per minute)
     */
    private const RATE_LIMITS = array(
        'mini'         => 100,
        'standard'     => 50,
        'advanced'     => 30,
        'enterprise'   => 20
    );
    
    /**
     * Constructor with dependency injection
     * 
     * @param RTBCB_OpenAI_Client|null     $api_client         Optional API client injection
     * @param RTBCB_Error_Handler|null     $error_handler      Optional error handler injection
     * @param RTBCB_Performance_Monitor|null $performance_monitor Optional performance monitor injection
     */
    public function __construct( 
        RTBCB_OpenAI_Client $api_client = null,
        RTBCB_Error_Handler $error_handler = null,
        RTBCB_Performance_Monitor $performance_monitor = null
    ) {
        $this->api_client = $api_client ?: new RTBCB_OpenAI_Client();
        $this->error_handler = $error_handler ?: new RTBCB_Error_Handler();
        $this->performance_monitor = $performance_monitor ?: new RTBCB_Performance_Monitor();
        
        $this->load_configuration();
        $this->initialize_rate_limiting();
    }
    
    /**
     * Load configuration from WordPress options
     */
    private function load_configuration() {
        $this->config = array_merge(
            array(
                'api_key'           => '',
                'organization_id'   => '',
                'default_model'     => 'gpt-4o-mini',
                'max_tokens'        => 4000,
                'temperature'       => 0.7,
                'timeout'           => 60,
                'retry_attempts'    => 3,
                'retry_delay'       => 1,
                'cache_enabled'     => true,
                'cache_ttl'         => 3600,
                'rate_limit_enabled' => true
            ),
            get_option( 'rtbcb_llm_config', array() )
        );
        
        // Sanitize configuration
        $this->config['api_key'] = sanitize_text_field( get_option( 'rtbcb_openai_api_key', '' ) );
        $this->config['organization_id'] = sanitize_text_field( $this->config['organization_id'] );
        $this->config['default_model'] = sanitize_text_field( $this->config['default_model'] );
        $this->config['max_tokens'] = absint( $this->config['max_tokens'] );
        $this->config['temperature'] = floatval( $this->config['temperature'] );
        $this->config['timeout'] = absint( $this->config['timeout'] );
        $this->config['retry_attempts'] = absint( $this->config['retry_attempts'] );
        $this->config['retry_delay'] = absint( $this->config['retry_delay'] );
        $this->config['cache_ttl'] = absint( $this->config['cache_ttl'] );
    }
    
    /**
     * Initialize rate limiting system
     */
    private function initialize_rate_limiting() {
        if ( ! $this->config['rate_limit_enabled'] ) {
            return;
        }
        
        $this->rate_limits = get_transient( 'rtbcb_llm_rate_limits' ) ?: array();
    }
    
    /**
     * Generate comprehensive business case analysis
     * 
     * @param array $user_inputs    Validated user inputs
     * @param array $roi_data       ROI calculation results
     * @param array $context_chunks Optional context from RAG system
     * @param string $model_tier    Model tier to use (mini, standard, advanced, enterprise)
     * 
     * @return array|WP_Error Business case analysis or error object
     */
    public function generate_business_case( array $user_inputs, array $roi_data, array $context_chunks = array(), string $model_tier = 'standard' ) {
        $start_time = microtime( true );
        
        try {
            // Validate inputs
            $validation_result = $this->validate_inputs( $user_inputs, $roi_data );
            if ( is_wp_error( $validation_result ) ) {
                return $validation_result;
            }
            
            // Check API key availability
            if ( empty( $this->config['api_key'] ) ) {
                return new WP_Error(
                    'rtbcb_no_api_key',
                    __( 'OpenAI API key not configured. Please configure your API key in the plugin settings.', 'rtbcb' ),
                    array( 'status' => 400 )
                );
            }
            
            // Check rate limits
            $rate_limit_check = $this->check_rate_limits( $model_tier );
            if ( is_wp_error( $rate_limit_check ) ) {
                return $rate_limit_check;
            }
            
            // Get model configuration
            $model_config = $this->get_model_config( $model_tier );
            
            // Build prompt
            $prompt = $this->build_business_case_prompt( $user_inputs, $roi_data, $context_chunks );
            
            // Check cache
            $cache_key = $this->generate_cache_key( $prompt, $model_config );
            if ( $this->config['cache_enabled'] ) {
                $cached_result = $this->get_cached_result( $cache_key );
                if ( $cached_result !== false ) {
                    $this->performance_monitor->log_event( 'llm_cache_hit', array(
                        'model_tier' => $model_tier,
                        'cache_key' => $cache_key
                    ));
                    return $cached_result;
                }
            }
            
            // Make API request with retry logic
            $response = $this->make_api_request_with_retry( $prompt, $model_config );
            
            if ( is_wp_error( $response ) ) {
                return $response;
            }
            
            // Parse and validate response
            $parsed_response = $this->parse_business_case_response( $response, $user_inputs );
            
            if ( is_wp_error( $parsed_response ) ) {
                return $parsed_response;
            }
            
            // Cache successful result
            if ( $this->config['cache_enabled'] ) {
                $this->cache_result( $cache_key, $parsed_response );
            }
            
            // Update rate limits
            $this->update_rate_limits( $model_tier );
            
            // Log performance metrics
            $execution_time = microtime( true ) - $start_time;
            $this->performance_monitor->log_event( 'llm_business_case_generated', array(
                'model_tier' => $model_tier,
                'execution_time' => $execution_time,
                'input_tokens' => strlen( $prompt ) / 4, // Rough estimate
                'success' => true
            ));
            
            return $parsed_response;
            
        } catch ( Exception $e ) {
            $this->error_handler->log_error(
                'LLM business case generation failed: ' . $e->getMessage(),
                RTBCB_Error_Handler::ERROR_LEVEL_CRITICAL,
                array(
                    'user_inputs' => $user_inputs,
                    'model_tier' => $model_tier,
                    'exception' => $e->getTraceAsString()
                ),
                'LLM_BUSINESS_CASE_GENERATION'
            );
            
            return new WP_Error(
                'rtbcb_llm_exception',
                __( 'An unexpected error occurred while generating the business case. Please try again later.', 'rtbcb' ),
                array( 'status' => 500 )
            );
        }
    }
    
    /**
     * Generate industry-specific commentary
     * 
     * @param string $industry     Industry identifier
     * @param string $model_tier   Model tier to use
     * 
     * @return string|WP_Error Commentary text or error object
     */
    public function generate_industry_commentary( string $industry, string $model_tier = 'mini' ) {
        try {
            // Validate industry
            $industry = sanitize_text_field( $industry );
            if ( empty( $industry ) ) {
                return new WP_Error(
                    'rtbcb_invalid_industry',
                    __( 'Industry parameter is required.', 'rtbcb' ),
                    array( 'status' => 400 )
                );
            }
            
            // Check API key
            if ( empty( $this->config['api_key'] ) ) {
                return new WP_Error(
                    'rtbcb_no_api_key',
                    __( 'OpenAI API key not configured.', 'rtbcb' ),
                    array( 'status' => 400 )
                );
            }
            
            // Check rate limits
            $rate_limit_check = $this->check_rate_limits( $model_tier );
            if ( is_wp_error( $rate_limit_check ) ) {
                return $rate_limit_check;
            }
            
            // Build industry commentary prompt
            $prompt = $this->build_industry_commentary_prompt( $industry );
            
            // Get model configuration
            $model_config = $this->get_model_config( $model_tier );
            $model_config['max_tokens'] = 500; // Shorter response for commentary
            
            // Check cache
            $cache_key = $this->generate_cache_key( $prompt, $model_config );
            if ( $this->config['cache_enabled'] ) {
                $cached_result = $this->get_cached_result( $cache_key );
                if ( $cached_result !== false ) {
                    return $cached_result;
                }
            }
            
            // Make API request
            $response = $this->make_api_request_with_retry( $prompt, $model_config );
            
            if ( is_wp_error( $response ) ) {
                return $response;
            }
            
            // Extract and sanitize commentary
            $commentary = $this->extract_commentary_from_response( $response );
            
            if ( is_wp_error( $commentary ) ) {
                return $commentary;
            }
            
            // Cache result
            if ( $this->config['cache_enabled'] ) {
                $this->cache_result( $cache_key, $commentary );
            }
            
            // Update rate limits
            $this->update_rate_limits( $model_tier );
            
            return $commentary;
            
        } catch ( Exception $e ) {
            $this->error_handler->log_error(
                'LLM industry commentary generation failed: ' . $e->getMessage(),
                RTBCB_Error_Handler::ERROR_LEVEL_ERROR,
                array(
                    'industry' => $industry,
                    'model_tier' => $model_tier,
                    'exception' => $e->getTraceAsString()
                ),
                'LLM_INDUSTRY_COMMENTARY'
            );
            
            return new WP_Error(
                'rtbcb_llm_commentary_failed',
                __( 'Unable to generate industry commentary at this time. Please try again later.', 'rtbcb' ),
                array( 'status' => 500 )
            );
        }
    }
    
    /**
     * Validate inputs for business case generation
     * 
     * @param array $user_inputs User inputs
     * @param array $roi_data    ROI data
     * 
     * @return true|WP_Error Validation result
     */
    private function validate_inputs( array $user_inputs, array $roi_data ) {
        // Required fields validation
        $required_fields = array( 'company_name', 'industry', 'company_size' );
        
        foreach ( $required_fields as $field ) {
            if ( empty( $user_inputs[ $field ] ) ) {
                return new WP_Error(
                    'rtbcb_missing_required_field',
                    sprintf( __( 'Required field "%s" is missing.', 'rtbcb' ), $field ),
                    array( 'status' => 400, 'field' => $field )
                );
            }
        }
        
        // ROI data validation
        if ( empty( $roi_data ) || ! is_array( $roi_data ) ) {
            return new WP_Error(
                'rtbcb_invalid_roi_data',
                __( 'Valid ROI data is required.', 'rtbcb' ),
                array( 'status' => 400 )
            );
        }
        
        return true;
    }
    
    /**
     * Check rate limits for model tier
     * 
     * @param string $model_tier Model tier
     * 
     * @return true|WP_Error Rate limit check result
     */
    private function check_rate_limits( string $model_tier ) {
        if ( ! $this->config['rate_limit_enabled'] ) {
            return true;
        }
        
        $current_minute = floor( time() / 60 );
        $rate_limit = self::RATE_LIMITS[ $model_tier ] ?? self::RATE_LIMITS['standard'];
        
        if ( ! isset( $this->rate_limits[ $model_tier ] ) ) {
            $this->rate_limits[ $model_tier ] = array();
        }
        
        // Clean old entries
        $this->rate_limits[ $model_tier ] = array_filter(
            $this->rate_limits[ $model_tier ],
            function( $timestamp ) use ( $current_minute ) {
                return floor( $timestamp / 60 ) === $current_minute;
            }
        );
        
        // Check if rate limit exceeded
        if ( count( $this->rate_limits[ $model_tier ] ) >= $rate_limit ) {
            return new WP_Error(
                'rtbcb_rate_limit_exceeded',
                sprintf( 
                    __( 'Rate limit exceeded for %s model. Please wait a moment before trying again.', 'rtbcb' ),
                    $model_tier
                ),
                array( 'status' => 429, 'retry_after' => 60 )
            );
        }
        
        return true;
    }
    
    /**
     * Get model configuration for tier
     * 
     * @param string $model_tier Model tier
     * 
     * @return array Model configuration
     */
    private function get_model_config( string $model_tier ) {
        $model_tier = sanitize_key( $model_tier );
        
        if ( ! array_key_exists( $model_tier, self::DEFAULT_MODELS ) ) {
            $model_tier = 'standard';
        }
        
        return array(
            'model'       => get_option( "rtbcb_{$model_tier}_model", self::DEFAULT_MODELS[ $model_tier ] ),
            'max_tokens'  => $this->config['max_tokens'],
            'temperature' => $this->config['temperature'],
            'timeout'     => self::TIMEOUTS[ $model_tier ] ?? self::TIMEOUTS['standard']
        );
    }
    
    /**
     * Build comprehensive business case prompt
     * 
     * @param array $user_inputs    User inputs
     * @param array $roi_data       ROI data
     * @param array $context_chunks Context chunks from RAG
     * 
     * @return string Formatted prompt
     */
    private function build_business_case_prompt( array $user_inputs, array $roi_data, array $context_chunks ) {
        $company_name = sanitize_text_field( $user_inputs['company_name'] ?? '' );
        $industry = sanitize_text_field( $user_inputs['industry'] ?? '' );
        $company_size = sanitize_text_field( $user_inputs['company_size'] ?? '' );
        $pain_points = array_map( 'sanitize_text_field', (array) ( $user_inputs['pain_points'] ?? array() ) );
        
        $prompt = "Create a comprehensive treasury technology business case for {$company_name}, a {$company_size} company in the {$industry} industry.\n\n";
        
        // Add pain points context
        if ( ! empty( $pain_points ) ) {
            $prompt .= "Current challenges: " . implode( ', ', $pain_points ) . "\n\n";
        }
        
        // Add ROI summary
        if ( ! empty( $roi_data ) ) {
            $prompt .= "ROI Analysis Summary:\n";
            foreach ( $roi_data as $scenario => $data ) {
                if ( isset( $data['net_benefit'], $data['roi_percentage'] ) ) {
                    $prompt .= "- {$scenario}: Net Benefit \${$data['net_benefit']}, ROI {$data['roi_percentage']}%\n";
                }
            }
            $prompt .= "\n";
        }
        
        // Add context from RAG system
        if ( ! empty( $context_chunks ) ) {
            $prompt .= "Industry Context:\n";
            foreach ( $context_chunks as $chunk ) {
                $prompt .= "- " . sanitize_text_field( $chunk ) . "\n";
            }
            $prompt .= "\n";
        }
        
        $prompt .= "Provide a detailed business case in JSON format with the following structure:\n";
        $prompt .= "{\n";
        $prompt .= '  "executive_summary": {' . "\n";
        $prompt .= '    "strategic_positioning": "string",' . "\n";
        $prompt .= '    "business_case_strength": "string",' . "\n";
        $prompt .= '    "key_value_drivers": ["string"],' . "\n";
        $prompt .= '    "executive_recommendation": "string"' . "\n";
        $prompt .= "  },\n";
        $prompt .= '  "operational_analysis": {' . "\n";
        $prompt .= '    "current_state_assessment": "string",' . "\n";
        $prompt .= '    "process_improvements": ["string"],' . "\n";
        $prompt .= '    "efficiency_gains": "string"' . "\n";
        $prompt .= "  },\n";
        $prompt .= '  "industry_insights": {' . "\n";
        $prompt .= '    "sector_trends": "string",' . "\n";
        $prompt .= '    "competitive_benchmarks": "string",' . "\n";
        $prompt .= '    "regulatory_considerations": "string"' . "\n";
        $prompt .= "  },\n";
        $prompt .= '  "risk_assessment": {' . "\n";
        $prompt .= '    "implementation_risks": ["string"],' . "\n";
        $prompt .= '    "mitigation_strategies": ["string"],' . "\n";
        $prompt .= '    "success_factors": ["string"]' . "\n";
        $prompt .= "  }\n";
        $prompt .= "}\n\n";
        $prompt .= "Ensure all content is professional, industry-specific, and actionable.";
        
        return $prompt;
    }
    
    /**
     * Build industry commentary prompt
     * 
     * @param string $industry Industry identifier
     * 
     * @return string Formatted prompt
     */
    private function build_industry_commentary_prompt( string $industry ) {
        return "Provide a brief, professional treasury industry commentary for the {$industry} industry. " .
               "Focus on current market trends, challenges, and opportunities related to treasury technology adoption. " .
               "Keep the response to 2-3 sentences, professional tone, and industry-specific insights.";
    }
    
    /**
     * Make API request with retry logic
     * 
     * @param string $prompt       The prompt to send
     * @param array  $model_config Model configuration
     * 
     * @return array|WP_Error API response or error
     */
    private function make_api_request_with_retry( string $prompt, array $model_config ) {
        $attempts = 0;
        $max_attempts = $this->config['retry_attempts'];
        
        while ( $attempts < $max_attempts ) {
            $attempts++;
            
            $response = $this->api_client->chat_completion( array(
                'model'       => $model_config['model'],
                'messages'    => array(
                    array(
                        'role'    => 'user',
                        'content' => $prompt
                    )
                ),
                'max_tokens'  => $model_config['max_tokens'],
                'temperature' => $model_config['temperature'],
                'timeout'     => $model_config['timeout']
            ));
            
            if ( ! is_wp_error( $response ) ) {
                return $response;
            }
            
            // Log retry attempt
            $this->error_handler->log_error(
                "LLM API request attempt {$attempts} failed: " . $response->get_error_message(),
                RTBCB_Error_Handler::ERROR_LEVEL_WARNING,
                array(
                    'attempt' => $attempts,
                    'max_attempts' => $max_attempts,
                    'model' => $model_config['model']
                ),
                'LLM_API_RETRY'
            );
            
            // If not the last attempt, wait before retrying
            if ( $attempts < $max_attempts ) {
                sleep( $this->config['retry_delay'] * $attempts );
            }
        }
        
        // All attempts failed
        return new WP_Error(
            'rtbcb_llm_api_failed',
            sprintf( 
                __( 'API request failed after %d attempts. Please try again later.', 'rtbcb' ),
                $max_attempts
            ),
            array( 'status' => 503 )
        );
    }
    
    /**
     * Parse business case response from API
     * 
     * @param array $response    API response
     * @param array $user_inputs User inputs for context
     * 
     * @return array|WP_Error Parsed business case or error
     */
    private function parse_business_case_response( array $response, array $user_inputs ) {
        if ( ! isset( $response['choices'][0]['message']['content'] ) ) {
            return new WP_Error(
                'rtbcb_invalid_api_response',
                __( 'Invalid response format from API.', 'rtbcb' ),
                array( 'status' => 502 )
            );
        }
        
        $content = $response['choices'][0]['message']['content'];
        
        // Try to extract JSON from response
        $json_start = strpos( $content, '{' );
        $json_end = strrpos( $content, '}' );
        
        if ( $json_start === false || $json_end === false ) {
            return new WP_Error(
                'rtbcb_no_json_in_response',
                __( 'No valid JSON found in API response.', 'rtbcb' ),
                array( 'status' => 502 )
            );
        }
        
        $json_content = substr( $content, $json_start, $json_end - $json_start + 1 );
        $parsed_json = json_decode( $json_content, true );
        
        if ( json_last_error() !== JSON_ERROR_NONE ) {
            return new WP_Error(
                'rtbcb_json_parse_error',
                __( 'Failed to parse JSON response from API.', 'rtbcb' ),
                array( 'status' => 502, 'json_error' => json_last_error_msg() )
            );
        }
        
        // Validate and sanitize parsed response
        return $this->validate_and_sanitize_business_case( $parsed_json, $user_inputs );
    }
    
    /**
     * Validate and sanitize business case data
     * 
     * @param array $data        Parsed JSON data
     * @param array $user_inputs User inputs for context
     * 
     * @return array|WP_Error Sanitized business case or error
     */
    private function validate_and_sanitize_business_case( array $data, array $user_inputs ) {
        $required_sections = array( 'executive_summary', 'operational_analysis', 'industry_insights' );
        
        foreach ( $required_sections as $section ) {
            if ( ! isset( $data[ $section ] ) || ! is_array( $data[ $section ] ) ) {
                return new WP_Error(
                    'rtbcb_missing_section',
                    sprintf( __( 'Missing required section: %s', 'rtbcb' ), $section ),
                    array( 'status' => 502, 'section' => $section )
                );
            }
        }
        
        // Sanitize and structure response
        $sanitized = array(
            'company_name'      => sanitize_text_field( $user_inputs['company_name'] ?? '' ),
            'analysis_date'     => current_time( 'Y-m-d H:i:s' ),
            'generated_by'      => 'RTBCB AI Analysis Engine',
            'executive_summary' => array(
                'strategic_positioning'    => sanitize_textarea_field( $data['executive_summary']['strategic_positioning'] ?? '' ),
                'business_case_strength'   => sanitize_textarea_field( $data['executive_summary']['business_case_strength'] ?? '' ),
                'key_value_drivers'        => array_map( 'sanitize_text_field', (array) ( $data['executive_summary']['key_value_drivers'] ?? array() ) ),
                'executive_recommendation' => sanitize_textarea_field( $data['executive_summary']['executive_recommendation'] ?? '' )
            ),
            'operational_analysis' => array(
                'current_state_assessment' => sanitize_textarea_field( $data['operational_analysis']['current_state_assessment'] ?? '' ),
                'process_improvements'     => array_map( 'sanitize_text_field', (array) ( $data['operational_analysis']['process_improvements'] ?? array() ) ),
                'efficiency_gains'         => sanitize_textarea_field( $data['operational_analysis']['efficiency_gains'] ?? '' )
            ),
            'industry_insights' => array(
                'sector_trends'             => sanitize_textarea_field( $data['industry_insights']['sector_trends'] ?? '' ),
                'competitive_benchmarks'    => sanitize_textarea_field( $data['industry_insights']['competitive_benchmarks'] ?? '' ),
                'regulatory_considerations' => sanitize_textarea_field( $data['industry_insights']['regulatory_considerations'] ?? '' )
            ),
            'risk_assessment' => array(
                'implementation_risks'  => array_map( 'sanitize_text_field', (array) ( $data['risk_assessment']['implementation_risks'] ?? array() ) ),
                'mitigation_strategies' => array_map( 'sanitize_text_field', (array) ( $data['risk_assessment']['mitigation_strategies'] ?? array() ) ),
                'success_factors'       => array_map( 'sanitize_text_field', (array) ( $data['risk_assessment']['success_factors'] ?? array() ) )
            )
        );
        
        return $sanitized;
    }
    
    /**
     * Extract commentary from API response
     * 
     * @param array $response API response
     * 
     * @return string|WP_Error Commentary text or error
     */
    private function extract_commentary_from_response( array $response ) {
        if ( ! isset( $response['choices'][0]['message']['content'] ) ) {
            return new WP_Error(
                'rtbcb_invalid_commentary_response',
                __( 'Invalid commentary response format from API.', 'rtbcb' ),
                array( 'status' => 502 )
            );
        }
        
        $commentary = trim( $response['choices'][0]['message']['content'] );
        
        if ( empty( $commentary ) ) {
            return new WP_Error(
                'rtbcb_empty_commentary',
                __( 'Empty commentary received from API.', 'rtbcb' ),
                array( 'status' => 502 )
            );
        }
        
        return sanitize_textarea_field( $commentary );
    }
    
    /**
     * Generate cache key for request
     * 
     * @param string $prompt       The prompt
     * @param array  $model_config Model configuration
     * 
     * @return string Cache key
     */
    private function generate_cache_key( string $prompt, array $model_config ) {
        $cache_data = array(
            'prompt' => $prompt,
            'model' => $model_config['model'],
            'max_tokens' => $model_config['max_tokens'],
            'temperature' => $model_config['temperature']
        );
        
        return 'rtbcb_llm_' . md5( wp_json_encode( $cache_data ) );
    }
    
    /**
     * Get cached result
     * 
     * @param string $cache_key Cache key
     * 
     * @return mixed|false Cached result or false if not found
     */
    private function get_cached_result( string $cache_key ) {
        return get_transient( $cache_key );
    }
    
    /**
     * Cache result
     * 
     * @param string $cache_key Cache key
     * @param mixed  $result    Result to cache
     */
    private function cache_result( string $cache_key, $result ) {
        set_transient( $cache_key, $result, $this->config['cache_ttl'] );
    }
    
    /**
     * Update rate limits
     * 
     * @param string $model_tier Model tier
     */
    private function update_rate_limits( string $model_tier ) {
        if ( ! $this->config['rate_limit_enabled'] ) {
            return;
        }
        
        $this->rate_limits[ $model_tier ][] = time();
        set_transient( 'rtbcb_llm_rate_limits', $this->rate_limits, 300 ); // 5 minutes
    }
    
    /**
     * Get service health status
     * 
     * @return array Health status information
     */
    public function get_health_status() {
        $status = array(
            'api_key_configured' => ! empty( $this->config['api_key'] ),
            'cache_enabled' => $this->config['cache_enabled'],
            'rate_limiting_enabled' => $this->config['rate_limit_enabled'],
            'default_model' => $this->config['default_model'],
            'last_error' => $this->error_handler->get_last_error( 'LLM' ),
            'rate_limits' => array()
        );
        
        // Add rate limit status for each tier
        foreach ( self::DEFAULT_MODELS as $tier => $model ) {
            $current_requests = isset( $this->rate_limits[ $tier ] ) ? count( $this->rate_limits[ $tier ] ) : 0;
            $limit = self::RATE_LIMITS[ $tier ] ?? self::RATE_LIMITS['standard'];
            
            $status['rate_limits'][ $tier ] = array(
                'current_requests' => $current_requests,
                'limit' => $limit,
                'remaining' => max( 0, $limit - $current_requests )
            );
        }
        
        return $status;
    }
    
    /**
     * Clear all caches
     * 
     * @return bool Success status
     */
    public function clear_cache() {
        global $wpdb;
        
        // Clear all LLM-related transients
        $deleted = $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
                '_transient_rtbcb_llm_%'
            )
        );
        
        // Clear timeout transients too
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
                '_transient_timeout_rtbcb_llm_%'
            )
        );
        
        $this->context_cache = array();
        
        return $deleted !== false;
    }
    
    /**
     * Update configuration
     * 
     * @param array $new_config New configuration settings
     * 
     * @return bool Success status
     */
    public function update_config( array $new_config ) {
        $this->config = array_merge( $this->config, $new_config );
        
        // Save to WordPress options
        $saved = update_option( 'rtbcb_llm_config', $this->config );
        
        if ( $saved ) {
            // Clear cache when configuration changes
            $this->clear_cache();
        }
        
        return $saved;
    }
}
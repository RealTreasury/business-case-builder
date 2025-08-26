<?php
/**
 * Modern Router Service - Rebuilt from Scratch with Best-in-Class Patterns
 *
 * This file has been completely rebuilt from scratch to implement:
 * - Modern service orchestration patterns
 * - Comprehensive error handling and validation
 * - Clean dependency injection architecture
 * - Performance optimization and monitoring
 * - Security-first design with proper sanitization
 * - Enterprise-grade request handling
 *
 * @package RealTreasuryBusinessCaseBuilder
 * @since 2.1.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit( 'Direct access not permitted.' );
}

/**
 * Modern Router Service Implementation
 * 
 * Orchestrates business case generation with clean service coordination.
 * Implements enterprise patterns for scalable, maintainable architecture.
 */
final class RTBCB_Router {
    
    /**
     * Service container
     * 
     * @var array
     */
    private $services = array();
    
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
     * Request validation rules
     * 
     * @var array
     */
    private $validation_rules = array();
    
    /**
     * Processing context
     * 
     * @var array
     */
    private $context = array();
    
    /**
     * Constructor with dependency injection
     * 
     * @param RTBCB_Error_Handler|null     $error_handler      Optional error handler injection
     * @param RTBCB_Performance_Monitor|null $performance_monitor Optional performance monitor injection
     */
    public function __construct( 
        RTBCB_Error_Handler $error_handler = null,
        RTBCB_Performance_Monitor $performance_monitor = null
    ) {
        $this->error_handler = $error_handler ?? new RTBCB_Error_Handler();
        $this->performance_monitor = $performance_monitor ?? new RTBCB_Performance_Monitor();
        
        $this->initialize_services();
        $this->setup_validation_rules();
    }
    
    /**
     * Initialize service container with dependency injection
     */
    private function initialize_services() {
        // Core services
        $this->services['validator'] = new RTBCB_Validator();
        $this->services['calculator'] = new RTBCB_Calculator();
        $this->services['llm'] = new RTBCB_LLM( null, $this->error_handler, $this->performance_monitor );
        $this->services['rag'] = new RTBCB_RAG();
        $this->services['leads'] = new RTBCB_Leads();
        $this->services['db'] = new RTBCB_DB();
    }
    
    /**
     * Setup validation rules for different request types
     */
    private function setup_validation_rules() {
        $this->validation_rules = array(
            'business_case' => array(
                'required' => array( 'company_name', 'industry', 'company_size' ),
                'optional' => array( 'hours_reconciliation', 'hours_cash_positioning', 'num_banks', 'ftes', 'pain_points', 'report_type' ),
                'sanitization' => array(
                    'company_name' => 'sanitize_text_field',
                    'industry' => 'sanitize_text_field',
                    'company_size' => 'sanitize_text_field',
                    'hours_reconciliation' => 'floatval',
                    'hours_cash_positioning' => 'floatval',
                    'num_banks' => 'absint',
                    'ftes' => 'floatval',
                    'pain_points' => array( $this, 'sanitize_pain_points' ),
                    'report_type' => 'sanitize_key'
                )
            ),
            'industry_commentary' => array(
                'required' => array( 'industry' ),
                'optional' => array( 'model_tier' ),
                'sanitization' => array(
                    'industry' => 'sanitize_text_field',
                    'model_tier' => 'sanitize_key'
                )
            )
        );
    }
    
    /**
     * Handle business case form submission with comprehensive orchestration
     * 
     * @param string $report_type Optional report type override
     * 
     * @return void Outputs JSON response
     */
    public function handle_form_submission( string $report_type = 'comprehensive' ) {
        $start_time = microtime( true );
        
        try {
            // Security validation
            $security_check = $this->validate_security();
            if ( is_wp_error( $security_check ) ) {
                $this->send_error_response( $security_check );
                return;
            }
            
            // Request validation and sanitization
            $validated_data = $this->validate_and_sanitize_request( 'business_case' );
            if ( is_wp_error( $validated_data ) ) {
                $this->send_error_response( $validated_data );
                return;
            }
            
            // Set up processing context
            $this->setup_processing_context( $validated_data, $report_type );
            
            // Execute business case generation pipeline
            $result = $this->execute_business_case_pipeline( $validated_data );
            
            if ( is_wp_error( $result ) ) {
                $this->send_error_response( $result );
                return;
            }
            
            // Save lead data (if enabled)
            $this->save_lead_data( $validated_data, $result );
            
            // Log successful processing
            $execution_time = microtime( true ) - $start_time;
            $this->performance_monitor->log_event( 'router_business_case_success', array(
                'execution_time' => $execution_time,
                'report_type' => $this->context['report_type'],
                'company_size' => $validated_data['company_size'],
                'industry' => $validated_data['industry']
            ));
            
            // Send successful response
            $this->send_success_response( $result, $execution_time );
            
        } catch ( Exception $e ) {
            $this->error_handler->log_error(
                'Router form submission failed: ' . $e->getMessage(),
                RTBCB_Error_Handler::ERROR_LEVEL_CRITICAL,
                array(
                    'context' => $this->context,
                    'exception' => $e->getTraceAsString()
                ),
                'ROUTER_FORM_SUBMISSION'
            );
            
            $this->send_error_response( new WP_Error(
                'rtbcb_router_exception',
                __( 'An unexpected error occurred while processing your request. Please try again.', 'rtbcb' ),
                array( 'status' => 500 )
            ));
        }
    }
    
    /**
     * Handle industry commentary generation
     * 
     * @return void Outputs JSON response
     */
    public function handle_industry_commentary() {
        try {
            // Security validation
            $security_check = $this->validate_security();
            if ( is_wp_error( $security_check ) ) {
                $this->send_error_response( $security_check );
                return;
            }
            
            // Request validation
            $validated_data = $this->validate_and_sanitize_request( 'industry_commentary' );
            if ( is_wp_error( $validated_data ) ) {
                $this->send_error_response( $validated_data );
                return;
            }
            
            // Generate commentary
            $commentary = $this->services['llm']->generate_industry_commentary(
                $validated_data['industry'],
                $validated_data['model_tier'] ?? 'mini'
            );
            
            if ( is_wp_error( $commentary ) ) {
                $this->send_error_response( $commentary );
                return;
            }
            
            wp_send_json_success( array(
                'commentary' => $commentary,
                'industry' => $validated_data['industry'],
                'generated_at' => current_time( 'c' )
            ));
            
        } catch ( Exception $e ) {
            $this->error_handler->log_error(
                'Router industry commentary failed: ' . $e->getMessage(),
                RTBCB_Error_Handler::ERROR_LEVEL_ERROR,
                array( 'exception' => $e->getTraceAsString() ),
                'ROUTER_INDUSTRY_COMMENTARY'
            );
            
            $this->send_error_response( new WP_Error(
                'rtbcb_commentary_exception',
                __( 'Unable to generate industry commentary at this time.', 'rtbcb' ),
                array( 'status' => 500 )
            ));
        }
    }
    
    /**
     * Execute the complete business case generation pipeline
     * 
     * @param array $validated_data Validated input data
     * 
     * @return array|WP_Error Pipeline result or error
     */
    private function execute_business_case_pipeline( array $validated_data ) {
        $pipeline_start = microtime( true );
        
        // Step 1: ROI Calculations
        $roi_calculations = $this->execute_roi_calculations( $validated_data );
        if ( is_wp_error( $roi_calculations ) ) {
            return $roi_calculations;
        }
        
        // Step 2: RAG Context Generation (if enabled)
        $context_chunks = $this->execute_rag_processing( $validated_data );
        if ( is_wp_error( $context_chunks ) ) {
            // RAG failure is not fatal, continue with empty context
            $context_chunks = array();
            $this->error_handler->log_error(
                'RAG processing failed, continuing without context',
                RTBCB_Error_Handler::ERROR_LEVEL_WARNING,
                array( 'error' => $context_chunks->get_error_message() ),
                'ROUTER_RAG_FALLBACK'
            );
        }
        
        // Step 3: LLM Business Case Generation
        $business_case = $this->execute_llm_generation( $validated_data, $roi_calculations, $context_chunks );
        if ( is_wp_error( $business_case ) ) {
            return $business_case;
        }
        
        // Step 4: Response Assembly
        $assembled_response = $this->assemble_final_response( $validated_data, $roi_calculations, $business_case );
        
        $pipeline_time = microtime( true ) - $pipeline_start;
        $this->performance_monitor->log_event( 'router_pipeline_complete', array(
            'pipeline_time' => $pipeline_time,
            'roi_scenarios' => count( $roi_calculations ),
            'context_chunks' => count( $context_chunks ),
            'report_type' => $this->context['report_type']
        ));
        
        return $assembled_response;
    }
    
    /**
     * Execute ROI calculations
     * 
     * @param array $validated_data Input data
     * 
     * @return array|WP_Error ROI calculations or error
     */
    private function execute_roi_calculations( array $validated_data ) {
        try {
            $roi_start = microtime( true );
            
            $calculations = $this->services['calculator']->calculate_roi( $validated_data );
            
            if ( empty( $calculations ) || ! is_array( $calculations ) ) {
                return new WP_Error(
                    'rtbcb_roi_calculation_failed',
                    __( 'ROI calculations could not be completed.', 'rtbcb' ),
                    array( 'status' => 500 )
                );
            }
            
            $roi_time = microtime( true ) - $roi_start;
            $this->performance_monitor->log_event( 'router_roi_calculations', array(
                'execution_time' => $roi_time,
                'scenarios_calculated' => count( $calculations )
            ));
            
            return $calculations;
            
        } catch ( Exception $e ) {
            return new WP_Error(
                'rtbcb_roi_exception',
                __( 'ROI calculation failed due to an internal error.', 'rtbcb' ),
                array( 'status' => 500, 'exception' => $e->getMessage() )
            );
        }
    }
    
    /**
     * Execute RAG context processing
     * 
     * @param array $validated_data Input data
     * 
     * @return array|WP_Error Context chunks or error
     */
    private function execute_rag_processing( array $validated_data ) {
        try {
            $rag_start = microtime( true );
            
            // Build search query from validated data
            $search_query = $this->build_rag_search_query( $validated_data );
            
            $context_chunks = $this->services['rag']->search_context( $search_query, 5 );
            
            if ( is_wp_error( $context_chunks ) ) {
                return $context_chunks;
            }
            
            $rag_time = microtime( true ) - $rag_start;
            $this->performance_monitor->log_event( 'router_rag_processing', array(
                'execution_time' => $rag_time,
                'chunks_found' => count( $context_chunks ),
                'search_query' => $search_query
            ));
            
            return $context_chunks;
            
        } catch ( Exception $e ) {
            return new WP_Error(
                'rtbcb_rag_exception',
                __( 'Context processing failed.', 'rtbcb' ),
                array( 'status' => 500, 'exception' => $e->getMessage() )
            );
        }
    }
    
    /**
     * Execute LLM business case generation
     * 
     * @param array $validated_data   Input data
     * @param array $roi_calculations ROI results
     * @param array $context_chunks   RAG context
     * 
     * @return array|WP_Error Business case or error
     */
    private function execute_llm_generation( array $validated_data, array $roi_calculations, array $context_chunks ) {
        try {
            $llm_start = microtime( true );
            
            // Determine model tier based on report type
            $model_tier = $this->determine_model_tier( $this->context['report_type'] );
            
            $business_case = $this->services['llm']->generate_business_case(
                $validated_data,
                $roi_calculations,
                $context_chunks,
                $model_tier
            );
            
            if ( is_wp_error( $business_case ) ) {
                return $business_case;
            }
            
            $llm_time = microtime( true ) - $llm_start;
            $this->performance_monitor->log_event( 'router_llm_generation', array(
                'execution_time' => $llm_time,
                'model_tier' => $model_tier,
                'context_chunks_used' => count( $context_chunks )
            ));
            
            return $business_case;
            
        } catch ( Exception $e ) {
            return new WP_Error(
                'rtbcb_llm_generation_exception',
                __( 'Business case generation failed.', 'rtbcb' ),
                array( 'status' => 500, 'exception' => $e->getMessage() )
            );
        }
    }
    
    /**
     * Assemble final response with all components
     * 
     * @param array $validated_data   Input data
     * @param array $roi_calculations ROI results
     * @param array $business_case    LLM-generated business case
     * 
     * @return array Complete response data
     */
    private function assemble_final_response( array $validated_data, array $roi_calculations, array $business_case ) {
        return array(
            'meta' => array(
                'request_id' => $this->context['request_id'],
                'generated_at' => current_time( 'c' ),
                'report_type' => $this->context['report_type'],
                'processing_time' => $this->context['processing_time'] ?? 0,
                'version' => RTBCB_VERSION
            ),
            'company_info' => array(
                'name' => $validated_data['company_name'],
                'industry' => $validated_data['industry'],
                'size' => $validated_data['company_size'],
                'pain_points' => $validated_data['pain_points'] ?? array()
            ),
            'roi_analysis' => $roi_calculations,
            'business_case' => $business_case,
            'recommendations' => $this->generate_recommendations( $validated_data, $roi_calculations ),
            'next_steps' => $this->generate_next_steps( $this->context['report_type'] )
        );
    }
    
    /**
     * Validate security requirements for the request
     * 
     * @return true|WP_Error Security validation result
     */
    private function validate_security() {
        // Nonce verification
        if ( ! isset( $_POST['rtbcb_nonce'] ) ) {
            return new WP_Error(
                'rtbcb_missing_nonce',
                __( 'Security token is missing.', 'rtbcb' ),
                array( 'status' => 403 )
            );
        }
        
        $nonce = sanitize_text_field( wp_unslash( $_POST['rtbcb_nonce'] ) );
        if ( ! wp_verify_nonce( $nonce, 'rtbcb_form_action' ) ) {
            return new WP_Error(
                'rtbcb_invalid_nonce',
                __( 'Security verification failed. Please refresh the page and try again.', 'rtbcb' ),
                array( 'status' => 403 )
            );
        }
        
        // Rate limiting check (basic)
        $user_ip = $this->get_client_ip();
        $rate_limit_key = 'rtbcb_rate_limit_' . md5( $user_ip );
        $current_requests = get_transient( $rate_limit_key ) ?: 0;
        
        if ( $current_requests >= 10 ) { // 10 requests per minute
            return new WP_Error(
                'rtbcb_rate_limit_exceeded',
                __( 'Too many requests. Please wait a moment before trying again.', 'rtbcb' ),
                array( 'status' => 429 )
            );
        }
        
        // Update rate limit counter
        set_transient( $rate_limit_key, $current_requests + 1, 60 );
        
        return true;
    }
    
    /**
     * Validate and sanitize request data
     * 
     * @param string $request_type Request type for validation rules
     * 
     * @return array|WP_Error Validated data or error
     */
    private function validate_and_sanitize_request( string $request_type ) {
        if ( ! isset( $this->validation_rules[ $request_type ] ) ) {
            return new WP_Error(
                'rtbcb_unknown_request_type',
                __( 'Unknown request type.', 'rtbcb' ),
                array( 'status' => 400 )
            );
        }
        
        $rules = $this->validation_rules[ $request_type ];
        $validated_data = array();
        
        // Check required fields
        foreach ( $rules['required'] as $field ) {
            if ( ! isset( $_POST[ $field ] ) || empty( $_POST[ $field ] ) ) {
                return new WP_Error(
                    'rtbcb_missing_required_field',
                    sprintf( __( 'Required field "%s" is missing or empty.', 'rtbcb' ), $field ),
                    array( 'status' => 400, 'field' => $field )
                );
            }
        }
        
        // Process all fields (required + optional)
        $all_fields = array_merge( $rules['required'], $rules['optional'] );
        
        foreach ( $all_fields as $field ) {
            if ( isset( $_POST[ $field ] ) ) {
                $value = wp_unslash( $_POST[ $field ] );
                
                // Apply sanitization
                if ( isset( $rules['sanitization'][ $field ] ) ) {
                    $sanitizer = $rules['sanitization'][ $field ];
                    
                    if ( is_callable( $sanitizer ) ) {
                        $validated_data[ $field ] = call_user_func( $sanitizer, $value );
                    } else {
                        $validated_data[ $field ] = $value;
                    }
                } else {
                    $validated_data[ $field ] = sanitize_text_field( $value );
                }
            }
        }
        
        // Additional validation using the dedicated validator service
        $validation_result = $this->services['validator']->validate_business_case_data( $validated_data );
        
        if ( is_wp_error( $validation_result ) ) {
            return $validation_result;
        }
        
        return $validated_data;
    }
    
    /**
     * Setup processing context for the request
     * 
     * @param array  $validated_data Validated input data
     * @param string $report_type    Report type
     */
    private function setup_processing_context( array $validated_data, string $report_type ) {
        $this->context = array(
            'request_id' => uniqid( 'rtbcb_', true ),
            'start_time' => microtime( true ),
            'report_type' => $report_type,
            'user_ip' => $this->get_client_ip(),
            'user_agent' => sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ?? '' ) ),
            'company_name' => $validated_data['company_name'],
            'industry' => $validated_data['industry'],
            'company_size' => $validated_data['company_size']
        );
    }
    
    /**
     * Build RAG search query from validated data
     * 
     * @param array $validated_data Input data
     * 
     * @return string Search query
     */
    private function build_rag_search_query( array $validated_data ) {
        $query_parts = array();
        
        $query_parts[] = $validated_data['industry'];
        $query_parts[] = $validated_data['company_size'];
        
        if ( ! empty( $validated_data['pain_points'] ) ) {
            $query_parts = array_merge( $query_parts, $validated_data['pain_points'] );
        }
        
        return implode( ' ', array_map( 'sanitize_text_field', $query_parts ) );
    }
    
    /**
     * Determine appropriate model tier based on report type
     * 
     * @param string $report_type Report type
     * 
     * @return string Model tier
     */
    private function determine_model_tier( string $report_type ) {
        $tier_mapping = array(
            'basic' => 'mini',
            'standard' => 'standard',
            'comprehensive' => 'advanced',
            'enterprise' => 'enterprise'
        );
        
        return $tier_mapping[ $report_type ] ?? 'standard';
    }
    
    /**
     * Generate recommendations based on ROI analysis
     * 
     * @param array $validated_data   Input data
     * @param array $roi_calculations ROI results
     * 
     * @return array Recommendations
     */
    private function generate_recommendations( array $validated_data, array $roi_calculations ) {
        $recommendations = array();
        
        // Analyze ROI scenarios
        if ( isset( $roi_calculations['base']['roi_percentage'] ) ) {
            $base_roi = floatval( $roi_calculations['base']['roi_percentage'] );
            
            if ( $base_roi > 300 ) {
                $recommendations[] = array(
                    'type' => 'high_priority',
                    'title' => __( 'Immediate Implementation Recommended', 'rtbcb' ),
                    'description' => __( 'Exceptional ROI indicates this investment should be prioritized immediately.', 'rtbcb' )
                );
            } elseif ( $base_roi > 100 ) {
                $recommendations[] = array(
                    'type' => 'recommended',
                    'title' => __( 'Strong Business Case', 'rtbcb' ),
                    'description' => __( 'Solid ROI supports moving forward with implementation planning.', 'rtbcb' )
                );
            } else {
                $recommendations[] = array(
                    'type' => 'review',
                    'title' => __( 'Additional Analysis Recommended', 'rtbcb' ),
                    'description' => __( 'Consider optimizing implementation strategy to improve ROI.', 'rtbcb' )
                );
            }
        }
        
        // Company size specific recommendations
        if ( $validated_data['company_size'] === 'enterprise' ) {
            $recommendations[] = array(
                'type' => 'implementation',
                'title' => __( 'Phased Implementation Approach', 'rtbcb' ),
                'description' => __( 'Consider implementing in phases to manage change and validate benefits.', 'rtbcb' )
            );
        }
        
        return $recommendations;
    }
    
    /**
     * Generate next steps based on report type
     * 
     * @param string $report_type Report type
     * 
     * @return array Next steps
     */
    private function generate_next_steps( string $report_type ) {
        $base_steps = array(
            array(
                'step' => 1,
                'title' => __( 'Review Business Case', 'rtbcb' ),
                'description' => __( 'Share this analysis with key stakeholders and decision makers.', 'rtbcb' )
            ),
            array(
                'step' => 2,
                'title' => __( 'Vendor Evaluation', 'rtbcb' ),
                'description' => __( 'Research and evaluate treasury technology providers.', 'rtbcb' )
            ),
            array(
                'step' => 3,
                'title' => __( 'Implementation Planning', 'rtbcb' ),
                'description' => __( 'Develop detailed implementation timeline and resource allocation.', 'rtbcb' )
            )
        );
        
        if ( $report_type === 'enterprise' ) {
            $base_steps[] = array(
                'step' => 4,
                'title' => __( 'Change Management', 'rtbcb' ),
                'description' => __( 'Develop comprehensive change management and training programs.', 'rtbcb' )
            );
        }
        
        return $base_steps;
    }
    
    /**
     * Save lead data if enabled
     * 
     * @param array $validated_data Input data
     * @param array $result         Processing result
     */
    private function save_lead_data( array $validated_data, array $result ) {
        try {
            if ( get_option( 'rtbcb_save_leads', true ) ) {
                $this->services['leads']->save_lead( $validated_data, $result );
            }
        } catch ( Exception $e ) {
            // Lead saving failure is not critical - log but don't fail the request
            $this->error_handler->log_error(
                'Lead data saving failed: ' . $e->getMessage(),
                RTBCB_Error_Handler::ERROR_LEVEL_WARNING,
                array( 'exception' => $e->getTraceAsString() ),
                'ROUTER_LEAD_SAVE'
            );
        }
    }
    
    /**
     * Send error response with proper formatting
     * 
     * @param WP_Error $error Error object
     */
    private function send_error_response( WP_Error $error ) {
        $error_data = $error->get_error_data();
        $status_code = $error_data['status'] ?? 500;
        
        wp_send_json_error(
            array(
                'message' => $error->get_error_message(),
                'code' => $error->get_error_code(),
                'request_id' => $this->context['request_id'] ?? null,
                'timestamp' => current_time( 'c' )
            ),
            $status_code
        );
    }
    
    /**
     * Send success response with timing information
     * 
     * @param array $result        Processing result
     * @param float $execution_time Total execution time
     */
    private function send_success_response( array $result, float $execution_time ) {
        $result['meta']['processing_time'] = round( $execution_time, 3 );
        
        wp_send_json_success( $result );
    }
    
    /**
     * Get client IP address
     * 
     * @return string Client IP address
     */
    private function get_client_ip() {
        $ip_headers = array(
            'HTTP_CF_CONNECTING_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        );
        
        foreach ( $ip_headers as $header ) {
            if ( ! empty( $_SERVER[ $header ] ) ) {
                $ip = sanitize_text_field( wp_unslash( $_SERVER[ $header ] ) );
                // Take first IP if comma-separated list
                $ip = explode( ',', $ip )[0];
                $ip = trim( $ip );
                
                if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) ) {
                    return $ip;
                }
            }
        }
        
        return '127.0.0.1';
    }
    
    /**
     * Sanitize pain points array
     * 
     * @param mixed $pain_points Pain points data
     * 
     * @return array Sanitized pain points
     */
    private function sanitize_pain_points( $pain_points ) {
        if ( ! is_array( $pain_points ) ) {
            return array();
        }
        
        return array_filter( array_map( 'sanitize_text_field', $pain_points ) );
    }
    
    /**
     * Get service health status
     * 
     * @return array Health status of all services
     */
    public function get_health_status() {
        $status = array(
            'router' => array(
                'status' => 'healthy',
                'services_initialized' => count( $this->services ),
                'validation_rules_loaded' => count( $this->validation_rules )
            )
        );
        
        // Check each service health
        foreach ( $this->services as $service_name => $service ) {
            if ( method_exists( $service, 'get_health_status' ) ) {
                $status[ $service_name ] = $service->get_health_status();
            } else {
                $status[ $service_name ] = array( 'status' => 'unknown' );
            }
        }
        
        return $status;
    }
    
    /**
     * Clear all service caches
     * 
     * @return bool Success status
     */
    public function clear_caches() {
        $success = true;
        
        foreach ( $this->services as $service ) {
            if ( method_exists( $service, 'clear_cache' ) ) {
                $result = $service->clear_cache();
                $success = $success && $result;
            }
        }
        
        return $success;
    }
}
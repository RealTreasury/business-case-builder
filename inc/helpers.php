<?php
/**
 * Helper functions for the Real Treasury Business Case Builder plugin.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Sanitize form input data
 *
 * @param array $data Raw form data
 * @return array Sanitized data
 */
function rtbcb_sanitize_form_data( $data ) {
    $sanitized = [];
    
    // Email
    if ( isset( $data['email'] ) ) {
        $sanitized['email'] = sanitize_email( $data['email'] );
    }
    
    // Text fields
    $text_fields = [ 'company_size', 'industry' ];
    foreach ( $text_fields as $field ) {
        if ( isset( $data[ $field ] ) ) {
            $sanitized[ $field ] = sanitize_text_field( $data[ $field ] );
        }
    }
    
    // Numeric fields
    $numeric_fields = [
        'hours_reconciliation'   => [ 'min' => 0,   'max' => 168 ],
        'hours_cash_positioning' => [ 'min' => 0,   'max' => 168 ],
        'num_banks'              => [ 'min' => 1,   'max' => 50 ],
        'ftes'                   => [ 'min' => 0.5, 'max' => 100 ],
    ];
    
    foreach ( $numeric_fields as $field => $limits ) {
        if ( isset( $data[ $field ] ) ) {
            $value = floatval( $data[ $field ] );
            $value = max( $limits['min'], min( $limits['max'], $value ) );
            $sanitized[ $field ] = $value;
        }
    }
    
    // Pain points array
    if ( isset( $data['pain_points'] ) && is_array( $data['pain_points'] ) ) {
        $valid_pain_points = [
            'manual_processes',
            'poor_visibility',
            'forecast_accuracy',
            'compliance_risk',
            'bank_fees',
            'integration_issues',
        ];
        
        $sanitized['pain_points'] = array_filter(
            array_map( 'sanitize_text_field', $data['pain_points'] ),
            function ( $point ) use ( $valid_pain_points ) {
                return in_array( $point, $valid_pain_points, true );
            }
        );
    }
    
    return $sanitized;
}

/**
 * Validate email domain
 *
 * @param string $email Email address
 * @return bool True if valid business email
 */
function rtbcb_is_business_email( $email ) {
    $consumer_domains = [
        'gmail.com', 'yahoo.com', 'hotmail.com', 'outlook.com',
        'aol.com', 'icloud.com', 'mail.com', 'protonmail.com',
    ];
    
    $domain = substr( strrchr( $email, '@' ), 1 );
    return ! in_array( strtolower( $domain ), $consumer_domains, true );
}

/**
 * Validate OpenAI API key format.
 *
 * Accepts standard and project-scoped keys which start with "sk-" and may
 * include letters, numbers, hyphens, colons, and underscores.
 *
 * @param string $api_key API key.
 * @return bool Whether the format is valid.
 */
function rtbcb_is_valid_openai_api_key( $api_key ) {
    return is_string( $api_key ) && preg_match( '/^sk-[A-Za-z0-9:_-]{48,}$/', $api_key );
}

/**
 * Get client information for analytics
 *
 * @return array Client data
 */
function rtbcb_get_client_info() {
    return [
        'ip'          => rtbcb_get_client_ip(),
        'user_agent'  => isset( $_SERVER['HTTP_USER_AGENT'] ) ?
            sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '',
        'referrer'    => isset( $_SERVER['HTTP_REFERER'] ) ?
            esc_url_raw( wp_unslash( $_SERVER['HTTP_REFERER'] ) ) : '',
        'utm_source'  => isset( $_GET['utm_source'] ) ?
            sanitize_text_field( wp_unslash( $_GET['utm_source'] ) ) : '',
        'utm_medium'  => isset( $_GET['utm_medium'] ) ?
            sanitize_text_field( wp_unslash( $_GET['utm_medium'] ) ) : '',
        'utm_campaign'=> isset( $_GET['utm_campaign'] ) ?
            sanitize_text_field( wp_unslash( $_GET['utm_campaign'] ) ) : '',
    ];
}

/**
 * Get client IP address
 *
 * @return string IP address
 */
function rtbcb_get_client_ip() {
    $ip_keys = [ 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR' ];
    
    foreach ( $ip_keys as $key ) {
        if ( ! empty( $_SERVER[ $key ] ) ) {
            $ip = wp_unslash( $_SERVER[ $key ] );
            
            // Handle comma-separated IPs
            if ( strpos( $ip, ',' ) !== false ) {
                $ip = trim( explode( ',', $ip )[0] );
            }
            
            // Validate IP
            if ( filter_var(
                $ip,
                FILTER_VALIDATE_IP,
                FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
            ) ) {
                return $ip;
            }
        }
    }
    
    return isset( $_SERVER['REMOTE_ADDR'] ) ? wp_unslash( $_SERVER['REMOTE_ADDR'] ) : '';
}


/**
 * Log API debug messages.
 *
 * @param string $message Log message.
 * @param mixed  $data    Optional data.
 * @return void
 */
function rtbcb_log_api_debug( $message, $data = null ) {
    $log_message = 'RTBCB API Debug: ' . $message;

    if ( null !== $data ) {
        $log_message .= ' | Data: ' . ( is_string( $data ) ? $data : wp_json_encode( $data ) );
    }

    error_log( $log_message );

    if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
        $upload_dir = wp_get_upload_dir();
        $log_file   = trailingslashit( $upload_dir['basedir'] ) . 'rtbcb-debug.log';
        $timestamp  = current_time( 'Y-m-d H:i:s' );
        $entry      = "[{$timestamp}] {$log_message}\n";
        file_put_contents( $log_file, $entry, FILE_APPEND | LOCK_EX );
    }
}

/**
 * Log error messages.
 *
 * @param string $message Error message.
 * @param mixed  $data    Optional context data.
 * @return void
 */
function rtbcb_log_error( $message, $data = null ) {
    $log_message = 'RTBCB Error: ' . $message;

    if ( null !== $data ) {
        $log_message .= ' | Data: ' . ( is_string( $data ) ? $data : wp_json_encode( $data ) );
    }

    error_log( $log_message );

    if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
        $upload_dir = wp_get_upload_dir();
        $log_file   = trailingslashit( $upload_dir['basedir'] ) . 'rtbcb-debug.log';
        $timestamp  = current_time( 'Y-m-d H:i:s' );
        $entry      = "[{$timestamp}] {$log_message}\n";
        file_put_contents( $log_file, $entry, FILE_APPEND | LOCK_EX );
    }
}

/**
 * Set up temporary error handlers for AJAX debugging.
 *
 * @return void
 */
function rtbcb_setup_ajax_logging() {
    set_error_handler(
        function ( $severity, $message, $file, $line ) {
            rtbcb_log_error(
                sprintf(
                    'PHP error [%s] %s in %s:%d',
                    $severity,
                    $message,
                    $file,
                    $line
                )
            );
            return false;
        }
    );

    register_shutdown_function(
        function () {
            $error = error_get_last();
            if ( $error && E_ERROR === $error['type'] ) {
                rtbcb_log_error(
                    sprintf(
                        'Fatal error %s in %s:%d',
                        $error['message'],
                        $error['file'],
                        $error['line']
                    )
                );
            }
        }
    );
}

/**
 * Attempt to increase PHP memory limit for heavy operations.
 *
 * @return void
 */
function rtbcb_increase_memory_limit() {
    if ( function_exists( 'ini_set' ) ) {
        @ini_set( 'memory_limit', '512M' );
    }
}

/**
 * Log current memory usage and peak usage.
 *
 * @param string $stage Description of current stage.
 * @return void
 */
function rtbcb_log_memory_usage( $stage ) {
    if ( function_exists( 'memory_get_usage' ) ) {
        $usage = size_format( memory_get_usage( true ) );
        $peak  = size_format( memory_get_peak_usage( true ) );
        error_log( "RTBCB Memory ({$stage}): usage={$usage}, peak={$peak}" );
    }
}

/**
 * Get memory usage statistics.
 *
 * @return array
 */
function rtbcb_get_memory_status() {
    return [
        'usage' => memory_get_usage( true ),
        'peak'  => memory_get_peak_usage( true ),
        'limit' => ini_get( 'memory_limit' ),
    ];
}

/**
 * Retrieve sample user inputs for testing purposes.
 *
 * @return array Sample user inputs.
 */
function rtbcb_get_sample_inputs() {
    $inputs = [
        'company_name'          => 'Acme Corp',
        'company_size'          => '$50M-$500M',
        'industry'              => 'manufacturing',
        'job_title'             => 'Treasury Manager',
        'hours_reconciliation'  => 5,
        'hours_cash_positioning'=> 3,
        'num_banks'             => 3,
        'ftes'                  => 1,
        'pain_points'           => [ 'manual_processes', 'bank_fees' ],
    ];

    /**
     * Filter sample inputs used for diagnostics and tests.
     *
     * @param array $inputs Default sample inputs.
     */
    return apply_filters( 'rtbcb_sample_inputs', $inputs );
}

/**
 * Retrieve predefined sample report scenarios.
 *
 * @return array Map of scenario keys to labels and input data.
 */
function rtbcb_get_sample_report_forms() {
    return [
        'enterprise_manufacturer' => [
            'label' => __( 'Enterprise Manufacturer', 'rtbcb' ),
            'data'  => [
                'company_name'  => 'Acme Manufacturing',
                'company_size'  => '1000-5000',
                'industry'      => 'Manufacturing',
                'location'      => 'USA',
                'analysis_date' => current_time( 'Y-m-d' ),
            ],
        ],
        'tech_startup'           => [
            'label' => __( 'Tech Startup', 'rtbcb' ),
            'data'  => [
                'company_name'  => 'Innovatech',
                'company_size'  => '1-50',
                'industry'      => 'Technology',
                'location'      => 'UK',
                'analysis_date' => current_time( 'Y-m-d' ),
            ],
        ],
    ];
}

/**
 * Map scenario keys to sample report inputs.
 *
 * @param array  $inputs       Default inputs.
 * @param string $scenario_key Scenario identifier.
 * @return array Filtered inputs.
 */
function rtbcb_map_sample_report_inputs( $inputs, $scenario_key ) {
    $forms = rtbcb_get_sample_report_forms();
    if ( $scenario_key && isset( $forms[ $scenario_key ] ) ) {
        return $forms[ $scenario_key ]['data'];
    }

    return $inputs;
}
add_filter( 'rtbcb_sample_report_inputs', 'rtbcb_map_sample_report_inputs', 10, 2 );

/**
 * Test generating industry commentary using the LLM.
 *
 * @param string $industry Industry slug.
 * @return string|WP_Error Commentary text or error object.
 */
function rtbcb_test_generate_industry_commentary( $industry ) {
    $industry = sanitize_text_field( $industry );

    try {
        $llm        = new RTBCB_LLM();
        $commentary = $llm->generate_industry_commentary( $industry );
    } catch ( \Throwable $e ) {
        return new WP_Error( 'llm_exception', __( 'Unable to generate commentary at this time.', 'rtbcb' ) );
    }

    return $commentary;
}

/**
 * Test generating a company overview using the LLM.
 *
 * @param string $company_name Company name.
 * @return string|WP_Error Overview text or error object.
 */
function rtbcb_test_generate_company_overview( $company_name ) {
    $company_name = sanitize_text_field( $company_name );

    try {
        $llm      = new RTBCB_LLM();
        $overview = $llm->generate_company_overview( $company_name );
    } catch ( \Throwable $e ) {
        return new WP_Error( 'llm_exception', __( 'Unable to generate overview at this time.', 'rtbcb' ) );
    }

    return $overview;
}

/**
 * Test generating a treasury tech overview using the LLM.
 *
 * @param array  $focus_areas Focus areas.
 * @param string $complexity  Company complexity.
 * @return string|WP_Error Overview text or error object.
 */
function rtbcb_test_generate_treasury_tech_overview( $focus_areas, $complexity ) {
    $focus_areas = array_map( 'sanitize_text_field', (array) $focus_areas );
    $focus_areas = array_filter( $focus_areas );
    $complexity  = sanitize_text_field( $complexity );

    try {
        $llm      = new RTBCB_LLM();
        $overview = $llm->generate_treasury_tech_overview( $focus_areas, $complexity );
    } catch ( \Throwable $e ) {
        return new WP_Error( 'llm_exception', __( 'Unable to generate overview at this time.', 'rtbcb' ) );
    }

    return $overview;
}

/**
 * Test generating an industry overview using the LLM.
 *
 * @param string $industry     Industry name.
 * @param string $company_size Company size description.
 * @return string|WP_Error Overview text or error object.
 */
function rtbcb_test_generate_industry_overview( $industry, $company_size ) {
    $industry     = sanitize_text_field( $industry );
    $company_size = sanitize_text_field( $company_size );

    try {
        $llm      = new RTBCB_LLM();
        $overview = $llm->generate_industry_overview( $industry, $company_size );
    } catch ( \Throwable $e ) {
        return new WP_Error( 'llm_exception', __( 'Unable to generate overview at this time.', 'rtbcb' ) );
    }

    return $overview;
}

/**
 * Test generating a Real Treasury overview using the LLM.
 *
 * @param bool  $include_portal Whether to include portal information.
 * @param array $categories     Vendor categories.
 * @return string|WP_Error Overview text or error object.
 */
function rtbcb_test_generate_real_treasury_overview( $include_portal, $categories ) {
    $include_portal = (bool) $include_portal;
    $categories     = array_filter( array_map( 'sanitize_text_field', (array) $categories ) );

    try {
        $llm      = new RTBCB_LLM();
        $overview = $llm->generate_real_treasury_overview( $include_portal, $categories );
    } catch ( \Throwable $e ) {
        return new WP_Error( 'llm_exception', __( 'Unable to generate overview at this time.', 'rtbcb' ) );
    }

    return $overview;
}

/**
 * Test generating a complete report with ROI calculations.
 *
 * Validates and sanitizes inputs, generates required sections, performs ROI
 * calculations, routes content through the router for HTML assembly, and saves
 * the resulting report for later download.
 *
 * @param array $all_inputs Section input data.
 *
 * @return array Structured report data including HTML, section content, word
 *               counts, timestamps, and export information.
 */
function rtbcb_test_generate_complete_report( $all_inputs ) {
    $start_time = microtime( true );

    $company_name = isset( $all_inputs['company_name'] )
        ? sanitize_text_field( $all_inputs['company_name'] )
        : '';

    $focus_areas = [];
    if ( isset( $all_inputs['focus_areas'] ) ) {
        $focus_areas = array_filter( array_map( 'sanitize_text_field', (array) $all_inputs['focus_areas'] ) );
    }

    $complexity = isset( $all_inputs['complexity'] )
        ? sanitize_text_field( $all_inputs['complexity'] )
        : '';

    $roi_inputs = [];
    if ( isset( $all_inputs['roi_inputs'] ) && is_array( $all_inputs['roi_inputs'] ) ) {
        $roi_inputs = rtbcb_sanitize_form_data( $all_inputs['roi_inputs'] );
    }

    $sections = [];
    $timings  = [];

    $section_start               = microtime( true );
    $sections['company_overview'] = rtbcb_test_generate_company_overview( $company_name );
    $timings['company_overview']  = microtime( true ) - $section_start;

    $section_start                      = microtime( true );
    $sections['treasury_tech_overview'] = rtbcb_test_generate_treasury_tech_overview( $focus_areas, $complexity );
    $timings['treasury_tech_overview']  = microtime( true ) - $section_start;

    $section_start         = microtime( true );
    $sections['roi']       = RTBCB_Calculator::calculate_roi( $roi_inputs );
    $timings['roi']        = microtime( true ) - $section_start;

    $company_text = is_wp_error( $sections['company_overview'] )
        ? $sections['company_overview']->get_error_message()
        : (string) $sections['company_overview'];

    $tech_text = is_wp_error( $sections['treasury_tech_overview'] )
        ? $sections['treasury_tech_overview']->get_error_message()
        : (string) $sections['treasury_tech_overview'];

    $router = new RTBCB_Router();
    $html   = $router->get_report_html(
        [
            'narrative' => $company_text . '\n\n' . $tech_text,
            'roi'       => $sections['roi'],
        ]
    );

    $word_counts = [
        'company_overview'       => str_word_count( wp_strip_all_tags( $company_text ) ),
        'treasury_tech_overview' => str_word_count( wp_strip_all_tags( $tech_text ) ),
        'combined'               => str_word_count( wp_strip_all_tags( $company_text . ' ' . $tech_text ) ),
    ];

    $end_time = microtime( true );

    $result = [
        'html'        => $html,
        'sections'    => [
            'company_overview'       => $company_text,
            'treasury_tech_overview' => $tech_text,
            'roi'                    => $sections['roi'],
        ],
        'word_counts' => $word_counts,
        'timestamps'  => [
            'start'       => $start_time,
            'end'         => $end_time,
            'elapsed'     => $end_time - $start_time,
            'per_section' => $timings,
        ],
    ];

    $upload_dir = wp_get_upload_dir();
    if ( ! empty( $upload_dir['basedir'] ) ) {
        $file_name = wp_unique_filename( $upload_dir['basedir'], 'rtbcb-report.html' );
        $file_path = trailingslashit( $upload_dir['basedir'] ) . $file_name;

        if ( wp_mkdir_p( dirname( $file_path ) ) ) {
            file_put_contents( $file_path, $html );
            $result['download_url'] = trailingslashit( $upload_dir['baseurl'] ) . $file_name;
        }
    }

    $post_id = wp_insert_post(
        [
            'post_title'   => $company_name ? sprintf( __( '%s Report', 'rtbcb' ), $company_name ) : __( 'RTBCB Report', 'rtbcb' ),
            'post_content' => $html,
            'post_status'  => 'draft',
            'post_type'    => 'rtbcb_report',
        ],
        true
    );

    if ( ! is_wp_error( $post_id ) ) {
        update_post_meta( $post_id, '_rtbcb_report_data', $result );
        $result['post_id'] = $post_id;
    }

    return $result;
}


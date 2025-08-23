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

    $llm        = new RTBCB_LLM();
    $commentary = $llm->generate_industry_commentary( $industry );

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

    $llm      = new RTBCB_LLM();
    $overview = $llm->generate_company_overview( $company_name );

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

    $llm      = new RTBCB_LLM();
    $overview = $llm->generate_treasury_tech_overview( $focus_areas, $complexity );

    return $overview;
}

/**
 * Generate a complete business case report for testing purposes.
 *
 * Validates and sanitizes all inputs, generates individual sections, performs ROI
 * calculations, assembles HTML via the router and stores the result for later
 * retrieval.
 *
 * @param array $all_inputs Raw section inputs.
 * @return array|WP_Error Structured report data or error on failure.
 */
function rtbcb_test_generate_complete_report( $all_inputs ) {
    $start_time = microtime( true );

    $validator = new RTBCB_Validator();
    $inputs    = $validator->validate( $all_inputs );

    if ( isset( $inputs['error'] ) ) {
        return new WP_Error( 'rtbcb_invalid_inputs', $inputs['error'] );
    }

    // Additional fields not handled by validator.
    if ( isset( $all_inputs['focus_areas'] ) ) {
        $inputs['focus_areas'] = array_map( 'sanitize_text_field', (array) $all_inputs['focus_areas'] );
    }
    if ( isset( $all_inputs['complexity'] ) ) {
        $inputs['complexity'] = sanitize_text_field( $all_inputs['complexity'] );
    }

    $sections = [];

    // Company overview.
    $company_start   = microtime( true );
    $company_content = rtbcb_test_generate_company_overview( $inputs['company_name'] );
    $company_end     = microtime( true );
    $sections['company_overview'] = [
        'content'    => is_wp_error( $company_content ) ? '' : $company_content,
        'word_count' => is_wp_error( $company_content ) ? 0 : str_word_count( wp_strip_all_tags( $company_content ) ),
        'start'      => gmdate( 'c', (int) $company_start ),
        'end'        => gmdate( 'c', (int) $company_end ),
        'elapsed'    => round( $company_end - $company_start, 4 ),
        'error'      => is_wp_error( $company_content ) ? $company_content->get_error_message() : '',
    ];

    // Treasury technology overview.
    $tech_start   = microtime( true );
    $tech_content = rtbcb_test_generate_treasury_tech_overview(
        $inputs['focus_areas'] ?? [],
        $inputs['complexity'] ?? ''
    );
    $tech_end     = microtime( true );
    $sections['treasury_tech_overview'] = [
        'content'    => is_wp_error( $tech_content ) ? '' : $tech_content,
        'word_count' => is_wp_error( $tech_content ) ? 0 : str_word_count( wp_strip_all_tags( $tech_content ) ),
        'start'      => gmdate( 'c', (int) $tech_start ),
        'end'        => gmdate( 'c', (int) $tech_end ),
        'elapsed'    => round( $tech_end - $tech_start, 4 ),
        'error'      => is_wp_error( $tech_content ) ? $tech_content->get_error_message() : '',
    ];

    // ROI calculations.
    $roi_start = microtime( true );
    $roi_data  = RTBCB_Calculator::calculate_roi( $inputs );
    $roi_end   = microtime( true );
    $sections['roi'] = [
        'data'    => $roi_data,
        'start'   => gmdate( 'c', (int) $roi_start ),
        'end'     => gmdate( 'c', (int) $roi_end ),
        'elapsed' => round( $roi_end - $roi_start, 4 ),
    ];

    // Assemble narrative and assumptions for HTML generation.
    $base_roi      = $roi_data['base'] ?? [];
    $roi_summary   = '';
    if ( ! empty( $base_roi ) ) {
        $roi_summary = sprintf(
            /* translators: 1: total annual benefit, 2: ROI percentage */
            __( 'Total annual benefit: %1$s, ROI: %2$s%%', 'rtbcb' ),
            number_format_i18n( $base_roi['total_annual_benefit'] ?? 0, 2 ),
            number_format_i18n( $base_roi['roi_percentage'] ?? 0, 2 )
        );
    }

    $narrative = trim(
        $sections['company_overview']['content'] . "\n\n" .
        $sections['treasury_tech_overview']['content'] . "\n\n" .
        $roi_summary
    );

    $assumptions = [];
    if ( ! empty( $base_roi['assumptions'] ) && is_array( $base_roi['assumptions'] ) ) {
        foreach ( $base_roi['assumptions'] as $key => $value ) {
            $assumptions[] = ucwords( str_replace( '_', ' ', $key ) ) . ': ' . $value;
        }
    }

    $router    = new RTBCB_Router();
    $html_start = microtime( true );
    $report_html = $router->get_report_html(
        [
            'narrative'            => $narrative,
            'assumptions_explained' => $assumptions,
        ]
    );
    $html_end = microtime( true );

    $total_elapsed = round( $html_end - $start_time, 4 );

    $result = [
        'html'       => $report_html,
        'sections'   => $sections,
        'roi'        => $roi_data,
        'word_counts'=> [
            'company_overview'      => $sections['company_overview']['word_count'],
            'treasury_tech_overview'=> $sections['treasury_tech_overview']['word_count'],
            'total'                 => str_word_count( wp_strip_all_tags( $report_html ) ),
        ],
        'timestamps' => [
            'start'   => gmdate( 'c', (int) $start_time ),
            'end'     => gmdate( 'c', (int) $html_end ),
            'elapsed' => $total_elapsed,
        ],
    ];

    // Save HTML report to uploads directory.
    $uploads = wp_upload_dir();
    if ( ! empty( $uploads['basedir'] ) && wp_is_writable( $uploads['basedir'] ) ) {
        $file_name = 'rtbcb-report-' . time() . '.html';
        $file_path = trailingslashit( $uploads['basedir'] ) . $file_name;
        $saved     = file_put_contents( $file_path, $report_html );

        if ( false !== $saved ) {
            $result['export']['html_path'] = $file_path;
            $result['export']['html_url']  = trailingslashit( $uploads['baseurl'] ) . $file_name;
        }
    }

    // Store report as a draft post for reference.
    $post_id = wp_insert_post(
        [
            'post_title'   => sprintf( __( 'Business Case Report for %s', 'rtbcb' ), $inputs['company_name'] ),
            'post_content' => wp_kses_post( $report_html ),
            'post_status'  => 'draft',
            'post_type'    => 'rtbcb_report',
        ]
    );

    if ( ! is_wp_error( $post_id ) ) {
        update_post_meta( $post_id, '_rtbcb_report_sections', $sections );
        update_post_meta( $post_id, '_rtbcb_report_roi', $roi_data );
        $result['export']['post_id'] = $post_id;
    }

    return $result;
}


<?php
defined( 'ABSPATH' ) || exit;

/**
 * Helper functions for the Real Treasury Business Case Builder plugin.
 */

require_once __DIR__ . '/config.php';

/**
 * Get the timeout for external API requests.
 *
 * Allows filtering via `rtbcb_api_timeout` to adjust how long remote
 * requests may take before failing.
 *
 * @return int Timeout in seconds.
 */
function rtbcb_get_api_timeout() {
    $timeout = (int) get_option( 'rtbcb_gpt5_timeout', 180 );

    /**
     * Filter the API request timeout.
     *
     * @param int $timeout Timeout in seconds.
     */
    if ( function_exists( 'apply_filters' ) ) {
        return (int) apply_filters( 'rtbcb_api_timeout', $timeout );
    }

    return $timeout;
}

/**
 * Retrieve the OpenAI API key from plugin settings.
 *
 * Reads the value stored in the WordPress options table.
 *
 * @return string Sanitized API key.
 */
function rtbcb_get_openai_api_key() {
    $api_key = get_option( 'rtbcb_openai_api_key', '' );

    return sanitize_text_field( $api_key );
}

/**
 * Determine if an OpenAI API key is configured.
 *
 * @return bool True if the API key is present.
 */
function rtbcb_has_openai_api_key() {
    return ! empty( rtbcb_get_openai_api_key() );
}

/**
 * Determine if an error indicates an OpenAI configuration issue.
 *
 * Checks for common phrases like a missing API key or invalid model.
 *
 * @param Throwable $e Thrown error or exception.
 * @return bool True if the error appears configuration related.
 */
function rtbcb_is_openai_configuration_error( $e ) {
    $message = strtolower( $e->getMessage() );

    return false !== strpos( $message, 'api key' ) || false !== strpos( $message, 'model' );
}

/**
 * Retrieve current company data.
 *
 * @return array Current company data.
 */
function rtbcb_get_current_company() {
    return get_option( 'rtbcb_current_company', [] );
}

/**
 * Clear stored company data and reset test progress.
 *
 * @return void
 */
function rtbcb_clear_current_company() {
    $sections = rtbcb_get_dashboard_sections( [] );

    $options = [
        'rtbcb_company_overview',
        'rtbcb_treasury_challenges',
        'rtbcb_company_data',
        'rtbcb_test_results',
    ];

    foreach ( $sections as $section ) {
        if ( ! empty( $section['option'] ) ) {
            $options[] = $section['option'];
        }
    }

    foreach ( array_unique( $options ) as $option ) {
        delete_option( $option );
    }
}

/**
 * Retrieve model capability configuration.
 *
 * @return array Model capability data.
 */
function rtbcb_get_model_capabilities() {
    return include RTBCB_DIR . 'inc/model-capabilities.php';
}

/**
 * Determine if a model supports the temperature parameter.
 *
 * Attempts to query the OpenAI models endpoint and caches the result. Falls
 * back to a static list of unsupported models if the request fails.
 *
 * @param string $model Model identifier.
 * @return bool Whether the model supports temperature.
 */
function rtbcb_model_supports_temperature( $model ) {
    $capabilities = include RTBCB_DIR . 'inc/model-capabilities.php';
    $unsupported = $capabilities['temperature']['unsupported'] ?? [];
    return ! in_array( $model, $unsupported, true );
}

/**
 * Sanitize the max output tokens option.
 *
 * Ensures the value stays within the allowed 256-8000 token range.
 *
 * @param mixed $value Raw option value.
 * @return int Sanitized token count.
 */
function rtbcb_sanitize_max_output_tokens( $value ) {
    $value = intval( $value );

    return min( 8000, max( 256, $value ) );
}

/**
 * Get testing dashboard sections and their completion state.
 *
 * The returned array is keyed by section ID and contains the section label,
 * related option key, AJAX action, dependencies, and whether the section has
 * been completed.
 *
 * @param array|null $test_results Optional preloaded test results.
 * @return array[] Section data keyed by section ID.
 */
function rtbcb_get_dashboard_sections( $test_results = null ) {
    if ( null === $test_results ) {
        $test_results = get_option( 'rtbcb_test_results', [] );
    }

    $sections = [
        'rtbcb-test-company-overview'      => [
            'label'    => __( 'Company Overview', 'rtbcb' ),
            'option'   => 'rtbcb_current_company',
            'requires' => [],
            'phase'    => 1,
            'action'   => 'rtbcb_test_company_overview',
        ],
        'rtbcb-test-data-enrichment'       => [
            'label'    => __( 'Data Enrichment', 'rtbcb' ),
            'option'   => 'rtbcb_data_enrichment',
            'requires' => [ 'rtbcb-test-company-overview' ],
            'phase'    => 1,
            'action'   => 'rtbcb_test_data_enrichment',
        ],
        'rtbcb-test-data-storage'          => [
            'label'    => __( 'Data Storage', 'rtbcb' ),
            'option'   => 'rtbcb_data_storage',
            'requires' => [ 'rtbcb-test-data-enrichment' ],
            'phase'    => 1,
            'action'   => 'rtbcb_test_data_storage',
        ],
        'rtbcb-test-maturity-model'        => [
            'label'    => __( 'Maturity Model', 'rtbcb' ),
            'option'   => 'rtbcb_maturity_model',
            'requires' => [ 'rtbcb-test-data-storage' ],
            'phase'    => 2,
            'action'   => 'rtbcb_test_maturity_model',
        ],
        'rtbcb-test-rag-market-analysis'   => [
            'label'    => __( 'RAG Market Analysis', 'rtbcb' ),
            'option'   => 'rtbcb_rag_market_analysis',
            'requires' => [ 'rtbcb-test-maturity-model' ],
            'phase'    => 2,
            'action'   => 'rtbcb_test_rag_market_analysis',
        ],
        'rtbcb-test-value-proposition'     => [
            'label'    => __( 'Value Proposition', 'rtbcb' ),
            'option'   => 'rtbcb_value_proposition',
            'requires' => [ 'rtbcb-test-rag-market-analysis' ],
            'phase'    => 2,
            'action'   => 'rtbcb_test_value_proposition',
        ],
        'rtbcb-test-industry-overview'      => [
            'label'    => __( 'Industry Overview', 'rtbcb' ),
            'option'   => 'rtbcb_industry_insights',
            'requires' => [ 'rtbcb-test-value-proposition' ],
            'phase'    => 2,
            'action'   => 'rtbcb_test_industry_overview',
        ],
        'rtbcb-test-real-treasury-overview' => [
            'label'    => __( 'Real Treasury Overview', 'rtbcb' ),
            'option'   => 'rtbcb_real_treasury_overview',
            'requires' => [ 'rtbcb-test-industry-overview' ],
            'phase'    => 2,
            'action'   => 'rtbcb_test_real_treasury_overview',
        ],
        'rtbcb-test-roadmap-generator'      => [
            'label'    => __( 'Roadmap Generator', 'rtbcb' ),
            'option'   => 'rtbcb_roadmap_plan',
            'requires' => [ 'rtbcb-test-real-treasury-overview' ],
            'phase'    => 3,
        ],
        'rtbcb-test-roi-calculator'         => [
            'label'    => __( 'ROI Calculator', 'rtbcb' ),
            'option'   => 'rtbcb_roi_results',
            'requires' => [ 'rtbcb-test-roadmap-generator' ],
            'phase'    => 3,
            'action'   => 'rtbcb_test_calculate_roi',
        ],
        'rtbcb-test-estimated-benefits'     => [
            'label'    => __( 'Estimated Benefits', 'rtbcb' ),
            'option'   => 'rtbcb_estimated_benefits',
            'requires' => [ 'rtbcb-test-roi-calculator' ],
            'phase'    => 3,
            'action'   => 'rtbcb_test_estimated_benefits',
        ],
        'rtbcb-test-report-assembly'        => [
            'label'    => __( 'Report Assembly & Delivery', 'rtbcb' ),
            'option'   => 'rtbcb_executive_summary',
            'requires' => [ 'rtbcb-test-estimated-benefits' ],
            'phase'    => 4,
            'action'   => 'rtbcb_test_report_assembly',
        ],
        'rtbcb-test-tracking-script'        => [
            'label'    => __( 'Tracking Scripts', 'rtbcb' ),
            'option'   => 'rtbcb_tracking_script',
            'requires' => [ 'rtbcb-test-report-assembly' ],
            'phase'    => 5,
            'action'   => 'rtbcb_test_tracking_script',
        ],
        'rtbcb-test-follow-up-email'        => [
            'label'    => __( 'Follow-up Emails', 'rtbcb' ),
            'option'   => 'rtbcb_follow_up_queue',
            'requires' => [ 'rtbcb-test-tracking-script' ],
            'phase'    => 5,
            'action'   => 'rtbcb_test_follow_up_email',
        ],
    ];

    foreach ( $sections as $id => &$section ) {
        $result               = rtbcb_get_last_test_result( $id, $test_results );
        $status               = $result['status'] ?? '';
        $section['completed'] = ( 'success' === $status );
    }

    return $sections;
}

/**
 * Calculate completion percentages for each phase.
 *
 * @param array $sections Dashboard sections.
 * @param array $phases   Optional phase numbers to include in the result.
 * @return array Percentages keyed by phase number.
 */
function rtbcb_calculate_phase_completion( $sections, $phases = [] ) {
    $totals = [];
    $done   = [];

    foreach ( $sections as $section ) {
        $phase = isset( $section['phase'] ) ? (int) $section['phase'] : 0;
        if ( $phase ) {
            if ( ! isset( $totals[ $phase ] ) ) {
                $totals[ $phase ] = 0;
                $done[ $phase ]   = 0;
            }
            $totals[ $phase ]++;
            if ( ! empty( $section['completed'] ) ) {
                $done[ $phase ]++;
            }
        }
    }

    $phase_keys  = $phases ? $phases : array_keys( $totals );
    $percentages = array_fill_keys( $phase_keys, 0 );

    foreach ( $totals as $phase => $total ) {
        $percentages[ $phase ] = $total ? round( ( $done[ $phase ] / $total ) * 100 ) : 0;
    }

    ksort( $percentages );

    return $percentages;
}

/**
 * Get the first incomplete dependency for a section.
 *
 * Recursively checks the dependency chain and returns the earliest section
 * that has not been completed.
 *
 * @param string $section_id Section identifier to check.
 * @param array  $sections   All dashboard sections.
 * @return string|null The first incomplete dependency or null if all met.
 */
function rtbcb_get_first_incomplete_dependency( $section_id, $sections, $visited = [] ) {
    if ( in_array( $section_id, $visited, true ) ) {
        return null;
    }

    $visited[] = $section_id;

    if ( empty( $sections[ $section_id ]['requires'] ) ) {
        return null;
    }

    foreach ( $sections[ $section_id ]['requires'] as $dependency ) {
        if ( empty( $sections[ $dependency ]['completed'] ) ) {
            $deep = rtbcb_get_first_incomplete_dependency( $dependency, $sections, $visited );
            if ( $deep ) {
                return $deep;
            }
            return $dependency;
        }
    }

    return null;
}

/**
 * Ensure required sections are complete before rendering a dashboard section.
 *
 * Outputs a warning linking to the first incomplete section when prerequisites
 * are missing.
 *
 * @param string $current_section Current section ID.
 * @param bool   $display_notice  Optional. Whether to show an admin notice.
 *                                Defaults to true.
 * @return bool True when allowed, false otherwise.
 */
function rtbcb_require_completed_steps( $current_section, $display_notice = true ) {
    static $displayed = [];

    $sections   = rtbcb_get_dashboard_sections();
    $dependency = rtbcb_get_first_incomplete_dependency( $current_section, $sections );

    if ( null === $dependency ) {
        return true;
    }

    if ( $display_notice && ! in_array( $dependency, $displayed, true ) ) {
        $phase  = isset( $sections[ $dependency ]['phase'] ) ? (int) $sections[ $dependency ]['phase'] : 0;
        $anchor = $phase ? 'rtbcb-phase' . $phase : $dependency;
        $url    = admin_url( 'admin.php?page=rtbcb-test-dashboard#' . $anchor );
        echo '<div class="notice notice-error"><p>' .
            sprintf(
                esc_html__( 'Please complete %s first.', 'rtbcb' ),
                '<a href="' . esc_url( $url ) . '">' .
                esc_html( $sections[ $dependency ]['label'] ) . '</a>'
            ) .
            '</p></div>';
        $displayed[] = $dependency;
    }

    return false;
}

/**
 * Retrieve the most recent test result for a section.
 *
 * @param string     $section_id   Section identifier.
 * @param array|null $test_results Optional preloaded test results.
 * @return array|null Matching result or null when none found.
 */
function rtbcb_get_last_test_result( $section_id, $test_results = null ) {
    if ( null === $test_results ) {
        $test_results = get_option( 'rtbcb_test_results', [] );
    }

    if ( ! is_array( $test_results ) ) {
        return null;
    }

    foreach ( $test_results as $result ) {
        if ( isset( $result['section'] ) && $result['section'] === $section_id ) {
            return $result;
        }
    }

    return null;
}

/**
 * Render a button to start a new company analysis.
 *
 * The button clears existing company data and navigates to the Company
 * Overview section of the testing dashboard so a new analysis can begin.
 *
 * @return void
 */
function rtbcb_render_start_new_analysis_button() {
    echo '<p>';
    echo '<button type="button" id="rtbcb-start-new-analysis" class="button">' .
        esc_html__( 'Start New Analysis', 'rtbcb' ) . '</button>';
    wp_nonce_field( 'rtbcb_test_company_overview', 'rtbcb_clear_current_company_nonce' );
    echo '</p>';
}

function rtbcb_check_database_health() {
    global $wpdb;

    $tables = [
        'rtbcb_leads'     => $wpdb->prefix . 'rtbcb_leads',
        'rtbcb_rag_index' => $wpdb->prefix . 'rtbcb_rag_index',
    ];

    $status = [];

    foreach ( $tables as $key => $table_name ) {
        $exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) );
        $status[ $key ] = [
            'exists' => ! empty( $exists ),
            'name'   => $table_name,
        ];
    }

    return $status;
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
    return preg_match( '/^sk-[A-Za-z0-9_:-]{48,}$/', $api_key );
}

/**
 * Normalize a model name by stripping date suffixes.
 *
 * @param string $model Raw model identifier.
 * @return string Model name without version date.
 */
function rtbcb_normalize_model_name( $model ) {
    $model = sanitize_text_field( $model );
    return preg_replace( '/^(gpt-[^\s]+?)(?:-\d{4}-\d{2}-\d{2})$/', '$1', $model );
}

/**
 * Send the generated report to the user via email.
 *
 * @param array  $form_data   Form submission data.
 * @param string $report_path Absolute path to the HTML report file.
 *
 * @return void
 */
function rtbcb_send_report_email( $form_data, $report_path ) {
    $email = isset( $form_data['email'] ) ? sanitize_email( $form_data['email'] ) : '';

    if ( empty( $email ) || ! is_readable( $report_path ) ) {
        return;
    }

    $subject = __( 'Your Business Case Report', 'rtbcb' );
    $message = __( 'Please find your business case report attached.', 'rtbcb' );

    wp_mail( $email, $subject, $message, [], [ $report_path ] );
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
 * Recursively sanitize values using sanitize_text_field.
 *
 * @param mixed $data Data to sanitize.
 * @return mixed Sanitized data.
 */
function rtbcb_recursive_sanitize_text_field( $data ) {
    if ( is_array( $data ) ) {
        foreach ( $data as $key => $value ) {
            $data[ $key ] = rtbcb_recursive_sanitize_text_field( $value );
        }

        return $data;
    }

    return sanitize_text_field( (string) $data );
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
    if ( $data ) {
        $log_message .= ' - ' . wp_json_encode( $data );
    }
    error_log( $log_message );
}

function rtbcb_log_error( $message, $context = null ) {
    $log_message = 'RTBCB Error: ' . $message;
    if ( $context ) {
        $log_message .= ' - Context: ' . wp_json_encode( $context );
    }
    error_log( $log_message );
}

function rtbcb_setup_ajax_logging() {
    if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
        error_reporting( E_ALL );
        ini_set( 'display_errors', 1 );
        ini_set( 'log_errors', 1 );
    }
}

function rtbcb_increase_memory_limit() {
    $current       = ini_get( 'memory_limit' );
    $current_bytes = wp_convert_hr_to_bytes( $current );
    $required_bytes = 256 * 1024 * 1024;

    if ( $current_bytes < $required_bytes ) {
        ini_set( 'memory_limit', '256M' );
    }
}

function rtbcb_log_memory_usage( $stage ) {
    $usage = memory_get_usage( true );
    $peak  = memory_get_peak_usage( true );
    error_log( sprintf( 'RTBCB Memory [%s]: Current: %s, Peak: %s',
        $stage, size_format( $usage ), size_format( $peak ) ) );
}

function rtbcb_get_memory_status() {
    return [
        'current' => memory_get_usage( true ),
        'peak'    => memory_get_peak_usage( true ),
        'limit'   => wp_convert_hr_to_bytes( ini_get( 'memory_limit' ) ),
    ];
}

/**
 * Retrieve sample user inputs for testing purposes.
 *
 * @return array Sample user inputs.
 */
function rtbcb_get_sample_inputs() {
    return [
        'company_name'           => 'Acme Manufacturing Corp',
        'company_size'           => '$500M-$2B',
        'industry'               => 'Manufacturing',
        'hours_reconciliation'   => 15,
        'hours_cash_positioning' => 10,
        'num_banks'              => 5,
        'ftes'                   => 3,
        'pain_points'            => [
            'manual_processes',
            'poor_visibility',
            'forecast_accuracy'
        ],
        'business_objective'     => 'reduce_costs',
        'implementation_timeline'=> '6_months',
        'budget_range'           => '100k_500k',
    ];
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
 * Generate a category recommendation with enriched context for testing.
 *
 * Sanitizes requirement inputs, runs the category recommender, augments the
 * response with human readable names, reasoning, alternative categories,
 * confidence and scoring data, and optional implementation guidance.
 *
 * @param array $requirements User provided requirement data.
 * @return array Structured recommendation data.
 */
function rtbcb_test_generate_category_recommendation( $analysis ) {
    $analysis = is_array( $analysis ) ? $analysis : [];

    $payload = [
        'company_overview'    => sanitize_textarea_field( $analysis['company_overview'] ?? '' ),
        'industry_insights'   => sanitize_textarea_field( $analysis['industry_insights'] ?? '' ),
        'maturity_model'      => sanitize_textarea_field( $analysis['maturity_model'] ?? '' ),
        'treasury_challenges' => sanitize_textarea_field( $analysis['treasury_challenges'] ?? '' ),
        'extra_requirements'  => sanitize_textarea_field( $analysis['extra_requirements'] ?? '' ),
    ];

    try {
        $api_key = get_option( 'rtbcb_openai_api_key' );
        if ( empty( $api_key ) ) {
            return new WP_Error( 'no_api_key', __( 'OpenAI API key not configured.', 'rtbcb' ) );
        }

        $model = sanitize_text_field( get_option( 'rtbcb_mini_model', rtbcb_get_default_model( 'mini' ) ) );

        $system_prompt = 'You are a treasury technology advisor. Based on the company overview, industry insights, technology overview, and treasury challenges provided, recommend the most suitable solution category (cash_tools, tms_lite, trms). Return JSON with keys "recommended", "reasoning", and "alternatives" (array of objects with "category" and "reasoning").';

        $input = "Company Overview: {$payload['company_overview']}";
        $input .= "\nIndustry Insights: {$payload['industry_insights']}";
        $input .= "\nMaturity Assessment: {$payload['maturity_model']}";
        $input .= "\nTreasury Challenges: {$payload['treasury_challenges']}";
        if ( ! empty( $payload['extra_requirements'] ) ) {
            $input .= "\nExtra Requirements: {$payload['extra_requirements']}";
        }

        $response = wp_remote_post(
            'https://api.openai.com/v1/responses',
            [
                'headers' => [
                    'Content-Type'  => 'application/json',
                    'Authorization' => 'Bearer ' . $api_key,
                ],
                'body'    => wp_json_encode(
                    [
                        'model'        => $model,
                        'instructions' => $system_prompt,
                        'input'        => $input,
                    ]
                ),
                'timeout' => 60,
            ]
        );

        if ( is_wp_error( $response ) ) {
            return new WP_Error( 'llm_failure', __( 'Unable to generate recommendation at this time.', 'rtbcb' ) );
        }

        $body    = wp_remote_retrieve_body( $response );
        $decoded = json_decode( $body, true );
        $content = '';

        if ( isset( $decoded['output_text'] ) ) {
            $content = is_array( $decoded['output_text'] ) ? implode( ' ', (array) $decoded['output_text'] ) : $decoded['output_text'];
        } elseif ( ! empty( $decoded['output'] ) && is_array( $decoded['output'] ) ) {
            foreach ( $decoded['output'] as $message ) {
                if ( empty( $message['content'] ) || ! is_array( $message['content'] ) ) {
                    continue;
                }

                foreach ( $message['content'] as $chunk ) {
                    if ( isset( $chunk['text'] ) && '' !== $chunk['text'] ) {
                        $content = $chunk['text'];
                        break 2;
                    }
                }
            }
        }

        $content = sanitize_textarea_field( $content );

        if ( '' === $content ) {
            return new WP_Error( 'llm_empty_response', __( 'No recommendation returned.', 'rtbcb' ) );
        }

        $json = json_decode( $content, true );
        if ( ! is_array( $json ) ) {
            return new WP_Error( 'invalid_response', __( 'Invalid recommendation format.', 'rtbcb' ) );
        }

        $recommended_key = sanitize_key( $json['recommended'] ?? '' );
        $category_info   = RTBCB_Category_Recommender::get_category_info( $recommended_key );

        $alternatives = [];
        if ( ! empty( $json['alternatives'] ) && is_array( $json['alternatives'] ) ) {
            foreach ( $json['alternatives'] as $alt ) {
                $alt_key  = sanitize_key( $alt['category'] ?? '' );
                $alt_info = RTBCB_Category_Recommender::get_category_info( $alt_key );
                if ( $alt_key && $alt_info ) {
                    $alternatives[] = [
                        'key'       => $alt_key,
                        'name'      => $alt_info['name'] ?? '',
                        'reasoning' => sanitize_text_field( $alt['reasoning'] ?? '' ),
                    ];
                }
            }
        }

        return [
            'recommended' => [
                'key'         => $recommended_key,
                'name'        => $category_info['name'] ?? '',
                'description' => $category_info['description'] ?? '',
                'features'    => $category_info['features'] ?? [],
                'ideal_for'   => $category_info['ideal_for'] ?? '',
            ],
            'reasoning'    => sanitize_textarea_field( $json['reasoning'] ?? '' ),
            'alternatives' => $alternatives,
        ];
    } catch ( \Throwable $e ) {
        return new WP_Error( 'llm_exception', __( 'Unable to generate recommendation at this time.', 'rtbcb' ) );
    }
}

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
 * @return array|WP_Error Structured overview array or error object.
 */
function rtbcb_test_generate_company_overview( $company_name ) {
    if ( ! class_exists( 'RTBCB_LLM' ) ) {
        return new WP_Error( 'missing_class', __( 'LLM class not available', 'rtbcb' ) );
    }

    $company_name = sanitize_text_field( $company_name );

    try {
        $llm      = new RTBCB_LLM();
        $overview = $llm->generate_company_overview( $company_name );
    } catch ( \Throwable $e ) {
        return new WP_Error( 'llm_exception', $e->getMessage() );
    }

    return $overview;
}

/**
 * Test generating a treasury tech overview using the LLM.
 *
 * @param array $company_data Company data including focus areas and complexity.
 * @return string|WP_Error Overview text or error object.
 */
/**
 * Test assessing treasury maturity.
 *
 * @param array $company_data Company information.
 * @return array|WP_Error Assessment data or error.
 */
function rtbcb_test_generate_maturity_model( $company_data ) {
    if ( ! class_exists( 'RTBCB_Maturity_Model' ) ) {
        return new WP_Error( 'missing_class', __( 'Maturity model class not available', 'rtbcb' ) );
    }

    $model       = new RTBCB_Maturity_Model();
    $company_data = is_array( $company_data ) ? $company_data : [];
    return $model->assess( $company_data );
}

/**
 * Test running RAG market analysis.
 *
 * @param string $query Search query.
 * @return array|WP_Error Vendor shortlist or error.
 */
function rtbcb_test_rag_market_analysis( $query ) {
    if ( ! class_exists( 'RTBCB_RAG' ) ) {
        return new WP_Error( 'missing_class', __( 'RAG class not available', 'rtbcb' ) );
    }

    $rag    = new RTBCB_RAG();
    $query  = sanitize_text_field( $query );
    $result = $rag->get_context( $query, 3 );

    $vendors = [];
    foreach ( $result as $meta ) {
        if ( isset( $meta['name'] ) ) {
            $vendors[] = sanitize_text_field( $meta['name'] );
        }
    }

    return $vendors;
}

/**
 * Test generating a value proposition.
 *
 * @param array $company_data Company information.
 * @return string|WP_Error Opening paragraph or error.
 */
function rtbcb_test_generate_value_proposition( $company_data ) {
    $company_data = is_array( $company_data ) ? $company_data : [];
    $company_name = isset( $company_data['name'] ) ? sanitize_text_field( $company_data['name'] ) : '';

    if ( class_exists( 'RTBCB_Maturity_Model' ) ) {
        $model      = new RTBCB_Maturity_Model();
        $assessment = $model->assess( $company_data );
        $level      = $assessment['level'];
    } else {
        $level = __( 'basic', 'rtbcb' );
    }

    $business_case_data = [
        'company_name'      => $company_name,
        'executive_summary' => [
            'strategic_positioning' => sprintf(
                __( 'Real Treasury helps %1$s advance from %2$s maturity toward optimized performance.', 'rtbcb' ),
                $company_name,
                strtolower( $level )
            ),
        ],
    ];

    ob_start();
    include RTBCB_DIR . 'templates/comprehensive-report-template.php';
    $output = ob_get_clean();

    if ( preg_match( '/<div class="rtbcb-strategic-positioning">\s*<h3>.*?<\/h3>\s*<p>(.*?)<\/p>/s', $output, $matches ) ) {
        return sanitize_text_field( wp_strip_all_tags( $matches[1] ) );
    }

    return new WP_Error( 'no_paragraph', __( 'Unable to generate value proposition.', 'rtbcb' ) );
}

/**
 * Test generating an industry overview using company data.
 *
 * @param array $company_data Company information including industry, size,
 *                            geography, and business model.
 * @return string|WP_Error Overview text or error object.
 */
function rtbcb_test_generate_industry_overview( $company_data ) {
    if ( ! class_exists( 'RTBCB_LLM' ) ) {
        return new WP_Error( 'missing_class', __( 'LLM class not available', 'rtbcb' ) );
    }

    $company_data = is_array( $company_data ) ? $company_data : [];
    $industry = isset( $company_data['industry'] ) ? sanitize_text_field( $company_data['industry'] ) : '';
    $size     = isset( $company_data['size'] ) ? sanitize_text_field( $company_data['size'] ) : '';

    if ( empty( $industry ) || empty( $size ) ) {
        return new WP_Error( 'missing_data', __( 'Industry and company size required', 'rtbcb' ) );
    }

    $llm = new RTBCB_LLM();
    return $llm->generate_industry_overview( $industry, $size );
}

/**
 * Test generating a Real Treasury overview using the LLM.
 *
 * @param array $company_data {
 *     Company context data.
 *
 *     @type bool   $include_portal Include portal integration details.
 *     @type string $company_size   Company size description.
 *     @type string $industry       Company industry.
 *     @type array  $challenges     List of identified challenges.
 *     @type array  $categories     Optional vendor categories to highlight.
 * }
 * @return string|WP_Error Overview text or error object.
 */
function rtbcb_test_generate_real_treasury_overview( $include_portal = false, $categories = [] ) {
    if ( ! class_exists( 'RTBCB_LLM' ) ) {
        return new WP_Error( 'missing_class', __( 'LLM class not available', 'rtbcb' ) );
    }

    $company = rtbcb_get_current_company();
    if ( empty( $company ) ) {
        return new WP_Error( 'no_company', __( 'No company data available', 'rtbcb' ) );
    }

    $company_data = [
        'include_portal' => (bool) $include_portal,
        'company_size'   => sanitize_text_field( $company['size'] ?? '' ),
        'industry'       => sanitize_text_field( $company['industry'] ?? '' ),
        'challenges'     => array_map( 'sanitize_text_field', $company['challenges'] ?? [] ),
        'categories'     => array_map( 'sanitize_text_field', (array) $categories ),
    ];

    $llm = new RTBCB_LLM();
    return $llm->generate_real_treasury_overview( $company_data );
}

/**
 * Test generating a benefits estimate using the LLM.
 *
 * @param array  $company_data        Company context including revenue, staff count and efficiency.
 * @param string $recommended_category Solution category.
 * @return array|WP_Error Structured estimate array or error object.
 */
function rtbcb_test_generate_benefits_estimate( $company_data, $recommended_category ) {
    $company_data = is_array( $company_data ) ? $company_data : [];
    $revenue      = isset( $company_data['revenue'] ) ? floatval( $company_data['revenue'] ) : 0;
    $staff_count  = isset( $company_data['staff_count'] ) ? intval( $company_data['staff_count'] ) : 0;
    $efficiency   = isset( $company_data['efficiency'] ) ? floatval( $company_data['efficiency'] ) : 0;
    $recommended_category = sanitize_text_field( $recommended_category );

    try {
        $llm      = new RTBCB_LLM();
        $estimate = $llm->generate_benefits_estimate( $revenue, $staff_count, $efficiency, $recommended_category );
    } catch ( \Throwable $e ) {
        return new WP_Error( 'llm_exception', __( 'Unable to estimate benefits at this time.', 'rtbcb' ) );
    }

    return $estimate;
}

/**
 * Test generating executive summary via the LLM.
 *
 * @return array|WP_Error Summary data or error object.
 */
function rtbcb_test_generate_executive_summary() {
    if ( ! class_exists( 'RTBCB_LLM' ) ) {
        return new WP_Error( 'missing_class', __( 'LLM class not available', 'rtbcb' ) );
    }

    $company = rtbcb_get_current_company();
    $roi     = get_option( 'rtbcb_roi_results', [] );

    $llm    = new RTBCB_LLM();
    $result = $llm->generate_comprehensive_business_case( $company, $roi );

    if ( is_wp_error( $result ) ) {
        return $result;
    }

    $summary = isset( $result['executive_summary'] ) ? $result['executive_summary'] : [];

    $summary = [
        'strategic_positioning'   => sanitize_text_field( $summary['strategic_positioning'] ?? '' ),
        'business_case_strength'  => sanitize_text_field( $summary['business_case_strength'] ?? '' ),
        'key_value_drivers'       => array_map( 'sanitize_text_field', $summary['key_value_drivers'] ?? [] ),
        'executive_recommendation'=> sanitize_text_field( $summary['executive_recommendation'] ?? '' ),
    ];

    update_option( 'rtbcb_executive_summary', $summary );

    return $summary;
}

/**
 * Parse GPT-5 Responses API output with quality validation.
 *
 * @param array $response Response data from GPT-5 API.
 *
 * @return array Parsed response with quality information.
 */
function rtbcb_parse_gpt5_business_case_response( $response ) {
    $parsed = rtbcb_parse_gpt5_response( $response );

    $result = [
        'text'           => $parsed['output_text'],
        'reasoning_notes' => $parsed['reasoning'],
        'function_calls' => $parsed['function_calls'],
        'quality_score'  => 0,
        'alerts'         => [],
    ];

    // Quality validation for business case content.
    $text        = $result['text'];
    $text_length = strlen( $text );
    $word_count  = str_word_count( $text );

    // Score based on content quality indicators.
    if ( $text_length > 500 ) {
        $result['quality_score'] += 2;
    }
    if ( $word_count > 100 ) {
        $result['quality_score'] += 2;
    }
    if ( stripos( $text, 'business case' ) !== false ) {
        $result['quality_score'] += 1;
    }
    if ( stripos( $text, 'ROI' ) !== false ) {
        $result['quality_score'] += 1;
    }
    if ( stripos( $text, 'implementation' ) !== false ) {
        $result['quality_score'] += 1;
    }
    if ( null !== json_decode( $text, true ) ) {
        $result['quality_score'] += 2;
    }

    // Alert conditions.
    if ( $text_length < 100 ) {
        $result['alerts'][] = 'SUSPICIOUSLY_SHORT_CONTENT';
    }

    if ( stripos( $text, 'pong' ) !== false ||
        stripos( $text, 'how can I help' ) !== false ) {
        $result['alerts'][] = 'HEALTH_CHECK_RESPONSE';
    }

    if ( $result['quality_score'] < 3 ) {
        $result['alerts'][] = 'LOW_QUALITY_BUSINESS_CASE';
    }

    // Log results.
    if ( ! empty( $result['alerts'] ) ) {
        error_log( 'RTBCB: Business case quality issues - ' . implode( ', ', $result['alerts'] ) );
    }

    return $result;
}

/**
 * Proxy requests to the OpenAI Responses API.
 *
 * Reads the API key from options and forwards the provided request body to
 * the OpenAI endpoint.
 *
 * @return void
 */
function rtbcb_proxy_openai_responses() {
    $api_key = get_option( 'rtbcb_openai_api_key' );
    if ( empty( $api_key ) ) {
        wp_send_json_error( [ 'message' => __( 'OpenAI API key not configured.', 'rtbcb' ) ], 500 );
    }

    if ( isset( $_POST['nonce'] ) ) {
        check_ajax_referer( 'rtbcb_openai_responses', 'nonce' );
    }

    $body = isset( $_POST['body'] ) ? wp_unslash( $_POST['body'] ) : '';
    if ( '' === $body ) {
        wp_send_json_error( [ 'message' => __( 'Missing request body.', 'rtbcb' ) ], 400 );
    }

    $body_array = json_decode( $body, true );
    if ( ! is_array( $body_array ) ) {
        $body_array = [];
    }

    $config            = rtbcb_get_gpt5_config();
    $max_output_tokens = intval( $body_array['max_output_tokens'] ?? $config['max_output_tokens'] );
    $max_output_tokens = min( 8000, max( 256, $max_output_tokens ) );
    $body_array['max_output_tokens'] = $max_output_tokens;
    $body_array['stream']            = true;
    $payload                         = wp_json_encode( $body_array );

    nocache_headers();
    header( 'Content-Type: text/event-stream' );
    header( 'Cache-Control: no-cache' );
    header( 'Connection: keep-alive' );

    $timeout = intval( get_option( 'rtbcb_responses_timeout', 120 ) );
    if ( $timeout <= 0 ) {
        $timeout = 120;
    }

    $ch = curl_init( 'https://api.openai.com/v1/responses' );
    curl_setopt( $ch, CURLOPT_POST, true );
    curl_setopt( $ch, CURLOPT_POSTFIELDS, $payload );
    curl_setopt( $ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $api_key,
    ] );
    curl_setopt( $ch, CURLOPT_TIMEOUT, $timeout );
    curl_setopt( $ch, CURLOPT_WRITEFUNCTION, function ( $curl, $data ) {
        echo $data;
        if ( function_exists( 'flush' ) ) {
            flush();
        }
        return strlen( $data );
    } );

    $ok    = curl_exec( $ch );
    $error = curl_error( $ch );
    curl_close( $ch );

    if ( false === $ok && '' !== $error ) {
        $msg = sanitize_text_field( $error );
        echo 'data: ' . wp_json_encode( [ 'error' => $msg ] ) . "\n\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }

    exit;
}

/**
 * Background handler for OpenAI response jobs.
 *
 * @param string $job_id  Job identifier.
 * @param int    $user_id User identifier.
 * @return void
 */
function rtbcb_handle_openai_responses_job( $job_id, $user_id ) {
    $job_id  = sanitize_key( $job_id );
    $user_id = intval( $user_id );

    $api_key = get_option( 'rtbcb_openai_api_key' );
    if ( empty( $api_key ) ) {
        set_transient(
            'rtbcb_openai_job_' . $job_id,
            [
                'status'  => 'error',
                'message' => __( 'OpenAI API key not configured.', 'rtbcb' ),
            ],
            HOUR_IN_SECONDS
        );
        return;
    }

    $body = get_transient( 'rtbcb_openai_job_' . $job_id . '_body' );
    if ( false === $body ) {
        set_transient(
            'rtbcb_openai_job_' . $job_id,
            [
                'status'  => 'error',
                'message' => __( 'Job request not found.', 'rtbcb' ),
            ],
            HOUR_IN_SECONDS
        );
        return;
    }
    delete_transient( 'rtbcb_openai_job_' . $job_id . '_body' );

    $body_array = json_decode( $body, true );
    if ( ! is_array( $body_array ) ) {
        $body_array = [];
    }

    $timeout = intval( get_option( 'rtbcb_responses_timeout', 120 ) );
    if ( $timeout <= 0 ) {
        $timeout = 120;
    }

    $response = wp_remote_post(
        'https://api.openai.com/v1/responses',
        [
            'headers' => [
                'Content-Type'  => 'application/json',
                'Authorization' => 'Bearer ' . $api_key,
            ],
            'body'    => $body,
            'timeout' => $timeout,
        ]
    );

    if ( is_wp_error( $response ) ) {
        if ( class_exists( 'RTBCB_API_Log' ) ) {
            RTBCB_API_Log::save_log( $body_array, [ 'error' => $response->get_error_message() ], $user_id );
        }
        set_transient(
            'rtbcb_openai_job_' . $job_id,
            [
                'status'  => 'error',
                'message' => $response->get_error_message(),
            ],
            HOUR_IN_SECONDS
        );
        return;
    }

    $code      = wp_remote_retrieve_response_code( $response );
    $resp_body = wp_remote_retrieve_body( $response );
    $decoded   = json_decode( $resp_body, true );
    if ( ! is_array( $decoded ) ) {
        $decoded = [];
    }

    if ( class_exists( 'RTBCB_API_Log' ) ) {
        RTBCB_API_Log::save_log( $body_array, $decoded, $user_id );
    }

    set_transient(
        'rtbcb_openai_job_' . $job_id,
        [
            'status'   => 'complete',
            'code'     => $code,
            'response' => $decoded,
        ],
        HOUR_IN_SECONDS
    );
}
if ( function_exists( 'add_action' ) ) {
    add_action( 'rtbcb_run_openai_responses_job', 'rtbcb_handle_openai_responses_job', 10, 2 );
}

/**
 * Retrieve the status or result of an OpenAI response job.
 *
 * @return void
 */
function rtbcb_get_openai_responses_status() {
    $job_id = isset( $_REQUEST['job_id'] ) ? sanitize_key( wp_unslash( $_REQUEST['job_id'] ) ) : '';
    if ( '' === $job_id ) {
        wp_send_json_error( [ 'message' => __( 'Missing job ID.', 'rtbcb' ) ], 400 );
    }

    $result = get_transient( 'rtbcb_openai_job_' . $job_id );
    if ( false === $result ) {
        wp_send_json_error( [ 'message' => __( 'Job not found or expired.', 'rtbcb' ) ], 404 );
    }

    if ( isset( $result['status'] ) && 'pending' === $result['status'] ) {
        wp_send_json_success( $result );
    }

    delete_transient( 'rtbcb_openai_job_' . $job_id );
    wp_send_json_success( $result );
}

/**
 * Queue comprehensive analysis generation in the background.
 *
 * @param string $company_name Company name.
 * @return string|WP_Error Job identifier on success or WP_Error on failure.
 */
function rtbcb_queue_comprehensive_analysis( $company_name ) {
    $company_name = sanitize_text_field( $company_name );
    if ( '' === $company_name ) {
        return new WP_Error( 'invalid_company', __( 'Company name is required.', 'rtbcb' ) );
    }

    $job_id = wp_generate_uuid4();
    wp_schedule_single_event(
        time(),
        'rtbcb_process_comprehensive_analysis',
        [ $company_name, $job_id ]
    );

    return $job_id;
}

/**
 * Background handler for comprehensive analysis jobs.
 *
 * @param string $company_name Company name.
 * @param string $job_id       Job identifier.
 * @return void
 */
function rtbcb_handle_comprehensive_analysis( $company_name, $job_id ) {
    $company_name = sanitize_text_field( $company_name );
    $job_id       = sanitize_key( $job_id );

    $rag_context = [];
    $vendor_list = [];

    if ( function_exists( 'rtbcb_test_rag_market_analysis' ) && function_exists( 'rtbcb_get_current_company' ) ) {
        $company = rtbcb_get_current_company();
        $terms   = [];

        if ( ! empty( $company['industry'] ) ) {
            $terms[] = sanitize_text_field( $company['industry'] );
        }

        if ( ! empty( $company['focus_areas'] ) && is_array( $company['focus_areas'] ) ) {
            $terms = array_merge( $terms, array_map( 'sanitize_text_field', $company['focus_areas'] ) );
        }

        if ( empty( $terms ) && ! empty( $company['summary'] ) ) {
            $terms[] = sanitize_text_field( wp_trim_words( $company['summary'], 5, '' ) );
        }

        if ( empty( $terms ) ) {
            $terms[] = $company_name;
        }

        $query       = sanitize_text_field( implode( ' ', $terms ) );
        $vendor_list = rtbcb_test_rag_market_analysis( $query );

        if ( is_wp_error( $vendor_list ) ) {
            $vendor_list = [];
        }

        $rag_context = array_map( 'sanitize_text_field', $vendor_list );
    }

    $llm      = new RTBCB_LLM();
    $analysis = $llm->generate_comprehensive_business_case( [ 'company_name' => $company_name ], [], $rag_context );

    if ( is_wp_error( $analysis ) ) {
        update_option(
            'rtbcb_analysis_job_' . $job_id,
            [
                'success'    => false,
                'message'    => $analysis->get_error_message(),
                'error_code' => $analysis->get_error_code(),
            ]
        );
        return;
    }

    $timestamp = current_time( 'mysql' );

    update_option( 'rtbcb_current_company', $analysis['company_overview'] );
    update_option( 'rtbcb_industry_insights', $analysis['industry_analysis'] );
    update_option( 'rtbcb_maturity_model', $analysis['treasury_maturity'] );
    update_option( 'rtbcb_rag_market_analysis', $vendor_list );
    update_option( 'rtbcb_roadmap_plan', $analysis['implementation_roadmap'] );
    update_option( 'rtbcb_value_proposition', $analysis['executive_summary']['executive_recommendation'] ?? '' );
    update_option( 'rtbcb_estimated_benefits', $analysis['financial_analysis'] );
    update_option( 'rtbcb_executive_summary', $analysis['executive_summary'] );

    $results = [
        'company_overview' => [
            'summary'   => $analysis['company_overview'],
            'stored_in' => 'rtbcb_current_company',
        ],
        'industry_analysis' => [
            'summary'   => $analysis['industry_analysis'],
            'stored_in' => 'rtbcb_industry_insights',
        ],
        'treasury_maturity' => [
            'summary'   => $analysis['treasury_maturity'],
            'stored_in' => 'rtbcb_maturity_model',
        ],
        'market_analysis' => [
            'summary'   => $vendor_list,
            'stored_in' => 'rtbcb_rag_market_analysis',
        ],
        'implementation_roadmap' => [
            'summary'   => $analysis['implementation_roadmap'],
            'stored_in' => 'rtbcb_roadmap_plan',
        ],
        'value_proposition' => [
            'summary'   => $analysis['executive_summary'],
            'stored_in' => 'rtbcb_value_proposition',
        ],
        'financial_analysis' => [
            'summary'   => $analysis['financial_analysis'],
            'stored_in' => 'rtbcb_estimated_benefits',
        ],
        'executive_summary' => [
            'summary'   => $analysis['executive_summary'],
            'stored_in' => 'rtbcb_executive_summary',
        ],
    ];

    $usage_map = [
        [ 'component' => __( 'Company Overview & Metrics', 'rtbcb' ), 'used_in' => __( 'Company Overview Test', 'rtbcb' ), 'option' => 'rtbcb_current_company' ],
        [ 'component' => __( 'Industry Analysis', 'rtbcb' ), 'used_in' => __( 'Industry Overview Test', 'rtbcb' ), 'option' => 'rtbcb_industry_insights' ],
        [ 'component' => __( 'Treasury Maturity Assessment', 'rtbcb' ), 'used_in' => __( 'Maturity Model Test', 'rtbcb' ), 'option' => 'rtbcb_maturity_model' ],
        [ 'component' => __( 'Market Analysis & Vendors', 'rtbcb' ), 'used_in' => __( 'RAG Market Analysis Test', 'rtbcb' ), 'option' => 'rtbcb_rag_market_analysis' ],
        [ 'component' => __( 'Value Proposition Paragraph', 'rtbcb' ), 'used_in' => __( 'Value Proposition Test', 'rtbcb' ), 'option' => 'rtbcb_value_proposition' ],
        [ 'component' => __( 'Financial Benefits Breakdown', 'rtbcb' ), 'used_in' => __( 'Estimated Benefits Test', 'rtbcb' ), 'option' => 'rtbcb_estimated_benefits' ],
        [ 'component' => __( 'Executive Summary', 'rtbcb' ), 'used_in' => __( 'Report Assembly Test', 'rtbcb' ), 'option' => 'rtbcb_executive_summary' ],
        [ 'component' => __( 'Implementation Roadmap', 'rtbcb' ), 'used_in' => __( 'Roadmap Generator Test', 'rtbcb' ), 'option' => 'rtbcb_roadmap_plan' ],
    ];

    update_option(
        'rtbcb_analysis_job_' . $job_id,
        [
            'success'              => true,
            'timestamp'            => $timestamp,
            'results'              => $results,
            'usage_map'            => $usage_map,
            'components_generated' => 7,
        ]
    );
}
if ( function_exists( 'add_action' ) ) {
    add_action( 'rtbcb_process_comprehensive_analysis', 'rtbcb_handle_comprehensive_analysis', 10, 2 );
}

/**
 * Get the result of a queued analysis job.
 *
 * @param string $job_id Job identifier.
 * @return mixed|null Stored result array or null if pending.
 */
function rtbcb_get_analysis_job_result( $job_id ) {
    $job_id = sanitize_key( $job_id );
    return get_option( 'rtbcb_analysis_job_' . $job_id, null );
}

/**
 * Build a cache key for LLM research results.
 *
 * @param string $company  Company name.
 * @param string $industry Industry name.
 * @param string $type     Cache segment identifier.
 *
 * @return string Cache key.
 */
function rtbcb_get_research_cache_key( $company, $industry, $type ) {
    $company  = sanitize_title( $company );
    $industry = sanitize_title( $industry );
    $type     = sanitize_key( $type );

    return 'rtbcb_' . $type . '_' . md5( $company . '_' . $industry );
}

/**
 * Retrieve cached LLM research data.
 *
 * @param string $company  Company name.
 * @param string $industry Industry name.
 * @param string $type     Cache segment identifier.
 *
 * @return mixed Cached data or false when not found.
 */
function rtbcb_get_research_cache( $company, $industry, $type ) {
    $key = rtbcb_get_research_cache_key( $company, $industry, $type );
    return get_transient( $key );
}

/**
 * Store LLM research data in cache.
 *
 * @param string $company  Company name.
 * @param string $industry Industry name.
 * @param string $type     Cache segment identifier.
 * @param mixed  $data     Data to cache.
 * @param int    $ttl      Optional TTL in seconds.
 */
function rtbcb_set_research_cache( $company, $industry, $type, $data, $ttl = 0 ) {
    $key = rtbcb_get_research_cache_key( $company, $industry, $type );
    $ttl = (int) $ttl;
    if ( 0 === $ttl ) {
        $ttl = DAY_IN_SECONDS;
    }

    /**
     * Filter the research cache TTL.
     *
     * @param int $ttl Cache duration in seconds.
     * @param string $type Cache segment identifier.
     * @param string $company Sanitized company name.
     * @param string $industry Sanitized industry.
     */
    $ttl = apply_filters( 'rtbcb_research_cache_ttl', $ttl, $type, $company, $industry );

    set_transient( $key, $data, $ttl );
}

/**
 * Delete cached LLM research data.
 *
 * @param string $company  Company name.
 * @param string $industry Industry name.
 * @param string $type     Cache segment identifier.
 */
function rtbcb_delete_research_cache( $company, $industry, $type ) {
    $key = rtbcb_get_research_cache_key( $company, $industry, $type );
    delete_transient( $key );
}


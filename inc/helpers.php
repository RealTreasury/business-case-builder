<?php
/**
 * Helper functions for the Real Treasury Business Case Builder plugin.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
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
 * Clear stored company data.
 *
 * @return bool True on success, false on failure.
 */
function rtbcb_clear_current_company() {
    delete_option( 'rtbcb_current_company' );
    delete_option( 'rtbcb_company_overview' );
    delete_option( 'rtbcb_industry_insights' );
    delete_option( 'rtbcb_treasury_challenges' );
    delete_option( 'rtbcb_maturity_model' );
    delete_option( 'rtbcb_rag_market_analysis' );
    delete_option( 'rtbcb_value_proposition' );
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
 * Get testing dashboard sections and their completion state.
 *
 * The returned array is keyed by section ID and contains the section label,
 * related option key, dependencies, and whether the section has been
 * completed.
 *
 * @return array[] Section data keyed by section ID.
 */
function rtbcb_get_dashboard_sections() {
    $sections = [
        'rtbcb-test-company-overview'      => [
            'label'    => __( 'Company Overview', 'rtbcb' ),
            'option'   => 'rtbcb_current_company',
            'requires' => [],
            'phase'    => 1,
        ],
        'rtbcb-test-data-enrichment'       => [
            'label'    => __( 'Data Enrichment', 'rtbcb' ),
            'option'   => 'rtbcb_data_enrichment',
            'requires' => [ 'rtbcb-test-company-overview' ],
            'phase'    => 1,
        ],
        'rtbcb-test-data-storage'          => [
            'label'    => __( 'Data Storage', 'rtbcb' ),
            'option'   => 'rtbcb_data_storage',
            'requires' => [ 'rtbcb-test-company-overview' ],
            'phase'    => 1,
        ],
        'rtbcb-test-maturity-model'        => [
            'label'    => __( 'Maturity Model', 'rtbcb' ),
            'option'   => 'rtbcb_maturity_model',
            'requires' => [ 'rtbcb-test-company-overview' ],
            'phase'    => 2,
        ],
        'rtbcb-test-rag-market-analysis'   => [
            'label'    => __( 'RAG Market Analysis', 'rtbcb' ),
            'option'   => 'rtbcb_rag_market_analysis',
            'requires' => [ 'rtbcb-test-company-overview' ],
            'phase'    => 2,
        ],
        'rtbcb-test-value-proposition'     => [
            'label'    => __( 'Value Proposition', 'rtbcb' ),
            'option'   => 'rtbcb_value_proposition',
            'requires' => [ 'rtbcb-test-company-overview' ],
            'phase'    => 2,
        ],
        'rtbcb-test-industry-overview'      => [
            'label'    => __( 'Industry Overview', 'rtbcb' ),
            'option'   => 'rtbcb_industry_insights',
            'requires' => [ 'rtbcb-test-company-overview' ],
            'phase'    => 2,
        ],
        'rtbcb-test-real-treasury-overview' => [
            'label'    => __( 'Real Treasury Overview', 'rtbcb' ),
            'option'   => 'rtbcb_real_treasury_overview',
            'requires' => [ 'rtbcb-test-company-overview' ],
            'phase'    => 2,
        ],
        'rtbcb-test-roadmap-generator'      => [
            'label'    => __( 'Roadmap Generator', 'rtbcb' ),
            'option'   => 'rtbcb_roadmap_plan',
            'requires' => [ 'rtbcb-test-company-overview' ],
            'phase'    => 3,
        ],
        'rtbcb-test-roi-calculator'         => [
            'label'    => __( 'ROI Calculator', 'rtbcb' ),
            'option'   => 'rtbcb_roi_results',
            'requires' => [ 'rtbcb-test-company-overview' ],
            'phase'    => 3,
        ],
        'rtbcb-test-estimated-benefits'     => [
            'label'    => __( 'Estimated Benefits', 'rtbcb' ),
            'option'   => 'rtbcb_estimated_benefits',
            'requires' => [ 'rtbcb-test-company-overview' ],
            'phase'    => 3,
        ],
        'rtbcb-test-report-assembly'        => [
            'label'    => __( 'Report Assembly & Delivery', 'rtbcb' ),
            'option'   => 'rtbcb_executive_summary',
            'requires' => [ 'rtbcb-test-estimated-benefits' ],
            'phase'    => 4,
        ],
    ];

    foreach ( $sections as $id => &$section ) {
        $section['completed'] = ! empty( get_option( $section['option'] ) );
    }

    return $sections;
}

/**
 * Ensure required sections are complete before rendering a dashboard section.
 *
 * Outputs a warning linking to the first incomplete section when prerequisites
 * are missing.
 *
 * @param string $current_section Current section ID.
 * @return bool True when allowed, false otherwise.
 */
function rtbcb_require_completed_steps( $current_section ) {
    $sections = rtbcb_get_dashboard_sections();

    if ( empty( $sections[ $current_section ]['requires'] ) ) {
        return true;
    }

    foreach ( $sections[ $current_section ]['requires'] as $dependency ) {
        if ( empty( $sections[ $dependency ]['completed'] ) ) {
            $phase  = isset( $sections[ $dependency ]['phase'] ) ? (int) $sections[ $dependency ]['phase'] : 0;
            $anchor = $phase ? 'rtbcb-phase' . $phase : $dependency;
            $url    = admin_url( 'admin.php?page=rtbcb-test-dashboard#' . $anchor );
            echo '<div class="notice notice-error"><p>' .
                sprintf(
                    __( 'Please complete %s first.', 'rtbcb' ),
                    '<a href="' . esc_url( $url ) . '">' .
                    esc_html( $sections[ $dependency ]['label'] ) . '</a>'
                ) .
                '</p></div>';
            return false;
        }
    }

    return true;
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
    $url = admin_url( 'admin.php?page=rtbcb-test-dashboard#rtbcb-phase1' );
    echo '<p><a href="' . esc_url( $url ) . '" class="button">' .
        esc_html__( 'Start New Analysis', 'rtbcb' ) . '</a></p>';
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

    $llm = new RTBCB_LLM();
    return $llm->generate_company_overview( $company_name );
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

    $body = isset( $_POST['body'] ) ? wp_unslash( $_POST['body'] ) : '';
    if ( empty( $body ) ) {
        wp_send_json_error( [ 'message' => __( 'Missing request body.', 'rtbcb' ) ], 400 );
    }

    $response = wp_remote_post(
        'https://api.openai.com/v1/responses',
        [
            'headers' => [
                'Content-Type'  => 'application/json',
                'Authorization' => 'Bearer ' . $api_key,
            ],
            'body'    => $body,
            'timeout' => 120,
        ]
    );

    if ( is_wp_error( $response ) ) {
        wp_send_json_error( [ 'message' => $response->get_error_message() ], 500 );
    }

    $code       = wp_remote_retrieve_response_code( $response );
    $resp_body  = wp_remote_retrieve_body( $response );
    $decoded    = json_decode( $resp_body, true );

    wp_send_json( $decoded, $code );
}


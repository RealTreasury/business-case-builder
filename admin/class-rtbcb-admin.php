<?php
/**
 * Enhanced admin functionality for Real Treasury Business Case Builder plugin.
 *
 * @package RealTreasuryBusinessCaseBuilder
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! defined( 'RTBCB_UNIFIED_TESTS_SLUG' ) ) {
    define( 'RTBCB_UNIFIED_TESTS_SLUG', 'rtbcb-unified-tests' );
}

/**
 * Enhanced admin class with full feature integration.
 */
class RTBCB_Admin {
    /**
     * Constructor.
     */
    public function __construct() {
        add_action( 'admin_menu', [ $this, 'add_admin_menu' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );
        add_action( 'admin_post_rtbcb-dashboard-settings-form', [ $this, 'handle_dashboard_settings_form' ] );

        // AJAX handlers
        add_action( 'wp_ajax_rtbcb_export_leads', [ $this, 'export_leads_csv' ] );
        add_action( 'wp_ajax_rtbcb_delete_lead', [ $this, 'delete_lead' ] );
        add_action( 'wp_ajax_rtbcb_bulk_action_leads', [ $this, 'bulk_action_leads' ] );
    }

    /**
     * Enqueue admin assets.
     *
     * @param string $hook Page hook.
     * @return void
     */
    public function enqueue_admin_assets( $hook ) {
        $page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
        if ( strpos( $hook, 'rtbcb' ) === false && strpos( $page, 'rtbcb' ) === false ) {
            return;
        }

        wp_enqueue_script( 'chart-js', RTBCB_URL . 'public/js/chart.min.js', [], '3.9.1', true );
        wp_enqueue_style(
            'rtbcb-admin',
            RTBCB_URL . 'admin/css/rtbcb-admin.css',
            [],
            RTBCB_VERSION
        );
        if ( false !== strpos( $hook, RTBCB_UNIFIED_TESTS_SLUG ) || RTBCB_UNIFIED_TESTS_SLUG === $page ) {
            wp_dequeue_script( 'rtbcb-dashboard' );
            wp_deregister_script( 'rtbcb-dashboard' );
            wp_dequeue_script( 'rtbcb-test-dashboard' );
            wp_deregister_script( 'rtbcb-test-dashboard' );

            wp_enqueue_style(
                'rtbcb-unified-dashboard',
                RTBCB_URL . 'admin/css/unified-test-dashboard.css',
                [],
                RTBCB_VERSION
            );

            // Remove any legacy dashboard scripts to prevent conflicts.
            wp_dequeue_script( 'rtbcb-dashboard' );
            wp_deregister_script( 'rtbcb-dashboard' );

            wp_register_script(
                'rtbcb-unified-dashboard',
                RTBCB_URL . 'admin/js/unified-test-dashboard.js',
                [ 'jquery', 'chart-js' ],
                RTBCB_VERSION,
                true
            );

            wp_localize_script(
                'rtbcb-unified-dashboard',
                'rtbcbDashboard',
                [
                    'ajaxurl' => admin_url( 'admin-ajax.php' ),
                    'nonces'  => [
                        'dashboard'     => wp_create_nonce( 'rtbcb_unified_test_dashboard' ),
                        'llm'           => wp_create_nonce( 'rtbcb_llm_testing' ),
                        'apiHealth'     => wp_create_nonce( 'rtbcb_api_health_tests' ),
                        'reportPreview' => wp_create_nonce( 'rtbcb_generate_preview_report' ),
                        'dataHealth'    => wp_create_nonce( 'rtbcb_data_health_checks' ),
                        'ragTesting'    => wp_create_nonce( 'rtbcb_rag_testing' ),
                        'saveSettings'  => wp_create_nonce( 'rtbcb_save_dashboard_settings' ),
                        'roiCalculator' => wp_create_nonce( 'rtbcb_roi_calculator_test' ),
                        'debugApiKey'   => wp_create_nonce( 'rtbcb_debug_api_key' ),
                    ],
                    'strings' => [
                        'generating'       => __( 'Generating...', 'rtbcb' ),
                        'generateOverview' => __( 'Generate Overview', 'rtbcb' ),
                        'complete'         => __( 'Complete!', 'rtbcb' ),
                        'error'            => __( 'Error occurred', 'rtbcb' ),
                        'confirm_clear'    => __( 'Are you sure you want to clear all results?', 'rtbcb' ),
                        'running'          => __( 'Running...', 'rtbcb' ),
                        'retrieving'       => __( 'Retrieving...', 'rtbcb' ),
                        'notTested'        => __( 'Not tested', 'rtbcb' ),
                        'allOperational'   => __( 'All systems operational', 'rtbcb' ),
                        'errorsDetected'   => __( '%d errors detected', 'rtbcb' ),
                        'passed'           => __( 'Passed', 'rtbcb' ),
                        'failed'           => __( 'Failed', 'rtbcb' ),
                        'settings'         => __( 'Settings', 'rtbcb' ),
                        'noResults'        => __( 'No results found', 'rtbcb' ),
                        'indexRebuilt'     => __( 'Index rebuilt successfully.', 'rtbcb' ),
                        'rebuildFailed'    => __( 'Index rebuild failed.', 'rtbcb' ),
                        'noChecks'         => __( 'No checks run yet.', 'rtbcb' ),
                        'lastIndexed'      => __( 'Last indexed: %s', 'rtbcb' ),
                        'entries'          => __( 'Entries: %d', 'rtbcb' ),
                        'settingsSaved'    => __( 'Settings saved.', 'rtbcb' ),
                        'show'             => __( 'Show', 'rtbcb' ),
                        'hide'             => __( 'Hide', 'rtbcb' ),
                        'apiKeyRequired'   => __( 'Please save a valid OpenAI API key in the Settings tab before running tests.', 'rtbcb' ),
                        'apiKeyDebugInfo'  => __( 'API Key Debug Info', 'rtbcb' ),
                        'configured'       => __( 'Configured', 'rtbcb' ),
                        'length'           => __( 'Length', 'rtbcb' ),
                        'preview'          => __( 'Preview', 'rtbcb' ),
                        'formatValid'      => __( 'Format valid', 'rtbcb' ),
                        'yes'              => __( 'Yes', 'rtbcb' ),
                        'no'               => __( 'No', 'rtbcb' ),
                    ],
                    'models'  => [
                        'mini'     => get_option( 'rtbcb_mini_model', rtbcb_get_default_model( 'mini' ) ),
                        'premium'  => get_option( 'rtbcb_premium_model', rtbcb_get_default_model( 'premium' ) ),
                        'advanced' => get_option( 'rtbcb_advanced_model', rtbcb_get_default_model( 'advanced' ) ),
                    ],
                    'features' => [
                        'debugMode'                  => defined( 'WP_DEBUG' ) && WP_DEBUG,
                        'lastSuccessfulOpenAIPingAt' => get_option( 'rtbcb_openai_last_ok', 0 ),
                    ],
                    'apiHealth' => [
                        'lastResults' => get_option( 'rtbcb_last_api_test', [] ),
                    ],
                    'circuitBreaker' => [
                        'threshold' => (int) get_option( 'rtbcb_cb_threshold', 5 ),
                        'resetTime' => (int) get_option( 'rtbcb_cb_reset_time', 60000 ),
                    ],
                    'urls'     => [
                        'settings' => admin_url( 'admin.php?page=' . RTBCB_UNIFIED_TESTS_SLUG . '#settings' ),
                    ],
                ]
            );

            wp_enqueue_script( 'rtbcb-unified-dashboard' );
        }
    }

    /**
     * Register admin menu and submenus.
     *
     * @return void
     */
    public function add_admin_menu() {
        add_menu_page(
            __( 'Business Case Builder', 'rtbcb' ),
            __( 'Real Treasury', 'rtbcb' ),
            'manage_options',
            'rtbcb-dashboard',
            [ $this, 'render_dashboard' ],
            'dashicons-calculator',
            30
        );

        add_submenu_page(
            'rtbcb-dashboard',
            __( 'Dashboard', 'rtbcb' ),
            __( 'Dashboard', 'rtbcb' ),
            'manage_options',
            'rtbcb-dashboard',
            [ $this, 'render_dashboard' ]
        );

        add_submenu_page(
            'rtbcb-dashboard',
            __( 'Leads', 'rtbcb' ),
            __( 'Leads', 'rtbcb' ),
            'manage_options',
            'rtbcb-leads',
            [ $this, 'render_leads' ]
        );

        add_submenu_page(
            'rtbcb-dashboard',
            __( 'Analytics', 'rtbcb' ),
            __( 'Analytics', 'rtbcb' ),
            'manage_options',
            'rtbcb-analytics',
            [ $this, 'render_analytics' ]
        );

        add_submenu_page(
            'rtbcb-dashboard',
            __( 'Unified Test Dashboard', 'rtbcb' ),
            __( 'Unified Tests', 'rtbcb' ),
            'manage_options',
            RTBCB_UNIFIED_TESTS_SLUG,
            [ $this, 'render_unified_test_dashboard' ]
        );

    }

    /**
     * Render enhanced dashboard with statistics.
     *
     * @return void
     */
    public function render_dashboard() {
        $stats = RTBCB_Leads::get_statistics();
        $recent_leads = RTBCB_Leads::get_all_leads( [ 'per_page' => 5, 'orderby' => 'created_at', 'order' => 'DESC' ] );
        
        include RTBCB_DIR . 'admin/dashboard-page.php';
    }

    /**
     * Render enhanced leads page with filtering and export.
     *
     * @return void
     */
    public function render_leads() {
        $page = isset( $_GET['paged'] ) ? intval( wp_unslash( $_GET['paged'] ) ) : 1;
        $search = isset( $_GET['search'] ) ? sanitize_text_field( wp_unslash( $_GET['search'] ) ) : '';
        $category = isset( $_GET['category'] ) ? sanitize_text_field( wp_unslash( $_GET['category'] ) ) : '';
        $date_from = isset( $_GET['date_from'] ) ? sanitize_text_field( wp_unslash( $_GET['date_from'] ) ) : '';
        $date_to = isset( $_GET['date_to'] ) ? sanitize_text_field( wp_unslash( $_GET['date_to'] ) ) : '';

        $orderby = isset( $_GET['orderby'] ) ? sanitize_key( wp_unslash( $_GET['orderby'] ) ) : 'created_at';
        $allowed_orderby = [ 'email', 'created_at' ];
        if ( ! in_array( $orderby, $allowed_orderby, true ) ) {
            $orderby = 'created_at';
        }

        $order = isset( $_GET['order'] ) ? sanitize_key( wp_unslash( $_GET['order'] ) ) : 'DESC';
        $order = strtoupper( $order );
        if ( ! in_array( $order, [ 'ASC', 'DESC' ], true ) ) {
            $order = 'DESC';
        }

        $args = [
            'page'      => $page,
            'search'    => $search,
            'category'  => $category,
            'date_from' => $date_from,
            'date_to'   => $date_to,
            'orderby'   => $orderby,
            'order'     => $order,
        ];

        $leads_data = RTBCB_Leads::get_all_leads( $args );
        $categories = RTBCB_Category_Recommender::get_all_categories();
        
        include RTBCB_DIR . 'admin/leads-page-enhanced.php';
    }

    /**
     * Render analytics page with charts and insights.
     *
     * @return void
     */
    public function render_analytics() {
        $stats = RTBCB_Leads::get_statistics();
        $monthly_trends = $this->get_monthly_trends();
        
        include RTBCB_DIR . 'admin/analytics-page.php';
    }

    /**
     * Render unified test dashboard page.
     *
     * @return void
     */
    public function render_unified_test_dashboard() {
        include RTBCB_DIR . 'admin/unified-test-dashboard-page.php';
    }

    /**
     * Handle dashboard settings form submission.
     *
     * @return void
     */
    public function handle_dashboard_settings_form() {
        if ( ! check_admin_referer( 'rtbcb_save_dashboard_settings', 'nonce' ) ) {
            wp_safe_redirect( add_query_arg( 'settings-status', 'invalid_nonce', wp_get_referer() ) );
            exit;
        }

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_safe_redirect( add_query_arg( 'settings-status', 'no_permission', wp_get_referer() ) );
            exit;
        }

        $openai_key = isset( $_POST['rtbcb_openai_api_key'] ) ? sanitize_text_field( wp_unslash( $_POST['rtbcb_openai_api_key'] ) ) : '';

        if ( $openai_key && ! rtbcb_is_valid_openai_api_key( $openai_key ) ) {
            wp_safe_redirect( add_query_arg( 'settings-status', 'invalid_api_key', wp_get_referer() ) );
            exit;
        }

        update_option( 'rtbcb_openai_api_key', $openai_key );

        $fields = [
            'rtbcb_mini_model'      => 'sanitize_text_field',
            'rtbcb_premium_model'   => 'sanitize_text_field',
            'rtbcb_advanced_model'  => 'sanitize_text_field',
            'rtbcb_embedding_model' => 'sanitize_text_field',
            'rtbcb_cb_threshold'    => 'absint',
            'rtbcb_cb_reset_time'   => 'absint',
        ];

        foreach ( $fields as $option => $sanitize ) {
            $value = isset( $_POST[ $option ] ) ? call_user_func( $sanitize, wp_unslash( $_POST[ $option ] ) ) : '';
            update_option( $option, $value );
        }

        wp_safe_redirect(
            add_query_arg(
                'settings-status',
                'success',
                admin_url( 'admin.php?page=' . RTBCB_UNIFIED_TESTS_SLUG )
            )
        );
        exit;
    }

    /**
     * Export leads to CSV.
     *
     * @return void
     */
    public function export_leads_csv() {
        check_ajax_referer( 'rtbcb_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => __( 'Permission denied.', 'rtbcb' ) ], 403 );
        }

        $filters = [
            'search'    => sanitize_text_field( wp_unslash( $_POST['search'] ?? '' ) ),
            'category'  => sanitize_text_field( wp_unslash( $_POST['category'] ?? '' ) ),
            'date_from' => sanitize_text_field( wp_unslash( $_POST['date_from'] ?? '' ) ),
            'date_to'   => sanitize_text_field( wp_unslash( $_POST['date_to'] ?? '' ) ),
        ];

        $csv_content = RTBCB_Leads::export_to_csv( $filters );
        $filename = 'rtbcb-leads-' . date( 'Y-m-d' ) . '.csv';

        wp_send_json_success( [
            'content'  => $csv_content,
            'filename' => $filename,
        ] );
    }

    /**
     * Delete a single lead.
     *
     * @return void
     */
    public function delete_lead() {
        check_ajax_referer( 'rtbcb_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => __( 'Permission denied.', 'rtbcb' ) ], 403 );
        }

        $lead_id = intval( wp_unslash( $_POST['lead_id'] ?? 0 ) );
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'rtbcb_leads';
        
        $result = $wpdb->delete( $table_name, [ 'id' => $lead_id ], [ '%d' ] );

        if ( $result ) {
            wp_send_json_success( [ 'message' => __( 'Lead deleted successfully.', 'rtbcb' ) ] );
        } else {
            wp_send_json_error( [ 'message' => __( 'Failed to delete lead.', 'rtbcb' ) ] );
        }
    }

    /**
     * Handle bulk actions on leads.
     *
     * @return void
     */
    public function bulk_action_leads() {
        check_ajax_referer( 'rtbcb_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => __( 'Permission denied.', 'rtbcb' ) ], 403 );
        }

        $action = sanitize_text_field( wp_unslash( $_POST['action'] ?? '' ) );
        $lead_ids = array_map( 'intval', wp_unslash( $_POST['lead_ids'] ?? [] ) );

        if ( empty( $lead_ids ) ) {
            wp_send_json_error( [ 'message' => __( 'No leads selected.', 'rtbcb' ) ] );
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'rtbcb_leads';
        $placeholders = implode( ',', array_fill( 0, count( $lead_ids ), '%d' ) );

        switch ( $action ) {
            case 'delete':
                $result = $wpdb->query( 
                    $wpdb->prepare( 
                        "DELETE FROM {$table_name} WHERE id IN ({$placeholders})", 
                        $lead_ids 
                    ) 
                );
                
                if ( $result ) {
                    wp_send_json_success( [ 
                        'message' => sprintf( __( '%d leads deleted successfully.', 'rtbcb' ), $result ) 
                    ] );
                } else {
                    wp_send_json_error( [ 'message' => __( 'Failed to delete leads.', 'rtbcb' ) ] );
                }
                break;

            default:
                wp_send_json_error( [ 'message' => __( 'Invalid action.', 'rtbcb' ) ] );
        }
    }


    /**
     * Generate ROI assumptions based on inputs.
     *
     * @param array $inputs Calculator inputs.
     * @return array List of assumptions.
     */
    private function generate_roi_assumptions( $inputs ) {
        $assumptions = [
            sprintf(
                __( 'Treasury staff will save %d hours per day through automation', 'rtbcb' ),
                round( $inputs['total_daily_hours'] * 0.25, 1 )
            ),
            sprintf(
                __( 'Banking fees will be reduced by 15%% through optimization (current: %s)', 'rtbcb' ),
                '$' . number_format( $inputs['monthly_bank_fees'] * 12 )
            ),
            sprintf(
                __( 'Error frequency will be reduced by 50%% (current: %d per week)', 'rtbcb' ),
                $inputs['error_frequency']
            ),
            __( 'Implementation will be completed within 6 months', 'rtbcb' ),
            __( 'Staff utilization efficiency will improve by 5%', 'rtbcb' ),
        ];

        switch ( $inputs['industry'] ) {
            case 'manufacturing':
                $assumptions[] = __( 'Working capital optimization will improve cash flow by 2%', 'rtbcb' );
                break;
            case 'technology':
                $assumptions[] = __( 'Rapid scaling will require automated treasury processes', 'rtbcb' );
                break;
            case 'financial-services':
                $assumptions[] = __( 'Regulatory compliance will be streamlined through automation', 'rtbcb' );
                break;
        }

        return $assumptions;
    }

    /**
     * Perform sensitivity analysis on key variables.
     *
     * @param array $base_inputs Base calculator inputs.
     * @param array $base_roi    Base ROI results.
     * @return array Sensitivity analysis results.
     */
    private function perform_sensitivity_analysis( $base_inputs, $base_roi ) {
        $sensitive_variables = [
            'treasury_staff_count' => 'Treasury Staff Count',
            'average_salary'       => 'Average Salary',
            'hours_reconciliation' => 'Reconciliation Hours',
            'monthly_bank_fees'    => 'Monthly Bank Fees',
            'error_frequency'      => 'Error Frequency',
            'avg_error_cost'       => 'Average Error Cost',
        ];

        $base_roi_percentage = $base_roi['realistic']['roi_percentage'] ?? 0;
        $variations          = [ -0.2, -0.1, 0.1, 0.2 ];

        $sensitivity_results = [
            'base_roi'   => $base_roi_percentage,
            'sensitivity'=> [],
        ];

        foreach ( $sensitive_variables as $variable => $label ) {
            $base_value       = $base_inputs[ $variable ];
            $variable_results = [
                'base_value' => $base_value,
                'base'       => $base_roi_percentage,
                'sensitivity'=> 0,
            ];

            $roi_changes = [];

            foreach ( $variations as $variation ) {
                $modified_inputs               = $base_inputs;
                $modified_inputs[ $variable ] = $base_value * ( 1 + $variation );

                // Quick ROI calculation for sensitivity.
                $quick_roi     = $this->quick_roi_calculation( $modified_inputs );
                $variation_key = ( $variation > 0 ? '+' : '' ) . ( $variation * 100 ) . '%';
                $variable_results[ $variation_key ] = round( $quick_roi, 1 );
                $roi_changes[] = abs( $quick_roi - $base_roi_percentage ) / abs( $variation * 100 );
            }

            // Calculate sensitivity (average change in ROI per 1% change in variable).
            $variable_results['sensitivity'] = round( array_sum( $roi_changes ) / count( $roi_changes ), 2 );

            $sensitivity_results['sensitivity'][ $variable ] = $variable_results;
        }

        return $sensitivity_results;
    }

    /**
     * Quick ROI calculation for sensitivity analysis.
     *
     * @param array $inputs Modified inputs.
     * @return float ROI percentage.
     */
    private function quick_roi_calculation( $inputs ) {
        // Simplified ROI calculation for speed.
        $annual_labor_cost = $inputs['treasury_staff_count'] * $inputs['average_salary'];
        $hourly_labor_cost = $annual_labor_cost / ( 365 * 8 );

        $benefits = $inputs['total_daily_hours'] * $hourly_labor_cost * 365 * 0.25 +
            ( $inputs['monthly_bank_fees'] * 12 ) * 0.15 +
            $inputs['error_frequency'] * $inputs['avg_error_cost'] * 52 * 0.50;

        $costs = 50000 * $this->get_company_size_multiplier( $inputs['company_size'] ) * 1.9; // Total annual cost.

        return $costs > 0 ? ( $benefits / $costs ) * 100 : 0;
    }

    /**
     * Get company size multiplier for pricing.
     *
     * @param string $company_size Company size category.
     * @return float Multiplier.
     */
    private function get_company_size_multiplier( $company_size ) {
        $multipliers = [
            'startup'    => 0.5,
            'small'      => 0.8,
            'medium'     => 1.0,
            'large'      => 1.5,
            'enterprise' => 2.5,
        ];

        return $multipliers[ $company_size ] ?? 1.0;
    }

    /**
     * Run comprehensive diagnostics to identify issues.
     *
     * @return array Diagnostic results.
     */
    public static function run_comprehensive_diagnostics() {
        $diagnostics = [];

        // 1. Check PHP Version
        $diagnostics['php_version'] = [
            'version'            => PHP_VERSION,
            'meets_requirement'  => version_compare( PHP_VERSION, '7.4', '>=' ),
            'status'             => version_compare( PHP_VERSION, '7.4', '>=' ) ? 'OK' : 'FAIL',
        ];

        // 2. Check WordPress Version
        $wp_version = get_bloginfo( 'version' );
        $diagnostics['wp_version'] = [
            'version'            => $wp_version,
            'meets_requirement'  => version_compare( $wp_version, '5.0', '>=' ),
            'status'             => version_compare( $wp_version, '5.0', '>=' ) ? 'OK' : 'FAIL',
        ];

        // 3. Check Database Connection
        global $wpdb;
        try {
            $wpdb->get_var( 'SELECT 1' );
            $diagnostics['database'] = [
                'status'  => 'OK',
                'message' => __( 'Database connection successful', 'rtbcb' ),
            ];
        } catch ( Exception $e ) {
            $diagnostics['database'] = [
                'status'  => 'FAIL',
                'message' => sprintf( __( 'Database connection failed: %s', 'rtbcb' ), $e->getMessage() ),
            ];
        }

        // 4. Check Required Classes
        $required_classes = [
            'RTBCB_Calculator',
            'RTBCB_Category_Recommender',
            'RTBCB_LLM',
            'RTBCB_RAG',
            'RTBCB_Leads',
            'RTBCB_Validator',
        ];

        foreach ( $required_classes as $class ) {
            $diagnostics['classes'][ $class ] = [
                'exists' => class_exists( $class ),
                'status' => class_exists( $class ) ? 'OK' : 'FAIL',
            ];
        }

        // 5. Check Database Tables
        $table_name = $wpdb->prefix . 'rtbcb_leads';
        $table_exists = $wpdb->get_var(
            $wpdb->prepare(
                'SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = %s AND table_name = %s',
                DB_NAME,
                $table_name
            )
        );

        $diagnostics['database_tables']['rtbcb_leads'] = [
            'exists' => (bool) $table_exists,
            'status' => $table_exists ? 'OK' : 'FAIL',
        ];

        // 6. Check Table Structure
        if ( $table_exists ) {
            $columns = $wpdb->get_results( "DESCRIBE {$table_name}" );
            $expected_columns = [
                'id',
                'email',
                'company_size',
                'industry',
                'hours_reconciliation',
                'hours_cash_positioning',
                'num_banks',
                'ftes',
                'pain_points',
                'recommended_category',
                'roi_low',
                'roi_base',
                'roi_high',
                'report_html',
                'ip_address',
                'user_agent',
                'utm_source',
                'utm_medium',
                'utm_campaign',
                'created_at',
                'updated_at',
            ];

            $actual_columns  = array_column( $columns, 'Field' );
            $missing_columns = array_diff( $expected_columns, $actual_columns );

            $diagnostics['table_structure'] = [
                'expected_columns' => count( $expected_columns ),
                'actual_columns'   => count( $actual_columns ),
                'missing_columns'  => $missing_columns,
                'status'           => empty( $missing_columns ) ? 'OK' : 'FAIL',
            ];
        }

        // 7. Check OpenAI API Configuration
        $api_key      = get_option( 'rtbcb_openai_api_key' );
        $configured   = ! empty( $api_key );
        $valid_format = $configured ? rtbcb_is_valid_openai_api_key( $api_key ) : false;
        $api_test     = RTBCB_API_Tester::test_connection();

        $status = $api_test['success'] ? 'OK' : 'FAIL';
        if ( $configured && ! $valid_format ) {
            $status = 'INVALID_FORMAT';
        }

        $diagnostics['openai_api'] = [
            'configured'   => $configured,
            'valid_format' => $valid_format,
            'success'      => $api_test['success'],
            'message'      => $api_test['message'],
            'details'      => $api_test['details'] ?? '',
            'status'       => $status,
        ];

        // 8. Check Memory Limit
        $memory_limit = ini_get( 'memory_limit' );
        $memory_bytes = wp_convert_hr_to_bytes( $memory_limit );
        $diagnostics['memory_limit'] = [
            'current'    => $memory_limit,
            'bytes'      => $memory_bytes,
            'sufficient' => $memory_bytes >= 128 * 1024 * 1024,
            'status'     => $memory_bytes >= 128 * 1024 * 1024 ? 'OK' : 'LOW',
        ];

        // 9. Check Error Logging
        $diagnostics['error_logging'] = [
            'wp_debug'      => defined( 'WP_DEBUG' ) && WP_DEBUG,
            'wp_debug_log'  => defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG,
            'log_errors'    => ini_get( 'log_errors' ),
            'error_log_path'=> ini_get( 'error_log' ),
        ];

        // 10. Test Basic Functionality
        try {
            $test_inputs = rtbcb_get_sample_inputs();

            if ( class_exists( 'RTBCB_Calculator' ) ) {
                $roi_result = RTBCB_Calculator::calculate_roi( $test_inputs );
                $diagnostics['functionality']['calculator'] = [
                    'status' => ! empty( $roi_result ) ? 'OK' : 'FAIL',
                    'result' => ! empty( $roi_result ),
                ];
            }

            if ( class_exists( 'RTBCB_Category_Recommender' ) ) {
                $recommendation = RTBCB_Category_Recommender::recommend_category( $test_inputs );
                $diagnostics['functionality']['recommender'] = [
                    'status' => ! empty( $recommendation ) ? 'OK' : 'FAIL',
                    'result' => ! empty( $recommendation ),
                ];
            }
        } catch ( Exception $e ) {
            $diagnostics['functionality']['error'] = $e->getMessage();
        } catch ( Error $e ) {
            $diagnostics['functionality']['fatal_error'] = $e->getMessage();
        }

        return $diagnostics;
    }

}
